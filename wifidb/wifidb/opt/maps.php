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

$n=0;
$nn=0;
$maps_compile_a = array();
$sql = "SELECT id, ssid, mac, auth, encry, chan, radio, sectype, aphash, `lat`, `long` FROM `wifi_pointers` WHERE `id` = ?";
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $_REQUEST['id'], PDO::PARAM_INT);
$dbcore->sql->checkError($prep->execute(), __LINE__, __FILE__);
$fetch = $prep->fetch(2);
if($fetch['lat'] === "0.0000") {
    die("AP Does not have Valid GPS.");
}

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
$map_pointer = "
                   var myLatLng$n = new google.maps.LatLng($lat, $long);
                   var beachMarker$n = new google.maps.Marker({position: myLatLng$n, map: map, icon: $img, title: \"$ssid\"});\r\n";

$dbcore->smarty->assign('map_pointer', $map_pointer);
$dbcore->smarty->assign('lat', $fetch['lat']);
