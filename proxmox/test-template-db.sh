#!/bin/bash
# Boot-test a built WiFiDB template against PostgreSQL (WIFIDB_DB_TYPE=pgsql): first boot loads blank_db.pgsql and the
# migrations, the pages show no PHP or SQL errors, a member signs in, a WiGLE file goes through the upload page and the
# importer, and a backup restores. The database user owns its database but isn't a superuser, as on a real server.
#
# usage (as root, on a Linux host with Docker):  proxmox/test-template-pgsql.sh TEMPLATE.tar.zst
set -uo pipefail
template=$(readlink -f "$1")
id=$$
net=wifidb-pgtest-net-$id
vol=wifidb-pgtest-data-$id
db=wifidb-pgtest-db-$id
ct=wifidb-pgtest-ct-$id
image=wifidb-template-pgtest:$id
fail=0

log() { echo "[$(date +%H:%M:%S)] $*"; }
ok() { echo "  ok   $*"; }
bad() { echo "  FAIL $*"; fail=1; }
check() { # check DESCRIPTION COMMAND...
    local desc=$1; shift
    if "$@" >/dev/null 2>&1; then ok "$desc"; else bad "$desc"; return 1; fi
}
cleanup() {
    docker rm -f "$ct" "$db" >/dev/null 2>&1 || true
    docker volume rm "$vol" >/dev/null 2>&1 || true
    docker network rm "$net" >/dev/null 2>&1 || true
    docker rmi "$image" >/dev/null 2>&1 || true
}
trap cleanup EXIT
psql_() { docker exec -e PGPASSWORD=testpw "$db" psql -h 127.0.0.1 -U wifidb -d wifi -At -c "$1"; }

log "importing the template"
zstd -dc "$template" | docker import --change 'CMD ["/sbin/init"]' - "$image" >/dev/null

log "starting PostgreSQL"
docker network create "$net" >/dev/null
docker volume create "$vol" >/dev/null
docker run -d --name "$db" --network "$net" -e POSTGRES_PASSWORD=rootpw postgres:16 >/dev/null
for _ in $(seq 60); do docker exec "$db" pg_isready -U postgres >/dev/null 2>&1 && break; sleep 2; done
sleep 2
docker exec "$db" psql -q -U postgres -c "CREATE ROLE wifidb LOGIN PASSWORD 'testpw'" -c "CREATE DATABASE wifi OWNER wifidb" >/dev/null

log "first boot"
docker create --name "$ct" --hostname wifidb-test --network "$net" --privileged \
    --cgroupns=private --tmpfs /run --tmpfs /run/lock -v "$vol:/srv/wifidb-data" "$image" >/dev/null
printf '%s\n' 'WIFIDB_DB_TYPE=pgsql' "WIFIDB_DB_HOST=$db" 'WIFIDB_DB_NAME=wifi' 'WIFIDB_DB_USER=wifidb' 'WIFIDB_DB_PASS=testpw' \
    'WIFIDB_URL=https://wifidb.test' 'WIFIDB_NODE=testnode' > /tmp/firstboot.env.$id
docker cp "/tmp/firstboot.env.$id" "$ct:/etc/wifidb/firstboot.env"; rm -f "/tmp/firstboot.env.$id"
docker start "$ct" >/dev/null
booted=0
for _ in $(seq 120); do
    if docker exec "$ct" test ! -e /etc/wifidb/firstboot-pending 2>/dev/null \
       && case "$(docker exec "$ct" systemctl is-system-running 2>/dev/null)" in running|degraded) true ;; *) false ;; esac; then booted=1; break; fi
    sleep 3
done
if [ $booted = 1 ]; then ok "first boot finished"; else bad "first boot did not finish"; docker exec "$ct" journalctl -u wifidb-firstboot --no-pager | tail -30; fi

log "checking the site"
check "the schema was loaded" test "$(psql_ "select count(*) from information_schema.tables where table_schema = 'public'")" -gt 30
check "...and the migrations applied" test "$(psql_ "select count(*) from schema_migrations where applied is not null")" -ge 4
check "this node is named testnode" test "$(psql_ "select node_name from settings limit 1")" = testnode
check "/health/ready answers 200" test "$(docker exec "$ct" curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/health/ready)" = 200
tzc="wifidb_client_check=1; wifidb_client_timezone=0; wifidb_client_dst=0"
errors='Undefined (constant|variable|array key)|Fatal error|Uncaught|Parse error|Stack trace|Call to undefined|SQLSTATE|PDOException'
scan() { # scan COOKIE PAGE...: no PHP or SQL error on each page
    local cookie=$1; shift
    for page in "$@"; do
        out=$(docker exec "$ct" curl -s -L -H "Cookie: $cookie" "http://127.0.0.1$page")
        code=$(docker exec "$ct" curl -s -L -o /dev/null -w '%{http_code}' -H "Cookie: $cookie" "http://127.0.0.1$page")
        if [ "$code" != 200 ]; then bad "no error on $page (HTTP $code)";
        elif echo "$out" | grep -qE "$errors"; then bad "no error on $page"; echo "$out" | grep -oE ".{0,120}($errors).{0,160}" | head -2 | sed 's/^/       /'; else ok "no error on $page"; fi
    done
}
scan "$tzc" / /login.php /stats.php /all.php /opt/map.php /opt/live.php /opt/search.php /opt/scheduling.php '/opt/scheduling.php?func=waiting' \
    '/opt/scheduling.php?func=done' '/opt/scheduling.php?func=schedule' /opt/results.php '/opt/userstats.php?func=allusers' /opt/export.php '/login.php?func=create_user_form' /import/

