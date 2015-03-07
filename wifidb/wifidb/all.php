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

$sql = "SELECT COUNT(*) FROM `wifi`.`wifi_pointers`";
$sqlprep = $dbcore->sql->conn->prepare($sql);       
$sqlprep->execute();
$total_rows = $sqlprep->fetchColumn();

$sql = "SELECT * FROM `wifi`.`wifi_pointers` ORDER BY `{$inputs['sort']}` {$inputs['ord']} LIMIT {$inputs['from']}, {$inputs['to']}";
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

    $wifidb_aps_all[$n]['id'] = $array['id'];
    
    $wifidb_aps_all[$n]['fa'] = $array['FA'];
    $wifidb_aps_all[$n]['la'] = $array['LA'];
    
    if($array['lat'] == "0.0000")
    {
        $wifidb_aps_all[$n]['globe_html'] = "<img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_off.png\">";
    }else
    {
        $wifidb_aps_all[$n]['globe_html'] = "<a href=\"".$dbcore->URL_PATH."opt/export.php?func=exp_all_signal&id=".$array['id']."\" title=\"Export to KMZ\"><img width=\"20px\" src=\"".$dbcore->URL_PATH."img/globe_on.png\"></a>";
    }
    
   // $wifidb_aps_all[$n]['ssid'] = ($array['ssid'] == '' ? '[Blank SSID]' : $array['ssid']);
    if($array['ssid'] == '')
    {
        $wifidb_aps_all[$n]['ssid'] = '[Blank SSID]';
    }
    elseif(!ctype_print($array['ssid']))
    {
        $wifidb_aps_all[$n]['ssid'] = '['.$array['ssid'].']';
    }
    else
    {
        $wifidb_aps_all[$n]['ssid'] = $array['ssid'];
    }
    
    if(@$array['mac'][2] != ":")
    {
        $mac_exp = str_split($array['mac'], 2);
        $implode_mac = implode(":",$mac_exp);
        $wifidb_aps_all[$n]['mac'] = ($implode_mac == '' ? '< 00:00:00:00:00:00 >' : $implode_mac );
    }else
    {
        $wifidb_aps_all[$n]['mac'] = $array['mac'];
    }
    $wifidb_aps_all[$n]['chan'] = ($array['chan'] == '' ? '< ? >' : $array['chan']);

    $wifidb_aps_all[$n]['auth'] = ($array['auth'] == '' ? 'Unknown :(' : $array['auth']);
    $wifidb_aps_all[$n]['encry'] = ($array['encry'] == '' ? 'Unknown :(' : $array['encry']);

    $wifidb_aps_all[$n]['radio'] = $array['radio'];
    $n++;
}

$dbcore->GeneratePages($total_rows, $inputs['from'], $inputs['to'], $inputs['sort'], $inputs['ord']);
$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
$dbcore->smarty->assign('wifidb_aps_all', $wifidb_aps_all);
$dbcore->smarty->smarty->display('all_aps.tpl');
?>