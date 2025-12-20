<?php
/*
fetch.php, fetches a single AP's details.
Copyright (C) 2011 Phil Ferland

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
define("SWITCH_EXTRAS", "EXPORT");

include('../lib/init.inc.php');
$id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
$ord = filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
$from = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
$inc = filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);

#security for order by, desc, to, from injections or incorrect values
$sorts=array("File_ID","file_date","points");
if(!in_array($sort, $sorts)){$sort = "File_ID";}
$ords=array("ASC","DESC");
if(!in_array($ord, $ords)){$ord = "DESC";}
if(!is_numeric($from)){$from = 0;}
if(!is_numeric($inc)){$inc = 50;}

$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);
switch($func)
{
	case "cid":
		$CellArray = $dbcore->export->CellArray($id);
		$cell_info = $CellArray['data'][0];
		if($cell_info['validgps'] == 1){$geonames = $dbcore->export->GeoNamesArray($cell_info['lat'], $cell_info['lon'], 0, 10);}else{$geonames = array();}

		$sql = "Select Count(distinct file_id) FROM cell_hist WHERE cell_id = ?";
		$sqlprep = $dbcore->sql->conn->prepare($sql);
		$sqlprep->bindParam(1, $id, PDO::PARAM_INT);
		$sqlprep->execute();
		$total_rows = $sqlprep->fetchColumn();

		$sql = "SELECT cell_hist.file_id, files.title, files.file_orig, files.notes, files.file_user, files.file_date, files.ValidGPS, cell_hist.new, COUNT(cell_hist.hist_date) As points\n"
			. "FROM cell_hist\n"
			. "INNER JOIN files ON cell_hist.file_id = files.id\n"
			. "WHERE cell_hist.cell_id = ?\n"
			. "GROUP BY cell_hist.file_id, files.title, files.file_orig, files.notes, files.file_user, files.file_date, files.ValidGPS, cell_hist.new\n"
			. "ORDER BY {$sort} {$ord}";
		if($dbcore->sql->service == "mysql"){$sql .= "\nLIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "\nOFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$fpointer = $prep->fetchAll();
		$file_array = array();
		foreach($fpointer as $file)
		{
			$sql1 = "SELECT cell_hist.rssi, cell_hist.hist_date, wifi_gps.Lat, wifi_gps.Lon, wifi_gps.Alt, wifi_gps.NumOfSats, wifi_gps.AccuracyMeters, wifi_gps.HorDilPitch\n"
				. "FROM cell_hist\n"
				. "LEFT JOIN wifi_gps ON wifi_gps.GPS_ID = cell_hist.gps_id\n"
				. "WHERE cell_hist.file_id = ? AND cell_hist.cell_id = ?\n"
				. "ORDER BY hist_date DESC";
			$prep1 = $dbcore->sql->conn->prepare($sql1);
			$prep1->bindParam(1, $file['file_id'], PDO::PARAM_INT);
			$prep1->bindParam(2, $id, PDO::PARAM_INT);
			$prep1->execute();
			$signals = $prep1->fetchAll();

			#Get AP GeoJSON
			$ap_info = array(
				"id" => $file['file_id'],
				"validgps" => $file['ValidGPS'],
				"nu" => $file['new'],
				"signals" => $signals,
				"points" => Count($signals),
				"file_user" => $file['file_user'],
				"file" => $file['file_orig'],
				"title" => $file['title'],
				"notes" => $file['notes'],
				"date" => $file['file_date']
			);
			$ap_array[] = $ap_info;
			$apcount++;
		}
		$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord, "", "", "", "", "", "", "", "", "", $id);
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_page_label', "Access Point Page ({$cell_info['ssid']})");
		$dbcore->smarty->assign('wifidb_assoc_lists', $ap_array);
		$dbcore->smarty->assign('wifidb_cid', $cell_info);
		$dbcore->smarty->assign('wifidb_geonames', $geonames);
		$dbcore->smarty->display('fetch_cell.tpl');
		break;
	case "":
		$ApArray = $dbcore->export->ApArray($id);
		$ap_info = $ApArray['data'][0];
		if($ap_info['validgps'] == 1){$geonames = $dbcore->export->GeoNamesArray($ap_info['lat'], $ap_info['lon'], 0, 10);}else{$geonames = array();}

		$sql = "Select Count(distinct File_ID) FROM wifi_hist WHERE AP_ID = ?";
		$sqlprep = $dbcore->sql->conn->prepare($sql);
		$sqlprep->bindParam(1, $id, PDO::PARAM_INT);
		$sqlprep->execute();
		$total_rows = $sqlprep->fetchColumn();

		$sql = "SELECT wifi_hist.File_ID, files.title, files.file_orig, files.notes, files.file_user, files.file_date, files.ValidGPS, wifi_hist.New, COUNT(wifi_hist.Hist_Date) As points\n"
			. "FROM wifi_hist\n"
			. "INNER JOIN files ON wifi_hist.File_ID = files.id\n"
			. "WHERE wifi_hist.AP_ID = ?\n"
			. "GROUP BY wifi_hist.File_ID, files.title, files.file_orig, files.notes, files.file_user, files.file_date, files.ValidGPS, wifi_hist.New\n"
			. "ORDER BY {$sort} {$ord}";
		if($dbcore->sql->service == "mysql"){$sql .= "\nLIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "\nOFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$fpointer = $prep->fetchAll();
		$file_array = array();
		foreach($fpointer as $file)
		{
			$sql1 = "SELECT wifi_hist.AP_ID, wifi_hist.Sig, wifi_hist.RSSI, wifi_hist.GPS_ID, wifi_hist.New, wifi_gps.Lat, wifi_gps.Lon, wifi_gps.Alt, wifi_gps.NumOfSats, wifi_gps.AccuracyMeters, wifi_gps.HorDilPitch, wifi_gps.TrackAngle, wifi_gps.GPS_Date, wifi_gps.MPH, wifi_gps.KPH\n"
				. "FROM wifi_hist\n"
				. "INNER JOIN wifi_gps ON wifi_hist.GPS_ID=wifi_gps.GPS_ID\n"
				. "WHERE wifi_hist.File_ID = ? AND wifi_hist.AP_ID = ?\n"
				. "ORDER BY wifi_hist.Hist_Date ASC";
			$prep1 = $dbcore->sql->conn->prepare($sql1);
			$prep1->bindParam(1, $file['File_ID'], PDO::PARAM_INT);
			$prep1->bindParam(2, $id, PDO::PARAM_INT);
			$prep1->execute();
			$signals = $prep1->fetchAll();

			#Get AP GeoJSON
			$file_info = array(
			"id" => $file['File_ID'],
			"validgps" => $file['ValidGPS'],
			"nu" => $file['New'],
			"signals" => $signals,
			"points" => Count($signals),
			"file_user" => $file['file_user'],
			"file" => $file['file_orig'],
			"title" => $file['title'],
			"notes" => $file['notes'],
			"date" => $file['file_date']
			);
			$file_array[] = $file_info;
		}
		$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord, "", "", "", "", "", "", "", "", "", $id);
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_page_label', "Access Point Page (".$ap_info[0]['ssid'].")");
		$dbcore->smarty->assign('wifidb_assoc_lists', $file_array);
		$dbcore->smarty->assign('wifidb_ap', $ap_info);
		$dbcore->smarty->assign('wifidb_geonames', $geonames);
		$dbcore->smarty->display('fetch.tpl');
		break;
}

?>
