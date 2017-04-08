<?php
/*
Database.inc.php, holds the database interactive functions.
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

$startdate="14-10-2007";
$lastedit="05-05-2013";

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "graph");

include('../lib/init.inc.php');

$func=$_REQUEST['func'];
$row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);

$sql = "SELECT * FROM `wifi_pointers` WHERE `id` = ?";
$result = $dbcore->sql->conn->prepare($sql);
$result->bindParam(1, $id, PDO::PARAM_INT);
$result->execute();
if($dbcore->sql->checkError() !== 0)
{
	throw new Exception("Error selecting from pointers table.");
}
$pointer = $result->fetch(2);

switch ($func) {
	case "graph_list_ap":
		$sql = "SELECT * FROM `wifi_signals` WHERE `ap_hash` = ? AND `file_id` = ? ORDER BY `time_stamp` ASC";
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $pointer['ap_hash'], PDO::PARAM_STR);
		$result->bindParam(2, $row, PDO::PARAM_INT);
		$result->execute();
		$signals = $result->fetchAll(2);
		$sig_size = $result->rowCount();
		break;
	default:
		$sql = "SELECT * FROM `wifi_signals` WHERE `ap_hash` = ? ORDER BY `time_stamp` ASC";
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $pointer['ap_hash'], PDO::PARAM_STR);
		$result->execute();
		$signals = $result->fetchAll(2);
		$sig_size = $result->rowCount();
}

$sig="";
foreach($signals as $points)
{
	if($sig<>""){$sig.="-";}
	$sig.=$points['signal'];
}

$apdata = array(
	"ssid"=>$pointer['ssid'],
	"mac"=>$pointer["mac"],
	"man"=>$pointer["manuf"],
	"auth"=>$pointer["auth"],
	"encry"=>$pointer["encry"],
	"radio"=>$pointer['radio'],
	"chan"=>$pointer["chan"],
	"lat"=>$pointer["lat"],
	"long"=>$pointer["long"],
	"btx"=>$pointer["BTx"],
	"otx"=>$pointer["OTx"],
	"fa"=>$pointer['FA'],
	"lu"=>$pointer['LA'],
	"nt"=>$pointer["NT"],
	"label"=>$pointer["label"],
	"sig"=>$sig,
	"name"=>$pointer['ap_hash']
);
$dbcore->smarty->assign("wifidb_page_label", "WiFiDB AP Graphing");
$dbcore->smarty->assign("AP_data", $apdata);
$dbcore->smarty->display("graph_index.tpl");
