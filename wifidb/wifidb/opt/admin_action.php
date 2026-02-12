<?php
/*
admin_action.php, Admin actions for WifiDB
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "export");

require '../lib/init.inc.php';

// Check if user is logged in and is an Administrator
if($dbcore->sec->priv_name != "Administrator")
{
	header("HTTP/1.1 403 Forbidden");
	die("Access denied. Administrator privileges required.");
}

$action = filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING);
$file_id = filter_input(INPUT_GET, 'file_id', FILTER_SANITIZE_NUMBER_INT);
$confirm = filter_input(INPUT_GET, 'confirm', FILTER_SANITIZE_STRING);
$return_url = filter_input(INPUT_GET, 'return', FILTER_SANITIZE_URL);

/**
 * Queue an admin job for background processing
 */
function queue_admin_job($dbcore, $job_type, $target_id, $target_table, $requested_by)
{
	try
	{
		$sql = "INSERT INTO admin_jobs (job_type, target_id, target_table, requested_by, status, created_at) VALUES (?, ?, ?, ?, 'pending', ?)";
		$prep = $dbcore->sql->conn->prepare($sql);
		$created_at = date('Y-m-d H:i:s');
		$prep->bindParam(1, $job_type, PDO::PARAM_STR);
		$prep->bindParam(2, $target_id, PDO::PARAM_INT);
		$prep->bindParam(3, $target_table, PDO::PARAM_STR);
		$prep->bindParam(4, $requested_by, PDO::PARAM_STR);
		$prep->bindParam(5, $created_at, PDO::PARAM_STR);
		$prep->execute();

		// Get the job ID
		if($dbcore->sql->service == "mysql")
		{
			$job_id = $dbcore->sql->conn->lastInsertId();
		}
		else
		{
			// SQL Server - get the ID from the insert
			$sql = "SELECT MAX(id) as job_id FROM admin_jobs WHERE job_type = ? AND target_id = ? AND requested_by = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $job_type, PDO::PARAM_STR);
			$prep->bindParam(2, $target_id, PDO::PARAM_INT);
			$prep->bindParam(3, $requested_by, PDO::PARAM_STR);
			$prep->execute();
			$result = $prep->fetch(PDO::FETCH_ASSOC);
			$job_id = $result['job_id'];
		}

		return array('success' => true, 'job_id' => $job_id);
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

switch($action)
{
	case "reset_file":
		if(!$file_id || !is_numeric($file_id))
		{
			die("Invalid file ID");
		}

		// Get file info for confirmation
		$sql = "SELECT id, file_orig, file_user, title FROM files WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $file_id, PDO::PARAM_INT);
		$prep->execute();
		$file_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$file_info)
		{
			die("File not found");
		}

		if($confirm == "yes")
		{
			// Queue the job for background processing
			$result = queue_admin_job($dbcore, 'reset_file', $file_id, 'files', $dbcore->sec->LoginUser);

			if($result['success'])
			{
				$message = "Reset job for File ID {$file_id} has been queued (Job #{$result['job_id']}). It will be processed shortly.";
				$message_type = "success";
			}
			else
			{
				$message = "Error queuing job: " . $result['error'];
				$message_type = "error";
			}

			// Display result page
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url);
			$dbcore->smarty->display('admin_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Reset File");
			$dbcore->smarty->assign("file_info", $file_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("file_id", $file_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_action_confirm.tpl');
		}
		break;

	case "delete_file":
		if(!$file_id || !is_numeric($file_id))
		{
			die("Invalid file ID");
		}

		// Get file info for confirmation
		$sql = "SELECT id, file_name, file_orig, file_user, title FROM files WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $file_id, PDO::PARAM_INT);
		$prep->execute();
		$file_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$file_info)
		{
			die("File not found");
		}

		if($confirm == "yes")
		{
			// Queue the job for background processing
			$result = queue_admin_job($dbcore, 'delete_file', $file_id, 'files', $dbcore->sec->LoginUser);

			if($result['success'])
			{
				$message = "Delete job for File ID {$file_id} has been queued (Job #{$result['job_id']}). It will be processed shortly.";
				$message_type = "success";
			}
			else
			{
				$message = "Error queuing job: " . $result['error'];
				$message_type = "error";
			}

			// Display result page
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url);
			$dbcore->smarty->display('admin_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Delete File");
			$dbcore->smarty->assign("file_info", $file_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("file_id", $file_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_action_confirm.tpl');
		}
		break;

	case "reset_failed_file":
		if(!$file_id || !is_numeric($file_id))
		{
			die("Invalid file ID");
		}

		// Get file info for confirmation from files_importing table
		$sql = "SELECT id, file_orig, file_user, title FROM files_importing WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $file_id, PDO::PARAM_INT);
		$prep->execute();
		$file_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$file_info)
		{
			die("File not found in importing queue");
		}

		if($confirm == "yes")
		{
			// Queue the job for background processing
			$result = queue_admin_job($dbcore, 'reset_failed_file', $file_id, 'files_importing', $dbcore->sec->LoginUser);

			if($result['success'])
			{
				$message = "Reset job for failed File ID {$file_id} has been queued (Job #{$result['job_id']}). It will be processed shortly.";
				$message_type = "success";
			}
			else
			{
				$message = "Error queuing job: " . $result['error'];
				$message_type = "error";
			}

			// Display result page
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url);
			$dbcore->smarty->display('admin_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Reset Failed Import");
			$dbcore->smarty->assign("file_info", $file_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("file_id", $file_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_action_confirm.tpl');
		}
		break;

	case "delete_failed_file":
		if(!$file_id || !is_numeric($file_id))
		{
			die("Invalid file ID");
		}

		// Get file info for confirmation from files_importing table
		$sql = "SELECT id, file_name, file_orig, file_user, title FROM files_importing WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $file_id, PDO::PARAM_INT);
		$prep->execute();
		$file_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$file_info)
		{
			die("File not found in importing queue");
		}

		if($confirm == "yes")
		{
			// Queue the job for background processing
			$result = queue_admin_job($dbcore, 'delete_failed_file', $file_id, 'files_importing', $dbcore->sec->LoginUser);

			if($result['success'])
			{
				$message = "Delete job for failed File ID {$file_id} has been queued (Job #{$result['job_id']}). It will be processed shortly.";
				$message_type = "success";
			}
			else
			{
				$message = "Error queuing job: " . $result['error'];
				$message_type = "error";
			}

			// Display result page
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url);
			$dbcore->smarty->display('admin_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Delete Failed Import");
			$dbcore->smarty->assign("file_info", $file_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("file_id", $file_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_action_confirm.tpl');
		}
		break;

		case "reset_files_bad":
			if(!$file_id || !is_numeric($file_id))
			{
				die("Invalid file ID");
			}

			// Get file info for confirmation from files_bad table
			$sql = "SELECT id, file_name, file_orig, file_user, title, notes, error_msg FROM files_bad WHERE id = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $file_id, PDO::PARAM_INT);
			$prep->execute();
			$file_info = $prep->fetch(PDO::FETCH_ASSOC);

			if(!$file_info)
			{
				die("File not found in files_bad");
			}

			if($confirm == "yes")
			{
				// Queue the job for background processing
				$result = queue_admin_job($dbcore, 'reset_files_bad', $file_id, 'files_bad', $dbcore->sec->LoginUser);

				if($result['success'])
				{
					$message = "Reset job for bad File ID {$file_id} has been queued (Job #{$result['job_id']}). It will be processed shortly.";
					$message_type = "success";
				}
				else
				{
					$message = "Error queuing job: " . $result['error'];
					$message_type = "error";
				}

				// Display result page
				$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
				$dbcore->smarty->assign("message", $message);
				$dbcore->smarty->assign("message_type", $message_type);
				$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url);
				$dbcore->smarty->display('admin_action_result.tpl');
			}
			else
			{
				// Show confirmation page
				$dbcore->smarty->assign("wifidb_page_label", "Confirm Reset Bad File");
				$dbcore->smarty->assign("file_info", $file_info);
				$dbcore->smarty->assign("action", $action);
				$dbcore->smarty->assign("file_id", $file_id);
				$dbcore->smarty->assign("return_url", $return_url);
				$dbcore->smarty->display('admin_action_confirm.tpl');
			}
			break;

	case "edit_files_bad":
		if(!$file_id || !is_numeric($file_id))
		{
			die("Invalid file ID");
		}

		// If form submitted (POST), perform update
		if($_SERVER['REQUEST_METHOD'] === 'POST')
		{
			$post_title = filter_input(INPUT_POST, 'title', FILTER_SANITIZE_STRING);
			$post_type = filter_input(INPUT_POST, 'type', FILTER_SANITIZE_STRING);

			// Validate type against allowed import types
			$allowed_types = array('vistumbler','ns1','wardrive','wiglewificsv','swardriving','kismet');
			if(!in_array($post_type, $allowed_types)){
				$post_type = '';
			}
			$post_file_user = filter_input(INPUT_POST, 'file_user', FILTER_SANITIZE_STRING);
			$post_otherusers = filter_input(INPUT_POST, 'otherusers', FILTER_SANITIZE_STRING);
			$post_notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);

			$sql = "UPDATE files_bad SET title = ?, type = ?, file_user = ?, otherusers = ?, notes = ? WHERE id = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $post_title, PDO::PARAM_STR);
			$prep->bindParam(2, $post_type, PDO::PARAM_STR);
			$prep->bindParam(3, $post_file_user, PDO::PARAM_STR);
			$prep->bindParam(4, $post_otherusers, PDO::PARAM_STR);
			$prep->bindParam(5, $post_notes, PDO::PARAM_STR);
			$prep->bindParam(6, $file_id, PDO::PARAM_INT);
			$prep->execute();

			$message = "Updated files_bad ID {$file_id}.";
			$message_type = "success";
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url.'opt/scheduling.php?func=bad');
			$dbcore->smarty->display('admin_action_result.tpl');
			break;
		}
		else
		{
			// Show edit form populated from files_bad
			$sql = "SELECT id, file_name, file_orig, file_user, otherusers, notes, title, type, error_msg FROM files_bad WHERE id = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $file_id, PDO::PARAM_INT);
			$prep->execute();
			$file_info = $prep->fetch(PDO::FETCH_ASSOC);

			if(!$file_info)
			{
				die("File not found in files_bad");
			}

			$dbcore->smarty->assign("wifidb_page_label", "Edit Bad Import");
			$dbcore->smarty->assign("file_info", $file_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("file_id", $file_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_edit_files_bad.tpl');
			break;
		}

	case "delete_daemon_pid":
		$pidfile = filter_input(INPUT_GET, 'pidfile', FILTER_SANITIZE_STRING);
		if(empty($pidfile))
		{
			die("Invalid pidfile");
		}

		// Get daemon pid info for confirmation
		$sql = "SELECT nodename, pidfile, pid FROM daemon_pid_stats WHERE pidfile = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $pidfile, PDO::PARAM_STR);
		$prep->execute();
		$pid_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$pid_info)
		{
			die("PID record not found");
		}

		if($confirm == "yes")
		{
			// Delete the record from daemon_pid_stats
			$sql = "DELETE FROM daemon_pid_stats WHERE pidfile = ?";
			$prep = $dbcore->sql->conn->prepare($sql);
			$prep->bindParam(1, $pidfile, PDO::PARAM_STR);
			$prep->execute();

			$message = "PID record for {$pidfile} deleted from database.";
			$message_type = "success";

			// Queue a job to delete the actual PID file (daemon user has permissions)
			$pidfile_result = queue_admin_job($dbcore, 'delete_pidfile', 0, $pidfile, $dbcore->sec->LoginUser);
			if($pidfile_result['success'])
			{
				$message .= " PID file cleanup queued (Job #{$pidfile_result['job_id']}).";
			}

			// Display result page
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url.'opt/scheduling.php?func=schedule');
			$dbcore->smarty->display('admin_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Delete PID");
			$dbcore->smarty->assign("pid_info", $pid_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("pidfile", $pidfile);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_action_confirm.tpl');
		}
		break;

	case "reset_schedule":
		$schedule_id = filter_input(INPUT_GET, 'schedule_id', FILTER_SANITIZE_NUMBER_INT);
		if(!$schedule_id || !is_numeric($schedule_id))
		{
			die("Invalid schedule ID");
		}

		// Get schedule info for confirmation (include pidfile for cleanup)
		$sql = "SELECT schedule.id, schedule.nodename, schedule.daemon, schedule.interval, schedule.status, schedule.pid, schedule.pidfile FROM schedule WHERE schedule.id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $schedule_id, PDO::PARAM_INT);
		$prep->execute();
		$schedule_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$schedule_info)
		{
			die("Schedule not found");
		}

		if($confirm == "yes")
		{
			// Schedule reset is quick, execute directly instead of queuing
			$result = reset_schedule($dbcore, $schedule_id, $schedule_info['interval']);

			if($result['success'])
			{
				$message = "Schedule ID {$schedule_id} ({$schedule_info['daemon']}) has been reset.";
				$message_type = "success";

				// Queue a job to delete the PID file (daemon user has permissions, Apache may not)
				if(!empty($schedule_info['pidfile']))
				{
					$pidfile_result = queue_admin_job($dbcore, 'delete_pidfile', $schedule_id, $schedule_info['pidfile'], $dbcore->sec->LoginUser);
					if($pidfile_result['success'])
					{
						$message .= " PID file cleanup queued (Job #{$pidfile_result['job_id']}).";
					}
				}
			}
			else
			{
				$message = "Error resetting schedule: " . $result['error'];
				$message_type = "error";
			}

			// Display result page
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url.'opt/scheduling.php?func=schedule');
			$dbcore->smarty->display('admin_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Reset Schedule");
			$dbcore->smarty->assign("schedule_info", $schedule_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("schedule_id", $schedule_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_action_confirm.tpl');
		}
		break;

	case "run_schedule_now":
		$schedule_id = filter_input(INPUT_GET, 'schedule_id', FILTER_SANITIZE_NUMBER_INT);
		if(!$schedule_id || !is_numeric($schedule_id))
		{
			die("Invalid schedule ID");
		}

		// Get schedule info
		$sql = "SELECT schedule.id, schedule.nodename, schedule.daemon FROM schedule WHERE schedule.id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $schedule_id, PDO::PARAM_INT);
		$prep->execute();
		$schedule_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$schedule_info)
		{
			die("Schedule not found");
		}

		if($confirm == "yes")
		{
			$result = run_schedule_now($dbcore, $schedule_id);

			if($result['success'])
			{
				$message = "Schedule ID {$schedule_id} ({$schedule_info['daemon']}) has been set to run now.";
				$message_type = "success";
			}
			else
			{
				$message = "Error updating schedule: " . $result['error'];
				$message_type = "error";
			}

			// Display result page
			$dbcore->smarty->assign("wifidb_page_label", "Admin Action Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url.'opt/scheduling.php?func=schedule');
			$dbcore->smarty->display('admin_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Run Schedule Now");
			$dbcore->smarty->assign("message", "Are you sure you want to run <strong>{$schedule_info['daemon']}</strong> immediately?<br>This will set the next run time to NOW.");
			$dbcore->smarty->assign("schedule_info", $schedule_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("schedule_id", $schedule_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('admin_action_confirm.tpl');
		}
		break;

	default:
		die("Invalid action");
}

/**
 * Reset a daemon schedule by setting status to Waiting, clearing PID, and setting next run to now + interval
 * This is quick enough to execute directly without queuing
 */
function reset_schedule($dbcore, $schedule_id, $interval)
{
	try
	{
		// Calculate next run time: now + interval minutes
		$next_run = date('Y-m-d H:i:s', strtotime("+{$interval} minutes"));

		// Update the schedule
		$sql = "UPDATE schedule SET status = 'Waiting', pid = '', nextrun = ? WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $next_run, PDO::PARAM_STR);
		$prep->bindParam(2, $schedule_id, PDO::PARAM_INT);
		$prep->execute();

		return array('success' => true);
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

/**
 * Set a daemon schedule to run immediately
 */
function run_schedule_now($dbcore, $schedule_id)
{
	try
	{
		// Reset next run time to now so it runs immediately
		$next_run = date('Y-m-d H:i:s');

		// Update the schedule
		$sql = "UPDATE schedule SET status = 'Waiting', pid = '', nextrun = ? WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $next_run, PDO::PARAM_STR);
		$prep->bindParam(2, $schedule_id, PDO::PARAM_INT);
		$prep->execute();

		return array('success' => true);
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}
?>
