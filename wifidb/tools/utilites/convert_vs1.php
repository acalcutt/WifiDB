<?php
/*
 * Convert folder of non-VS1 files to VS1 files.
 */
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$debug = 0;
$bench = 0;
$lastedit="2009.Apr.10";
$start="6.21.2008";
$ver="1.3";
echo "\n\n==-=-=-=-=-=-Vistumbler Summery Text to VS1 converter -=-=-=-=-=-==\nVersion: ".$ver."\nLast Edit: ".$lastedit."\n";

$vs1dir = getcwd();
$vs1dir .="/vs1/";
$textdir = getcwd();
$textdir .="/text/";


if (file_exists($vs1dir)===FALSE){if(mkdir($vs1dir)){echo "made VS1 folder for the converted VS1 Files\n";}}
if (file_exists($textdir)===FALSE){echo "You need to put some files in a folder named 'text' first.\nPlease do this first then run this again.\nDir:".$vs1dir; mkdir($vs1dir);}
// self aware of Script location and where to search for Txt files


echo "|\n----------------\nDirectory: ".$textdir."\n\n";
echo "Files to Convert: \n";

$file_a = array();
$n=0;
$dh = opendir($textdir) or die("couldn't open directory");
while (!(($file = readdir($dh)) == false))
{
	if (is_file($textdir."/".$file)) 
	{
		if($file == '.'){continue;}
		if($file == '..'){continue;}
		$file_e = explode('.',$file);
		$file_max = count($file_e);
		if ($file_e[$file_max-1]=='txt' or $file_e[$file_max-1]=="TXT")
		{
			$file_a[$n] = $file;
			echo $n." ".$file."\n";
			$n++;
		}else{
			echo "file not supported !\n";
		}
	}
}
echo "\n\n";
closedir($dh);
foreach($file_a as $file)
{
	$source = $textdir.$file;
	echo '################=== Start conversion of '.$file.' ===################';
	echo "\n";
	convert_vs1($source, "file");
//	function ( Source file , output destination type [ file only at the moment MySQL support soon ] )
}

$TOTAL_END = date("H:i:s");
echo "\nTOTAL Running time::\n\nStart: ".$TOTAL_START."\nStop : ".$TOTAL_END."\n";