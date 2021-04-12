<?php
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
$func=$_GET['func'];

switch($func)
{
	case "user_all":
		define("SWITCH_SCREEN", "HTML");
		define("SWITCH_EXTRAS", "export");
		include('../lib/init.inc.php');
		$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
		if((int)@$_REQUEST['xml'] === 1){$xml = 1;}else{$xml = 0;}#output json instead of creating a download
		$inc	=	filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
		$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
		if ($from == ""){$from = 0;}	
		if ($inc == ""){$inc = 25000;}

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
			$dbcore->smarty->display('kmz_segments.tpl');
			break;
		}
		else
		{
			$url = $dbcore->URL_PATH.'api/export.php?xml='.$xml.'&func=exp_user_all&user='.$user;
			header('Location: ' . $url);
		}

		break;
}