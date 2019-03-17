<?php
/*
Frontend.inc.php, Functions to generate the frontend data and some views..
Copyright (C) 2011 Phil Ferland, 2015 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/


#===========================================================#
#   WiFiDB Database Class that holds DB based functions	 #
#===========================================================#
class frontend extends dbcore
{
	#===========================#
	#   __construct (default)   #
	#===========================#
	function __construct($config)
	{
		parent::__construct($config);
		if(strtolower(SWITCH_EXTRAS) != "api")
		{
			require_once($config['wifidb_install'].'/lib/misc.inc.php');
			$this->sec->LoginCheck();
			$this->meta = new stdClass();
			$this->meta->ads = $config['ads'];
			$this->meta->tracker = $config['tracker'];
			$this->meta->header = $config['header'];

			$this->smarty = new Smarty();
			$this->smarty->template_dir = $config['wifidb_install'].'themes/'.$this->theme.'/templates/';
			$this->smarty->compile_dir  = $config['wifidb_install'].'smarty/templates_c/'.$this->theme.'/';
			$this->smarty->config_dir   = $config['wifidb_install'].'smarty/configs/'.$this->theme.'/';
			$this->smarty->cache_dir    = $config['wifidb_install'].'smarty/cache/'.$this->theme.'/';

			$this->smarty->assign('themeurl', $this->URL_PATH.'themes/'.$this->theme.'/');
			$this->smarty->assign('wifidb_host_url', $this->URL_PATH);
			$this->smarty->assign('wifidb_meta_header', $this->meta->header);
			$this->smarty->assign('wifidb_theme', $this->theme);
			$this->smarty->assign('wifidb_version_label', $this->ver_array['wifidb']);

			$this->smarty->assign('critical_error_message', '');

			$this->smarty->assign("redirect_func", "");
			$this->smarty->assign("redirect_html", "");
			$this->smarty->assign('wifidb_login_label', $this->sec->LoginLabel);
			$this->smarty->assign('wifidb_login_html', $this->sec->LoginHtml);
			$this->smarty->assign('wifidb_login_user', $this->sec->LoginUser);
			$this->smarty->assign('wifidb_current_uri', $this->sec->LoginUri);
			#$this->smarty->assign('wifidb_current_uri', '?return='.$_SERVER['PHP_SELF']);
			$this->htmlheader();
			$this->htmlfooter();
			$this->users_import_aps = array();
		}

		$this->ver_array['frontend']	=   array(
													"AllUsers"	   =>  "1.0",
													"AllUsersAP"	 =>  "1.0",
													"dump"		   =>  "1.0",
													"GenPageCount"   =>  "1.0",
													"GetAnnouncement"=>  "1.0",
													"HTMLFooter"	 =>  "1.0",
													"HTMLHeader"	 =>  "1.0",
													"UserAPList"	 =>  "1.0",
													"UserLists"	  =>  "1.0"
												);
	}

	function APFetch($id = "")
	{
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.FLAGS, wap.fa, wap.la, wap.points,\n"
					. "wGPS.Lat As Lat,\n"
					. "wGPS.Lon As Lon,\n"
					. "wf.user As user\n"
					. "FROM `wifi_ap` AS wap\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
					. "WHERE wap.AP_ID = ? LIMIT 1";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT TOP 1 [wap].[AP_ID], [wap].[BSSID], [wap].[SSID], [wap].[CHAN], [wap].[AUTH], [wap].[ENCR], [wap].[SECTYPE], [wap].[RADTYPE], [wap].[NETTYPE], [wap].[BTX], [wap].[OTX], [wap].[FLAGS], [wap].[fa], [wap].[la], [wap].[points],\n"
					. "[wGPS].[Lat] As [Lat],\n"
					. "[wGPS].[Lon] As [Lon],\n"
					. "[wf].[user] As [user]\n"
					. "FROM [wifi_ap] AS [wap]\n"
					. "LEFT JOIN [wifi_gps] AS [wGPS] ON [wGPS].[GPS_ID] = [wap].[HighGps_ID]\n"
					. "LEFT JOIN [files] AS [wf] ON [wf].[id] = [wap].[File_ID]\n"
					. "WHERE [wap].[AP_ID] = ?";
			}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$newArray = $prep->fetch(2);

		$list_geonames = array();
		$flip = 0;
		if($newArray['Lat'] == '0.0000' || $newArray['Lat'] == '')
		{
			$validgps = 0;
		}
		else
		{
			$validgps = 1;
			$Latdd = $this->convert->dm2dd($newArray["Lat"]);
			$Londd = $this->convert->dm2dd($newArray["Lon"]);
			$lat_search = bcdiv($Latdd, 1, 1);
			$long_search = bcdiv($Londd, 1, 1);
			
			if($this->sql->service == "mysql")
				{
					$sql = "SELECT  id, asciiname, country_code, admin1_code, admin2_code, timezone, latitude, longitude, \n"
						. "(3959 * acos(cos(radians('".$Latdd."')) * cos(radians(`latitude`)) * cos(radians(`longitude`) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(`latitude`)))) AS `miles`,\n"
						. "(6371 * acos(cos(radians('".$Latdd."')) * cos(radians(`latitude`)) * cos(radians(`longitude`) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(`latitude`)))) AS `kilometers`\n"
						. "FROM `geonames` \n"
						. "WHERE `latitude` LIKE '".$lat_search."%' AND `longitude` LIKE '".$long_search."%' ORDER BY `kilometers` ASC LIMIT 10";
				}
			else if($this->sql->service == "sqlsrv")
				{
					$sql = "SELECT TOP 10 [id], [asciiname], [country_code], [admin1_code], [admin2_code], [timezone], [latitude], [longitude], \n"
						. "(3959 * acos(cos(radians('".$Latdd."')) * cos(radians([latitude])) * cos(radians([longitude]) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians([latitude])))) AS [miles],\n"
						. "(6371 * acos(cos(radians('".$Latdd."')) * cos(radians([latitude])) * cos(radians([longitude]) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians([latitude])))) AS [kilometers]\n"
						. "FROM [geonames] \n"
						. "WHERE [latitude] LIKE '".$lat_search."%' AND [longitude] LIKE '".$long_search."%' ORDER BY [kilometers] ASC";
				}
			$geoname_res = $this->sql->conn->query($sql);
			while ($GeonamesArray = $geoname_res->fetch(1))
			{
				if($GeonamesArray['id'] !== '')
				{
					if($flip)
						{$class="dark";$flip=0;}
					else
						{$class="light";$flip=1;}
					
					$admin1 = $GeonamesArray['country_code'].".".$GeonamesArray['admin1_code'];
					if($this->sql->service == "mysql")
						{$sql = "SELECT `name` FROM `geonames_admin1` WHERE `admin1` = ?";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "SELECT [name] FROM [geonames_admin1] WHERE [admin1] = ?";}
					$prep_geonames = $this->sql->conn->prepare($sql);
					$prep_geonames->bindParam(1, $admin1, PDO::PARAM_STR);
					$prep_geonames->execute();
					$Admin1Array = $prep_geonames->fetch(2);

					$admin2 = $GeonamesArray['country_code'].".".$GeonamesArray['admin1_code'].".".$GeonamesArray['admin2_code'];
					if($this->sql->service == "mysql")
						{$sql = "SELECT `name` FROM `geonames_admin2` WHERE `admin2` = ?";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "SELECT [name] FROM [geonames_admin2] WHERE [admin2] = ?";}
					$prep_geonames = $this->sql->conn->prepare($sql);
					$prep_geonames->bindParam(1, $admin2, PDO::PARAM_STR);
					$prep_geonames->execute();
					$Admin2Array = $prep_geonames->fetch(2);
					
					$list_geonames[]= array(
						'class'=>$class,
						'id'=>$GeonamesArray['id'],
						'asciiname'=>$GeonamesArray['asciiname'],
						'country_code'=>$GeonamesArray['country_code'],
						'timezone'=>$GeonamesArray['timezone'],
						'miles'=>$GeonamesArray['miles'],
						'kilometers'=>$GeonamesArray['kilometers'],
						'latitude'=>$this->convert->all2dm(number_format($GeonamesArray['latitude'],7)),
						'longitude'=>$this->convert->all2dm(number_format($GeonamesArray['longitude'],7)),
						'admin1name'=>$Admin1Array['name'],
						'admin2name'=>$Admin2Array['name']
					);
				}
			}
			$globe_html = '<a href="'.$this->URL_PATH."opt/map.php?func=exp_ap&labeled=0&id=".$newArray['AP_ID'].'" title="Show AP on Map"><img width="20px" src="'.$this->URL_PATH.'/img/globe_on.png"></a>';
			$globe_html .= '<a href="'.$this->URL_PATH."api/geojson.php?json=1&func=exp_ap&id=".$newArray['AP_ID'].'" title="Export AP to JSON"><img width="20px" src="'.$this->URL_PATH.'/img/json_on.png"></a>';
			$globe_html .= '<a href="'.$this->URL_PATH."api/export.php?func=exp_ap_netlink&id=".$newArray['AP_ID'].'" title="Export AP to KMZ"><img width="20px" src="'.$this->URL_PATH.'/img/kmz_on.png"></a>';
		}

		$ap_data = array(
			'id'=>$newArray['AP_ID'],
			'radio'=>$newArray['RADTYPE'],
			'manuf'=>$this->findManuf($newArray['BSSID']),
			'mac'=>$newArray['BSSID'],
			'ssid'=>$this->formatSSID($newArray['SSID']),
			'chan'=>$newArray['CHAN'],
			'encry'=>$newArray['ENCR'],
			'auth'=>$newArray['AUTH'],
			'btx'=>$newArray["BTX"],
			'otx'=>$newArray["OTX"],
			'flags'=>$newArray["FLAGS"],
			'nt'=>$newArray["NETTYPE"],
			'lat'=>$newArray["Lat"],
			'lon'=>$newArray["Lon"],
			'user'=>$newArray["user"],
			'fa'=>$newArray["fa"],
			'la'=>$newArray["la"],
			'points'=>$newArray["points"],
			'validgps'=>$validgps
		);
		
		$list = array();
		$flip = 0;

			
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT `wifi_hist`.`File_ID`, `files`.`title`, `files`.`notes`, `files`.`user`, `files`.`date`, `files`.`ValidGPS`, `wifi_hist`.`New`, COUNT(`wifi_hist`.`Hist_Date`) As `points`\n"
					. "FROM `wifi_hist`\n"
					. "INNER JOIN `files` ON `wifi_hist`.`File_ID` = `files`.`id`\n"
					. "WHERE `wifi_hist`.`AP_ID` = ?\n"
					. "GROUP BY `wifi_hist`.`File_ID`, `wifi_hist`.`New`\n"
					. "ORDER BY `files`.`date` DESC";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wifi_hist].[File_ID], [files].[title], [files].[notes], [files].[user], [files].[date], [files].[ValidGPS], [wifi_hist].[New], COUNT([wifi_hist].[Hist_Date]) As [points]\n"
					. "FROM [wifi_hist]\n"
					. "INNER JOIN [files] ON [wifi_hist].[File_ID] = [files].[id]\n"
					. "WHERE [wifi_hist].[AP_ID] = ?\n"
					. "GROUP BY [wifi_hist].[File_ID], [files].[title], [files].[notes], [files].[user], [files].[date], [files].[ValidGPS], [wifi_hist].[New]\n"
					. "ORDER BY [files].[date] DESC";
			}
		$prep2 = $this->sql->conn->prepare($sql);
		$prep2->bindParam(1, $id, PDO::PARAM_INT);
		$prep2->execute();
		if($this->sql->checkError() !== 0)
		{
			throw new Exception("Error getting associated lists");
		}

		while ($field = $prep2->fetch(1))
		{
			if($flip)
				{$class="dark";$flip=0;}
			else
				{$class="light";$flip=1;}

			$file_id = $field["File_ID"];
			
			$flip2=0;
			$sigarr = array();			

			if($this->sql->service == "mysql")
				{
					$sql = "SELECT `wifi_hist`.`AP_ID`, `wifi_hist`.`Sig`, `wifi_hist`.`RSSI`, `wifi_hist`.`GPS_ID`, `wifi_hist`.`New`, `wifi_gps`.`Lat`, `wifi_gps`.`Lon`, `wifi_gps`.`Alt`, `wifi_gps`.`NumOfSats`, `wifi_gps`.`AccuracyMeters`, `wifi_gps`.`HorDilPitch`, `wifi_gps`.`TrackAngle`, `wifi_gps`.`GPS_Date`, `wifi_gps`.`MPH`, `wifi_gps`.`KPH`\n"
						. "FROM `wifi_hist`\n"
						. "INNER JOIN `wifi_gps` ON `wifi_hist`.`GPS_ID`=`wifi_gps`.`GPS_ID`\n"
						. "WHERE `wifi_hist`.`AP_ID` = ? AND `wifi_hist`.`File_ID` = ?\n"
						. "ORDER BY `wifi_hist`.`Hist_Date` ASC";
				}
			else if($this->sql->service == "sqlsrv")
				{
					$sql = "SELECT [wifi_hist].[AP_ID], [wifi_hist].[Sig], [wifi_hist].[RSSI], [wifi_hist].[GPS_ID], [wifi_hist].[New], [wifi_gps].[Lat], [wifi_gps].[Lon], [wifi_gps].[Alt], [wifi_gps].[NumOfSats], [wifi_gps].[AccuracyMeters], [wifi_gps].[HorDilPitch], [wifi_gps].[TrackAngle], [wifi_gps].[GPS_Date], [wifi_gps].[MPH], [wifi_gps].[KPH]\n"
						. "FROM [wifi_hist]\n"
						. "INNER JOIN [wifi_gps] ON [wifi_hist].[GPS_ID]=[wifi_gps].[GPS_ID]\n"
						. "WHERE [wifi_hist].[AP_ID] = ? AND [wifi_hist].[File_ID] = ?\n"
						. "ORDER BY [wifi_hist].[Hist_Date] ASC";
				}
			$prep1 = $this->sql->conn->prepare($sql);
			$prep1->bindParam(1, $id, PDO::PARAM_STR);
			$prep1->bindParam(2, $file_id, PDO::PARAM_STR);
			$prep1->execute();
			while ($signals = $prep1->fetch(1))
			{
				if($flip2)
					{$class2="dark";$flip2=0;}
				else
					{$class2="light";$flip2=1;}
				
				$sigarr[]= array(
					'class'=>$class2,
					'id'=>$signals['AP_ID'],
					'Sig'=>$signals['Sig'],
					'RSSI'=>$signals['RSSI'],
					'Lat'=>$signals['Lat'],
					'Lon'=>$signals['Lon'],
					'Alt'=>$signals['Alt'],
					'NumOfSats'=>$signals['NumOfSats'],
					'AccuracyMeters'=>$signals['AccuracyMeters'],
					'GPS_Date'=>$signals['GPS_Date']
				);				
			}

			$list[]= array(
				'class'=>$class,
				'id'=>$field['File_ID'],
				'nu'=>$field['New'],
				'date'=>$field['date'],
				'points'=>$field['points'],
				'user'=>$field['user'],
				'title'=>$field['title'],
				'notes'=>$field['notes'],
				'signals'=>$sigarr,
				'validgps'=>$field['ValidGPS']
			);

		}
		$ap_data['from'] = $signals[0]['id'];
		$ap_data['limit'] = $prep2->rowCount();
		return array(
			$newArray['SSID'],
			$list,
			$ap_data,
			$list_geonames
		);
	}

	function GetAnnouncement()
	{
		if($this->sql->service == "mysql")
		{
			$sql = "SELECT `body` FROM `annunc` WHERE `set` = '1'";
		}
		else if($this->sql->service == "sqlsrv")
		{
			$sql = "SELECT [body] FROM [annunc] WHERE [set] = '1'";
		}
		$result = $this->sql->conn->query($sql);
		$array = $result->fetch(2);
		if($this->sql->checkError() || $array['body'] == "")
		{
			return 0;
		}
		return $array;
	}


	function htmlheader()
	{
		if(@WIFIDB_INSTALL_FLAG != "installing" && $this->sec->login_check)
		{
			$this->smarty->assign("login_val", "1");
			$login_bar = 'Welcome, <a class="links" href="'.$this->URL_PATH.'cp/">'.$this->sec->username.'</a>';
			$wifidb_mysticache_link = 1;
		}else
		{
			$this->smarty->assign("login_val", "0");
			$wifidb_mysticache_link = 0;
			$login_bar = "";
		}
		$this->smarty->assign("install_header", $this->check_install_folder());
		$announc = $this->GetAnnouncement();

		$this->smarty->assign("wifidb_announce_header", '<p class="annunc_text">'.$announc['body'].'</p>');
		$this->smarty->assign("wifidb_mysticache_link", $wifidb_mysticache_link);
		$this->login_bar = $login_bar;
		return 1;
	}


	function htmlfooter()
	{
		$out = '';
		if($this->sec->login_check)
		{
			if($this->sec->privs >= 1000)
			{
				$out .= '<a class="links" href="'.$this->URL_PATH.'/cp/?func=admin_cp">Admin Control Panel</a>  |-|  ';
			}
			if($this->sec->privs >= 10)
			{
				$out .= '<a class="links" href="'.$this->URL_PATH.'/cp/?func=mod_cp">Moderator Control Panel</a>  |-|  ';
			}
			if($this->sec->privs >= 1)
			{
				$out .= '<a class="links" href="'.$this->URL_PATH.'/cp/">User Control Panel</a>';
			}

		}
		$this->footer = $this->meta->tracker.$this->meta->ads;
		return 1;
	}

	#===================================#
	#   Grab the stats for All Users	#
	#===================================#
	Public function AllUsers()
	{
		$this->all_users_data = array();
		$flip = 0;
		$rowid = 0;

		if($this->sql->service == "mysql")
			{$sql = "SELECT DISTINCT(`user`) FROM `files` WHERE completed = 1 ORDER BY `user` ASC";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT DISTINCT([user]) FROM [files] WHERE [completed] = 1 ORDER BY [user] ASC";}
		$result = $this->sql->conn->query($sql);
		$result->execute();
		while($userfetch = $result->fetch(2))
		{			
			$user = $userfetch['user'];
			
			if($this->sql->service == "mysql")
				{$sql = "SELECT COUNT(`id`) AS `ApCount` FROM `files` WHERE `user` LIKE ? And `ValidGPS` = 1";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT COUNT([id]) AS [ApCount] FROM [files] WHERE [user] LIKE ? And [ValidGPS] = 1";}
			$globeprep = $this->sql->conn->prepare($sql);
			$globeprep->bindParam(1, $user, PDO::PARAM_STR);
			$globeprep->execute();
			$globeprepfetch = $globeprep->fetch(2);
			if($globeprepfetch['ApCount'] !== "0")
			{
				$user_globe_html = "<a href=\"".$this->URL_PATH."opt/map.php?func=user_all&labeled=0&user=".$user."\" title=\"Show User APs on Map\"><img width=\"20px\" src=\"".$this->URL_PATH."img/globe_on.png\"></a>";				
				$user_globe_html .= "<a href=\"".$this->URL_PATH."api/geojson.php?json=1&func=exp_user_all&user=".$user."\" title=\"Export User APs to JSON\"><img width=\"20px\" src=\"".$this->URL_PATH."img/json_on.png\"></a>";							
				$user_globe_html .= "<a href=\"".$this->URL_PATH."api/export.php?func=exp_user_netlink&user=".$user."\" title=\"Export User APs to KMZ\"><img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_on.png\"></a>";
			}
			else
			{
				$user_globe_html = "<img width=\"20px\" src=\"".$this->URL_PATH."img/globe_off.png\">";
				$user_globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/json_off.png\">";
				$user_globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_off.png\">";
			}
	
			$all_users_files = array();
			$flip2 = 0;

			if($this->sql->service == "mysql")
				{$sql = "SELECT `id`, `user`, `aps`, `gps`, `NewAPPercent`, `notes`, `date`, `title`, `ValidGPS` FROM `files` WHERE completed = 1 AND `user`= ? ORDER BY `date` DESC";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT [id], [user], [aps], [gps], [NewAPPercent], [notes], [date], [title], [ValidGPS] FROM [files] WHERE [completed] = 1 AND [user]= ? ORDER BY [date] DESC";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $user , PDO::PARAM_STR);
			$prep->execute();
			$imports = 0;
			while ($user_array = $prep->fetch(2))
			{
				$imports++;
				
				if($user_array['ValidGPS'] == 1)
				{
					$list_globe_html = "<a href=\"".$this->URL_PATH."opt/map.php?func=user_list&labeled=0&id=".$user_array['id']."\" title=\"Show List on Map\"><img width=\"20px\" src=\"".$this->URL_PATH."img/globe_on.png\"></a>";				
					$list_globe_html .= "<a href=\"".$this->URL_PATH."api/geojson.php?json=1&func=exp_list&id=".$user_array['id']."\" title=\"Export List to JSON\"><img width=\"20px\" src=\"".$this->URL_PATH."img/json_on.png\"></a>";							
					$list_globe_html .= "<a href=\"".$this->URL_PATH."api/export.php?func=exp_list&id=".$user_array['id']."\" title=\"Export List to KMZ\"><img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_on.png\"></a>";
				}
				else
				{
					$list_globe_html = "<img width=\"20px\" src=\"".$this->URL_PATH."img/globe_off.png\">";
					$list_globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/json_off.png\">";	
					$list_globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_off.png\">";
				}
				
				if($flip2)
					{$class2 = "dark";$flip2=0;}
				else
					{$class2="light";$flip2=1;}
				
				$user_array['notes'] = str_replace(array('\n','\r','\n\r'), "", $user_array['notes']);
				if ($user_array['notes'] == ""){ $user_array['notes']="No Notes, hmm..";}
				if ($user_array['title'] === "" or $user_array['title'] === " "){ $user_array['title']="UNTITLED";}
				if ($user_array['date'] === ""){ $user_array['date']="No date, hmm..";}

				$all_users_files[]= array(
					'class'=>$class2,
					'id'=>$user_array['id'],
					'globe'=>$list_globe_html,
					'title' => $user_array['title'],
					'notes' => $user_array['notes'],
					'aps'   => $user_array['aps'],
					'NewAPPercent' => $user_array['NewAPPercent']."%",
					'date'  => $user_array['date']
				);
			}
			
			$rowid++;
			
			if($flip)
				{$class = "dark";$flip=0;}
			else
				{$class="light";$flip=1;}
			
			$this->all_users_data[]= array(
				'rowid'=>$rowid,
				'class'=>$class,
				'user'=>$user,
				'globe'=>$user_globe_html,
				'imports' => $imports,
				'files' => $all_users_files,
			);
		}
		return 1;
	}

	#=======================================#
	#   Grab All the AP's for a given user  #
	#=======================================#
	function AllUsersAPs($user = "")
	{
		if($user == ""){return 0;}

		$args = array(
			'ord' => FILTER_SANITIZE_ENCODED,
			'sort' => FILTER_SANITIZE_ENCODED,
			'to' => FILTER_SANITIZE_NUMBER_INT,
			'from' => FILTER_SANITIZE_NUMBER_INT
		);

		$inputs = filter_input_array(INPUT_GET, $args);

		if($inputs['from'] == ''){$inputs['from'] = 0;}
		if($inputs['to'] == ''){$inputs['to'] = 100;}
		if($inputs['sort'] == ''){$inputs['sort'] = 'ModDate';}
		if($inputs['ord'] == ''){$inputs['ord'] = 'DESC';}

		$prep = array();
		$apprep = array();
		$prep['allaps'] = array();
		$prep['user'] = $user;

		if($this->sql->service == "mysql")
			{
				$sql = "SELECT COUNT(*) \n"
					. "FROM `wifi_ap`\n"
					. "INNER JOIN `files` ON `wifi_ap`.`File_ID` = `files`.`id`\n"
					. "WHERE `files`.`user` LIKE ?";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT COUNT(*) \n"
					. "FROM [wifi_ap]\n"
					. "INNER JOIN [files] ON [wifi_ap].[File_ID] = [files].[id]\n"
					. "WHERE [files].[user] LIKE ?";
			}
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$prep['total_aps'] = $rows[0];

		$flip = 0;

		if($this->sql->service == "mysql")
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX,\n"
					. "whFA.Hist_Date As FA,\n"
					. "whLA.Hist_Date As LA,\n"
					. "wGPS.Lat As Lat,\n"
					. "wGPS.Lon As Lon\n"
					. "FROM wifi_ap AS wap\n"
					. "LEFT JOIN wifi_hist AS whFA ON whFA.Hist_ID = wap.FirstHist_ID\n"
					. "LEFT JOIN wifi_hist AS whLA ON whLA.Hist_ID = wap.LastHist_ID\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT JOIN files AS f ON f.id = wap.File_ID\n"
					. "WHERE f.user LIKE ? And f.completed = 1 ORDER BY `{$inputs['sort']}` {$inputs['ord']} LIMIT {$inputs['from']}, {$inputs['to']}";	
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT [wap].[AP_ID], [wap].[BSSID], [wap].[SSID], [wap].[CHAN], [wap].[AUTH], [wap].[ENCR], [wap].[SECTYPE], [wap].[RADTYPE], [wap].[NETTYPE], [wap].[BTX], [wap].[OTX],\n"
					. "[whFA].[Hist_Date] As [FA],\n"
					. "[whLA].[Hist_Date] As [LA],\n"
					. "[wGPS].[Lat] As [Lat],\n"
					. "[wGPS].[Lon] As [Lon]\n"
					. "FROM [wifi_ap] AS [wap]\n"
					. "LEFT JOIN [wifi_hist] AS [whFA] ON [whFA].[Hist_ID] = [wap].[FirstHist_ID]\n"
					. "LEFT JOIN [wifi_hist] AS [whLA] ON [whLA].[Hist_ID] = [wap].[LastHist_ID]\n"
					. "LEFT JOIN [wifi_gps] AS [wGPS] ON [wGPS].[GPS_ID] = [wap].[HighGps_ID]\n"
					. "LEFT JOIN [files] AS [f] ON [f].[id] = [wap].[File_ID]\n"
					. "WHERE [f].[user] LIKE ? And [f].[completed] = 1\n"
					. "ORDER BY [{$inputs['sort']}] {$inputs['ord']}\n"
					. "OFFSET {$inputs['from']} ROWS\n"
					. "FETCH NEXT {$inputs['to']} ROWS ONLY";
			}			
		$result1 = $this->sql->conn->prepare($sql);
		$result1->bindParam(1, $user, PDO::PARAM_STR);
		$result1->execute();

		while($array = $result1->fetch(2))
		{
			if($flip)
				{$style = "dark";$flip=0;}
			else
				{$style="light";$flip=1;}
				
			if($array['Lat'] == "")
			{
				$globe = "off";
				$globe_html = "<img width=\"20px\" src=\"".$this->URL_PATH."img/globe_off.png\">";
				$globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/json_off.png\">";
				$globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_off.png\">";
			}else
			{
				$globe = "on";
				$globe_html = "<a href=\"".$this->URL_PATH."opt/map.php?func=exp_ap&labeled=0&id=".$array['AP_ID']."\" title=\"Show AP on Map\"><img width=\"20px\" src=\"".$this->URL_PATH."img/globe_on.png\"></a>";
				$globe_html .= "<a href=\"".$this->URL_PATH."api/geojson.php?json=1&func=exp_ap&id=".$array['AP_ID']."\" title=\"Export AP to JSON\"><img width=\"20px\" src=\"".$this->URL_PATH."img/json_on.png\"></a>";
				$globe_html .= "<a href=\"".$this->URL_PATH."api/export.php?func=exp_ap_netlink&id=".$array['AP_ID']."\" title=\"Export AP to KMZ\"><img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_on.png\"></a>";
			}

			$apprep[] = array(
						"id" => $array['AP_ID'],
						"class" => $style,
						"globe" => $globe,
						"globe_html" => $globe_html,
						"ssid" => $this->formatSSID($array['SSID']),
						"mac" => $array['BSSID'],
						"radio" => $array['RADTYPE'],
						"auth" => $array['AUTH'],
						"encry" => $array['ENCR'],
						"chan" => $array['CHAN'],
						"fa"   => $array['FA'],
						"la"   => $array['LA'],
						"lat"   => $array['Lat'],
						"lon"   => $array['Long']
						);
		}
		$prep['allaps'] = $apprep;
		$this->all_users_aps = $prep;
		$this->GeneratePages($prep['total_aps'], $inputs['from'], $inputs['to'], $inputs['sort'], $inputs['ord'], 'allap', $user);
		return 1;
	}

	#===================================#
	#   Grab all user Import lists	  #
	#===================================#
	function UsersLists($username = "")
	{
		if($username == ""){return 0;}

		#Total APs
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT COUNT(*) \n"
					. "FROM `wifi_ap`\n"
					. "INNER JOIN `files` ON `wifi_ap`.`File_ID` = `files`.`id`\n"
					. "WHERE `files`.`user` LIKE ?";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT COUNT(*) \n"
					. "FROM [wifi_ap]\n"
					. "INNER JOIN [files] ON [wifi_ap].[File_ID] = [files].[id]\n"
					. "WHERE [files].[user] LIKE ?";
			}
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$total = $rows[0];

		#Get First Active AP
		if($this->sql->service == "mysql")
			{$sql = "SELECT id, user, date FROM `files` WHERE `user` LIKE ? And `date` != '' And `completed` = 1 ORDER BY `date` ASC LIMIT 1";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT TOP 1 [id], [user], [date] FROM [files] WHERE [user] LIKE ? And [date] != '' And [completed] = 1 ORDER BY [date] ASC";}
		$prep2 = $this->sql->conn->prepare($sql);
		$prep2->bindParam(1, $username, PDO::PARAM_STR);
		$prep2->execute();
		$user_first = $prep2->fetch(2);

		#Get Last Active AP
		if($this->sql->service == "mysql")
			{$sql = "SELECT `id`, `date` FROM `files` WHERE `user` LIKE ? And `date` != '' And `completed` = 1 ORDER BY `date` DESC LIMIT 1";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT TOP 1 [id], [date] FROM [files] WHERE [user] LIKE ? And [date] != '' And [completed] = 1 ORDER BY [date] DESC";}
		$prep1 = $this->sql->conn->prepare($sql);
		$prep1->bindParam(1, $username, PDO::PARAM_STR);
		$prep1->execute();
		$user_last = $prep1->fetch(2);

		#Get All Imports for User
		if($this->sql->service == "mysql")
			{$sql1 = "SELECT `id`, `title`, `notes`, `date`, `aps`, `gps`, `ValidGPS`, `NewAPPercent` FROM `files` WHERE `user` LIKE ? And `date` != '' And `completed` = 1 ORDER BY `date` DESC";}
		else if($this->sql->service == "sqlsrv")
			{$sql1 = "SELECT [id], [title], [notes], [date], [aps], [gps], [ValidGPS], [NewAPPercent] FROM [files] WHERE [user] LIKE ? And [date] != '' And [completed] = 1 ORDER BY [date] DESC";}
		$other_imports = $this->sql->conn->prepare($sql1);
		$other_imports->bindParam(1, $username, PDO::PARAM_STR);
		//$other_imports->bindParam(2, $user_last['id'], PDO::PARAM_INT);
		$other_imports->execute();
		$other_rows = $other_imports->rowCount();
		$other_imports_array = array();
		$flip = 0;
		while($imports = $other_imports->fetch(2))
		{
			if($imports['ValidGPS'] == 1)
			{
				$globe_html = "<a href=\"".$this->URL_PATH."opt/map.php?func=user_list&labeled=0&id=".$imports['id']."\" title=\"Show List on Map\"><img width=\"20px\" src=\"".$this->URL_PATH."img/globe_on.png\"></a>";
				$globe_html .= "<a href=\"".$this->URL_PATH."api/geojson.php?json=1&func=exp_list&id=".$imports['id']."\" title=\"Export List to JSON\"><img width=\"20px\" src=\"".$this->URL_PATH."img/json_on.png\"></a>";
				$globe_html .= "<a href=\"".$this->URL_PATH."api/export.php?func=exp_list&id=".$imports['id']."\" title=\"Export List to KMZ\"><img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_on.png\"></a>";
			}
			else
			{
				$globe_html = "<img width=\"20px\" src=\"".$this->URL_PATH."img/globe_off.png\">";
				$globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/json_off.png\">";
				$globe_html .= "<img width=\"20px\" src=\"".$this->URL_PATH."img/kmz_off.png\">";
			}
			
			if($flip)
			{
				$style = "dark";
				$flip=0;
			}else
			{
				$style="light";
				$flip=1;
			}
			$other_imports_array[] = array(
											'class' => $style,
											'globe_html' => $globe_html,
											'id' => $imports['id'],
											'title' => $imports['title'],
											'notes' => $imports['notes'],
											'aps' => $imports['aps'],
											'efficiency'=>$imports['NewAPPercent'],
											'date' => $imports['date']
										   );
		}
		$this->user_all_imports_data = array();
		$this->user_all_imports_data['user_id'] = $user_first['id'];
		$this->user_all_imports_data['user'] = $user_first['user'];
		$this->user_all_imports_data['first_import_date'] = $user_first['date'];
		$this->user_all_imports_data['newest_date'] = $user_last['date'];
		$this->user_all_imports_data['total_aps'] = $total;

		$this->user_all_imports_data['other_imports'] = $other_imports_array;
		return 1;
	}

	#===============================================#
	#   Grab the AP's for a given user's Import	 #
	#===============================================#
	function UserAPList($row=0, $sort = "AP_ID", $ord = "DESC")
	{
		if(!$row){return 0;}
		
		# Get import list information
		if($this->sql->service == "mysql")
			{$sql = "SELECT `id`, `file_orig`, `user`, `aps`, `gps`, `notes`, `title`, `date`, `hash`, `converted`, `prev_ext`, `NewAPPercent`, `size`, `ValidGPS` FROM `files` WHERE `id`= ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT [id], [file_orig], [user], [aps], [gps], [notes], [title], [date], [hash], [converted], [prev_ext], [NewAPPercent], [size], [ValidGPS] FROM [files] WHERE [id]= ?";}
        $result = $this->sql->conn->prepare($sql);
		$result->execute(array($row));
		$user_array = $result->fetch(2);

		$all_aps_array = array();
		$all_aps_array['allaps'] = array();
		$all_aps_array['id'] = $user_array['id'];
		$all_aps_array['file'] = $user_array['file_orig'];
		$all_aps_array['user'] = $user_array['user'];
		$all_aps_array['notes'] = $user_array['notes'];
		$all_aps_array['title'] = $user_array['title'];
		$all_aps_array['aps'] = $user_array['aps'];
		$all_aps_array['gps'] = $user_array['gps'];
		$all_aps_array['size'] = $user_array['size'];
		$all_aps_array['hash'] = $user_array['hash'];
		$all_aps_array['date'] = $user_array['date'];
		$all_aps_array['NewAPPercent'] = $user_array['NewAPPercent'];
		$all_aps_array['validgps'] = $user_array['ValidGPS'];
		

		#Get APs, First Active, Last Active, and points that go with this list
		if($this->sql->service == "mysql")
			{$sql = "SELECT `AP_ID`, `New`, Min(`Hist_Date`) As `fa`, Max(`Hist_Date`) As `la`, Count(`Hist_Date`) As `points` FROM `wifi_hist` WHERE `File_ID` = ? GROUP BY `AP_ID`, `New` ORDER BY `$sort` $ord";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT [AP_ID], [New], Min([Hist_Date]) As [fa], Max([Hist_Date]) As [la], Count([Hist_Date]) As [points] FROM [wifi_hist] WHERE [File_ID] = ? GROUP BY [AP_ID], [New] ORDER BY [$sort] $ord";}
		$prep_AP_IDS = $this->sql->conn->prepare($sql);
		$prep_AP_IDS->bindParam(1,$user_array['id'], PDO::PARAM_INT);
		$prep_AP_IDS->execute();
		$count = 0;
		$flip=0;
		while ( $array = $prep_AP_IDS->fetch(2) )
		{
			#Get access point information for this AP
			$apid = $array['AP_ID'];
			$List_AP_FA = $array['fa'];
			$List_AP_LA = $array['la'];
			$List_points = $array['points'];
			if($array['New'] == 1){$update_or_new = "New";}else{$update_or_new = "Update";}
			$count++;
			
			if($flip)
				{$style = "dark";$flip=0;}
			else
				{$style="light";$flip=1;}

			if($this->sql->service == "mysql")
				{
					$sql = "SELECT `wifi_ap`.`AP_ID`, `wifi_ap`.`BSSID`, `wifi_ap`.`SSID`, `wifi_ap`.`CHAN`, `wifi_ap`.`AUTH`, `wifi_ap`.`ENCR`, `wifi_ap`.`SECTYPE`, `wifi_ap`.`RADTYPE`, `wifi_ap`.`NETTYPE`, `wifi_ap`.`BTX`, `wifi_ap`.`OTX`, `wifi_ap`.`points`,\n"
						. "`wifi_gps`.`Lat` AS Lat,\n"
						. "`wifi_gps`.`Lon` AS Lon\n"
						. "FROM `wifi_ap`\n"
						. "LEFT JOIN  `wifi_gps` ON `wifi_ap`.`HighGps_ID` = `wifi_gps`.`GPS_ID`\n"
						. "WHERE AP_ID = ?";					
				}
			else if($this->sql->service == "sqlsrv")
				{
					$sql = "SELECT [wifi_ap].[AP_ID], [wifi_ap].[BSSID], [wifi_ap].[SSID], [wifi_ap].[CHAN], [wifi_ap].[AUTH], [wifi_ap].[ENCR], [wifi_ap].[SECTYPE], [wifi_ap].[RADTYPE], [wifi_ap].[NETTYPE], [wifi_ap].[BTX], [wifi_ap].[OTX], [wifi_ap].[points],\n"
						. "[wifi_gps].[Lat] AS [Lat],\n"
						. "[wifi_gps].[Lon] AS [Lon]\n"
						. "FROM [wifi_ap]\n"
						. "LEFT JOIN [wifi_gps] ON [wifi_ap].[HighGps_ID] = [wifi_gps].[GPS_ID]\n"
						. "WHERE [wifi_ap].[AP_ID] = ?";					
				}
			$result = $this->sql->conn->prepare($sql);
			$result->bindParam(1, $apid, PDO::PARAM_INT);
			$result->execute();
			$ap_array = $result->fetch(2);
			
			if($ap_array['BSSID']  == "00:00:00:00:00:00"){continue;}
			if($ap_array['Lat']  == "0.0000" || $ap_array['Lat']  == ""){$validgps = 0;}else{$validgps = 1;}

			$all_aps_array['allaps'][] = array(
					'id' => $ap_array['AP_ID'],
					'class' => $style,
					'un' => $update_or_new,
					'ssid' => $this->formatSSID($ap_array['SSID']),
					'mac' => $ap_array['BSSID'],
					'chan' => $ap_array['CHAN'],
					'radio' => $ap_array['RADTYPE'],
					'auth' => $ap_array['AUTH'],
					'encry' => $ap_array['ENCR'],
					'fa' => $List_AP_FA,
					'la' => $List_AP_LA,
					'list_points' => $List_points,
					'points' => $ap_array['points'],
					'lat' => $ap_array['Lat'],
					'lon' => $ap_array['Lon'],
					'validgps' => $validgps
			);
		}
		$all_aps_array['total_aps'] = $count;
		$this->users_import_aps = $all_aps_array;
		return 1;
	}


	function GeneratePages($total_rows, $from, $inc, $sort, $ord, $func="", $user="", $ssid="", $mac="", $chan="", $radio="", $auth="", $encry="", $view="")
	{
		if($ssid=="" && $mac=="" && $chan=="" && $radio=="" && $auth=="" && $encry=="")
		{
			$no_search = 0;
		}else
		{
			$no_search = 1;
		}
		
		if($view==""){$viewparam="";}else{$viewparam="&view={$view}";}
		

		$function_and_username = "";
		if($func != "")
		{
			$function_and_username = "func=".$func;
		}

		if($user != "")
		{
			$function_and_username .= "&amp;user={$user}&amp;";
		}

		$pages = ceil($total_rows/$inc);
		$mid_page = (($from + $inc)/$inc);
		if($no_search)
		{
			$pages_together = "Pages: &lt;&#45;&#45;  &#91<a class=\"links\" href=\"?{$function_and_username}from=0&inc={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}{$viewparam}\">First</a>&#93 &#45; \r\n";
		}else
		{
			$pages_together = "Pages: &lt;&#45;&#45;  &#91<a class=\"links\" href=\"?{$function_and_username}from=0&inc={$inc}&sort={$sort}&ord={$ord}{$viewparam}\">First</a>&#93 &#45; \r\n";
		}
		for($I=($mid_page - 5); $I<=($mid_page + 5); $I++)
		{
			if($I <= 0){continue;}
			if($I > $pages){break;}
			if($I==1){$cal_from = 0;}else{$cal_from = (($I-1)*$inc);}
			if($mid_page == $I)
			{
				$pages_together .= " <b><i>{$I}</i></b> - \r\n";
			}else
			{
				if($no_search)
				{
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&inc={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}{$viewparam}\">{$I}</a> &#45; \r\n";
				}else
				{
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&inc={$inc}&sort={$sort}&ord={$ord}{$viewparam}\">{$I}</a> &#45; \r\n";
				}
			}
		}
		if($pages==1){$cal_from = 0;}else{$cal_from = (($pages-1)*$inc);}
		if($no_search)
		{
			$pages_together .= " &#91<a class=\"links\" href=\"?{$function_and_username}from=".$cal_from."&inc={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}{$viewparam}\">Last</a>&#93 &#45;&#45;&gt; \r\n";
		}else
		{
			$pages_together .= " &#91<a class=\"links\" href=\"?{$function_and_username}from=".$cal_from."&inc={$inc}&sort={$sort}&ord={$ord}{$viewparam}\">Last</a>&#93 &#45;&#45;&gt; \r\n";
		}
		
		$this->pages_together = $pages_together;
		return 1;
	}

	#==============================#
	#   Redirects the user after   #
	#   something has happened.	#
	#==============================#
	function redirect_page($return = "", $delay = 0)
	{
		if($return == ''){$return = $this->URL_PATH;}
		$this->smarty->assign("redirect_func", "<script type=\"text/javascript\">
			function reload()
			{
				//window.open('{$return}')
				location.href = '{$return}';
			}
		</script>");
		$this->smarty->assign("redirect_html", " onload=\"setTimeout('reload()', {$delay})\"");
	}

	/*
	 * Export Search to KML File
	 */
	public function search_export($array = "")
	{
		if($array === "")
		{
			return 0;
		}
		$no_gps = 0;
		$total = count($array);
		$date = date($this->datetime_format);
		$rand = rand();
		$temp_kml = "../tmp/save_".$date."_".$rand."_tmp.kml";

		fopen($temp_kml, "w");
		$fileappend = fopen($temp_kml, "a");

		$filename = "save_".$date.'_'.$rand.'.kmz';
		$moved = $this->PATH.'/out/kmz/lists/'.$filename;
		$this->smarty->assign("KML_SOURCE_LOC", $this->KML_SOURCE_URL);
		$this->smarty->assign("wifidb_ver_str", $this->ver_str);
		$this->smarty->assign("OPEN_LOC", $this->open_loc);
		$this->smarty->assign("WEP_LOC", $this->WEP_loc);
		$this->smarty->assign("WPA_LOC", $this->WPA_loc);
		$this->smarty->assign("Total_aps", $total);
		$NN = 0;
		foreach($array as $aps)
		{
			$ssids_ptb = $this->make_ssid($aps['ssid']);
			$ssid = $ssids_ptb[0];
			switch($aps['sectype'])
			{
				case 1:
					$type = "#openStyleDead";
					break;
				case 2:
					$type = "#wepStyleDead";
					break;
				case 3:
					$type = "#secureStyleDead";
					break;
			}
			$signal_exp = explode("-", $aps['sig']);
			$first = explode(",", $signal_exp[0]);
			$first_gps_id = $first[1];
			if($this->sql->service == "mysql")
				{$sql = "SELECT `Gps_Date` FROM `wifi_gps` WHERE `id` = '{$first_gps_id}'";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT [Gps_Date] FROM [wifi_gps] WHERE [id] = '{$first_gps_id}'";}			
			$result = $this->sql->conn->prep($sql);
			$first_data = $result->fetch(1);
			$fa = $first_data["Gps_Date"];

			$sig_c = count($signal_exp);
			$last = explode(",", $signal_exp[$sig_c-1]);
			$last_gps_id = $last[0];
			
			if($this->sql->service == "mysql")
				{$sql = "SELECT `Gps_Date` FROM `wifi_gps` WHERE `id` = '{$last_gps_id}'";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT [Gps_Date] FROM [wifi_gps] WHERE `id` = '{$last_gps_id}'";}	
			$result = $this->sql->conn->query($sql);
			$last_data = $result->fetch(1);
			$la = $last_data["Gps_Date"];

			if($this->sql->service == "mysql")
				{$sql_1 = "SELECT * FROM `wifi_gps` WHERE `Gps_ID` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql_1 = "SELECT * FROM [wifi_gps] WHERE `Gps_ID` = ?";}	
			$result_1 = $this->sql->conn->prepare($sql_1);
			foreach($signal_exp as $signal)
			{
				$sig_exp = explode(",", $signal);
				$result_1->bindParam(1, $sig_exp[0], PDO::PARAM_STR);
				$result_1->execute();
				$gps = $result_1->fetch(1);

				if(!preg_match('/^(\-?\d+(\.\d+)?),\s*(\-?\d+(\.\d+)?)$/', $gps['lat']) || $gps['lat'] == "0.0000")
				{
					$zero = 1;
					continue;
				}
				$lat = $this->convert_dm_dd($gps['lat']);
				$long = $this->convert_dm_dd($gps['long']);
				$zero = 0;
				$NN++;
				break;
			}
			if($zero == 1)
			{
				$this->mesg[] = 'No GPS Data, Skipping Access Point: '.$aps['ssid'];
				$zero = 0;
				$no_gps++;
				$total++;
				continue;
			}
			$KML_data[] = array(
				"id"=>$aps["id"],
				"ssid"=>$ssid,
				"mac"=>$aps["mac"],
				"nt"=>$aps["nt"],
				"radio"=>$aps["radio"],
				"chan"=>$aps["chan"],
				"auth"=>$aps["auth"],
				"encry"=>$aps["encry"],
				"sectype"=>$aps["sectype"],
				"type"=>$type,
				"btx"=>$aps["btx"],
				"otx"=>$aps["otx"],
				"fa"=>$fa,
				"la"=>$la,
				"lat"=>$lat,
				"long"=>$long,
				"alt"=>$gps["alt"],
				"manuf"=>$this->findManuf($aps["mac"]),
				"label"=>$aps["label"]
			);
		}
		$this->smarty->assign("KML_data", $KML_data);
		if($no_gps < $total)
		{
			$this->mesg[] = 'Zipping up the files into a KMZ file.';
			$zip = new ZipArchive;
			if ($zip->open($filename, ZipArchive::CREATE) === TRUE)
			{
				$zip->addFile($temp_kml, 'doc.kml');
				$zip->close();
				unlink($temp_kml);
			}else
			{
				$this->mesg[] = 'Blown up';
				$this->mesg[] = 'Your Google Earth KML file is not ready.';
				return 0;
			}

			$this->mesg[] = 'Move KMZ file from its tmp home to its permanent residence';
			if(copy($filename, $moved))
			{
				$this->mesg[] = 'Your Google Earth KML file is ready, you can download it from here: '.$moved;
				return 1;
			}else
			{
				$this->mesg[] = 'Your Google Earth KML file is not ready.';
				return 0;
			}
			unlink($filename);
		}else
		{
			$this->mesg[] = 'Your Google Earth KML file is not ready.';
			return 0;
		}
	}
}
