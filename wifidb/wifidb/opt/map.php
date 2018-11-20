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
$func=$_REQUEST['func'];
switch($func)
{
	case "wifidbmap":
		$wifidb_meta_header = '<script src="https://omt.wifidb.net/mapbox-gl.js"></script><link rel="stylesheet" type="text/css" href="https://omt.wifidb.net/mapbox-gl.css" />';
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		$centerpoint =  "[-95.712891, 37.090240]";
		$zoom =  3.5;
		$layer_source_all = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00b33c","#e68a00","#cc0000",2.5,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00e64d","#ff9900","#e60000",2.75,1,0.5,"visible");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#1aff66","#ffad33","#ff1a1a",3,1,0.5,"visible");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"visible");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"visible");
		}			

		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer($labeled);
		$layer_source_all .= $dl['layer_source'];
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['layer_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['layer_name'],"","label","{chan}","Open Sans Regular",11,"none");;}
	
		$layer_name = "'".$dl['layer_name']."','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('wifidb_meta_header', $wifidb_meta_header);
		$dbcore->smarty->display('map_wifidb.tpl');
		break;
		
	case "user_list":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
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
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		$centerpoint =  "[".$Center_LatLon['long'].",".$Center_LatLon['lat']."]";
		$zoom = 9;
		
		$layer_source_all = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"none");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"visible");
		}		

		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer($labeled,"#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		$layer_source_all .= $dl['layer_source'];	
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['layer_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['layer_name'],"","label","{chan}","Open Sans Regular",11,"none");;}
		
		$ml = $dbcore->createGeoJSON->CreateListGeoJsonLayer($id, $labeled);
		$layer_source_all .= $ml['layer_source'];
		$layer_name = "'".$ml['layer_name']."','".$dl['layer_name']."','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	

		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
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
		$style = "https://omt.wifidb.net/styles/WDB_OSM/style.json";
		$centerpoint =  "[".$dbcore->convert->dm2dd($latlng['long']).",".$dbcore->convert->dm2dd($latlng['lat'])."]";
		$zoom = 12;
		
		$layer_source_all = $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_Legacy","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_2to3year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB","WifiDB_1to2year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		$layer_source_all .= $dbcore->createGeoJSON->CreateApLayer("WifiDB_newest","WifiDB_0to1year","#00802b","#cc7a00","#b30000",2.25,1,0.5,"none");
		if ($labeled) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","label","{ssid}","Open Sans Regular",11,"none");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","label","{ssid}","Open Sans Regular",11,"none");
		}
		if ($channels) {
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_Legacy","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_2to3year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB","WifiDB_1to2year","channel","{chan}","Open Sans Regular",11,"visible");
			$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer("WifiDB_newest","WifiDB_0to1year","channel","{chan}","Open Sans Regular",11,"visible");
		}		
		
		$dl = $dbcore->createGeoJSON->CreateDailyGeoJsonLayer($labeled,"#00802b","#cc7a00","#b30000",3,1,0.5,"none");
		if ($labeled) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['layer_name'],"","label","{ssid}","Open Sans Regular",11,"none");}		
		if ($channels) {$layer_source_all .= $dbcore->createGeoJSON->CreateLabelLayer($dl['layer_name'],"","label","{chan}","Open Sans Regular",11,"none");;}
		$layer_source_all .= $dl['layer_source'];
		
		$ml = $dbcore->createGeoJSON->CreateApGeoJsonLayer($id, $labeled);
		$layer_source_all .= $ml['layer_source'];
		$layer_name = "'".$ml['layer_name']."','".$dl['layer_name']."','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'";	
		
		$dbcore->smarty->assign('layer_source_all', $layer_source_all);
		$dbcore->smarty->assign('layer_name', $layer_name);
		$dbcore->smarty->assign('style', $style);
		$dbcore->smarty->assign('centerpoint', $centerpoint);
		$dbcore->smarty->assign('zoom', $zoom);
		$dbcore->smarty->assign('labeled', $labeled);
		$dbcore->smarty->assign('channels', $channels);
		$dbcore->smarty->assign('list', 0);
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
		
		if(@$_REQUEST['ssid'])
		{
			$ssid   =   $_REQUEST['ssid'];
		}else
		{
			$ssid   =   "";
		}
		
		if(@$_REQUEST['mac'])
		{
			$mac	=   $_REQUEST['mac'];
		}else
		{
			$mac	=   "";
		}
		
		if(@$_REQUEST['radio'])
		{
			$radio  =   $_REQUEST['radio'];
		}else
		{
			$radio  =   "";
		}
		
		if(@$_REQUEST['chan'])
		{
			$chan   =   $_REQUEST['chan'];
		}else
		{
			$chan   =   "";
		}
		
		if(@$_REQUEST['auth'])
		{
			$auth   =   $_REQUEST['auth'];
		}else
		{
			$auth   =   "";
		}
		
		if(@$_REQUEST['encry'])
		{
			$encry  =   $_REQUEST['encry'];
		}else
		{
			$encry  =   "";
		}
		if ($from == ""){$from = NULL;}
		if ($inc == ""){$inc = NULL;}
		if ($ord == ""){$ord = "ASC";}
		if ($sort == ""){$sort = "ssid";}
		
		list($total_rows, $results_all, $save_url, $export_url) = $dbcore->Search($ssid, $mac, $radio, $chan, $auth, $encry, $ord, $sort, $from, $inc);
		

			
		$Import_Map_Data = "";
		foreach($results_all as $ResultAP) {
			$sql = "SELECT `mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`username` FROM `wifi_pointers` WHERE `id` = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $ResultAP['id'], PDO::PARAM_INT);
			$prep->execute();
			$appointer = $prep->fetchAll();
			foreach($appointer as $ap)
			{
				#Get AP GeoJSON
				$ap_info = array(
				"id" => $id,
				"new_ap" => $new_icons,
				"named" => $named,
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
				"manuf"=>$dbcore->findManuf($ap['mac']),
				"username" => $ap['username']
				);
				if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
				$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
			}
		}

		if($KML_data == "")
		{
			$results = array("mesg" => 'This export has no APs with gps. No KMZ file has been exported');
		}
		else
		{
			$KML_data = $dbcore->createKML->createFolder("Search Export", $KML_data, 0);
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), "Search_Export");
			$kmz_filename = $dbcore->kml_out.$title.".kmz";
			#$this->verbosed("Writing KMZ for ".$title." : ".$kmz_filename);
			$KML_data = $dbcore->createKML->createKMLstructure($title, $KML_data);
			$dbcore->Zip->addFile($KML_data, 'doc.kml');
			$dbcore->Zip->setZipFile($kmz_filename);
			$dbcore->Zip->getZipFile();
			
			if (file_exists($kmz_filename)) 
			{
				$results = array("mesg" => 'File is ready: <a href="'.$dbcore->kml_htmlpath.$title.'.kmz">'.$title.'.kmz</a>');
			}
			else
			{
				$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
			}
		}

		$dbcore->smarty->assign('results', $results);
		$dbcore->smarty->display('export_results.tpl');
		
		break;
}
?>
