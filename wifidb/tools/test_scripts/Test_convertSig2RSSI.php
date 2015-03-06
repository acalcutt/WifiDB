#!/usr/bin/php
<?php
/*
Test_convertSig2RSSI.php, WiFiDB Import Daemon
Copyright (C) 2015 Phil Ferland.
This script is made to test the convertSig2RSSI function.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");


require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );


var_dump($dbcore->import->convertdBm2Sig(-35));
var_dump($dbcore->import->convertSig2dBm(75));

