<?php
/*
backup_transfer.php, how another WifiDB server copies this server's backups (lib/backups.inc.php, Import). It answers
only with this server's transfer key, made on admin/backups.php, in the X-WifiDB-Transfer-Key header:

  GET  ?func=list               the backups, as JSON
  GET  ?func=get&name=NAME      a finished backup's .tar; a Range header asks for a piece (copies go 16 MB at a time)
  POST ?func=fresh              ask for a new backup (it shows up in the list, running, then done)

That's all the key can do. It needs no database, so a server whose database is down can still hand over its backups.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
require dirname(__FILE__).'/../lib/config.inc.php';
require_once dirname(__FILE__).'/../lib/backups.inc.php';

$backups = new BackupService($config);
$json = function ($data, $status = 200) {
	http_response_code($status);
	header('Content-Type: application/json');
	header('Cache-Control: no-store');
	echo json_encode($data, JSON_UNESCAPED_SLASHES);
	exit;
};

$key = isset($_SERVER['HTTP_X_WIFIDB_TRANSFER_KEY']) ? (string)$_SERVER['HTTP_X_WIFIDB_TRANSFER_KEY'] : '';
if(!$backups->CheckTransferKey($key))
{
	usleep(500000);   # a guess costs half a second
	$json(array('ok' => false, 'error' => 'forbidden'), 403);
}

$func = isset($_GET['func']) ? (string)$_GET['func'] : '';
$from = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '?';
switch($func)
{
	case 'list':
		$list = array();
		foreach($backups->Backups() as $b)
		{
			if(in_array($b['state'] ?? '', array('done', 'running', 'failed'), true)) { $list[] = BackupService::PublicSummary($b); }
		}
		$json(array('ok' => true, 'site' => $config['hosturl'] ?? '', 'backups' => $list));

	case 'get':
		$name = isset($_GET['name']) ? (string)$_GET['name'] : '';
		$b = $backups->Get($name);
		if(!$b || ($b['state'] ?? '') !== 'done') { $json(array('ok' => false, 'error' => 'not_found'), 404); }
		BackupService::SendFile($backups->Path($name), $name.'.tar');
		exit;

	case 'fresh':
		if($_SERVER['REQUEST_METHOD'] !== 'POST') { $json(array('ok' => false, 'error' => 'post'), 405); }
		$backups->Queue('export', 'another server ('.$from.') with the transfer key');
		$json(array('ok' => true));

	default:
		$json(array('ok' => false, 'error' => 'unknown'), 400);
}
