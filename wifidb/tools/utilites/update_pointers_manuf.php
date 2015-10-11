<?php
$switches = array('extras'=>'export','screen'=>"CLI");

if(!(require('config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$dbcore->verbose = 1;

$sql = "SELECT `id`, `mac` FROM `wifi`.`wifi_pointers`";
$result = $dbcore->sql->conn->query($sql);

while($array = $result->fetch())
{
    $sql = "UPDATE `wifi`.`wifi_pointers` SET `manuf` = '{$dbcore->manufactures($array['mac'])}' WHERE `id` = '{$array['id']}'";
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