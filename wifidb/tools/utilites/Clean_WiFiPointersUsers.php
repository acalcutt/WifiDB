#!/usr/bin/php
<?php
/*
importd.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, based on imp_expd.php by Phil Ferland.
This script is made to do imports and be run as a cron job.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

echo "Start fixing the Usernames in the WiFi Pointers table.\n";
$result = $dbcore->sql->conn->query("SELECT id, username FROM wifi.wifi_pointers");
$fetch = $result->fetchAll(2);
foreach($fetch as $row)
{
    echo "--------------------------------\n";
    echo $row['id']."\n";
    echo $row['username']."\n";
    if(@explode("|", $row['username'])[1] == "")
    {
        $user = str_replace("|", "", $row['username']);
    }else
    {
        $user = explode("|", $row['username'])[0];
    }

    $dbcore->sql->conn->query("UPDATE wifi.wifi_pointers SET `username` = '$user' WHERE `id` = '".$row['id']."'");
    if(!$dbcore->sql->checkError(__LINE__, __FILE__))
    {
        echo "Updated to $user\n";
    }
}