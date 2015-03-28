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

$lastedit  = "2015-03-19";
$dbcore->daemon_name	=	"Geoname";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: importd.php [args...]
  -v				Run Verbosely (SHOW EVERYTHING!)
  -i				Version Info.
  -h				Show this screen.
  -l				Show License Information.
  -f				Force daemon to run without being scheduled.

* = Not working yet.
";
	exit();
}

if(@$arguments['i'])
{
	$dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals");
	exit();
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
	exit();
}

if(@$arguments['v'])
{
	$dbcore->verbose = 1;
}else
{
	$dbcore->verbose = 0;
}

if(@$arguments['f'])
{
	$dbcore->ForceDaemonRun = 1;
}else
{
	$dbcore->ForceDaemonRun = 0;
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
 - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level);
# Safely kill script if Daemon kill flag has been set
if($dbcore->checkDaemonKill())
{
	$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
	unlink($dbcore->pid_file);
	exit($dbcore->exit_msg);
}

$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");

#Checking for Geoname Jobs
$currentrun = date("Y-m-d G:i:s"); # Use PHP for Date/Time since it is already set to UTC and MySQL may not be set to UTC.
$sql = "SELECT `id`, `interval` FROM `wifi`.`schedule` WHERE `nodename` = ? And `daemon` = ? And `status` <> ? And `nextrun` <= ? And `enabled` = 1 LIMIT 1";
$prepgj = $dbcore->sql->conn->prepare($sql);
$prepgj->bindParam(1, $dbcore->node_name, PDO::PARAM_STR);
$prepgj->bindParam(2, $dbcore->daemon_name, PDO::PARAM_STR);
$prepgj->bindParam(3, $dbcore->StatusRunning, PDO::PARAM_STR);
$prepgj->bindParam(4, $currentrun, PDO::PARAM_STR);
$prepgj->execute();
var_dump($dbcore->ForceDaemonRun);
if($prepgj->rowCount() == 0 && !$dbcore->ForceDaemonRun)
{
	$dbcore->verbosed("There are no jobs that need to be run... I'll go back to waiting...");
}
else
{
	$dbcore->verbosed("Running...");
	$job = $prepgj->fetch(2);
	if(!$dbcore->ForceDaemonRun)
	{
		#Job Settings
		$job = $prepgj->fetch(2);
		$dbcore->job_interval = $job['interval'];
		$job_id = $job['id'];

		#Set Job to Running
		$dbcore->SetStartJob($job_id);
	}

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
	if(!$dbcore->ForceDaemonRun)
	{
		#Finished Job
		$dbcore->verbosed("Finished - Id:".$job_id, 1);

		#Set Next Run Job to Waiting
		$dbcore->SetNextJob($job_id);
		$dbcore->verbosed("Finished - Job: ".$dbcore->daemon_name , 1);
	}
}
unlink($dbcore->pid_file);