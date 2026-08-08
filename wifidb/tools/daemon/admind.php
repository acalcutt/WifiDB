#!/usr/bin/php
<?php
error_reporting(E_ALL);
/*
admind.php, WiFiDB Admin Jobs Daemon
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit = "2025-12-21";
$dbcore->daemon_name = "Admin";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: admind.php [args...]
  -f		(null)			Force daemon to run without being scheduled.
  -o		(null)			Run a loop through admin jobs, and end once done.
  -d		(null)			Run the Admin script as a Daemon.
  -v		(null)			Run Verbosely (SHOW EVERYTHING!)
  -l		(null)			Show License Information.
  -h		(null)			Show this screen.
  --version	(null)			Version Info.
  --logfile=filename.log	Specify the log file name so it can be written to the schedule db

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
Copyright (C) 2025 Andrew Calcutt

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

// Write PID file
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$pid_filename = 'admind_'.$dbcore->This_is_me.'_'.date("YmdHis").'.pid';
$dbcore->pid_file = $dbcore->pid_file_loc.$pid_filename;

if(!file_exists($dbcore->pid_file_loc))
{
	if(!mkdir($dbcore->pid_file_loc))
	{
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
$dbcore->job_interval = 1; // Check every 1 minute by default

if(!$dbcore->ForceDaemonRun)
{
	$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");

	$currentrun = date("Y-m-d G:i:s");

	// Claim a schedule ID
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

	// Get claimed schedule ID
	$sql = "SELECT schedule.id, schedule.interval FROM schedule WHERE pid = ? And pidfile = ?";
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

$dbcore->verbosed("Starting Admin Jobs Processing");
if(!$dbcore->ForceDaemonRun)
{
	$dbcore->SetStartJob($job_id);
}

While(1)
{
	// Check daemon kill flag
	if($dbcore->checkDaemonKill($job_id))
	{
		$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
		if(!$dbcore->ForceDaemonRun){$dbcore->SetNextJob($job_id);}
		unlink($dbcore->pid_file);
		echo "Daemon was told to kill itself\n";
		exit(-7);
	}

	$dbcore->verbosed("Checking for pending admin jobs...");

	// Get next pending job
	if($dbcore->sql->service == "mysql")
		{$sql = "SELECT id, job_type, target_id, target_table, requested_by FROM admin_jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sql = "SELECT TOP 1 id, job_type, target_id, target_table, requested_by FROM admin_jobs WHERE status = 'pending' ORDER BY created_at ASC";}
	else if($dbcore->sql->service == "pgsql")
		{$sql = "SELECT id, job_type, target_id, target_table, requested_by FROM admin_jobs WHERE status = 'pending' ORDER BY created_at ASC LIMIT 1";}

	$result = $dbcore->sql->conn->prepare($sql);
	$result->execute();

	if($result->rowCount() === 0)
	{
		if($dbcore->RunOnceThrough)
		{
			$dbcore->verbosed("There are no admin jobs waiting.");
			$dbcore->return_message = -9;
			break;
		}
	}
	else
	{
		$admin_job = $result->fetch(PDO::FETCH_ASSOC);
		$admin_job_id = $admin_job['id'];

		$dbcore->verbosed("Processing admin job ID: $admin_job_id, Type: {$admin_job['job_type']}");

		// Mark job as running
		$sql = "UPDATE admin_jobs SET status = 'running', started_at = ?, nodename = ?, pid = ? WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$started_at = date('Y-m-d H:i:s');
		$prep->bindParam(1, $started_at, PDO::PARAM_STR);
		$prep->bindParam(2, $dbcore->node_name, PDO::PARAM_STR);
		$prep->bindParam(3, $dbcore->This_is_me, PDO::PARAM_INT);
		$prep->bindParam(4, $admin_job_id, PDO::PARAM_INT);
		$prep->execute();

		// Process the job
		$job_result = process_admin_job($dbcore, $admin_job);

		// Update job status
		$completed_at = date('Y-m-d H:i:s');
		if($job_result['success'])
		{
			$status = 'completed';
			$message = $job_result['message'] ?? 'Job completed successfully';
		}
		else
		{
			$status = 'failed';
			$message = $job_result['error'] ?? 'Job failed';
		}

		$sql = "UPDATE admin_jobs SET status = ?, message = ?, completed_at = ? WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $status, PDO::PARAM_STR);
		$prep->bindParam(2, $message, PDO::PARAM_STR);
		$prep->bindParam(3, $completed_at, PDO::PARAM_STR);
		$prep->bindParam(4, $admin_job_id, PDO::PARAM_INT);
		$prep->execute();

		$dbcore->verbosed("Job ID $admin_job_id completed with status: $status");
	}

	if($dbcore->daemonize)
	{
		$dbcore->verbosed("Sleeping for $dbcore->DaemonSleepTime seconds.", 1);
		sleep($dbcore->DaemonSleepTime);
	}
	else
	{
		break;
	}
}

if(!$dbcore->ForceDaemonRun)
{
	$dbcore->SetNextJob($job_id);
}
unlink($dbcore->pid_file);
$dbcore->verbosed("Done");
exit($dbcore->return_message);

/**
 * Process an admin job based on its type
 */
