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
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

$usersa =  array();
$sql = "SELECT count(`id`) FROM `wifi`.`wifi_pointers`";
#echo $sql;
$result = $dbcore->sql->conn->query($sql);
$rows = $result->fetch(2);


$sql = "SELECT count(`id`) FROM `wifi`.`wifi_pointers` WHERE `sectype`='1'";
$result = $dbcore->sql->conn->query($sql);
$open = $result->fetch(2);


$sql = "SELECT count(`id`) FROM `wifi`.`wifi_pointers` WHERE `sectype`='2'";
$result = $dbcore->sql->conn->query($sql);
$wep = $result->fetch(2);


$sql = "SELECT count(`id`) FROM `wifi`.`wifi_pointers` WHERE `sectype`='3'";
$result = $dbcore->sql->conn->query($sql);
$sec = $result->fetch(2);

$sql = "SELECT * FROM `wifi`.`wifi_pointers` ORDER BY ID DESC LIMIT 1";
$result = $dbcore->sql->conn->query($sql);
$last = $result->fetch(2);

$sql = "SELECT `username` FROM `wifi`.`user_imports`";
$result = $dbcore->sql->conn->query($sql);
while($user_array = $result->fetch(2))
{
    $usersa[]=$user_array['username'];
}
$usersa = array_unique($usersa);
$usercount = count($usersa);

$sql = "SELECT * FROM `wifi`.`user_imports` order by id desc limit 1";
$result = $dbcore->sql->conn->query($sql);
$newest_array = $result->fetch(2);

$sql = "SELECT `id`,`ap_hash`,`ssid` FROM `wifi`.`wifi_pointers` order by `id` desc limit 1";
$result = $dbcore->sql->conn->query($sql);
$lastap_array = $result->fetch(2);

$lastap_id = $lastap_array['id'];
$lastap_ssid = $lastap_array['ssid'];
$ap_hash = $lastap_array['ap_hash'];
$sql_gps = "select `lat` from `wifi`.`wifi_pointers` WHERE `lat` != 'N 0000.0000' AND `lat` != '' AND `lat` != 'N 0.0000' AND `ap_hash` = '$ap_hash'";
#echo $sql_gps;

$result = $dbcore->sql->conn->query($sql_gps);
$lastgps = $result->fetch(2);

$lat_check = explode(" ", $lastgps['lat']);
$lat_c = @$lat_check[1]+0;
if($lat_c != "0")
{
    $gps_yes = 1;
}
else
{
    $gps_yes = 0;
}

if ($usercount == NULL)
{
    $lastusername = "No users in Database.";
    $lasttitle = "No imports have finished yet.";
    $lastdate = date("Y-m-d H:i:s");
    $usercount = 0;
    $lastid = 0;
}else
{
    $sql = "SELECT * FROM `wifi`.`user_imports` order by `id` desc limit 1";
    $result = $dbcore->sql->conn->query($sql);
    $lastuser = $result->fetch(2);
    #var_dump($lastuser);
    $lastusername = $lastuser['username'];
    $lasttitle = $lastuser['title'];
    $lastdate = $lastuser['date'];
    if($lastdate == ""){$lastdate = date("Y-m-d H:i:s");}
    $lastid = $lastuser['id'];
}
if($gps_yes) { $gps = "on"; }else{ $gps = "off"; }

$dbcore->smarty->assign('wifidb_page_label', 'Index Page');
$dbcore->smarty->assign('total_aps', $rows['count(`id`)']);
$dbcore->smarty->assign('open_aps', $open['count(`id`)']);
$dbcore->smarty->assign('wep_aps', $wep['count(`id`)']);
$dbcore->smarty->assign('sec_aps', $sec['count(`id`)']);
$dbcore->smarty->assign('total_users', $usercount);
$dbcore->smarty->assign('new_ap_id', $lastap_id);

$dbcore->smarty->assign('globe_status', $gps);

$dbcore->smarty->assign('new_import_user', $lastusername);
$dbcore->smarty->assign('new_ap_ssid', $lastap_ssid);
$dbcore->smarty->assign('new_import_date', $lastdate);
$dbcore->smarty->assign('new_import_title', $lasttitle);
$dbcore->smarty->assign('new_import_id', $lastid);

$dbcore->smarty->smarty->display('index.tpl');

?>