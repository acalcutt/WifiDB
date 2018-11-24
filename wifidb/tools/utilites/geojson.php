<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";



$exports = [
    ["WifiDB_0to1year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, whFA.Hist_Date As FA, whLA.Hist_Date As LA, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user As user FROM `wifi_ap` AS wap LEFT JOIN wifi_hist AS whFA ON whFA.Hist_ID = wap.FirstHist_ID LEFT JOIN wifi_hist AS whLA ON whLA.Hist_ID = wap.LastHist_ID LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON whFA.File_ID = wf.id WHERE wGPS.Lat IS NOT NULL AND wGPS.Lat != '0.0000' AND whLA.Hist_Date >= DATE_SUB(NOW(),INTERVAL 1 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
    ["WifiDB_1to2year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, whFA.Hist_Date As FA, whLA.Hist_Date As LA, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user As user FROM `wifi_ap` AS wap LEFT JOIN wifi_hist AS whFA ON whFA.Hist_ID = wap.FirstHist_ID LEFT JOIN wifi_hist AS whLA ON whLA.Hist_ID = wap.LastHist_ID LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON whFA.File_ID = wf.id WHERE wGPS.Lat IS NOT NULL AND wGPS.Lat != '0.0000' AND whLA.Hist_Date >= DATE_SUB(NOW(),INTERVAL 2 YEAR) AND whLA.Hist_Date < DATE_SUB(NOW(),INTERVAL 1 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
	["WifiDB_2to3year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, whFA.Hist_Date As FA, whLA.Hist_Date As LA, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user As user FROM `wifi_ap` AS wap LEFT JOIN wifi_hist AS whFA ON whFA.Hist_ID = wap.FirstHist_ID LEFT JOIN wifi_hist AS whLA ON whLA.Hist_ID = wap.LastHist_ID LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON whFA.File_ID = wf.id WHERE wGPS.Lat IS NOT NULL AND wGPS.Lat != '0.0000' AND whLA.Hist_Date >= DATE_SUB(NOW(),INTERVAL 3 YEAR) AND whLA.Hist_Date < DATE_SUB(NOW(),INTERVAL 2 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
	["WifiDB_Legacy.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, whFA.Hist_Date As FA, whLA.Hist_Date As LA, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user As user FROM `wifi_ap` AS wap LEFT JOIN wifi_hist AS whFA ON whFA.Hist_ID = wap.FirstHist_ID LEFT JOIN wifi_hist AS whLA ON whLA.Hist_ID = wap.LastHist_ID LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON whFA.File_ID = wf.id WHERE wGPS.Lat IS NOT NULL AND wGPS.Lat != '0.0000' AND whLA.Hist_Date < DATE_SUB(NOW(),INTERVAL 3 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
];

foreach ($exports as list($filename, $sql)) {
    echo "filename: $filename; sql: $sql; \n";
	$Import_Map_Data="";
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
			#Get AP KML
			$ap_info = array(
			"id" => $ap['AP_ID'],
			"new_ap" => 1,
			"named" => 0,
			"mac" => $ap['BSSID'],
			"ssid" => $ap['SSID'],
			"chan" => $ap['CHAN'],
			"radio" => $ap['RADTYPE'],
			"NT" => $ap['NETTYPE'],
			"sectype" => $ap['SECTYPE'],
			"auth" => $ap['AUTH'],
			"encry" => $ap['ENCR'],
			"BTx" => $ap['BTX'],
			"OTx" => $ap['OTX'],
			"FA" => $ap['FA'],
			"LA" => $ap['LA'],
			"lat" => $dbcore->convert->dm2dd($ap['Lat']),
			"lon" => $dbcore->convert->dm2dd($ap['Lon']),
			"alt" => $ap['Alt'],
			"manuf"=>$dbcore->findManuf($ap['BSSID']),
			"user" => $ap['user']
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
	$fp = fopen($daemon_config['wifidb_install'].'out/geojson/'.$filename, 'w');
	fwrite($fp, $results);
	fclose($fp);
}