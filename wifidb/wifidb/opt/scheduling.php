<?php
#Database.inc.php, holds the database interactive functions.
#Copyright (C) 2011 Phil Ferland
#
#This program is free software; you can redistribute it and/or modify it under the terms
#of the GNU General Public License as published by the Free Software Foundation; either
#version 2 of the License, or (at your option) any later version.
#
#This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
#without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#See the GNU General Public License for more details.
#
#You should have received a copy of the GNU General Public License along with this program;
#if not, write to the
#
#   Free Software Foundation, Inc.,
#   59 Temple Place, Suite 330,
#   Boston, MA 02111-1307 USA
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");


include('../lib/init.inc.php');

$func = strtolower(filter_input(INPUT_GET, 'func', FILTER_SANITIZE_ENCODED));

$sort = filter_input(INPUT_GET, 'sort', FILTER_SANITIZE_STRING);
$ord = filter_input(INPUT_GET, 'ord', FILTER_SANITIZE_STRING);
$from = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
$inc = filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);
$sorts=array("file_date","id");
if(!in_array($sort, $sorts)){$sort = "file_date";}
$ords=array("ASC","DESC");
if(!in_array($ord, $ords)){$ord = "DESC";}
if(!is_numeric($from)){$from = 0;}
if(!is_numeric($inc)){$inc = 250;}

