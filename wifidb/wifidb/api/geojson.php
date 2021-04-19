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

if((int)@$_REQUEST['only_new'] === 1){$only_new = 1;}else{$only_new = 0;}#Show both old and new access points. by default only new APs are shown.
if((int)@$_REQUEST['no_gps'] === 1){$valid_gps = 0;}else{$valid_gps = 1;}#Show both old and new access points. by default only new APs are shown.
if((int)@$_REQUEST['new_icons'] === 1){$new_icons = 1;}else{$new_icons = 0;}#use new AP icons instead of old AP icons in kml file. by default old icons are shown.
if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels in kml file. by default labels are not shown.
if((int)@$_REQUEST['json'] === 1){$json = 1;}else{$json = 0;}#output json instead of creating a download
if((int)@$_REQUEST['debug'] === 1){$debug = 1;}else{$debug = 0;}#output extra debug stuff
$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
if(is_numeric($from) && is_numeric($inc)){$range .= $from.'-'.$inc;}else{$range = '';}
if(!is_numeric($from)){$from = 0;}
if(!is_numeric($inc)){$inc = 50000;}
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
			"nt" => $ap['NT'],
			"sectype" => $ap['sectype'],
			"auth" => $ap['auth'],
			"encry" => $ap['encry'],
			"btx" => $ap['BTx'],
			"otx" => $ap['OTx'],
			"fa" => $ap['FA'],
			"la" => $ap['LA'],
			"lat" => $dbcore->convert->dm2dd($ap['lat']),
			"lon" => $dbcore->convert->dm2dd($ap['long']),
			"lat_dm" => $ap['lat'],
			"lon_dm" => $ap['long'],
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
		#Get SSID
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `SSID` FROM `wifi_ap` WHERE `AP_ID` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [SSID] FROM [wifi_ap] WHERE [AP_ID] = ?";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$ap_array = $prep->fetch();
		$ssid = $dbcore->formatSSID($ap_array['SSID']);
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), 'AP_'.$id.'-'.$ssid);
		#Create GeoJSON
		$ApArray = $dbcore->export->ApArray($id, $labeled, $new_icons, $valid_gps);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($ApArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($ApArray['data']);
		if($labeled){$file_name = $title."_Labeled.geojson";}else{$file_name = $title.".geojson";}
	break;

	case "exp_cid":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		#Get SSID

		$sql = "SELECT ssid FROM cell_id WHERE cell_id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$ap_array = $prep->fetch();
		$ssid = $dbcore->formatSSID($ap_array['ssid']);
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), 'CID_'.$id.'-'.$ssid);
		#Create GeoJSON
		$CellArray = $dbcore->export->CellArray($id, $labeled, $new_icons, $valid_gps);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($CellArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($CellArray['data']);
		if($labeled){$file_name = $title."_Labeled.geojson";}else{$file_name = $title.".geojson";}
	break;

	case "exp_ap_sig":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$file_id = (int)($_REQUEST['file_id'] ? $_REQUEST['file_id']: 0);
		$title = "SigHist_".$id;
		if(is_numeric($file_id)){$title .= '_'.$file_id;}
		if($range){$title .= "_".$range;}
		$SigHistArray = $dbcore->export->SigHistArray($id, $file_id, $from, $inc, $valid_gps);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($SigHistArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($SigHistArray['data']);
		if($labeled){$file_name = $title."_Labeled.geojson";}else{$file_name = $title.".geojson";}
		break;

	case "exp_cell_sig":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$file_id = (int)($_REQUEST['file_id'] ? $_REQUEST['file_id']: 0);
		$title = "CellSigHist_".$id;
		if(is_numeric($file_id)){$title .= '_'.$file_id;}
		if($range){$title .= "_".$range;}
		$CellSigHistArray = $dbcore->export->CellSigHistArray($id, $file_id, $from, $inc, $valid_gps);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($CellSigHistArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($CellSigHistArray['data']);
		if($labeled){$file_name = $title."_Labeled.geojson";}else{$file_name = $title.".geojson";}
		break;

	case "exp_list":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$title = "File_".$id;

		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `title` FROM `files` WHERE `id` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [title] FROM [files] WHERE [id] = ?";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$fetch = $prep->fetch();
		$title .= "_".preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);
		if($range){$title .= "_".$range;}

		$UserListArray = $dbcore->export->UserListArray($id, $from, $inc, "AP_ID", "DESC", $labeled, $new_icons, $only_new, $valid_gps);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($UserListArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($UserListArray['data']);
		if($labeled){$file_name = $title."_Labeled.geojson";}else{$file_name = $title.".geojson";}

		break;

	case "exp_cid_list":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$title = "File_".$id;

		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `title` FROM `files` WHERE `id` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [title] FROM [files] WHERE [id] = ?";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$fetch = $prep->fetch();
		$title .= "_".preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);
		if($range){$title .= "_".$range;}

		$CellUserListArray = $dbcore->export->CellUserListArray($id, $from, $inc, "cell_id", "DESC", 0, 0, 0, 1);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($CellUserListArray['latlongarray']);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($CellUserListArray['data']);
		if($labeled){$file_name = $title."_Labeled.geojson";}else{$file_name = $title.".geojson";}

		break;

	case "exp_user_all":
		$user = $_REQUEST['user'];
		$title = "User_".preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		if($range){$title .= "_".$range;}

		$UserAllList = $dbcore->export->UserAllArray($user, $from, $inc, $labeled);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($UserAllList['data']);
		if($labeled){$file_name = $title."_Labeled.geojson";}else{$file_name = $title.".geojson";}
		break;

	case "exp_date":
		$start_date = $_REQUEST['date'];
		$end_date = $_REQUEST['end_date'];
		if(empty($start_date)){	
			#Get the date of the newest import
			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT file_date FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY file_date DESC LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [file_date] FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY [file_date] DESC";}
			$date_query = $dbcore->sql->conn->query($sql);
			$date_fetch = $date_query->fetch(2);
			$start_date = date('Y-m-d',strtotime($date_fetch['file_date']));
			$end_date = date('Y-m-d',strtotime($date_fetch['file_date']));
			$title_date = $start_date;
		}elseif(empty($end_date)){
			$end_date = $start_date;
			$title_date = $start_date;
		}else{
			$title_date = $start_date."_".$end_date;
		}
		$start_date =  "$start_date 00:00:00";
		$end_date =  "$end_date 23:59:59";

		$DateList = $dbcore->export->DateArray($start_date, $end_date, $labeled, 1, $from, $inc, 1);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($DateList['data']);
		if($labeled){$file_name = "date_list_".$title_date."_Labeled.geojson";}else{$file_name = "date_list_".$title_date.".geojson";}
		break;

	case "exp_daily":
		$date = new DateTime(); 
		$end_date = $date->format('Y-m-d H:i:s');// current time
		$date->sub(new DateInterval('PT36H'));
		$start_date = $date->format('Y-m-d H:i:s');// 36 Hours Ago

		$DateList = $dbcore->export->DateArray($start_date, $end_date, $labeled, 1, $from, $inc, 1);
		$results = $dbcore->createGeoJSON->CreateApFeatureCollection($DateList['data']);
		if($labeled){$file_name = "date_list_".$title_date."_Labeled.geojson";}else{$file_name = "date_list_".$title_date.".geojson";}
		break;

	case "exp_search":
		$ord	=   filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
		$sort   =	filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
		
		if(@$_REQUEST['ssid']){$ssid = $_REQUEST['ssid'];}else{$ssid = "";}
		if(@$_REQUEST['mac']){$mac = $_REQUEST['mac'];}else{$mac = "";}
		if(@$_REQUEST['radio']){$radio = $_REQUEST['radio'];}else{$radio = "";}	
		if(@$_REQUEST['chan']){$chan = $_REQUEST['chan'];}else{$chan = "";}
		if(@$_REQUEST['auth']){$auth = $_REQUEST['auth'];}else{$auth = "";}
		if(@$_REQUEST['encry']){$encry = $_REQUEST['encry'];}else{$encry =  "";}
		if(@$_REQUEST['sectype']){$sectype = $_REQUEST['sectype'];}else{$sectype =  "";}

		$sorts=array("AP_ID","SSID","mac","chan","radio","auth","encry","fa","la","points","ModDate");
		if(!in_array($sort, $sorts)){$sort = "ModDate";}
		$ords=array("ASC","DESC");
		if(!in_array($ord, $ords)){$ord = "DESC";}

		$SearchArray = $dbcore->export->SearchArray($ssid, $mac, $radio, $chan, $auth, $encry, $sectype, $ord, $sort, $labeled, $new_icons, $from, $inc, $valid_gps);
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

