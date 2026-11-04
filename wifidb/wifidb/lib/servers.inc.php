<?php
/*
servers.inc.php, the servers (nodes) of one site, for admin/servers.php: what each reports every minute
(tools/daemon/server_status.php: version, load, memory, disks, services), what its updater reports (wifidb-update: the
releases it sees, and how an update is going), and the update an administrator asks for, which every node's updater
does in turn, one at a time (docs/updates.md). Tables server_status and server_update_requests (migration 0008).
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class ServerStatus
{
	/** A node not heard from for this long is called out; one gone this long drops off the page. */
	const SILENT_SECONDS = 300;
	const GONE_SECONDS = 7 * 86400;
	/** Free space below this share of a disk is called out. */
	const LOW_DISK = 0.10;
	/** The reports as they're kept: paths and addresses readable in the table */
	const JSON = JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;
	/** The services a node is expected to run (the two daemons can be switched off per node). */
	const SERVICES = array('apache2', 'php-fpm', 'cron', 'wifidb-monitord', 'wifidb-update.timer');
	/** pmtiles-swarm's public port on the node itself (docs/pmtiles-swarm.md) */
	const SWARM_URL = 'http://127.0.0.1:8090';
	/** How long a node following the swarm may go without the building node's newest build of a category. */
	const SWARM_LAG_SECONDS = 1800;

	/** The updater's states, and what the page calls them. */
	const STATES = array(
		'waiting'      => 'waiting its turn',
		'downloading'  => 'downloading',
		'backing_up'   => 'backing up the site',
		'installing'   => 'installing',
		'checking'     => 'checking the site',
		'rolling_back' => 'putting the old version back',
		'done'         => 'updated',
		'failed'       => 'failed',
		'rolled_back'  => 'failed, old version back',
	);
	const ACTIVE_STATES = array('downloading', 'backing_up', 'installing', 'checking', 'rolling_back');
	const FINISHED_STATES = array('done', 'failed', 'rolled_back');

	private static function Now() { return gmdate('Y-m-d H:i:s'); }

	/** A stored UTC time as Unix time, or false when there's none (strtotime(' UTC') alone would be now). */
	public static function Time($datetime)
	{
		return ($datetime === null || trim((string)$datetime) === '') ? false : strtotime(substr((string)$datetime, 0, 19).' UTC');
	}

	/** Writes $fields to this node's row, making it if need be. */
	private static function Put(PDO $conn, $node, array $fields)
	{
		$set = array();
		foreach(array_keys($fields) as $col) { $set[] = "$col = ?"; }
		$prep = $conn->prepare("UPDATE server_status SET ".implode(', ', $set)." WHERE node_name = ?");
		$prep->execute(array_merge(array_values($fields), array($node)));
		if($prep->rowCount() > 0) { return; }
		try
		{
			$cols = array_merge(array('node_name'), array_keys($fields));
			$conn->prepare("INSERT INTO server_status (".implode(', ', $cols).") VALUES (".implode(', ', array_fill(0, count($cols), '?')).")")
				->execute(array_merge(array($node), array_values($fields)));
		}
		catch(PDOException $e)
		{
			# there already (MariaDB counts a row the update left as it was as none changed)
			$prep->execute(array_merge(array_values($fields), array($node)));
		}
	}

	/** The minute's report: this node's version and what Collect() found. */
	public static function Report(PDO $conn, $node, $version, array $info)
	{
		self::Put($conn, $node, array('version' => (string)$version, 'reported_at' => self::Now(), 'info' => json_encode($info, self::JSON)));
	}

	/** The releases the updater found (newest first, as WifidbReleases gives them), or why it couldn't. */
	public static function SetReleases(PDO $conn, $node, $releases, $error = '')
	{
		self::Put($conn, $node, array('releases' => json_encode(array('list' => $releases, 'error' => (string)$error), self::JSON), 'releases_at' => self::Now()));
	}

	/** How this node's update for request $request is going. */
	public static function SetUpdate(PDO $conn, $node, $request, $version, $state, $message = '')
	{
		self::Put($conn, $node, array('update_request' => (int)$request, 'update_version' => (string)$version,
			'update_state' => (string)$state, 'update_message' => (string)$message, 'update_at' => self::Now()));
	}

	/** @return array|null one node's row, with info and releases decoded */
	public static function Get(PDO $conn, $node)
	{
		$prep = $conn->prepare("SELECT * FROM server_status WHERE node_name = ?");
		$prep->execute(array($node));
		$row = $prep->fetch(PDO::FETCH_ASSOC);
		return $row ? self::Decode($row) : null;
	}

	/** @return array every node's row, by name */
	public static function All(PDO $conn)
	{
		$out = array();
		foreach($conn->query("SELECT * FROM server_status ORDER BY node_name")->fetchAll(PDO::FETCH_ASSOC) as $row) { $out[] = self::Decode($row); }
		return $out;
	}

	private static function Decode(array $row)
	{
		$row = array_change_key_case($row, CASE_LOWER);
		$row['info'] = json_decode((string)($row['info'] ?? ''), true) ?: array();
		$releases = json_decode((string)($row['releases'] ?? ''), true) ?: array();
		$row['releases'] = $releases['list'] ?? array();
		$row['releases_error'] = $releases['error'] ?? '';
		return $row;
	}

	public static function Forget(PDO $conn, $node)
	{
		$conn->prepare("DELETE FROM server_status WHERE node_name = ?")->execute(array($node));
	}

	/** Asks every node to update to $version. @return int the request's id */
	public static function Request(PDO $conn, $version, $backup, $user)
	{
		# BACKUP is a reserved word in SQL Server
		$backup_col = $conn->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlsrv' ? '[backup]' : 'backup';
		$conn->prepare("INSERT INTO server_update_requests (version, $backup_col, backup_taken, requested_by, requested_at, cancelled) VALUES (?, ?, 0, ?, ?, 0)")
			->execute(array((string)$version, $backup ? 1 : 0, (string)$user, self::Now()));
		return (int)$conn->lastInsertId();
	}

	/** @return array|null the newest request not cancelled */
	public static function LatestRequest(PDO $conn)
	{
		$rows = $conn->query("SELECT * FROM server_update_requests WHERE cancelled = 0 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
		return $rows ? array_change_key_case($rows[0], CASE_LOWER) : null;
	}

	/** Calls off the newest request: nodes that haven't started on it leave it; one already installing finishes. */
	public static function Cancel(PDO $conn)
	{
		$latest = self::LatestRequest($conn);
		if($latest) { $conn->prepare("UPDATE server_update_requests SET cancelled = 1 WHERE id = ?")->execute(array((int)$latest['id'])); }
	}

	/**
	 * What needs an administrator's eye on one node, in words.
	 * @param array $row as All() gives it
	 * @param int $now Unix time
	 * @param string $newest the newest version any node runs (or the newest release)
	 */
	public static function Warnings(array $row, $now, $newest = '', $builder_swarm = null)
	{
		$out = array();
		$seen = self::Time($row['reported_at'] ?? null);
		if(!$seen) { $out[] = 'has never reported'; }
		elseif($now - $seen > self::SILENT_SECONDS) { $out[] = 'not heard from for '.self::Ago($now - $seen); }
		if($newest !== '' && ($row['version'] ?? '') !== '' && WifidbVersion::Compare($row['version'], $newest) < 0)
		{
			$out[] = 'runs '.$row['version'].', older than '.$newest;
		}
		$info = $row['info'] ?? array();
		foreach(($info['disks'] ?? array()) as $path => $d)
		{
			if(!empty($d['total']) && $d['free'] / $d['total'] < self::LOW_DISK)
			{
				$out[] = $path.' is '.round(100 - 100 * $d['free'] / $d['total']).'% full';
			}
		}
		if(!empty($info['memory']['total']) && $info['memory']['available'] / $info['memory']['total'] < 0.05) { $out[] = 'almost out of memory'; }
		if(!empty($info['cpus']) && ($info['load'][1] ?? 0) > 2 * $info['cpus']) { $out[] = 'busy (load '.$info['load'][1].' on '.$info['cpus'].' CPUs)'; }
		foreach(($info['services'] ?? array()) as $service => $state)
		{
			if($state !== 'active' && $state !== 'off') { $out[] = $service.' is '.$state; }
		}
		$swarm = $info['swarm'] ?? null;
		if(is_array($swarm))
		{
			if(($swarm['service'] ?? '') !== 'active') { $out[] = 'pmtiles-swarm is '.($swarm['service'] ?? 'unknown'); }
			elseif(($swarm['status'] ?? '') === 'no answer') { $out[] = "pmtiles-swarm isn't answering on port 8090"; }
			elseif(($swarm['status'] ?? '') !== 'ok') { $out[] = 'pmtiles-swarm says it is '.$swarm['status'].(!empty($swarm['error']) ? ': '.$swarm['error'] : ''); }
			if(is_array($builder_swarm) && ($swarm['role'] ?? '') === 'follows')
			{
				$behind = self::SwarmBehind($swarm, $builder_swarm, $now);
				if($behind) { $out[] = "pmtiles-swarm hasn't got the newest build of ".implode(', ', $behind); }
			}
		}
		if(($row['update_state'] ?? '') === 'failed' || ($row['update_state'] ?? '') === 'rolled_back')
		{
			$out[] = 'its last update '.self::STATES[$row['update_state']].': '.($row['update_message'] ?? '');
		}
		return $out;
	}

	/**
	 * The categories whose newest build on the building node a following node hasn't got, once that build is old enough
	 * to have reached it (SWARM_LAG_SECONDS).
	 * @param array $swarm the following node's info['swarm']
	 * @param array $builder_swarm the building node's
	 * @return string[]
	 */
	public static function SwarmBehind(array $swarm, array $builder_swarm, $now)
	{
		$out = array();
		foreach(($builder_swarm['newest'] ?? array()) as $category => $build)
		{
			$at = strtotime((string)($build['at'] ?? ''));
			if(!$at || $now - $at < self::SWARM_LAG_SECONDS) { continue; }
			if(($swarm['newest'][$category]['hash'] ?? '') !== ($build['hash'] ?? '')) { $out[] = $category; }
		}
		return $out;
	}

	/**
	 * What the minute's report keeps of pmtiles-swarm: its service, what /health says, and the current build of each
	 * category (both answered on its public port, without its API key, which /api/ wants).
	 * @param string $state systemctl is-active's answer
	 * @param array|null $health /health, or null when it didn't answer
	 * @param array|null $newest CategoriesFeed()'s, or null when it didn't answer
	 * @param string $role 'builds' on the node that builds the archives (tile_archive_generate), 'follows' on the others
	 */
	public static function SwarmInfo($state, $health, $newest, $role = '')
	{
		$out = array('service' => $state !== '' ? (string)$state : 'unknown', 'role' => (string)$role, 'status' => 'no answer');
		if(is_array($health))
		{
			$out['status'] = (string)($health['status'] ?? 'unknown');
			if(isset($health['version'])) { $out['version'] = (string)$health['version']; }
			if(!empty($health['error'])) { $out['error'] = (string)$health['error']; }
		}
		if(is_array($newest))
		{
			$out['categories'] = count($newest);
			$out['newest'] = $newest;
		}
		return $out;
	}

	/**
	 * /categories.xml (RSS, an item for each category's current build) as category => array(hash, at), or null when it
	 * isn't such a feed.
	 */
	public static function CategoriesFeed($xml)
	{
		$feed = @simplexml_load_string((string)$xml);
		if($feed === false || !isset($feed->channel)) { return null; }
		$newest = array();
		foreach($feed->channel->item as $item)
		{
			$hash = (string)$item->children('pmtiles', true)->infohash;
			$at = gmdate('Y-m-d\TH:i:s\Z', (int)strtotime((string)$item->pubDate));
			foreach($item->category as $category)
			{
				$category = (string)$category;
				if(!isset($newest[$category]) || strcmp($at, $newest[$category]['at']) > 0) { $newest[$category] = array('hash' => $hash, 'at' => $at); }
			}
		}
		ksort($newest);
		return $newest;
	}

	/** pmtiles-swarm on this node, when it runs it (it's switched on per node), or null. */
	public static function CollectSwarm($role = '', $base = self::SWARM_URL)
	{
		if(trim((string)@shell_exec("systemctl is-enabled pmtiles-swarm 2>/dev/null")) !== 'enabled') { return null; }
		$state = trim((string)@shell_exec("systemctl is-active pmtiles-swarm 2>/dev/null"));
		$health = json_decode((string)self::Fetch("$base/health"), true);
		return self::SwarmInfo($state, is_array($health) ? $health : null,
			$state === 'active' ? self::CategoriesFeed(self::Fetch("$base/categories.xml?limit=0")) : null, $role);
	}

	/** What $url answers, an error status's body too (/health says why with a 503), or false. */
	private static function Fetch($url)
	{
		return @file_get_contents($url, false, stream_context_create(array('http' => array('timeout' => 5, 'ignore_errors' => true))));
	}

	public static function Ago($seconds)
	{
		if($seconds < 120) { return $seconds.' seconds'; }
		if($seconds < 7200) { return round($seconds / 60).' minutes'; }
		if($seconds < 172800) { return round($seconds / 3600).' hours'; }
		return round($seconds / 86400).' days';
	}

	/**
	 * This machine's state, for the minute's report: load, CPUs, memory, the disks WifiDB uses, its services, PHP, and
	 * pmtiles-swarm when it runs here.
	 * @param string[] $disks paths whose filesystems to measure
	 * @param string $swarm_role see SwarmInfo()
	 */
	public static function Collect(array $disks = array('/', '/srv/wifidb-data'), $swarm_role = '')
	{
		$info = array('php' => PHP_VERSION, 'host' => gethostname());
		$load = function_exists('sys_getloadavg') ? sys_getloadavg() : false;
		if($load) { $info['load'] = array_map(function ($l) { return round($l, 2); }, $load); }
		$cpus = @file_get_contents('/proc/cpuinfo');
		if($cpus) { $info['cpus'] = max(1, preg_match_all('/^processor\s*:/m', $cpus)); }
		$mem = @file_get_contents('/proc/meminfo');
		if($mem && preg_match('/^MemTotal:\s+(\d+)/m', $mem, $t) && preg_match('/^MemAvailable:\s+(\d+)/m', $mem, $a))
		{
			$info['memory'] = array('total' => (int)$t[1] * 1024, 'available' => (int)$a[1] * 1024);
		}
		$uptime = @file_get_contents('/proc/uptime');
		if($uptime) { $info['uptime'] = (int)$uptime; }
		$info['disks'] = array();
		foreach($disks as $path)
		{
			if(!is_dir($path)) { continue; }
			$total = @disk_total_space($path);
			$free = @disk_free_space($path);
			if($total) { $info['disks'][$path] = array('total' => (int)$total, 'free' => (int)$free); }
		}
		$info['services'] = array();
		if(is_executable('/usr/bin/systemctl') || is_executable('/bin/systemctl'))
		{
			$fpm = 'php'.PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION.'-fpm';
			foreach(self::SERVICES as $service)
			{
				$unit = escapeshellarg($service === 'php-fpm' ? $fpm : $service);
				$state = trim((string)@shell_exec("systemctl is-active $unit 2>/dev/null"));
				# not running and not switched on either: switched off for this node (the daemons, the updater can be)
				if($state !== 'active' && trim((string)@shell_exec("systemctl is-enabled $unit 2>/dev/null")) === 'disabled') { $state = 'off'; }
				$info['services'][$service] = $state !== '' ? $state : 'unknown';
			}
			$swarm = self::CollectSwarm($swarm_role);
			if($swarm !== null) { $info['swarm'] = $swarm; }
		}
		return $info;
	}
}