$TZone= (@$_COOKIE['wifidb_client_timezone'] ? @$_COOKIE['wifidb_client_timezone'] : $dbcore->default_timezone);
$dst = (@$_COOKIE['wifidb_client_dst']!='' ? @$_COOKIE['wifidb_client_dst'] : $dbcore->default_dst);
$refresh = (@$_COOKIE['wifidb_refresh']!='' ? @$_COOKIE['wifidb_refresh'] : $dbcore->default_refresh);
#echo $func;
switch($func)
{
	case 'refresh':
		$POST_refresh = filter_input(INPUT_POST, 'refresh', FILTER_SANITIZE_ENCODED);
		if( (!isset($POST_refresh)) or $POST_refresh=='' ) { $POST_refresh = $refresh; }
		setcookie( 'wifidb_refresh' , $POST_refresh , (time()+($dbcore->timeout)), "/".$dbcore->root."/opt/scheduling.php" );
		header('Location: '.$dbcore->HOSTURL.$dbcore->root.'/opt/scheduling.php');
	break;
	case 'timezone':
		$POST_timezone = filter_input(INPUT_POST, 'timezone', FILTER_SANITIZE_ENCODED);
		$POST_dst = filter_input(INPUT_POST, 'dst', FILTER_SANITIZE_ENCODED);
		if( (!isset($POST_timezone)) or $POST_timezone=='' ) { $POST_timezone = $TZone; }
		if( (!isset($POST_dst)) or $POST_dst=='' ) { $POST_dst = 0; }
		setcookie( 'wifidb_client_timezone' , $POST_timezone , (time()+($dbcore->timeout)), "/".$dbcore->root."/opt/scheduling.php" );
		setcookie( 'wifidb_client_dst' , $POST_dst , (time()+($dbcore->timeout)), "/".$dbcore->root."/opt/scheduling.php" );
		header('Location: '.$dbcore->HOSTURL.$dbcore->root.'/opt/scheduling.php');
	break;
#######################################################################
	case 'full_kml':
	$flip=0;
	$files = array();
	foreach (array_reverse(glob($dbcore->PATH."out/kmz/full/unlabeled/*.kmz")) as $file) {
		$filename = basename($file);
		$filesize = $dbcore->format_size(@filesize($file), 2);
		$fileurl = $dbcore->URL_PATH."out/kmz/full/unlabeled/".$filename;
		
		if($flip)
		{
			$class="dark";
			$flip=0;
		}else
		{
			$class="light";
			$flip=1;
		}
		
		$files[] = array(
			"class"		=> $class,
			"filename"	=> $filename,
			"filesize"	=> $filesize,
			"fileurl"	=> $fileurl,
		);
	}
	
	$dbcore->smarty->assign('wifidb_page_label', "Full KMZ Export Archive");
	$dbcore->smarty->assign('files', $files);
	$dbcore->smarty->display('scheduling_kml_list.tpl');

	break;
#######################################################################
	case 'full_labeled_kml':
	$flip=0;
	$files = array();
	foreach (array_reverse(glob($dbcore->PATH."out/kmz/full/labeled/*.kmz")) as $file) {
		$filename = basename($file);
		$filesize = $dbcore->format_size(@filesize($file), 2);
		$fileurl = $dbcore->URL_PATH."out/kmz/full/labeled/".$filename;
		
		if($flip)
		{
			$class="dark";
			$flip=0;
		}else
		{
			$class="light";
			$flip=1;
		}
		
		$files[] = array(
			"class"		=> $class,
			"filename"	=> $filename,
			"filesize"	=> $filesize,
			"fileurl"	=> $fileurl,
		);
	}
	
	$dbcore->smarty->assign('wifidb_page_label', "Full Labeled KMZ Export Archive");
	$dbcore->smarty->assign('files', $files);
	$dbcore->smarty->display('scheduling_kml_list.tpl');

	break;
#######################################################################
	case 'incremental_kml':
	$flip=0;
	$files = array();
	foreach (array_reverse(glob($dbcore->PATH."out/kmz/incremental/unlabeled/*.kmz")) as $file) {
		$filename = basename($file);
		$filesize = $dbcore->format_size(@filesize($file), 2);
		$fileurl = $dbcore->URL_PATH."out/kmz/incremental/unlabeled/".$filename;
		
		if($flip)
		{
			$class="dark";
			$flip=0;
		}else
		{
			$class="light";
			$flip=1;
		}
		
		$files[] = array(
			"class"		=> $class,
			"filename"	=> $filename,
			"filesize"	=> $filesize,
			"fileurl"	=> $fileurl,
		);
	}
	
	$dbcore->smarty->assign('wifidb_page_label', "Daily KMZ Export Archive");
	$dbcore->smarty->assign('files', $files);
	$dbcore->smarty->display('scheduling_kml_list.tpl');

	break;
#######################################################################
	case 'incremental_labeled_kml':
	$flip=0;
	$files = array();
	foreach (array_reverse(glob($dbcore->PATH."out/kmz/incremental/labeled/*.kmz")) as $file) {
		$filename = basename($file);
		$filesize = $dbcore->format_size(@filesize($file), 2);
		$fileurl = $dbcore->URL_PATH."out/kmz/incremental/labeled/".$filename;
		
		if($flip)
		{
			$class="dark";
			$flip=0;
		}else
		{
			$class="light";
			$flip=1;
		}
		
		$files[] = array(
			"class"		=> $class,
			"filename"	=> $filename,
			"filesize"	=> $filesize,
			"fileurl"	=> $fileurl,
		);
	}
	
	$dbcore->smarty->assign('wifidb_page_label', "Daily Labeled KMZ Export Archive");
	$dbcore->smarty->assign('files', $files);
	$dbcore->smarty->display('scheduling_kml_list.tpl');

	break;
#######################################################################
	case 'legacy_kml':
		$daemon_out = $dbcore->PATH."out/daemon/";
		$url_base = $dbcore->URL_PATH."out/daemon/";;
		$kml_all = array();
		$dh = opendir($daemon_out) or die("couldn't open directory");
		$files = array();
		while ($file = readdir($dh))
		{
			if($file === "."){continue;}
			if($file === ".."){continue;}
			if($file === "history"){continue;}
			if($file === "history.kml"){continue;}
			if($file === "history.kmz"){continue;}
			if($file === "boundaries.kml"){continue;}
			if($file === "full_db.kml"){continue;}
			if($file === "full_db.kmz"){continue;}
			if($file === "full_db_label.kml"){continue;}
			if($file === "full_db_label.kmz"){continue;}
			if($file === "daily_db_label.kmz"){continue;}
			if($file === "daily_db_label.kml"){continue;}
			if($file === "daily_db.kmz"){continue;}
			if($file === "daily_db.kml"){continue;}
			if($file === "newestAP_label.kml"){continue;}
			if($file === "newestAP_label.kmz"){continue;}
			if($file === "newestAP.kml"){continue;}
			if($file === "newestAP.kmz"){continue;}
			if($file === "update.kml"){continue;}
			if($file === "update.kmz"){continue;}
			#var_dump(array(
			#	"file"	 => $file,
			#	"file_url" => $url_base.$file.'/full_db.kmz',
			#	"time"	 => date ("H:i:s", filectime($kmz_file)),
			#	"size"	 => $dbcore->format_size(filesize($daemon_out.$file."/full_db.kmz"), 2)
			#));

			$files[] = $file;
		}
		rsort($files);
		$flip = 0;
		foreach ($files as $file)
		{
			if($flip)
			{
				$class="dark";
				$flip=0;
			}else
			{
				$class="light";
				$flip=1;
			}

			$daily_label = $daemon_out.$file."/daily_db_label.kmz";
			if(file_exists($daily_label))
			{
				$daily_label_url = $url_base.$file.'/daily_db_label.kmz';
				$daily_label_size = $dbcore->format_size(@filesize($daily_label), 2);
			}else
			{
				$daily_label_url = "#";
				$daily_label_size = "0.00 kB";
			}
			$daily = $daemon_out.$file."/daily_db.kmz";
			if(file_exists($daily))
			{
				$daily_url = $url_base.$file.'/daily_db.kmz';
				$daily_size = $dbcore->format_size(@filesize($daily), 2);
			}else
			{
				$daily_url = "#";
				$daily_size = "0.00 kB";
			}
			$full_label = $daemon_out.$file."/full_db_label.kmz";
			if(file_exists($full_label))
			{
				$full_label_url = $url_base.$file.'/full_db_label.kmz';
				$full_label_size = $dbcore->format_size(@filesize($full_label), 2);
			}else
			{
				$full_label_url = "#";
				$full_label_size = "0.00 kB";
			}
			$full = $daemon_out.$file."/full_db.kmz";
			if(file_exists($full))
			{
				$full_url = $url_base.$file.'/full_db.kmz';
				$full_size = $dbcore->format_size(@filesize($full), 2);
			}else
			{
				$full_url = "#";
				$full_size = "0.00 kB";
			}
			
			$link_url = $dbcore->URL_PATH.'api/export.php?func=exp_history_netlink&date='.$file;

			$kml_all[] = array(
				"class"			 => $class,
				"file"			  => $file,
				"daily_name"		=> "daily_db.kmz",
				"daily_label_name"  => "daily_db_label.kmz",
				"file_name"		 => "full_db.kmz",
				"file_label_name"   => "full_db_label.kmz",
				"daily_label_url"   => $daily_label_url,
				"daily_url"		 => $daily_url,
				"file_label_url"	=> $full_label_url,
				"file_url"		  => $full_url,
				"full_size"		 => $full_size,
				"full_size_label"   => $full_label_size,
				"daily_size"		=> $daily_size,
				"daily_size_label"  => $daily_label_size,
				"link_url"  => $link_url
			);
		}
		$dbcore->smarty->assign('wifidb_page_label', "Legacy KMZ Exports");
		$dbcore->smarty->assign('wifidb_kml_all_array', $kml_all);
		$dbcore->smarty->display('scheduling_kml_lecacy.tpl');
	break;
#######################################################################
	case 'daemon_kml':
		$kml_head = array();
		$kml_head['update_kml'] = '<a class="links" href="'.$dbcore->URL_PATH.'api/export.php?func=exp_combined_netlink">WifiDB Network Link Download</a>';
		$kmldate=date ("Y-m-d");
		#-----------
		if($dbcore->sql->service == "mysql")
			{
				$sql = "SELECT wifi_ap.ModDate\n"
					. "FROM wifi_ap\n"
					. "WHERE wifi_ap.HighGps_ID IS NOT NULL\n"
					. "ORDER BY wifi_ap.AP_ID DESC\n"
					. "LIMIT 1";
			}
		else if($dbcore->sql->service == "sqlsrv")
			{
				$sql = "SELECT TOP 1 [wifi_ap].[ModDate]\n"
					. "FROM [wifi_ap]\n"
					. "WHERE [wifi_ap].[HighGps_ID] IS NOT NULL\n"
					. "ORDER BY [wifi_ap].[AP_ID] DESC";
			}
		$result = $dbcore->sql->conn->query($sql);
		$ap_array = $result->fetch(2);

		if($ap_array['ModDate'])
		{
			if(strpos($ap_array['ModDate'], ".")){$lastapdate = substr($ap_array['ModDate'], 0, strpos($ap_array['ModDate'], "."));}else{$lastapdate = $ap_array['ModDate'];}

			$kml_head['newest_date'] = $lastapdate;
			$kml_head['newest_link'] = $dbcore->URL_PATH."api/export.php?func=exp_latest_netlink&labeled=0";
			$kml_head['newest_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['newest_link'])));

			$kml_head['newest_labeled_date'] = $lastapdate;
			$kml_head['newest_labeled_link'] = $dbcore->URL_PATH."api/export.php?func=exp_latest_netlink&labeled=1";
			$kml_head['newest_labeled_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['newest_labeled_link'])));
			
			$kml_head['daily_date'] = $lastapdate;
			$kml_head['daily_link'] = $dbcore->URL_PATH."api/export.php?func=exp_daily_netlink&labeled=0";
			$kml_head['daily_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['daily_link'])));

			$kml_head['daily_labeled_date'] = $lastapdate;
			$kml_head['daily_labeled_link'] = $dbcore->URL_PATH."api/export.php?func=exp_daily_netlink&labeled=1";
			$kml_head['daily_labeled_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['daily_labeled_link'])));
		}
		else
		{
			$kml_head['newest_date'] = "None APs yet.";
			$kml_head['newest_link'] = "#";
			$kml_head['newest_size'] = "0.00 kB";

			$kml_head['newest_labeled_date'] = "None APs yet.";
			$kml_head['newest_labeled_link'] = "#";
			$kml_head['newest_labeled_size'] = "0.00 kB";
			
			$kml_head['daily_date'] = "None APs yet.";
			$kml_head['daily_link'] = "#";
			$kml_head['daily_size'] = "0.00 kB";

			$kml_head['daily_labeled_date'] = "None APs yet.";
			$kml_head['daily_labeled_link'] = "#";
			$kml_head['daily_labeled_size'] = "0.00 kB";
		}
		#-----------
		if(file_exists($dbcore->PATH."out/daemon/daily_db.kmz"))
		{
			$kml_head['incremental_link'] = $dbcore->URL_PATH."out/daemon/daily_db.kmz";
			$kml_head['incremental_date'] = date ("Y-m-d H:i:s", filemtime ($dbcore->PATH."out/daemon/daily_db.kmz"));
			$kml_head['incremental_size'] = $dbcore->format_size(filesize($dbcore->PATH."out/daemon/daily_db.kmz"));
		}
		else
		{
			$kml_head['incremental_date'] = "None generated for ".$kmldate." yet.";
			$kml_head['incremental_link'] = "#";
			$kml_head['incremental_size'] = "0.00 kB";
		}

		if(file_exists($dbcore->PATH."out/daemon/daily_db_labeled.kmz"))
		{
			$kml_head['incremental_labeled_link'] = $dbcore->URL_PATH."out/daemon/daily_db_labeled.kmz";
			$kml_head['incremental_labeled_date'] = date ("Y-m-d H:i:s", filemtime ($dbcore->PATH."out/daemon/daily_db_labeled.kmz"));
			$kml_head['incremental_labeled_size'] = $dbcore->format_size(filesize($dbcore->PATH."out/daemon/daily_db_labeled.kmz"));
		}
		else
		{
			$kml_head['incremental_labeled_date'] = "None generated for ".$kmldate." yet.";
			$kml_head['incremental_labeled_link'] = "#";
			$kml_head['incremental_labeled_size'] = "0.00 kB";
		}
		#-----------
		if(file_exists($dbcore->PATH."out/daemon/full_db.kmz"))
		{
			$kml_head['full_link'] = $dbcore->URL_PATH."out/daemon/full_db.kmz";
			$kml_head['full_date'] = date ("Y-m-d H:i:s", filemtime ($dbcore->PATH."out/daemon/full_db.kmz"));
			$kml_head['full_size'] = $dbcore->format_size(filesize($dbcore->PATH."out/daemon/full_db.kmz"));
		}
		else
		{
			$kml_head['full_date'] = "None generated for ".$kmldate." yet.";
			$kml_head['full_link'] = "#";
			$kml_head['full_size'] = "0.00 kB";
		}

		if(file_exists($dbcore->PATH."out/daemon/full_db_labeled.kmz"))
		{
			$kml_head['full_labeled_link'] = $dbcore->URL_PATH."out/daemon/full_db_labeled.kmz";
			$kml_head['full_labeled_date'] = date ("Y-m-d H:i:s", filemtime ($dbcore->PATH."out/daemon/full_db_labeled.kmz"));
			$kml_head['full_labeled_size'] = $dbcore->format_size(filesize($dbcore->PATH."out/daemon/full_db_labeled.kmz"));
		}
		else
		{
			$kml_head['full_labeled_date'] = "None generated for ".$kmldate." yet.";
			$kml_head['full_labeled_link'] = "#";
			$kml_head['full_labeled_size'] = "0.00 kB";
		}
		#-----------

		$dbcore->smarty->assign('wifidb_page_label', "Daemon KMZ Exports");
		$dbcore->smarty->assign('wifidb_kml_head', $kml_head);
		$dbcore->smarty->display('scheduling_kml.tpl');
	break;

	case 'websockets':
		$wspath = $dbcore->WebSocketURL;
		$dbcore->smarty->assign('WebSocketScripts', '<script src="'.$dbcore->HOSTURL.$dbcore->root.'/lib/jquery-1.11.3.js"></script>
		<script type="text/javascript">
			var host = "'.$dbcore->WebSocketURL.'Scheduling";
		</script>
		<script type="text/javascript" src="/wifidb/lib/WebSockClient.js"></script>');
		$dbcore->smarty->assign('OnLoad', "onload='init()'");

		$timezone_opt = '';
		$offsets = array(-12, -11, -10, -9, -8, -7, -6, -5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14);
		foreach($offsets as $key=>$value)
		{

			if(((int)$TZone === $value))
			{
				$select = "selected ";
			}else
			{
				$select = "";
			}
			$timezone_opt .= '<OPTION '.$select.' VALUE="'.$value.'"> '.$value.'</option>
			';
		}

		if($dst == 1)
		{
			$dst_opt = "checked";
		}else
		{
			$dst_opt = "";
		}

		$refresh_opt = "";
		$val = 1;
		$max = 300;
		while($val < $max)
		{
			if($refresh == $val)
			{
				$select = "selected ";
			}else
			{
				$select = "";
			}
			if($val > 60)
			{
				$time_inc_name = "Minutes";
				$d=60;
			}
			else
			{
				$time_inc_name = "Seconds";
				$d=1;
			}
			$refresh_opt .= '<OPTION '.$select.' VALUE="'.$val.'"> '.($val/$d).' '.$time_inc_name.'</option>
			';
			$val = $val*2;
		}
		$dbcore->smarty->assign('wifidb_page_label', 'Scheduling Page (Waiting Imports and Daemon Status)');
		$dbcore->smarty->assign('wifidb_refresh_options', $refresh_opt);
		$dbcore->smarty->assign('wifidb_timezone_options', $timezone_opt);
		$dbcore->smarty->assign('wifidb_dst_options', $dst_opt);
		$dbcore->smarty->assign('wifidb_schedules', "");
		$dbcore->smarty->assign('wifidb_daemons', "");
		$dbcore->smarty->assign('wifidb_importing', "");
		$dbcore->smarty->assign('wifidb_waiting', "");
		$dbcore->smarty->display('scheduling_waiting_websockets.tpl');
	break;
	
	case 'schedule':
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT * FROM settings WHERE id = '1'";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT * FROM [settings] WHERE [id] = '1'";}
		$result = $dbcore->sql->conn->query($sql);
		$file_array = $result->fetch(2);
		$timezone_opt = '';
		$offsets = array(-12, -11, -10, -9, -8, -7, -6, -5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14);
		foreach($offsets as $key=>$value)
		{
			if($TZone == $value)
			{
				$select = "selected ";
			}else
			{
				$select = "";
			}

			$timezone_opt .= '<OPTION '.$select.' VALUE="'.$value.'"> '.$value.'</option>
			';
		}

		if($dst == 1)
		{
			$dst_opt = "checked";
		}else
		{
			$dst_opt = "";
		}
		
		$timezonediff = $TZone+$dst;
		$alter_by = (($timezonediff*60)*60);

		$curtime = time();
		$altered = $curtime+$alter_by;
		$curtime_local = date("Y-m-d h:i:s A", $altered);
		$curtime_utc = date('Y-m-d h:i:s A', $curtime);
		
		$refresh_opt = "";
		$val = 15;
		$max = 30720;
		while($val < $max)
		{
			if($refresh == $val)
			{
				$select = "selected ";
			}else
			{
				$select = "";
			}
			if($val > 60)
			{
				$time_inc_name = "Minutes";
				$d=60;
			}
			else
			{
				$time_inc_name = "Seconds";
				$d=1;
			}
			$refresh_opt .= '<OPTION '.$select.' VALUE="'.$val.'"> '.($val/$d).' '.$time_inc_name.'</option>
			';
			$val = $val*2;
		}

		$schedule_row = array();
		$n=0;
		$sql = "SELECT schedule.id, schedule.nodename, schedule.daemon, schedule.enabled, schedule.interval, schedule.status, schedule.nextrun, schedule.logfile, schedule.pidfile, schedule.pid AS schedpid,\n"
			. "daemon_pid_stats.pid, daemon_pid_stats.pidtime, daemon_pid_stats.pidcpu, daemon_pid_stats.pidmem, daemon_pid_stats.pidcmd, daemon_pid_stats.date AS pidud\n"
			. "FROM schedule\n"
			. "LEFT OUTER JOIN daemon_pid_stats ON daemon_pid_stats.pidfile = schedule.pidfile\n"
			. "ORDER BY schedule.id ASC";
		$result_1 = $dbcore->sql->conn->query($sql);
		while ($newArray = $result_1->fetch(2))
		{
			$nextrun = strtotime($newArray['nextrun']);
			$altered = $nextrun+$alter_by;
			$nextrun_local = date("Y-m-d h:i:s A", $altered);
			$nextrun_utc = date('Y-m-d h:i:s A', $nextrun);
			$curtime = date();
			$lastupdatetime = strtotime($newArray['pidud']);
			$interval = (int)$newArray['interval'];
			$status = $newArray['status'];
			$enabled = $newArray['enabled'];
			$schedpid = '';
			

			if($status=="Running")
			{
				$nextrun = strtotime("+$interval minutes");
				$altered = $nextrun+$alter_by;
				$nextrun_local = date("Y-m-d h:i:s A", $altered);
				$nextrun_utc = date('Y-m-d h:i:s A', $nextrun);
				$schedpid = $newArray['schedpid'];
				$pid = $newArray['pid'];
				$logfile = $newArray['logfile'];
				if($schedpid == $pid)
				{
					if(($curtime-$lastupdatetime) < 60) 
						{$color = 'lime';}
					else
						{$color = 'orange';}
				}
				else
				{
					$color = 'orange';
				}
				if($logfile){$schedpid .= " (".$logfile.")";}
			}
			else if($status=="Error" or $enabled==0)
			{
				$color = 'red';
			}			
			else if($curtime < $nextrun_utc)
			{
				$color = 'lightgreen';
			}
			else
			{
				$color = 'yellow';
			}

			$schedule_row[$n]['color'] = $color;
			$schedule_row[$n]['id'] = $newArray['id'];
			$schedule_row[$n]['nodename'] = $newArray['nodename'];
			$schedule_row[$n]['daemon'] = $newArray['daemon'];
			$schedule_row[$n]['enabled'] = $newArray['enabled'];
			$schedule_row[$n]['interval'] = $newArray['interval'];
			$schedule_row[$n]['status'] = $newArray['status'];
			$schedule_row[$n]['nextrun_utc'] = $nextrun_utc;
			$schedule_row[$n]['nextrun_local'] = $nextrun_local;
			$schedule_row[$n]['schedpid'] = $schedpid;

			$n++;
		}

		$pid_row = array();
		$n=0;
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT * FROM daemon_pid_stats ORDER BY nodename ASC";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT * FROM [daemon_pid_stats] ORDER BY [nodename] ASC";}
		$result_1 = $dbcore->sql->conn->query($sql);
		while ($newArray = $result_1->fetch(2))
		{

			$lastupdatetime = strtotime($newArray['date']);
			$altered = $lastupdatetime+$alter_by;
			$lastupdatetime_local = date("Y-m-d h:i:s A", $altered);
			$lastupdatetime_utc = date('Y-m-d h:i:s A', $lastupdatetime);
			$curtime = time();

			if($newArray['pid'] == 0)
			{
				$color = 'red';
			}else
			{
				if(($curtime-$lastupdatetime) < 60) {
					$color = 'lime';
				}else
				{
					$color = 'yellow';
				}
			}

			$pid_row[$n]['color'] = $color;
			$pid_row[$n]['nodename'] = $newArray['nodename'];
			$pid_row[$n]['pidfile'] = $newArray['pidfile'];
			$pid_row[$n]['pid'] = $newArray['pid'];
			$pid_row[$n]['pidtime'] = $newArray['pidtime'];
			$pid_row[$n]['pidcpu'] = $newArray['pidcpu'];
			$pid_row[$n]['pidmem'] = $newArray['pidmem'];
			$pid_row[$n]['pidcmd'] = $newArray['pidcmd'];
			$pid_row[$n]['lastupdatetime_utc'] = $lastupdatetime_utc;
			$pid_row[$n]['lastupdatetime_local'] = $lastupdatetime_local;

			$n++;
		}
		
		#Get Complete Count
		$sql = "SELECT Count(id) AS imp_count FROM files WHERE completed = 1";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$complete_count = $prepf[0];
		
		#Get Importing Count
		$sql = "SELECT Count(id) AS imp_count FROM files_importing";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$importing_count = $prepf[0];
		
		#Get Waiting Count
		$sql = "SELECT Count(id) AS imp_count FROM files_tmp";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$waiting_count = $prepf[0];

		$dbcore->smarty->assign('wifidb_page_label', 'Scheduling Page (Waiting Imports and Daemon Status)');
		$dbcore->smarty->assign('wifidb_refresh_options', $refresh_opt);
		$dbcore->smarty->assign('wifidb_timezone_options', $timezone_opt);
		$dbcore->smarty->assign('wifidb_dst_options', $dst_opt);
		$dbcore->smarty->assign('wifidb_schedules', $schedule_row);
		$dbcore->smarty->assign('wifidb_daemons', $pid_row);
		$dbcore->smarty->assign('curtime_local', $curtime_local);
		$dbcore->smarty->assign('curtime_utc', $curtime_utc);
		$dbcore->smarty->assign('complete_count', $complete_count);
		$dbcore->smarty->assign('importing_count', $importing_count);
		$dbcore->smarty->assign('waiting_count', $waiting_count);
		$dbcore->smarty->display('scheduling_schedule.tpl');
	break;
	
	case 'done':
	
	
		$sql = "SELECT id, file_orig, file_user, notes, title, file_date, aps, gps, ValidGPS, size, NewAPPercent, hash \n"
			. "FROM files\n"
			. "WHERE completed = 1\n";
		if($dbcore->sql->service == "mysql"){$sql .= "ORDER BY {$sort} {$ord} LIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "ORDER BY {$sort} {$ord} OFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}
		$result = $dbcore->sql->conn->query($sql);
		$class_f = 0;
		$files_all = array();
		while ($newArray = $result->fetch(2))
		{
			if($class_f){$class = "light"; $class_f = 0;}else{$class = "dark"; $class_f = 1;}
			$files_all[] = array(
									'class'=>$class,
									'id'=>$newArray['id'],
									'file'=>$newArray['file_orig'],
									'date'=>$newArray['file_date'],
									'user'=>$newArray["file_user"],
									'notes'=>$newArray['notes'],
									'title'=>$newArray['title'],
									'efficiency'=>$newArray['NewAPPercent'],
									'aps'=>$newArray['aps'],
									'gps'=>$newArray['gps'],
									'size'=>$newArray['size'],
									'hash'=>$newArray['hash'],
									'validgps'=>$newArray['ValidGPS']
								);
		}
		
		#Get Complete Count
		$sql = "SELECT Count(id) AS imp_count FROM files WHERE completed = 1";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$complete_count = $prepf[0];
		
		#Get Importing Count
		$sql = "SELECT Count(id) AS imp_count FROM files_importing";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$importing_count = $prepf[0];
		
		#Get Waiting Count
		$sql = "SELECT Count(id) AS imp_count FROM files_tmp";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$waiting_count = $prepf[0];
		
		$dbcore->GeneratePages($complete_count, $from, $inc, $sort, $ord, 'done&');
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_page_label', "Files Imported Page");
		$dbcore->smarty->assign("wifidb_done_all_array", $files_all);
		$dbcore->smarty->assign('complete_count', $complete_count);
		$dbcore->smarty->assign('importing_count', $importing_count);
		$dbcore->smarty->assign('waiting_count', $waiting_count);
		$dbcore->smarty->display('scheduling_done.tpl');
	break;
	
	case 'waiting':
		$waiting_row = array();
		$n=0;
		$sql = "SELECT id, file_orig, title, notes, file_date, size, hash, file_user FROM files_tmp\n";
		if($dbcore->sql->service == "mysql"){$sql .= "ORDER BY {$sort} {$ord} LIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= "ORDER BY {$sort} {$ord} OFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}
		$result_1 = $dbcore->sql->conn->query($sql);
		while ($newArray = $result_1->fetch(2))
		{
			$color = 'yellow';
			$waiting_row[$n]['color'] = $color;
			$waiting_row[$n]['id'] = $newArray['id'];
			$waiting_row[$n]['file'] = $newArray['file_orig'];
			$waiting_row[$n]['title'] = $newArray['title'];
			$waiting_row[$n]['notes'] = $newArray['notes'];
			$waiting_row[$n]['date'] = $newArray['file_date'];
			$waiting_row[$n]['size'] = $newArray['size'];
			$waiting_row[$n]['hash'] = $newArray['hash'];
			$waiting_row[$n]['user'] = $newArray['file_user'];
			$waiting_row[$n]['status'] = "Waiting for Import";
			$n++;
		}

		#Get Complete Count
		$sql = "SELECT Count(id) AS imp_count FROM files WHERE completed = 1";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$complete_count = $prepf[0];
		
		#Get Importing Count
		$sql = "SELECT Count(id) AS imp_count FROM files_importing";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$importing_count = $prepf[0];
		
		#Get Waiting Count
		$sql = "SELECT Count(id) AS imp_count FROM files_tmp";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$waiting_count = $prepf[0];

		$dbcore->GeneratePages($waiting_count, $from, $inc, $sort, $ord, 'waiting&');
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_page_label', 'Scheduling Page (Files waiting for import)');
		$dbcore->smarty->assign('wifidb_waiting', $waiting_row);
		$dbcore->smarty->assign('complete_count', $complete_count);
		$dbcore->smarty->assign('importing_count', $importing_count);
		$dbcore->smarty->assign('waiting_count', $waiting_count);
		$dbcore->smarty->display('scheduling_waiting.tpl');
	break;
	
	default:
		$importing_row = array();
		$n=0;

		$sql = "SELECT id, file_orig, title, notes, file_date, size, hash, file_user FROM files_importing ORDER BY {$sort} {$ord}";
		if($dbcore->sql->service == "mysql"){$sql .= " LIMIT {$from},{$inc}";}
		else if($dbcore->sql->service == "sqlsrv"){$sql .= " OFFSET {$from} ROWS FETCH NEXT {$inc} ROWS ONLY";}
		
		$result_1 = $dbcore->sql->conn->query($sql);
		while ($newArray = $result_1->fetch(2))
		{
			if($newArray['importing'] == "1" ){$color = "lime";}else{$color = "yellow";}
			if($newArray['ap'] == "" && $newArray['tot'] == "")
			{
				$ap_text = "";
				$status_text = "Processing";
			}
			else if($newArray['tot'] == "Preparing for Import")
			{
				$ap_text = "";
				$status_text = $newArray['tot'];
			}
			else
			{
				$ap_text = $dbcore->formatSSID($newArray['ap']);
				$status_text = $newArray['tot'];
			}
			
			$importing_row[$n]['color'] = $color;
			$importing_row[$n]['id'] = $newArray['id'];
			$importing_row[$n]['file'] = $newArray['file_orig'];
			$importing_row[$n]['title'] = $newArray['title'];
			$importing_row[$n]['notes'] = $newArray['notes'];
			$importing_row[$n]['date'] = $newArray['file_date'];
			$importing_row[$n]['size'] = $newArray['size'];
			$importing_row[$n]['hash'] = $newArray['hash'];
			$importing_row[$n]['user'] = $newArray['file_user'];
			$importing_row[$n]['ap'] = $ap_text;
			$importing_row[$n]['status'] = $status_text;

			$n++;
		}
		
		#Get Complete Count
		$sql = "SELECT Count(id) AS imp_count FROM files WHERE completed = 1";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$complete_count = $prepf[0];
		
		#Get Importing Count
		$sql = "SELECT Count(id) AS imp_count FROM files_importing";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$importing_count = $prepf[0];
		
		#Get Waiting Count
		$sql = "SELECT Count(id) AS imp_count FROM files_tmp";
		$prep = $dbcore->sql->conn->query($sql);
		$prepf = $prep->fetch(1);
		$waiting_count = $prepf[0];
		
		$dbcore->GeneratePages($importing_count	, $from, $inc, $sort, $ord);
		$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
		$dbcore->smarty->assign('wifidb_page_label', 'Scheduling Page (Files being imported)');
		$dbcore->smarty->assign('wifidb_importing', $importing_row);
		$dbcore->smarty->assign('complete_count', $complete_count);
		$dbcore->smarty->assign('importing_count', $importing_count);
		$dbcore->smarty->assign('waiting_count', $waiting_count);
		$dbcore->smarty->display('scheduling_importing.tpl');
	break;
}
