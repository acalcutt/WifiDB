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
			$this->smarty->assign('tileserver_gl_url', $this->tileserver_gl_url);

			$this->smarty->assign('critical_error_message', '');

			$this->smarty->assign("redirect_func", "");
			$this->smarty->assign("redirect_html", "");
			$this->smarty->assign('wifidb_login_label', $this->sec->LoginLabel);
			$this->smarty->assign('wifidb_login_user', $this->sec->LoginUser);
			$this->smarty->assign('wifidb_login_privs', $this->sec->privs);
			$this->smarty->assign('wifidb_login_priv_name', $this->sec->priv_name);
			$this->smarty->assign('wifidb_login_logged_in', $this->sec->login_check);
			$this->smarty->assign('wifidb_message_unread_count', $this->GetMessageCount($this->sec->LoginUser));
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
	
	function GetMessageCount($username="")
	{
		$message_count = 0;
		if($username)
		{
			#Get Unread Message Count
			$sql = "SELECT COUNT(pm.id) AS unread_count FROM pm\n"
				. "INNER JOIN user_info ON user_info.id = pm.user2\n"
				. "WHERE user2read = 0 And user2del = 0 And user_info.username LIKE ?";
			$result = $this->sql->conn->prepare($sql);
			$result->bindParam(1, $username);
			$result->execute();
			$array = $result->fetch(2);
			$message_count = $array['unread_count'];
		}
		return $message_count;
	}

	function GetAnnouncement()
	{
		$sql = "SELECT body FROM annunc WHERE enabled = '1'";
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
	Public function AllUsers($sort = "file_user", $ord = "ASC", $from = 0, $inc = 250)
	{
		#Total Users
		$sql = "SELECT COUNT(DISTINCT file_user)\n"
			. "FROM files\n"
			. "WHERE completed = 1";
		$result = $this->sql->conn->prepare($sql);
		$result->execute();
		$fcount = $result->fetch(1);
		$total_files = $fcount[0];
		$this->all_users_data = array();
		$flip = 0;
		$rowid = $from;
		
		$sql = "SELECT file_user, Count(id) As FileCount, MAX(ValidGPS) As ValidGPS, SUM(aps) As ApCount, SUM(gps) As GpsCount,AVG(NewAPPercent) As NewAPPercent,MIN(file_date) As FirstImport,MAX(file_date) As LastImport\n"
			. "FROM files\n"
			. "WHERE completed = 1\n"
			. "GROUP BY file_user\n"
			. "ORDER BY $sort $ord\n";
		if($this->sql->service == "mysql"){$sql .= "LIMIT $from, $inc";}
		else if($this->sql->service == "sqlsrv"){$sql .= "OFFSET $from ROWS FETCH NEXT $inc ROWS ONLY";}
		$result = $this->sql->conn->query($sql);
		$result->execute();
		while($userfetch = $result->fetch(2))
		{
			if($flip){$class = "dark";$flip=0;}else{$class="light";$flip=1;}
			$rowid++;
			
			#Check If Registered User
			$sql = "SELECT id\n"
				. "FROM user_info\n"
				. "WHERE username like ? And validated = 0";
			$vres = $this->sql->conn->prepare($sql);
			$vres->bindParam(1, $userfetch['file_user']);
			$vres->execute();
			$vfetch = $vres->fetch(1);
			$regid = $vfetch[0];
			
			$this->all_users_data[] = array(
				"class" => $class,			
				"rowid" => $rowid,
				"regid" => $regid,
				"user" => htmlspecialchars($userfetch['file_user'], ENT_QUOTES, 'UTF-8'),
				"filecount" => $userfetch['FileCount'],
				"validgps" => $userfetch['ValidGPS'],
				"apcount" => $userfetch['ApCount'],
				"gpscount" => $userfetch['GpsCount'],
				"firstimport" => $userfetch['FirstImport'],
				"lastimport" => $userfetch['LastImport'],
				"newappercent" => $userfetch['NewAPPercent']
			);
		}
		$this->GeneratePages($total_files, $from, $inc, $sort, $ord, 'allusers&');
		return 1;
	}

	#=======================================#
	#   Grab All the AP's for a given user  #
	#=======================================#
	function AllUsersAPs($user = "", $sort = "AP_ID", $ord = "DESC", $from = 0, $inc = 250)
	{
		if($user == ""){return 0;}

		$prep = array();
		$apprep = array();
		$prep['allaps'] = array();
		$prep['user'] = $user;

		$sql = "SELECT COUNT(DISTINCT AP_ID)\n"
			. "FROM wifi_hist\n"
			. "INNER JOIN files ON files.id = wifi_hist.File_ID\n"
			. "WHERE files.file_user LIKE ?";
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$prep['total_aps'] = $rows[0];

		$sql = "SELECT COUNT(DISTINCT AP_ID)\n"
			. "FROM wifi_hist\n"
			. "INNER JOIN files ON files.id = wifi_hist.File_ID\n"
			. "WHERE files.file_user LIKE ? And wifi_hist.New = 1";
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$prep['new_aps'] = $rows[0];
		$flip = 0;

		$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon\n"
			. "FROM wifi_ap AS wap\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
			. "LEFT JOIN files AS f ON f.id = wap.File_ID\n"
			. "WHERE f.file_user LIKE ? And f.completed = 1\n"
			. "ORDER BY $sort $ord\n";
		if($this->sql->service == "mysql"){$sql .= "LIMIT $from, $inc";}
		else if($this->sql->service == "sqlsrv"){$sql .= "OFFSET $from ROWS FETCH NEXT $inc ROWS ONLY";}
		$result1 = $this->sql->conn->prepare($sql);
		$result1->bindParam(1, $user, PDO::PARAM_STR);
		$result1->execute();

		while($array = $result1->fetch(2))
		{
			if($flip)
				{$style = "dark";$flip=0;}
			else
				{$style="light";$flip=1;}
				
			if($array['Lat'] == ""){$validgps = 0;}else{$validgps = 1;}

			$apprep[] = array(
						"id" => $array['AP_ID'],
						"class" => $style,
						"validgps" => $validgps,
						"ssid" => htmlspecialchars($this->formatSSID($array['SSID']), ENT_QUOTES, 'UTF-8'),
						"mac" => htmlspecialchars($array['BSSID'], ENT_QUOTES, 'UTF-8'),
						"radio" => htmlspecialchars($array['RADTYPE'], ENT_QUOTES, 'UTF-8'),
						"auth" => htmlspecialchars($array['AUTH'], ENT_QUOTES, 'UTF-8'),
						"encry" => htmlspecialchars($array['ENCR'], ENT_QUOTES, 'UTF-8'),
						"chan" => htmlspecialchars($array['CHAN'], ENT_QUOTES, 'UTF-8'),
						"fa"   => htmlspecialchars($array['fa'], ENT_QUOTES, 'UTF-8'),
						"la"   => htmlspecialchars($array['la'], ENT_QUOTES, 'UTF-8'),
						"points"   => $array['points'],
						"lat"   => htmlspecialchars($array['Lat'], ENT_QUOTES, 'UTF-8'),
						"lon"   => htmlspecialchars($array['Long'], ENT_QUOTES, 'UTF-8')
						);
		}
		$prep['allaps'] = $apprep;
		$prep['efficiency'] = ($prep['new_aps']/$prep['total_aps'])*100;
		$this->all_users_aps = $prep;
		$this->GeneratePages($prep['total_aps'], $from, $inc, $sort, $ord, 'allap', $user);
		return 1;
	}

	#===================================#
	#   Grab all user Import lists	  #
	#===================================#
	function UsersLists($username = "", $sort = "id", $ord = "DESC", $from = 0, $inc = 100)
	{
		if($username == ""){return 0;}
		
		$sql = "SELECT COUNT(id) AS ApCount FROM files WHERE file_user LIKE ? And ValidGPS = 1";
		$globeprep = $this->sql->conn->prepare($sql);
		$globeprep->bindParam(1, $username, PDO::PARAM_STR);
		$globeprep->execute();
		$globeprepfetch = $globeprep->fetch(2);
		if($globeprepfetch['ApCount'] !== "0"){$user_validgps = 1;}else{$user_validgps = 0;}
		
		#Total APs
		$sql = "SELECT SUM(aps) As aps, SUM(gps) As gps, Min(file_date) As fa, Max(file_date) As la, AVG(NewAPPercent) As NewAPPercent FROM files WHERE file_user like ? GROUP BY file_user";
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$user_counts = $result->fetch(2);

		#New APs
		$sql = "SELECT COUNT(AP_ID)\n"
			. "FROM wifi_ap\n"
			. "INNER JOIN files ON files.id = wifi_ap.File_ID\n"
			. "WHERE files.file_user LIKE ?";
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$new_aps = $rows[0];
		
		#Total Files

		$sql = "SELECT COUNT(id)\n"
			. "FROM files\n"
			. "WHERE file_user LIKE ? And file_date != '' And completed = 1";
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$fcount = $result->fetch(1);
		$total_files = $fcount[0];

		#Check If Registered User
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT `id`\n"
					. "FROM `user_info`\n"
					. "WHERE `username` like ? And `validated` = 0";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT [id]\n"
					. "FROM [user_info]\n"
					. "WHERE [username] like ? And [validated] = 0";
			}
		$vres = $this->sql->conn->prepare($sql);
		$vres->bindParam(1, $username);
		$vres->execute();
		$vfetch = $vres->fetch(1);
		$regid = $vfetch[0];

		#Get All Imports for User
		$sql1 = "SELECT id, file_orig, title, notes, file_date, aps, gps, ValidGPS, NewAPPercent FROM files WHERE file_user LIKE ? And file_date != '' And completed = 1 ORDER BY $sort $ord";
		if($this->sql->service == "mysql"){$sql1 .= " LIMIT $from, $inc";}
		else if($this->sql->service == "sqlsrv"){$sql1 .= " OFFSET $from ROWS FETCH NEXT $inc ROWS ONLY";}
		
		$other_imports = $this->sql->conn->prepare($sql1);
		$other_imports->bindParam(1, $username, PDO::PARAM_STR);
		//$other_imports->bindParam(2, $user_last['id'], PDO::PARAM_INT);
		$other_imports->execute();
		$other_rows = $other_imports->rowCount();
		$other_imports_array = array();
		$flip = 0;
		while($imports = $other_imports->fetch(2))
		{
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
											'validgps' => $imports['ValidGPS'],
											'id' => $imports['id'],
											'file' => htmlspecialchars($imports['file_orig'], ENT_QUOTES, 'UTF-8'),
											'title' => htmlspecialchars($imports['title'], ENT_QUOTES, 'UTF-8'),
											'notes' => htmlspecialchars($imports['notes'], ENT_QUOTES, 'UTF-8'),
											'aps' => $imports['aps'],
											'gps' => $imports['gps'],
											'efficiency'=>$imports['NewAPPercent'],
											'date' => htmlspecialchars($imports['file_date'], ENT_QUOTES, 'UTF-8')
										   );
		}
		$this->user_all_imports_data = array();
		$this->user_all_imports_data['user'] = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
		$this->user_all_imports_data['regid'] = $regid;
		$this->user_all_imports_data['first_import_date'] = htmlspecialchars($user_counts['fa'], ENT_QUOTES, 'UTF-8');
		$this->user_all_imports_data['newest_date'] = htmlspecialchars($user_counts['la'], ENT_QUOTES, 'UTF-8');
		$this->user_all_imports_data['new_aps'] = $new_aps;
		$this->user_all_imports_data['total_aps'] = $user_counts['aps'];
		$this->user_all_imports_data['total_gps'] = $user_counts['gps'];
		$this->user_all_imports_data['total_files'] = $total_files;
		$this->user_all_imports_data['NewAPPercent'] = $user_counts['NewAPPercent'];
		$this->user_all_imports_data['validgps'] = $user_validgps;

		$this->user_all_imports_data['other_imports'] = $other_imports_array;
		
		$this->GeneratePages($total_files, $from, $inc, $sort, $ord, 'alluserlists', $username);
		return 1;
	}

	#===============================================#
	#   Grab the AP's for a given user's Import	 #
	#===============================================#
	function UserAPList($row=0, $sort = "AP_ID", $ord = "DESC")
	{
		if(!$row){return 0;}
		
		# Get import list information
		$sql = "SELECT id, file_orig, file_user, aps, gps, notes, title, file_date, hash, converted, prev_ext, NewAPPercent, size, ValidGPS FROM files WHERE id= ?";
        $result = $this->sql->conn->prepare($sql);
		$result->execute(array($row));
		$user_array = $result->fetch(2);

		$all_aps_array = array();
		$all_aps_array['allaps'] = array();
		$all_aps_array['id'] = $user_array['id'];
		$all_aps_array['file'] = htmlspecialchars($user_array['file_orig'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['user'] = htmlspecialchars($user_array['file_user'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['notes'] = htmlspecialchars($user_array['notes'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['title'] = htmlspecialchars($user_array['title'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['aps'] = htmlspecialchars($user_array['aps'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['gps'] = htmlspecialchars($user_array['gps'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['size'] = htmlspecialchars($user_array['size'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['hash'] = htmlspecialchars($user_array['hash'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['date'] = htmlspecialchars($user_array['file_date'], ENT_QUOTES, 'UTF-8');
		$all_aps_array['NewAPPercent'] = $user_array['NewAPPercent'];
		$all_aps_array['validgps'] = $user_array['ValidGPS'];
		

		#Get APs, First Active, Last Active, and points that go with this list
		$sql = "SELECT wifi_hist.AP_ID, wifi_hist.New, Min(wifi_hist.Hist_Date) As fa, Max(wifi_hist.Hist_Date) As la, Count(wifi_hist.Hist_Date) As list_points, wifi_ap.SSID, wifi_ap.BSSID, wifi_ap.AUTH, wifi_ap.ENCR, wifi_ap.RADTYPE, wifi_ap.CHAN, wifi_ap.points, wifi_gps.Lat, wifi_gps.Lon\n"
				. "FROM wifi_hist\n"
				. "INNER JOIN wifi_ap ON wifi_ap.AP_ID = wifi_hist.AP_ID\n"
				. "LEFT JOIN wifi_gps ON wifi_gps.GPS_ID = wifi_ap.HighGps_ID\n"
				. "WHERE wifi_hist.File_ID = ? \n"
				. "GROUP BY wifi_hist.AP_ID, wifi_hist.New, wifi_ap.SSID, wifi_ap.BSSID, wifi_ap.AUTH, wifi_ap.ENCR, wifi_ap.RADTYPE, wifi_ap.CHAN, wifi_ap.points, wifi_gps.Lat, wifi_gps.Lon\n"
				. "ORDER BY $sort $ord";

		$prep_AP_IDS = $this->sql->conn->prepare($sql);
		$prep_AP_IDS->bindParam(1,$user_array['id'], PDO::PARAM_INT);
		$prep_AP_IDS->execute();
		$count = 0;
		$flip=0;
		while ( $array = $prep_AP_IDS->fetch(2) )
		{
			$count++;
			
			if($flip)
				{$style = "dark";$flip=0;}
			else
				{$style="light";$flip=1;}
			
			if($array['Lat']  == "0.0000" || $array['Lat']  == ""){$validgps = 0;}else{$validgps = 1;}
			if($array['New'] == 1){$update_or_new = "New";}else{$update_or_new = "Update";}
			
			$all_aps_array['allaps'][] = array(
					'id' => $array['AP_ID'],
					'class' => $style,
					'un' => $update_or_new,
					'ssid' => htmlspecialchars($this->formatSSID($array['SSID']), ENT_QUOTES, 'UTF-8'),
					'mac' => htmlspecialchars($array['BSSID'], ENT_QUOTES, 'UTF-8'),
					'chan' => htmlspecialchars($array['CHAN'], ENT_QUOTES, 'UTF-8'),
					'radio' => htmlspecialchars($array['RADTYPE'], ENT_QUOTES, 'UTF-8'),
					'auth' => htmlspecialchars($array['AUTH'], ENT_QUOTES, 'UTF-8'),
					'encry' => htmlspecialchars($array['ENCR'], ENT_QUOTES, 'UTF-8'),
					'fa' => htmlspecialchars($array['fa'], ENT_QUOTES, 'UTF-8'),
					'la' => htmlspecialchars($array['la'], ENT_QUOTES, 'UTF-8'),
					'list_points' => $array['list_points'],
					'points' => $array['points'],
					'lat' => htmlspecialchars($array['Lat'], ENT_QUOTES, 'UTF-8'),
					'lon' => htmlspecialchars($array['Lon'], ENT_QUOTES, 'UTF-8'),
					'validgps' => $validgps
			);
		}
		$all_aps_array['total_aps'] = $count;
		$this->users_import_aps = $all_aps_array;
		return 1;
	}


	function GeneratePages($total_rows, $from, $inc, $sort, $ord, $func="", $user="", $ssid="", $mac="", $chan="", $radio="", $auth="", $encry="", $view="", $id="")
	{
		if($ssid=="" && $mac=="" && $chan=="" && $radio=="" && $auth=="" && $encry=="")
		{
			$no_search = 0;
		}else
		{
			$no_search = 1;
		}
		
		if($view==""){$viewparam="";}else{$viewparam="&view={$view}";}
		if($id==""){$idparam="";}else{$idparam="&id={$id}";}
		

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
			$pages_together = "Pages: &lt;&#45;&#45;  &#91<a class=\"links\" href=\"?{$function_and_username}from=0&inc={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}{$viewparam}{$idparam}\">First</a>&#93 &#45; \r\n";
		}else
		{
			$pages_together = "Pages: &lt;&#45;&#45;  &#91<a class=\"links\" href=\"?{$function_and_username}from=0&inc={$inc}&sort={$sort}&ord={$ord}{$viewparam}{$idparam}\">First</a>&#93 &#45; \r\n";
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
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&inc={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}{$viewparam}{$idparam}\">{$I}</a> &#45; \r\n";
				}else
				{
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&inc={$inc}&sort={$sort}&ord={$ord}{$viewparam}{$idparam}\">{$I}</a> &#45; \r\n";
				}
			}
		}
		if($pages==1){$cal_from = 0;}else{$cal_from = (($pages-1)*$inc);}
		if($no_search)
		{
			$pages_together .= " &#91<a class=\"links\" href=\"?{$function_and_username}from=".$cal_from."&inc={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}{$viewparam}{$idparam}\">Last</a>&#93 &#45;&#45;&gt; \r\n";
		}else
		{
			$pages_together .= " &#91<a class=\"links\" href=\"?{$function_and_username}from=".$cal_from."&inc={$inc}&sort={$sort}&ord={$ord}{$viewparam}{$idparam}\">Last</a>&#93 &#45;&#45;&gt; \r\n";
		}
		if(!$total_rows){$pages_together = "";}
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
}
