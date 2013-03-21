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
global $switches;
$switches = array('screen'=>"HTML",'extras'=>'API');
include('../lib/init.inc.php');

// AP Detail Variables
$ssid   =   (@$_GET['SSID'] ? html_entity_decode($_GET['SSID'], ENT_QUOTES) : "UNAMED");
$mac    =   (@$_GET['Mac'] ? $_GET['Mac'] : "00:00:00:00:00:00");
$radio  =   (@$_GET['Rad'] ? $_GET['Rad'] : "802.11u");
$sectype=   (@$_GET['SecType'] ? $_GET['SecType'] : 0);
$chan   =   (@$_GET['Chn'] ? $_GET['Chn'] : 0);
//Other AP Info
$auth   =   (@$_GET['Auth'] ? html_entity_decode($_GET['Auth'], ENT_QUOTES) : "Open");
$encry  =   (@$_GET['Encry'] ? html_entity_decode($_GET['Encry'], ENT_QUOTES) : "None");
$BTx    =   (@$_GET['BTx'] ? html_entity_decode($_GET['BTx'], ENT_QUOTES) : "0.0");
$OTx    =   (@$_GET['OTx'] ? html_entity_decode($_GET['OTx'], ENT_QUOTES) : "0.0");
$NT     =   (@$_GET['NT'] ? $_GET['NT'] : "Unknown");
$label  =   (@$_GET['Label'] ? html_entity_decode($_GET['Label'], ENT_QUOTES) : "No Label");
$sig    =   (@$_GET['Sig'] ? $_GET['Sig'] : "0");

// GPS Variables
$lat    =   (@$_GET['Lat'] ? html_entity_decode($_GET['Lat'], ENT_QUOTES) : "N 0000.0000");
$long   =   (@$_GET['Long'] ? html_entity_decode($_GET['Long'], ENT_QUOTES) : "E 0000.0000");
$sats   =   (@$_GET['Sats'] ? $_GET['Sats'] : 0 );
$hdp    =   (@$_GET['HDP'] ? $_GET['HDP'] : 0 );
$alt    =   (@$_GET['ALT'] ? $_GET['ALT'] : 0 );
$geo    =   (@$_GET['GEO'] ? $_GET['GEO'] : 0 );
$kmh    =   (@$_GET['KMH'] ? $_GET['KMH'] : 0 );
$mph    =   (@$_GET['MPH'] ? $_GET['MPH'] : 0 );
$track  =   (@$_GET['Track'] ? $_GET['Track'] : 0 );
$date   =   (@$_GET['Date'] ? $_GET['Date'] : date("Y-m-d") );
$time   =   (@$_GET['Time'] ? $_GET['Time'] : date("H:i:s") );
$utime = time();

//Username, API Key, Session ID
$username   =   (@$_GET['username'] ? $_GET['username'] : die("Unauthorized User.</br>Please register and get an API Key.") );
$apikey     =   (@$_GET['apikey'] ? $_GET['apikey'] : die("API Key not supplied."));
$session_id =   (@$_GET['SessionID'] ? $_GET['SessionID'] : "");
$dbcore->output        =   (@$_GET['output'] ? strtolower($_GET['output']) : "json");
if($ssid === "UNNAMED" && $mac === "00:00:00:00:00:00" && $chan === 0 && $sectype === 0)
{
    $this->Dump("You seem to have gotten here accidently or your Access Point does not have enough unique information to be added to the database.");
}

//Lets see if we can find a user with this name.
//If so, lets check to see if the API key they provided is correct.
$key_result = $dbcore->sec->ValidateAPIKey($username, $apikey);
if($key_result !== 1){ $dbcore->Dump($key_result); }

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
    'utime'=>$utime,
    
    #user data
    'username'=>$username,
    'session_id'=>$session_id
);

$dbcore->InsertLiveAP($data);
$dbcore->Output();
?>