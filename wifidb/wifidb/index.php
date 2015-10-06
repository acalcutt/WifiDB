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
$sql = "SELECT count(`id`) FROM `wifi_pointers`";
#echo $sql;
$result = $dbcore->sql->conn->query($sql);
$rows = $result->fetch(2);


$sql = "SELECT count(`id`) FROM `wifi_pointers` WHERE `sectype`='1'";
$result = $dbcore->sql->conn->query($sql);
$open = $result->fetch(2);


$sql = "SELECT count(`id`) FROM `wifi_pointers` WHERE `sectype`='2'";
$result = $dbcore->sql->conn->query($sql);
$wep = $result->fetch(2);


$sql = "SELECT count(`id`) FROM `wifi_pointers` WHERE `sectype`='3'";
$result = $dbcore->sql->conn->query($sql);
$sec = $result->fetch(2);

$sql = "SELECT * FROM `wifi_pointers` ORDER BY ID DESC LIMIT 1";
$result = $dbcore->sql->conn->query($sql);
$last = $result->fetch(2);

$sql = "SELECT `username` FROM `user_imports`";
$result = $dbcore->sql->conn->query($sql);
while($user_array = $result->fetch(2))
{
    $usersa[]=$user_array['username'];
}
$usersa = array_unique($usersa);
$usercount = count($usersa);

$sql = "SELECT * FROM `user_imports` order by id desc limit 1";
$result = $dbcore->sql->conn->query($sql);
$newest_array = $result->fetch(2);

$sql = "SELECT `id`,`ap_hash`,`ssid`,`lat` FROM `wifi_pointers` order by `id` desc limit 1";
$result = $dbcore->sql->conn->query($sql);
$lastap_array = $result->fetch(2);

$lastap_id = $lastap_array['id'];
if($lastap_array['ssid'] == '')
{
    $lastap_ssid = '[Blank SSID]';
}
elseif(!ctype_print($lastap_array['ssid']))
{
    $lastap_ssid = '['.$lastap_array['ssid'].']';
}
else
{
    $lastap_ssid = $lastap_array['ssid'];
}

$ap_hash = $lastap_array['ap_hash'];

if($lastap_array['lat'] == "0.0000")
{
    $globe_html = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_off.png\">";
}else
{
    $globe_html = "<a href=\"".$dbcore->URL_PATH."api/export.php?func=exp_ap_netlink&id=".$lastap_array['id']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_on.png\"></a>";
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
    $sql = "SELECT * FROM `user_imports` order by `id` desc limit 1";
    $result = $dbcore->sql->conn->query($sql);
    $lastuser = $result->fetch(2);
    #var_dump($lastuser);
    $lastusername = $lastuser['username'];
    $lasttitle = $lastuser['title'];
    $lastdate = $lastuser['date'];
    if($lastdate == ""){$lastdate = date("Y-m-d H:i:s");}
    $lastid = $lastuser['id'];
}

$dbcore->smarty->assign('wifidb_page_label', 'Index Page');
$dbcore->smarty->assign('total_aps', $rows['count(`id`)']);
$dbcore->smarty->assign('open_aps', $open['count(`id`)']);
$dbcore->smarty->assign('wep_aps', $wep['count(`id`)']);
$dbcore->smarty->assign('sec_aps', $sec['count(`id`)']);
$dbcore->smarty->assign('total_users', $usercount);
$dbcore->smarty->assign('new_ap_id', $lastap_id);
$dbcore->smarty->assign('globe_html', $globe_html);
$dbcore->smarty->assign('new_import_user', $lastusername);

$dbcore->smarty->assign('new_ap_ssid', $lastap_ssid);
$dbcore->smarty->assign('new_import_date', $lastdate);
$dbcore->smarty->assign('new_import_title', $lasttitle);
$dbcore->smarty->assign('new_import_id', $lastid);

$dbcore->smarty->smarty->display('index.tpl');

?>