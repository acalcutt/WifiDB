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

$lastedit  = "2015-03-06";
$daemon_name = "Import";
$daemon_version = "1.0";
$node_name = $daemon_config['wifidb_nodename'];

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: importd.php [args...]
  -v			   Run Verbosely (SHOW EVERYTHING!)
  -i			   Version Info.
  -h			   Show this screen.
  -l			   Show License Information.

* = Not working yet.
";
	exit();
}

if(@$arguments['i'])
{
	$dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$daemon_name} Daemon {$daemon_version}, {$lastedit}, GPLv2 Random Intervals");
	exit();
}

if(@$arguments['l'])
{
	$dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$daemon_name} Daemon {$daemon_version}, {$lastedit}, GPLv2
Copyright (C) 2015 Andrew Calcutt,
This script is based on imp_expd.php by Phil Ferland. It is made to do just exports and be run as a cron job.

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
 - {$daemon_name} Daemon {$daemon_version}, {$lastedit}, GPLv2
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level);
# Safely kill script if Daemon kill flag has been set
if($dbcore->checkDaemonKill())
{
	$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
	exit($dbcore->exit_msg);
}

$dbcore->verbosed("Running $daemon_name jobs for $node_name");

#Checking for Import Jobs
$currentrun = date("Y-m-d G:i:s");
$sql = "SELECT `id`, `interval` FROM `wifi`.`schedule` WHERE `nodename` = ? And `daemon` = ? And `status` <> ? And `nextrun` <= ? And `enabled` = 1 LIMIT 1";
$prepgj = $dbcore->sql->conn->prepare($sql);
$prepgj->bindParam(1, $node_name, PDO::PARAM_STR);
$prepgj->bindParam(2, $daemon_name, PDO::PARAM_STR);
$prepgj->bindParam(3, $daemon_config['status_running'], PDO::PARAM_STR);
$prepgj->bindParam(4, $currentrun, PDO::PARAM_STR);
$prepgj->execute();

