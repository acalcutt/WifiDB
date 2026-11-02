<?php
/*
out_sync.php: keeps this node's generated files (out/pmtiles, out/pmtiles-mlt, out/geojson, out/daemon) the same as
the other nodes', through the shared files bucket (lib/out_sync.inc.php). Run every few minutes by cron
(tools/cron/out_sync); with no shared files bucket in Site Settings it does nothing.
  php out_sync.php      sync, saying only what it did
  php out_sync.php -v   and each file
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){fwrite(STDERR, "daemon.config.inc.php is missing\n"); exit(1);}
if($daemon_config['wifidb_install'] == ""){fwrite(STDERR, "wifidb_install is not set in daemon.config.inc.php\n"); exit(1);}
// Keep the library's own output out of the log
ob_start();
require $daemon_config['wifidb_install']."/lib/init.inc.php";
ob_end_clean();
require_once $daemon_config['wifidb_install']."/lib/shared_files.inc.php";
require_once $daemon_config['wifidb_install']."/lib/out_sync.inc.php";

$shared = SharedFiles::FromSettings($dbcore->sql->conn, $config);
if(!$shared) { exit(0); }   # no bucket: each node keeps its own out/
$sync = new OutSync($shared->S3(), $shared->Prefix(), rtrim($daemon_config['wifidb_install'], '/').'/out',
	rtrim($config['wifidb_tools'], '/').'/log/out-sync-state.json');
if(in_array('-v', $argv, true)) { $sync->log = function ($line) { echo $line."\n"; }; }
$did = $sync->Run();
$counts = array_filter(array_intersect_key($did, array_flip(array('pushed', 'pulled', 'removed_remote', 'removed_here'))));
if($counts) { echo '['.gmdate('Y-m-d H:i:s').'] '.http_build_query($counts, '', ', ')."\n"; }
foreach($did['errors'] as $error) { fwrite(STDERR, '['.gmdate('Y-m-d H:i:s').'] '.$error."\n"); }
exit($did['errors'] ? 1 : 0);
