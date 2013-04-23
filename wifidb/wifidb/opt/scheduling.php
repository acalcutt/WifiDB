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

if($func === "refresh")
{
    $POST_san = filter_input(INPUT_POST, 'refresh', FILTER_SANITIZE_ENCODED);

    if( (!isset($POST_san['refresh'])) or $POST_san['refresh']=='' ) { $POST_san['refresh'] = "wifidb"; }
    $refresh_post = strip_tags(addslashes($POST_san['refresh']));
    setcookie( 'wifidb_refresh' , $refresh_post , (time()+($dbcore->timeout)), "/".$dbcore->root."/opt/scheduling.php" );
    #echo $refresh_post."<BR>";
    header('Location: '.$dbcore->HOSTURL.$dbcore->root.'/opt/scheduling.php');
}

$TZone= (@$_COOKIE['wifidb_client_timezone'] ? @$_COOKIE['wifidb_client_timezone'] : $dbcore->default_timezone);
$dst = (@$_COOKIE['wifidb_client_dst']!='' ? @$_COOKIE['wifidb_client_dst'] : $dbcore->default_dst);
$refresh = (@$_COOKIE['wifidb_refresh']!='' ? @$_COOKIE['wifidb_refresh'] : $dbcore->default_refresh);
#echo $func;
switch($func)
{
    case 'done':
        $sql = "SELECT * FROM `wifi`.`files` ORDER BY `id` DESC";
        #echo $sql;
        $result = $dbcore->sql->conn->query($sql);
        $class_f = 0;
        $files_all = array();
        while ($newArray = $result->fetch(2))
        {
            if($class_f){$class = "light"; $class_f = 0;}else{$class = "dark"; $class_f = 1;}
            $files_all[] = array(
                                    'class'=>$class,
                                    'id'=>$newArray['id'],
                                    'user_row'=>$newArray["user_row"],
                                    'file'=>html_entity_decode($newArray['file']),
                                    'date'=>$newArray['date'],
                                    'user'=>$newArray["user"],
                                    'title'=>$newArray['title'],
                                    'aps'=>$newArray['aps'],
                                    'gps'=>$newArray['gps'],
                                    'size'=>$dbcore->format_size($newArray['size']*1024, 2),
                                    'hash'=>$newArray['hash']
                                );
        }
        $dbcore->smarty->assign("wifidb_done_all_array", $files_all);
        $dbcore->smarty->display('scheduling_done.tpl');
    break;
#######################################################################
    case 'daemon_kml':
        $date = date("Y-m-d");
        $kml_head = array();
        $daemon_out = $dbcore->PATH."out/daemon/";
        $url_base = $dbcore->URL_PATH."out/daemon/";;
        $kml_all = array();
        $dh = opendir($daemon_out) or die("couldn't open directory");
        while ($file = readdir($dh))
        {
            if($file == "."){continue;}
            if($file == ".."){continue;}
            if($file == ".svn"){continue;}
            if($file == "history"){continue;}
            if($file == "full_db.kml"){continue;}
            if($file == "full_db.kmz"){continue;}
            if($file == "full_db_label.kml"){continue;}
            if($file == "full_db_label.kmz"){continue;}
            if($file == "update.kml"){continue;}
            if($file == "history.kml"){continue;}
            if($file == "newestAP.kml"){continue;}
            if($file == "newestAP_label.kml"){continue;}
            var_dump($file);
            $kmz_file = $daemon_out.$file.'/full_db.kmz';
            var_dump($kmz_file);
            var_dump(array(
                "file"     => $file,
                "file_url" => $url_base.$file.'/full_db.kmz',
                "time"     => date ("H:i:s", filemtime($kmz_file)),
                "size"     => $dbcore->format_size(filesize($daemon_out.$file."/full_db.kmz"), 2)
            ));
            if(file_exists($kmz_file))
            {
                $kml_all[] = array(
                                    "file"     => $file,
                                    "file_url" => $url_base.$file.'/full_db.kmz',
                                    "time"     => date ("H:i:s", filemtime($kmz_file)),
                                    "size"     => $dbcore->format_size(filesize($daemon_out.$file."/full_db.kmz"), 2)
                            );
            }

        }

        if(file_exists($daemon_out."update.kml"))
        {

            $kml_head['update_kml'] = '<a class="links" href="../out/daemon/update.kml">Current WiFiDB Network Link</a>';

        }else
        {
            $kml_head['update_kml'] = 'The Daemon Needs to be on and you need to import something with GPS for the first update.kml file to be created.';
        }

        $newest = $daemon_out.'newestAP.kml';
        if(file_exists($newest))
        {
            $kml_head['newest_date'] = date ("Y-m-d H:i:s", filemtime($newest));
            $kml_head['newest_size'] = $dbcore->format_size(filesize($newest), 2);
        }else
        {
            $kml_head['newest_date'] = "None generated yet";
            $kml_head['newest_size'] = "0.00 kb";
        }

        $full = $daemon_out.$date.'full_db.kml';
        if(file_exists($full))
        {
            $kml_head['full_date'] = date ("Y-m-d H:i:s", filemtime($full));
            $kml_head['full_size'] = $dbcore->format_size(filesize($full), 2);
        }else
        {
            $kml_head['full_date'] = "None generated for ".$date." yet, <br>be patient young grasshopper.";
            $kml_head['full_size'] = "0.00 kb";
        }

        $daily = $daemon_out.$date.'daily_db.kml';
        if(file_exists($daily))
        {
            $kml_head['today_date'] = date ("Y-m-d H:i:s", filemtime($daily));
            $kml_head['today_size'] = $dbcore->format_size(filesize($daily), 2);
        }else
        {
            $kml_head['today_date'] = "None generated for ".$date." yet, <br>be patient young grasshopper.";
            $kml_head['today_size'] = "0.00 kb";
        }
        $dbcore->smarty->assign('wifidb_kml_head', $kml_head);
        $dbcore->smarty->assign('wifidb_kml_all_array', $kml_all);
        $dbcore->smarty->display('scheduling_kml.tpl');
    break;

    default:
        #include $dbcore->TOOLS_PATH."/daemon/config.inc.php";
        $sql = "SELECT * FROM `wifi`.`settings` WHERE `id` = '1'";
        $result = $dbcore->sql->conn->query($sql);
        $file_array = $result->fetch(2);

        if($dst == 1){$dst = 0;}
#	echo "Before: ".$file_array['size']."<BR>";
        $str_time = strtotime($file_array['size']);
#	echo "Convert: ".$str_time."<BR>";
        $alter_by = ((($TZone+$dst)*60)*60);
#	echo "CALC: ".$alter_by."<BR>";
        $altered = $str_time+$alter_by;
#	echo "ADD: ".$altered."<BR>";
        $next_run = date("Y-m-d H:i:s", $altered);
####### echo $next_run.'  [ '.getTZ('-5').' ]';

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
                $time_inc_name = "Minuets";
                $d=60;
            }
            else
            {
                $time_inc_name = "Seconds";
                $d=1;
            }
            $refresh_opt .= '<OPTION '.$select.' VALUE="'.$val.'"> '.($val/$d).' '.$time_inc_name."\r\n";
            $val = $val*2;
        }
        $sched_row = array();
        $n=0;
        $sql = "SELECT * FROM `wifi`.`files_tmp` ORDER BY `date` DESC";
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
                case "Preping for Import":
                    $ssid = "<td colspan='2' align='center'>Preping for Import...</td>";
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
        $dbcore->smarty->assign('wifidb_page_label', 'Scheduling Page (Waiting Imports and Daemon Status)');
        $dbcore->smarty->assign('wifidb_next_run', array('utc'=>$file_array['size'],'local'=>$next_run));
        $dbcore->smarty->assign('wifidb_refresh_options', $refresh_opt);
        $dbcore->smarty->assign('wifidb_daemon', $dbcore->getdaemonstats('imp_expd.pid'));
        $dbcore->smarty->assign('wifidb_done_all', $sched_row);
        $dbcore->smarty->display('scheduling_waiting.tpl');
    break;
}
?>