<?php
$switches = array('screen'=>"CLI");

if(!(require('daemon/config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$lastedit="2012.01.03";
$start="2012.01.03";
$ver="1.0";
$localtimezone = date("T");
echo $localtimezone."\n";
global $not, $is;
$not = 0;
$is = 0;
$i=0;

$TOTAL_START = date("Y-m-d H:i:s");

echo "\n==-=-=-=-=-=- WiFiDB VS1 Duplicate Search Script -=-=-=-=-=-==\nVersion: ".$ver."\nLast Edit: ".$lastedit."\n";
$vs1dir = $dbcore->PATH."import/up/";

if (!file_exists($vs1dir))
{
    echo "You need to put some files in a folder named 'vs1' first.\nPlease do this first then run this again.\nDir:".$vs1dir;
    die();
}
// self aware of Script location and where to search for Txt files

echo "Directory: ".$vs1dir."\r\n";

// Go through the VS1 folder and grab all the VS1 and tmp files
// I included tmp because if you dont tell PHP to rename a file on upload to a website, it will give it a random name with a .tmp extension
echo "Going through the import/up folder for the source files...\r\n";
$hash_array = array();
$file_a = array();
$dh = opendir($vs1dir) or die("couldn't open directory");
$ii = 0;
while (!(($file = readdir($dh)) == false))
{
    $ii++;
    #echo $ii."\r\n";
    if ((is_file($vs1dir.$file)))
    {
	if($file == '.'){continue;}
	if($file == '..'){continue;}
	$file_e = explode('.',$file);
	$file_max = count($file_e);
	$fileext = strtolower($file_e[$file_max-1]);
	if ($fileext=='vs1' or $fileext=="tmp" or $fileext=="db3")
	{
	    $hash = md5_file($vs1dir.$file);
            
            if(is_null(@$hash_array[$hash]))
            {
                #echo "#will import";
                $hash_array[$hash] = $file;
                
            }else
            {
                echo "this is a duplicate\r\n".$vs1dir.$file."\r\n".$vs1dir.$hash_array[$hash]."\r\n".$hash."\r\n\r\n";
            }
            
	}else
	{
	    $dbcore->verbosed("EXT: ".$fileext);
	    $dbcore->verbosed("File not supported -->$file");
            $dbcore->logd("( ".$file." ) is not a supported file extention of ".$file_e[$file_max-1]."\r\n If the file is a txt file run it through the converter first.");
            continue;
        }
    }else{continue;}
    if($i==3)
    {
     #   die();
    }
    $i++;
}
$dbcore->verbosed("Is: $is\r\nNot: $not");
$TOTAL_END = date("Y-m-d H:i:s");
$dbcore->verbosed("TOTAL Running time::\n\nStart: ".$TOTAL_START."\nStop : ".$TOTAL_END."\n");
closedir($dh);
?>