<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbosed("Gathered AP data");

if($dbcore->sql->service == "mysql")
	{$sql = "SELECT `AP_ID`, `File_ID` FROM `wifi_ap` WHERE AP_ID > 0 AND AP_ID <= 1000000 ORDER BY `AP_ID` ASC";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT [AP_ID], [File_ID] FROM [wifi_ap] ORDER BY [AP_ID] ASC";}
$result = $dbcore->sql->conn->query($sql);

echo "Rows that need updating: ".$result->rowCount()."\r\n";
sleep(4);
while($ap = $result->fetch(1))
{
    $AP_ID = $ap['AP_ID'];
	$Orig_File_ID = $ap['File_ID'];
	
	#Get High Points
	if($dbcore->sql->service == "mysql")
		{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? ORDER BY `File_ID` ASC LIMIT 1";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? ORDER BY `File_ID` ASC LIMIT 1";}
	$resgps = $dbcore->sql->conn->prepare($sqlhp);
	$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
	$resgps->execute();
	$fetchgps = $resgps->fetch(2);
	$File_ID = $fetchgps['File_ID'];

	
	if($Orig_File_ID != $File_ID)
	{
		echo "Updating AP_ID".$AP_ID.")\r\n";
		#Update AP IDs
		if($dbcore->sql->service == "mysql")
			{$sqlu = "UPDATE `wifi_ap` SET `File_ID` = ? WHERE `AP_ID` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sqlu = "UPDATE [wifi_ap] SET [File_ID] = ? WHERE [AP_ID] = ?";}
		echo "UPDATE `wifi_ap` SET `File_ID` = $File_ID WHERE `AP_ID` = $AP_ID\r\n";
		$prep = $dbcore->sql->conn->prepare($sqlu);
		$prep->bindParam(1, $File_ID, PDO::PARAM_INT);
		$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
		$prep->execute();
	}
	else
	{
		echo "No Updates needed for AP_ID:".$AP_ID."\r\n";
	}
}