<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$filewrite = fopen("filenames_v2.txt", 'w');
$sql = "select * from files ORDER BY id ASC";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Gathered file data");
$write = "# FILE HASH | TYPE | FILENAME | ORIG_FILENAME | USERNAME | TITLE | DATE | NOTES\r\n";
while($array = $result->fetch(1))
{
	if ($array['hash'] != "")
	{
		if (trim($array['title']) == ""){$title = "Untitled";}else{$title = trim($array['title']);}
		if (trim($array['type']) == ""){$type = "vistumbler";}else{$type = trim($array['type']);}
		$title = str_replace(array("|", "\n", "\r"), "", $title);
		$notes = str_replace(array("|", "\n", "\r"), "", $array['notes']);
		$user = str_replace(array("|", "\n", "\r"), "", $array['file_user']);
		$hash = trim($array['hash']);
		$write .= $hash."|".$type."|".$array['file_orig']."|".$array['file_name']."|".$user."|".$title."|".$array['file_date']."|".$notes."\r\n";
		echo $array['id']."|".$hash."|".$type."|".$array['file_orig']."|".$array['file_name']."|".$user."|".$title."|".$array['file_date']."|".$notes."\r\n";
		
	}
}

fwrite($filewrite, $write);
fclose($filewrite);
?>
