<?php
/*
lock_run.php: runs a command while holding a database lock (lib/nodelock.inc.php), so when several nodes share the
database only one of them runs it at a time. The cron scripts use it for the work one node does for all: tiles,
GeoJSON, the manufacturer list, the nightly backup.
  php lock_run.php NAME COMMAND [ARGS...]
If another node (or another run on this one) holds NAME, or a restore is under way, it says so and exits 0 without
running anything; otherwise it exits with the command's own code. The command's output is its own (redirect this).
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){fwrite(STDERR, "daemon.config.inc.php is missing\n"); exit(1);}
if($daemon_config['wifidb_install'] == ""){fwrite(STDERR, "wifidb_install is not set in daemon.config.inc.php\n"); exit(1);}
if(count($argv) < 3){fwrite(STDERR, "usage: php lock_run.php NAME COMMAND [ARGS...]\n"); exit(1);}
$name = $argv[1];
$command = array_slice($argv, 2);
// Keep the library's own output out of the log this is redirected to
ob_start();
require $daemon_config['wifidb_install']."/lib/init.inc.php";
ob_end_clean();
require_once $daemon_config['wifidb_install']."/lib/nodelock.inc.php";
require_once $daemon_config['wifidb_install']."/lib/backups.inc.php";
// A restore is replacing the site's data: nothing starts until it's done (as schedulecheck.php)
if(is_file(BackupService::PauseFile($config)))
{
	echo "A restore is under way: $name waits for the next run.\n";
	exit(0);
}
$conn = $dbcore->sql->conn;
if(!NodeLock::Take($conn, $name))
{
	echo "$name is already running (on another node, or here). Skipping.\n";
	exit(0);
}
echo "[".gmdate("Y-m-d H:i:s")."] $name on ".$daemon_config['wifidb_nodename'].": ".implode(' ', $command)."\n";
$proc = proc_open($command, array(0 => STDIN, 1 => STDOUT, 2 => STDERR), $pipes);
if(!is_resource($proc))
{
	NodeLock::Release($conn, $name);
	fwrite(STDERR, "Could not start ".$command[0]."\n");
	exit(1);
}
// The lock lasts as long as the connection, so it's kept in use while the command runs (a long tile build would
// otherwise outlast the server's idle timeout, and the lock would go with the connection)
$waited = 0;
while(($status = proc_get_status($proc)) && $status['running'])
{
	sleep(5);
	if(($waited += 5) % 60 === 0)
	{
		try { $conn->query("SELECT 1")->fetchAll(); }
		catch(Throwable $e) { fwrite(STDERR, "Lost the database connection, and with it the lock on $name: ".$e->getMessage()."\n"); }
	}
}
proc_close($proc);
NodeLock::Release($conn, $name);
exit($status['exitcode']);
