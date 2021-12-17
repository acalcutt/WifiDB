<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
Copyright (C) 2021 Andrew Calcutt

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


$ua = htmlentities($_SERVER['HTTP_USER_AGENT'], ENT_QUOTES, 'UTF-8');
if (preg_match('~MSIE|Internet Explorer~i', $ua) || (strpos($ua, 'Trident/7.0; rv:11.0') !== false)) {
	$ie = 1;
//	$terrain = 1;
//	$wifidb_meta_header = '<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.ie.js"></script><link rel="stylesheet" type="text/css" href="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.css" />';
//	$wifidb_meta_header .= '<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.ie.min.js"></script><link rel="stylesheet" type="text/css" href="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.css" />';
}else{
	$ie = 0;
//	$terrain = 1;
//	$wifidb_meta_header = '<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.js"></script><link rel="stylesheet" type="text/css" href="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.css" />';
//	$wifidb_meta_header .= '<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.min.js"></script><link rel="stylesheet" type="text/css" href="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.css" />';
}


$terrain = 1;
//$wifidb_meta_header = '<script>if (typeof Symbol !== \'undefined\') {document.write(\'<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.es6.js"><\/script>\')}else{document.write(\'<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.es5.js"><\/script>\')}</script>';
//$wifidb_meta_header .= '<script>if (typeof Symbol !== \'undefined\') {document.write(\'<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.min.js"><\/script>\')}else{document.write(\'<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.ie.min.js"><\/script>\')}</script>';
$wifidb_meta_header = '<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.es5.js"></script><link rel="stylesheet" type="text/css" href="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.css" />';
$wifidb_meta_header .= '<link rel="stylesheet" type="text/css" href="'.$dbcore->tileserver_gl_url.'/maplibre-gl2.css" />';
$wifidb_meta_header .= '<script src="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.ie.min.js"></script><link rel="stylesheet" type="text/css" href="'.$dbcore->tileserver_gl_url.'/maplibre-gl-inspect.css" />';


$dbcore->smarty->assign('ie', $ie);
$dbcore->smarty->assign('terrain', $terrain);
$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);

