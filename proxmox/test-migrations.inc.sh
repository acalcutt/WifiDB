# Sourced by the boot tests: with a migration the database hasn't had, the site answers 503 saying what to run and the
# daemons hold off, until migrate.php (which still runs) applies it. 0007 is the one taken away: applying it again adds
# nothing. Needs from the caller: ct, sqlq, ok/bad/check/log.

log "a migration the database hasn't had"
mcookie="Cookie: wifidb_client_check=1; wifidb_client_timezone=0"
sqlq "DELETE FROM schema_migrations WHERE id = '0007_stats_schedule'" >/dev/null
check "the site answers 503 while one is pending" test "$(docker exec "$ct" curl -s -o /var/tmp/pending.html -w '%{http_code}' -H "$mcookie" http://127.0.0.1/stats.php)" = 503
check "...saying which, and what to run" docker exec "$ct" grep -q "0007_stats_schedule.*migrate.php" /var/tmp/pending.html
check "the daemons hold off too" sh -c 'docker exec "$0" runuser -u www-data -- php /opt/wifidb/tools/daemon/schedulecheck.php Import 2>&1 | grep -q "migrate.php"' "$ct"
check "migrate.php still runs, and applies it" sh -c 'docker exec "$0" runuser -u www-data -- php /opt/wifidb/tools/utilites/migrate.php 2>&1 | grep -q "applied 0007_stats_schedule"' "$ct"
check "...and the site is back" test "$(docker exec "$ct" curl -s -o /dev/null -w '%{http_code}' -H "$mcookie" http://127.0.0.1/stats.php)" = 200
