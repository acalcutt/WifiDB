<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

if($dbcore->sql->service == "mysql")
	{$sql = "SELECT `AP_ID`,`SSID`,`BSSID`,`CHAN`,`SECTYPE`,`AUTH`,`ENCR`,`ap_hash` FROM `wifi_ap` ORDER BY `AP_ID` ASC";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT [AP_ID],[SSID],[BSSID],[CHAN],[SECTYPE],[AUTH],[ENCR],[ap_hash] FROM [wifi_ap] ORDER BY [AP_ID] ASC";}
else if($dbcore->sql->service == "pgsql")
	{$sql = "SELECT ap_id,ssid,bssid,chan,sectype,auth,encr,ap_hash FROM wifi_ap ORDER BY AP_ID ASC";}
echo $sql."\r\n";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Gathered AP data");
sleep(4);
while($ap = $result->fetch(1))
{
    $AP_ID = $ap['AP_ID'];
    $SSID = $ap['SSID'];
    $BSSID = $ap['BSSID'];
	$CHAN = $ap['CHAN'];
	$SECTYPE = $ap['SECTYPE'];
	$AUTH = $ap['AUTH'];
	$ENCR = $ap['ENCR'];
	$old_ap_hash = $ap['ap_hash'];
	$ap_hash = md5($SSID.$BSSID.$CHAN.$SECTYPE.$AUTH.$ENCR);
	
	echo "------------------------------------------------------------------------\r\n";
	echo "$AP_ID|$SSID|$BSSID|$CHAN|$SECTYPE|$AUTH|$ENCR|old_hash:$old_ap_hash|new_hash:$ap_hash\r\n";
	
	if ($old_ap_hash != $ap_hash) 
	{
		echo ">>>>>>>>>>>>>>>>>>>> Updating $AP_ID \r\n";
		if($dbcore->sql->service == "mysql")
			{$sql = "UPDATE `wifi_ap` SET `ap_hash` = ? WHERE `AP_ID` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "UPDATE [wifi_ap] SET [ap_hash] = ? WHERE [AP_ID] = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sql = "UPDATE wifi_ap SET ap_hash = ? WHERE ap_id = ?";}
		
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $ap_hash, PDO::PARAM_STR);
		$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
		$prep->execute();
		if($dbcore->sql->checkError() !== 0)
		{
			$dbcore->verbosed(var_export($dbcore->sql->conn->errorInfo(),1), -1);
			$dbcore->logd("Error Updating AP Hash.\r\n".var_export($dbcore->sql->conn->errorInfo(),1));
			throw new ErrorException("Error Updating AP Hash.\r\n".var_export($dbcore->sql->conn->errorInfo(),1));
		}
	}
}