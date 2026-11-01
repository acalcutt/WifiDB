# Sourced by the boot tests when IMPORT_SAMPLES names a folder of real uploads (.vs1, .vsz, .csv, .gz, .db, .db3,
# .netxml, .ns1, .txt, .mdb): each goes through the upload page and the importer, and what came of it is written to
# IMPORT_REPORT (one line per file: done, access points, GPS points; or bad and why). Running the same folder against
# MariaDB and PostgreSQL and comparing the two reports shows whether both databases import the same.
#
# Needs from the caller: ct (the container), sqlq (runs SQL, prints rows with | between columns), tzc, ok/bad/check/log.
# The samples are people's uploads: keep the folder out of the repository.

import_type() { # import_type FILE: what the upload page's "type" would be
    case "$(basename "${1,,}")" in
        *.gz) echo wiglewificsv ;;
        *.db|*.db3) echo wardrive ;;
        *.netxml) echo kismet ;;
        *.csv)
            if head -c 60 "$1" | grep -q WigleWifi; then echo wiglewificsv
            elif head -c 60 "$1" | grep -q '^BSSID,SSID,AuthMode,channel,frequency'; then echo swardriving
            else echo vistumbler; fi ;;
        *) echo vistumbler ;;
    esac
}

samples=("$IMPORT_SAMPLES"/*)
# the upload page turns away a file it already has (by hash), so the same file twice is queued once
distinct=$(md5sum "${samples[@]}" | cut -d' ' -f1 | sort -u | wc -l)
log "importing ${#samples[@]} real uploads ($distinct different files)"
imhash=$(docker exec -e P="import pass phrase" "$ct" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
sqlq "INSERT INTO user_info (username, password, uid, email, validated, permissions) VALUES ('importer', '$imhash', 'im1', 'im@example.test', 0, '0001')" >/dev/null
imsession=$(docker exec "$ct" curl -s -D - -o /dev/null -H "Cookie: $tzc" -X POST --data-urlencode "time_user=importer" --data-urlencode "time_pass=import pass phrase" \
    'http://127.0.0.1/login.php?func=login_proc' | sed -n 's/^[Ss]et-[Cc]ookie: WiFiDB_login_yes=\([^;]*\);.*/\1/p' | head -1)
check "the importing member signs in" test -n "$imsession"
# cron starts importers every minute, which would race the one run here: it's held off (and any running left to
# finish) until the report is written
docker exec "$ct" systemctl stop cron
for _ in $(seq 90); do docker exec "$ct" pgrep -f importd.php >/dev/null || break; sleep 2; done
before=$(sqlq "select count(*) from files_tmp")
docker exec "$ct" mkdir -p /var/tmp/samples
# first, a file sent as the wrong type (a WiGLE .gz as Vistumbler; a byte added so it isn't a duplicate): it has to be
# set aside without stopping the run, so everything queued after it still imports
wrong=$(ls "$IMPORT_SAMPLES"/*.gz 2>/dev/null | head -1)
if [ -n "$wrong" ]; then
    { cat "$wrong"; echo; } > /tmp/wrong-type.$id.gz
    docker cp /tmp/wrong-type.$id.gz "$ct:/var/tmp/samples/wrong-type.gz"; rm -f /tmp/wrong-type.$id.gz
    docker exec "$ct" curl -s -o /dev/null -H "Cookie: $tzc; WiFiDB_login_yes=$imsession" -F "upload[]=@/var/tmp/samples/wrong-type.gz" \
        -F user=importer -F "title=sample zz-wrong-type.gz" -F "type=vistumbler" 'http://127.0.0.1/import/index.php?func=import'
    distinct=$((distinct + 1))
fi
for f in "${samples[@]}"; do
    base=$(basename "$f")
    docker cp "$f" "$ct:/var/tmp/samples/$base"
    docker exec "$ct" curl -s -o /dev/null -H "Cookie: $tzc; WiFiDB_login_yes=$imsession" -F "upload[]=@/var/tmp/samples/$base" \
        -F user=importer -F "title=sample $base" -F "type=$(import_type "$f")" 'http://127.0.0.1/import/index.php?func=import'
done
check "the upload page queued all $distinct, each once" test "$(sqlq "select count(*) from files_tmp")" = "$((before + distinct))"

started=$(date +%s)
imp=$(docker exec "$ct" runuser -u www-data -- php /opt/wifidb/tools/daemon/importd.php -f -o -v 2>&1 | tr -d '\0')
log "the importer took $(( $(date +%s) - started ))s"
if echo "$imp" | grep -qE 'Fatal error|Uncaught|SQLSTATE|PDOException|Stack trace'; then
    bad "the importer ran without a PHP or SQL error"
    echo "$imp" | grep -E 'Fatal error|Uncaught|SQLSTATE|PDOException' | sort | uniq -c | sort -rn | head -8 | sed 's/^/       /'
else
    ok "the importer ran without a PHP or SQL error"
fi
check "nothing is left waiting or half-imported" test "$(sqlq "select (select count(*) from files_tmp) + (select count(*) from files_importing)")" = 0 ||
    echo "$imp" | grep -v 'Deprecated\|^Insert \(GPS \)\?Size\|^Import Line Error\|^---' | tail -25 | cut -c1-200 | sed 's/^/       /'

{
    sqlq "select title, 'done', aps, gps from files where completed = 1 and title like 'sample %' order by title"
    sqlq "select title, 'bad', error_msg from files_bad where title like 'sample %' order by title"
} | sed 's/^sample //; s/ |=| .*//' | sort > /tmp/import-report.$id   # (after |=| is the upload's path, different every run)
done_n=$(grep -c '|done|' /tmp/import-report.$id)
bad_n=$(grep -c '|bad|' /tmp/import-report.$id)
log "imported $done_n, set aside $bad_n"
sed 's/^/       /' /tmp/import-report.$id | cut -c1-200
check "every upload either imported or was set aside with a reason" test "$((done_n + bad_n))" = "$distinct"
[ -n "$wrong" ] && check "...the one sent as the wrong type among those set aside" grep -q '^zz-wrong-type.gz|bad|' /tmp/import-report.$id
[ -n "${IMPORT_REPORT:-}" ] && cp /tmp/import-report.$id "$IMPORT_REPORT"
rm -f /tmp/import-report.$id
docker exec "$ct" systemctl start cron
