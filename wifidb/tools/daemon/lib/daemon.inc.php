<?php
/*
Daemon.inc.php, holds the WiFiDB daemon functions.
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
        
        $this->export               = new export($config, $daemon_config, $this->colors);
        $this->import               = new import($config, $daemon_config, $this->export, $this->colors);
        $this->time_interval_to_check = $daemon_config['time_interval_to_check'];
        $this->default_user         = $daemon_config['default_user'];
        $this->default_title        = $daemon_config['default_title'];
        $this->default_notes        = $daemon_config['default_notes'];
        $this->convert_extentions   = array('csv','db3','vsz');
        $this->ver_array['Daemon']  = array(
                                    "last_edit"             =>  "2013-Jan-18",
                                    "import_vs1"            =>	"3.0",#
                                    "insert_file"           =>  "1.0",#
                                    "convert2vs1"           =>  "1.0",#
                                    "convert_logic"         =>  "1.0",#
                                    "txt2vs1"               =>  "1.0",#
                                    "csv2vs1"               =>  "1.0",#
                                    "extractVSZ"            =>  "1.0",#
                                    "db32vs1"               =>  "1.0",#
                                    "WriteConvertFile"      =>  "1.0",#
                                    "CheckDaemonKill"       =>  "1.0",#
                                    "verbosed"              =>  "1.0",#
                                    "parseArgs"             =>  "1.0"
                                    );
    }
####################
    public function CheckDaemonKill()
    {
        $D_SQL = "SELECT * FROM `wifi`.`settings` WHERE `table` = 'daemon_state'";
        $Dresult = $this->sql->conn->query($D_SQL);
        $daemon_state = $Dresult->fetchall();

        if($daemon_state[0]['size']=="0")
        {
            $this->exit_msg = "Daemon was told to kill itself";
            return 1;
        }else
        {
            $this->exit_msg = NULL;
            return 0;
        }
    }
####################
    public function insert_file($file, $file_names)
    {
        $source = $this->PATH.'import/up/'.$file;

        $hash = hash_file('md5', $source);
        $size1 = $this->format_size($this->dos_filesize($source));
        if(@is_array($file_names[$hash]))
        {
            $user = $file_names[$hash]['user'];
            $title = $file_names[$hash]['title'];
            $notes = $file_names[$hash]['notes'];
            $date = $file_names[$hash]['date'];
            $hash_ = $file_names[$hash]['hash'];
        }else
        {
            $user = $this->default_user;
            $title = $this->default_title;
            $notes = $this->default_notes;
            $date = date("y-m-d H:i:s");
            $hash_ = $hash;

        }
        $this->logd("=== Start Daemon Prep of ".$file." ===");

        $sql = "INSERT INTO `wifi`.`files_tmp` ( `id`, `file`, `date`, `user`, `notes`, `title`, `size`, `hash`  )
                                                                VALUES ( '', '$file', '$date', '$user', '$notes', '$title', '$size1', '$hash')";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $file, PDO::PARAM_STR);
        $prep->bindParam(2, $date, PDO::PARAM_STR);
        $prep->bindParam(3, $user, PDO::PARAM_STR);
        $prep->bindParam(4, $notes, PDO::PARAM_STR);
        $prep->bindParam(5, $title, PDO::PARAM_STR);
        $prep->bindParam(6, $size1, PDO::PARAM_STR);
        $prep->bindParam(7, $hash, PDO::PARAM_STR);
        $prep->execute();
        
        $err = $this->sql->conn->errorCode();
        if($err[0] === "00000")
        {
            $this->verbosed("File Inserted into Files_tmp. ({$file})\r\n");
            $this->logd("File Inserted into Files_tmp.".$sql);
            return 1;
        }else
        {
            $this->verbosed("Failed to insert file info into Files_tmp.\r\n".var_export($this->sql->conn->errorInfo(),1));
            $this->logd("Failed to insert file info into Files_tmp.".var_export($this->sql->conn->errorInfo(),1));
            return 0;
        }
    }
####################

    
    public function convert_logic($file, $remove_file)
    {
        $source = $this->PATH.'import/up/'.str_replace("%20", " ", $file);
        $this->verbosed("This file needs to be converted to VS1 first. Please wait while the computer does the work for you.", 1);
        $file_src = explode(".", $file);
        $update_tmp = "UPDATE `wifi`.`files_tmp` SET `importing` = '0', `ap` = '@#@# CONVERTING TO VS1 @#@#', `converted` = '1', `prev_ext` = ? WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($update_tmp);
        $prep->execute(array($file_src[1],$remove_file));
        $err = $this->sql->conn->errorCode();
        if($err[0] != "00000")
        {
            $this->verbosed("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.", -1);
            $this->logd("Failed to set the Import flag for this file. If running with more than one Import Daemon you may have problems.".var_export($this->sql->conn->errorInfo(),1));
        }
        $dest_file = $this->convert2vs1($source);
        if($dest_file == -1)
        {
            $this->verbosed("Convert failed!", -1);
            return -1;
        }
        $dest_name = 'convert/'.str_replace(" ", "_", $dest_file);
        $dest = $this->PATH.'import/up/'.$dest_name;
        $hash1 = hash_file('md5', $dest);
        $size1 = $this->format_size($dest);
        
        $update = "UPDATE `wifi`.`files_tmp` SET `file` = ?, `hash` = ?, `size` = ? WHERE `id` = ?";
        $data = array(
            $dest_name,
            $hash1,
            $size1,
            $remove_file
        );
        $prep = $this->sql->conn->prepare($update);
        $prep->execute($data);
        $err = $this->sql->conn->errorCode();
        if($err[0] == "00000")
        {
            $this->verbosed("Conversion completed.", 1);
            $this->logd("Conversion completed.".$file_src[0].".".$file_src[1]." -> ".$dest, $this->This_is_me);
        }else
        {
            $this->verbosed("Conversion completed, but the update of the table with the new info failed.", -1);
            $this->logd("Conversion completed, but the update of the table with the new info failed.".$file_src[0].".".$file_src[1]." -> ".$file.var_export($this->sql->conn->errorInfo(),1), $this->This_is_me);
        }
        return $dest_name;
    }
    
    public function convert2vs1($source='')
    {
        if($source == ''){return FALSE;}
        $ext_e = explode(".", $source);
        $c_ext = count($ext_e)-1;
        $file_type = strtolower($ext_e[$c_ext]);
        $ext_e[$c_ext] = "vs1";
        $converted = implode(".", $ext_e);
        switch($file_type)
        {
            case "db3":
                $data = $this->db32vs1($source);
                break;
            case "csv":
                $data = $this->csv2vs1($source);
                break;
            case "vsz":
                $data = $this->extractVSZ($source);
                break;
            default:
                $this->verbosed("Unsupported File Type of :$file_type", -1);
                $this->logd("Unsupported File Type of :$file_type", $this->This_is_me);
                break;
        }
        if(!is_string($data))
        {
            $filename = $this->WriteConvertFile($converted, $data);
            return $filename;
        }else
        {
            return $data;
        }
    }
    
    public function extractVSZ($source)
    {
        $dir = $this->PATH."import/up/";
        $file_exp = explode(".", $source);
        $folder_exp = explode("/", $file_exp[0]);
        $c = count($folder_exp)-1;
        $folder = $folder_exp[$c];
        $extract_path = $dir."convert/".$folder;
        $this->verbosed("Make Extract folder for this file: $extract_path");
        if(@mkdir($extract_path))
        {
            $this->verbosed("Folder created!", 2);
        }else
        {
            $this->verbosed("Failed to create the folder", -1);
        }
        $zip = new ZipArchive;
        $zip->open($source, ZIPARCHIVE::CREATE);
        $zip->extractTo($extract_path, array($zip->getNameIndex(0)));
        rename($extract_path."/data.vs1", $extract_path."/$folder.vs1");
        return $folder."/$folder.vs1";
    }
    
    public function txt2vs1($source)
    {
        $apdata = array();
        $gpsdata = array();
        $return = file($source);
        //create interval for progress
        $line = count($return);
        $stat_c = $line/97;
        $complete = 0;
        $c = 0;
        $cc = 0;
        if ($this->debug ==1){echo $stat_c."\r\n";}
        if ($this->debug ==1){echo $line."\r\n";}
        // Start the main loop
        foreach($return as $ret)
        {
            $c++;
            $cc++;
            if ($ret[0] == "#"){continue;}
            $wifi = explode("|",$ret);
            $ret_count = count($wifi);
            if ($ret_count == 17)// test to see if the data is in correct format
            {
                if ($cc >= $stat_c)
                {
                    $cc=0;
                    $complete++;
                    echo $complete."% - ";
                    if ($complete == 100 ){ echo "\r\n\r\n";}
                }
                //format date and time
                $datetime = explode(" ",$wifi[13]);
                $date = $datetime[0];
                $time = $datetime[1];

                // This is a temp array of data to be tested against the GPS array
                $gpsdata_t=array(
                                "lat"=> $wifi[8],
                                "long"=> $wifi[9],
                                "sats"=> "0",
                                "hdp"=> '0.0',
                                "alt"=> '0.0',
                                "geo"=> '-0.0',
                                "kmh"=> '0.0',
                                "mph"=> '0.0',
                                "track"=> '0.0',
                                "date"=>$date,
                                "time"=>$time
                                );

                // Create the Security Type number for the respective Access point
                if ($wifi[4]=="Open"&&$wifi[5]=="None"){$sectype="1";}
                if ($wifi[4]=="Open"&&$wifi[5]=="WEP"){$sectype="2";}
                if ($wifi[4]=="WPA-Personal" or $wifi[4] =="WPA2-Personal"){$sectype="3";}

                    if ($GLOBALS["debug"] ==1)
                    {echo "\$n = ".$n."\n\$N = ".$N."\n";}
                    $n++;
                    $N++;
                    $sig=$n.",".$wifi[3];
                    $gpsdata[$n]=array(
                                    "id"=>$n,
                                    "lat"=>$wifi[8],
                                    "long"=>$wifi[9],
                                    "sats"=>'0',
                                    "hdp"=> '0.0',
                                    "alt"=> '0.0',
                                    "geo"=> '-0.0',
                                    "kmh"=> '0.0',
                                    "mph"=> '0.0',
                                    "track"=> '0.0',
                                    "date"=>$date,
                                    "time"=>$time
                                    );

                    $apdata[$N]=array(
                                    "ssid"=>$wifi[0],
                                    "mac"=>$wifi[1],
                                    "man"=>$wifi[2],
                                    "auth"=>$wifi[4],
                                    "encry"=>$wifi[5],
                                    "sectype"=>$sectype,
                                    "radio"=>$wifi[6],
                                    "chan"=>$wifi[7],
                                    "btx"=>$wifi[10],
                                    "otx"=>$wifi[11],
                                    "nt"=>$wifi[14],
                                    "label"=>$wifi[15],
                                    "sig"=>$sig
                                    );
                    if ($GLOBALS["debug"] == 1 )
                    {
                        echo "\n\n+_+_+_+_+_+_\n".$gpsdata[$n]["lat"]."  +_\n".$gpsdata[$n]["long"]."  +_\n".$gpsdata[$n]["sats"]."  +_\n".$gpsdata[$n]["date"]."  +_\n".$gpsdata[$n]["time"]."  +_\n";
                        echo "Access Point Number: ".$N."\n";
                        echo "=-=-=-=-=-=-\n".$apdata[$N]["ssid"]."  =-\n".$apdata[$N]["mac"]."  =-\n".$apdata[$N]["auth"]."  =-\n".$apdata[$N]["encry"]."  =-\n".$apdata[$N]["sectype"]."  =-\n".$apdata[$N]["radio"]."  =-\n".$apdata[$N]["chan"]."  =-\n".$apdata[$N]["btx"]."  =-\n".$apdata[$N]["otx"]."  =-\n".$apdata[$N]["nt"]."  =-\n".$apdata[$N]["label"]."  =-\n".$apdata[$N]["sig"]."\n";
                    }
                
            }else
            {
                    echo "\nLine: ".$c." - Wrong data type, dropping row\n";
            }
            if(@$gpsdata_t[0])
            {
                unset($gpsdata_t[0]);
            }
        }
    }
    
    public function csv2vs1($source)
    {
        $apdata = array();
        $gpsdata = array();
        if(($handle = fopen($source, "r")) !== FALSE)
        {
            $n = 1;
            while(($line = fgetcsv($handle, 1000, ",")) !== FALSE)
            {
                if($line[1] == "BSSID" && $line[2] == "MANUFACTURER" && $line[3] == "SIGNAL" && $line[4] == "High Signal"){continue;}
                $line_count = count($line);
                if($line_count < 26){echo "CSV file with less than 26 fields\r\n";break;}
                if(!@$this->languages->code)
                {
                    $this->languages->code = $this->languages->FindLanguageType($line[8]);
                }
                if ($line[7]===$this->languages->current_language[1]['SearchWords']['Open'] && $line[8]===$this->languages->current_language[1]['SearchWords']['None']){$sectype="1";}
                if ($line[7]===$this->languages->current_language[1]['SearchWords']['Open'] && $line[8]===$this->languages->current_language[1]['SearchWords']['WEP']){$sectype="2";}
                if ($line[8] !== $this->languages->current_language[1]['SearchWords']['None'] || $line[8] !== $this->languages->current_language[1]['SearchWords']['WEP']){$sectype="3";}
                
                $ap_hash = md5(
                        $line[0].
                        $line[1].
                        $line[10].
                        $sectype.
                        $line[9].
                        $line[7].
                        $line[8]);
                $gpsdata[$n] = array(
                            "id"=>$n,
                            "lat"=>$line[15],
                            "long"=>$line[16],
                            "sats"=>$line[17],
                            "hdp"=> $line[18],
                            "alt"=> $line[19],
                            "geo"=> $line[20],
                            "kmh"=> $line[21],
                            "mph"=> $line[22],
                            "track"=> $line[23],
                            "date"=>$line[24],
                            "time"=>$line[25]
                            
                );
                if(is_array(@$apdata[$ap_hash]))
                {
                    $apdata[$ap_hash]['sig'][$n] = array($n,$line[3],$line[5]);
                }
                else
                {
                    $apdata[$ap_hash] = array(
                            "ssid"=>$line[0],
                            "mac"=>$line[1],
                            "man"=>$line[2],
                            "HighSig"=>$line[4],
                            "HighRSSI"=>$line[6],
                            "auth"=>$line[7],
                            "encry"=>$line[8],
                            "sectype"=>$sectype,
                            "radio"=>$line[9],
                            "chan"=>$line[10],
                            "btx"=>$line[11],
                            "otx"=>$line[12],
                            "nt"=>$line[13],
                            "label"=>$line[14],
                            "sig"=>array($n => array($n,$line[3],$line[5]))
                    );
                }
                $n++;
            }
            fclose($handle);
        }
        $apdata1 = array();
        foreach($apdata as $ap)
        {
            $apdata1[] = $ap;
        }
        echo "Counts: ".count($apdata)." | ".count($gpsdata)."\r\n";
        $data = array($apdata1, $gpsdata);
        return $data;
    }
    
    public function db32vs1($source)
    {
        $apdata = array();
        $gpsdata = array();
        $dbh = new PDO("sqlite:$source"); // success
        $dbh->setAttribute(PDO::ATTR_ERRMODE, 
                           PDO::ERRMODE_EXCEPTION);
        
        $thing = $dbh->query("SELECT * FROM networks");
        $all_aps = $thing->fetchAll();
        
        $n=1;
        $N=1;
        foreach ($all_aps as $row)
        {
            $sig = array();
            $ssid = $row["ssid"];
            $mac = strtoupper($row["bssid"]);
            $man = $this->manufactures($mac);

            $Found_Capabilies = $row["capabilities"];

            If(stristr($Found_Capabilies, "WPA2-PSK-CCMP") Or stristr($Found_Capabilies, "WPA2-PSK-TKIP+CCMP"))
            {	$Found_AUTH = "WPA2-Personal";
                $Found_ENCR = "CCMP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-PSK-CCMP") Or stristr($Found_Capabilies, "WPA-PSK-TKIP+CCMP"))
            {	$Found_AUTH = "WPA-Personal";
                $Found_ENCR = "CCMP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA2-EAP-CCMP") Or stristr($Found_Capabilies, "WPA2-EAP-TKIP+CCMP"))
            {	$Found_AUTH = "WPA2-Enterprise";
                $Found_ENCR = "CCMP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-EAP-CCMP") Or stristr($Found_Capabilies, "WPA-EAP-TKIP+CCMP"))
            {	$Found_AUTH = "WPA-Enterprise";
                $Found_ENCR = "CCMP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA2-PSK-TKIP"))
            {	$Found_AUTH = "WPA2-Personal";
                $Found_ENCR = "TKIP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-PSK-TKIP"))
            {	$Found_AUTH = "WPA-Personal";
                $Found_ENCR = "TKIP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA2-EAP-TKIP"))
            {	$Found_AUTH = "WPA2-Enterprise";
                $Found_ENCR = "TKIP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-EAP-TKIP"))
            {	$Found_AUTH = "WPA-Enterprise";
                $Found_ENCR = "TKIP";
                $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WEP"))
            {	$Found_AUTH = "Open";
                $Found_ENCR = "WEP";
                $Found_SecType = 2;
            }Else
            {	$Found_AUTH = "Open";
                    $Found_ENCR = "None";
                    $Found_SecType = 1;
            }
            if(stristr($Found_Capabilies, "IBSS"))
            {
                    $nt = "Ad-Hoc";
            }else
            {
                    $nt = "Infrastructure";
            }
            $authen = $Found_AUTH;
            $encry = $Found_ENCR;
            $sectype = $Found_SecType;
            ###########################
            switch($row["frequency"]+0)
            {
                case 2412:
                        $chan = 1;
                        $radio = '802.11g';
                break;
                case 2417:
                        $chan = 2;
                        $radio = '802.11g';
                break;
                case 2422:
                        $chan = 3;
                        $radio = '802.11g';
                break;
                case 2427:
                        $chan = 4;
                        $radio = '802.11g';
                break;
                case 2432:
                        $chan = 5;
                        $radio = '802.11g';
                break;
                case 2437:
                        $chan = 6;
                        $radio = '802.11g';
                break;
                case 2442:
                        $chan = 7;
                        $radio = '802.11g';
                break;
                case 2447:
                        $chan = 8;
                        $radio = '802.11g';
                break;
                case 2452:
                        $chan = 9;
                        $radio = '802.11g';
                break;
                case 2457:
                        $chan = 10;
                        $radio = '802.11g';
                break;
                case 2462:
                        $chan = 11;
                        $radio = '802.11g';
                break;
                case 2467:
                        $chan = 12;
                        $radio = '802.11g';
                break;
                case 2472:
                        $chan = 13;
                        $radio = '802.11g';
                break;
                case 2484:
                        $chan = 14;
                        $radio = '802.11b';
                break;
                default:
                        $chan = 6;
                        $radio = 'g';
                break;
            }
            $level = (100+$row["level"]);
            $alt = $row["alt"];

            $time_pre = str_split($row["timestamp"], 10);
            $timestamp = date("Y-m-d H:i:s", $time_pre[0]);
            
    #	if($ssid_t == "yellow"){ die(); }
            //format date and time
            $datetime = explode(" ",$timestamp);
            $date = $datetime[0];
            $time = $datetime[1];
            $lat = $row['lat'];
            $long = $row['lon'];
            #echo "$timestamp - - $man\r\n$nt - $authen - $encry - $sectype - $lat - $long\r\n----------\r\n\r\n";
            $n++;
            $N++;
            $sig[]=array($n,$level,0);
            $gpsdata[$n]=array(
                            "id"=>$n,
                            "lat"=>$lat,
                            "long"=>$long,
                            "sats"=>'0',
                            "hdp"=> '0.0',
                            "alt"=> $alt,
                            "geo"=> '-0.0',
                            "kmh"=> '0.0',
                            "mph"=> '0.0',
                            "track"=> '0.0',
                            "date"=>$date,
                            "time"=>$time
                            );
            $apdata[$N]=array(
                            "ssid"=>$ssid,
                            "mac"=>$mac,
                            "man"=>$man,
                            "auth"=>$authen,
                            "encry"=>$encry,
                            "sectype"=>$sectype,
                            "radio"=>$radio,
                            "chan"=>$chan,
                            "btx"=>"0",
                            "otx"=>"0",
                            "nt"=>$nt,
                            "label"=>"Unknown",
                            "sig"=>$sig
                            );

            #echo "\n\n+_+_+_+_+_+_\n".$gpsdata[$n]["lat"]."  +_\n".$gpsdata[$n]["long"]."  +_\n".$gpsdata[$n]["sats"]."  +_\n".$gpsdata[$n]["date"]."  +_\n".$gpsdata[$n]["time"]."  +_\n";
            echo "Access Point Number: ".$N."\n";
            #echo "=-=-=-=-=-=-\n".$apdata[$N]["ssid"]."  =-\n".$apdata[$N]["mac"]."  =-\n".$apdata[$N]["auth"]."  =-\n".$apdata[$N]["encry"]."  =-\n".$apdata[$N]["sectype"]."  =-\n".$apdata[$N]["radio"]."  =-\n".$apdata[$N]["chan"]."  =-\n".$apdata[$N]["btx"]."  =-\n".$apdata[$N]["otx"]."  =-\n".$apdata[$N]["nt"]."  =-\n".$apdata[$N]["label"]."  =-\n".$apdata[$N]["sig"]."\n";
            
        }
        echo "Counts: ".count($apdata)." | ".count($gpsdata)."\r\n";
        $data = array($apdata, $gpsdata);
        return $data;
    }
    
    public function WriteConvertFile($converted, $data)
    {
        $dir = $this->PATH.'import/up/convert/';
        $src = explode("/",$converted);
        $f_max = count($src);
        $file_src = explode(".",$src[$f_max-1]);
        $filename = $file_src[0].'.vs1';
        $fullfile = $dir.$filename;
        
        #echo $fullfile."\n";
        
        // define initial write and appends
        fopen($fullfile, "w");
        $fileappend = fopen($fullfile, "a");
        # Dump GPS data to VS1 File
        $h1 = "# Vistumbler VS1 - Detailed Export Version 4.0\r\n# Created By: RanInt WiFi DB Alpha \r\n# -------------------------------------------------\r\n# GpsID|Latitude|Longitude|NumOfSatalites|Date|Time\r\n# -------------------------------------------------\r\n";
        $gpsd = $h1;
        $n=1;
        foreach( $data[1] as $gps )
        {
            //GPS Convertion  if needed, check for ddmm.mmmm and leave it alone, otherwise i am guessing its DD.mmmmm and that needs to be converted to ddmm.mmmm:
            if($gps['lat'] != "0.0000000" || $gps['lat'] != "N 0.00000" ||$gps['lat'] != "N 0000.0000")
            {
                $exp = explode(".", $gps['lat']);
                
                if(strlen($exp[1]) > 4)
                {
                    $lat  = $gps['lat'];
                    $long = $gps['long'];
                }else
                {
                    $lat  = $this->convert_dd_dm($gps['lat']);
                    $long = $this->convert_dd_dm($gps['long']);
                }
                #echo $gps['lat']." - ".$lat."\r\n";
                #echo $gps['long']." - ".$long."\r\n---------------------------\r\n";
                if(substr($lat,0,1) == "-")
                {
                    $lat = "S ".str_replace("-", "", $lat);
                }else
                {
                    $lat = "N ".$lat;
                }
                if(substr($long,0,1) == "-")
                {
                    $long = "W ".str_replace("-", "", $long);
                }else
                {
                    $long = "E ".$long;
                }
                //END GPS convert
            }
            else
            {
                $lat = $gps['lat'];
                $long = $gps['long'];
            }
            
            #if ($this->debug ==1 ){echo "Lat : ".$gps['lat']." - Long : ".$gps['long']."\n";}
            $gpsd .= $n."|".$lat."|".$long."|".$gps["sats"]."|".$gps["hdp"]."|".$gps["alt"]."|".$gps["geo"]."|".$gps["kmh"]."|".$gps["mph"]."|".$gps["track"]."|".$gps["date"]."|".$gps["time"]."\r\n";
            $n++;
        }

        $ap_head = "#---------------------------------------------------------------------------------------------------------------------------------------------------------#\r\n# SSID|BSSID|MANUFACTURER|Authetication|Encryption|Security Type|Radio Type|Channel|Basic Transfer Rates|Other Transfer Rates|Network Type|High Signal|High RSSI|Label|GpsID,SIGNAL,RSSI\r\n# ---------------------------------------------------------------------------------------------------------------------------------------------------------\r\n";

        $apd = $gpsd.$ap_head;
        foreach($data[0] as $ap)
        {
            foreach($ap['sig'] as $key=>$sig)
            {
                $ap['sig'][$key] = implode(",", $sig);
            }
            $apd .= $ap["ssid"]."|".$ap["mac"]."|".$ap["man"]."|".$ap["auth"]."|".$ap["encry"]."|".$ap["sectype"]."|".$ap["radio"]."|".$ap["chan"]."|".$ap["btx"]."|".$ap["otx"]."|".$ap["nt"]."|0|0|".$ap["label"]."|".implode("\\", $ap["sig"])."\r\n";            
        }
        fwrite($fileappend, $apd);
        fclose($fileappend);
        
        return $filename;
    }
####################
    
    function parseArgs($argv)
    {
        array_shift($argv);
        $out = array();
        foreach ($argv as $arg)
        {
            if (substr($arg,0,2) == '--'){
                $eqPos = strpos($arg,'=');
                if ($eqPos === false){
                    $key = substr($arg,2);
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                } else {
                    $key = substr($arg,2,$eqPos-2);
                    $out[$key] = substr($arg,$eqPos+1);
                }
            } else if (substr($arg,0,1) == '-'){
                if (substr($arg,2,1) == '='){
                    $key = substr($arg,1,1);
                    $out[$key] = substr($arg,3);
                } else {
                    $chars = str_split(substr($arg,1));
                    foreach ($chars as $char){
                        $key = $char;
                        $out[$key] = isset($out[$key]) ? $out[$key] : true;
                    }
                }
            } else {
                $out[] = $arg;
            }
        }
        return $out;
    }
    
#END DAEMON CLASS
}
?>