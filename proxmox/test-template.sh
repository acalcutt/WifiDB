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
    if "$@" >/dev/null 2>&1; then ok "$desc"; else bad "$desc"; return 1; fi
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
           && case "$(docker exec "$ct" systemctl is-system-running 2>/dev/null)" in running|degraded) true ;; *) false ;; esac; then return 0; fi
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
    check "$svc is active" docker exec "$ct1" systemctl is-active --quiet "$svc" || {
        docker exec "$ct1" systemctl status "$svc" --no-pager 2>&1 | head -12 | sed "s/^/       /"
        docker exec "$ct1" journalctl -u "$svc" --no-pager 2>&1 | tail -12 | sed "s/^/       /"
    }
done
check "php-fpm is active" docker exec "$ct1" sh -c 'systemctl is-active --quiet "php$(php -r "echo PHP_MAJOR_VERSION.\".\".PHP_MINOR_VERSION;")-fpm"'
check "the site answers 200 on /" test "$(http "$ct1" http://127.0.0.1/)" = 200
# A page can answer 200 and still carry a PHP error box (PHP 8 made an undefined constant fatal). Look at the text.
# (and an error can also be an empty 500, so the status counts too)
for page in / /login.php /stats.php /opt/map.php /opt/scheduling.php /opt/results.php /opt/userstats.php /opt/export.php /cp/ /login.php?func=create_user_form /import/; do
    check "no PHP error on $page" sh -c 'test "$(docker exec "$0" curl -s -L -o /dev/null -w "%{http_code}" -H "Cookie: wifidb_client_check=1; wifidb_client_timezone=0" "http://127.0.0.1$1")" = 200 && ! docker exec "$0" curl -s -L -H "Cookie: wifidb_client_check=1; wifidb_client_timezone=0" "http://127.0.0.1$1" | grep -qE "Undefined (constant|variable|array key)|Fatal error|Uncaught|Parse error|Stack trace|Call to undefined"' "$ct1" "$page"
