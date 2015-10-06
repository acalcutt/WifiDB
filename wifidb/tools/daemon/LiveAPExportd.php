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

$lastedit			=	"2015-08-30";
$dbcore->daemon_name	=	"LiveAPExport";

echo "
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level."\n\n\n";

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
$dbcore->pid_file = $dbcore->pid_file_loc.'LiveAPExportd_'.$dbcore->This_is_me.'.pid';

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

$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");


$sql = "SELECT
            `t1`.`id`,
            `t1`.`username`,
            `t1`.`session_id`,
            `t2`.`timestamp`,
            `t2`.`title`,
            `t2`.`notes`,
            `t2`.`completed`
        FROM `wifi`.`live_users` AS `t1`
            LEFT JOIN `wifi`.`live_titles` AS `t2`
            ON `t2`.`id` = `t1`.`title_id`
            ORDER BY `t2`.`timestamp` DESC";
var_dump("Before Fetch: ".microtime(1));
$return = $dbcore->sql->conn->query($sql);
$this->sql->checkError( $return, __LINE__, __FILE__);
var_dump("After Fetch: ".microtime(1));
$AllUsers = $return->fetchAll(2);
foreach($AllUsers as $user)
{
    $timestamp_int = strtotime($user['timestamp']);
    if((int)$user['completed'] === 1)
    {
        var_dump($user);
        echo "Getting APs for Title...\r\n";
        $user_sql = "SELECT `id` FROM `wifi`.`live_aps` WHERE `session_id` = ?";
        $user_prep = $dbcore->sql->conn->prepare($user_sql);
        $user_prep->bindParam(1, $user['session_id'], PDO::PARAM_STR);
        var_dump("Before Fetch Title Details: ".microtime(1));
        $this->sql->checkError( $user_prep->execute(), __LINE__, __FILE__);
        var_dump("After Fetch Table Details: ".microtime(1));
        $fetch = $user_prep->fetchAll(2);
        var_dump($fetch);
        foreach($fetch as $row)
        {
            echo "-----------------------------------------------------------------\r\n";
            var_dump($row['id']);
            $fetch_ap = $dbcore->GetLiveAP();
            foreach($fetch_ap as $sigHistory)
            {
                #var_dump($sigHistory);
                break;
            }
        }
    }
}
echo "DONE!!\r\n";