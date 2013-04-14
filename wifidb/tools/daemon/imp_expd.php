#!/usr/bin/php
<?php
/*
imp_expd.php, WiFiDB Import/Export Daemon
Copyright (C) 2013 Phil Ferland

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

Now this is not what I would call a 'true' 'daemon' by any sorts,
I mean it does have a php script (/tools/rund.php) that can turn
the daemon on and off. But it is a php script that is running
perpetually in the background. I am hoping to get a C++ version working
sometime soon, until then I am using php.
*/
$lastedit  = "2013-01-06";
global $switches;
$switches = array('screen'=>"CLI",'extras'=>'daemon');

if(!(require('config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}

if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($daemon_config['wifidb_install']."/lib/init.inc.php");

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
    echo "Usage: imp_expd.php [args...]
  -v               Run Verbosly (SHOW EVERTHING!)
  -c               Location of the config file you want to load. *
  -l               Log Daemon output to a file.
  -i               Version Info.
  -d               Run continuously without stop (as a daemon) *
  -h               Show this screen.
  
* = Not working yet.
";
    exit();
}

if(@$arguments['l'])
{
    $dbcore->verbosed("WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
Import/Export Daemon 3.0, {$lastedit}, GPLv2 Random Intervals");
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
$dbcore->pid_file = $dbcore->pid_file_loc.'wdb_imp_exp.pid';
fopen($dbcore->pid_file, "w");
$fileappend = fopen($dbcore->pid_file, "a");
$write_pid = fwrite($fileappend, "$dbcore->This_is_me");
if(!$write_pid){die("Could not write pid file, thats not good... >:[");}
$dbcore->logd("Have writen the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")", $dbcore->This_is_me);
$dbcore->verbosed("Have writen the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")");

$dbcore->verbosed("
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - Import/Export Daemon 3.0, {$lastedit}, GPLv2 Random Intervals
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level);


if($dbcore->time_interval_to_check < '30'){$dbcore->time_interval_to_check = '30';} //its really pointless to check more then 5 min at a time, becuse if it is
$finished = 0;
//Main loop
while(1)
{
    if($dbcore->checkDaemonKill())
    {
        exit($dbcore->exit_msg);
    }
    
    $daemon_sql = "SELECT * FROM `wifi`.`files_tmp` where `importing` = '0' ORDER BY `date` ASC";
    $result = $dbcore->sql->conn->query($daemon_sql);
    if($result)//Check to see if I can successfully look at the file_tmp folder
    {
        $files_aa = $result->fetchAll(2);
        foreach($files_aa as $files_a)
        {
            if($dbcore->checkDaemonKill())
            {
                exit($dbcore->exit_msg);
            }

            $remove_file = $files_a['id'];

            $source = $dbcore->PATH.'import/up/'.$files_a['file'];
            
            $file_src = explode(".",$files_a['file']);
            $file_type = strtolower($file_src[1]);
            $file_name = $files_a['file'];
            #Lets check and see if it is has a valid VS1 file header.
            if(in_array($file_type, $dbcore->convert_extentions))
            {
                $ret_file_name = $dbcore->convert_logic($files_a['file'], $remove_file);
                if($ret_file_name === -1)
                {
                    //
                    //
                    // Yeah this needs to be changed... but later..
                    //
                    //
                    continue;
                }
                $file_name = $ret_file_name;
                $source = $dbcore->PATH.'import/up/'.$file_name;
            }
            #$source = str_replace(" ", '\\ ', $source);
	#echo $source."\r\n".$file_name;
            $return  = file($source);
            #echo $return[0]."\r\n";
            $count = count($return);
            if(!($count <= 8) && preg_match("/Vistumbler VS1/", $return[0]))//make sure there is at least a valid file in the field
            {
                $dbcore->verbosed("Hey look! a valid file waiting to be imported, lets import it.", 1);
                $update_tmp = "UPDATE `wifi`.`files_tmp` SET `importing` = '1', `ap` = 'Preping for Import' WHERE `id` = '$remove_file'";
                #echo $update_tmp."\r\n";
                if(!$dbcore->sql->conn->query($update_tmp))
                {
                    $dbcore->verbosed("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.", -1);
                    $dbcore->logd("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($dbcore->sql->conn->errorInfo(),1), $dbcore->This_is_me);
                }

                //check to see if this file has aleady been imported into the DB
                $hash = hash_file('md5', $source);
                $sql_check = "SELECT * FROM `wifi`.`files` WHERE `hash` LIKE ?";
                $prep = $dbcore->sql->conn->prepare($sql_check);
                $prep->execute(array($hash));
                $fileqq = $prep->fetchAll();
                
                if($hash != @$fileqq[0]['hash'])
                {
                    $user = $files_a['user'];
                    $notes = $files_a['notes'];
                    $title = $files_a['title'];
                    
                    $dbcore->logd("Start Import of : (".$files_a['id'].") ".$file_name, $dbcore->This_is_me);
                    $dbcore->verbosed("Start Import of : (".$files_a['id'].") ".$file_name, 1); //default verbise is 0 or none, or STFU, IE dont do shit.
                    
                    $multi_user = explode(";", $user);
                    # Now lets insert some preliminary data into the User Import table as a place holder for the finished product.
                    foreach($multi_user as $muser)
                    {
                        if ($muser === ""){continue;}
                        $sql = "INSERT INTO `wifi`.`user_imports` ( `id` , `username` , `notes` , `title`, `hash`) VALUES ( NULL , ? , ? , ? , ? )";
                        $data = array(
                            $muser , $notes , $title, $hash
                        );
                        $prep = $dbcore->sql->conn->prepare($sql);
                        $prep->execute($data);
                        $err = $dbcore->sql->conn->errorCode();
                        if($err[0] === "00000")
                        {
                            $dbcore->logd("Failed to insert Preliminary user information into the Imports table. :(");
                            $dbcore->verbosed("Failed to insert Preliminary user information into the Imports table. :(\r\n".var_export($dbcore->sql->conn->errorInfo(), 1), -1);
                            return -1;
                        }
                        $user_import_row = $dbcore->sql->conn->lastInsertId();# Lets get the ID of that insert so we know where to put the rest of the imports info later.
                        $dbcore->logd("User ($muser) import row: ".$user_import_row);
                        $dbcore->verbosed("User ($muser) import row: ".$user_import_row);
                    }
                    $sql_select_tmp_file_ext = "SELECT `converted`, `prev_ext` FROM `wifi`.`files_tmp` WHERE `hash` = ?";
                    $prep_ext = $dbcore->sql->conn->prepare($sql_select_tmp_file_ext);
                    $prep_ext->execute(array($hash));
                    $prev_ext = $prep_ext->fetch(2);
                    
                    #echo $files_a['id']."\r\n".$source."\r\n";
                    //
                    //
                    #echo "\r\nRUN IMPORT!\r\n";
                    $tmp = $dbcore->import->import_vs1( $source, $files_a['user']);
                    //
                    //
                    //
                    if($tmp == -1)
                    {
                        $dbcore->logd("Skipping Import of :".$file_name, $dbcore->This_is_me);
                        $dbcore->verbosed("Skipping Import of :".$file_name, -1);
                        continue;
                    }
                    $temp = " | ".$tmp['aps']." - ".$tmp['gps'];
                    $dbcore->logd("Finished Import of : ".$file_name.$temp, $dbcore->This_is_me);
                    $dbcore->verbosed("Finished Import of :".$file_name.$temp, 3);
                    
                    $hash = hash_file('md5', $source);
                    
                    $totalaps = $tmp['aps'];
                    $totalgps = $tmp['gps'];
                    $size = (filesize($source)/1024);
                    $date = $files_a['date'];
                    $sql = "SELECT `id` FROM `wifi`.`user_imports` WHERE `hash` = ?";
                    $prep = $dbcore->sql->conn->prepare($sql);
                    $prep->execute(array($hash));
                    $err = $dbcore->sql->conn->errorCode();
                    if($err[0] != "00000")
                    {
                        $dbcore->logd("Error Fetching the row ID's for the user imports.\r\n".var_export($dbcore->sql->conn->errorInfo(),1), $dbcore->This_is_me);
                        $dbcore->verbosed("Error Adding $source ($remove_file) to the Files table\n\t".var_export($dbcore->sql->conn->errorInfo(),1));
                    }
                    $ids = array();
                    
                    $rows = $prep->fetchAll(PDO::FETCH_ASSOC);
                    foreach($rows as $row)
                    {
                        $ids[] = $row['id'];
                    }
                    $user_ids = implode(":", $ids);
                    
                    $sql_insert_file = "INSERT INTO `wifi`.`files` 
                        (`id`, `file`, `date`, `size`, `aps`, `gps`, `hash`, `user`, `notes`, `title`, `user_row`, `converted`, `prev_ext`) 
                        VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    
                    $prep1 = $dbcore->sql->conn->prepare($sql_insert_file);
                    $prep1->execute(array($file_name, $date, $size, $totalaps, $totalgps, $hash, $user, $notes, $title, $user_ids, $prev_ext['converted'], $prev_ext['prev_ext']));
                    #var_dump(array($file_name, $date, $size, $totalaps, $totalgps, $hash, $user, $notes, $title, $user_ids, $prev_ext['converted'], $prev_ext['prev_ext']));
                    $err1 = $dbcore->sql->conn->errorCode();
                    #var_dump($err1);
                    if($err1[0] == "00000")
                    {
                        $dbcore->logd("Added $source ($remove_file) to the Files table", $dbcore->This_is_me);
                        $dbcore->verbosed("Added $source ($remove_file) to the Files table.\n");
                        
                        $file_row = $dbcore->sql->conn->lastInsertID();
                        #var_dump($file_row);
                        foreach ($multi_user as $muser)
                        {
                            if ($muser === ""){continue;}
                            $get_row_sql = "SELECT `id` FROM `wifi`.`user_imports` WHERE `username` = ? AND `hash` = ? LIMIT 1";
                            $prep2 = $dbcore->sql->conn->prepare($get_row_sql);
                            $prep2->execute(array($muser, $hash));
                            $fetch = $prep2->fetch(1);
                            $row = $fetch['id'];
                            
                            $sql = "UPDATE `wifi`.`user_imports` SET `points` = ?, `date` = ?, `aps` = ?, `gps` = ?, `file_id` = ?, `converted` = ?, `prev_ext` = ? WHERE `id` = ?";
                            $prep3 = $dbcore->sql->conn->prepare($sql);
                            $prep3->execute(array(
                                $tmp['imported'],
                                $tmp['date'],
                                $tmp['aps'],
                                $tmp['gps'],
                                $file_row,
                                $prev_ext['converted'],
                                $prev_ext['prev_ext'],
                                $row));
                            
                            $err = $dbcore->sql->conn->errorCode();
                            if($err[0] == "00000")
                            {
                                $dbcore->verbosed("Updated User Import row.({$muser} $hash : $row)", 2);
                                $dbcore->logd("Updated User Import Row.({$muser} $hash : $row})", $dbcore->This_is_me);
                            }else
                            {
                                $dbcore->verbosed("Failed to update import row.({$muser} $hash : $row})", -1);
                                $dbcore->logd("Failed to update import row. ({$muser} $hash : $row}) ".var_export($dbcore->sql->conn->errorInfo()), $dbcore->This_is_me);
                            }
                        }
                        
                        $del_file_tmp = "DELETE FROM `wifi`.`files_tmp` WHERE `id` = ?";
                        #echo $del_file_tmp."\r\n";
                        $prep = $dbcore->sql->conn->prepare($del_file_tmp);
                        $prep->execute(array($remove_file));
                        $err = $dbcore->sql->conn->errorCode();
                        if($err != "00000")
                        {
                            #mail_users("Error removing file: $source ($remove_file)", "Error removing file: $source ($remove_file)", "import", 1);
                            $dbcore->logd("Error removing $source ($remove_file) from the Temp files table\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1), $dbcore->This_is_me);
                            $dbcore->verbosed("Error removing $source ($remove_file) from the Temp files table\n\t".var_export($dbcore->sql->conn->errorInfo(),1));
                        }else
                        {
                            $sel_new = "SELECT `id` FROM `wifi`.`user_imports` ORDER BY `id` DESC LIMIT 1";
                            $res_new = $dbcore->sql->conn->query($sel_new);
                            $new_array = $res_new->fetchall();
                            $newrow = $new_array[0]['id'];
                            //**TODO**
                            #$message = "File has finished importing.\r\nUser: $user\r\nTitle: $title\r\nFile: $source ($remove_file)\r\nLink: ".$dbcore->PATH."/opt/userstats.php?func=useraplist&row=$newrow \r\n-WiFiDB Daemon.";
                            #mail_users($message, $subject, "import", 0);
                            
                            $dbcore->logd("Removed $source ($remove_file) from the Temp files table.", $dbcore->This_is_me);
                            $dbcore->verbosed("Removed ".$remove_file." from the Temp files table.\n");
                        }
                    }else
                    {
                        #mail_users("Error Adding file to finished table: ".$source, $subject, "import", 1);
                        $dbcore->logd("Error Adding $source ($remove_file) to the Files table\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1), $dbcore->This_is_me);
                        $dbcore->verbosed("Error Adding $source ($remove_file) to the Files table\n\t".var_export($dbcore->sql->conn->errorInfo(),1));
                    }
                    $finished = 1;
                }else
                {
                    $dbcore->logd("File has already been successfully imported into the Database, skipping.\r\n\t\t\t$source ($remove_file)", $dbcore->This_is_me);
                    $dbcore->verbosed("File has already been successfully imported into the Database, skipping.\r\n\t\t\t$source ($remove_file)");
                    $del_file_tmp = "DELETE FROM `wifi`.`files_tmp` WHERE `id` = '$remove_file'";
                    #echo $del_file_tmp."\r\n";
                    #$del_file_tmp = "UPDATE `wifi`.`files_tmp` SET `ap`='File is already in table ".addslashes(var_export($fileqq, 1))."' WHERE `id` = '$remove_file'";
                    if(!$dbcore->sql->conn->query($del_file_tmp))
                    {
                        #mail_users("_error_removing_file_tmp:".$remove_file, $subject, "import", 1);
                        $dbcore->logd("Error removing ".$remove_file." from the Temp files table\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1));
                        $dbcore->verbosed("Error removing ".$remove_file." from the Temp files table\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1));
                    }else
                    {
                        $dbcore->logd("Removed ".$remove_file." from the Temp files table");
                        $dbcore->verbosed("Removed ".$remove_file." from the Temp files table\n");
                    }
                }
            }else
            {
                $finished = 0;
                $dbcore->logd("File is empty or not valid, go and import something.", $dbcore->This_is_me);
                $dbcore->verbosed("File is empty, go and import something.\n");
                $del_file_tmp = "DELETE FROM `wifi`.`files_tmp` WHERE `id` = ?";
                $prep = $this->sql->conn->prepare($del_file_tmp);
                $prep->bindParam(1, $remove_file, PDO::PARAM_INT);
                $prep->execute();
                $err = $this->sql->conn->errorCode();
                #echo $del_file_tmp."\r\n";
                if(!$dbcore->sql->conn->query($del_file_tmp))
                {
                    #mail_users("_error_removing_file_tmp:".$remove_file, $subject, "import", 1);
                    $dbcore->logd("Error removing ".$remove_file." from the Temp files table\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1), $dbcore->This_is_me);
                    $dbcore->verbosed("Error removing ".$remove_file." from the Temp files table\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1)."\n");
                }else
                {
                    $dbcore->logd("Removed empty ".$remove_file." from the Temp files table.", $dbcore->This_is_me);
                    $dbcore->verbosed("Removed empty ".$remove_file." from the Temp files table.\n");
                }
            }
            $result = $dbcore->sql->conn->query($daemon_sql);
            if(!$result)
            {
                $dbcore->verbosed("Failed to update the File table query so that we know what files have already been imported.", -1);
                die();
            }else
            {
                $dbcore->verbosed("Updated the File table query so that we know what files have already been imported.", 3);
            }
            //die("*******************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************\r\n");
        }
        
        #####
        #####
        ##### Daemon Exports
        #####
        #####
	$dbcore->logd("Running Daily Full DB KML Export.", $dbcore->This_is_me);
	$dbcore->verbosed("Running Daily Full DB KML Export.");
	#$dbcore->export->daemon_kml($named = 0);


        $nextrun = date("Y-m-d H:i:s", (time()+$dbcore->config['time_interval_to_check']));
        $sqlup2 = "UPDATE `wifi`.`{$dbcore->sql->settings_tb}` SET `size` = '$nextrun' WHERE `id` = '1'";
        if($dbcore->sql->conn->query($sqlup2))
        {
            $dbcore->logd("Updated settings table with next run time: ".$nextrun, $dbcore->This_is_me);
            $dbcore->verbosed("Updated settings table with next run time: ".$nextrun);
        }else
        {
            #mail_users("_error_updating_settings_table:".$remove_file, $subject, "import", 1);
            $dbcore->logd("ERROR!! COULD NOT Update settings table with next run time: ".$nextrun);
            $dbcore->verbosed("ERROR!! COULD NOT Update settings table with next run time: ".$nextrun);
        }

        $dbcore->logd("File tmp table is empty, go and import something. While your doing that I'm going to sleep for ".($dbcore->config['time_interval_to_check']/60)." minutes.", $dbcore->This_is_me);
        $dbcore->verbosed("File tmp table is empty, go and import something. While your doing that I'm going to sleep for ".($dbcore->config['time_interval_to_check']/60)." minutes.");
    }else
    {
        #mail_users("_error_looking_for_files:", $subject, "import", 1);
        $dbcore->logd("There was an error trying to look into the files_tmp table.\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1), $dbcore->This_is_me);
        $dbcore->verbosed("There was an erroer trying to look into the files_tmp table.\r\n\t".var_export($dbcore->sql->conn->errorInfo(),1));
        die();#########################################################################################################################################
    }
#    die("*******************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! *******************************************************************************************************************************************************************************************************************************************************THIS NEEDS TO BE REMOVED YOU DUMBASSS!!!!!!!!!!!!!!!!!! ************************************************************************************************************************************************************************************");
    if(@$arguments['d'])
    {
        sleep($dbcore->time_interval_to_check);
    }else
    {
        break;
    }
}
?>