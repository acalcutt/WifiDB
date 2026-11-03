<?php
/*
traffic.inc.php, the site's own traffic counts, for admin/traffic.php: page views and unique visitors for the whole
site, by country and by page, and what people did (sign-ups, sign-ins, uploads, imports), by hour, day and month. As in
tippr: counted by the site itself instead of from the web server's logs (AWStats), so with several nodes on one database
it's all in one place.

Each node writes what happens to a file of its own (tools/log/traffic/pending.log: one line a page view, a line costs a
page an append and no database), and tools/cron/traffic adds that file to the counts in the database each minute
(Flush). A page view is counted when it's a page someone looked at: a GET of a site page, not from a bot. A unique
visitor is counted the first time their key is seen in a period, on whichever node; the key is a salted hash of their
username (or, signed out, their address and browser), so no address is kept, and keys are deleted once their period is
over (Prune).
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class Traffic
{
	const SPANS = array('hour', 'day', 'month');
	/** How long after a period starts its visitor keys are kept. */
	const KEEP = array('hour' => 7200, 'day' => 172800, 'month' => 2851200);
	/** Days the hourly counts are kept (the event totals read the last 30). */
	const HOURS_KEPT = 35;
	/** Kinds with unique visitors (page and event counts are only how many). */
	const UNIQUE_KINDS = array('site', 'country');
	const BOTS = '/bot\b|bot\/|spider|crawl|slurp|archiver|facebookexternalhit|embedly|preview|feedfetcher|monitor|uptime|curl\/|wget\/|python-|httpclient|okhttp|java\/|go-http|libwww|headless|scrapy|lighthouse|pingdom/i';
	/** What people did: event name => label (admin/traffic.php). */
	const EVENTS = array(
		'signup'        => 'Sign-ups',
		'login'         => 'Sign-ins',
		'login_failed'  => 'Failed sign-ins',
		'upload'        => 'Uploads (site)',
		'upload_api'    => 'Uploads (API, Vistumbler)',
		'import_done'   => 'Imports finished',
		'import_failed' => 'Imports set aside',
	);
	const SPOOL = 'pending.log';

	private $dir;
	private $key;

	/**
	 * @param string $dir this node's spool folder (tools/log/traffic)
	 * @param string $key what visitors' keys are hashed with: the same on every node (two_factor_key)
	 */
	public function __construct($dir, $key)
	{
		$this->dir = rtrim((string)$dir, '/');
		$this->key = (string)$key;
	}

	/** @return Traffic|null the spool for this install, tools/log/traffic under config's wifidb_tools (null without one) */
	public static function ForConfig(array $config)
	{
		if(empty($config['wifidb_tools'])) { return null; }
		return new self(rtrim((string)$config['wifidb_tools'], '/').'/log/traffic', isset($config['two_factor_key']) ? $config['two_factor_key'] : '');
	}

	/** Counts one of EVENTS, from a page or a daemon: never throws, and does nothing it can't. */
	public static function Note($config, $name)
	{
		try
		{
			$traffic = is_array($config) ? self::ForConfig($config) : null;
			return $traffic !== null && $traffic->Event($name);
		}
		catch(Throwable $e) { return false; }
	}

	public static function IsBot($agent)
	{
		return !is_string($agent) || $agent === '' || preg_match(self::BOTS, $agent) === 1;
	}

	/**
	 * Counts a page view, if it is one someone looked at.
	 * @param string $page the script, from the site's root (opt/fetch.php)
	 * @param string $username who's signed in ('' for nobody)
	 * @param array $server $_SERVER
	 * @param string|null $country their country (lib/visitor_country.inc.php), null when it can't be told
	 * @return bool whether it was counted
	 */
	public function Page($page, $username, array $server, $country, $now = null)
	{
		$method = isset($server['REQUEST_METHOD']) ? $server['REQUEST_METHOD'] : '';
		$agent = isset($server['HTTP_USER_AGENT']) ? (string)$server['HTTP_USER_AGENT'] : '';
		$purpose = strtolower(isset($server['HTTP_SEC_PURPOSE']) ? $server['HTTP_SEC_PURPOSE'] : (isset($server['HTTP_PURPOSE']) ? $server['HTTP_PURPOSE'] : ''));
		if($method !== 'GET' || self::IsBot($agent) || strpos($purpose, 'prefetch') !== false) { return false; }
		$ip = isset($server['REMOTE_ADDR']) ? (string)$server['REMOTE_ADDR'] : '';
		$visitor = substr(hash_hmac('sha256', $username !== '' ? "u:$username" : "a:$ip:$agent", 'traffic'.$this->key), 0, 20);
		$page = preg_replace('/[^A-Za-z0-9_\/.-]/', '', (string)$page);
		$country = (is_string($country) && preg_match('/^[A-Z]{2}$/', $country)) ? $country : '';
		return $this->Write(array('P', $now === null ? time() : $now, $visitor, $country, substr($page, 0, 128)));
	}

	/** Counts something someone did: one of EVENTS. */
	public function Event($name, $now = null)
	{
		if(!isset(self::EVENTS[$name])) { return false; }
		return $this->Write(array('E', $now === null ? time() : $now, $name));
	}

	private function Write(array $fields)
	{
		if(!is_dir($this->dir) && !@mkdir($this->dir, 0775, true)) { return false; }
		return @file_put_contents($this->dir.'/'.self::SPOOL, implode("\t", $fields)."\n", FILE_APPEND | LOCK_EX) !== false;
	}

	/**
	 * Adds this node's spool to the counts in the database (tools/cron/traffic, each minute). The spool is moved aside
	 * first, so pages go on writing to a new one; one a run before didn't finish is added too.
	 * @return int how many lines were added
	 */
	public function Flush(PDO $conn)
	{
		$spool = $this->dir.'/'.self::SPOOL;
		if(is_file($spool) && filesize($spool) > 0) { @rename($spool, $this->dir.'/flushing-'.gmdate('YmdHis').'-'.getmypid().'.log'); }
		$lines = 0;
		foreach(glob($this->dir.'/flushing-*.log') ?: array() as $file)
		{
			$counts = array();
			foreach(file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: array() as $line)
			{
				$f = explode("\t", $line);
				if(count($f) < 3 || !ctype_digit($f[1])) { continue; }
				$hour = (int)$f[1] - (int)$f[1] % 3600;
				if($f[0] === 'P' && count($f) === 5)
				{
					foreach(array(array('site', ''), array('country', $f[3]), array('page', $f[4])) as $scope)
					{
						if($scope[0] === 'country' && $scope[1] === '') { $scope[1] = '--'; }   # not known
						self::Tally($counts, $scope[0], $scope[1], $hour, $f[2]);
					}
				}
				elseif($f[0] === 'E' && isset(self::EVENTS[$f[2]])) { self::Tally($counts, 'event', $f[2], $hour, null); }
				else { continue; }
				$lines++;
			}
			self::Save($conn, $counts);
			unlink($file);
		}
		return $lines;
	}

	private static function Tally(array &$counts, $kind, $item, $hour, $visitor)
	{
		$key = $kind."\t".$item."\t".$hour;
		if(!isset($counts[$key])) { $counts[$key] = array($kind, $item, $hour, 0, array()); }
		$counts[$key][3]++;
		if($visitor !== null && in_array($kind, self::UNIQUE_KINDS, true)) { $counts[$key][4][$visitor] = true; }
	}

	public static function PeriodStart($hour, $span)
	{
		if($span === 'hour') { return $hour; }
		if($span === 'day') { return $hour - $hour % 86400; }
		return gmmktime(0, 0, 0, (int)gmdate('n', $hour), 1, (int)gmdate('Y', $hour));
	}

	private static function Stamp($time) { return gmdate('Y-m-d H:i:s', $time); }

	/** Adds the tallies: each (kind, item, period)'s page views, and the visitors not counted there before (by any node). */
	private static function Save(PDO $conn, array $counts)
	{
		$update = $conn->prepare("UPDATE traffic_counts SET pageviews = pageviews + ?, visitors = visitors + ? WHERE kind = ? AND item = ? AND span = ? AND period = ?");
		$insert = $conn->prepare("INSERT INTO traffic_counts (kind, item, span, period, pageviews, visitors) VALUES (?, ?, ?, ?, ?, ?)");
		$seen = $conn->prepare("INSERT INTO traffic_visitors (kind, item, span, period, visitor) VALUES (?, ?, ?, ?, ?)");
		foreach($counts as $c)
		{
			list($kind, $item, $hour, $views, $visitors) = $c;
			foreach(self::SPANS as $span)
			{
				$period = self::Stamp(self::PeriodStart($hour, $span));
				$new = 0;
				foreach(array_keys($visitors) as $visitor)
				{
					# counted before in this period (here or on another node): the key is already there
					try { $seen->execute(array($kind, $item, $span, $period, $visitor)); $new++; }
					catch(PDOException $e) {}
				}
				$update->execute(array($views, $new, $kind, $item, $span, $period));
				if($update->rowCount() > 0) { continue; }
				try { $insert->execute(array($kind, $item, $span, $period, $views, $new)); }
				catch(PDOException $e)
				{
					# another node made the row just now
					$update->execute(array($views, $new, $kind, $item, $span, $period));
				}
			}
		}
	}

	/**
	 * Deletes the visitor keys of periods that are over, and hourly counts older than HOURS_KEPT (days and months are
	 * kept). @return int how many rows
	 */
	public static function Prune(PDO $conn, $now = null)
	{
		$now = $now === null ? time() : $now;
		$gone = 0;
		$prep = $conn->prepare("DELETE FROM traffic_visitors WHERE span = ? AND period < ?");
		foreach(self::KEEP as $span => $keep)
		{
			$prep->execute(array($span, self::Stamp($now - $keep)));
			$gone += $prep->rowCount();
		}
		$prep = $conn->prepare("DELETE FROM traffic_counts WHERE span = 'hour' AND period < ?");
		$prep->execute(array(self::Stamp($now - self::HOURS_KEPT * 86400)));
		return $gone + $prep->rowCount();
	}

	#---# Reading them (admin/traffic.php)

	/**
	 * @return array the last $count periods of $span, newest first, each [start (Unix time), visitors, page views]
	 *         (periods with nothing counted are there with 0)
	 */
	public static function History(PDO $conn, $kind, $item, $span, $count, $now = null)
	{
		$now = $now === null ? time() : $now;
		$starts = array();
		$start = self::PeriodStart($now - $now % 3600, $span);
		for($i = 0; $i < $count; $i++)
		{
			$starts[] = $start;
			$start = $span === 'hour' ? $start - 3600 : ($span === 'day' ? $start - 86400 : self::PeriodStart($start - 86400, 'month'));
		}
		$prep = $conn->prepare("SELECT period, visitors, pageviews FROM traffic_counts WHERE kind = ? AND item = ? AND span = ? AND period >= ?");
		$prep->execute(array($kind, $item, $span, self::Stamp(end($starts))));
		$found = array();
		foreach($prep->fetchAll(PDO::FETCH_NUM) as $row) { $found[strtotime($row[0].' UTC')] = array((int)$row[1], (int)$row[2]); }
		$out = array();
		foreach($starts as $s) { $out[] = array($s, isset($found[$s]) ? $found[$s][0] : 0, isset($found[$s]) ? $found[$s][1] : 0); }
		return $out;
	}

	/** @return array $kind's items in the period of $span starting $start, most page views first: [item, visitors, page views] */
	public static function Top(PDO $conn, $kind, $span, $start, $limit = 50)
	{
		$prep = $conn->prepare("SELECT item, visitors, pageviews FROM traffic_counts WHERE kind = ? AND span = ? AND period = ? ORDER BY pageviews DESC, item");
		$prep->execute(array($kind, $span, self::Stamp($start)));
		return array_map(function ($r) { return array($r[0], (int)$r[1], (int)$r[2]); }, array_slice($prep->fetchAll(PDO::FETCH_NUM), 0, $limit));
	}

	/** @return array each of EVENTS' counts over the last $days days: name => count */
	public static function EventTotals(PDO $conn, $days, $now = null)
	{
		$now = $now === null ? time() : $now;
		$prep = $conn->prepare("SELECT item, SUM(pageviews) FROM traffic_counts WHERE kind = 'event' AND span = 'hour' AND period >= ? GROUP BY item");
		$prep->execute(array(self::Stamp($now - $now % 3600 - ($days * 24 - 1) * 3600)));
		$totals = array_fill_keys(array_keys(self::EVENTS), 0);
		foreach($prep->fetchAll(PDO::FETCH_NUM) as $row) { if(isset($totals[$row[0]])) { $totals[$row[0]] = (int)$row[1]; } }
		return $totals;
	}
}
