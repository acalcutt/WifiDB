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
$func=$_GET['func'];

switch($func)
{
		#--------------------------
		case "user_all":
			define("SWITCH_SCREEN", "HTML");
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
			if((int)@$_REQUEST['xml'] === 1){$xml = 1;}else{$xml = 0;}#output json instead of creating a download
			$limit	=	filter_input(INPUT_GET, 'limit', FILTER_SANITIZE_NUMBER_INT);
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
			if ($from == ""){$from = 0;}	
			if ($limit == ""){$limit = 25000;}
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

		#--------------------------
		case "exp_user_list":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export User List');
			$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
			
			if(!is_int($id))
			{
				throw new ErrorException('$id value for export::UserList() is NaN');
				return 0;
			}

			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT `user`, `title`, `date` FROM `files` WHERE `id` = ?";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "SELECT [user], [title], [date] FROM [files] WHERE [id] = ?";}
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $id, PDO::PARAM_INT);
			$prep->execute();
			$dbcore->sql->checkError(__LINE__, __FILE__);
			$fetch = $prep->fetch();

			$ListKML = $dbcore->export->UserList($id);
			if($ListKML['data'] !== "")
			{
				$final_box = $dbcore->export->FindBox($ListKML['box']);
				$KML_region = $dbcore->createKML->PlotRegionBox($final_box, uniqid());
				$KML_data = $KML_region.$ListKML['data'];
				
				$KML_data = $dbcore->createKML->createFolder($fetch['username']." - ".$fetch['title']." - ".$fetch['date'], $KML_data, 0);
				$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);

				$kmz_filename = $dbcore->kml_out.$title.".kmz";
				$KML_data = $dbcore->createKML->createKMLstructure($title, $KML_data);
				$dbcore->Zip->addFile($KML_data, 'doc.kml');
				$dbcore->Zip->setZipFile($kmz_filename);
				$dbcore->Zip->getZipFile();
				
				if (file_exists($kmz_filename)) 
				{
					$results = array("mesg" => 'File is ready: <a href="'.$dbcore->kml_htmlpath.$title.'.kmz">'.$title.'.kmz</a>');
				}
				else
				{
					$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
				}	
			}
			else
			{
				$results = array("mesg" => 'This export has no APs with gps. No KMZ file has been exported');
			}

			$dbcore->smarty->assign('results', $results);
			$dbcore->smarty->display('export_results.tpl');
			break;
		#--------------------------
		case "exp_single_ap":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export Single AP');
			$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
			
			if(!is_int($id))
			{
				throw new ErrorException('$id value for export::SingleAp() is NaN');
				return 0;
			}

			list($KML_data, $export_ssid) = $dbcore->export->ExportSingleAp($id, 0, 0);
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $id."-".$export_ssid);
			$KML_data = $dbcore->createKML->createKMLstructure($title, $KML_data);
			$kmz_filename = $dbcore->kml_out.$title.".kmz";
			$dbcore->Zip->addFile($KML_data, 'doc.kml');
			$dbcore->Zip->setZipFile($kmz_filename);
			$dbcore->Zip->getZipFile();
			if (file_exists($kmz_filename)) 
			{
				$results = array("mesg" => 'File is ready: <a href="'.$dbcore->kml_htmlpath.$title.'.kmz">'.$title.'.kmz</a>');
			}
			else
			{
				$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
			}

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
			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT `id`,`title`, `user`, `aps`, `date` FROM `files` ORDER BY `user`, `title`";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "SELECT [id],[title], [user], [aps], [date] FROM [files] ORDER BY [user], [title]";}
			$result = $dbcore->sql->conn->query($sql);
			while($user_array = $result->fetch(2))
			{
				$imports[] = array(
								"id"=>$user_array["id"],
								"username"=>$user_array["user"],
								"title"=>$user_array["title"],
								"aps"=>$user_array["aps"],
								"date"=>$user_array["date"]
							 );
			}

			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT `user` FROM `files` ORDER BY `user`";}
			else if($dbcore->sql->service == "sqlsrv")
				{$$sql = "SELECT [user] FROM [files] ORDER BY [user]";}
			$result = $dbcore->sql->conn->query($sql);
			while($user_array = $result->fetch(2))
			{
				$usernames[] = $user_array["user"];
			}
			$usernames = array_unique($usernames);

			$dbcore->smarty->assign('wifidb_export_imports_all', $imports);
			$dbcore->smarty->assign('wifidb_export_users_all', $usernames);
			$dbcore->smarty->display('export_index.tpl');
		break;
}