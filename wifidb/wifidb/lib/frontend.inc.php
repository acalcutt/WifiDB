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
			require_once($config['wifidb_install'].'/lib/manufactures.inc.php');
			$this->sec->LoginCheck();
			$this->meta = new stdClass();
			$this->meta->ads = $config['ads'];
			$this->meta->tracker = $config['tracker'];
			$this->meta->header = $config['header'];

			define('WWW_DIR', $this->PATH);
			define('SMARTY_DIR', $this->PATH.'/smarty/');
			require_once(SMARTY_DIR.'Smarty.class.php');
			$this->smarty = new Smarty();
			$this->smarty->setTemplateDir( WWW_DIR.'smarty/templates/'.$this->theme.'/' );
			$this->smarty->setCompileDir( WWW_DIR.'smarty/templates_c/' );
			$this->smarty->setCacheDir( WWW_DIR.'smarty/cache/' );
			$this->smarty->setConfigDir( WWW_DIR.'/smarty/configs/');

			$this->smarty->assign('wifidb_host_url', $this->URL_PATH);
			$this->smarty->assign('wifidb_meta_header', $this->meta->header);
			$this->smarty->assign('wifidb_theme', $this->theme);
			$this->smarty->assign('wifidb_version_label', $this->ver_array['wifidb']);

			$this->smarty->assign('critical_error_message', '');

			$this->smarty->assign("redirect_func", "");
			$this->smarty->assign("redirect_html", "");
			$this->smarty->assign('wifidb_login_label', $this->sec->LoginLabel);
			$this->smarty->assign('wifidb_login_html', $this->sec->LoginHtml);
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
		$sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = ?";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$newArray = $prep->fetch(2);

		if($newArray['ssid'] == '')
		{
			$new_ssid = '[Blank SSID]';
		}
		elseif(!ctype_print($newArray['ssid']))
		{
			$new_ssid = '['.$newArray['ssid'].']';
		}
		else
		{
			$new_ssid = $newArray['ssid'];
		}

		$ap_data = array(
			'id'=>$newArray['id'],
			'radio'=>$newArray['radio'],
			'manuf'=>$newArray['manuf'],
			'mac'=>$newArray['mac'],
			'ssid'=>$new_ssid,
			'chan'=>$newArray['chan'],
			'encry'=>$newArray['encry'],
			'auth'=>$newArray['auth'],
			'btx'=>$newArray["BTx"],
			'otx'=>$newArray["OTx"],
			'fa'=>$newArray["FA"],
			'la'=>$newArray["LA"],
			'nt'=>$newArray["NT"],
			'label'=>$newArray["label"],
			'user'=>$newArray["username"]
		);

		if($newArray['lat'] == "0.0000")
		{
			$globe_html = "<img width=\"20px\" src=\"".$this->URL_PATH."/img/globe_off.png\">";
		}else
		{
			$globe_html = "<a href=\"".$this->URL_PATH."/opt/export.php?func=exp_all_signal&id=".$newArray['id']."\" title=\"Export to KMZ\">
			<img width=\"20px\" src=\"".$this->URL_PATH."/img/globe_on.png\"></a>";
		}

		$sql = "SELECT  `id`, `signal`, `rssi`, `gps_id`, `username`
				FROM `wifi`.`wifi_signals`
				WHERE `ap_hash` =  ?
				ORDER BY `time_stamp` ASC";
		$prep1 = $this->sql->conn->prepare($sql);
		$prep1->bindParam(1, $newArray["ap_hash"], PDO::PARAM_STR);
		$prep1->execute();
		if($this->sql->checkError() !== 0)
		{
			throw new Exception("Error getting Signal History.");
		}

		$flip = 0;
		$prev_date = 0;
		$date_range = -1;
		$signal_runs = array();
		$signals = $prep1->fetchAll(2);

		$sql_gps = "SELECT `lat`, `long`, `sats`, `hdp`, `track`, `date`, `time`, `mph`, `kmh`
						FROM `wifi`.`wifi_gps`
						WHERE `id` = ?";
		$prep_gps = $this->sql->conn->prepare($sql_gps);
		$from = 0;
		foreach($signals as $field)
		{
			$prep_gps->bindParam(1, $field['gps_id'], PDO::PARAM_INT);
			$prep_gps->execute();
			if($this->sql->checkError() !== 0)
			{
				throw new Exception("Error getting GPS");
			}
			$field_g = $prep_gps->fetch(2);
			if($flip){$class="light";$flip=0;}else{$class="dark";$flip=1;}
			if($prev_date < strtotime($field_g['date']))
			{
				$date_range++;
				$signal_runs[$date_range]['id'] = $date_range;
				$signal_runs[$date_range]['start'] = $field_g['date']." ".$field_g['time'];
				$signal_runs[$date_range]['descstart'] = $field_g['time'];
				$signal_runs[$date_range]['desc'] = $field_g['time'];
				$signal_runs[$date_range]['user'] = $field['username'];
				$signal_runs[$date_range]['start_id'] = $field['id'];
				$signal_runs[$date_range]['from'] = $from;
			}else
			{
				if($signal_runs[$date_range]['user'] != $field['username'])
				{
					$signal_runs[$date_range]['user'] .= " and ".$field['username'];
				}
				$signal_runs[$date_range]['desc'] = $field_g['date'].": ".$signal_runs[$date_range]['descstart']." - ".$field_g['time'];
				$signal_runs[$date_range]['stop'] = $field_g['date']." ".$field_g['time'];
				$signal_runs[$date_range]['limit'] = $from+1;
			}
			$from++;
			$prev_date = strtotime($field_g['date']);

			$signal_runs[$date_range]['gps'][] = array(
														'class'=>$class,
														'lat'=>$field_g["lat"],
														'long'=>$field_g["long"],
														'sats'=>$field_g["sats"],
														'date'=>$field_g["date"],
														'time'=>$field_g["time"],
														'signal'=>$field["signal"],
														'rssi'=>$field["rssi"]
													);
		}

		$list = array();
		$id_find = "%-{$id}:%";
		$id_find_firstitem = "{$id}:%";
		$prep2 = $this->sql->conn->prepare("SELECT * FROM `wifi`.`user_imports` WHERE (`points` LIKE ? OR `points` LIKE ?)");
		$prep2->bindParam(1, $id_find, PDO::PARAM_STR);
		$prep2->bindParam(2, $id_find_firstitem, PDO::PARAM_STR);
		$prep2->execute();
		if($this->sql->checkError() !== 0)
		{
			throw new Exception("Error getting associated lists");
		}

		while ($field = $prep2->fetch(1))
		{
			if($flip){$class="light";$flip=0;}else{$class="dark";$flip=1;}
			preg_match("/(?P<ap_id>{$id}):(?P<stat>\d+)/", $field['points'], $matches);
			$list[]= array(
							'class'=>$class,
							'id'=>$field['id'],
							'nu'=>$matches['stat'],
							'date'=>$field['date'],
							'aps'=>$field['aps'],
							'username'=>$field['username'],
							'title'=>$field['title'],
							'title_id'=>$field['file_id']
							);

		}
		$ap_data['from'] = $signals[0]['id'];
		$ap_data['limit'] = $prep1->rowCount();
		return array(
						$newArray['ssid'],
						$signal_runs,
						$list,
						$globe_html,
						$ap_data
					);
	}

	function GetAnnouncement()
	{
		$result = $this->sql->conn->query("SELECT `body` FROM `wifi`.`annunc` WHERE `set` = '1'");
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
		$this->footer .= $this->meta->tracker.$this->meta->ads;
		return 1;
	}

	#===================================#
	#   Grab the stats for All Users	#
	#===================================#
	Public function AllUsers()
	{
		$sql = "SELECT `username` FROM `wifi`.`user_imports` ORDER BY `username` ASC";
		$result = $this->sql->conn->query($sql);

		$users_all = $result->fetchAll(2);
		foreach($users_all as $user)
		{
			$user_all[] = $user['username'];
		}

		$users = array_unique($user_all);
		$tablerowid = 0;
		$row_color = 0;
		$this->all_users_data = array();
		$prev_id = 0;
		foreach($users as $user)
		{
			$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `username`= ? ORDER BY `id` ASC";
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $user, PDO::PARAM_STR);
			$prep->execute();

			$imports = (int) $prep->rowCount();
			if($imports === 0){continue;}

			$row_color2 = 1;
			$pre_user = 1;
			$tablerowid++;
			while ($user_array = $prep->fetch(2))
			{
				if($user_array['points'] === ""){continue;}
				$username = $user_array['username'];

				if ($user_array['title'] === "" or $user_array['title'] === " "){ $user_array['title']="UNTITLED";}
				if ($user_array['date'] === ""){ $user_array['date']="No date, hmm..";}

				$search = array('\n','\r','\n\r');
				$user_array['notes'] = str_replace($search, "", $user_array['notes']);

				if ($user_array['notes'] == ""){ $user_array['notes']="No Notes, hmm..";}
				$notes = $user_array['notes'];
				$points = explode("-",$user_array['points']);
				$pc = count($points);

				if($pre_user)
				{
					if($prev_id == $user_array['id'] )
					{$prev_id = $user_array['id'];continue 2;}
					else{$prev_id = $user_array['id'];}

					if($row_color2 == 1)
					{$row_color2 = 0; $color2 = "light";}
					else{$row_color2 = 1; $color2 = "dark";}

					if($row_color == 1)
					{$row_color = 0; $color = "light";}
					else{$row_color = 1; $color = "dark";}

					$this->all_users_data[$user] = array(
								'rowid'	=> $tablerowid,
								'class'	=> $color,
								'id'	   => $user_array['id'],
								'imports'  => $imports,
								'username' => $username,
								'data'	 => array(
													array(
														'id'	=> $user_array['id'],
														'class' => $color2,
														'title' => $user_array['title'],
														'notes' => wordwrap(str_replace("\r\n", "", $notes), 56, "<br />\n"),
														'aps'   => $pc,
														'date'  => $user_array['date']
													),
												),
							);
					$pre_user = 0;
				}else
				{
					if($row_color2 == 1)
					{$row_color2 = 0; $color2 = "light";}
					else{$row_color2 = 1; $color2 = "dark";}

					$this->all_users_data[$user]['data'][] = array(
								'id'	=> $user_array['id'],
								'class' => $color2,
								'title' => $user_array['title'],
								'notes' => wordwrap(str_replace("\r\n", "", $notes), 56, "<br />\n"),
								'aps'   => $pc,
								'date'  => $user_array['date']
							);
				}
			}
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
		if($inputs['sort'] == ''){$inputs['sort'] = 'id';}
		if($inputs['ord'] == ''){$inputs['ord'] = 'ASC';}

		$prep = array();
		$apprep = array();
		$prep['allaps'] = array();
		$prep['username'] = $user;

		$sql = "SELECT count(`id`) FROM `wifi`.`wifi_pointers` WHERE `username` LIKE ?";
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$prep['total_aps'] = $rows[0];

		$flip = 0;
		$sql = "SELECT `id`,`ssid`,`mac`,`radio`,`auth`,`encry`,`chan`,`lat`, `FA`,`LA` FROM
				`wifi`.`wifi_pointers`
				WHERE `username` = ? ORDER BY `{$inputs['sort']}` {$inputs['ord']} LIMIT {$inputs['from']}, {$inputs['to']}";

		$result1 = $this->sql->conn->prepare($sql);
		$result1->bindParam(1, $user, PDO::PARAM_STR);
		$result1->execute();

		while($array = $result1->fetch(2))
		{
			if($flip)
				{$style = "dark";$flip=0;}
			else
				{$style="light";$flip=1;}

			if($array['lat'] == "0.0000")
				{$globe = "off";}
			else
				{$globe = "on";}

			if($array['ssid'] == "")
				{$ssid = "Unknown";}
			else
				{$ssid = $array['ssid'];}

			$apprep[] = array(
						"id" => $array['id'],
						"class" => $style,
						"globe" => $globe,
						"ssid" => $ssid,
						"mac" => $array['mac'],
						"radio" => $array['radio'],
						"auth" => $array['auth'],
						"encry" => $array['encry'],
						"chan" => $array['chan'],
						"fa"   => $array['FA'],
						"la"   => $array['LA']
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
		$total_aps = array();
		
		#Total APs
		$sql = "SELECT count(`id`) FROM `wifi`.`wifi_pointers` WHERE `username` LIKE ?";
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$total = $rows[0];

		#Get First Active AP
		$sql = "SELECT id, username, date FROM `wifi`.`user_imports` WHERE `username` LIKE ? ORDER BY `id` ASC LIMIT 1";
		$prep2 = $this->sql->conn->prepare($sql);
		$prep2->bindParam(1, $username, PDO::PARAM_STR);
		$prep2->execute();
		$user_first = $prep2->fetch(2);
	
		#Get Last Active AP
		$sql = "SELECT id, aps, gps, title, date FROM `wifi`.`user_imports` WHERE `username` LIKE ? ORDER BY `id` DESC LIMIT 1";
		$prep1 = $this->sql->conn->prepare($sql);
		$prep1->bindParam(1, $username, PDO::PARAM_STR);
		$prep1->execute();
		$user_last = $prep1->fetch(2);

		#Get All Imports for User
		$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `username` LIKE ? AND `id` != ? ORDER BY `id` DESC";
		#echo $sql."\r\n";
		$other_imports = $this->sql->conn->prepare($sql);
		$other_imports->execute(array($username, $user_last['id']));
		$other_rows = $other_imports->rowCount();
		$other_imports_array = array();
		if($other_rows > 0)
		{
			#var_dump($other_rows);
			$flip = 0;
			while($imports = $other_imports->fetch(2))
			{
				#var_dump($imports);
				if($imports['points'] == ""){continue;}
				if($flip)
				{
					$style = "dark";
					$flip=0;
				}else
				{
					$style="light";
					$flip=1;
				}
				$import_id = $imports['id'];
				$import_title = $imports['title'];
				$import_date = $imports['date'];
				$import_ap = $imports['aps'];

				$other_imports_array[] = array(
												'class' => $style,
												'id' => $import_id,
												'title' => $import_title,
												'aps' => $import_ap,
												'date' => $import_date
											   );
			}
		}
		$this->user_all_imports_data = array();
		$this->user_all_imports_data['user_id'] = $user_first['id'];
		$this->user_all_imports_data['username'] = $user_first['username'];
		$this->user_all_imports_data['first_import_date'] = $user_first['date'];
		$this->user_all_imports_data['total_aps'] = $total;

		$this->user_all_imports_data['newest_id'] = $user_last['id'];
		$this->user_all_imports_data['newest_aps'] = $user_last['aps'];
		$this->user_all_imports_data['newest_gps'] = $user_last['gps'];
		$this->user_all_imports_data['newest_title'] = $user_last['title'];
		$this->user_all_imports_data['newest_date'] = $user_last['date'];

		$this->user_all_imports_data['other_imports'] = $other_imports_array;
		return 1;
	}

	#===============================================#
	#   Grab the AP's for a given user's Import	 #
	#===============================================#
	function UserAPList($row=0)
	{
		if(!$row){return 0;}
		$sql = "SELECT * FROM `wifi`.`user_imports` WHERE `id`= ?";
		$result = $this->sql->conn->prepare($sql);
		$result->execute(array($row));
		$user_array = $result->fetch(2);

		$all_aps_array = array();
		$all_aps_array['allaps'] = array();
		$all_aps_array['username'] = $user_array['username'];

		$all_aps_array['notes'] = $user_array['notes'];
		$all_aps_array['title'] = $user_array['title'];

		$points = explode("-", $user_array['points']);
		$flip = 0;
		$sql = "SELECT `id`, `ssid`, `mac`, `chan`, `radio`, `auth`, `encry`, `LA`, `FA`, `lat` FROM `wifi`.`wifi_pointers` WHERE `id`= ?";
		$result = $this->sql->conn->prepare($sql);
		$count = 0;
		foreach($points as $ap)
		{
			$ap_exp = explode(":" , $ap);
			$apid = $ap_exp[0];

			#if($ap_exp[0] == 0){continue;}
			$count++;

			if($flip)
				{$style = "dark";$flip=0;}
			else
				{$style="light";$flip=1;}

			if($ap_exp[1] == 1)
			{
				$update_or_new = "Update";
			}else
			{
				$update_or_new = "New";
			}
			$result->bindParam(1, $apid, PDO::PARAM_STR);
			$result->execute();
			$ap_array = $result->fetch(2);

			if($ap_array['lat'] == "0.0000")
			{
				$globe = "off";
				$globe_html = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."../img/globe_off.png\">";
			}else
			{
				$globe = "on";
				$globe_html = "<a href=\"".$dbcore->URL_PATH."../opt/export.php?func=exp_all_signal&id=".$ap_array['id']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."../img/globe_on.png\"></a>";
			}

			if($ap_array['ssid'] == '')
			{
				$ssid = '[Blank SSID]';
			}
			elseif(!ctype_print($ap_array['ssid']))
			{
				$ssid = '['.$ap_array['ssid'].']';
			}
			else
			{
				$ssid = $ap_array['ssid'];
			}

			$all_aps_array['allaps'][] = array(
					'id' => $ap_array['id'],
					'class' => $style,
					'un' => $update_or_new,
					'globe' => $globe,
					'globe_html' => $globe_html,
					'ssid' => $ssid,
					'mac' => $ap_array['mac'],
					'chan' => $ap_array['chan'],
					'radio' => $ap_array['radio'],
					'auth' => $ap_array['auth'],
					'encry' => $ap_array['encry'],
					'fa' => $ap_array['FA'],
					'la' => $ap_array['LA']
				);
		}
		$all_aps_array['total_aps'] = $count;
		$this->users_import_aps = $all_aps_array;
		return 1;
	}


	function GeneratePages($total_rows, $from, $inc, $sort, $ord, $func="", $user="", $ssid="", $mac="", $chan="", $radio="", $auth="", $encry="")
	{
		if($ssid=="" && $mac=="" && $chan=="" && $radio=="" && $auth=="" && $encry=="")
		{
			$no_search = 0;
		}else
		{
			$no_search = 1;
		}

		$function_and_username = "";
		if($func != "")
		{
			$function_and_username = "func=".$func;
		}

		if($user != "")
		{
			$function_and_username .= "&amp;user={$user}&amp;";
		}

		$pages = ($total_rows/$inc);
		$mid_page = round($from/$inc, 0);
		if($no_search)
		{
			$pages_together = "Pages: &lt;&#45;&#45;  &#91<a class=\"links\" href=\"?{$function_and_username}from=0&to={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}\">First</a>&#93 &#45; \r\n";
		}else
		{
			$pages_together = "Pages: &lt;&#45;&#45;  &#91<a class=\"links\" href=\"?{$function_and_username}from=0&to={$inc}&sort={$sort}&ord={$ord}\">First</a>&#93 &#45; \r\n";
		}
		for($I=($mid_page - 5); $I<=($mid_page + 5); $I++)
		{
			if($I <= 0){continue;}
			if($I > $pages){break;}
			$cal_from = ($I*$inc);
			if($I==1)
			{
				$cal_from = $cal_from-$inc;
				if($no_search)
				{
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&to={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}\">{$I}</a> &#45; \r\n";
				}else
				{
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&to={$inc}&sort={$sort}&ord={$ord}\">{$I}</a> &#45; \r\n";
				}
			}elseif($mid_page == $I)
			{
				$pages_together .= " <b><i>{$I}</i></b> - \r\n";
			}else
			{
				if($no_search)
				{
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&to={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}\">{$I}</a> &#45; \r\n";
				}else
				{
					$pages_together .= " <a class=\"links\" href=\"?{$function_and_username}from={$cal_from}&to={$inc}&sort={$sort}&ord={$ord}\">{$I}</a> &#45; \r\n";
				}
			}
		}
		$pages_together .= " &#91<a class=\"links\" href=\"?{$function_and_username}from=".(($pages*$inc)-$inc)."&to={$inc}&sort={$sort}&ord={$ord}&ssid={$ssid}&mac={$mac}&chan={$chan}&radio={$radio}&auth={$auth}&encry={$encry}\">Last</a>&#93 &#45;&#45;&gt; \r\n";
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

	function Search($ssid, $mac, $radio, $chan, $auth, $encry, $ord, $sort, $from = NULL, $inc = NULL)
	{
		$sql1 = "SELECT * FROM `wifi`.`wifi_pointers` WHERE
			`ssid` LIKE ? AND
			`mac` LIKE ? AND
			`radio` LIKE ? AND
			`chan` LIKE ? AND
			`auth` LIKE ? AND
			`encry` LIKE ? ORDER BY `".$sort."` ".$ord;
		if($from !== NULL And $inc !== NULL){$sql1 .=  " LIMIT ".$from.", ".$inc;}
		$prep1 = $this->sql->conn->prepare($sql1);

		$sql2 = "SELECT * FROM `wifi`.`wifi_pointers` WHERE
				`ssid` LIKE ? AND
				`mac` LIKE ? AND
				`radio` LIKE ? AND
				`chan` LIKE ? AND
				`auth` LIKE ? AND
				`encry` LIKE ? ORDER BY `".$sort."` ".$ord;
		$prep2 = $this->sql->conn->prepare($sql2);

		$save_url = 'ord='.$ord.'&sort='.$sort.'&from='.$from.'&to='.$inc;
		$export_url = '';
		if($ssid!='')
		{
			$save_url   .= '&ssid='.$ssid;
			$export_url .= '&ssid='.$ssid;
		}

		if($mac!='')
		{
			$save_url   .= '&mac='.$mac;
			$export_url .= '&mac='.$mac;
		}

		if($radio!='')
		{
			$save_url   .= '&radio='.$radio;
			$export_url .= '&radio='.$radio;
		}

		if($chan!='')
		{
			$save_url   .= '&chan='.$chan;
			$export_url .= '&chan='.$chan;
		}

		if($auth!='')
		{
			$save_url   .= '&auth='.$auth;
			$export_url .= '&auth='.$auth;
		}

		if($encry!='')
		{
			$save_url   .= '&encry='.$encry;
			$export_url .= '&encry='.$encry;
		}

		$ssid = $ssid."%";
		$prep1->bindParam(1, $ssid, PDO::PARAM_STR);
		$prep2->bindParam(1, $ssid, PDO::PARAM_STR);
		$mac = $mac."%";
		$prep1->bindParam(2, $mac, PDO::PARAM_STR);
		$prep2->bindParam(2, $mac, PDO::PARAM_STR);
		$radio = $radio."%";
		$prep1->bindParam(3, $radio, PDO::PARAM_STR);
		$prep2->bindParam(3, $radio, PDO::PARAM_STR);
		$chan = $chan."%";
		$prep1->bindParam(4, $chan, PDO::PARAM_STR);
		$prep2->bindParam(4, $chan, PDO::PARAM_STR);
		$auth = $auth."%";
		$prep1->bindParam(5, $auth, PDO::PARAM_STR);
		$prep2->bindParam(5, $auth, PDO::PARAM_STR);
		$encry = $encry."%";
		$prep1->bindParam(6, $encry, PDO::PARAM_STR);
		$prep2->bindParam(6, $encry, PDO::PARAM_STR);
		$prep1->execute();
		$prep2->execute();
		$total_rows = $prep2->rowCount();

		$row_color = 0;
		$results_all = array();
		$i=0;
		while ($newArray = $prep1->fetch(2))
		{
			if($row_color == 1)
			{
				$row_color = 0;
				$results_all[$i]['class'] = "light";
			}
			else
			{
				$row_color = 1;
				$results_all[$i]['class'] = "dark";
			}
			if($newArray['lat'] == "0.0000")
			{
				$results_all[$i]['globe_html'] = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."../img/globe_off.png\">";
			}else
			{
				$results_all[$i]['globe_html'] = "<a href=\"".$dbcore->URL_PATH."export.php?func=exp_all_signal&id=".$newArray['id']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."../img/globe_on.png\"></a>";
			}
			if($newArray['ssid'] == '')
			{
				$results_all[$i]['ssid'] = '[Blank SSID]';
			}
			elseif(!ctype_print($newArray['ssid']))
			{
				$results_all[$i]['ssid'] = '['.$newArray['ssid'].']';
			}
			else
			{
				$results_all[$i]['ssid'] = $newArray['ssid'];
			}
			$results_all[$i]['id'] = $newArray['id'];
			$results_all[$i]['mac'] = $newArray['mac'];
			$results_all[$i]['chan'] = $newArray['chan'];
			$results_all[$i]['auth'] = $newArray['auth'];
			$results_all[$i]['encry'] = $newArray['encry'];
			$results_all[$i]['radio']=$newArray['radio'];
			$results_all[$i]['FA']=$newArray['FA'];
			$results_all[$i]['LA']=$newArray['LA'];
			$results_all[$i]['ap_hash']=$newArray['ap_hash'];
			$i++;
		}

		return array($total_rows, $results_all, $save_url, $export_url);
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
			$half_mac = substr(str_replace(":", "", $aps['mac']), 0, 6);
			$manuf = $this->manufactures[$half_mac];
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
			$result = $this->sql->conn->prep("SELECT `date`,`time` FROM `wifi`.`wifi_gps` WHERE `id` = '{$first_gps_id}'");
			$first_data = $result->fetch(1);
			$fa = $first_data["date"]." ".$first_data["time"];

			$sig_c = count($signal_exp);
			$last = explode(",", $signal_exp[$sig_c-1]);
			$last_gps_id = $last[0];
			$result = $this->sql->conn->query("SELECT `date`,`time` FROM `wifi`.`wifi_gps` WHERE `id` = '{$last_gps_id}'");
			$last_data = $result->fetch(1);
			$la = $last_data["date"]." ".$last_data["time"];

			$sql_1 = "SELECT * FROM `wifi`.`wifi_gps` WHERE `id` = ?";
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
				"manuf"=>$manuf,
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
