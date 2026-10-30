<?php
/*
migrate.inc.php, applies the numbered SQL files in sql/migrations/<mysql|sqlsrv>/ to a database, once each and in order,
and remembers which it has applied in the schema_migrations table. A fresh database (blank_db.sql or blank_db.sqlsrv)
and an old one both end up at the same place, because blank_db is the base schema and everything since is a migration.

Several nodes may run it at once against one database: a migration is claimed by inserting its row first, and only the
node whose insert succeeded runs it; the others wait for it to finish.

A file holds statements separated by ';' (mysql) or by lines that say GO (sqlsrv). Name them 0002_what_it_does.sql.

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class MigrationException extends RuntimeException {}

class Migrator
{
	private $conn;
	private $dialect;
	private $dir;
	private $sleep;
	public $waitSeconds = 600;
	/** @var callable|null called with a line of progress text */
	public $log = null;

	/**
	 * @param PDO $conn
	 * @param string $dialect mysql, sqlsrv (or sqlite, for tests): picks the folder and how statements are split
	 * @param string $dir the migrations folder, holding one folder per dialect
	 * @param callable|null $sleep what to call to wait a second (for tests)
	 */
	public function __construct($conn, $dialect, $dir, $sleep = null)
	{
		$this->conn = $conn;
		$this->dialect = $dialect;
		$this->dir = rtrim($dir, '/\\');
		$this->sleep = $sleep ? $sleep : 'sleep';
	}

	private function Say($text) { if($this->log) { call_user_func($this->log, $text); } }

	/** @return string[] migration ids (file names without .sql), in order */
	public function Available()
	{
		$files = glob($this->dir . '/' . $this->dialect . '/*.sql');
		$ids = array();
		foreach(($files ? $files : array()) as $file) { $ids[] = basename($file, '.sql'); }
		sort($ids, SORT_STRING);
		return $ids;
	}

	private function EnsureTable()
	{
		if($this->dialect === 'sqlsrv')
		{
			$sql = "IF OBJECT_ID(N'dbo.schema_migrations', N'U') IS NULL CREATE TABLE dbo.schema_migrations (id NVARCHAR(100) NOT NULL PRIMARY KEY, started DATETIME NOT NULL, applied DATETIME NULL)";
		}
		else
		{
			$sql = "CREATE TABLE IF NOT EXISTS schema_migrations (id VARCHAR(100) NOT NULL PRIMARY KEY, started DATETIME NOT NULL, applied DATETIME NULL)";
		}
		$this->conn->exec($sql);
	}

	/** @return string[] the ids already applied */
	public function Applied()
	{
		$this->EnsureTable();
		return $this->conn->query("SELECT id FROM schema_migrations WHERE applied IS NOT NULL ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
	}

	/** @return string[] the ids still to apply */
	public function Pending()
	{
		return array_values(array_diff($this->Available(), $this->Applied()));
	}

	/**
	 * Applies everything pending.
	 * @return string[] the ids this call applied (not the ones another node applied while it waited)
	 * @throws MigrationException with the statement that failed
	 */
	public function Run()
	{
		$done = array();
		foreach($this->Pending() as $id)
		{
			if($this->Claim($id))
			{
				$this->Say("applying $id");
				$this->Apply($id);
				$done[] = $id;
			}
			else
			{
				$this->Say("$id is being applied by another node; waiting");
				$this->WaitFor($id);
			}
		}
		return $done;
	}

	private function Claim($id)
	{
		try
		{
			$this->conn->prepare("INSERT INTO schema_migrations (id, started) VALUES (?, ?)")->execute(array($id, gmdate('Y-m-d H:i:s')));
			return true;
		}
		catch(PDOException $e)
		{
			// the row is there: either applied, or another node is applying it
			return false;
		}
	}

	private function WaitFor($id)
	{
		for($i = 0; $i < $this->waitSeconds; $i++)
		{
			$prep = $this->conn->prepare("SELECT applied FROM schema_migrations WHERE id = ?");
			$prep->execute(array($id));
			$row = $prep->fetch(PDO::FETCH_NUM);
			if($row && $row[0] !== null) { return; }
			if(!$row)
			{
				// the other node failed and let go of it: this node takes over
				if($this->Claim($id)) { $this->Say("applying $id"); $this->Apply($id); return; }
			}
			call_user_func($this->sleep, 1);
		}
		throw new MigrationException("gave up waiting for $id, which another node started and has not finished");
	}

	private function Apply($id)
	{
		$text = file_get_contents($this->dir . '/' . $this->dialect . '/' . $id . '.sql');
		$statement = '';
		try
		{
			foreach(self::Split($text, $this->dialect) as $statement)
			{
				$this->conn->exec($statement);
			}
		}
		catch(PDOException $e)
		{
			// let go of the claim, so a fixed migration can be run again
			$this->conn->prepare("DELETE FROM schema_migrations WHERE id = ? AND applied IS NULL")->execute(array($id));
			throw new MigrationException("$id failed: " . $e->getMessage() . "\nStatement:\n" . $statement, 0, $e);
		}
		$this->conn->prepare("UPDATE schema_migrations SET applied = ? WHERE id = ?")->execute(array(gmdate('Y-m-d H:i:s'), $id));
	}

	/** @return string[] the statements in a file */
	public static function Split($text, $dialect)
	{
		$text = str_replace("\r\n", "\n", $text);
		if($dialect === 'sqlsrv')
		{
			$batches = preg_split('/^[ \t]*GO[ \t]*$/mi', $text);
			return array_values(array_filter(array_map('trim', $batches), 'strlen'));
		}
		// ';' ends a statement, except inside quotes and comments
		$statements = array();
		$current = '';
		$quote = null;
		$length = strlen($text);
		for($i = 0; $i < $length; $i++)
		{
			$c = $text[$i];
			if($quote !== null)
			{
				$current .= $c;
				if($c === '\\' && $quote !== '`' && $i + 1 < $length) { $current .= $text[++$i]; }
				elseif($c === $quote) { $quote = null; }
				continue;
			}
			if($c === "'" || $c === '"' || $c === '`') { $quote = $c; $current .= $c; continue; }
			if($c === '-' && substr($text, $i, 2) === '--')
			{
				$end = strpos($text, "\n", $i);
				$i = $end === false ? $length : $end;
				continue;
			}
			if($c === '/' && substr($text, $i, 2) === '/*')
			{
				$end = strpos($text, '*/', $i + 2);
				$i = $end === false ? $length : $end + 1;
				continue;
			}
			if($c === ';')
			{
				if(trim($current) !== '') { $statements[] = trim($current); }
				$current = '';
				continue;
			}
			$current .= $c;
		}
		if(trim($current) !== '') { $statements[] = trim($current); }
		return $statements;
	}
}