function process_admin_job($dbcore, $job)
{
	$job_type = $job['job_type'];
	$target_id = $job['target_id'];
	$target_table = $job['target_table'];

	switch($job_type)
	{
		case 'reset_file':
			return reset_file($dbcore, $target_id);

		case 'delete_file':
			return delete_file($dbcore, $target_id, 'deleted');

		case 'user_delete_file':
			return delete_file($dbcore, $target_id, 'user_deleted');

		case 'reset_failed_file':
			return reset_failed_file($dbcore, $target_id);

			case 'reset_files_bad':
				return reset_files_bad($dbcore, $target_id);

		case 'delete_failed_file':
			return delete_failed_file($dbcore, $target_id);

		case 'delete_pidfile':
			// target_table contains the pidfile name for this job type
			return delete_pidfile($dbcore, $target_table);

		default:
			return array('success' => false, 'error' => 'Unknown job type: ' . $job_type);
	}
}

/**
 * Reset a file by removing its data and re-queuing for import
 */
function reset_file($dbcore, $File_ID)
{
	try
	{
		// Go through APs with this File ID and update to alternate file if available
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `AP_ID` FROM `wifi_ap` WHERE File_ID = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [AP_ID] FROM [wifi_ap] WHERE File_ID = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sql = "SELECT \"AP_ID\" FROM wifi_ap WHERE \"File_ID\" = ?";}
		$apl = $dbcore->sql->conn->prepare($sql);
		$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
		$apl->execute();

		while($ap = $apl->fetch(PDO::FETCH_NUM))
		{
			$AP_ID = $ap[0];

			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? AND `File_ID` != ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [File_ID] FROM [wifi_hist] WHERE [AP_ID] = ? AND [File_ID] != ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqlhp = "SELECT \"File_ID\" FROM wifi_hist WHERE \"AP_ID\" = ? AND \"File_ID\" != ? LIMIT 1";}
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
			$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
			$resgps->execute();
			$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
			$New_File_ID = $fetchgps['File_ID'];

			if($New_File_ID)
			{
				if($dbcore->sql->service == "mysql")
					{$sqlu = "UPDATE `wifi_ap` SET `File_ID` = ? WHERE `AP_ID` = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sqlu = "UPDATE [wifi_ap] SET [File_ID] = ? WHERE [AP_ID] = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sqlu = "UPDATE wifi_ap SET \"File_ID\" = ? WHERE \"AP_ID\" = ?";}
				$prep = $dbcore->sql->conn->prepare($sqlu);
				$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
				$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
				$prep->execute();
			}
		}

		// Go through Cells with this File ID
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `cell_id` FROM `cell_id` WHERE file_id = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [cell_id] FROM [cell_id] WHERE [file_id] = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sql = "SELECT cell_id FROM cell_id WHERE file_id = ?";}
		$apl = $dbcore->sql->conn->prepare($sql);
		$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
		$apl->execute();

		while($ap = $apl->fetch(PDO::FETCH_NUM))
		{
			$cell_id = $ap[0];

			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `file_id` FROM `cell_hist` WHERE `cell_id` = ? AND `file_id` != ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [file_id] FROM [cell_hist] WHERE [cell_id] = ? AND [file_id] != ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqlhp = "SELECT file_id FROM cell_hist WHERE cell_id = ? AND file_id != ? LIMIT 1";}
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $cell_id, PDO::PARAM_INT);
			$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
			$resgps->execute();
			$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
			$New_File_ID = $fetchgps['file_id'];

			if($New_File_ID)
			{
				if($dbcore->sql->service == "mysql")
					{$sqlu = "UPDATE `cell_id` SET `file_id` = ? WHERE `cell_id` = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sqlu = "UPDATE [cell_id] SET [file_id] = ? WHERE [cell_id] = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sqlu = "UPDATE cell_id SET file_id = ? WHERE cell_id = ?";}
				$prep = $dbcore->sql->conn->prepare($sqlu);
				$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
				$prep->bindParam(2, $cell_id, PDO::PARAM_INT);
				$prep->execute();
			}
		}

		// Copy file info to files_tmp for re-import
		$sqlhp = "INSERT INTO files_tmp\n"
			. "(file_name, file_orig, file_user, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type)\n"
			. "SELECT file_name, file_orig, file_user, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type\n"
			. "FROM files\n"
			. "WHERE id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Delete wifi_hist records
		$sqlhp = "DELETE FROM wifi_hist WHERE File_ID = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Delete wifi_ap records
		$sqlhp = "DELETE FROM wifi_ap WHERE File_ID = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Delete wifi_gps records
		$sqlhp = "DELETE FROM wifi_gps WHERE File_ID = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Delete cell_hist records
		$sqlhp = "DELETE FROM cell_hist WHERE file_id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Delete cell_id records
		$sqlhp = "DELETE FROM cell_id WHERE file_id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Delete the file record
		$sqlhp = "DELETE FROM files WHERE id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		return array('success' => true, 'message' => "File ID $File_ID reset and queued for re-import");
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

