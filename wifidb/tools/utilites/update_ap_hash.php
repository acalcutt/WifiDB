<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

if($dbcore->sql->service == "mysql")
	{$sql = "SELECT `AP_ID`,`SSID`,`BSSID`,`CHAN`,`SECTYPE`,`AUTH`,`ENCR`,`FLAGS`,`ap_hash` FROM `wifi_ap` ORDER BY `AP_ID` ASC";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT [AP_ID],[SSID],[BSSID],[CHAN],[SECTYPE],[AUTH],[ENCR],[FLAGS],[ap_hash] FROM [wifi_ap] ORDER BY [AP_ID] ASC";}
else if($dbcore->sql->service == "pgsql")
	{$sql = "SELECT ap_id,ssid,bssid,chan,sectype,auth,encr,flags,ap_hash FROM wifi_ap ORDER BY AP_ID ASC";}
echo $sql."\r\n";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Gathered AP data");
sleep(4);
while($ap = $result->fetch(1))
{
	list($CHAN, $radio2) = $dbcore->convert->findFreq($ap['CHAN']);
    $AP_ID = $ap['AP_ID'];
    $SSID = $ap['SSID'];
    $BSSID = $ap['BSSID'];
	$SECTYPE = $ap['SECTYPE'];
	if($ap['FLAGS'])
	{
		//echo "FLAGS:".$ap['FLAGS']."\r\n";
		list($AUTH, $ENCR, $sectype2, $nt2) = $dbcore->convert->findCapabilities($ap['FLAGS']);
	}
	else
	{
		$AUTH = $ap['AUTH'];
		$ENCR = $ap['ENCR'];
	}
	$old_ap_hash = $ap['ap_hash'];
	$ap_hash = md5($SSID.$BSSID.$CHAN.$SECTYPE.$AUTH.$ENCR);
	

	
	if ($old_ap_hash != $ap_hash) 
	{
		echo ">>>>>>>>>>>>>>>>>>>> Updating $AP_ID \r\n";
		echo "$AP_ID|$SSID|$BSSID|$CHAN(".$ap['CHAN'].")|$SECTYPE|$AUTH|$ENCR|old_hash:$old_ap_hash|new_hash:$ap_hash\r\n";
		
		if($dbcore->sql->service == "mysql")
			{$sql = "UPDATE `wifi_ap` SET `ap_hash` = ?, `CHAN` = ?, `AUTH` = ?, `ENCR` = ? WHERE `AP_ID` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "UPDATE [wifi_ap] SET [ap_hash] = ?, [CHAN] = ?, [AUTH] = ?, [ENCR] = ? WHERE [AP_ID] = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sql = "UPDATE wifi_ap SET ap_hash = ?, chan = ?, auth = ?, encr = ? WHERE ap_id = ?";}
		
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $ap_hash, PDO::PARAM_STR);
		$prep->bindParam(2, $CHAN, PDO::PARAM_INT);
		$prep->bindParam(3, $AUTH, PDO::PARAM_STR);
		$prep->bindParam(4, $ENCR, PDO::PARAM_STR);
		$prep->bindParam(5, $AP_ID, PDO::PARAM_INT);
		$prep->execute();
		if($dbcore->sql->checkError() !== 0)
		{
			$dbcore->verbosed(var_export($dbcore->sql->conn->errorInfo(),1), -1);
			$dbcore->logd("Error Updating AP Hash.\r\n".var_export($dbcore->sql->conn->errorInfo(),1));
			throw new ErrorException("Error Updating AP Hash.\r\n".var_export($dbcore->sql->conn->errorInfo(),1));
		}
	}
}

if($dbcore->sql->service == "mysql")
	{$sql = "SELECT `ap_hash` FROM `wifi_ap` GROUP BY `ap_hash` HAVING COUNT(*) > 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT [ap_hash] FROM [wifi_ap] GROUP BY [ap_hash] HAVING COUNT(*) > 1";}
else if($dbcore->sql->service == "pgsql")
	{$sql = "SELECT ap_hash FROM wifi_ap GROUP BY ap_hash HAVING COUNT(*) > 1";}
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Gathered AP data");
while($ap = $result->fetch(1))
{
	$ap_hash = $ap['ap_hash'];
	echo "hash:".$ap_hash."\r\n";
	
	if($dbcore->sql->service == "mysql")
		{$sqlh = "SELECT `AP_ID` FROM `wifi_ap` WHERE `ap_hash` = ? ORDER BY AP_ID ASC";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sqlh = "SELECT [AP_ID] FROM [wifi_ap] WHERE `ap_hash` = ? ORDER BY AP_ID ASC";}
	else if($dbcore->sql->service == "pgsql")
		{$sqlh = "SELECT ap_id_ID FROM wifi_ap WHERE `ap_hash` = ? ORDER BY AP_ID ASC";}
	$prep2 = $dbcore->sql->conn->prepare($sqlh);
	$prep2->bindParam(1, $ap_hash, PDO::PARAM_STR);
	$prep2->execute();
	$count = 0;
	$first_apid = 0;
	while($ap_info = $prep2->fetch(1))
	{
		$count++;
		if($count == 1)
		{
			$first_apid = $ap_info['AP_ID'];
			echo "AP_ID:".$first_apid."\r\n";
		}
		else
		{
			$orig_apid = $ap_info['AP_ID'];
			$new_apid = $first_apid;
			echo "Change AP_ID $orig_apid to $new_apid\r\n";
			
			if($dbcore->sql->service == "mysql")
				{$sqlu = "UPDATE `wifi_hist` SET `AP_ID` = ?, New = 0 WHERE `AP_ID` = ?";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlu = "UPDATE [wifi_hist] SET [AP_ID] = ?, New = 0 WHERE [AP_ID] = ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqlu = "UPDATE wifi_hist SET ap_id = ?, new = 0 WHERE `ap_id` = ?";}
			$prep3 = $dbcore->sql->conn->prepare($sqlu);
			$prep3->bindParam(1, $new_apid, PDO::PARAM_INT);
			$prep3->bindParam(2, $orig_apid, PDO::PARAM_INT);
			$prep3->execute();
			
			
			echo "Delete duplicate AP_ID $orig_apid\r\n";
			if($dbcore->sql->service == "mysql")
				{$sqld = "DELETE FROM `wifi_ap` WHERE `AP_ID` = ?";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqld = "DELETE FROM [wifi_ap] WHERE [AP_ID] = ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqld = "DELETE FROM wifi_ap WHERE ap_id = ?";}
			$prep4 = $dbcore->sql->conn->prepare($sqld);
			$prep4->bindParam(1, $orig_apid, PDO::PARAM_INT);
			$prep4->execute();
		}
	}
	
}

