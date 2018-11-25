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
define("SWITCH_EXTRAS", "cli");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2015-03-02";
echo "
##########################################################
##       Start Gathering of Boarder data from KML       ##
##########################################################
";
$dbcore->verbose = 1;

$files = array("../kml/US_States.kml", "../kml/Countries.kml");
foreach ($files as $filename)
{
    $xml = simplexml_load_file($filename);
    if($filename == "../kml/US_States.kml")
    {
        $StateSoup = $xml->Document->children();
        foreach($StateSoup as $state)
        {
            if($state->name != "")
            {
                echo "\t".$state->name."\n";
            }else
            {
                continue;
            }
            if($state->Polygon)
            {
                echo "\t   Polygon!\n";
                list($North, $South, $East, $West) = FindBox($state->Polygon->outerBoundaryIs->LinearRing->coordinates);

                list($distance_calc, $minLodPix) = distance($North, $East, $South, $West, "K"); # North, East, South, West

                InsertPolygon($dbcore, $state->name, $state->Polygon, "$North, $South, $East, $West", $distance_calc, $minLodPix);
            }
            if($state->MultiGeometry)
            {
                echo "\t   MultiGeometry!\n\t     ";
                $i = 1;
                foreach($state->MultiGeometry->children() as $key=>$polygon)
                {
                    echo $i." ";
                    $i++;
                    list($North, $South, $East, $West) = FindBox($polygon->outerBoundaryIs->LinearRing->coordinates);

                    list($distance_calc, $minLodPix) = distance($North, $East, $South, $West, "K"); # North, East, South, West

                    InsertPolygon($dbcore, $state->name, $polygon, "$North, $South, $East, $West", $distance_calc, $minLodPix);
                }
                echo "\n";
            }
        }
    }else
    {
        $AlphabetSoup = $xml->Folder->children();
        foreach ($AlphabetSoup as $letter)
        {
            if($letter->name == "Labels")
            {continue;}
            echo "Letter Folder: ".$letter->name."\n";
            foreach($letter->children() as $country)
            {
                if(@$country->name)
                {
                    echo "   ".$country->name."\n";
                }
                if(@$country->Polygon)
                {
                    echo "\tPolygon!\n";
                    list($North, $South, $East, $West) = FindBox($country->Polygon->outerBoundaryIs->LinearRing->coordinates);

                    list($distance_calc, $minLodPix) = distance($North, $East, $South, $West, "K"); # North, East, South, West

                    InsertPolygon($dbcore, $country->name, $country->Polygon, "$North, $South, $East, $West", $distance_calc, $minLodPix);
                }
                if(@$country->MultiGeometry)
                {
                    echo "\tMultiGeometry!\n\t";
                    $i = 1;
                    foreach($country->MultiGeometry->children() as $key=>$polygon)
                    {
                        echo $i." ";
                        $i++;
                        list($North, $South, $East, $West) = FindBox($polygon->outerBoundaryIs->LinearRing->coordinates);

                        list($distance_calc, $minLodPix) = distance($North, $East, $South, $West, "K"); # North, East, South, West

                        InsertPolygon($dbcore, $country->name, $polygon, "$North, $South, $East, $West", $distance_calc, $minLodPix);
                    }
                    echo "\n";
                }
            }
        }
    }


}

/**
 * @param $core
 * @param $name
 * @param $polygon
 * @param $box
 * @param $distance
 * @param $minLodPix
 */
function InsertPolygon($core, $name, $polygon, $box, $distance, $minLodPix)
{
    $coordinates = $polygon->outerBoundaryIs->LinearRing->coordinates;
    $sql = "INSERT INTO `boundaries` (id, `name`, `polygon`, `box`, `distance`, `minLodPix`) VALUES ('', '$name', '$coordinates', '$box', '$distance', '$minLodPix')";
    $core->sql->conn->query($sql);
    if($core->sql->conn->errorCode() != "00000")
    {
        var_dump($core->sql->conn->errorInfo());
    }
}



function FindBox($polygon)
{
    $North = NULL;
    $South = NULL;
    $East = NULL;
    $West = NULL;
    $points = explode(" ", $polygon);
    foreach($points as $point)
    {
        $elements = explode(",", trim($point));

        if(@$elements[0] == '' || @$elements[1] == '')
        {
            continue;
        }
        if($North == NULL)
        {
            $North = $elements[1];
        }
        if($South == NULL)
        {
            $South = $elements[1];
        }

        if($East == NULL)
        {
            $East = $elements[0];
        }
        if($West == NULL)
        {
            $West = $elements[0];
        }

        if((float)$North < (float)$elements[1])
        {
            $North = $elements[1];
        }
        if((float)$South > (float)$elements[1])
        {
            $South = $elements[1];
        }
        if((float)$East < (float)$elements[0])
        {
            $East = $elements[0];
        }
        if((float)$West > (float)$elements[0])
        {
            $West = $elements[0];
        }
    }
    #var_dump(array( $North, $South, $East, $West));
    return array( $North, $South, $East, $West);
}

function distance($lat1, $lon1, $lat2, $lon2, $unit)
{
    $theta = $lon1 - $lon2;
    $dist = sin(deg2rad($lat1)) * sin(deg2rad($lat2)) +  cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * cos(deg2rad($theta));
    $dist = acos($dist);
    $dist = rad2deg($dist);
    $miles = $dist * 60 * 1.1515;
    $unit = strtoupper($unit);
    if ($unit == "K") {
        $ret = ($miles * 1.609344);
    }
    elseif ($unit == "N")
    {
        $ret = ($miles * 0.8684);
    }
    else
    {
        $ret = $miles;
    }
    if($ret < 100)
    {
        $distance_calc = 3000;
        $minLodPix = 512;
    }

    if($ret > 100 && $ret < 400)
    {
        $distance_calc = 3000;
        $minLodPix = 768;
    }

    if($ret > 400)
    {
        $distance_calc = 6000;
        $minLodPix = 256;
    }
    return array($distance_calc, $minLodPix);
}
