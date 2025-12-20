<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbosed("Gathered AP data");

$sql = "SELECT AP_ID, File_ID FROM wifi_ap WHERE AP_ID > 3500000 AND AP_ID <= 5000000 ORDER BY AP_ID ASC";
$result = $dbcore->sql->conn->query($sql);

echo "Rows that need updating: ".$result->rowCount()."\r\n";
sleep(4);
while($ap = $result->fetch(1))
{
    $AP_ID = $ap['AP_ID'];
	$File_ID = $ap['File_ID'];

	echo "-------------------- $AP_ID - $File_ID --------------------\r\n";
	
	$sql = "UPDATE wifi_hist SET New = 1 WHERE AP_ID = ? AND File_ID = ?";
	echo "UPDATE wifi_hist SET New = 1 WHERE AP_ID = $AP_ID AND File_ID = $File_ID\r\n";
	$prep1 = $dbcore->sql->conn->prepare($sql);
	$prep1->bindParam(1, $AP_ID, PDO::PARAM_INT);
	$prep1->bindParam(2, $File_ID, PDO::PARAM_INT);
	$prep1->execute();
	
	$sql = "UPDATE wifi_hist SET New = 0 WHERE AP_ID = ? AND File_ID != ?";
	echo "UPDATE wifi_hist SET New = 0 WHERE AP_ID = $AP_ID AND File_ID != $File_ID\r\n";
	$prep2 = $dbcore->sql->conn->prepare($sql);
	$prep2->bindParam(1, $AP_ID, PDO::PARAM_INT);
	$prep2->bindParam(2, $File_ID, PDO::PARAM_INT);
	$prep2->execute();



}