<?php
/*
userstats.php, user ap stats.
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

require '../lib/init.inc.php';

$user = filter_input(INPUT_GET, 'user', FILTER_SANITIZE_STRING);
$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
$ord = filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
$row = filter_input(INPUT_GET, 'row', FILTER_SANITIZE_NUMBER_INT);
$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_STRING);

if($user)
{
	$dbcore->smarty->assign("wifidb_page_label", "Stats for User: ".$user);
}
else
{
	$dbcore->smarty->assign("wifidb_page_label", "Users Statistics Page");
}

switch($func)
{
		case "allusers":
			$dbcore->AllUsers();
			$dbcore->smarty->assign('wifidb_imports_all' , $dbcore->all_users_data);
			$dbcore->smarty->display('users_index.tpl');
			break;
		#-------------
		case "alluserlists":
			$dbcore->UsersLists($user);
			$dbcore->smarty->assign('wifidb_user_details', $dbcore->user_all_imports_data);
			$dbcore->smarty->display('user_overview.tpl');
			break;
		#-------------
		case "useraplist":
			$sorts=array("AP_ID","fa","la","points");
			if(!in_array($sort, $sorts)){$sort = "AP_ID";}
			$ords=array("ASC","DESC");
			if(!in_array($ord, $ords)){$ord = "DESC";}
			
			$dbcore->UserAPList($row, $sort, $ord);
			$dbcore->smarty->assign('wifidb_all_user_aps' , $dbcore->users_import_aps);
			$dbcore->smarty->assign('wifidb_all_user_row' , $row);
			$dbcore->smarty->assign('sort' , $sort);
			$dbcore->smarty->assign('ord' , $ord);
			$dbcore->smarty->display('user_import_aps.tpl');
			break;
		#-------------
		case "allap":
			$dbcore->AllUsersAPs($user);
			$dbcore->smarty->assign('wifidb_all_user_aps' , $dbcore->all_users_aps);
			$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
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