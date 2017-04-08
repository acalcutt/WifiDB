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
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "import");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}

require $daemon_config['wifidb_install'].'/lib/config.inc.php';

$dsn = $config['srvc'].':host='.$config['host'];
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);

$conn = new PDO($dsn, $config['db_user'], $config['db_pwd'], $options);

$sql = "SELECT `size` FROM `wifi`.`settings` WHERE `table` = 'version'";
echo $sql."\n";

$res = $conn->query($sql);
$fetch = $res->fetchAll(2);

foreach($fetch as $row)
{
    echo $row['size']."\n";
}