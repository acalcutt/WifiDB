<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";


$dbcore->verbose = 1;
$dbcore->named = 1;

if($dbcore->GenerateDaemonKMLData())
{
    echo "Done!\r\n";
}else
{
    echo "Failed :(\r\n";
}
