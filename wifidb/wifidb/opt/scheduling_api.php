<?php
// Ensure init.inc.php runs in HTML+API mode so it doesn't try to enforce cookie setup
if(!defined('SWITCH_SCREEN')) define('SWITCH_SCREEN', 'HTML');
if(!defined('SWITCH_EXTRAS')) define('SWITCH_EXTRAS', '');
include('../lib/init.inc.php');

header('Content-Type: application/json; charset=utf-8');

$func = strtolower(filter_input(INPUT_GET, 'func', FILTER_SANITIZE_ENCODED));

$response = array(
    'ok' => false,
    'func' => $func,
    'center_html' => '',
    'counts' => array(),
);

function extract_center($html) {
    $start_tag = '<div class="center">';
    $start = strpos($html, $start_tag);
    if ($start === false) { return $html; }
    $pos = $start + strlen($start_tag);
    $depth = 1;
    $len = strlen($html);
    while ($pos < $len) {
        $next_open = strpos($html, '<div', $pos);
        $next_close = strpos($html, '</div>', $pos);
        if ($next_close === false) { break; }
        if ($next_open !== false && $next_open < $next_close) {
            // another nested div opener
            $depth++;
            $pos = $next_open + 4;
            continue;
        } else {
            // closing div
            $depth--;
            $pos = $next_close + 6;
            if ($depth === 0) {
                $end = $pos;
                return substr($html, $start, $end - $start);
            }
            continue;
        }
    }
    // fallback: return from first <div class="center"> to end
    return substr($html, $start);
}

