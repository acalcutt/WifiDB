#!/usr/bin/php
<?php
/*
exportd.php, WiFiDB Export Daemon
Copyright (C) 2015 Andrew Calcutt, based on imp_expd.php by Phil Ferland. 
This script is made to do exports and be run as a cron job. 

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit  = "2015-03-07";
$daemon_name = "Export";
$daemon_version = "1.0";
$node_name = $daemon_config['wifidb_nodename'];

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
    echo "Usage: exportd.php [args...]
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
$dbcore->pid_file = $dbcore->pid_file_loc.'exportd_'.time().'.pid';

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
$sql = "SELECT `id`, `interval` FROM `wifi`.`schedule` WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `nextrun` <= now() And `enabled` = 1 LIMIT 1";
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
	$job = $prepgj->fetch(2);
    #Job Settings
    $job_id = $job['id'];
    $job_interval = $job['interval'];
    //if($job_interval < '5'){$job_interval = '5';} //its really pointless to check more then 5 min at a time

    #Set Job to Running
    $dbcore->verbosed("Starting - Job:".$daemon_name." Id:".$job_id, 1);
    $sql = "UPDATE `wifi`.`schedule` SET `status` = ? WHERE `id` = ?";
    $prepsr = $dbcore->sql->conn->prepare($sql);
    $prepsr->bindParam(1, $daemon_config['status_running'], PDO::PARAM_STR);
    $prepsr->bindParam(2, $job_id, PDO::PARAM_INT);
    $prepsr->execute();

    #Find How Many APs had GPS on the last run
    $sql = "SELECT `size` FROM `wifi`.`settings` WHERE `table` = 'apswithgps'";
    $result =  $dbcore->sql->conn->query($sql);
    if($dbcore->sql->checkError(__LINE__, __FILE__))
    {
        $dbcore->verbosed("There was an error running the SQL");
        throw new ErrorException("There was an error running the SQL".var_export($dbcore->sql->conn->errorInfo(), 1));
    }
    $settingarray = $result->fetch(2);
    $apswithgps_last = $settingarray['size'];
    $dbcore->verbosed("APs with GPS on Last Run: ".$apswithgps_last);

    #Find How Many APs have GPS now
    $sql = "SELECT `id`, `ssid`, `ap_hash` FROM `wifi`.`wifi_pointers` WHERE `lat` != '0.0000'";
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
        $dbcore->verbosed("Number of APs with GPS has not changed. go import something and try again.");
    }
    else
    {
        $dbcore->verbosed("Looks like there are some new results...now this script has something to eat...");

        #Run Deamon Exports
        $dbcore->verbosed("Running Daily and a Full DB KML Export if one does not already exists.");

        $dbcore->export->GenerateDaemonKMLData();

        #Set current number of APs with GPS into the settings table
        $sqlup2 = "UPDATE `wifi`.`settings` SET `size` = ? WHERE `table` = 'apswithgps'";
        $prep6 = $dbcore->sql->conn->prepare($sqlup2);
        $prep6->bindParam(1, $apswithgps_now, PDO::PARAM_INT);
        $prep6->execute();
        if(!$dbcore->sql->checkError())
        {
            $nextrun = date("Y-m-d G:i:s", strtotime("+".$job_interval." minutes"));
            $dbcore->verbosed("Updated settings table with next run time: ".$nextrun);
        }

        ##### make sure import/export files are in sync with remote nodes
        $dbcore->verbosed("Synchronizing files between nodes...", 1);
        $cmd = '/opt/unison/sync_wifidb_exports > /opt/unison/log/sync_wifidb_exports 2>&1';
        #exec ($cmd);
        #####
    }

    #Set Next Run Job to Waiting
    $nextrun = date("Y-m-d G:i:s", strtotime("+".$job_interval." minutes"));
    $dbcore->verbosed("Setting Job Next Run to ".$nextrun, 1);
    $sql = "UPDATE `wifi`.`schedule` SET `nextrun` = ? , `status` = ? WHERE `id` = ?";
    $prepnr = $dbcore->sql->conn->prepare($sql);
    $prepnr->bindParam(1, $nextrun, PDO::PARAM_STR);
    $prepnr->bindParam(2, $daemon_config['status_waiting'], PDO::PARAM_STR);
    $prepnr->bindParam(3, $job_id, PDO::PARAM_INT);
    $prepnr->execute();

    #Finished Job
    $dbcore->verbosed("Finished - Job:".$daemon_name." Id:".$job_id, 1);
}
unlink($dbcore->pid_file);