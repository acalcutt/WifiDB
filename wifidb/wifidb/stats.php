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

#Get SECTYPE and AP Counts
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT `SECTYPE`, count(`AP_ID`) AS `ap_count` FROM `wifi_ap` WHERE `fa` IS NOT NULL GROUP BY `SECTYPE`";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT [SECTYPE], count([AP_ID]) AS [ap_count] FROM [wifi_ap] WHERE [fa] IS NOT NULL GROUP BY [SECTYPE]";}
$result = $dbcore->sql->conn->query($sql);
$count = null;
$open = null;
$wep = null;
$sec = null;
$result->execute();
$seclist = $result->fetchAll();
foreach($seclist as $secval)
{
	$count += $secval["ap_count"];
	if($secval["SECTYPE"] == 1)
		{$open = $secval["ap_count"];}
	elseif($secval["SECTYPE"] == 2)
		{$wep = $secval["ap_count"];}
	elseif($secval["SECTYPE"] == 3)
		{$sec = $secval["ap_count"];}
}

#Count the number of users who have imported files
$sql = "SELECT count(distinct file_user) AS user_count FROM files WHERE completed = 1";
$result = $dbcore->sql->conn->query($sql);
$usercount = $result->fetch(2);

#Get the latest import list
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT id, file_user, title, date, ValidGPS FROM files WHERE completed = 1 ORDER BY id DESC LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 id, file_user, title, date, ValidGPS FROM files WHERE completed = 1 ORDER BY id DESC";}
$result = $dbcore->sql->conn->query($sql);
$lastuser = $result->fetch(2);
$lastid = $lastuser['id'];
$lastusername =  htmlspecialchars($lastuser['file_user'], ENT_QUOTES, 'UTF-8');
$lasttitle = htmlspecialchars($lastuser['title'], ENT_QUOTES, 'UTF-8');
$lastdate = htmlspecialchars($lastuser['date'], ENT_QUOTES, 'UTF-8');
$list_validgps = $lastuser['ValidGPS'];
if($lastdate == ""){$lastdate = date("Y-m-d H:i:s");}

#Find if last user has valid GPS
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT ValidGPS FROM files WHERE file_user LIKE ? And ValidGPS = 1 LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 ValidGPS FROM files WHERE file_user LIKE ? And ValidGPS = 1";}
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $lastusername, PDO::PARAM_STR);
$prep->execute();
$appointer = $prep->fetch(2);
$user_validgps = $appointer['ValidGPS'];

#Get the latest AP
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT AP_ID,SSID,HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL ORDER BY AP_ID DESC LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 AP_ID,SSID,HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL ORDER BY AP_ID DESC";}
$result = $dbcore->sql->conn->query($sql);
$lastap_array = $result->fetch(2);

$lastap_id = $lastap_array['AP_ID'];
$lastap_ssid = htmlspecialchars($dbcore->formatSSID($lastap_array['SSID']), ENT_QUOTES, 'UTF-8');
if($lastap_array['HighGps_ID'] == ""){$ap_validgps = 0;}else{$ap_validgps = 1;}


if ($usercount == NULL)
{
    $lastusername = "No users in Database.";
    $lasttitle = "No imports have finished yet.";
    $lastdate = date("Y-m-d H:i:s");
    $usercount = 0;
    $lastid = 0;
}

$dbcore->smarty->assign('wifidb_page_label', 'Index Page');
$dbcore->smarty->assign('total_aps', $count);
$dbcore->smarty->assign('open_aps', $open);
$dbcore->smarty->assign('wep_aps', $wep);
$dbcore->smarty->assign('sec_aps', $sec);
$dbcore->smarty->assign('total_users', $usercount['user_count']);
$dbcore->smarty->assign('new_ap_id', $lastap_id);
$dbcore->smarty->assign('ap_validgps', $ap_validgps);
$dbcore->smarty->assign('list_validgps', $list_validgps);
$dbcore->smarty->assign('user_validgps', $user_validgps);
$dbcore->smarty->assign('new_import_user', $lastusername);
$dbcore->smarty->assign('new_ap_ssid', $lastap_ssid);
$dbcore->smarty->assign('new_import_date', $lastdate);
$dbcore->smarty->assign('new_import_title', $lasttitle);
$dbcore->smarty->assign('new_import_id', $lastid);

$dbcore->smarty->display('index.tpl');

?>