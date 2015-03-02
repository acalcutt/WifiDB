#!/usr/bin/php
<?php
/*
GenerateBoardersFromKML.php, WiFiDB Import Daemon
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

$lastedit  = "2015-02-28";
echo "
##########################################################
##       Start Gathering of Boarder data from KML       ##
##########################################################
";
$dbcore->verbose = 1;

$files = array("../kml/US_States.kml", "../kml/Countries_full.kml");
foreach ($files as $filename)
{
    $xml = simplexml_load_file($filename);
    #var_dump($xml);
    $AlphabetSoup = $xml->Folder->children();
    foreach ($AlphabetSoup as $letter)
    {
        echo "Letter Folder: ".$letter->name."\n";
        foreach($letter->children() as $country)
        {
            if(@$country->name)
            {
                echo "\t".$country->name."\n";
            }
            if(@$country->Polygon)
            {
                echo "\t\tPolygon!\n";
                $coordinates = $country->Polygon->outerBoundaryIs->LinearRing->coordinates;
                $name = $country->name;
                $sql = "INSERT INTO `wifi`.`boundaries` (id, `name`, `polygon`) VALUES ('', '$name', '$coordinates')";
                $dbcore->sql->conn->query($sql);
                if($dbcore->sql->conn->errorCode() != "00000")
                {
                    var_dump($dbcore->sql->conn->errorInfo());
                }
            }
            if(@$country->MultiGeometry)
            {
                echo "\t\tMultiGeometry!\n";
                foreach($country->MultiGeometry->children() as $key=>$polygon)
                {
                    echo "\t\t\t".$key."\n";
                    $coordinates = $country->Polygon->outerBoundaryIs->LinearRing->coordinates;
                    $name = $country->name;
                    $sql = "INSERT INTO `wifi`.`boundaries` (id, `name`, `polygon`) VALUES ('', '$name', '$coordinates')";
                    $dbcore->sql->conn->query($sql);
                    if($dbcore->sql->conn->errorCode() != "00000")
                    {
                        var_dump($dbcore->sql->conn->errorInfo());
                    }
                }
            }
        }
    }
}