<?php
/*
test-backup-crossdb.php, run by test-backup-crossdb.sh: a backup made on MariaDB restores on SQL Server, a backup of that
restores on PostgreSQL, and a backup of that restores on MariaDB again, with every row of every table the same at each
stop as where it started.

Every column of every table gets three rows of made-up values that test the awkward cases: non-ASCII text (including
4-byte characters), binary, dates, decimals and ids that line up across the foreign keys.
  php test-backup-crossdb.php LIB_DIR MIGRATIONS_DIR WORK_DIR
Environment: MARIA_HOST, MARIA2_HOST, MSSQL_HOST, MSSQL_PW, PG_HOST.
*/
list(, $lib, $migrations, $work) = $argv;
require "$lib/backup.inc.php";
require "$lib/migrate.inc.php";

$failed = 0;
function check($what, $ok, $detail = '') { global $failed; echo ($ok ? "  ok   " : "  FAIL ").$what.($ok || $detail === '' ? '' : "\n       ".$detail)."\n"; if(!$ok) { $failed = 1; } }

function connect($kind, $host)
{
	$opts = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION);
	if($kind === 'mysql') { return new PDO("mysql:host=$host;dbname=wifi;charset=utf8mb4", 'root', 'rootpw', $opts); }
	if($kind === 'pgsql') { return new PDO("pgsql:host=$host;dbname=wifi", 'wifidb', 'testpw', $opts); }
	return new PDO("sqlsrv:Server=$host;Database=prod_wifi;TrustServerCertificate=true", 'sa', getenv('MSSQL_PW'), $opts);
}

