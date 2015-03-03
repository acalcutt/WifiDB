#!/usr/bin/php
<?php
/*
Test_UpdateAPIKeys.php, WiFiDB Import Daemon
Copyright (C) 2015 Phil Ferland.
Used to test API Key generation.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "cli");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbose = 1;
$parm = parseArgs($argv);
$all_users = @$parm['all_users'];
$user = @$parm['user'];


if($all_users)
{
    echo "Going to clear out the GPS data from the pointers table.\r\n";
    $sql = "SELECT id, username FROM `wifi`.`user_info`";
    $result = $dbcore->sql->conn->query($sql);
    while($users_array = $result->fetch_array(1))
    {
        echo "------------------------------------\r\n";
        echo "Username: ".$users_array['username']."\r\n";
        echo "ID: ".$users_array['id']."\r\n";
        $id = $users_array['id'];
        $key = $dbcore->sec->GenerateKey();
        echo "API Key: ".$key."\r\n";

        $update = "UPDATE `wifi`.`user_info` SET `apikey` = '$key' WHERE `id` = '$id'";
        if($conn->query($update))
        {
            echo "Updated users APIkey.\r\n";$good++;
        }else
        {
            echo "Failed to update user's APIkey :(\r\n";$bad++;
        }
    }
    echo "------------------------------------\r\n";
}elseif($user)
{
    $sql = "SELECT id, username FROM `wifi`.`user_info` WHERE `username` = '$user'";
    $result = $dbcore->sql->conn->query($sql);
    $users_array = $result->fetch_array(1);
    echo "Username: ".$user."\r\n";
    echo "ID: ".$users_array['id']."\r\n";
    $id = $users_array['id'];
    $key = $dbcore->sec->GenerateKey();
    echo "API Key: ".$key."\r\n";
    $update = "UPDATE `wifi`.`user_info` SET `apikey` = '$key' WHERE `id` = '$id'";
    if($conn->query($update))
    {
        echo "Updated users APIkey.\r\n";
    }else
    {
        echo "Failed to update user's APIkey :(\r\n";
    }

}else{
    die("You need to pass an argument for this script to run.\r\n--user=%username% or --all_users\r\n\r\n");
}


function parseArgs($argv){
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg)
    {
        if (substr($arg,0,2) == '--'){
            $eqPos = strpos($arg,'=');
            if ($eqPos === false){
                $key = substr($arg,2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key = substr($arg,2,$eqPos-2);
                $out[$key] = substr($arg,$eqPos+1);
            }
        } else if (substr($arg,0,1) == '-'){
            if (substr($arg,2,1) == '='){
                $key = substr($arg,1,1);
                $out[$key] = substr($arg,3);
            } else {
                $chars = str_split(substr($arg,1));
                foreach ($chars as $char){
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }
    return $out;
}
?>