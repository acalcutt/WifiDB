<?php
/*
 * well duh, it counts all the rows in the wifi database.
 */
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";


$sql0 = "SELECT SUM( TABLE_ROWS ) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = '".$dbcore->sql->database."'";
$result = $dbcore->sql->conn->query($sql0, $conn);
$newArray = $result->fetch(2);
echo "Aprox number of rows in `Wifi`: \033[0;31m".$newArray[0]."\033[0;37m";