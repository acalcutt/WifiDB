<?php
/*
geojson.php: writes the access point GeoJSON files by age (out/geojson/WifiDB_0to1year.json, _1to2year, _2to3year and
_Legacy) in one go, from the command line. daemon/geojsond.php makes the same files on its schedule. MariaDB, SQL Server
and PostgreSQL: the age limits are worked out here and bound, so no date function differs between them.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$q = function ($name) use ($dbcore) { return $dbcore->sql->ident($name); };   # mixed-case names, quoted on PostgreSQL
$ago = function ($years) { return gmdate('Y-m-d H:i:s', strtotime("-$years year")); };

$select = "SELECT wap.".$q('AP_ID').", wap.".$q('BSSID').", wap.".$q('SSID').", wap.".$q('CHAN').", wap.".$q('AUTH').", wap.".$q('ENCR')
	.", wap.".$q('SECTYPE').", wap.".$q('RADTYPE').", wap.".$q('NETTYPE').", wap.".$q('BTX').", wap.".$q('OTX').", wap.fa, wap.la"
	.", wGPS.".$q('Lat')." As ".$q('Lat').", wGPS.".$q('Lon')." As ".$q('Lon').", wGPS.".$q('Alt')." As ".$q('Alt').", wf.file_user"
	." FROM wifi_ap AS wap"
	." LEFT JOIN wifi_gps AS wGPS ON wGPS.".$q('GPS_ID')." = wap.".$q('HighGps_ID')
	." LEFT JOIN files AS wf ON wf.id = wap.".$q('File_ID')
	." WHERE wGPS.".$q('Lat')." IS NOT NULL AND wGPS.".$q('Lat')." != '0.0000'";
# file => [the age condition, its bound dates]
$exports = array(
	"WifiDB_0to1year.json" => array(" AND wap.la >= ?", array($ago(1))),
	"WifiDB_1to2year.json" => array(" AND wap.la >= ? AND wap.la < ?", array($ago(2), $ago(1))),
	"WifiDB_2to3year.json" => array(" AND wap.la >= ? AND wap.la < ?", array($ago(3), $ago(2))),
	"WifiDB_Legacy.json"   => array(" AND wap.la < ?", array($ago(3))),
);
$row_count = 100000;

foreach($exports as $filename => $bucket)
{
	list($where, $dates) = $bucket;
	echo "$filename\n";
	$Import_Map_Data = "";
	for($offset = 0; ; $offset += $row_count)
	{
		$prep = $dbcore->sql->conn->prepare($select.$where." ORDER BY wap.".$q('AP_ID')." ".$dbcore->sql->limit($offset, $row_count));
		$prep->execute($dates);
		$appointer = $prep->fetchAll(PDO::FETCH_ASSOC);
		foreach($appointer as $ap)
		{
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
				"FA" => $ap['fa'],
				"LA" => $ap['la'],
				"lat" => $dbcore->convert->dm2dd($ap['Lat']),
				"lon" => $dbcore->convert->dm2dd($ap['Lon']),
				"alt" => $ap['Alt'],
				"manuf" => $dbcore->findManuf($ap['BSSID']),
				"user" => $ap['file_user']
			);
			if($Import_Map_Data !== ''){$Import_Map_Data .= ',';}
			$Import_Map_Data .= $dbcore->createGeoJSON->CreateApFeature($ap_info, 1);
		}
		echo count($appointer).'-';
		if(count($appointer) < $row_count) {break;}
	}
	echo "\n";
	$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
	file_put_contents($daemon_config['wifidb_install'].'out/geojson/'.$filename, $results);
}