try {
    // counts
    $sql = "SELECT Count(id) AS imp_count FROM files WHERE completed = 1";
    $prep = $dbcore->sql->conn->query($sql);
    $response['counts']['complete_count'] = $prep->fetch(1)[0];

    $sql = "SELECT Count(id) AS imp_count FROM files_importing";
    $prep = $dbcore->sql->conn->query($sql);
    $response['counts']['importing_count'] = $prep->fetch(1)[0];

    $sql = "SELECT Count(id) AS imp_count FROM files_tmp";
    $prep = $dbcore->sql->conn->query($sql);
    $response['counts']['waiting_count'] = $prep->fetch(1)[0];

    $sql = "SELECT Count(id) AS imp_count FROM files_bad";
    $prep = $dbcore->sql->conn->query($sql);
    $response['counts']['bad_count'] = $prep->fetch(1)[0];

    $dbcore->smarty->assign('complete_count', $response['counts']['complete_count']);
    $dbcore->smarty->assign('importing_count', $response['counts']['importing_count']);
    $dbcore->smarty->assign('waiting_count', $response['counts']['waiting_count']);
    $dbcore->smarty->assign('bad_count', $response['counts']['bad_count']);

    switch ($func) {
        case 'waiting':
            $waiting_row = array(); $n=0;
            if (strtolower($dbcore->sql->service) == 'mysql') {
                $sql = "SELECT id, file_orig, title, notes, file_date, size, hash, file_user FROM files_tmp ORDER BY file_date DESC LIMIT 250";
            } else {
                $sql = "SELECT id, file_orig, title, notes, file_date, size, hash, file_user FROM files_tmp ORDER BY file_date DESC OFFSET 0 ROWS FETCH NEXT 250 ROWS ONLY";
            }
            $result_1 = $dbcore->sql->conn->query($sql);
            while ($newArray = $result_1->fetch(2)) {
                $waiting_row[$n]['color'] = 'yellow';
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
            $dbcore->smarty->assign('wifidb_waiting', $waiting_row);
            $html = $dbcore->smarty->fetch('scheduling_waiting.tpl');
            $response['center_html'] = extract_center($html);
            $response['ok'] = true;
        break;

        case 'importing':
            $importing_row = array(); $n=0;
            if (strtolower($dbcore->sql->service) == 'mysql') {
                $sql = "SELECT id, file_orig, title, notes, file_date, size, hash, file_user FROM files_importing ORDER BY file_date DESC LIMIT 250";
            } else {
                $sql = "SELECT id, file_orig, title, notes, file_date, size, hash, file_user FROM files_importing ORDER BY file_date DESC OFFSET 0 ROWS FETCH NEXT 250 ROWS ONLY";
            }
            $result_1 = $dbcore->sql->conn->query($sql);
            while ($newArray = $result_1->fetch(2)) {
                $importing_row[$n]['color'] = 'yellow';
                $importing_row[$n]['id'] = $newArray['id'];
                $importing_row[$n]['file'] = $newArray['file_orig'];
                $importing_row[$n]['title'] = $newArray['title'];
                $importing_row[$n]['notes'] = $newArray['notes'];
                $importing_row[$n]['date'] = $newArray['file_date'];
                $importing_row[$n]['size'] = $newArray['size'];
                $importing_row[$n]['hash'] = $newArray['hash'];
                $importing_row[$n]['user'] = $newArray['file_user'];
                $importing_row[$n]['ap'] = isset($newArray['ap']) ? $newArray['ap'] : '';
                $importing_row[$n]['status'] = isset($newArray['tot']) ? $newArray['tot'] : 'Processing';
                $n++;
            }
            $dbcore->smarty->assign('wifidb_importing', $importing_row);
            $html = $dbcore->smarty->fetch('scheduling_importing.tpl');
            $response['center_html'] = extract_center($html);
            $response['ok'] = true;
        break;

        case 'schedule':
            // Build timezone and refresh options similar to opt/scheduling.php
            $TZone = (isset($_COOKIE['wifidb_client_timezone']) && $_COOKIE['wifidb_client_timezone'] !== '') ? $_COOKIE['wifidb_client_timezone'] : (isset($dbcore->default_timezone) ? $dbcore->default_timezone : 0);
            $dst = (isset($_COOKIE['wifidb_client_dst']) && $_COOKIE['wifidb_client_dst'] !== '') ? $_COOKIE['wifidb_client_dst'] : (isset($dbcore->default_dst) ? $dbcore->default_dst : 0);
            $refresh = (isset($_COOKIE['wifidb_refresh']) && $_COOKIE['wifidb_refresh'] !== '') ? $_COOKIE['wifidb_refresh'] : (isset($dbcore->default_refresh) ? $dbcore->default_refresh : 15);

            // timezone options
            $offsets = array(-12, -11, -10, -9, -8, -7, -6, -5, -4, -3, -2, -1, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14);
            $timezone_opt = '';
            foreach($offsets as $value) {
                $select = ($TZone == $value) ? 'selected ' : '';
                $timezone_opt .= '<OPTION '.$select.' VALUE="'.$value.'"> '.$value.'</option>\n';
            }
            $dst_opt = ($dst == 1) ? 'checked' : '';

            $timezonediff = $TZone + $dst;
            $alter_by = (($timezonediff * 60) * 60);

            $curtime = time();
            $altered = $curtime + $alter_by;
            $curtime_local = date("Y-m-d h:i:s A", $altered);

            // refresh options
            $refresh_opt = "";
            $val = 15;
            $max = 30720;
            while($val < $max) {
                $select = ($refresh == $val) ? 'selected ' : '';
                if($val > 60) { $time_inc_name = 'Minutes'; $d = 60; } else { $time_inc_name = 'Seconds'; $d = 1; }
                $refresh_opt .= '<OPTION '.$select.' VALUE="'.$val.'"> '.($val/$d).' '.$time_inc_name.'</option>\n';
                $val = $val * 2;
            }

            // Build schedule rows with nextrun_local, color, schedpid
            $schedule_row = array(); $n = 0;
            $sql = "SELECT schedule.id, schedule.nodename, schedule.daemon, schedule.enabled, schedule.interval, schedule.status, schedule.nextrun, schedule.logfile, schedule.pidfile, schedule.pid AS schedpid,\n"
                 . "daemon_pid_stats.pid, daemon_pid_stats.pidtime, daemon_pid_stats.pidcpu, daemon_pid_stats.pidmem, daemon_pid_stats.pidcmd, daemon_pid_stats.date AS pidud\n"
                 . "FROM schedule\n"
                 . "LEFT OUTER JOIN daemon_pid_stats ON daemon_pid_stats.pidfile = schedule.pidfile\n"
                 . "ORDER BY schedule.id ASC";
            $result_1 = $dbcore->sql->conn->query($sql);
            while ($newArray = $result_1->fetch(2)) {
                $curtime = time();
                // defensively parse nextrun; handle empty/unparseable values
                $nextrun_raw = isset($newArray['nextrun']) ? $newArray['nextrun'] : '';
                $nextrun = false;
                if ($nextrun_raw !== '' && ($tmp = strtotime($nextrun_raw)) !== false) {
                    $nextrun = $tmp;
                    $altered_nextrun = $nextrun + $alter_by;
                    $nextrun_local = date("Y-m-d h:i:s A", $altered_nextrun);
                    $nextrun_utc = date('Y-m-d h:i:s A', $nextrun);
                } else {
                    $nextrun = 0;
                    $nextrun_local = 'N/A';
                    $nextrun_utc = 'N/A';
                }
                $lastupdatetime = strtotime(@$newArray['pidud']);
                $interval = (int)$newArray['interval'];
                $status = $newArray['status'];
                $enabled = $newArray['enabled'];
                $schedpid = '';

                if($status === 'Running') {
                    $nextrun = strtotime("+{$interval} minutes");
                    $altered_nextrun = $nextrun + $alter_by;
                    $nextrun_local = date("Y-m-d h:i:s A", $altered_nextrun);
                    $nextrun_utc = date('Y-m-d h:i:s A', $nextrun);
                    $schedpid = isset($newArray['schedpid']) ? $newArray['schedpid'] : '';
                    $pid = isset($newArray['pid']) ? $newArray['pid'] : '';
                    $logfile = isset($newArray['logfile']) ? $newArray['logfile'] : '';
                    if($schedpid == $pid) {
                        if(($curtime - $lastupdatetime) < 60) { $color = 'lime'; } else { $color = 'orange'; }
                    } else {
                        $color = 'orange';
                    }
                    if($logfile) { $schedpid .= ' ('.$logfile.')'; }
                } else if($status === 'Error' || $enabled == 0) {
                    $color = 'red';
                } else if($curtime < $nextrun) {
                    $color = 'lightgreen';
                } else {
                    $color = 'yellow';
                }

                $schedule_row[$n]['color'] = $color;
                $schedule_row[$n]['id'] = $newArray['id'];
                $schedule_row[$n]['nodename'] = $newArray['nodename'];
                $schedule_row[$n]['daemon'] = $newArray['daemon'];
                $schedule_row[$n]['enabled'] = $newArray['enabled'];
                $schedule_row[$n]['interval'] = $newArray['interval'];
                $schedule_row[$n]['status'] = $newArray['status'];
                $schedule_row[$n]['nextrun_utc'] = $newArray['nextrun'];
                $schedule_row[$n]['nextrun_local'] = $nextrun_local;
                $schedule_row[$n]['schedpid'] = $schedpid;
                $n++;
            }

            // Build pid (daemon) rows
            $pid_row = array(); $n = 0;
            $sql = ($dbcore->sql->service == 'mysql') ? "SELECT * FROM daemon_pid_stats ORDER BY nodename ASC" : "SELECT * FROM [daemon_pid_stats] ORDER BY [nodename] ASC";
            $result_1 = $dbcore->sql->conn->query($sql);
            while ($newArray = $result_1->fetch(2)) {
                $lastupdatetime = strtotime(@$newArray['date']);
                $altered = $lastupdatetime + $alter_by;
                $lastupdatetime_local = date("Y-m-d H:i:s", $altered);
                $curtime = time();
                if($newArray['pid'] == 0) { $color = 'red'; }
                else { $color = (($curtime - $lastupdatetime) < 60) ? 'lime' : 'yellow'; }

                $pid_row[$n]['color'] = $color;
                $pid_row[$n]['nodename'] = $newArray['nodename'];
                $pid_row[$n]['pidfile'] = $newArray['pidfile'];
                $pid_row[$n]['pid'] = $newArray['pid'];
                $pid_row[$n]['pidtime'] = $newArray['pidtime'];
                $pid_row[$n]['pidmem'] = $newArray['pidmem'];
                $pid_row[$n]['pidcmd'] = $newArray['pidcmd'];
                $pid_row[$n]['date'] = $newArray['date'];
                $pid_row[$n]['lastupdatetime_local'] = $lastupdatetime_local;
                $n++;
            }

            $dbcore->smarty->assign('wifidb_page_label', 'Scheduling Page (Daemon Schedule)');
            $dbcore->smarty->assign('wifidb_refresh_options', $refresh_opt);
            $dbcore->smarty->assign('wifidb_timezone_options', $timezone_opt);
            $dbcore->smarty->assign('wifidb_dst_options', $dst_opt);
            $dbcore->smarty->assign('curtime_local', $curtime_local);
            $dbcore->smarty->assign('wifidb_schedules', $schedule_row);
            $dbcore->smarty->assign('wifidb_daemons', $pid_row);

            $html = $dbcore->smarty->fetch('scheduling_schedule.tpl');
            $response['center_html'] = extract_center($html);
            $response['ok'] = true;
        break;

        case 'done':
            if (strtolower($dbcore->sql->service) == 'mysql') {
                $sql = "SELECT id, file_orig, file_user, notes, title, file_date, aps, gps, ValidGPS, size, NewAPPercent, hash FROM files WHERE completed = 1 ORDER BY file_date DESC LIMIT 250";
            } else {
                $sql = "SELECT id, file_orig, file_user, notes, title, file_date, aps, gps, ValidGPS, size, NewAPPercent, hash FROM files WHERE completed = 1 ORDER BY file_date DESC OFFSET 0 ROWS FETCH NEXT 250 ROWS ONLY";
            }
            $result = $dbcore->sql->conn->query($sql);
            $files_all = array(); $class_f = 0;
            while ($newArray = $result->fetch(2)) {
                $class = $class_f ? 'light' : 'dark'; $class_f = !$class_f;
                $files_all[] = array(
                    'class'=>$class,
                    'id'=>$newArray['id'],
                    'file'=>$newArray['file_orig'],
                    'date'=>$newArray['file_date'],
                    'user'=>$newArray['file_user'],
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
            $dbcore->smarty->assign('wifidb_done_all_array', $files_all);
            $html = $dbcore->smarty->fetch('scheduling_done.tpl');
            $response['center_html'] = extract_center($html);
            $response['ok'] = true;
        break;

        case 'bad':
            $bad_row = array(); $n=0; $class_f = 0;
            if (strtolower($dbcore->sql->service) == 'mysql') {
                $sql = "SELECT id, file_name, file_orig, file_user, notes, title, file_date, size, hash, type, error_msg FROM files_bad ORDER BY file_date DESC LIMIT 250";
            } else {
                $sql = "SELECT id, file_name, file_orig, file_user, notes, title, file_date, size, hash, type, error_msg FROM files_bad ORDER BY file_date DESC OFFSET 0 ROWS FETCH NEXT 250 ROWS ONLY";
            }
            $result_1 = $dbcore->sql->conn->query($sql);
            while ($newArray = $result_1->fetch(2)) {
                $class = $class_f ? 'light' : 'dark'; $class_f = !$class_f;
                $bad_row[$n]['color'] = 'red';
                $bad_row[$n]['class'] = $class;
                $bad_row[$n]['id'] = $newArray['id'];
                $bad_row[$n]['file'] = $newArray['file_orig'];
                $bad_row[$n]['file_name'] = $newArray['file_name'];
                $bad_row[$n]['title'] = $newArray['title'];
                $bad_row[$n]['notes'] = $newArray['notes'];
                $bad_row[$n]['date'] = $newArray['file_date'];
                $bad_row[$n]['size'] = $newArray['size'];
                $bad_row[$n]['hash'] = $newArray['hash'];
                $bad_row[$n]['user'] = $newArray['file_user'];
                $bad_row[$n]['error'] = $newArray['error_msg'];
                $bad_row[$n]['type'] = isset($newArray['type']) ? $newArray['type'] : '';
                $n++;
            }
            $dbcore->smarty->assign('wifidb_bad', $bad_row);
            $html = $dbcore->smarty->fetch('scheduling_bad.tpl');
            $response['center_html'] = extract_center($html);
            $response['ok'] = true;
        break;

        default:
            $response['ok'] = true;
        break;
    }

} catch (Exception $e) {
    $response['error'] = $e->getMessage();
}

echo json_encode($response);
