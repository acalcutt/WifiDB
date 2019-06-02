<?php
/*
geojsond.php, WiFiDB GeoJson Daemon
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->daemon_name	=	"GeoJson";
$dbcore->lastedit		=	"2019-03-16";
$dbcore->daemon_version =	"1.0";

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$pid_filename = 'geojsond_'.$dbcore->This_is_me.'_'.date("YmdHis").'.pid';
$dbcore->pid_file = $dbcore->pid_file_loc.$pid_filename;

if(!file_exists($dbcore->pid_file_loc))
{
	if(!mkdir($dbcore->pid_file_loc))
	{
		#throw new ErrorException("Could not make WiFiDB PID folder. ($dbcore->pid_file_loc)");
		echo "Could not create PID Folder at path: $dbcore->pid_file_loc \n";
		exit(-4);
	}
}
if(file_put_contents($dbcore->pid_file, $dbcore->This_is_me) === FALSE)
{
	echo "Could not write pid file ($dbcore->pid_file), that's not good... >:[\n";
	exit(-5);
}
echo "
WiFiDB ".$dbcore->ver_array['wifidb']." - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$dbcore->lastedit}, GPLv2
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level."\n";

$currentrun = date("Y-m-d G:i:s");

if($dbcore->sql->service == "mysql")
	{
		$exports = [
			["WifiDB_Legacy.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la < DATE_SUB('$currentrun',INTERVAL 3 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_2to3year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 3 YEAR) AND wap.la < DATE_SUB('$currentrun',INTERVAL 2 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_1to2year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 2 YEAR) AND wap.la < DATE_SUB('$currentrun',INTERVAL 1 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_0to1year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 1 YEAR) AND wap.la < DATE_SUB('$currentrun',INTERVAL 1 MONTH) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_monthly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 1 MONTH) AND wap.la < DATE_SUB('$currentrun',INTERVAL 1 WEEK) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_weekly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 1 WEEK) ORDER BY wap.AP_ID LIMIT ?,?"]
		];
	}
else if($dbcore->sql->service == "sqlsrv")
	{
		$exports = [
			["WifiDB_Legacy.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.[user] FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la < dateadd(year, -3, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_2to3year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.[user] FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(year, -3, '$currentrun') AND wap.la < dateadd(year, -2, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_1to2year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.[user] FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(year, -2, '$currentrun') AND wap.la < dateadd(year, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_0to1year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.[user] FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(year, -1, '$currentrun') AND wap.la < dateadd(month, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_monthly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.[user] FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(month, -1, '$currentrun') AND wap.la < dateadd(week, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_weekly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.[user] FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(week, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"]
		];
	}

foreach ($exports as list($filename, $sql)) {
    echo "\r\nfilename: $filename; sql: $sql; \r\n";
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
			"FA" => $ap['fa'],
			"LA" => $ap['la'],
			"points" => $ap['points'],
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
		echo $number_of_rows."\r\n";
		if ($number_of_rows !== $row_count) {break;}
	}
	$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
	#echo json_encode($geojson, JSON_NUMERIC_CHECK);
	$fp = fopen($daemon_config['wifidb_install'].'out/geojson/'.$filename, 'w');
	fwrite($fp, $results);
	fclose($fp);
}

unlink($dbcore->pid_file);