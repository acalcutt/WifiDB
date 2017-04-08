<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";


$sql = "SELECT `id`,`ssid`,`mac`,`ap_hash`,`lat`,`long` FROM `wifi`.`wifi_pointers` ORDER BY `id` ASC";
echo $sql."\r\n";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Gathered AP data");
echo "Rows that need updating: ".$result->rowCount()."\r\n";
sleep(4);
while($ap = $result->fetch(1))
{
    $id = $ap['id'];
    $ssid = $ap['ssid'];
    $mac = $ap['mac'];
	$ap_hash = $ap['ap_hash'];
	$lat = $ap['lat'];
	$long = $ap['long'];
	
	echo "------------------------------------\r\n";
	echo $id."|".$ssid."|".$mac."|".$ap_hash."\r\n";
	
	// Update Highest GPS position
	#$sql = "SELECT `lat`, `long`, `sats` FROM `wifi`.`wifi_gps` WHERE `ap_hash` = ? And `lat`<>'0.0000' ORDER BY `date` DESC, `sats` DESC, `time` DESC";
	#$sql = "SELECT `wifi_gps`.`lat` AS `lat`, `wifi_gps`.`long` AS `long`, `wifi_gps`.`sats` AS `sats` FROM `wifi`.`wifi_signals` INNER JOIN `wifi`.`wifi_gps` on `wifi_signals`.`gps_id` = `wifi_gps`.`id` WHERE `wifi_signals`.`ap_hash` = ? And `wifi_gps`.`lat`<>'0.0000' ORDER BY `wifi_gps`.`date` DESC, `wifi_gps`.`sats` DESC, `wifi_gps`.`time` DESC LIMIT 1";
	#$sql = "SELECT `wifi_gps`.`lat` AS `lat`, `wifi_gps`.`long` AS `long`, `wifi_gps`.`sats` AS `sats`, `wifi_signals`.`signal` AS `signal`, `wifi_signals`.`rssi` AS `rssi` FROM `wifi`.`wifi_signals` INNER JOIN `wifi`.`wifi_gps` on wifi_signals.gps_id = `wifi_gps`.`id` WHERE `wifi_signals`.`ap_hash` = ? And `wifi_gps`.`lat`<>'0.0000' ORDER BY cast(`wifi_signals`.`rssi` as int) DESC, `wifi_signals`.`signal` DESC, `wifi_gps`.`sats` DESC, `wifi_gps`.`date` DESC, `wifi_gps`.`time` DESC";
	$sql = "SELECT `wifi_gps`.`lat` AS `lat`, `wifi_gps`.`long` AS `long`, `wifi_gps`.`sats` AS `sats`, `wifi_signals`.`signal` AS `signal`, `wifi_signals`.`rssi` AS `rssi` FROM `wifi`.`wifi_signals` INNER JOIN `wifi`.`wifi_gps` on wifi_signals.gps_id = `wifi_gps`.`id` WHERE `wifi_signals`.`ap_hash` = ? And `wifi_gps`.`lat`<>'0.0000' ORDER BY cast(`wifi_signals`.`rssi` as SIGNED) DESC, `wifi_signals`.`signal` DESC, `wifi_gps`.`date` DESC, `wifi_gps`.`sats` DESC LIMIT 1";
	#echo $sql;
	$resgps = $dbcore->sql->conn->prepare($sql);
	$resgps->bindParam(1, $ap_hash, PDO::PARAM_STR);
	$resgps->execute();
    #$rows = $resgps->rowCount();
	#echo $rows."\r\n";
	$dbcore->sql->checkError();
	$fetchgps = $resgps->fetch(2);
	
	if($fetchgps['lat'])
	{
		$high_lat = $fetchgps['lat'];
		$high_long = $fetchgps['long'];
		$high_sats = $fetchgps['sats'];
		$high_sig = $fetchgps['signal'];
		$high_rssi = $fetchgps['rssi'];

		if ($lat != $high_lat && $long != $high_long)
		{
			echo "New GPS :".$high_lat."|".$high_long."|".$high_sats."|".$high_sig."|".$high_rssi."\r\n";
							
			$sql = "UPDATE `wifi`.`wifi_pointers` SET `lat` = ?, `long` = ? WHERE `ap_hash` = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $high_lat, PDO::PARAM_STR);
			$prep->bindParam(2, $high_long, PDO::PARAM_STR);
			$prep->bindParam(3, $ap_hash, PDO::PARAM_STR);
			$prep->execute();
			if($dbcore->sql->checkError() !== 0)
			{
				$dbcore->verbosed(var_export($dbcore->sql->conn->errorInfo(),1), -1);
				$dbcore->logd("Error Updating High GPS Position.\r\n".var_export($dbcore->sql->conn->errorInfo(),1));
				throw new ErrorException("Error Updating High GPS Position.\r\n".var_export($dbcore->sql->conn->errorInfo(),1));
			}
		}
		else
		{
			echo "Already up to date :".$lat."=".$high_lat."||".$long."=".$high_long."\r\n";
		}
	}
}