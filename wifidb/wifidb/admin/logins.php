<?php
/*
logins.php, which outside accounts members can sign in with (lib/linked_logins.inc.php): Google, GitHub, Discord and
Facebook, each turned on here with the OAuth client ID and secret the site was given by that provider, and the address
it sends the browser back to shown for pasting into the provider's console. Needs admin mode.

Kept with the site settings (the secrets encrypted with two_factor_key); every change leaves the administrators a
notice, as the Site Settings page does.
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
$dbcore->sec->RequireAdminMode('admin/logins.php');

$username = $dbcore->sec->LoginUser;
$settings = $dbcore->sec->Settings();
$logins = $dbcore->sec->LinkedLogins();
$messages = array(
	'saved'   => 'Saved.',
	'nothing' => 'Nothing changed.',
	'needs'   => 'Turning a provider on needs its client ID and secret. Nothing was saved.',
	'error'   => 'The settings could not be saved. If you have not run the database migrations yet, run tools/utilites/migrate.php.',
);

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$values = array();
	foreach(SiteSettings::LOGIN_SETTINGS as $name => $def)
	{
		if($def[0] === 'bool') { $values[$name] = isset($_POST[$name]); continue; }
		if($def[0] === 'text') { $values[$name] = isset($_POST[$name]) ? trim((string)$_POST[$name]) : ''; continue; }
		$given = isset($_POST[$name]) ? trim((string)$_POST[$name]) : '';
		if(isset($_POST['clear_'.$name])) { $values[$name] = ''; }
		elseif($given !== '') { $values[$name] = $given; }
		else { $values[$name] = null; }   # left blank: keep what is there
	}
	$result = 'saved';
	foreach(array_keys(LinkedLogins::PROVIDERS) as $provider)
	{
		$secret = $values['login_'.$provider.'_secret'] !== null ? $values['login_'.$provider.'_secret'] : $settings->Get('login_'.$provider.'_secret');
		if($values['login_'.$provider.'_enabled'] && ($values['login_'.$provider.'_client_id'] === '' || $secret === '')) { $result = 'needs'; }
	}
	if($result === 'saved')
	{
		try
		{
			$changed = $settings->Save($values, $username);
			if(!$changed) { $result = 'nothing'; }
			else
			{
				$labels = array();
				foreach($changed as $name) { $labels[] = SiteSettings::LOGIN_SETTINGS[$name][1]; }
				$dbcore->sec->logd("Sign-in settings changed by ".$username.": ".implode(', ', $changed), "Info");
				try
				{
					require_once '../lib/notify.inc.php';
					$notifier = new Notifier($dbcore->sql->conn);
					$notifier->Notify('admin_alert', 'Sign-in settings changed', $username.' changed: '.implode('; ', $labels).'.', $dbcore->URL_PATH.'admin/logins.php');
				}
				catch(Throwable $e)
				{
					$dbcore->sec->logd("Could not tell the administrators about a sign-in settings change: ".$e->getMessage(), "error");
				}
			}
		}
		catch(Throwable $e)
		{
			$dbcore->sec->logd("Saving sign-in settings failed: ".$e->getMessage(), "error");
			$result = 'error';
		}
	}
	header('Location: logins.php?r='.$result);
	exit;
}
$r = (string)filter_input(INPUT_GET, 'r', FILTER_SANITIZE_SPECIAL_CHARS);

$described = array();
foreach($settings->Describe(SiteSettings::LOGIN_SETTINGS) as $s) { $described[$s['name']] = $s; }
$providers = array();
foreach(LinkedLogins::PROVIDERS as $provider => $info)
{
	$providers[] = array(
		'id'        => $provider,
		'name'      => $info['name'],
		'console'   => $info['console'],
		'callback'  => $logins->CallbackUrl($provider),
		'on'        => $logins->Enabled($provider),
		'enabled'   => $described['login_'.$provider.'_enabled'],
		'client_id' => $described['login_'.$provider.'_client_id'],
		'secret'    => $described['login_'.$provider.'_secret'],
	);
}
$dbcore->smarty->assign('wifidb_page_label', 'Sign-in Providers');
$dbcore->smarty->assign('login_providers', $providers);
$dbcore->smarty->assign('settings_message', isset($messages[$r]) ? $messages[$r] : '');
$dbcore->smarty->assign('settings_problem', in_array($r, array('needs', 'error'), true));
$dbcore->smarty->assign('settings_unencrypted', empty($config['two_factor_key']));
$dbcore->smarty->display('admin_logins.tpl');
