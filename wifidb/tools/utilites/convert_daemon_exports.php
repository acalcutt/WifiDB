<?php
global $switches;
$switches = array('extras'=>'export','screen'=>"CLI");

if(!(require('../daemon/config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$dbcore->verbose = 1;
$dbcore->named = 1;

$daemon_export = $dbcore->PATH."out/daemon/";
$dir = opendir($daemon_export);
while ($file = readdir($dir))
{
    if($file === "." || $file === ".." || $file === ".svn" || $file === "history"){continue;}
    if(is_dir($daemon_export.$file))
    {
        echo "---------------------------------------------\r\n";
        $dir2 = opendir($daemon_export.$file);
        while ($file2 = readdir($dir2))
        {
            if($file2 === "." || $file2 === ".."){continue;}
            var_dump($daemon_export.$file.'/'.$file2);
            if($file2 === "doc.kml")
            {
                if(unlink($daemon_export.$file.'/'.$file2))
                {
                    $dbcore->verbosed("Deleted unneeded doc.kml file.");
                }
            }
            if($file2 === "fulldb.kmz")
            {
                if(rename($daemon_export.$file.'/'.$file2, $daemon_export.$file.'/full_db.kmz'))
                {
                    $dbcore->verbosed("Renamed fulldb.kmz to full_db.kmz.");
                }
            }
            if($file2 == "daily_db_label.kml" || $file2 == "daily_db.kml" || $file2 == "full_db_label.kml" || $file2 == "full_db.kml")
            {
                $file_exp = explode(".", $file2);
                if(!file_exists($daemon_export.$file.'/'.$file_exp[0].'.kmz'))
                {
                    if(!$dbcore->CreateKMZ($daemon_export.$file.'/'.$file2))
                    {
                        $dbcore->verbosed("Created KMZ for ".$file2);
                    }else
                    {
                        $dbcore->verbosed("Failed to create KMZ for ".$file2);
                    }
                }
            }
        }
    }
}
?>
