<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";


$user = "ArizonaJon";
$Import_Map_Data="";
for ($i = 0; TRUE; $i++) {
	error_log("Processing pass $i");
	$row_count = 100000;	
	$offset = $i*$row_count ;
	$sql = "SELECT `id`,`mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`manuf` FROM `wifi_pointers` WHERE `long` != '0.0000' AND `username` LIKE ? LIMIT $offset,$row_count";
	#$sql = "SELECT `id`,`mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`manuf` FROM `wifi_pointers` WHERE `long` != '0.0000' AND `username` LIKE ?";
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->bindParam(1, $user, PDO::PARAM_STR);
	$prep->execute();
	$appointer = $prep->fetchAll();
	$number_of_rows = $prep->rowCount();
	echo $number_of_rows.'-';
	if ($number_of_rows == 0) {break;}
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
		);
		if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
		$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
	}
}
$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
		
#echo json_encode($geojson, JSON_NUMERIC_CHECK);
$fp = fopen($user.'.json', 'w');
fwrite($fp, $results);
fclose($fp);