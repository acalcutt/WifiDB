<?php
/*
servers.php, the site's servers (nodes) and updating them (docs/updates.md). Each node reports every minute (version,
load, memory, disks, services: tools/daemon/server_status.php), and its updater (wifidb-update, as root) the releases
it sees and how an update is going. Asking for an update here writes a request that every node's updater does in
turn, one at a time; the page itself changes nothing on any server. Needs admin mode.
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
$dbcore->sec->RequireAdminMode('admin/servers.php');
require_once '../lib/servers.inc.php';

$conn = $dbcore->sql->conn;
$username = $dbcore->sec->LoginUser;
$messages = array(
	'requested'   => 'Asked. Each server updates in turn, one at a time, within a minute or two of the last; this page shows how it goes.',
	'cancelled'   => 'Called off. A server already installing it finishes; the rest leave it.',
	'forgotten'   => 'Forgotten. It comes back if it reports again.',
	'bad_version' => 'That release isn\'t one the servers have found. Nothing was asked.',
	'error'       => 'That didn\'t work. If you haven\'t run the database migrations yet, run tools/utilites/migrate.php.',
);

// the release list the servers' updaters last read (the freshest of them)
$freshest = function (array $rows) {
	$best = null;
	foreach($rows as $row) { if($row['releases_at'] && (!$best || $row['releases_at'] > $best['releases_at'])) { $best = $row; } }
	return $best;
};

$tell = function ($subject, $body) use ($dbcore, $username) {
	$dbcore->sec->logd("Servers: ".$body." (".$username.")", "Info");
	try
	{
		require_once '../lib/notify.inc.php';
		(new Notifier($dbcore->sql->conn))->Notify('admin_alert', $subject, $username.': '.$body, $dbcore->URL_PATH.'admin/servers.php');
	}
	catch(Throwable $e) { $dbcore->sec->logd("Could not tell the administrators: ".$e->getMessage(), "error"); }
};

if($_SERVER['REQUEST_METHOD'] === 'POST')
{
	$action = (string)($_POST['action'] ?? '');
	try
	{
		if($action === 'update')
		{
			$version = trim((string)($_POST['version'] ?? ''));
			$source = $freshest(ServerStatus::All($conn));
			require_once '../lib/releases.inc.php';
			if(!$source || !WifidbVersion::Valid($version) || !WifidbReleases::Find($source['releases'], $version)) { $result = 'bad_version'; }
			else
			{
				$backup = isset($_POST['backup']);
				$id = ServerStatus::Request($conn, $version, $backup, $username);
				$tell('Servers updating to v'.$version, "asked every server to update to v$version".($backup ? ", after a backup" : "")." (request #$id)");
				$result = 'requested';
			}
		}
		elseif($action === 'cancel')
		{
			ServerStatus::Cancel($conn);
			$tell('Server update called off', 'called off the update');
			$result = 'cancelled';
		}
		elseif($action === 'forget')
		{
			ServerStatus::Forget($conn, (string)($_POST['node'] ?? ''));
			$result = 'forgotten';
		}
		else { $result = 'error'; }
	}
	catch(Throwable $e)
	{
		$dbcore->sec->logd("Servers page: ".$e->getMessage(), "error");
		$result = 'error';
	}
	header('Location: servers.php?r='.$result);
	exit;
}
$r = (string)filter_input(INPUT_GET, 'r', FILTER_SANITIZE_SPECIAL_CHARS);

$human = function ($bytes) {
	$units = array('B', 'KB', 'MB', 'GB', 'TB');
	for($i = 0; $bytes >= 1024 && $i < count($units) - 1; $i++) { $bytes /= 1024; }
	return round($bytes, $i ? 1 : 0).' '.$units[$i];
};

$servers = array();
$releases = array();
$releases_error = '';
$request = null;
$problem = '';
try
{
	$rows = ServerStatus::All($conn);
	$request = ServerStatus::LatestRequest($conn);
	$source = $freshest($rows);
	if($source)
	{
		$releases = $source['releases'];
		$releases_error = $source['releases_error'];
	}
	// what each server is compared with: the newest release, or the newest version a server runs
	$newest = '';
	foreach($releases as $rel) { if(empty($rel['prerelease'])) { $newest = $rel['version']; break; } }
	foreach($rows as $row) { if($row['version'] && ($newest === '' || WifidbVersion::Compare($row['version'], $newest) > 0)) { $newest = $row['version']; } }
	$now = time();
	foreach($rows as $row)
	{
		$seen = ServerStatus::Time($row['reported_at'] ?? null);
		if($seen && $now - $seen > ServerStatus::GONE_SECONDS) { continue; }
		$info = $row['info'];
		$disks = array();
		foreach(($info['disks'] ?? array()) as $path => $d) { $disks[] = $path.': '.$human($d['free']).' free of '.$human($d['total']); }
		$servers[] = array(
			'node' => $row['node_name'],
			'version' => $row['version'],
			'seen' => $seen ? ServerStatus::Ago($now - $seen).' ago' : 'never',
			'load' => isset($info['load']) ? implode(' ', $info['load']).(isset($info['cpus']) ? ' ('.$info['cpus'].' CPUs)' : '') : '',
			'memory' => isset($info['memory']['total']) ? $human($info['memory']['total'] - $info['memory']['available']).' used of '.$human($info['memory']['total']) : '',
			'disks' => $disks,
			'services' => $info['services'] ?? array(),
			'php' => $info['php'] ?? '',
			'update' => ($row['update_state'] && $request && (int)$row['update_request'] === (int)$request['id'])
				? (ServerStatus::STATES[$row['update_state']] ?? $row['update_state']).($row['update_message'] ? ': '.$row['update_message'] : '') : '',
			'updating' => in_array($row['update_state'], ServerStatus::ACTIVE_STATES, true),
			'warnings' => ServerStatus::Warnings($row, $now, $newest),
		);
	}
}
catch(Throwable $e)
{
	$dbcore->sec->logd("Servers page: ".$e->getMessage(), "error");
	$problem = 'The server tables are missing: run tools/utilites/migrate.php.';
}

$dbcore->smarty->assign('wifidb_page_label', 'Servers');
$dbcore->smarty->assign('servers', $servers);
$dbcore->smarty->assign('servers_releases', $releases);
$dbcore->smarty->assign('servers_releases_error', $releases_error);
$dbcore->smarty->assign('servers_request', $request);
$dbcore->smarty->assign('servers_running', require '../lib/version.inc.php');
$dbcore->smarty->assign('servers_message', $problem !== '' ? $problem : ($messages[$r] ?? ''));
$dbcore->smarty->assign('servers_problem', $problem !== '' || in_array($r, array('bad_version', 'error'), true));
$dbcore->smarty->display('admin_servers.tpl');
