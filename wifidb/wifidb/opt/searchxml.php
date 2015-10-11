<?php
/*
searchxml.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");
require "../lib/init.inc.php";

$ssid = filter_input(INPUT_GET, 'ssid', FILTER_SANITIZE_STRING);
$mac = filter_input(INPUT_GET, 'mac', FILTER_SANITIZE_STRING);
$auth = filter_input(INPUT_GET, 'auth', FILTER_SANITIZE_STRING);
$encry = filter_input(INPUT_GET, 'encry', FILTER_SANITIZE_STRING);
$radio = filter_input(INPUT_GET, 'radfio', FILTER_SANITIZE_STRING);
$chan = filter_input(INPUT_GET, 'chan', FILTER_SANITIZE_NUMBER_INT);

if($ssid == "" && $mac == "" && $auth == "" && $encry == "" && $radio == "" && $chan == ""){die();}

if($ssid != ""){$sql_a[] = "`ssid` like '$ssid%'";}
if($mac != ""){$sql_a[] = "`mac` like '%$mac%'";}
if($auth != ""){$sql_a[] = "`auth` like '$auth%'";}
if($encry != ""){$sql_a[] = "`encry` like '$encry%'";}
if($radio != ""){$sql_a[] = "`radio` like '$radio%'";}
if($chan != ""){$sql_a[] = "`chan` like '%$chan%'";}

$waps = array();
$sql_imp = implode(" AND ", $sql_a);
$sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE ".$sql_imp." ORDER by `ssid` ASC LIMIT 0,15";
$result = $dbcore->sql->conn->query($sql);
while($obj = $result->fetch(2))
{
    $waps[] = $obj;
}

// prepare xml data
if(sizeof($waps) != 0)
{
    header('Content-type: text/xml');
    echo "<waps>\r\n";
    foreach($waps as $result) {
        #  var_dump($result);
        echo "\t<ap>\r\n";
        echo "\t\t<id>" . $result['id'] . "</id>\r\n";
        echo "\t\t<ssid>" . $result['ssid'] . "</ssid>\r\n";
        echo "\t\t<mac>" . $result['mac'] . "</mac>\r\n";
        echo "\t\t<auth>" . $result['auth'] . "</auth>\r\n";
        echo "\t\t<encry>" . $result['encry'] . "</encry>\r\n";
        echo "\t\t<radio>" . $result['radio'] . "</radio>\r\n";
        echo "\t\t<chan>" . $result['chan'] . "</chan>\r\n";
        echo "\t</ap>\r\n";
    }
    echo "</waps>\r\n";
}