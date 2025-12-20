<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbosed("Gathered AP data");

if($dbcore->sql->service == "mysql")
	{
		//$sql = "SELECT `AP_ID`, `FirstHist_ID`, `LastHist_ID`, `HighRSSI_ID`, `HighSig_ID`, `HighGps_ID` FROM `wifi_ap` WHERE AP_ID > 4000000 AND AP_ID <= 5000000 ORDER BY `AP_ID` ASC";
		$sql = "SELECT `AP_ID`, `FirstHist_ID`, `LastHist_ID`, `HighRSSI_ID`, `HighSig_ID`, `HighGps_ID` FROM `wifi_ap` ORDER BY `AP_ID` ASC";
	}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT [AP_ID], [FirstHist_ID], [LastHist_ID], [HighRSSI_ID], [HighSig_ID], [HighGps_ID] FROM [wifi_ap] ORDER BY [AP_ID] ASC";}
$result = $dbcore->sql->conn->query($sql);

echo "Rows that need updating: ".$result->rowCount()."\r\n";
sleep(4);
while($ap = $result->fetch(1))
{
    $AP_ID = $ap['AP_ID'];
	$Orig_FirstHist_ID = $ap['FirstHist_ID'];
	$Orig_LastHist_ID = $ap['LastHist_ID'];
	$Orig_HighRSSI_ID = $ap['HighRSSI_ID'];
	$Orig_HighSig_ID = $ap['HighSig_ID'];
	$Orig_HighGps_ID = $ap['HighGps_ID'];
	
	#Get High Points
	if($dbcore->sql->service == "mysql")
	{
		$sqlhp = "SELECT \n"
			. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL ORDER BY `Hist_Date` ASC, `Hist_ID` ASC LIMIT 1) As `FA_id`,\n"
			. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL ORDER BY `Hist_Date` DESC, `Hist_ID` ASC LIMIT 1) As `LA_id`,\n"
			. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL ORDER BY `Sig` DESC, `Hist_Date` DESC, `Hist_ID` ASC LIMIT 1) As `HighSig_id`,\n"
			. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL ORDER BY `RSSI` DESC, `Hist_Date` DESC, `Hist_ID` ASC LIMIT 1) As `HighRSSI_id`,\n"
			. "(SELECT `wifi_hist`.`GPS_ID`\n"
			. "    FROM `wifi_hist`\n"
			. "    INNER JOIN `wifi_gps` ON `wifi_hist`.`GPS_ID` = `wifi_gps`.`GPS_ID`\n"
			. "    WHERE `wifi_hist`.`AP_ID` = `wap`.`AP_ID` And `wifi_hist`.`Hist_date` IS NOT NULL And `wifi_gps`.`Lat` != '0.0000'\n"
			. "    ORDER BY `wifi_hist`.`RSSI` DESC, `wifi_hist`.`Hist_Date` DESC, `wifi_gps`.`NumOfSats` DESC, `wifi_hist`.`Hist_ID` ASC\n"
			. "    LIMIT 1) As `HighGps_id`\n"
			. "FROM `wifi_ap` As `wap`\n"
			. "WHERE `wap`.`AP_ID` = ?";
	}
	else if($dbcore->sql->service == "sqlsrv")
	{
		$sqlhp = "SELECT\n"
			. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL ORDER BY [Hist_Date] ASC, [Hist_ID] ASC) AS [FA_id],\n"
			. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL ORDER BY [Hist_Date] DESC, [Hist_ID] ASC) AS [LA_id],\n"
			. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL ORDER BY [Sig] DESC, [Hist_Date] DESC, [Hist_ID] ASC) AS [HighSig_id],\n"
			. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL ORDER BY [RSSI] DESC, [Hist_Date] DESC, [Hist_ID] ASC) AS [HighRSSI_id],\n"
			. "(SELECT TOP 1 [wifi_hist].[GPS_ID]\n"
			. "    FROM [wifi_hist]\n"
			. "    INNER JOIN [wifi_gps] ON [wifi_hist].[GPS_ID] = [wifi_gps].[GPS_ID]\n"
			. "    WHERE [wifi_hist].[AP_ID] = [wap].[AP_ID] AND [wifi_hist].[Hist_Date] IS NOT NULL AND [wifi_gps].[Lat] != '0.0000'\n"
			. "    ORDER BY [wifi_hist].[RSSI] DESC, [wifi_hist].[Hist_Date] DESC, [wifi_gps].[NumOfSats] DESC) As [HighGps_id], [wifi_hist].[Hist_ID] ASC\n"
			. "FROM [wifi_ap] As [wap]\n"
			. "WHERE [wap].[AP_ID] = ?";
	}

	$resgps = $dbcore->sql->conn->prepare($sqlhp);
	$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
	$resgps->execute();
	$fetchgps = $resgps->fetch(2);
	$HighSig_id = $fetchgps['HighSig_id'];
	$FA_id = $fetchgps['FA_id'];
	$LA_id = $fetchgps['LA_id'];
	$HighRSSI_id = $fetchgps['HighRSSI_id'];
	$HighGps_id = $fetchgps['HighGps_id'];
	
	if($Orig_FirstHist_ID != $FA_id || $Orig_LastHist_ID != $LA_id || $Orig_HighRSSI_ID != $HighRSSI_id || $Orig_HighSig_ID != $HighSig_id || $Orig_HighGps_ID != $HighGps_id)
	{
		echo "Updating AP_ID".$AP_ID." - FA:".$FA_id."(".$Orig_FirstHist_ID.") - LA:".$LA_id."(".$Orig_LastHist_ID.") - HighRSSI:".$HighRSSI_id."(".$Orig_HighRSSI_ID.") - HighSig:".$HighSig_id."(".$Orig_HighSig_ID.") - HighGPS:".$HighGps_id."(".$Orig_HighGps_ID.")\r\n";
		#Update AP IDs
		if($dbcore->sql->service == "mysql")
			{$sqlu = "UPDATE `wifi_ap` SET `FirstHist_ID` = ? , `LastHist_ID` = ? , `HighRSSI_ID` = ?, `HighSig_ID` = ? , `HighGps_ID` = ? WHERE `AP_ID` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sqlu = "UPDATE [wifi_ap] SET [FirstHist_ID] = ? , [LastHist_ID] = ? , [HighRSSI_ID] = ?, [HighSig_ID] = ? , [HighGps_ID] = ? WHERE [AP_ID] = ?";}
		echo "UPDATE `wifi_ap` SET `FirstHist_ID` = $FA_id , `LastHist_ID` = $LA_id , `HighRSSI_ID` = $HighRSSI_id, `HighSig_ID` = $HighSig_id , `HighGps_ID` = $HighGps_id WHERE `AP_ID` = $AP_ID\r\n";
		$prep = $dbcore->sql->conn->prepare($sqlu);
		$prep->bindParam(1, $FA_id, PDO::PARAM_INT);
		$prep->bindParam(2, $LA_id, PDO::PARAM_INT);
		$prep->bindParam(3, $HighRSSI_id, PDO::PARAM_INT);
		$prep->bindParam(4, $HighSig_id, PDO::PARAM_INT);
		$prep->bindParam(5, $HighGps_id, PDO::PARAM_INT);
		$prep->bindParam(6, $AP_ID, PDO::PARAM_INT);
		$prep->execute();
	}
	else
	{
		echo "No Updates needed for AP_ID:".$AP_ID."\r\n";
	}
}
