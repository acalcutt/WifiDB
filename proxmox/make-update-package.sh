#!/bin/bash
# Makes a release's update package from its Proxmox template, so an update installs exactly what the template has:
#
#   WifiDB-v<version>-update.tar.gz
#     wifidb-update-<version>/
#       VERSION
#       web/       /var/www/wifidb, with Composer's vendor/
#       tools/     /opt/wifidb/tools
#       sql/       /opt/wifidb/sql (blank_db.* and migrations/)
#       swarm/     /opt/wifidb/swarm, the pmtiles-swarm release the code goes with (package.json, package-lock.json;
#                  wifidb-firstboot installs it from npm on a node with another), when the template has one
#       proxmox/   install-files.sh and the system files it installs (Apache, PHP-FPM, cron, the services, the updater)
#
# wifidb-update (admin/servers.php) checks it against the release's SHA256SUMS, moves the old code aside, runs
# proxmox/install-files.sh web tools sql, and the first boot again.
#
# usage: proxmox/make-update-package.sh TEMPLATE.tar.zst OUTPUT.tar.gz
set -euo pipefail
[ $# -eq 2 ] || { echo "usage: $0 TEMPLATE.tar.zst OUTPUT.tar.gz" >&2; exit 2; }
template=$(readlink -f "$1")
output=$(readlink -m "$2")
here=$(cd "$(dirname "$0")" && pwd)
work=$(mktemp -d)
trap 'rm -rf "$work"' EXIT

zstd -dc --long=27 "$template" | tar -x -C "$work" var/www/wifidb opt/wifidb
version=$(sed -n "s/^return '\(.*\)';\$/\1/p" "$work/var/www/wifidb/lib/version.inc.php")
[ -n "$version" ] || { echo "no version in the template's lib/version.inc.php" >&2; exit 1; }
test -f "$work/var/www/wifidb/vendor/autoload.php" || { echo "the template's site has no vendor/" >&2; exit 1; }

stage=$work/wifidb-update-$version
mkdir -p "$stage/proxmox"
mv "$work/var/www/wifidb" "$stage/web"
mv "$work/opt/wifidb/tools" "$stage/tools"
mv "$work/opt/wifidb/sql" "$stage/sql"
if [ -f "$work/opt/wifidb/swarm/package-lock.json" ]; then mv "$work/opt/wifidb/swarm" "$stage/swarm"; fi
echo "$version" > "$stage/VERSION"
# the system files install-files.sh installs (not the tests, nor the build's own scripts)
for f in install-files.sh wifidb-firstboot wifidb-dkim wifidb-update; do
    install -m 0755 "$here/$f" "$stage/proxmox/$f"
done
for f in wifidb-apache.conf wifidb-slow-pool.conf wifidb-slow-pool-fpm.conf wifidb-health-pool-fpm.conf wifidb-backup-pool-fpm.conf \
         wifidb-monitord.service wifidb-websocketd.service wifidb-firstboot.service wifidb-update.service wifidb-update.timer wifidb.cron; do
    install -m 0644 "$here/$f" "$stage/proxmox/$f"
done

mkdir -p "$(dirname "$output")"
tar -czf "$output" --owner=0 --group=0 --numeric-owner -C "$work" "wifidb-update-$version"
echo "update package: $output ($(du -h "$output" | cut -f1), version $version)"
