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
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');
require_once '../lib/live_session.inc.php';

// Version 2 (lib/live_session.inc.php): sessions that belong to an account, and become its import once finished
$live = new LiveSession($dbcore->sql->conn, $dbcore->sql->service);
if(isset($_REQUEST['LiveVersion']))
{
	$dbcore->Output(array('LiveVersion' => LiveSession::VERSION));
}
$live_session = $live->Find(trim((string)($_REQUEST['SessionID'] ?? '')));
if(isset($_REQUEST['completed']))
{
	if(!$live_session) { $dbcore->Output(array('error' => 'There is no live session with that ID to finish.')); }
	if($live_session['completed_at']) { $dbcore->Output(array('error' => 'That live session is already finished.')); }
	list($vs1, $aps) = $live->Vs1($_REQUEST['SessionID'], 'WiFiDB live session '.$live_session['title']);
	if(!$aps)
	{
		$live->Finished($live_session['title_id']);
		$dbcore->Output(array('message' => 'Live session finished. It had no access points to import.', 'aps' => 0));
	}
	# onto the import queue as the session's member's, as an upload of theirs would be
	$file_orig = 'live_'.preg_replace('/[^A-Za-z0-9._-]+/', '_', $live_session['title']).'.vs1';
	$file_name = 'APIlive_'.random_int(10000000, 99999999).'_'.$file_orig;
	$upload = $dbcore->PATH.'import/up/'.$file_name;
	if(file_put_contents($upload, $vs1) === false) { $dbcore->Output(array('error' => 'The live session could not be saved for importing. Please try again in a while.')); }
	chmod($upload, 0600);
	$result = $dbcore->QueueUpload($upload, array('file_name' => $file_name, 'file_orig' => $file_orig, 'file_date' => date("Y-m-d H:i:s"),
		'file_user' => $live_session['username'], 'otherusers' => '', 'notes' => $live_session['notes'] !== '' ? $live_session['notes'] : 'No Notes',
		'title' => $live_session['title'], 'size' => $dbcore->format_size(strlen($vs1)), 'hash' => md5($vs1), 'type' => 'vistumbler'));
	# (a session that couldn't be queued stays open, to be finished again)
	if(!isset($result['error'])) { $live->Finished($live_session['title_id']); }
	$dbcore->Output($result + array('aps' => $aps));
}
if(!$live_session && trim((string)($_REQUEST['SessionID'] ?? '')) === '' && isset($_REQUEST['title']) && !isset($_REQUEST['Mac']) && !isset($_REQUEST['SSID']))
{
	# a new session: an account's, by its username and API key, or no one's
	$who = trim((string)($_REQUEST['username'] ?? ''));
	$key = (string)($_REQUEST['apikey'] ?? '');
	if($who === '' || $who === LiveSession::ANONYMOUS) { $who = LiveSession::ANONYMOUS; }
	elseif($dbcore->sec->APILoginCheck($who, $key) !== 1) { $dbcore->Output(array('error' => 'That username and API key don\'t match an account. Check them in your WiFiDB profile.')); }
	else { $who = $dbcore->sec->username; }
	$dbcore->Output(array('SessionID' => $live->Start($who, $_REQUEST['title'], $_REQUEST['notes'] ?? ''), 'username' => $who, 'LiveVersion' => LiveSession::VERSION));
}
if($live_session && $live_session['completed_at']) { $dbcore->Output(array('error' => 'That live session is finished. Start a new one.')); }

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
$lat    =   str_replace("0000.0000","0.0000",@$_REQUEST['Lat']);
$long   =   str_replace("0000.0000","0.0000",@$_REQUEST['Long']);
$lat    =   ($lat ? $lat : "0.0000");
$long   =   ($long ? $long : "0.0000");
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
# a version 2 session's access points are its member's, whatever username comes with them
if($live_session) { $username = $live_session['username']; }
$session_id   =   (@$_REQUEST['SessionID'] ? $_REQUEST['SessionID'] : "" );
if($session_id === "")
{
    $dbcore->mesg['message'] = "Session ID is blank :/";
} 
//echo $session_id;
if($ssid == "" && $mac == "000000000000" && $radio == "802.11u" && $sectype == 0 && $chan == 0 && $auth == "Open" && $encry == "None" && $BTx == "" && $OTX == "" && $NT == "Unknown" && $sig == "0" && $rssi == "-0")
{
	//echo "no data";
    //$dbcore->mesg = array("error"=>"You have not supplied any data.. you can't be a computer... shoo, go away.");
    //$dbcore->Output();
	$dbcore->Output("You have not supplied any data... shoo, go away.");
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
    'hist_date'=>$date." ".$time,
    
    #user data
    #'username'=>$dbcore->username,
    'username'=>$username,
    'session_id'=>$session_id
);
$dbcore->InsertLiveAP($data);
$dbcore->Output();
?>