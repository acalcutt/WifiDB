#!/bin/bash
# Two nodes on one database, with Shared files in S3 on a one-node Garage: the second node joins (its own settings and
# schedule rows, the first node's two_factor_key); without the bucket an upload is imported only by the node that has the
# file; with it, an upload made on the first is imported by the second, which fetches
# it from the bucket, and a lock taken on one node turns the same work away on the other.
#
# usage (as root, on a Linux host with Docker):  proxmox/test-two-nodes.sh TEMPLATE.tar.zst [mariadb|pgsql|sqlsrv]
set -uo pipefail
template=$(readlink -f "$1")
type=${2:-mariadb}
case "$type" in mariadb|pgsql|sqlsrv) ;; *) echo "usage: $0 TEMPLATE.tar.zst [mariadb|pgsql|sqlsrv]" >&2; exit 2 ;; esac
here=$(dirname "$(readlink -f "$0")")
id=$$
net=wifidb-2n-net-$id
db=wifidb-2n-db-$id
garage=wifidb-2n-garage-$id
cta=wifidb-2n-a-$id
ctb=wifidb-2n-b-$id
image=wifidb-template-2n:$id
GARAGE_VERSION=v1.0.1
fail=0
sapw='Wdb-Sa#2026pw'
apppw='Wdb-App#2026pw'

log() { echo "[$(date +%H:%M:%S)] $*"; }
ok() { echo "  ok   $*"; }
bad() { echo "  FAIL $*"; fail=1; }
check() { # check DESCRIPTION COMMAND...
    local desc=$1; shift
    if "$@" >/dev/null 2>&1; then ok "$desc"; else bad "$desc"; return 1; fi
}
cleanup() {
    docker rm -f "$cta" "$ctb" "$db" "$garage" >/dev/null 2>&1 || true
    docker volume rm "wifidb-2n-a-$id" "wifidb-2n-b-$id" >/dev/null 2>&1 || true
    docker network rm "$net" >/dev/null 2>&1 || true
    docker rmi "$image" >/dev/null 2>&1 || true
    rm -rf "/tmp/wifidb-2n-$id"
}
trap cleanup EXIT
sqlcmd=/opt/mssql-tools18/bin/sqlcmd
case "$type" in
    mariadb) sqlq() { docker exec "$db" mariadb -uwifidb -p"$apppw" wifi -N -e "$1" | tr '\t' '|'; }; Q() { printf '%s' "$1"; } ;;
    pgsql)   sqlq() { docker exec -e PGPASSWORD="$apppw" "$db" psql -h 127.0.0.1 -U wifidb -d wifi -At -c "$1"; }; Q() { printf '"%s"' "$1"; } ;;
    sqlsrv)  sqlq() { docker exec "$db" $sqlcmd -C -I -b -h -1 -W -s '|' -S localhost -U wifidb -P "$apppw" -d wifi -Q "SET NOCOUNT ON; $1"; }; Q() { printf '"%s"' "$1"; } ;;
esac

log "importing the template"
zstd -dc "$template" | docker import --change 'CMD ["/sbin/init"]' - "$image" >/dev/null
docker network create "$net" >/dev/null

log "starting $type and Garage $GARAGE_VERSION"
case "$type" in
    mariadb)
        docker run -d --name "$db" --network "$net" -e MARIADB_ROOT_PASSWORD="$sapw" -e MARIADB_DATABASE=wifi -e MARIADB_USER=wifidb \
            -e MARIADB_PASSWORD="$apppw" mariadb:11 >/dev/null
        for _ in $(seq 60); do docker exec "$db" mariadb -uwifidb -p"$apppw" wifi -e 'select 1' >/dev/null 2>&1 && break; sleep 2; done ;;
    pgsql)
        docker run -d --name "$db" --network "$net" -e POSTGRES_PASSWORD="$sapw" postgres:16 >/dev/null
        for _ in $(seq 60); do docker exec "$db" pg_isready -U postgres >/dev/null 2>&1 && break; sleep 2; done
        sleep 2
        docker exec "$db" psql -q -U postgres -c "CREATE ROLE wifidb LOGIN PASSWORD '$apppw'" -c "CREATE DATABASE wifi OWNER wifidb" >/dev/null ;;
    sqlsrv)
        docker run -d --name "$db" --network "$net" -e ACCEPT_EULA=Y -e "MSSQL_SA_PASSWORD=$sapw" -e MSSQL_MEMORY_LIMIT_MB=2048 mcr.microsoft.com/mssql/server:2022-latest >/dev/null
        for _ in $(seq 60); do docker exec "$db" $sqlcmd -C -S localhost -U sa -P "$sapw" -Q "select 1" >/dev/null 2>&1 && break; sleep 3; done
        docker exec "$db" $sqlcmd -C -b -S localhost -U sa -P "$sapw" -Q "CREATE DATABASE wifi; CREATE LOGIN wifidb WITH PASSWORD = '$apppw', CHECK_POLICY = OFF;" >/dev/null
        docker exec "$db" $sqlcmd -C -b -S localhost -U sa -P "$sapw" -d wifi -Q "CREATE USER wifidb FOR LOGIN wifidb; ALTER ROLE db_owner ADD MEMBER wifidb;" >/dev/null ;;
