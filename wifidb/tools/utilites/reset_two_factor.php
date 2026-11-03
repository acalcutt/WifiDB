<?php
/*
reset_two_factor.php, lockout recovery: removes a member's authenticator apps, passkeys and recovery codes, and signs them
out everywhere, so they can sign in with their password and set two-factor up again. Whoever can run this on the server
can already do anything to the site, which is why it is a command and not a button anyone can press.

  php reset_two_factor.php USERNAME
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\r\n");}
require $daemon_config['wifidb_install'].'/lib/init.inc.php';

if($argc < 2 || $argv[1] === '' || $argv[1][0] === '-')
{
	fwrite(STDERR, "Usage: php reset_two_factor.php USERNAME\r\n");
	exit(2);
}
$username = $argv[1];

$prep = $dbcore->sql->conn->prepare("SELECT username FROM user_info WHERE username = ?");
$prep->execute(array($username));
$found = $prep->fetchColumn();
if($found === false)
{
	fwrite(STDERR, "No member named '".$username."'.\r\n");
	exit(1);
}

$tf = $dbcore->sec->TwoFactor();
if(!$tf)
{
	fwrite(STDERR, "Two-factor is not available here (run migrate.php first?).\r\n");
	exit(1);
}
$had = count($tf->Methods($found));
$tf->ResetTwoFactor($found);
# anyone holding a session for this member signs in again; the member's sign-in settings go back to the default
$prep = $dbcore->sql->conn->prepare("DELETE FROM user_login_hashes WHERE username = ?");
$prep->execute(array($found));
$tf->SetAsks($found, false, false);
$dbcore->sec->Tell($found, 'Two-factor sign-in was reset', 'An administrator with access to the server removed the authenticator apps and passkeys from your account, and signed you out everywhere. Set two-factor up again under Preferences, Security.');
$dbcore->sec->logd("Two-factor reset from the command line for ".$found." (".$had." method(s) removed)", "Info");
echo "Two-factor reset for ".$found.": ".$had." method(s) removed, all sessions ended.\r\n";
