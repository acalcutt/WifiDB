<?php
/*
server_status.php: this node's report for admin/servers.php, sent each minute by cron (tools/cron/server_status): its
version, load, memory, the disks WifiDB uses and its services (lib/servers.inc.php). Prints nothing unless it fails.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){fwrite(STDERR, "daemon.config.inc.php is missing\n"); exit(1);}
if($daemon_config['wifidb_install'] == ""){fwrite(STDERR, "wifidb_install is not set in daemon.config.inc.php\n"); exit(1);}
// Keep the library's own output out of cron's mail
ob_start();
require $daemon_config['wifidb_install']."/lib/init.inc.php";
ob_end_clean();
require_once $daemon_config['wifidb_install']."/lib/servers.inc.php";

$version = require $daemon_config['wifidb_install']."/lib/version.inc.php";
ServerStatus::Report($dbcore->sql->conn, $daemon_config['wifidb_nodename'], $version, ServerStatus::Collect());
