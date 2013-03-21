<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/


error_reporting(E_ALL && E_STRICT);
global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');

include('../lib/init.inc.php');
$id = filter_input(INPUT_GET, 'id', 519);

$sqls = "SELECT * FROM `wifi`.`wifi_pointers` WHERE id = ?";
$prep = $dbcore->sql->conn->prepare($sqls);
$result = $prep->execute(array($id));
$newArray = $prep->fetch();
$ap_data = array(
    'id'=>$newArray['id'],
    'radio'=>$newArray['radio'],
    'manuf'=>$newArray['manuf'],
    'mac'=>$newArray['mac'],
    'ssid'=>$newArray['ssid'],
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

$sql_gps = "SELECT `lat` FROM `wifi`.`wifi_gps` WHERE `ap_hash` = '{$newArray['ap_hash']}' LIMIT 1";
$resultgps = $dbcore->sql->conn->query($sql_gps);
$lastgps = $resultgps->fetch(2);
if($lastgps['lat'] !== FALSE)
{
    $lat_check = explode(" ", $lastgps['lat']);
    $lat_c = $lat_check[1]+0;
    if($lat_c != "0"){$gps_globe = "on";}else{$gps_globe = "off";}
}else
{
    $gps_globe = "off";
}
$sql = "SELECT * FROM `wifi`.`wifi_signals` WHERE `ap_hash` = '{$newArray["ap_hash"]}' ORDER BY `date` ASC, `time` ASC";
$result = $dbcore->sql->conn->query($sql);
$flip = 0;
$prev_date = 0;
$date_range = -1;
$signal_runs = array();
while ($field = $result->fetch(2))
{
    if($flip){$class="light";$flip=0;}else{$class="dark";$flip=1;}
    $sql_gps = "SELECT * FROM `wifi`.`wifi_gps` WHERE `id` = '{$field['id']}'";
    $gps_res = $dbcore->sql->conn->query($sql_gps);
    $gps = $gps_res->fetch(2);
    if($gps['id']==''){continue;}
    if($prev_date < strtotime($gps['date']))
    {
        $date_range++;
        $signal_runs[$date_range]['id'] = $date_range;
        $signal_runs[$date_range]['start'] = $gps['date']." ".$gps['time'];
        $signal_runs[$date_range]['descstart'] = $gps['time'];
        $signal_runs[$date_range]['user'] = $field['username'];
    }else
    {
        if($signal_runs[$date_range]['user'] != $field['username'])
        {
            $signal_runs[$date_range]['user'] .= " and ".$field['username'];
        }
        $signal_runs[$date_range]['desc'] = $gps['date'].": ".$signal_runs[$date_range]['descstart']." - ".$gps['time'];
        $signal_runs[$date_range]['stop'] = $gps['date']." ".$gps['time'];
    }

    $prev_date = strtotime($gps['date']);

    $signal_runs[$date_range]['gps'][] = array(   
                            'class'=>$class,
                            'lat'=>$gps["lat"],
                            'long'=>$gps["long"],
                            'sats'=>$gps["sats"],
                            'date'=>$gps["date"],
                            'time'=>$gps["time"],
                            'signal'=>$field["signal"]
                        );
}
$list = array();
$result = $dbcore->sql->conn->query("SELECT * FROM `wifi`.`user_imports` WHERE `points` LIKE '%-{$id}:%' ");

$flip = 0;
while ($field = $result->fetch(2))
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

$dbcore->smarty->assign('wifidb_page_label', "Access Point Page ({$newArray['ssid']})");
$dbcore->smarty->assign('wifidb_ap_signal_all', $signal_runs);
$dbcore->smarty->assign('wifidb_assoc_lists', $list);
$dbcore->smarty->assign('wifidb_ap_globe', $gps_globe);
$dbcore->smarty->assign('wifidb_ap', $ap_data);
#var_dump($ap_data);
#$dbcore->smarty->display('ap.tpl');
$dbcore->smarty->display('fetch.tpl');
?>