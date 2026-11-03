<?php
/*
server_status.php: this node's report for admin/servers.php, sent each minute by cron (tools/cron/server_status): its
version, load, memory, the disks WifiDB uses, its services and pmtiles-swarm (lib/servers.inc.php). Prints nothing
unless it fails.
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
require_once $daemon_config['wifidb_install']."/lib/mvt.inc.php";

$version = require $daemon_config['wifidb_install']."/lib/version.inc.php";
// with pmtiles-swarm on, whether this node's swarm publishes the archives or mirrors them (tile_archive_generate)
$swarm_role = mvt_generates_archives($dbcore, 'mvt') ? 'builds' : 'follows';
ServerStatus::Report($dbcore->sql->conn, $daemon_config['wifidb_nodename'], $version, ServerStatus::Collect(array('/', '/srv/wifidb-data'), $swarm_role));
