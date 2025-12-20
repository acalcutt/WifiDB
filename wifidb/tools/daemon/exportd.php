#!/usr/bin/php
<?php
/*
exportd.php, WiFiDB Export Daemon
Copyright (C) 2019 Andrew Calcutt, Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2019-04-14";
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
  --logfile=filename.log	Specify the log file name so it can be written to the schedule db

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
Copyright (C) 2019 Andrew Calcutt, Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
";
	exit(-3);
}

if(@$arguments['v']){$dbcore->verbose = 1;}else{$dbcore->verbose = 0;}
if(@$arguments['f']){$dbcore->ForceDaemonRun = 1;}else{$dbcore->ForceDaemonRun = 0;}
if(@$arguments['d']){$dbcore->daemonize = 1;}else{$dbcore->daemonize = 0;}
if(@$arguments['o']){$dbcore->RunOnceThrough = 1;}else{$dbcore->RunOnceThrough = 0;}
if(@$arguments['logfile']){$dbcore->LogFile = $arguments['logfile'];}else{$dbcore->LogFile = "";}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$pid_filename = 'exportd_'.$dbcore->This_is_me.'_'.date("YmdHis").'.pid';
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

$dbcore->verbosed("Have written the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")");

echo "
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level."\n";

$job_id = 0;
$dbcore->job_interval = 15;
if(!$dbcore->ForceDaemonRun)
{
	$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");

	#Claim a import schedule ID
	if($dbcore->sql->service == "mysql")
		{$sql = "UPDATE `schedule` SET `pid` = ?, `pidfile` = ?, `logfile` = ?, `status` = ? WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `enabled` = 1 LIMIT 1";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sql = "UPDATE TOP (1) [schedule] SET [pid] = ?, [pidfile] = ?, [logfile] = ?, [status] = ? WHERE [nodename] = ? And [daemon] = ? And [status] != ? And [enabled] = 1";}
	$prepus = $dbcore->sql->conn->prepare($sql);
	$prepus->bindParam(1, $dbcore->This_is_me, PDO::PARAM_INT);
	$prepus->bindParam(2, $pid_filename, PDO::PARAM_STR);
	$prepus->bindParam(3, $logfile, PDO::PARAM_STR);
	$prepus->bindParam(4, $dbcore->StatusRunning, PDO::PARAM_STR);
	$prepus->bindParam(5, $dbcore->node_name, PDO::PARAM_STR);
	$prepus->bindParam(6, $dbcore->daemon_name, PDO::PARAM_STR);
	$prepus->bindParam(7, $dbcore->StatusRunning, PDO::PARAM_STR);
	$prepus->execute();

	#Start importing claimed schedule ID, if one exists
	$sql = "SELECT id, interval FROM schedule WHERE pid = ? And pidfile = ?";
	$prepgj = $dbcore->sql->conn->prepare($sql);
	$prepgj->bindParam(1, $dbcore->This_is_me, PDO::PARAM_INT);
	$prepgj->bindParam(2, $pid_filename, PDO::PARAM_STR);
	$prepgj->execute();
	
	if($prepgj->rowCount() === 0)
	{
		$dbcore->verbosed("There are no jobs that need to be run... Exiting...");
		unlink($dbcore->pid_file);
		exit(-6);
	}
	
	$job = $prepgj->fetch(2);
	$job_id = $job['id'];	
	$dbcore->job_interval = $job['interval'];
	$dbcore->verbosed("Job ID: $job_id , Interval:".$dbcore->job_interval);
}

While(1)
{
	# Safely kill script if Daemon kill flag has been set
	if($dbcore->checkDaemonKill($job_id))
	{
		$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
		if(!$dbcore->ForceDaemonRun){$dbcore->SetNextJob($job_id);}
		unlink($dbcore->pid_file);
		echo "Daemon was told to kill itself\n";
		exit(-7);
	}

	#Find How Many APs had GPS on the last run
	if($dbcore->sql->service == "mysql")
		{$sql = "SELECT `apswithgps` FROM `settings` WHERE `node_name` = ? LIMIT 1";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sql = "SELECT TOP 1 [apswithgps] FROM [settings] WHERE [node_name] = ?";}
	$prep5 = $dbcore->sql->conn->prepare($sql);
	$prep5->bindParam(1, $dbcore->node_name, PDO::PARAM_STR);
	$prep5->execute();
	$settingarray = $prep5->fetch(2);
	$apswithgps_last = $settingarray['apswithgps'];
	$dbcore->verbosed("APs with GPS on Last Run: ".$apswithgps_last);

	#Find How Many APs have GPS now
	$sql = "SELECT Count(AP_ID) As ap_count FROM wifi_ap WHERE HighGPS_ID IS NOT NULL";
	$result = $dbcore->sql->conn->query($sql);
	$result->execute();
	$ap_countarray = $result->fetch(2);
	$apswithgps_now = $ap_countarray['ap_count'];
	$dbcore->verbosed("APs with GPS on Current Run: ".$apswithgps_now);

	#If current gps count is higher than last gps count, run the kml export daemon

	if ($apswithgps_last >= $apswithgps_now)
	{
		$dbcore->verbosed("Number of APs with GPS has not changed. Go import something and try again.");
	}
	else
	{
		#Run Deamon Exports
		$dbcore->verbosed("Looks like there are some new results...now this script has something to eat...");
		$dbcore->verbosed("Running Daily and a Full DB KML Export if one does not already exists.");
		$dbcore->export->GenerateDaemonKMLData($dbcore->verbose);

		#Set current number of APs with GPS into the settings table
		if($dbcore->sql->service == "mysql")
			{$sqlup2 = "UPDATE `settings` SET `apswithgps` = ? WHERE `node_name` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sqlup2 = "UPDATE [settings] SET [apswithgps] = ? WHERE [node_name] = ?";}
		$prep6 = $dbcore->sql->conn->prepare($sqlup2);
		$prep6->bindParam(1, $apswithgps_now, PDO::PARAM_INT);
		$prep6->bindParam(2, $dbcore->node_name, PDO::PARAM_STR);
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
if(!$dbcore->ForceDaemonRun)
{
	#Finished Job
	$dbcore->verbosed("Finished - Id:".$job_id, 1);
	#Set Next Run Job to Waiting
	$dbcore->SetNextJob($job_id);
	$dbcore->verbosed("Next Job Schedule Set for: ".$dbcore->daemon_name , 1);
}
unlink($dbcore->pid_file);		
exit($dbcore->return_message);
