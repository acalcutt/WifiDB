<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbosed("Gathered AP data");

#Get file hash from file import ID

echo "Enter the File ID to reset: ";
$handle = fopen ("php://stdin","r");
$File_ID = trim(fgets($handle));
if($File_ID == ''){
    echo "No file ID entered, exiting!\n";
    exit;
}
echo "File ID:$File_ID";

if($File_ID)
{
	#Go through APs with this File ID
	if($dbcore->sql->service == "mysql")
		{$sql = "SELECT `AP_ID` FROM `wifi_ap` WHERE File_ID = ?";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sql = "SELECT [AP_ID]  FROM [wifi_ap] WHERE File_ID = ?";}
	$apl = $dbcore->sql->conn->prepare($sql);
	$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
	$apl->execute();
	while($ap = $apl->fetch(1))
	{
		$AP_ID = $ap['AP_ID'];
		echo "AP_ID:$AP_ID\r\n";
		
		#Find if this AP is in another list
		if($dbcore->sql->service == "mysql")
			{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? And `File_ID != ? LIMIT 1";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sqlhp = "SELECT TOP 1 [File_ID] FROM [wifi_hist] WHERE [AP_ID] = ? And [File_ID] != ?";}
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
		$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
		$resgps->execute();
		$fetchgps = $resgps->fetch(2);
		$New_File_ID = $fetchgps['File_ID'];
		if($New_File_ID)
		{
			echo "Updating AP_ID".$AP_ID.")\r\n";
			#Update AP IDs
			if($dbcore->sql->service == "mysql")
				{$sqlu = "UPDATE `wifi_ap` SET `File_ID` = ? WHERE `AP_ID` = ?";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlu = "UPDATE [wifi_ap] SET [File_ID] = ? WHERE [AP_ID] = ?";}
			$prep = $dbcore->sql->conn->prepare($sqlu);
			$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
			$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
			$prep->execute();
		}
	}
	$sqlhp = "INSERT INTO files_tmp\n"
		.	 "([file], file_orig, [user], otherusers, notes, title, size, date, hash, converted, prev_ext, type)\n"
			. "SELECT [file], file_orig, [user], otherusers, notes, title, size, date, hash, converted, prev_ext, type\n"
			. "FROM files\n"
			. "WHERE id = ?";
	$resgps = $dbcore->sql->conn->prepare($sqlhp);
	$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
	$resgps->execute();
	
	$sqlhp = "DELETE FROM wifi_hist WHERE File_ID = ?";
	$resgps = $dbcore->sql->conn->prepare($sqlhp);
	$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
	$resgps->execute();	
	
	$sqlhp = "DELETE FROM wifi_ap WHERE File_ID = ?";
	$resgps = $dbcore->sql->conn->prepare($sqlhp);
	$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
	$resgps->execute();

	$sqlhp = "DELETE FROM wifi_gps WHERE File_ID = ?";
	$resgps = $dbcore->sql->conn->prepare($sqlhp);
	$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
	$resgps->execute();
	
	$sqlhp = "DELETE FROM files WHERE id = ?";
	$resgps = $dbcore->sql->conn->prepare($sqlhp);
	$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
	$resgps->execute();
}