done
check "/health answers 200" test "$(http "$ct1" http://127.0.0.1/health)" = 200
check "/health/ready answers 200 with the database up" test "$(http "$ct1" http://127.0.0.1/health/ready)" = 200
# The first line a WebSocket server answers a handshake with: "HTTP/1.1 101 Switching Protocols"
ws_status() {
    docker exec "$ct1" curl -s -m 5 -i \
        -H 'Connection: Upgrade' -H 'Upgrade: websocket' -H 'Sec-WebSocket-Version: 13' \
        -H 'Sec-WebSocket-Key: dGhlIHNhbXBsZSBub25jZQ==' "$1" | head -1
}
check "the websocket daemon accepts a handshake" test "$(ws_status http://127.0.0.1:9000/wifidb/api/Scheduling | grep -c 101)" = 1
check "...and Apache passes /ws/Scheduling to it" test "$(ws_status http://127.0.0.1/ws/Scheduling | grep -c 101)" = 1
check "uploads are not served" test "$(http "$ct1" http://127.0.0.1/import/up/.htaccess)" = 403
# The signal graph: an address as Vistumbler builds it (spaces are %20 here only because curl wants a valid URL)
vq='SSID=My%20Net&Mac=00:11:22:33:44:55&Manuf=Cisco&Auth=WPA2-Personal&Encry=CCMP&radio=802.11n&Chn=6&Lat=N%2043.1234&Long=W%2070.1234&BTx=54&OTx=54&FA=2024-01-02%2010:11:12&LU=2024-01-02%2010:12:12&NT=Infrastructure&Label=Unknown&Sig=0-45-50-0-0-62-70'
check "signal.png is a real PNG image" sh -c 'docker exec "$0" curl -s "http://127.0.0.1/api/wifi/signal.png?$1" | head -c 8 | od -An -c | tr -d " " | grep -q "211PNG"' "$ct1" "$vq"
check "...served as image/png" sh -c 'docker exec "$0" curl -s -o /dev/null -w "%{content_type}" "http://127.0.0.1/api/wifi/signal.png?$1" | grep -q "^image/png"' "$ct1" "$vq"
check "signal.svg is an SVG with the access point in it" sh -c 'docker exec "$0" curl -s "http://127.0.0.1/api/wifi/signal.svg?$1" | grep -q "SSID: My Net"' "$ct1" "$vq"
check "the old graph page (index.php) accepts Vistumbler's address" test "$(http "$ct1" "http://127.0.0.1/api/wifi/index.php?$vq")" = 200
check "...and does not echo a script from the address" sh -c '! docker exec "$0" curl -s "http://127.0.0.1/api/wifi/index.php?SSID=%3Cscript%3Ealert(1)%3C/script%3E&Sig=1-2" | grep -q "<script>alert"' "$ct1"
# The API's own host name (api.wifidb.net on wifidb.net): api.wifidb.test here, from WIFIDB_URL
check "api.<site> serves the API at its top" sh -c 'docker exec "$0" curl -s -H "Host: api.wifidb.test" http://127.0.0.1/index.php | grep -q "WiFiDB API Version"' "$ct1"
check "...Vistumbler's signal graph too" sh -c 'docker exec "$0" curl -s -H "Host: api.wifidb.test" "http://127.0.0.1/wifi/signal.png?$1" | head -c 8 | od -An -c | tr -d " " | grep -q "211PNG"' "$ct1" "$vq"
check "...and the site is still what any other name gets" sh -c '! docker exec "$0" curl -s -H "Host: wifidb.test" http://127.0.0.1/index.php | grep -q "WiFiDB API Version" && ! docker exec "$0" curl -s -H "Host: other.example" http://127.0.0.1/index.php | grep -q "WiFiDB API Version"' "$ct1"
check "the graph writes nothing to a tmp folder" docker exec "$ct1" sh -c 'test -z "$(ls /var/www/wifidb/api/wifi/tmp 2>/dev/null)"'
# An access point from the database: ?ap=
docker exec "$db" mariadb -uwifidb -ptestpw wifi -e "
  INSERT INTO wifi_ap (AP_ID, BSSID, SSID, CHAN, AUTH, ENCR, RADTYPE, NETTYPE) VALUES (4242, '00:11:22:33:44:55', 'Test <AP>', 6, 'WPA2', 'CCMP', '802.11n', 'Infrastructure');
  INSERT INTO wifi_hist (Hist_ID, AP_ID, Sig, Hist_Date) VALUES (880001, 4242, 30, '2024-01-01 00:00:01'), (880002, 4242, 70, '2024-01-01 00:00:02'), (880003, 4242, 50, '2024-01-01 00:00:03');" >/dev/null 2>&1
check "signal.png?ap= draws a stored access point" sh -c 'docker exec "$0" curl -s "http://127.0.0.1/api/wifi/signal.png?ap=4242" | head -c 8 | od -An -c | tr -d " " | grep -q "211PNG"' "$ct1"
check "signal.svg?ap= shows its name, escaped" sh -c 'docker exec "$0" curl -s "http://127.0.0.1/api/wifi/signal.svg?ap=4242" | grep -q "SSID: Test &lt;AP&gt;"' "$ct1"
check "an access point that does not exist is a 404" test "$(http "$ct1" "http://127.0.0.1/api/wifi/signal.png?ap=999999")" = 404
check "a non-numeric ap is a 404, not an error" test "$(http "$ct1" "http://127.0.0.1/api/wifi/signal.png?ap=1%20OR%201=1")" = 404
check "the schema was loaded" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select count(*) from information_schema.tables where table_schema=\"wifi\"")" -gt 20'
check "this node is named testnode" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select node_name from settings limit 1")" = testnode'
check "admin jobs will run here (an Admin schedule row, migration 0006)" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select count(*) from schedule where nodename = '"'"'testnode'"'"' and daemon = '"'"'Admin'"'"' and enabled = 1")" = 1'
check "schedulecheck prints a number" docker exec "$ct1" sh -c 'out=$(su -s /bin/bash www-data -c "php /opt/wifidb/tools/daemon/schedulecheck.php Import"); echo "$out"; case "$out" in (*[!0-9]*|"") exit 1;; esac'
check "the cron file is installed" docker exec "$ct1" sh -c 'test "$(grep -c update_mvt /etc/cron.d/wifidb)" -ge 20'
check "a cron wrapper runs" docker exec "$ct1" su -s /bin/bash www-data -c '/opt/wifidb/tools/cron/import_files'
check "uploads are on the data disk, the upload page isn't" docker exec "$ct1" sh -c 'test -L /var/www/wifidb/import/up && test -d /srv/wifidb-data/import/up && test ! -L /var/www/wifidb/import'
check "logs are on the data disk" docker exec "$ct1" sh -c 'test -L /opt/wifidb/tools/log && ls /srv/wifidb-data/log/import'
for dir in /var/www/wifidb/import/up /var/www/wifidb/out/tiles /var/www/wifidb/out/pmtiles /srv/wifidb-data/log/import /var/www/wifidb/smarty/templates_c; do
    check "www-data can write to $dir" docker exec "$ct1" su -s /bin/bash www-data -c "f=$dir/.write-test-\$\$ && echo x > \$f && rm \$f"
