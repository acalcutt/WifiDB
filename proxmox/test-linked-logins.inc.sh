# Sourced by the boot tests: signing in with a linked Google or GitHub account (lib/linked_logins.inc.php), end to end,
# against a stand-in provider (test-oauth-provider.php) run in the container, which "signs in" whoever /tmp/oauth-subject
# names. Needs from the caller: ct, sqlq (rows with | between columns), tzc, ok/bad/check/log. The site's cookies are
# for https://wifidb.test, so as in test-two-factor.inc.sh they're carried in headers by hand.

log "linked accounts (sign in with Google or GitHub)"
ll_site() { docker exec "$ct" curl -s -D - "$@"; }                       # headers, then the page
ll_loc() { sed -n 's/^[Ll]ocation: \(.*\)\r*$/\1/p' | tr -d '\r' | head -1; }
ll_cookie() { sed -n "s/^[Ss]et-[Cc]ookie: $1=\([^;]*\);.*/\1/p" | head -1; }
ll_status() { sed -n '1s/^HTTP\/[0-9.]* \([0-9]*\).*/\1/p'; }
ll_local() { echo "$1" | sed 's#^https://wifidb.test#http://127.0.0.1#'; }
ll_subject() { docker exec "$ct" sh -c "echo '$1' > /tmp/oauth-subject"; }
# ll_back HEADERS [COOKIES]: from the site's redirect to the provider, through the provider, to the site's callback
ll_back() {
    local state auth
    state=$(echo "$1" | ll_cookie WiFiDB_login_state)
    auth=$(docker exec "$ct" curl -s -D - -o /dev/null "$(echo "$1" | ll_loc)" | ll_loc)
    ll_site -H "Cookie: $tzc; WiFiDB_login_state=$state${2:+; $2}" "$(ll_local "$auth")"
}
ll_start() { ll_subject "$1"; ll_site -H "Cookie: $tzc" "http://127.0.0.1/logins.php?func=start&provider=${2:-google}&return=%2Fall.php"; }
ll_php() { docker exec -i "$ct" php -r 'foreach (array("helpers", "config", "site_settings", "totp", "webauthn", "two_factor") as $f) { require_once "/var/www/wifidb/lib/$f.inc.php"; }
    $pdo = wifidb_pdo_from_config($config); '"$1"; }
ll_links() { sqlq "select count(*) from user_linked_logins where provider = '$1' and subject = '$2' and username = '$3'"; }
errors=${errors:-'Undefined (constant|variable|array key)|Fatal error|Uncaught|Parse error|Stack trace|Call to undefined|SQLSTATE|PDOException'}
ll_clean() { ! echo "$1" | grep -qE "$errors|Warning:|Deprecated:"; }

docker cp "$(dirname "$(readlink -f "$0")")/test-oauth-provider.php" "$ct:/var/tmp/oauth-provider.php"
docker exec -d "$ct" php -S 127.0.0.1:8099 /var/tmp/oauth-provider.php
# Google and GitHub pointed at it (linked_logins_endpoints), and turned on with the ID and secret it expects
docker exec "$ct" php -r '$f = "/srv/wifidb-data/config/web.overrides.php"; $c = include $f;
    foreach (array("google", "github") as $p) { $c["linked_logins_endpoints"][$p] = array("authorize" => "http://127.0.0.1:8099/authorize", "token" => "http://127.0.0.1:8099/token", "userinfo" => "http://127.0.0.1:8099/userinfo"); }
    file_put_contents($f, "<?php\nreturn " . var_export($c, true) . ";\n");'
ll_php '(new SiteSettings($pdo, $config))->Save(array("login_google_enabled" => true, "login_google_client_id" => "fake-google", "login_google_secret" => "fake-secret",
    "login_github_enabled" => true, "login_github_client_id" => "fake-github", "login_github_secret" => "fake-secret"), "test");'
