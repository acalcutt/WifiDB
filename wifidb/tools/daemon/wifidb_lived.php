<?php
error_reporting(E_ALL|E_STRICT);
global $screen_output;
$screen_output = "CLI";

if(!(require_once 'config.inc.php')){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}

if($wifidb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
#require_once $wifidb_install."/lib/database.inc.php";
require_once $wifidb_install."/lib/daemon.inc.php";
require_once $wifidb_install."/lib/config.inc.php";
if(!file_exists($GLOBALS['daemon_log_folder']))
{
    if(mkdir($GLOBALS['daemon_log_folder']))
    {echo "Made WiFiDB Log Folder [".$GLOBALS['daemon_log_folder']."]\r\n";}
    else{echo "Could not make Log Folder [".$GLOBALS['daemon_log_folder']."]\r\n";}
}
if(!file_exists($GLOBALS['pid_file_loc']))
{
    if(mkdir($GLOBALS['pid_file_loc']))
    {echo "Made WiFiDB PID Folder [".$GLOBALS['pid_file_loc']."]\r\n";}
    else{echo "Could not make PID Folder [".$GLOBALS['pid_file_loc']."]\r\n";}
}

$dim = @DIRECTORY_SEPERATOR;
date_default_timezone_set("UTC");
ini_set("memory_limit","3072M"); //lots of objects need lots of memory, that and shitty programing from a fucking idiot of a developer
if(!file_exists($GLOBALS['pid_file_loc']))
{
    if(mkdir($GLOBALS['pid_file_loc']))
    {echo "Made WiFiDB PID Folder [".$GLOBALS['pid_file_loc']."]\r\n";}
    else{echo "Could not make PID Folder [".$GLOBALS['pid_file_loc']."]\r\n";}
}
$This_is_me     =   getmypid();
$pid_file       =   $GLOBALS['pid_file_loc'].'wifidb_lived.pid';

$fileappend = fopen($pid_file, "w");
$write_pid = fwrite($fileappend, $This_is_me);

verbosed("
WiFiDB 'Live AP Daemon'
Version: 1.0.0
 - Daemon Start: 14-May-2011
 - Last Daemon File Edit: 2011-Apr-2011
	(/tools/daemon/wifidb_lived.php)
 - By: Phillip Ferland ( pferland@randomintervals.com )
 - http://www.randomintervals.com

PID: [ $This_is_me ]", $verbose, $screen_output, 0);
$daemon = new daemon($host, $db_user, $db_pwd);
$daemon->live_timeout = 10;
$daemon->live_run_timeout = 3600;
$daemon->sleep_time = 30;
$i=0;
while(1)
{
    echo "Running ($i)\r\n";
    $i++;
    $users = array();
    $sql = "SELECT * FROM `$db`.`$daemon->live_aps` ORDER BY `username` ASC";
    $result = $daemon->conn->query($sql);
    while($live_table = $result->fetch_array(1))
    {
        $users[] = $live_table['username'];
    }
    $sql = "SELECT * FROM `$db`.`$daemon->live_stage` ORDER BY `username` ASC";
    $result1 = $daemon->conn->query($sql);
    while($stage_table = $result1->fetch_array(1))
    {
        $users[] = $stage_table['username'];
    }

    $users = array_unique($users);
    foreach($users as $user)
    {
        //Find no active APs and move them to the staging tables
        echo "Checking ($user) Live AP Stage...\r\n";
        $daemon->live_stage($user);
        
        //Check the staging table and see when the last AP for each user was last updated,
        //if it is more then the defined limit, move it to be imported
        echo "Running check ($user) Live AP Migrate...\r\n";
        $sql = "SELECT * FROM `$db`.`$daemon->live_titles` WHERE `username` = '$user' limit 1";
        $result3 = $daemon->conn->query($sql);
        $title_table = $result3->fetch_array(1);
        $id    = $title_table['id'];
        $title = $title_table['title'];
        $notes = $title_table['notes'];
        if($daemon->live_migrate($user, $title, $notes))
        {
            $sql = "DELETE FROM `$db`.`$daemon->live_titles` WHERE `id` = '$id'";
            echo $sql."\r\n";
            if($daemon->conn->query($sql))
            {
                echo "Failed to remove the Import title from the table. :/ ($id)\r\n";
            }
        }
    }
    echo "Shhhh. The Trolls are Sleeping (sec: $daemon->sleep_time)\r\n";
    sleep($daemon->sleep_time);
}
?>
