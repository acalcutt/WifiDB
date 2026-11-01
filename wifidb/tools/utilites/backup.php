<?php
/*
backup.php, whole-site database backups from the command line: the same as admin/backups.php (lib/backups.inc.php).
A backup made on SQL Server restores on MariaDB and the other way round. See docs/backups.md.

  php backup.php export [--daily]     make a backup now (--daily: one of the daily ones, of which the newest 7 are kept)
  php backup.php list                 the backups in backup_dir
  php backup.php verify NAME          check every file in it against its SHA-256
  php backup.php restore NAME --yes   replace this site's data with it (everyone is signed out)
  php backup.php delete NAME
  php backup.php import URL --key KEY [--name NAME] [--fresh] [--restore --yes] [--insecure]
                                      copy a backup from another server (its newest, or NAME; --fresh: have it make one first)
  php backup.php key [--revoke]       make this server's transfer key (shown once), or revoke it
  php backup.php daily                queue the daily backup (tools/cron/backups daily, from cron)
  php backup.php run-queue            run what the admin page asked for (tools/cron/backups does this each minute)

NAME is a backup in backup_dir, wifidb-backup-YYYYMMDD-HHMMSS. A .tar copied in from elsewhere (downloaded from another
server's admin page, say) shows up in the list and restores the same way.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\r\n");}
require $daemon_config['wifidb_install'].'/lib/init.inc.php';
require_once $daemon_config['wifidb_install'].'/lib/backups.inc.php';

$args = array_slice($argv, 1);
$command = $args[0] ?? '';
$name = $args[1] ?? '';
$backups = new BackupService($config, $dbcore->sql->conn, $dbcore->sql->service, $dbcore->ver_str ?? '');
$backups->echo = true;
$fail = function ($message) { fwrite(STDERR, $message."\n"); exit(1); };

switch($command)
{
	case 'export':
		if(!$backups->Ready()) { $fail("The backups folder ".$backups->Dir()." can't be written."); }
		$ok = $backups->Run(array('action' => 'export', 'daily' => in_array('--daily', $args, true), 'requested_by' => 'command line'));
		exit($ok ? 0 : 1);

	case 'list':
		foreach($backups->Backups() as $b)
		{
			printf("%-32s %-8s %-10s %10s  %s%s\n", $b['name'], $b['state'] ?? '', $b['kind'] ?? '', BackupService::HumanSize($b['size'] ?? 0),
				($b['source']['site'] ?? '').' '.($b['source']['service'] ?? ''), !empty($b['error']) ? '  ('.$b['error'].')' : '');
		}
		exit(0);

	case 'verify':
		$b = $backups->Get($name);
		if(!$b || ($b['state'] ?? '') !== 'done') { $fail("No finished backup named '$name'."); }
		try { (new WifidbBackup($dbcore->sql->conn, $dbcore->sql->service))->Verify($backups->Path($name)); }
		catch(Throwable $e) { $fail($e->getMessage()); }
		echo "$name is complete: every file matches its SHA-256.\n";
		exit(0);

	case 'restore':
		if(!in_array('--yes', $args, true)) { $fail("This replaces all of this site's data with $name and signs everyone out. Run it again with --yes to go ahead."); }
		exit($backups->Run(array('action' => 'restore', 'name' => $name, 'requested_by' => 'command line')) ? 0 : 1);

	case 'delete':
		exit($backups->Run(array('action' => 'delete', 'name' => $name, 'requested_by' => 'command line')) ? 0 : 1);

	case 'import':
		$opt = function ($flag) use ($args) { $i = array_search($flag, $args, true); return ($i !== false && isset($args[$i + 1])) ? $args[$i + 1] : ''; };
		$restore = in_array('--restore', $args, true);
		if($restore && !in_array('--yes', $args, true)) { $fail("--restore replaces all of this site's data and signs everyone out. Add --yes to go ahead."); }
		exit($backups->Run(array('action' => 'import', 'url' => $name, 'key' => $opt('--key'), 'name' => $opt('--name'), 'fresh' => in_array('--fresh', $args, true),
			'restore' => $restore, 'insecure' => in_array('--insecure', $args, true), 'requested_by' => 'command line')) ? 0 : 1);

	case 'key':
		if(in_array('--revoke', $args, true)) { $backups->RevokeTransferKey(); echo "Revoked.\n"; exit(0); }
		echo $backups->CreateTransferKey('command line')."\n";
		fwrite(STDERR, "This key lets another server copy this server's backups for ".BackupService::TRANSFER_KEY_DAYS." days. It isn't shown again.\n");
		exit(0);

	case 'daily':
		require_once $daemon_config['wifidb_install'].'/lib/site_settings.inc.php';
		if(!(new SiteSettings($dbcore->sql->conn, $config))->Get('backup_daily')) { echo "Daily backups are off (Site Settings).
"; exit(0); }
		$backups->Queue('export', 'daily schedule', array('daily' => true));
		exit(0);

	case 'run-queue':
		$backups->echo = false;
		$backups->RunQueue();
		exit(0);

	default:
		fwrite(STDERR, "Usage: php backup.php export | list | verify NAME | restore NAME --yes | delete NAME | import URL --key KEY [...] | key [--revoke] | daily | run-queue\n");
		exit(2);
}
