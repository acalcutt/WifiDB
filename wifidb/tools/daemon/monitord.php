<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
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

$node_name = $daemon_config['wifidb_nodename'];

$dbcore->verbosed("Start Monitoring of WiFiDB Daemons for $node_name");

while(1)
{
	$timestamp = date('Y-m-d G:i:s');

	foreach (glob($dbcore->pid_file_loc."*.pid") as $file) {
		$pidfile = basename($file);
		if($pidfile == "monitord.pid"){continue;}

		$dbcore->verbosed("Checking status of $pidfile daemon", 1);

		$stats_imp_exp = $dbcore->getdaemonstats($pidfile);

		$pid = $stats_imp_exp["pid"];
		$time = $stats_imp_exp["time"];
		$mem = $stats_imp_exp["mem"];
		$cmd = $stats_imp_exp["cmd"];
		if($mem == "0%" && $dbcore->DeleteDeadPids)
		{
			$pid_contents = (int)trim(file_get_contents($dbcore->pid_file_loc.$pidfile));
			echo "Pid is dead.\n";
			unlink($dbcore->pid_file_loc.$pidfile);
			$sql = "DELETE FROM `daemon_pid_stats` where pid LIKE '$pid_contents'";
			$result_delete = $dbcore->sql->conn->query($sql);
			$dbcore->sql->checkError(__LINE__, __FILE__);
			continue;
		}
		#echo "node:".$node_name."\r\n";
		#echo "pidefile:".$pidfile."\r\n";
		#echo "pid:".$pid."\r\n";
		#echo "time:".$time."\r\n";
		#echo "mem:".$mem."\r\n";
		#echo "cmd:".$cmd."\r\n";

		$daemon_sql = "SELECT * FROM `daemon_pid_stats` where `nodename` = '$node_name' AND `pidfile` = '$pidfile'";
		$result = $dbcore->sql->conn->query($daemon_sql);
		if($result->rowCount() > 0)
		{
			$sql = "UPDATE `daemon_pid_stats` SET `pid` = '$pid', `pidtime` = '$time', `pidmem` = '$mem', `pidcmd` = '$cmd' , `date` = '$timestamp' where `nodename` = '$node_name' AND `pidfile` = '$pidfile'";
			$result_update = $dbcore->sql->conn->query($sql);
		}else
		{
			$sql = "INSERT INTO `daemon_pid_stats` (nodename, pidfile, pid, pidtime, pidmem, pidcmd, date) VALUES ('$node_name', '$pidfile', '$pid', '$time', '$mem', '$cmd', '$timestamp')";
			$result_update = $dbcore->sql->conn->query($sql);
		}
	}

	$sql = "DELETE FROM `daemon_pid_stats` where `date` <> '$timestamp' AND `nodename` = '$node_name'";
	$result_delete = $dbcore->sql->conn->query($sql);

	sleep(10);
}

