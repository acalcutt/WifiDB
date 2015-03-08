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
        $sql = "SELECT * FROM `wifi`.`files` ORDER BY `id` DESC";
        #echo $sql;
        $result = $dbcore->sql->conn->query($sql);
        $class_f = 0;
        $files_all = array();
        while ($newArray = $result->fetch(2))
        {
            $users_array = explode("|", $newArray["user"]);
            $users_array = array_filter($users_array);
            if($class_f){$class = "light"; $class_f = 0;}else{$class = "dark"; $class_f = 1;}
            $files_all[] = array(
                                    'class'=>$class,
                                    'id'=>$newArray['id'],
                                    'user_row'=>$newArray["user_row"],
                                    'file'=>html_entity_decode($newArray['file']),
                                    'date'=>$newArray['date'],
                                    'user'=>$users_array,
                                    'title'=>$newArray['title'],
                                    'aps'=>$newArray['aps'],
                                    'gps'=>$newArray['gps'],
                                    'size'=>$dbcore->format_size($newArray['size']*1024, 2),
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
                "daily_size_label"  => $daily_label_size
            );
        }

        if(file_exists($daemon_out."update.kmz"))
        {

            $kml_head['update_kml'] = 'Current WiFiDB Network Link: <a class="links" href="'.$dbcore->URL_PATH.'out/daemon/update.kmz">Download!</a>';

        }else
        {
            $kml_head['update_kml'] = 'The Daemon Needs to be on and you need to import something with GPS for the first update.kmz file to be created.';
        }

        if($files[0]){$kmldate=$files[0];}else{$kmldate=date ("Y-m-d");}

        $sql = "SELECT `LA` FROM `wifi`.`wifi_pointers` WHERE `lat` != '0.0000' ORDER BY `id` DESC LIMIT 1";
        $result = $dbcore->sql->conn->query($sql);
        $ap_array = $result->fetch(2);

        if($ap_array['LA'])
        {
            if(strpos($ap_array['LA'], ".")){$lastapdate = substr($ap_array['LA'], 0, strpos($ap_array['LA'], "."));}else{$lastapdate = $ap_array['LA'];}
            
            $newest = $daemon_out.'newestAP.kml';
            $kml_head['newest_date'] = $lastapdate;
            $kml_head['newest_link'] = $dbcore->URL_PATH."api/latest.php?labeled=0&download=newestAP.kml";
            $kml_head['newest_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['newest_link'])));
            
            $newest_label = $daemon_out.'newestAP_label.kml';
            $kml_head['newest_labeled_date'] = $lastapdate;
            $kml_head['newest_labeled_link'] = $dbcore->URL_PATH."api/latest.php?labeled=1&download=newestAP_label.kml";
            $kml_head['newest_labeled_size'] = $dbcore->format_size(strlen(file_get_contents($kml_head['newest_labeled_link'])));
        }
        else
        {
            $newest = $daemon_out.'newestAP.kml';
            $kml_head['newest_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['newest_link'] = "#";
            $kml_head['newest_size'] = "0.00 kB";
            
            $newest_label = $daemon_out.'newestAP_label.kml';
            $kml_head['newest_labeled_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['newest_labeled_link'] = "#";
            $kml_head['newest_labeled_size'] = "0.00 kB";
        }
        
        $date = date("Y-m-d");
        $full = $daemon_out.$files[0].'/full_db.kmz';
        if(file_exists($full))
        {
            $kml_head['full_date'] = date ("Y-m-d H:i:s", filemtime($full));
            $kml_head['full_size'] = $dbcore->format_size(filesize($full), 2);

            $kml_head['full_link'] = $dbcore->URL_PATH."out/daemon/".$files[0].'/full_db.kmz';
        }else
        {
            $kml_head['full_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['full_size'] = "0.00 kB";
            $kml_head['full_link'] = "#";
        }

        $full_label = $daemon_out.$files[0].'/full_db_label.kmz';
        if(file_exists($full_label))
        {
            $kml_head['full_labeled_date'] = date ("Y-m-d H:i:s", filemtime($full_label));
            $kml_head['full_labeled_size'] = $dbcore->format_size(filesize($full_label), 2);
            $kml_head['full_labeled_link'] = $dbcore->URL_PATH."out/daemon/".$files[0].'/full_db_label.kmz';
        }else
        {
            $kml_head['full_labeled_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['full_labeled_size'] = "0.00 kB";
            $kml_head['full_labeled_link'] = "#";
        }

        $daily_label = $daemon_out.$files[0].'/daily_db_label.kmz';
        if(file_exists($daily_label))
        {
            $kml_head['daily_labeled_date'] = date ("Y-m-d H:i:s", filemtime($daily_label));
            $kml_head['daily_labeled_size'] = $dbcore->format_size(filesize($daily_label), 2);
            $kml_head['daily_labeled_link'] = $dbcore->URL_PATH."out/daemon/".$files[0].'/daily_db_label.kmz';
        }else
        {
            $kml_head['daily_labeled_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['daily_labeled_size'] = "0.00 kB";
            $kml_head['daily_labeled_link'] = "#";
        }

        $daily = $daemon_out.$files[0].'/daily_db.kmz';
        if(file_exists($daily))
        {
            $kml_head['daily_date'] = date ("Y-m-d H:i:s", filemtime($daily));
            $kml_head['daily_size'] = $dbcore->format_size(filesize($daily), 2);
            $kml_head['daily_link'] = $dbcore->URL_PATH."out/daemon/".$files[0].'/daily_db.kmz';
        }else
        {
            $kml_head['daily_date'] = "None generated for ".$kmldate." yet.";
            $kml_head['daily_size'] = "0.00 kB";
            $kml_head['daily_link'] = "#";
        }
        $dbcore->smarty->assign('wifidb_page_label', "Daemon KML Exports");
        $dbcore->smarty->assign('wifidb_kml_head', $kml_head);
        $dbcore->smarty->assign('wifidb_kml_all_array', $kml_all);
        $dbcore->smarty->display('scheduling_kml.tpl');
    break;

    default:
        #include $dbcore->TOOLS_PATH."/daemon/config.inc.php";
        $sql = "SELECT * FROM `wifi`.`settings` WHERE `id` = '1'";
        $result = $dbcore->sql->conn->query($sql);
        $file_array = $result->fetch(2);
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

            $timezone_opt = '<OPTION '.$select.' VALUE="'.$value.'"> '.$value.'</option>
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
        $sched_row = array();
        $n=0;
        $sql = "SELECT * FROM `wifi`.`files_tmp` ORDER BY `date` ASC";
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
            $sched_row[$n]['color'] = $color;
            $sched_row[$n]['id'] = $newArray['id'];
            $sched_row[$n]['file'] = $newArray['file'];
            $sched_row[$n]['title'] = $newArray['title'];
            $sched_row[$n]['date'] = $newArray['date'];
            $sched_row[$n]['size'] = $newArray['size'];
            $sched_row[$n]['hash'] = $newArray['hash'];
            $sched_row[$n]['user'] = $newArray['user'];

            $tot = "";
            $ssid = "";
            switch($newArray['ap'])
            {
                case "":
                    $ssid = "<td colspan='2' align='center'>Not being imported</td>";
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
            $sched_row[$n]['last_cell'] = $ssid.$tot;
            $n++;
        }
        
        $schedule_row = array();
        $n=0;
        $sql = "SELECT * FROM `wifi`.`schedule` ORDER BY `nodename` ASC";
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
        $sql = "SELECT * FROM `wifi`.`daemon_pid_stats` ORDER BY `nodename` ASC";
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
        $dbcore->smarty->assign('wifidb_done_all', $sched_row);
        $dbcore->smarty->display('scheduling_waiting.tpl');
    break;
}
