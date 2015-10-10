<?php
error_reporting(0);
@ini_set('display_errors', 0);
/*
Copyright (C) 2015 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');

if((int)@$_REQUEST['all'] === 1){$all = 1;}else{$all = 0;}#Show both old and new access points. by default only new APs are shown.
if((int)@$_REQUEST['new_icons'] === 1){$new_icons = 1;}else{$new_icons = 0;}#use new AP icons instead of old AP icons in kml file. by default old icons are shown.
if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels in kml file. by default labels are not shown.
if((int)@$_REQUEST['xml'] === 1){$xml = 1;}else{$xml = 0;}#output xml/kml insteand of creating a kmz. by default a kmz is created.
$func=$_REQUEST['func'];
switch($func)
{
		case "exp_combined_netlink":
			$results = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_latest_netlink', "Newest AP", 1, 0, "onChange", 86400, 1);
			$results .= $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_daily_netlink', "Daily APs", 1, 0, "onChange", 86400, 1);
			$results .= $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_all_netlink', "All APs", 0, 0, "onChange", 86400, 1);
			$results = $dbcore->createKML->createKMLstructure("Combined Network Link", $results);
			$file_name = "Combined_NetworkLink.kmz";
			break;
			
		case "exp_all_netlink":
			$Full = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_all&#x26;labeled=0&#x26;all=0&#x26;new_icons=0', "All Exports (No Label)", 1, 0, "onInterval", 86400, 1);
			$Full_Labeled = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_all&#x26;labeled=1&#x26;all=0&#x26;new_icons=0', "All Exports (Label)", 0, 0, "onInterval", 86400, 1);
			$results = $dbcore->createKML->createKMLstructure("All Exports Network Link", $Full.$Full_Labeled);
			if($labeled){$file_name = "All_Export_Labeled_NetworkLink.kmz";}else{$file_name = "All_Export_NetworkLink.kmz";}
			break;

		case "exp_daily_netlink":
			$Daily = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_daily&#x26;labeled=0&#x26;all=1&#x26;new_icons=1', "Daily AP (No Label)", 1, 0, "onInterval", 3600, 1);
			$Daily_Labeled = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_daily&#x26;labeled=1&#x26;all=1&#x26;new_icons=1', "Daily AP (Label)", 0, 0, "onInterval", 3600, 1);
			$results = $dbcore->createKML->createKMLstructure("Daily Exports Network Link", $Daily.$Daily_Labeled);
			if($labeled){$file_name = "Daily_Export_Labeled_NetworkLink.kmz";}else{$file_name = "Daily_Export_NetworkLink.kmz";}
			break;

		case "exp_latest_netlink":
			$Newest_FT = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_latest&#x26;labeled=0&#x26;new_icons=1', "Newest AP w/ Fly To (No Label)", 0, 1, "onInterval", 60, 1);
			$Newest_Labeled_FT = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_latest&#x26;labeled=1&#x26;new_icons=1', "Newest AP w/ Fly To (Label)", 1, 1, "onInterval", 60, 1);
			$Newest = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_latest&#x26;labeled=0&#x26;new_icons=1', "Newest AP (No Label)", 0, 0, "onInterval", 60, 1);
			$Newest_Labeled = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_latest&#x26;labeled=1&#x26;new_icons=1', "Newest AP (Label)", 0, 0, "onInterval", 60, 1);
			$results = $dbcore->createKML->createKMLstructure("Latest AP Network Link", $Newest_FT.$Newest_Labeled_FT.$Newest.$Newest_Labeled);
			if($labeled){$file_name = "Latest_Labeled_NetworkLink.kmz";}else{$file_name = "Latest_NetworkLink.kmz";}
			break;
			
		case "exp_user_netlink":
			$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
			$results = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_user_all&#x26;user='.$user.'&#x26;labeled='.$labeled.'&#x26;all='.$all.'&#x26;new_icons='.$new_icons, $user, 1, 0, "onInterval", 86400);
			$user_fn = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
			$title = $user."'s Network Link";
			$results = $dbcore->createKML->createKMLstructure($title , $results);
			$user_fn = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
			if($labeled){$file_name = $user_fn."NetworkLink_Labeled.kmz";}else{$file_name = $user_fn."_NetworkLink.kmz";}
			break;
			
		case "exp_ap_netlink":
			$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
			#Get SSID
			$sql = "SELECT ssid FROM `wifi`.`wifi_pointers` WHERE `id` = '$id' And `lat` != '0.0000'";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $row, PDO::PARAM_INT);
			$prep->execute();
			$dbcore->sql->checkError(__LINE__, __FILE__);
			$fetch = $prep->fetch();
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['ssid'].'-'.$id);
			#Create Network Link
			$results = $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_ap&#x26;id='.$id.'&#x26;labeled='.$labeled.'&#x26;new_icons='.$new_icons, $title, 1, 0, "onInterval", 86400);
			$results = $dbcore->createKML->createKMLstructure($title , $results);
			if($labeled){$file_name = $title."NetworkLink_Labeled.kmz";}else{$file_name = $title."_NetworkLink.kmz";}
			break;
			
		case "exp_all":
			$sql = "SELECT DISTINCT(username) FROM `wifi`.`user_imports` ORDER BY `username` ASC";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->execute();
			$fetch_user = $prep->fetchAll();
			$results="";
			foreach($fetch_user as $user)
			{
				$username = $user['username'];
				$sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `username` LIKE '$username' And `lat` != '0.0000' AND `mac` != '00:00:00:00:00:00' LIMIT 1";
				$result = $dbcore->sql->conn->query($sql);
				if($result->rowCount() > 0)
				{
					#valid results found, add network link for this user
					$results .= $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_user_all&#x26;user='.$username.'&#x26;labeled='.$labeled.'&#x26;all='.$all.'&#x26;new_icons='.$new_icons, $username, 1, 0, "onChange", 86400);
				}
			}
			
			if($results == ""){$results = $dbcore->createKML->createFolder("No Exports with GPS", $results, 0);}else{$results = $dbcore->createKML->createFolder("All Exports", $results, 0);}
			$results = $dbcore->createKML->createKMLstructure("All Exports", $results);
			if($labeled){$file_name = "All_Exports_Labeled.kmz";}else{$file_name = "All_Exports.kmz";}
			break;

		case "exp_daily":
			$date = (empty($_REQUEST['date'])) ? date($dbcore->export->date_format) : $_REQUEST['date'];
			$date_search = $date."%";
			$sql = "SELECT `id` , `points`, `username`, `title`, `date` FROM `wifi`.`user_imports` WHERE `date` LIKE '$date_search'";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->execute();
			$fetch_imports = $prep->fetchAll();
			$results="";
			foreach($fetch_imports as $import)
			{
				#Check is list has access points with gps and non blank mac
				$stage_pts = explode("-", $import['points']);
				foreach($stage_pts as $point)
				{
					if($point)
					{
						list($id, $new_old) = explode(":", $point);
						if($new_old == 1){continue;}
						$sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = '$id' And `lat` != '0.0000' AND `mac` != '00:00:00:00:00:00'";
						$result = $dbcore->sql->conn->query($sql);
						if($result->rowCount() > 0)
						{
							#valid results found, add network link and exit check
							$results .= $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_list&#x26;row='.$import['id'].'&#x26;labeled='.$labeled.'&#x26;all='.$all.'&#x26;new_icons='.$new_icons, $import['date'].'-'.$import['title'].'-'.$import['id'], 1, 0, "onInterval", 86400);
							break;
						}
					}
				}
			}
			
			if($results == ""){$results = $dbcore->createKML->createFolder("No Daily Exports with GPS", $results, 0);}else{$results = $dbcore->createKML->createFolder("Daily Exports", $results, 0);}
			$results = $dbcore->createKML->createKMLstructure("Daily Exports", $results);
			if($labeled){$file_name = "Daily_Exports_Labeled.kmz";}else{$file_name = "Daily_Exports.kmz";}
			break;
			
		case "exp_latest":
			$results = $dbcore->export->ExportCurrentAPkmlApi($labeled, $new_icons);
			if($labeled){$file_name = "Latest_Labeled.kmz";}else{$file_name = "Latest.kmz";}
			break;

		case "exp_user_all":
			$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
			$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `username` LIKE ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $user, PDO::PARAM_STR);
			$prep->execute();
			$fetch_imports = $prep->fetchAll();
			$results="";
			foreach($fetch_imports as $import)
			{
				#Check is list has access points with gps and non blank mac
				$stage_pts = explode("-", $import['points']);
				foreach($stage_pts as $point)
				{
					if($point)
					{
						list($id, $new_old) = explode(":", $point);
						if($new_old == 1){continue;}
						$sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = '$id' And `lat` != '0.0000' AND `mac` != '00:00:00:00:00:00'";
						$result = $dbcore->sql->conn->query($sql);
						if($result->rowCount() > 0)
						{
							#valid results found, add network link and exit check
							$results .= $dbcore->createKML->createNetworkLink($dbcore->URL_PATH.'api/export.php?func=exp_list&#x26;row='.$import['id'].'&#x26;labeled='.$labeled.'&#x26;all='.$all.'&#x26;new_icons='.$new_icons, $import['date'].'-'.$import['title'].'-'.$import['id'], 1, 0, "onChange", 86400);
							break;
						}
					}
				}
			}
			
			if($results == ""){$results .= $dbcore->createKML->createFolder("No User Exports with GPS", $results, 0);}
			
			$user_fn = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $user);
			$results = $dbcore->createKML->createKMLstructure("$user_fn AP's", $results);
			if($labeled){$file_name = $user_fn."_Labeled.kmz";}else{$file_name = $user_fn.".kmz";}
			break;
			
		case "exp_list":
			$row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
			$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `id` = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $row, PDO::PARAM_INT);
			$prep->execute();
			$dbcore->sql->checkError(__LINE__, __FILE__);
			$fetch = $prep->fetch();
			
			if($all){$only_new = 0;}else{$only_new = 1;}
			$ListKML = $dbcore->export->UserListKml($fetch['points'], $fetch['username'], $fetch['title'], $fetch['date'], $labeled, $only_new, $new_icons, 1);
			$results = $ListKML['region'].$ListKML['data'];
			if($results == ""){$results .= $dbcore->createKML->createFolder("No APs with GPS", $results, 0);}
			
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $fetch['title']);
			$results = $dbcore->createKML->createFolder($title, $results, 0);
			$results = $dbcore->createKML->createKMLstructure($title, $results);
			if($labeled){$file_name = $title."_Labeled.kmz";}else{$file_name = $title.".kmz";}
			break;
			
		case "exp_ap":
			$id = (int)($_REQUEST['id'] ? $_REQUEST['id']: 0);
			if(isset($_REQUEST['from'])){$from = (int)($_REQUEST['from'] ? $_REQUEST['from']: NULL);}else{$from=NULL;}
			if(isset($_REQUEST['limit'])){$limit = (int)($_REQUEST['limit'] ? $_REQUEST['limit']: NULL);}else{$limit=NULL;}
			
			list($results, $export_ssid) = $dbcore->export->SingleApKml($id, $limit, $from, $labeled, $new_icons);
			
			$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $id."-".$export_ssid);
			$results = $dbcore->createKML->createKMLstructure($title, $results);
			if($labeled){$file_name = $title."_Labeled.kmz";}else{$file_name = $title.".kmz";}
			break;
			
		default:
			echo 'No function or incorrect function has been given...what am I supposed to do with this request?';
			die();
}

if($xml)
{
	header("Content-type: text/xml");
}
else
{
	$dbcore->Zip->addFile($results, 'doc.kml');
	$results = $dbcore->Zip->getZipData();
	$download = (empty($_REQUEST['download'])) ? $file_name : $_REQUEST['download'];#Override export filename if set
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$download.'"');
}
echo $results;