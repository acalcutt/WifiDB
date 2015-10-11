<?php
/*
export.inc.php
Copyright (C) 2011 Phil Ferland
This is all the Export functions, text files, csv, kml, and vs1

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
define("SWITCH_EXTRAS", "");
$func=$_GET['func'];

switch($func)
{
		#--------------------------
		case "exp_user_all_kml":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export All User APs');
			$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
			$results = $dbcore->export->UserAll($user);
			$dbcore->smarty->assign('results', $results);
			$dbcore->smarty->display('export_results.tpl');
			break;
		#--------------------------
		case "exp_user_list":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export User List');
			$row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
			$result = $dbcore->export->UserList($row);
			$dbcore->smarty->assign('results', $result);
			$dbcore->smarty->display('export_results.tpl');
			break;
		#--------------------------
		case "exp_all_signal":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export All Signals for AP');
			$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
			$from = (int)($_REQUEST['from'] ? $_REQUEST['from']: NULL);
			$limit = (int)($_REQUEST['limit'] ? $_REQUEST['limit']: NULL);
			$result = $dbcore->export->SingleApSignal3d($id,$limit,$from);
			$dbcore->smarty->assign('results', $result);
			$dbcore->smarty->display('export_results.tpl');
			break;
		#--------------------------
		case "exp_single_ap":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export Single AP');
			$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
			$result = $dbcore->export->SingleAp($id);
			$dbcore->smarty->assign('results', $result);
			$dbcore->smarty->display('export_results.tpl');
			break;
		#--------------------------
		case "exp_search":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export Search Results');
			$ord	=   filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
			$sort   =	filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
			$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
			$inc	=	filter_input(INPUT_GET, 'to', FILTER_SANITIZE_NUMBER_INT);
			
			if(@$_REQUEST['ssid'])
			{
				$ssid   =   $_REQUEST['ssid'];
			}else
			{
				$ssid   =   "";
			}
			
			if(@$_REQUEST['mac'])
			{
				$mac	=   $_REQUEST['mac'];
			}else
			{
				$mac	=   "";
			}
			
			if(@$_REQUEST['radio'])
			{
				$radio  =   $_REQUEST['radio'];
			}else
			{
				$radio  =   "";
			}
			
			if(@$_REQUEST['chan'])
			{
				$chan   =   $_REQUEST['chan'];
			}else
			{
				$chan   =   "";
			}
			
			if(@$_REQUEST['auth'])
			{
				$auth   =   $_REQUEST['auth'];
			}else
			{
				$auth   =   "";
			}
			
			if(@$_REQUEST['encry'])
			{
				$encry  =   $_REQUEST['encry'];
			}else
			{
				$encry  =   "";
			}
			if ($from == ""){$from = NULL;}
			if ($inc == ""){$inc = NULL;}
			if ($ord == ""){$ord = "ASC";}
			if ($sort == ""){$sort = "ssid";}
			
			list($total_rows, $results_all, $save_url, $export_url) = $dbcore->Search($ssid, $mac, $radio, $chan, $auth, $encry, $ord, $sort, $from, $inc);
			$results = $dbcore->export->exp_search($results_all);
			$dbcore->smarty->assign('results', $results);
			$dbcore->smarty->display('export_results.tpl');
			
			break;
		#--------------------------
		default:
			define("SWITCH_EXTRAS", "");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export Page');

			$imports = array();
			$usernames = array();
			$sql = "SELECT `id`,`title`, `username`, `aps`, `date` FROM `user_imports` ORDER BY `username`, `title`";
			$result = $dbcore->sql->conn->query($sql);
			while($user_array = $result->fetch(2))
			{
				$imports[] = array(
								"id"=>$user_array["id"],
								"username"=>$user_array["username"],
								"title"=>$user_array["title"],
								"aps"=>$user_array["aps"],
								"date"=>$user_array["date"]
							 );
			}

			$sql = "SELECT `username` FROM `user_imports` ORDER BY `username`";
			$result = $dbcore->sql->conn->query($sql);
			while($user_array = $result->fetch(2))
			{
				$usernames[] = $user_array["username"];
			}
			$usernames = array_unique($usernames);

			$dbcore->smarty->assign('wifidb_export_imports_all', $imports);
			$dbcore->smarty->assign('wifidb_export_users_all', $usernames);
			$dbcore->smarty->display('export_index.tpl');
		break;
}