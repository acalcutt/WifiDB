<?php

#error_reporting("E_ALL");
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");

require( '../config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->named = 0;
var_dump($dbcore->export->ExportDailykml("2013-07-18"));