<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
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
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		if((int)@$_REQUEST['xml'] === 1){$xml = 1;}else{$xml = 0;}#output xml instead of creating a download
		if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels in kml file. by default labels are not shown.
		$from   =	filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
		$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
		if(!is_numeric($from)){$from = 0;}
		if(!is_numeric($inc)){$inc = 50000;}		

		$sql = "SELECT Count(AP_ID) As ap_count\n"
			. "FROM wifi_ap\n"
			. "WHERE\n"
			. "	File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND file_user LIKE ?)";
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$newArray = $result->fetch(2);
		$ap_count = $newArray['ap_count'];
		if($ap_count > $inc)
		{
			$ldivs = ceil($ap_count / $inc);
			$dbcore->smarty->assign('user', $user);
			$dbcore->smarty->assign('inc', $inc);
			$dbcore->smarty->assign('count', $ap_count);
			$dbcore->smarty->assign('ldivs', $ldivs);
			$dbcore->smarty->assign('xml', $xml);
			$dbcore->smarty->assign('labeled', $labeled);
			$dbcore->smarty->display('gpx_segments.tpl');
			break;
		}

		$url = $dbcore->URL_PATH.'api/gpx.php?xml='.$json.'&func=exp_user_all&user='.$user.'&labeled='.$labeled;
		header('Location: ' . $url);

		break;
}
?>