/**
 * Delete a file permanently
 */
function delete_file($dbcore, $File_ID, $deleted_folder = 'deleted')
{
	try
	{
		// Get file info first for moving the uploaded file
		$sql = "SELECT file_name, file_orig, file_user, title, notes, hash, type, file_date FROM files WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $File_ID, PDO::PARAM_INT);
		$prep->execute();
		$file_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$file_info)
		{
			return array('success' => false, 'error' => 'File not found');
		}

		// Similar AP/Cell re-assignment logic as reset_file
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `AP_ID` FROM `wifi_ap` WHERE File_ID = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [AP_ID] FROM [wifi_ap] WHERE File_ID = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sql = "SELECT \"AP_ID\" FROM wifi_ap WHERE \"File_ID\" = ?";}
		$apl = $dbcore->sql->conn->prepare($sql);
		$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
		$apl->execute();

		while($ap = $apl->fetch(PDO::FETCH_NUM))
		{
			$AP_ID = $ap[0];

			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? AND `File_ID` != ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [File_ID] FROM [wifi_hist] WHERE [AP_ID] = ? AND [File_ID] != ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqlhp = "SELECT \"File_ID\" FROM wifi_hist WHERE \"AP_ID\" = ? AND \"File_ID\" != ? LIMIT 1";}
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
			$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
			$resgps->execute();
			$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
			$New_File_ID = $fetchgps['File_ID'];

			if($New_File_ID)
			{
				if($dbcore->sql->service == "mysql")
					{$sqlu = "UPDATE `wifi_ap` SET `File_ID` = ? WHERE `AP_ID` = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sqlu = "UPDATE [wifi_ap] SET [File_ID] = ? WHERE [AP_ID] = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sqlu = "UPDATE wifi_ap SET \"File_ID\" = ? WHERE \"AP_ID\" = ?";}
				$prep = $dbcore->sql->conn->prepare($sqlu);
				$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
				$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
				$prep->execute();
			}
		}

		// Go through Cells
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `cell_id` FROM `cell_id` WHERE file_id = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [cell_id] FROM [cell_id] WHERE [file_id] = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sql = "SELECT cell_id FROM cell_id WHERE file_id = ?";}
		$apl = $dbcore->sql->conn->prepare($sql);
		$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
		$apl->execute();

		while($ap = $apl->fetch(PDO::FETCH_NUM))
		{
			$cell_id = $ap[0];

			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `file_id` FROM `cell_hist` WHERE `cell_id` = ? AND `file_id` != ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [file_id] FROM [cell_hist] WHERE [cell_id] = ? AND [file_id] != ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqlhp = "SELECT file_id FROM cell_hist WHERE cell_id = ? AND file_id != ? LIMIT 1";}
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $cell_id, PDO::PARAM_INT);
			$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
			$resgps->execute();
			$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
			$New_File_ID = $fetchgps['file_id'];

			if($New_File_ID)
			{
				if($dbcore->sql->service == "mysql")
					{$sqlu = "UPDATE `cell_id` SET `file_id` = ? WHERE `cell_id` = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sqlu = "UPDATE [cell_id] SET [file_id] = ? WHERE [cell_id] = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sqlu = "UPDATE cell_id SET file_id = ? WHERE cell_id = ?";}
				$prep = $dbcore->sql->conn->prepare($sqlu);
				$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
				$prep->bindParam(2, $cell_id, PDO::PARAM_INT);
				$prep->execute();
			}
		}

		// Delete records
		$sqlhp = "DELETE FROM wifi_hist WHERE File_ID = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		$sqlhp = "DELETE FROM wifi_ap WHERE File_ID = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		$sqlhp = "DELETE FROM wifi_gps WHERE File_ID = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		$sqlhp = "DELETE FROM cell_hist WHERE file_id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		$sqlhp = "DELETE FROM cell_id WHERE file_id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		$sqlhp = "DELETE FROM files WHERE id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Move uploaded file to deleted folder
		$upload_path = $dbcore->PATH . 'import/up/';
		$deleted_path = $dbcore->PATH . 'import/up/' . $deleted_folder . '/';

		if(!is_dir($deleted_path))
		{
			mkdir($deleted_path, 0755, true);
		}

		$source_file = $upload_path . $file_info['file_name'];
		if(file_exists($source_file))
		{
			// Save file info to txt
			$info_file = $deleted_path . pathinfo($file_info['file_name'], PATHINFO_FILENAME) . '.txt';
			$info_content = $file_info['hash'] . '|' . $file_info['type'] . '|' . $file_info['file_name'] . '|'
				. $file_info['file_orig'] . '|' . $file_info['file_user'] . '|' . $file_info['title'] . '|'
				. $file_info['file_date'] . '|' . $file_info['notes'];
			file_put_contents($info_file, $info_content);

			rename($source_file, $deleted_path . $file_info['file_name']);
		}

		return array('success' => true, 'message' => "File ID $File_ID deleted successfully");
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

