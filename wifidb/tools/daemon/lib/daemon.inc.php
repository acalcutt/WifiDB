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
	public function __construct($config, $daemon_config, &$SQL)
	{
		parent::__construct($config, $daemon_config, $SQL);
		$this->default_user		 		=	$daemon_config['default_user'];
		$this->default_title			=	$daemon_config['default_title'];
		$this->default_notes			=	$daemon_config['default_notes'];
		$this->StatusWaiting			=	$daemon_config['status_waiting'];
		$this->StatusRunning			=	$daemon_config['status_running'];
		$this->node_name 				= 	$daemon_config['wifidb_nodename'];
        $this->NumberOfThreads          =   $daemon_config['NumberOfThreads'];
		$this->daemon_name				=	"";
		$this->job_interval				=	0;
		$this->ForceDaemonRun			=   0;
		$this->daemonize				=	0;
		$this->RunOnceThrough			=	0;
		$this->ImportID					=	0;
		$this->NodeSyncing				=	$daemon_config['NodeSyncing'];
		$this->DaemonSleepTime			=	$daemon_config['time_interval_to_check'];
		$this->DeleteDeadPids			=	$daemon_config['DeleteDeadPids'];
		$this->return_message			=	"";
		$this->convert_extentions   = array('csv','db','db3','vsz');

		$this->daemon_version			=	"3.0";
		$this->ver_array['Daemon']  = array(
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
	public function CheckDaemonKill()
	{
        var_dump($this->node_name);
		$D_SQL = "SELECT `daemon_state` FROM `settings` WHERE `node_name` = ? LIMIT 1";
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
			return 0;
		}
	}


	function cleanBadImport($import_ids, $file_id = 0, $file_importing_id = 0, $error_msg = "")
	{
		if ($import_ids !== 0)
		{
			if (is_array($import_ids)) {
				foreach ($import_ids as $import_id) {
					$this->RemoveUserImport($import_id);
				}
			} elseif ($import_ids === 0) {
			} else {
				$this->RemoveUserImport($import_ids);
			}
		}

		$sql = "INSERT INTO `files_bad` (`file`,`user`,`notes`,`title`,`size`,`date`,`hash`,`converted`,`prev_ext`,`error_msg`) SELECT `file`,`user`,`notes`,`title`,`size`,`date`,`hash`,`converted`,`prev_ext`,? FROM `files_importing` WHERE `id` = ?";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $error_msg, PDO::PARAM_STR);
		$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
		$prep->execute();
		if ($this->sql->checkError()) {
			$this->verbosed("Failed to add bad file to bad import table." . var_export($this->sql->conn->errorInfo(), 1), -1);
			$this->logd("Failed to add bad file to bad import table." . var_export($this->sql->conn->errorInfo(), 1));
			throw new ErrorException("Failed to add bad file to bad import table.");
		} else {
			$this->verbosed("Added file to the Bad Import table.");
		}
		$thread_row_id = $this->sql->conn->lastInsertId();
		$sql = "UPDATE `files_bad` SET `thread_id` = ?, `node_name` = ? WHERE `id` = ?";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $this->thread_id, PDO::PARAM_INT);
		$prep->bindParam(2, $this->node_name, PDO::PARAM_STR);
		$prep->bindParam(3, $thread_row_id, PDO::PARAM_INT);
		$prep->execute();

		if ($this->sql->checkError()) {
			$this->verbosed("Failed to update bad file with the Thread ID." . var_export($this->sql->conn->errorInfo(), 1), -1);
			$this->logd("Failed to update bad file with the Thread ID." . var_export($this->sql->conn->errorInfo(), 1));
			throw new ErrorException("Failed to update bad file with the Thread ID.");
		} else {
			$this->verbosed("Updated file Thread ID in the Bad Import table.");
		}

		if ($file_importing_id !== 0) {
			$sql = "DELETE FROM `files_importing` WHERE `id` = ?";
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
			$sql = "DELETE FROM `files` WHERE `id` = ?";
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $file_id, PDO::PARAM_INT);
			$prep->execute();
			if ($this->sql->checkError()) {
				$this->verbosed("Failed to remove bad file from the files table." . var_export($this->sql->conn->errorInfo(), 1), -1);
				$this->logd("Failed to remove bad file from the files table." . var_export($this->sql->conn->errorInfo(), 1));
				throw new ErrorException("Failed to remove bad file from the files table.");
			} else {
				$this->verbosed("Cleaned file from the files table.");
			}
		}
	}


    public function ExportLiveAPs()
    {

    }

	public function GetNextImportID()
	{
		$this->sql->conn->query("LOCK TABLES wifi.files_importing WRITE, wifi.files_tmp  WRITE");

		$daemon_sql = "INSERT INTO `files_importing` (`file`, `user`, `title`, `notes`, `size`, `date`, `hash`, `tmp_id`) SELECT `file`, `user`, `title`, `notes`, `size`, `date`, `hash`, `id` FROM `files_tmp` ORDER BY `date` ASC LIMIT 1;";
		$result = $this->sql->conn->prepare($daemon_sql);
		$result->execute();
		$this->sql->checkError(__LINE__, __FILE__);
		$LastInsert = $this->sql->conn->lastInsertID();

		$select = "SELECT tmp_id FROM wifi.files_importing WHERE id = ?";
		$prep = $this->sql->conn->prepare($select);
		$prep->bindParam(1, $LastInsert, PDO::PARAM_INT);
		$prep->execute();
		$this->sql->checkError(__LINE__, __FILE__);

		$tmp_id = $prep->fetch(2)['tmp_id'];
		$delete = "DELETE FROM wifi.files_tmp WHERE id = ?";
		$prep = $this->sql->conn->prepare($delete);
		$prep->bindParam(1, $tmp_id, PDO::PARAM_INT);
		$prep->execute();
		$this->sql->checkError(__LINE__, __FILE__);

		$this->sql->conn->query("UNLOCK TABLES");
		return $LastInsert;
	}

    /**
     * @param string $user
     * @param string $notes
     * @param string $title
     * @param string $hash
	 * @param integer $file_row
     * @return array
     * @throws ErrorException
     */
    function GenerateUserImportIDs($user = "", $notes = "", $title = "", $hash = "", $file_row = 0)
    {
        if($file_row === 0)
        {
            throw new ErrorException("GenerateUserImportIDs was passed a blank file_row, this is a fatal exception.");
        }

        if($user === "")
        {
            throw new ErrorException("GenerateUserImportIDs was passed a blank username, this is a fatal exception.");
        }
        $multi_user = explode("|", $user);
        $rows = array();
        $n = 0;
        # Now lets insert some preliminary data into the User Import table as a place holder for the finished product.
        $sql = "INSERT INTO `user_imports` ( `id` , `username` , `notes` , `title`, `hash`, `file_id`) VALUES ( NULL, ?, ?, ?, ?, ?)";
        $prep = $this->sql->conn->prepare($sql);
        foreach($multi_user as $muser)
        {
            if ($muser === ""){continue;}
            $prep->bindParam(1, $muser, PDO::PARAM_STR);
            $prep->bindParam(2, $notes, PDO::PARAM_STR);
            $prep->bindParam(3, $title, PDO::PARAM_STR);
            $prep->bindParam(4, $hash, PDO::PARAM_STR);
            $prep->bindParam(5, $file_row, PDO::PARAM_INT);
            $prep->execute();

            if($this->sql->checkError())
            {
                $this->logd("Failed to insert Preliminary user information into the Imports table. :(", "Error");
                $this->verbosed("Failed to insert Preliminary user information into the Imports table. :(\r\n".var_export($this->sql->conn->errorInfo(), 1), -1);
                Throw new ErrorException;
            }
            $n++;
            $rows[$n] = $this->sql->conn->lastInsertId();
            $this->logd("User ($muser) import row: ".$this->sql->conn->lastInsertId());
            $this->verbosed("User ($muser) import row: ".$this->sql->conn->lastInsertId());
        }
        return $rows;
    }


	function ImportProcess($file_to_Import = array())
	{
		$importing_id = $file_to_Import['id'];

		$source = $this->PATH.'import/up/'.$file_to_Import['file'];

		#echo $file_to_Import['file']."\r\n";
		$file_src = explode(".",$file_to_Import['file']);
		$file_type = strtolower($file_src[1]);
		$file_name = $file_to_Import['file'];
		$file_hash = $file_to_Import['hash'];
		$file_size = (filesize($source)/1024);
		$file_date = $file_to_Import['date'];
		#Lets check and see if it is has a valid VS1 file header.
		if(in_array($file_type, $this->convert_extentions))
		{
			$this->verbosed("This file needs to be converted to VS1 first. Please wait while the computer does the work for you.", 1);
			$update_tmp = "UPDATE `files_importing` SET `ap` = '@#@# CONVERTING TO VS1 @#@#', `converted` = '1', `prev_ext` = ? WHERE `id` = ?";
			$prep = $this->sql->conn->prepare($update_tmp);
			$prep->bindParam(1, $file_type, PDO::PARAM_STR);
			$prep->bindParam(2, $importing_id, PDO::PARAM_INT);
			$prep->execute();
			$err = $this->sql->conn->errorCode();
			if($err[0] != "00000")
			{
				$this->verbosed("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.", -1);
				$this->logd("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($this->sql->conn->errorInfo(),1), "Error", $this->This_is_me);
				throw new ErrorException("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($this->sql->conn->errorInfo(),1));
			}
			$ret_file_name = $this->convert->main($source);
			if($ret_file_name === -1)
			{
				$this->verbosed("Error Converting File. $source, Skipping to next file.");
				if( !$this->daemonize )
				{
					$this->return_message = "ErrorConvertingFile:$source";
					return -1;
				}else
				{
					return 0;
				}

			}

			$parts = pathinfo($ret_file_name);
			$dest_name = $parts['basename'];
			$file_hash1 = hash_file('md5', $ret_file_name);
			$file_size1 = (filesize($ret_file_name)/1024);

			$update = "UPDATE `files_importing` SET `file` = ?, `hash` = ?, `size` = ? WHERE `id` = ?";
			$prep = $this->sql->conn->prepare($update);
			$prep->bindParam(1, $dest_name, PDO::PARAM_STR);
			$prep->bindParam(2, $file_hash1, PDO::PARAM_STR);
			$prep->bindParam(3, $file_size1, PDO::PARAM_STR);
			$prep->bindParam(4, $importing_id, PDO::PARAM_INT);
			$prep->execute();
			$err = $this->sql->conn->errorCode();
			if($err[0] == "00000")
			{
				$this->verbosed("Conversion completed.", 1);
				$this->logd("Conversion completed.".$file_src[0].".".$file_src[1]." -> ".$dest_name, $this->This_is_me);
				$source = $ret_file_name;
				$file_name = $dest_name;
				$file_hash = $file_hash1;
				$file_size = $file_size1;
			}else
			{
				$this->verbosed("Conversion completed, but the update of the table with the new info failed.", -1);
				$this->logd("Conversion completed, but the update of the table with the new info failed.".$file_src[0].".".$file_src[1]." -> ".$source.var_export($this->sql->conn->errorInfo(),1), "Error", $this->This_is_me);
				throw new ErrorException("Conversion completed, but the update of the table with the new info failed.".$file_src[0].".".$file_src[1]." -> ".$source.var_export($this->sql->conn->errorInfo(),1));
			}
		}
		$return	=	file($source);
		$count	=	count($return);
		if(!($count <= 8) && preg_match("/Vistumbler VS1/", $return[0]))//make sure there is at least a 'valid' file in the field
		{
			$this->verbosed("Hey look! a valid file waiting to be imported, lets import it.", 1);
			$update_tmp = "UPDATE `files_importing` SET `ap` = 'Preparing for Import', `importing` = '1' WHERE `id` = ?";
			$prep4 = $this->sql->conn->prepare($update_tmp);
			$prep4->bindParam(1, $importing_id, PDO::PARAM_INT);
			$prep4->execute();
			if($this->sql->checkError(__LINE__, __FILE__))
			{
				$this->verbosed("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.",
					-1);
				$this->logd("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($this->sql->conn->errorInfo(),1),
					"Error", $this->This_is_me);
				Throw new ErrorException("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.");
			}

			//check to see if this file has already been imported into the DB
			$sql_check = "SELECT `hash` FROM `files` WHERE `hash` = ? LIMIT 1";
			$prep = $this->sql->conn->prepare($sql_check);
			$prep->bindParam(1, $file_hash, PDO::PARAM_STR);
			$prep->execute();
			if($this->sql->checkError(__LINE__, __FILE__))
			{
				$this->logd("Failed to select file hash from files table. :(",
					"Error", $this->This_is_me);
				$this->verbosed("Failed to select file hash from files table. :(\r\n".var_export($this->sql->conn->errorInfo(), 1), -1);
				Throw new ErrorException("Failed to select file hash from files table. :(");
			}

			$fileqq = $prep->fetch(2);

			if($file_hash !== @$fileqq['hash'])
			{
				if(count(explode(";", $file_to_Import['notes'])) === 1)
				{
					$user = str_replace(";", "", $file_to_Import['user']);
					$this->verbosed("Start Import of : (".$file_to_Import['id'].") ".$file_name, 1);
				}else
				{
					$user = $file_to_Import['user'];
					$this->verbosed("Start Import of : (".$file_to_Import['id'].") ".$file_name, 1);
				}
				$sql_select_tmp_file_ext = "SELECT `converted`, `prev_ext` FROM `files_importing` WHERE `hash` = ?";
				$prep_ext = $this->sql->conn->prepare($sql_select_tmp_file_ext);
				$prep_ext->bindParam(1, $file_hash, PDO::PARAM_STR);
				$prep_ext->execute();
				if($this->sql->checkError())
				{
					$this->logd("Failed to select previous convert extension. :(",
						"Error", $this->This_is_me);
					$this->verbosed("Failed to select previous convert extension. :(\r\n".var_export($this->sql->conn->errorInfo(), 1), -1);
					Throw new ErrorException("Failed to select previous convert extension. :(");
				}
				$prev_ext = $prep_ext->fetch(2);
				$notes = $file_to_Import['notes'];
				$title = $file_to_Import['title'];
                if( $prev_ext['prev_ext'] === NULL)
                {
                    $PrevExt = "";
                }else
                {
                    $PrevExt =  $prev_ext['prev_ext'];
                }
				$sql_insert_file = "INSERT INTO `files`
				(`id`, `file`, `date`, `size`, `aps`, `gps`, `hash`, `user`, `notes`, `title`, `converted`, `prev_ext`, `node_name`)
				VALUES (NULL, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?)";
				$prep1 = $this->sql->conn->prepare($sql_insert_file);
				$prep1->bindParam(1, $file_name, PDO::PARAM_STR);
				$prep1->bindParam(2, $file_date, PDO::PARAM_STR);
				$prep1->bindParam(3, $file_size, PDO::PARAM_STR);
				$prep1->bindParam(4, $file_hash, PDO::PARAM_STR);
				$prep1->bindParam(5, $user, PDO::PARAM_STR);
				$prep1->bindParam(6, $notes, PDO::PARAM_STR);
				$prep1->bindParam(7, $title, PDO::PARAM_STR);
				$prep1->bindParam(8, $prev_ext['converted'], PDO::PARAM_INT);
				$prep1->bindParam(9, $PrevExt, PDO::PARAM_STR);
				$prep1->bindParam(10, $this->node_name, PDO::PARAM_STR);
				$prep1->execute();

				if($this->sql->checkError(__LINE__, __FILE__))
				{
					$this->logd("Failed to Insert the results of the new Import into the files table. :(",
						"Error", $this->This_is_me);
					$this->verbosed("Failed to Insert the results of the new Import into the files table. :(\r\n".var_export($this->sql->conn->errorInfo(), 1), -1);
					Throw new ErrorException("Failed to Insert the results of the new Import into the files table. :(");
				}else{
					$file_row = $this->sql->conn->lastInsertID();
					var_dump($file_row);
					$this->verbosed("Added $source ($importing_id) to the Files table.\n");
				}

				$import_ids = $this->GenerateUserImportIDs($user, $notes, $title, $file_hash, $file_row);

				$tmp = $this->import->import_vs1( $source, $user, $file_row,  $importing_id);

				if(@$tmp[0] === -1)
				{
					trigger_error("Import Error! Reason: $tmp[1] |=| $source Thread ID: ".$this->thread_id, E_USER_NOTICE);
					$this->logd("Skipping Import \nReason: $tmp[1]\n".$file_name,
						"Error", $this->This_is_me);
					$this->verbosed("Skipping Import \nReason: $tmp[1]\n".$file_name, -1);
					//remove files_tmp row and user_imports row
					$this->cleanBadImport($import_ids, $file_row, $importing_id, "Import Error! Reason: $tmp[1] |=| $source", $this->thread_id);
				}else
				{
					$this->verbosed("Finished Import of :".$file_name." | AP Count:".$tmp['aps']." - GPS Count: ".$tmp['gps'], 3);
					$update_files_table_sql = "UPDATE `files` SET `aps` = ?, `gps` = ?, `completed` = 1 WHERE `id` = ?";
					$prep_update_files_table = $this->sql->conn->prepare($update_files_table_sql);
					$prep_update_files_table->bindParam(1, $tmp['aps'], PDO::PARAM_STR);
					$prep_update_files_table->bindParam(2, $tmp['gps'], PDO::PARAM_STR);
					$prep_update_files_table->bindParam(3, $file_row, PDO::PARAM_INT);

					$prep_update_files_table->execute();
					$this->sql->checkError(__LINE__, __FILE__);

					$sql = "UPDATE `user_imports` SET `points` = ?, `date` = ?, `aps` = ?, `gps` = ?, `file_id` = ?, `converted` = ?, `prev_ext` = ? WHERE `id` = ?";
					$prep3 = $this->sql->conn->prepare($sql);
					foreach($import_ids as $id)
					{
						$prep3->bindParam(1, $tmp['imported'], PDO::PARAM_STR);
						$prep3->bindParam(2, $file_date, PDO::PARAM_STR);
						$prep3->bindParam(3, $tmp['aps'], PDO::PARAM_INT);
						$prep3->bindParam(4, $tmp['gps'], PDO::PARAM_INT);
						$prep3->bindParam(5, $file_row, PDO::PARAM_INT);
						$prep3->bindParam(6, $prev_ext['converted'], PDO::PARAM_INT);
						$prep3->bindParam(7, $prev_ext['prev_ext'], PDO::PARAM_STR);
						$prep3->bindParam(8, $id, PDO::PARAM_INT);
						$prep3->execute();
						$this->sql->checkError(__LINE__, __FILE__);
						$this->verbosed("Updated User Import row. ($id : $file_hash)", 2);
					}

					$del_file_tmp = "DELETE FROM `files_importing` WHERE `id` = ?";
					#echo $del_file_tmp."\r\n";
					$prep = $this->sql->conn->prepare($del_file_tmp);
					$prep->bindParam(1, $importing_id, PDO::PARAM_INT);
					$prep->execute();
					if($this->sql->checkError(__LINE__, __FILE__))
					{
						//**TODO
						#mail_users("Error removing file: $source ($importing_id)", "Error removing file: $source ($importing_id)", "import", 1);
						$this->logd("Error removing $source ($importing_id) from the Temp files table\r\n\t".var_export($this->sql->conn->errorInfo(),1),
							"Error", $this->This_is_me);
						$this->verbosed("Error removing $source ($importing_id) from the Temp files table\n\t".var_export($this->sql->conn->errorInfo(),1), -1);
						Throw new ErrorException("Error removing $source ($importing_id) from the Temp files table\n\t".var_export($this->sql->conn->errorInfo(),1));
					}else
					{
						//**TODO
						#$message = "File has finished importing.\r\nUser: $user\r\nTitle: $title\r\nFile: $source ($importing_id)\r\nLink: ".$this->PATH."/opt/userstats.php?func=useraplist&row=$newrow \r\n-WiFiDB Daemon.";
						#mail_users($message, $subject, "import");
						$this->verbosed("Removed ".$importing_id." from the Importing files table.\n");
					}
					$this->return_message = $file_row.":".$tmp['aps'].":".$tmp['gps'];
				}
			}else
			{
				trigger_error("File already imported. $source Thread ID: ".$this->thread_id, E_USER_NOTICE);
				$this->logd("File has already been successfully imported into the Database, skipping.\r\n\t\t\t$source ($importing_id)",
					"Warning", $this->This_is_me);
				//$this->verbosed("File has already been successfully imported into the Database. Skipping and deleting source file.\r\n\t\t\t$source ($importing_id)");
				//unlink($source);
				$this->verbosed("File has already been successfully imported into the Database. Skipping source file.\r\n\t\t\t$source ($importing_id)");
				$this->cleanBadImport(0, 0, $importing_id, 'Already Imported', $this->thread_id);
			}
		}else
		{
			trigger_error("File is Empty or bad $source Thread ID: ".$this->thread_id, E_USER_NOTICE);
			$this->logd("File is empty or not valid. $source ($importing_id)",
				"Warning", $this->This_is_me);
			$this->verbosed("File is empty. Skipping and deleting from files_importing. $source ($importing_id |-| $file_hash)\n");
			//unlink($source);
			$this->cleanBadImport(0, 0, $importing_id, 'Empty or not valid', $this->thread_id);
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
			$user	=	$file_names[$hash]['user'];
			$title	=	$file_names[$hash]['title'];
			$notes	=	$file_names[$hash]['notes'];
			$date	=	$file_names[$hash]['date'];
			$hash_	=	$file_names[$hash]['hash'];
			#echo "Is in filenames.txt\n";
		}else
		{
			$user	=	$this->default_user;
			$title	=	$this->default_title;
			$notes	=	$this->default_notes;
			$date	=	date("y-m-d H:i:s");
			$hash_	=	$hash;
			#echo "Recovery import, no previous data :(\n";

		}
		$this->logd("=== Start Daemon Prep of ".$file." ===");

		$sql = "INSERT INTO `files_tmp` ( `id`, `file`, `date`, `user`, `notes`, `title`, `size`, `hash`  )
																VALUES ( '', '$file', '$date', '$user', '$notes', '$title', '$size1', '$hash_')";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $file, PDO::PARAM_STR);
		$prep->bindParam(2, $date, PDO::PARAM_STR);
		$prep->bindParam(3, $user, PDO::PARAM_STR);
		$prep->bindParam(4, $notes, PDO::PARAM_STR);
		$prep->bindParam(5, $title, PDO::PARAM_STR);
		$prep->bindParam(6, $size1, PDO::PARAM_STR);
		$prep->bindParam(7, $hash, PDO::PARAM_STR);
		$prep->execute();

		$err = $this->sql->conn->errorInfo();
		if($err[0] == "00000")
		{
			#$this->verbosed("File Inserted into Files_tmp. ({$file})\r\n");
			$this->logd("File Inserted into Files_tmp.".$sql);
			return 1;
		}else
		{
			#$this->verbosed("Failed to insert file info into Files_tmp.\r\n".var_export($this->sql->conn->errorInfo(),1));
			$this->logd("Failed to insert file info into Files_tmp.".var_export($this->sql->conn->errorInfo(),1));
			throw new ErrorException("Failed to insert file info into Files_tmp.".var_export($this->sql->conn->errorInfo()) );
		}
	}

	public function SetNextJob($job_id)
	{
		$nextrun = strtotime("+".$this->job_interval." minutes");
		$this->verbosed("Setting Job Next Run to ".$nextrun, 1);

		$sql = "UPDATE `schedule` SET `nextrun` = ? , `status` = ? WHERE `id` = ?";
		$prepnr = $this->sql->conn->prepare($sql);
		$prepnr->bindParam(1, $nextrun, PDO::PARAM_INT);
		$prepnr->bindParam(2, $this->StatusWaiting, PDO::PARAM_STR);
		$prepnr->bindParam(3, $job_id, PDO::PARAM_INT);

		$prepnr->execute();
		$this->sql->checkError(__LINE__, __FILE__);
	}

	public function SetStartJob($job_id)
	{
		$nextrun = strtotime("+".$this->job_interval." minutes");
		$this->verbosed("Starting - Job:".$this->daemon_name." Id:".$job_id, 1);

		$sql = "UPDATE `schedule` SET `status` = ?, `nextrun` = ? WHERE `id` = ?";
		$prepsr = $this->sql->conn->prepare($sql);
		$prepsr->bindParam(1, $this->StatusRunning, PDO::PARAM_STR);
		$prepsr->bindParam(2, $nextrun, PDO::PARAM_INT);
		$prepsr->bindParam(3, $job_id, PDO::PARAM_INT);

		$prepsr->execute();
		$this->sql->checkError(__LINE__, __FILE__);
	}

    public function GetWaitingImportRowCount()
    {
        $result = $this->sql->conn->query("SELECT count(id) FROM `files_tmp`");
        $fetch = $result->fetch();
        var_dump($fetch[0]);
    }

    public function  RemoveUserImport($import_ID = 0)
    {
        $sql = "DELETE FROM `user_imports` WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $import_ID, PDO::PARAM_STR);
        $prep->execute();
        if($this->sql->checkError())
        {
            $this->verbosed("Failed to remove bad file from the user import table.".var_export($this->sql->conn->errorInfo(),1), -1);
            $this->logd("Failed to remove bad file from the user import table.".var_export($this->sql->conn->errorInfo(),1));
            throw new ErrorException("Failed to remove bad file from the user import table.");
        }else
        {
            $this->verbosed("Cleaned file from the User Import table.");
        }
        return 1;
    }

#END DAEMON CLASS
}