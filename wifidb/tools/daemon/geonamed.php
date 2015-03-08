#!/usr/bin/php
<?php
/*
geonamed.php, WiFiDB Geoname Daemon
Copyright (C) 2015 Andrew Calcutt, based on geonamed.php by Phil Ferland. 
This script is made to update geoname information and be run as a cron job. 

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2015-02-28";
$daemon_name = "Geoname";
$daemon_version = "4.0";
$node_name = $daemon_config['wifidb_nodename'];

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
    echo "Usage: geonamed.php [args...]
  -v               Run Verbosely (SHOW EVERYTHING!)
  -i               Version Info.
  -h               Show this screen.
  -l               Show License Information.
  
* = Not working yet.
";
    exit();
}

if(@$arguments['i'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$daemon_name} Daemon {$daemon_version}, {$lastedit}, GPLv2 Random Intervals");
    exit();
}

if(@$arguments['l'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$daemon_name} Daemon {$daemon_version}, {$lastedit}, GPLv2
Copyright (C) 2015 Andrew Calcutt,
This script is based on imp_expd.php by Phil Ferland. It is made to do just exports and be run as a cron job.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
");
    exit();
}

if(@$arguments['v'])
{
    $dbcore->verbose = 1;
}else
{
    $dbcore->verbose = 0;
}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
    mkdir($dbcore->pid_file_loc);
}
$dbcore->pid_file = $dbcore->pid_file_loc.'geonamed_'.time().'.pid';

if(!file_exists($dbcore->pid_file_loc))
{
    if(!mkdir($dbcore->pid_file_loc))
    {
        throw new ErrorException("Could not make WiFiDB PID folder. ($dbcore->pid_file_loc)");
    }
}
if(file_put_contents($dbcore->pid_file, $dbcore->This_is_me) === FALSE)
{
    die("Could not write pid file ($dbcore->pid_file), that's not good... >:[");
}

$dbcore->verbosed("Have written the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")");

$dbcore->verbosed("
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - {$daemon_name} Daemon {$daemon_version}, {$lastedit}, GPLv2 Random Intervals
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]

 Log Level is: ".$dbcore->log_level);
 # Safely kill script if Daemon kill flag has been set
if($dbcore->checkDaemonKill())
{
	$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
	exit($dbcore->exit_msg);
}

$dbcore->verbosed("Running $daemon_name jobs for $node_name");

#Checking for Import Jobs
$sql = "SELECT `id`, `interval` FROM `wifi`.`schedule` WHERE `nodename` = ? And `daemon` = ? And `status` <> ? And `nextrun` <= now() And `enabled` = 1 LIMIT 1";
$prepgj = $dbcore->sql->conn->prepare($sql);
$prepgj->bindParam(1, $node_name, PDO::PARAM_STR);
$prepgj->bindParam(2, $daemon_name, PDO::PARAM_STR);
$prepgj->bindParam(3, $daemon_config['status_running'], PDO::PARAM_STR);
$prepgj->execute();

if($prepgj->rowCount() == 0)
{
	$dbcore->verbosed("There are no import jobs that need to be run... I'll go back to waiting...");
}
else
{
	$dbcore->verbosed("Running...");
	$job_fetch = $prepgj->fetchAll(2);
	foreach($job_fetch as $job)
	{
		#Job Settings
		$job_id = $job['id'];
		$job_interval = $job['interval'];
		if($job_interval < '5'){$job_interval = '5';} //its really pointless to check more then 5 min at a time
		
		#Set Job to Running
		$dbcore->verbosed("Starting - Job:".$daemon_name." Id:".$job_id, 1);
		$sql = "UPDATE `wifi`.`schedule` SET `status`=? WHERE `id`=?";
		$prepsr = $dbcore->sql->conn->prepare($sql);
		$prepsr->bindParam(1, $daemon_config['status_running'], PDO::PARAM_STR);
		$prepsr->bindParam(2, $job_id, PDO::PARAM_INT);
		$prepsr->execute();
		
		#Start gathering Geonames
		$sql = "SELECT `id`,`lat`,`long`,`ap_hash` FROM `wifi`.`wifi_pointers` WHERE `geonames_id` = '' AND `lat` != '0.0000' ORDER BY `id` ASC";
		echo $sql."\r\n";
		$result = $dbcore->sql->conn->query($sql);
		$dbcore->verbosed("Gathered Wtable data");
		echo "Rows that need updating: ".$result->rowCount()."\r\n";
		sleep(4);
		while($ap = $result->fetch(1))
		{
			$dbcore->verbosed($ap['id']." - ".$ap['ap_hash']);
			$lat = round($dbcore->convert->dm2dd($ap['lat']), 1);
			$long = round($dbcore->convert->dm2dd($ap['long']), 1);
			$dbcore->verbosed("Lat - Long: ".$lat." [----] ".$long);
			$sql = "SELECT `geonameid`, `country code`, `admin1 code`, `admin2 code` FROM `wifi`.`geonames` WHERE `latitude` LIKE '$lat%' AND `longitude` LIKE '$long%' LIMIT 1";
			$dbcore->verbosed("Query Geonames Table to see if there is a location in an area that is equal to the geocord rounded to the first decimal.", 3);
			$geo_res = $dbcore->sql->conn->query($sql);
			$geo_array = $geo_res->fetch(PDO::FETCH_ASSOC);
			if(!$geo_array['geonameid'])
			{continue;}
			
			$dbcore->verbosed("Geoname ID: ".$geo_array['geonameid']);
			$admin1_array = array('id'=>'');
			$admin2_array = array('id'=>'');
			if($geo_array['admin1 code'])
			{
				$dbcore->verbosed("Admin1 Code is Numeric, need to query the admin1 table for more information.");
				$admin1 = $geo_array['country code'].".".$geo_array['admin1 code'];
				
				$sql = "SELECT `id` FROM `wifi`.`geonames_admin1` WHERE `admin1`='$admin1'";
				$admin1_res = $dbcore->sql->conn->query($sql);
				$admin1_array = $admin1_res->fetch(PDO::FETCH_ASSOC);
			}
			if(is_numeric($geo_array['admin2 code']))
			{
				$dbcore->verbosed("Admin2 Code is Numeric, need to query the admin2 table for more information.");
				$admin2 = $geo_array['country code'].".".$geo_array['admin1 code'].".".$geo_array['admin2 code'];
				$sql = "SELECT `id` FROM `wifi`.`geonames_admin2` WHERE `admin2`='$admin2'";
				$admin2_res = $dbcore->sql->conn->query($sql);
				$admin2_array = $admin2_res->fetch(PDO::FETCH_ASSOC);
			}

			$sql = "UPDATE `wifi`.`wifi_pointers` SET `geonames_id` = '{$geo_array['geonameid']}', `admin1_id` = '{$admin1_array['id']}', `admin2_id` = '{$admin2_array['id']}' WHERE `ap_hash` = '{$ap['ap_hash']}'";
			if($dbcore->sql->conn->query($sql))
			{
				$dbcore->verbosed("Updated AP's Geolocation  [{$ap['id']}] ({$ap['ap_hash']})" , 2);
			}else
			{
				$dbcore->verbosed("Failed to update AP's Geolocation [{$ap['id']}] ({$ap['ap_hash']})", -1);
				var_dump($dbcore->sql->conn->errorInfo());
			}
		}
		
		#Set Next Run
		$nextrun = date("Y-m-d G:i:s", strtotime("+".$job_interval." minutes"));
		$dbcore->verbosed("Setting Job Next Run to ".$nextrun, 1);
		$sql = "UPDATE `wifi`.`schedule` SET `nextrun`=? WHERE `id`=?";
		$prepnr = $dbcore->sql->conn->prepare($sql);
		$prepnr->bindParam(1, $nextrun, PDO::PARAM_STR);
		$prepnr->bindParam(2, $job_id, PDO::PARAM_INT);
		$prepnr->execute();
		
		#Set Job to Waiting
		$dbcore->verbosed("Setting Job to ".$daemon_config['status_waiting'], 1);
		$sql = "UPDATE `wifi`.`schedule` SET `status`=? WHERE `id`=?";
		$prepsw = $dbcore->sql->conn->prepare($sql);
		$prepsw->bindParam(1, $daemon_config['status_waiting'], PDO::PARAM_STR);
		$prepsw->bindParam(2, $job_id, PDO::PARAM_INT);
		$prepsw->execute();
		
		#Finished Job
		$dbcore->verbosed("Finished - Job:".$daemon_name." Id:".$job_id, 1);
	}
}
unlink($dbcore->pid_file);