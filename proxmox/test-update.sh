#!/bin/bash
# The updater (wifidb-update, asked from admin/servers.php) against a stand-in for GitLab's release API that serves
# three update packages made from the template under test: v9.9.11, whose SHA256SUMS is wrong (refused, nothing
# touched); v9.9.10, whose home page is broken and whose pmtiles-swarm release npm hasn't got (installed, the swarm
# kept as it was, fails its check, the old version goes back); and v9.9.9, asked for with a backup first and going
# with another pmtiles-swarm release (installed: new code, old code kept aside, data disk and services as they were,
# the swarm on its release from npm and answering). The container runs the swarm; npm has to be reachable.
#
# usage (as root, on a Linux host with Docker):  proxmox/test-update.sh TEMPLATE.tar.zst
set -uo pipefail
template=$(readlink -f "$1")
here=$(dirname "$(readlink -f "$0")")
id=$$
net=wifidb-up-net-$id
db=wifidb-up-db-$id
feed=wifidb-up-feed-$id
ct=wifidb-up-ct-$id
image=wifidb-template-up:$id
work=/tmp/wifidb-up-$id
fail=0

log() { echo "[$(date +%H:%M:%S)] $*"; }
ok() { echo "  ok   $*"; }
bad() { echo "  FAIL $*"; fail=1; }
check() { # check DESCRIPTION COMMAND...
    local desc=$1; shift
    if "$@" >/dev/null 2>&1; then ok "$desc"; else bad "$desc"; return 1; fi
}
cleanup() {
    docker rm -f "$ct" "$db" "$feed" >/dev/null 2>&1 || true
    docker volume rm "wifidb-up-data-$id" >/dev/null 2>&1 || true
    docker network rm "$net" >/dev/null 2>&1 || true
    docker rmi "$image" >/dev/null 2>&1 || true
    rm -rf "$work"
}
trap cleanup EXIT
sqlq() { docker exec "$db" mariadb -uwifidb -ptestpw wifi -N -e "$1" | tr '\t' '|'; }
cver() { docker exec "$ct" sed -n "s/^return '\(.*\)';\$/\1/p" /var/www/wifidb/lib/version.inc.php; }
home() { docker exec "$ct" curl -s -o /dev/null -w '%{http_code}' -H 'Cookie: wifidb_client_check=1; wifidb_client_timezone=0' http://127.0.0.1/; }
state() { sqlq "select concat(update_state, '|', coalesce(update_message, '')) from server_status where node_name = 'testnode'"; }
update() { # update VERSION [BACKUP]: asks for it, as admin/servers.php does, and runs the updater (as its timer would)
    sqlq "INSERT INTO server_update_requests (version, backup, requested_by, requested_at) VALUES ('$1', ${2:-0}, 'test', UTC_TIMESTAMP())" >/dev/null
    docker exec "$ct" systemctl start wifidb-update.service
    # the timer may have started it first; wait for that run to finish
    for _ in $(seq 120); do docker exec "$ct" systemctl is-active -q wifidb-update.service || break; sleep 5; done
}

log "making the update packages from the template"
mkdir -p "$work/src" "$work/w" "$work/feed/api/v4/projects/7" "$work/pin-other"
bash "$here/make-update-package.sh" "$template" "$work/orig.tar.gz" || { echo "make-update-package.sh failed"; exit 1; }
tar -xzf "$work/orig.tar.gz" -C "$work/src"
orig=$(ls "$work/src")
was=$(cat "$work/src/$orig/VERSION")
zstd -dc "$template" | docker import --change 'CMD ["/sbin/init"]' - "$image" >/dev/null
# pmtiles-swarm: the package carries the template's release (npm's lock file). v9.9.9 goes with another release, from
# npm, and v9.9.10 with one npm hasn't got.
node_in() { docker run --rm -v "$work:/w" "$image" "$@"; }
swarm_lock_version() { node_in node -p "require('/w/$1').packages['node_modules/pmtiles-swarm'].version"; }
swarm_orig=$(swarm_lock_version "src/$orig/swarm/package-lock.json")
swarm_other=0.107.1; [ "$swarm_orig" = 0.107.1 ] && swarm_other=0.107.0
node_in sh -c "cd /w/pin-other && npm install --package-lock-only --no-audit --no-fund --loglevel=error pmtiles-swarm@$swarm_other" >/dev/null
releases=''
for v in 9.9.9 9.9.10 9.9.11; do
    cp -a "$work/src/$orig" "$work/w/wifidb-update-$v"
    sed -i "s/^return '.*';\$/return '$v';/" "$work/w/wifidb-update-$v/web/lib/version.inc.php"
    echo "$v" > "$work/w/wifidb-update-$v/VERSION"
    [ $v = 9.9.10 ] && echo '<?php this is not PHP(' > "$work/w/wifidb-update-$v/web/index.php"
    [ $v = 9.9.9 ] && cp "$work/pin-other/package.json" "$work/pin-other/package-lock.json" "$work/w/wifidb-update-$v/swarm/"
    [ $v = 9.9.10 ] && node_in node -e '
        const fs = require("fs"), d = "/w/w/wifidb-update-9.9.10/swarm/";
        const lock = JSON.parse(fs.readFileSync(d + "package-lock.json")), pkg = JSON.parse(fs.readFileSync(d + "package.json"));
        const entry = lock.packages["node_modules/pmtiles-swarm"];
        entry.version = "0.0.0-missing";
        entry.resolved = entry.resolved.replace(/[^/]+\.tgz$/, "pmtiles-swarm-0.0.0-missing.tgz");
        delete entry.integrity;
        lock.packages[""].dependencies["pmtiles-swarm"] = pkg.dependencies["pmtiles-swarm"] = "0.0.0-missing";
        fs.writeFileSync(d + "package-lock.json", JSON.stringify(lock, null, 2));
        fs.writeFileSync(d + "package.json", JSON.stringify(pkg, null, 2));'
    mkdir -p "$work/feed/r/$v"
    tar -czf "$work/feed/r/$v/WifiDB-v$v-update.tar.gz" --owner=0 --group=0 -C "$work/w" "wifidb-update-$v"
    sum=$(sha256sum "$work/feed/r/$v/WifiDB-v$v-update.tar.gz" | cut -d' ' -f1)
    [ $v = 9.9.11 ] && sum=$(printf '%064d' 0)
    echo "$sum  WifiDB-v$v-update.tar.gz" > "$work/feed/r/$v/SHA256SUMS"
    link() { printf '{"name":"%s","url":"http://feed:8000/r/%s/%s","direct_asset_url":"http://feed:8000/r/%s/%s"}' "$1" "$v" "$1" "$v" "$1"; }
    releases="$releases${releases:+,}{\"tag_name\":\"v$v\",\"released_at\":\"2026-11-01T00:00:00Z\",\"assets\":{\"links\":[$(link "WifiDB-v$v-update.tar.gz"),$(link SHA256SUMS)]}}"
done
echo "[$releases]" > "$work/feed/api/v4/projects/7/releases"

log "starting MariaDB and the release feed"
docker network create "$net" >/dev/null
docker run -d --name "$db" --network "$net" -e MARIADB_ROOT_PASSWORD=rootpw -e MARIADB_DATABASE=wifi -e MARIADB_USER=wifidb \
    -e MARIADB_PASSWORD=testpw mariadb:11 >/dev/null
docker create --name "$feed" --network "$net" --network-alias feed python:3.12-alpine python -m http.server 8000 -d /srv >/dev/null
docker cp "$work/feed/." "$feed:/srv/"
docker start "$feed" >/dev/null
for _ in $(seq 60); do docker exec "$db" mariadb -uwifidb -ptestpw wifi -e 'select 1' >/dev/null 2>&1 && break; sleep 2; done

log "first boot"
docker volume create "wifidb-up-data-$id" >/dev/null
docker create --name "$ct" --hostname wifidb-test --network "$net" --privileged --cgroupns=private --tmpfs /run --tmpfs /run/lock \
    -v "wifidb-up-data-$id:/srv/wifidb-data" "$image" >/dev/null
printf '%s\n' 'WIFIDB_DB_TYPE=mariadb' "WIFIDB_DB_HOST=$db" 'WIFIDB_DB_NAME=wifi' 'WIFIDB_DB_USER=wifidb' 'WIFIDB_DB_PASS=testpw' \
    'WIFIDB_URL=https://wifidb.test' 'WIFIDB_NODE=testnode' 'WIFIDB_UPDATE_GITLAB_URL=http://feed:8000' 'WIFIDB_UPDATE_GITLAB_PROJECT=7' \
    'WIFIDB_UPDATE_TOKEN=s3cret-token' 'WIFIDB_SWARM=1' > "$work/env"
docker cp "$work/env" "$ct:/etc/wifidb/firstboot.env"
docker start "$ct" >/dev/null
for _ in $(seq 120); do
    if docker exec "$ct" test ! -e /etc/wifidb/firstboot-pending 2>/dev/null \
       && case "$(docker exec "$ct" systemctl is-system-running 2>/dev/null)" in running|degraded) true ;; *) false ;; esac; then break; fi
    sleep 3
