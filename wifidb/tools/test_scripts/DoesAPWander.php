#!/usr/bin/php
<?php
/*
Test_Import.php, WiFiDB Import Daemon
Copyright (C) 2016 Phil Ferland.
This script is made to do imports and be run as a cron job.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "daemon");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";



list($vs1data ) = $dbcore->import->CreateDataArrayFromVS1("/wifidb/www/wifidb/import/up/474584327_2010-06-02 20-24-36.VS1");

$wander_rating = 0;

$sql = "select DATEDIFF(`LA`, `FA`) as DD from wifi_pointers WHERE ap_hash = ?";
$prep = $this->sql->conn->prepare($sql);
$prep->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
$prep->execute();
$DateRangeRet = $prep->fetch(2);

if((int)$DateRangeRet['DD'] > 1)
{
    if( $dbcore->import->DoesAPWander("51737a762aa68919c2ac767f0469d0ea", 1, $vs1data) )
    {
        $wander_rating++;
    }
}

var_dump($wander_rating);