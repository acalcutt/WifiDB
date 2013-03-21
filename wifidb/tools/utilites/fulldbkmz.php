<?php
$switches = array('screen'=>"CLI",'extras'=>'export');
date_default_timezone_set("UTC");

if(!(require('daemon/config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$dbcore->PATH = $daemon_config['wifidb_install'];
if($dbcore->PATH == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($dbcore->PATH)."/lib/init.inc.php";

$kml_file = $dbcore->exp_all_kml();

if(class_exists('ZipArchive'))
{
    $zip = new ZipArchive();
    $open_ret = $zip->open($kml_file.'.kmz', ZIPARCHIVE::CREATE);
    if($open_ret !== TRUE)
    {
        die("Failed to create daily Full KMZ file.\r\n".$open_ret."\r\n");
    }
    $zip->addFile($kml_file.'.kml');
    $zip->close();
}else
{
    echo "Failed to create Zip Class\r\nthe ZipArchive class may not exist.";
}
?>