# Sourced by the boot tests: Google Analytics, AdSense and the cookie banner as Site Settings sets them, and who is asked
# (lib/cookie_consent.inc.php, by the country: lib/visitor_country.inc.php). Needs from the caller: ct, tzc,
# ok/bad/check/log.

log "analytics, ads and the cookie banner"
sm_page() { docker exec "$ct" curl -s -H "Cookie: $tzc" "$@" http://127.0.0.1/; }
sm_set() { # sm_set PHP-ARRAY: saves those site settings
    docker exec -i "$ct" php -r 'foreach (array("helpers", "config", "site_settings") as $f) { require_once "/var/www/wifidb/lib/$f.inc.php"; }
        (new SiteSettings(wifidb_pdo_from_config($config), $config))->Save('"$1"', "test");'
}
sm_asked() { echo "$1" | grep -q "WIFIDB_CONSENT = { ask: true }"; }

page=$(sm_page)
check "with no IDs set, nothing of Google's is on the page" sh -c '! echo "$0" | grep -qE "googletagmanager|adsbygoogle|G-S1ZD9TQYZK|pub-2350008141216245|cookie-consent.js"' "$page"
check "the template has the country database on the data disk" docker exec "$ct" test -s /srv/wifidb-data/geoip/dbip-country-lite.mmdb
check "...which update_geoip leaves while it's new" sh -c 'docker exec "$0" runuser -u www-data -- php /opt/wifidb/tools/utilites/update_geoip.php | grep -q "up to date"' "$ct"

sm_set 'array("analytics_id" => "G-TEST12345", "adsense_client" => "ca-pub-1234567890123456", "adsense_slot" => "9876543210", "adsense_slot_menu" => "1112223334")'
page=$(sm_page)
check "with them set, the page has the Analytics tag" sh -c 'echo "$0" | grep -q "googletagmanager.com/gtag/js?id=G-TEST12345" && echo "$0" | grep -q "gtag(.config., .G-TEST12345."' "$page"
check "...and the ad" sh -c 'echo "$0" | grep -q "adsbygoogle.js?client=ca-pub-1234567890123456" && echo "$0" | grep -q "data-ad-slot=\"9876543210\""' "$page"
check "...and in the mobile theme the one under the menu" sh -c 'echo "$0" | grep -q "data-ad-slot=\"1112223334\""' \
    "$(docker exec "$ct" curl -s -H "Cookie: $tzc; wifidb_theme=vistumbler_mobile" http://127.0.0.1/)"
check "...with consent mode saying no until the visitor allows it" sh -c 'echo "$0" | grep -q "gtag(.consent., .default., { .ad_storage.: .denied."' "$page"
check "...and the footer's Cookie choices and DB-IP's credit" sh -c 'echo "$0" | grep -q "WifiDBCookieChoices" && echo "$0" | grep -q "IP Geolocation by DB-IP"' "$page"
check "the banner script is served" test "$(docker exec "$ct" curl -s -o /dev/null -w '%{http_code}' http://127.0.0.1/lib/js/cookie-consent.js)" = 200
# where it's required (the default): by the country of the address, or a CDN's word for it
check "where it's required, a visitor from Germany is asked" sm_asked "$(sm_page -H 'CF-IPCountry: DE')"
check "...one from the UK too" sm_asked "$(sm_page -H 'CF-IPCountry: GB')"
check "...one from the US isn't" sh -c '! echo "$0" | grep -q "ask: true" && echo "$0" | grep -q "WIFIDB_CONSENT = { ask: false }"' "$(sm_page -H 'CF-IPCountry: US')"
check "...and one whose country can't be told is (a private address)" sm_asked "$(sm_page)"
check "...as is one who already chose, without a lookup (the browser has the choice)" sm_asked "$(sm_page -H "Cookie: $tzc; vi_cookie_consent=%7B%7D")"
sm_set 'array("analytics_consent" => "ask")'
check "asking every visitor asks one from the US" sm_asked "$(sm_page -H 'CF-IPCountry: US')"
sm_set 'array("analytics_consent" => "off")'
check "not asking doesn't ask one from Germany" sh -c '! echo "$0" | grep -q "ask: true"' "$(sm_page -H 'CF-IPCountry: DE')"
sm_set 'array("analytics_id" => "", "adsense_client" => "", "adsense_slot" => "", "adsense_slot_menu" => "", "analytics_consent" => "region")'
check "clearing them takes Google's tags away again" sh -c '! echo "$0" | grep -qE "googletagmanager|adsbygoogle"' "$(sm_page)"