$latitude = filter_input(INPUT_GET, 'latitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$longitude = filter_input(INPUT_GET, 'longitude', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$zoom = filter_input(INPUT_GET, 'zoom', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$bearing = filter_input(INPUT_GET, 'bearing', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$pitch = filter_input(INPUT_GET, 'pitch', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

$style = filter_input(INPUT_GET, 'style', FILTER_SANITIZE_STRING);
$styles = array("WDB_OSM","WDB_DARK_MATTER","WDB_BASIC","WDB_ELEV");
if(!in_array($style, $styles)){$style = "WDB_OSM";}

$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
if(!is_numeric($from)){$from = 0;}
if(!is_numeric($inc)){$inc = 50000;}
$dbcore->smarty->assign('from', $from);
$dbcore->smarty->assign('inc', $inc);

$func=$_REQUEST['func'];
$dbcore->smarty->assign('func', $func);
switch($func)
{
	case "wifidbmap":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}
		
		if (empty($latitude)){$latitude = 37.090240;}
		if (empty($longitude)){$longitude = -95.009766;}
		if (empty($zoom)){$zoom = 2;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$layer_cell = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"visible");
		$layer_legacy = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",3,1,0.5,"visible");
		$layer_2_3 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00b33c","#e68a00","#cc0000",2.75,1,0.5,"visible");
		$layer_1_2 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00e64d","#ff9900","#e60000",2.5,1,0.5,"visible");
		$layer_0_1 = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#1aff66","#ffad33","#ff1a1a",2,1,0.5,"visible");
		$layer_monthly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#1aff66","#ffad33","#ff1a1a",2,1,0.5,"visible");
		$layer_weekly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#1aff66","#ffad33","#ff1a1a",2,1,0.5,"visible");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];

		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer("WifiDB_cells", "cell_networks", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_Legacy", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_2to3year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_1to2year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_0to1year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_monthly", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_weekly", "Open Sans Regular", 10, "none");

		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateApLayer($dgs['layer_name']);
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($dgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateApLayer($lgs['layer_name']);
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($lgs['layer_name'],"","latest","{ssid}","Open Sans Regular",10,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lgs['layer_name'],"", "Open Sans Regular", 10, "none");


		$layer_name = "'".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('wifidbmap', 1);
		$dbcore->smarty->assign('default_hidden', 0);
		$dbcore->smarty->display('map.tpl');
		break;
	case "heatmap":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}
		
		if (empty($latitude)){$latitude = 37.090240;}
		if (empty($longitude)){$longitude = -95.009766;}
		if (empty($zoom)){$zoom = 2;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$layer_cell = $dbcore->createGeoJSON->CreateHeatMapLayer("WifiDB_cells","cell_networks","visible");
		$layer_legacy = $dbcore->createGeoJSON->CreateHeatMapLayer("WifiDB","WifiDB_Legacy","visible");
		$layer_2_3 = $dbcore->createGeoJSON->CreateHeatMapLayer("WifiDB","WifiDB_2to3year","visible");
		$layer_1_2 = $dbcore->createGeoJSON->CreateHeatMapLayer("WifiDB","WifiDB_1to2year","visible");
		$layer_0_1 = $dbcore->createGeoJSON->CreateHeatMapLayer("WifiDB_newest","WifiDB_0to1year","visible");
		$layer_monthly = $dbcore->createGeoJSON->CreateHeatMapLayer("WifiDB_newest","WifiDB_monthly","visible");
		$layer_weekly = $dbcore->createGeoJSON->CreateHeatMapLayer("WifiDB_newest","WifiDB_weekly","visible");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];



		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateHeatMapLayer($dgs['layer_name']);
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateHeatMapLayer($lgs['layer_name']);
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];



		$layer_name = "'".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('wifidbmap', 1);
		$dbcore->smarty->assign('default_hidden', 0);
		$dbcore->smarty->display('map.tpl');
		break;
	case "user_all":
		$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}

		#Get Point count and division count
		$sql = "SELECT Count(AP_ID) As point_count\n"
			. "FROM wifi_ap\n"
			. "WHERE\n"
			. "	File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND file_user LIKE ?)";
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$newArray = $result->fetch(2);
		$point_count = $newArray['point_count'];
		if($point_count > $inc){$ldivs = ceil($point_count / $inc);}else{$ldivs = 1;}

		#Get the first point in the results
		if($latitude == "" && $longitude== "")
		{
			$UserAllList = $dbcore->export->UserAllArray($user, $from, 1, 0);
			$latlongarray = $UserAllList['latlongarray'];
			$latitude = $latlongarray[0]['lat'];
			$longitude = $latlongarray[0]['long'];
		}
		
		#Set Default View
		if (empty($zoom)){$zoom = 3;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";

		#Create Map Layers
		$layer_cell = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$layer_legacy = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_2_3 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00b33c","#e68a00","#cc0000",2.75,1,0.5,"none");
		$layer_1_2 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00e64d","#ff9900","#e60000",2.5,1,0.5,"none");
		$layer_0_1 = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#1aff66","#ffad33","#ff1a1a",2,1,0.5,"none");
		$layer_monthly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#1aff66","#ffad33","#ff1a1a",2,1,0.5,"none");
		$layer_weekly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#1aff66","#ffad33","#ff1a1a",2,1,0.5,"none");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];
		
		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer("WifiDB_cells", "cell_networks", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_Legacy", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_2to3year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_1to2year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_0to1year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_monthly", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_weekly", "Open Sans Regular", 10, "none");

		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateApLayer($dgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($dgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateApLayer($lgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($lgs['layer_name'],"","latest","{ssid}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$uags = $dbcore->createGeoJSON->CreateUserAllGeoJsonSource($user, $from, $inc);
		$ml = $dbcore->createGeoJSON->CreateApLayer($uags['layer_name']);
		
		$layer_source_all .= $uags['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($uags['layer_name'],"", "Open Sans Regular", 10, "none");

		$layer_name = "'".$uags['layer_name']."','".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('default_hidden', 1);	
		$dbcore->smarty->assign('user', $user);
		$dbcore->smarty->assign('point_count', $point_count);
		$dbcore->smarty->assign('ldivs', $ldivs);
		$dbcore->smarty->assign('title', $title);
		$dbcore->smarty->display('map.tpl');

		break;
	case "user_list":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);

		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}

		#Get Point count and division count
		$sql = "SELECT Count(DISTINCT wifi_hist.AP_ID) AS point_count\n"
			. "From wifi_hist\n"
			. "LEFT JOIN wifi_ap AS wap ON wifi_hist.AP_ID = wap.AP_ID\n"
			. "WHERE wifi_hist.File_ID = ? AND wap.HighGps_ID IS NOT NULL";
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $id, PDO::PARAM_INT);
		$result->execute();
		$newArray = $result->fetch(2);
		$point_count = $newArray['point_count'];
		if($point_count > $inc){$ldivs = ceil($point_count / $inc);}else{$ldivs = 1;}

		#Get File Title
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

		#Get the first point in the results
		if($latitude == "" && $longitude== "")
		{
			$UserListArray = $dbcore->export->UserListArray($id, $from, 1, "AP_ID", "DESC", 0, 0, 0, 1);
			$latlongarray = $UserListArray['latlongarray'];
			$latitude = $latlongarray[0]['lat'];
			$longitude = $latlongarray[0]['long'];
		}
		if($latitude == "" && $longitude== "")
		{
			$CellUserListArray = $dbcore->export->CellUserListArray($id, $from, 1, "cell_id", "DESC", 0, 0, 0, 1, "'BT','BLE'");
			$latlongarray = $CellUserListArray['latlongarray'];
			$latitude = $latlongarray[0]['lat'];
			$longitude = $latlongarray[0]['long'];
		}

		#Set Default View
		if (empty($zoom)){$zoom = 9;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";

		$layer_cell = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$layer_legacy = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_2_3 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_1_2 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_0_1 = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_monthly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_weekly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];

		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer("WifiDB_cells", "cell_networks", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_Legacy", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_2to3year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_1to2year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_0to1year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_monthly", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_weekly", "Open Sans Regular", 10, "none");
		
		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateApLayer($dgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($dgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateApLayer($lgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($lgs['layer_name'],"","latest","{ssid}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$clgs = $dbcore->createGeoJSON->CreateCellListGeoJsonSource($id, $from, $inc);
		$cl = $dbcore->createGeoJSON->CreateCellLayer($clgs['layer_name']);
		$layer_source_all .= $clgs['layer_source'];
		$layer_source_all .= $cl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer($clgs['layer_name'],"", "Open Sans Regular", 10, "none");

		
		$mlgs = $dbcore->createGeoJSON->CreateListGeoJsonSource($id, $from, $inc);
		$ml = $dbcore->createGeoJSON->CreateApLayer($mlgs['layer_name']);
		$layer_source_all .= $mlgs['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($mlgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$layer_name = "'".$mlgs['layer_name']."','".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$clgs['layer_name']."','".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('point_count', $point_count);
		$dbcore->smarty->assign('ldivs', $ldivs);
		$dbcore->smarty->assign('title', $title);
		$dbcore->smarty->display('map.tpl');
		break;
	case "exp_ap":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}
		
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT `wifi_gps`.`Lat`, `wifi_gps`.`Lon`, `wifi_ap`.`ssid`\n"
					. "FROM `wifi_ap`\n"
					. "LEFT JOIN `wifi_gps` ON `wifi_ap`.`HighGps_ID` = `wifi_gps`.`GPS_ID`\n"
					. "WHERE `wifi_ap`.`AP_ID` = ?";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wifi_gps].[Lat], [wifi_gps].[Lon], [wifi_ap].[ssid]\n"
					. "FROM [wifi_ap]\n"
					. "LEFT JOIN [wifi_gps] ON [wifi_ap].[HighGps_ID] = [wifi_gps].[GPS_ID]\n"
					. "WHERE [wifi_ap].[AP_ID] = ?";
			}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$apinfo = $prep->fetch();
		
		if (empty($latitude)){$latitude = $dbcore->convert->dm2dd($apinfo['Lat']);}
		if (empty($longitude)){$longitude = $dbcore->convert->dm2dd($apinfo['Lon']);}
		if (empty($zoom)){$zoom = 12;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";

		$layer_cell = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$layer_legacy = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_2_3 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_1_2 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_0_1 = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_monthly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_weekly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];

		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer("WifiDB_cells", "cell_networks", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_Legacy", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_2to3year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_1to2year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_0to1year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_monthly", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_weekly", "Open Sans Regular", 10, "none");
		
		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateApLayer($dgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($dgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateApLayer($lgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($lgs['layer_name'],"","latest","{ssid}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$ags = $dbcore->createGeoJSON->CreateApGeoJsonSource($id);
		$ml = $dbcore->createGeoJSON->CreateApLayer($ags['layer_name']);
		$layer_source_all .= $ags['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($ags['layer_name'],"", "Open Sans Regular", 10, "none");

		$layer_name = "'".$ags['layer_name']."','".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('ssid', $apinfo['SSID']);
		$dbcore->smarty->display('map.tpl');
		break;

	case "exp_ap_sig":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","signal","rssi","hist_date");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}
		
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$file_id = (int)($_REQUEST['file_id'] ? $_REQUEST['file_id']: 0);

		#Get Point count and division count
		$sql = "SELECT Count(wh.Hist_ID) As point_count\n"
			. "FROM wifi_hist AS wh\n"
			. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wh.GPS_ID\n";
		if($file_id)
			{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ? And wh.File_ID = ?\n";}
		else
			{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ?\n";}
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $id, PDO::PARAM_INT);
		if($file_id){$result->bindParam(2, $file_id, PDO::PARAM_INT);}
		$result->execute();
		$newArray = $result->fetch(2);
		$point_count = $newArray['point_count'];
		if($point_count > $inc){$ldivs = ceil($point_count / $inc);}else{$ldivs = 1;}

		#Get Marker Centerpoint
		$sql = "SELECT wifi_gps.Lat, wifi_gps.Lon, wifi_ap.SSID, wifi_ap.SECTYPE\n"
			. "FROM wifi_ap\n"
			. "LEFT JOIN wifi_gps ON wifi_ap.HighGps_ID = wifi_gps.GPS_ID\n"
			. "WHERE wifi_ap.AP_ID = ?";
		$prepc = $dbcore->sql->conn->prepare($sql);
		$prepc->bindParam(1, $id, PDO::PARAM_INT);
		$prepc->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$ap_center_info = $prepc->fetch();
		$default_marker =  "[".$dbcore->convert->dm2dd($ap_center_info['Lon']).",".$dbcore->convert->dm2dd($ap_center_info['Lat'])."]";

		#Get the first point in the results
		if($latitude == "" && $longitude== "")
		{
			$SigHistArray = $dbcore->export->SigHistArray($id, $file_id, $from, 1);
			$latlongarray = $SigHistArray['latlongarray'];
			$latitude = $latlongarray[0]['lat'];
			$longitude = $latlongarray[0]['long'];
		}

		if (empty($zoom)){$zoom = 14;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";

		$asgs = $dbcore->createGeoJSON->CreateApSignalGeoJsonSource($id, $file_id, $from, $inc);
		$ml = $dbcore->createGeoJSON->CreateApSigLayer($asgs['layer_name']);
		$layer_source_all .= $asgs['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($asgs['layer_name'],"","signal","{signal}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($asgs['layer_name'],"","rssi","{rssi}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($asgs['layer_name'],"","hist_date","{hist_date}","Open Sans Regular",10,"none");

		$layer_name = "'".$asgs['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('default_marker', $default_marker);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('ssid', dbcore::formatSSID($ap_center_info['SSID']));
		$dbcore->smarty->assign('sectype', $ap_center_info['SECTYPE']);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('file_id', $file_id);
		$dbcore->smarty->assign('point_count', $point_count);
		$dbcore->smarty->assign('ldivs', $ldivs);
		$dbcore->smarty->assign('signal_source_name', $asgs['layer_name']);
		$dbcore->smarty->display('map.tpl');
		break;

	case "exp_cid":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}
		
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);

		if($latitude == "" && $longitude== "")
		{
			$CellArray = $dbcore->export->CellArray($id);
			$latlongarray = $CellArray['latlongarray'];
			$latitude = $latlongarray[0]['lat'];
			$longitude = $latlongarray[0]['long'];
		}

		if (empty($zoom)){$zoom = 12;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";

		$layer_cell = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$layer_legacy = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_2_3 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_1_2 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_0_1 = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_monthly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_weekly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];

		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer("WifiDB_cells", "cell_networks", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_Legacy", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_2to3year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_1to2year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_0to1year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_monthly", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_weekly", "Open Sans Regular", 10, "none");
		
		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateApLayer($dgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($dgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateApLayer($lgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($lgs['layer_name'],"","latest","{ssid}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$ags = $dbcore->createGeoJSON->CreateCellGeoJsonSource($id);
		$ml = $dbcore->createGeoJSON->CreateCellLayer($ags['layer_name']);
		$layer_source_all .= $ags['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer($ags['layer_name'],"", "Open Sans Regular", 10, "none");

		$layer_name = "'".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$ags['layer_name']."','".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('ssid', $apinfo['SSID']);
		$dbcore->smarty->display('map.tpl');
		break;

	case "exp_cell_sig":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","rssi","hist_date");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}
		
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$file_id = (int)($_REQUEST['file_id'] ? $_REQUEST['file_id']: 0);

		#Get Point count and division count
		$sql = "SELECT Count(ch.cell_hist_id) As point_count\n"
			. "FROM cell_hist AS ch\n"
			. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = ch.gps_id\n";
		if($file_id)
			{$sql .= "WHERE wGPS.Lat <> '0.0000' AND ch.cell_id = ? And ch.file_id = ?\n";}
		else
			{$sql .= "WHERE wGPS.Lat <> '0.0000' AND ch.cell_id = ?\n";}
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $id, PDO::PARAM_INT);
		if($file_id){$result->bindParam(2, $file_id, PDO::PARAM_INT);}
		$result->execute();
		$newArray = $result->fetch(2);
		$point_count = $newArray['point_count'];
		if($point_count > $inc){$ldivs = ceil($point_count / $inc);}else{$ldivs = 1;}

		#Get Marker Centerpoint
		$sql = "SELECT wifi_gps.Lat, wifi_gps.Lon, cell_id.ssid\n"
			. "FROM cell_id\n"
			. "LEFT JOIN wifi_gps ON wifi_gps.GPS_ID = cell_id.highgps_id\n"
			. "WHERE cell_id.cell_id = ?";
		$prepc = $dbcore->sql->conn->prepare($sql);
		$prepc->bindParam(1, $id, PDO::PARAM_INT);
		$prepc->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$ap_center_info = $prepc->fetch();
		$default_marker =  "[".$dbcore->convert->dm2dd($ap_center_info['Lon']).",".$dbcore->convert->dm2dd($ap_center_info['Lat'])."]";
		
		#Get the last point in the results
		#Get the first point in the results
		if($latitude == "" && $longitude== "")
		{
			$CellSigHistArray = $dbcore->export->CellSigHistArray($id, $file_id, $from, 1);
			$latlongarray = $CellSigHistArray['latlongarray'];
			$latitude = $latlongarray[0]['lat'];
			$longitude = $latlongarray[0]['long'];
		}
		
		if (empty($zoom)){$zoom = 10;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";
		

		$asgs = $dbcore->createGeoJSON->CreateCellSignalGeoJsonSource($id, $file_id, $from, $inc);
		$ml = $dbcore->createGeoJSON->CreateCellSigLayer($asgs['layer_name']);
		$layer_source_all .= $asgs['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($asgs['layer_name'],"","rssi","{rssi}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($asgs['layer_name'],"","hist_date","{hist_date}","Open Sans Regular",10,"none");

		$cell_layer_name = "'".$asgs['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('default_marker', $default_marker);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('ssid', dbcore::formatSSID($ap_center_info['ssid']));
		$dbcore->smarty->assign('sectype', $ap_center_info['SECTYPE']);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('file_id', $file_id);
		$dbcore->smarty->assign('point_count', $point_count);
		$dbcore->smarty->assign('ldivs', $ldivs);
		$dbcore->smarty->assign('signal_source_name', $asgs['layer_name']);
		$dbcore->smarty->display('map.tpl');
		break;

	case "exp_live_ap":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}

		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$sql = "SELECT lat, long FROM live_aps WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$latlng = $prep->fetch();

		if (empty($latitude)){$latitude = $dbcore->convert->dm2dd($latlng['lat']);}
		if (empty($longitude)){$longitude = $dbcore->convert->dm2dd($latlng['long']);}
		if (empty($zoom)){$zoom = 12;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}	
		$centerpoint =  "[".$longitude.",".$latitude."]";
		$layer_cell = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$layer_legacy = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_2_3 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_1_2 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_0_1 = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_monthly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_weekly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];

		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer("WifiDB_cells", "cell_networks", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_Legacy", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_2to3year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_1to2year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_0to1year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_monthly", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_weekly", "Open Sans Regular", 10, "none");

		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateApLayer($dgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($dgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateApLayer($lgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($lgs['layer_name'],"","latest","{ssid}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lags = $dbcore->createGeoJSON->CreateLiveApGeoJsonSource($id);
		$ml = $dbcore->createGeoJSON->CreateApLayer($lags['layer_name']);
		$layer_source_all .= $lags['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lags['layer_name'],"", "Open Sans Regular", 10, "none");

		$layer_name = "'".$lags['layer_name']."','".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->display('map.tpl');
		break;
		
	case "exp_search":
		$sig_label = filter_input(INPUT_GET, 'sig_label', FILTER_SANITIZE_STRING);
		$sig_labels = array("none","ssid","chan","FA","LA","points","high_gps_sig","high_gps_rssi");
		if(!in_array($sig_label, $sig_labels)){$sig_label = "none";}
		
		define("SWITCH_EXTRAS", "export");
		$ord	=   filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
		$sort   =	filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);

		$sorts=array("AP_ID","SSID","mac","chan","radio","auth","encry","FA","LA","points","ModDate");
		if(!in_array($sort, $sorts)){$sort = "ModDate";}
		$ords=array("ASC","DESC");
		if(!in_array($ord, $ords)){$ord = "DESC";}

		if(@$_REQUEST['ssid']){$ssid = $_REQUEST['ssid'];}else{$ssid = "";}
		if(@$_REQUEST['mac']){$mac = $_REQUEST['mac'];}else{$mac = "";}
		if(@$_REQUEST['radio']){$radio = $_REQUEST['radio'];}else{$radio = "";}	
		if(@$_REQUEST['chan']){$chan = $_REQUEST['chan'];}else{$chan = "";}
		if(@$_REQUEST['auth']){$auth = $_REQUEST['auth'];}else{$auth = "";}
		if(@$_REQUEST['encry']){$encry = $_REQUEST['encry'];}else{$encry =  "";}
		if(@$_REQUEST['sectype']){$sectype = $_REQUEST['sectype'];}else{$sectype =  "";}

		$SearchArray = $dbcore->export->SearchArray($ssid, $mac, $radio, $chan, $auth, $encry, $sectype, $ord, $sort, $labeled, $new_icons, $from, 1, 1);
		$results_all = $SearchArray['data'];
		$ap_count = $SearchArray['count'];
		$point_count = $SearchArray['total_rows'];
		if($point_count > $inc){$ldivs = ceil($point_count / $inc);}else{$ldivs = 1;}
		$export_url = "&ssid=$ssid&mac=$mac&radio=$radio&chan=$chan&auth=$auth&encry=$encry&sectype=$sectype";
		

		if($latitude == "" && $longitude== "")
		{
			$latlongarray = $SearchArray['latlongarray'];
			$latitude = $latlongarray[0]['lat'];
			$longitude = $latlongarray[0]['long'];
		}
		if (empty($zoom)){$zoom = 9;}
		if (empty($bearing)){$bearing = 0;}
		if (empty($pitch)){$pitch = 0;}
		$centerpoint =  "[".$longitude.",".$latitude."]";
		
		$layer_cell = $dbcore->createGeoJSON->CreateCellLayer("WifiDB_cells","cell_networks","#885FCD",2.25,1,0.5,"none");
		$layer_legacy = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_2_3 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_1_2 = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_0_1 = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_monthly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_monthly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_weekly = $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_weekly","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all = $layer_cell['layer_source'];
		$layer_source_all .= $layer_legacy['layer_source'];
		$layer_source_all .= $layer_2_3['layer_source'];
		$layer_source_all .= $layer_1_2['layer_source'];
		$layer_source_all .= $layer_0_1['layer_source'];
		$layer_source_all .= $layer_monthly['layer_source'];
		$layer_source_all .= $layer_weekly['layer_source'];

		$layer_source_all .= $dbcore->createGeoJSON->CreateCellLabelLayer("WifiDB_cells", "cell_networks", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_Legacy", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_2to3year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB", "WifiDB_1to2year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_0to1year", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_monthly", "Open Sans Regular", 10, "none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer("WifiDB_newest", "WifiDB_weekly", "Open Sans Regular", 10, "none");
		
		$dgs = $dbcore->createGeoJSON->CreateDailyGeoJsonSource();
		$dl = $dbcore->createGeoJSON->CreateApLayer($dgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dgs['layer_source'];
		$layer_source_all .= $dl['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($dgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lgs = $dbcore->createGeoJSON->CreateLatestGeoJsonSource();
		$ll = $dbcore->createGeoJSON->CreateApLayer($lgs['layer_name'],"","#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $lgs['layer_source'];
		$layer_source_all .= $ll['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($lgs['layer_name'],"","latest","{ssid}","Open Sans Regular",10,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$lsgs = $dbcore->createGeoJSON->CreateSearchGeoJsonSource($export_url);
		$ml = $dbcore->createGeoJSON->CreateApLayer($lsgs['layer_name']);
		$layer_source_all .= $lsgs['layer_source'];
		$layer_source_all .= $ml['layer_source'];
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLabelLayer($lsgs['layer_name'],"", "Open Sans Regular", 10, "none");

		$layer_name = "'".$lsgs['layer_name']."','".$lgs['layer_name']."','".$dgs['layer_name']."','".$layer_weekly['layer_name']."','".$layer_monthly['layer_name']."','".$layer_0_1['layer_name']."','".$layer_1_2['layer_name']."','".$layer_2_3['layer_name']."','".$layer_legacy['layer_name']."'";
		$cell_layer_name = "'".$layer_cell['layer_name']."'";
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('cell_layer_name', $cell_layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('pitch', $pitch);
		$dbcore->smarty->assign('bearing', $bearing);
		$dbcore->smarty->assign('sig_label', $sig_label);
		$dbcore->smarty->assign('search', 1);
		$dbcore->smarty->assign('default_hidden', 1);
		$dbcore->smarty->assign('export_url', $export_url);
		$dbcore->smarty->assign('id', $id);
		$dbcore->smarty->assign('point_count', $point_count);
		$dbcore->smarty->assign('ldivs', $ldivs);
		$dbcore->smarty->display('map.tpl');
		break;
}
?>
