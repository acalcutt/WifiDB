<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";


$dbcore->verbose = 1;

$sql = "SELECT `AP_ID`, `mac` FROM `wifi_ap`";
$result = $dbcore->sql->conn->query($sql);

while($array = $result->fetch())
{
    $sql = "UPDATE `wifi_ap` SET `manuf` = '{$dbcore->findManuf($array['mac'])}' WHERE `AP_ID` = '{$array['AP_ID']}'";
    echo $sql."\r\n";
    if($dbcore->sql->conn->query($sql))
    {
        echo "Updated {$array['id']}!!!\r\n";
    }else
    {
        echo "Failed to update {$array['id']}\r\n";
        var_dump($dbcore->sql->conn->errorInfo());
    }
    #die();
}
