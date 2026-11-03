#!/bin/bash
# pmtiles-swarm in the template (docs/pmtiles-swarm.md), on two nodes and MariaDB: node-a builds the map's tile
# archives and publishes them; node-b follows node-a and mirrors them into its own archive folder, under the same
# names, where its site reads them. Both nodes have the site address http://node-a, so the web seed every torrent
# carries is node-a's archive folder, as the site's would be.
#
# usage (as root, on a Linux host with Docker):  proxmox/test-swarm.sh TEMPLATE.tar.zst
set -uo pipefail
template=$(readlink -f "$1")
id=$$
net=wifidb-sw-net-$id
db=wifidb-sw-db-$id
cta=wifidb-sw-a-$id
ctb=wifidb-sw-b-$id
image=wifidb-template-sw:$id
work=/tmp/wifidb-sw-$id
fail=0
apppw='Wdb-App#2026pw'

log() { echo "[$(date +%H:%M:%S)] $*"; }
ok() { echo "  ok   $*"; }
bad() { echo "  FAIL $*"; fail=1; }
check() { # check DESCRIPTION COMMAND...
    local desc=$1; shift
    if "$@" >/dev/null 2>&1; then ok "$desc"; else bad "$desc"; return 1; fi
}
cleanup() {
    docker rm -f "$cta" "$ctb" "$db" >/dev/null 2>&1 || true
    docker volume rm "$cta" "$ctb" >/dev/null 2>&1 || true
    docker network rm "$net" >/dev/null 2>&1 || true
    docker rmi "$image" >/dev/null 2>&1 || true
    rm -rf "$work"
}
trap cleanup EXIT
sqlq() { docker exec "$db" mariadb -uwifidb -p"$apppw" wifi -N -e "$1" | tr '\t' '|'; }
swarmconf() { docker exec "$1" node -p "const c = require('/srv/wifidb-data/swarm/swarm.config.json'); $2"; }
journal() { docker exec "$1" journalctl -u wifidb-firstboot --no-pager -o cat; }

log "importing the template"
zstd -dc "$template" | docker import --change 'CMD ["/sbin/init"]' - "$image" >/dev/null
docker network create "$net" >/dev/null
mkdir -p "$work"
docker run -d --name "$db" --network "$net" -e MARIADB_ROOT_PASSWORD=rootpw -e MARIADB_DATABASE=wifi -e MARIADB_USER=wifidb \
    -e MARIADB_PASSWORD="$apppw" mariadb:11 >/dev/null
for _ in $(seq 60); do docker exec "$db" mariadb -uwifidb -p"$apppw" wifi -e 'select 1' >/dev/null 2>&1 && break; sleep 2; done

firstboot_done() {
    for _ in $(seq 120); do
        if docker exec "$1" test ! -e /etc/wifidb/firstboot-pending 2>/dev/null \
           && case "$(docker exec "$1" systemctl is-system-running 2>/dev/null)" in running|degraded) true ;; *) false ;; esac; then return 0; fi
        sleep 3
    done
    journal "$1" | tail -30
    return 1
}
boot() { # boot CONTAINER NODE [ENV LINE...]
    local ct=$1 node=$2; shift 2
    docker volume create "$ct" >/dev/null
    docker create --name "$ct" --hostname "$node" --network "$net" --network-alias "$node" --privileged --cgroupns=private \
        --tmpfs /run --tmpfs /run/lock -v "$ct:/srv/wifidb-data" "$image" >/dev/null
    printf '%s\n' 'WIFIDB_DB_TYPE=mariadb' "WIFIDB_DB_HOST=$db" 'WIFIDB_DB_NAME=wifi' 'WIFIDB_DB_USER=wifidb' "WIFIDB_DB_PASS=$apppw" \
        'WIFIDB_URL=http://node-a' "WIFIDB_NODE=$node" "$@" > "$work/env"
    docker cp "$work/env" "$ct:/etc/wifidb/firstboot.env"
    docker start "$ct" >/dev/null
    firstboot_done "$ct"
}
# Which node builds the archives, and the swarm's public name, as an administrator would set them
tile_settings() {
    docker exec "$1" php -r '$f = "/srv/wifidb-data/config/web.overrides.php"; $c = include $f;
        $c["tile_archive_generate"] = "node-a"; $c["tile_swarm_url"] = "http://data.wifidb.test";
        file_put_contents($f, "<?php\nreturn " . var_export($c, true) . ";\n");'
}