sleep 3   # PHP rechecks a changed file after a couple of seconds
ll_pass="linked pass phrase"
ll_hash=$(docker exec -e P="$ll_pass" "$ct" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
sqlq "INSERT INTO user_info (username, password, uid, email, validated) VALUES ('lluser', '$ll_hash', 'll1', 'll@example.test', 0)" >/dev/null
# ll2fa has two-factor sign-in on (an authenticator app, and recovery codes, each used once below) and is an administrator
sqlq "INSERT INTO user_info (username, password, uid, email, validated, permissions, admin) VALUES ('ll2fa', '$ll_hash', 'll2', 'll2@example.test', 0, '1000', 1)" >/dev/null
ll_codes=$(ll_php '$tf = new TwoFactor($pdo, array("two_factor_key" => $config["two_factor_key"], "hosturl" => "https://wifidb.test"));
    $pdo->prepare("INSERT INTO user_login_hashes (username, hash, utime) VALUES (?, ?, ?)")->execute(array("ll2fa", "setup", time() + 600));
    $s = $tf->StartTotp("ll2fa", "setup");
    $d = $tf->FinishTotp("ll2fa", "setup", "test", Totp::Code($s["secret"], Totp::Step()));
    $pdo->prepare("DELETE FROM user_login_hashes WHERE hash = ?")->execute(array("setup"));
    foreach ($d["recovery_codes"] as $c) { echo "$c\n"; }')
ll_code() { echo "$ll_codes" | sed -n "${1}p"; }
check "a member with two-factor sign-in was set up" test "$(echo "$ll_codes" | wc -l)" = 10

page=$(ll_site -H "Cookie: $tzc" http://127.0.0.1/login.php)
check "the sign-in page offers Google and GitHub" sh -c 'echo "$0" | grep -q "name=\"provider\" value=\"google\"" && echo "$0" | grep -q "name=\"provider\" value=\"github\""' "$page"

# someone signs in with a Google account nobody linked
start=$(ll_start 1001)
check "Sign in with Google goes to the provider, with a state and PKCE" sh -c 'echo "$0" | grep -q "^http://127.0.0.1:8099/authorize?" && echo "$0" | grep -q "[?&]state=" && echo "$0" | grep -q "[?&]code_challenge=" && test -n "$1"' \
    "$(echo "$start" | ll_loc)" "$(echo "$start" | ll_cookie WiFiDB_login_state)"
page=$(ll_back "$start")
check "an account nobody linked is offered to be linked, naming it" sh -c 'echo "$0" | grep -q "Link your Google account" && echo "$0" | grep -q "Person 1001"' "$page"
check "...with nobody signed in" sh -c '! echo "$0" | grep -qi "^set-cookie: WiFiDB_login_yes"' "$page"
check "...and no PHP errors" ll_clean "$page"
link_cookie=$(echo "$page" | ll_cookie WiFiDB_link_pending)
link_token=$(echo "$page" | sed -n 's/.*name="token" value="\([^"]*\)".*/\1/p' | head -1)
ll_link() { ll_site -H "Cookie: $tzc; WiFiDB_link_pending=$link_cookie" -X POST --data-urlencode "token=$link_token" --data-urlencode "time_user=$1" \
    --data-urlencode "time_pass=$2" 'http://127.0.0.1/logins.php?func=link_existing'; }
check "a wrong password links nothing" sh -c 'echo "$0" | grep -q "Bad username or password" && test "$1" = 0' "$(ll_link lluser wrong)" "$(ll_links google 1001 lluser)"
page=$(ll_link lluser "$ll_pass")
check "the right password signs in and links it" sh -c 'test "$0" = /all.php && test -n "$1" && test "$2" = 1' "$(echo "$page" | ll_loc)" \
    "$(echo "$page" | ll_cookie WiFiDB_login_yes)" "$(ll_links google 1001 lluser)"
check "...and leaves the member a notice" test "$(sqlq "select count(*) from notifications where username = 'lluser' and title = 'A Google account was linked'")" = 1
check "...once: the same link page can't be used again" sh -c 'echo "$0" | grep -q "took too long"' "$(ll_link lluser "$ll_pass")"

# from now on, Sign in with Google signs lluser in
start=$(ll_start 1001)
state=$(echo "$start" | ll_cookie WiFiDB_login_state)
callback=$(ll_local "$(docker exec "$ct" curl -s -D - -o /dev/null "$(echo "$start" | ll_loc)" | ll_loc)")
page=$(ll_site -H "Cookie: $tzc; WiFiDB_login_state=$state" "$callback")
session=$(echo "$page" | ll_cookie WiFiDB_login_yes)
check "a linked account signs straight in" sh -c 'test "$0" = /all.php && test -n "$1"' "$(echo "$page" | ll_loc)" "$session"
check "...to its member" sh -c 'echo "$0" | grep -q "Linked accounts" && echo "$0" | grep -q "Person 1001"' \
    "$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$session" http://127.0.0.1/cp/security.php)"
check "...and when it was used is kept" test "$(sqlq "select count(*) from user_linked_logins where subject = '1001' and last_used is not null")" = 1
check "the same answer from the provider works only once" sh -c 'echo "$0" | grep -q "took too long"' "$(ll_site -H "Cookie: $tzc; WiFiDB_login_state=$state" "$callback")"
start=$(ll_start 1001)
abandoned=$(echo "$start" | ll_cookie WiFiDB_login_state)
check "an answer that comes back to another browser is refused" sh -c 'echo "$0" | grep -q "start in this browser" && ! echo "$0" | grep -qi "^set-cookie: WiFiDB_login_yes"' \
    "$(ll_site -H "Cookie: $tzc" "$(ll_local "$(docker exec "$ct" curl -s -D - -o /dev/null "$(echo "$start" | ll_loc)" | ll_loc)")")"

# a member with two-factor sign-in links Google from the Security page
s2=$(ll_site -H "Cookie: $tzc" -X POST --data-urlencode time_user=ll2fa --data-urlencode "time_pass=$ll_pass" 'http://127.0.0.1/login.php?func=login_proc' | ll_cookie WiFiDB_login_yes)
ll_site -X POST --data-urlencode "code=$(ll_code 1)" -H "Cookie: $tzc; WiFiDB_login_yes=$s2" 'http://127.0.0.1/login.php?func=mfa_proc' >/dev/null
page=$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s2" http://127.0.0.1/cp/security.php)
check "the Security page offers to link Google and GitHub" sh -c 'echo "$0" | grep -q "Link a Google account" && echo "$0" | grep -q "Link a GitHub account"' "$page"
check "...with no PHP errors" ll_clean "$page"
check "linking needs the password" sh -c 'echo "$0" | grep -q "nothing was linked"' \
    "$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s2" -X POST -d provider=google -d password=wrong http://127.0.0.1/cp/security.php?func=link)"
ll_subject 2002
start=$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s2" -X POST -d provider=google --data-urlencode "password=$ll_pass" http://127.0.0.1/cp/security.php?func=link)
check "...and with it goes to the provider" sh -c 'echo "$0" | grep -q "^http://127.0.0.1:8099/authorize?"' "$(echo "$start" | ll_loc)"
check "...and back, linked" sh -c 'test "$0" = /cp/security.php?linked=google && test "$1" = 1' "$(ll_back "$start" "WiFiDB_login_yes=$s2" | ll_loc)" "$(ll_links google 2002 ll2fa)"
check "...which the page says" sh -c 'echo "$0" | grep -q "Your Google account is linked"' \
    "$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s2" 'http://127.0.0.1/cp/security.php?linked=google')"

# signing in with it still asks for the second factor
page=$(ll_back "$(ll_start 2002)")
s3=$(echo "$page" | ll_cookie WiFiDB_login_yes)
check "signing in with a linked account still asks for the second factor" sh -c 'echo "$0" | grep -q "/login.php?func=mfa"' "$(echo "$page" | ll_loc)"
check "...and until it's given, the session grants nothing" sh -c 'echo "$0" | grep -q "must be logged in"' \
    "$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s3" http://127.0.0.1/cp/security.php)"
check "...then it does" sh -c 'test "$0" = /all.php' \
    "$(ll_site -X POST --data-urlencode "code=$(ll_code 2)" -H "Cookie: $tzc; WiFiDB_login_yes=$s3" 'http://127.0.0.1/login.php?func=mfa_proc&return=%2Fall.php' | ll_loc)"

# linking a GitHub account nobody had, with the password and then the second factor
page=$(ll_back "$(ll_start 3003 github)")
link_cookie=$(echo "$page" | ll_cookie WiFiDB_link_pending)
link_token=$(echo "$page" | sed -n 's/.*name="token" value="\([^"]*\)".*/\1/p' | head -1)
page=$(ll_link ll2fa "$ll_pass")
s4=$(echo "$page" | ll_cookie WiFiDB_login_yes)
mfa=$(echo "$page" | ll_loc)
check "a member with two-factor sign-in linking one is asked for it first" sh -c 'echo "$0" | grep -q "func=mfa&return=%2Flogins.php%3Ffunc%3Dfinish_link" && test "$1" = 0' "$mfa" "$(ll_links github 3003 ll2fa)"
page=$(ll_site -X POST --data-urlencode "code=$(ll_code 3)" -H "Cookie: $tzc; WiFiDB_login_yes=$s4" "http://127.0.0.1$(echo "$mfa" | sed 's/func=mfa&/func=mfa_proc\&/')")
page=$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s4; WiFiDB_link_pending=$link_cookie" "http://127.0.0.1$(echo "$page" | ll_loc)")
check "...then it's linked" sh -c 'test "$0" = /all.php && test "$1" = 1' "$(echo "$page" | ll_loc)" "$(ll_links github 3003 ll2fa)"
check "unlinking needs the password" sh -c 'echo "$0" | grep -q "nothing was unlinked" && test "$1" = 1' \
    "$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s4" -X POST -d provider=github -d password=wrong http://127.0.0.1/cp/security.php?func=unlink)" "$(ll_links github 3003 ll2fa)"
check "...and with it, unlinks" sh -c 'echo "$0" | grep -q "Unlinked" && test "$1" = 0' \
    "$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s4" -X POST -d provider=github --data-urlencode "password=$ll_pass" http://127.0.0.1/cp/security.php?func=unlink)" "$(ll_links github 3003 ll2fa)"

# signing up after signing in with one nobody linked gives the new account it
page=$(ll_back "$(ll_start 4004)")
link_cookie=$(echo "$page" | ll_cookie WiFiDB_link_pending)
check "the link page offers to sign up" sh -c 'echo "$0" | grep -q "func=create_user_form"' "$page"
page=$(ll_site -H "Cookie: $tzc; WiFiDB_link_pending=$link_cookie" -X POST --data-urlencode time_user=llnew --data-urlencode "time_pass=$ll_pass" --data-urlencode "time_pass2=$ll_pass" \
    --data-urlencode time_email=llnew@example.test -d agree_terms=yes -d ct_js=1 -d ct_t=$(( $(date +%s) - 20 )) 'http://127.0.0.1/login.php?func=create_user_proc')
# (with e-mail confirmation on, the page says the mail couldn't be sent, as this test can't send it)
check "signing up then links it to the new account" sh -c 'echo "$0" | grep -q "Your Google account is linked" && test "$1" = 1' "$page" "$(ll_links google 4004 llnew)"
page=$(ll_back "$(ll_start 4004)")
if [ "$(sqlq "select validated from user_info where username = 'llnew'")" = 1 ]; then
    check "...which, until its e-mail is confirmed, can't sign in" sh -c 'echo "$0" | grep -q "not been confirmed" && ! echo "$0" | grep -qi "^set-cookie: WiFiDB_login_yes"' "$page"
else
    check "...which signs in with it" sh -c 'test "$0" = /all.php' "$(echo "$page" | ll_loc)"
fi

# the administrators' page
check "the Sign-in Providers page is for administrators" test "$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$session" http://127.0.0.1/admin/logins.php | ll_status)" = 403
ll_site -X POST --data-urlencode "code=$(ll_code 4)" -H "Cookie: $tzc; WiFiDB_login_yes=$s4" 'http://127.0.0.1/cp/admin_mode.php?func=proc' >/dev/null
page=$(ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s4" http://127.0.0.1/admin/logins.php)
check "...which in admin mode shows the redirect URI to give each provider" sh -c 'echo "$0" | grep -q "https://wifidb.test/logins.php?callback=discord" && echo "$0" | grep -q "Google (on)"' "$page"
check "...with no PHP errors" ll_clean "$page"
admin_save() { ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s4" -X POST -d login_google_enabled=on -d login_google_client_id=fake-google \
    -d login_github_enabled=on -d login_github_client_id=fake-github "$@" http://127.0.0.1/admin/logins.php | ll_loc; }
check "turning one on without its secret saves nothing" sh -c 'echo "$0" | grep -q "r=needs"' "$(admin_save -d login_discord_enabled=on -d login_discord_client_id=disc)"
check "...with it, saves (the secret encrypted)" sh -c 'echo "$0" | grep -q "r=saved" && echo "$1" | grep -q "^enc:"' \
    "$(admin_save -d login_discord_enabled=on -d login_discord_client_id=disc -d login_discord_secret=disc-secret)" "$(sqlq "select value from site_settings where name = 'login_discord_secret'")"
check "...and the sign-in page offers it" sh -c 'echo "$0" | grep -q "name=\"provider\" value=\"discord\""' "$(ll_site -H "Cookie: $tzc" http://127.0.0.1/login.php)"
check "turning it off again takes it away (the others' secrets kept)" sh -c 'echo "$0" | grep -q "r=saved" && ! echo "$1" | grep -q "value=\"discord\"" && echo "$1" | grep -q "value=\"google\""' \
    "$(admin_save)" "$(ll_site -H "Cookie: $tzc" http://127.0.0.1/login.php)"
ll_site -H "Cookie: $tzc; WiFiDB_login_yes=$s4" 'http://127.0.0.1/cp/admin_mode.php?func=off' >/dev/null
check "a sign-in under way is kept by its token's hash, not the token" sh -c 'echo "$0" | grep -qx "$2" && ! echo "$0" | grep -qF -- "$1"' \
    "$(sqlq "select token from linked_login_states")" "$abandoned" "$(printf %s "$abandoned" | sha256sum | cut -d' ' -f1)"
docker exec "$ct" pkill -f 'php -S 127.0.0.1:8099' || true
