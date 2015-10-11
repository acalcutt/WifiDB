#!/usr/bin/php
<?php
/*
manufmac.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, based on manufumac.php by Phil Ferland.
This script is made to get the latest list of Manufactures and their assigned MAC Addresses.
It creates an INI file for vistumbler. It can also insert them into the manufactures table for WiFiDB.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/

/*
 *  Compile to exe using bamcomple 1.21 with command "bamcompile.exe manufmac.php manufmac.exe"
 */


define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$ver = "2.0.0";
ini_set("memory_limit","3072M");
$script_start = "2009-Jan-24";
$lastedit = "2015-04-12";
$author = "pferland"; //modified by acalcutt 2013-06-30 to support new oui.txt file

$arguments = $dbcore->parseArgs($argv);
if(@$arguments['h'] OR empty($arguments) )
{
    echo "Usage: manufmac [args...]
  -i            (null)          Write Vistmbler INI only.
  -w            (null)          Write to WiFiDB Manufactures table only.
  -d		(null)		Run debug mode (SHOW EVERYTHING!)
  -l		(null)		Show License Information.
  -h		(null)		Show this screen.
  --version	(null)		Version Info.

* = Not working yet.
";
    return "help_menu";
}


if(@$arguments['version'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
Manufactures MacAddress Update script {$ver}, {$lastedit}, GPLv2 Random Intervals");
    return "version";
}
if(@$arguments['l'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
Copyright (C) 2015 Andrew Calcutt, Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
");
    return "license";
}

if(@$arguments['v'])
{
    $debug = 1;
}
else
{
    $debug = 0;
}

if(@$arguments['w'])
{
    $wifidb_update = 1;
    echo "Told to WiFiDB Manufactures table.\n";
}
else
{
    $wifidb_update = 0;
}


if(@$arguments['i'])
{
    $vistumbler_ini = 1;
    echo "Told to write the Vistumbler INI File.\n";
}
else
{
    $vistumbler_ini = 0;
}

# Neither flag was set, so lets do the vistumler ini only.
if((!$vistumbler_ini AND !$wifidb_update))
{
    $vistumbler_ini = 1;
    $wifidb_update = 0;
}

if(@$arguments['s'])
{
    $source = $arguments['s'];
    echo "Told to use alternate source for oui.txt.\n";
}
else
{
    $source="oui.txt";
}
$stime = time();
$cwd = getcwd();
echo "-----------------------------------------------------------------------\n";
echo "| Starting creation of Vistumbler compatible Wireless Router Manuf List.\n| By: $author\n| http:\\www.randomintervals.com\n| Version: $ver\n";

$manuf_list = array();

$vs1file = "manufactures.ini";
$vs1filewrite = fopen($vs1file, "w");
$vs1fileappend = fopen($vs1file, "a");

echo "Downloading and Opening the Source File from: \n----->".$source."\n|\n|";
$return = file($source);
$total_lines = count($return);
echo "Source File opened, starting to parse file.\n|";
$r = 0;
foreach($return as $ret)
{
	$test = substr($ret, 13, 5);
	if ($test != "(hex)"){if($debug === 1){echo "Erroneous data found, dropping\n| This is normal...\n| ";} continue;}
	$retexp = explode("(hex)",$ret);

	$Man_mac = trim($retexp[0]);
	$man_mac = explode("-",$Man_mac);
	$Man_mac = implode("",$man_mac);
	$Manuf = trim($retexp[1]);
	if($Manuf == "PRIVATE")
    {
        if($debug)
        {
            echo "Unneeded Manuf found...\n| ";
        }
        continue;
    }
	$manuf_list[] = array(
						"mac" 	=> $Man_mac,
						"manuf"	=> addslashes($Manuf)
						);
    $r = $dbcore->RotateSpinner($r);
}
$total_manuf = count($manuf_list);
if(!($total_manuf > 0))
{
    die("No Manufactures were found..\n");
}
echo "Manufactures and MAC Address' found...\n";

fwrite($vs1fileappend, ";This file allows you to assign a manufacturer to a mac address(first 6 digits).\r\n[MANUFACURERS]\r\n");

$current = 1;
$result = $dbcore->sql->conn->prepare("INSERT INTO `wifi`.`manufactures` (`id`, `manuf`, `mac`) VALUES (NULL, ?, ?)");
if($wifidb_update)
{
	echo "Inserting into WifiDB Manufactures Table...\n";
}

if($vistumbler_ini)
{
	echo "Writing to manufactures.ini for Vistumbler.\n";
}
$r = 0;
foreach($manuf_list as $manuf)
{
    $r = $dbcore->RotateSpinner($r);
    if($wifidb_update)
    {
        $result->bindParam(1, $manuf['manuf'], PDO::PARAM_STR);
        $result->bindParam(2, $manuf['mac'], PDO::PARAM_STR);
        $result->execute();
        $dbcore->sql->checkError(__LINE__, __FILE__);
    }

    if($vistumbler_ini)
    {
        if($total_manuf == $current)
        {
            $write = $manuf['mac']."=".$manuf['manuf']."\r\n";
        }else{
            $write = $manuf['mac']."=".$manuf['manuf'].",\r\n";
        }

        fwrite($vs1fileappend, $manuf['mac']."=".$manuf['manuf']."\r\n");
    }
	if($debug == 1){echo $write."\n";}
	$r = $dbcore->RotateSpinner($r);
    $current++;
}

$result = $dbcore->sql->conn->query("SELECT count(id) FROM `wifi`.`manufactures`");
echo "Rows Inserted: ".$result->fetch(2)['count(id)']."\n";

#------------------------------------------------------------------------------------------------------#

$etime = time();
$diff_time = $etime - $stime;
$lines_p_min = $total_lines/$diff_time;
	echo "Total Manufactures found: ".$total_manuf."\n----------------\n"
	."Start Time:.......".$stime."\n"
	."End Time:.........".$etime."\n"
	."Total Run Time:...".$diff_time."\n----------------\n"
	."Total Lines:......".$total_lines."\n"
	."Lines per min:....".$lines_p_min."\n"
	."----------------\nDone";