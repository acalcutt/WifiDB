<?php
/*
check_mail.php, checks the site's mail settings and, if asked, sends a test message. The Mail page (admin/mail.php) does
the same from the site.

  php check_mail.php                   check the settings (SMTP, DKIM key, DKIM record in DNS, SPF, DMARC)
  php check_mail.php --send you@x.org  also send a signed test message from the main mail account
  php check_mail.php --send you@x.org --signup   ... from the sign-up account (the one sign-up and reset mail uses)

The settings are config.inc.php's with what was saved on the Mail page over them, as the site uses them. Exits 1 if
anything failed. Docs: docs/email.md
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\r\n");}
$root = rtrim($daemon_config['wifidb_install'], '/\\');
if(!is_file($root.'/lib/config.inc.php')){die("No $root/lib/config.inc.php\r\n");}
require $root.'/lib/helpers.inc.php';
require $root.'/lib/config.inc.php';
require $root.'/lib/mailcheck.inc.php';
require_once $root.'/lib/site_settings.inc.php';
require_once $root.'/vendor/autoload.php';   // PHPMailer, from Composer
require_once dirname(__FILE__).'/../daemon/lib/wdbmail.inc.php';

$icons = array('ok' => '[ OK ]', 'warn' => '[WARN]', 'fail' => '[FAIL]');
$failed = false;
try
{
	$config = (new SiteSettings(wifidb_pdo_from_config($config, 10), $config))->MailConfig($config);
}
catch(Throwable $e)
{
	echo $icons['warn']." Could not read the settings saved on the Mail page (".$e->getMessage()."), so these are config.inc.php's alone.\r\n";
}

foreach(array_merge(MailCheck::Check($config, array('MailCheck', 'Txt')), wdbmail::CheckOAuth($config)) as $result)
{
	echo $icons[$result[0]].' '.$result[1]."\r\n";
	if($result[0] === 'fail') { $failed = true; }
}

$to = null;
foreach($argv as $i => $arg) { if($arg === '--send' && isset($argv[$i + 1])) { $to = $argv[$i + 1]; } }
if($to !== null)
{
	echo "\r\nSending a test message to $to ...\r\n";
	list($sent, $error, $lines) = wdbmail::Test($config, $to, in_array('--signup', $argv));
	foreach($lines as $line) { echo '  '.str_replace("\n", "\n  ", $line)."\r\n"; }
	echo $sent ? "Sent.\r\n" : "Not sent: ".$error."\r\n";
	if(!$sent) { $failed = true; }
	echo "Look at the message's headers in your mail program: 'dkim=pass' in Authentication-Results means the signature verified.\r\n";
}
exit($failed ? 1 : 0);
