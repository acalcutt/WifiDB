<?php

#error_reporting("E_ALL");
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";


$dbcore->export->named = 0;
var_dump($dbcore->export->ExportDailykml("2015-03-21"));

#$dbcore->export->named = 1;
#var_dump($dbcore->export->ExportDailykml("2015-03-21"));