/** Three rows in every table, every column filled. */
function seed(PDO $db, array $asciiOnly)
{
	$db->exec("SET FOREIGN_KEY_CHECKS = 0");
	$cols = $db->query("SELECT TABLE_NAME, COLUMN_NAME, DATA_TYPE, COLUMN_TYPE, CHARACTER_MAXIMUM_LENGTH, NUMERIC_SCALE, EXTRA, CHARACTER_SET_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC);
	$tables = array();
	foreach($cols as $c) { $tables[$c['TABLE_NAME']][] = $c; }
	$seeded = 0;
	foreach($tables as $table => $columns)
	{
		if(in_array(strtolower($table), WifidbBackup::LOCAL_TABLES, true)) { continue; }
		for($n = 1; $n <= 3; $n++)
		{
			$names = array(); $values = array();
			foreach($columns as $c)
			{
				if(stripos($c['EXTRA'], 'GENERATED') !== false || stripos($c['EXTRA'], 'VIRTUAL') !== false) { continue; }
				$names[] = '`'.$c['COLUMN_NAME'].'`';
				$c['ascii'] = isset($asciiOnly[strtolower($table.'.'.$c['COLUMN_NAME'])]) || ($c['CHARACTER_SET_NAME'] && $c['CHARACTER_SET_NAME'] !== 'utf8mb4');
				$values[] = value($c, $n);
			}
			$sql = "INSERT INTO `$table` (".implode(', ', $names).") VALUES (".implode(', ', array_fill(0, count($names), '?')).")";
			try { $db->prepare($sql)->execute($values); }
			catch(Throwable $e) { echo "       (couldn't seed $table row $n: ".$e->getMessage().")\n"; continue 2; }
		}
		$seeded++;
	}
	$db->exec("SET FOREIGN_KEY_CHECKS = 1");
	return $seeded;
}

function value(array $c, $n)
{
	$type = strtolower($c['DATA_TYPE']);
	$max = (int)$c['CHARACTER_MAXIMUM_LENGTH'];
	switch($type)
	{
		case 'tinyint':
			if(strtolower($c['COLUMN_TYPE']) === 'tinyint(1)') { return $n % 2; }   # a yes/no, which is a bit on SQL Server
		case 'smallint': case 'mediumint': case 'int': case 'bigint': case 'year':
			return $type === 'year' ? 2020 + $n : $n;
		case 'bit': return 1;
		case 'decimal': case 'numeric': return (int)$c['NUMERIC_SCALE'] > 0 ? $n.'.5' : $n;
		case 'float': case 'double': case 'real': return $n + 0.5;
		case 'date': return "2026-0$n-0$n";
		case 'time': return "0$n:00:00";
		case 'datetime': case 'timestamp': return "2026-0$n-0$n 1$n:2$n:3$n";
		case 'blob': case 'tinyblob': case 'mediumblob': case 'longblob': case 'binary': case 'varbinary':
			return "\x00\xff".chr($n)."bin\x80".$n;
		case 'enum': case 'set':
			preg_match("/'([^']*)'/", $c['COLUMN_TYPE'], $m);
			return $m[1];
	}
	$text = $c['ascii'] ? "$n-plain-o'neil" : "$n-ü-📡-o'neil";
	if($max > 0 && mb_strlen($text) > $max) { $text = mb_substr((string)$n.str_repeat('x', 64), 0, $max); }
	return $text;
}

/** Every row of every table, written the way a backup writes it and made comparable across the two databases. */
function snapshot(PDO $db, $service)
{
	$b = new WifidbBackup($db, $service);
	$out = array();
	foreach($b->Tables() as $table)
	{
		$columns = $b->Columns($table);
		$names = implode(', ', array_map(array($b, 'Q'), array_column($columns, 0)));
		$rows = array();
		foreach($db->query("SELECT $names FROM ".$b->Q($table))->fetchAll(PDO::FETCH_NUM) as $row)
		{
			$encoded = WifidbBackup::EncodeRow($row, $columns);
			$line = array();
			foreach($encoded as $i => $v)
			{
				if(is_string($v) || is_int($v) || is_float($v))
				{
					$v = rtrim((string)$v, ' ');                                           # CHAR(n) pads on SQL Server
					if(preg_match('/^-?\d+\.\d+$/', $v)) { $v = rtrim(rtrim($v, '0'), '.'); }   # 1.50 and 1.5
				}
				$line[strtolower($columns[$i][0])] = $v;
			}
			ksort($line);
			$rows[] = json_encode($line, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
		}
		sort($rows);
		$out[strtolower($table)] = $rows;
	}
	return $out;
}

function compare($what, array $a, array $b, array $skipTables)
{
	$diff = array();
	foreach($a as $table => $rows)
	{
		if(in_array($table, $skipTables, true)) { continue; }
		if(!isset($b[$table])) { $diff[] = "$table is missing"; continue; }
		# only the columns both have
		$common = null;
		$norm = function ($rows) use (&$common) {
			return array_map(function ($r) use (&$common) { $r = json_decode($r, true); return json_encode(array_intersect_key($r, $common)); }, $rows);
		};
		$common = array_intersect_key(json_decode($rows[0] ?? '{}', true), json_decode($b[$table][0] ?? '{}', true));
		$x = $norm($rows); $y = $norm($b[$table]); sort($x); sort($y);
		if($x !== $y) { $diff[] = "$table: ".(count($x) !== count($y) ? count($x)." rows against ".count($y) : "first difference: ".json_encode(array_values(array_diff($x, $y))[0] ?? '').' / '.json_encode(array_values(array_diff($y, $x))[0] ?? '')); }
	}
	check($what, !$diff, implode("\n       ", array_slice($diff, 0, 8)));
}

function migrate(PDO $db, $service, $migrations)
{
	$m = new Migrator($db, $service, $migrations);
	$m->Run();
	return $m->Applied();
}

$maria = connect('mysql', getenv('MARIA_HOST'));
$maria2 = connect('mysql', getenv('MARIA2_HOST'));
$mssql = connect('sqlsrv', getenv('MSSQL_HOST'));
$pg = connect('pgsql', getenv('PG_HOST'));
$appliedMaria = migrate($maria, 'mysql', $migrations);
$appliedMaria2 = migrate($maria2, 'mysql', $migrations);
$appliedMssql = migrate($mssql, 'sqlsrv', $migrations);
$appliedPg = migrate($pg, 'pgsql', $migrations);
check("the migrations ran on all three kinds of database", $appliedMaria === $appliedMssql && $appliedMaria === $appliedPg && count($appliedMaria) >= 4,
	implode(',', $appliedMaria).' / '.implode(',', $appliedMssql).' / '.implode(',', $appliedPg));
$ascii = array();
foreach($mssql->query("SELECT LOWER(TABLE_NAME + '.' + COLUMN_NAME) FROM INFORMATION_SCHEMA.COLUMNS WHERE DATA_TYPE IN ('varchar', 'char', 'text')")->fetchAll(PDO::FETCH_COLUMN) as $col) { $ascii[$col] = true; }
$seeded = seed($maria, $ascii);
check("every column of $seeded tables on MariaDB was filled", $seeded >= 35);
$original = snapshot($maria, 'mysql');

/** Backs up $from and restores it on $to. @return array the restore's report */
function hop(PDO $from, $fromService, array $fromMigrations, PDO $to, $toService, array $toMigrations, $tar)
{
	global $work;
	$b = new WifidbBackup($from, $fromService);
	$b->rowsPerPart = 2;   // several parts per table
	$manifest = $b->Export($tar, array('site' => "https://$fromService.test/", 'migrations' => $fromMigrations), array('two_factor_key' => 'k'));
	$r = new WifidbBackup($to, $toService);
	$r->tmpDir = $work;
	$report = $r->Restore($tar, $toMigrations, "$tar.rejects");
	$report['manifest_rows'] = $manifest['rows'];
	$report['rejects'] = @file_get_contents("$tar.rejects") ?: '';
	return $report;
}

# MariaDB -> SQL Server
$report = hop($maria, 'mysql', $appliedMaria, $mssql, 'sqlsrv', $appliedMssql, "$work/wifidb-backup-20270101-000001.tar");
$total = array_sum(array_map('count', $original));
check("the MariaDB backup holds every table's rows", $report['manifest_rows'] === $total, $report['manifest_rows'].' rows, '.$total.' in the database');
check("it restores on SQL Server with nothing refused", $report['rejected'] === 0, $report['rejects']);
check("...the table only MariaDB has is reported", $report['missing_here'] === array('user_imports'), json_encode($report['missing_here']));
check("...and no table here is missing from the backup", $report['not_in_backup'] === array(), json_encode($report['not_in_backup']));
compare("...and every row on SQL Server matches MariaDB's", $original, snapshot($mssql, 'sqlsrv'), array('user_imports'));
$mssql->exec("INSERT INTO notifications (username, type, title, created) VALUES ('x', 'security', 't', '2026-01-01 00:00:00')");
check("SQL Server's next id comes after the restored ones", (int)$mssql->query("SELECT MAX(id) FROM notifications")->fetchColumn() === 4);
$mssql->exec("DELETE FROM notifications WHERE username = 'x'");

# SQL Server -> PostgreSQL
$report = hop($mssql, 'sqlsrv', $appliedMssql, $pg, 'pgsql', $appliedPg, "$work/wifidb-backup-20270101-000002.tar");
check("the SQL Server backup restores on PostgreSQL with nothing refused", $report['rejected'] === 0, $report['rejects']);
check("...with the same tables", $report['missing_here'] === array() && $report['not_in_backup'] === array(), json_encode(array($report['missing_here'], $report['not_in_backup'])));
compare("...and every row on PostgreSQL matches MariaDB's", $original, snapshot($pg, 'pgsql'), array('user_imports'));
$pg->exec("INSERT INTO notifications (username, type, title, created) VALUES ('x', 'security', 't', '2026-01-01 00:00:00')");
check("PostgreSQL's next id comes after the restored ones", (int)$pg->query("SELECT MAX(id) FROM notifications")->fetchColumn() === 4);
$pg->exec("DELETE FROM notifications WHERE username = 'x'");
check("PostgreSQL's foreign keys are back", (int)$pg->query("SELECT COUNT(*) FROM pg_constraint WHERE contype = 'f' AND connamespace = current_schema()::regnamespace")->fetchColumn() === 2);

# PostgreSQL -> MariaDB
$report = hop($pg, 'pgsql', $appliedPg, $maria2, 'mysql', $appliedMaria2, "$work/wifidb-backup-20270101-000003.tar");
check("the PostgreSQL backup restores on MariaDB with nothing refused", $report['rejected'] === 0, $report['rejects']);
compare("...and after the trip round all three every row is as it started", $original, snapshot($maria2, 'mysql'), array('user_imports'));
$maria2->exec("INSERT INTO notifications (username, type, title, created) VALUES ('x', 'security', 't', '2026-01-01 00:00:00')");
check("MariaDB's next id comes after the restored ones", (int)$maria2->query("SELECT MAX(id) FROM notifications")->fetchColumn() === 4);
check("the foreign keys are on again on MariaDB", (int)$maria2->query("SELECT @@FOREIGN_KEY_CHECKS")->fetchColumn() === 1);
check("the foreign keys are on again on SQL Server", (int)$mssql->query("SELECT COUNT(*) FROM sys.foreign_keys WHERE is_disabled = 1")->fetchColumn() === 0);
exit($failed);
