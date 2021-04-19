<?php
/*
all.php: AP List for WiFiDB.
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');
$dbcore->smarty->assign('wifidb_page_label', 'All Access Points Page');

$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
$ord = filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
$from = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
$inc = filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);

#security for order by, desc, to, from injections or incorrect values
$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);
$ords=array("ASC","DESC");
if(!in_array($ord, $ords)){$ord = "DESC";}
if(!is_numeric($from)){$from = 0;}
if(!is_numeric($inc)){$inc = 250;}

$dbcore->smarty->assign('func', $func);
switch($func)
{
	case "bt":
		$sorts=array("new","cell_id","ssid","mac","authmode","type","chan","fa","la","network","list_points","points","file_user");
		if(!in_array($sort, $sorts)){$sort = "cell_id";}
		#Get count of APs for pageation
		$sql = "SELECT COUNT(*) FROM cell_id WHERE fa IS NOT NULL AND fa != '1970-01-01 00:00:00.000' AND type IN ('BT','BLE')";
		$sqlprep = $dbcore->sql->conn->prepare($sql);	   
		$sqlprep->execute();
		$total_rows = $sqlprep->fetchColumn();

		#Get the list of access points in the requsted order
		
		$sql = "SELECT cid.cell_id, cid.mac, cid.authmode, cid.ssid, cid.chan, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points, cell_carriers.network,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon,\n"
			. "wGPS.Alt As Alt,\n"
			. "wf.file_user As file_user\n"
			. "FROM cell_id AS cid\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = cid.highgps_id\n"
			. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
			. "LEFT OUTER JOIN cell_carriers ON CAST(mcc AS varchar) = substring(cid.mac,0,4) AND CAST(mnc AS varchar) = substring(cid.mac,4,3)\n"
			. "WHERE cid.fa IS NOT NULL AND cid.type IN ('BT','BLE')\n"	
			. "ORDER BY {$sort} {$ord}";
		if($dbcore->sql->service == "mysql"){$sql .= "\nLIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "\nOFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}
		$pre_page_list = $dbcore->sql->conn->query($sql);

		$n=0;
		$wifidb_aps_all = array();
		while ( $array = $pre_page_list->fetch(2) )
		{
			$wifidb_aps_all[$n]['id'] = $array['cell_id'];
			$wifidb_aps_all[$n]['authmode'] = $array['authmode'];
			$wifidb_aps_all[$n]['mac'] = $array['mac'];
			$wifidb_aps_all[$n]['ssid'] = $dbcore->formatSSID($array['ssid']);
			$wifidb_aps_all[$n]['chan'] = $array['chan'];
			$wifidb_aps_all[$n]['type'] = $array['type'];
			$wifidb_aps_all[$n]['high_rssi'] = $array['high_rssi'];
			$wifidb_aps_all[$n]['high_gps_rssi'] = $array['high_gps_rssi'];
			$wifidb_aps_all[$n]['network'] = $array['network'];
			$wifidb_aps_all[$n]['fa'] = $array['fa'];
			$wifidb_aps_all[$n]['la'] = $array['la'];
			$wifidb_aps_all[$n]['points'] = $array['points'];
			$wifidb_aps_all[$n]['user'] = $array['file_user'];
			if($array['Lat'] == "" || $array['Lat'] == "0.0000"){$wifidb_aps_all[$n]['ValidGPS'] = 0;}else{$wifidb_aps_all[$n]['ValidGPS'] = 1;}

			$n++;
		}

		$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord, "$func&");
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_aps_all', $wifidb_aps_all);
		$dbcore->smarty->assign('sort', $sort);
		$dbcore->smarty->assign('ord', $ord);
		$dbcore->smarty->assign('from', $from);
		$dbcore->smarty->assign('inc', $inc);
		$dbcore->smarty->assign('points', $total_rows);
		$dbcore->smarty->display('all_cids.tpl');
	break;
	case "cid":
		$sorts=array("new","cell_id","ssid","mac","authmode","type","chan","fa","la","network","list_points","points","file_user");
		if(!in_array($sort, $sorts)){$sort = "cell_id";}
		#Get count of APs for pageation
		$sql = "SELECT COUNT(*) FROM cell_id WHERE fa IS NOT NULL AND fa != '1970-01-01 00:00:00.000' AND type NOT IN ('BT','BLE')";
		$sqlprep = $dbcore->sql->conn->prepare($sql);	   
		$sqlprep->execute();
		$total_rows = $sqlprep->fetchColumn();

		#Get the list of access points in the requsted order
		
		$sql = "SELECT cid.cell_id, cid.mac, cid.authmode, cid.ssid, cid.chan, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points, cell_carriers.network,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon,\n"
			. "wGPS.Alt As Alt,\n"
			. "wf.file_user As file_user\n"
			. "FROM cell_id AS cid\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = cid.highgps_id\n"
			. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
			. "LEFT OUTER JOIN cell_carriers ON CAST(mcc AS varchar) = substring(cid.mac,0,4) AND CAST(mnc AS varchar) = substring(cid.mac,4,3)\n"
			. "WHERE cid.fa IS NOT NULL AND cid.type NOT IN ('BT','BLE')\n"	
			. "ORDER BY {$sort} {$ord}";
		if($dbcore->sql->service == "mysql"){$sql .= "\nLIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "\nOFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}

		$pre_page_list = $dbcore->sql->conn->query($sql);


		$row_color = 0;
		$n=0;
		$wifidb_aps_all = array();
		while ( $array = $pre_page_list->fetch(2) )
		{
			$wifidb_aps_all[$n]['id'] = $array['cell_id'];
			$wifidb_aps_all[$n]['authmode'] = $array['authmode'];
			$wifidb_aps_all[$n]['mac'] = $array['mac'];
			$wifidb_aps_all[$n]['ssid'] = $dbcore->formatSSID($array['ssid']);
			$wifidb_aps_all[$n]['chan'] = $array['chan'];
			$wifidb_aps_all[$n]['type'] = $array['type'];
			$wifidb_aps_all[$n]['high_rssi'] = $array['high_rssi'];
			$wifidb_aps_all[$n]['high_gps_rssi'] = $array['high_gps_rssi'];
			$wifidb_aps_all[$n]['network'] = $array['network'];
			$wifidb_aps_all[$n]['fa'] = $array['fa'];
			$wifidb_aps_all[$n]['la'] = $array['la'];
			$wifidb_aps_all[$n]['points'] = $array['points'];
			$wifidb_aps_all[$n]['user'] = $array['file_user'];
			if($array['Lat'] == "" || $array['Lat'] == "0.0000"){$wifidb_aps_all[$n]['ValidGPS'] = 0;}else{$wifidb_aps_all[$n]['ValidGPS'] = 1;}

			$n++;
		}

		$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord, "$func&");
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_aps_all', $wifidb_aps_all);
		$dbcore->smarty->assign('sort', $sort);
		$dbcore->smarty->assign('ord', $ord);
		$dbcore->smarty->assign('from', $from);
		$dbcore->smarty->assign('inc', $inc);
		$dbcore->smarty->assign('points', $total_rows);
		$dbcore->smarty->display('all_cids.tpl');
	break;
	case "":
		$sorts=array("AP_ID","BSSID","SSID","CHAN","AUTH","ENCR","SECTYPE","RADTYPE","NETTYPE","BTX","OTX","fa","la","points");
		if(!in_array($sort, $sorts)){$sort = "AP_ID";}
		#Get count of APs for pageation
		$sql = "SELECT COUNT(*) FROM wifi_ap WHERE fa IS NOT NULL AND fa != '1970-01-01 00:00:00.000'";
		$sqlprep = $dbcore->sql->conn->prepare($sql);	   
		$sqlprep->execute();
		$total_rows = $sqlprep->fetchColumn();

		#Get the list of access points in the requsted order
		$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon,\n"
			. "wf.file_user As file_user\n"
			. "FROM wifi_ap AS wap\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
			. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
			. "WHERE wap.fa IS NOT NULL AND wap.fa != '1970-01-01 00:00:00.000'\n"	
			. "ORDER BY {$sort} {$ord}";
			
		
		if($dbcore->sql->service == "mysql"){$sql .= "\nLIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "\nOFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}
		$pre_page_list = $dbcore->sql->conn->query($sql);


		$row_color = 0;
		$n=0;
		$wifidb_aps_all = array();
		while ( $array = $pre_page_list->fetch(2) )
		{
			if($row_color == 1){$row_color = 0; $color = "light";}else{$row_color = 1; $color = "dark";}
			
			$wifidb_aps_all[$n]['class'] = $color;
			$wifidb_aps_all[$n]['id'] = $array['AP_ID'];
			$wifidb_aps_all[$n]['mac'] = $array['BSSID'];
			$wifidb_aps_all[$n]['ssid'] = $dbcore->formatSSID($array['SSID']);
			$wifidb_aps_all[$n]['chan'] = $array['CHAN'];
			$wifidb_aps_all[$n]['auth'] = $array['AUTH'];
			$wifidb_aps_all[$n]['encry'] = $array['ENCR'];
			$wifidb_aps_all[$n]['radio'] = $array['RADTYPE'];
			$wifidb_aps_all[$n]['nt'] = $array['NETTYPE'];
			$wifidb_aps_all[$n]['fa'] = $array['fa'];
			$wifidb_aps_all[$n]['la'] = $array['la'];
			$wifidb_aps_all[$n]['points'] = $array['points'];
			$wifidb_aps_all[$n]['user'] = $array['file_user'];
			if($array['Lat'] == "" || $array['Lat'] == "0.0000"){$wifidb_aps_all[$n]['ValidGPS'] = 0;}else{$wifidb_aps_all[$n]['ValidGPS'] = 1;}

			$n++;
		}

		$dbcore->GeneratePages($total_rows, $from, $inc, $sort, $ord);
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_aps_all', $wifidb_aps_all);
		$dbcore->smarty->assign('sort', $sort);
		$dbcore->smarty->assign('ord', $ord);
		$dbcore->smarty->assign('from', $from);
		$dbcore->smarty->assign('inc', $inc);
		$dbcore->smarty->assign('points', $total_rows);
		$dbcore->smarty->display('all_aps.tpl');
	break;
}
?>