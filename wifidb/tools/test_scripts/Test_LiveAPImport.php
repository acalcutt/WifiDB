#!/usr/bin/php
<?php
/*
Test_LiveAPImport.php, WiFiDB Import Daemon
Copyright (C) 2015 Phil Ferland.
Used to test the Live AP API import page.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/

$n = 25;
$authens = array("Open", "WPA-Personal", "WPA2-Personal", "WPA-Enterprise", "WPA2-Enterprise");
$encrys = array("WEP", "TKIP", "CCMP-AES", "PSK");
$radios = array("802.11a", "802.11b","802.11g","802.11n");
$lats = array('42.35837', '42.35847', '42.35857','42.35867', '42.35877', '42.35887', '42.35897', '42.35937', '42.35947', '42.35957', '42.35967', '42.35977', '42.35987', '42.35997', '42.36037', '42.35937');
$longs = array('-71.94303', '-71.94313', '-71.94323', '-71.94333', '-71.94343', '-71.94353', '-71.94363', '-71.94373', '-71.94383', '-71.94393', '-71.94403', '-71.94413', '-71.94423', '-71.94433', '-71.94443');

while($n !== 0)
{
    echo "----------------------------------------\r\nID: $n\r\n";
    $url_data = "SSID=".urlencode(gen_str(rand(5, 32))).
    "&Mac=".urlencode(gen_mac()).
    "&Auth=".array_rand($authens).
    "&SecType=".rand(1,3).
    "&Encry=".array_rand($encrys).
    "&Rad=".array_rand($radios).
    "&Chn=".rand(1,11).
    "&Lat=".urlencode(convert_dd_dm($lats[array_rand($lats)])).
    "&Long=".urlencode(convert_dd_dm($longs[array_rand($longs)])).
    "&BTx=5.5".
    "&OTx=".urlencode("1 2 5.5 10 20 30 48 54").
    "&Time=".time().
    "&NT=Infrastructure".
    "&Label=unknown".
    "&Sig=".rand(9, 100).
    "&Sats=".rand(1,9).
    "&HDP=".rand(1,100).
    "&ALT=".rand(500,900).
    "&GEO=".rand(-180,180).
    "&KMH=".rand(40,120).
    "&MPH=".rand(25,75).
    "&Track=".rand(0,100).
    "&username=pferland".
    "&apikey=".urlencode("9e%m)KW6dn3fjb3G(6!7A7OFAqDy*DLt4!tGq").
    "&SessionID=SessionID";
    $url = "http://172.16.1.77/wifidb/api/live.php?".$url_data;
    echo $url."\r\n";
    var_dump(file($url));
    die();
    $n--;
}
echo "----------------------------------------\r\n";

function gen_str($len = 10)
{
    $base           =   'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
    $max            =   strlen($base)-1;
    $key            =   '';
    mt_srand((double)microtime()*1000000);
    while(strlen($key) < $len+1)
    {$key.=$base{mt_rand(0,$max)};}
    return $key;
}

function gen_mac()
{
    $base           =   'ABCDEF123456789';
    $max            =   strlen($base)-1;
    $key            =   '';
    mt_srand((double)microtime()*1000000);
    while(strlen($key) < 13)
    {$key.=$base{mt_rand(0,$max)};}
    return $key;
}

function &convert_dd_dm($geocord_in="")
{
    #echo "----------------\r\n";
    $start = microtime(true);
    //	GPS Convertion :
    #echo "1) $geocord_in\r\n";
    $neg=FALSE;
    $geocord_exp = explode(".", $geocord_in);

    $front = $geocord_exp[0];
    $back = $geocord_exp[1];

    if(substr($front,0,1)=="-" || substr($front,0,1)=="S" || substr($front,0,1)=="W"){$neg = TRUE;}
    #echo "NEG: $neg\r\n";

    $pattern[0] = '/-/';
    $pattern[1] = '/ /';
    $pattern[2] = '/N/';
    $pattern[3] = '/E/';
    $pattern[4] = '/W/';
    $pattern[5] = '/S/';
    $replacements = "";
    $front = preg_replace($pattern, $replacements, $front);
    #echo "2) $front....$back\r\n";
    // 4.146255 ---- 4 - 146255
#		echo $geocord_exp[1].'<br>';
    $geocord_dec = "0.".$back;
    // 4.146255 ---- 4 - 0.146255
    #echo "Dec: $geocord_dec\r\n";
    $geocord_mult = $geocord_dec*60;
    // 4.146255 ---- 4 - (0.146255)*60 = 8.7753
    #echo "Milt: $geocord_mult\r\n";
    $mult = explode(".",$geocord_mult);
    $len = strlen($mult[0]);
    #echo "Len: $len\r\n";
    if( $len < 2 )
    {
        $geocord_mult = "0".$geocord_mult;
    }
    // 4.146255 ---- 4 - 08.7753

    $geocord_out = $front.$geocord_mult;
    #echo "3.1) $geocord_out\r\n";

    // 4.146255 ---- 408.7753
    $geocord_o = explode(".", $geocord_out);
    if( strlen($geocord_o[1]) > 4 )
    {
        $geocord_o[1] = substr($geocord_o[1], 0 , 4);
        $geocord_out = implode('.', $geocord_o);
    }
    #echo "3.2) $geocord_out\r\n";

    if($neg === TRUE){$geocord_out = "-".$geocord_out;}
    #echo "3.3) $geocord_out\r\n";

    $end = microtime(true);
    if ($GLOBALS["bench"]  == 1)
    {
            echo "Time is [Unix Epoc]<BR>";
            echo "Start Time: ".$start."<BR>";
            echo "  End Time: ".$end."<BR>";
    }
    #echo "----------------\r\n";
    return $geocord_out;
}
