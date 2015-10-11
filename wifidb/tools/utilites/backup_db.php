<?php
error_reporting(E_ALL|E_STRICT);
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
require('../daemon/config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );
require( $daemon_config['wifidb_install']."/lib/config.inc.php" );
$bver = array(
				'version'	=>	'1.0',
				'usage'		=>	'Will create the dump files for people to download. Compressed or not.'
);
$date = date($dbcore->date_format);
$tmp_full = "/tmp/WiFiDB_Full_".$date.".sql";
$tmp_agps = "/tmp/WiFiDB_AP_GPS_".$date.".sql";
$full_export_file = $dbcore->PATH."out/archive/dumps/WiFiDB_Full_".$date.".sql";
$ap_gps_export_file = $dbcore->PATH."out/archive/dumps/WiFiDB_AP_GPS_".$date.".sql";
var_dump($ap_gps_export_file, $full_export_file);

$dbcore->sql->conn->query("SELECT * INTO OUTFILE '{$tmp_agps}' FROM `wifi`.`wifi_pointers`,`wifi`.`wifi_gps`, `wifi`.`wifi_signals`");
var_dump($dbcore->sql->conn->errorInfo());
if(move_uploaded_file($tmp_agps, $ap_gps_export_file))
{
    $tarfile = $dbcore->TarFile($ap_gps_export_file);
    var_dump($tarfile);
}
$dbcore->sql->conn->query("SELECT * INTO OUTFILE '{$tmp_full}' FROM `wifi`.`wifi_pointers`,`wifi`.`wifi_gps`, `wifi`.`wifi_signals`, `wifi`.`user_imports`, `wifi`.`files`, `wifi`.`live_aps`, `wifi`.`live_gps`, `wifi`.`live_titles`");
var_dump($dbcore->sql->conn->errorInfo());
if(copy($tmp_full, $full_export_file))
{
    $tarfile = $dbcore->TarFile($full_export_file);
    var_dump($tarfile);
}