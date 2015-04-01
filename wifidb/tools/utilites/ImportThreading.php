#!/usr/bin/php
<?php
/*
ImportThreading.php, WiFiDB Import Daemon
Copyright (C) 2015 by Phil Ferland.
This script is made to do imports and be run as a cron job.
It will run the number of import scripts as you set $NumberOfThreads to.
I did not see an imporvement in import time after 20 threads.
20 Threads imported 430,000 APs and their signal and GPS data in just over 1 hour and 45 minutes
This was with 8 vCPU's and 16GB of RAM.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/

/*
 * DANGER, Will Robinson!!
 * DANGER, Will Robinson!!
 * DANGER, Will Robinson!!
 * This Process WILL use a shit load of RAM.
 * On my Dev server with 8 vCPU's and 16GB of RAM, 20 import processes after 340,000 APs was using 8GB of RAM with the Console Output enabled
 * The 20 Processes with the Console Output disabled it only used about .
 * Just a reminder Don't say I didn't warn you that this script will most likely bring your server to its knees and beg for mercy.
 */
$NumberOfThreads = 20;

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit	=	"2015-03-28";
$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: importd.php [args...]
  -v		(null)			Run Verbosely (SHOW EVERYTHING!)
  -version	(null)			Version Info.
  -h		(null)			Show this screen.
  -l		(null)			Show License Information.
  -f		(null)			Force daemon to run without being scheduled.
  -t		(integer)		Identify the Import Daemon with a Thread ID. Used to track what thread was importing what file in the bab files table.
* = Not working yet.
";
	exit();
}

if(@$arguments['version'])
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

if(@$arguments['i'])
{
	$dbcore->ImportID = (int)$arguments['i'];
}else
{
	$dbcore->ImportID = 0;
}

if(@$arguments['f'])
{
	$dbcore->ForceDaemonRun = 1;
}else
{
	$dbcore->ForceDaemonRun = 0;
}

if(@$arguments['t'])
{
	$dbcore->thread_id = (int)$arguments['t'];
}else
{
	$dbcore->thread_id = 1;
}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$dbcore->pid_file = $dbcore->pid_file_loc.'importd_'.$dbcore->This_is_me.'.pid';

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


$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");
#Checking for Import Jobs
$currentrun = date("Y-m-d G:i:s"); # Use PHP for Date/Time since it is already set to UTC and MySQL may not be set to UTC.
$sql = "SELECT `id`, `interval` FROM `wifi`.`schedule` WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `nextrun` <= ? And `enabled` = 1 LIMIT 1";
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
}
else
{
	if (!$dbcore->ForceDaemonRun) {
		#Job Settings
		$job = $prepgj->fetch(2);
		$dbcore->job_interval = $job['interval'];
		$job_id = $job['id'];

		#Set Job to Running
		$dbcore->SetStartJob($job_id);
	}
	for ($i = 1; $i <= $NumberOfThreads; ++$i)
	{
		$dbcore->SpawnImportDaemon($i);
		sleep(2);
	}
	while (pcntl_waitpid(0, $status) != -1)
	{
		$status = pcntl_wexitstatus($status);
		echo "Import Thread $status completed\n";
		$dbcore->SpawnImportDaemon($status);
	}
}
