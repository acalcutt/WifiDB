<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
Copyright (C) 2015 Andrew Calcutt

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
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$Import_Map_Data = "";
		
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
					. "wGPS.Lat As Lat,\n"
					. "wGPS.Lon As Lon,\n"
					. "wGPS.Alt As Alt,\n"
					. "wf.user As user\n"
					. "FROM `wifi_ap` AS wap\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
					. "WHERE `wap`.`HighGps_ID` IS NOT NULL\n"
					. "ORDER BY wap.AP_ID DESC\n"
					. "LIMIT 1";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT TOP 1 wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
					. "wGPS.Lat As Lat,\n"
					. "wGPS.Lon As Lon,\n"
					. "wGPS.Alt As Alt,\n"
					. "wf.[user] As [user]\n"
					. "FROM wifi_ap AS wap\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
					. "WHERE wap.HighGps_ID IS NOT NULL\n"
					. "ORDER BY wap.AP_ID DESC";
			}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			#Get AP GeoJSON
			$ap_info = array(
			"id" => $ap['AP_ID'],
			"new_ap" => $new_icons,
			"named" => $named,
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
			$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
		}
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data, $labeled);
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
		$Import_Map_Data = "";
		
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
					. "wGPS.Lat As Lat,\n"
					. "wGPS.Lon As Lon,\n"
					. "wGPS.Alt As Alt,\n"
					. "wf.user As user\n"
					. "FROM `wifi_ap` AS wap\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
					. "WHERE `wap`.`HighGps_ID` IS NOT NULL And `wGPS`.`Lat` != '0.0000' AND `wap`.`AP_ID` = ?";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wap].[AP_ID], [wap].[BSSID], [wap].[SSID], [wap].[CHAN], [wap].[AUTH], [wap].[ENCR], [wap].[SECTYPE], [wap].[RADTYPE], [wap].[NETTYPE], [wap].[BTX], [wap].[OTX], [wap].[fa], [wap].[la], [wap].[points],\n"
					. "[wGPS].[Lat] As [Lat],\n"
					. "[wGPS].[Lon] As [Lon],\n"
					. "[wGPS].[Alt] As [Alt],\n"
					. "[wf].[user] As [user]\n"
					. "FROM [wifi_ap] AS [wap]\n"
					. "LEFT JOIN [wifi_gps] AS [wGPS] ON [wGPS].[GPS_ID] = [wap].[HighGps_ID]\n"
					. "LEFT JOIN [files] AS [wf] ON [wf].[id] = [wap].[File_ID]\n"
					. "WHERE [wap].[HighGps_ID] IS NOT NULL And [wGPS].[Lat] != '0.0000' AND [wap].[AP_ID] = ?";
			}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			#Get AP GeoJSON
			$ap_info = array(
			"id" => $ap['AP_ID'],
			"new_ap" => $new_icons,
			"named" => $named,
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
			$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
		}
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data, $labeled);
	break;

	case "exp_ap_sig":
		$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
		$list_id = (int)($_REQUEST['list_id'] ? $_REQUEST['list_id']: 0);
		$Import_Map_Data = "";
		
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
					. "wGPS.Lat As Lat,\n"
					. "wGPS.Lon As Lon,\n"
					. "wGPS.Alt As Alt,\n"
					. "wf.user As user\n"
					. "FROM `wifi_ap` AS wap\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
					. "WHERE `wap`.`HighGps_ID` IS NOT NULL And `wGPS`.`Lat` != '0.0000' AND `wap`.`AP_ID` = ?";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wap].[AP_ID], [wap].[BSSID], [wap].[SSID], [wap].[CHAN], [wap].[AUTH], [wap].[ENCR], [wap].[SECTYPE], [wap].[RADTYPE], [wap].[NETTYPE], [wap].[BTX], [wap].[OTX], [wap].[fa], [wap].[la], [wap].[points],\n"
					. "[wGPS].[Lat] As [Lat],\n"
					. "[wGPS].[Lon] As [Lon],\n"
					. "[wGPS].[Alt] As [Alt],\n"
					. "[wf].[user] As [user]\n"
					. "FROM [wifi_ap] AS [wap]\n"
					. "LEFT JOIN [wifi_gps] AS [wGPS] ON [wGPS].[GPS_ID] = [wap].[HighGps_ID]\n"
					. "LEFT JOIN [files] AS [wf] ON [wf].[id] = [wap].[File_ID]\n"
					. "WHERE [wap].[HighGps_ID] IS NOT NULL And [wGPS].[Lat] != '0.0000' AND [wap].[AP_ID] = ?";
			}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			$sql = "SELECT TOP 50000 wh.Sig, wh.RSSI, wh.Hist_Date, wGPS.Lat, wGPS.Lon, wh.File_ID\n"
				. "FROM wifi_hist AS wh\n"
				. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wh.GPS_ID\n";
			if($list_id)
			{
				$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ? And wh.File_ID = ?\n";
			}
			else
			{
				$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ?\n";
			}
			$sql .= "ORDER BY wh.Hist_Date ASC";
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
				"lat" => $dbcore->convert->dm2dd($hist['Lat']),
				"lon" => $dbcore->convert->dm2dd($hist['Lon']),
				"alt" => $ap['Alt'],
				"manuf"=>$dbcore->findManuf($ap['BSSID']),
				"user" => $ap['user'],
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
		
		$ListGeoJSON = $dbcore->export->UserListGeoJSON($id, $new_icons);
		$Center_LatLon = $dbcore->convert->GetCenterFromDegrees($ListGeoJSON['latlongarray']);
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($ListGeoJSON['data'], $labeled);
		$file_name = $id."-".$title.".geojson";
		
		break;
		
	case "exp_user_all":
		$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$limit	=	filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
		if ($from == ""){$from = NULL;}
		if ($limit == ""){$limit = NULL;}
		
		
		$UserGeoJSON = $dbcore->export->UserAllGeoJSON($user, $from, $limit);
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($UserGeoJSON['data']);
		$file_name = $title.".geojson";
		break;
		
	case "exp_date":
		if(!empty($_REQUEST['date']))
		{
			$date = $_REQUEST['date'];
		}
		else
		{	
			#Get the date of the newest import
			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT `date` FROM `files` WHERE `completed` = 1 ORDER BY `date` DESC LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [date] FROM [files] WHERE [completed] = 1 ORDER BY [date] DESC";}
			$date_query = $dbcore->sql->conn->query($sql);
			$date_fetch = $date_query->fetch(2);
			$datestamp = $date_fetch['date'];
			$datestamp_split = explode(" ", $datestamp);
			$date = $datestamp_split[0];
		}
		$date = (empty($date)) ? date($dbcore->export->date_format) : $date;
		
		#Get lists from the date specified
		$date_search = $date."%";
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `id` FROM `files` WHERE `date` LIKE ? ORDER BY `date` DESC";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [id] FROM [files] WHERE [date] LIKE ? ORDER BY [date] DESC";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $date_search, PDO::PARAM_STR);
		$prep->execute();
		$fetch_imports = $prep->fetchAll();
		$AllListGeoJSON = "";
		foreach($fetch_imports as $import)
		{
			if($AllListGeoJSON !== ''){$AllListGeoJSON .=',';};
			$ListGeoJSON = $dbcore->export->UserListGeoJSON($import['id'], $new_icons);
			$AllListGeoJSON .= $ListGeoJSON['data'];
		}
		
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($AllListGeoJSON, $labeled);
		$file_name = "Daily_Exports.geojson";
		break;

	case "exp_daily":
		#Get lists from the last day and a half
		$row_count = 1000;	
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT `wap`.`AP_ID`, `wap`.`BSSID`, `wap`.`SSID`, `wap`.`CHAN`, `wap`.`AUTH`, `wap`.`ENCR`, `wap`.`SECTYPE`, `wap`.`RADTYPE`, `wap`.`NETTYPE`, `wap`.`BTX`, `wap`.`OTX`, `wap`.`fa`, `wap`.`la`, `wap`.`points`,\n"
					. "`wGPS`.`Lat` As `Lat`,\n"
					. "`wGPS`.`Lon` As `Lon`,\n"
					. "`wGPS`.`Alt` As `Alt`,\n"
					. "`wf`.`user` As `user`\n"
					. "FROM `wifi_ap` AS `wap`\n"
					. "LEFT JOIN `wifi_gps` AS `wGPS` ON `wGPS`.`GPS_ID` = `wap`.`HighGps_ID`\n"
					. "LEFT JOIN `files` AS `wf` ON `wf`.`id` = `wap`.`File_ID`\n"
					. "WHERE `wap`.`HighGps_ID` IS NOT NULL And `wGPS`.`Lat` != '0.0000' AND `wap`.`ModDate` >= DATE_SUB(NOW(),INTERVAL 1.5 DAY) ORDER BY `wap`.`AP_ID` LIMIT ?,?";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wap].[AP_ID], [wap].[BSSID], [wap].[SSID], [wap].[CHAN], [wap].[AUTH], [wap].[ENCR], [wap].[SECTYPE], [wap].[RADTYPE], [wap].[NETTYPE], [wap].[BTX], [wap].[OTX], [wap].[fa], [wap].[la], [wap].[points],\n"
					. "[wGPS].[Lat] As [Lat],\n"
					. "[wGPS].[Lon] As [Lon],\n"
					. "[wGPS].[Alt] As [Alt],\n"
					. "[wf].[user] As [user]\n"
					. "FROM [wifi_ap] AS [wap]\n"
					. "LEFT JOIN [wifi_gps] AS [wGPS] ON [wGPS].[GPS_ID] = [wap].[HighGps_ID]\n"
					. "LEFT JOIN [files] AS [wf] ON [wf].[id] = [wap].[File_ID]\n"
					. "WHERE [wap].[HighGps_ID] IS NOT NULL And [wGPS].[Lat] != '0.0000' AND [wap].[ModDate] >= dateadd(day, -1.5, getdate()) ORDER BY [wap].[AP_ID]\n"
					. "OFFSET ? ROWS\n"
					. "FETCH NEXT ? ROWS ONLY";
			}
		$Import_Map_Data = "";
		for ($i = 0; TRUE; $i++) {
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
				$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
			}
			$number_of_rows = $prep->rowCount();
			if ($number_of_rows !== $row_count) {break;}
		}	
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data, $labeled);
		$file_name = "Daily_Exports.geojson";
		break;
		
		case "exp_search":
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
			$Import_Map_Data = "";
			foreach($results_all as $ap) 
			{
				#Get number of AP points
				if($dbcore->sql->service == "mysql")
					{$sqlp = "SELECT count(`Hist_Date`) AS `points` FROM `wifi_hist` WHERE `AP_ID` = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sqlp = "SELECT count([Hist_Date]) AS [points] FROM [wifi_hist] WHERE [AP_ID] = ?";}
				$prep2 = $dbcore->sql->conn->prepare($sqlp);
				$prep2->bindParam(1, $ap['id'], PDO::PARAM_INT);
				$prep2->execute();
				$prep2_fetch = $prep2->fetch(2);

				#Get AP GeoJSON
				$ap_info = array(
				"id" => $ap['id'],
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
				"points" => $ap['points'],
				"lat" => $dbcore->convert->dm2dd($ap['Lat']),
				"lon" => $dbcore->convert->dm2dd($ap['Lon']),
				"alt" => $ap['Alt'],
				"manuf"=>$dbcore->findManuf($ap['mac']),
				"user" => $ap['user']
				);
				if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
				$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
			}
			$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data, $labeled);
			
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

