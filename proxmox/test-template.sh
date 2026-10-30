#!/bin/bash
# Boot-test a built WiFiDB template against a real MariaDB: first boot, the site, services, cron file, and an
# upgrade (a second container on the same data disk).
#
# usage (as root, on a Linux host with Docker):  proxmox/test-template.sh TEMPLATE.tar.zst
set -uo pipefail
template=$(readlink -f "$1")
id=$$
net=wifidb-test-net-$id
vol=wifidb-test-data-$id
db=wifidb-test-db-$id
image=wifidb-template-test:$id
fail=0

log() { echo "[$(date +%H:%M:%S)] $*"; }
ok() { echo "  ok   $*"; }
bad() { echo "  FAIL $*"; fail=1; }
check() { # check DESCRIPTION COMMAND...
    local desc=$1; shift
    if "$@" >/dev/null 2>&1; then ok "$desc"; else bad "$desc"; fi
}
cleanup() {
    docker rm -f "wifidb-test-ct1-$id" "wifidb-test-ct2-$id" "$db" >/dev/null 2>&1 || true
    docker volume rm "$vol" >/dev/null 2>&1 || true
    docker network rm "$net" >/dev/null 2>&1 || true
    docker rmi "$image" >/dev/null 2>&1 || true
}
trap cleanup EXIT

log "importing the template"
zstd -dc "$template" | docker import --change 'CMD ["/sbin/init"]' - "$image" >/dev/null

log "starting MariaDB"
docker network create "$net" >/dev/null
docker volume create "$vol" >/dev/null
docker run -d --name "$db" --network "$net" \
    -e MARIADB_ROOT_PASSWORD=rootpw -e MARIADB_DATABASE=wifi -e MARIADB_USER=wifidb -e MARIADB_PASSWORD=testpw \
    mariadb:11 >/dev/null
for _ in $(seq 60); do
    docker exec "$db" mariadb -uwifidb -ptestpw wifi -e 'select 1' >/dev/null 2>&1 && break
    sleep 2
done

start_container() { # start_container NAME
    local ct=$1
    docker create --name "$ct" --hostname wifidb-test --network "$net" --privileged \
        --cgroupns=private --tmpfs /run --tmpfs /run/lock -v "$vol:/srv/wifidb-data" "$image" >/dev/null
    printf '%s\n' \
        'WIFIDB_DB_TYPE=mariadb' "WIFIDB_DB_HOST=$db" 'WIFIDB_DB_NAME=wifi' 'WIFIDB_DB_USER=wifidb' 'WIFIDB_DB_PASS=testpw' \
        'WIFIDB_URL=https://wifidb.test' 'WIFIDB_NODE=testnode' 'WIFIDB_EXTERNAL_PROXY=10.0.0.1' \
        > /tmp/firstboot.env.$id
    docker cp "/tmp/firstboot.env.$id" "$ct:/etc/wifidb/firstboot.env"
    rm -f "/tmp/firstboot.env.$id"
    docker start "$ct" >/dev/null
}
wait_firstboot() { # wait_firstboot NAME
    local ct=$1
    for _ in $(seq 120); do
        if docker exec "$ct" test ! -e /etc/wifidb/firstboot-pending 2>/dev/null \
           && docker exec "$ct" systemctl is-system-running >/dev/null 2>&1; then return 0; fi
        sleep 3
    done
    docker exec "$ct" journalctl -u wifidb-firstboot --no-pager | tail -40
    return 1
}
http() { docker exec "$1" curl -s -o /dev/null -w '%{http_code}' "${@:2}"; }

ct1=wifidb-test-ct1-$id
log "first boot"
start_container "$ct1"
if wait_firstboot "$ct1"; then ok "first boot finished"; else bad "first boot did not finish"; fi

log "checking the first container"
for svc in apache2 cron wifidb-monitord wifidb-websocketd; do
    check "$svc is active" docker exec "$ct1" systemctl is-active --quiet "$svc"
done
check "php-fpm is active" docker exec "$ct1" sh -c 'systemctl is-active --quiet "php$(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;")-fpm"'
check "the site answers 200 on /" test "$(http "$ct1" http://127.0.0.1/)" = 200
check "/health answers 200" test "$(http "$ct1" http://127.0.0.1/health)" = 200
check "uploads are not served" test "$(http "$ct1" http://127.0.0.1/import/up/.htaccess)" = 403
check "the schema was loaded" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select count(*) from information_schema.tables where table_schema=\"wifi\"")" -gt 20'
check "this node is named testnode" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select node_name from settings limit 1")" = testnode'
check "schedulecheck prints a number" docker exec "$ct1" sh -c 'out=$(su -s /bin/bash www-data -c "php /opt/wifidb/tools/daemon/schedulecheck.php Import"); echo "$out"; case "$out" in (*[!0-9]*|"") exit 1;; esac'
check "the cron file is installed" docker exec "$ct1" sh -c 'test "$(grep -c update_mvt /etc/cron.d/wifidb)" -ge 20'
check "a cron wrapper runs" docker exec "$ct1" su -s /bin/bash www-data -c '/opt/wifidb/tools/cron/import_files'
check "uploads are on the data disk" docker exec "$ct1" sh -c 'test -L /var/www/wifidb/import && test -d /srv/wifidb-data/import/up'
check "logs are on the data disk" docker exec "$ct1" sh -c 'test -L /opt/wifidb/tools/log && ls /srv/wifidb-data/log/import'
check "the proxy's X-Forwarded-For is trusted" docker exec "$ct1" grep -q 'RemoteIPInternalProxy 10.0.0.1' /etc/apache2/conf-enabled/wifidb-proxy.conf
check "no password is baked into the code tree" docker exec "$ct1" sh -c '! grep -rq testpw /var/www/wifidb/lib/config.inc.php'

log "keeping a setting on the data disk, then moving to a new container"
docker exec "$ct1" sh -c 'echo "// marker" >> /srv/wifidb-data/config/web.overrides.php'
docker exec "$ct1" touch /srv/wifidb-data/import/up/uploaded-before-upgrade.vs1
docker stop -t 60 "$ct1" >/dev/null
docker rm "$ct1" >/dev/null

ct2=wifidb-test-ct2-$id
start_container "$ct2"
if wait_firstboot "$ct2"; then ok "second first boot finished"; else bad "second first boot did not finish"; fi
check "the settings on the data disk were kept" docker exec "$ct2" grep -q '// marker' /srv/wifidb-data/config/web.overrides.php
check "the upload is still there" docker exec "$ct2" test -f /srv/wifidb-data/import/up/uploaded-before-upgrade.vs1
check "the database was left alone" docker exec "$ct2" sh -c 'journalctl -u wifidb-firstboot --no-pager | grep -q "already has the WiFiDB tables"'
check "the site answers 200 again" test "$(http "$ct2" http://127.0.0.1/)" = 200

if [ "$fail" = 0 ]; then log "all checks passed"; else log "SOME CHECKS FAILED"; fi
exit "$fail"
