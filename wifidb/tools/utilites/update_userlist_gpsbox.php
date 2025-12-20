<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$dbcore->verbose = 1;

$sql = "SELECT `id`, `file_id` FROM `user_imports` WHERE GPSBOX_NORTH = ''";
$result = $dbcore->sql->conn->query($sql);
$fetch_imports = $result->fetchAll();

foreach($fetch_imports as $import)
{
	$userlist_id = $import['id'];
	$file_id = $import['file_id'];
	echo "File ID:".$file_id."\r\n";
	$box_latlon = array();
	$Valid_Points = 0;
	
	$sql = "SELECT
			  `wifi_gps`.`lat` AS `lat`, `wifi_gps`.`long` AS `long`
			FROM `wifi_signals`
			  LEFT JOIN `wifi_gps` ON `wifi_signals`.`gps_id` = `wifi_gps`.`id`
			WHERE `wifi_signals`.`file_id` = '$file_id' AND `wifi_gps`.`lat` != '0.0000'";
			
	$result = $dbcore->sql->conn->query($sql);
	while($latlon_fetch = $result->fetch(2))
	{
		# -Add gps to region array-
		$latlon_info = array(
		"lat" => $latlon_fetch['lat'],
		"long" => $latlon_fetch['long'],
		);
		echo $latlon_fetch['lat'];
		$box_latlon[] = $latlon_info;

		# -Set List to be exported-
		$Valid_Points = 1;
	}
	
	if($Valid_Points)
	{
		#Create Region Box
		$final_box = $dbcore->export->FindBox($box_latlon);
		$NORTH = $final_box[0];
		$SOUTH = $final_box[1];
		$EAST = $final_box[2];
		$WEST = $final_box[3];
	}
	else
	{
		$NORTH = "0.0000";
		$SOUTH = "0.0000";
		$EAST = "0.0000";
		$WEST = "0.0000";
	}
	
	echo $NORTH."\r\n";
	echo $SOUTH."\r\n";
	echo $EAST."\r\n";
	echo $WEST."\r\n";
	
	$sql = "UPDATE `user_imports` SET `GPSBOX_NORTH` = '$NORTH', `GPSBOX_SOUTH` = '$SOUTH', `GPSBOX_EAST` = '$EAST', `GPSBOX_WEST` = '$WEST' WHERE `id` = '$userlist_id'";
	echo $sql."\r\n";
	$uresult = $dbcore->sql->conn->query($sql);
	$uresult->execute();
	
}
