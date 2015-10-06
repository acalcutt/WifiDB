<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "cli");
error_reporting("E_ALL");

require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->verbose = 1;
$dbcore->named = 1;
$username = $argv[1];
$password = $argv[2];

echo "Test login:\r\n";
var_dump($dbcore->sec->Login($username, $password));
var_dump($dbcore->sec->username);
var_dump($dbcore->sec->pass_hash);