<?php
/*
backups.php, whole-site database backups (lib/backups.inc.php): make one, download one, restore one, delete one. Needs
admin mode. The work is done by tools/cron/backups, which runs what this page queues within a minute; this page shows
how it's going. A backup made on SQL Server restores on MariaDB and the other way round. See docs/backups.md.
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
require_once '../lib/backups.inc.php';
$dbcore->sec->RequireAdminMode('admin/backups.php');

$username = $dbcore->sec->LoginUser;
$backups = new BackupService($config, $dbcore->sql->conn, $dbcore->sql->service, $dbcore->ver_str ?? '');
$domain = (string)parse_url($config['hosturl'], PHP_URL_HOST);
$func = (string)filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);

if($func === 'download')
{
	$name = (string)filter_input(INPUT_GET, 'name', FILTER_UNSAFE_RAW);
	$b = $backups->Get($name);
	if(!$b || ($b['state'] ?? '') !== 'done') { header("HTTP/1.1 404 Not Found"); die("No finished backup named that."); }
	$dbcore->sec->logd("Backup $name downloaded by $username", "Info");
	BackupService::SendFile($backups->Path($name), $name.'.tar');
	exit;
}

$messages = array(
	'queued'      => 'Asked for. It starts within a minute; this page shows how it goes.',
	'confirm'     => "Type this site's domain to confirm a restore.",
	'busy'        => 'Something is already running or waiting; try again once it has finished.',
	'no_backup'   => 'No finished backup has that name.',
	'not_ready'   => "The backups folder can't be written: check backup_dir in config.inc.php.",
	'import_what' => "Give the other server's address (https://...) and its transfer key.",
	'revoked'     => 'The transfer key was revoked.',
);
$newKey = '';
if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = (string)($_POST['action'] ?? '');
	$name = (string)($_POST['name'] ?? '');
	$result = 'queued';
	if($action === 'create_key' && $backups->Ready())
	{
		# shown once, on this page, and never again: no redirect
		$newKey = $backups->CreateTransferKey($username);
		$dbcore->sec->logd("Backup transfer key made by $username", "Info");
		$result = '';
	}
	elseif($action === 'revoke_key') { $backups->RevokeTransferKey(); $result = 'revoked'; }
	elseif($action === 'import')
	{
		$url = trim((string)($_POST['url'] ?? ''));
		$key = trim((string)($_POST['key'] ?? ''));
		$restore = isset($_POST['restore']);
		if(!preg_match('#^https?://[^\s/]+#i', $url) || $key === '') { $result = 'import_what'; }
		elseif($restore && strcasecmp(trim((string)($_POST['confirm'] ?? '')), $domain) !== 0) { $result = 'confirm'; }
		elseif(!$backups->Ready()) { $result = 'not_ready'; }
		else
		{
			$backups->Queue('import', $username, array('url' => $url, 'key' => $key, 'name' => trim((string)($_POST['import_name'] ?? '')),
				'fresh' => isset($_POST['fresh']), 'restore' => $restore, 'insecure' => isset($_POST['insecure'])));
			$dbcore->sec->logd("Backup import from $url asked for by $username".($restore ? ', then restore' : ''), "Info");
		}
	}
	elseif(!$backups->Ready()) { $result = 'not_ready'; }
	elseif(in_array($action, array('restore', 'delete'), true) && !BackupService::ValidName($name)) { $result = 'no_backup'; }
	elseif($action === 'restore' && strcasecmp(trim((string)($_POST['confirm'] ?? '')), $domain) !== 0) { $result = 'confirm'; }
	elseif($action === 'restore' && $backups->Busy()) { $result = 'busy'; }
	elseif($action === 'restore' && (($backups->Get($name)['state'] ?? '') !== 'done')) { $result = 'no_backup'; }
	elseif(in_array($action, array('export', 'restore', 'delete'), true))
	{
		$backups->Queue($action, $username, $action === 'export' ? array() : array('name' => $name));
		$dbcore->sec->logd("Backup request by $username: $action $name", "Info");
		if($action === 'restore')
		{
			try
			{
				require_once '../lib/notify.inc.php';
				(new Notifier($dbcore->sql->conn))->Notify('admin_alert', 'A restore was started', "$username asked to restore $name, replacing all of the site's data.", $dbcore->URL_PATH.'admin/backups.php');
			}
			catch(Throwable $e) { /* the restore empties the notifications anyway */ }
		}
	}
	if($result !== '') { header('Location: backups.php?r='.$result); exit; }
}

$r = (string)filter_input(INPUT_GET, 'r', FILTER_SANITIZE_SPECIAL_CHARS);
$dbcore->smarty->assign('backups_new_key', $newKey);
$dbcore->smarty->assign('backups_key', $backups->TransferKeyInfo());
$dbcore->smarty->assign('backups_key_days', BackupService::TRANSFER_KEY_DAYS);
$list = $backups->Backups();
foreach($list as &$b) { $b['size_text'] = BackupService::HumanSize($b['size'] ?? 0); }
unset($b);
$job = $backups->Job();
if($job && !empty($job['log'])) { $job['log_text'] = implode("\n", array_slice($job['log'], -15)); }
$pending = $backups->Pending();
$stale = false;
foreach($pending as $p) { if(strtotime(($p['requested'] ?? '').' UTC') < time() - 300) { $stale = true; } }

$dbcore->smarty->assign('wifidb_page_label', 'Backups');
$dbcore->smarty->assign('backups_message', $messages[$r] ?? '');
$dbcore->smarty->assign('backups_problem', $r !== '' && $r !== 'queued');
$dbcore->smarty->assign('backups_list', $list);
$dbcore->smarty->assign('backups_job', $job);
$dbcore->smarty->assign('backups_pending', $pending);
$dbcore->smarty->assign('backups_busy', $backups->Busy());
$dbcore->smarty->assign('backups_stale', $stale);
$dbcore->smarty->assign('backups_ready', $backups->Ready());
$dbcore->smarty->assign('backups_dir', $backups->Dir());
$dbcore->smarty->assign('backups_domain', $domain);
$dbcore->smarty->display('admin_backups.tpl');
