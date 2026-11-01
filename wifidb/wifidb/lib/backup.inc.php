<?php
/*
backup.inc.php, backups of the whole database that restore into any of the databases WifiDB runs on: one made on SQL
Server restores on MariaDB or PostgreSQL, and every other way round.

A backup is one .tar file, wifidb-backup-YYYYMMDD-HHMMSS.tar, holding
  tables/<table>.<part>.jsonl.gz   the rows, one JSON array per line, in the column order the manifest gives
  secrets.json                     two_factor_key, so authenticator apps and saved keys keep working on another server
  manifest.json                    (last) where it came from, every table's columns, row counts, and each file's SHA-256
Values are written so neither database's own types leak: dates as "Y-m-d H:i:s", binary columns as base64, and the odd
text that isn't valid UTF-8 as {"x": base64}. Big tables are split into parts so no file in the tar is huge.

Restore loads the rows into the tables this server already has (its own schema and migrations), matching columns by
name, keeping ids, then fixes the id counters. Tables that belong to the server rather than the site are left alone (see
LOCAL_TABLES). A row the target refuses (too long, a duplicate under a different collation) is skipped and written to
the restore's rejects file instead of stopping the restore.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class BackupException extends Exception {}

class WifidbBackup
{
	const FORMAT = 'wifidb-backup';
	const FORMAT_VERSION = 1;
	const ROWS_PER_PART = 500000;
	const NAME_RE = '/^wifidb-backup-\d{8}-\d{6}(-[a-z0-9]+)?$/';

	/**
	 * This server's own tables: not in a backup, and a restore keeps what this server has. Sessions (everyone signs in
	 * again after a restore), the migrations this database has had, the node's daemon switch and schedule, running
	 * daemons, and queued admin jobs, which point at rows the restore replaces.
	 */
	const LOCAL_TABLES = array('user_login_hashes', 'schema_migrations', 'settings', 'schedule', 'daemon_pid_stats', 'admin_jobs');
	/** Emptied by a restore though not restored: they'd point at the old site's state. */
	const EMPTIED_TABLES = array('user_login_hashes', 'daemon_pid_stats', 'admin_jobs');

	private $conn;
	private $service;
	public $log;          // function ($line): progress lines
	public $clock;        // function (): Unix time (tests)
	public $rowsPerPart = self::ROWS_PER_PART;
	public $tmpDir = '';
	private $held = array();
	private $foreignKeys = array();   // PostgreSQL's, while a restore has them off   // values bound by reference (SQL Server binary)  // where a restore unpacks one part at a time: next to the backup, not /tmp

	/** @param string $service 'mysql' (MariaDB), 'sqlsrv', 'pgsql', or 'sqlite' (tests) */
	public function __construct(PDO $conn, $service)
	{
		$this->conn = $conn;
		$this->service = $service;
	}

	private function Say($line)
	{
		if($this->log) { call_user_func($this->log, $line); }
	}

	private function Now() { return $this->clock ? call_user_func($this->clock) : time(); }

	// ── What's in this database ──────────────────────────────────────────────────────────────────────

	public function Q($name)
	{
		if($this->service === 'sqlsrv') { return '['.str_replace(']', ']]', $name).']'; }
		if($this->service === 'mysql') { return '`'.str_replace('`', '``', $name).'`'; }
		return '"'.str_replace('"', '""', $name).'"';
	}

	/** The site's tables (not this server's own), sorted. */
	public function Tables()
	{
		if($this->service === 'mysql') { $sql = "SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_TYPE = 'BASE TABLE'"; }
		elseif($this->service === 'sqlsrv') { $sql = "SELECT TABLE_NAME FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_TYPE = 'BASE TABLE' AND TABLE_SCHEMA = 'dbo'"; }
		elseif($this->service === 'pgsql') { $sql = "SELECT table_name FROM information_schema.tables WHERE table_type = 'BASE TABLE' AND table_schema = current_schema()"; }
		else { $sql = "SELECT name FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%'"; }
		$tables = array();
		foreach($this->conn->query($sql)->fetchAll(PDO::FETCH_COLUMN) as $name)
		{
			if(!in_array(strtolower($name), self::LOCAL_TABLES, true)) { $tables[] = $name; }
		}
		sort($tables, SORT_STRING | SORT_FLAG_CASE);
		return $tables;
	}

	/** @return array of [name, kind, nullable]: kind is binary, datetime, date, bool, skip (can't be written) or value */
	public function Columns($table)
	{
		$columns = array();
		if($this->service === 'sqlite')
		{
			foreach($this->conn->query("PRAGMA table_info(".$this->Q($table).")")->fetchAll(PDO::FETCH_ASSOC) as $c)
			{
				$columns[] = array($c['name'], self::Kind($c['type']), !(int)$c['notnull']);
			}
			return $columns;
		}
		$schema = $this->service === 'mysql' ? 'DATABASE()' : ($this->service === 'pgsql' ? 'current_schema()' : "'dbo'");
		$prep = $this->conn->prepare("SELECT COLUMN_NAME, DATA_TYPE, IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = $schema ORDER BY ORDINAL_POSITION");
		$prep->execute(array($table));
		foreach($prep->fetchAll(PDO::FETCH_NUM) as $c)
		{
			$kind = self::Kind($c[1]);
			if($this->service === 'sqlsrv' && in_array(strtolower($c[1]), array('timestamp', 'rowversion'), true)) { $kind = 'skip'; }
			$columns[] = array($c[0], $kind, strtoupper($c[2]) === 'YES');
		}
		return $columns;
	}

	public static function Kind($type)
	{
		$t = strtolower(preg_replace('/\(.*$/', '', trim((string)$type)));
		if(in_array($t, array('blob', 'tinyblob', 'mediumblob', 'longblob', 'binary', 'varbinary', 'image', 'bytea'), true)) { return 'binary'; }
		if(in_array($t, array('datetime', 'datetime2', 'smalldatetime', 'timestamp', 'datetimeoffset'), true) || strpos($t, 'timestamp ') === 0) { return 'datetime'; }
		if($t === 'date') { return 'date'; }
		if($t === 'bit' || $t === 'boolean') { return 'bool'; }
		return 'value';
	}

	/** The column the database numbers itself (identity), by table: lower-case table => the column's own name. */
	private function IdentityTables()
	{
		if($this->service === 'sqlsrv') { $sql = "SELECT t.name, c.name FROM sys.identity_columns c JOIN sys.tables t ON t.object_id = c.object_id"; }
		elseif($this->service === 'pgsql') { $sql = "SELECT table_name, column_name FROM information_schema.columns WHERE table_schema = current_schema() AND (is_identity = 'YES' OR column_default LIKE 'nextval(%')"; }
		else { return array(); }
		$out = array();
		foreach($this->conn->query($sql)->fetchAll(PDO::FETCH_NUM) as $r) { $out[strtolower($r[0])] = $r[1]; }
		return $out;
	}

	/** Every row of a query. PostgreSQL's driver would hold a whole table in memory, so it's read through a cursor there. */
	private function Rows($sql)
	{
		if($this->service !== 'pgsql')
		{
			$result = $this->conn->query($sql);
			while(($row = $result->fetch(PDO::FETCH_NUM)) !== false) { yield $row; }
			$result->closeCursor();
			return;
		}
		$this->conn->beginTransaction();
		try
		{
			$this->conn->exec("DECLARE wdb_export NO SCROLL CURSOR FOR ".$sql);
			while($rows = $this->conn->query("FETCH FORWARD 5000 FROM wdb_export")->fetchAll(PDO::FETCH_NUM))
			{
				foreach($rows as $row) { yield $row; }
			}
			$this->conn->exec("CLOSE wdb_export");
		}
		finally
		{
			$this->conn->commit();
		}
	}

	// ── Export ───────────────────────────────────────────────────────────────────────────────────────

	/** A row's values, in a form that means the same to either database. */
	public static function EncodeRow(array $row, array $columns)
	{
		$out = array();
		foreach($columns as $i => $col)
		{
			$v = $row[$i];
			if($v === null || $col[1] === 'skip') { $out[] = null; continue; }
			if(is_resource($v)) { $v = stream_get_contents($v); }
			switch($col[1])
			{
				case 'binary':
					$out[] = base64_encode((string)$v);
					break;
				case 'datetime':
				case 'date':
					$out[] = self::NormalDate((string)$v);
					break;
				case 'bool':
					$out[] = (int)(bool)$v;
					break;
				default:
					if(is_string($v) && !mb_check_encoding($v, 'UTF-8')) { $out[] = array('x' => base64_encode($v)); }
					else { $out[] = $v; }
			}
		}
		return $out;
	}

	/** "2026-01-02 03:04:05.0000000" (SQL Server) and "2026-01-02 03:04:05" (MariaDB) come out the same. */
	public static function NormalDate($v)
	{
		$v = str_replace('T', ' ', trim($v));
		$v = preg_replace('/\.0+$/', '', $v);
		return preg_replace('/(\.\d*?)0+$/', '$1', $v);
	}

	/**
	 * Writes the backup to $tarPath (as $tarPath.partial until it's complete).
	 * @param array $source where it came from: site, service, wifidb_version, migrations
	 * @param array $secrets written to secrets.json
	 * @return array the manifest
	 */
	public function Export($tarPath, array $source, array $secrets = array())
	{
		$work = $tarPath.'.parts';
		if(!is_dir($work) && !mkdir($work, 0700, true)) { throw new BackupException("Can't make $work"); }
		$tar = new BackupTar($tarPath.'.partial', 'w');
		$manifest = array(
			'format' => self::FORMAT,
			'format_version' => self::FORMAT_VERSION,
			'name' => basename($tarPath, '.tar'),
			'created' => gmdate('Y-m-d H:i:s', $this->Now()),
			'source' => $source + array('service' => $this->service),
			'tables' => array(),
			'files' => array(),
		);
		$buffered = null;
		if($this->service === 'mysql')
		{
			# stream the rows instead of holding a whole table in memory
			$buffered = $this->conn->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY);
			$this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		}
		try
		{
			foreach($this->Tables() as $table)
			{
				$columns = $this->Columns($table);
				$this->Say("exporting $table");
				$info = array('columns' => $columns, 'rows' => 0, 'parts' => array());
				$names = implode(', ', array_map(array($this, 'Q'), array_column($columns, 0)));
				$part = 0; $inPart = 0; $gz = null; $partFile = '';
				foreach($this->Rows("SELECT $names FROM ".$this->Q($table)) as $row)
				{
					if($gz === null)
					{
						$part++;
						$partFile = $work.'/part.jsonl.gz';
						$gz = gzopen($partFile, 'wb6');
						if(!$gz) { throw new BackupException("Can't write $partFile"); }
					}
					gzwrite($gz, json_encode(self::EncodeRow($row, $columns), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRESERVE_ZERO_FRACTION)."\n");
					$info['rows']++;
					if(++$inPart >= $this->rowsPerPart)
					{
						gzclose($gz); $gz = null;
						$manifest['files'][] = $info['parts'][] = $this->AddPart($tar, $partFile, $table, $part, $inPart);
						$this->Say("  $table: ".number_format($info['rows'])." rows");
						$inPart = 0;
					}
				}
				if($gz !== null)
				{
					gzclose($gz);
					$manifest['files'][] = $info['parts'][] = $this->AddPart($tar, $partFile, $table, $part, $inPart);
				}
				$info['parts'] = array_column($info['parts'], 'path');
				$manifest['tables'][$table] = $info;
			}
		}
		finally
		{
			if($buffered !== null) { $this->conn->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, $buffered); }
		}

		$secretsFile = $work.'/secrets.json';
		file_put_contents($secretsFile, json_encode($secrets, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
		chmod($secretsFile, 0600);
		$manifest['files'][] = $this->AddMember($tar, $secretsFile, 'secrets.json');

		$manifest['rows'] = array_sum(array_column($manifest['tables'], 'rows'));
		$manifestFile = $work.'/manifest.json';
		file_put_contents($manifestFile, json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
		$tar->Add($manifestFile, 'manifest.json');
		$tar->Close();
		@unlink($manifestFile);
		@rmdir($work);
		if(!rename($tarPath.'.partial', $tarPath)) { throw new BackupException("Can't rename the finished backup to $tarPath"); }
		return $manifest;
	}

	private function AddPart(BackupTar $tar, $file, $table, $part, $rows)
	{
		$entry = $this->AddMember($tar, $file, sprintf('tables/%s.%04d.jsonl.gz', $table, $part));
		return $entry + array('table' => $table, 'rows' => $rows);
	}

	private function AddMember(BackupTar $tar, $file, $name)
	{
		$entry = array('path' => $name, 'size' => filesize($file), 'sha256' => hash_file('sha256', $file));
		$tar->Add($file, $name);
		unlink($file);
		return $entry;
	}

	// ── Restore ──────────────────────────────────────────────────────────────────────────────────────

	/** The manifest of a backup file, or throws if it isn't one. */
	public static function ReadManifest($tarPath)
	{
		$tar = new BackupTar($tarPath, 'r');
		$json = $tar->Read('manifest.json', 2 * 1024 * 1024 * 1024);
		$tar->Close();
		$manifest = json_decode((string)$json, true);
		if(!is_array($manifest) || @$manifest['format'] !== self::FORMAT) { throw new BackupException(basename($tarPath)." is not a WifiDB backup"); }
		if((int)$manifest['format_version'] > self::FORMAT_VERSION) { throw new BackupException("This backup was made by a newer WifiDB; upgrade this one first"); }
		return $manifest;
	}

	/** Checks every file in the backup against its size and SHA-256. Throws on the first that doesn't match. */
	public function Verify($tarPath, $manifest = null)
	{
		$manifest = $manifest ?: self::ReadManifest($tarPath);
		$tar = new BackupTar($tarPath, 'r');
		foreach($manifest['files'] as $file)
		{
			$hash = $tar->Hash($file['path']);
			if($hash === null) { throw new BackupException("The backup is missing ".$file['path']); }
			if($hash !== $file['sha256']) { throw new BackupException($file['path']." is damaged (its SHA-256 doesn't match)"); }
		}
		$tar->Close();
		return $manifest;
	}

	/** The secrets the backup carries (two_factor_key). */
	public static function ReadSecrets($tarPath)
	{
		$tar = new BackupTar($tarPath, 'r');
		$json = $tar->Read('secrets.json', 1024 * 1024);
		$tar->Close();
		$secrets = json_decode((string)$json, true);
		return is_array($secrets) ? $secrets : array();
	}

	/**
	 * Replaces this database's site data with the backup's. Checks the whole backup first, so a damaged file stops it
	 * before anything is changed.
	 * @param array $appliedMigrations the migrations this database has had: it must have every one the backup's had
	 * @param string $rejectsFile where rows the database refused are written (JSON lines)
	 * @return array report: tables => [name => [expected, loaded, rejected]], missing_here, not_in_backup, rejected
	 */
	public function Restore($tarPath, array $appliedMigrations, $rejectsFile)
	{
		$manifest = self::ReadManifest($tarPath);
		$needed = isset($manifest['source']['migrations']) ? (array)$manifest['source']['migrations'] : array();
		$missing = array_values(array_diff($needed, $appliedMigrations));
		if($missing) { throw new BackupException("This database hasn't had the migrations ".implode(', ', $missing)." that the backup's had. Run migrate.php (or upgrade WifiDB) first."); }
		$this->Say("checking the backup");
		$this->Verify($tarPath, $manifest);

		$here = array();
		foreach($this->Tables() as $t) { $here[strtolower($t)] = $t; }
		$report = array('tables' => array(), 'missing_here' => array(), 'not_in_backup' => array(), 'rejected' => 0, 'zero_dates' => 0);
		$inBackup = array();
		foreach($manifest['tables'] as $t => $info) { $inBackup[strtolower($t)] = $t; }
		foreach($inBackup as $lower => $t) { if(!isset($here[$lower])) { $report['missing_here'][] = $t; } }
		foreach($here as $lower => $t) { if(!isset($inBackup[$lower])) { $report['not_in_backup'][] = $t; } }

		$rejects = fopen($rejectsFile, 'w');
		$identity = $this->IdentityTables();
		$this->Constraints(false, array_values($here));
		try
		{
			foreach($here as $table) { $this->Empty($table); }
			foreach(self::EMPTIED_TABLES as $table) { try { $this->Empty($table); } catch(Throwable $e) { /* not in this database */ } }

			$tar = new BackupTar($tarPath, 'r');
			foreach($manifest['tables'] as $source => $info)
			{
				if(!isset($here[strtolower($source)])) { continue; }
				$table = $here[strtolower($source)];
				$stats = $this->LoadTable($tar, $table, $info, isset($identity[strtolower($table)]) ? $identity[strtolower($table)] : null, $rejects, $report);
				$report['tables'][$table] = $stats;
			}
			$tar->Close();
			foreach($identity as $lower => $column)
			{
				if(!isset($here[$lower])) { continue; }
				# the next id comes after the highest restored one
				if($this->service === 'sqlsrv') { $this->conn->exec("DBCC CHECKIDENT ('dbo.".str_replace("'", "''", $here[$lower])."', RESEED) WITH NO_INFOMSGS"); }
				else
				{
					$prep = $this->conn->prepare("SELECT setval(pg_get_serial_sequence(?, ?), COALESCE((SELECT MAX(".$this->Q($column).") FROM ".$this->Q($here[$lower])."), 0) + 1, false)");
					$prep->execute(array($this->Q($here[$lower]), $column));
				}
			}
		}
		finally
		{
			$this->Constraints(true, array_values($here));
			fclose($rejects);
		}
		if($report['rejected'] === 0) { @unlink($rejectsFile); }
		return $report;
	}

	private function Empty($table)
	{
		if($this->service === 'sqlite') { $this->conn->exec("DELETE FROM ".$this->Q($table)); return; }
		try { $this->conn->exec("TRUNCATE TABLE ".$this->Q($table)); }
		catch(Throwable $e) { $this->conn->exec("DELETE FROM ".$this->Q($table)); }   # SQL Server won't truncate a table a foreign key points at
	}

	private function Constraints($on, array $tables)
	{
		if($this->service === 'mysql') { $this->conn->exec("SET FOREIGN_KEY_CHECKS = ".($on ? 1 : 0)); }
		elseif($this->service === 'sqlite') { $this->conn->exec("PRAGMA foreign_keys = ".($on ? 'ON' : 'OFF')); }
		elseif($this->service === 'pgsql')
		{
			# switching them off needs a superuser, so they're taken off and put back (NOT VALID: the rows aren't checked
			# again, as SQL Server's WITH NOCHECK and MariaDB's FOREIGN_KEY_CHECKS don't either)
			if(!$on)
			{
				$this->foreignKeys = $this->conn->query("SELECT conrelid::regclass::text, conname, pg_get_constraintdef(oid) FROM pg_constraint WHERE contype = 'f' AND connamespace = current_schema()::regnamespace")->fetchAll(PDO::FETCH_NUM);
				foreach($this->foreignKeys as $fk) { $this->conn->exec("ALTER TABLE ".$fk[0]." DROP CONSTRAINT ".$this->Q($fk[1])); }
			}
			else
			{
				foreach($this->foreignKeys as $fk) { $this->conn->exec("ALTER TABLE ".$fk[0]." ADD CONSTRAINT ".$this->Q($fk[1])." ".$fk[2]." NOT VALID"); }
				$this->foreignKeys = array();
			}
		}
		else
		{
			foreach($tables as $t)
			{
				$this->conn->exec("ALTER TABLE ".$this->Q($t).($on ? " WITH NOCHECK CHECK" : " NOCHECK")." CONSTRAINT ALL");
			}
		}
	}

	private function LoadTable(BackupTar $tar, $table, array $info, $identityColumn, $rejects, array &$report)
	{
		$target = array();
		foreach($this->Columns($table) as $c) { $target[strtolower($c[0])] = $c; }
		# the backup's columns that this table has, by position in the backup's rows
		$map = array();
		foreach($info['columns'] as $i => $c)
		{
			if(isset($target[strtolower($c[0])]) && $target[strtolower($c[0])][1] !== 'skip') { $map[$i] = array($c, $target[strtolower($c[0])]); }
		}
		$stats = array('expected' => (int)$info['rows'], 'loaded' => 0, 'rejected' => 0);
		if(!$map || !$info['rows']) { return $stats; }
		$this->Say("restoring $table (".number_format($info['rows'])." rows)");

		$names = array();
		$hasIdentity = false;
		foreach($map as $m)
		{
			$names[] = $this->Q($m[1][0]);
			if($this->service === 'sqlsrv' && $identityColumn !== null && strtolower($m[1][0]) === strtolower($identityColumn)) { $hasIdentity = true; }   # else SQL Server numbers the rows itself
		}
		$perRow = count($map);
		$batch = $this->service === 'sqlsrv' ? max(1, min(1000, intdiv(2000, $perRow))) : max(1, min(500, intdiv(30000, $perRow)));
		$head = "INSERT INTO ".$this->Q($table)." (".implode(', ', $names).") VALUES ";
		$one = '('.implode(', ', array_fill(0, $perRow, '?')).')';
		if($hasIdentity) { $this->conn->exec("SET IDENTITY_INSERT ".$this->Q($table)." ON"); }
		try
		{
			$rows = array();
			foreach($info['parts'] as $path)
			{
				$tmp = tempnam($this->tmpDir !== '' ? $this->tmpDir : sys_get_temp_dir(), 'wdbr');
				$tar->Extract($path, $tmp);
				$gz = gzopen($tmp, 'rb');
				while(($line = gzgets($gz)) !== false)
				{
					if(trim($line) === '') { continue; }
					$rows[] = json_decode($line, true);
					if(count($rows) >= $batch) { $this->InsertRows($table, $head, $one, $map, $rows, $stats, $rejects, $report); $rows = array(); }
				}
				gzclose($gz);
				unlink($tmp);
			}
			if($rows) { $this->InsertRows($table, $head, $one, $map, $rows, $stats, $rejects, $report); }
		}
		finally
		{
			if($hasIdentity) { $this->conn->exec("SET IDENTITY_INSERT ".$this->Q($table)." OFF"); }
		}
		return $stats;
	}

	/** One multi-row INSERT; if the database refuses it, row by row, setting aside the rows it refuses. */
	private function InsertRows($table, $head, $one, array $map, array $rows, array &$stats, $rejects, array &$report)
	{
		try
		{
			$this->conn->beginTransaction();
			$prep = $this->conn->prepare($head.implode(', ', array_fill(0, count($rows), $one)));
			$n = 1;
			$zero = 0;
			$this->held = array();
			foreach($rows as $row) { $n = $this->Bind($prep, $n, $map, $row, $zero); }
			$prep->execute();
			$this->conn->commit();
			$stats['loaded'] += count($rows);
			$report['zero_dates'] += $zero;
			return;
		}
		catch(Throwable $e)
		{
			if($this->conn->inTransaction()) { $this->conn->rollBack(); }
			if(count($rows) === 1) { $this->Reject($table, $rows[0], $e, $stats, $rejects, $report); return; }
		}
		$prep = $this->conn->prepare($head.$one);
		foreach($rows as $row)
		{
			try
			{
				$zero = 0;
				$this->held = array();
				$this->Bind($prep, 1, $map, $row, $zero);
				$prep->execute();
				$stats['loaded']++;
				$report['zero_dates'] += $zero;
			}
			catch(Throwable $e)
			{
				$this->Reject($table, $row, $e, $stats, $rejects, $report);
				# a statement whose run failed can't be trusted with the next row (PHP 8.5's SQLite driver refuses to run it
				# again), so the rest get a fresh one
				$prep = $this->conn->prepare($head.$one);
			}
		}
	}

	private function Reject($table, $row, Throwable $e, array &$stats, $rejects, array &$report)
	{
		$stats['rejected']++;
		$report['rejected']++;
		fwrite($rejects, json_encode(array('table' => $table, 'error' => $e->getMessage(), 'row' => $row), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE)."\n");
	}

	/** Binds one backup row's values, made right for this database's columns. @return int the next parameter number */
	private function Bind(PDOStatement $prep, $n, array $map, array $row, &$zero)
	{
		foreach($map as $i => $m)
		{
			list($from, $to) = $m;
			$v = array_key_exists($i, $row) ? $row[$i] : null;
			if(is_array($v)) { $v = base64_decode((string)@$v['x']); }
			elseif($v !== null && $from[1] === 'binary') { $v = base64_decode((string)$v); }
			if($v !== null && ($to[1] === 'datetime' || $to[1] === 'date') && preg_match('/^0000-00-00/', (string)$v))
			{
				# MariaDB's zero date: SQL Server has none
				$zero++;
				$v = $to[2] ? null : ($to[1] === 'date' ? '1900-01-01' : '1900-01-01 00:00:00');
			}
			if($to[1] === 'binary')
			{
				if($this->service === 'sqlsrv')
				{
					# only bindParam takes the encoding, and it binds a variable: keep it until the statement has run
					$this->held[] = $v;
					$prep->bindParam($n++, $this->held[array_key_last($this->held)], PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
				}
				else { $prep->bindValue($n++, $v, $v === null ? PDO::PARAM_NULL : PDO::PARAM_LOB); }
				continue;
			}
			if($v === null) { $prep->bindValue($n++, null, PDO::PARAM_NULL); }
			elseif($to[1] === 'bool') { $prep->bindValue($n++, (int)(bool)$v, PDO::PARAM_INT); }
			elseif(is_bool($v)) { $prep->bindValue($n++, (int)$v, PDO::PARAM_INT); }
			else { $prep->bindValue($n++, is_float($v) || is_int($v) ? (string)$v : $v, PDO::PARAM_STR); }
		}
		return $n;
	}
}

/**
 * A plain ustar file, written one member at a time and read by seeking from header to header, so neither needs memory
 * or disk space for more than one member. Members over 8 GB aren't needed (big tables are split into parts).
 */
class BackupTar
{
	private $fh;
	private $mode;
	private $index = null;

	public function __construct($path, $mode)
	{
		$this->mode = $mode;
		$this->fh = @fopen($path, $mode === 'w' ? 'wb' : 'rb');
		if(!$this->fh) { throw new BackupException("Can't open $path"); }
	}

	public function Add($file, $name)
	{
		$size = filesize($file);
		fwrite($this->fh, self::Header($name, $size, time()));
		$in = fopen($file, 'rb');
		stream_copy_to_stream($in, $this->fh);
		fclose($in);
		$pad = (512 - $size % 512) % 512;
		if($pad) { fwrite($this->fh, str_repeat("\0", $pad)); }
	}

	public function Close()
	{
		if($this->mode === 'w') { fwrite($this->fh, str_repeat("\0", 1024)); }
		fclose($this->fh);
	}

	public static function Header($name, $size, $mtime)
	{
		if(strlen($name) > 99) { throw new BackupException("Name too long for the backup: $name"); }
		if($size > 077777777777) { throw new BackupException("$name is too big for one file in the backup"); }
		$h = str_pad($name, 100, "\0")
			.sprintf("%07o\0", 0600).sprintf("%07o\0", 0).sprintf("%07o\0", 0)
			.sprintf("%011o\0", $size).sprintf("%011o\0", $mtime)
			.'        '.'0'.str_repeat("\0", 100)
			."ustar\0"."00".str_pad('', 32, "\0").str_pad('', 32, "\0")
			.sprintf("%07o\0", 0).sprintf("%07o\0", 0).str_repeat("\0", 155);
		$h = str_pad($h, 512, "\0");
		$sum = 0;
		for($i = 0; $i < 512; $i++) { $sum += ord($h[$i]); }
		return substr_replace($h, sprintf("%06o\0 ", $sum), 148, 8);
	}

	/** name => [offset of the data, size] */
	public function Members()
	{
		if($this->index !== null) { return $this->index; }
		$this->index = array();
		$pos = 0;
		while(true)
		{
			fseek($this->fh, $pos);
			$h = fread($this->fh, 512);
			if(strlen($h) < 512 || trim($h, "\0") === '') { break; }
			$name = rtrim(substr($h, 0, 100), "\0");
			$prefix = rtrim(substr($h, 345, 155), "\0");
			if($prefix !== '') { $name = $prefix.'/'.$name; }
			$size = octdec(trim(substr($h, 124, 12), "\0 "));
			$this->index[$name] = array($pos + 512, $size);
			$pos += 512 + (int)(ceil($size / 512) * 512);
		}
		return $this->index;
	}

	/** A small member's contents, or null. */
	public function Read($name, $max)
	{
		$m = $this->Members();
		if(!isset($m[$name]) || $m[$name][1] > $max) { return null; }
		fseek($this->fh, $m[$name][0]);
		return $m[$name][1] ? fread($this->fh, $m[$name][1]) : '';
	}

	/** Copies a member to a file. */
	public function Extract($name, $dest)
	{
		$m = $this->Members();
		if(!isset($m[$name])) { throw new BackupException("The backup is missing $name"); }
		fseek($this->fh, $m[$name][0]);
		$out = fopen($dest, 'wb');
		stream_copy_to_stream($this->fh, $out, $m[$name][1]);
		fclose($out);
	}

	/** A member's SHA-256, or null if it isn't there. */
	public function Hash($name)
	{
		$m = $this->Members();
		if(!isset($m[$name])) { return null; }
		fseek($this->fh, $m[$name][0]);
		$ctx = hash_init('sha256');
		$left = $m[$name][1];
		while($left > 0)
		{
			$chunk = fread($this->fh, (int)min(8 * 1024 * 1024, $left));
			if($chunk === '' || $chunk === false) { return ''; }
			hash_update($ctx, $chunk);
			$left -= strlen($chunk);
		}
		return hash_final($ctx);
	}
}
