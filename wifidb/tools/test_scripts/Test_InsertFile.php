#!/usr/bin/php
<?php
/*
Test_Import.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, based on Test_Import.php by Phil Ferland.
This script is made to do imports and be run as a cron job.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "apiv2");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";


$lastedit  = "2016-04-25";

$dbcore->verbose = 1;

$dbcore->ImportVS1($user = "pferland", $otherusers = "", $date = "2016-04-25", $title = "Test New AP Percent Import", $notes = "", $size = "100kb", $hash = "758f2c03117e6381f41ad6da48382691", $ext = "vs1", $filename = "1709508122_sunday_drive_atlanta.VS1");

var_dump($dbcore->mesg);

