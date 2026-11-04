# Sourced by the boot tests after an import: the maintenance utilities in tools/utilites run on the database under test
# without a PHP or SQL error, and do what they're for. Needs from the caller: ct, sqlq (rows with | between columns),
# errors (the error pattern), ok/bad/check/log, and at least one imported file.

log "maintenance utilities"
# names quoted as the database under test wants them: \`x\` on MariaDB (where "x" is a string), "x" on the others
Q() { if [ "${type:-mysql}" = mysql ]; then printf '`%s`' "$1"; else printf '"%s"' "$1"; fi; }
errors=${errors:-'Undefined (constant|variable|array key)|Fatal error|Uncaught|Parse error|Stack trace|Call to undefined|SQLSTATE|PDOException'}
if [ "$(sqlq 'select count(*) from files where completed = 1')" = 0 ]; then
    log "(nothing has been imported: the utilities are not tested)"
else
# every notice reported (the template's settings leave deprecations out), so PHP 9's are caught too
PHPALL="-d error_reporting=-1 -d log_errors=1 -d error_log="
util() { docker exec "$ct" runuser -u www-data -- php $PHPALL "/opt/wifidb/tools/utilites/$1" "${@:2}" 2>&1; }
ran() { # ran NAME OUTPUT: the utility gave no PHP or SQL error, nor a warning (a file it couldn't write is one) or deprecation
    if echo "$2" | grep -qE "$errors|PHP Warning|Deprecated"; then bad "$1 runs without an error"; echo "$2" | grep -E "$errors|PHP Warning|Deprecated" | head -4 | sed 's/^/       /'; else ok "$1 runs without an error"; fi
}

out=$(util update_files_validgps.php); ran update_files_validgps.php "$out"
check "...and checks the finished files" sh -c 'echo "$0" | grep -q "^Checked [1-9]"' "$out"

out=$(util NewAPPercentCalc.php); ran NewAPPercentCalc.php "$out"
check "...and agrees with what the importer worked out (nothing to change)" sh -c 'echo "$0" | grep -q "changed 0"' "$out"

# take an access point's summary away, then have update_high_gps.php put it back from the readings (one the importer
# made, so the summary it starts with is the importer's; the boot test's own made-up one has none)
apx=$(sqlq "select min($(Q AP_ID)) from wifi_ap where $(Q File_ID) is not null")
points=$(sqlq "select points from wifi_ap where $(Q AP_ID) = $apx")
sqlq "UPDATE wifi_ap SET points = 0, high_rssi = NULL, $(Q HighGps_ID) = NULL WHERE $(Q AP_ID) = $apx" >/dev/null
out=$(util update_high_gps.php --from "$apx" --to "$apx"); ran update_high_gps.php "$out"
check "...and works an access point's summary out again from its readings" test "$(sqlq "select points from wifi_ap where $(Q AP_ID) = $apx")" = "$points"
check "...including its best GPS point" test -n "$(sqlq "select $(Q HighGps_ID) from wifi_ap where $(Q AP_ID) = $apx")"

sqlq "UPDATE wifi_ap SET fa = NULL WHERE $(Q AP_ID) = $apx" >/dev/null
out=$(util fix_dates.php); ran fix_dates.php "$out"
check "...and fills an emptied first active date from the readings" test -n "$(sqlq "select fa from wifi_ap where $(Q AP_ID) = $apx")"

out=$(util geojson.php); ran geojson.php "$out"
check "...and writes the GeoJSON files by age, as JSON" docker exec "$ct" php -r 'foreach (array("0to1year", "1to2year", "2to3year", "Legacy") as $b) { $j = json_decode(file_get_contents("/var/www/wifidb/out/geojson/WifiDB_$b.json")); if (!is_object($j)) { exit(1); } }'