done
check "first boot finished" docker exec "$ct" test ! -e /etc/wifidb/firstboot-pending
check "the updater's timer is on" docker exec "$ct" systemctl is-enabled -q wifidb-update.timer
check "its settings are root's alone (the token is in them)" sh -c 'test "$(docker exec "$0" stat -c "%a %U" /etc/wifidb/updates.conf)" = "600 root" && docker exec "$0" grep -q "TOKEN=s3cret-token" /etc/wifidb/updates.conf' "$ct"
swarm_installed() { docker exec "$ct" node -p "require('/var/lib/pmtiles-swarm/node_modules/pmtiles-swarm/package.json').version"; }
swarm_pinned() { docker exec "$ct" node -p "require('/opt/wifidb/swarm/package-lock.json').packages['node_modules/pmtiles-swarm'].version"; }
swarm_left() { docker exec "$ct" sh -c 'test ! -e /var/lib/pmtiles-swarm/next && test ! -e /var/lib/pmtiles-swarm/previous'; }
check "the template's pmtiles-swarm ($swarm_orig) is installed, with its release recorded for updates" test "$(swarm_installed)|$(swarm_pinned)" = "$swarm_orig|$swarm_orig"
check "...which a new container doesn't fetch from npm again" sh -c '! docker exec "$0" journalctl -u wifidb-firstboot --no-pager -o cat | grep -q "which this release of WifiDB goes with"' "$ct"
docker exec "$ct" systemctl stop cron   # the test runs the jobs it needs itself
docker exec "$ct" runuser -u www-data -- /opt/wifidb/tools/cron/server_status
check "the node reports itself (version, load, disks, services)" sh -c 'test "$(echo "$1" | cut -d"|" -f1)" = "$2" && echo "$1" | grep -q "\"services\"" && echo "$1" | grep -q "/srv/wifidb-data"' \
    _ "$(sqlq "select concat(version, '|', info) from server_status where node_name = 'testnode'")" "$was"
