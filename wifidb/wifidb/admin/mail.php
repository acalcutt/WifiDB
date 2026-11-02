<?php
/*
mail.php, how the site sends mail (tools/daemon/lib/wdbmail.inc.php): the mail server and account, a second account for
sign-up and password-reset mail if wanted, and DKIM signing, whose key can be made here and is then kept in the
database, so every server signs with it. It checks the settings and DNS (lib/mailcheck.inc.php, as
tools/utilites/check_mail.php does) and sends a test message. Needs admin mode.

Kept with the site settings: they start from config.inc.php's smtp_* and DKIM_* settings, and once saved here the value
here is used (the password and the key encrypted with two_factor_key). Every change leaves the administrators a notice,
as the Site Settings page does.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

include('../lib/init.inc.php');
require_once '../lib/mailcheck.inc.php';
$dbcore->sec->RequireAdminMode('admin/mail.php');

$username = $dbcore->sec->LoginUser;
$settings = $dbcore->sec->Settings();
$messages = array(
	'saved'     => 'Saved.',
	'nothing'   => 'Nothing changed.',
	'key_made'  => 'A new signing key was made, and mail is signed with it from now on. Publish its record (below) in DNS: until it is there, receivers can\'t check the signature.',
	'key_kept'  => 'There is a signing key already: tick Replace it to make a new one. Nothing was changed.',
	'bad_key'   => 'That isn\'t a private key PHP can read (a PEM file, without a passphrase, of at least 1024 bits). Nothing was saved.',
	'bad_value' => 'Nothing was saved:',
	'error'     => 'The settings could not be saved. If you have not run the database migrations yet, run tools/utilites/migrate.php.',
);
$detail = '';
$results = null;   # the check's
$test = null;      # the test message's

/** Saves $values, and tells the administrators what changed. @return string the result */
function mail_save($dbcore, $settings, $values, $username)
{
	try
	{
		$changed = $settings->Save($values, $username);
	}
	catch(Throwable $e)
	{
		$dbcore->sec->logd("Saving mail settings failed: ".$e->getMessage(), "error");
		return 'error';
	}
	if(!$changed) { return 'nothing'; }
	$labels = array();
	foreach($changed as $name) { $labels[] = SiteSettings::MAIL_SETTINGS[$name][1]; }
	$dbcore->sec->logd("Mail settings changed by ".$username.": ".implode(', ', $changed), "Info");
	try
	{
		require_once '../lib/notify.inc.php';
		$notifier = new Notifier($dbcore->sql->conn);
		$notifier->Notify('admin_alert', 'Mail settings changed', $username.' changed: '.implode('; ', $labels).'.', $dbcore->URL_PATH.'admin/mail.php');
	}
	catch(Throwable $e)
	{
		$dbcore->sec->logd("Could not tell the administrators about a mail settings change: ".$e->getMessage(), "error");
	}
	return 'saved';
}

/** A private key as pasted, if PHP can read it and it's big enough to sign with, else null */
function mail_key($pem)
{
	$pem = trim(str_replace("\r", '', (string)$pem))."\n";
	$bits = MailCheck::KeyBits($pem);
	return ($bits !== null && $bits >= 1024) ? $pem : null;
}

