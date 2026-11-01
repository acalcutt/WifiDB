<?php
/*
backups.inc.php, the backups folder and the work done on it, for admin/backups.php and tools/utilites/backup.php.

The folder (backup_dir in config.inc.php; the Proxmox template keeps it on the data disk) holds
  wifidb-backup-YYYYMMDD-HHMMSS.tar    a backup (lib/backup.inc.php); .tar.partial while it's being made or copied
  wifidb-backup-YYYYMMDD-HHMMSS.json   its status: state, step, size, SHA-256 of the whole file, where it came from
  queue/*.json                         requests from the admin page, run one at a time by tools/cron/backups
  job.json                             the request being run, or the last one, with its log
  restoring                            there while a restore runs: the scheduled daemons don't start (schedulecheck.php)
  transfer-key.json                    the hash of the key another server uses to copy backups from here

The web site can't do this work itself (a page may only run for seconds), so the page queues requests and a cron job,
as the same user, runs them.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
require_once dirname(__FILE__).'/backup.inc.php';

class BackupService
{
	const DAILY_KEEP = 7;
	const LOG_LINES = 200;

	private $config;
	private $conn;
	private $service;
	private $version;
	public $echo = false;   // also print the log (the command line)

	public function __construct(array $config, ?PDO $conn = null, $service = '', $version = '')
	{
		$this->config = $config;
		$this->conn = $conn;
		$this->service = $service;
		$this->version = $version;
	}

	public static function DirFor(array $config)
	{
		if(!empty($config['backup_dir'])) { return rtrim($config['backup_dir'], '/\\'); }
		return rtrim(isset($config['wifidb_tools']) ? $config['wifidb_tools'] : '.', '/\\').'/backups';
	}

	/** The file that says a restore is running: the scheduled daemons wait while it's there. */
	public static function PauseFile(array $config) { return self::DirFor($config).'/restoring'; }

	public function Dir() { return self::DirFor($this->config); }
	public function Path($name) { return $this->Dir().'/'.$name.'.tar'; }
	private function StatusPath($name) { return $this->Dir().'/'.$name.'.json'; }
	public static function ValidName($name) { return is_string($name) && preg_match(WifidbBackup::NAME_RE, $name); }

	public function Ready()
	{
		$dir = $this->Dir();
		if(!is_dir($dir)) { @mkdir($dir, 0700, true); @mkdir($dir.'/queue', 0700, true); }
		return is_dir($dir) && is_writable($dir) && (is_dir($dir.'/queue') || @mkdir($dir.'/queue', 0700, true));
	}

	// ── Listing ──────────────────────────────────────────────────────────────────────────────────────

	/** Every backup in the folder, newest first: name, state, step, size, sha256, created, kind, source, rows, error. */
	public function Backups()
	{
		$out = array();
		foreach(glob($this->Dir().'/wifidb-backup-*.{tar,json,tar.partial}', GLOB_BRACE) ?: array() as $file)
		{
			$name = preg_replace('/\.(tar|json|tar\.partial)$/', '', basename($file));
			if(self::ValidName($name) && !isset($out[$name])) { $out[$name] = $this->Get($name); }
		}
		$out = array_filter($out);
		uasort($out, function ($a, $b) { return strcmp($b['name'], $a['name']); });
		return array_values($out);
	}

	/** One backup's status, or null. A .tar copied in by hand gets its status from its manifest. */
	public function Get($name)
	{
		if(!self::ValidName($name)) { return null; }
		$status = self::ReadJson($this->StatusPath($name));
		$tar = $this->Path($name);
		if(!$status && is_file($tar))
		{
			try
			{
				$manifest = WifidbBackup::ReadManifest($tar);
				$status = $this->StatusFrom($manifest, 'copied in');
				$status['size'] = filesize($tar);
				self::WriteJson($this->StatusPath($name), $status);
			}
			catch(Throwable $e)
			{
				$status = array('name' => $name, 'state' => 'failed', 'error' => $e->getMessage(), 'size' => filesize($tar));
			}
		}
		if(!$status) { return null; }
		$status['name'] = $name;
		if(($status['state'] ?? '') === 'done' && !is_file($tar)) { $status['state'] = 'missing'; }
		return $status;
	}

	private function StatusFrom(array $manifest, $kind)
	{
		return array(
			'name' => $manifest['name'], 'state' => 'done', 'kind' => $kind, 'created' => $manifest['created'],
			'source' => $manifest['source'], 'rows' => $manifest['rows'] ?? 0, 'tables' => count($manifest['tables']),
		);
	}

	public static function ReadJson($file)
	{
		if(!is_file($file)) { return null; }
		$data = json_decode((string)@file_get_contents($file), true);
		return is_array($data) ? $data : null;
	}

	public static function WriteJson($file, array $data)
	{
		$tmp = $file.'.tmp'.getmypid();
		file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		@chmod($tmp, 0600);
		rename($tmp, $file);
	}

	// ── Requests ─────────────────────────────────────────────────────────────────────────────────────

	/** Hands a request to the cron job: export, restore, delete, import, upload, fetch. */
	public function Queue($action, $requestedBy, array $fields = array())
	{
		if(!$this->Ready()) { throw new BackupException("The backups folder ".$this->Dir()." can't be written"); }
		$request = $fields + array('action' => $action, 'requested_by' => $requestedBy, 'requested' => gmdate('Y-m-d H:i:s'));
		self::WriteJson($this->Dir().'/queue/'.sprintf('%.0f', microtime(true) * 1000).'-'.bin2hex(random_bytes(3)).'.json', $request);
	}

	/** Requests not started yet, oldest first. A transfer key in one is never shown. */
	public function Pending()
	{
		$out = array();
		foreach(glob($this->Dir().'/queue/*.json') ?: array() as $file)
		{
			$r = self::ReadJson($file);
			if($r) { unset($r['key']); $out[] = $r; }
		}
		return $out;
	}

	/** The request being run, or the last one. */
	public function Job() { return self::ReadJson($this->Dir().'/job.json'); }

	public function Busy()
	{
		$job = $this->Job();
		return ($job && ($job['state'] ?? '') === 'running') || $this->Pending();
	}

	private $lock = null;

	/** One request at a time, whether from the queue or the command line. */
	private function Lock($wait)
	{
		if($this->lock) { return true; }
		$fh = fopen($this->Dir().'/.lock', 'c');
		if(!$fh || !flock($fh, $wait ? LOCK_EX : LOCK_EX | LOCK_NB)) { return false; }
		$this->lock = $fh;
		return true;
	}

	private function Unlock()
	{
		if($this->lock) { flock($this->lock, LOCK_UN); fclose($this->lock); $this->lock = null; }
	}

	/** Runs every waiting request, one at a time (tools/cron/backups, each minute). Returns how many ran. */
	public function RunQueue()
	{
		if(!$this->Lock(false)) { return 0; }   # one is already running
		$ran = 0;
		while(true)
		{
			$files = glob($this->Dir().'/queue/*.json') ?: array();
			sort($files);
			if(!$files) { break; }
			$request = self::ReadJson($files[0]);
			unlink($files[0]);
			if(!$request) { continue; }
			$this->Run($request);
			$ran++;
		}
		$this->Unlock();
		return $ran;
	}

	private $job = null;

	public function Log($line)
	{
		$line = '['.gmdate('H:i:s').'] '.$line;
		if($this->echo) { echo $line."\n"; }
		if($this->job !== null)
		{
			$this->job['log'][] = $line;
			$this->job['log'] = array_slice($this->job['log'], -self::LOG_LINES);
			self::WriteJson($this->Dir().'/job.json', $this->job);
		}
	}

	/** Runs one request now, keeping job.json up to date. @return bool whether it worked */
	public function Run(array $request)
	{
		$ownLock = !$this->lock;
		if($ownLock) { $this->Lock(true); }   # the command line waits for a request that's running
		$public = $request;
		unset($public['key']);
		$this->job = $public + array('state' => 'running', 'started' => gmdate('Y-m-d H:i:s'), 'log' => array());
		$this->Log(($request['action'] ?? '?').' requested by '.($request['requested_by'] ?? '?'));
		try
		{
			switch($request['action'] ?? '')
			{
				case 'export':  $this->job['name'] = $this->Export(!empty($request['daily']) ? 'daily' : 'manual', $request['requested_by'] ?? ''); break;
				case 'restore': $this->Restore($request['name'] ?? ''); break;
				case 'delete':  $this->Delete($request['name'] ?? ''); break;
				default: throw new BackupException("Unknown request: ".($request['action'] ?? ''));
			}
			$this->job['state'] = 'done';
			$this->Log('done');
			$ok = true;
		}
		catch(Throwable $e)
		{
			$this->job['state'] = 'failed';
			$this->job['error'] = $e->getMessage();
			$this->Log('failed: '.$e->getMessage());
			$ok = false;
		}
		$this->job['finished'] = gmdate('Y-m-d H:i:s');
		self::WriteJson($this->Dir().'/job.json', $this->job);
		$this->job = null;
		if($ownLock) { $this->Unlock(); }
		return $ok;
	}

	// ── The work ─────────────────────────────────────────────────────────────────────────────────────

	private function Engine()
	{
		if(!$this->conn) { throw new BackupException("No database connection"); }
		$engine = new WifidbBackup($this->conn, $this->service);
		$engine->log = array($this, 'Log');
		$engine->tmpDir = $this->Dir();
		return $engine;
	}

	private function Migrations()
	{
		require_once dirname(__FILE__).'/migrate.inc.php';
		$dir = dirname(__FILE__).'/../../sql/migrations';
		if(!empty($this->config['wifidb_tools'])) { $dir = rtrim($this->config['wifidb_tools'], '/').'/../sql/migrations'; }
		$m = new Migrator($this->conn, $this->service, $dir);
		return $m->Applied();
	}

	/** Makes a backup. @param string $kind manual or daily (daily ones beyond DAILY_KEEP are removed) @return string its name */
	public function Export($kind = 'manual', $by = '')
	{
		if(!$this->Ready()) { throw new BackupException("The backups folder ".$this->Dir()." can't be written"); }
		$name = 'wifidb-backup-'.gmdate('Ymd-His');
		$status = array('name' => $name, 'state' => 'running', 'kind' => $kind, 'by' => $by, 'created' => gmdate('Y-m-d H:i:s'), 'step' => 'starting');
		self::WriteJson($this->StatusPath($name), $status);
		$engine = $this->Engine();
		$engine->log = function ($line) use (&$status, $name) {
			$this->Log($line);
			$status['step'] = $line;
			self::WriteJson($this->StatusPath($name), $status);
		};
		try
		{
			$source = array('site' => $this->config['hosturl'] ?? '', 'wifidb_version' => $this->version, 'migrations' => $this->Migrations());
			$manifest = $engine->Export($this->Path($name), $source, array('two_factor_key' => $this->config['two_factor_key'] ?? ''));
			@chmod($this->Path($name), 0600);
			$this->Log("checking the finished file");
			$status = $this->StatusFrom($manifest, $kind) + array('by' => $by);
			$status['size'] = filesize($this->Path($name));
			$status['sha256'] = hash_file('sha256', $this->Path($name));
			self::WriteJson($this->StatusPath($name), $status);
			$this->Log("$name: ".number_format($status['rows'])." rows, ".self::HumanSize($status['size']));
		}
		catch(Throwable $e)
		{
			@unlink($this->Path($name).'.partial');
			foreach(glob($this->Path($name).'.parts/*') ?: array() as $f) { @unlink($f); }
			@rmdir($this->Path($name).'.parts');
			$status['state'] = 'failed';
			$status['error'] = $e->getMessage();
			self::WriteJson($this->StatusPath($name), $status);
			throw $e;
		}
		if($kind === 'daily') { $this->PruneDaily(); }
		return $name;
	}

	private function PruneDaily()
	{
		$daily = array_values(array_filter($this->Backups(), function ($b) { return ($b['kind'] ?? '') === 'daily' && ($b['state'] ?? '') === 'done'; }));
		$keep = (int)($this->config['backup_keep'] ?? self::DAILY_KEEP);
		foreach(array_slice($daily, max(1, $keep)) as $old)
		{
			$this->Log("removing the old daily backup ".$old['name']);
			$this->Delete($old['name']);
		}
	}

	public function Delete($name)
	{
		if(!self::ValidName($name)) { throw new BackupException("No backup named $name"); }
		if(($this->Get($name)['state'] ?? '') === 'running') { throw new BackupException("$name is still being made"); }
		foreach(array($this->Path($name), $this->Path($name).'.partial', $this->StatusPath($name)) as $f) { @unlink($f); }
		$this->Log("deleted $name");
	}

	/**
	 * Replaces this site's data with a backup's. The scheduled daemons are paused while it runs. The backup's
	 * two_factor_key is installed, so authenticator apps and saved keys keep working.
	 */
	public function Restore($name)
	{
		$status = $this->Get($name);
		if(!$status || $status['state'] !== 'done') { throw new BackupException("$name isn't a finished backup"); }
		$pause = self::PauseFile($this->config);
		file_put_contents($pause, gmdate('Y-m-d H:i:s')." restoring $name\n");
		try
		{
			$this->Log("restoring $name (made ".$status['created'].' on '.($status['source']['site'] ?? '?').", ".($status['source']['service'] ?? '?').")");
			$report = $this->Engine()->Restore($this->Path($name), $this->Migrations(), $this->Dir()."/$name.rejects.jsonl");
			foreach($report['tables'] as $table => $s)
			{
				if($s['rejected']) { $this->Log("  $table: ".$s['loaded']." of ".$s['expected']." rows; ".$s['rejected']." refused"); }
			}
			if($report['missing_here']) { $this->Log("tables in the backup that this database doesn't have (not restored): ".implode(', ', $report['missing_here'])); }
			if($report['not_in_backup']) { $this->Log("tables here that the backup doesn't have (left empty): ".implode(', ', $report['not_in_backup'])); }
			if($report['zero_dates']) { $this->Log($report['zero_dates']." zero dates (0000-00-00) became empty or 1900-01-01"); }
			if($report['rejected']) { $this->Log($report['rejected']." rows were refused by this database; they're in $name.rejects.jsonl"); }
			$this->InstallKey(WifidbBackup::ReadSecrets($this->Path($name)));
			$this->Log("restored ".number_format(array_sum(array_column($report['tables'], 'loaded')))." rows. Everyone has been signed out.");
		}
		finally
		{
			@unlink($pause);
		}
	}

	/** Puts the backup's two_factor_key in place, if it differs and this server says where its settings file is. */
	private function InstallKey(array $secrets)
	{
		$key = $secrets['two_factor_key'] ?? '';
		if($key === '' || $key === ($this->config['two_factor_key'] ?? '')) { return; }
		$file = $this->config['overrides_file'] ?? '';
		if($file === '' || !is_file($file) || !is_writable($file))
		{
			$this->Log("NOTE: the backup's two_factor_key differs from this server's. Set two_factor_key in config.inc.php to the one in the backup's secrets.json, or authenticator apps and the CleanTalk key won't work.");
			return;
		}
		$overrides = include $file;
		$overrides = is_array($overrides) ? $overrides : array();
		$overrides['two_factor_key'] = $key;
		file_put_contents($file, "<?php\n// Settings that differ from the release's defaults. Edit freely; wifidb-firstboot leaves this file alone.\nreturn ".var_export($overrides, true).";\n");
		$this->Log("installed the backup's two_factor_key in $file");
	}

	/**
	 * Sends a file to the browser or another server, from the byte a Range header asks for, so a big download that was
	 * cut off can carry on where it stopped. Needs a PHP pool without a time limit (the template's backup pool).
	 */
	public static function SendFile($path, $downloadName)
	{
		$size = filesize($path);
		$start = 0; $end = $size - 1;
		if(isset($_SERVER['HTTP_RANGE']) && preg_match('/^bytes=(\d*)-(\d*)$/', trim($_SERVER['HTTP_RANGE']), $m) && ($m[1] !== '' || $m[2] !== ''))
		{
			if($m[1] === '') { $start = max(0, $size - (int)$m[2]); }
			else { $start = (int)$m[1]; if($m[2] !== '') { $end = min($end, (int)$m[2]); } }
			if($start > $end || $start >= $size)
			{
				header('HTTP/1.1 416 Range Not Satisfiable');
				header("Content-Range: bytes */$size");
				return;
			}
			header('HTTP/1.1 206 Partial Content');
			header("Content-Range: bytes $start-$end/$size");
		}
		header('Content-Type: application/x-tar');
		header('Content-Disposition: attachment; filename="'.$downloadName.'"');
		header('Accept-Ranges: bytes');
		header('Content-Length: '.($end - $start + 1));
		header('Cache-Control: no-store');
		header('X-Content-Type-Options: nosniff');
		while(ob_get_level()) { ob_end_clean(); }
		@set_time_limit(0);
		$fh = fopen($path, 'rb');
		fseek($fh, $start);
		$left = $end - $start + 1;
		while($left > 0 && !connection_aborted())
		{
			$chunk = fread($fh, (int)min(1024 * 1024, $left));
			if($chunk === '' || $chunk === false) { break; }
			echo $chunk;
			flush();
			$left -= strlen($chunk);
		}
		fclose($fh);
	}

	public static function HumanSize($n)
	{
		$n = (float)$n;
		foreach(array('B', 'KB', 'MB', 'GB') as $unit)
		{
			if($n < 1024 || $unit === 'GB') { return $unit === 'B' ? sprintf('%d B', $n) : sprintf('%.1f %s', $n, $unit); }
			$n /= 1024;
		}
		return sprintf('%.1f TB', $n);
	}
}
