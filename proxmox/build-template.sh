#!/bin/bash
# Build the WiFiDB Proxmox LXC template: an Ubuntu container with the site, the services and the cron jobs installed,
# ready for first boot (wifidb-firstboot).
#
# usage (as root, on a Linux host with Docker):
#   proxmox/build-template.sh SOURCE_TARBALL OUTPUT.tar.zst
#
# SOURCE_TARBALL is a `git archive` of the repository (it holds wifidb/wifidb, wifidb/tools, the blank_db files and composer.json/composer.lock):
#   git archive --format=tar.gz -o wifidb-src.tar.gz HEAD wifidb composer.json composer.lock
#
# How: boot proxmox/Dockerfile.base with systemd as PID 1, unpack the sources, run install-wifidb.sh, switch off
# everything that needs the settings (wifidb-firstboot starts it), remove what must differ per container (SSH host
# keys, machine ID), and export the filesystem.
#
# Environment:
#   UBUNTU_VERSION  base image, default 26.04
#   WIFIDB_SQLSRV   auto (default), yes or no: whether the SQL Server driver is required (see install-wifidb.sh)
set -euo pipefail
tarball=$(readlink -f "$1")
# -m: the output's folder may not exist yet
output=$(readlink -m "$2")
mkdir -p "$(dirname "$output")"
here=$(cd "$(dirname "$0")" && pwd)
name=wifidb-template-build-$$
ubuntu=${UBUNTU_VERSION:-26.04}

log() { echo "[$(date +%H:%M:%S)] $*"; }
cleanup() { docker rm -f "$name" >/dev/null 2>&1 || true; }
trap cleanup EXIT

log "building the base image (Ubuntu $ubuntu)"
docker build -q -t wifidb-template-base --build-arg "UBUNTU_VERSION=$ubuntu" -f "$here/Dockerfile.base" "$here"

log "booting it"
docker run -d --name "$name" --hostname wifidb --privileged \
    --cgroupns=private --tmpfs /run --tmpfs /run/lock \
    wifidb-template-base >/dev/null
state=starting
for _ in $(seq 60); do
    state=$(docker exec "$name" systemctl is-system-running 2>/dev/null || true)
    case "$state" in running|degraded) break ;; esac
    sleep 2
done
log "systemd is $state"

log "unpacking the sources and the template files"
docker exec "$name" install -d /usr/local/src/wifidb /usr/local/src/wifidb-proxmox
docker cp "$tarball" "$name:/usr/local/src/wifidb-src.tar.gz"
docker exec "$name" sh -c 'tar -xzf /usr/local/src/wifidb-src.tar.gz -C /usr/local/src/wifidb && rm /usr/local/src/wifidb-src.tar.gz'
docker cp "$here/." "$name:/usr/local/src/wifidb-proxmox/"

log "running the installer (this takes a while)"
docker exec -e WIFIDB_SQLSRV="${WIFIDB_SQLSRV:-auto}" "$name" \
    bash /usr/local/src/wifidb-proxmox/install-wifidb.sh </dev/null \
    > "$output.install.log" 2>&1 || {
        tail -50 "$output.install.log"
        log "the installer failed; full log in $output.install.log"
        exit 1
    }
grep -E 'WARNING|pdo_sqlsrv is installed' "$output.install.log" || true

