<?php
/*
live.php, The Live AP import API
Copyright (C) 2013 Phil Ferland

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
define("SWITCH_SCREEN", "API");
define("SWITCH_EXTRAS", "live");
define("WDB_DEBUG", 1);

include('../lib/init.inc.php');
if(isset($_REQUEST['LiveVersion']))
{
    $dbcore->mesg['version'] = "2.0";
    $dbcore->output();
}

$session_id   =   (isset($_REQUEST['SessionID']) ? $_REQUEST['SessionID'] : "" );
if($session_id === "")
{
    $dbcore->ManageLiveSession();
    $dbcore->output();
}

// AP Detail Variables
$ssid   =   (@$_REQUEST['SSID'] ? html_entity_decode($_REQUEST['SSID'], ENT_QUOTES) : "");
$mac    =   (@$_REQUEST['Mac'] ? $_REQUEST['Mac'] : "000000000000");
$radio  =   (@$_REQUEST['Rad'] ? $_REQUEST['Rad'] : "802.11u");
$sectype=   (@$_REQUEST['SecType'] ? $_REQUEST['SecType'] : 0);
$chan   =   (@$_REQUEST['Chn'] ? $_REQUEST['Chn'] : 0);
//Other AP Info
$auth   =   (@$_REQUEST['Auth'] ? html_entity_decode($_REQUEST['Auth'], ENT_QUOTES) : "Open");
$encry  =   (@$_REQUEST['Encry'] ? html_entity_decode($_REQUEST['Encry'], ENT_QUOTES) : "None");
$BTx    =   (@$_REQUEST['BTx'] ? html_entity_decode($_REQUEST['BTx'], ENT_QUOTES) : "");
$OTx    =   (@$_REQUEST['OTx'] ? html_entity_decode($_REQUEST['OTx'], ENT_QUOTES) : "");
$NT     =   (@$_REQUEST['NT'] ? $_REQUEST['NT'] : "Unknown");
$label  =   (@$_REQUEST['Label'] ? html_entity_decode($_REQUEST['Label'], ENT_QUOTES) : "No Label");
$sig    =   (@$_REQUEST['Sig'] ? $_REQUEST['Sig'] : "0");
$rssi    =   (@$_REQUEST['RSSI'] ? $_REQUEST['RSSI'] : "-0");

// GPS Variables
$lat    =   str_replace("0000.0000","0.0000",(@$_REQUEST['Lat'] ? html_entity_decode($_REQUEST['Lat'], ENT_QUOTES) : "0.0000"));
$long   =   str_replace("0000.0000","0.0000",(@$_REQUEST['Long'] ? html_entity_decode($_REQUEST['Long'], ENT_QUOTES) : "0.0000"));
$sats   =   (@$_REQUEST['Sats'] ? $_REQUEST['Sats'] : 0 );
$hdp    =   (@$_REQUEST['HDP'] ? $_REQUEST['HDP'] : 0 );
$alt    =   (@$_REQUEST['ALT'] ? $_REQUEST['ALT'] : 0 );
$geo    =   (@$_REQUEST['GEO'] ? $_REQUEST['GEO'] : 0 );
$kmh    =   (@$_REQUEST['KMH'] ? $_REQUEST['KMH'] : 0 );
$mph    =   (@$_REQUEST['MPH'] ? $_REQUEST['MPH'] : 0 );
$track  =   (@$_REQUEST['Track'] ? $_REQUEST['Track'] : 0 );
$date   =   (@$_REQUEST['Date'] ? $_REQUEST['Date'] : date("Y-m-d") );
$time   =   (@$_REQUEST['Time'] ? $_REQUEST['Time'] : date("H:i:s") );
$username   =   (@$_REQUEST['username'] ? $_REQUEST['username'] : "Unknown" );
$session_id   =   (@$_REQUEST['SessionID'] ? $_REQUEST['SessionID'] : "" );
if($session_id === "")
{
    $dbcore->mesg['message'] = "Session ID is blank :/";
} 
echo $session_id;
if($ssid == "" && $mac == "000000000000" && $radio == "802.11u" && $sectype == 0 && $chan == 0 && $auth == "Open" && $encry == "None" && $BTx == "" && $OTX == "" && $NT == "Unknown" && $sig == "0" && $rssi == "-0")
{
    $dbcore->mesg[] = array("error"=>"You have not supplied any data.. you can't be a computer... shoo, go away.");
    $dbcore->Output();
}

$data = array(
    #ap data
    'ssid'=>$ssid,
    'mac'=>$mac,
    'chan'=>$chan,
    'radio'=>$radio,
    'sectype'=>$sectype,
    'auth'=>$auth,
    'encry'=>$encry,
    'NT'=>$NT,
    'BTx'=>$BTx,
    'OTx'=>$OTx,
    'label'=>$label,
    'sig'=>$sig,
    'rssi'=>$rssi,
    
    #gps data
    'lat'=>$lat,
    'long'=>$long,
    'sats'=>$sats,
    'hdp'=>$hdp,
    'kmh'=>$kmh,
    'mph'=>$mph,
    'alt'=>$alt,
    'geo'=>$geo,
    'track'=>$track,
    'date'=>$date,
    'time'=>$time,
    
    #user data
    #'username'=>$dbcore->username,
	'username'=>$username,
    'session_id'=>$session_id
);

$dbcore->InsertLiveAP($data);
$dbcore->Output();
