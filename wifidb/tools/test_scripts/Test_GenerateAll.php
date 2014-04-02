<?php

#error_reporting("E_ALL");
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");

require( '../config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );
var_dump($dbcore->GenerateDaemonKMLData());
