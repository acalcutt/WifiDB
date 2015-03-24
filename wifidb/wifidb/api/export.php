<?php
/*
export.inc.php
Copyright (C) 2015 Andrew Calcutt
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
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');
$func=$_REQUEST['func'];

switch($func)
{
		case "exp_user_all_kml":
			$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
			
			$sql = "SELECT `id` FROM `wifi`.`user_imports` WHERE `username` LIKE ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $user, PDO::PARAM_STR);
			$prep->execute();
			$fetch_imports = $prep->fetchAll();
			$results="";
			foreach($fetch_imports as $import)
			{
				$expid = $import['id'];
				$results .= $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_user_list&#x26;row='.$expid, $user.'-'.$expid, 1, 0, "onInterval", 3600);
			}
			
			if($results == "")
			{
				echo 'This export has no APs with gps. No KMZ file has been exported';
				break;
			}
			else
			{
				$user_fn = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
				$title = $user."'s APs";
				$results = $dbcore->createKML->createFolder($user, $results, 0);
				$results = $dbcore->createKML->createKMLstructure("$user AP's", $results);		
			
				$dbcore->Zip->addFile($results, 'doc.kml');
				$results = $dbcore->Zip->getZipData();
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.$user_fn.'.kmz"');
				echo $results;
				break;
			}
			
		case "exp_user_list":
			$row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
			$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `id` = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $row, PDO::PARAM_INT);
			$prep->execute();
			$dbcore->sql->checkError(__LINE__, __FILE__);
			$fetch = $prep->fetch();
			
			$results = $dbcore->export->UserListKml($fetch['points'], $fetch['username'], $fetch['title'], $fetch['date']);
			if($results == "")
			{
				echo 'This export has no APs with gps. No KMZ file has been exported';
				break;
			}
			else
			{
				$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);
				$results = $dbcore->createKML->createKMLstructure($title, $results);		
			
				$dbcore->Zip->addFile($results, 'doc.kml');
				$results = $dbcore->Zip->getZipData();
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="'.$title.'.kmz"');
				echo $results;
				break;
			}
			break;
		default:
				echo 'No function has been given...what am I supposed to do with this request?';
				break;
		break;
}