if($prepgj->rowCount() == 0)
{
	$dbcore->verbosed("There are no import jobs that need to be run... I'll go back to waiting...");
}
else
{
	$dbcore->verbosed("Running...");
	$job = $prepgj->fetch(2);

	#Job Settings
	$job_id = $job['id'];
	$job_interval = $job['interval'];

	#Set Job to Running
	$dbcore->verbosed("Starting - Job:".$daemon_name." Id:".$job_id, 1);
	$sql = "UPDATE `wifi`.`schedule` SET `status`=? WHERE `id`=?";
	$prepsr = $dbcore->sql->conn->prepare($sql);
	$prepsr->bindParam(1, $daemon_config['status_running'], PDO::PARAM_STR);
	$prepsr->bindParam(2, $job_id, PDO::PARAM_INT);
	$prepsr->execute();

	#Check if there are any imports
	While(1)
	{
		$daemon_sql = "SELECT * FROM `wifi`.`files_tmp` where `importing` = '0' ORDER BY `date` ASC LIMIT 1";
		$result = $dbcore->sql->conn->query($daemon_sql);
		if($dbcore->sql->checkError())
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
			if($dbcore->checkDaemonKill())# Safely kill script if Daemon kill flag has been set
			{
				$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
				exit($dbcore->exit_msg);
			}elseif(!@$file_to_Import['id'])
			{
				$dbcore->verbosed("Error fetching data.... Skipping row for admin to check into it.");
			}else
			{
				$remove_file = $file_to_Import['id'];
				$source = $dbcore->PATH.'import/up/'.$file_to_Import['file'];

				echo $file_to_Import['file']."\r\n";
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
						Throw new ErrorException("Error Converting File. $source");
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

				$return  = file($source);
				$count = count($return);
				if(!($count <= 8) && preg_match("/Vistumbler VS1/", $return[0]))//make sure there is at least a 'valid' file in the field
				{
					$dbcore->verbosed("Hey look! a valid file waiting to be imported, lets import it.", 1);
					$update_tmp = "UPDATE `wifi`.`files_tmp` SET `importing` = '1', `ap` = 'Preparing for Import' WHERE `id` = ?";
					$prep4 = $dbcore->sql->conn->prepare($update_tmp);
					$prep4->bindParam(1, $remove_file, PDO::PARAM_INT);
					$prep4->execute();
					if($dbcore->sql->checkError())
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
					if($dbcore->sql->checkError())
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
						$notes = $file_to_Import['notes'];
						$title = $file_to_Import['title'];

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
						$tmp = $dbcore->import->import_vs1( $source, $user);
						if($tmp == -1)
						{
							$dbcore->logd("Skipping Import of :".$file_name,
								"Warning", $dbcore->This_is_me);
							$dbcore->verbosed("Skipping Import of :".$file_name, -1);
							//remove files_tmp row and user_imports row
							$dbcore->cleanBadImport($file_hash);
						}else
						{
							$dbcore->verbosed("Finished Import of :".$file_name." | AP Count:".$tmp['aps']." - GPS Count: ".$tmp['gps'], 3);
							$import_ids = $dbcore->GenerateUserImportIDs($user, $notes, $title, $file_hash);

							$totalaps = $tmp['aps'];
							$totalgps = $tmp['gps'];
                            $user_ids = implode(":", $import_ids);
							$sql_insert_file = "INSERT INTO `wifi`.`files`
							   (`id`, `file`, `date`, `size`, `aps`, `gps`, `hash`, `user`, `notes`, `title`, `user_row`, `converted`, `prev_ext`)
						VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

							$prep1 = $dbcore->sql->conn->prepare($sql_insert_file);
							$prep1->bindParam(1, $file_name, PDO::PARAM_STR);
							$prep1->bindParam(2, $file_date, PDO::PARAM_STR);
							$prep1->bindParam(3, $file_size, PDO::PARAM_STR);
							$prep1->bindParam(4, $totalaps, PDO::PARAM_STR);
							$prep1->bindParam(5, $totalgps, PDO::PARAM_STR);
							$prep1->bindParam(6, $file_hash, PDO::PARAM_STR);
							$prep1->bindParam(7, $user, PDO::PARAM_STR);
							$prep1->bindParam(8, $notes, PDO::PARAM_STR);
							$prep1->bindParam(9, $title, PDO::PARAM_STR);
							$prep1->bindParam(10, $user_ids, PDO::PARAM_STR);
							$prep1->bindParam(11, $prev_ext['converted'], PDO::PARAM_INT);
							$prep1->bindParam(12, $prev_ext['prev_ext'], PDO::PARAM_STR);
							echo "file_name:".$file_name."\r\n";
							echo "date:".$file_date."\r\n";
							echo "size:".$file_size."\r\n";
							echo "totalaps:".$totalaps."\r\n";
							echo "totalgps:".$totalgps."\r\n";
							echo "hash:".$file_hash."\r\n";
							echo "user:".$user."\r\n";
							echo "notes:".$notes."\r\n";
							echo "title:".$title."\r\n";
							echo "user_ids:".$user_ids."\r\n";
							echo "prev_ext['converted']:".$prev_ext['converted']."\r\n";
							echo "prev_ext['prev_ext']:".$prev_ext['prev_ext']."\r\n";

							$prep1->execute();
							if($dbcore->sql->checkError())
							{
								$dbcore->logd("Failed to Insert the results of the new Import into the files table. :(",
									"Error", $dbcore->This_is_me);
								$dbcore->verbosed("Failed to Insert the results of the new Import into the files table. :(\r\n".var_export($dbcore->sql->conn->errorInfo(), 1), -1);
								Throw new ErrorException("Failed to Insert the results of the new Import into the files table. :(");
							}
							$file_row = $dbcore->sql->conn->lastInsertID();
							$dbcore->verbosed("Added $source ($remove_file) to the Files table.\n");

							$sql = "UPDATE `wifi`.`user_imports` SET `points` = ?, `date` = ?, `aps` = ?, `gps` = ?, `file_id` = ?, `converted` = ?, `prev_ext` = ? WHERE `id` = ?";
							$prep3 = $dbcore->sql->conn->prepare($sql);
							foreach($import_ids as $id)
							{
								$prep3->bindParam(1, $tmp['imported'], PDO::PARAM_STR);
								$prep3->bindParam(2, $tmp['date'], PDO::PARAM_STR);
								$prep3->bindParam(3, $tmp['aps'], PDO::PARAM_INT);
								$prep3->bindParam(4, $tmp['gps'], PDO::PARAM_INT);
								$prep3->bindParam(5, $file_row, PDO::PARAM_INT);
								$prep3->bindParam(6, $prev_ext['converted'], PDO::PARAM_INT);
								$prep3->bindParam(7, $prev_ext['prev_ext'], PDO::PARAM_STR);
								$prep3->bindParam(8, $id, PDO::PARAM_INT);

								echo "id:".$id."\r\n";
								echo "tmp['imported']:".$tmp['imported']."\r\n";
								echo "tmp['date']:".$tmp['date']."\r\n";
								echo "tmp['aps']:".$tmp['aps']."\r\n";
								echo "tmp['gps']:".$tmp['gps']."\r\n";
								echo "file_row:".$file_row."\r\n";
								echo "prev_ext['converted']:".$prev_ext['converted']."\r\n";
								echo "prev_ext['prev_ext']:".$prev_ext['prev_ext']."\r\n";

								$prep3->execute();
								if($dbcore->sql->checkError())
								{
									$dbcore->logd("Failed to update user import row. :(",
										"Error", $dbcore->This_is_me);
									$dbcore->verbosed("Failed to update user import row. :(\r\n".var_export($dbcore->sql->conn->errorInfo(), 1), -1);
									Throw new ErrorException("Failed to update user import row. :(\r\n".var_export($dbcore->sql->conn->errorInfo(), 1));
								}else
								{
									$dbcore->verbosed("Updated User Import row. ($id : $file_hash)", 2);
								}
							}

							$del_file_tmp = "DELETE FROM `wifi`.`files_tmp` WHERE `id` = ?";
							#echo $del_file_tmp."\r\n";
							$prep = $dbcore->sql->conn->prepare($del_file_tmp);
							$prep->bindParam(1, $remove_file, PDO::PARAM_INT);
							$prep->execute();
							if($dbcore->sql->checkError())
							{
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

				$result = $dbcore->sql->conn->query($daemon_sql);
				if($dbcore->sql->checkError())
				{
					$dbcore->logd("Failed to update the File table query so that we know what files have already been imported.".var_export($dbcore->sql->conn->errorInfo(),1),
						"Error", $dbcore->This_is_me);
					$dbcore->verbosed("Failed to update the File table query so that we know what files have already been imported.", -1);
					Throw new ErrorException("Failed to update the File table query so that we know what files have already been imported.".var_export($dbcore->sql->conn->errorInfo(),1));
				}else
				{
					$dbcore->verbosed("Updated the File table query so that we know what files have already been imported.", 3);
				}
			}

		}
	}

	#Set Next Run Job to Waiting
	$nextrun = date("Y-m-d G:i:s", strtotime("+".$job_interval." minutes"));
	$dbcore->verbosed("Setting Job Next Run to ".$nextrun, 1);
	$sql = "UPDATE `wifi`.`schedule` SET `nextrun` = ? , `status` = ? WHERE `id` = ?";
	$prepnr = $dbcore->sql->conn->prepare($sql);
	$prepnr->bindParam(1, $nextrun, PDO::PARAM_STR);
	$prepnr->bindParam(2, $daemon_config['status_waiting'], PDO::PARAM_STR);
	$prepnr->bindParam(3, $job_id, PDO::PARAM_INT);
	$prepnr->execute();

	#Finished Job
	$dbcore->verbosed("Finished - Job:".$daemon_name." Id:".$job_id, 1);
}
unlink($dbcore->pid_file);