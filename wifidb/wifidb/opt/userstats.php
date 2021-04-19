<?php
/*
userstats.php, user ap stats.
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "export");

require '../lib/init.inc.php';

$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);
$user = filter_input(INPUT_GET, 'user', FILTER_SANITIZE_STRING);
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
$ord = filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
$row = filter_input(INPUT_GET, 'row', FILTER_SANITIZE_NUMBER_INT);
$from = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
$inc = filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);


$ords=array("ASC","DESC");
if(!in_array($ord, $ords)){$ord = "DESC";}
if(!is_numeric($from)){$from = 0;}
if(!is_numeric($inc)){$inc = 100;}

if($user)
{
	$dbcore->smarty->assign("wifidb_page_label", "Stats for User: ".$user);
}
else
{
	$dbcore->smarty->assign("wifidb_page_label", "Users Statistics Page");
}

$dbcore->smarty->assign('func' , $func);
switch($func)
{
		case "allusers":
			$sorts=array("file_user","FileCount","ValidGPS","ApCount","GpsCount","NewAPPercent","FirstImport","LastImport");
			if(!in_array($sort, $sorts)){$sort = "file_user";}
			$dbcore->AllUsers($sort, $ord, $from, $inc);
			$dbcore->smarty->assign('wifidb_imports_all' , $dbcore->all_users_data);
			$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
			$dbcore->smarty->assign('sort' , $sort);
			$dbcore->smarty->assign('ord' , $ord);
			$dbcore->smarty->assign('from' , $from);
			$dbcore->smarty->assign('inc' , $inc);
			$dbcore->smarty->display('users_index.tpl');
			break;
		#-------------
		case "alluserlists":
			$sorts=array("id","file_orig","title","notes","aps","gps","NewAPPercent","date");
			if(!in_array($sort, $sorts)){$sort = "id";}
			
			$dbcore->UsersLists($user, $sort, $ord, $from, $inc);
			
			$dbcore->smarty->assign('wifidb_user_details', $dbcore->user_all_imports_data);
			$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
			$dbcore->smarty->assign('sort' , $sort);
			$dbcore->smarty->assign('ord' , $ord);
			$dbcore->smarty->display('user_overview.tpl');
			break;
		#-------------
		case "useraplist":
			$sorts=array("New","AP_ID","SSID","BSSID","AUTH","ENCR","RADTYPE","CHAN","fa","la","list_points","points");
			if(!in_array($sort, $sorts)){$sort = "AP_ID";}
			
			$dbcore->UserAPList($row, $sort, $ord);
			$CellUserListArray = $dbcore->export->CellUserListArray($row, $from, 1);
			$dbcore->smarty->assign('wifidb_all_user_aps' , $dbcore->users_import_aps);
			$dbcore->smarty->assign('wifidb_all_user_row' , $row);
			$dbcore->smarty->assign('cids' , $CellUserListArray['count']);
			$dbcore->smarty->assign('sort' , $sort);
			$dbcore->smarty->assign('ord' , $ord);
			$dbcore->smarty->display('user_import_aps.tpl');
			break;
		case "cidlist":
			$sorts=array("new","cell_id","ssid","mac","authmode","type","chan","fa","la","list_points","points");
			if(!in_array($sort, $sorts)){$sort = "cell_id";}
			
			$UserListArray = $dbcore->export->UserListArray($row, $from, 1);
			$CellUserListArray = $dbcore->export->CellUserListArray($row, $from, $inc, $sort, $ord, 0, 0, 0, 0, "'BT','BLE'");
			$cell_info = $CellUserListArray['data'];
			$file_info = $CellUserListArray['file_info'];
			
			$dbcore->smarty->assign('points', $cell_info);
			$dbcore->smarty->assign('file_info', $file_info);
			$dbcore->smarty->assign('wifidb_all_user_row' , $row);
			$dbcore->smarty->assign('aps' , $UserListArray['count']);
			$dbcore->smarty->assign('sort' , $sort);
			$dbcore->smarty->assign('ord' , $ord);
			$dbcore->smarty->display('user_import_cids.tpl');
			break;
		case "btlist":
			$sorts=array("new","cell_id","ssid","mac","authmode","type","chan","fa","la","list_points","points");
			if(!in_array($sort, $sorts)){$sort = "cell_id";}
			
			$UserListArray = $dbcore->export->UserListArray($row, $from, 1);
			$CellUserListArray = $dbcore->export->CellUserListArray($row, $from, $inc, $sort, $ord, 0, 0, 0, 0, "", "'BT','BLE'");
			$cell_info = $CellUserListArray['data'];
			$file_info = $CellUserListArray['file_info'];
			
			$dbcore->smarty->assign('points', $cell_info);
			$dbcore->smarty->assign('file_info', $file_info);
			$dbcore->smarty->assign('wifidb_all_user_row' , $row);
			$dbcore->smarty->assign('aps' , $UserListArray['count']);
			$dbcore->smarty->assign('sort' , $sort);
			$dbcore->smarty->assign('ord' , $ord);
			$dbcore->smarty->display('user_import_cids.tpl');
			break;
		#-------------
		case "allap":
			$sorts=array("AP_ID","SSID","BSSID","AUTH","ENCR","RADTYPE","CHAN","fa","la","points");
			if(!in_array($sort, $sorts)){$sort = "AP_ID";}
			
			$dbcore->AllUsersAPs($user, $sort, $ord, $from, $inc);
			$dbcore->smarty->assign('wifidb_all_user_aps' , $dbcore->all_users_aps);
			$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
			$dbcore->smarty->assign('sort' , $sort);
			$dbcore->smarty->assign('ord' , $ord);
			$dbcore->smarty->display('user_all_aps.tpl');
			break;
		#-------------
		case "":
			$dbcore->all_users();
			$dbcore->smarty->assign('wifidb_imports_all' , $dbcore->all_users_data);
			$dbcore->smarty->display('users_index.tpl');
			break;
}
?>