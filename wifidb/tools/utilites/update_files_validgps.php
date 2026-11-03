<?php
/*
update_files_validgps.php: sets files.ValidGPS on every finished import that has at least one reading with a real GPS
fix (and clears it on one that has none). MariaDB, SQL Server and PostgreSQL.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$dbcore->verbose = 1;
$q = function ($name) use ($dbcore) { return $dbcore->sql->ident($name); };   # mixed-case names, quoted on PostgreSQL

$files = $dbcore->sql->conn->query("SELECT id, ".$q('ValidGPS')." AS validgps FROM files WHERE completed = 1 ORDER BY file_date DESC")->fetchAll(PDO::FETCH_ASSOC);

# does the file have a reading with a GPS fix?
$check = $dbcore->sql->conn->prepare("SELECT COUNT(*) FROM wifi_hist"
	." INNER JOIN wifi_gps ON wifi_hist.".$q('GPS_ID')." = wifi_gps.".$q('GPS_ID')
	." WHERE wifi_hist.".$q('File_ID')." = ? AND wifi_gps.".$q('Lat')." != '0.0000'");
$update = $dbcore->sql->conn->prepare("UPDATE files SET ".$q('ValidGPS')." = ? WHERE id = ?");
$changed = 0;
foreach($files as $file)
{
	$check->execute(array($file['id']));
	$valid = ((int)$check->fetchColumn() > 0) ? 1 : 0;
	if($valid !== (int)$file['validgps'])
	{
		echo "File {$file['id']}: ValidGPS ".(int)$file['validgps']." -> $valid\r\n";
		$update->execute(array($valid, $file['id']));
		$changed++;
	}
}
echo "Checked ".count($files)." files, changed $changed.\r\n";
