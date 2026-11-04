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
	// Set by the constructor and the methods (PHP 8.2 deprecates making a property by assigning it)
	public $all_users_aps, $all_users_data, $footer, $login_bar, $meta, $pages_together, $smarty,
		$user_all_imports_data, $users_import_aps, $wifidb_host_url;
	private $visitor_country = false;   # VisitorCountry(): false until looked up, then the code or null
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

			$this->smarty = new Smarty();
			$this->smarty->template_dir = $config['wifidb_install'].'themes/'.$this->theme.'/templates/';
			$this->smarty->compile_dir  = $config['wifidb_install'].'smarty/templates_c/'.$this->theme.'/';
			$this->smarty->config_dir   = $config['wifidb_install'].'smarty/configs/'.$this->theme.'/';
			$this->smarty->cache_dir    = $config['wifidb_install'].'smarty/cache/'.$this->theme.'/';

			$this->smarty->assign('themeurl', $this->URL_PATH.'themes/'.$this->theme.'/');
			$this->smarty->assign('wifidb_host_url', $this->URL_PATH);
			$this->wifidb_host_url = $this->URL_PATH;
			$this->smarty->assign('wifidb_theme', $this->theme);
			$this->smarty->assign('wifidb_version_label', $this->ver_array['wifidb']);
			$this->smarty->assign('tileserver_gl_url', $this->tileserver_gl_url);
			// Expose Google API key to templates (if set in config)
			$this->smarty->assign('googleApiKey', isset($config['googleApiKey']) ? $config['googleApiKey'] : '');

			// Detect IP Version and assign to template
			// Get Client IP
			$client_ip = $_SERVER['REMOTE_ADDR'];
			if (isset($_SERVER['HTTP_X_FORWARDED_FOR']) && $_SERVER['HTTP_X_FORWARDED_FOR'] != '') {
				$client_ip_list = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
				$client_ip = trim($client_ip_list[0]);
			}
			// Check if Client IP contains a colon, which indicates IPv6
			$ip_version = (strpos($client_ip, ':') !== false) ? 'IPv6' : 'IPv4';
			$this->smarty->assign('ip_version', $ip_version);
			$this->AssignTracking(isset($config['geoip_country_db']) ? $config['geoip_country_db'] : '');
			$this->CountPageView($config);

			$this->smarty->assign('critical_error_message', '');

			$this->smarty->assign("redirect_func", "");
			$this->smarty->assign("redirect_html", "");
			$this->smarty->assign('wifidb_login_label', $this->sec->LoginLabel);
			$this->smarty->assign('wifidb_login_user', $this->sec->LoginUser);
			$this->smarty->assign('wifidb_login_privs', $this->sec->privs);
			$this->smarty->assign('wifidb_login_priv_name', $this->sec->priv_name);
			# Admin buttons show only in admin mode; an Administrator outside it sees a link to turn it on
			$this->smarty->assign('wifidb_is_admin', $this->sec->IsAdministrator());
			$this->smarty->assign('wifidb_admin_mode', $this->sec->AdminModeActive(false));
			$this->smarty->assign('wifidb_login_logged_in', $this->sec->login_check);
			$this->smarty->assign('wifidb_message_unread_count', $this->GetMessageCount($this->sec->LoginUser));
			$this->smarty->assign('wifidb_notification_unread_count', $this->GetNotificationCount($this->sec->LoginUser));
			$this->smarty->assign('wifidb_current_uri', $this->sec->LoginUri);
			$this->smarty->assign('ct_form_time', time());   # spam_check_fields.tpl: when a form was shown
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
				. "WHERE user2read = 0 And user2del = 0 And user_info.username = ?";
			$result = $this->sql->conn->prepare($sql);
			$result->bindParam(1, $username);
			$result->execute();
			$array = $result->fetch(2);
			$message_count = $array['unread_count'];
		}
		return $message_count;
	}

	// The count shown in the header. A missing table (a site that has not run its migrations yet) must not break the page.
	function GetNotificationCount($username="")
	{
		if(!$username) { return 0; }
		try
		{
			require_once dirname(__FILE__)."/notify.inc.php";
			$notifier = new Notifier($this->sql->conn);
			return $notifier->UnreadCount($username);
		}
		catch(Throwable $e)
		{
			return 0;
		}
	}

	function GetAnnouncement()
	{
		$sql = "SELECT body FROM annunc WHERE enabled = '1'";
		$result = $this->sql->conn->query($sql);
		$array = $result->fetch(2);
		if ($this->sql->checkError() || !is_array($array) || !isset($array['body']) || $array['body'] == "")
		{
			return 0;
		}
		return $array;
	}


	function htmlheader()
	{
		if((!defined('WIFIDB_INSTALL_FLAG') || WIFIDB_INSTALL_FLAG != "installing") && $this->sec->login_check)
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
		$ann_body = '';
		if (is_array($announc) && isset($announc['body']) && $announc['body'] !== '') {
			$ann_body = $announc['body'];
		}

		$this->smarty->assign("wifidb_announce_header", '<p class="annunc_text">'. $ann_body .'</p>');
		$this->smarty->assign("wifidb_mysticache_link", $wifidb_mysticache_link);
		$this->login_bar = $login_bar;
		return 1;
	}


	/**
	 * Google Analytics, AdSense, the cookie banner and what search engines read, as Site Settings has them, for header.tpl
	 * and footer.tpl: nothing of
	 * Google's loads until an ID is set, and the banner is for the visitors "Visitors' consent" says to ask (by their
	 * country, from $geoipDb, lib/visitor_country.inc.php; not looked up for one who has already chosen).
	 */
	private function AssignTracking($geoipDb)
	{
		$settings = $this->sec->Settings();
		$analytics = $settings->Get('analytics_id');
		$client = $settings->Get('adsense_client');
		$slot = $client !== '' ? $settings->Get('adsense_slot') : '';
		$tracking = ($analytics !== '' || $client !== '');
		$ask = false;
		$credit = false;
		if($tracking)
		{
			require_once dirname(__FILE__).'/cookie_consent.inc.php';
			require_once dirname(__FILE__).'/visitor_country.inc.php';
			$mode = CookieConsent::Mode($settings->Get('analytics_consent'));
			$country = null;
			if($mode === 'region')
			{
				$credit = is_string($geoipDb) && $geoipDb !== '' && is_file($geoipDb);   # DB-IP's licence asks for a link
				if(!isset($_COOKIE[CookieConsent::COOKIE])) { $country = $this->VisitorCountry($geoipDb); }
			}
			$ask = CookieConsent::Ask($mode, $country);
		}
		$this->smarty->assign('wifidb_tracking', $tracking);
		$this->smarty->assign('wifidb_consent_ask', $ask);
		$this->smarty->assign('wifidb_analytics_id', $analytics);
		$this->smarty->assign('wifidb_adsense_client', $slot !== '' ? $client : '');
		$this->smarty->assign('wifidb_adsense_slot', $slot);
		$this->smarty->assign('wifidb_adsense_slot_menu', $client !== '' ? $settings->Get('adsense_slot_menu') : '');
		$this->smarty->assign('wifidb_geoip_credit', $credit);

		# What search engines read (Site Settings), and this site's own address for the canonical link, not wifidb.net's
		$this->smarty->assign('wifidb_site_description', $settings->Get('site_description'));
		$this->smarty->assign('wifidb_noindex', $settings->Get('search_noindex'));
		$this->smarty->assign('wifidb_google_verification', $settings->Get('google_site_verification'));
		$this->smarty->assign('wifidb_bing_verification', $settings->Get('bing_site_verification'));
		$this->smarty->assign('wifidb_site_origin', preg_replace('#^(https?://[^/]+).*$#i', '$1', $this->URL_PATH));
		# where the map's contour lines get their elevation (Site Settings): a tile address, or a TileJSON the map reads
		# the rest from. encoding and maxzoom are null for "as the TileJSON says" (map.tpl's own defaults otherwise).
		$dem_url = $settings->Get('map_dem_url');
		$dem_encoding = $settings->Get('map_dem_encoding');
		$dem_maxzoom = $settings->Get('map_dem_maxzoom');
		$this->smarty->assign('wifidb_map_dem_url', $dem_url);
		$this->smarty->assign('wifidb_map_dem', json_encode(array(
			'url' => $dem_url,
			'tilejson' => strpos($dem_url, '{z}') === false,
			'encoding' => $dem_encoding === 'auto' ? null : $dem_encoding,
			'maxzoom' => $dem_maxzoom === '' ? null : (int)$dem_maxzoom,
		), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT));
	}

	/**
	 * The visitor's country (lib/visitor_country.inc.php), looked up once a page and kept for a day for each address in
	 * APCu, when this node has it (the cookie banner and the traffic counts both ask). @return string|null
	 */
	private function VisitorCountry($geoipDb)
	{
		if($this->visitor_country !== false) { return $this->visitor_country; }
		require_once dirname(__FILE__).'/visitor_country.inc.php';
		$ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '';
		# (a CDN that says the country: nothing to look up)
		$cache = !isset($_SERVER['HTTP_CF_IPCOUNTRY']) && $ip !== '' && function_exists('apcu_fetch') && filter_var(ini_get('apc.enabled'), FILTER_VALIDATE_BOOLEAN);
		if($cache)
		{
			$cached = apcu_fetch('wifidb_country_'.$ip, $found);
			if($found) { return $this->visitor_country = ($cached === '' ? null : $cached); }
		}
		$this->visitor_country = VisitorCountry::Lookup($_SERVER, $geoipDb);
		if($cache) { apcu_store('wifidb_country_'.$ip, (string)$this->visitor_country, 86400); }
		return $this->visitor_country;
	}

	/** This page view, for admin/traffic.php (lib/traffic.inc.php). Never in the page's way. */
	private function CountPageView(array $config)
	{
		try
		{
			require_once dirname(__FILE__).'/traffic.inc.php';
			$traffic = Traffic::ForConfig($config);
			$agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';
			if($traffic === null || $_SERVER['REQUEST_METHOD'] !== 'GET' || Traffic::IsBot($agent)) { return; }
			# the script from the site's root: opt/fetch.php
			$script = isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '';
			$base = ($this->root != '') ? '/'.trim($this->root, '/').'/' : '/';
			$page = strpos($script, $base) === 0 ? substr($script, strlen($base)) : ltrim($script, '/');
			$traffic->Page($page, (string)$this->sec->LoginUser, $_SERVER,
				$this->VisitorCountry(isset($config['geoip_country_db']) ? $config['geoip_country_db'] : ''));
		}
		catch(Throwable $e) {}
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
		return 1;
	}

	#===================================#
	#   Grab the stats for All Users	#
	#===================================#
	Public function AllUsers($sort = "file_user", $ord = "ASC", $from = 0, $inc = 250)
	{
		// $sort is also handed to GeneratePages() to build paging URLs, so quote a
		// copy for the SQL rather than mutating the original.
		$sort_sql = $this->sql->sortIdent($sort);
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
		
		if($this->sql->service == "pgsql")
		{
			// Postgres lower-cases unquoted identifiers *and* unquoted aliases, so
			// both the ValidGPS/NewAPPercent columns and every alias this method
			// reads back by name ($userfetch['FileCount'], ...) must be quoted.
			$sql = 'SELECT file_user, Count(id) As "FileCount", MAX("ValidGPS") As "ValidGPS", SUM(aps) As "ApCount", SUM(gps) As "GpsCount",AVG("NewAPPercent") As "NewAPPercent",MIN(file_date) As "FirstImport",MAX(file_date) As "LastImport"'."\n"
				. "FROM files\n"
				. "WHERE completed = 1\n"
				. "GROUP BY file_user\n"
				. "ORDER BY $sort_sql $ord\n";
		}
		else
		{
			$sql = "SELECT file_user, Count(id) As FileCount, MAX(ValidGPS) As ValidGPS, SUM(aps) As ApCount, SUM(gps) As GpsCount,AVG(NewAPPercent) As NewAPPercent,MIN(file_date) As FirstImport,MAX(file_date) As LastImport\n"
				. "FROM files\n"
				. "WHERE completed = 1\n"
				. "GROUP BY file_user\n"
				. "ORDER BY $sort_sql $ord\n";
		}
		if($this->sql->service == "mysql"){$sql .= "LIMIT $from, $inc";}
		else if($this->sql->service == "pgsql"){$sql .= "LIMIT $inc OFFSET $from";}
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
				. "WHERE username = ? And validated = 0";
			$vres = $this->sql->conn->prepare($sql);
			$vres->bindParam(1, $userfetch['file_user']);
			$vres->execute();
			$vfetch = $vres->fetch(1);
			$regid = $vfetch ? $vfetch[0] : null;

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

		// See AllUsers(): $sort is reused for paging URLs, so quote a copy.
		$sort_sql = $this->sql->sortIdent($sort);
		$prep = array();
		$apprep = array();
		$prep['allaps'] = array();
		$prep['user'] = $user;

		if($this->sql->service == "pgsql")
			{
				$sql = 'SELECT COUNT(DISTINCT "AP_ID")'."\n"
					. "FROM wifi_hist\n"
					. 'INNER JOIN files ON files.id = wifi_hist."File_ID"'."\n"
					. "WHERE files.file_user = ?";
			}
		else
			{
				$sql = "SELECT COUNT(DISTINCT AP_ID)\n"
					. "FROM wifi_hist\n"
					. "INNER JOIN files ON files.id = wifi_hist.File_ID\n"
					. "WHERE files.file_user = ?";
			}
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$prep['total_aps'] = $rows[0];

		if($this->sql->service == "pgsql")
			{
				$sql = 'SELECT COUNT(DISTINCT "AP_ID")'."\n"
					. "FROM wifi_hist\n"
					. 'INNER JOIN files ON files.id = wifi_hist."File_ID"'."\n"
					. 'WHERE files.file_user = ? And wifi_hist."New" = 1';
			}
		else
			{
				$sql = "SELECT COUNT(DISTINCT AP_ID)\n"
					. "FROM wifi_hist\n"
					. "INNER JOIN files ON files.id = wifi_hist.File_ID\n"
					. "WHERE files.file_user = ? And wifi_hist.New = 1";
			}
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $user, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$prep['new_aps'] = $rows[0];
		$flip = 0;

		if($this->sql->service == "pgsql")
			{
				$sql = 'SELECT wap."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR", wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX", wap.fa, wap.la, wap.points,'."\n"
					. 'wap.est_lat As "Lat",'."\n"
					. 'wap.est_lon As "Lon"'."\n"
					. 'FROM wifi_ap AS wap'."\n"
					. 'LEFT JOIN files AS f ON f.id = wap."File_ID"'."\n"
					. 'WHERE f.file_user = ? And f.completed = 1'."\n"
					. "ORDER BY $sort_sql $ord\n";
			}
		else
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
					. "wap.est_lat As Lat,\n"
					. "wap.est_lon As Lon\n"
					. "FROM wifi_ap AS wap\n"
					. "LEFT JOIN files AS f ON f.id = wap.File_ID\n"
					. "WHERE f.file_user = ? And f.completed = 1\n"
					. "ORDER BY $sort_sql $ord\n";
			}
		if($this->sql->service == "mysql"){$sql .= "LIMIT $from, $inc";}
		else if($this->sql->service == "pgsql"){$sql .= "LIMIT $inc OFFSET $from";}
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
						"lon"   => htmlspecialchars($array['Lon'], ENT_QUOTES, 'UTF-8')
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

		// See AllUsers(): $sort is reused for paging URLs, so quote a copy.
		$sort_sql = $this->sql->sortIdent($sort);
		if($this->sql->service == "pgsql")
			{$sql = 'SELECT COUNT(id) AS "ApCount" FROM files WHERE file_user = ? And "ValidGPS" = 1';}
		else
			{$sql = "SELECT COUNT(id) AS ApCount FROM files WHERE file_user = ? And ValidGPS = 1";}
		$globeprep = $this->sql->conn->prepare($sql);
		$globeprep->bindParam(1, $username, PDO::PARAM_STR);
		$globeprep->execute();
		$globeprepfetch = $globeprep->fetch(2);
		if($globeprepfetch['ApCount'] !== "0"){$user_validgps = 1;}else{$user_validgps = 0;}
		
		#Total APs
		if($this->sql->service == "pgsql")
			{$sql = 'SELECT SUM(aps) As aps, SUM(gps) As gps, Min(file_date) As fa, Max(file_date) As la, AVG("NewAPPercent") As "NewAPPercent" FROM files WHERE file_user = ? GROUP BY file_user';}
		else
			{$sql = "SELECT SUM(aps) As aps, SUM(gps) As gps, Min(file_date) As fa, Max(file_date) As la, AVG(NewAPPercent) As NewAPPercent FROM files WHERE file_user = ? GROUP BY file_user";}
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$user_counts = $result->fetch(2);

		#New APs
		if($this->sql->service == "pgsql")
			{
				$sql = 'SELECT COUNT("AP_ID")'."\n"
					. "FROM wifi_ap\n"
					. 'INNER JOIN files ON files.id = wifi_ap."File_ID"'."\n"
					. "WHERE files.file_user = ?";
			}
		else
			{
				$sql = "SELECT COUNT(AP_ID)\n"
					. "FROM wifi_ap\n"
					. "INNER JOIN files ON files.id = wifi_ap.File_ID\n"
					. "WHERE files.file_user = ?";
			}
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$rows = $result->fetch(1);
		$new_aps = $rows[0];
		
		#Total Files

		$sql = "SELECT COUNT(id)\n"
			. "FROM files\n"
			. "WHERE file_user = ? And ".($this->sql->service == "pgsql" ? "file_date IS NOT NULL" : "file_date != ''")." And completed = 1";   # PostgreSQL won't compare a date with ''
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
					. "WHERE `username` = ? And `validated` = 0";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT [id]\n"
					. "FROM [user_info]\n"
					. "WHERE [username] = ? And [validated] = 0";
			}
		else if($this->sql->service == "pgsql")
			{
				$sql = "SELECT id\n"
					. "FROM user_info\n"
					. "WHERE username = ? And validated = 0";
			}
		$vres = $this->sql->conn->prepare($sql);
		$vres->bindParam(1, $username);
		$vres->execute();
		$vfetch = $vres->fetch(1);
		$regid = $vfetch ? $vfetch[0] : null;

		#Get All Imports for User
		if($this->sql->service == "pgsql")
			{$sql1 = 'SELECT id, file_orig, title, notes, file_date, aps, gps, "ValidGPS", "NewAPPercent" FROM files WHERE file_user = ? And file_date IS NOT NULL And completed = 1 ORDER BY '."$sort_sql $ord";}
		else
			{$sql1 = "SELECT id, file_orig, title, notes, file_date, aps, gps, ValidGPS, NewAPPercent FROM files WHERE file_user = ? And file_date != '' And completed = 1 ORDER BY $sort_sql $ord";}
		if($this->sql->service == "mysql"){$sql1 .= " LIMIT $from, $inc";}
		else if($this->sql->service == "pgsql"){$sql1 .= " LIMIT $inc OFFSET $from";}
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
											'file' => $imports['file_orig'],
											'title' => $imports['title'],
											'notes' => $imports['notes'],
											'aps' => $imports['aps'],
											'gps' => $imports['gps'],
											'efficiency'=>$imports['NewAPPercent'],
											'date' => $imports['file_date']
										   );
		}
		$this->user_all_imports_data = array();
		$this->user_all_imports_data['user'] = $username;
		$this->user_all_imports_data['regid'] = $regid;
		$this->user_all_imports_data['first_import_date'] = $user_counts['fa'];
		$this->user_all_imports_data['newest_date'] = $user_counts['la'];
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

	function GeneratePages($total_rows, $from, $inc, $sort, $ord, $func="", $user="", $ssid="", $mac="", $chan="", $radio="", $auth="", $encry="", $view="", $id="")
	{
		if(!is_numeric($inc) || $inc <= 0){$inc = 1;}

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
		$target = wifidb_js_string($return);
		$this->smarty->assign("redirect_func", "<script type=\"text/javascript\">
			function reload()
			{
				location.href = {$target};
			}
		</script>");
		$this->smarty->assign("redirect_html", " onload=\"setTimeout('reload()', ".(int)$delay.")\"");
	}
}
