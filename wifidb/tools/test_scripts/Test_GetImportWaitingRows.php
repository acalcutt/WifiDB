#!/usr/bin/php
<?php
/*
import_process.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, based on imp_expd.php by Phil Ferland.
This script is made to do imports and be run off of the importd.php daemon.
It is not possible to run this script on its own, it needs to be controled and scheduled by the importd.php daemon.
This is to prevent the import processes from colliding and thinking that an import is already in, when it is really not.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2015-04-06";
$dbcore->daemon_name	=	"Import";

$arguments = $dbcore->parseArgs($argv);

$dbcore->GetWaitingImportRowCount();
