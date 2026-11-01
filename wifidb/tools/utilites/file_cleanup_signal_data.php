<?php
/*
file_cleanup_signal_data.php: removes one import's signal readings (wifi_hist) and GPS points (wifi_gps), and on MariaDB
its user_imports row. The access points themselves are kept. MariaDB, SQL Server and PostgreSQL.

  php file_cleanup_signal_data.php            asks for the file id
  php file_cleanup_signal_data.php FILE_ID
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$file_id = (int)(isset($argv[1]) ? $argv[1] : readline("Please enter the file id: "));
if($file_id <= 0) { die("No file id given.\r\n"); }
$q = function ($name) use ($dbcore) { return $dbcore->sql->ident($name); };   # mixed-case names, quoted on PostgreSQL

# the readings first: they point at the GPS points (a foreign key on MariaDB)
$prep = $dbcore->sql->conn->prepare("DELETE FROM wifi_hist WHERE ".$q('File_ID')." = ?");
$prep->execute(array($file_id));
echo "Removed ".$prep->rowCount()." signal readings.\r\n";

$prep = $dbcore->sql->conn->prepare("DELETE FROM wifi_gps WHERE ".$q('File_ID')." = ?");
$prep->execute(array($file_id));
echo "Removed ".$prep->rowCount()." GPS points.\r\n";

if($dbcore->sql->service == "mysql")
{
	# only MariaDB's schema still has user_imports
	$prep = $dbcore->sql->conn->prepare("DELETE FROM user_imports WHERE file_id = ?");
	$prep->execute(array($file_id));
}