# GeoNames, from the real download site (skipped if it can't be reached)
if docker exec "$ct" curl -sf -m 30 -o /dev/null https://download.geonames.org/export/dump/countryInfo.txt; then
    out=$(util load_geonames.php --countrynames --admin1 --download --dir=/var/tmp/geonames --replace); ran "load_geonames.php --download (countryInfo.txt, admin1CodesASCII.txt)" "$out"
    check "...and loads every country" test "$(sqlq 'select count(*) from geonames_country_names')" -gt 200
    check "...with the hyphenated and spaced columns filled" test "$(sqlq "select count(*) from geonames_country_names where $(Q ISO-Numeric) = '840' and $(Q 'Postal Code Format') = '#####-####'")" = 1
    check "...and the first-level regions" test "$(sqlq 'select count(*) from geonames_admin1')" -gt 3000
    check "...names with apostrophes included" test "$(sqlq "select count(*) from geonames_admin1 where name like '%''%'")" -gt 0
    regions=$(sqlq 'select count(*) from geonames_admin1')
    sqlq "UPDATE geonames_admin1 SET name = 'Changed' WHERE admin1 = 'US.MA'" >/dev/null
    sqlq "INSERT INTO geonames_admin1 (admin1, name, asciiname) VALUES ('ZZ.98', 'Gone', 'Gone')" >/dev/null
    out=$(util load_geonames.php --admin1 --file=/var/tmp/geonames/admin1CodesASCII.txt --keep); ran "load_geonames.php --file again, --keep" "$out"
    check "...updates the regions in place: none twice" test "$(sqlq 'select count(*) from geonames_admin1')" = "$((regions + 1))"
    check "...one that was changed is GeoNames' again" test "$(sqlq "select name from geonames_admin1 where admin1 = 'US.MA'")" = Massachusetts
    check "...and with --keep, one GeoNames doesn't have stays" test "$(sqlq "select count(*) from geonames_admin1 where admin1 = 'ZZ.98'")" = 1
    out=$(util load_geonames.php --admin1 --file=/var/tmp/geonames/admin1CodesASCII.txt); ran "load_geonames.php --file again" "$out"
    check "...without it, that one is taken out, and only that one" sh -c 'test "$0" = 0 && test "$1" = "$2"' \
        "$(sqlq "select count(*) from geonames_admin1 where admin1 = 'ZZ.98'")" "$(sqlq 'select count(*) from geonames_admin1')" "$regions"
    out=$(util load_geonames.php --countrynames --download --dir=/var/tmp/geonames); ran "load_geonames.php --countrynames again, without --replace" "$out"
    check "...every country still once" test "$(sqlq "select count(*) from geonames_country_names where $(Q ISO) = 'US'")" = 1
    out=$(util load_geonames.php --geonames --set=cities15000 --download --dir=/var/tmp/geonames --replace); ran "load_geonames.php --set=cities15000 (read from its zip)" "$out"
    check "...and loads the towns" test "$(sqlq 'select count(*) from geonames')" -gt 20000
    near=$(docker exec "$ct" curl -s 'http://127.0.0.1/api/geonames.php?lat=42.3601&long=-71.0589')
    check "api/geonames.php finds the nearest place, measured from the point asked about" \
        sh -c 'echo "$0" | grep -q "\"Area Name\":\"Boston\"" && echo "$0" | grep -qE "\"miles\":\"?0\."' "$near" ||
        echo "$near" | head -c 300 | sed 's/^/       /'
    check "...and turns away a lat that isn't a number" sh -c 'echo "$0" | grep -q "have to be numbers"' \
        "$(docker exec "$ct" curl -s 'http://127.0.0.1/api/geonames.php?lat=abc&long=-71')"
else
    log "(download.geonames.org can't be reached from here: the GeoNames load is not tested)"
fi

# the stats daemon, through its schedule row (migration 0007), as cron starts it
out=$(docker exec "$ct" runuser -u www-data -- php $PHPALL /opt/wifidb/tools/daemon/statsd.php -o -v 2>&1 | tr -d '\0'); ran statsd.php "$out"
check "...and fills the stats cache" test "$(sqlq 'select count(*) from stats_cache')" -gt 0
out=$(docker exec "$ct" runuser -u www-data -- php $PHPALL /opt/wifidb/tools/daemon/statsd.php -o -v 2>&1 | tr -d '\0')
check "...and started again at once, waits for its next run" sh -c 'echo "$0" | grep -q "There are no jobs that need to be run"' "$out"
# a run that died (a restart mid-run) leaves its row 'Running' with its pid: the next run takes it back
sqlq "UPDATE schedule SET status = 'Running', pid = 999999, nextrun = '2000-01-01 00:00:00' WHERE daemon = 'Stats'" >/dev/null
out=$(docker exec "$ct" runuser -u www-data -- php $PHPALL /opt/wifidb/tools/daemon/statsd.php -o -v 2>&1 | tr -d '\0')
check "...and takes back a schedule row left 'Running' by a run that died" sh -c 'echo "$0" | grep -q "left running by a process that.s gone" && echo "$0" | grep -q "Statistics cache generation complete"' "$out"

# last, as it removes data: one file's readings and GPS points
fid=$(sqlq "select min(id) from files where completed = 1")
out=$(util file_cleanup_signal_data.php "$fid"); ran file_cleanup_signal_data.php "$out"
check "...and removes that file's readings and GPS points" test "$(sqlq "select (select count(*) from wifi_hist where $(Q File_ID) = $fid) + (select count(*) from wifi_gps where $(Q File_ID) = $fid)")" = 0
fi