esac
mkdir -p "/tmp/wifidb-2n-$id"
cat > "/tmp/wifidb-2n-$id/garage.toml" <<EOF
metadata_dir = "/var/lib/garage/meta"
data_dir = "/var/lib/garage/data"
db_engine = "sqlite"
replication_factor = 1
rpc_bind_addr = "[::]:3901"
rpc_public_addr = "127.0.0.1:3901"
rpc_secret = "$(openssl rand -hex 32)"
[s3_api]
s3_region = "garage"
api_bind_addr = "[::]:3900"
root_domain = ".s3.garage.localhost"
EOF
docker run -d --name "$garage" --network "$net" -v "/tmp/wifidb-2n-$id/garage.toml:/etc/garage.toml:ro" "dxflrs/garage:$GARAGE_VERSION" >/dev/null
for _ in $(seq 30); do docker exec "$garage" /garage status >/dev/null 2>&1 && break; sleep 1; done
gnode=$(docker exec "$garage" /garage node id -q 2>/dev/null | cut -d@ -f1)
docker exec "$garage" /garage layout assign -z dc1 -c 1G "$gnode" >/dev/null 2>&1
docker exec "$garage" /garage layout apply --version 1 >/dev/null 2>&1
docker exec "$garage" /garage bucket create wdb-files >/dev/null 2>&1
keyinfo=$(docker exec "$garage" /garage key create wdb-files 2>/dev/null)
keyid=$(echo "$keyinfo" | sed -n 's/^Key ID: *//p')
secret=$(echo "$keyinfo" | sed -n 's/^Secret key: *//p')
docker exec "$garage" /garage bucket allow --read --write --owner wdb-files --key wdb-files >/dev/null 2>&1

boot() { # boot CONTAINER NODE [TWO_FACTOR_KEY]
    docker volume create "wifidb-2n-${1##wifidb-2n-}" >/dev/null
    docker create --name "$1" --hostname "$2" --network "$net" --privileged --cgroupns=private --tmpfs /run --tmpfs /run/lock \
        -v "wifidb-2n-${1##wifidb-2n-}:/srv/wifidb-data" "$image" >/dev/null
    printf '%s\n' "WIFIDB_DB_TYPE=$type" "WIFIDB_DB_HOST=$db" 'WIFIDB_DB_NAME=wifi' 'WIFIDB_DB_USER=wifidb' "WIFIDB_DB_PASS=$apppw" \
        'WIFIDB_URL=https://wifidb.test' "WIFIDB_NODE=$2" "WIFIDB_TWO_FACTOR_KEY=${3:-}" > "/tmp/wifidb-2n-$id/env"
    docker cp "/tmp/wifidb-2n-$id/env" "$1:/etc/wifidb/firstboot.env"
    docker start "$1" >/dev/null
    for _ in $(seq 120); do
        if docker exec "$1" test ! -e /etc/wifidb/firstboot-pending 2>/dev/null \
           && case "$(docker exec "$1" systemctl is-system-running 2>/dev/null)" in running|degraded) true ;; *) false ;; esac; then return 0; fi
        sleep 3
    done
    docker exec "$1" journalctl -u wifidb-firstboot --no-pager | tail -30
    return 1
}
helper() { local ct=$1; shift; docker exec "$ct" runuser -u www-data -- php /var/tmp/test-two-nodes.php "$@" 2>&1; }

