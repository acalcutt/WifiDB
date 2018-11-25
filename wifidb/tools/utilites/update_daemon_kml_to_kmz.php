<?php

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");
date_default_timezone_set("UTC");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$dbcore->PATH = $daemon_config['wifidb_install'];
if($dbcore->PATH == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($dbcore->PATH)."/lib/init.inc.php";


$daemon_exports = $dbcore->PATH."out/daemon/";

//get all files in specified directory
$folders = glob($daemon_exports . "*", GLOB_ONLYDIR);
 
//print each file name
foreach($folders as $folder)
{
 //check to see if the file is a folder/directory
 if(is_dir($folder))
 {
	$export_dir = $folder."/";
	echo $export_dir."\r\n";
	
	$export_full_db_kml = $export_dir."full_db.kml";
	$export_full_db_kmz = $export_dir."full_db.kmz";
	$export_full_db_label_kml = $export_dir."full_db_label.kml";
	$export_full_db_label_kmz = $export_dir."full_db_label.kmz";
	$export_daily_db_kml = $export_dir."daily_db.kml";
	$export_daily_db_kmz = $export_dir."daily_db.kmz";
	$export_daily_db_label_kml = $export_dir."daily_db_label.kml";
	$export_daily_db_label_kmz = $export_dir."daily_db_label.kmz";
	
	if (file_exists($export_full_db_kmz)) {
		echo "The file $export_full_db_kmz exists\r\n";
	} else {
		echo "The file $export_full_db_kmz does not exist\r\n";
		if (file_exists($export_full_db_kml)) {
			echo "The file $export_full_db_kml exists\r\n";
			CreateKMZ($export_full_db_kml);
		} else {
			echo "The file $export_full_db_kml does not exist\r\n";
		}
	}
	
	if (file_exists($export_full_db_label_kmz)) {
		echo "The file $export_full_db_label_kmz exists\r\n";
	} else {
		echo "The file $export_full_db_label_kmz does not exist\r\n";
		if (file_exists($export_full_db_label_kml)) {
			echo "The file $export_full_db_label_kml exists\r\n";
			CreateKMZ($export_full_db_label_kml);
		} else {
			echo "The file $export_full_db_label_kml does not exist\r\n";
		}
	}
	
	if (file_exists($export_daily_db_kmz)) {
		echo "The file $export_daily_db_kmz exists\r\n";
	} else {
		echo "The file $export_daily_db_kmz does not exist\r\n";
		if (file_exists($export_daily_db_kml)) {
			echo "The file $export_daily_db_kml exists\r\n";
			CreateKMZ($export_daily_db_kml);
		} else {
			echo "The file $export_daily_db_kml does not exist\r\n";
		}
	}
	
	if (file_exists($export_daily_db_label_kmz)) {
		echo "The file $export_daily_db_label_kmz exists\r\n";
	} else {
		echo "The file $export_daily_db_label_kmz does not exist\r\n";
		if (file_exists($export_daily_db_label_kml)) {
			echo "The file $export_daily_db_label_kml exists\r\n";
			CreateKMZ($export_daily_db_label_kml);
		} else {
			echo "The file $export_daily_db_label_kml does not exist\r\n";
		}
	}
	
	
	
 }
}

    function CreateKMZ($file = "")
    {
        if($file === ""){return -1;}

        #create new kmz filename
        $parts = pathinfo($file);
        $parts_base = $parts['dirname'];
        $parts_name = $parts['filename'];
        $file_create = $parts_base."/".$parts_name.".kmz";

        #Create KMZ zip file
        $zip = new ZipArchive;
        $zip->open($file_create, ZipArchive::CREATE);
        #var_dump($zip->getStatusString());

        $zip->addFile($file, 'doc.kml');
        #var_dump($zip->getStatusString());

        $zip->close();

        if (file_exists($file_create)) {
            return $file_create;
        } else {
            return -2;
        }
    }

?>