<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$Import_Map_Data = "";

$sql = "SELECT cell_id.cell_id, cell_id.mac, cell_id.ssid, cell_id.authmode, cell_id.chan, cell_id.type,\n"
    . "c_fa.hist_date AS fa,\n"
    . "c_la.hist_date AS la,\n"
    . "c_gps.lat AS lat,\n"
    . "c_gps.lon AS lon,\n"
    . "c_gps.rssi AS rssi,\n"
    . "c_file.user AS user\n"
    . "FROM cell_id\n"
    . "INNER JOIN cell_hist AS c_fa ON c_fa.cell_hist_id = cell_id.fa_id\n"
    . "INNER JOIN cell_hist AS c_la ON c_la.cell_hist_id = cell_id.la_id\n"
    . "INNER JOIN cell_hist AS c_gps ON c_gps.cell_hist_id = cell_id.highgps_id\n"
    . "INNER JOIN files AS c_file ON c_file.id = cell_id.file_id\n"
    . "WHERE cell_id.type != 'BT' AND cell_id.type != 'BLE' \n"
    . "ORDER BY cell_id.cell_id ASC LIMIT ?,?";

for ($i = 0; TRUE; $i++) {
	error_log("Processing pass $i");
	$row_count = 100000;	
	$offset = $i*$row_count ;
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->bindParam(1, $offset, PDO::PARAM_INT);
	$prep->bindParam(2, $row_count, PDO::PARAM_INT);
	$prep->execute();
	$appointer = $prep->fetchAll();
	foreach($appointer as $ap)
	{
		#Get number of AP points
		$sqlp = "SELECT count(hist_date) AS points FROM cell_hist WHERE cell_id = ?";
		$prep2 = $dbcore->sql->conn->prepare($sqlp);
		$prep2->bindParam(1, $ap['cell_id'], PDO::PARAM_INT);
		$prep2->execute();
		$prep2_fetch = $prep2->fetch(2);
		
		$split = explode('_', $ap['mac']);
		$MCCMNC = $split[0];
		$MCC = substr($MCCMNC, 0, 3);
		$MNC = substr($MCCMNC, 3);
		$LAC = $split[1];
		$CELLID = $split[2];
		
		$sqlp = "SELECT network, country FROM `cell_carriers` WHERE mcc = ? AND mnc = ?";
		$prep3 = $dbcore->sql->conn->prepare($sqlp);
		$prep3->bindParam(1, $MCC, PDO::PARAM_INT);
		$prep3->bindParam(2, $MNC, PDO::PARAM_INT);
		$prep3->execute();
		$prep3_fetch = $prep3->fetch(2);

		if($prep3_fetch['network']){$name = $prep3_fetch['network'];}else{$name = $ap['ssid'];}
		echo $name." - ".$ap['ssid']." - ".$MCCMNC." - ".$MCC." - ".$MNC." - ".$LAC." - ".$CELLID." - ".$prep3_fetch['network']." - ".$prep3_fetch['country']."\r\n";
		#Create AP Array
		$ap_info = array(
		"id" => $ap['cell_id'],
		"name" => $name,
		"mac" => $ap['mac'],
		"ssid" => $ap['ssid'],
		"authmode" => $ap['authmode'],
		"chan" => $ap['chan'],
		"type" => $ap['type'],
		"lat" => $dbcore->convert->dm2dd($ap['lat']),
		"lon" => $dbcore->convert->dm2dd($ap['lon']),
		"rssi" => $ap['rssi'],
		"fa" => $ap['fa'],
		"la" => $ap['la'],
		"user" => $ap['user'],
		"points" => $prep2_fetch['points']
		);
		if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
		$Import_Map_Data .=$dbcore->createGeoJSON->CreateCellFeature($ap_info, 1);
	}
	$number_of_rows = $prep->rowCount();
	echo $number_of_rows.'-';
	if ($number_of_rows !== $row_count) {break;}
}
$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
#echo json_encode($geojson, JSON_NUMERIC_CHECK);
$fp = fopen($daemon_config['wifidb_install'].'out/geojson/cell_networks.json', 'w');
fwrite($fp, $results);
fclose($fp);