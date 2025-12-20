<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
Copyright (C) 2018 Andrew Calcutt

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
define("SWITCH_EXTRAS", "export");

include('../lib/init.inc.php');

$func=$_REQUEST['func'];
switch($func)
{
	default:
		$dbcore->smarty->assign('wifidb_page_label', 'All Live Access Points Page');

		if (filter_has_var(INPUT_GET, "from")) {(int)$from = htmlspecialchars(filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT));}else{(int)$from = 0;}
		if (filter_has_var(INPUT_GET, "to")) {(int)$to = htmlspecialchars(filter_input(INPUT_GET, 'to', FILTER_SANITIZE_NUMBER_INT));}else{(int)$to = 500;}
		if (filter_has_var(INPUT_GET, "ord")) {$ord = htmlspecialchars(filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_ENCODED));}else{$ord = "DESC";}
		if (filter_has_var(INPUT_GET, "sort")) {$sort = htmlspecialchars(filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_ENCODED));}else{$sort = "la";}
		if (filter_has_var(INPUT_GET, "view")) {(int)$view = htmlspecialchars(filter_input(INPUT_GET, 'view', FILTER_SANITIZE_NUMBER_INT));}else{(int)$view = 86400;}
		
		switch($view)
		{
			case 1800:
				$interval_v = 30;
				$interval_i = "minute";
				$intervalt = "30 Minutes";
				break;
			case 3600:
				$interval_v = 60;
				$interval_i = "minute";
				$intervalt = "60 Minutes";
				break;
			case 7200:
				$interval_v = 2;
				$interval_i = "hour";
				$intervalt = "2 Hours";
				break;
			case 21600:
				$interval_v = 6;
				$interval_i = "hour";
				$intervalt = "6 Hours";
				break;
			case 86400:
				$interval_v = 1;
				$interval_i = "day";
				$intervalt = "1 Day";
				break;
			case 604800:
				$interval_v = 1;
				$interval_i = "week";
				$intervalt = "1 Week";
				break;
			default:
				$interval_v = 1;
				$interval_i = "day";
				$intervalt = "1 Day";
		}
		
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT COUNT(*) FROM `live_aps` WHERE la >= DATE_SUB(NOW(),INTERVAL {$interval_v} {$interval_i})";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT COUNT(*) FROM [live_aps] WHERE [la] >= dateadd({$interval_i}, -{$interval_v}, getdate())";}
		$sqlprep = $dbcore->sql->conn->prepare($sql);       
		$sqlprep->execute();
		$total_rows = $sqlprep->fetchColumn();
		
		$liveaps = array();
		$row_color = 1;
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `id`, `ssid`, `mac`, `radio`, `chan`, `auth`, `encry`, `sectype`, `sig` , `fa`, `la`, `username`, `Label`, `lat`, `long` FROM `live_aps` WHERE la >= DATE_SUB(NOW(),INTERVAL {$interval_v} {$interval_i}) ORDER BY `{$sort}` {$ord} LIMIT {$from}, {$to}";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [id], [ssid], [mac], [radio], [chan], [auth], [encry], [sectype], [sig] , [fa], [la], [username], [Label], [lat], [long] FROM [live_aps] WHERE [la] >= dateadd({$interval_i}, -{$interval_v}, getdate()) ORDER BY [{$sort}] {$ord} OFFSET {$from} ROWS FETCH NEXT {$to} ROWS ONLY";}
		$prep = $dbcore->sql->conn->query($sql);
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			if($row_color == 1){$row_color = 0; $color = "light";}
			else{$row_color = 1; $color = "dark";}
			
			if($ap['lat'] == "0.0000")
			{
				$validgps = 0;
			}else
			{
				$validgps = 1;
			}
			
			
			if($ap['ssid'] == '')
			{
				$ssid = '[Blank SSID]';
			}
			elseif(!ctype_print($ap['ssid']))
			{
				$ssid = '['.$ap['ssid'].']';
			}
			else
			{
				$ssid = $ap['ssid'];
			}

			$liveaps[] = array(
						"id" => $ap['id'],
						"class" => $color,
						"validgps" => $validgps,
						"ssid" => $ssid,
						"mac" => $ap['mac'],
						"radio" => $ap['radio'],
						"chan" => $ap['chan'],						
						"auth" => $ap['auth'],
						"encry" => $ap['encry'],
						"sectype" => $ap['sectype'],
						"sig" => $ap['sig'],
						"fa"   => $ap['fa'],
						"la"   => $ap['la'],
						"username"   => $ap['username'],
						"label"   => $ap['Label'],
						"lat"   => $ap['lat'],
						"long"   => $ap['long']
						);
		}
		$dbcore->GeneratePages($total_rows, $from, $to, $sort, $ord, "", "", "", "", "", "", "", "", $view);
		$dbcore->smarty->assign('from', $from);
		$dbcore->smarty->assign('to', $to);
		$dbcore->smarty->assign('ord', $ord);
		$dbcore->smarty->assign('sort', $sort);
		$dbcore->smarty->assign('view', $view);
		$dbcore->smarty->assign('intervalt', $intervalt);
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_all_live_aps' , $liveaps);
		$dbcore->smarty->display('live_aps.tpl');
		break;
}