/**
 * Reset a failed file for re-import
 */
function reset_failed_file($dbcore, $File_ID)
{
	try
	{
		// This reset is for an incomplete import: use the files_importing id to find file hash
		// and cleanup any partial data in the `files`/`wifi_*`/`cell_*` tables, then leave
		// the `files_importing` row so the daemon can re-import.

		// Get hash from files_importing
		if($dbcore->sql->service == "mysql")
			{$sqlhp = "SELECT `hash` FROM `files_importing` WHERE `id` = ? LIMIT 1";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sqlhp = "SELECT TOP 1 [hash] FROM [files_importing] WHERE [id] = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sqlhp = "SELECT hash FROM files_importing WHERE id = ? LIMIT 1";}
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();
		$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
		$File_Hash = $fetchgps['hash'] ?? null;

		if($File_Hash)
		{
			// Find file id from hash
			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `id` FROM `files` WHERE `hash` = ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [id] FROM [files] WHERE [hash] = ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqlhp = "SELECT id FROM files WHERE hash = ? LIMIT 1";}
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $File_Hash, PDO::PARAM_STR);
			$resgps->execute();
			$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
			$Partial_File_ID = $fetchgps['id'] ?? null;

			if($Partial_File_ID)
			{
				$dbcore->verbosed("Found partial import with File ID: $Partial_File_ID, cleaning up before reset...");

				// Reassign APs to alternate file if available
				if($dbcore->sql->service == "mysql")
					{$sql = "SELECT `AP_ID` FROM `wifi_ap` WHERE File_ID = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sql = "SELECT [AP_ID] FROM [wifi_ap] WHERE File_ID = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sql = "SELECT \"AP_ID\" FROM wifi_ap WHERE \"File_ID\" = ?";}
				$apl = $dbcore->sql->conn->prepare($sql);
				$apl->bindParam(1, $Partial_File_ID, PDO::PARAM_INT);
				$apl->execute();

				while($ap = $apl->fetch(PDO::FETCH_NUM))
				{
					$AP_ID = $ap[0];

					if($dbcore->sql->service == "mysql")
						{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? And `File_ID != ? LIMIT 1";}
					else if($dbcore->sql->service == "sqlsrv")
						{$sqlhp = "SELECT TOP 1 [File_ID] FROM [wifi_hist] WHERE [AP_ID] = ? And [File_ID] != ?";}
					else if($dbcore->sql->service == "pgsql")
						{$sqlhp = "SELECT \"File_ID\" FROM wifi_hist WHERE \"AP_ID\" = ? And \"File_ID\" != ? LIMIT 1";}
					$resgps = $dbcore->sql->conn->prepare($sqlhp);
					$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
					$resgps->bindParam(2, $Partial_File_ID, PDO::PARAM_INT);
					$resgps->execute();
					$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
					$New_File_ID = $fetchgps['File_ID'] ?? null;

					if($New_File_ID)
					{
						if($dbcore->sql->service == "mysql")
							{$sqlu = "UPDATE `wifi_ap` SET `File_ID` = ? WHERE `AP_ID` = ?";}
						else if($dbcore->sql->service == "sqlsrv")
							{$sqlu = "UPDATE [wifi_ap] SET [File_ID] = ? WHERE [AP_ID] = ?";}
						else if($dbcore->sql->service == "pgsql")
							{$sqlu = "UPDATE wifi_ap SET \"File_ID\" = ? WHERE \"AP_ID\" = ?";}
						$prep = $dbcore->sql->conn->prepare($sqlu);
						$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
						$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
						$prep->execute();
					}
				}

				// Reassign cells if present
				if($dbcore->sql->service == "mysql")
					{$sql = "SELECT `cell_id` FROM `cell_id` WHERE file_id = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sql = "SELECT [cell_id] FROM [cell_id] WHERE [file_id] = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sql = "SELECT cell_id FROM cell_id WHERE file_id = ?";}
				$apl = $dbcore->sql->conn->prepare($sql);
				$apl->bindParam(1, $Partial_File_ID, PDO::PARAM_INT);
				$apl->execute();

				while($ap = $apl->fetch(PDO::FETCH_NUM))
				{
					$cell_id = $ap[0];

					if($dbcore->sql->service == "mysql")
						{$sqlhp = "SELECT `file_id` FROM `cell_hist` WHERE `cell_id` = ? And `file_id != ? LIMIT 1";}
					else if($dbcore->sql->service == "sqlsrv")
						{$sqlhp = "SELECT TOP 1 [file_id] FROM [cell_hist] WHERE [cell_id] = ? And [file_id] != ?";}
					else if($dbcore->sql->service == "pgsql")
						{$sqlhp = "SELECT file_id FROM cell_hist WHERE cell_id = ? And file_id != ? LIMIT 1";}
					$resgps = $dbcore->sql->conn->prepare($sqlhp);
					$resgps->bindParam(1, $cell_id, PDO::PARAM_INT);
					$resgps->bindParam(2, $Partial_File_ID, PDO::PARAM_INT);
					$resgps->execute();
					$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
					$New_File_ID = $fetchgps['file_id'] ?? null;

					if($New_File_ID)
					{
						if($dbcore->sql->service == "mysql")
							{$sqlu = "UPDATE `cell_id` SET `file_id` = ? WHERE `cell_id` = ?";}
						else if($dbcore->sql->service == "sqlsrv")
							{$sqlu = "UPDATE [cell_id] SET [file_id] = ? WHERE [cell_id] = ?";}
						else if($dbcore->sql->service == "pgsql")
							{$sqlu = "UPDATE cell_id SET file_id = ? WHERE cell_id = ?";}
						$prep = $dbcore->sql->conn->prepare($sqlu);
						$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
						$prep->bindParam(2, $cell_id, PDO::PARAM_INT);
						$prep->execute();
					}
				}

				// Delete partial data for the found File ID
				$tables = array(
					"DELETE FROM wifi_hist WHERE File_ID = ?",
					"DELETE FROM wifi_ap WHERE File_ID = ?",
					"DELETE FROM wifi_gps WHERE File_ID = ?",
					"DELETE FROM cell_hist WHERE file_id = ?",
					"DELETE FROM cell_id WHERE file_id = ?",
					"DELETE FROM files WHERE id = ?"
				);

				foreach($tables as $sqlt)
				{
					$retry = true;
					while ($retry)
					{
						try
						{
							$resgps = $dbcore->sql->conn->prepare($sqlt);
							$resgps->bindParam(1, $Partial_File_ID, PDO::PARAM_INT);
							$resgps->execute();
							$retry = false;
						}
						catch (Exception $e)
						{
							$retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
						}
					}
				}
			}
		}

		// Copy files_importing row to files_tmp so metadata is preserved for re-import attempts
		$retry = true;
		while ($retry)
		{
			try
			{
				$sqlhp = "INSERT INTO files_tmp\n"
					. "(file_user, file_name, file_orig, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type)\n"
					. "SELECT file_user, file_name, file_orig, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type\n"
					. "FROM files_importing\n"
					. "WHERE id = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();
				$retry = false;
			}
			catch (Exception $e)
			{
				$retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
			}
		}

		// Mark files_importing as not importing so daemon will pick it up again
		$sql = "UPDATE files_importing SET importing = 0, tot = NULL, ap = NULL WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $File_ID, PDO::PARAM_INT);
		$prep->execute();

		return array('success' => true, 'message' => "Failed file import ID $File_ID reset (partial data cleaned) and ready for re-import");
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

/**
 * Delete a failed file permanently
 * This also cleans up any partial data that may have been written during a failed import
 */
function delete_failed_file($dbcore, $File_Import_ID)
{
	try
	{
		// Get file info including hash from files_importing
		$sql = "SELECT file_name, file_orig, file_user, title, notes, hash, type, file_date FROM files_importing WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $File_Import_ID, PDO::PARAM_INT);
		$prep->execute();
		$file_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$file_info)
		{
			return array('success' => false, 'error' => 'File not found in files_importing');
		}

		$File_Hash = $file_info['hash'];

		// Check if there's a partial import in the files table using the hash
		if($File_Hash)
		{
			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `id` FROM `files` WHERE `hash` = ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [id] FROM [files] WHERE [hash] = ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sqlhp = "SELECT id FROM files WHERE hash = ? LIMIT 1";}
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $File_Hash, PDO::PARAM_STR);
			$resgps->execute();
			$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
			$File_ID = $fetchgps['id'] ?? null;

			if($File_ID)
			{
				$dbcore->verbosed("Found partial import with File ID: $File_ID, cleaning up...");

				// Go through APs with this File ID and update to alternate file if available
				if($dbcore->sql->service == "mysql")
					{$sql = "SELECT `AP_ID` FROM `wifi_ap` WHERE File_ID = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sql = "SELECT [AP_ID] FROM [wifi_ap] WHERE File_ID = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sql = "SELECT \"AP_ID\" FROM wifi_ap WHERE \"File_ID\" = ?";}
				$apl = $dbcore->sql->conn->prepare($sql);
				$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
				$apl->execute();

				while($ap = $apl->fetch(PDO::FETCH_NUM))
				{
					$AP_ID = $ap[0];

					if($dbcore->sql->service == "mysql")
						{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? AND `File_ID` != ? LIMIT 1";}
					else if($dbcore->sql->service == "sqlsrv")
						{$sqlhp = "SELECT TOP 1 [File_ID] FROM [wifi_hist] WHERE [AP_ID] = ? AND [File_ID] != ?";}
					else if($dbcore->sql->service == "pgsql")
						{$sqlhp = "SELECT \"File_ID\" FROM wifi_hist WHERE \"AP_ID\" = ? AND \"File_ID\" != ? LIMIT 1";}
					$resgps = $dbcore->sql->conn->prepare($sqlhp);
					$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
					$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
					$resgps->execute();
					$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
					$New_File_ID = $fetchgps['File_ID'] ?? null;

					if($New_File_ID)
					{
						if($dbcore->sql->service == "mysql")
							{$sqlu = "UPDATE `wifi_ap` SET `File_ID` = ? WHERE `AP_ID` = ?";}
						else if($dbcore->sql->service == "sqlsrv")
							{$sqlu = "UPDATE [wifi_ap] SET [File_ID] = ? WHERE [AP_ID] = ?";}
						else if($dbcore->sql->service == "pgsql")
							{$sqlu = "UPDATE wifi_ap SET \"File_ID\" = ? WHERE \"AP_ID\" = ?";}
						$prep = $dbcore->sql->conn->prepare($sqlu);
						$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
						$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
						$prep->execute();
					}
				}

				// Go through Cells with this File ID
				if($dbcore->sql->service == "mysql")
					{$sql = "SELECT `cell_id` FROM `cell_id` WHERE file_id = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$sql = "SELECT [cell_id] FROM [cell_id] WHERE [file_id] = ?";}
				else if($dbcore->sql->service == "pgsql")
					{$sql = "SELECT cell_id FROM cell_id WHERE file_id = ?";}
				$apl = $dbcore->sql->conn->prepare($sql);
				$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
				$apl->execute();

				while($ap = $apl->fetch(PDO::FETCH_NUM))
				{
					$cell_id = $ap[0];

					if($dbcore->sql->service == "mysql")
						{$sqlhp = "SELECT `file_id` FROM `cell_hist` WHERE `cell_id` = ? AND `file_id` != ? LIMIT 1";}
					else if($dbcore->sql->service == "sqlsrv")
						{$sqlhp = "SELECT TOP 1 [file_id] FROM [cell_hist] WHERE [cell_id] = ? AND [file_id] != ?";}
					else if($dbcore->sql->service == "pgsql")
						{$sqlhp = "SELECT file_id FROM cell_hist WHERE cell_id = ? AND file_id != ? LIMIT 1";}
					$resgps = $dbcore->sql->conn->prepare($sqlhp);
					$resgps->bindParam(1, $cell_id, PDO::PARAM_INT);
					$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
					$resgps->execute();
					$fetchgps = $resgps->fetch(PDO::FETCH_ASSOC);
					$New_File_ID = $fetchgps['file_id'] ?? null;

					if($New_File_ID)
					{
						if($dbcore->sql->service == "mysql")
							{$sqlu = "UPDATE `cell_id` SET `file_id` = ? WHERE `cell_id` = ?";}
						else if($dbcore->sql->service == "sqlsrv")
							{$sqlu = "UPDATE [cell_id] SET [file_id] = ? WHERE [cell_id] = ?";}
						else if($dbcore->sql->service == "pgsql")
							{$sqlu = "UPDATE cell_id SET file_id = ? WHERE cell_id = ?";}
						$prep = $dbcore->sql->conn->prepare($sqlu);
						$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
						$prep->bindParam(2, $cell_id, PDO::PARAM_INT);
						$prep->execute();
					}
				}

				// Delete wifi_hist records
				$sqlhp = "DELETE FROM wifi_hist WHERE File_ID = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();

				// Delete wifi_ap records
				$sqlhp = "DELETE FROM wifi_ap WHERE File_ID = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();

				// Delete wifi_gps records
				$sqlhp = "DELETE FROM wifi_gps WHERE File_ID = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();

				// Delete cell_hist records
				$sqlhp = "DELETE FROM cell_hist WHERE file_id = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();

				// Delete cell_id records
				$sqlhp = "DELETE FROM cell_id WHERE file_id = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();

				// Delete the files record
				$sqlhp = "DELETE FROM files WHERE id = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();
			}
		}

		// Delete the importing record
		$sql = "DELETE FROM files_importing WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $File_Import_ID, PDO::PARAM_INT);
		$prep->execute();

		// Move uploaded file to deleted folder
		$upload_path = $dbcore->PATH . 'import/up/';
		$deleted_path = $dbcore->PATH . 'import/up/deleted/';

		if(!is_dir($deleted_path))
		{
			mkdir($deleted_path, 0755, true);
		}

		$source_file = $upload_path . $file_info['file_name'];
		if(file_exists($source_file))
		{
			// Save file info to txt
			$info_file = $deleted_path . pathinfo($file_info['file_name'], PATHINFO_FILENAME) . '.txt';
			$info_content = $file_info['hash'] . '|' . $file_info['type'] . '|' . $file_info['file_name'] . '|'
				. $file_info['file_orig'] . '|' . $file_info['file_user'] . '|' . $file_info['title'] . '|'
				. $file_info['file_date'] . '|' . $file_info['notes'];
			file_put_contents($info_file, $info_content);

			rename($source_file, $deleted_path . $file_info['file_name']);
		}

		return array('success' => true, 'message' => "Failed file ID $File_Import_ID deleted successfully");
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

/**
 * Delete a PID file left behind by a stuck daemon
 */
function delete_pidfile($dbcore, $pidfile_name)
{
	try
	{
		if(empty($pidfile_name))
		{
			return array('success' => false, 'error' => 'No pidfile name provided');
		}

		$pidfile_path = $dbcore->pid_file_loc . $pidfile_name;

		if(file_exists($pidfile_path))
		{
			if(unlink($pidfile_path))
			{
				return array('success' => true, 'message' => "PID file $pidfile_name deleted successfully");
			}
			else
			{
				return array('success' => false, 'error' => "Failed to delete PID file: $pidfile_path");
			}
		}
		else
		{
			// File doesn't exist, consider it a success
			return array('success' => true, 'message' => "PID file $pidfile_name already removed");
		}
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

/**
 * Reset a files_bad entry back to files_tmp for re-import
 */
function reset_files_bad($dbcore, $Bad_ID)
{
	try {
		if(!$Bad_ID || !is_numeric($Bad_ID)){
			return array('success' => false, 'error' => 'Invalid files_bad id');
		}

		// Fetch files_bad row
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT id, file_name, file_orig, file_user, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type, error_msg FROM files_bad WHERE id = ? LIMIT 1";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT TOP 1 [id], [file_name], [file_orig], [file_user], [otherusers], [notes], [title], [size], [file_date], [hash], [converted], [prev_ext], [type], [error_msg] FROM [files_bad] WHERE [id] = ?";}
		else if($dbcore->sql->service == "pgsql")
			{$sql = "SELECT id, file_name, file_orig, file_user, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type, error_msg FROM files_bad WHERE id = ? LIMIT 1";}
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $Bad_ID, PDO::PARAM_INT);
		$prep->execute();
		$file_bad = $prep->fetch(PDO::FETCH_ASSOC);
		if(!$file_bad){
			return array('success' => false, 'error' => "files_bad id $Bad_ID not found");
		}

		$hash = $file_bad['hash'];

		// Perform checks and move inside a transaction to avoid race conditions
		try {
			$dbcore->sql->conn->beginTransaction();

			// Check if file already exists in files
			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT id FROM files WHERE hash = ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [id] FROM [files] WHERE [hash] = ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sql = "SELECT id FROM files WHERE hash = ? LIMIT 1";}
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $hash, PDO::PARAM_STR);
			$prep->execute();
			$exists = $prep->fetch(PDO::FETCH_ASSOC);
			if($exists && $exists['id']){
				$dbcore->sql->conn->rollBack();
				return array('success' => false, 'error' => "File with same hash already exists in 'files' (id={$exists['id']}). Not re-adding to import queue.");
			}

			// Check files_tmp to avoid duplicate tmp entries
			if($dbcore->sql->service == "mysql")
				{$sql = "SELECT id FROM files_tmp WHERE hash = ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [id] FROM [files_tmp] WHERE [hash] = ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sql = "SELECT id FROM files_tmp WHERE hash = ? LIMIT 1";}
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $hash, PDO::PARAM_STR);
			$prep->execute();
			$tmp_exists = $prep->fetch(PDO::FETCH_ASSOC);
			if($tmp_exists && $tmp_exists['id']){
				$dbcore->sql->conn->rollBack();
				return array('success' => false, 'error' => "A files_tmp entry with same hash already exists (id={$tmp_exists['id']}). Not re-adding.");
			}

			// Insert into files_tmp from files_bad
			$sql = "INSERT INTO files_tmp (file_user, file_name, file_orig, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type) SELECT file_user, file_name, file_orig, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type FROM files_bad WHERE id = ?";
			$res = $dbcore->sql->conn->prepare($sql);
			$res->bindParam(1, $Bad_ID, PDO::PARAM_INT);
			$res->execute();

			// Delete files_bad row
			if($dbcore->sql->service == "mysql")
				{$sql = "DELETE FROM files_bad WHERE id = ?";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql = "DELETE FROM [files_bad] WHERE [id] = ?";}
			else if($dbcore->sql->service == "pgsql")
				{$sql = "DELETE FROM files_bad WHERE id = ?";}
			$res = $dbcore->sql->conn->prepare($sql);
			$res->bindParam(1, $Bad_ID, PDO::PARAM_INT);
			$res->execute();

			$dbcore->sql->conn->commit();
			return array('success' => true, 'message' => "Moved files_bad id $Bad_ID to files_tmp and removed from files_bad. Daemon will pick it up for import.");
		} catch (Exception $e) {
			if($dbcore->sql->conn->inTransaction()){
				$dbcore->sql->conn->rollBack();
			}
			return array('success' => false, 'error' => $e->getMessage());
		}
	} catch (Exception $e) {
		return array('success' => false, 'error' => $e->getMessage());
	}
}

?>
