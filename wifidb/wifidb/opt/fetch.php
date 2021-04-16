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

switch($func)
{
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