log "node-a, which builds the archives and publishes them"
if boot "$cta" node-a WIFIDB_SWARM=1; then ok "node-a's first boot finished"; else bad "node-a's first boot did not finish"; fi
check "pmtiles-swarm is running" docker exec "$cta" systemctl is-active -q pmtiles-swarm
check "...as its own account" test "$(docker exec "$cta" systemctl show -p User --value pmtiles-swarm)" = pmtiles-swarm
check "...which can't read the site's settings (the database password)" sh -c '! docker exec "$0" runuser -u pmtiles-swarm -- cat /var/www/wifidb/lib/config.inc.php' "$cta"
check "...and shares the archive folder through its group" test "$(docker exec "$cta" stat -c '%U:%G %a' /srv/wifidb-data/out/pmtiles)" = "www-data:pmtiles-swarm 2775"
check "its settings are on the data disk, its alone" test "$(docker exec "$cta" stat -c '%U %a' /srv/wifidb-data/swarm/swarm.config.json)" = "pmtiles-swarm 600"
check "it watches the archive folder for every bucket" test "$(swarmconf "$cta" 'c.watch.filter((w) => w.path === "/srv/wifidb-data/out/pmtiles").length')" = 20
check "...a category each" test "$(swarmconf "$cta" 'c.watch.find((w) => w.match === "cell_daily-*.pmtiles").categories[0]')" = wifidb-cell-daily
check "...with the site's archive folder as the web seed" test "$(swarmconf "$cta" 'c.watch[0].webSeedBase')" = http://node-a/out/pmtiles
check "...as wifidb.net's swarm has them (1 MiB pieces, more of the hourly builds kept, the stable name a hard link)" test \
    "$(swarmconf "$cta" 'const d = c.watch.find((w) => w.match === "daily-*.pmtiles"); [d.pieceLength, d.keep, d.latestLink, d.latestLinkType].join(" ")')" = "1048576 4 daily.pmtiles hard"
check "...and no MLT archives, which the site doesn't build" test "$(swarmconf "$cta" 'c.watch.filter((w) => w.path.endsWith("pmtiles-mlt")).length')" = 0
check "a new node starts with wifidb.net's feed and firewall settings" test \
    "$(swarmconf "$cta" '[c.feedTitle, c.md5, c.libtorrent.upnp, c.libtorrent.natpmp].join("|")')" = "WifiDB tile archives|true|false|false"
check "it signs each category's current build (BEP 46), with a key of its own" sh -c 'test "$(docker exec "$0" node -p "require(\"/srv/wifidb-data/swarm/swarm.config.json\").mutable.publish")" = true && docker exec "$0" test -s /srv/wifidb-data/swarm/publisher.pem' "$cta"
check "the console stays inside the container" test "$(swarmconf "$cta" 'c.adminHost')" = 127.0.0.1
check "...and its password is written for root" docker exec "$cta" grep -q 'password:  [A-Za-z0-9_-]' /root/pmtiles-swarm-first-login.txt
check "the BitTorrent port is the default" test "$(swarmconf "$cta" 'c.libtorrent.listen')|$(swarmconf "$cta" 'c.webtorrent.clientOptions.torrentPort')" = "0.0.0.0:6881,[::]:6881|6882"
check "the shared files bucket's sync leaves the archives to it (out_sync_skip)" test \
    "$(docker exec "$cta" php -r 'require "/var/www/wifidb/lib/config.inc.php"; echo implode(",", $config["out_sync_skip"]);')" = pmtiles,pmtiles-mlt
check "it answers on port 8090" sh -c 'for _ in $(seq 30); do docker exec "$0" curl -fs -o /dev/null http://127.0.0.1:8090/health && exit 0; sleep 2; done; exit 1' "$cta"
tile_settings "$cta"
apikey=$(swarmconf "$cta" 'c.auth.apiKey')

