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
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2015-02-10";

echo "
#####################################
##       File Number ONE (1)       ##
#####################################
";
$dbcore->verbose = 1;
$dbcore->import_vs1( '/var/www/wifidb/import/up/atlanta_hotel.VS1', 'pferland', 'no notes', 'Test 1', date("Y-m-d H:i:s"));

/*
echo "
*************************************
**       File Number TWO (2)       **
*************************************
";

$dbcore->import_vs1( '/var/www/wifidb/import/up/atlanta_hotel_2.VS1', 'pferland', 'no notes', 'Test 2', date("Y-m-d H:i:s"));



echo "
*************************************
**       File Number TWO (2)       **
*************************************
";

$dbcore->import_vs1( '/var/www/wifidb/import/up/atlanta_hotel_3.VS1', 'pferland', 'no notes', 'Large File Test', date("Y-m-d H:i:s"));
*/

?>
