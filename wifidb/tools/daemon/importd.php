#!/usr/bin/php
<?php
error_reporting(E_ALL);
/*
importd.php, WiFiDB Import Daemon
Copyright (C) 2019 Andrew Calcutt, Phil Ferland.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2019-04-14";
$dbcore->daemon_name	=	"Import";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: importd.php [args...]
  -f		(null)			Force daemon to run without being scheduled.
  -o		(null)			Run a loop through the files waiting table, and end once done. ( Will override the -d argument. )
  -d		(null)			Run the Import script as a Daemon. ( Will override the -i argument. )
  -i        	(integer)       	The ID Number for the Import to be well... Imported... ( Not to be used with the -d argument. )
  -t		(integer)		Identify the Import Daemon with a Thread ID. Used to track what thread was importing what file in the bad files table.
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
if(@$arguments['i']){$dbcore->ImportID = (int)$arguments['i'];}else{$dbcore->ImportID = 0;}
if(@$arguments['f']){$dbcore->ForceDaemonRun = 1;}else{$dbcore->ForceDaemonRun = 0;}
if(@$arguments['t']){$dbcore->thread_id = (int)$arguments['t'];}else{$dbcore->thread_id = 1;}
if(@$arguments['d']){$dbcore->daemonize = 1;}else{$dbcore->daemonize = 0;}
if(@$arguments['o']){$dbcore->RunOnceThrough = 1;}else{$dbcore->RunOnceThrough = 0;}
if(@$arguments['logfile']){$dbcore->LogFile = $arguments['logfile'];}else{$dbcore->LogFile = "";}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$pid_filename = 'importd_'.$dbcore->This_is_me.'_'.date("YmdHis").'.pid';
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
	
	#Checking for Import Jobs
	$currentrun = date("Y-m-d G:i:s"); # Use PHP for Date/Time since it is already set to UTC and MySQL may not be set to UTC.

	#Claim a import schedule ID
	if($dbcore->sql->service == "mysql")
		{$sql = "UPDATE `schedule` SET `pid` = ?, `pidfile` = ?, `logfile` = ?, `status` = ? WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `nextrun` <= ? And `enabled` = 1 LIMIT 1";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sql = "UPDATE TOP (1) [schedule] SET [pid] = ?, [pidfile] = ?, [logfile] = ?, [status] = ? WHERE [nodename] = ? And [daemon] = ? And [status] != ? And [nextrun] <= ? And [enabled] = 1";}
	$prepus = $dbcore->sql->conn->prepare($sql);
	$prepus->bindParam(1, $dbcore->This_is_me, PDO::PARAM_INT);
	$prepus->bindParam(2, $pid_filename, PDO::PARAM_STR);
	$prepus->bindParam(3, $dbcore->LogFile, PDO::PARAM_STR);
	$prepus->bindParam(4, $dbcore->StatusRunning, PDO::PARAM_STR);
	$prepus->bindParam(5, $dbcore->node_name, PDO::PARAM_STR);
	$prepus->bindParam(6, $dbcore->daemon_name, PDO::PARAM_STR);
	$prepus->bindParam(7, $dbcore->StatusRunning, PDO::PARAM_STR);
	$prepus->bindParam(8, $currentrun, PDO::PARAM_STR);
	$prepus->execute();

	#Start importing claimed schedule ID, if one exists
	$sql = "SELECT id, interval FROM schedule WHERE pid = ? And pidfile = ?";
	$prepgj = $dbcore->sql->conn->prepare($sql);
	$prepgj->bindParam(1, $dbcore->This_is_me, PDO::PARAM_INT);
	$prepgj->bindParam(2, $pid_filename, PDO::PARAM_STR);
	$prepgj->execute();
	
	if($prepgj->rowCount() === 0)
	{
		$dbcore->verbosed("There are no jobs that need to be run... I'll go back to waiting...");
		unlink($dbcore->pid_file);
		exit(-6);
	}
	
	$job = $prepgj->fetch(2);
	$job_id = $job['id'];	
	$dbcore->job_interval = $job['interval'];
	$dbcore->verbosed("Job ID: $job_id , Interval:".$dbcore->job_interval);
}

$dbcore->verbosed("Starting Import on Proc: ".$dbcore->thread_id);
if(!$dbcore->ForceDaemonRun)
{
	#Set Job to Running
	$dbcore->SetStartJob($job_id);
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
	$dbcore->verbosed("Attempting to get the next Import ID.");
	if( $dbcore->ImportID > 0 AND ( !$dbcore->daemonize AND !$dbcore->RunOnceThrough ) )
	{
		$NextID = $dbcore->ImportID;

	}elseif($dbcore->daemonize OR $dbcore->RunOnceThrough) {
		$NextID = $dbcore->GetNextImportID();
	}
	//var_dump($NextID);

	$daemon_sql = "SELECT id, file_name, file_orig, file_user, otherusers, notes, title, file_date, size, hash, type, tmp_id FROM files_importing WHERE id = ?";
	$result = $dbcore->sql->conn->prepare($daemon_sql);
	$result->bindParam(1, $NextID, PDO::PARAM_INT);
	$result->execute();
	if ($dbcore->sql->checkError(__LINE__, __FILE__)) {
		$dbcore->verbosed("There was an error getting an import file");
		$dbcore->return_message = -8;
		break;
	}
	//var_dump($result->rowCount());
	//var_dump($dbcore->RunOnceThrough);
	if( ( ( $result->rowCount() === 0 ) AND $dbcore->RunOnceThrough))
	{
		$dbcore->verbosed("There are no imports waiting, go import something and funny stuff will happen.");
		$dbcore->return_message = -9;
		break;
	}
	else
	{
		if($dbcore->NodeSyncing)
		{
			##### make sure import/export files are in sync with remote nodes
			#$dbcore->verbosed("Synchronizing files between nodes...", 1);
			#$cmd = '/opt/unison/sync_wifidb_imports > /opt/unison/log/sync_wifidb_imports 2>&1';
			#exec($cmd);
			#####
		}

		$file_to_Import = $result->fetch(2);
		if(!@$file_to_Import['id'])
		{
			$dbcore->verbosed("Error fetching data.... Skipping row for admin to check into it.");
			if( !$dbcore->daemonize )
			{
				$dbcore->return_message = -10;
				break;
			}
		}else
		{
			$ImportProcessReturn = $dbcore->ImportProcess($file_to_Import);
			#$ImportProcessReturn = 1;
			$dbcore->return_message = (int)$file_to_Import['id'];

			switch($ImportProcessReturn)
			{
				#Error converting file for single run through, break loop.
				case -1:
					break;
				#Error Converting file for daemon, continue run.
				case 0:
					continue;
				case 1:
					$dbcore->verbosed("Import function inside the daemon Completed With A Return Of : 1");
			}
		}
	}
	if($dbcore->ImportID !== 0)
	{
		break;
	}
	if($dbcore->daemonize)
	{
		$dbcore->verbosed("We have been told to become a daemon, will sleep for the defined time period of $dbcore->DaemonSleepTime seconds.", 1);
		sleep($dbcore->DaemonSleepTime);
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