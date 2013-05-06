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
$startdate = "14-10-2009";
$lastedit  = "05-05-2013";

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "graph");

include('../lib/init.inc.php');
$apdata = array(
    "ssid" => $_POST['ssid'],
    "mac" => $_POST['mac'],
    "man" => $_POST['man'],
    "auth" => $_POST['auth'],
    "encry" => $_POST['encry'],
    "radio" => $_POST['radio'],
    "chan" => $_POST['chan'],
    "lat" => $_POST['lat'],
    "long" => $_POST['long'],
    "btx" => $_POST['btx'],
    "otx" => $_POST['otx'],
    "fa" => $_POST['fa'],
    "lu" => $_POST['lu'],
    "nt" => $_POST['nt'],
    "label" => $_POST['label'],
    "sig" => $_POST['sig'],
    "name" => $_POST['name'],
    "bgc" => $_POST['bgc'],
    "linec" => $_POST['linec'],
    "text" => $_POST['text']
);

$ssid = $_POST['ssid'];
$mac = $_POST['mac'];
$man = $_POST['man'];
$auth = $_POST['auth'];
$encry = $_POST['encry'];
$radio = $_POST['radio'];
$chan = $_POST['chan'];
$lat = $_POST['lat'];
$long = $_POST['long'];
$btx = $_POST['btx'];
$otx = $_POST['otx'];
$fa = $_POST['fa'];
$lu = $_POST['lu'];
$nt = $_POST['nt'];
$label = $_POST['label'];
$sig = $_POST['sig'];
$name = $_POST['name'];
$bgc = $_POST['bgc'];
$linec = $_POST['linec'];
$text = $_POST['text'];

if($_POST['line'] === 'line')
{
	$ret = $dbcore->graphs->wifigraphline( $apdata );
}else
{
	$ret = $dbcore->graphs->wifigraphbar( $apdata );
}
$dbcore->smarty->assign("AP_data", $apdata);
$dbcore->smarty->assign("graph_ret", $ret);
$dbcore->smarty->display("graph_results.tpl");
