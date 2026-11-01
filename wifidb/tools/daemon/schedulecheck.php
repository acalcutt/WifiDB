<?php
/*
schedulecheck.php: prints how many scheduled runs of a daemon are due on this node.

Used by the tools/cron scripts (import_files, export_kml, admind) in place of sqlcmd, so they work with
MariaDB as well as SQL Server and need no database credentials of their own: this connects the same way
the daemons do, through daemon.config.inc.php and the web site's config.

  php schedulecheck.php Import
  php schedulecheck.php Export --ignore-nextrun

Prints 0 when the node's daemons are switched off (settings.daemon_state), otherwise the number of enabled schedule
rows for the daemon that are not running and whose nextrun has passed (--ignore-nextrun skips that last test).
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){fwrite(STDERR, "daemon.config.inc.php is missing\n"); echo "0\n"; exit(1);}
if($daemon_config['wifidb_install'] == ""){fwrite(STDERR, "wifidb_install is not set in daemon.config.inc.php\n"); echo "0\n"; exit(1);}

$daemon = isset($argv[1]) ? $argv[1] : '';
$ignore_nextrun = in_array('--ignore-nextrun', $argv, true);
if($daemon === '' || $daemon[0] === '-'){fwrite(STDERR, "usage: php schedulecheck.php <Import|Export|Admin> [--ignore-nextrun]\n"); echo "0\n"; exit(1);}

// Keep the library's own output off stdout: the cron scripts read the number from it.
ob_start();
require $daemon_config['wifidb_install']."/lib/init.inc.php";
ob_end_clean();

// A restore is replacing the site's data (lib/backups.inc.php): nothing starts until it's done.
require_once $daemon_config['wifidb_install']."/lib/backups.inc.php";
if(is_file(BackupService::PauseFile($config)))
{
    echo "0\n";
    exit(0);
}

$node = $daemon_config['wifidb_nodename'];
$conn = $dbcore->sql->conn;

$prep = $conn->prepare("SELECT COUNT(*) FROM settings WHERE node_name = ? AND daemon_state = 1");
$prep->execute(array($node));
if((int)$prep->fetchColumn() < 1)
{
    echo "0\n";
    exit(0);
}

$sql = "SELECT COUNT(*) FROM schedule WHERE nodename = ? AND daemon = ? AND status <> 'Running' AND enabled = 1";
$params = array($node, $daemon);
if(!$ignore_nextrun)
{
    $sql .= " AND nextrun <= ?";
    $params[] = gmdate("Y-m-d H:i:s");
}
$prep = $conn->prepare($sql);
$prep->execute($params);
echo (int)$prep->fetchColumn()."\n";