log "preparing for first boot"
# -i: the script comes on stdin
docker exec -i "$name" bash -s <<'FINALIZE'
set -e
phpver=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;')
# Nothing that needs the settings starts until wifidb-firstboot has run; it enables these again.
systemctl stop apache2 "php${phpver}-fpm" cron wifidb-monitord wifidb-websocketd 2>/dev/null || true
systemctl disable apache2 "php${phpver}-fpm" cron wifidb-monitord wifidb-websocketd 2>/dev/null || true
systemctl enable wifidb-firstboot.service
# per-container identity
rm -f /etc/ssh/ssh_host_*
: > /etc/machine-id
rm -f /var/lib/dbus/machine-id
# With no machine ID, systemd treats the first start as a first boot and applies unit presets, whose default is
# "enable". Keep the WiFiDB services off until wifidb-firstboot turns them on.
install -d /etc/systemd/system-preset
cat > /etc/systemd/system-preset/10-wifidb-firstboot.preset <<'PRESET'
enable wifidb-firstboot.service
disable apache2.service
disable php*-fpm.service
disable cron.service
disable wifidb-monitord.service
disable wifidb-websocketd.service
PRESET
install -d /etc/wifidb
touch /etc/wifidb/firstboot-pending
cat > /etc/wifidb/firstboot.env <<'ENV'
# Settings for the first boot (wifidb-firstboot). Fill these in before starting the container, or after the first
# attempt fails: systemctl restart wifidb-firstboot
#
# The database. The template has none of its own; point it at your MariaDB or SQL Server. It must exist, with
# a user that can create tables (the first boot loads the schema into an empty database).
WIFIDB_DB_TYPE=mariadb            # mariadb, or sqlsrv for SQL Server
#WIFIDB_DB_HOST=db.example.lan
#WIFIDB_DB_PORT=3306              # default 3306 (mysql) or 1433 (sqlsrv)
#WIFIDB_DB_NAME=wifi
#WIFIDB_DB_USER=wifidb
#WIFIDB_DB_PASS=
#
# The site's public address: what visitors type, with https:// if the proxy does TLS.
#WIFIDB_URL=https://wifidb.example.com
#WIFIDB_ROOT=                     # leave empty to serve the site at the top of the address
#
# This node's name in the database (settings.node_name and schedule.nodename). Default: the container's hostname.
#WIFIDB_NODE=wifidb
#
# Behind HAProxy on OPNsense (or another proxy that does TLS): the proxy's addresses on this network, separated by
# spaces. For a CARP pair list both firewalls and the shared address.
#WIFIDB_EXTERNAL_PROXY=192.168.1.1
#
# The two background daemons, on by default: the monitor (records the other daemons' status) and the websocket
# daemon (live updates on the Import Status and Live APs pages). Set to 0 on a node that shouldn't run one.
#WIFIDB_MONITORD=1
#WIFIDB_WEBSOCKETD=1
ENV
cat > /etc/motd <<'MOTD'

  WiFiDB: fill in /etc/wifidb/firstboot.env, then   systemctl restart wifidb-firstboot
  Watch the setup with   journalctl -u wifidb-firstboot -f
  Uploads, generated files, logs and settings live in /srv/wifidb-data (mount a second disk there).

MOTD
# leave no build leftovers
rm -rf /usr/local/src/wifidb /usr/local/src/wifidb-proxmox
apt-get clean
rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/* /root/.bash_history /root/.cache
journalctl --rotate >/dev/null 2>&1 || true
journalctl --vacuum-time=1s >/dev/null 2>&1 || true
find /var/log -type f \( -name '*.gz' -o -name '*.1' \) -delete || true
find /var/log -type f -exec truncate -s 0 {} + 2>/dev/null || true
FINALIZE

# make sure that took
docker exec "$name" test -f /etc/wifidb/firstboot-pending
docker exec "$name" systemctl is-enabled -q wifidb-firstboot.service
! docker exec "$name" systemctl is-enabled -q wifidb-monitord.service

log "stopping and exporting"
# Docker's marker file would make systemd think it runs in Docker, not LXC; pct writes its own hostname, hosts
# and resolv.conf.
docker exec "$name" rm -f /.dockerenv
docker stop -t 60 "$name" >/dev/null
docker export "$name" | zstd -q -T0 -19 --long=27 -o "$output" -f
# check the archive reads back
zstd -dc "$output" | tar -t >/dev/null
log "template: $output ($(du -h "$output" | cut -f1))"