$func = isset($_POST['func']) ? (string)$_POST['func'] : '';
if($_SERVER['REQUEST_METHOD'] === 'POST' && $func === 'save')
{
	$values = array();
	$problems = array();
	foreach(SiteSettings::MAIL_SETTINGS as $name => $def)
	{
		if($def[0] === 'bool') { $values[$name] = isset($_POST[$name]); continue; }
		if($def[0] === 'choice') { $values[$name] = isset($_POST[$name]) ? (string)$_POST[$name] : null; continue; }
		if($def[0] === 'text')
		{
			list($values[$name], $wrong) = SiteSettings::Clean($name, isset($_POST[$name]) ? (string)$_POST[$name] : '');
			if($wrong !== '') { $problems[] = $wrong; }
			continue;
		}
		$given = isset($_POST[$name]) ? trim((string)$_POST[$name]) : '';
		if(isset($_POST['clear_'.$name])) { $values[$name] = ''; }
		elseif($given !== '') { $values[$name] = $given; }
		else { $values[$name] = null; }   # left blank: keep what is there
	}
	$result = 'saved';
	if($values['DKIM_private_string'] !== null && $values['DKIM_private_string'] !== '')
	{
		$values['DKIM_private_string'] = mail_key($values['DKIM_private_string']);
		if($values['DKIM_private_string'] === null) { $result = 'bad_key'; }
	}
	if($problems)
	{
		$result = 'bad_value';
		$detail = implode(' ', $problems);
	}
	if($result === 'saved') { $result = mail_save($dbcore, $settings, $values, $username); }
	if($detail === '') { header('Location: mail.php?r='.$result); exit; }
	$r = $result;   # shown here, with what went wrong, instead of after a redirect
}
elseif($_SERVER['REQUEST_METHOD'] === 'POST' && $func === 'make_key')
{
	list($selector, $wrong) = SiteSettings::Clean('DKIM_selector', isset($_POST['selector']) ? (string)$_POST['selector'] : '');
	if($settings->Get('DKIM_private_string') !== '' && !isset($_POST['replace'])) { $result = 'key_kept'; }
	elseif($selector === '' || $wrong !== '') { $result = 'bad_value'; $detail = $wrong !== '' ? $wrong : 'A new key needs a selector.'; }
	else
	{
		$key = openssl_pkey_new(array('private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA));
		if($key === false || !openssl_pkey_export($key, $pem)) { $result = 'error'; }
		else
		{
			$result = mail_save($dbcore, $settings, array('DKIM_private_string' => $pem, 'DKIM_selector' => $selector), $username);
			if($result === 'saved') { $result = 'key_made'; }
		}
	}
	if($detail === '') { header('Location: mail.php?r='.$result); exit; }
	$r = $result;
}
elseif($_SERVER['REQUEST_METHOD'] === 'POST' && $func === 'check')
{
	$results = MailCheck::Check($settings->MailConfig($config), array('MailCheck', 'Txt'));
	$r = '';
}
elseif($_SERVER['REQUEST_METHOD'] === 'POST' && $func === 'test')
{
	$to = isset($_POST['to']) ? trim((string)$_POST['to']) : '';
	$signup = isset($_POST['account']) && $_POST['account'] === 'signup';
	if(filter_var($to, FILTER_VALIDATE_EMAIL) === false) { $r = 'bad_value'; $detail = "$to isn't an e-mail address."; }
	else
	{
		list($sent, $error, $lines) = wdbmail::Test($settings->MailConfig($config), $to, $signup);
		$test = array('to' => $to, 'signup' => $signup, 'sent' => $sent, 'error' => $error, 'lines' => implode("\n", $lines));
		$dbcore->sec->logd("Test mail to ".$to." from the ".($signup ? 'sign-up' : 'main')." account by ".$username.": ".($sent ? 'sent' : 'not sent: '.$error), "Info");
		$r = '';
	}
}
else { $r = (string)filter_input(INPUT_GET, 'r', FILTER_SANITIZE_SPECIAL_CHARS); }

# the settings, in the page's sections
$sections = array('main' => array(), 'signup' => array(), 'signing' => array());
foreach($settings->Describe(SiteSettings::MAIL_SETTINGS) as $s)
{
	if(strpos($s['name'], 'signup_') === 0) { $sections['signup'][] = $s; }
	elseif(strpos($s['name'], 'DKIM_') === 0) { $sections['signing'][] = $s; }
	else { $sections['main'][] = $s; }
}

# the signing key in use, and the record to publish for it
$mailConfig = $settings->MailConfig($config);
$dkim = array('key' => '', 'from' => '', 'bits' => null, 'name' => '', 'record' => '');
$path = isset($mailConfig['DKIM_private']) ? (string)$mailConfig['DKIM_private'] : '';
if(!empty($mailConfig['DKIM_private_string']))
{
	$dkim['key'] = $mailConfig['DKIM_private_string'];
	$dkim['from'] = 'saved';
	$passphrase = '';
}
elseif($path !== '' && is_file($path) && is_readable($path))
{
	$dkim['key'] = (string)file_get_contents($path);
	$dkim['from'] = $path;
	$passphrase = isset($mailConfig['DKIM_passphrase']) ? (string)$mailConfig['DKIM_passphrase'] : '';
}
if($dkim['key'] !== '')
{
	$dkim['bits'] = MailCheck::KeyBits($dkim['key'], $passphrase);
	$dkim['record'] = (string)MailCheck::DnsRecord($dkim['key'], $passphrase);
	$domain = $settings->Get('DKIM_domain');
	$selector = $settings->Get('DKIM_selector');
	$dkim['name'] = ($domain !== '' && $selector !== '') ? $selector.'._domainkey.'.$domain : '';
}
unset($dkim['key']);
# a new key gets a selector of its own, so its record can be published beside the old one's
$dkim['new_selector'] = $dkim['from'] === '' ? $settings->Get('DKIM_selector') : 'wifidb'.gmdate('Ym');

$me = '';
try
{
	$prep = $dbcore->sql->conn->prepare("SELECT email FROM user_info WHERE username = ?");
	$prep->execute(array($username));
	$me = (string)$prep->fetchColumn();
}
catch(Throwable $e) {}

$dbcore->smarty->assign('wifidb_page_label', 'Mail');
$dbcore->smarty->assign('mail_main', $sections['main']);
$dbcore->smarty->assign('mail_signup', $sections['signup']);
$dbcore->smarty->assign('mail_signing', $sections['signing']);
$dbcore->smarty->assign('mail_dkim', $dkim);
$dbcore->smarty->assign('mail_results', $results);
$dbcore->smarty->assign('mail_test', $test);
$dbcore->smarty->assign('mail_to', $test !== null ? $test['to'] : $me);
$dbcore->smarty->assign('mail_validation', (bool)$settings->Get('email_validation'));
$dbcore->smarty->assign('settings_message', (isset($messages[$r]) ? $messages[$r] : '').($detail !== '' ? ' ('.$detail.')' : ''));
$dbcore->smarty->assign('settings_problem', in_array($r, array('key_kept', 'bad_key', 'bad_value', 'error'), true));
$dbcore->smarty->assign('settings_unencrypted', empty($config['two_factor_key']));
$dbcore->smarty->display('admin_mail.tpl');