docker exec "$ct" wifidb-update --releases >/dev/null 2>&1
check "the updater reads the release list (newest first)" sh -c 'echo "$0" | grep -q "\"version\":\"9.9.11\".*\"version\":\"9.9.10\".*\"version\":\"9.9.9\""' \
    "$(sqlq "select releases from server_status where node_name = 'testnode'")"

log "v9.9.11: the package doesn't match SHA256SUMS"
update 9.9.11
check "refused" sh -c 'echo "$0" | grep -q "^failed|.*SHA256SUMS"' "$(state)"
check "...nothing changed" test "$(cver)|$(home)" = "$was|200"
check "...and cron runs again" docker exec "$ct" systemctl is-active -q cron
check "a finished request isn't tried again" sh -c 'docker exec "$0" wifidb-update; test "$(docker exec "$0" grep -c "downloading: v9.9.11" /var/log/wifidb-update.log)" = 1' "$ct"

log "v9.9.10: installs, but its home page is broken"
update 9.9.10
check "the old version goes back" sh -c 'echo "$0" | grep -q "^rolled_back|the site doesn.t answer"' "$(state)"
check "...the old code, answering" test "$(cver)|$(home)" = "$was|200"
check "...and nothing left aside" docker exec "$ct" test ! -e /var/www/wifidb.old
check "...its pmtiles-swarm release, which npm hasn't got, was tried, and the installed one kept" sh -c 'docker exec "$0" grep -q "pmtiles-swarm 0.0.0-missing couldn.t be installed" /var/log/wifidb-update.log' "$ct"
check "...the template's release still installed, and recorded again" test "$(swarm_installed)|$(swarm_pinned)" = "$swarm_orig|$swarm_orig"
check "...with nothing of the attempt left" swarm_left

