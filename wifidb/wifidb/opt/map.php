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

if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels in kml file. by default labels are not shown.
$func=$_REQUEST['func'];
switch($func)
{
	case "wifidbmap":
		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_NE2/style.json";
		$centerpoint =  "[-95.712891, 37.090240]";
		$zoom =  3.5;
		$layer_source_all = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00b33c","#e68a00","#cc0000",2.5,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00e64d","#ff9900","#e60000",2.75,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#1aff66","#ffad33","#ff1a1a",3,1,0.5,"visible");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","{ssid}","Open Sans Regular",10,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","{ssid}","Open Sans Regular",10,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","{ssid}","Open Sans Regular",10,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","{ssid}","Open Sans Regular",10,"visible");
		}		

		$layer_name =  "'WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map_wifidb.tpl');
		break;
		
	case "user_list":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$labeled = (int)($_REQUEST['labeled'] ? $_REQUEST['labeled']: 0);
		$sql = "SELECT * FROM `user_imports` WHERE `id` = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$fetch = $prep->fetch();
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);
		$ListGeoJSON = $dbcore->export->UserListGeoJSON($fetch['points'], 0);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($ListGeoJSON['latlongarray']);		

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_NE2/style.json";
		$centerpoint =  "[".$Center_LatLon['long'].",".$Center_LatLon['lat']."]";
		$zoom = 9;
		
		$layer_source_all = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","{ssid}","Open Sans Regular",10,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","{ssid}","Open Sans Regular",10,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","{ssid}","Open Sans Regular",10,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","{ssid}","Open Sans Regular",10,"none");
		}
		
		$ml = $dbcore->createGeoJSON->CreateListGeoJsonLayer($id, $labeled);
		$layer_source_all .= $ml['layer_source'];
		$layer_name = "'".$ml['layer_name']."','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	

		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('list', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
	case "exp_ap":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$sql = "SELECT `lat`,`long` FROM `wifi_pointers` WHERE `id` = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$latlng = $prep->fetch();



		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_NE2/style.json";
		$centerpoint =  "[".$dbcore->convert->dm2dd($latlng['long']).",".$dbcore->convert->dm2dd($latlng['lat'])."]";
		$zoom = 12;
		
		$layer_source_all = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","{ssid}","Open Sans Regular",10,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","{ssid}","Open Sans Regular",10,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","{ssid}","Open Sans Regular",10,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","{ssid}","Open Sans Regular",10,"none");
		}
		
		$ml = $dbcore->createGeoJSON->CreateApGeoJsonLayer($id, $labeled);
		$layer_source_all .= $ml['layer_source'];
		$layer_name = "'".$ml['layer_name']."','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('list', 0);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
}
?>