/** Release versions: 1.2.3, or 1.2.3-rc.1 (a pre-release, older than 1.2.3). */
class WifidbVersion
{
	const RE = '/^v?(\d+)\.(\d+)\.(\d+)(?:-([0-9A-Za-z.]+))?$/';

	public static function Valid($version) { return is_string($version) && preg_match(self::RE, $version) === 1; }

	public static function IsPrerelease($version) { return self::Valid($version) && strpos(ltrim($version, 'v'), '-') !== false; }

	/** <0, 0 or >0, as $a is older than, the same as or newer than $b. Not a version sorts oldest. */
	public static function Compare($a, $b)
	{
		$pa = preg_match(self::RE, (string)$a, $ma);
		$pb = preg_match(self::RE, (string)$b, $mb);
		if(!$pa || !$pb) { return $pa - $pb; }
		for($i = 1; $i <= 3; $i++) { if((int)$ma[$i] !== (int)$mb[$i]) { return (int)$ma[$i] <=> (int)$mb[$i]; } }
		$ra = $ma[4] ?? ''; $rb = $mb[4] ?? '';
		if($ra === $rb) { return 0; }
		if($ra === '') { return 1; }   # a release is newer than its pre-releases
		if($rb === '') { return -1; }
		$xa = explode('.', $ra); $xb = explode('.', $rb);
		for($i = 0; $i < max(count($xa), count($xb)); $i++)
		{
			if(!isset($xa[$i])) { return -1; }
			if(!isset($xb[$i])) { return 1; }
			$na = ctype_digit($xa[$i]); $nb = ctype_digit($xb[$i]);
			if($na && $nb && (int)$xa[$i] !== (int)$xb[$i]) { return (int)$xa[$i] <=> (int)$xb[$i]; }
			if($na !== $nb) { return $na ? -1 : 1; }
			if(!$na && $xa[$i] !== $xb[$i]) { return strcmp($xa[$i], $xb[$i]); }
		}
		return 0;
	}
}
