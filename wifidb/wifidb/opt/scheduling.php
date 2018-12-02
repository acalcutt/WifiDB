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
    case 'done':
        #$sql = "SELECT `files`.`id`, `user_imports`.`id` as `UserImportID`, `user_imports`.`username`, `files`.`file`, `user_imports`.`NewAPPercent`, `files`.`title`, `files`.`date`, `files`.`size`, `files`.`aps`, `files`.`gps`, `files`.`hash` FROM `files` INNER JOIN user_imports WHERE `files`.`completed` = 1 AND `files`.`id` = `user_imports`.`file_id` ORDER BY `files`.`id` DESC";
        #echo $sql;
		$sql = "SELECT `id`, `file`, `user`, `notes`, `title`, `date`, `aps`, `gps`, `ValidGPS`, `size`, `NewAPPercent`, `hash` \n"
			. "FROM `files` \n"
			. "WHERE `completed` = 1 ORDER BY `date` DESC";
        $result = $dbcore->sql->conn->query($sql);
        $class_f = 0;
        $files_all = array();
        while ($newArray = $result->fetch(2))
        {
			if($newArray['ValidGPS'] == 1)
			{
				$globe_html = "<a href=\"".$dbcore->URL_PATH."opt/map.php?func=user_list&labeled=0&id=".$newArray['id']."\" title=\"Show on Map\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_on.png\"></a>";				
				$globe_html .= "<a href=\"".$dbcore->URL_PATH."api/geojson.php?json=1&func=exp_list&id=".$newArray['id']."\" title=\"Export to JSON\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/json_on.png\"></a>";							
				$globe_html .= "<a href=\"".$dbcore->URL_PATH."api/export.php?func=exp_list&id=".$newArray['id']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/kmz_on.png\"></a>";		
			}
			else
			{
				$globe_html = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_off.png\">";
				$globe_html .= "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/json_off.png\">";	
				$globe_html .= "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/kmz_off.png\">";
			}

            if($class_f){$class = "light"; $class_f = 0;}else{$class = "dark"; $class_f = 1;}
            $files_all[] = array(
                                    'class'=>$class,
                                    'globe_html'=>$globe_html,
                                    'id'=>$newArray['id'],
                                    'file'=>html_entity_decode($newArray['file']),
                                    'date'=>$newArray['date'],
                                    'user'=>$newArray["user"],
                                    'notes'=>$newArray['notes'],
                                    'title'=>$newArray['title'],
                                    'efficiency'=>$newArray['NewAPPercent'],
                                    'aps'=>$newArray['aps'],
                                    'gps'=>$newArray['gps'],
                                    'size'=>$newArray['size'],
                                    'hash'=>$newArray['hash']
                                );
        }
        $dbcore->smarty->assign('wifidb_page_label', "Files Imported Page");
        $dbcore->smarty->assign("wifidb_done_all_array", $files_all);
        $dbcore->smarty->display('scheduling_done.tpl');
    break;
