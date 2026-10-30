# Sourced by test-template.sh: two-factor sign-in, end to end, against the booted container ($ct1) and its database ($db).
# Needs ok/bad/check/log from the caller. The site's URL is https://wifidb.test, so its cookies are Secure and tied to
# that name; curl would not send them to http://127.0.0.1, so the cookies are copied into headers by hand.

tf_php() { # tf_php PHP-CODE: runs it in the container, with the site's libraries and a connection to the database
    docker exec -i -e DBHOST="$db" "$ct1" php -r '
        $pdo = new PDO("mysql:host=" . getenv("DBHOST") . ";dbname=wifi;charset=utf8mb4", "wifidb", "testpw", array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION));
        foreach (array("totp", "webauthn", "two_factor") as $f) { require "/var/www/wifidb/lib/$f.inc.php"; }
        $conf = require "/srv/wifidb-data/config/web.overrides.php";
        $tf = new TwoFactor($pdo, array("two_factor_key" => $conf["two_factor_key"], "hosturl" => "https://wifidb.test"));
        '"$1"
}
site() { docker exec "$ct1" curl -s -D - "$@"; }                       # headers, then the page
setcookie_value() { sed -n "s/^[Ss]et-[Cc]ookie: $1=\([^;]*\);.*/\1/p" | head -1; }
location() { sed -n 's/^[Ll]ocation: \(.*\)\r*$/\1/p' | tr -d '\r' | head -1; }

log "two-factor sign-in"
check "the database migrations were applied" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select count(*) from user_two_factor") $(mariadb -uwifidb -ptestpw wifi -N -e "select count(*) from notifications")" = "0 0"'
check "the first boot made the two-factor key" docker exec "$ct1" grep -q "two_factor_key" /srv/wifidb-data/config/web.overrides.php
pass="correct horse battery staple"
hash=$(docker exec -e P="$pass" "$ct1" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
docker exec "$db" mariadb -uwifidb -ptestpw wifi -e "INSERT INTO user_info (username, password, uid, email, validated) VALUES ('tfuser', '$hash', 'tf1', 'tf@example.test', 0)"

enrolled=$(tf_php '
    $pdo->exec("INSERT INTO user_login_hashes (username, hash, utime) VALUES (\"tfuser\", \"setup\", " . (time() + 600) . ")");
    $start = $tf->StartTotp("tfuser", "setup");
    $done = $tf->FinishTotp("tfuser", "setup", "test", Totp::Code($start["secret"], Totp::Step()));
    $pdo->exec("DELETE FROM user_login_hashes WHERE hash = \"setup\"");
    echo "SECRET=" . $start["secret"] . "\n";
    foreach ($done["recovery_codes"] as $code) { echo "CODE=$code\n"; }
')
secret=$(echo "$enrolled" | sed -n 's/^SECRET=//p')
codes=$(echo "$enrolled" | sed -n 's/^CODE=//p')
recovery1=$(echo "$codes" | sed -n 1p)
check "an authenticator app and ten recovery codes were set up" test -n "$secret" -a "$(echo "$codes" | wc -l)" = 10
next_code() { echo "$enrolled" | sed -n 's/^SECRET=//p' | docker exec -i "$ct1" php -r 'require "/var/www/wifidb/lib/totp.inc.php"; echo Totp::Code(trim(fgets(STDIN)), Totp::Step() + 1);'; }

login() { site -X POST --data-urlencode "time_user=tfuser" --data-urlencode "time_pass=$pass" "${@}" http://127.0.0.1/login.php?func=login_proc; }

headers=$(login)
session=$(echo "$headers" | setcookie_value WiFiDB_login_yes)
check "a right password goes on to the second step" sh -c 'echo "$0" | grep -q "func=mfa"' "$(echo "$headers" | location)"
page=$(site -H "Cookie: WiFiDB_login_yes=$session" 'http://127.0.0.1/cp/security.php')
check "...and until it is done the session grants nothing" sh -c 'echo "$0" | grep -q "must be logged in"' "$page"
check "the second-step page asks for a code" sh -c 'site() { docker exec "$0" curl -s -H "Cookie: WiFiDB_login_yes=$1" "http://127.0.0.1/login.php?func=mfa"; }; site "$0" "$1" | grep -q "One more step"' "$ct1" "$session"
wrong=$(site -X POST -d code=000000 -H "Cookie: WiFiDB_login_yes=$session" 'http://127.0.0.1/login.php?func=mfa_proc' | location)
check "a wrong code is refused" sh -c 'echo "$0" | grep -q "e=wrong_code"' "$wrong"
check "a pass with no pending session is refused" test "$(docker exec "$ct1" curl -s -o /dev/null -w '%{http_code}' -X POST -H 'Content-Type: application/json' -d '{}' 'http://127.0.0.1/login.php?func=mfa_passkey_begin')" = 403
right=$(site -X POST -d "code=$(next_code)" -d remember=yes -H "Cookie: WiFiDB_login_yes=$session" 'http://127.0.0.1/login.php?func=mfa_proc')
device=$(echo "$right" | setcookie_value WiFiDB_device)
check "the right code finishes sign-in" sh -c '! echo "$0" | grep -q "func=mfa"' "$(echo "$right" | location)"
check "...and 'remember this computer' sets a device cookie" test -n "$device"
page=$(site -H "Cookie: WiFiDB_login_yes=$session" 'http://127.0.0.1/cp/security.php')
check "now the Security page opens" sh -c 'echo "$0" | grep -q "Two-factor sign-in"' "$page"

headers=$(login -H "Cookie: WiFiDB_device=$device")
check "a remembered computer skips the second step" sh -c '! echo "$0" | grep -q "func=mfa"' "$(echo "$headers" | location)"
second=$(login | setcookie_value WiFiDB_login_yes)
via=$(site -X POST --data-urlencode "code=$recovery1" -H "Cookie: WiFiDB_login_yes=$second" 'http://127.0.0.1/login.php?func=mfa_proc' | location)
check "a recovery code finishes sign-in" sh -c '! echo "$0" | grep -q "func=mfa"' "$via"
third=$(login | setcookie_value WiFiDB_login_yes)
again=$(site -X POST --data-urlencode "code=$recovery1" -H "Cookie: WiFiDB_login_yes=$third" 'http://127.0.0.1/login.php?func=mfa_proc' | location)
check "...and works only once" sh -c 'echo "$0" | grep -q "e=wrong_code"' "$again"
check "using a recovery code leaves a notice for the member" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select count(*) from notifications where username=\"tfuser\" and type=\"security\"")" -ge 1'