log "node-b, which follows node-a"
if boot "$ctb" node-b WIFIDB_SWARM=1 WIFIDB_SWARM_FOLLOW=http://node-a:8090/ WIFIDB_SWARM_PORT=6883; then ok "node-b's first boot finished"; else bad "node-b's first boot did not finish"; fi
warning="follows http://node-a:8090 but builds archives too"
check "a follower that would build archives too is warned" sh -c 'docker exec "$0" journalctl -u wifidb-firstboot --no-pager -o cat | grep -q "$1"' "$ctb" "$warning"
tile_settings "$ctb"
bkey=$(swarmconf "$ctb" 'c.auth.apiKey')
# first boot again, as an administrator runs it after changing a setting (the unit runs only the first time)
docker exec "$ctb" /usr/local/sbin/wifidb-firstboot > "$work/again.out" 2>&1
check "first boot runs again" grep -q "done: " "$work/again.out"
check "...and doesn't warn once tile_archive_generate names node-a" sh -c '! grep -q "$1" "$0"' "$work/again.out" "$warning"
check "...which kept the swarm's API key" test "$(swarmconf "$ctb" 'c.auth.apiKey')" = "$bkey"
check "...and is still running it" docker exec "$ctb" systemctl is-active -q pmtiles-swarm
check "it follows a feed per category, into its own archive folder" test "$(swarmconf "$ctb" 'c.subscriptions.filter((s) => s.savePath === "/srv/wifidb-data/out/pmtiles" && s.mode === "mirror").length')" = 20
check "...node-a's" test "$(swarmconf "$ctb" 'c.subscriptions.find((s) => s.latestLink === "weekly.pmtiles").url')" = http://node-a:8090/feed/wifidb-weekly.xml
check "...and publishes nothing of its own" test "$(swarmconf "$ctb" 'c.watch.length')|$(swarmconf "$ctb" 'c.mutable.publish')" = "0|false"
check "...on the BitTorrent port it was given" test "$(swarmconf "$ctb" 'c.libtorrent.listen')|$(swarmconf "$ctb" 'c.webtorrent.clientOptions.torrentPort')" = "0.0.0.0:6883,[::]:6883|6884"
check "...with the swarm's public name for the URLs that outlive a request (tile_swarm_url)" test "$(swarmconf "$ctb" 'c.publishingUrl')" = http://data.wifidb.test
check "node-a's tiles and feeds are on the network" docker exec "$ctb" curl -fs -o /dev/null http://node-a:8090/health
check "...and its console isn't" sh -c '! docker exec "$0" curl -s -o /dev/null --max-time 5 http://node-a:8091/' "$ctb"

