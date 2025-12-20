<?php
/*
daemon.inc.php, holds the WiFiDB daemon functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/

class daemon extends wdbcli
{
	public function __construct($config, $daemon_config)
	{
		parent::__construct($config, $daemon_config);
		$this->default_user		 		=	$daemon_config['default_user'];
		$this->default_title			=	$daemon_config['default_title'];
		$this->default_notes			=	$daemon_config['default_notes'];
		$this->StatusWaiting			=	$daemon_config['status_waiting'];
		$this->StatusRunning			=	$daemon_config['status_running'];
		$this->node_name 				=	$daemon_config['wifidb_nodename'];
		$this->NumberOfThreads			=	$daemon_config['NumberOfThreads'];
		$this->LogFile					=	"";
		$this->daemon_name				=	"";
		$this->job_interval				=	0;
		$this->ForceDaemonRun			=	0;
		$this->daemonize				=	0;
		$this->RunOnceThrough			=	0;
		$this->ImportID					=	0;
		$this->NodeSyncing				=	$daemon_config['NodeSyncing'];
		$this->DaemonSleepTime			=	$daemon_config['time_interval_to_check'];
		$this->DeleteDeadPids			=	$daemon_config['DeleteDeadPids'];
		$this->return_message			=	"";

		$this->daemon_version			=	"3.0";
		$this->ver_array['Daemon']		=	array(
												"last_edit"				=>	"2015-Mar-21",
												"CheckDaemonKill"		=>	"1.0",#
												"cleanBadImport"		=>	"1.0",
												"GenerateUserImport"	=>	"1.0",
												"insert_file"			=>	"1.0",
												"parseArgs"				=>	"1.0"
												);
	}
####################
	/**
	 * @return int
	 */
	public function CheckDaemonKill($sched_id = 0)
	{
		#Check if daemon kill flag has been set
		if($this->sql->service == "mysql")
			{$D_SQL = "SELECT daemon_state FROM settings WHERE node_name = ? LIMIT 1";}
		else if($this->sql->service == "sqlsrv")
			{$D_SQL = "SELECT TOP 1 [daemon_state] FROM [settings] WHERE [node_name] = ?";}
		$Dresult = $this->sql->conn->prepare($D_SQL);
		$Dresult->bindParam(1, $this->node_name, PDO::PARAM_STR);
		$Dresult->execute();
		$this->sql->checkError(__LINE__, __FILE__);
		$daemon_state = $Dresult->fetch();
		if($daemon_state['daemon_state'] == 0)
		{
			return 1;
		}else
		{
			if($sched_id)
			{
				#Check if schedule process has been disabled
				if($this->sql->service == "mysql")
					{$E_SQL = "SELECT enabled FROM schedule WHERE id = ? LIMIT 1";}
				else if($this->sql->service == "sqlsrv")
					{$E_SQL = "SELECT TOP 1 [enabled] FROM [schedule] WHERE [id] = ?";}
				$Eresult = $this->sql->conn->prepare($E_SQL);
				$Eresult->bindParam(1, $sched_id, PDO::PARAM_INT);
				$Eresult->execute();
				$this->sql->checkError(__LINE__, __FILE__);
				$enabled_state = $Eresult->fetch();
				if($enabled_state['enabled'] == 0)
				{
					return 1;
				}else
				{
					return 0;
				}
			}
			else
			{
				return 0;
			}
		}
	}
	
	function CheckFileImported($source)
	{
		$retry = true;
		while ($retry)
		{
			try 
			{
				$file_hash1 = hash_file('md5', $source);
				
				if($this->sql->service == "mysql")
					{$sql_check = "SELECT COUNT(id) FROM files WHERE hash = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql_check = "SELECT COUNT([id]) FROM [files] WHERE [hash] = ?";}
				$prep = $this->sql->conn->prepare($sql_check);
				$prep->bindParam(1, $file_hash1, PDO::PARAM_STR);
				$prep->execute();
				$ids = $prep->fetch(1);
				$hash_count = $ids[0];
				if($hash_count == 0){$return = 0;}else{$return = 1;}
				$retry = false;
			}
			catch (Exception $e)
			{
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
				$return = -1;
			}
		}
		return $return;
	}

	function cleanBadImport($file_id = 0, $file_importing_id = 0, $error_msg = "")
	{
		$sql = "INSERT INTO files_bad (file_name, file_orig,file_user,notes,title,size,file_date,hash,converted,prev_ext,type,error_msg) SELECT file_name,file_orig,file_user,notes,title,size,file_date,hash,converted,prev_ext,type,? FROM files_importing WHERE id = ?";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $error_msg, PDO::PARAM_STR);
		$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
		$prep->execute();
		if ($this->sql->checkError()) {
			$this->verbosed("Failed to add bad file to bad import table." . var_export($this->sql->conn->errorInfo(), 1), -1);
			//$this->logd("Failed to add bad file to bad import table." . var_export($this->sql->conn->errorInfo(), 1));
			throw new ErrorException("Failed to add bad file to bad import table.");
		} else {
			$this->verbosed("Added file to the Bad Import table.");
		}
		$thread_row_id = $this->sql->conn->lastInsertId();
		if($this->sql->service == "mysql")
			{$sql = "UPDATE files_bad SET thread_id = ?, node_name = ? WHERE id = ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "UPDATE [files_bad] SET [thread_id] = ?, [node_name] = ? WHERE [id] = ?";}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $this->thread_id, PDO::PARAM_INT);
		$prep->bindParam(2, $this->node_name, PDO::PARAM_STR);
		$prep->bindParam(3, $thread_row_id, PDO::PARAM_INT);
		$prep->execute();

		if ($this->sql->checkError()) {
			$this->verbosed("Failed to update bad file with the Thread ID." . var_export($this->sql->conn->errorInfo(), 1), -1);
			//$this->logd("Failed to update bad file with the Thread ID." . var_export($this->sql->conn->errorInfo(), 1));
			throw new ErrorException("Failed to update bad file with the Thread ID.");
		} else {
			$this->verbosed("Updated file Thread ID in the Bad Import table.");
		}

		if ($file_importing_id !== 0) {
			if($this->sql->service == "mysql")
				{$sql = "DELETE FROM files_importing WHERE id = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "DELETE FROM [files_importing] WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $file_importing_id, PDO::PARAM_INT);
			$prep->execute();
			if ($this->sql->checkError()) {
				$this->verbosed("Failed to remove file from the files_importing table." . var_export($this->sql->conn->errorInfo(), 1), -1);
				$this->logd("Failed to remove bad file from the files_importing table." . var_export($this->sql->conn->errorInfo(), 1));
				throw new ErrorException("Failed to remove bad file from the files_importing table.");
			} else {
				$this->verbosed("Cleaned file from the files_importing table.");
			}
		}

		if ($file_id !== 0) {
			if($this->sql->service == "mysql")
				{$sql = "DELETE FROM files WHERE id = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "DELETE FROM [files] WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $file_id, PDO::PARAM_INT);
			$prep->execute();
			if ($this->sql->checkError()) {
				$this->verbosed("Failed to remove bad file from the files table." . var_export($this->sql->conn->errorInfo(), 1), -1);
				//$this->logd("Failed to remove bad file from the files table." . var_export($this->sql->conn->errorInfo(), 1));
				throw new ErrorException("Failed to remove bad file from the files table.");
			} else {
				$this->verbosed("Cleaned file from the files table.");
			}
		}
	}


	public function GetNextImportID()
	{
		if($this->sql->service == "mysql")
		{
			$this->sql->conn->query("LOCK TABLES files_importing WRITE, files_tmp  WRITE");

			$daemon_sql = "INSERT INTO files_importing (file, file_orig, user, otherusers, title, notes, size, date, hash, tmp_id, type) SELECT file, file_orig, user, otherusers, title, notes, size, date, hash, id, type FROM files_tmp ORDER BY date ASC LIMIT 1";
			$result = $this->sql->conn->prepare($daemon_sql);
			$result->execute();
			$this->sql->checkError(__LINE__, __FILE__);
			$LastInsert = $this->sql->conn->lastInsertID();
			//var_dump($LastInsert);

			$select = "SELECT tmp_id FROM files_importing WHERE id = ?";
			$prep = $this->sql->conn->prepare($select);
			$prep->bindParam(1, $LastInsert, PDO::PARAM_INT);
			$prep->execute();
			$this->sql->checkError(__LINE__, __FILE__);
			$tmp_id = $prep->fetch(2)['tmp_id'];

			$delete = "DELETE FROM files_tmp WHERE id = ?";
			$prep = $this->sql->conn->prepare($delete);
			$prep->bindParam(1, $tmp_id, PDO::PARAM_INT);
			$prep->execute();
			$this->sql->checkError(__LINE__, __FILE__);

			$this->sql->conn->query("UNLOCK TABLES");
		}
		else if($this->sql->service == "sqlsrv")
		{
			$daemon_sql = "DELETE files_tmp\n"
				. "OUTPUT DELETED.id, DELETED.file_name, DELETED.file_orig, DELETED.file_user, DELETED.otherusers, DELETED.title, DELETED.notes, DELETED.size, DELETED.file_date, DELETED.hash, DELETED.type\n"
				. "WHERE files_tmp.id IN (SELECT TOP 1 id FROM files_tmp ORDER BY file_date ASC)";

			$result = $this->sql->conn->prepare($daemon_sql);
			$result->execute();
			$this->sql->checkError(__LINE__, __FILE__);
			$temp_imp_id_arr = $result->fetch();
			if($temp_imp_id_arr['id'] != '')
			{
				$sql = "INSERT INTO files_importing (file_name, file_orig, file_user, otherusers, title, notes, size, file_date, hash, tmp_id, type) VALUES (?,?,?,?,?,?,?,?,?,?,?)";
				$result2 = $this->sql->conn->prepare($sql);
				$result2->bindParam(1, $temp_imp_id_arr['file_name'], PDO::PARAM_STR);
				$result2->bindParam(2, $temp_imp_id_arr['file_orig'], PDO::PARAM_STR);
				$result2->bindParam(3, $temp_imp_id_arr['file_user'], PDO::PARAM_STR);
				$result2->bindParam(4, $temp_imp_id_arr['otherusers'], PDO::PARAM_STR);
				$result2->bindParam(5, $temp_imp_id_arr['title'], PDO::PARAM_STR);
				$result2->bindParam(6, $temp_imp_id_arr['notes'], PDO::PARAM_STR);
				$result2->bindParam(7, $temp_imp_id_arr['size'], PDO::PARAM_STR);
				$result2->bindParam(8, $temp_imp_id_arr['file_date'], PDO::PARAM_STR);
				$result2->bindParam(9, $temp_imp_id_arr['hash'], PDO::PARAM_STR);
				$result2->bindParam(10, $temp_imp_id_arr['id'], PDO::PARAM_INT);
				$result2->bindParam(11, $temp_imp_id_arr['type'], PDO::PARAM_STR);
				$result2->execute();
				$this->sql->checkError(__LINE__, __FILE__);
				$LastInsert = $this->sql->conn->lastInsertID();
			}
			else
			{
				$LastInsert = "";
			}
		}
		return $LastInsert;
		
	}

	function ImportProcess($file_to_Import = array())
	{
		$importing_id = $file_to_Import['id'];
		$file_name = $file_to_Import['file_name'];
		$file_orig = $file_to_Import['file_orig'];
		$file_hash = $file_to_Import['hash'];
		$file_size = $file_to_Import['size'];
		$file_date = $file_to_Import['file_date'];
		$file_type = $file_to_Import['type'];
		$file_user = $file_to_Import['file_user'];
		$file_otherusers = $file_to_Import['otherusers'];
		$file_notes = $file_to_Import['notes'];
		$file_title = $file_to_Import['title'];	
		$file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));		

		$source = $this->PATH.'import/up/'.$file_name;
		if(file_exists($source) && count(file($source)) > 1)//make sure the file exists
		{
			$this->verbosed("Hey look! a file waiting to be imported, lets import it.", 1);
			if($this->sql->service == "mysql")
				{$update_tmp = "UPDATE files_importing SET tot = 'Preparing for Import', importing = '1' WHERE id = ?";}
			else if($this->sql->service == "sqlsrv")
				{$update_tmp = "UPDATE [files_importing] SET [tot] = 'Preparing for Import', [importing] = '1' WHERE [id] = ?";}
			$prep4 = $this->sql->conn->prepare($update_tmp);
			$prep4->bindParam(1, $importing_id, PDO::PARAM_INT);
			$prep4->execute();
			if($this->sql->checkError(__LINE__, __FILE__))
			{
				$this->verbosed("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.",
					-1);
				//$this->logd("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($this->sql->conn->errorInfo(),1),"Error", $this->This_is_me);
				Throw new ErrorException("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.");
			}
			
			if($this->CheckFileImported($source))
			{
				trigger_error("File already imported. $source Thread ID: ".$this->thread_id, E_USER_NOTICE);
				//$this->logd("File has already been successfully imported into the Database, skipping.\r\n\t\t\t$source ($importing_id)","Warning", $this->This_is_me);
				//$this->verbosed("File has already been successfully imported into the Database. Skipping and deleting source file.\r\n\t\t\t$source ($importing_id)");
				//unlink($source);
				$this->verbosed("File has already been successfully imported into the Database. Skipping source file.\r\n\t\t\t$source ($importing_id)");
				$this->cleanBadImport(0, $importing_id, 'Already Imported');
			}
			else
			{

				$sql_insert_file = "INSERT INTO files
				(file_name, file_orig, file_date, size, aps, gps, hash, file_user, otherusers, notes, title, type, node_name)
				VALUES (?, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?)";
				$prep1 = $this->sql->conn->prepare($sql_insert_file);
				$prep1->bindParam(1, $file_name, PDO::PARAM_STR);
				$prep1->bindParam(2, $file_orig, PDO::PARAM_STR);
				$prep1->bindParam(3, $file_date, PDO::PARAM_STR);
				$prep1->bindParam(4, $file_size, PDO::PARAM_STR);
				$prep1->bindParam(5, $file_hash, PDO::PARAM_STR);
				$prep1->bindParam(6, $file_user, PDO::PARAM_STR);
				$prep1->bindParam(7, $file_otherusers, PDO::PARAM_STR);
				$prep1->bindParam(8, $file_notes, PDO::PARAM_STR);
				$prep1->bindParam(9, $file_title, PDO::PARAM_STR);
				$prep1->bindParam(10, $file_type, PDO::PARAM_STR);
				$prep1->bindParam(11, $this->node_name, PDO::PARAM_STR);
				$prep1->execute();
				if($this->sql->checkError(__LINE__, __FILE__))
				{
					//$this->logd("Failed to Insert the results of the new Import into the files table. :(","Error", $this->This_is_me);
					$this->verbosed("Failed to Insert the results of the new Import into the files table. :(\r\n".var_export($this->sql->conn->errorInfo(), 1), -1);
					Throw new ErrorException("Failed to Insert the results of the new Import into the files table. :(");
				}else{
					$file_row = $this->sql->conn->lastInsertID();
					$this->verbosed("Added $source ($importing_id) to the Files table.\n");
					
					$subject = "Vistumbler WifiDB - File Import Started (User:$file_user ImportID:$importing_id FileID:$file_row Filename:$file_name)";
					$message = "File has started importing.\r\nUser: $file_user\r\nTitle: $file_title\r\nFile: $file_name\r\nFileID: $file_row\r\nImport ID: $importing_id\r\nImport Information: ".$this->URL_PATH."opt/scheduling.php \r\n";
					$this->wdbmail->mail_users($message, $subject, "schedule");
				}
				
				if($file_type == "vistumbler" || $file_type == "")
				{
					if($file_ext == 'csv')
					{
						$this->verbosed("Importing CSV. ".$source, 1);
						$tmp = $this->import->import_vistumblercsv($source, $file_row,  $importing_id);
					}
					elseif($file_ext == 'mdb')
					{
						$this->verbosed("Importing MDB. ".$source, 1);
						$tmp = $this->import->import_vistumblermdb($source, $file_row,  $importing_id);
					}
					elseif($file_ext == 'vsz')
					{
						$this->verbosed("Extracting VSZ. ".$source, 1);
						$path_parts = pathinfo($source);
						$detination_vs1 = $path_parts['dirname']."/extract/".$path_parts['filename'].".VS1";

						$VSZ = new ZipArchive();
						if ($VSZ->open($source) === TRUE) {
							if($fileData = $VSZ->getFromName('data.vs1')) {
								file_put_contents($detination_vs1, $fileData);
							}
						}
						
						if (file_exists($detination_vs1)) 
						{
							if($this->CheckFileImported($detination_vs1))
							{
								$tmp = array(-1, "Extracted VS1 has already been imported. ".$detination_vs1);
							}
							else
							{
								$this->verbosed("Importing Extracted VSZ. ".$detination_vs1, 1);
								$tmp = $this->import->import_vs1($detination_vs1, $file_row,  $importing_id);								
							}
							unlink($detination_vs1);
						}
						else
						{
							$tmp = array(-1, "Extracted VS1 Does not exists. ".$detination_vs1);
						}
					}
					else
					{
						$this->verbosed("Importing VS1. ".$source, 1);
						$tmp = $this->import->import_vs1($source, $file_row,  $importing_id);
					}
				}
				elseif ($file_type == "wardrive")
				{
					if($file_ext == "db")
					{
						$this->verbosed("Importing Wardrive4. ".$source, 1);
						$tmp = $this->import->import_wardrive4($source, $file_row,  $importing_id);
					}
					elseif ($file_ext == "db3")
					{
						$this->verbosed("Importing Wardrive3. ".$source, 1);
						$tmp = $this->import->import_wardrive3($source, $file_row,  $importing_id);
					}
					else
					{
						$tmp = array(-1, "Unknown File Type");
					}
				}
				elseif ($file_type == "kismet")
				{
					if($file_ext == "netxml")
					{
						$this->verbosed("Importing Kismet netxml. ".$source, 1);
						$tmp = $this->import->import_kismetnetxml($source, $file_row,  $importing_id);
					}
					else
					{
						$tmp = array(-1, "Unknown File Type");
					}
				}
				elseif ($file_type == "wiglewificsv")
				{
					if($file_ext == "gz")
					{
						$this->verbosed("Extracting GZ. ".$source, 1);
						$path_parts = pathinfo($source);
						$detination_csv = $path_parts['dirname']."/extract/".$path_parts['filename'].".CSV";

						$file = gzopen($source, 'rb');
						$out_file = fopen($detination_csv, 'wb'); 

						while (!gzeof($file)) 
						{
							fwrite($out_file, gzread($file, 4096));
						}

						fclose($out_file);
						gzclose($file);
						
						if (file_exists($detination_csv)) 
						{
							if($this->CheckFileImported($detination_csv))
							{
								$tmp = array(-1, "Extracted CSV has already been imported. ".$detination_csv);
							}
							else
							{
								$this->verbosed("Importing Extracted CSV. ".$detination_csv, 1);
								$tmp = $this->import->import_wiglewificsv($detination_csv, $file_row,  $importing_id);							
							}
							unlink($detination_csv);
						}
						else
						{
							$tmp = array(-1, "Extracted CSV Does not exists. ".$detination_csv);
						}
					}
					else
					{
						$this->verbosed("Importing Wiggle Wifi. ".$source, 1);
						$tmp = $this->import->import_wiglewificsv($source, $file_row,  $importing_id);
					}
				}
				elseif ($file_type == "swardriving")
				{
					$this->verbosed("Importing SWardriving. ".$source, 1);
					$tmp = $this->import->import_swardriving($source, $file_row,  $importing_id);
				}
				else
				{
					$tmp = array(-1, "Unknown File Type");
				}
			
				if(@$tmp[0] === -1)
				{
					trigger_error("Import Error! Reason: $tmp[1] |=| $source Thread ID: ".$this->thread_id, E_USER_NOTICE);
					//$this->logd("Skipping Import \nReason: $tmp[1]\n".$file_name,"Error", $this->This_is_me);
					$this->verbosed("Skipping Import \nReason: $tmp[1]\n".$file_name, -1);
					//remove files_tmp row and user_imports row
					$this->cleanBadImport($file_row, $importing_id, "Import Error! Reason: $tmp[1] |=| $source");
				}
				elseif($tmp['aps'] == 0 && $tmp['gps'] == 0 && $tmp['cells'] == 0 && $tmp['cells_hist'] == 0)
				{
					trigger_error("Import Error! Reason: Import did not have any aps, gps, cells, or cell hist |=| $source Thread ID: ".$this->thread_id, E_USER_NOTICE);
					//$this->logd("Skipping Import \nReason: Import did not have any aps, gps, cells, or cell hist\n".$file_name,"Error", $this->This_is_me);
					$this->verbosed("Skipping Import \nReason: Import did not have any aps, gps, cells, or cell hist\n".$file_name, -1);
					//remove files_tmp row and user_imports row
					$this->cleanBadImport($file_row, $importing_id, "Import Error! Reason: Import did not have any aps, gps, cells, or cell hist |=| $source");
				}
				else
				{
					$this->verbosed("Finished Import of :".$file_name." | AP Count:".$tmp['aps']." - GPS Count: ".$tmp['gps']." - Cell Count: ".$tmp['gps']." - Cell Hist Count: ".$tmp['gps'], 3);
					$NewAPPercent = (int)( ( ( $tmp['newaps'] / $tmp['aps'] ) ) * 100 );
					if($this->sql->service == "mysql")
						{$update_files_table_sql = "UPDATE files SET aps = ?, gps = ?, NewAPPercent = ?, completed = 1 WHERE id = ?";}
					else if($this->sql->service == "sqlsrv")
						{$update_files_table_sql = "UPDATE [files] SET [aps] = ?, [gps] = ?, [NewAPPercent] = ?, [completed] = 1 WHERE [id] = ?";}
					$prep_update_files_table = $this->sql->conn->prepare($update_files_table_sql);
					$prep_update_files_table->bindParam(1, $tmp['aps'], PDO::PARAM_STR);
					$prep_update_files_table->bindParam(2, $tmp['gps'], PDO::PARAM_STR);
					$prep_update_files_table->bindParam(3, $NewAPPercent, PDO::PARAM_INT);
					$prep_update_files_table->bindParam(4, $file_row, PDO::PARAM_INT);

					$prep_update_files_table->execute();
					$this->sql->checkError(__LINE__, __FILE__);

					if($this->sql->service == "mysql")
						{$del_file_tmp = "DELETE FROM files_importing WHERE id = ?";}
					else if($this->sql->service == "sqlsrv")
						{$del_file_tmp = "DELETE FROM [files_importing] WHERE [id] = ?";}
					#echo $del_file_tmp."\r\n";
					$prep = $this->sql->conn->prepare($del_file_tmp);
					$prep->bindParam(1, $importing_id, PDO::PARAM_INT);
					$prep->execute();
					if($this->sql->checkError(__LINE__, __FILE__))
					{
						$this->wdbmail->mail_users("Error removing file: $file_name ($importing_id)", "Error removing file: $file_name ($importing_id)", "import", 1);
						//$this->logd("Error removing $source ($importing_id) from the Temp files table\r\n\t".var_export($this->sql->conn->errorInfo(),1),"Error", $this->This_is_me);
						$this->verbosed("Error removing $source ($importing_id) from the Temp files table\n\t".var_export($this->sql->conn->errorInfo(),1), -1);
						Throw new ErrorException("Error removing $source ($importing_id) from the Temp files table\n\t".var_export($this->sql->conn->errorInfo(),1));
					}else
					{
						$subject = "Vistumbler WifiDB - File Imported (User:$file_user FileID:$file_row Filename:$file_name)";
						$message = "File has finished importing.\r\nUser: $file_user\r\nTitle: $file_title\r\nFile: $file_name ($file_row)\r\nList Information: ".$this->URL_PATH."opt/userstats.php?func=useraplist&row=$file_row \r\nMap: ".$this->URL_PATH."opt/map.php?func=user_list&id=$file_row \r\n";
						$this->wdbmail->mail_users($message, $subject, "import");
						$this->verbosed("Removed ".$importing_id." from the Importing files table.\n");
					}
					$this->return_message = $file_row.":".$tmp['aps'].":".$tmp['gps'];
				}
			}
		}
		else
		{
			trigger_error("File is Empty or bad $source Thread ID: ".$this->thread_id, E_USER_NOTICE);
			//$this->logd("File is empty or not valid. $source ($importing_id)","Warning", $this->This_is_me);
			$this->verbosed("File is empty. Skipping and deleting from files_importing. $source ($importing_id |-| $file_hash)\n");
			//unlink($source);
			$this->cleanBadImport(0, $importing_id, 'Empty or not valid');
		}
		return 1;
	}

	/**
	 * @param $file
	 * @param $file_names
	 * @return int
	 * @throws ErrorException
	 */
	public function insert_file($file, $file_names)
	{
		$source = $this->PATH.'import/up/'.$file;
		#echo $source."\r\n";
		$hash = hash_file('md5', $source);
		$size1 = $this->format_size(filesize($source));
		if(@is_array($file_names[$hash]))
		{
			$type	=	$file_names[$hash]['type'];
			$file_orig	=	$file_names[$hash]['file_orig'];
			$user	=	$file_names[$hash]['file_user'];
			$title	=	$file_names[$hash]['title'];
			$notes	=	$file_names[$hash]['notes'];
			$date	=	$file_names[$hash]['file_date'];
			$hash_	=	$file_names[$hash]['hash'];
			#echo "Is in filenames.txt\n";
		}else
		{
			$type	=	'vistumbler';
			$file_orig	= $file;
			$user	=	$this->default_user;
			$title	=	$this->default_title;
			$notes	=	$this->default_notes;
			$date	=	date("Y-m-d H:i:s");
			$hash_	=	$hash;
			#echo "Recovery import, no previous data :(\n";

		}
		//$this->logd("=== Start Daemon Prep of ".$file." ===");
		
		
		
		if($this->sql->service == "sqlsrv")
		{			
			$retry = true;
			while ($retry)
			{
				try 
				{
					
					$sql = "MERGE INTO files_tmp WITH (HOLDLOCK)\n"
						. "	USING (SELECT :s_hash AS hash) AS newcell (hash)\n"
						. "		ON files_tmp.hash = newcell.hash\n"
						. "	WHEN NOT MATCHED THEN\n"
						. "		INSERT (type, file_name, file_orig, file_date, file_user, notes, title, size, hash)\n"
						. "		VALUES (:type, :file_name, :file_orig, :file_date, :file_user, :notes, :title, :size, :hash);";
						
					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(':s_hash', $hash);
					$prep->bindParam(':type', $type);
					$prep->bindParam(':file_name', $file);
					$prep->bindParam(':file_orig', $file_orig);
					$prep->bindParam(':file_date', $date);
					$prep->bindParam(':file_user', $user);		
					$prep->bindParam(':notes', $notes);
					$prep->bindParam(':title', $title);
					$prep->bindParam(':size', $size1);
					$prep->bindParam(':hash', $hash);
					
					$prep->execute();
					$this->verbosed("File Inserted into Files_tmp. ({$file})\r\n");
					$retry = false;
				}
				catch (Exception $e) 
				{
					$this->verbosed("Failed to insert file info into Files_tmp.\r\n".var_export($this->sql->conn->errorInfo(),1));
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
					$cell_id = 0;
				}
			}
		}
		else if($this->sql->service == "mysql")
		{
			$sql = "INSERT INTO files_tmp (type, file, file_orig, date, user, notes, title, size, hash) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $type, PDO::PARAM_STR);
			$prep->bindParam(2, $file, PDO::PARAM_STR);
			$prep->bindParam(3, $file_orig, PDO::PARAM_STR);
			$prep->bindParam(4, $date, PDO::PARAM_STR);
			$prep->bindParam(5, $user, PDO::PARAM_STR);
			$prep->bindParam(6, $notes, PDO::PARAM_STR);
			$prep->bindParam(7, $title, PDO::PARAM_STR);
			$prep->bindParam(8, $size1, PDO::PARAM_STR);
			$prep->bindParam(9, $hash, PDO::PARAM_STR);
			$prep->execute();

			$err = $this->sql->conn->errorInfo();
			if($err[0] == "00000")
			{
				$this->verbosed("File Inserted into Files_tmp. ({$file})\r\n");
				//$this->logd("File Inserted into Files_tmp.".$sql);
				return 1;
			}else
			{
				$this->verbosed("Failed to insert file info into Files_tmp.\r\n".var_export($this->sql->conn->errorInfo(),1));
				//$this->logd("Failed to insert file info into Files_tmp.".var_export($this->sql->conn->errorInfo(),1));
				throw new ErrorException("Failed to insert file info into Files_tmp.".var_export($this->sql->conn->errorInfo()) );
			}
		}
	}

	public function SetNextJob($job_id)
	{
		$nextrun = date("Y-m-d G:i:s", strtotime("+".$this->job_interval." minutes"));
		$this->verbosed("Setting Job Next Run to ".$nextrun, 1);

		if($this->sql->service == "mysql")
			{$sql = "UPDATE schedule SET nextrun = ? , status = ? WHERE id = ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "UPDATE [schedule] SET [nextrun] = ? , [status] = ? WHERE [id] = ?";}
		$prepnr = $this->sql->conn->prepare($sql);
		$prepnr->bindParam(1, $nextrun, PDO::PARAM_STR);
		$prepnr->bindParam(2, $this->StatusWaiting, PDO::PARAM_STR);
		$prepnr->bindParam(3, $job_id, PDO::PARAM_INT);

		$prepnr->execute();
		$this->sql->checkError(__LINE__, __FILE__);
	}

	public function SetStartJob($job_id)
	{
		$nextrun = date("Y-m-d G:i:s", strtotime("+".$this->job_interval." minutes"))."";
		$this->verbosed("Starting - Job:".$this->daemon_name." Id:".$job_id, 1);

		if($this->sql->service == "mysql")
			{$sql = "UPDATE schedule SET status = ?, nextrun = ? WHERE id = ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "UPDATE [schedule] SET [status] = ?, [nextrun] = ? WHERE [id] = ?";}
		$prepsr = $this->sql->conn->prepare($sql);
		$prepsr->bindParam(1, $this->StatusRunning, PDO::PARAM_STR);
		$prepsr->bindParam(2, $nextrun, PDO::PARAM_STR);
		$prepsr->bindParam(3, $job_id, PDO::PARAM_INT);

		$prepsr->execute();
		$this->sql->checkError(__LINE__, __FILE__);
	}

	public function GetWaitingImportRowCount()
	{
		if($this->sql->service == "mysql")
			{$sql = "SELECT count(id) FROM files_tmp";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT count([id]) FROM [files_tmp]";}
		$result = $this->sql->conn->query($sql);
		$fetch = $result->fetch();
	}

#END DAEMON CLASS
}