log "signing in"
pass="correct horse battery staple"
hash=$(docker exec -e P="$pass" "$ct" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
psql_ "INSERT INTO user_info (username, password, uid, email, validated, permissions, admin) VALUES ('pguser', '$hash', 'pg1', 'pg@example.test', 0, '1000', 1)" >/dev/null
session=$(docker exec "$ct" curl -s -D - -o /dev/null -H "Cookie: $tzc" -X POST --data-urlencode "time_user=pguser" --data-urlencode "time_pass=$pass" \
    'http://127.0.0.1/login.php?func=login_proc' | sed -n 's/^[Ss]et-[Cc]ookie: WiFiDB_login_yes=\([^;]*\);.*/\1/p' | head -1)
check "a member signs in" test -n "$session"
member="$tzc; WiFiDB_login_yes=$session"
check "...and their control panel opens" sh -c '! docker exec "$0" curl -s -H "Cookie: $1" http://127.0.0.1/cp/ | grep -q "must be logged in"' "$ct" "$member"
scan "$member" /cp/ '/cp/index.php?func=profile' '/cp/index.php?func=pref' /cp/security.php /cp/notifications.php '/cp/messages.php?func=inbox' \
    '/cp/messages.php?func=sendmsg' '/opt/userstats.php?func=alluserlists&user=pguser'

log "importing a WiGLE file"
docker exec "$ct" sh -c 'printf "%s\n" "WigleWifi-1.4,appRelease=2.53,model=Test,release=11,device=test,display=x,board=x,brand=x" \
    "MAC,SSID,AuthMode,FirstSeen,Channel,RSSI,CurrentLatitude,CurrentLongitude,AltitudeMeters,AccuracyMeters,Type" \
    "00:11:22:33:44:01,Café Ünïcode,[WPA2-PSK-CCMP][ESS],2026-01-01 10:00:00,6,-50,42.3601000,-71.0589000,10,5,WIFI" \
    "00:11:22:33:44:01,Café Ünïcode,[WPA2-PSK-CCMP][ESS],2026-01-01 10:00:05,6,-60,42.3602000,-71.0590000,10,5,WIFI" \
    "00:11:22:33:44:02,OpenNet,[ESS],2026-01-01 10:00:10,11,-70,42.3603000,-71.0591000,10,5,WIFI" > /tmp/wigle.csv'
docker exec "$ct" curl -s -o /dev/null -H "Cookie: $member" -F "upload[]=@/tmp/wigle.csv" -F user=pguser -F title=pgtest -F type=wiglewificsv \
    'http://127.0.0.1/import/index.php?func=import'
check "the upload page queued it" test "$(psql_ "select count(*) from files_tmp")" = 1
imp=$(docker exec "$ct" runuser -u www-data -- php /opt/wifidb/tools/daemon/importd.php -f -o -v 2>&1)
if echo "$imp" | grep -qE "$errors"; then bad "the importer runs without an error"; echo "$imp" | grep -E "$errors" | head -5 | sed 's/^/       /'; else ok "the importer runs without an error"; fi
check "...and finishes the file" test "$(psql_ "select count(*) from files where completed = 1")" = 1
check "...with both access points" test "$(psql_ 'select count(*) from wifi_ap')" = 2
check "...their GPS points" test "$(psql_ 'select count(*) from wifi_gps')" -ge 3
check "...the non-ASCII name intact" test "$(psql_ "select count(*) from wifi_ap where \"SSID\" = 'Café Ünïcode'")" = 1
check "...and nothing went to the bad files" test "$(psql_ 'select count(*) from files_bad')" = 0
if [ "$(psql_ 'select count(*) from wifi_ap')" != 2 ]; then echo "$imp" | tail -15 | sed 's/^/       /'; fi
ap=$(psql_ 'select "AP_ID" from wifi_ap order by "AP_ID" limit 1')
scan "$member" "/opt/fetch.php?id=$ap" "/opt/userstats.php?func=useraplist&row=1" '/all.php?sort=AP_ID&ord=DESC&from=0&inc=100' /stats.php \
    "/opt/map.php?func=user_list&id=1" '/opt/scheduling.php?func=done'
check "the signal graph draws an imported access point" sh -c 'docker exec "$0" curl -s "http://127.0.0.1/api/wifi/signal.png?ap=$1" | head -c 8 | od -An -c | tr -d " " | grep -q "211PNG"' "$ct" "$ap"

if [ -n "${IMPORT_SAMPLES:-}" ]; then
    sqlq() { psql_ "$1"; }
    . "$(dirname "$(readlink -f "$0")")/test-imports.inc.sh"
fi

log "backup and restore"
bk() { docker exec "$ct" runuser -u www-data -- php /opt/wifidb/tools/utilites/backup.php "$@"; }
check "a backup is made" bk export
name=$(bk list | awk '/ done /{print $1; exit}')
check "...and is complete" bk verify "$name"
before=$(psql_ 'select (select count(*) from wifi_ap) + (select count(*) from wifi_gps) + (select count(*) from user_info)')
psql_ "DELETE FROM wifi_hist; DELETE FROM wifi_ap" >/dev/null
check "it restores (as a database owner, not a superuser)" bk restore "$name" --yes
check "...every row back" test "$(psql_ 'select (select count(*) from wifi_ap) + (select count(*) from wifi_gps) + (select count(*) from user_info)')" = "$before"
check "...the foreign keys back" test "$(psql_ "select count(*) from pg_constraint where contype = 'f'")" -ge 2
check "...and a new member gets a fresh id" psql_ "INSERT INTO user_info (username, password, uid, email) VALUES ('afterrestore', 'x', 'ar1', 'ar@example.test')"

[ $fail = 0 ] && log "all checks passed" || log "some checks failed"
exit $fail
