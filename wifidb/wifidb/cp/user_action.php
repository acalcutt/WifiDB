<?php
/*
user_action.php, User actions for WifiDB (delete own imports)
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

require '../lib/init.inc.php';

// Check if user is logged in
$username = $dbcore->sec->LoginUser;
if(!$username)
{
	header("HTTP/1.1 403 Forbidden");
	die("Access denied. You must be logged in.");
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
	case "delete_my_file":
		if(!$file_id || !is_numeric($file_id))
		{
			die("Invalid file ID");
		}

		// Get file info and verify ownership
		$sql = "SELECT id, file_name, file_orig, file_user, title FROM files WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $file_id, PDO::PARAM_INT);
		$prep->execute();
		$file_info = $prep->fetch(PDO::FETCH_ASSOC);

		if(!$file_info)
		{
			die("File not found");
		}

		// Check if the logged-in user owns this file
		if($file_info['file_user'] !== $username)
		{
			header("HTTP/1.1 403 Forbidden");
			die("Access denied. You can only delete your own imports.");
		}

		if($confirm == "yes")
		{
			// Queue the job for background processing
			$result = queue_admin_job($dbcore, 'user_delete_file', $file_id, 'files', $username);

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
			$dbcore->smarty->assign("wifidb_page_label", "Delete Import Result");
			$dbcore->smarty->assign("message", $message);
			$dbcore->smarty->assign("message_type", $message_type);
			$dbcore->smarty->assign("return_url", $return_url ? $return_url : $dbcore->wifidb_host_url.'cp/index.php?func=myimports');
			$dbcore->smarty->display('user_cp_action_result.tpl');
		}
		else
		{
			// Show confirmation page
			$dbcore->smarty->assign("wifidb_page_label", "Confirm Delete Import");
			$dbcore->smarty->assign("file_info", $file_info);
			$dbcore->smarty->assign("action", $action);
			$dbcore->smarty->assign("file_id", $file_id);
			$dbcore->smarty->assign("return_url", $return_url);
			$dbcore->smarty->display('user_cp_action_confirm.tpl');
		}
		break;

	default:
		die("Invalid action");
}
?>
