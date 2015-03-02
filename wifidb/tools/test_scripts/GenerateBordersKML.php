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

$results = $dbcore->sql->conn->query("SELECT `id`, `name`, `polygon` FROM wifi.boundaries");
$Items = $results->fetchAll(2);

$regions = array();

foreach ($Items as $bounds)
{
    echo $bounds['name']."\n";

    $points = explode(" ", $bounds['polygon']);
    $North = NULL;
    $South = NULL;
    $East = NULL;
    $West = NULL;
    $regions[$bounds['id']] = array( 'name' => $bounds['name'], 'polygon' => $bounds['polygon']);

    foreach($points as $point)
    {
        $elements = explode(",", $point);
        #var_dump($elements);
        if($elements[1] == '')
        {
            continue;
        }
        if($North == NULL)
        {
            $North = $elements[1];
        }
        if($South == NULL)
        {
        #    var_dump($elements);
            $South = $elements[1];
        }

        if($East == NULL)
        {
            $East = $elements[0];
        }
        if($West == NULL)
        {
        #    var_dump($elements);
            $West = $elements[0];
        }
        #var_dump((float)$HighLat,
        #    (float)$LowLat,
        #    (float)$HighLong,
        #    (float)$LowLat);
        #######
        #######
        #echo "Highlat: $HighLat < $elements[1]\n";
        if((float)$North < (float)$elements[1])
        {
            $North = $elements[1];
        }
        #echo "LowLat: $LowLat > $elements[1]\n";
        if((float)$South > (float)$elements[1])
        {
            $South = $elements[1];
        }

        #echo "HighLong: $HighLong < $elements[0]\n";
        if((float)$East < (float)$elements[0])
        {
            $East = $elements[0];
        }
        #echo "LowLong: $LowLong > $elements[0]\n";
        if((float)$West > (float)$elements[0])
        {
            $West = $elements[0];
        }
    }

#    var_dump(array( $North, $South, $East, $West));

    $regions[$bounds['id']['RegionBox']] = array( $North, $South, $East, $West );
}

