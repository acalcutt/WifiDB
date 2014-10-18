<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbosed("
WiFiDB 'Monitor Deamon Daemon'
Version: 1.0.0
- Daemon Start: 2014-10-17
( /tools/daemon/monitor_daemons.php )
- By: Andrew Calcutt ( acalcutt@techidiots.net )
- http://www.wifidb.net
");

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
    mkdir($dbcore->pid_file_loc);
}
$dbcore->pid_file = $dbcore->pid_file_loc.'monitord.pid';

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
//End write of PID

$nodename = $daemon_config['wifidb_nodename'];

$dbcore->verbosed("Start Monitoring of WiFiDB Daemons for $nodename");

while(1)
{
	$daemon_pids = array( );
	$daemon_pids["Import Export"] = "imp_expd.pid";
	$daemon_pids["Geoname"] = "geonamed.pid";

	foreach ($daemon_pids as $daemon_name => $pidfile) 
	{
		$dbcore->verbosed("Checking status of $daemon_name daemon", 1);
		$timestamp = date('Y-m-d G:i:s');
		$stats_imp_exp = $dbcore->getdaemonstats($pidfile);
		
		$pid = $stats_imp_exp["pid"];
		$time = $stats_imp_exp["time"];
		$mem = $stats_imp_exp["mem"];
		$cmd = $stats_imp_exp["cmd"];
		
		#echo "node:".$nodename."\r\n";
		#echo "pidefile:".$pidfile."\r\n";
		#echo "pid:".$pid."\r\n";
		#echo "time:".$time."\r\n";
		#echo "mem:".$mem."\r\n";
		#echo "cmd:".$cmd."\r\n";
		
		$daemon_sql = "SELECT * FROM `wifi`.`daemon_pid_stats` where `nodename` = '$nodename' AND `pidfile` = '$pidfile'";
		$result = $dbcore->sql->conn->query($daemon_sql);
		if($result->rowCount() > 0)
		{
			$sql = "UPDATE `wifi`.`daemon_pid_stats` SET `pid` = '$pid', `pidtime` = '$time', `pidmem` = '$mem', `pidcmd` = '$cmd' , `date` = '$timestamp' where `nodename` = '$nodename' AND `pidfile` = '$pidfile'";
			$result_update = $dbcore->sql->conn->query($sql);
		}else
		{
			$sql = "INSERT INTO `wifi`.`daemon_pid_stats` (nodename, pidfile, pid, pidtime, pidmem, pidcmd, date) VALUES ('$nodename', '$pidfile', '$pid', '$time', '$mem', '$cmd', '$timestamp')";
			$result_update = $dbcore->sql->conn->query($sql);
		}
	}
	
	sleep(30);
}

