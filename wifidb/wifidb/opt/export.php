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
		case "exp_user_all_kml":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export All User APs');
			$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
			
			$KML_data = $dbcore->export->UserAll($user);
			if($KML_data == "")
			{
				$results = array("mesg" => 'This export has no APs with gps. No KMZ file has been exported');
			}
			else
			{
				$user_fn = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
				$kmz_filename = $dbcore->kml_out.$user_fn.".kmz";
				#$this->verbosed("Writing KMZ for ".$user_fn." : ".$kmz_filename);
				$KML_data = $dbcore->createKML->createKMLstructure($user_fn, $KML_data);
				$dbcore->Zip->addFile($KML_data, 'doc.kml');
				$dbcore->Zip->setZipFile($kmz_filename);
				$dbcore->Zip->getZipFile();
				
				if (file_exists($kmz_filename)) 
				{
					$results = array("mesg" => 'File is ready: <a href="'.$dbcore->kml_htmlpath.$user_fn.'.kmz">'.$user_fn.'.kmz</a>');
				}
				else
				{
					$results = array("mesg" => 'Error: No kmz file... what am I supposed to do with that? :/');
				}
			}

			$dbcore->smarty->assign('results', $results);
			$dbcore->smarty->display('export_results.tpl');
			break;
		#--------------------------
		case "exp_user_list":
			define("SWITCH_EXTRAS", "export");
			include('../lib/init.inc.php');
			$dbcore->smarty->assign('wifidb_page_label', 'Export User List');
			$row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
			
			if(!is_int($row))
			{
				throw new ErrorException('$row value for export::UserList() is NaN');
				return 0;
			}
			
			$sql = "SELECT * FROM `user_imports` WHERE `id` = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $row, PDO::PARAM_INT);
			$prep->execute();
			$dbcore->sql->checkError(__LINE__, __FILE__);
			$fetch = $prep->fetch();

			$ListKML = $dbcore->export->UserList($fetch['points']);
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
			

				
			
			$KML_data = "";
			foreach($results_all as $ResultAP) {
				
				list($KML_AP_data, $export_ssid) = $dbcore->export->ExportSingleAp($ResultAP['id'], 0);
				
				if($KML_AP_data){$KML_data .= $KML_AP_data;}
			}

			if($KML_data == "")
			{
				$results = array("mesg" => 'This export has no APs with gps. No KMZ file has been exported');
			}
			else
			{
				$KML_data = $dbcore->createKML->createFolder("Search Export", $KML_data, 0);
				$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), "Search_Export");
				$kmz_filename = $dbcore->kml_out.$title.".kmz";
				#$this->verbosed("Writing KMZ for ".$title." : ".$kmz_filename);
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