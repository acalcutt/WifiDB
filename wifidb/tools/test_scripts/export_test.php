<?php
global $switches;
$switches = array('extras'=>'export','screen'=>"CLI");

if(!(require('../daemon/config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$dbcore->verbose = 1;
$dbcore->named = 1;

if($dbcore->GenerateDaemonKMLData())
{
    echo "Done!\r\n";
}else
{
    echo "Failed :(\r\n";
}

?>