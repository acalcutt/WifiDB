<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";


$file_id = readline("Please enter the file id: ");

$sql = "SELECT DISTINCT `GPS_ID` FROM `wifi_hist` WHERE `File_ID` = ?";
$result = $dbcore->sql->conn->prepare($sql);
$result->bindParam(1, $file_id, PDO::PARAM_INT);
$result->execute();
while($ap = $result->fetch(1))
{
    $gps_id = $ap["GPS_ID"];
	echo $gps_id."\r\n";

	$delete_sql = "DELETE FROM `wifi_gps` WHERE `GPS_ID` = ?";
	$dresult = $dbcore->sql->conn->prepare($delete_sql);
	$dresult->bindParam(1, $gps_id, PDO::PARAM_INT);
	$dresult->execute();
	
}


$delete_sql = "DELETE FROM `user_imports` WHERE `file_id` = ?";
$dresult = $dbcore->sql->conn->prepare($delete_sql);
$dresult->bindParam(1, $file_id, PDO::PARAM_INT);
$dresult->execute();

$delete_sql = "DELETE FROM `wifi_hist` WHERE `File_ID` = ?";
$dresult = $dbcore->sql->conn->prepare($delete_sql);
$dresult->bindParam(1, $file_id, PDO::PARAM_INT);
$dresult->execute();