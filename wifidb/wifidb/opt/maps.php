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

include('../lib/init.inc.php');

$n=0;
$nn=0;
$maps_compile_a = array();
$sql = "SELECT id, ssid, mac, auth, encry, chan, radio, sectype FROM `$db`.`$wtable`";
$result = mysql_query($sql, $conn);
while($array = mysql_fetch_assoc($result))
{
    #var_dump($array);
    echo $array['id']."\r\n";
    $ssid = $array['ssid'];
    $macaddress = $array['mac'];
    $mac = str_split($macaddress,2);
    $mac_full = implode(":", $mac);
    $radio = $array['radio'];
    list($ssid_ptb) = make_ssid($array["ssid"]);
    $table  = $ssid_ptb.'-'.$array["mac"].'-'.$array["sectype"].'-'.$array["radio"].'-'.$array['chan'].$gps_ext;
    #echo $table_gps."\r\n";
    $sql1 = "SELECT * FROM `$db_st`.`$table` WHERE `lat`!= 'N 0000.0000' ORDER BY `date` DESC, `sats` DESC LIMIT 1";
    #echo $sql1."\r\n";
    #die();
    $result1 = mysql_query($sql1, $conn);
    echo mysql_error($conn);
    echo mysql_num_rows($result1)."\r\n";
    
    if(mysql_num_rows($result1) < 1){$nn++;continue;}
    $array_gps = mysql_fetch_array($result1);
    $lat = @database::convert_dm_dd($array_gps['lat']);
    $long = @database::convert_dm_dd($array_gps['long']);
    if($lat == "0"){$nn++;continue;}
    echo $lat." - ".$long."\r\n";
    
    switch($array['sectype'])
    {
        case 1:
            $img = "open";
            break;
        case 2:
            $img = "wep";
            break;
        case 3:
            $img = "secure";
            break;
    }
    $maps_compile_a[] = "
                       var myLatLng$n = new google.maps.LatLng($lat, $long);
                       var beachMarker$n = new google.maps.Marker({position: myLatLng$n, map: map, icon: $img, title: \"$ssid\"});\r\n";
    $n++;
}
#var_dump($maps_compile_a);
$maps_compile = implode("", $maps_compile_a);
#exit();

$body = '<html>
    <head>
        <meta name="viewport" content="initial-scale=1.0, user-scalable=yes" />
        <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
        <link href="https://code.google.com/apis/maps/documentation/javascript/examples/default.css" rel="stylesheet" type="text/css" />
        <script type="text/javascript" src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
        <style type="text/css">
            .maps_label {background-color:#ffffff;font-weight:bold;border:2px #006699 solid;}
        </style>
        <script type="text/javascript">
            function initialize()
            {
                var myOptions = {
                  zoom: 16,
                  center: new google.maps.LatLng(<?php echo $lat; ?>, <?php echo $long; ?>),
                  mapTypeId: google.maps.MapTypeId.ROADMAP
                }
                var open = \'http://vistumbler.sourceforge.net/images/program-images/open.png\';
                var wep = \'http://vistumbler.sourceforge.net/images/program-images/secure-wep.png\';
                var secure = \'http://vistumbler.sourceforge.net/images/program-images/secure.png\';
                var map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
                '.$maps_compile.'
                }
            window.onload = initialize;
        </script>
    </head>
    <body>
        <div style="width:100%;height:100%;" id="map_canvas"></div>
    </body>
</html>';


file_put_contents('../out/maps.html', $body);

$ft_stop = microtime(1);
echo $ft_stop-$ft_start."\r\n";
echo $n."\r\n".$nn;


?>