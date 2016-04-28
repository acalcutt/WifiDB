#!/usr/bin/php
<?php
/*
daemon_prep.php
Copyright (C) 2015 Andrew Calcutt, based on imp_expd.php by Phil Ferland.
Used to prepare for a recovery import. Will take filenames.txt that is a | seperated file that was generated with filenames_create.php as a psudo-backup, as long as you have the import files still.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$sql = "SELECT `id`, `points`, `aps` FROM `user_imports`";

$result = $dbcore->sql->conn->query($sql);
$allImports = $result->fetchAll(2);
$count = count($allImports)-1;
foreach($allImports as $import)
{
    $new = 0;
    echo $count." / ".$import['id']." - ".$import['aps']."\r\n";
    $points = explode("-", $import['points']);
    foreach($points as $point)
    {
        $exp = explode(":", $point);
        #var_dump($exp);
        if($exp[1] == "0")
        {
            $new++;
        }
    }
    $percent = ( $new / $import['aps'] ) * 100;
    $sql2 = "UPDATE `user_imports` SET `NewAPPercent` = ? WHERE `id` = ?";
    $prep = $dbcore->sql->conn->prepare($sql2);
    $prep->bindParam(1, $percent, PDO::PARAM_INT);
    $prep->bindParam(2, $import['id'], PDO::PARAM_INT);
    $prep->execute();
}