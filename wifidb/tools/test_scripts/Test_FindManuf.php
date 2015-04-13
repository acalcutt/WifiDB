<?php

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "CLI");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

for($i = 0; $i < 10 ;$i++)
{
	$GenMac = str_pad( dechex($i), 6, 0, STR_PAD_LEFT);
	var_dump( $GenMac);
	var_dump( $dbcore->findManuf( $GenMac ) );
}
