#!/usr/bin/php
<?php
/*
exportd.php, WiFiDB Export Daemon
Copyright (C) 2015 Andrew Calcutt, Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2015-06-08";
$dbcore->daemon_name	=	"Export";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: exportd.php [args...]
  -f		(null)			Force daemon to run without being scheduled.
  -o		(null)			Run a loop through the files waiting table, and end once done. ( Will override the -d argument. )
  -d		(null)			Run the Export script as a Daemon. 
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
$dbcore->pid_file = $dbcore->pid_file_loc.'exportd_'.$dbcore->This_is_me.'.pid';

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

#Checking for Export Jobs
$currentrun = time(); # Use PHP for Date/Time since it is already set to UTC and MySQL may not be set to UTC.
$sql = "SELECT `id`, `interval` FROM `schedule` WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `nextrun` <= ? And `enabled` = 1 LIMIT 1";
$prepgj = $dbcore->sql->conn->prepare($sql);
$prepgj->bindParam(1, $dbcore->node_name, PDO::PARAM_STR);
$prepgj->bindParam(2, $dbcore->daemon_name, PDO::PARAM_STR);
$prepgj->bindParam(3, $dbcore->StatusRunning, PDO::PARAM_STR);
$prepgj->bindParam(4, $currentrun, PDO::PARAM_INT);
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

		#Find How Many APs had GPS on the last run
		$sql = "SELECT `apswithgps` FROM `settings` LIMIT 1";
		$result =  $dbcore->sql->conn->query($sql);
		if($dbcore->sql->checkError(__LINE__, __FILE__))
		{
			$dbcore->verbosed("There was an error running the SQL");
			throw new ErrorException("There was an error running the SQL".var_export($dbcore->sql->conn->errorInfo(), 1));
		}
		$settingarray = $result->fetch(2);
		$apswithgps_last = $settingarray['apswithgps'];
		$dbcore->verbosed("APs with GPS on Last Run: ".$apswithgps_last);

		#Find How Many APs have GPS now
		$sql = "SELECT `id`, `ssid`, `ap_hash` FROM `wifi_pointers` WHERE `lat` != '0.0000'";
		$result = $dbcore->sql->conn->query($sql);
		if($dbcore->sql->checkError(__LINE__, __FILE__))
		{
			$dbcore->verbosed("There was an error running the SQL");
			throw new ErrorException("There was an error running the SQL".var_export($dbcore->sql->conn->errorInfo(), 1));
		}
		$apswithgps_now = $result->rowCount();
		$dbcore->verbosed("APs with GPS on Current Run: ".$apswithgps_now);

		#If current gps count is higher than last gps count, run the kml export daemon

		if ($apswithgps_last >= $apswithgps_now)
		{
			$dbcore->verbosed("Number of APs with GPS has not changed. Go Export something and try again.");
		}
		else
		{
			#Run Deamon Exports
			$dbcore->verbosed("Looks like there are some new results...now this script has something to eat...");
			$dbcore->verbosed("Running Daily and a Full DB KML Export if one does not already exists.");
			$dbcore->export->GenerateDaemonKMLData();

			#Set current number of APs with GPS into the settings table
			$sqlup2 = "UPDATE `settings` SET `apswithgps` = ? WHERE `id` = 1";
			$prep6 = $dbcore->sql->conn->prepare($sqlup2);
			$prep6->bindParam(1, $apswithgps_now, PDO::PARAM_INT);
			$prep6->execute();

			if($dbcore->NodeSyncing)
			{
				##### make sure export files are in sync with remote nodes
				$dbcore->verbosed("Synchronizing files between nodes...", 1);
				$cmd = '/opt/unison/sync_wifidb_exports > /opt/unison/log/sync_wifidb_exports 2>&1';
				exec ($cmd);
				#####
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
