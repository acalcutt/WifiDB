<?php
/*
traffic.php, adds this node's page views and events to the traffic counts in the database (lib/traffic.inc.php,
admin/traffic.php), and once an hour forgets the visitor keys of periods that are over. tools/cron/traffic runs it each
minute on every node; each adds its own, so no lock.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\r\n");}
$web = rtrim($daemon_config['wifidb_install'], '/');
require $web.'/lib/helpers.inc.php';
require $web.'/lib/config.inc.php';
require_once $web.'/lib/traffic.inc.php';

try
{
	$conn = wifidb_pdo_from_config($config, 15);
	$traffic = Traffic::ForConfig($config);
	if($traffic === null) { throw new RuntimeException("wifidb_tools isn't set in config.inc.php"); }
	$added = $traffic->Flush($conn);
	if($added && in_array('-v', $argv, true)) { echo "added $added\r\n"; }
	if((int)gmdate('i') === 7) { Traffic::Prune($conn); }
}
catch(Throwable $e)
{
	# the spool stays for the next run
	fwrite(STDERR, '['.gmdate('Y-m-d H:i:s').'] '.$e->getMessage()."\r\n");
	exit(1);
}
