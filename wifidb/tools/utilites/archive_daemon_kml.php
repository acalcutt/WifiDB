<?php

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
date_default_timezone_set("UTC");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$dbcore->PATH = $daemon_config['wifidb_install'];
if($dbcore->PATH == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($dbcore->PATH)."/lib/init.inc.php";

$archive_dir = "/opt/kml_archive/";
$daemon_exports = $dbcore->PATH."out/daemon/";

//get all files in specified directory
$folders = glob($daemon_exports . "*", GLOB_ONLYDIR);
 
//print each file name
foreach($folders as $folder)
{
 //check to see if the file is a folder/directory
 if(is_dir($folder))
 {
	//Get Directory Info
	$export_dir = $folder."/";
	$export_name = "";
	$export_name = basename ($export_dir);
	echo $export_dir."\r\n";
	echo $export_name."\r\n";
	
	//Create ArchiveDirectory
	$export_archive_dir = $archive_dir.$export_name."/";
	mkdir ($export_archive_dir);
	echo $export_archive_dir."\r\n";
	
	$export_full_db_kml = $export_dir."full_db.kml";
	$export_full_db_label_kml = $export_dir."full_db_label.kml";
	$export_daily_db_kml = $export_dir."daily_db.kml";
	$export_daily_db_label_kml = $export_dir."daily_db_label.kml";
	$export_doc_kml = $export_dir."doc.kml";
	$export_fulldb_kmz = $export_dir."fulldb.kmz";
	$export_archive_full_db_kml = $export_archive_dir."full_db.kml";
	$export_archive_full_db_label_kml = $export_archive_dir."full_db_label.kml";
	$export_archive_daily_db_kml = $export_archive_dir."daily_db.kml";
	$export_archive_daily_db_label_kml = $export_archive_dir."daily_db_label.kml";
	$export_archive_doc_kml = $export_archive_dir."doc.kml";
	$export_archive_fulldb_kmz = $export_archive_dir."fulldb.kmz";
	
	if (file_exists($export_full_db_kml)) {
		echo "The file $export_full_db_kml exists\r\n";
		rename($export_full_db_kml, $export_archive_full_db_kml);
	} else {
		echo "The file $export_full_db_kml does not exist\r\n";
	}
	
	if (file_exists($export_full_db_label_kml)) {
		echo "The file $export_full_db_label_kml exists\r\n";
		rename($export_full_db_label_kml, $export_archive_full_db_label_kml);
	} else {
		echo "The file $export_full_db_label_kml does not exist\r\n";
	}
	
	if (file_exists($export_daily_db_kml)) {
		echo "The file $export_daily_db_kml exists\r\n";
		rename($export_daily_db_kml, $export_archive_daily_db_kml);
	} else {
		echo "The file $export_daily_db_kml does not exist\r\n";
	}
	
	if (file_exists($export_daily_db_label_kml)) {
		echo "The file $export_daily_db_label_kml exists\r\n";
		rename($export_daily_db_label_kml, $export_archive_daily_db_label_kml);
	} else {
		echo "The file $export_daily_db_label_kml does not exist\r\n";
	}
	
	if (file_exists($export_doc_kml)) {
		echo "The file $export_doc_kml exists\r\n";
		rename($export_doc_kml, $export_archive_doc_kml);
	} else {
		echo "The file $export_doc_kml does not exist\r\n";
	}
	
	if (file_exists($export_fulldb_kmz)) {
		echo "The file $export_fulldb_kmz exists\r\n";
		rename($export_fulldb_kmz, $export_archive_fulldb_kmz);
	} else {
		echo "The file $export_fulldb_kmz does not exist\r\n";
	}
 }
}
?>
