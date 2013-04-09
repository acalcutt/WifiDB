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

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');
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

$sql = "SELECT `size` FROM `wifi`.`settings` WHERE `table` = 'wifi0'";
$number_aps = $dbcore->sql->conn->query($sql);
$num_ap_array = $number_aps->fetch(2);
$total_rows = $num_ap_array['size'];

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
    
    $wifidb_aps_all[$n]['ssid'] = ($array['ssid'] == '' ? '[Blank SSID]' : $array['ssid']);

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

    switch(strtolower($array['radio']))
    {
        case "a":
            $radios="802.11a";
            break;
        case "b":
            $radios="802.11b";
            break;
        case "g":
            $radios="802.11g";
            break;
        case "n":
            $radios="802.11n";
            break;
        default:
            $radios="Unknown Radio";
            break;
    }
    $wifidb_aps_all[$n]['radio'] = $radios;
    $n++;
}

$dbcore->GeneratePages($total_rows, $inputs['from'], $inputs['to'], $inputs['sort'], $inputs['ord']);
$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
$dbcore->smarty->assign('wifidb_aps_all', $wifidb_aps_all);
$dbcore->smarty->smarty->display('all_aps.tpl');
?>