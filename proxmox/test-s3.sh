#!/bin/bash
# lib/s3.inc.php against a real S3 service: a one-node Garage, the same software as the cluster the backups go to.
# test-s3.php does the work, in PHP's own image.
#
# usage (on a Linux host with Docker):  proxmox/test-s3.sh
set -uo pipefail
src=$(cd "$(dirname "$(readlink -f "$0")")/.." && pwd)
id=$$
net=wdb-s3-net-$id; garage=wdb-s3-garage-$id
GARAGE_VERSION=v1.0.1
log() { echo "[$(date +%H:%M:%S)] $*"; }
cleanup() { docker rm -f "$garage" >/dev/null 2>&1; docker network rm "$net" >/dev/null 2>&1; rm -rf "/tmp/wdb-s3-$id"; }
trap cleanup EXIT

log "starting Garage $GARAGE_VERSION"
docker network create "$net" >/dev/null
mkdir -p "/tmp/wdb-s3-$id"
cat > "/tmp/wdb-s3-$id/garage.toml" <<EOF
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
docker run -d --name "$garage" --network "$net" -v "/tmp/wdb-s3-$id/garage.toml:/etc/garage.toml:ro" "dxflrs/garage:$GARAGE_VERSION" >/dev/null
for _ in $(seq 30); do docker exec "$garage" /garage status >/dev/null 2>&1 && break; sleep 1; done
node=$(docker exec "$garage" /garage node id -q 2>/dev/null | cut -d@ -f1)
docker exec "$garage" /garage layout assign -z dc1 -c 1G "$node" >/dev/null 2>&1
docker exec "$garage" /garage layout apply --version 1 >/dev/null 2>&1
docker exec "$garage" /garage bucket create wdb-backups >/dev/null 2>&1
keyinfo=$(docker exec "$garage" /garage key create wdb-test 2>/dev/null)
keyid=$(echo "$keyinfo" | sed -n 's/^Key ID: *//p')
secret=$(echo "$keyinfo" | sed -n 's/^Secret key: *//p')
docker exec "$garage" /garage bucket allow --read --write --owner wdb-backups --key wdb-test >/dev/null 2>&1

log "testing the client"
docker run --rm --network "$net" -v "$src:/src:ro" -e S3_ENDPOINT="http://$garage:3900" -e S3_REGION=garage -e S3_BUCKET=wdb-backups \
    -e S3_KEY_ID="$keyid" -e S3_SECRET="$secret" php:8.3-cli php /src/proxmox/test-s3.php /src/wifidb/wifidb/lib
status=$?
[ $status = 0 ] && log "all checks passed" || log "some checks failed"
exit $status
