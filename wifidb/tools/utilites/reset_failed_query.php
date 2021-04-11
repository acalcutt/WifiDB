<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbosed("Gathered AP data");

#Go through APs with this File ID
$sql = "SELECT id FROM files WHERE aps = 0 AND completed = 1";
$fidl = $dbcore->sql->conn->prepare($sql);
$fidl->bindParam(1, $File_ID, PDO::PARAM_INT);
$fidl->execute();
while($fid = $fidl->fetch(1))
{
	$File_ID = $fid['id'];
	echo "File_ID:$File_ID\r\n";
	if($File_ID)
	{
		#Go through APs with this File ID
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `AP_ID` FROM `wifi_ap` WHERE File_ID = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [AP_ID]  FROM [wifi_ap] WHERE File_ID = ?";}
		$apl = $dbcore->sql->conn->prepare($sql);
		$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
		$apl->execute();
		while($ap = $apl->fetch(1))
		{
			$AP_ID = $ap['AP_ID'];
			echo "AP_ID:$AP_ID\r\n";
			
			#Find if this AP is in another list
			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `File_ID` FROM `wifi_hist` WHERE `AP_ID` = ? And `File_ID != ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [File_ID] FROM [wifi_hist] WHERE [AP_ID] = ? And [File_ID] != ?";}
			echo "SELECT TOP 1 [File_ID] FROM [wifi_hist] WHERE [AP_ID] = $AP_ID And [File_ID] != $File_ID\r\n";
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $AP_ID, PDO::PARAM_INT);
			$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
			$resgps->execute();
			$fetchgps = $resgps->fetch(2);
			$New_File_ID = $fetchgps['File_ID'];
			if($New_File_ID)
			{
				echo "Updating AP_ID".$AP_ID.")\r\n";
				#Update AP IDs
				$retry = true;
				while ($retry)
				{
					try 
					{
						if($dbcore->sql->service == "mysql")
							{$sqlu = "UPDATE `wifi_ap` SET `File_ID` = ? WHERE `AP_ID` = ?";}
						else if($dbcore->sql->service == "sqlsrv")
							{$sqlu = "UPDATE [wifi_ap] SET [File_ID] = ? WHERE [AP_ID] = ?";}
						echo "UPDATE [wifi_ap] SET [File_ID] = $New_File_ID WHERE [AP_ID] = $AP_ID\r\n";
						$prep = $dbcore->sql->conn->prepare($sqlu);
						$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
						$prep->bindParam(2, $AP_ID, PDO::PARAM_INT);
						$prep->execute();
						$retry = false;
					}
					catch (Exception $e) 
					{
						$retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
					}
				}
			}
		}
			
		#Go through Cells with this File ID
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `cell_id` FROM `cell_id` WHERE file_id = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [cell_id]  FROM [cell_id] WHERE [file_id] = ?";}
		$apl = $dbcore->sql->conn->prepare($sql);
		$apl->bindParam(1, $File_ID, PDO::PARAM_INT);
		$apl->execute();
		while($ap = $apl->fetch(1))
		{
			$cell_id = $ap['cell_id'];
			echo "cell_id:$cell_id\r\n";
			
			#Find if this AP is in another list
			if($dbcore->sql->service == "mysql")
				{$sqlhp = "SELECT `file_id` FROM `cell_hist` WHERE `cell_id` = ? And `file_id != ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sqlhp = "SELECT TOP 1 [file_id] FROM [cell_hist] WHERE [cell_id] = ? And [file_id] != ?";}
			$resgps = $dbcore->sql->conn->prepare($sqlhp);
			$resgps->bindParam(1, $cell_id, PDO::PARAM_INT);
			$resgps->bindParam(2, $File_ID, PDO::PARAM_INT);
			$resgps->execute();
			$fetchgps = $resgps->fetch(2);
			$New_File_ID = $fetchgps['file_id'];
			if($New_File_ID)
			{
				echo "Updating cell_id".$cell_id.")\r\n";
				#Update AP IDs
				$retry = true;
				while ($retry)
				{
					try 
					{
						if($dbcore->sql->service == "mysql")
							{$sqlu = "UPDATE `cell_id` SET `file_id` = ? WHERE `cell_id` = ?";}
						else if($dbcore->sql->service == "sqlsrv")
							{$sqlu = "UPDATE [cell_id] SET [file_id] = ? WHERE [cell_id] = ?";}
						$prep = $dbcore->sql->conn->prepare($sqlu);
						$prep->bindParam(1, $New_File_ID, PDO::PARAM_INT);
						$prep->bindParam(2, $cell_id, PDO::PARAM_INT);
						$prep->execute();
						$retry = false;
					}
					catch (Exception $e) 
					{
						$retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
					}
				}

			}
		}
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sqlhp = "INSERT INTO files_tmp\n"
					.	 "(file_name, file_orig, file_user, otherusers, notes, title, size, date, hash, converted, prev_ext, type)\n"
						. "SELECT file_name, file_orig, file_user, otherusers, notes, title, size, date, hash, converted, prev_ext, type\n"
						. "FROM files\n"
						. "WHERE id = ?";
				$resgps = $dbcore->sql->conn->prepare($sqlhp);
				$resgps->bindParam(1, $File_ID, PDO::PARAM_INT);
				$resgps->execute();
				$retry = false;
			}
			catch (Exception $e) 
			{
				//$retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
				$retry = false;
			}
		}
		
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sqlhp = "DELETE FROM wifi_hist WHERE File_ID = ?";
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
		
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sqlhp = "DELETE FROM wifi_ap WHERE File_ID = ?";
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
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sqlhp = "DELETE FROM wifi_gps WHERE File_ID = ?";
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
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sqlhp = "DELETE FROM cell_hist WHERE file_id = ?";
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
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sqlhp = "DELETE FROM cell_id WHERE file_id = ?";
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
		$retry = true;
		while ($retry)
		{
			try 
			{
				$sqlhp = "DELETE FROM files WHERE id = ?";
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
	}
}