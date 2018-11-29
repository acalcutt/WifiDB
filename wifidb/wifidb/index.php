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
$sql = "SELECT count(`AP_ID`) FROM `wifi_ap`";
#echo $sql;
$result = $dbcore->sql->conn->query($sql);
$rows = $result->fetch(2);


$sql = "SELECT count(`AP_ID`) FROM `wifi_ap` WHERE `sectype`='1'";
$result = $dbcore->sql->conn->query($sql);
$open = $result->fetch(2);


$sql = "SELECT count(`AP_ID`) FROM `wifi_ap` WHERE `sectype`='2'";
$result = $dbcore->sql->conn->query($sql);
$wep = $result->fetch(2);


$sql = "SELECT count(`AP_ID`) FROM `wifi_ap` WHERE `sectype`='3'";
$result = $dbcore->sql->conn->query($sql);
$sec = $result->fetch(2);

$sql = "SELECT * FROM `wifi_ap` ORDER BY `AP_ID` DESC LIMIT 1";
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

$sql = "SELECT `AP_ID`,`ssid`,`HighGps_ID` FROM `wifi_ap` order by `AP_ID` desc limit 1";
$result = $dbcore->sql->conn->query($sql);
$lastap_array = $result->fetch(2);

$lastap_id = $lastap_array['AP_ID'];
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

if($lastap_array['HighGps_ID'] == "")
{
    $ap_globe_html = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_off.png\">";
	$ap_globe_html .= "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/json_off.png\">";
}else
{
    $ap_globe_html = "<a href=\"".$dbcore->URL_PATH."api/export.php?func=exp_ap_netlink&id=".$lastap_array['AP_ID']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_on.png\"></a>";
	$ap_globe_html .= "<a href=\"".$dbcore->URL_PATH."api/geojson.php?json=1&func=exp_ap&id=".$lastap_array['AP_ID']."\" title=\"Export to JSON\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/json_on.png\"></a>";
}

$sql = "SELECT `user`, `id`, `title`, `date`, `ValidGPS` FROM `files` WHERE `completed`=1 order by `date` desc limit 1";
$result = $dbcore->sql->conn->query($sql);
$lastuser = $result->fetch(2);
$lastusername =  $lastuser['user'];
$lasttitle = $lastuser['title'];
$lastdate = $lastuser['date'];
if($lastdate == ""){$lastdate = date("Y-m-d H:i:s");}
$lastid = $lastuser['id'];

if($lastuser['ValidGPS'] == 1)
{
	$list_globe_html = "<a href=\"".$dbcore->URL_PATH."/api/export.php?func=exp_list&id=".$lastuser['id']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."/img/globe_on.png\"></a>";				
	$list_globe_html .= "<a href=\"".$dbcore->URL_PATH."/api/geojson.php?json=1&func=exp_list&id=".$lastuser['id']."\" title=\"Export to JSON\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."/img/json_on.png\"></a>";				
}
else
{
	$list_globe_html = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."/img/globe_off.png\">";
	$list_globe_html .= "<img width=\"20px\" src=\"".$dbcore->URL_PATH."/img/json_off.png\">";
}

if ($usercount == NULL)
{
    $lastusername = "No users in Database.";
    $lasttitle = "No imports have finished yet.";
    $lastdate = date("Y-m-d H:i:s");
    $usercount = 0;
    $lastid = 0;
}

$dbcore->smarty->assign('wifidb_page_label', 'Index Page');
$dbcore->smarty->assign('total_aps', $rows['count(`AP_ID`)']);
$dbcore->smarty->assign('open_aps', $open['count(`AP_ID`)']);
$dbcore->smarty->assign('wep_aps', $wep['count(`AP_ID`)']);
$dbcore->smarty->assign('sec_aps', $sec['count(`AP_ID`)']);
$dbcore->smarty->assign('total_users', $usercount);
$dbcore->smarty->assign('new_ap_id', $lastap_id);
$dbcore->smarty->assign('ap_globe_html', $ap_globe_html);
$dbcore->smarty->assign('list_globe_html', $list_globe_html);
$dbcore->smarty->assign('new_import_user', $lastusername);
$dbcore->smarty->assign('new_ap_ssid', $lastap_ssid);
$dbcore->smarty->assign('new_import_date', $lastdate);
$dbcore->smarty->assign('new_import_title', $lasttitle);
$dbcore->smarty->assign('new_import_id', $lastid);

$dbcore->smarty->smarty->display('index.tpl');

?>