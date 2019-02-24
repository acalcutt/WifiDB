<?php
/*
dbcore.inc.php, holds the WiFiDB Core functions.
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
	public $cli;
	public function __construct($config = NULL)
	{
		if($config === NULL){throw new Exception("DBCore construct value is NULL.");}
		$this->sql					  = new SQL($config);
		$this->verbose					= 0;
		$this->mesg					 = "";
		$this->switches				 = array(SWITCH_SCREEN, SWITCH_EXTRAS);
		$this->reserved_users		   = $config['reserved_users'];
		$this->supported_extentions	 = array('csv','db3','vsz','vs1','gpx','ns1');
		$this->login_check			  = 0;
		$this->alerts_message_flag	  = 0;
		$this->bypass_check			 = 0;
		$this->debug					= 1;
		$this->rebuild				  = $config['rebuild'];
		$this->log_level				= $config['log_level'];
		$this->log_interval			 = $config['log_interval'];

		$this->default_refresh		  = $config['default_refresh'];
		$this->default_timezone		 = $config['default_timezone'];
		$this->default_dst			  = $config['default_dst'];
		$this->date_format			  = "Y-m-d";
		$this->time_format			  = "H:i:s";
		$this->datetime_format		  = $this->date_format." ".$this->time_format;
		$this->timeout				  = $config['timeout'];

		$this->TOOLS_PATH			   = $config['wifidb_tools'];
		$this->pid_file_loc			 = $config['pid_file_loc'];
		$this->apache_user			  = $config['apache_user'];
		$this->apache_group			 = $config['apache_group'];

		$this->dim					  = DIRECTORY_SEPARATOR;
		$this->WebSocketURL            = $config['WebSocketURL'];
		$this->HOSTURL				  = $config['hosturl'];
		$this->root					 = $config['root'];
		$this->URL_PATH				 = $this->HOSTURL.$this->root.'/';
		$this->PATH					 = $config['wifidb_install'];
		$this->gpx_out				  = $this->PATH.$config['gpx_out'];
		$this->gpx_htmlpath			 = $this->URL_PATH.$config['gpx_out'];
		$this->daemon_out			   = $this->PATH.$config['daemon_out'];
		$this->daemon_htmlpath		  = $this->URL_PATH.$config['daemon_out'];
		$this->region_out			   = $this->PATH.$config['region_out'];
		$this->region_htmlpath			= $this->URL_PATH.$config['region_out'];
		$this->vs1_out				  = $this->PATH.$config['vs1_out'];
		$this->vs1_htmlpath			 = $this->URL_PATH.$config['vs1_out'];
		$this->kml_out				  = $this->PATH.$config['kml_out'];
		$this->kml_htmlpath			 = $this->URL_PATH.$config['kml_out'];
		$this->csv_out				  = $this->PATH.$config['csv_out'];
		$this->csv_htmlpath			 = $this->URL_PATH.$config['csv_out'];

		if (isset($_COOKIE['wifidb_theme']) && $_COOKIE['wifidb_theme'] != '') {$this->theme = $_COOKIE['wifidb_theme'];}else{$this->theme = $config['default_theme'];}
		$this->PATH_THEMES			  = $this->PATH.'themes/'.$this->theme;

		$this->open_loc				 = $config['open_loc'];
		$this->WEP_loc				  = $config['WEP_loc'];
		$this->WPA_loc				  = $config['WPA_loc'];
		$this->KML_SOURCE_URL		   = $config['KML_SOURCE_URL'];

		$this->smarty_path			  = $config['smarty_path'];

		if(empty($config['colors_setting']) or PHP_OS != "Linux")
		{
			$this->colors = array(
				"LIGHTGRAY"	=> "",
				"BLUE"		=> "",
				"GREEN"		=> "",
				"RED"		=> "",
				"YELLOW"	=> ""
			);
		}else
		{
			$this->colors = array(
				"LIGHTGRAY"	=> "\033[0;37m",
				"BLUE"		=> "\033[0;34m",
				"GREEN"		=> "\033[0;32m",
				"RED"		=> "\033[0;31m",
				"YELLOW"	=> "\033[1;33m"
			);
		}

		$this->ver_array				=   array(
			"wifidb"					=>  "v0.40 Beta",
			"codename"				  =>  "Phoenix",
			"Last_Core_Edit"			=>  "09-04-2018"
			);
		$this->ver_str				  = $this->ver_array['wifidb'];
		$this->This_is_me			   = getmypid();
		$this->sec					  = new security($this, $config);
		$this->lang					 = new languages($config['wifidb_install']);
		$this->xml					  = new xml();
		$this->languages = $this->lang;
		$this->dBmMaxSignal = $config['dBmMaxSignal'];
		$this->dBmDissociationSignal = $config['dBmDissociationSignal'];
	}

	##############################
	/**
	 * @param string $email
	 * @return int
	 */
	function checkEmail($email = "")
	{
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
		{
			return 0;
		}
		else
		{
			return 1;
		}
	}

	function GetAPhash($id)
	{
		$sql = "SELECT `ap_hash` FROM `wifi_pointers` WHERE `id` = '$id'";
		$result = $this->sql->conn->query($sql);
		$ret = $result->fetch(2);
		$hash = $ret['ap_hash'];
		return $hash;
	}

	###################################
	/**
	 * @param string $value
	 * @param int $level
	 * @return string
	 */
	public function dump($value = "", $level = 0)
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
			$value = dbcore::dump($value,-1);
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
				echo "\n".str_repeat("\t",$level+1).dbcore::dump($key,-1).' => ';
				dbcore::dump($val,$level+1);
			}
			$value= '';
		}
		echo " <b>$value</b>";
		if ($level==0) echo '</pre>';
	}

	# Gets the status of the Import/Export Daemon, windows/linux
	/**
	 * @param null $daemon_pid
	 * @return array|int
	 */
	public function getdaemonstats( $daemon_pid = NULL)
	{
		if($daemon_pid == NULL ) # Test to see if a PID file was passed, if not fail.
		{
			$ret = array('OS'=>'-','pid'=>'0','time'=>'0:00','mem'=>'0%','cmd'=>'No PID File supplied','color'=>'red', 'errc'=>-4);
			return $ret;
		}
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

					//$patterns[1] = '/  /';
					//$patterns[2] = '/ /';
					//$ps_stats = preg_replace($patterns , "|" , $start); #a second way of parsing the data.
					//$ps_Sta_exp = explode("|", $ps_stats);

					//$returns = array(  # lets now throw all this
					//	$mem,$CMD,$time,$ps_Sta_exp # into one array
					//);
					//var_dump($returns);

					$ret = array('OS'=>'Linux','pid'=>$pid_open[0],'time'=>$time,'mem'=>$mem.'%','cmd'=>$CMD,'color'=>'green','errc'=>-5);
					return $ret; # and return it
				}else
				{
					$ret = array('OS'=>'Linux','pid'=>'0','time'=>'0:00','mem'=>'0%','cmd'=>'There was no data in the PS return.','color'=>'red','errc'=>-5);
					return $ret; # There was no data in the PS return.
				}
			}else
			{
				$ret = array('OS'=>'Linux','pid'=>'0','time'=>'0:00','mem'=>'0%','cmd'=>'PID File could not be found.','color'=>'red','errc'=>-6);
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
					$ret = array('Windows'=>'Linux','pid'=>'0','time'=>'0:00','mem'=>'0%','cmd'=>'no data returned from tasklist','color'=>'red','errc'=>-3);
					return $ret; #no data returned from tasklist
				}
			}else
			{
				$ret = array('OS'=>'Windows','pid'=>'0','time'=>'0:00','mem'=>'0%','cmd'=>'PID File did not exsist','color'=>'red','errc'=>-2);
				return $ret; #PID File did not exsist
			}
		}else
		{
			return -1; #OS not supported.
		}
	}


	/**
	 * @param null $rank
	 * @return array
	 */
	public function GetRanks($rank = NULL)
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
	/**
	 * @param $size
	 * @param int $round
	 * @return string
	 */
	public static function format_size($size, $round = 2)
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
	/**
	 * @param $mypath
	 * @param $uid
	 * @param $gid
	 */
	public static function recurse_chown_chgrp($mypath, $uid, $gid)
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
					dbcore::recurse_chown_chgrp ($typepath, $uid, $gid);
				}
				chown($typepath, $uid);
				chgrp($typepath, $gid);
			}
		}
	}

	#================================#
	#   Recureivly chmod a folder	#
	#================================#
	/**
	 * @param $mypath
	 * @param $mod
	 */
	public static function recurse_chmod($mypath, $mod)
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
					dbcore::recurse_chmod($typepath, $mod);
				}
				chmod($typepath, $mod);
			}
		}
	}

	#=================================#
	#   Install Folder Warning Code   #
	#=================================#
	/**
	 * @return int|string
	 */
	public function check_install_folder()
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
	/**
	 * @param string $message
	 * @param string $type
	 * @param string $prefix
	 * @return int
	 */
	public function logd($message = '', $type = "message", $prefix = "")
	{
		if($dbcore->log_level) # Check to see if logging is turned on.
		{
			if(@strtoupper(SWITCH_SCREEN) === "CLI" && $prefix === "")
			{
				$prefix = $dbcore->This_is_me;
			}else{
				$prefix = "httpd";
			}
			if($message === "")
			{
				$dbcore->verbosed("Logd was told to write a blank string.\r\n Message has NOT been logged and this will NOT be allowed!", -1);
				return 0;
			}

			$date = date("y-m-d");
			$utime = explode(".", microtime(1));
			$time = date("H:i:s.").$utime[1];
			$datetime = $date." ".$time;
			$message = $datetime."   ->	".$message."\r\n"; #append the date and time to the log message.
			switch($dbcore->Log_Type)
			{
				case "SQL":
					$dbcore->log_sql($message, $type, $prefix, $datetime);
					break;
				case "File":
					$dbcore->log_file($message, $type, $prefix, $datetime);
					break;
				case "Both":
					$dbcore->log_sql($message, $type, $prefix, $datetime);
					$dbcore->log_file($message, $type, $prefix, $datetime);
			}


				# Done with the SQL Log, lets write to the file log now, if we are on the CLI
				if($this->cli)
				{

				}
		}
	}

	private function log_sql($message, $type, $prefix, $datetime)
	{
		$sql = "INSERT INTO `log` (`message`, `level`, `timestamp`, `prefix`) VALUES (?, ?, ?, ?)";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $message, PDO::PARAM_STR);
		$prep->bindParam(2, $type, PDO::PARAM_STR);
		$prep->bindParam(3, $datetime, PDO::PARAM_STR);
		$prep->bindParam(4, $prefix, PDO::PARAM_INT);
		$prep->execute();
		if($this->sql->checkError())
		{
			$this->verbosed("Error writing to the Log table 0_o", -1);
			throw new ErrorException("Error writing to the Log table. ".var_export($this->sql->conn->errorInfo() ,1));
			return 0;
		}else
		{
			return 1;
		}
	}

	private function log_file($message = "", $type = "", $prefix = 0, $datetime = "")
	{
		$date = date($this->date_format);
		$filename = $this->TOOLS_PATH.'log/'.$prefix.'wifidbd_'.$date.'.log'; #generate the log file name for today.
		#If it does not exist create the log file.
		if(!is_file($filename)){ fopen($filename, "w");}

		$filehandle = fopen($filename, "a"); # Append to the end of the log file.
		$write_message = fwrite($filehandle, $message); # Lets write our message.
		if(!$write_message){echo "The WiFiDB Import/Export Daemon could not write message to the file, thats not good...";} # If there was an error, lets let them know ad the console.
		fclose($filehandle); # Now we need to close the file, otherwise we might have lock errors.
	}
	#===============================#
	#   Smart (filtering for GPS)   #
	#===============================#
	/**
	 * @param string $text
	 * @return mixed
	 */
	public static function GPSFilter($text="") // Used for GPS
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


	public static function normalize_ssid($string)
	{
		$string = htmlentities($string, ENT_QUOTES, 'UTF-8');
		$string = preg_replace('~&([a-z]{1,2})(acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml);~i', '$1', $string);
		$string = html_entity_decode($string, ENT_QUOTES, 'UTF-8');
		$string = preg_replace(array('~[^0-9a-z]~i', '~[ -]+~'), ' ', $string);

		return trim($string, ' -');
	}

	public function FindHighSig($sig)
	{
		foreach($sig as $point)
		{
			var_dump($point);
		}
		die();
	}

	/**
	 * @param string $mac
	 * @return string
	 */
	public function findManuf($mac="")
	{
		if(count(explode(":", $mac)) > 1)
		{
			$mac = str_replace(":", "", $mac);
		}
		$mac = strtoupper(substr($mac, 0, 6));
		
		$result = $this->sql->conn->prepare("SELECT Manufacturer FROM `manufacturers` WHERE `BSSID` = ?");
		$result->bindParam(1, $mac, PDO::PARAM_STR);
		$result->execute();
		$this->sql->checkError(__LINE__, __FILE__);
		if($result->rowCount() > 0)
		{
			$fetch = $result->fetch(2);
			$manuf = $fetch['Manufacturer'];
		}
		else
		{
			$manuf = "Unknown Manufacturer";
		}
		return $manuf;
	}
	
	public function formatSSID($ssid)
	{
		if($ssid == '')
		{
			$new_ssid = '[Blank SSID]';
		}
		elseif(!ctype_print($ssid))
		{
			$new_ssid = '['.$ssid.']';
		}
		else
		{
			$new_ssid = $ssid;
		}
		return $new_ssid;
	}

	/**
	 * @param $lat1
	 * @param $long1
	 * @param $lat2
	 * @param $long2
	 * @return array
	 */
	public static function CalcDistance($lat1, $long1, $lat2, $long2, $return_type = "m")
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
			if($return_type === "m")
			{
				return array((($km * 0.621371192)*5280), $km*1000);#feet, meters
			}elseif($return_type === "km")
			{
				return array(($km * 0.621371192), $km);#Miles, KM
			}

	}

	/**
	 * @param $a
	 * @param $subkey
	 * @param int $asc
	 * @return array
	 */
	public static function subval_sort($a,$subkey, $asc = 0)
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



	public static function RotateSpinner($r = 0)
	{
		if($r===0){echo "|\r";}
		if($r===10){echo "/\r";}
		if($r===20){echo "-\r";}
		if($r===30){echo "\\\r";}
		if($r===40){echo "|\r";}
		if($r===50){echo "/\r";}
		if($r===60){echo "-\r";}
		if($r===70){echo "\\\r";$r=0;}
		$r++;
		return $r;
	}

	/**
	 * @param string $file
	 * @return int|string
	 */
	public static function TarFile($file = "")
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
	/**
	 * @param string $message
	 * @param int $color
	 * @return int
	 */
	public function verbosed($message = "", $color = 1)
	{
		if($this->verbose)
		{
			$datetime = date("Y-m-d H:i:s");
			if ($message != '') {
				switch ($color) {
					case -1: #Error
						$message = $this->colors['RED'] . $datetime . $this->colors['YELLOW'] . "   ->	" . $this->colors['RED'] . $message . $this->colors['LIGHTGRAY'];
						break;
					case 1: #normal message
						$message = $this->colors['YELLOW'] . $datetime . $this->colors['LIGHTGRAY'] . "   ->	" . $this->colors['LIGHTGRAY'] . $message . $this->colors['LIGHTGRAY'];
						break;
					case 2: #good / header message
						$message = $this->colors['YELLOW'] . $datetime . $this->colors['LIGHTGRAY'] . "   ->	" . $this->colors['GREEN'] . $message . $this->colors['LIGHTGRAY'];
						break;
					case 3: #different good/header message
						$message = $this->colors['YELLOW'] . $datetime . $this->colors['LIGHTGRAY'] . "   ->	" . $this->colors['BLUE'] . $message . $this->colors['LIGHTGRAY'];
						break;
					default: #normal message
						$message = $this->colors['YELLOW'] . $datetime . $this->colors['LIGHTGRAY'] . "   ->	" . $this->colors['YELLOW'] . $message . $this->colors['LIGHTGRAY'];
						break;
				}
				echo $message . "\r\n";
				return 1;
			} else {
				echo "WiFiDB Verbose was told to write a blank string :/\r\n";
				return 0;
			}
		}
	}
}