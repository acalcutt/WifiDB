#!/usr/bin/php
<?php
/*
TestMySQLConnection.php, WiFiDB Import Daemon
Copyright (C) 2015 Phil Ferland.
Used to test the MySQL or other Database Connection status.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php file\r\n");}
require $daemon_config['wifidb_install'].'/lib/init.inc.php';

// Connects the same way the web site and daemons do, so it works for MySQL and SQL Server.
$sql = "SELECT version FROM settings";
echo "Database service: ".$dbcore->sql->service."\r\n";
echo $sql."\r\n";

$res = $dbcore->sql->conn->query($sql);
foreach($res->fetchAll(PDO::FETCH_ASSOC) as $row)
{
    echo "WiFiDB database version: ".$row['version']."\r\n";
}
