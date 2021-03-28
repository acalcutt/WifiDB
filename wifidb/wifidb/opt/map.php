<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
fetch.php, fetches a single AP's details.
Copyright (C) 2018 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "export");

include('../lib/init.inc.php');

if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels on map. by default labels are not shown.
if((int)@$_REQUEST['channels'] === 1){$channels = 1;}else{$channels = 0;}#Show AP labels on map. by default labels are not shown.
if((int)@$_REQUEST['signal'] === 1){$signal = 1;}else{$signal = 0;}#Show AP signals on map. by default labels are not shown.
if((int)@$_REQUEST['rssi'] === 1){$rssi = 1;}else{$rssi = 0;}#Show AP rssi on map. by default labels are not shown.

$latitude = filter_input(INPUT_GET, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$longitude = filter_input(INPUT_GET, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$zoom = filter_input(INPUT_GET, 'zoom', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$bearing = filter_input(INPUT_GET, 'bearing', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$pitch = filter_input(INPUT_GET, 'pitch', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

$func=$_REQUEST['func'];
$dbcore->smarty->assign('func', $func);
switch($func)
{
	case "wifidbmap":
		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$wifidb_meta_header .= '<script src="https://omt.wifidb.net/mapbox-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl-inspect.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		if (empty($latitude)){$latitude = 37.090240;}
		if (empty($longitude)){$longitude = -95.009766;}
		if (empty($zoom)){$zoom = 2;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$layer_source_all = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"visible");
		$cell_layer_name = "'cell_networks'";
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00b33c","#e68a00","#cc0000",2.5,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00e64d","#ff9900","#e60000",2.75,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#1aff66","#ffad33","#ff1a1a",3,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#1aff66","#ffad33","#ff1a1a",3,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#1aff66","#ffad33","#ff1a1a",3,1,0.5,"visible");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","label","{ssid}","Open Sans Regular",11,"visible");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","channel","{chan}","Open Sans Regular",11,"visible");
		}			

		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer();
		$layer_source_all .= $dl['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{chan}","Open Sans Regular",11,"none");}
		
		$ll = $dbcore->createGeoJSON->CreateLatestGeoJsonLayer();
		$layer_source_all .= $ll['layer_source'];
		if ($channels) 
			{$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","label","{chan}","Open Sans Regular",11,"visible");}
		else
			{$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","label","{ssid}","Open Sans Regular",11,"visible");}

	
		$layer_name = "'".$ll['layer_name']."','".$dl['layer_name']."','WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('wifidbmap', 1);
		$dbcore->smarty->assign('default_hidden', 0);	
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
	case "user_all":
		$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$limit	=	filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		if ($from == ""){$from = 0;}		
		if($latitude == "")
		{
			if($dbcore->sql->service == "mysql")
				{
					$sql = "SELECT TOP 1 `Lat`, `Lon`\n"
						. "FROM `wifi_gps`\n"
						. "WHERE\n"
						. "	File_ID IN (SELECT TOP (1) `id` FROM `files` WHERE `ValidGPS` = 1 AND `user` LIKE ? ORDER BY `date` DESC) And `Lat` != '0.0000'\n"
						. "ORDER BY `GPS_Date` DESC";
				}
			else if($dbcore->sql->service == "sqlsrv")
				{
					$sql = "SELECT TOP 1 Lat, Lon\n"
						. "FROM wifi_gps\n"
						. "WHERE\n"
						. "	File_ID IN (SELECT TOP (1) id FROM files WHERE ValidGPS = 1 AND [user] LIKE ? ORDER BY date DESC) And Lat != '0.0000'\n"
						. "ORDER BY GPS_Date DESC";
				}
			$result = $dbcore->sql->conn->prepare($sql);
			$result->bindParam(1, $user, PDO::PARAM_STR);
			$result->execute();
			$newArray = $result->fetch(2);
			$latitude = $dbcore->convert->dm2dd($newArray['Lat']);
			$longitude = $dbcore->convert->dm2dd($newArray['Lon']);
		}
		if (empty($zoom)){$zoom = 5;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}
		$centerpoint =  "[".$longitude.",".$latitude."]";

		if($limit == "")
		{
			if ($limit == ""){$limit = 100000;}
			if($dbcore->sql->service == "mysql")
				{
					$sql = "SELECT Count(AP_ID) As ap_count\n"
						. "FROM wifi_ap\n"
						. "WHERE\n"
						. "	File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND [user] LIKE ?)";
				}
			else if($dbcore->sql->service == "sqlsrv")
				{
					$sql = "SELECT Count(AP_ID) As ap_count\n"
						. "FROM wifi_ap\n"
						. "WHERE\n"
						. "	File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND [user] LIKE ?)";
				}
			$result = $dbcore->sql->conn->prepare($sql);
			$result->bindParam(1, $user, PDO::PARAM_STR);
			$result->execute();
			$newArray = $result->fetch(2);
			$ap_count = $newArray['ap_count'];
			if($ap_count > $limit)
			{
				$ldivs = ceil($ap_count / $limit);
				$dbcore->smarty->assign('labeled', $labeled);
				$dbcore->smarty->assign('user', $user);
				$dbcore->smarty->assign('limit', $limit);
				$dbcore->smarty->assign('count', $ap_count);
				$dbcore->smarty->assign('ldivs', $ldivs);
				$dbcore->smarty->assign('clat', $latitude);
				$dbcore->smarty->assign('clon', $longitude);
				$dbcore->smarty->display('map_segments.tpl');
				break;
			}
		}		

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$wifidb_meta_header .= '<script src="https://omt.wifidb.net/mapbox-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl-inspect.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		$layer_source_all = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$cell_layer_name = "'cell_networks'";
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","label","{ssid}","Open Sans Regular",11,"none");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","channel","{chan}","Open Sans Regular",11,"none");
		}		

		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dl['layer_source'];	
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{chan}","Open Sans Regular",11,"none");;}
		
		$ll = $dbcore->createGeoJSON->CreateLatestGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $ll['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","channel","{chan}","Open Sans Regular",11,"none");;}
		
		$ml = $dbcore->createGeoJSON->CreateUserAllGeoJsonLayer($user, $labeled, $from, $limit);
		$layer_source_all .= $ml['layer_source'];
		$layer_name = "'".$ml['layer_name']."','".$dl['layer_name']."','WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	

		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('list', 1);
		$dbcore->smarty->assign('default_hidden', 1);	
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');

		break;
	case "user_list":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `title` FROM `files` WHERE `id` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [title] FROM [files] WHERE [id] = ?";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$fetch = $prep->fetch();
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);
		$ListGeoJSON = $dbcore->export->UserListGeoJSON($id, 0);
		
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($ListGeoJSON['latlongarray']);		

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$wifidb_meta_header .= '<script src="https://omt.wifidb.net/mapbox-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl-inspect.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		if (empty($latitude)){$latitude = $Center_LatLon['lat'];}
		if (empty($longitude)){$longitude = $Center_LatLon['long'];}
		if (empty($zoom)){$zoom = 9;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$zoom = 9;
		$layer_source_all = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$cell_layer_name = "'cell_networks'";
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","label","{ssid}","Open Sans Regular",11,"none");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","channel","{chan}","Open Sans Regular",11,"none");
		}		

		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dl['layer_source'];	
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{chan}","Open Sans Regular",11,"none");;}

		$ll = $dbcore->createGeoJSON->CreateLatestGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $ll['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","channel","{chan}","Open Sans Regular",11,"none");;}
		
		$ml = $dbcore->createGeoJSON->CreateListGeoJsonLayer($id, $labeled);
		$layer_source_all .= $ml['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ml['source_name'],"","label","{ssid}","Open Sans Regular",11,"visible");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ml['source_name'],"","channel","{chan}","Open Sans Regular",11,"visible");;}
		
		$layer_name = "'".$ml['layer_name']."','".$dl['layer_name']."','WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	

		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('list', 1);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
	case "exp_ap":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT `wifi_gps`.`Lat`, `wifi_gps`.`Lon`\n"
					. "FROM `wifi_ap`\n"
					. "LEFT JOIN `wifi_gps` ON `wifi_ap`.`HighGps_ID` = `wifi_gps`.`GPS_ID`\n"
					. "WHERE `wifi_ap`.`AP_ID` = ?";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wifi_gps].[Lat], [wifi_gps].[Lon]\n"
					. "FROM [wifi_ap]\n"
					. "LEFT JOIN [wifi_gps] ON [wifi_ap].[HighGps_ID] = [wifi_gps].[GPS_ID]\n"
					. "WHERE [wifi_ap].[AP_ID] = ?";
			}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$latlng = $prep->fetch();



		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$wifidb_meta_header .= '<script src="https://omt.wifidb.net/mapbox-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl-inspect.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		
		if (empty($latitude)){$latitude = $dbcore->convert->dm2dd($latlng['Lat']);}
		if (empty($longitude)){$longitude = $dbcore->convert->dm2dd($latlng['Lon']);}
		if (empty($zoom)){$zoom = 12;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";

		$layer_source_all = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$cell_layer_name = "'cell_networks'";
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","label","{ssid}","Open Sans Regular",11,"none");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","channel","{chan}","Open Sans Regular",11,"none");
		}		
		
		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{chan}","Open Sans Regular",11,"none");;}
		$layer_source_all .= $dl['layer_source'];

		$ll = $dbcore->createGeoJSON->CreateLatestGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $ll['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","channel","{chan}","Open Sans Regular",11,"none");;}
		
		$ml = $dbcore->createGeoJSON->CreateApGeoJsonLayer($id);
		$layer_source_all .= $ml['layer_source'];
		if ($labeled) {$layer_source .= $this->CreateLabelLayer($ml['source_name']);}
		$layer_name = "'".$ml['layer_name']."','".$dl['layer_name']."','WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('list', 0);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;

	case "exp_ap_sig":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$list_id = (int)($_REQUEST['list_id'] ? $_REQUEST['list_id']: 0);
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT `wifi_gps`.`Lat`, `wifi_gps`.`Lon`\n"
					. "FROM `wifi_ap`\n"
					. "LEFT JOIN `wifi_gps` ON `wifi_ap`.`HighGps_ID` = `wifi_gps`.`GPS_ID`\n"
					. "WHERE `wifi_ap`.`AP_ID` = ?";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wifi_gps].[Lat], [wifi_gps].[Lon]\n"
					. "FROM [wifi_ap]\n"
					. "LEFT JOIN [wifi_gps] ON [wifi_ap].[HighGps_ID] = [wifi_gps].[GPS_ID]\n"
					. "WHERE [wifi_ap].[AP_ID] = ?";
			}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$latlng = $prep->fetch();

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$wifidb_meta_header .= '<script src="https://omt.wifidb.net/mapbox-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl-inspect.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		if (empty($latitude)){$latitude = $dbcore->convert->dm2dd($latlng['Lat']);}
		if (empty($longitude)){$longitude = $dbcore->convert->dm2dd($latlng['Lon']);}
		if (empty($zoom)){$zoom = 11;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$layer_source_all = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$cell_layer_name = "'cell_networks'";
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");

		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dl['layer_source'];

		$ll = $dbcore->createGeoJSON->CreateLatestGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $ll['layer_source'];

		$ml = $dbcore->createGeoJSON->CreateApSigGeoJsonLayer($id, $list_id);
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ml['source_name'],"","signal","{signal}","Open Sans Regular",11,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ml['source_name'],"","rssi","{rssi}","Open Sans Regular",11,"none");
		$layer_name = "'".$ml['layer_name']."','".$dl['layer_name']."','WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('signal', $signal);
		$dbcore->smarty->assign('rssi', $rssi);
		$dbcore->smarty->assign('list', 0);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('list_id', $list_id);
		$dbcore->smarty->assign('signal_source_name', $ml['source_name']);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
		
	case "exp_live_ap":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$sql = "SELECT lat, long FROM live_aps WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$latlng = $prep->fetch();

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$wifidb_meta_header .= '<script src="https://omt.wifidb.net/mapbox-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl-inspect.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";

		if (empty($latitude)){$latitude = $dbcore->convert->dm2dd($latlng['lat']);}
		if (empty($longitude)){$longitude = $dbcore->convert->dm2dd($latlng['long']);}
		if (empty($zoom)){$zoom = 12;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$layer_source_all = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$cell_layer_name = "'cell_networks'";
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","label","{ssid}","Open Sans Regular",11,"none");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","channel","{chan}","Open Sans Regular",11,"none");
		}		
		
		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{chan}","Open Sans Regular",11,"none");;}
		$layer_source_all .= $dl['layer_source'];

		$ll = $dbcore->createGeoJSON->CreateLatestGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $ll['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","channel","{chan}","Open Sans Regular",11,"none");;}
		
		$ml = $dbcore->createGeoJSON->CreateLiveApGeoJsonLayer($id, $labeled);
		$layer_source_all .= $ml['layer_source'];
		$layer_name = "'".$ml['layer_name']."','".$dl['layer_name']."','WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('list', 0);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
		
	case "exp_search":
		define("SWITCH_EXTRAS", "export");
		$ord	=   filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
		$sort   =	filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$inc	=	filter_input(INPUT_GET, 'to', FILTER_SANITIZE_NUMBER_INT);

		if(@$_REQUEST['ssid']){$ssid = $_REQUEST['ssid'];}else{$ssid = "";}
		if(@$_REQUEST['mac']){$mac = $_REQUEST['mac'];}else{$mac = "";}
		if(@$_REQUEST['radio']){$radio = $_REQUEST['radio'];}else{$radio = "";}	
		if(@$_REQUEST['chan']){$chan = $_REQUEST['chan'];}else{$chan = "";}
		if(@$_REQUEST['auth']){$auth = $_REQUEST['auth'];}else{$auth = "";}
		if(@$_REQUEST['encry']){$encry = $_REQUEST['encry'];}else{$encry =  "";}
		if(@$_REQUEST['sectype']){$sectype = $_REQUEST['sectype'];}else{$sectype =  "";}

		if ($from == ""){$from = NULL;}
		if ($inc == ""){$inc = NULL;}
		if ($ord == ""){$ord = "ASC";}
		if ($sort == ""){$sort = "ssid";}
		
		list($total_rows, $results_all, $save_url, $export_url) = $dbcore->export->Search($ssid, $mac, $radio, $chan, $auth, $encry, $sectype, $ord, $sort, $from, $inc, 1);

		#Get Center GPS
		$latlon_array = array();
		foreach($results_all as $ap) 
		{
			$latlon_info = array(
			"lat" => $dbcore->convert->dm2dd($ap['Lat']),
			"long" => $dbcore->convert->dm2dd($ap['Lon']),
			);
			$latlon_array[] = $latlon_info;
		}
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($latlon_array);

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$wifidb_meta_header .= '<script src="https://omt.wifidb.net/mapbox-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl-inspect.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		if (empty($latitude)){$latitude = $dbcore->convert->dm2dd($Center_LatLon['lat']);}
		if (empty($longitude)){$longitude = $dbcore->convert->dm2dd($Center_LatLon['long']);}
		if (empty($zoom)){$zoom = 9;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$layer_source_all = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$cell_layer_name = "'cell_networks'";
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","label","{ssid}","Open Sans Regular",11,"none");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_monthly","channel","{chan}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_weekly","channel","{chan}","Open Sans Regular",11,"none");
		}		

		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dl['layer_source'];	
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['source_name'],"","label","{chan}","Open Sans Regular",11,"none");;}

		$ll = $dbcore->createGeoJSON->CreateLatestGeoJsonLayer("#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $ll['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ll['source_name'],"","channel","{chan}","Open Sans Regular",11,"none");;}
		
		$ml = $dbcore->createGeoJSON->CreateSearchGeoJsonLayer($export_url);
		$layer_source_all .= $ml['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ml['source_name'],"","label","{ssid}","Open Sans Regular",11,"visible");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($ml['source_name'],"","channel","{chan}","Open Sans Regular",11,"visible");;}
		
		$layer_name = "'".$ml['layer_name']."','".$ll['layer_name']."','".$dl['layer_name']."','WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	

		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('search', 1);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('export_url', $export_url);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
}
?>
