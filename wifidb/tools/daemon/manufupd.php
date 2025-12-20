<?php
/*
manufupd.php, WiFiDB Manufacturer Update Daemon
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->daemon_name	=	"ManufUpd";
$dbcore->lastedit		=	"2019-09-01";
$dbcore->daemon_version =	"1.0";

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$pid_filename = 'manufupd_'.$dbcore->This_is_me.'_'.date("YmdHis").'.pid';
$dbcore->pid_file = $dbcore->pid_file_loc.$pid_filename;

if(!file_exists($dbcore->pid_file_loc))
{
	if(!mkdir($dbcore->pid_file_loc))
	{
		#throw new ErrorException("Could not make WiFiDB PID folder. ($dbcore->pid_file_loc)");
		echo "Could not create PID Folder at path: $dbcore->pid_file_loc \n";
		exit(-4);
	}
}
if(file_put_contents($dbcore->pid_file, $dbcore->This_is_me) === FALSE)
{
	echo "Could not write pid file ($dbcore->pid_file), that's not good... >:[\n";
	exit(-5);
}
echo "
WiFiDB ".$dbcore->ver_array['wifidb']." - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$dbcore->lastedit}, GPLv2
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level."\n";

$currentrun = date("Y-m-d G:i:s");

$source = 'http://standards-oui.ieee.org/oui.txt';
$manuf_list = array();
$debug = 0;

echo "Downloading and Opening the Source File from: \n----->".$source."\n|\n|";
$oui_text = file_get_contents($source);
$oui_arr = explode(PHP_EOL,$oui_text);
$total_lines = count($oui_arr);


foreach($oui_arr as $ret)
{
	
	$test = substr($ret, 11, 5);
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
						"bssid" 	=> $Man_mac,
						"manuf"	=> addslashes($Manuf)
						);
}

$total_manuf = count($manuf_list);
if(!($total_manuf > 0))
{
	unlink($dbcore->pid_file);
    die("No Manufactures were found..\n");
}else{
	echo $total_manuf." Manufactures and MAC Address' found...\n";
	
	foreach($manuf_list as $minfo)
	{
		$u_bssid = $minfo['bssid'];
		$u_manuf = $minfo['manuf'];
		echo $u_bssid." - ".$u_manuf.PHP_EOL;
		
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sql = "MERGE INTO manufacturers WITH (HOLDLOCK)\n"
					. "	USING (SELECT :s_bssid AS BSSID) AS newmac (BSSID)\n"
					. "		ON manufacturers.BSSID = newmac.BSSID\n"
					. "	WHEN MATCHED THEN\n"
					. "		UPDATE SET manufacturers.Manufacturer = :uManuf, manufacturers.modified = getdate()\n"
					. "	WHEN NOT MATCHED THEN\n"
					. "		INSERT (BSSID, Manufacturer, modified)\n"
					. "		VALUES (:BSSID, :iManuf, :modified)\n"
					. 'OUTPUT INSERTED.id, $action;';
							
				$prep = $dbcore->sql->conn->prepare($sql);
				$prep->bindParam(':s_bssid', $u_bssid);
				$prep->bindParam(':BSSID', $u_bssid);
				$prep->bindParam(':uManuf', $u_manuf);
				$prep->bindParam(':iManuf', $u_manuf);
				$prep->bindParam(':modified', $currentrun);
				$prep->execute();
				$return = $prep->fetch(2);
				$retry = false;
			}
			catch (Exception $e) 
			{
				$retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
				$return = 0;
			}
		}
	}
	unlink($dbcore->pid_file);
}