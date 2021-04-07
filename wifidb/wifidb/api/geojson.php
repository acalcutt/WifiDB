<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
Copyright (C) 2021 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');

if((int)@$_REQUEST['all'] === 1){$all = 1;}else{$all = 0;}#Show both old and new access points. by default only new APs are shown.
if((int)@$_REQUEST['new_icons'] === 1){$new_icons = 1;}else{$new_icons = 0;}#use new AP icons instead of old AP icons in kml file. by default old icons are shown.
if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels in kml file. by default labels are not shown.
if((int)@$_REQUEST['json'] === 1){$json = 1;}else{$json = 0;}#output json instead of creating a download
if((int)@$_REQUEST['debug'] === 1){$debug = 1;}else{$debug = 0;}#output extra debug stuff
$func=$_REQUEST['func'];
switch($func)
{
	case "exp_latest_ap":
		$ExportCurrentApArray = $dbcore->export->ExportCurrentApArray($labeled, $new_icons);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($ExportCurrentApArray['data']);
		if($labeled){$file_name = "Latest_Labeled.geojson";}else{$file_name = "Latest.geojson";}
	break;
	
	case "exp_live_ap":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$Import_Map_Data = "";
		
		$sql = "SELECT id, ssid, mac, auth, encry, sectype, radio, chan, sig, username, session_id, ap_hash, BTx, OTx, NT, Label, FA, LA, lat, long\n"
					. "FROM live_aps\n"
					. "WHERE lat <> '0.0000' AND id = ?";

		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			#Get AP GeoJSON
			$ap_info = array(
			"live_id" => $ap['id'],
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
			"lon" => $dbcore->convert->dm2dd($ap['long']),
			"manuf"=>$dbcore->findManuf($ap['mac']),
			"user" => $ap['username']
			);
			if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
			$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
		}
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data, $labeled);
	break;

	case "exp_ap":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$ApArray = $dbcore->export->ApArray($id, $labeled, $new_icons);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($ApArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($ApArray['data']);
		if($labeled){$file_name = "ap_id_".$id."_Labeled.geojson";}else{$file_name = "ap_id_".$id.".geojson";}
	break;

	case "exp_ap_sig":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$list_id = (int)($_REQUEST['list_id'] ? $_REQUEST['list_id']: 0);
		$Import_Map_Data = "";
		$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon,\n"
			. "wGPS.Alt As Alt,\n";
		if($dbcore->sql->service == "mysql"){$sql .= "wf.user As user\n";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "wf.[user] As [user]\n";}
		$sql .= "FROM wifi_ap AS wap\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
			. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
			. "WHERE wap.HighGps_ID IS NOT NULL And wGPS.Lat != '0.0000' AND wap.AP_ID = ?";

		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			
			if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT wh.Sig, wh.RSSI, wh.Hist_Date, wGPS.Lat, wGPS.Lon, wh.File_ID, wf.user\n"
					. "FROM wifi_hist AS wh\n"
					. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wh.GPS_ID\n"
					. "LEFT OUTER JOIN files AS wf ON wf.id = wh.File_ID\n";
				if($list_id)
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ? And wh.File_ID = ?\n";}
				else
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ?\n";}
				$sql .= "ORDER BY wh.Hist_Date ASC\n"
					. "LIMIT 50000";
			}
			else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT TOP (50000) wh.Sig, wh.RSSI, wh.Hist_Date, wGPS.Lat, wGPS.Lon, wh.File_ID, wf.[user]\n"
					. "FROM wifi_hist AS wh\n"
					. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wh.GPS_ID\n"
					. "LEFT OUTER JOIN files AS wf ON wf.id = wh.File_ID\n";
				if($list_id)
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ? And wh.File_ID = ?\n";}
				else
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ?\n";}
				$sql .= "ORDER BY wh.Hist_Date ASC";
			}
			$prep2 = $dbcore->sql->conn->prepare($sql);
			$prep2->bindParam(1, $ap['AP_ID'], PDO::PARAM_INT);
			if($list_id){$prep2->bindParam(2, $list_id, PDO::PARAM_INT);}
			$prep2->execute();
			$histpointer = $prep2->fetchAll();
			foreach($histpointer as $hist)
			{
				#Get AP GeoJSON
				$ap_info = array(
				"id" => $ap['AP_ID'],
				"new_ap" => $new_icons,
				"named" => $named,
				"mac" => $ap['BSSID'],
				"ssid" => $ap['SSID'],
				"chan" => $ap['CHAN'],
				"sectype" => $ap['SECTYPE'],
				"auth" => $ap['AUTH'],
				"encry" => $ap['ENCR'],
				"lat" => $dbcore->convert->dm2dd($hist['Lat']),
				"lon" => $dbcore->convert->dm2dd($hist['Lon']),
				"alt" => $ap['Alt'],
				"user" => $hist['user'],
				"signal" => $hist['Sig'],
				"rssi" => $hist['RSSI'],
				"hist_date" => $hist['Hist_Date'],
				"hist_file_id" => $hist['File_ID']
				);
				if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
				$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
			}
		}
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data, $labeled);
		break;

	case "exp_list":
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

		$UserListArray = $dbcore->export->UserListArray($id, $labeled, $new_icons);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($UserListArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($UserListArray['data']);
		$file_name = $id."-".$title.".geojson";
		
		break;
		
	case "exp_user_all":
		$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
		if(is_numeric($from) && is_numeric($inc)){$title .= '-'.$from.'-'.$inc;}
		if(!is_numeric($from)){$from = 0;}
		if(!is_numeric($inc)){$inc = 50000;}
		
		$UserAllList = $dbcore->export->UserAllArray($user, $from, $inc, $labeled);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($UserAllList['data']);
		$file_name = $title.".geojson";
		break;
		
	case "exp_date":
		$start_date = $_REQUEST['date'];
		$end_date = $_REQUEST['end_date'];
		if(empty($start_date)){	
			#Get the date of the newest import
			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT date FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY date DESC LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [date] FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY [date] DESC";}
			$date_query = $dbcore->sql->conn->query($sql);
			$date_fetch = $date_query->fetch(2);
			$start_date = date('Y-m-d',strtotime($date_fetch['date']));
			$end_date = date('Y-m-d',strtotime($date_fetch['date']));
			$title_date = $start_date;
		}elseif(empty($end_date)){
			$end_date = $start_date;
			$title_date = $start_date;
		}else{
			$title_date = $start_date."_".$end_date;
		}
		$start_date =  "$start_date 00:00:00";
		$end_date =  "$end_date 23:59:59";

		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
		if(!is_numeric($from)){$from = 0;}
		if(!is_numeric($inc)){$inc = 50000;}
		$DateList = $dbcore->export->DateArray($start_date, $end_date, $labeled, 1, $from, $inc, 1);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($DateList['data']);
		if($labeled){$file_name = "date_list_".$title_date."_Labeled.geojson";}else{$file_name = "date_list_".$title_date.".geojson";}
		break;

	case "exp_daily":
		$date = new DateTime(); 
		$end_date = $date->format('Y-m-d H:i:s');// current time
		$date->sub(new DateInterval('PT36H'));
		$start_date = $date->format('Y-m-d H:i:s');// 36 Hours Ago

		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
		if(!is_numeric($from)){$from = 0;}
		if(!is_numeric($inc)){$inc = 50000;}
		$DateList = $dbcore->export->DateArray($start_date, $end_date, $labeled, 1, $from, $inc, 1);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($DateList['data']);
		if($labeled){$file_name = "date_list_".$title_date."_Labeled.geojson";}else{$file_name = "date_list_".$title_date.".geojson";}
		break;

	case "exp_search":
		$ord	=   filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
		$sort   =	filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
		
		if(@$_REQUEST['ssid']){$ssid = $_REQUEST['ssid'];}else{$ssid = "";}
		if(@$_REQUEST['mac']){$mac = $_REQUEST['mac'];}else{$mac = "";}
		if(@$_REQUEST['radio']){$radio = $_REQUEST['radio'];}else{$radio = "";}	
		if(@$_REQUEST['chan']){$chan = $_REQUEST['chan'];}else{$chan = "";}
		if(@$_REQUEST['auth']){$auth = $_REQUEST['auth'];}else{$auth = "";}
		if(@$_REQUEST['encry']){$encry = $_REQUEST['encry'];}else{$encry =  "";}
		if(@$_REQUEST['sectype']){$sectype = $_REQUEST['sectype'];}else{$sectype =  "";}
		
		if ($from == ""){$from = 0;}
		if ($inc == ""){$inc = 50000;}
		if ($ord == ""){$ord = "ASC";}
		if ($sort == ""){$sort = "ssid";}
		

		$SearchArray = $dbcore->export->SearchArray($ssid, $mac, $radio, $chan, $auth, $encry, $sectype, $ord, $sort, $labeled, $new_icons, $from, $inc, 1);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($SearchArray['data']);
		if($labeled){$file_name = "Search_".uniqid()."_Labeled.geojson";}else{$file_name = "Search_".uniqid().".geojson";}
		break;
}	
if($json)
{
	header('Content-type: application/json');
}
else
{
	$download = (empty($_REQUEST['download'])) ? $file_name : $_REQUEST['download'];#Override export filename if set
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$download.'"');
}
echo $results;

