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

include('../lib/init.inc.php');

$dbcore->smarty->assign('wifidb_host_url', $GLOBALS['hosturl'].$root."/");
$dbcore->smarty->assign('wifidb_version_label', 'wifidb_version_label');
$dbcore->smarty->assign('wifidb_page_label', "Live Access Point Page");
$live_aps   =   'live_aps';
$out        =   filter_input(INPUT_GET, 'out', FILTER_SANITIZE_STRING, array(16,32));
$ord        =   (@$ord ? filter_input('INPUT_GET', 'ord', FILTER_SANITIZE_STRING, array(16,32)) : "ASC");
$sort       =   (@$sort ? filter_input('INPUT_GET', 'sort', FILTER_SANITIZE_STRING, array(16,32)) : "id");
$from       =   (@$from ? filter_input('INPUT_GET', 'from', FILTER_SANITIZE_NUMBER_INT) : 0);
$from_      =   $from;
$inc        =   (@$inc ? filter_input('INPUT_GET', 'to', FILTER_SANITIZE_NUMBER_INT) : 100);
$list       =   array();
$conn = new mysqli($host, $db_user, $db_pwd);
$sql = "SELECT * FROM `$db`.`$live_aps` ORDER BY `$sort` $ord LIMIT $from, $inc";
$result = $conn->query($sql) or die($conn->error);
$sql = "SELECT count(*) FROM `$db`.`$live_aps`";
$result1 = $conn->query($sql) or die($conn->error);
$total_rows = $result1->num_rows;
$result1->free();
if($total_rows != 0)
{
    $row_color = 0;
    while ($newArray = $result->fetch_array(1))
    {
        if($row_color == 1)
        {$row_color = 0; $color = "light";}
        else{$row_color = 1; $color = "dark";}
        $mac = $newArray['mac'];
        $mac_exp = str_split($mac,2);
        $mac = implode(":",$mac_exp);
        $radio = $newArray['radio'];
        if($radio=="a")
        {$radio="802.11a";}
        elseif($radio=="b")
        {$radio="802.11b";}
        elseif($radio=="g")
        {$radio="802.11g";}
        elseif($radio=="n")
        {$radio="802.11n";}
        else
        {$radio="Unknown Radio";}
        switch($newArray['sectype'])
        {
            case 1:
                $type = "open";
                break;
            case 2:
                $type = "wep";
                break;
            case 3:
                $type = "secure";
                break;
        }
        $list[] = array(
            'color'     =>  $color,
            'id'        =>  $newArray['id'],
            'ssid'      =>  $newArray['ssid'],
            'mac'       =>  $newArray['mac'],
            'manuf'     =>  manufactures($newArray['mac']),
            'auth'      =>  $newArray['auth'],
            'encry'     =>  $newArray['encry'],
            'radio'     =>  $radio,
            'chan'      =>  $newArray['chan'],
            'btx'       =>  $newArray['BTx'],
            'otx'       =>  $newArray['OTx'],
            'NT'        =>  $newArray['NT'],
            'lat'       =>  convert_dm_dd($newArray['lat']),
            'long'      =>  convert_dm_dd($newArray['long']),
            'label'     =>  $newArray['Label'],
            'type'      =>  $type,
            'LA'        =>  date("Y-m-d H:i:s", $newArray['LA'])
        );
    }
}else
{
    $list = "";
}
$dbcore->smarty->assign('live', $list);

switch($out)
{
    case "kml":
        header('Content-type: text/xml');
        $dbcore->smarty->display('live_kml.tpl');
        break;
    default:
        $dbcore->smarty->display('live_list.tpl');
        break;
}
#echo "Total Memory Usage: ".format_size(memory_get_usage(1))."<br />";



function format_size($size, $round = 2)
{
    //Size must be bytes!

    $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

    for ($i=0; $size > 1024 && $i < (count($sizes)-1); $i++)
    {
        $size = $size/1024;
    }
    #echo $size."<BR>";
    return round($size,$round).$sizes[$i];
}

function manufactures($mac="")
{
    include '../lib/manufactures.inc.php';
    if(count(explode(":", $mac)) > 1)
    {
        $mac = str_replace(":", "", $mac);
    }
    $man_mac = str_split($mac,6);
    if(isset($manufactures[$man_mac[0]]))
    {
        $manuf = $manufactures[$man_mac[0]];
    }
    else
    {
        $manuf = "Unknown Manufacture";
    }
    return $manuf;
}
function convert_dm_dd($geocord_in = "")
{
        $start = microtime(true);
//	GPS Convertion :
        $neg=FALSE;
        $geocord_exp = explode(".", $geocord_in);//replace any Letter Headings with Numeric Headings
        $geocord_front = explode(" ", $geocord_exp[0]);
        if($geocord_exp[0][0] === "S" or $geocord_exp[0][0] === "W"){$neg = TRUE;}
        $patterns[0] = '/N /';
        $patterns[1] = '/E /';
        $patterns[2] = '/S /';
        $patterns[3] = '/W /';
        $replacements = "";
        $geocord_in = preg_replace($patterns, $replacements, $geocord_in);
        $geocord_exp = explode(".", $geocord_in);
        if($geocord_exp[0][0] === "-"){$geocord_exp[0] = 0 - $geocord_exp[0];$neg = TRUE;}


        // 428.7753 ---- 428 - 7753
        $geocord_dec = "0.".$geocord_exp[1];
        // 428.7753 ---- 428 - 0.7753
        $len = strlen($geocord_exp[0]);
#		echo $len.'<BR>';
        $geocord_min = substr($geocord_exp[0],-2,3);
#		echo $geocord_min.'<BR>';
        // 428.7753 ---- 4 - 28 - 0.7753
        $geocord_min = $geocord_min+$geocord_dec;
        // 428.7753 ---- 4 - 28.7753
        $geocord_div = $geocord_min/60;
        // 428.7753 ---- 4 - (28.7753)/60 = 0.4795883
        if($len == 3)
        {
                $geocord_deg = substr($geocord_exp[0], 0,1);
#			echo $geocord_deg.'<br>';
        }elseif($len == 4)
        {
                $geocord_deg = substr($geocord_exp[0], 0,2);
#			echo $geocord_deg.'<br>';
        }elseif($len == 5)
        {
                $geocord_deg = substr($geocord_exp[0], 0,3);
#			echo $geocord_deg.'<br>';
        }elseif($len <= 2)
        {
                $geocord_deg = 0;
#			echo $geocord_deg.'<br>';
        }
        if(!isset($geocord_deg))
        {
            echo $geocord_in."\r\n";
            return -1;
        }
        $geocord_out = $geocord_deg + $geocord_div;
        // 428.7753 ---- 4.4795883
        if($neg === TRUE){$geocord_out = "-".$geocord_out;}
        $end = microtime(true);

        $geocord_out = substr($geocord_out, 0,10);
        return $geocord_out;
}
?>