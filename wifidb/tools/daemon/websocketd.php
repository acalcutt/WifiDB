#!/usr/bin/php
<?php
/*
importd.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, Phil Ferland.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
ini_set('display_errors', 1);//***DEV USE ONLY***
error_reporting(E_ALL);# || E_STRICT);//***DEV USE ONLY***

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "API");

require_once('./lib/websockets.php');

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}

require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->lastedit		=	"2015-09-27";
$dbcore->daemon_name	=	"WebSocket";
$dbcore->createPIDFile();

#$arguments = $dbcore->parseArgs($argv);

$echo = new WebSocketDaemon($dbcore, $daemon_config['WebSocketBindIP'], $daemon_config['WebSocketPort']);

try {
  $echo->run();
}
catch (Exception $e) {
  $echo->stdout($e->getMessage());
}