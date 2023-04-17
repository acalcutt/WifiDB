<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$Import_Map_Data = "";

if($dbcore->sql->service == "mysql")
	{
		$sql = "SELECT cell_id.cell_id, cell_id.mac, cell_id.ssid, cell_id.authmode, cell_id.chan, cell_id.type, cell_id.fa, cell_id.la, cell_id.points, cell_id.high_gps_rssi AS rssi,\n"
			. "c_gps.lat AS lat,\n"
			. "c_gps.lon AS lon,\n"
			. "c_file.file_user AS file_user\n"
			. "FROM cell_id\n"
			. "INNER JOIN wifi_gps AS c_gps ON c_gps.GPS_ID = cell_id.highgps_id\n"
			. "INNER JOIN files AS c_file ON c_file.id = cell_id.file_id\n"
			. "WHERE cell_id.type != 'BT' AND cell_id.type != 'BLE' AND cell_id.highgps_id IS NOT NULL\n"
			. "ORDER BY cell_id.cell_id ASC LIMIT ?,?";
	}
else if($dbcore->sql->service == "sqlsrv")
	{
		$sql = "SELECT cell_id.cell_id, cell_id.mac, cell_id.ssid, cell_id.authmode, cell_id.chan, cell_id.type, cell_id.fa, cell_id.la, cell_id.points, cell_id.high_gps_rssi AS rssi,\n"
			. "c_gps.lat AS lat,\n"
			. "c_gps.lon AS lon,\n"
			. "c_file.[file_user] AS [file_user],\n"
			. "cell_carriers.network, cell_carriers.country\n"
			. "FROM cell_id\n"
			. "INNER JOIN wifi_gps AS c_gps ON c_gps.GPS_ID = cell_id.highgps_id\n"
			. "INNER JOIN files AS c_file ON c_file.id = cell_id.file_id\n"
			. "LEFT OUTER JOIN cell_carriers ON CAST(mcc AS varchar) = substring(cell_id.mac,0,4) AND CAST(mnc AS varchar) = substring(cell_id.mac,4,3)\n"
			. "WHERE cell_id.type != 'BT' AND cell_id.type != 'BLE' AND cell_id.highgps_id IS NOT NULL\n"
			. "ORDER BY cell_id.cell_id OFFSET ? ROWS FETCH NEXT ? ROWS ONLY";
	}

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
		$split = explode('_', $ap['mac']);
		$MCCMNC = $split[0];
		$MCC = substr($MCCMNC, 0, 3);
		$MNC = substr($MCCMNC, 3);
		$LAC = $split[1];
		$CELLID = $split[2];
		
		$sqlp = "SELECT network, country FROM cell_carriers WHERE mcc = ? AND mnc = ?";
		$prep3 = $dbcore->sql->conn->prepare($sqlp);
		$prep3->bindParam(1, $MCC, PDO::PARAM_INT);
		$prep3->bindParam(2, $MNC, PDO::PARAM_INT);
		$prep3->execute();
		$prep3_fetch = $prep3->fetch(2);

		if($ap['network']){$name = $ap['network'];}else{$name = $ap['ssid'];}
		echo $name." - ".$ap['ssid']." - ".$MCCMNC." - ".$MCC." - ".$MNC." - ".$LAC." - ".$CELLID." - ".$prep3_fetch['network']." - ".$prep3_fetch['country']."\r\n";
		#Create AP Array
		$ap_info = array(
		"id" => $ap['cell_id'],
		"mac" => $ap['mac'],
		"mapname" => $dbcore->formatSSID($name),
		"network" => $ap['network'],
		"ssid" => $dbcore->formatSSID($ap['ssid']),
		"authmode" => $ap['authmode'],
		"chan" => $ap['chan'],
		"type" => $ap['type'],
		"lat" => $dbcore->convert->dm2dd($ap['lat']),
		"lon" => $dbcore->convert->dm2dd($ap['lon']),
		"rssi" => $ap['rssi'],
		"fa" => $ap['fa'],
		"la" => $ap['la'],
		"user" => $ap['file_user'],
		"points" => $ap['points']
		);
		if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
		$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info, 1);
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