#######################################################################
    case 'daemon_kml':
        $kml_head = array();
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
            #    "file"     => $file,
            #    "file_url" => $url_base.$file.'/full_db.kmz',
            #    "time"     => date ("H:i:s", filectime($kmz_file)),
            #    "size"     => $dbcore->format_size(filesize($daemon_out.$file."/full_db.kmz"), 2)
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
                "class"             => $class,
                "file"              => $file,
                "daily_name"        => "daily_db.kmz",
                "daily_label_name"  => "daily_db_label.kmz",
                "file_name"         => "full_db.kmz",
                "file_label_name"   => "full_db_label.kmz",
                "daily_label_url"   => $daily_label_url,
                "daily_url"         => $daily_url,
                "file_label_url"    => $full_label_url,
                "file_url"          => $full_url,
                "full_size"         => $full_size,
                "full_size_label"   => $full_label_size,
                "daily_size"        => $daily_size,
                "daily_size_label"  => $daily_label_size,
				"link_url"  => $link_url
            );
        }

        $kml_head['update_kml'] = 'Current WiFiDB Network Link: <a class="links" href="'.$dbcore->URL_PATH.'api/export.php?func=exp_combined_netlink">Download!</a>';
        $kmldate=date ("Y-m-d");
		#-----------
		$sql = "SELECT `wifi_gps`.`GPS_Date`\n"
			. "FROM `wifi_ap`\n"
			. "LEFT JOIN `wifi_gps` ON `wifi_ap`.`HighGps_ID` = `wifi_gps`.`GPS_ID`\n"
			. "WHERE `wifi_ap`.`HighGps_ID` IS NOT NULL and `wifi_gps`.`Lat` != '0.0000'\n"
			. "ORDER BY `wifi_gps`.`GPS_Date` DESC\n"
			. "LIMIT 1";
        $result = $dbcore->sql->conn->query($sql);
        $ap_array = $result->fetch(2);

        if($ap_array['GPS_Date'])
        {
            if(strpos($ap_array['GPS_Date'], ".")){$lastapdate = substr($ap_array['GPS_Date'], 0, strpos($ap_array['GPS_Date'], "."));}else{$lastapdate = $ap_array['GPS_Date'];}

            $kml_head['newest_date'] = $lastapdate;
            $kml_head['newest_link'] = $dbcore->URL_PATH."api/export.php?func=exp_latest_netlink&labeled=0";
            $kml_head['newest_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['newest_link'])));

            $kml_head['newest_labeled_date'] = $lastapdate;
            $kml_head['newest_labeled_link'] = $dbcore->URL_PATH."api/export.php?func=exp_latest_netlink&labeled=1";
            $kml_head['newest_labeled_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['newest_labeled_link'])));
        }
        else
        {
            $kml_head['newest_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['newest_link'] = "#";
            $kml_head['newest_size'] = "0.00 kB";

            $kml_head['newest_labeled_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['newest_labeled_link'] = "#";
            $kml_head['newest_labeled_size'] = "0.00 kB";
        }
		#-----------
		$date_search = $kmldate."%";
        $sql = "SELECT `id`, `date` FROM `files` ORDER BY `date` DESC LIMIT 1";
        $result = $dbcore->sql->conn->query($sql);
        $ap_array = $result->fetch(2);

        if($ap_array['id'])
        {
            if(strpos($ap_array['date'], ".")){$lastapdate = substr($ap_array['date'], 0, strpos($ap_array['date'], "."));}else{$lastapdate = $ap_array['date'];}

            
            $kml_head['daily_date'] = $lastapdate;
            $kml_head['daily_link'] = $dbcore->URL_PATH."api/export.php?func=exp_daily_netlink&labeled=0";
            $kml_head['daily_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['daily_link'])));

            $kml_head['daily_labeled_date'] = $lastapdate;
            $kml_head['daily_labeled_link'] = $dbcore->URL_PATH."api/export.php?func=exp_daily_netlink&labeled=1";
            $kml_head['daily_labeled_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['daily_labeled_link'])));
        }
        else
        {
            $kml_head['daily_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['daily_link'] = "#";
            $kml_head['daily_size'] = "0.00 kB";

            $kml_head['daily_labeled_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['daily_labeled_link'] = "#";
            $kml_head['daily_labeled_size'] = "0.00 kB";
        }
		#-----------
        $sql = "SELECT `id`, `date` FROM `files` ORDER BY `date` DESC LIMIT 1";
        $result = $dbcore->sql->conn->query($sql);
        $ap_array = $result->fetch(2);

        if($ap_array['id'])
        {
            if(strpos($ap_array['date'], ".")){$lastapdate = substr($ap_array['date'], 0, strpos($ap_array['date'], "."));}else{$lastapdate = $ap_array['date'];}

            
            $kml_head['full_date'] = $lastapdate;
            $kml_head['full_link'] = $dbcore->URL_PATH."api/export.php?func=exp_all_netlink&labeled=0";
            $kml_head['full_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['full_link'])));

            $kml_head['full_labeled_date'] = $lastapdate;
            $kml_head['full_labeled_link'] = $dbcore->URL_PATH."api/export.php?func=exp_all_netlink&labeled=1";
            $kml_head['full_labeled_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['full_labeled_link'])));
        }
        else
        {
            $kml_head['full_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['full_link'] = "#";
            $kml_head['full_size'] = "0.00 kB";

            $kml_head['full_labeled_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['full_labeled_link'] = "#";
            $kml_head['full_labeled_size'] = "0.00 kB";
        }
		#-----------

        $dbcore->smarty->assign('wifidb_page_label', "Daemon KML Exports");
        $dbcore->smarty->assign('wifidb_kml_head', $kml_head);
        $dbcore->smarty->assign('wifidb_kml_all_array', $kml_all);
        $dbcore->smarty->display('scheduling_kml.tpl');
    break;

    default:
        #include $dbcore->TOOLS_PATH."/daemon/config.inc.php";
        $sql = "SELECT * FROM `settings` WHERE `id` = '1'";
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
        $importing_row = array();
        $n=0;
        $sql = "SELECT * FROM `files_importing` ORDER BY `date` ASC";
        $result_1 = $dbcore->sql->conn->query($sql);
        while ($newArray = $result_1->fetch(2))
        {
            if($newArray['importing'] == '1' )
            {
                $color = 'lime';
            }else
            {
                $color = 'yellow';
            }
            $importing_row[$n]['color'] = $color;
            $importing_row[$n]['id'] = $newArray['id'];
            $importing_row[$n]['file'] = $newArray['file'];
            $importing_row[$n]['title'] = $newArray['title'];
            $importing_row[$n]['date'] = $newArray['date'];
            $importing_row[$n]['size'] = $newArray['size'];
            $importing_row[$n]['hash'] = $newArray['hash'];
            $importing_row[$n]['user'] = $newArray['user'];

            $tot = "";
            $ssid = "";
            switch($newArray['ap'])
            {
                case "":
                    $ssid = "<td colspan='2' align='center'>Processing...</td>";
                    break;
                case "Preparing for Import":
                    $ssid = "<td colspan='2' align='center'>Preparing for Import...</td>";
                    break;
                case "File is already in table array (":
                    $ssid = "<td colspan='2' align='center'>File is already in table...</td>";
                    break;
                case "@#@#_CONVERTING TO VS1_@#@#":
                    $ssid = "<td colspan='2' align='center'>Converting file to VS1 Format...</td>";
                    break;
                default:
                    $ssid = '<td align="center">'.$newArray['ap'].'</td>';
                    if($newArray['tot'] == NULL){$tot = "";}else{$tot = '<td align="center">'.$newArray['tot'].'</td>';}
                    break;
            }
            $importing_row[$n]['last_cell'] = $ssid.$tot;
            $n++;
        }
		
        $waiting_row = array();
        $n=0;
        $sql = "SELECT * FROM `files_tmp` ORDER BY `date` ASC";
        $result_1 = $dbcore->sql->conn->query($sql);
        while ($newArray = $result_1->fetch(2))
        {
            $color = 'yellow';
            $waiting_row[$n]['color'] = $color;
            $waiting_row[$n]['id'] = $newArray['id'];
            $waiting_row[$n]['file'] = $newArray['file'];
            $waiting_row[$n]['title'] = $newArray['title'];
            $waiting_row[$n]['date'] = $newArray['date'];
            $waiting_row[$n]['size'] = $newArray['size'];
            $waiting_row[$n]['hash'] = $newArray['hash'];
            $waiting_row[$n]['user'] = $newArray['user'];

            $tot = "";
            $ssid = "<td colspan='2' align='center'>Not being imported</td>";
            $waiting_row[$n]['last_cell'] = $ssid.$tot;
            $n++;
        }

        $schedule_row = array();
        $n=0;
        $sql = "SELECT * FROM `schedule` ORDER BY `nodename` ASC";
        $result_1 = $dbcore->sql->conn->query($sql);
        while ($newArray = $result_1->fetch(2))
        {

            $nextrun_utc = strtotime($newArray['nextrun']);
            $curtime = time();
            $min_diff = round(($nextrun_utc - $curtime) / 60);
            $interval = (int)$newArray['interval'];
            $status = $newArray['status'];
            $enabled = $newArray['enabled'];

            if($enabled==0 or $status=="Error")
            {
                $color = 'red';
            }
            else
            {
                if(($min_diff <= $interval and $min_diff >= 0) or $status=="Running")
                {
                    $color = 'lime';
                }
                else
                {
                    $color = 'yellow';
                }
            }

        #convert to local time
        $timezonediff = $TZone+$dst;
        $alter_by = (($timezonediff*60)*60);
        $altered = $nextrun_utc+$alter_by;
        $nextrun_local = date("Y-m-d H:i:s", $altered);

            $schedule_row[$n]['color'] = $color;
            $schedule_row[$n]['id'] = $newArray['id'];
            $schedule_row[$n]['nodename'] = $newArray['nodename'];
            $schedule_row[$n]['daemon'] = $newArray['daemon'];
            $schedule_row[$n]['enabled'] = $newArray['enabled'];
            $schedule_row[$n]['interval'] = $newArray['interval'];
            $schedule_row[$n]['status'] = $newArray['status'];
            $schedule_row[$n]['nextrun_utc'] = $newArray['nextrun'];
            $schedule_row[$n]['nextrun_local'] = $nextrun_local;

            $n++;
        }

        $pid_row = array();
        $n=0;
        $sql = "SELECT * FROM `daemon_pid_stats` ORDER BY `nodename` ASC";
        $result_1 = $dbcore->sql->conn->query($sql);
        while ($newArray = $result_1->fetch(2))
        {

            $lastupdatetime = strtotime($newArray['date']);
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
            $pid_row[$n]['pidmem'] = $newArray['pidmem'];
            $pid_row[$n]['pidcmd'] = $newArray['pidcmd'];
            $pid_row[$n]['date'] = $newArray['date'];

            $n++;
        }

        $dbcore->smarty->assign('wifidb_page_label', 'Scheduling Page (Waiting Imports and Daemon Status)');
        $dbcore->smarty->assign('wifidb_refresh_options', $refresh_opt);
        $dbcore->smarty->assign('wifidb_timezone_options', $timezone_opt);
        $dbcore->smarty->assign('wifidb_dst_options', $dst_opt);
        $dbcore->smarty->assign('wifidb_schedules', $schedule_row);
        $dbcore->smarty->assign('wifidb_daemons', $pid_row);
        $dbcore->smarty->assign('wifidb_importing', $importing_row);
		$dbcore->smarty->assign('wifidb_waiting', $waiting_row);
        $dbcore->smarty->display('scheduling_waiting.tpl');
    break;
}