log "v9.9.9, with a backup first"
docker exec "$ct" systemctl start cron
update 9.9.9 1
check "updated" sh -c 'echo "$0" | grep -q "^done|updated from"' "$(state)"
check "...the new code, answering" test "$(cver)|$(home)" = "9.9.9|200"
check "...the old code kept aside" docker exec "$ct" grep -q "return '$was';" /var/www/wifidb.old/lib/version.inc.php
check "...uploads and generated files still on the data disk" docker exec "$ct" sh -c 'test -L /var/www/wifidb/import/up && test -L /var/www/wifidb/out && test -L /opt/wifidb/tools/log'
# (they're in web.overrides.php on the data disk, which the new config.inc.php loads)
check "...the site's settings kept" test "$(docker exec "$ct" php -r 'require "/var/www/wifidb/lib/config.inc.php"; echo $config["wifidb_nodename"];')" = testnode
check "...cron and the daemons running" docker exec "$ct" systemctl is-active -q cron wifidb-monitord wifidb-websocketd
check "...and the backup made first" sh -c 'docker exec "$0" sh -c "ls /srv/wifidb-data/backups/wifidb-backup-*.tar" && test "$(echo "$1")" = 1' "$ct" "$(sqlq "select backup_taken from server_update_requests where version = '9.9.9'")"
check "...the pmtiles-swarm release it goes with ($swarm_other), from npm" test "$(swarm_installed)|$(swarm_pinned)" = "$swarm_other|$swarm_other"
check "...running and answering as that release" sh -c 'docker exec "$0" systemctl is-active -q pmtiles-swarm && docker exec "$0" curl -fs http://127.0.0.1:8090/health | grep -q "\"version\":\"$1\""' "$ct" "$swarm_other"
check "...owned by the swarm's account" sh -c 'test "$(docker exec "$0" stat -c %U /var/lib/pmtiles-swarm/node_modules/pmtiles-swarm/package.json)" = pmtiles-swarm' "$ct"
check "...and nothing of the switch left" swarm_left
check "...the old release's record kept aside with the old code" docker exec "$ct" test -f /opt/wifidb/swarm.old/package-lock.json
check "the token went to the release host only, never in the log" sh -c '! docker exec "$0" grep -q s3cret-token /var/log/wifidb-update.log' "$ct"
if [ $fail != 0 ]; then echo "--- /var/log/wifidb-update.log"; docker exec "$ct" tail -40 /var/log/wifidb-update.log | cut -c1-200; fi

[ $fail = 0 ] && log "all checks passed" || log "some checks failed"
exit $fail
