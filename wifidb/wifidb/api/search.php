<?php
global $switches;
$switches = array('screen'=>"HTML",'extras'=>'API');

include('../lib/init.inc.php');
$api_key    = (empty($_REQUEST['apikey'])) ? "" : $_REQUEST['apikey'];
$user       = (empty($_REQUEST['username'])) ? "Unknown" : $_REQUEST['username'];
$date       = date("y-m-d H:i:s");
$dbcore->output    =   @$_GET['output'];
#$dbcore->ValidateAPIKey($user, $api_key);

$ssid   =   html_entity_decode(@$_GET['SSID'], ENT_QUOTES);
$mac    =   @$_GET['Mac'];
$radio  =   @$_GET['Rad'];
$sectype=   @$_GET['SecType'];
$chan   =   @$_GET['Chn'];
//Other AP Info
$auth   =   html_entity_decode(@$_GET['Auth'], ENT_QUOTES);
$encry  =   html_entity_decode(@$_GET['Encry'], ENT_QUOTES);
$NT     =   @$_GET['NT'];
$user     =   @$_GET['user'];

// GPS Variables
$lat    =   html_entity_decode(@$_GET['Lat'], ENT_QUOTES);
$long   =   html_entity_decode(@$_GET['Long'], ENT_QUOTES);



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
    'username'=>$user,
    #gps data
    'lat'=>$lat,
    'long'=>$long,
);

$dbcore->SearchAP($data);
$dbcore->Output();
?>
