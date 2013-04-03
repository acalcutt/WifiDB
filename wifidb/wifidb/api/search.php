<?php
global $switches;
$switches = array('screen'=>"HTML",'extras'=>'API');

include('../lib/init.inc.php');

$ssid   =   html_entity_decode(@$_REQUEST['SSID'], ENT_QUOTES);
$mac    =   @$_REQUEST['Mac'];
$radio  =   @$_REQUEST['Rad'];
$sectype=   @$_REQUEST['SecType'];
$chan   =   @$_REQUEST['Chn'];
//Other AP Info
$auth   =   html_entity_decode(@$_REQUEST['Auth'], ENT_QUOTES);
$encry  =   html_entity_decode(@$_REQUEST['Encry'], ENT_QUOTES);
$NT     =   @$_REQUEST['NT'];
$user   =   @$_REQUEST['user'];

// GPS Variables
$lat    =   html_entity_decode(@$_REQUEST['Lat'], ENT_QUOTES);
$long   =   html_entity_decode(@$_REQUEST['Long'], ENT_QUOTES);



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
