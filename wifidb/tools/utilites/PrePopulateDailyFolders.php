#!/usr/bin/php
<?php
/*
PrePopulateDailyFolders.php
Copyright (C) 2015 Phil Ferland.
After a recovery, and there is no history KML data, this will try to generate all the previous history.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit	=	"2015-03-21";
$exports = array();
$dbcore->verbosed("Starting Pre-Populating of Daily Folders after a Recovery.");

$dbcore->verbosed("Grabbing all the imports.");
$UsersImportsResult = $dbcore->sql->conn->query("SELECT `id`, `title`, `username`, `points`, `date`, `hash`, `file_id` FROM `wifi`.`user_imports`");
$dbcore->sql->checkError(__LINE__, __FILE__);

$dbcore->verbosed("Go through all the imports and find the First Active for the first AP in each import.");
foreach($UsersImportsResult->fetchAll(2) as $import)
{
	var_dump($import['username'].' - '.$import['title'].' - '.$import['date']);
	foreach(explode("-", $import['points']) as $ap)
	{
		$id = explode(":", $ap)[0];
		$AP_FA_Result = $dbcore->sql->conn->prepare("SELECT FA FROM `wifi`.`wifi_pointers` WHERE `id` = ?");
		$AP_FA_Result->bindParam(1, $id, PDO::PARAM_INT);
		$AP_FA_Result->execute();
		$dbcore->sql->checkError(__LINE__, __FILE__);
		$AP_fetch = $AP_FA_Result->fetch(2);
		$import_date = explode(" ", $AP_fetch['FA'])[0];
		break;
	}
	$dbcore->verbosed("Got the First Active. $import_date");
	$OutputPath = $dbcore->daemon_out.
	$ret = $dbcore->export->UserList($import['id'], 1, $import['hash'], $import_date);
	if($ret['code'] === -1)
	{
		$dbcore->verbosed("Error with name of file...");
		continue;
	}elseif($ret['code'] === -2)
	{
		$dbcore->verbosed("Import has not GPS");
		continue;
	}

	if(empty($exports[$import_date]))
	{
		$exports[$import_date] = array($export_url);
	}else{
		$exports[$import_date][] = $export_url;
	}

	var_dump("------------------------------------------------");
}
var_dump($exports);








