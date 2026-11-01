#!/usr/bin/php
<?php
/*
NewAPPercentCalc.php: works out each finished import's "new AP percent" again (files.NewAPPercent): of the access
points in the file, the share that this file was the first to bring in (wifi_ap.File_ID is this file), the same sum
the importer does. MariaDB, SQL Server and PostgreSQL.
Copyright (C) 2015 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$q = function ($name) use ($dbcore) { return $dbcore->sql->ident($name); };   # mixed-case names, quoted on PostgreSQL

$files = $dbcore->sql->conn->query("SELECT id, aps, ".$q('NewAPPercent')." AS pct FROM files WHERE completed = 1 AND aps > 0 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
$new = $dbcore->sql->conn->prepare("SELECT COUNT(*) FROM wifi_ap WHERE ".$q('File_ID')." = ?");
$update = $dbcore->sql->conn->prepare("UPDATE files SET ".$q('NewAPPercent')." = ? WHERE id = ?");
$changed = 0;
foreach($files as $file)
{
	$new->execute(array($file['id']));
	$percent = (int)(((int)$new->fetchColumn() / (int)$file['aps']) * 100);
	if($percent !== (int)$file['pct'])
	{
		echo "File {$file['id']}: {$file['pct']}% -> $percent%\r\n";
		$update->execute(array($percent, $file['id']));
		$changed++;
	}
}
echo "Checked ".count($files)." files, changed $changed.\r\n";
