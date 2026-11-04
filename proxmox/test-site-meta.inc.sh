# Sourced by the boot tests: Google Analytics, AdSense and the cookie banner as Site Settings sets them, and who is asked
# (lib/cookie_consent.inc.php, by the country: lib/visitor_country.inc.php), and what search engines read
# (lib/site_files.inc.php, the header). Needs from the caller: ct, sqlq, tzc,
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

log "search engines: robots.txt, the sitemaps, ads.txt and the page's own tags"
sm_get() { docker exec "$ct" curl -s "$@"; }
sm_code() { docker exec "$ct" curl -s -o /dev/null -w '%{http_code}' "$@"; }
check "robots.txt names this site's sitemap and keeps crawlers out of members' pages" sh -c \
    'echo "$0" | grep -q "^Sitemap: https://wifidb.test/sitemap.xml" && echo "$0" | grep -q "^Disallow: /cp/" && ! echo "$0" | grep -q "wifidb.net"' \
    "$(sm_get http://127.0.0.1/robots.txt)"
check "with no AdSense, there's no ads.txt" test "$(sm_code http://127.0.0.1/ads.txt)" = 404
# a finished import for the sitemaps to list (taken away again after)
sqlq "INSERT INTO files (file_name, file_orig, file_user, title, completed, hash) VALUES ('sm.vs1', 'sm.vs1', 'smuser', 'sitemap test', 1, 'sitemap-test')" >/dev/null
smid=$(sqlq "select id from files where hash = 'sitemap-test'")
index=$(sm_get http://127.0.0.1/sitemap.xml)
check "sitemap.xml lists the pages', members' and imports' sitemaps" sh -c 'for m in pages members-1 imports-1; do echo "$0" | grep -q "<loc>https://wifidb.test/sitemap-$m.xml</loc>" || exit 1; done' "$index"
check "...the main pages" sh -c 'echo "$0" | grep -q "<loc>https://wifidb.test/stats.php</loc>"' "$(sm_get http://127.0.0.1/sitemap-pages.xml)"
check "...each member with a finished import" sh -c 'echo "$0" | grep -q "func=alluserlists&amp;user=smuser</loc>"' "$(sm_get http://127.0.0.1/sitemap-members-1.xml)"
check "...and each finished import" sh -c 'echo "$0" | grep -q "func=useraplist&amp;row=$1</loc>"' "$(sm_get http://127.0.0.1/sitemap-imports-1.xml)" "$smid"
check "...and a piece past the last is a 404" test "$(sm_code http://127.0.0.1/sitemap-imports-9.xml)" = 404
sqlq "DELETE FROM files WHERE hash = 'sitemap-test'" >/dev/null
# (the MariaDB boot test's settings form, sent with only some fields, has saved the description empty by now)
sm_set 'array("site_description" => "Wireless networks & where they are")'
page=$(sm_page)
check "the page's canonical address is this site's" sh -c 'echo "$0" | grep -q "<link rel=\"canonical\" href=\"https://wifidb.test/\"" && ! echo "$0" | grep -q "https://wifidb.net"' "$page"
check "...with the site's description, and nothing keeping search engines out" sh -c \
    'echo "$0" | grep -q "<meta name=\"description\" content=\"Wireless networks &amp; where they are\"" && ! echo "$0" | grep -q "noindex"' "$page" ||
    echo "$page" | grep -n -i -o '.\{60\}noindex.\{30\}\|<meta name=.description[^>]*>' | head -3 | sed 's/^/       /'
sm_set 'array("adsense_client" => "ca-pub-1234567890123456", "google_site_verification" => "verify-me-google_123", "bing_site_verification" => "0123456789ABCDEF")'
check "with AdSense, ads.txt has its line" sh -c 'test "$0" = "google.com, pub-1234567890123456, DIRECT, f08c47fec0942fa0"' "$(sm_get http://127.0.0.1/ads.txt)"
check "...or whatever Site Settings says" sh -c 'test "$(docker exec "$0" curl -s http://127.0.0.1/ads.txt)" = "example.com, 1, RESELLER"' "$ct" \
    "$(sm_set 'array("ads_txt" => "example.com, 1, RESELLER")')"
check "the verification codes are on the page" sh -c 'echo "$0" | grep -q "google-site-verification\" content=\"verify-me-google_123\"" && echo "$0" | grep -q "msvalidate.01\" content=\"0123456789ABCDEF\""' "$(sm_page)"
sm_set 'array("search_noindex" => true)'
check "keeping search engines out: every page says noindex" sh -c 'echo "$0" | grep -q "<meta name=\"robots\" content=\"noindex, nofollow\""' "$(sm_page)"
check "...robots.txt turns all crawlers away" sh -c 'echo "$0" | grep -q "^Disallow: /$" && ! echo "$0" | grep -q "Sitemap:"' "$(sm_get http://127.0.0.1/robots.txt)"
check "...and there's no sitemap" test "$(sm_code http://127.0.0.1/sitemap.xml)" = 404
sm_set 'array("search_noindex" => false, "adsense_client" => "", "ads_txt" => "", "google_site_verification" => "", "bing_site_verification" => "")'

log "the map's elevation tiles"
sm_map() { docker exec "$ct" curl -s -H "Cookie: $tzc; wifidb_theme=vistumbler_mobile" 'http://127.0.0.1/opt/map.php?func=wifidbmap'; }
check "the mobile map draws contours from tiles.wifidb.net unless told otherwise" sh -c 'echo "$0" | grep -qF "url: '"'"'https://tiles.wifidb.net/data/jaxa_terrarium/{z}/{x}/{y}.png'"'"'"' "$(sm_map)"
sm_set 'array("map_dem_url" => "https://dem.example.test/{z}/{x}/{y}.png")'
check "...from the address Site Settings gives" sh -c 'echo "$0" | grep -q "dem.example.test" && ! echo "$0" | grep -q "jaxa_terrarium"' "$(sm_map)"
sm_set 'array("map_dem_url" => "")'
check "...and with none, it draws none" sh -c '! echo "$0" | grep -q "DemSource\|contour_source"' "$(sm_map)"
sm_set 'array("map_dem_url" => "https://tiles.wifidb.net/data/jaxa_terrarium/{z}/{x}/{y}.png")'
