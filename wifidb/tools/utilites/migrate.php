<?php
/*
migrate.php, applies the database migrations (sql/migrations/<mysql|sqlsrv>/) that this database hasn't had yet.
Run it after loading blank_db.sql or blank_db.sqlsrv into a new database, and after upgrading WiFiDB: the Proxmox
template does both for you on every first boot.

  php migrate.php            apply what's pending
  php migrate.php --status   list what's applied and what's pending
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
define("WIFIDB_SKIP_MIGRATION_CHECK", true);   # it's what applies them
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\r\n");}
require $daemon_config['wifidb_install'].'/lib/init.inc.php';
require_once $daemon_config['wifidb_install'].'/lib/migrate.inc.php';

$dir = dirname(__FILE__).'/../../sql/migrations';
if(!is_dir($dir.'/'.$dbcore->sql->service)) { fwrite(STDERR, "No migrations for '".$dbcore->sql->service."' in $dir\r\n"); exit(1); }
$migrator = new Migrator($dbcore->sql->conn, $dbcore->sql->service, $dir);
$migrator->log = function($line) { echo $line."\r\n"; };

if(in_array('--status', $argv))
{
	echo "applied: ".(implode(', ', $migrator->Applied()) ?: 'none')."\r\n";
	echo "pending: ".(implode(', ', $migrator->Pending()) ?: 'none')."\r\n";
	exit(0);
}
try
{
	$done = $migrator->Run();
	echo $done ? "applied ".implode(', ', $done)."\r\n" : "nothing to apply\r\n";
}
catch(MigrationException $e)
{
	fwrite(STDERR, $e->getMessage()."\r\n");
	exit(1);
}
