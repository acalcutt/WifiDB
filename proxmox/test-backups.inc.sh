# Sourced by test-template.sh after the two-factor tests (it uses their helpers): whole-site backups. The page queues a
# backup, the queue runs it, the page downloads it (by byte range too), another server copies it with a transfer key,
# and a restore puts every row back. The queue is run by hand here rather than waiting for cron's minute.

log "backups"
bk() { docker exec "$ct1" runuser -u www-data -- php /opt/wifidb/tools/utilites/backup.php "$@"; }
rows() { docker exec "$db" mariadb -uwifidb -ptestpw wifi -N -e "select (select count(*) from user_info) + (select count(*) from notifications) + (select count(*) from user_two_factor)"; }

# an administrator in admin mode, with recovery codes to spend
bkhash=$(docker exec -e P="$pass" "$ct1" php -r 'echo password_hash(getenv("P"), PASSWORD_BCRYPT);')
docker exec "$db" mariadb -uwifidb -ptestpw wifi -e "INSERT INTO user_info (username, password, uid, email, validated, permissions, admin) VALUES ('bkadmin', '$bkhash', 'bk1', 'bk@example.test', 0, '1000', 1)"
bkcodes=$(tf_php '
    $pdo->exec("INSERT INTO user_login_hashes (username, hash, utime) VALUES (\"bkadmin\", \"setup\", " . (time() + 600) . ")");
    $start = $tf->StartTotp("bkadmin", "setup");
    $done = $tf->FinishTotp("bkadmin", "setup", "test", Totp::Code($start["secret"], Totp::Step()));
    $pdo->exec("DELETE FROM user_login_hashes WHERE hash = \"setup\"");
    foreach ($done["recovery_codes"] as $code) { echo "CODE=$code\n"; }
' | sed -n 's/^CODE=//p')
bk_session=$(site -H "Cookie: $tzc" -X POST --data-urlencode "time_user=bkadmin" --data-urlencode "time_pass=$pass" 'http://127.0.0.1/login.php?func=login_proc' | setcookie_value WiFiDB_login_yes)
site -X POST --data-urlencode "code=$(echo "$bkcodes" | sed -n 1p)" -H "Cookie: $tzc; WiFiDB_login_yes=$bk_session" 'http://127.0.0.1/login.php?func=mfa_proc' >/dev/null
bk_get() { site -H "Cookie: $tzc; WiFiDB_login_yes=$bk_session" "$@"; }
check "the backups page needs admin mode" sh -c 'echo "$0" | grep -q "cp/admin_mode.php"' "$(bk_get 'http://127.0.0.1/admin/backups.php' | location)"
bk_get -X POST --data-urlencode "code=$(echo "$bkcodes" | sed -n 2p)" 'http://127.0.0.1/cp/admin_mode.php?func=proc' >/dev/null
check "in admin mode it opens, served by the backup pool" sh -c 'echo "$0" | grep -q "No backups yet" && docker exec "$1" test -S /run/php/wdb-backup.sock' "$(bk_get 'http://127.0.0.1/admin/backups.php')" "$ct1"
check "a member who isn't an administrator is refused" test "$(site -H "Cookie: $tzc; WiFiDB_login_yes=$session" 'http://127.0.0.1/admin/backups.php' | status)" = 403

check "'back up now' queues a request" sh -c 'echo "$0" | grep -q "r=queued" && docker exec "$1" sh -c "ls /srv/wifidb-data/backups/queue/*.json"' "$(bk_get -X POST -d action=export 'http://127.0.0.1/admin/backups.php' | location)" "$ct1"
before=$(rows)
bk run-queue
name=$(bk list | awk '/ done /{print $1; exit}')
check "the queue made the backup" test -n "$name"
check "...which the page lists with a download link" sh -c 'echo "$0" | grep -q "func=download&amp;name=$1"' "$(bk_get 'http://127.0.0.1/admin/backups.php')" "$name"
check "...and which is complete" bk verify "$name"
check "the backups folder is the data disk's, and only www-data can read it" sh -c 'test "$(docker exec "$0" stat -c "%U %a" /srv/wifidb-data/backups)" = "www-data 700"' "$ct1"
size=$(docker exec "$ct1" stat -c %s "/srv/wifidb-data/backups/$name.tar")
docker exec "$ct1" sh -c "curl -s -o /tmp/dl.tar -H 'Cookie: $tzc; WiFiDB_login_yes=$bk_session' 'http://127.0.0.1/admin/backups.php?func=download&name=$name'"
check "it downloads whole" sh -c 'docker exec "$0" cmp -s /tmp/dl.tar "/srv/wifidb-data/backups/$1.tar"' "$ct1" "$name"
check "...and by byte range, so a cut-off download can carry on" test "$(docker exec "$ct1" sh -c "curl -s -r 100-199 -o /dev/null -w '%{http_code} %{size_download}' -H 'Cookie: $tzc; WiFiDB_login_yes=$bk_session' 'http://127.0.0.1/admin/backups.php?func=download&name=$name'")" = "206 100"
check "...and not without admin mode" test "$(site -H "Cookie: $tzc; WiFiDB_login_yes=$session" "http://127.0.0.1/admin/backups.php?func=download&name=$name" | status)" = 403
docker exec "$ct1" rm -f /tmp/dl.tar

# another server copying it: here the same container, into another folder, through the real endpoint
check "the transfer endpoint refuses a request without the key" test "$(docker exec "$ct1" curl -s -o /dev/null -w '%{http_code}' 'http://127.0.0.1/admin/backup_transfer.php?func=list')" = 403
key=$(bk key 2>/dev/null)
check "with the key it lists the backup" sh -c 'docker exec "$0" curl -s -H "X-WifiDB-Transfer-Key: $1" "http://127.0.0.1/admin/backup_transfer.php?func=list" | grep -q "\"name\":\"$2\""' "$ct1" "$key" "$name"
check "another server copies it in pieces and checks it" docker exec "$ct1" runuser -u www-data -- php -r '
    define("SWITCH_SCREEN", "CLI"); define("SWITCH_EXTRAS", "cli");
    require "/opt/wifidb/tools/daemon.config.inc.php"; require "/var/www/wifidb/lib/init.inc.php"; require_once "/var/www/wifidb/lib/backups.inc.php";
    $other = new BackupService(array("backup_dir" => "/tmp/other-server") + $config);
    $other->Ready();
    $got = $other->Run(array("action" => "import", "url" => "http://127.0.0.1", "key" => $argv[1], "requested_by" => "test"));
    $b = $other->Get($argv[2]);
    exit($got && $b && $b["state"] === "done" && $b["kind"] === "imported" && hash_file("sha256", $other->Path($argv[2])) === hash_file("sha256", "/srv/wifidb-data/backups/" . $argv[2] . ".tar") ? 0 : 1);
' "$key" "$name"
docker exec "$ct1" rm -rf /tmp/other-server
bk key --revoke >/dev/null
check "a revoked key no longer works" test "$(docker exec "$ct1" curl -s -o /dev/null -w '%{http_code}' -H "X-WifiDB-Transfer-Key: $key" 'http://127.0.0.1/admin/backup_transfer.php?func=list')" = 403

# restore: change something, then put it back
docker exec "$db" mariadb -uwifidb -ptestpw wifi -e "DELETE FROM user_info WHERE username = 'tfuser'; INSERT INTO user_info (username, password, uid, email, validated) VALUES ('afterbackup', 'x', 'ab1', 'ab@example.test', 0)"
check "a restore from the command line needs --yes" sh -c '! docker exec "$0" runuser -u www-data -- php /opt/wifidb/tools/utilites/backup.php restore "$1" 2>/dev/null' "$ct1" "$name"
check "the restore runs" bk restore "$name" --yes
check "...every row is back as it was" test "$(rows)" = "$before"
check "...including the member deleted after the backup, not the one added" docker exec "$db" sh -c 'test "$(mariadb -uwifidb -ptestpw wifi -N -e "select group_concat(username order by username) from user_info where username in (\"tfuser\", \"afterbackup\")")" = tfuser'
check "...everyone is signed out" test "$(docker exec "$db" mariadb -uwifidb -ptestpw wifi -N -e 'select count(*) from user_login_hashes')" = 0
check "...this server's own schedule is untouched" test "$(docker exec "$db" mariadb -uwifidb -ptestpw wifi -N -e 'select count(*) from schedule')" -gt 0
check "...and the daemons aren't left paused" docker exec "$ct1" test ! -e /srv/wifidb-data/backups/restoring
check "a restored member signs in again, and is asked for their authenticator code" sh -c 'echo "$0" | grep -q "func=mfa"' "$(login | location)"
bk delete "$name" >/dev/null
check "deleting removes it" sh -c '! docker exec "$0" ls "/srv/wifidb-data/backups/$1.tar" 2>/dev/null' "$ct1" "$name"
