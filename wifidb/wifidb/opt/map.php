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

$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);
switch($func)
{
	case "wifidbmap":
		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$style = "https://omt.wifidb.net/styles/WifiDB_NE2/style.json";
		$centerpoint =  "[-96.018674, 40.314893]";
		$zoom =  4;
		$layer_name =  "'WifiDB','WifiDB_Legacy'";
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map_wifidb.tpl');
		break;
		
	case "user_list":
		$row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
		$sql = "SELECT * FROM `user_imports` WHERE `id` = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $row, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$fetch = $prep->fetch();
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);
		$ListGeoJSON = $dbcore->export->UserListGeoJSON($fetch['points'], 0);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($ListGeoJSON['latlongarray']);

		$ml = $dbcore->createGeoJSON->CreateListMapLayer($row);
		$layer_source_all .= $ml['layer_source'];
		$layer_name .= $ml['layer_name'];			

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$style = "https://omt.wifidb.net/styles/NE2/style.json";
		$centerpoint =  "[".$Center_LatLon['long'].",".$Center_LatLon['lat']."]";
		$zoom = 9;

		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
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

		$ml = $dbcore->createGeoJSON->CreateApMapLayer($id);
		$layer_source_all .= $ml['layer_source'];
		$layer_name .= $ml['layer_name'];			

		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$style = "https://omt.wifidb.net/styles/osm-bright/style.json";
		$centerpoint =  "[".$dbcore->convert->dm2dd($latlng['long']).",".$dbcore->convert->dm2dd($latlng['lat'])."]";
		$zoom = 12;
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map.tpl');
		break;
}
?>
