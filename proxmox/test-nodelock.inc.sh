# Sourced by the boot tests: lock_run.php, the database lock that keeps work one node does for all (tiles, GeoJSON,
# the nightly backup) to one node at a time. Two runs here are two connections, as two nodes would be. Needs from the
# caller: ct, ok/bad/check/log.

log "the lock that keeps shared work to one node"
lockrun() { docker exec "$ct" runuser -u www-data -- php /opt/wifidb/tools/daemon/lock_run.php "$@" 2>&1; }
docker exec -d "$ct" runuser -u www-data -- sh -c 'php /opt/wifidb/tools/daemon/lock_run.php boottest sleep 15 > /var/tmp/lockrun-first.out 2>&1'
sleep 5
out=$(lockrun boottest sh -c 'echo ran-second')
check "while one run holds it, another is turned away" sh -c 'echo "$0" | grep -q "already running" && ! echo "$0" | grep -q ran-second' "$out"
out=$(lockrun boottest-other sh -c 'echo ran-other')
check "...but a different name isn't" sh -c 'echo "$0" | grep -q ran-other' "$out"
for _ in $(seq 20); do docker exec "$ct" pgrep -f 'lock_run.php boottest sleep' >/dev/null || break; sleep 1; done
check "the first run finished and said what it ran" docker exec "$ct" grep -q "boottest on .*: sleep 15" /var/tmp/lockrun-first.out
out=$(lockrun boottest sh -c 'echo ran-third; exit 3'); code=$?
check "once it's done the next run gets it" sh -c 'echo "$0" | grep -q ran-third' "$out"
check "...and lock_run.php exits with the command's own code" test "$code" = 3