for ct in "$cta" "$ctb"; do docker exec "$ct" systemctl stop cron; done
log "an import on node-a, and its weekly archive"
tzc="wifidb_client_check=1; wifidb_client_timezone=0; wifidb_client_dst=0"
pass="correct horse battery staple"
hash=$(docker exec -e P="$pass" "$cta" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
sqlq "INSERT INTO user_info (username, password, uid, email, validated, permissions) VALUES ('tuser', '$hash', 'tu1', 'tu@example.test', 0, '0001')" >/dev/null
session=$(docker exec "$cta" curl -s -D - -o /dev/null -H "Cookie: $tzc" -X POST --data-urlencode "time_user=tuser" --data-urlencode "time_pass=$pass" \
    'http://127.0.0.1/login.php?func=login_proc' | sed -n 's/^[Ss]et-[Cc]ookie: WiFiDB_login_yes=\([^;]*\);.*/\1/p' | head -1)
today=$(date -u +%F)
docker exec -e D="$today" "$cta" sh -c 'printf "%s\n" "WigleWifi-1.4,appRelease=2.53,model=Test,release=11,device=test,display=x,board=x,brand=x" \
    "MAC,SSID,AuthMode,FirstSeen,Channel,RSSI,CurrentLatitude,CurrentLongitude,AltitudeMeters,AccuracyMeters,Type" \
    "00:11:22:33:44:01,Swarm,[WPA2-PSK-CCMP][ESS],$D 10:00:00,6,-50,42.3601000,-71.0589000,10,5,WIFI" \
    "00:11:22:33:44:02,SwarmOpen,[ESS],$D 10:00:10,11,-70,42.3603000,-71.0591000,10,5,WIFI" > /var/tmp/wigle.csv'
docker exec "$cta" curl -s -o /dev/null -H "Cookie: $tzc; WiFiDB_login_yes=$session" -F "upload[]=@/var/tmp/wigle.csv" -F user=tuser \
    -F title=Swarm -F type=wiglewificsv 'http://127.0.0.1/import/index.php?func=import'
docker exec "$cta" runuser -u www-data -- php /opt/wifidb/tools/daemon/importd.php -f -o -v >/dev/null 2>&1
check "imported" test "$(sqlq "select count(*) from wifi_ap where SSID like 'Swarm%'")" = 2
docker exec "$cta" runuser -u www-data -- php /opt/wifidb/tools/daemon/mvtd.php --bucket weekly --force > "$work/mvtd.out" 2>&1
build=$(docker exec "$cta" sh -c 'cd /srv/wifidb-data/out/pmtiles && ls weekly-*.pmtiles 2>/dev/null | tail -1')
check "node-a built it, named by the date and time" sh -c 'echo "$0" | grep -Eq "^weekly-[0-9]{14}\.pmtiles$"' "$build"
check "...with the stable name pointing at it" test "$(docker exec "$cta" stat -c %i "/srv/wifidb-data/out/pmtiles/$build")" = "$(docker exec "$cta" stat -c %i /srv/wifidb-data/out/pmtiles/weekly.pmtiles)"
check "...in the swarm's group" test "$(docker exec "$cta" stat -c %G "/srv/wifidb-data/out/pmtiles/$build")" = pmtiles-swarm
[ -n "$build" ] || tail -20 "$work/mvtd.out"

tilejson() { docker exec "$1" curl -fs "${@:2}" http://127.0.0.1:8090/latest/wifidb-weekly/tiles.json; }
infohash() { # read in the container: the host needn't have PHP
    docker exec "$1" php -r '$j = json_decode((string)@file_get_contents("http://127.0.0.1:8090/latest/wifidb-weekly/tiles.json"), true);
        echo is_array($j) ? ($j["torrent"]["infohash"] ?? "") : "";'
}
for _ in $(seq 60); do [ -n "$(infohash "$cta")" ] && break; sleep 2; done
ih=$(infohash "$cta")
check "node-a's swarm published it (wifidb-weekly)" sh -c 'echo "$0" | grep -Eq "^[0-9a-f]{40}$"' "$ih"
check "...with HAProxy's address in the URLs it hands out (X-Forwarded-Proto and Host)" sh -c 'echo "$0" | grep -q "https://data.wifidb.test/"' \
    "$(tilejson "$cta" -H 'X-Forwarded-Proto: https' -H 'Host: data.wifidb.test')"
docker exec "$cta" runuser -u www-data -- /opt/wifidb/tools/cron/update_swarm_index
check "node-a records the build for the map page (swarm_archives)" test "$(sqlq "select infohash from swarm_archives where bucket = 'weekly'")" = "$ih"

log "node-b mirrors it"
docker exec "$ctb" curl -s -o /dev/null -X POST -H "Authorization: Bearer $bkey" http://127.0.0.1:8091/api/subscriptions/refresh
for _ in $(seq 90); do
    docker exec "$ctb" test -f "/srv/wifidb-data/out/pmtiles/$build" && docker exec "$ctb" test -f /srv/wifidb-data/out/pmtiles/weekly.pmtiles && break
    sleep 2
done
check "node-b downloaded it into its archive folder, under the same name" docker exec "$ctb" test -f "/srv/wifidb-data/out/pmtiles/$build"
check "...the same bytes" test "$(docker exec "$cta" sha256sum "/srv/wifidb-data/out/pmtiles/$build" | cut -d' ' -f1)" = \
    "$(docker exec "$ctb" sha256sum "/srv/wifidb-data/out/pmtiles/$build" 2>/dev/null | cut -d' ' -f1)"
check "...with the stable name its site reads pointing at it" test "$(docker exec "$ctb" stat -c %i "/srv/wifidb-data/out/pmtiles/$build" 2>/dev/null)" = "$(docker exec "$ctb" stat -c %i /srv/wifidb-data/out/pmtiles/weekly.pmtiles 2>/dev/null)"
check "...and its site serves tiles from it" test "$(docker exec "$ctb" curl -s -o /dev/null -w '%{http_code}' -H "Cookie: $tzc" 'http://127.0.0.1/api/mvt.php?z=0&x=0&y=0&bucket=weekly')" = 200
check "...and its swarm too, the same build" test -n "$ih" -a "$(infohash "$ctb")" = "$ih"
before=$(sqlq "select checked_at from swarm_archives where bucket = 'weekly'")
docker exec "$ctb" runuser -u www-data -- /opt/wifidb/tools/cron/update_swarm_index
check "node-b leaves the record to node-a, which builds" test "$(sqlq "select checked_at from swarm_archives where bucket = 'weekly'")" = "$before"
if [ "$(docker exec "$ctb" test -f /srv/wifidb-data/out/pmtiles/weekly.pmtiles && echo y)" != y ]; then
    docker exec "$ctb" journalctl -u pmtiles-swarm --no-pager -o cat | tail -25 | sed 's/^/       /'
fi
docker exec "$ctb" sh -c 'echo partial > /srv/wifidb-data/out/pmtiles/weekly-20990101000000.pmtiles.incomplete'
check "an archive still downloading isn't served" test "$(docker exec "$ctb" curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/out/pmtiles/weekly-20990101000000.pmtiles.incomplete)" = 403

log "switching it off"
docker exec "$ctb" sed -i 's/^WIFIDB_SWARM=1$/WIFIDB_SWARM=0/' /etc/wifidb/firstboot.env
docker exec "$ctb" /usr/local/sbin/wifidb-firstboot >> "$work/again.out" 2>&1
check "WIFIDB_SWARM=0 stops it" sh -c '! docker exec "$0" systemctl is-active -q pmtiles-swarm && ! docker exec "$0" systemctl is-enabled -q pmtiles-swarm' "$ctb"
check "...and keeps what it had" docker exec "$ctb" test -f /srv/wifidb-data/swarm/swarm.config.json

[ $fail = 0 ] && log "all checks passed" || log "some checks failed"
exit $fail
