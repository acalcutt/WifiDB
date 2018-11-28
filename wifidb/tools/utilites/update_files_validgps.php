<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";


$dbcore->verbose = 1;

$sql = "SELECT `id` \n"
	. "FROM `files` \n"
	. "WHERE `completed` = 1 ORDER BY `date` DESC";
$result = $dbcore->sql->conn->query($sql);

while($array = $result->fetch())
{
	#Find if file had Valid GPS
	$sql = "SELECT `wifi_hist`.`Hist_ID`\n"
		. "FROM `wifi_hist`\n"
		. "LEFT JOIN `wifi_gps` ON `wifi_hist`.`GPS_ID` = `wifi_gps`.`GPS_ID`\n"
		. "WHERE `wifi_hist`.`File_ID` = ? And `wifi_gps`.`GPS_ID` IS NOT NULL And `wifi_gps`.`Lat` != '0.0000'\n"
		. "LIMIT 1";
	$prepvgps = $dbcore->sql->conn->prepare($sql);
	$prepvgps->bindParam(1, $array['id'], PDO::PARAM_INT);
	$prepvgps->execute();
	$prepvgps_fetch = $prepvgps->fetch(2);
	if($prepvgps_fetch)
	{
		echo "Updating {$array['id']}\r\n";
		$ValidGPS = 1;
		$sql = "UPDATE `files` SET `ValidGPS` = ? WHERE `id` = ?";
		$prepvgpsu = $dbcore->sql->conn->prepare($sql);
		$prepvgpsu->bindParam(1, $ValidGPS, PDO::PARAM_INT);
		$prepvgpsu->bindParam(2, $array['id'], PDO::PARAM_INT);
		$prepvgpsu->execute();
	}
}