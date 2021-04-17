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
		#Get count of files with this ap_id for pageation

		$CellArray = $dbcore->export->CellArray($id);
		$cell_info = $CellArray['data'];
		
		$sql = "SELECT file_id, Max(new) AS new FROM cell_hist WHERE cell_id = 80 GROUP BY file_id, new ORDER BY file_id DESC";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$fpointer = $prep->fetchAll();
		$ap_array = array();
		foreach($fpointer as $file)
		{
			$file_id = $file['file_id'];
			$new = $file['new'];
			
			$sql = "SELECT cell_hist.rssi, cell_hist.hist_date, wifi_gps.Lat, wifi_gps.Lon, wifi_gps.Alt, wifi_gps.NumOfSats, wifi_gps.AccuracyMeters, wifi_gps.HorDilPitch\n"
				. "FROM cell_hist\n"
				. "LEFT JOIN wifi_gps ON wifi_gps.GPS_ID = cell_hist.gps_id\n"
				. "WHERE cell_hist.file_id = ?\n"
				. "ORDER BY hist_date DESC";
			$prep1 = $dbcore->sql->conn->prepare($sql);
			$prep1->bindParam(1, $file_id, PDO::PARAM_INT);
			$prep1->execute();
			$signals = $prep1->fetchAll();
			
			$sql = "SELECT * FROM files WHERE id = ?";
			$prep2 = $dbcore->sql->conn->prepare($sql);
			$prep2->bindParam(1, $id, PDO::PARAM_INT);
			$prep2->execute();
			$flpointer = $prep2->fetchAll();
			foreach($flpointer as $fl)
			{
				$apcount++;
				#Get AP GeoJSON
				$ap_info = array(
				"id" => $file_id,
				"nu" => $new,
				"signals" => $signals,
				"file_user" => $fl['file_user'],
				"file" => $fl['file_orig'],
				"title" => $fl['title'],
				"notes" => $fl['notes'],
				"date" => $fl['file_date'],
				"validgps" => $fl['ValidGPS']
				);
				$ap_array[] = $ap_info;
			}
		}
		$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord, "", "", "", "", "", "", "", "", "", $id);
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_page_label', "Access Point Page ({$results[0]})");
		$dbcore->smarty->assign('wifidb_assoc_lists', $ap_array);
		$dbcore->smarty->assign('wifidb_cid', $cell_info[0]);
		$dbcore->smarty->assign('wifidb_geonames', $results[3]);
		$dbcore->smarty->display('fetch_cell.tpl');
		break;
	case "":
		#Get count of files with this ap_id for pageation

		$sql = "Select Count(distinct File_ID) FROM wifi_hist WHERE AP_ID = ?";
		$sqlprep = $dbcore->sql->conn->prepare($sql);
		$sqlprep->bindParam(1, $id, PDO::PARAM_INT);
		$sqlprep->execute();
		$total_rows = $sqlprep->fetchColumn();

		$results = $dbcore->APFetch($id, $sort, $ord, $from, $inc);
		$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord, "", "", "", "", "", "", "", "", "", $id);
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_page_label', "Access Point Page ({$results[0]})");
		$dbcore->smarty->assign('wifidb_assoc_lists', $results[1]);
		$dbcore->smarty->assign('wifidb_ap', $results[2]);
		$dbcore->smarty->assign('wifidb_geonames', $results[3]);
		$dbcore->smarty->display('fetch.tpl');
		break;
}

?>