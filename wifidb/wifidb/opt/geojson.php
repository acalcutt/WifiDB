<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
fetch.php, fetches a single AP's details.
Copyright (C) 2021 Andrew Calcutt

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
$dbcore->smarty->assign('func', $func);
switch($func)
{
	case "user_all":
		$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
		if((int)@$_REQUEST['json'] === 1){$json = 1;}else{$json = 0;}#output json instead of creating a download
		if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels in kml file. by default labels are not shown.
		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$limit	=	filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		if ($from == ""){$from = 0;}	
		if($limit == "")
		{
			if ($limit == ""){$limit = 50000;}
			if($dbcore->sql->service == "mysql")
				{
					$sql = "SELECT Count(AP_ID) As ap_count\n"
						. "FROM wifi_ap\n"
						. "WHERE\n"
						. "	File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND [user] LIKE ?)";
				}
			else if($dbcore->sql->service == "sqlsrv")
				{
					$sql = "SELECT Count(AP_ID) As ap_count\n"
						. "FROM wifi_ap\n"
						. "WHERE\n"
						. "	File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND [user] LIKE ?)";
				}
			$result = $dbcore->sql->conn->prepare($sql);
			$result->bindParam(1, $user, PDO::PARAM_STR);
			$result->execute();
			$newArray = $result->fetch(2);
			$ap_count = $newArray['ap_count'];
			if($ap_count > $limit)
			{
				$ldivs = ceil($ap_count / $limit);
				$dbcore->smarty->assign('user', $user);
				$dbcore->smarty->assign('limit', $limit);
				$dbcore->smarty->assign('count', $ap_count);
				$dbcore->smarty->assign('ldivs', $ldivs);
				$dbcore->smarty->assign('json', $json);
				$dbcore->smarty->assign('labeled', $labeled);
				$dbcore->smarty->display('geojson_segments.tpl');
				break;
			}
		}
		
		$url = $dbcore->URL_PATH.'api/geojson.php?json='.$json.'&func=exp_user_all&user='.$user.'&labeled='.$labeled;
		header('Location: ' . $url);

		break;
}
?>
