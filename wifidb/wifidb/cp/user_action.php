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
			// Execute the delete
			$result = delete_user_file($dbcore, $file_id);

			if($result['success'])
			{
				$message = "File ID {$file_id} has been deleted.";
				$message_type = "success";
			}
			else
			{
				$message = "Error deleting file: " . $result['error'];
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

/**
 * Delete a user's own file by removing its data and moving the uploaded file
 */
function delete_user_file($dbcore, $File_ID)
{
	try {
		// Go through APs with this File ID and update to alternate file if available
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `AP_ID` FROM `wifi_ap` WHERE File_ID = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [AP_ID] FROM [wifi_ap] WHERE File_ID = ?";}
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
				$prep = $dbcore->sql->conn->prepare($sqlu);
				$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
				$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
				$prep->execute();
			}
		}

		// Cells
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `cell_id` FROM `cell_id` WHERE file_id = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [cell_id] FROM [cell_id] WHERE [file_id] = ?";}
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

		// Delete wifi_ap records (only those still pointing to this file)
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

		// Delete cell_id records (only those still pointing to this file)
		$sqlhp = "DELETE FROM cell_id WHERE file_id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		// Get full file info before deletion
		$sqlf = "SELECT * FROM files WHERE id = ?";
		$prep = $dbcore->sql->conn->prepare($sqlf);
		$prep->bindParam(1, $File_ID, PDO::PARAM_INT);
		$prep->execute();
		$finfo = $prep->fetch(PDO::FETCH_ASSOC);

		if($finfo && !empty($finfo['file_name']))
		{
			$orig = $finfo['file_name'];
			$upload_dir = realpath(dirname(__FILE__).'/../import/up');
			if($upload_dir !== false)
			{
				$del_dir = $upload_dir.DIRECTORY_SEPARATOR.'user_deleted';
				if(!is_dir($del_dir)) { @mkdir($del_dir, 0755, true); }

				// Save file info to txt file for potential re-import
				save_file_info($finfo, $del_dir);

				// Move the uploaded file
				$src = $upload_dir.DIRECTORY_SEPARATOR.$orig;
				$dst = $del_dir.DIRECTORY_SEPARATOR.$orig;
				if(is_file($src)) { @rename($src, $dst); }
			}
		}

		// Delete the file record
		$sqlhp = "DELETE FROM files WHERE id = ?";
		$resgps = $dbcore->sql->conn->prepare($sqlhp);
		$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
		$resgps->execute();

		return array('success' => true);
	}
	catch(Exception $e)
	{
		return array('success' => false, 'error' => $e->getMessage());
	}
}

/**
 * Save file info to a txt file for potential re-import
 * Format matches filenames_create.php: HASH|TYPE|FILENAME|ORIG_FILENAME|USERNAME|TITLE|DATE|NOTES
 */
function save_file_info($finfo, $del_dir)
{
	if(empty($finfo['file_name'])) return;

	// Get base filename without extension for the info file
	$base_name = pathinfo($finfo['file_name'], PATHINFO_FILENAME);
	$info_file = $del_dir.DIRECTORY_SEPARATOR.$base_name.'.txt';

	$hash = trim($finfo['hash']);
	$type = trim($finfo['type']) == "" ? "vistumbler" : trim($finfo['type']);
	$title = trim($finfo['title']) == "" ? "Untitled" : trim($finfo['title']);
	$title = str_replace(array("|", "\n", "\r"), "", $title);
	$notes = str_replace(array("|", "\n", "\r"), "", $finfo['notes']);
	$user = str_replace(array("|", "\n", "\r"), "", $finfo['file_user']);

	$content = "# FILE HASH | TYPE | FILENAME | ORIG_FILENAME | USERNAME | TITLE | DATE | NOTES\r\n";
	$content .= $hash."|".$type."|".$finfo['file_orig']."|".$finfo['file_name']."|".$user."|".$title."|".$finfo['file_date']."|".$notes."\r\n";

	@file_put_contents($info_file, $content);
}
?>