log "first node (node-a)"
if boot "$cta" node-a; then ok "node-a's first boot finished"; else bad "node-a's first boot did not finish"; fi
key=$(docker exec "$cta" php -r '$c = include "/srv/wifidb-data/config/web.overrides.php"; echo $c["two_factor_key"];')
log "second node (node-b), with node-a's two_factor_key"
if boot "$ctb" node-b "$key"; then ok "node-b's first boot finished"; else bad "node-b's first boot did not finish"; fi
check "node-b has the same two_factor_key" test "$(docker exec "$ctb" php -r '$c = include "/srv/wifidb-data/config/web.overrides.php"; echo $c["two_factor_key"];')" = "$key"
check "node-b added itself to settings" test "$(sqlq "select count(*) from settings where node_name = 'node-b'")" = 1
check "...with imports on" test "$(sqlq "select enabled from schedule where nodename = 'node-b' and daemon = 'Import'")" = 1
check "...and the KML export left to node-a" test "$(sqlq "select enabled from schedule where nodename = 'node-b' and daemon = 'Export'")" = 0
check "...and the stats too (node-a works them out, migration 0007)" test "$(sqlq "select enabled from schedule where nodename = 'node-a' and daemon = 'Stats'")$(sqlq "select enabled from schedule where nodename = 'node-b' and daemon = 'Stats'")" = 10
docker exec "$ctb" journalctl -u wifidb-firstboot --no-pager | grep -q "new to this database" && ok "...and its first boot said so" || bad "...and its first boot said so"

for ct in "$cta" "$ctb"; do docker cp "$here/test-two-nodes.php" "$ct:/var/tmp/test-two-nodes.php"; docker exec "$ct" chmod 644 /var/tmp/test-two-nodes.php; done
# hold both nodes' cron off: the importers are run by hand below
for ct in "$cta" "$ctb"; do docker exec "$ct" systemctl stop cron; done
for ct in "$cta" "$ctb"; do for _ in $(seq 60); do docker exec "$ct" pgrep -f importd.php >/dev/null || break; sleep 2; done; done

