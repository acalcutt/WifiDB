# Sourced by the boot tests: sending mail as the Mail page (admin/mail.php) sets it up, through a stand-in mail server
# run in the container (test-smtp-sink.php): a test message, a signing key made on the page and the record it shows,
# check_mail.php reading what the page saved, and a new member's confirmation mail, signed; and with the mail server
# gone, a sign-up that says the mail wasn't sent. Needs from the caller: ct, sqlq, tzc, ok/bad/check/log; and from
# test-linked-logins.inc.sh, $session (a member), $s4 (ll2fa, an administrator) and ll_code (ll2fa's recovery codes:
# 1-5 are used before).

log "mail, as the Mail page sets it up"
here=$(dirname "$(readlink -f "$0")")
sink=/var/tmp/smtp-sink
ml_get() { docker exec "$ct" curl -s -H "Cookie: $tzc; WiFiDB_login_yes=$s4" "$@"; }
ml_post() { docker exec "$ct" curl -s -D - -H "Cookie: $tzc; WiFiDB_login_yes=$s4" -X POST "$@" http://127.0.0.1/admin/mail.php; }
ml_loc() { sed -n 's/^[Ll]ocation: \(.*\)\r*$/\1/p' | tr -d '\r' | head -1; }
ml_count() { docker exec "$ct" sh -c "ls $sink 2>/dev/null | wc -l"; }
ml_newest() { docker exec "$ct" sh -c "cat $sink/\$(ls $sink | tail -1)"; }
# ml_verify RECORD: whether the newest message's signature verifies with the key in that DNS record
ml_verify() {
    docker exec -e R="$1" "$ct" php -r 'require "/var/tmp/DkimVerifier.php"; preg_match("/p=([A-Za-z0-9+\/=]+)/", getenv("R"), $m);
        $pem = "-----BEGIN PUBLIC KEY-----\n" . chunk_split($m[1], 64, "\n") . "-----END PUBLIC KEY-----\n";
        $f = glob("/var/tmp/smtp-sink/*.eml"); $r = DkimVerifier::Verify(file_get_contents(end($f)), $pem); echo $r[0] ? "ok" : $r[1];'
}
ml_save() {
    ml_post -d func=save -d smtp_host=127.0.0.1 -d smtp_port=2525 -d smtp_secure= -d smtp_auth=on -d smtp_authtype= -d smtp_user=wifidb-test \
        --data-urlencode smtp_pass=test-pass -d smtp_from=wifidb@wifidb.test --data-urlencode "smtp_from_name=WiFiDB Test" -d wifidb_email_updates=on \
        -d signup_smtp_secure= -d DKIM_domain=wifidb.test -d DKIM_selector=wifidb "$@"
}
ml_clean() { ! echo "$1" | grep -qE "Fatal error|Uncaught|Warning:|Deprecated:|Notice:|SQLSTATE"; }
docker cp "$here/test-smtp-sink.php" "$ct:/var/tmp/smtp-sink.php"
docker cp "$here/../tests/Support/DkimVerifier.php" "$ct:/var/tmp/DkimVerifier.php"
docker exec -d "$ct" php /var/tmp/smtp-sink.php 2525 "$sink"

check "the Mail page is for administrators" test "$(docker exec "$ct" curl -s -o /dev/null -w '%{http_code}' -H "Cookie: $tzc; WiFiDB_login_yes=$session" http://127.0.0.1/admin/mail.php)" = 403
docker exec "$ct" curl -s -o /dev/null -X POST --data-urlencode "code=$(ll_code 6)" -H "Cookie: $tzc; WiFiDB_login_yes=$s4" 'http://127.0.0.1/cp/admin_mode.php?func=proc'
page=$(ml_get http://127.0.0.1/admin/mail.php)
check "...which in admin mode starts from config.inc.php" sh -c 'echo "$0" | grep -q "Mail account" && echo "$0" | grep -q "value=\"smtp.someemail.com\"" && echo "$0" | grep -q "From config.inc.php"' "$page"
check "...with no PHP errors" ml_clean "$page"
check "a port that isn't one saves nothing" sh -c 'echo "$0" | grep -q "abc isn.*t a port number" && test -z "$1"' \
    "$(ml_save -d smtp_port=abc)" "$(sqlq "select value from site_settings where name = 'smtp_host'")"
check "the mail server and account save" sh -c 'echo "$0" | grep -q "r=saved"' "$(ml_save | ml_loc)"
check "...the password encrypted" sh -c 'echo "$0" | grep -q "^enc:"' "$(sqlq "select value from site_settings where name = 'smtp_pass'")"
check "...and the administrators are told" test "$(sqlq "select count(*) from notifications where title = 'Mail settings changed'")" -ge 1

before=$(ml_count)
page=$(ml_post -d func=test -d to=me@example.test -d account=main)
check "a test message is sent" sh -c 'echo "$0" | grep -q "Sent to me@example.test" && test "$1" = $(( $2 + 1 ))' "$page" "$(ml_count)" "$before"
check "...the page shows what the server said, but not the login" sh -c 'echo "$0" | grep -q "AUTH LOGIN (the login is left out)" && echo "$0" | grep -q "250 OK queued" && ! echo "$0" | grep -qE "test-pass|dGVzdC1wYXNz|d2lmaWRiLXRlc3Q"' "$page"
check "...from the From name and address saved" sh -c 'echo "$0" | grep -q "^From: WiFiDB Test <wifidb@wifidb.test>" && echo "$0" | grep -q "^Subject: WiFiDB mail test"' "$(ml_newest)"
check "...unsigned, with no key yet" sh -c '! echo "$0" | grep -q "^DKIM-Signature"' "$(ml_newest)"

check "a signing key is made on the page" sh -c 'echo "$0" | grep -q "r=key_made"' "$(ml_post -d func=make_key -d selector=wifidb | ml_loc)"
check "...kept encrypted" sh -c 'echo "$0" | grep -q "^enc:"' "$(sqlq "select value from site_settings where name = 'DKIM_private_string'")"
page=$(ml_get http://127.0.0.1/admin/mail.php)
record=$(echo "$page" | grep -o 'v=DKIM1; k=rsa; p=[A-Za-z0-9+/=]*' | head -1)
check "...and the page shows the record to publish, and where" sh -c 'test ${#0} -gt 300 && echo "$1" | grep -q "named wifidb._domainkey.wifidb.test" && echo "$1" | grep -q "Saved here, 2048 bits"' "$record" "$page"
check "...but not the key" sh -c '! echo "$0" | grep -q "PRIVATE KEY"' "$page"
check "making another without saying to replace it changes nothing" sh -c 'echo "$0" | grep -q "r=key_kept"' "$(ml_post -d func=make_key -d selector=wifidb2 | ml_loc)"
ml_post -d func=test -d to=me@example.test -d account=main >/dev/null
check "the next message is signed for the domain, with the key the record has" sh -c 'echo "$0" | grep -q "^DKIM-Signature:.*d=wifidb.test; s=wifidb;" && test "$1" = ok' \
    "$(ml_newest | tr -d '\r' | sed -e ':a' -e 'N' -e '$!ba' -e 's/\n[ \t]\+/ /g')" "$(ml_verify "$record")"
page=$(ml_post -d func=check)
check "the check reads the saved key, and finds no record in DNS (there is none for wifidb.test)" sh -c 'echo "$0" | grep -q "The DKIM key is readable, 2048 bits" && echo "$0" | grep -q "No DKIM record at wifidb._domainkey.wifidb.test"' "$page"
check "...with no PHP errors" ml_clean "$page"
out=$(docker exec "$ct" runuser -u www-data -- php /opt/wifidb/tools/utilites/check_mail.php --send cli@example.test 2>&1)
check "check_mail.php uses what the page saved too, and sends with it" sh -c 'echo "$0" | grep -q "The DKIM key is readable, 2048 bits" && echo "$0" | grep -q "^Sent." && ! echo "$0" | grep -q "Could not read"' "$out"
check "...signed" test "$(ml_verify "$record")" = ok

# a new member's confirmation, through the sign-up account (the mail account's, as it has nothing of its own)
ml_validation=$(sqlq "select value from site_settings where name = 'email_validation'")
docker exec "$ct" php -r 'foreach (array("helpers", "config", "site_settings") as $f) { require_once "/var/www/wifidb/lib/$f.inc.php"; }
    (new SiteSettings(wifidb_pdo_from_config($config), $config))->Save(array("email_validation" => true, "signups_open" => true), "test");'
ml_signup() { docker exec "$ct" curl -s -H "Cookie: $tzc" -X POST --data-urlencode "time_user=$1" --data-urlencode "time_pass=mail test pass" --data-urlencode "time_pass2=mail test pass" \
    --data-urlencode "time_email=$1@example.test" -d agree_terms=yes -d ct_js=1 -d ct_t=$(( $(date +%s) - 20 )) 'http://127.0.0.1/login.php?func=create_user_proc'; }
before=$(ml_count)
page=$(ml_signup mailnew)
check "a new member is told their confirmation is on its way" sh -c 'echo "$0" | grep -q "You should be getting a Confirmation email soon"' "$page"
check "...and it is: to them, with the link, signed" sh -c 'test "$1" = $(( $2 + 1 )) && echo "$0" | grep -q "^To: mailnew@example.test" && echo "$0" | grep -q "validate_code=" && test "$3" = ok' \
    "$(ml_newest)" "$(ml_count)" "$before" "$(ml_verify "$record")"
docker exec "$ct" pkill -f smtp-sink.php
page=$(ml_signup mailgone)
check "with the mail server gone, a new member is told the mail couldn't be sent, not to wait for it" sh -c 'echo "$0" | grep -q "failed to send the email" && ! echo "$0" | grep -q "getting a Confirmation email soon"' "$page"
check "...and the log says why" test "$(sqlq "select count(*) from log where message like 'Validation email to mailgone could not be sent%'")" -ge 1
if [ -n "$ml_validation" ]; then sqlq "UPDATE site_settings SET value = '$ml_validation' WHERE name = 'email_validation'" >/dev/null
else sqlq "DELETE FROM site_settings WHERE name = 'email_validation'" >/dev/null; fi
docker exec "$ct" curl -s -o /dev/null -H "Cookie: $tzc; WiFiDB_login_yes=$s4" 'http://127.0.0.1/cp/admin_mode.php?func=off'
