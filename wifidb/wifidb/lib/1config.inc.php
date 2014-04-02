<?php
global $header, $ads, $tracker, $hosturl, $admin_email, $wifidb_from_pass, $wifidb_from, $wifidb_email_updates;
global $WiFiDB_LNZ_User, $apache_grp, $div, $conn, $db, $db_st, $wifidb_tools, $daemon, $root, $users_t, $user_logins_table, $files, $files_tmp, $annunc, $annunc_comm;
global $console_refresh, $console_scroll, $console_last5, $console_lines, $console_log, $DB_stats_table, $daemon_perf_table;
global $default_theme, $default_refresh, $default_dst, $default_timezone, $timeout, $bypass_check, $config_fails, $login_seed, $collate, $engine, $char_set;


$lastedit	=	'2010-04-14';


#----------General Settings------------#
$bypass_check	=	1;
$wifidb_tools	=	'/CLI';
$timezn			=	'UTC';
$root			=	'';
$hosturl		=	'http://wifidb.randomintervals.com/';
$admin_email	=	'wifidb@randomintervals.com';
$config_fails	=	3;
$login_seed		=	'3N3nt4zRaNs3ep9&-qnNxN8qK%LSmhZHW';
$wifidb_email_updates	=	1;
$wifidb_from	=	'wifidb@randomintervals.com';
$wifidb_from_pass	=	'w1f163';

#---------------- Daemon Info ----------------#
$daemon				=	1;
$debug				=	0;
$log_level			=	0;
$log_interval		=	0;
$WiFiDB_LNZ_User 	=	'www-data';
$apache_grp			=	'www-data';

#-------------Themes Settings--------------#
$default_theme	= 'wifidb';
$default_refresh 	= 15;
$default_timezone	= 0;
$default_dst		= 0;
$timeout		= 31536000; #(86400 [seconds in a day] * 365 [days in a year]) 

#-------------Console Viewer Settings--------------#
$console_refresh	= 15;
$console_scroll	= 1;
$console_last5	= 1;
$console_lines	= 10;
$console_log		= '/var/log/wifidb';

#---------------- Debug Info ----------------#
$rebuild	=	0;
$bench		=	0;

#---------------- Tables ----------------#
$settings_tb		=	'settings';
$users_t			=	'users_imports';
$links				=	'links';
$wtable				=	'wifi0';
$user_logins_table	=	'user_info';
$share_cache		=	'share_waypoints';
$files				=	'files';
$files_tmp			=	'files_tmp';
$annunc				=	'annunc';
$annunc_comm		=	'annunc_comm';
$gps_ext			=	'_GPS';
$sep				=	'-';

#---------------- DataBases ----------------#
$db		=	'wifi';
$db_st		=	'wifi_st';

#---------------- SQL Info ----------------#
$host		=	'192.168.1.28';
$db_user	=	'wifidbuser_mysql';
$db_pwd	=	'W1F1d3';
$conn		=	 mysql_pconnect($host, $db_user, $db_pwd) or die("Unable to connect to SQL server: $host");
$collate		=	'utf8_bin';
$engine		=	'innodb';
$char_set	=	'utf8';

#---------------- Export Info ----------------#
$open_loc		=	'http://vistumbler.sourceforge.net/images/program-images/open.png';
$WEP_loc		=	'http://vistumbler.sourceforge.net/images/program-images/secure-wep.png';
$WPA_loc		=	'http://vistumbler.sourceforge.net/images/program-images/secure.png';
$KML_SOURCE_URL	=	'http://www.opengis.net/kml/2.2';
$kml_out		=	'../out/kml/';
$vs1_out		=	'../out/vs1/';
$daemon_out		=	'out/daemon/';
$gpx_out		=	'../out/gpx/';

#---------------- Header and Footer Additional Info -----------------#
$ads		= ''; # <-- put the code for your ads in here www.google.com/adsense
$header	= '<meta name="description" content="A Wireless Database based off of scans from Vistumbler." /><meta name="keywords" content="WiFiDB, linux, windows, vistumbler, Wireless, database, db, php, mysql" />';
$tracker	= ''; # <-- put the code for the url tracker that you use here (ie - www.google.com/analytics )
?>