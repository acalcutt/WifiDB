#!/usr/bin/php
<?php
/*
GenerateBoardersKML.php, WiFiDB Import Daemon
Copyright (C) 2015 Phil Ferland.
Used to generate the boarders data from a KML file.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2015-03-02";

$results = $dbcore->sql->conn->query(" SELECT * FROM wifi.boundaries");
$dbcore->sql->checkError();
$Items = $results->fetchAll(2);

$placemarks = array();
$regions = array();
$i = 0;

foreach ($Items as $bounds)
{
    echo $bounds['name']."\n";
    list($North, $South, $East, $West) = explode(",", $bounds['box']);
	$placemarks[] = '        <Placemark>
            <name>'.$bounds['name'].'</name>
            <styleUrl>#default</styleUrl>
            <Polygon>
                <outerBoundaryIs>
                    <LinearRing>
                        <coordinates>'.$bounds['polygon'].'</coordinates>
                    </LinearRing>
                </outerBoundaryIs>
            </Polygon>
            <Region>
            <LatLonAltBox>
                <north>'.trim($North).'</north>
                <south>'.trim($South).'</south>
                <east>'.trim($East).'</east>
                <west>'.trim($West).'</west>
                <minAltitude>0</minAltitude>
                <maxAltitude>'.$bounds['distance'].'</maxAltitude>
            </LatLonAltBox>
            <Lod>
                <minLodPixels>'.$bounds['minLodPix'].'</minLodPixels>
                <maxLodPixels>-1</maxLodPixels>
                <minFadeExtent>0</minFadeExtent>
                <maxFadeExtent>0</maxFadeExtent>
            </Lod>
            </Region>
        </Placemark>';
    #if($i == 5){break;}else{$i++;}
    echo "\n";
}

####Compile data
$data = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Document>
    <Style id="default">
		<PolyStyle>
			<fill>0</fill>
		</PolyStyle>
	</Style>
    <Folder>
'.implode("\n", $placemarks).'
    </Folder>
</Document>
</kml>';
file_put_contents("test.kml", $data);