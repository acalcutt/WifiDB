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
define("SWITCH_EXTRAS", "api");

require '../lib/init.inc.php';

// Collect request params; allow client to supply '%' wildcards explicitly.
$ssid   = isset($_REQUEST['ssid']) ? $_REQUEST['ssid'] : "";
$mac    = isset($_REQUEST['mac']) ? $_REQUEST['mac'] : "";
$radio  = isset($_REQUEST['radio']) ? $_REQUEST['radio'] : "";
$chan   = isset($_REQUEST['chan']) ? $_REQUEST['chan'] : "";
$auth   = isset($_REQUEST['auth']) ? $_REQUEST['auth'] : "";
$encry  = isset($_REQUEST['encry']) ? $_REQUEST['encry'] : "";

# optional paging parameters
$from = (isset($_REQUEST['from']) ? (int)$_REQUEST['from'] : 0);
$inc = (isset($_REQUEST['inc']) ? (int)$_REQUEST['inc'] : 50000);
# optional gps-only flag: when true, only return APs with associated GPS (HighGps_ID)
$gpsonly = false;
if (isset($_REQUEST['gpsonly'])) {
    $v = $_REQUEST['gpsonly'];
    if ($v === '1' || strtolower($v) === 'true') { $gpsonly = true; }
}

$dbcore->search($ssid, $mac, $radio, $chan, $auth, $encry, $from, $inc, $gpsonly);
$dbcore->Output();
?>
