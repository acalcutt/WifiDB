<?php
/**
 * Created by Phillip Ferland
 * Date: 5/26/13
 * Time: 2:15 PM
 */
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");


require( '../daemon/config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->verbosed("Testing Wardrive 4 SQLite file.");
$ret = $dbcore->convert->main("/var/www/wifidb/import/up/wifi.db", 0);
var_dump($ret);
#die();


$dbcore->verbosed("Testing Wardrive 3 SQLite file.");
$ret = $dbcore->convert->main("/var/www/wifidb/import/up/wardrive.db3", 0);
var_dump($ret);




$dbcore->verbosed("Testing CSV file.");
$ret = $dbcore->convert->main("/var/www/wifidb/import/up/testing.csv", 0);
var_dump($ret);





$dbcore->verbosed("Testing Compressed VS1 file.");
$ret = $dbcore->convert->main("/var/www/wifidb/import/up/testing.vsz", 0);
var_dump($ret);