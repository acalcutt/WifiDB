<?php
/*

Copyright (C) 2011 Phil Ferland,2018 Andrew Calcutt

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

$func = isset($_REQUEST['func']) ? $_REQUEST['func'] : '';
$row = (int)(isset($_REQUEST['row']) && $_REQUEST['row'] ? $_REQUEST['row']: 0);
$id = (int)(isset($_REQUEST['id']) && $_REQUEST['id'] ? $_REQUEST['id']: 0);
	
# One query for every database: mixed-case names are quoted on PostgreSQL by ident(). (The MariaDB and SQL Server
# versions read columns that no longer exist: FirstHist_ID, LastHist_ID and files.user.)
$q = function ($name) use ($dbcore) { return $dbcore->sql->ident($name); };
$sql = "SELECT wap.".$q('AP_ID').", wap.".$q('BSSID').", wap.".$q('SSID').", wap.".$q('CHAN').", wap.".$q('AUTH').", wap.".$q('ENCR').", wap.".$q('SECTYPE').", wap.".$q('RADTYPE').", wap.".$q('NETTYPE').", wap.".$q('BTX').", wap.".$q('OTX').", wap.".$q('FLAGS').", wap.ap_hash,\n"
	. "wap.fa As ".$q('FA').",\n"
	. "wap.la As ".$q('LA').",\n"
	. "wGPS.".$q('Lat')." As ".$q('Lat').",\n"
	. "wGPS.".$q('Lon')." As ".$q('Lon').",\n"
	. "wf.file_user As file_user\n"
	. "FROM wifi_ap AS wap\n"
	. "LEFT JOIN wifi_gps AS wGPS ON wGPS.".$q('GPS_ID')." = wap.".$q('HighGps_ID')."\n"
	. "LEFT JOIN files AS wf ON wf.id = wap.".$q('File_ID')."\n"
	. "WHERE wap.".$q('AP_ID')." = ?";
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
		$sql = "SELECT * FROM wifi_hist WHERE ".$q('AP_ID')." = ? AND ".$q('File_ID')." = ? ORDER BY ".$q('Hist_Date')." ASC";
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $id, PDO::PARAM_INT);
		$result->bindParam(2, $row, PDO::PARAM_INT);
		$result->execute();
		$signals = $result->fetchAll(2);
		$sig_size = $result->rowCount();
		break;
	default:
		$sql = "SELECT * FROM wifi_hist WHERE ".$q('AP_ID')." = ? ORDER BY ".$q('Hist_Date')." ASC";
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $id, PDO::PARAM_INT);
		$result->execute();
		$signals = $result->fetchAll(2);
		$sig_size = $result->rowCount();
}

$sig="";
foreach($signals as $points)
{
	if($sig<>""){$sig.="-";}
	$sig.=$points['Sig'];
}

$apdata = array(
	"ssid"=>$pointer['SSID'],
	"mac"=>$pointer["BSSID"],
	"man"=>$dbcore->findManuf($pointer['BSSID']),
	"auth"=>$pointer["AUTH"],
	"encry"=>$pointer["ENCR"],
	"radio"=>$pointer['RADTYPE'],
	"chan"=>$pointer["CHAN"],
	"lat"=>$pointer["Lat"],
	"long"=>$pointer["Lon"],
	"btx"=>$pointer["BTX"],
	"otx"=>$pointer["OTX"],
	"fa"=>$pointer['FA'],
	"lu"=>$pointer['LA'],
	"nt"=>$pointer['NETTYPE'],
	"user"=>$pointer['file_user'],
	"name"=>$pointer['ap_hash'],
	"sig"=>$sig
);
$dbcore->smarty->assign("wifidb_page_label", "WiFiDB AP Graphing");
$dbcore->smarty->assign("AP_data", $apdata);
$dbcore->smarty->display("graph_index.tpl");
