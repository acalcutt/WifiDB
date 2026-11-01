<?php
/*
settings.php, the site settings an administrator changes here instead of in config.inc.php (lib/site_settings.inc.php):
whether sign-ups are open, e-mail confirmation for new accounts, and CleanTalk. Needs admin mode.

A new CleanTalk key is checked with CleanTalk before it is saved; a key CleanTalk refuses saves nothing. If CleanTalk
can't be reached the key is saved anyway, with a warning. Every change leaves the administrators a notice saying who
changed what.
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
$dbcore->sec->RequireAdminMode('admin/settings.php');

$username = $dbcore->sec->LoginUser;
$settings = $dbcore->sec->Settings();
$messages = array(
	'saved'       => 'Saved.',
	'nothing'     => 'Nothing changed.',
	'unreachable' => 'Saved, but CleanTalk could not be reached to check the key. Check it again later by saving it once more.',
	'needs_key'   => 'Turning on CleanTalk needs its access key. Nothing was saved.',
	'bad_key'     => 'CleanTalk did not accept that key. Nothing was saved.',
	'error'       => 'The settings could not be saved. If you have not run the database migrations yet, run tools/utilites/migrate.php.',
	'bad_s3'      => "That S3 bucket couldn't be used with those keys. Nothing was saved.",
);
$detail = '';

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$values = array();
	foreach(SiteSettings::SETTINGS as $name => $def)
	{
		if($def[0] === 'bool') { $values[$name] = isset($_POST[$name]); continue; }
		if($def[0] === 'text') { $values[$name] = isset($_POST[$name]) ? trim((string)$_POST[$name]) : ''; continue; }
		$given = isset($_POST[$name]) ? trim((string)$_POST[$name]) : '';
		if(isset($_POST['clear_'.$name])) { $values[$name] = ''; }
		elseif($given !== '') { $values[$name] = $given; }
		else { $values[$name] = null; }   # left blank: keep what is there
	}

	$result = 'saved';
	$newKey = $values['cleantalk_key'];
	$key = ($newKey !== null) ? $newKey : $settings->Get('cleantalk_key');
	if($values['cleantalk_enabled'] && $key === '') { $result = 'needs_key'; }
	elseif($newKey !== null && $newKey !== '')
	{
		$check = $dbcore->sec->CleanTalk($newKey)->CheckKey();
		if($check === 'invalid') { $result = 'bad_key'; }
		elseif($check === 'unreachable') { $result = 'unreachable'; }
	}

	# a changed S3 location for backup copies is tried before it's saved
	$s3 = array();
	$s3Changed = false;
	foreach(array('endpoint', 'region', 'bucket', 'key_id', 'secret') as $part)
	{
		$name = 'backup_s3_'.$part;
		$s3[$part] = ($values[$name] !== null) ? $values[$name] : $settings->Get($name);
		if($values[$name] !== null && $values[$name] !== $settings->Get($name)) { $s3Changed = true; }
	}
	if(($result === 'saved' || $result === 'unreachable') && $s3Changed && $s3['endpoint'] !== '')
	{
		try
		{
			require_once '../lib/s3.inc.php';
			(new S3Client($s3['endpoint'], $s3['region'], $s3['bucket'], $s3['key_id'], $s3['secret']))->Check();
		}
		catch(Throwable $e)
		{
			$result = 'bad_s3';
			$detail = $e->getMessage();
		}
	}

	if($result === 'saved' || $result === 'unreachable')
	{
		try
		{
			$changed = $settings->Save($values, $username);
			if(!$changed) { $result = 'nothing'; }
			else
			{
				$labels = array();
				foreach($changed as $name) { $labels[] = SiteSettings::SETTINGS[$name][1]; }
				$dbcore->sec->logd("Site settings changed by ".$username.": ".implode(', ', $changed), "Info");
				try
				{
					require_once '../lib/notify.inc.php';
					$notifier = new Notifier($dbcore->sql->conn);
					$notifier->Notify('admin_alert', 'Site settings changed', $username.' changed: '.implode('; ', $labels).'.', $dbcore->URL_PATH.'admin/settings.php');
				}
				catch(Throwable $e)
				{
					$dbcore->sec->logd("Could not tell the administrators about a settings change: ".$e->getMessage(), "error");
				}
			}
		}
		catch(Throwable $e)
		{
			$dbcore->sec->logd("Saving site settings failed: ".$e->getMessage(), "error");
			$result = 'error';
		}
	}
	if($detail === '') { header('Location: settings.php?r='.$result); exit; }
	$r = $result;   # shown here, with what went wrong, instead of after a redirect
}
else { $r = (string)filter_input(INPUT_GET, 'r', FILTER_SANITIZE_SPECIAL_CHARS); }

$dbcore->smarty->assign('wifidb_page_label', 'Site Settings');
$dbcore->smarty->assign('site_settings', $settings->Describe());
$dbcore->smarty->assign('settings_message', (isset($messages[$r]) ? $messages[$r] : '').($detail !== '' ? ' ('.$detail.')' : ''));
$dbcore->smarty->assign('settings_problem', in_array($r, array('needs_key', 'bad_key', 'error', 'unreachable', 'bad_s3'), true));
$dbcore->smarty->assign('settings_unencrypted', empty($config['two_factor_key']));
$dbcore->smarty->display('admin_settings.tpl');