tzc="wifidb_client_check=1; wifidb_client_timezone=0; wifidb_client_dst=0"
pass="correct horse battery staple"
hash=$(docker exec -e P="$pass" "$cta" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
sqlq "INSERT INTO user_info (username, password, uid, email, validated, permissions) VALUES ('tuser', '$hash', 'tu1', 'tu@example.test', 0, '0001')" >/dev/null
session=$(docker exec "$cta" curl -s -D - -o /dev/null -H "Cookie: $tzc" -X POST --data-urlencode "time_user=tuser" --data-urlencode "time_pass=$pass" \
    'http://127.0.0.1/login.php?func=login_proc' | sed -n 's/^[Ss]et-[Cc]ookie: WiFiDB_login_yes=\([^;]*\);.*/\1/p' | head -1)
check "a member signs in on node-a" test -n "$session"
upload() { # upload SSID-PREFIX: a two-access-point WiGLE file through node-a's upload page
    docker exec -e P="$1" "$cta" sh -c 'printf "%s\n" "WigleWifi-1.4,appRelease=2.53,model=Test,release=11,device=test,display=x,board=x,brand=x" \
        "MAC,SSID,AuthMode,FirstSeen,Channel,RSSI,CurrentLatitude,CurrentLongitude,AltitudeMeters,AccuracyMeters,Type" \
        "00:11:22:33:44:01,${P},[WPA2-PSK-CCMP][ESS],2026-01-01 10:00:00,6,-50,42.3601000,-71.0589000,10,5,WIFI" \
        "00:11:22:33:44:02,${P}Open,[ESS],2026-01-01 10:00:10,11,-70,42.3603000,-71.0591000,10,5,WIFI" > /var/tmp/wigle.csv'
    docker exec "$cta" curl -s -o /dev/null -H "Cookie: $tzc; WiFiDB_login_yes=$session" -F "upload[]=@/var/tmp/wigle.csv" -F user=tuser -F "title=$1" \
        -F type=wiglewificsv 'http://127.0.0.1/import/index.php?func=import'
}
importer() { docker exec "$1" runuser -u www-data -- php /opt/wifidb/tools/daemon/importd.php -f -o -v 2>&1 | tr -d '\0'; }

log "without the shared files bucket: only the node with the file imports it"
upload NoBucket
name=$(sqlq "select file_name from files_tmp")
check "node-a's upload page queued it, for node-a" test "$(sqlq "select node_name from files_tmp")" = node-a
check "...and keeps the file" docker exec "$cta" test -f "/var/www/wifidb/import/up/$name"
importer "$ctb" >/dev/null
check "node-b's importer leaves it (the file isn't on its disk)" test "$(sqlq "select count(*) from files_tmp")" = 1
check "...nor sets it aside" test "$(sqlq "select count(*) from files_bad")" = 0
imp=$(importer "$cta")
check "node-a's importer imports it" test "$(sqlq "select count(*) from files where completed = 1 and node_name = 'node-a'")" = 1
check "...both access points" test "$(sqlq "select count(*) from wifi_ap where $(Q SSID) like 'NoBucket%'")" = 2
if [ "$(sqlq "select count(*) from files where completed = 1")" != 1 ]; then echo "$imp" | grep -v Deprecated | tail -20 | sed 's/^/       /'; fi

log "with the shared files bucket: any node imports it"
helper "$cta" set-shared "http://$garage:3900" wdb-files "$keyid" "$secret" >/dev/null
check "node-b reads the S3 secret node-a saved (the shared key)" test "$(helper "$ctb" secret)" = "$secret"
upload TwoNodes
name=$(sqlq "select file_name from files_tmp")
check "node-a's upload page queued it" test -n "$name"
check "...and put it in the bucket" test "$(helper "$ctb" has-upload "$name")" = 1
check "...which is where it's kept: node-a let its own copy go" docker exec "$cta" test ! -e "/var/www/wifidb/import/up/$name"
imp=$(importer "$ctb")
check "node-b's importer fetched it from the bucket" sh -c 'echo "$0" | grep -q "Fetched .* from the shared files bucket"' "$imp"
check "...and imported it" test "$(sqlq "select count(*) from files where completed = 1 and node_name = 'node-b'")" = 1
check "...both access points" test "$(sqlq "select count(*) from wifi_ap where $(Q SSID) like 'TwoNodes%'")" = 2
check "...and removed the copy it fetched" docker exec "$ctb" test ! -e "/var/www/wifidb/import/up/$name"
check "the bucket still has it" test "$(helper "$ctb" has-upload "$name")" = 1
if [ "$(sqlq "select count(*) from files where completed = 1 and node_name = 'node-b'")" != 1 ]; then echo "$imp" | grep -v Deprecated | tail -20 | sed 's/^/       /'; fi

log "an admin job: deleting the import node-b made, done by node-a's admind"
check "each node has an Admin schedule row (migration 0006)" test "$(sqlq "select count(*) from schedule where daemon = 'Admin' and enabled = 1")" = 2
fid=$(sqlq "select id from files where node_name = 'node-b'")
sqlq "INSERT INTO admin_jobs (job_type, target_id, target_table, requested_by, status, created_at) VALUES ('delete_file', $fid, 'files', 'test', 'pending', '2026-01-01 00:00:00')" >/dev/null
adm=$(docker exec "$cta" runuser -u www-data -- php /opt/wifidb/tools/daemon/admind.php -o -v 2>&1 | tr -d '\0')
check "node-a's admind did it" test "$(sqlq "select status from admin_jobs where target_id = $fid")|$(sqlq "select nodename from admin_jobs where target_id = $fid")" = "completed|node-a"
check "...the import is gone" test "$(sqlq "select count(*) from files where id = $fid")" = 0
check "...and its upload moved to deleted/ in the bucket" test "$(helper "$ctb" has-upload "$name")$(helper "$ctb" has-deleted "$name")" = 01
if [ "$(sqlq "select status from admin_jobs where target_id = $fid")" != completed ]; then echo "$adm" | grep -v Deprecated | tail -15 | sed 's/^/       /'; fi

log "the lock, across the two nodes"
docker exec -d "$cta" runuser -u www-data -- sh -c 'php /opt/wifidb/tools/daemon/lock_run.php twonodes sleep 15 > /var/tmp/lock.out 2>&1'
sleep 5
out=$(docker exec "$ctb" runuser -u www-data -- php /opt/wifidb/tools/daemon/lock_run.php twonodes sh -c 'echo ran-on-b' 2>&1)
check "while node-a holds it, node-b is turned away" sh -c 'echo "$0" | grep -q "already running" && ! echo "$0" | grep -q ran-on-b' "$out"
for _ in $(seq 20); do docker exec "$cta" pgrep -f 'lock_run.php twonodes' >/dev/null || break; sleep 1; done
out=$(docker exec "$ctb" runuser -u www-data -- php /opt/wifidb/tools/daemon/lock_run.php twonodes sh -c 'echo ran-on-b' 2>&1)
check "once node-a is done, node-b gets it" sh -c 'echo "$0" | grep -q ran-on-b' "$out"
# a node that stops lets its lock go
docker exec -d "$cta" runuser -u www-data -- sh -c 'php /opt/wifidb/tools/daemon/lock_run.php twonodes sleep 300 > /var/tmp/lock2.out 2>&1'
sleep 5
docker kill "$cta" >/dev/null
for _ in $(seq 10); do
    out=$(docker exec "$ctb" runuser -u www-data -- php /opt/wifidb/tools/daemon/lock_run.php twonodes sh -c 'echo ran-on-b' 2>&1)
    echo "$out" | grep -q ran-on-b && break
    sleep 3
done
check "when node-a stops while holding it, node-b gets it" sh -c 'echo "$0" | grep -q ran-on-b' "$out"

[ $fail = 0 ] && log "all checks passed" || log "some checks failed"
exit $fail
