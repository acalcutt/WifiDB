<?php
error_reporting(E_ALL|E_STRICT);
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
require( '../daemon/config.inc.php' );
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


$sql_file = '-- WiFiDB SQL Dump
-- version {$this->ver_str}
-- http://www.wifidb.net
-- Generation Time: {$date_time}

SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';

$tables = array('wifi_pointers', 'wifi_gps', 'wifi_signals', 'user_imports', 'files', 'live_aps', 'live_gps', 'live_titles');
foreach ($tables as $table)
{
    var_dump($table);
    $result = $dbcore->sql->conn->query("SHOW CREATE TABLE `wifi`.`$table`");
    #$array = $result->fetch(1);
    #$sql_file .= $array['Create Table'].";\r\n\r\n\r\n";
    
    $result1 = $dbcore->sql->conn->query("SELECT * FROM `wifi`.`$table` LIMIT 2");
    $array_test = $result1->fetchall(2);
    $fields = array_keys($array_test[0]);
    $insert .= "INSERT INTO `files` ( `".implode('`, `', $fields)."` ) VALUES \r\n";
    $sql_file .= $insert;
    $value_array = array();
    $i=0;
    foreach($array_test as $key=>$array1)
    {
        echo $i." ";
        if($i === 300)
        {
            $i=0;
            $sql_file .= implode(",", $value_array).";\r\n".$insert;
            $value_array = array();
        }
        $i++;
        $value_array[] = "( '".implode("' , '", $array1)."' ),";
    }
}

die();
$values = "({$i}, '407998950_WDB_Export.VS1', 0, '', '2009-08-24 15:36:34', '15.0498046875', 6, 170, '66e73372feab5864153ccf741d9a4012', 1, 'WiFiDB', 'No Notes\n', 'Recovered'),";









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
?>
