#!/usr/bin/php
<?php
/*
geonamed.php, WiFiDB Geoname Daemon
Copyright (C) 2015 Andrew Calcutt, Phil Ferland.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your /etc/wifidb/daemon.config.inc.php file in the [tools dir]/etc/wifidb/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/etc/wifidb/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2015-06-08";
$dbcore->daemon_name	=	"Geoname";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: geonamed.php [args...]
  -f		(null)			Force daemon to run without being scheduled.
  -o		(null)			Run a loop through the files waiting table, and end once done. ( Will override the -d argument. )
  -d		(null)			Run the Geoname script as a Daemon.
  -v		(null)			Run Verbosely (SHOW EVERYTHING!)
  -l		(null)			Show License Information.
  -h		(null)			Show this screen.
  --version	(null)			Version Info.

* = Not working yet.
";
	exit(-1);
}

if(@$arguments['version'])
{
	echo "WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals\n";
	exit(-2);
}

if(@$arguments['l'])
{
	echo "WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
Copyright (C) 2015 Andrew Calcutt, Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
";
	exit(-3);
}

if(@$arguments['v'])
{
	$dbcore->verbose = 1;
}
else
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

if(@$arguments['d'])
{
	$dbcore->daemonize = 1;
}else
{
	$dbcore->daemonize = 0;
}

if(@$arguments['o'])
{
	$dbcore->RunOnceThrough = 1;
}else
{
	$dbcore->RunOnceThrough = 0;
}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$dbcore->pid_file = $dbcore->pid_file_loc.'geonamed_'.$dbcore->This_is_me.'.pid';

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

$dbcore->verbosed("Have written the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")");

echo "
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level."\n";

$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");

#Checking for Geoname Jobs
$currentrun = date("Y-m-d G:i:s"); # Use PHP for Date/Time since it is already set to UTC and MySQL may not be set to UTC.
$sql = "SELECT `id`, `interval` FROM `schedule` WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `nextrun` <= ? And `enabled` = 1 LIMIT 1";
$prepgj = $dbcore->sql->conn->prepare($sql);
$prepgj->bindParam(1, $dbcore->node_name, PDO::PARAM_STR);
$prepgj->bindParam(2, $dbcore->daemon_name, PDO::PARAM_STR);
$prepgj->bindParam(3, $dbcore->StatusRunning, PDO::PARAM_STR);
$prepgj->bindParam(4, $currentrun, PDO::PARAM_STR);
$prepgj->execute();
$dbcore->sql->checkError(__LINE__, __FILE__);

if($prepgj->rowCount() === 0 && !$dbcore->ForceDaemonRun)
{
	$dbcore->verbosed("There are no jobs that need to be run... I'll go back to waiting...");
	unlink($dbcore->pid_file);
	exit(-6);
}
else
{
	if(!$dbcore->ForceDaemonRun)
	{
		#Job Settings
		$job = $prepgj->fetch(2);
		$dbcore->job_interval = $job['interval'];
		$job_id = $job['id'];

		#Set Job to Running
		$dbcore->SetStartJob($job_id);
	}

	While(1)
	{
		# Safely kill script if Daemon kill flag has been set
		if($dbcore->checkDaemonKill())
		{
			$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
			if(!$dbcore->ForceDaemonRun){$dbcore->SetNextJob($job_id);}
			unlink($dbcore->pid_file);
			echo "Daemon was told to kill itself\n";
			exit(-7);
		}

		#Start gathering Geonames
		$sql = "SELECT `id`,`lat`,`long`,`ap_hash` FROM `wifi_pointers` WHERE `geonames_id` = '' AND `lat` != '0.0000' ORDER BY `id` ASC";
		echo $sql."\r\n";
		$result = $dbcore->sql->conn->query($sql);
		$dbcore->verbosed("Gathered Wtable data");
		echo "Rows that need updating: ".$result->rowCount()."\r\n";
		sleep(4);
		while($ap = $result->fetch(1))
		{
			
			#$dbcore->verbosed($ap['id']." - ".$ap['ap_hash']);
			#$lat = $dbcore->convert->dm2dd($ap['lat']);
			#$long = $dbcore->convert->dm2dd($ap['long']);
			#$dbcore->verbosed("Lat - Long: ".$lat." [----] ".$long);
			#$sql = "SELECT `geonameid`, `country_code`, `admin1_code`, `admin2_code`, SQRT(POW((69.1 * (latitude - $lat)) , 2 ) + POW((53 * (longitude - $long)), 2)) AS distance 
			#		FROM geonames
			#		ORDER BY distance ASC 
			#		LIMIT 1";
			#echo $sql."\r\n";
			$dbcore->verbosed($ap['id']." - ".$ap['ap_hash']);
			$lat = $dbcore->convert->dm2dd($ap['lat']);
			$long = $dbcore->convert->dm2dd($ap['long']);
			$lat_rounded = number_format(round($lat, 1), 1, '.', '');
			$long_rounded = number_format(round($long, 1), 1, '.', '');
			$dbcore->verbosed("Lat - Long: ".$lat." [----] ".$long);
			$sql = "SELECT `geonameid`, `country_code`, `admin1_code`, `admin2_code`, SQRT(POW((69.1 * (latitude - $lat)) , 2 ) + POW((53 * (longitude - $long)), 2)) AS distance 
					FROM `geonames` 
					WHERE `latitude` LIKE '$lat_rounded%' AND `longitude` LIKE '$long_rounded%' 
					ORDER BY distance ASC 
					LIMIT 1";
			#echo $sql."\r\n";
			$dbcore->verbosed("Query Geonames Table to see if there is a location in an area that is equal to the geocord rounded to the first decimal.", 3);
			$geo_res = $dbcore->sql->conn->query($sql);
			$geo_array = $geo_res->fetch(1);
			if(!$geo_array['geonameid'])
			{continue;}

			$dbcore->verbosed("Geoname ID: ".$geo_array['geonameid']);
			$admin1_array = array('id'=>'');
			$admin2_array = array('id'=>'');
			if($geo_array['admin1_code'])
			{
				$dbcore->verbosed("Admin1 Code is Numeric, need to query the admin1 table for more information.");
				$admin1 = $geo_array['country_code'].".".$geo_array['admin1_code'];

				$sql = "SELECT `id` FROM `geonames_admin1` WHERE `admin1`='$admin1'";
				$admin1_res = $dbcore->sql->conn->query($sql);
				$admin1_array = $admin1_res->fetch(PDO::FETCH_ASSOC);
			}
			if(is_numeric($geo_array['admin2_code']))
			{
				$dbcore->verbosed("Admin2 Code is Numeric, need to query the admin2 table for more information.");
				$admin2 = $geo_array['country_code'].".".$geo_array['admin1_code'].".".$geo_array['admin2_code'];
				$sql = "SELECT `id` FROM `geonames_admin2` WHERE `admin2`='$admin2'";
				$admin2_res = $dbcore->sql->conn->query($sql);
				$admin2_array = $admin2_res->fetch(PDO::FETCH_ASSOC);
			}

			$sql = "UPDATE `wifi_pointers` SET `geonames_id` = '{$geo_array['geonameid']}', `admin1_id` = '{$admin1_array['id']}', `admin2_id` = '{$admin2_array['id']}', `country_code` = '{$geo_array['country_code']}' WHERE `ap_hash` = '{$ap['ap_hash']}'";
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
			$dbcore->verbosed("Next Job Schedule Set for: ".$dbcore->daemon_name , 1);
		}
		
		if($dbcore->daemonize)
		{
			$dbcore->verbosed("We have been told to become a daemon, will sleep for the defined time period of $dbcore->DaemonSleepTime seconds.", 1);
			sleep($dbcore->DaemonSleepTime);
		}
		else
		{
			$dbcore->verbosed("not set to run as a daemon, exiting.");
			$dbcore->return_message = -9;
			break;
		}
	}
}
unlink($dbcore->pid_file);