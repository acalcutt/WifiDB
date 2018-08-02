<?php
error_reporting(1);
@ini_set('display_errors', 1);
/*
Copyright (C) 2015 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "export");

include('../lib/init.inc.php');

if((int)@$_REQUEST['all'] === 1){$all = 1;}else{$all = 0;}#Show both old and new access points. by default only new APs are shown.
if((int)@$_REQUEST['new_icons'] === 1){$new_icons = 1;}else{$new_icons = 0;}#use new AP icons instead of old AP icons in kml file. by default old icons are shown.
if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}#Show AP labels in kml file. by default labels are not shown.
if((int)@$_REQUEST['xml'] === 1){$xml = 1;}else{$xml = 0;}#output xml/kml insteand of creating a kmz. by default a kmz is created.
if((int)@$_REQUEST['debug'] === 1){$debug = 1;}else{$debug = 0;}#output extra debug stuff
$func=$_REQUEST['func'];
switch($func)
{
	case "exp_list":
		$row = (int)($_REQUEST['row'] ? $_REQUEST['row']: 0);
		$sql = "SELECT * FROM `user_imports` WHERE `id` = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $row, PDO::PARAM_INT);
		$prep->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$fetch = $prep->fetch();
		
		if($all){$only_new = 0;}else{$only_new = 1;}
		#echo $fetch['points'];
		$ListGeoJSON = $dbcore->export->UserListGeoJSON($fetch['points'], $labeled, $only_new, $new_icons);
		$results = $dbcore->createGeoJSON->createGeoJSONstructure($ListGeoJSON);
		if($labeled){$file_name = "Labeled.kmz";}else{$file_name ="unlabeled.kmz";}
		
		header('Content-type: application/json');
		echo $results;
		
		break;
		
		case "exp_user_all":
			$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
			$sql = "SELECT `id`, `points`, `username`, `title`, `date` FROM `user_imports` WHERE `username` LIKE ? AND `points` != ''";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $user, PDO::PARAM_STR);
			$prep->execute();
			$fetch_imports = $prep->fetchAll();
			$layer_source_all="";
			$layer_name_all="";
			foreach($fetch_imports as $import)
			{

					#echo $import['id']."-";
					$ml = $dbcore->createGeoJSON->CreateMapLayer($import['id']);
					$layer_source_all .= $ml['layer_source'];
					if($layer_name_all !== ''){$layer_name_all .=',';};
					$layer_name_all .= $ml['layer_name'];
					
					

			}
			
			#echo $layer_source_all.$layer_name_all;
			$dbcore->smarty->assign('layer_source_all', $layer_source_all);
			$dbcore->smarty->assign('layer_name_all', $layer_name_all);
			$dbcore->smarty->display('export_map.tpl');
		break;
		
		case "exp_user_list":
		$user = ($_REQUEST['user'] ? $_REQUEST['user'] : die("User value is empty"));
		$Import_Map_Data="";
		for ($i = 0; TRUE; $i++) {
			error_log("Processing pass $i");
			$row_count = 10000;	
			$offset = $i*$row_count ;

			$sql = "SELECT `mac`,`ssid`,`chan`,`radio`,`NT`,`sectype`,`auth`,`encry`,`BTx`,`OTx`,`FA`,`LA`,`lat`,`long`,`alt`,`manuf` FROM `wifi_pointers` WHERE `long` != '0.0000' AND `username` LIKE ? LIMIT $offset,$row_count";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $user, PDO::PARAM_STR);
			$prep->execute();
			$appointer = $prep->fetchAll();
			foreach($appointer as $ap)
			{
				#Get AP KML
				$ap_info = array(
				"id" => $id,
				"new_ap" => $new_icons,
				"named" => $named,
				"mac" => $ap['mac'],
				"ssid" => $ap['ssid'],
				"chan" => $ap['chan'],
				"radio" => $ap['radio'],
				"NT" => $ap['NT'],
				"sectype" => $ap['sectype'],
				"auth" => $ap['auth'],
				"encry" => $ap['encry'],
				"BTx" => $ap['BTx'],
				"OTx" => $ap['OTx'],
				"FA" => $ap['FA'],
				"LA" => $ap['LA'],
				"lat" => $dbcore->convert->dm2dd($ap['lat']),
				"long" => $dbcore->convert->dm2dd($ap['long']),
				"alt" => $ap['alt'],
				"manuf" => $ap['manuf'],
				);
				if($Import_Map_Data !== ''){$Import_Map_Data .=',';};
				$Import_Map_Data .=$dbcore->createGeoJSON->CreateApFeature($ap_info);
			}
			$results = $dbcore->createGeoJSON->createGeoJSONstructure($Import_Map_Data);
			if($labeled){$file_name = "Labeled.kmz";}else{$file_name ="unlabeled.kmz";}
			
			header('Content-type: application/json');
			echo $results;
		}	
		break;
		
}			


