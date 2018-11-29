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
$dbcore->smarty->assign('wifidb_page_label', 'All Access Points Page');

$args = array(
    'ord' => FILTER_SANITIZE_ENCODED,
    'sort' => FILTER_SANITIZE_ENCODED,
    'to' => FILTER_SANITIZE_NUMBER_INT,
    'from' => FILTER_SANITIZE_NUMBER_INT
);
$inputs = filter_input_array(INPUT_GET, $args);


$dbcore->smarty->assign('from', $inputs['from']);
$dbcore->smarty->assign('inc', $inputs['to']);
$dbcore->smarty->assign('ord', $inputs['ord']);
$dbcore->smarty->assign('sort', $inputs['sort']);

$sql = "SELECT COUNT(*) FROM `wifi_ap` WHERE `FirstHist_ID` IS NOT NULL";
$sqlprep = $dbcore->sql->conn->prepare($sql);       
$sqlprep->execute();
$total_rows = $sqlprep->fetchColumn();

#$sql = "SELECT * FROM `wifi_ap` ORDER BY `{$inputs['sort']}` {$inputs['ord']} LIMIT {$inputs['from']}, {$inputs['to']}";
#$sql = "SELECT AP_ID, BSSID, SSID, CHAN, AUTH, ENCR, SECTYPE, RADTYPE, NETTYPE, BTX, OTX,\n"
#    . "(SELECT Hist_Date FROM wifi_hist WHERE Hist_ID = WAP.FirstHist_ID) As FA,\n"
#    . "(SELECT Hist_Date FROM wifi_hist WHERE Hist_ID = WAP.LastHist_ID) As LA,\n"
#    . "(SELECT (SELECT Lat FROM wifi_gps WHERE GPS_ID = WGPS.GPS_ID) As Lon FROM `wifi_hist` AS WGPS WHERE Hist_ID = WAP.HighGpsHist_ID) As Lat,\n"
#    . "(SELECT (SELECT Lon FROM wifi_gps WHERE GPS_ID = WGPS.GPS_ID) As Lon FROM `wifi_hist` AS WGPS WHERE Hist_ID = WAP.HighGpsHist_ID) As Lon\n"
#    . "FROM `wifi_ap` AS WAP  \n"
#    . "ORDER BY `{$inputs['sort']}` {$inputs['ord']} LIMIT {$inputs['from']}, {$inputs['to']}";


$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX,\n"
    . "whFA.Hist_Date As FA,\n"
    . "whLA.Hist_Date As LA,\n"
    . "wGPS.Lat As Lat,\n"
    . "wGPS.Lon As Lon\n"
    . "FROM `wifi_ap` AS wap\n"
    . "LEFT JOIN wifi_hist AS whFA ON whFA.Hist_ID = wap.FirstHist_ID\n"
    . "LEFT JOIN wifi_hist AS whLA ON whLA.Hist_ID = wap.LastHist_ID\n"
    . "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
    . "WHERE wap.FirstHist_ID IS NOT NULL\n"	
    . "ORDER BY `{$inputs['sort']}` {$inputs['ord']} LIMIT {$inputs['from']}, {$inputs['to']}";



$pre_page_list = $dbcore->sql->conn->query($sql);


$row_color = 0;
$n=0;
$wifidb_aps_all = array();
while ( $array = $pre_page_list->fetch(2) )
{
    if($row_color == 1)
    {$row_color = 0; $color = "light";}
    else{$row_color = 1; $color = "dark";}
    $wifidb_aps_all[$n]['class'] = $color;

    $wifidb_aps_all[$n]['id'] = $array['AP_ID'];
    
    $wifidb_aps_all[$n]['fa'] = $array['FA'];
    $wifidb_aps_all[$n]['la'] = $array['LA'];
    
    if($array['Lat'] == "")
    {
        $wifidb_aps_all[$n]['globe_html'] = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_off.png\">";
		$wifidb_aps_all[$n]['globe_html'] .= "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/json_off.png\">";
    }else
    {
        $wifidb_aps_all[$n]['globe_html'] = "<a href=\"".$dbcore->URL_PATH."api/export.php?func=exp_ap_netlink&id=".$array['AP_ID']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_on.png\"></a>";
		$wifidb_aps_all[$n]['globe_html'] .= "<a href=\"".$dbcore->URL_PATH."api/geojson.php?json=1&func=exp_ap&id=".$array['AP_ID']."\" title=\"Export to JSON\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/json_on.png\"></a>";
    }
    
   // $wifidb_aps_all[$n]['ssid'] = ($array['ssid'] == '' ? '[Blank SSID]' : $array['ssid']);
    if($array['SSID'] == '')
    {
        $wifidb_aps_all[$n]['ssid'] = '[Blank SSID]';
    }
    elseif(!ctype_print($array['SSID']))
    {
        $wifidb_aps_all[$n]['ssid'] = '['.$array['SSID'].']';
    }
    else
    {
        $wifidb_aps_all[$n]['ssid'] = $array['SSID'];
    }
    
    if(@$array['BSSID'][2] != ":")
    {
        $mac_exp = str_split($array['BSSID'], 2);
        $implode_mac = implode(":",$mac_exp);
        $wifidb_aps_all[$n]['mac'] = ($implode_mac == '' ? '< 00:00:00:00:00:00 >' : $implode_mac );
    }else
    {
        $wifidb_aps_all[$n]['mac'] = $array['BSSID'];
    }
    $wifidb_aps_all[$n]['chan'] = ($array['CHAN'] == '' ? '< ? >' : $array['CHAN']);

    $wifidb_aps_all[$n]['auth'] = ($array['AUTH'] == '' ? 'Unknown :(' : $array['AUTH']);
    $wifidb_aps_all[$n]['encry'] = ($array['ENCR'] == '' ? 'Unknown :(' : $array['ENCR']);

    $wifidb_aps_all[$n]['radio'] = $array['RADTYPE'];
    $n++;
}

$dbcore->GeneratePages($total_rows, $inputs['from'], $inputs['to'], $inputs['sort'], $inputs['ord']);
$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
$dbcore->smarty->assign('wifidb_aps_all', $wifidb_aps_all);
$dbcore->smarty->smarty->display('all_aps.tpl');
?>