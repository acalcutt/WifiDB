<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "cli");
error_reporting("E_ALL");

require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->verbose = 1;
var_dump($dbcore->sec->UnlockUser($argv[1]));