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
		else if($this->sql->service == "pgsql")
			{$D_SQL = "SELECT daemon_state FROM settings WHERE node_name = ? LIMIT 1";}
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
				else if($this->sql->service == "pgsql")
					{$E_SQL = "SELECT enabled FROM schedule WHERE id = ? LIMIT 1";}
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
				else if($this->sql->service == "pgsql")
					{$sql_check = "SELECT COUNT(id) FROM files WHERE hash = ?";}
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
		else if($this->sql->service == "pgsql")
			{$sql = "UPDATE files_bad SET thread_id = ?, node_name = ? WHERE id = ?";}
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
			else if($this->sql->service == "pgsql")
				{$sql = "DELETE FROM files_importing WHERE id = ?";}
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
			else if($this->sql->service == "pgsql")
				{$sql = "DELETE FROM files WHERE id = ?";}
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

		// Tell the member whose file it was; the part after " |=| " is the server's path to it, which they do not need.
		try
		{
			$prep = $this->sql->conn->prepare("SELECT file_user, title, file_orig FROM files_bad WHERE id = ?");
			$prep->bindParam(1, $thread_row_id, PDO::PARAM_INT);
			$prep->execute();
			$bad = $prep->fetch(2);
			if($bad && $bad['file_user'])
			{
				$name = $bad['title'] ? $bad['title'] : $bad['file_orig'];
				$reason = trim(explode(' |=| ', (string)$error_msg)[0]);
				$this->wdbmail->notify('import_failed', "Import failed: $name", "Your file $name could not be imported.\r\nReason: $reason", $this->URL_PATH."opt/scheduling.php", '', array($bad['file_user']));
			}
		}
		catch (Throwable $e)
		{
			$this->verbosed("Could not tell the user their import failed: ".$e->getMessage(), -1);
		}
	}


	# Takes out what an import that failed part way got as far as writing, so its files row can go too: its readings, the
	# access points it made (any another import has since seen are kept, pointing at that import instead), the best-GPS
	# links other access points had to its GPS points, the GPS points, and its cell readings.
	public function RemovePartialImport($file_id)
	{
		$q = function ($name) { return $this->sql->ident($name); };
		$run = function ($sql) use ($file_id) { $this->sql->conn->prepare($sql)->execute(array($file_id)); };
		$run("DELETE FROM wifi_hist WHERE ".$q('File_ID')." = ?");
		$run("UPDATE wifi_ap SET ".$q('File_ID')." = (SELECT MIN(h.".$q('File_ID').") FROM wifi_hist h WHERE h.".$q('AP_ID')." = wifi_ap.".$q('AP_ID').") "
			."WHERE ".$q('File_ID')." = ? AND EXISTS (SELECT 1 FROM wifi_hist h WHERE h.".$q('AP_ID')." = wifi_ap.".$q('AP_ID').")");
		$run("DELETE FROM wifi_ap WHERE ".$q('File_ID')." = ?");
		$run("UPDATE wifi_ap SET ".$q('HighGps_ID')." = NULL, high_gps_rssi = NULL, high_gps_sig = NULL WHERE ".$q('HighGps_ID')." IN (SELECT ".$q('GPS_ID')." FROM wifi_gps WHERE ".$q('File_ID')." = ?)");
		$run("DELETE FROM wifi_gps WHERE ".$q('File_ID')." = ?");
		$run("DELETE FROM cell_hist WHERE file_id = ?");
		$run("DELETE FROM cell_id WHERE file_id = ? AND NOT EXISTS (SELECT 1 FROM cell_hist h WHERE h.cell_id = cell_id.cell_id)");
	}

	# A run that died (it failed, or was killed by a restart mid-run) leaves its schedule row 'Running' with its pid, and
	# nothing would take that row again, so the daemon would never run here again. The rows are this node's, so the pids
	# are processes here: a row whose process is gone is set back to waiting before this daemon claims one.
	public function ReleaseDeadClaims()
	{
		$prep = $this->sql->conn->prepare("SELECT id, pid FROM schedule WHERE nodename = ? AND daemon = ? AND status = ?");
		$prep->execute(array($this->node_name, $this->daemon_name, $this->StatusRunning));
		foreach($prep->fetchAll(2) as $row)
		{
			$pid = (int)$row['pid'];
			if($pid > 0 && file_exists("/proc/$pid")) { continue; }
			$this->sql->conn->prepare("UPDATE schedule SET status = ? WHERE id = ? AND status = ?")->execute(array($this->StatusWaiting, $row['id'], $this->StatusRunning));
			$this->verbosed("Schedule row ".$row['id']." was left running by a process that's gone (pid $pid): set back to waiting.", 1);
		}
	}

	public function GetNextImportID()
	{
		# Which waiting files this node may take: any, with the shared files bucket (it fetches what it hasn't got);
		# without it only the uploads that came in here, as the file is on this node's disk, and those queued before
		# uploads were marked with their node.
		$mine = "";
		$params = array();
		try
		{
			require_once $this->PATH.'lib/shared_files.inc.php';
			$shared = SharedFiles::FromSettings($this->sql->conn, isset($GLOBALS['config']) ? $GLOBALS['config'] : array());
		}
		catch(Throwable $e) { $shared = null; }
		if(!$shared)
		{
			$mine = " WHERE (node_name = ? OR node_name IS NULL)";
			$params[] = $this->node_name;
		}
		$columns = "id, file_name, file_orig, file_user, otherusers, title, notes, size, file_date, hash, type, node_name";
		$LastInsert = "";
		$this->sql->conn->beginTransaction();
		try
		{
			if($this->sql->service == "sqlsrv")
			{
				# One statement takes the oldest waiting file off the queue, so two importers never get the same one.
				# READPAST skips a row another importer has locked instead of waiting on it and then finding nothing.
				$prep = $this->sql->conn->prepare("DELETE files_tmp\n"
					. "OUTPUT DELETED.".str_replace(", ", ", DELETED.", $columns)."\n"
					. "WHERE files_tmp.id IN (SELECT TOP 1 id FROM files_tmp WITH (UPDLOCK, READPAST, ROWLOCK)$mine ORDER BY file_date ASC, id ASC)");
				$prep->execute($params);
				$rows = $prep->fetchAll(2);
				$next = $rows ? $rows[0] : null;
			}
			else
			{
				# SKIP LOCKED: two importers running at once each get a different file instead of waiting on, or both
				# taking, the same one
				$prep = $this->sql->conn->prepare("SELECT $columns FROM files_tmp$mine ORDER BY file_date ASC, id ASC LIMIT 1 FOR UPDATE SKIP LOCKED");
				$prep->execute($params);
				$next = $prep->fetch(2);
			}
			# moved into files_importing in the same transaction, so a file can't drop out of the queue between the two
			if($next)
			{
				$sql = "INSERT INTO files_importing (file_name, file_orig, file_user, otherusers, title, notes, size, file_date, hash, tmp_id, type, node_name) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";
				$this->sql->conn->prepare($sql)->execute(array($next['file_name'], $next['file_orig'], $next['file_user'], $next['otherusers'], $next['title'],
					$next['notes'], $next['size'], $next['file_date'], $next['hash'], $next['id'], $next['type'], $next['node_name']));
				$LastInsert = $this->sql->conn->lastInsertId();
				if($this->sql->service != "sqlsrv") { $this->sql->conn->prepare("DELETE FROM files_tmp WHERE id = ?")->execute(array($next['id'])); }
			}
			$this->sql->conn->commit();
		}
		catch(Throwable $e)
		{
			$this->sql->conn->rollBack();
			throw $e;
		}
		# 0 when nothing is waiting: the id is then looked up, and PostgreSQL won't take '' for a number
		return (int)$LastInsert;
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
		// an upload that came in on another node (several nodes on one database) is fetched from the shared files bucket
		$fetch_error = '';
		$fetched = false;
		if(!file_exists($source))
		{
			try
			{
				require_once $this->PATH.'lib/shared_files.inc.php';
				$shared = SharedFiles::FromSettings($this->sql->conn, isset($GLOBALS['config']) ? $GLOBALS['config'] : array());
				if($shared && $shared->FetchUpload($file_name, $source))
				{
					$fetched = true;
					$this->verbosed("Fetched $file_name from the shared files bucket.", 1);
				}
			}
			catch(Throwable $e)
			{
				$fetch_error = "Could not fetch it from the shared files bucket: ".$e->getMessage();
				$this->verbosed($fetch_error, -1);
			}
		}
		if(file_exists($source) && filesize($source) > 0)//make sure the file exists (by size: a zip or database file may have no line breaks in it)
		{
			$this->verbosed("Hey look! a file waiting to be imported, lets import it.", 1);
			if($this->sql->service == "mysql")
				{$update_tmp = "UPDATE files_importing SET tot = 'Preparing for Import', importing = '1' WHERE id = ?";}
			else if($this->sql->service == "sqlsrv")
				{$update_tmp = "UPDATE [files_importing] SET [tot] = 'Preparing for Import', [importing] = '1' WHERE [id] = ?";}
			else if($this->sql->service == "pgsql")
				{$update_tmp = "UPDATE files_importing SET tot = 'Preparing for Import', importing = '1' WHERE id = ?";}
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
					$this->wdbmail->notify('import_started', $subject, $message, $this->URL_PATH."opt/scheduling.php", 'schedule', array($file_user), "Import started: ".($file_title ? $file_title : $file_name));
				}
				
				# a parser that throws (a file that isn't what its type says, or a bug) sets this file aside rather than stopping
				# the run and leaving it half-imported
				try
				{
					if($file_type == "vistumbler" || $file_type == "ns1" || $file_type == "")
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
							if(!is_dir($path_parts['dirname']."/extract")) { mkdir($path_parts['dirname']."/extract", 0775, true); }

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
						elseif($file_ext == 'ns1' || $file_type == "ns1")
						{
							$this->verbosed("Importing NS1. ".$source, 1);
							$tmp = $this->import->import_ns1($source, $file_row,  $importing_id);
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
							if(!is_dir($path_parts['dirname']."/extract")) { mkdir($path_parts['dirname']."/extract", 0775, true); }

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
				}
				catch(Throwable $e)
				{
					if($this->sql->conn->inTransaction()) { $this->sql->conn->rollBack(); }
					$this->RemovePartialImport($file_row);
					$tmp = array(-1, get_class($e).": ".$e->getMessage());
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
					else if($this->sql->service == "pgsql")
						{$update_files_table_sql = "UPDATE files SET aps = ?, gps = ?, \"NewAPPercent\" = ?, completed = 1 WHERE id = ?";}
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
					else if($this->sql->service == "pgsql")
						{$del_file_tmp = "DELETE FROM files_importing WHERE id = ?";}
					#echo $del_file_tmp."\r\n";
					$prep = $this->sql->conn->prepare($del_file_tmp);
					$prep->bindParam(1, $importing_id, PDO::PARAM_INT);
					$prep->execute();
					if($this->sql->checkError(__LINE__, __FILE__))
					{
						$this->wdbmail->alert("Error removing file: $file_name ($importing_id)", "Error removing file: $file_name ($importing_id)", $this->URL_PATH."opt/scheduling.php");
						//$this->logd("Error removing $source ($importing_id) from the Temp files table\r\n\t".var_export($this->sql->conn->errorInfo(),1),"Error", $this->This_is_me);
						$this->verbosed("Error removing $source ($importing_id) from the Temp files table\n\t".var_export($this->sql->conn->errorInfo(),1), -1);
						Throw new ErrorException("Error removing $source ($importing_id) from the Temp files table\n\t".var_export($this->sql->conn->errorInfo(),1));
					}else
					{
						$subject = "Vistumbler WifiDB - File Imported (User:$file_user FileID:$file_row Filename:$file_name)";
						$message = "File has finished importing.\r\nUser: $file_user\r\nTitle: $file_title\r\nFile: $file_name ($file_row)\r\nList Information: ".$this->URL_PATH."opt/userstats.php?func=useraplist&row=$file_row \r\nMap: ".$this->URL_PATH."opt/map.php?func=user_list&id=$file_row \r\n";
						$this->wdbmail->notify('import_finished', $subject, $message, $this->URL_PATH."opt/userstats.php?func=useraplist&row=$file_row", 'import', array($file_user), "Import finished: ".($file_title ? $file_title : $file_name));
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
			$this->cleanBadImport(0, $importing_id, $fetch_error !== '' ? $fetch_error : (file_exists($source) ? 'Empty or not valid' : 'The file is missing from import/up'));
		}
		// a copy fetched only to import here goes again: the bucket and the node it came in on keep it
		if($fetched && file_exists($source)) { unlink($source); }
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
		# one query for all three databases: queued unless it's already waiting, importing or imported
		$dup = $this->sql->conn->prepare("SELECT (SELECT COUNT(*) FROM files_tmp WHERE hash = ?) + (SELECT COUNT(*) FROM files_importing WHERE hash = ?) + (SELECT COUNT(*) FROM files WHERE hash = ?) AS n");
		$dup->execute(array($hash, $hash, $hash));
		if((int)$dup->fetchColumn() > 0)
		{
			$this->verbosed("Already queued or imported. ({$file})\r\n");
			return 0;
		}
		# queued for this node: the file is on its disk
		$prep = $this->sql->conn->prepare("INSERT INTO files_tmp (type, file_name, file_orig, file_date, file_user, notes, title, size, hash, node_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
		$prep->execute(array($type, $file, $file_orig, $date, $user, $notes, $title, $size1, $hash, $this->node_name));
		$this->verbosed("File Inserted into Files_tmp. ({$file})\r\n");
		return 1;
	}

	public function SetNextJob($job_id)
	{
		$nextrun = date("Y-m-d G:i:s", strtotime("+".$this->job_interval." minutes"));
		$this->verbosed("Setting Job Next Run to ".$nextrun, 1);

		if($this->sql->service == "mysql")
			{$sql = "UPDATE schedule SET nextrun = ? , status = ? WHERE id = ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "UPDATE [schedule] SET [nextrun] = ? , [status] = ? WHERE [id] = ?";}
		else if($this->sql->service == "pgsql")
			{$sql = "UPDATE schedule SET nextrun = ? , status = ? WHERE id = ?";}
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
		else if($this->sql->service == "pgsql")
			{$sql = "UPDATE schedule SET status = ?, nextrun = ? WHERE id = ?";}
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
		else if($this->sql->service == "pgsql")
			{$sql = "SELECT count(id) FROM files_tmp";}
		$result = $this->sql->conn->query($sql);
		$fetch = $result->fetch();
	}

#END DAEMON CLASS
}