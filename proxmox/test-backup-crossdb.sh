#!/bin/bash
# A backup made on MariaDB restores on SQL Server and back, every row the same (test-backup-crossdb.php does the work).
# Uses a built template for its PHP (it has pdo_mysql and pdo_sqlsrv) and this checkout's code.
#
# usage (as root, on a Linux host with Docker):  proxmox/test-backup-crossdb.sh TEMPLATE.tar.zst
set -uo pipefail
template=$(readlink -f "$1")
src=$(cd "$(dirname "$(readlink -f "$0")")/.." && pwd)
id=$$
net=wdb-xdb-net-$id
image=wdb-xdb-php:$id
maria=wdb-xdb-maria-$id; maria2=wdb-xdb-maria2-$id; mssql=wdb-xdb-mssql-$id; php=wdb-xdb-php-$id
pw='Wdb-Test#2026pw'
log() { echo "[$(date +%H:%M:%S)] $*"; }
cleanup() { docker rm -f "$maria" "$maria2" "$mssql" "$php" >/dev/null 2>&1; docker network rm "$net" >/dev/null 2>&1; docker rmi "$image" >/dev/null 2>&1; }
trap cleanup EXIT

log "starting MariaDB (two) and SQL Server"
docker network create "$net" >/dev/null
for m in "$maria" "$maria2"; do
    docker run -d --name "$m" --network "$net" -e MARIADB_ROOT_PASSWORD=rootpw -e MARIADB_DATABASE=wifi mariadb:11 >/dev/null
done
docker run -d --name "$mssql" --network "$net" -e ACCEPT_EULA=Y -e "MSSQL_SA_PASSWORD=$pw" -e MSSQL_MEMORY_LIMIT_MB=2048 mcr.microsoft.com/mssql/server:2022-latest >/dev/null
zstd -dc "$template" | docker import - "$image" >/dev/null
docker run -d --name "$php" --network "$net" -v "$src:/src:ro" -e MARIA_HOST="$maria" -e MARIA2_HOST="$maria2" -e MSSQL_HOST="$mssql" -e MSSQL_PW="$pw" "$image" sleep infinity >/dev/null

sqlcmd=/opt/mssql-tools18/bin/sqlcmd
for _ in $(seq 60); do docker exec "$mssql" $sqlcmd -C -S localhost -U sa -P "$pw" -Q "select 1" >/dev/null 2>&1 && break; sleep 3; done
for m in "$maria" "$maria2"; do
    for _ in $(seq 60); do docker exec "$m" mariadb -uroot -prootpw wifi -e 'select 1' >/dev/null 2>&1 && break; sleep 2; done
done

log "loading the blank schemas"
for m in "$maria" "$maria2"; do docker exec -i "$m" mariadb -uroot -prootpw wifi < "$src/wifidb/blank_db.sql" || echo "  FAIL loading blank_db.sql"; done
docker exec "$mssql" $sqlcmd -C -S localhost -U sa -P "$pw" -Q "CREATE DATABASE prod_wifi" >/dev/null
docker cp "$src/wifidb/blank_db.sqlsrv" "$mssql:/tmp/blank.sql"
docker exec "$mssql" $sqlcmd -C -b -S localhost -U sa -P "$pw" -d prod_wifi -i /tmp/blank.sql | grep -E '^Msg' | head -5

log "backing up and restoring"
docker exec "$php" mkdir -p /work
docker exec "$php" php /src/proxmox/test-backup-crossdb.php /src/wifidb/wifidb/lib /src/wifidb/sql/migrations /work
status=$?
[ $status = 0 ] && log "all checks passed" || log "some checks failed"
exit $status
