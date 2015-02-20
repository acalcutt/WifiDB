#!/usr/bin/php
<?php
/*
scheduled.php, WiFiDB Schedule Daemon
Copyright (C) 2015 Andrew Calcutt, based on imp_expd.php by Phil Ferland. 
This script is made to do Schedules and be run as a cron job. 

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2015-02-11";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
    echo "Usage: scheduled.php [args...]
  -v               Run Verbosely (SHOW EVERYTHING!)
  -c               Location of the config file you want to load. *
  -L               Log Daemon output to a file. *
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
Schedule Daemon 1.0, {$lastedit}, GPLv2");
    exit();
}

if(@$arguments['l'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
Schedule Daemon 1.0, {$lastedit}, GPLv2

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
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
$dbcore->pid_file = $dbcore->pid_file_loc.'scheduled.pid';

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
 - Schedule Daemon 1.0, {$lastedit}, GPLv2
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level);
# Safely kill script if Daemon kill flag has been set
if($dbcore->checkDaemonKill())
{
    $dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
    exit($dbcore->exit_msg);
}

#Find Scheduled Jobs for current nodes
$nodename = $daemon_config['wifidb_nodename'];
$status_running = "Running";
$status_waiting = "Waiting";
$status_error = "Error";
$dbcore->verbosed("Running jobs for $nodename");

$daemon_path = "/opt/wifidb-0-30/tools/daemon/";
$php_path = "/usr/bin/php";

$daemon = array( );
$daemon["Import"] = "importd.php";
$daemon["Export"] = "exportd.php";
$daemon["Geoname"] = "geonamed.php";

foreach ($daemon as $daemon_name => $daemon_file) 
{
	$sql = "SELECT `id`, `interval` FROM `wifi`.`schedule` WHERE `nodename` = ? And `daemon` = ? And `status` <> ? And `nextrun` <= now() And `enabled` = 1 LIMIT 1";
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->bindParam(1, $nodename, PDO::PARAM_STR);
	$prep->bindParam(2, $daemon_name, PDO::PARAM_STR);
	$prep->bindParam(3, $status_running, PDO::PARAM_STR);
	$prep->execute();
	$job_fetch = $prep->fetchAll(2);
	foreach($job_fetch as $job)
    {
		#Job Settings
		$job_id = $job['id'];
		$job_cmd = $php_path." ".$daemon_path.$daemon_file;
		$dbcore->verbosed($job_cmd);
		$job_interval = $job['interval'];
		if($job_interval < '5'){$job_interval = '5';} //its really pointless to check more then 5 min at a time
		
		#Set job to Running
		$sql = "UPDATE `wifi`.`schedule` SET `status` = ? WHERE `id` = ?";
		$prep2 = $dbcore->sql->conn->prepare($sql);
		$prep2->bindParam(1, $status_running, PDO::PARAM_STR);
		$prep2->bindParam(2, $job_id, PDO::PARAM_INT);
		$prep2->execute();
		
		#Execute Job Command
		passthru($job_cmd, $return);
		if(!$return){$ret_status = $status_waiting;}else{$ret_status = $status_error;}
		
		#Set Next run time and status
		$nextrun = date("Y-m-d G:i:s", strtotime("+".$job_interval." minutes"));
		$dbcore->verbosed("Setting ".$daemon_name." to ".$ret_status." and next run to ".$nextrun, 1);

		$sql = "UPDATE `wifi`.`schedule` SET `nextrun` = ? , `status` = ? WHERE `id` = ?";
		$prep3 = $dbcore->sql->conn->prepare($sql);
		$prep3->bindParam(1, $nextrun, PDO::PARAM_STR);
		$prep3->bindParam(2, $ret_status, PDO::PARAM_STR);
		$prep3->bindParam(3, $job_id, PDO::PARAM_INT);
		$prep3->execute();

	}
}
unlink($dbcore->pid_file);