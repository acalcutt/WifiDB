<?php
global $switches;
$switches = array('extras'=>'cli','screen'=>"CLI");

require( '../daemon/config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->verbose = 1;
$dbcore->named = 1;
var_dump($dbcore->sec->CreateUser("pferland2", "blank", "pferland@eiri.lan"));
?>