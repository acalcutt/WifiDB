<?php

#error_reporting("E_ALL");
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");

require( '../config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );


$dbcore->verbosed("Testing KML Update KML file.");

echo "Start\r\n";
$dbcore->named = 0;
var_dump($dbcore->ExportAllkml());

echo "End\r\n";
