<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

#-----------------------------------------------------------------------------------
#Export data from the last year
#-----------------------------------------------------------------------------------

$Import_Map_Data="";
for ($i = 0; TRUE; $i++) {
	error_log("Processing pass $i");
	$row_count = 100000;	
	$offset = $i*$row_count ;
	$sql = "SELECT `id`,`mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`manuf`,`username` FROM `wifi_pointers` WHERE `long` != '0.0000' AND LA >= DATE_SUB(NOW(),INTERVAL 1 YEAR) LIMIT $offset,$row_count";
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->execute();
	$appointer = $prep->fetchAll();
	foreach($appointer as $ap)
	{
		#Get AP KML
		$ap_info = array(
		"id" => $ap['id'],
		"new_ap" => 1,
		"named" => 0,
		"mac" => $ap['mac'],
		"ssid" => $ap['ssid'],
		"chan" => $ap['chan'],
		"radio" => $ap['radio'],
		"NT" => $ap['NT'],
		"sectype" => $ap['sectype'],
		"auth" => $ap['auth'],
		"encry" => $ap['encry'],
		"BTx" => $ap['BTx'],
		"OTx" => $ap['OTx'],
		"FA" => $ap['FA'],
		"LA" => $ap['LA'],
		"lat" => $dbcore->convert->dm2dd($ap['lat']),
		"long" => $dbcore->convert->dm2dd($ap['long']),
		"alt" => $ap['alt'],
		"manuf" => $ap['manuf'],
		"username" => $ap['username']
		);
		if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
		$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
	}
	$number_of_rows = $prep->rowCount();
	echo $number_of_rows.'-';
	if ($number_of_rows !== $row_count) {break;}
}
$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
#echo json_encode($geojson, JSON_NUMERIC_CHECK);
$fp = fopen($daemon_config['wifidb_install'].'out/geojson/WifiDB_0to1year.json', 'w');
fwrite($fp, $results);
fclose($fp);

#-----------------------------------------------------------------------------------
#Export data older than a year, less than 2 year
#-----------------------------------------------------------------------------------

$Import_Map_Data="";
for ($i = 0; TRUE; $i++) {
	error_log("Processing pass $i");
	$row_count = 100000;	
	$offset = $i*$row_count ;
	$sql = "SELECT `id`,`mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`manuf`,`username` FROM `wifi_pointers` WHERE `long` != '0.0000' AND LA >= DATE_SUB(NOW(),INTERVAL 2 YEAR) AND LA < DATE_SUB(NOW(),INTERVAL 1 YEAR) LIMIT $offset,$row_count";
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->execute();
	$appointer = $prep->fetchAll();
	foreach($appointer as $ap)
	{
		#Get AP KML
		$ap_info = array(
		"id" => $ap['id'],
		"new_ap" => 1,
		"named" => 0,
		"mac" => $ap['mac'],
		"ssid" => $ap['ssid'],
		"chan" => $ap['chan'],
		"radio" => $ap['radio'],
		"NT" => $ap['NT'],
		"sectype" => $ap['sectype'],
		"auth" => $ap['auth'],
		"encry" => $ap['encry'],
		"BTx" => $ap['BTx'],
		"OTx" => $ap['OTx'],
		"FA" => $ap['FA'],
		"LA" => $ap['LA'],
		"lat" => $dbcore->convert->dm2dd($ap['lat']),
		"long" => $dbcore->convert->dm2dd($ap['long']),
		"alt" => $ap['alt'],
		"manuf" => $ap['manuf'],
		"username" => $ap['username']
		);
		if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
		$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
	}
	$number_of_rows = $prep->rowCount();
	echo $number_of_rows.'-';
	if ($number_of_rows !== $row_count) {break;}
}
$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
#echo json_encode($geojson, JSON_NUMERIC_CHECK);
$fp = fopen($daemon_config['wifidb_install'].'out/geojson/WifiDB_1to2year.json', 'w');
fwrite($fp, $results);
fclose($fp);

