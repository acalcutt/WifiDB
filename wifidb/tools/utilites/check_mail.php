<?php
/*
check_mail.php, checks the site's mail settings and, if asked, sends a test message.

  php check_mail.php                   check the settings (SMTP, DKIM key, DKIM record in DNS, SPF, DMARC)
  php check_mail.php --send you@x.org  also send a signed test message from the main mail account
  php check_mail.php --send you@x.org --signup   ... from the sign-up account (the one sign-up and reset mail uses)

Exits 1 if anything failed. Docs: docs/email.md
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\r\n");}
$root = rtrim($daemon_config['wifidb_install'], '/\\');
if(!is_file($root.'/lib/config.inc.php')){die("No $root/lib/config.inc.php\r\n");}
require $root.'/lib/config.inc.php';
require $root.'/lib/mailcheck.inc.php';

$dns = function ($name)
{
	$out = array();
	foreach((array)@dns_get_record($name, DNS_TXT) as $record)
	{
		if(isset($record['txt'])) { $out[] = $record['txt']; }
	}
	return $out;
};

$icons = array('ok' => '[ OK ]', 'warn' => '[WARN]', 'fail' => '[FAIL]');
$failed = false;
foreach(MailCheck::Check($config, $dns) as $result)
{
	echo $icons[$result[0]].' '.$result[1]."\r\n";
	if($result[0] === 'fail') { $failed = true; }
}

$to = null;
foreach($argv as $i => $arg) { if($arg === '--send' && isset($argv[$i + 1])) { $to = $argv[$i + 1]; } }
if($to !== null)
{
	echo "\r\nSending a test message to $to ...\r\n";
	require_once dirname(__FILE__).'/../daemon/lib/PHPMailer.inc.php';
	require_once dirname(__FILE__).'/../daemon/lib/SMTP.inc.php';

	$p = in_array('--signup', $argv) && isset($config['signup_smtp_host']) ? 'signup_smtp_' : 'smtp_';
	$mail = new PHPMailer();
	$mail->isSMTP();
	$mail->SMTPDebug = 2;
	$mail->Debugoutput = function ($line) { echo '  '.trim($line)."\r\n"; };
	$mail->Host = $config[$p.'host'];
	$mail->Port = $config[$p.'port'];
	$mail->Username = $config[$p.'user'];
	$mail->Password = $config[$p.'pass'];
	$mail->SMTPSecure = $config[$p.'secure'];
	$mail->SMTPAuth = $config[$p.'auth'];
	$mail->AuthType = $config[$p.'authtype'];
	$mail->SMTPOptions = $config[$p.'options'];
	$mail->XMailer = $config['XMailer'];
	$mail->SetFrom($config[$p.'from']);
	foreach(array('DKIM_domain', 'DKIM_private', 'DKIM_selector', 'DKIM_passphrase', 'DKIM_identity', 'DKIM_copyHeaderFields') as $k) { $mail->$k = $config[$k]; }
	$mail->addAddress($to);
	$mail->Subject = 'WiFiDB mail test';
	$mail->Body = "This is a test message from the WiFiDB mail settings check.\r\nIf you can read it, sending works.\r\n";
	try
	{
		$sent = $mail->send();
		echo $sent ? "Sent.\r\n" : "Not sent: ".$mail->ErrorInfo."\r\n";
		if(!$sent) { $failed = true; }
	}
	catch(Throwable $e)
	{
		echo "Not sent: ".$e->getMessage()."\r\n";
		$failed = true;
	}
	echo "Look at the message's headers in your mail program: 'dkim=pass' in Authentication-Results means the signature verified.\r\n";
}
exit($failed ? 1 : 0);
