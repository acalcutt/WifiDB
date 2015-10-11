<?php

#error_reporting("E_ALL");
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");

require( '../config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->export->named = 0;
var_dump($dbcore->export->ExportDailykml("2015-03-21"));

#$dbcore->export->named = 1;
#var_dump($dbcore->export->ExportDailykml("2015-03-21"));