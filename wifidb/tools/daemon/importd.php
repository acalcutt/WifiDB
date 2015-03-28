#!/usr/bin/php
<?php
/*
importd.php, WiFiDB Import Daemon
Copyright (C) 2015 Andrew Calcutt, based on imp_expd.php by Phil Ferland.
This script is made to do imports and be run as a cron job.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "import");

if(!(require('../config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2015-03-21";
$dbcore->daemon_name	=	"Import";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: importd.php [args...]
  -v				Run Verbosely (SHOW EVERYTHING!)
  -i				Version Info.
  -h				Show this screen.
  -l				Show License Information.

* = Not working yet.
";
	exit();
}

if(@$arguments['i'])
{
	$dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$daemon_version}, {$lastedit}, GPLv2 Random Intervals");
	exit();
}

if(@$arguments['l'])
{
	$dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
Copyright (C) 2015 Andrew Calcutt, Phil Ferland
This script is based on imp_expd.php by Phil Ferland. It is made to do just imports and be run as a cron job.

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
");
	exit();
}

if(@$arguments['v'])
{
	$dbcore->verbose = 1;
}else
{
	$dbcore->verbose = 0;
}

if(@$arguments['f'])
{
	$dbcore->ForceDaemonRun = 1;
}else
{
	$dbcore->ForceDaemonRun = 0;
}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$dbcore->pid_file = $dbcore->pid_file_loc.'importd_'.time().'.pid';

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

$dbcore->verbosed("
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level);
# Safely kill script if Daemon kill flag has been set

$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");

#Checking for Import Jobs
$currentrun = date("Y-m-d G:i:s");
$sql = "SELECT `id`, `interval` FROM `wifi`.`schedule` WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `nextrun` <= ? And `enabled` = 1 LIMIT 1";
$prepgj = $dbcore->sql->conn->prepare($sql);
$prepgj->bindParam(1, $dbcore->node_name, PDO::PARAM_STR);
$prepgj->bindParam(2, $dbcore->daemon_name, PDO::PARAM_STR);
$prepgj->bindParam(3, $dbcore->StatusRunning, PDO::PARAM_STR);
$prepgj->bindParam(4, $currentrun, PDO::PARAM_STR);
$prepgj->execute();
$dbcore->sql->checkError(__LINE__, __FILE__);
var_dump($dbcore->ForceDaemonRun);
if($prepgj->rowCount() == 0 && !$dbcore->ForceDaemonRun)
{
	$dbcore->verbosed("There are no import jobs that need to be run... I'll go back to waiting...");
}
else
{
	$dbcore->verbosed("Running...");
	$job = $prepgj->fetch(2);
	#Job Settings
	$dbcore->job_interval = $job['interval'];
	$job_id = $job['id'];

	#Set Job to Running
	$dbcore->SetStartJob($job_id);

	#Check if there are any imports
	While(1)
	{
		if($dbcore->checkDaemonKill())# Safely kill script if Daemon kill flag has been set
		{
			$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
			$dbcore->SetNextJob($job_id);
			exit($dbcore->exit_msg);
		}

		$daemon_sql = "SELECT `id`, `file`, `user`, `notes`, `title`, `date`, `size`, `hash` FROM `wifi`.`files_tmp` where `importing` = '0' ORDER BY `date` ASC LIMIT 1";
		$result = $dbcore->sql->conn->query($daemon_sql);
		if($dbcore->sql->checkError(__LINE__, __FILE__))
		{
			$dbcore->verbosed("There was an error getting a list of import files");
			break;
		}
		elseif($result->rowCount() == 0)
		{
			$dbcore->verbosed("There are no imports waiting, go import something and funny stuff will happen.");
			break;
		}
		else
		{
			##### make sure import/export files are in sync with remote nodes
			//$dbcore->verbosed("Synchronizing files between nodes...", 1);
			//$cmd = '/opt/unison/sync_wifidb_imports > /opt/unison/log/sync_wifidb_imports 2>&1';
			//exec ($cmd);
			#####

			$file_to_Import = $result->fetch(2);
			if(!@$file_to_Import['id'])
			{
				$dbcore->verbosed("Error fetching data.... Skipping row for admin to check into it.");
			}else
			{
				$remove_file = $file_to_Import['id'];
				$source = $dbcore->PATH.'import/up/'.$file_to_Import['file'];

				#echo $file_to_Import['file']."\r\n";
				$file_src = explode(".",$file_to_Import['file']);
				$file_type = strtolower($file_src[1]);
				$file_name = $file_to_Import['file'];
				$file_hash = $file_to_Import['hash'];
				$file_size = (filesize($source)/1024);
				$file_date = $file_to_Import['date'];
				#Lets check and see if it is has a valid VS1 file header.
				if(in_array($file_type, $dbcore->convert_extentions))
				{
					$dbcore->verbosed("This file needs to be converted to VS1 first. Please wait while the computer does the work for you.", 1);
					$update_tmp = "UPDATE `wifi`.`files_tmp` SET `importing` = '0', `ap` = '@#@# CONVERTING TO VS1 @#@#', `converted` = '1', `prev_ext` = ? WHERE `id` = ?";
					$prep = $dbcore->sql->conn->prepare($update_tmp);
					$prep->bindParam(1, $file_type, PDO::PARAM_STR);
					$prep->bindParam(2, $remove_file, PDO::PARAM_INT);
					$prep->execute();
					$err = $dbcore->sql->conn->errorCode();
					if($err[0] != "00000")
					{
						$dbcore->verbosed("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.", -1);
						$dbcore->logd("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($daemon->sql->conn->errorInfo(),1), "Error", $daemon->This_is_me);
						throw new ErrorException("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($daemon->sql->conn->errorInfo(),1));
					}
					$ret_file_name = $dbcore->convert->main($source);
					if($ret_file_name === -1)
					{
						$dbcore->verbosed("Error Converting File. $source, Skipping to next file.");
						continue;
					}

					$parts = pathinfo($ret_file_name);
					$dest_name = $parts['basename'];
					$file_hash1 = hash_file('md5', $ret_file_name);
					$file_size1 = (filesize($ret_file_name)/1024);

					$update = "UPDATE `wifi`.`files_tmp` SET `file` = ?, `hash` = ?, `size` = ? WHERE `id` = ?";
					$prep = $dbcore->sql->conn->prepare($update);
					$prep->bindParam(1, $dest_name, PDO::PARAM_STR);
					$prep->bindParam(2, $file_hash1, PDO::PARAM_STR);
					$prep->bindParam(3, $file_size1, PDO::PARAM_STR);
					$prep->bindParam(4, $remove_file, PDO::PARAM_INT);
					$prep->execute();
					$err = $dbcore->sql->conn->errorCode();
					if($err[0] == "00000")
					{
						$dbcore->verbosed("Conversion completed.", 1);
						$dbcore->logd("Conversion completed.".$file_src[0].".".$file_src[1]." -> ".$dest_name, $dbcore->This_is_me);
						$source = $ret_file_name;
						$file_name = $dest_name;
						$file_hash = $file_hash1;
						$file_size = $file_size1;
					}else
					{
						$dbcore->verbosed("Conversion completed, but the update of the table with the new info failed.", -1);
						$dbcore->logd("Conversion completed, but the update of the table with the new info failed.".$file_src[0].".".$file_src[1]." -> ".$file.var_export($daemon->sql->conn->errorInfo(),1), "Error", $daemon->This_is_me);
						throw new ErrorException("Conversion completed, but the update of the table with the new info failed.".$file_src[0].".".$file_src[1]." -> ".$file.var_export($daemon->sql->conn->errorInfo(),1));
					}
				}
				$return	=	file($source);
				$count	=	count($return);
				if(!($count <= 8) && preg_match("/Vistumbler VS1/", $return[0]))//make sure there is at least a 'valid' file in the field
				{
					$dbcore->verbosed("Hey look! a valid file waiting to be imported, lets import it.", 1);
					$update_tmp = "UPDATE `wifi`.`files_tmp` SET `importing` = '1', `ap` = 'Preparing for Import' WHERE `id` = ?";
					$prep4 = $dbcore->sql->conn->prepare($update_tmp);
					$prep4->bindParam(1, $remove_file, PDO::PARAM_INT);
					$prep4->execute();
					if($dbcore->sql->checkError(__LINE__, __FILE__))
					{
						$dbcore->verbosed("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.",
							-1);
						$dbcore->logd("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($dbcore->sql->conn->errorInfo(),1),
							"Error", $dbcore->This_is_me);
						Throw new ErrorException("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.");
					}

					//check to see if this file has already been imported into the DB
					$sql_check = "SELECT `hash` FROM `wifi`.`files` WHERE `hash` = ? LIMIT 1";
					$prep = $dbcore->sql->conn->prepare($sql_check);
					$prep->bindParam(1, $file_hash, PDO::PARAM_STR);
					$prep->execute();
					if($dbcore->sql->checkError(__LINE__, __FILE__))
					{
						$dbcore->logd("Failed to select file hash from files table. :(",
							"Error", $dbcore->This_is_me);
						$dbcore->verbosed("Failed to select file hash from files table. :(\r\n".var_export($dbcore->sql->conn->errorInfo(), 1), -1);
						Throw new ErrorException("Failed to select file hash from files table. :(");
					}

					$fileqq = $prep->fetch(2);

					if($file_hash != @$fileqq['hash'])
					{
						if(count(explode(";", $file_to_Import['notes'])) === 1)
						{
							$user = str_replace(";", "", $file_to_Import['user']);
							$dbcore->verbosed("Start Import of : (".$file_to_Import['id'].") ".$file_name, 1);
						}else
						{
							$user = $file_to_Import['user'];
							$dbcore->verbosed("Start Import of : (".$file_to_Import['id'].") ".$file_name, 1);
						}
						$sql_select_tmp_file_ext = "SELECT `converted`, `prev_ext` FROM `wifi`.`files_tmp` WHERE `hash` = ?";
						$prep_ext = $dbcore->sql->conn->prepare($sql_select_tmp_file_ext);
						$prep_ext->bindParam(1, $file_hash, PDO::PARAM_STR);
						$prep_ext->execute();
						if($dbcore->sql->checkError())
						{
							$dbcore->logd("Failed to select previous convert extension. :(",
								"Error", $dbcore->This_is_me);
							$dbcore->verbosed("Failed to select previous convert extension. :(\r\n".var_export($dbcore->sql->conn->errorInfo(), 1), -1);
							Throw new ErrorException("Failed to select previous convert extension. :(");
						}
						$prev_ext = $prep_ext->fetch(2);
						$notes = $file_to_Import['notes'];
						$title = $file_to_Import['title'];

                        $sql_insert_file = "INSERT INTO `wifi`.`files`
						(`id`, `file`, `date`, `size`, `aps`, `gps`, `hash`, `user`, `notes`, `title`, `user_row`, `converted`, `prev_ext`, `node_name`)
						VALUES (NULL, ?, ?, ?, 0, 0, ?, ?, ?, ?, ?, ?, ?, ?)";
                        $prep1 = $dbcore->sql->conn->prepare($sql_insert_file);
                        $prep1->bindParam(1, $file_name, PDO::PARAM_STR);
                        $prep1->bindParam(2, $file_date, PDO::PARAM_STR);
                        $prep1->bindParam(3, $file_size, PDO::PARAM_STR);
                        $prep1->bindParam(4, $file_hash, PDO::PARAM_STR);
                        $prep1->bindParam(5, $user, PDO::PARAM_STR);
                        $prep1->bindParam(6, $notes, PDO::PARAM_STR);
                        $prep1->bindParam(7, $title, PDO::PARAM_STR);
                        $prep1->bindParam(8, $user_ids, PDO::PARAM_STR);
                        $prep1->bindParam(9, $prev_ext['converted'], PDO::PARAM_INT);
                        $prep1->bindParam(10, $prev_ext['prev_ext'], PDO::PARAM_STR);
                        $prep1->bindParam(11, $dbcore->node_name, PDO::PARAM_STR);
                        $prep1->execute();
                        if($dbcore->sql->checkError(__LINE__, __FILE__))
                        {
                            $dbcore->logd("Failed to Insert the results of the new Import into the files table. :(",
                                "Error", $dbcore->This_is_me);
                            $dbcore->verbosed("Failed to Insert the results of the new Import into the files table. :(\r\n".var_export($dbcore->sql->conn->errorInfo(), 1), -1);
                            Throw new ErrorException("Failed to Insert the results of the new Import into the files table. :(");
                        }else{
                            $file_row = $dbcore->sql->conn->lastInsertID();
                            $dbcore->verbosed("Added $source ($remove_file) to the Files table.\n");
                        }
                        $import_ids = $dbcore->GenerateUserImportIDs($user, $notes, $title, $file_hash, $file_row);
                        $user_ids = implode(":", $import_ids);

                        $tmp = $dbcore->import->import_vs1( $source, $user, $file_row );
						if($tmp == -1)
						{
							$dbcore->logd("Skipping Import of :".$file_name,
								"Error", $dbcore->This_is_me);
							$dbcore->verbosed("Skipping Import of :".$file_name, -1);
							//remove files_tmp row and user_imports row
							$dbcore->cleanBadImport($file_hash);
						}else
						{
							$dbcore->verbosed("Finished Import of :".$file_name." | AP Count:".$tmp['aps']." - GPS Count: ".$tmp['gps'], 3);
							$update_files_table_sql = "UPDATE `wifi`.`files` SET `aps` = ?, `gps` = ?, `completed` = 1 WHERE `id` = ?";
							$prep_update_files_table = $dbcore->sql->conn->prepare($update_files_table_sql);
							$prep_update_files_table->bindParam(1, $tmp['aps'], PDO::PARAM_STR);
							$prep_update_files_table->bindParam(2, $tmp['gps'], PDO::PARAM_STR);
							$prep_update_files_table->bindParam(3, $file_row, PDO::PARAM_INT);

							$prep_update_files_table->execute();
							$dbcore->sql->checkError(__LINE__, __FILE__);

							$sql = "UPDATE `wifi`.`user_imports` SET `points` = ?, `date` = ?, `aps` = ?, `gps` = ?, `file_id` = ?, `converted` = ?, `prev_ext` = ? WHERE `id` = ?";
							$prep3 = $dbcore->sql->conn->prepare($sql);
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
								$dbcore->sql->checkError(__LINE__, __FILE__);
								$dbcore->verbosed("Updated User Import row. ($id : $file_hash)", 2);
							}

							$del_file_tmp = "DELETE FROM `wifi`.`files_tmp` WHERE `id` = ?";
							#echo $del_file_tmp."\r\n";
							$prep = $dbcore->sql->conn->prepare($del_file_tmp);
							$prep->bindParam(1, $remove_file, PDO::PARAM_INT);
							$prep->execute();
							if($dbcore->sql->checkError(__LINE__, __FILE__))
							{
								//**TODO
								#mail_users("Error removing file: $source ($remove_file)", "Error removing file: $source ($remove_file)", "import", 1);
								$dbcore->logd("Error removing $source ($remove_file) from the Temp files table\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1),
									"Error", $dbcore->This_is_me);
								$dbcore->verbosed("Error removing $source ($remove_file) from the Temp files table\n\t".var_export($dbcore->sql->conn->errorInfo(),1), -1);
								Throw new ErrorException("Error removing $source ($remove_file) from the Temp files table\n\t".var_export($dbcore->sql->conn->errorInfo(),1));
							}else
							{
								//**TODO
								#$message = "File has finished importing.\r\nUser: $user\r\nTitle: $title\r\nFile: $source ($remove_file)\r\nLink: ".$dbcore->PATH."/opt/userstats.php?func=useraplist&row=$newrow \r\n-WiFiDB Daemon.";
								#mail_users($message, $subject, "import");
								$dbcore->verbosed("Removed ".$remove_file." from the Temp files table.\n");
							}
							$finished = 1;
						}
					}else
					{
						$dbcore->logd("File has already been successfully imported into the Database, skipping.\r\n\t\t\t$source ($remove_file)",
							"Warning", $dbcore->This_is_me);
						$dbcore->verbosed("File has already been successfully imported into the Database. Skipping and deleting source file.\r\n\t\t\t$source ($remove_file)");
						unlink($source);
						$dbcore->cleanBadImport($file_hash);
					}
				}else
				{
					$finished = 0;
					$dbcore->logd("File is empty or not valid. $source ($remove_file)",
						"Warning", $dbcore->This_is_me);
					$dbcore->verbosed("File is empty, go and import something. Skipping and deleting source file. $source ($remove_file)\n");
					unlink($source);
					$dbcore->cleanBadImport($file_hash);
				}
			}
		}
	#Finished Job
	$dbcore->verbosed("Finished - Id:".$job_id, 1);
	}
	#Set Next Run Job to Waiting
	$dbcore->SetNextJob($job_id);
	$dbcore->verbosed("Finished - Job: ".$dbcore->daemon_name , 1);
}
unlink($dbcore->pid_file);