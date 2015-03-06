#!/usr/bin/php
<?php
/*
Test_ConvertToVS1.php, WiFiDB Import Daemon
Copyright (C) 2015 Phil Ferland.
This script is made to test the convert other files (csv, wardrive 3 [db3] and wardrive 4 [db], and VSZ) to VS1.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

require('../config.inc.php');
require( $daemon_config['wifidb_install']."lib/init.inc.php" );
$dbcore->verbosed("Starting Conversion test script.....");
$dbcore->verbosed("SQLite Version: ".SQLite3::version()['versionString']);
$dbcore->verbosed("Testing Wardrive 4 SQLite file.");

$source = "/var/www/wifidb/import/up/wifi.db";
$dbh = new PDO("sqlite:$source");
$dbh->setAttribute(PDO::ATTR_ERRMODE,
    PDO::ERRMODE_EXCEPTION );


/*
$tablesquery = $dbh->query("SELECT * FROM wifi;");
while ($table = $tablesquery->fetch(SQLITE3_ASSOC)) {
    var_dump($table['ssid']);
    $tablesquery1 = $dbh->query("SELECT * FROM wifispot WHERE `fk_wifi` = '".$table['_id']."';");
    while ($table1 = $tablesquery1->fetch(SQLITE3_ASSOC)) {
        var_dump($table1);
    }

}



$tablesquery = $dbh->query("SELECT * FROM sqlite_master WHERE type='table';");
while ($table = $tablesquery->fetch(SQLITE3_ASSOC)) {
    echo $table['name']."\n";
}
die();
*/

$ret = $dbcore->convert->main($source, 0);
var_dump($ret);
if($ret === -1)
{
    Throw new ErrorException("Error Converting File. $source");
}
dump_info($ret);
die();

$dbcore->verbosed("Testing Wardrive 3 SQLite file.");
$ret = $dbcore->convert->main("/var/www/wifidb/import/up/wardrive.db3", 0);
var_dump($ret);
if($ret === -1)
{
    Throw new ErrorException("Error Converting File. $source");
}
dump_info($ret);
#die();


$dbcore->verbosed("Testing CSV file.");
$ret = $dbcore->convert->main("/var/www/wifidb/import/up/testing.csv", 0);
var_dump($ret);
if($ret === -1)
{
    Throw new ErrorException("Error Converting File. $source");
}
dump_info($ret);
#die();


$dbcore->verbosed("Testing Compressed VS1 file.");
$ret = $dbcore->convert->main("/var/www/wifidb/import/up/testing.vsz", 0);
var_dump($ret);
if($ret === -1)
{
    Throw new ErrorException("Error Converting File. $source");
}
dump_info($ret);







function dump_info($file)
{
    $parts = pathinfo($file);
    $dest_name = $parts['basename'];
    $file_hash1 = hash_file('md5', $file);
    $file_size1 = (filesize($file)/1024);
    var_dump($dest_name, $file_hash1, $file_size1);
}