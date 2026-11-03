# Sourced by the boot tests: the site's own traffic counts (lib/traffic.inc.php). Page views and events go to the node's
# spool, tools/cron/traffic adds them to the database, and admin/traffic.php shows them. Needs from the caller: ct, sqlq,
# tzc, ok/bad/check/log; and from test-linked-logins.inc.sh, $session (a member), $s4 (ll2fa, an administrator) and
# ll_code (ll2fa's recovery codes: 1-4 are used there).

log "traffic counts"
tr_flush() { docker exec "$ct" runuser -u www-data -- /opt/wifidb/tools/cron/traffic; }
tr_views() { sqlq "select coalesce(sum(pageviews), 0) from traffic_counts where kind = '$1' and item = '$2' and span = 'day'"; }
tr_get() { docker exec "$ct" curl -s -o /dev/null -H "Cookie: $tzc" "$@"; }
spool=/srv/wifidb-data/log/traffic/pending.log
tr_flush   # what the tests before counted, out of the way
before=$(tr_views site '')
for _ in 1 2 3; do tr_get -A 'Mozilla/5.0 (traffic test)' -H 'CF-IPCountry: SE' http://127.0.0.1/stats.php; done
tr_get -A 'Googlebot/2.1 (+http://www.google.com/bot.html)' http://127.0.0.1/stats.php
check "page views wait in the node's spool, not the database" docker exec "$ct" test -s "$spool"
tr_flush
check "the cron job adds them: three (the bot isn't counted)" test "$(( $(tr_views site '') - before ))" = 3
check "...by page" test "$(tr_views page stats.php)" -ge 3
check "...and by country, as one visitor" test "$(sqlq "select visitors from traffic_counts where kind = 'country' and item = 'SE' and span = 'day'")" = 1
check "...and the spool is emptied" docker exec "$ct" test ! -s "$spool"
check "no visitor's address is kept" sh -c '! echo "$0" | grep -q "127.0.0.1"' "$(sqlq "select visitor from traffic_visitors")"
tr_get -A 'Mozilla/5.0 (traffic test)' -X POST --data-urlencode time_user=nobody-here --data-urlencode time_pass=wrong 'http://127.0.0.1/login.php?func=login_proc'
tr_flush
check "a failed sign-in is counted" test "$(tr_views event login_failed)" -ge 1

check "the traffic page is for administrators" test "$(docker exec "$ct" curl -s -o /dev/null -w '%{http_code}' -H "Cookie: $tzc; WiFiDB_login_yes=$session" http://127.0.0.1/admin/traffic.php)" = 403
docker exec "$ct" curl -s -o /dev/null -X POST --data-urlencode "code=$(ll_code 5)" -H "Cookie: $tzc; WiFiDB_login_yes=$s4" 'http://127.0.0.1/cp/admin_mode.php?func=proc'
page=$(docker exec "$ct" curl -s -H "Cookie: $tzc; WiFiDB_login_yes=$s4" http://127.0.0.1/admin/traffic.php)
check "...which in admin mode shows the counts" sh -c 'echo "$0" | grep -q "By day of the week" && echo "$0" | grep -q "<td>SE</td>" && echo "$0" | grep -q "Failed sign-ins"' "$page"
check "...with no PHP errors" sh -c '! echo "$0" | grep -qE "Fatal error|Uncaught|Warning:|Deprecated:|SQLSTATE"' "$page"
docker exec "$ct" curl -s -o /dev/null -H "Cookie: $tzc; WiFiDB_login_yes=$s4" 'http://127.0.0.1/cp/admin_mode.php?func=off'