done
check "the config files are not world-readable" docker exec "$ct1" sh -c 'test "$(stat -c %a /var/www/wifidb/lib/config.inc.php)" = 640'
check "the proxy's X-Forwarded-For is trusted" docker exec "$ct1" grep -q 'RemoteIPInternalProxy 10.0.0.1' /etc/apache2/conf-enabled/wifidb-proxy.conf
check "no password is baked into the code tree" docker exec "$ct1" sh -c '! grep -rq testpw /var/www/wifidb/lib/config.inc.php'
# DKIM: the helper makes a key with the right permissions, prints a record that parses, and never overwrites a key
check "wifidb-dkim generate makes a key only root and www-data can read" docker exec "$ct1" sh -c 'wifidb-dkim generate testsel >/dev/null && test "$(stat -c "%a %U:%G" /srv/wifidb-data/config/dkim/testsel.pem)" = "640 root:www-data"'
check "...and prints a DNS record that carries its public key" sh -c 'docker exec "$0" wifidb-dkim show testsel | grep -q "v=DKIM1; k=rsa; p=MII"' "$ct1"
check "...and will not replace a key that exists" sh -c '! docker exec "$0" wifidb-dkim generate testsel >/dev/null 2>&1' "$ct1"
check "the key is readable by the web server's user" docker exec "$ct1" su -s /bin/bash www-data -c 'test -r /srv/wifidb-data/config/dkim/testsel.pem'
check "check_mail.php runs, and reports the placeholder mail settings as failed" sh -c 'out=$(docker exec "$0" su -s /bin/bash www-data -c "php /opt/wifidb/tools/utilites/check_mail.php" 2>&1); echo "$out" | grep -q "\[FAIL\]" && ! echo "$out" | grep -qi "fatal\|Uncaught"' "$ct1"
check "Smarty is the Composer-pinned version" docker exec "$ct1" sh -c 'grep -q "4.5.8" /var/www/wifidb/vendor/smarty/smarty/libs/Smarty.class.php'
check "dev tools (PHPUnit) are not in the container" docker exec "$ct1" test ! -e /var/www/wifidb/vendor/phpunit

. "$(dirname "$(readlink -f "$0")")/test-two-factor.inc.sh"
. "$(dirname "$(readlink -f "$0")")/test-passkey.inc.sh"
. "$(dirname "$(readlink -f "$0")")/test-backups.inc.sh"
ct=$ct1; sqlq() { docker exec "$db" mariadb -uwifidb -ptestpw wifi -N -e "$1" | tr "	" "|"; }
if [ -n "${IMPORT_SAMPLES:-}" ]; then
    . "$(dirname "$(readlink -f "$0")")/test-imports.inc.sh"
fi
. "$(dirname "$(readlink -f "$0")")/test-utilities.inc.sh"
. "$(dirname "$(readlink -f "$0")")/test-nodelock.inc.sh"
. "$(dirname "$(readlink -f "$0")")/test-migrations.inc.sh"

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

log "stopping the database: /health/ready must say so, and /health must not care"
docker stop "$db" >/dev/null
check "/health/ready answers 503 with the database down" test "$(http "$ct2" http://127.0.0.1/health/ready)" = 503
check "...and says why, without naming the server" sh -c 'out=$(docker exec "$0" curl -s http://127.0.0.1/health/ready); echo "$out" | grep -q "database unreachable" && ! echo "$out" | grep -q "wifidb-test-db"' "$ct2"
check "/health still answers 200 (it is only Apache)" test "$(http "$ct2" http://127.0.0.1/health)" = 200

if [ "$fail" = 0 ]; then log "all checks passed"; else log "SOME CHECKS FAILED"; fi
exit "$fail"
