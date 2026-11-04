# Sourced by the boot tests: the AP search (lib/searchconditions.inc.php) on the search page, its exports and
# api/search.php, with each field's match (equals, contains, starts, ends). Needs from the caller: ct, sqlq, ok/bad/check/log,
# and type (pgsql or sqlsrv; unset for MariaDB).

log "searching for access points"
if [ -n "${type:-}" ]; then sq() { echo "\"$1\""; }; else sq() { echo "$1"; }; fi
for row in "SrchCafe|6|01" "SrchCafe Shop|11|02" "MySrchCafe|1|03"; do
    IFS='|' read -r s_ssid s_chan s_n <<< "$row"
    sqlq "INSERT INTO wifi_ap ($(sq BSSID), $(sq SSID), $(sq CHAN), $(sq AUTH), $(sq ENCR), $(sq RADTYPE), $(sq NETTYPE), $(sq ap_hash), fa, la)
        VALUES ('00:5E:00:53:00:$s_n', '$s_ssid', $s_chan, 'WPA2-Personal', 'CCMP', '802.11n', 'Infrastructure', 'search-test-$s_n', '2026-01-01 10:00:00', '2026-01-01 10:00:00')" >/dev/null
done
s_errors='Undefined (constant|variable|array key)|Fatal error|Uncaught|Parse error|Stack trace|Call to undefined|SQLSTATE|PDOException'
s_get() { docker exec "$ct" curl -s -H "Cookie: wifidb_client_check=1; wifidb_client_timezone=0; wifidb_client_dst=0" "http://127.0.0.1$1"; }
s_found() { # s_found PATH COUNT: the search page finds COUNT access points, with no error
    local out; out=$(s_get "$1")
    echo "$out" | grep -qE "$s_errors" && { echo "$out" | grep -oE ".{0,120}($s_errors).{0,160}" | head -2 | sed 's/^/       /'; return 1; }
    echo "$out" | grep -q "Search Results: $2 Points"
}
s_api() { # s_api QUERY SSID...: api/search.php answers with exactly these access points
    local out; out=$(s_get "/api/search.php?$1"); shift
    echo "$out" | grep -qE "$s_errors" && { echo "$out" | head -c 300 | sed 's/^/       /'; return 1; }
    test "$(echo "$out" | grep -o '"ssid":"[^"]*"' | sort | tr '\n' ' ')" = "$(for s in "$@"; do echo "\"ssid\":\"$s\""; done | sort | tr '\n' ' ')"
}
r='/opt/results.php?sort=SSID&ord=ASC'
check "a search matches the whole SSID unless told otherwise" s_found "$r&ssid=SrchCafe" 1
check "...a part of one with contains" s_found "$r&ssid=SrchCafe&ssid_op=contains" 3
check "...its start with starts" s_found "$r&ssid=SrchCafe&ssid_op=starts" 2
check "...its end with ends" s_found "$r&ssid=SrchCafe&ssid_op=ends" 2
check "...a % as a pattern, whatever the match says" s_found "$r&ssid=%25Cafe%20Shop" 1
check "...fields together, the channel among them" s_found "$r&ssid=SrchCafe&ssid_op=starts&chan=11" 1
check "...a channel that isn't a number finds nothing, without an error" s_found "$r&chan=abc" 0
check "...and a search with every field blank is turned away" sh -c 'echo "$0" | grep -q "cant search for all of something"' "$(s_get "$r&ssid=%20")"
check "the results' links repeat the search's matches" sh -c 'echo "$0" | grep -q "api/geojson.php?func=exp_search&ssid=SrchCafe&[^\"]*sectype=&ssid_op=starts&mac_op=equals"' \
    "$(s_get "$r&ssid=SrchCafe&ssid_op=starts")"
check "...and its GeoJSON export finds the same" sh -c 'test "$(echo "$0" | grep -o "SrchCafe" | wc -l)" -ge 2 && ! echo "$0" | grep -q "MySrchCafe"' \
    "$(s_get '/api/geojson.php?func=exp_search&ssid=SrchCafe&ssid_op=starts&json=1&no_gps=1')"
check "api/search.php matches the whole SSID by default" s_api "ssid=SrchCafe" "SrchCafe"
check "...takes the same matches" s_api "ssid=Cafe&ssid_op=ends" "SrchCafe" "MySrchCafe"
check "...and a % pattern as before" s_api "ssid=Srch%25" "SrchCafe" "SrchCafe Shop"
sqlq "DELETE FROM wifi_ap WHERE $(sq ap_hash) LIKE 'search-test-%'" >/dev/null
