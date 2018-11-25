<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$filewrite = fopen("filenames.txt", 'w');
$sql = "select * from `files` ORDER BY `id` ASC";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Gathered file data");
$write = "# FILE HASH | FILENAME | USERNAME | TITLE | DATE | NOTES\r\n";
while($array = $result->fetch(1))
{
	if ($array['hash'] != "")
	{
		if (trim($array['title']) == ""){$array['title'] = "Untitled";}
		$write .= trim($array['hash']."|".$array['file']."|".str_replace("|", "", $array['user'])."|".$array['title']."|".$array['date']."|".$array['notes'])."\r\n";
		echo $array['id']."|".$array['hash']."|".$array['file']."|".$array['user']."|".$array['title']."|".$array['date']."|".$array['notes']."\r\n";
		
	}
}

fwrite($filewrite, $write);
fclose($filewrite);
?>