#-----------------------------------------------------------------------------------
#Export data older than 2 year, less than 3 year
#-----------------------------------------------------------------------------------

$Import_Map_Data="";
for ($i = 0; TRUE; $i++) {
	error_log("Processing pass $i");
	$row_count = 100000;	
	$offset = $i*$row_count ;
	$sql = "SELECT `id`,`mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`manuf`,`username` FROM `wifi_pointers` WHERE `long` != '0.0000' AND LA >= DATE_SUB(NOW(),INTERVAL 3 YEAR) AND LA < DATE_SUB(NOW(),INTERVAL 2 YEAR) LIMIT $offset,$row_count";
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->execute();
	$appointer = $prep->fetchAll();
	foreach($appointer as $ap)
	{
		#Get AP KML
		$ap_info = array(
		"id" => $ap['id'],
		"new_ap" => 1,
		"named" => 0,
		"mac" => $ap['mac'],
		"ssid" => $ap['ssid'],
		"chan" => $ap['chan'],
		"radio" => $ap['radio'],
		"NT" => $ap['NT'],
		"sectype" => $ap['sectype'],
		"auth" => $ap['auth'],
		"encry" => $ap['encry'],
		"BTx" => $ap['BTx'],
		"OTx" => $ap['OTx'],
		"FA" => $ap['FA'],
		"LA" => $ap['LA'],
		"lat" => $dbcore->convert->dm2dd($ap['lat']),
		"long" => $dbcore->convert->dm2dd($ap['long']),
		"alt" => $ap['alt'],
		"manuf" => $ap['manuf'],
		"username" => $ap['username']
		);
		if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
		$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
	}
	$number_of_rows = $prep->rowCount();
	echo $number_of_rows.'-';
	if ($number_of_rows !== $row_count) {break;}
}
$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
#echo json_encode($geojson, JSON_NUMERIC_CHECK);
$fp = fopen($daemon_config['wifidb_install'].'out/geojson/WifiDB_2to3year.json', 'w');
fwrite($fp, $results);
fclose($fp);

#-----------------------------------------------------------------------------------
#Export data older than 3 years
#-----------------------------------------------------------------------------------

$Import_Map_Data="";
for ($i = 0; TRUE; $i++) {
	error_log("Processing pass $i");
	$row_count = 100000;	
	$offset = $i*$row_count ;
	$sql = "SELECT `id`,`mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`manuf`,`username` FROM `wifi_pointers` WHERE `long` != '0.0000' AND LA < DATE_SUB(NOW(),INTERVAL 3 YEAR) LIMIT $offset,$row_count";
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->execute();
	$appointer = $prep->fetchAll();
	foreach($appointer as $ap)
	{
		#Get AP KML
		$ap_info = array(
		"id" => $ap['id'],
		"new_ap" => 1,
		"named" => 0,
		"mac" => $ap['mac'],
		"ssid" => $ap['ssid'],
		"chan" => $ap['chan'],
		"radio" => $ap['radio'],
		"NT" => $ap['NT'],
		"sectype" => $ap['sectype'],
		"auth" => $ap['auth'],
		"encry" => $ap['encry'],
		"BTx" => $ap['BTx'],
		"OTx" => $ap['OTx'],
		"FA" => $ap['FA'],
		"LA" => $ap['LA'],
		"lat" => $dbcore->convert->dm2dd($ap['lat']),
		"long" => $dbcore->convert->dm2dd($ap['long']),
		"alt" => $ap['alt'],
		"manuf" => $ap['manuf'],
		"username" => $ap['username']
		);
		if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
		$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
	}
	$number_of_rows = $prep->rowCount();
	echo $number_of_rows.'-';
	if ($number_of_rows !== $row_count) {break;}
}
$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
#echo json_encode($geojson, JSON_NUMERIC_CHECK);
$fp = fopen($daemon_config['wifidb_install'].'out/geojson/WifiDB_Legacy.json', 'w');
fwrite($fp, $results);
fclose($fp);