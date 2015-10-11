<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");


require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );
$dbcore->verbosed("Testing KML Plot AP 3D Signal Track.");
$ret = $dbcore->UserList(1, 3, 1);