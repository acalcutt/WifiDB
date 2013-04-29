<?php
/*
DBcore.inc.php, holds the WiFiDB Core functions.
Copyright (C) 2012 Phil Ferland

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

class dbcore
{
    public  $cli;
    function __construct($config = NULL)
    {
        if($config === NULL){throw new Exception("DBCore construct value is NULL.");}
        $this->sql                      = new SQL($config);
        
        $this->mesg                     = "";
        $this->switches                 = array(SWITCH_SCREEN, SWITCH_EXTRAS);
        $this->reserved_users           = $config['reserved_users'];
        $this->supported_extentions     = array('csv','db3','vsz','vs1','gpx','ns1');
        $this->login_check              = 0;
        $this->alerts_message_flag      = 0;
        $this->bypass_check             = 0;
        $this->debug                    = 1;
        $this->rebuild                  = $config['rebuild'];
        $this->log_level                = $config['log_level'];
        $this->log_interval             = $config['log_interval'];
        
        $this->default_refresh          = $config['default_refresh'];
        $this->default_timezone         = $config['default_timezone'];
        $this->default_dst              = $config['default_dst'];
        $this->date_format              = "Y-m-d";
        $this->time_format              = "H:i:s";
        $this->datetime_format          = $this->date_format." ".$this->time_format;
        $this->timeout                  = $config['timeout'];
        
        $this->TOOLS_PATH               = $config['wifidb_tools'];
        $this->pid_file_loc             = $config['pid_file_loc'];
        $this->apache_user              = $config['apache_user'];
        $this->apache_group             = $config['apache_group'];
        
        $this->dim                      = DIRECTORY_SEPARATOR;
        $this->HOSTURL                  = $config['hosturl'];
        $this->root                     = $config['root'];
        $this->URL_PATH                 = $this->HOSTURL.$this->root.'/';
        $this->PATH                     = $config['wifidb_install'];
        $this->gpx_out                  = $this->PATH.$config['gpx_out'];
        $this->daemon_out               = $this->PATH.$config['daemon_out'];
        $this->vs1_out                  = $this->PATH.$config['vs1_out'];
        $this->kml_out                  = $this->PATH.$config['kml_out'];
        $this->csv_out                  = $this->PATH.$config['csv_out'];
        
        $this->theme                    = (@$_REQUEST['wifidb_theme']!='' ? @$_REQUEST['wifidb_theme'] : $config['default_theme']);
        $this->PATH_THEMES              = $this->PATH.'themes/'.$this->theme;
        
        $this->open_loc                 = $config['open_loc'];
        $this->WEP_loc                  = $config['WEP_loc'];
        $this->WPA_loc                  = $config['WPA_loc'];
        $this->KML_SOURCE_URL           = $config['KML_SOURCE_URL'];
        
        $this->smarty_path              = $config['smarty_path'];
        include_once $config['wifidb_install'].'lib/manufactures.inc.php' ;
        $this->manufactures             = @$GLOBALS['manufactures'];
        unset($GLOBALS['manufactures']);
        
        $this->wifidb_email_updates     = 0;
        $this->email_validation         = 1;
        $this->WDBadmin                 = $config['admin_email'];
        $this->smtp                     = $config['wifidb_smtp'];
        
        $this->ver_array                =   array(
            "wifidb"                    =>  " *Alpha* 0.30 Build 1 *Pre-Release* ",
            "codename"                  =>  "Peabody",
            "Last_Core_Edit"            =>  "2013-Apr-01"
            );
        $this->ver_str                  = $this->ver_array['wifidb'];
        $this->This_is_me               = getmypid();
        $this->sec                      = new security($this, $config);
        $this->lang                     = new languages($config['wifidb_install']);
        $this->xml                      = new xml();
        $this->wdbmail                  = new wdbmail($this);
        $this->sec->LoginCheck();
    }

    ##############################
    function checkEmail($email)
    {
        // First, we check that there's one @ symbol, and that the lengths are right
        if (!ereg("^[^@]{1,64}@[^@]{1,255}$", $email))
        {
            // Email invalid because wrong number of characters in one section, or wrong number of @ symbols.
            return 0;
        }
        // Split it into sections to make life easier
        $email_array = explode("@", $email);
        $local_array = explode(".", $email_array[0]);
        for ($i = 0; $i < sizeof($local_array); $i++)
        {
            if (!ereg("^(([A-Za-z0-9!#$%&'*+/=?^_`{|}~-][A-Za-z0-9!#$%&'*+/=?^_`{|}~\.-]{0,63})|(\"[^(\\|\")]{0,62}\"))$", $local_array[$i]))
            {
                return 0;
            }
        }
        if (!ereg("^\[?[0-9\.]+\]?$", $email_array[1]))
        {
            // Check if domain is IP. If not, it should be valid domain name
            $domain_array = explode(".", $email_array[1]);
            if (sizeof($domain_array) < 2)
            {
                return 0; // Not enough parts to domain
            }
            for ($i = 0; $i < sizeof($domain_array); $i++)
            {
                if (!ereg("^(([A-Za-z0-9][A-Za-z0-9-]{0,61}[A-Za-z0-9])|([A-Za-z0-9]+))$", $domain_array[$i]))
                {
                    return 0;
                }
            }
        }
        return 1;
    }
    
    ###################################
    function dump($value="" , $level=0)
    {
        if ($level==-1)
        {
            $trans[' ']='&there4;';
            $trans["\t"]='&rArr;';
            $trans["\n"]='&para;;';
            $trans["\r"]='&lArr;';
            $trans["\0"]='&oplus;';
            return strtr(htmlspecialchars($value),$trans);
        }
        if ($level==0) echo '<pre>';
        $type= gettype($value);
        echo $type;
        if ($type=='string')
        {
            echo '('.strlen($value).')';
            $value= $this->dump($value,-1);
        }
        elseif ($type=='boolean') $value= ($value?'true':'false');
        elseif ($type=='object')
        {
            $props= get_class_vars(get_class($value));
            echo '('.count($props).') <u>'.get_class($value).'</u>';
            foreach($props as $key=>$val)
            {
                echo "\n".str_repeat("\t",$level+1).$key.' => ';
                dump($value->$key,$level+1);
            }
            $value= '';
        }
        elseif ($type=='array')
        {
            echo '('.count($value).')';
            foreach($value as $key=>$val)
            {
                echo "\n".str_repeat("\t",$level+1).$this->dump($key,-1).' => ';
                $this->dump($val,$level+1);
            }
            $value= '';
        }
        echo " <b>$value</b>";
        if ($level==0) echo '</pre>';
    }
    
    # Gets the status of the Import/Export Daemon, windows/linux
    function getdaemonstats( $daemon_pid = NULL)
    {
        if($daemon_pid == NULL ) {$ret = array('OS'=>'-','pid'=>'0','time'=>'0:00','mem'=>'0 bytes','cmd'=>'No PID File supplied','color'=>'red', 'errc'=>-4);return ;} # Test to see if a PID file was passed, if not fail.

        $WFDBD_PID = $this->pid_file_loc.$daemon_pid; // /var/run/dbstatsd.pid | C:\wifidb\tools\daemon\run\imp_expd.pid
        $os = PHP_OS; #find out what OS we are running under.
        if ( $os[0] == 'L') #Linux :)
        {
            $output = array();
            if(file_exists($WFDBD_PID)) #Check and see if the PID File exists
            {
                $pid_open = file($WFDBD_PID); #open it and get the PID of the daemon
        #	echo $pid_open[0]."<br>";
                exec('ps vp '.$pid_open[0] , $output, $sta); #execute PS for the PID given.
                if(isset($output[1])) #if there was data returned from PS lets parse it.
                {
                    $start = trim($output[1], " ");
                    preg_match_all("/(\d+?)(\.)(\d+?)/", $start, $mat); #we try and parse for the memory useage.
                    $mem = $mat[0][0];

                    preg_match_all("/(php.*)/", $start, $mat); #parse for the CMD path of the daemon
                    $CMD = $mat[0][0];

                    preg_match_all("/(\d+)(\:)(\d+)/", $start, $mat); # get the uptime of the daemon.
                    $time = $mat[0][0];

                    $patterns[1] = '/  /';
                    $patterns[2] = '/ /';
                    $ps_stats = preg_replace($patterns , "|" , $start); #a second way of parsing the data.
                    $ps_Sta_exp = explode("|", $ps_stats);

                    $returns = array(  # lets now throw all this
                        $mem,$CMD,$time,$ps_Sta_exp # into one array
                    );
                    return $returns; # and return it
                }else
                {
                    $ret = array('OS'=>'Linux','pid'=>'0','time'=>'0:00','mem'=>'0 bytes','cmd'=>'There was no data in the PS return.','color'=>'red','errc'=>-5);
                    return $ret; # There was no data in the PS return.
                }
            }else
            {
                $ret = array('OS'=>'Linux','pid'=>'0','time'=>'0:00','mem'=>'0 bytes','cmd'=>'PID File could not be found.','color'=>'red','errc'=>-6);
                return $ret; # PID File could not be found.
            }
        }elseif( $os[0] == 'W')
        {
            $output = array();
            if(file_exists($WFDBD_PID)) #Check to see if the file exists.
            {
                $pid_open = file($WFDBD_PID); #Open it and get the PID of the daemon.
                exec('tasklist /V /FI "PID eq '.$pid_open[0].'" /FO CSV' , $output, $sta); #Execute Tasklist a sysinternals app
                if(isset($output[2])) #if there was data returned,
                {
                    $ps_stats = explode("," , $output[2]); #we can parse it for the data.
                    return $ps_stats;
                }else
                {
                    $ret = array('Windows'=>'Linux','pid'=>'0','time'=>'0:00','mem'=>'0 bytes','cmd'=>'no data returned from tasklist','color'=>'red','errc'=>-3);
                    return $ret; #no data returned from tasklist
                }
            }else
            {
                $ret = array('OS'=>'Windows','pid'=>'0','time'=>'0:00','mem'=>'0 bytes','cmd'=>'PID File did not exsist','color'=>'red','errc'=>-2);
                return $ret; #PID File did not exsist
            }
        }else
        {
            $ret = array('OS'=>'Unknow','pid'=>'0','time'=>'0:00','mem'=>'0 bytes','cmd'=>'OS not supported.','color'=>'red','errc'=>-1);
            return -1; #OS not supported.
        }
    }

    
    function GetRanks($rank = NULL)
    {
        $ranks = @file($this->PATH."/themes/".$this->theme."/ranks.txt");
        if($rank === NULL)
        {
            return $ranks;
        }else
        {
            return $ranks[$rank];
        }
        
    }
    
    # Formats a bit size to Bytes/kB/MB/GB/TB/PB/EB/ZB/YB
    function format_size($size, $round = 2)
    {
        //Size must be bytes!
        $sizes = array('B', 'kB', 'MB', 'GB', 'TB', 'PB', 'EB', 'ZB', 'YB');

        for ($i=0; $size > 1024 && $i < (count($sizes)-1); $i++)
        {
            $size = $size/1024;
        }
        return round($size,$round).$sizes[$i];
    }

    #=========================================#
    #   Recureivly chown and chgrp a folder   #
    #=========================================#
    private function recurse_chown_chgrp($mypath, $uid, $gid)
    {
        $d = opendir ($mypath) ;
        while(($file = readdir($d)) !== false)
        {
            if ($file != "." && $file != "..")
            {
                $typepath = $mypath . "/" . $file ;
                //print $typepath. " : " . filetype ($typepath). "<BR>" ;
                if (filetype ($typepath) == 'dir')
                {
                    $this->recurse_chown_chgrp ($typepath, $uid, $gid);
                }
                chown($typepath, $uid);
                chgrp($typepath, $gid);
            }
        }
    }

    #================================#
    #   Recureivly chmod a folder    #
    #================================#
    private function recurse_chmod($mypath, $mod)
    {
        $d = opendir ($mypath) ;
        while(($file = readdir($d)) !== false)
        {
            if ($file != "." && $file != "..")
            {
                $typepath = $mypath . "/" . $file ;
                //print $typepath. " : " . filetype ($typepath). "<BR>" ;
                if (filetype ($typepath) == 'dir')
                {
                    $this->recurse_chmod($typepath, $mod);
                }
                chmod($typepath, $mod);
            }
        }
    }

    #=================================#
    #   Install Folder Warning Code   #
    #=================================#
    function check_install_folder()
    {
        $install_folder_remove = "";
        if(@$this->bypass_check){return 0;}
        $path = getcwd();
        $path_exp = explode($this->dim , $path);
        foreach($path_exp as $key=>$val)
        {
            if($val == $this->root){ $path_key = $key;}
        }
        $full_path = '';
        $I = 0;
        if(isset($path_key))
        {
            while($I!=($path_key+1))
            {
                $full_path = $full_path.$path_exp[$I].$this->dim ;
                $I++;
            }
            $full_path = $full_path.'install';
            if(is_dir($full_path)){$install_folder_remove = '<p align="center"><font color="red" size="6">The install Folder is still there, remove it!</font></p>';}
        }
        return $install_folder_remove;
    }

    #=====================================#
    #   When Enabled, logs a file a day.  #
    #=====================================#
    function logd($message = '', $type = "message", $prefix = "")
    {
        if($this->log_level) # Check to see if logging is turned on.
        {
            if($GLOBALS['switches']['screen'] === "CLI" || $prefix === "")
            {
                $prefix = $this->This_is_me;
            }
            if($message == '')
            {
                echo "Logd was told to write a blank string.\r\n Message has NOT been logged and this will NOT be allowed!\n";
                return 0;
            }
            
            $date = date("y-m-d");
            $utime = explode(".", microtime(1));
            $time = date("H:i:s.").$utime[1];
            $datetime = $date." ".$time;
            $message = $datetime."   ->    ".$message."\r\n"; #append the date and time to the log message.
            
            $sql = "INSERT INTO `wifi`.`log` (`id`, `message`, `level`, `timestamp`, `prefix`) VALUES ('', ?, ?, ?, ?)";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(1, $message, PDO::PARAM_STR);
            $prep->bindParam(2, $type, PDO::PARAM_STR);
            $prep->bindParam(3, $datetime, PDO::PARAM_STR);
            $prep->bindParam(4, $prefix, PDO::PARAM_INT);
            $prep->execute();
            if($this->sql->checkError())
            {
                $this->verbosed("Error writing to the Log table 0_o", -1);
            }
            # Done with the SQL Log, lets write to the file log now, if we are on the CLI
            if($this->cli)
            {
                $filename = $this->TOOLS_PATH.'log/'.$prefix.'wifidb_'.$date.'.log'; #generate the log file name for today.
                #If it does not exist create the log file.
                if(!is_file($filename)){ fopen($filename, "w");}

                $filehandle = fopen($filename, "a"); # Append to the end of the log file.
                $write_message = fwrite($filehandle, $message); # Lets write our message.
                if(!$write_message){echo "The WiFiDB Import/Export Daemon could not write message to the file, thats not good...";} # If there was an error, lets let them know ad the console.
                fclose($filehandle); # Now we need to close the file, otherwise we might have lock errors.
            }
        }
    }


    #===============================#
    #   Smart (filtering for GPS)   #
    #===============================#
    function GPSFilter($text="") // Used for GPS
    {
        $pattern = '/"((.)*?)"/i';
        $strip = array(
                                        0=>" ",
                                        1=>":",
                                        2=>"-",
                                        3=>".",
                                        4=>"N",
                                        5=>"E",
                                        6=>"W",
                                        7=>"S"
                                  );
        $text = preg_replace($pattern,"&#147;\\1&#148;",stripslashes($text));
        $text = str_replace($strip,"",$text);
        return $text;
    }
    
    #===========================================================================#
    #   make ssid (makes a DB safe, File safe and Unsan versions of an SSID)    #
    #===========================================================================#
    function make_ssid($ssid_in = '')
    {
        $ssid_in = preg_replace('/[\x00-\x1F\x7F]/', '', $ssid_in); #remove any hidden chars
        if($ssid_in == "") # check to see if the ssid is blank,
        {
            $ssid_out = "UNNAMED";  #if so lets set it to UNNAMED
            $A = array($ssid_out, $ssid_out, $ssid_out); # Assign it to everything!!!
            return $A; # and lets return it
        }

        # Make File Safe SSID
        $file_safe_ssid = $this->smart_quotes($ssid_in);
        # Make HTML safe SSID
        $ssid_html = htmlentities($ssid_in, ENT_QUOTES);
        # Make SQL Table Name safe SSID from HTML safe SSID
        $ssid_sized = str_split($ssid_html,25); //split SSID in two on is 25 char.
        $ssid_table_safe = $ssid_sized[0]; //Use the 25 char word for the APs table name, this is due to a limitation in MySQL table name lengths,
        # Return
        $A = array($ssid_html, $ssid_table_safe, $file_safe_ssid);
        return $A;
        #---------#
    }

    #===============================#
    #   Convert GeoCord DM to DD    #
    #===============================#
    function &convert_dm_dd($geocord_in = "")
    {
        $geocord_in_exp = explode(".", $geocord_in);
        if(strlen($geocord_in_exp[1]) > 4){return $geocord_in;}
        
        $start = microtime(true);
        //	GPS Convertion :
        $neg=FALSE;
        $geocord_exp = explode(".", $geocord_in);//replace any Letter Headings with Numeric Headings
        if($geocord_exp[0][0] === "S" or $geocord_exp[0][0] === "W"){$neg = TRUE;}
        $patterns[0] = '/N /';
        $patterns[1] = '/E /';
        $patterns[2] = '/S /';
        $patterns[3] = '/W /';
        $replacements = "";
        $geocord_in = preg_replace($patterns, $replacements, $geocord_in);
        $geocord_exp = explode(".", $geocord_in);
        if($geocord_exp[0][0] === "-"){$geocord_exp[0] = 0 - $geocord_exp[0];$neg = TRUE;}

        if(!@$geocord_exp[1])
        {
            var_dump($geocord_in);
        }
        // 428.7753 ---- 428 - 7753
        $geocord_dec = "0.".$geocord_exp[1];
        // 428.7753 ---- 428 - 0.7753
        $len = strlen($geocord_exp[0]);
        #		echo $len.'<BR>';
        $geocord_min = substr($geocord_exp[0],-2,3);
        #		echo $geocord_min.'<BR>';
        // 428.7753 ---- 4 - 28 - 0.7753
        $geocord_min = $geocord_min+$geocord_dec;
        // 428.7753 ---- 4 - 28.7753
        $geocord_div = $geocord_min/60;
        // 428.7753 ---- 4 - (28.7753)/60 = 0.4795883
        if($len == 3)
        {
            $geocord_deg = substr($geocord_exp[0], 0,1);
        #			echo $geocord_deg.'<br>';
        }elseif($len == 4)
        {
            $geocord_deg = substr($geocord_exp[0], 0,2);
        #			echo $geocord_deg.'<br>';
        }elseif($len == 5)
        {
            $geocord_deg = substr($geocord_exp[0], 0,3);
        #			echo $geocord_deg.'<br>';
        }elseif($len <= 2)
        {
            $geocord_deg = 0;
        #			echo $geocord_deg.'<br>';
        }
        if(!isset($geocord_deg))
        {
            echo $geocord_in."\r\n";
            return -1;
        }
        $geocord_out = $geocord_deg + $geocord_div;
        // 428.7753 ---- 4.4795883
        if($neg === TRUE){$geocord_out = "-".$geocord_out;}
        $end = microtime(true);

        $geocord_out = substr($geocord_out, 0,10);
        #var_dump($geocord_out);
        return $geocord_out;
    }


    #===============================#
    #   Convert GeoCord DecDeg to DegMin    #
    #===============================#
    function &convert_dd_dm($geocord_in="")
    {
        $neg=FALSE;
        $geocord_exp = explode(".", $geocord_in);
        if($geocord_exp[0][0] == "S" or $geocord_exp[0][0] == "W"){$neg = TRUE;}
        $pattern[0] = '/N /';
        $pattern[1] = '/E /';
        $pattern[2] = '/S /';
        $pattern[3] = '/W /';
        $replacements = "";
        $geocord_exp[0] = preg_replace($pattern, $replacements, $geocord_exp[0]);
        if($geocord_exp[0][0] === "-"){$geocord_exp[0] = 0 - $geocord_exp[0];$neg = TRUE;}
        if(strlen($geocord_exp[0]) >= 4)
        {
            if($neg)
            {
                $out = "-".$geocord_exp[0].'.'.$geocord_exp[1];
                return $out;
            }else
            {
                $out = $geocord_exp[0].'.'.$geocord_exp[1];
                return $out;
            }
        }
        // 4.146255 ---- 4 - 146255
        $geocord_dec = "0.".$geocord_exp[1];
        // 4.146255 ---- 4 - 0.146255
        $geocord_mult = $geocord_dec*60;
        // 4.146255 ---- 4 - (0.146255)*60 = 8.7753
        $mult = explode(".",$geocord_mult);

        if( strlen($mult[0]) < 2 )
        {
            $geocord_mult = "0".$geocord_mult;
        }
        // 4.146255 ---- 4 - 08.7753
        $geocord_out = $geocord_exp[0].$geocord_mult;
        // 4.146255 ---- 408.7753
        $geocord_o = explode(".", $geocord_out);
        if( strlen($geocord_o[1]) > 4 )
        {
            $geocord_o[1] = substr($geocord_o[1], 0 , 4);
            $geocord_out = implode('.', $geocord_o);
        }
        if($neg === TRUE){$geocord_out = "-".$geocord_out;}
        return $geocord_out;
    }

    
    
    function &manufactures($mac="")
    {
        if(count(explode(":", $mac)) > 1)
        {
            $mac = str_replace(":", "", $mac);
        }
        $man_mac = str_split($mac,6);
        if(isset($this->manufactures[$man_mac[0]]))
        {
            $manuf = $this->manufactures[$man_mac[0]];
        }
        else
        {
            $manuf = "Unknown Manufacture";
        }
        return $manuf;
    }
    
    function CalcDistance($lat1, $long1, $lat2, $long2)
    {
            $pi80 = M_PI / 180;
            $lat1 *= $pi80;
            $long1 *= $pi80;
            $lat2 *= $pi80;
            $long2 *= $pi80;

            $r = 6372.797; // mean radius of Earth in km
            $dlat = $lat2 - $lat1;
            $dlong = $long2 - $long1;
            $a = sin($dlat / 2) * sin($dlat / 2) + cos($lat1) * cos($lat2) * sin($dlong / 2) * sin($dlong / 2);
            $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
            $km = $r * $c;
            return array(($km * 0.621371192), $km);
    }
    
    function subval_sort($a,$subkey, $asc = 0)
    {
        foreach($a as $k=>$v)
        {
            $b[$k] = strtolower($v[$subkey]);
        }
        
        if($asc)
        {
            asort($b , 6); //SORT_NATURAL (6)
        }else
        {
            arsort($b , 6); //SORT_NATURAL (6)
        }
        
        foreach($b as $key=>$val)
        {
            $c[] = $a[$key];
        }
        $c;
        return $c;
    }
    
    public function TarFile($file = "")
    {
        if($file == "")
        {
            return 0;
        }
        $exp_file = explode(".", $file);
        $filename = $exp_file[0];
        $tared_file = $filename.".tar";
        var_dump("tar -zcvf $tared_file $file");
        $tared = `tar -zcvf $tared_file $file`;
        var_dump($tared);
        die();
        return $tared_file;
    }
    
####################
    /*
       verbosed (writes a message to the screen)
       $message = Message to be displayed
       $colors:
           -1  -   Red
           1   -   Light Gray (default)
           2   -   Green
           3   -   Blue
           4   -   Yellow
    */
    public function verbosed($message = "", $color = 1)
    {
        $datetime = date("Y-m-d H:i:s");
        if($message != '')
        {
            switch($color)
            {
                case -1:
                    $message = $this->colors['RED'].$datetime.$this->colors['YELLOW']."   ->    ".$this->colors['RED'].$message.$this->colors['LIGHTGRAY'];
                    break;
                case 1:
                    $message = $this->colors['YELLOW'].$datetime.$this->colors['LIGHTGRAY']."   ->    ".$this->colors['LIGHTGRAY'].$message.$this->colors['LIGHTGRAY'];
                    break;
                case 2:
                    $message = $this->colors['YELLOW'].$datetime.$this->colors['LIGHTGRAY']."   ->    ".$this->colors['GREEN'].$message.$this->colors['LIGHTGRAY'];
                    break;
                case 3:
                    $message = $this->colors['YELLOW'].$datetime.$this->colors['LIGHTGRAY']."   ->    ".$this->colors['BLUE'].$message.$this->colors['LIGHTGRAY'];
                    break;
                default:
                    $message = $this->colors['YELLOW'].$datetime.$this->colors['LIGHTGRAY']."   ->    ".$this->colors['YELLOW'].$message.$this->colors['LIGHTGRAY'];
                    break;
            }
            echo $message."\r\n";
            return 1;
        }else
        {
            echo "WiFiDB Verbose was told to write a blank string :/\r\n";
            return 0;
        }
    }
}
?>