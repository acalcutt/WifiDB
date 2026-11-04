# Sourced by the boot tests: the live API (api/live.php) as Vistumbler uses it, version 2: a session started with a
# member's username and API key, access points sent with it, and the session finished, after which what was sent is
# imported as that member's (lib/live_session.inc.php). Needs from the caller: ct, sqlq, ok/bad/check/log.

log "live API sessions"
docker exec "$ct" systemctl stop cron   # the importer is run here, not raced
sqlq "INSERT INTO user_info (username, password, uid, email, validated, permissions, admin, apikey) VALUES ('liveuser', 'x', 'lu1', 'lu@example.test', 0, '1000', 0, 'livetestkey')" >/dev/null
lv() { docker exec "$ct" curl -s "$@"; }
lv_ap() { # lv_ap SESSION LAT TIME SIGNAL: an access point as Vistumbler's Export.exe sends it
    lv -G 'http://127.0.0.1/api/live.php' --data-urlencode "SessionID=$1" --data-urlencode 'SSID=Live & Well' --data-urlencode 'Mac=00163E00AB01' \
        --data-urlencode 'Auth=WPA2-Personal' --data-urlencode 'SecType=3' --data-urlencode 'Encry=CCMP' --data-urlencode 'Rad=802.11n' --data-urlencode 'Chn=6' \
        --data-urlencode "Lat=$2" --data-urlencode 'Long=-7103.5000' --data-urlencode 'Date=2026-11-03' --data-urlencode "Time=$3" --data-urlencode 'NT=Infrastructure' \
        --data-urlencode "Sig=$4" --data-urlencode 'RSSI=-60' --data-urlencode 'Sats=7' --data-urlencode 'username=someone-else'
}
check "live.php says it's version 2" sh -c 'echo "$0" | grep -q "\"LiveVersion\":2"' "$(lv 'http://127.0.0.1/api/live.php?LiveVersion=1')"
check "...turns away a session with the wrong API key" sh -c 'echo "$0" | grep -q "\"error\""' \
    "$(lv -F username=liveuser -F apikey=wrong -F 'title=Live test' -F notes= http://127.0.0.1/api/live.php)"
start=$(lv -F username=liveuser -F apikey=livetestkey -F 'title=Live test' -F 'notes=from the boot test' http://127.0.0.1/api/live.php)
sid=$(echo "$start" | grep -oE '"SessionID":"[0-9a-f]{64}"' | cut -d'"' -f4)
check "...starts one with the right one" test -n "$sid" || echo "$start" | head -c 300 | sed 's/^/       /'
out1=$(lv_ap "$sid" 4221.6000 14:00:01.000 60)
out2=$(lv_ap "$sid" 4221.7000 14:00:05.000 80)
check "...takes the access points sent with it" sh -c '! echo "$0 $1" | grep -qE "error|SQLSTATE|Fatal"' "$out1" "$out2" || echo "$out1 / $out2" | head -c 400 | sed 's/^/       /'
check "...as the session's member's, not the username sent with them" test "$(sqlq "select username from live_aps where session_id = '$sid'")" = liveuser
done_out=$(lv -G 'http://127.0.0.1/api/live.php' --data-urlencode completed=1 --data-urlencode "SessionID=$sid")
check "finishing the session queues it for import" sh -c 'echo "$0" | grep -q "\"importnum\"" && echo "$0" | grep -q "\"aps\":1"' "$done_out" ||
    echo "$done_out" | head -c 300 | sed 's/^/       /'
check "...as the member's, with the session's title" test "$(sqlq "select file_user from files_tmp where title = 'Live test'")" = liveuser
check "...and only once" sh -c 'echo "$0" | grep -q "already finished"' \
    "$(lv -G 'http://127.0.0.1/api/live.php' --data-urlencode completed=1 --data-urlencode "SessionID=$sid")"
check "...after which it takes no more" sh -c 'echo "$0" | grep -q "is finished"' "$(lv_ap "$sid" 4221.8000 14:00:09.000 70)"
imp=$(docker exec "$ct" runuser -u www-data -- php /opt/wifidb/tools/daemon/importd.php -f -o -v 2>&1)
check "the importer imports it" test "$(sqlq "select count(*) from files where title = 'Live test' and file_user = 'liveuser' and completed = 1")" = 1 ||
    echo "$imp" | tail -8 | sed 's/^/       /'
check "...with the access point and both its readings" test "$(sqlq "select points from wifi_ap where $( [ -n "${type:-}" ] && echo '"SSID"' || echo SSID ) = 'Live & Well'")" = 2
check "a session that isn't anyone's is no one's" sh -c 'echo "$0" | grep -q "\"username\":\"AnonCoward\""' \
    "$(lv -F username= -F apikey= -F 'title=Anonymous' http://127.0.0.1/api/live.php)"
docker exec "$ct" systemctl start cron
