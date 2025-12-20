<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require('../daemon/config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

error_reporting(E_ALL && E_STRICT);
date_default_timezone_set('EST');
$placemarks = '';
$points = array();

$sql = "SELECT * FROM `wifi`.`live_gps` WHERE `lat` != 'N 0000.0000' AND `lat` != '' ORDER BY `date` ASC, `time` ASC";

$result = $dbcore->sql->conn->query($sql);
$prev_lat = "";
$prev_long = "";

$prev_time = "";
$N = 1;
while($fetch = $result->fetch_object())
{
    $lat_exp = explode(" ", $fetch->lat);
    $long_exp = explode(" ", $fetch->long);
    
    $lat_test = $lat_exp[1];
    $long_test = $long_exp[1];
    
    $lat_calc = abs($lat_test - $prev_lat);
    $long_calc = abs($long_test - $prev_long);
    
    var_dump($fetch->date, $fetch->time);
    $time_exp = explode(".", $fetch->time);
    $time = strtotime($fetch->date." ".$time_exp[0]);
    $time_calc = abs($time-$prev_time);
    var_dump($time_calc, $lat_calc, $long_calc);
    
    #if( $time_calc > 3600)
    if($lat_calc > 40 || $long_calc > 40)# || $time_calc > 3600)
    {
        echo "*************************************************\r\n*************************************************\r\nNEW LINE!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!\r\n*************************************************\r\n*************************************************\r\n";
        #end previsous placemark, and start new one
        $lat = database::convert_dm_dd($fetch->lat);
        $long = database::convert_dm_dd($fetch->long);
        $line_points = implode(" ", $points);
        $placemarks .= '
        <Placemark>
		<name>'.$N.'</name>
		<styleUrl>#style</styleUrl>
		<LineString>
			<tessellate>1</tessellate>
			<coordinates>
				'.$line_points.'
			</coordinates>
		</LineString>
	</Placemark>
        <Placemark>
                <name>'.$N.' Start</name>
                <description>
                    <![CDATA[Date:<b>'.$fetch->date.'</b></br>Time: <b>'.$fetch->time.'</b>]]>
                </description>
                <styleUrl>#openStyleDead</styleUrl>
                <Point id="'.$N.'">
                    <coordinates>'.$long.','.$lat.','.$fetch->alt.'</coordinates>
                </Point>
        </Placemark>';
        unset($points);
        $points = array();
        $N++;
        $points[] = $long.",".$lat.",".$fetch->alt;
    }else
    {
        #continue on with the placemark.
        $lat = database::convert_dm_dd($fetch->lat);
        $long = database::convert_dm_dd($fetch->long);
        $points[] = $long.",".$lat.",".$fetch->alt;
    }
    $prev_time = $time;
    $prev_lat = $lat_test;
    $prev_long = $long_test;
    echo "---------------------------\r\n";
}

$kml_data = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Document>
	<name>Untitled Path.kml</name>
        <Style id="style">
		<LineStyle>
			<color>ff0000ff</color>
			<width>3</width>
		</LineStyle>
	</Style>
        <Style id="openStyleDead">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>http://sourceforge.net/p/vistumbler/code/948/tree/VistumblerMDB/Images/open.png?format=raw</href>
                </Icon>
            </IconStyle>
        </Style>'.$placemarks.'
</Document>
</kml>';
file_put_contents("gps_line_test.kml", $kml_data);


?>