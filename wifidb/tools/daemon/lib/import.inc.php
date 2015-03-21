<?PHP
/*
import.inc.php, holds the WiFiDB Importing functions.
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

class import extends dbcore
{
	function __construct($config, $convert_obj = NULL)
	{
		if($convert_obj === NULL)
		{die("Convert Object is null...");}
		parent::__construct($config);
		$this->convert = $convert_obj;
		$this->log_level	= $config['log_level'];
		$this->log_interval = $config['log_interval'];
		$this->verbose	  = $config['verbose'];
		$this->dBmMaxSignal	  = $config['dBmMaxSignal'];
		$this->dBmDissociationSignal	  = $config['dBmDissociationSignal'];
		$this->rssi_signals_flag = 0;
	}


	/**
	 * @param string $signals
	 * @return mixed
	 */
	private function FindHighestSig($signals = "")
	{
		$signals_exp = explode("-", $signals);
		$signals_all = array();
		foreach($signals_exp as $signal)
		{
			$sig_exp = explode(",", $signal);
			$signals_all[] = $sig_exp[1];
		}
		rsort($signals_all);
		return $signals_all[0];
	}

	/**
	 * @param string $mac
	 * @return bool
	 */
	private function validateMacAddress($mac = "")
	{
		return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $mac) == 1);
	}


####################
	/**
	 * @param string $source
	 * @param string $user
	 * @return array
	 * @throws ErrorException
	 */
	public function import_vs1($source="" , $user="Unknown")
	{
		$r = 0;
		$increment_ids = 0;
		$apdata = array();
		$gdata = array();
		# We need to check and see if the file location was passed, if not fail gracefully.
		if ($source == NULL)
		{
			$this->logd("The file that needs to be imported was not included in the import function.", "Error");
			$this->verbosed("The file that needs to be imported was not included in the import function", -1);
			throw new ErrorException;
		}
		# Open the file and dump its contents into an array. probably should re think this part...
		$File_return	 = explode("\r\n", utf8_decode(file_get_contents($source)));
		# get the MD5 hash for the file data.
		$hash = hash_file('md5', $source);

		# Now lets loop through the file and see what we have.
		$this->verbosed("Compiling data from file to array:", 3);
		$sql = "SELECT `id` FROM `wifi`.`files_tmp` WHERE `hash`= ? LIMIT 1";
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $hash, PDO::PARAM_STR);
		$prep->execute();
		if($this->sql->checkError())
		{
			$this->verbosed("Failed to Select The current imports ID from the temp table.".var_export($this->sql->conn->errorInfo(),1), -1);
			$this->logd("Failed to Select The current imports ID from the temp table.".var_export($this->sql->conn->errorInfo(),1), "Error");
			throw new ErrorException("Failed to Select The current imports ID from the temp table.".var_export($this->sql->conn->errorInfo(),1));
		}

		$file_tmp_array = $prep->fetch(2);
		$file_tmp_id = $file_tmp_array['id'];
		foreach($File_return as $key => $file_line)
		{
			$encoding = mb_detect_encoding($file_line);
			$file_line_alt = @iconv($encoding, 'UTF-8//TRANSLIT', $file_line);
			if($key == 0)
			{
				$file_line_alt = str_replace("?","",$file_line_alt);
			}
			$first_char = trim(substr($file_line_alt,0,1));
			if($first_char == "#"){continue;}
			if($file_line_alt == ""){continue;}

			$file_line_exp = explode("|",$file_line_alt);
			$file_line_exp_count = count($file_line_exp);
			switch($file_line_exp_count)
			{
				case 6:
					#This is from an older version of the VS1 GPS data, sanitize and order it into an array.
					$gps_line = $file_line_exp;
					if($gps_line[1] == "" || $gps_line[2] == ""){continue;}
					if($gps_line[0] == 0){$increment_ids = 1;}
					if($increment_ids){$gps_line[0]++;}
					$gdata[$gps_line[0]] = array(
								'import_id' => 0,
								'id'	=>  (int) $gps_line[0],
								'lat'	=>  $this->convert->all2dm($gps_line[1]),
								'long'	=>  $this->convert->all2dm($gps_line[2]),
								'sats'	=>  (int) $gps_line[3],
								'hdp'   =>  '0',
								'alt'   =>  '0',
								'geo'   =>  '0',
								'kmh'   =>  '0',
								'mph'   =>  '0',
								'track' =>  '0',
								'date'	=>  $gps_line[4],
								'time'	=>  $gps_line[5]
					);
					break;
				case 12:
					#This is the current version of the VS1 export, sanitize and order it into an array.
					$gps_line = $file_line_exp;
					if($gps_line[1] == "" || $gps_line[2] == ""){continue;}
					if($gps_line[0] == 0){$increment_ids = 1;}
					if($increment_ids){$gps_line[0]++;}
					$gdata[$gps_line[0]] = array(
								'import_id' => 0,
								'id'	=>  (int) $gps_line[0],
								'lat'	=>  $this->convert->all2dm($gps_line[1]),
								'long'	=>  $this->convert->all2dm($gps_line[2]),
								'sats'	=>  (int) $gps_line[3],
								'hdp'	=>  (float) $gps_line[4],
								'alt'	=>  (float) $gps_line[5],
								'geo'	=>  (float) $gps_line[6],
								'kmh'	=>  (float) $gps_line[7],
								'mph'	=>  (float) $gps_line[8],
								'track'	=>  (float) $gps_line[9],
								'date'	=>  $gps_line[10],
								'time'	=>  $gps_line[11]
							);
					break;
				case 13:
					#This is to generate a sanitized and sane array for each AP from the old VS1 format.
					$ap_line = $file_line_exp;
					if(!$this->validateMacAddress($ap_line[1]))
					{
						$this->verbosed("MAC Address for the AP SSID of {$ap_line[0]} was not valid, dropping AP.", -1);
						break;
					}

					$highestSignal = $this->FindHighestSig($ap_line[12]);
					$highestRSSI = $this->convert->Sig2dBm($highestSignal);
					$apdata[] = array(
								'ap_hash'   => "",
								'ssid'	  =>  $ap_line[0],
								'mac'	   =>  $ap_line[1],
								'auth'	  =>  $ap_line[3],
								'encry'	 =>  $ap_line[4],
								'sectype'   =>  (int) $ap_line[5],
								'radio'	 =>  $ap_line[6],
								'manuf'	 =>  $this->findManuf($ap_line[1]),
								'chan'	  =>  (int) $ap_line[7],
								'btx'	   =>  $ap_line[8],
								'otx'	   =>  $ap_line[9],
								'nt'		=>  $ap_line[10],
								'HighSig'   =>  $highestSignal,
								'HighRSSI'  =>  $highestRSSI,
								'label'	 =>  $ap_line[11],
								'signals'   =>  $ap_line[12]
							);
					$this->rssi_signals_flag = 0;
					break;
				case 15:
					#This is to generate a sanitized and sane array for each AP from the new VS1 format.
					$ap_line = $file_line_exp;
					if(!$this->validateMacAddress($ap_line[1]))
					{
						$this->verbosed("MAC Address for the AP SSID of `{$ap_line[0]}` was not valid, dropping AP.");
						break;
					}
					$apdata[] = array(
								'ap_hash'   => "",
								'ssid'	  =>  $ap_line[0],
								'mac'	   =>  $ap_line[1],
								'manuf'	 =>  $ap_line[2],
								'auth'	  =>  $ap_line[3],
								'encry'	 =>  $ap_line[4],
								'sectype'   =>  (int) $ap_line[5],
								'radio'	 =>  $ap_line[6],
								'manuf'	 =>  $this->findManuf($ap_line[1]),
								'chan'	  =>  (int) $ap_line[7],
								'btx'	   =>  $ap_line[8],
								'otx'	   =>  $ap_line[9],
								'nt'		=>  $ap_line[10],
								'HighSig'   =>  $ap_line[11],
								'HighRSSI'  =>  $ap_line[12],
								'label'	 =>  $ap_line[13],
								'signals'   =>  $ap_line[14]
							);
					$this->rssi_signals_flag = 1;
					break;

				default:
					echo "--------------------------------\r\n";
					$this->logd("Error parsing File.\r\n".var_export($file_line_alt, 1), "Error");
					$this->verbosed($file_line_exp_count."\r\nummm.... wrong number of columns... I'm going to ignore this line:/\r\n", -1);
					break;
			}
			//$r = $this->RotateSpinner($r);
		}
		if(count($apdata) === 0)
		{
			$this->verbosed("File did not have an valid AP data, dropping file. $source from user: $user.", -1);
			$this->logd("File did not have an valid AP data, dropping file. $source from user: $user.", "Warning");
			return -1;
		}
		if(count($gdata) === 0)
		{
			$this->verbosed("File did not have an valid GPS data, dropping file. $source from user: $user.", -1);
			$this->logd("File did not have an valid GPS data, dropping file. $source from user: $user.", "Warning");
			return -1;
		}

		$vs1data = array('gpsdata'=>$gdata, 'apdata'=>$apdata);
		$ap_count = count($vs1data['apdata']);
		$gps_count = count($vs1data['gpsdata']);

		$this->verbosed("Importing GPS data [$gps_count]", 2);
		foreach($vs1data['gpsdata'] as $key=>$gps)
		{
			$calc = "GPS: ".($key+1)." / ".$gps_count;
			$sql = "UPDATE `wifi`.`files_tmp` SET `tot` = ?, `ap` = ? WHERE `id` = ?";
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $aps['ssid'], PDO::PARAM_STR);
			$prep->bindParam(3, $file_tmp_id, PDO::PARAM_INT);
			$prep->execute();
			if($this->sql->checkError() !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				$this->logd("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}

			$sql = "INSERT INTO `wifi`.`wifi_gps` ( `id`, `lat`, `long`, `sats`, `hdp`, `alt`, `geo`, `kmh`, `mph`, `track`, `date`, `time`)
					VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
			$prep = $this->sql->conn->prepare($sql);

			$prep->bindParam(1,$gps['lat'], PDO::PARAM_STR);
			$prep->bindParam(2,$gps['long'], PDO::PARAM_STR);
			$prep->bindParam(3,$gps['sats'],PDO::PARAM_INT);
			$prep->bindParam(4,$gps['hdp'],PDO::PARAM_STR);
			$prep->bindParam(5,$gps['alt'],PDO::PARAM_STR);
			$prep->bindParam(6,$gps['geo'],PDO::PARAM_STR);
			$prep->bindParam(7,$gps['kmh'],PDO::PARAM_STR);
			$prep->bindParam(8,$gps['mph'],PDO::PARAM_STR);
			$prep->bindParam(9,$gps['track'],PDO::PARAM_STR);
			$prep->bindParam(10,$gps['date'],PDO::PARAM_STR);
			$prep->bindParam(11,$gps['time'],PDO::PARAM_STR);
			$prep->execute();
			if($this->sql->checkError())
			{
				$this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
				$this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
			}
			$vs1data['gpsdata'][$key]['import_id'] = $this->sql->conn->lastInsertId();
			//$r = $this->RotateSpinner($r);
		}

		$this->verbosed("Importing AP Data [$ap_count]:", 2);
		$imported_aps = array();

		foreach($vs1data['apdata'] as $key=>$aps)
		{
			$calc = "AP: ".($key+1)." / ".$ap_count;
			$sql = "UPDATE `wifi`.`files_tmp` SET `tot` = ?, `ap` = ? WHERE `id` = ?";
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $aps['ssid'], PDO::PARAM_STR);
			$prep->bindParam(3, $file_tmp_id, PDO::PARAM_INT);
			$prep->execute();
			if($this->sql->checkError() !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current AP.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				$this->logd("Error Updating Temp Files Table for current AP.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current AP.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}

			$ap_hash = md5($aps['ssid'].$aps['mac'].$aps['chan'].$aps['sectype'].$aps['radio'].$aps['auth'].$aps['encry']);

			if(strlen($aps['ssid']) < 7){$pad_ssid = 20;}else{$pad_ssid = strlen($aps['ssid']);}
			if(strlen($aps['chan']) < 7){$pad_chan = 20;}else{$pad_chan = strlen($aps['chan']);}
			if(strlen($aps['radio']) < 7){$pad_radio = 20;}else{$pad_radio = strlen($aps['radio']);}
			if(strlen($aps['encry']) < 7){$pad_encr = 20;}else{$pad_encr = strlen($aps['encry']);}
			$key_c = $key+1;
			$ssid = str_pad($aps['ssid'], $pad_ssid);
			$chan = str_pad($aps['chan'], $pad_chan);
			$radio = str_pad($aps['radio'], $pad_radio);
			$encry = str_pad($aps['encry'], $pad_encr);
			$this->verbosed("------------------------
			File AP/Total: {$key_c}/{$ap_count}
			SSID:  {$ssid} | MAC: {$aps['mac']}
			CHAN:  {$chan} | SECTYPE: {$aps['sectype']}
			RADIO: {$radio}| AUTH: {$aps['auth']}
			ENCRY: {$encry}| APHASH:".$ap_hash, 1);
			#$this->logd("Starting Import of AP ({$ap_hash}), {$aps['ssid']} ");

			$sql = "SELECT `id`, `signals`, `LA` FROM `wifi`.`wifi_pointers` WHERE `ap_hash` = ? LIMIT 1";
			$res = $this->sql->conn->prepare($sql);
			$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
			$res->execute();
			$this->sql->checkError();

			$fetch = $res->fetch(2);
			if($fetch['id'])
			{
				$prev_signals = $fetch['signals'];
				$prev_LA_time = $fetch['LA'];
				$prev_id	  = $fetch['id'];
				$no_pointer   = 0;
			}else
			{
				$prev_signals = "";
				$prev_LA_time = 0;
				$prev_id	  = 0;
				$no_pointer   = 1;
			}

			if($this->rssi_signals_flag)
			{
				$ap_sig_exp = explode("\\", $aps['signals']);
			}
			else
			{
			   $ap_sig_exp = explode("-", $aps['signals']);
			}

			$compile_sig = array();
			$sig_high = 0;
			$rssi_high = 0;
			$gps_center = 0;
			$FA = 0;

			$this->verbosed("Starting Import of Wifi Signal... ", 1);

			foreach($ap_sig_exp as $sig_gps_id)
			{
				$sig_gps_exp = explode(",", $sig_gps_id);

				$gps_id = $sig_gps_exp[0];
				$signal = $sig_gps_exp[1];
				if(!@$sig_gps_exp[2])
				{
					$rssi = $this->convert->Sig2dBm($sig_gps_exp[1]);
				}else
				{
					$rssi = $sig_gps_exp[2];
				}
				if(!@$vs1data['gpsdata'][$gps_id]){continue;}

				$time_stamp = strtotime($vs1data['gpsdata'][$gps_id]['date']." ".$vs1data['gpsdata'][$gps_id]['time']);

				$sql = "INSERT INTO `wifi`.`wifi_signals` (`id`, `ap_hash`, `signal`, `rssi`, `gps_id`, `time_stamp`) VALUES (NULL, ?, ?, ?, ?, ?)";
				$preps = $this->sql->conn->prepare($sql);
				$preps->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$preps->bindParam(2, $signal, PDO::PARAM_INT);
				$preps->bindParam(3, $rssi, PDO::PARAM_INT);
				$preps->bindParam(4, $vs1data['gpsdata'][$gps_id]['import_id'], PDO::PARAM_INT);
				$preps->bindParam(5, $time_stamp, PDO::PARAM_INT);
				$preps->execute();
				if($this->sql->checkError() !== 0)
				{
					$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
					$this->logd("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
					throw new ErrorException("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
				}

				$sql = "UPDATE `wifi`.`wifi_gps` SET `ap_hash` = ? WHERE `id` = ?";
				$prepg = $this->sql->conn->prepare($sql);
				$prepg->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$prepg->bindParam(2, $vs1data['gpsdata'][$gps_id]['import_id'], PDO::PARAM_STR);
				$prepg->execute();

				if($this->sql->checkError() !== 0)
				{
					$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
					$this->logd("Error Updating GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
					throw new ErrorException("Error Updating GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
				}
				$compile_sig[] = $vs1data['gpsdata'][$gps_id]['import_id'].",".$this->sql->conn->lastInsertId();

				//$r = $this->RotateSpinner($r);
			}

			if(count($compile_sig) < 1 )
			{
				$this->verbosed("This AP has No vaild GPS in the file, this means a corrupted file. APs with corrupted data will not have signal data until there is valid GPS data.", -1);
				#$this->logd("This AP has No vaild GPS in the file, this means a corrupted file. APs with corrupted data will not have signal data until there is valid GPS data.");
			}else
			{
				#Find New First Seen Timestamp
				$FA_SQL = "SELECT `time_stamp` FROM `wifi`.`wifi_signals` WHERE `ap_hash` = ? ORDER BY `time_stamp` ASC LIMIT 1";
				$faprep = $this->sql->conn->prepare($FA_SQL);
				$faprep->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$faprep->execute();
				$fetchfaprep = $faprep->fetch(2);
				$FA_time = date("Y-m-d H:i:s", $fetchfaprep['time_stamp']);

				#Find New Last Seen Timestamp
				$LA_SQL = "SELECT `time_stamp` FROM `wifi`.`wifi_signals` WHERE `ap_hash` = ? ORDER BY `time_stamp` DESC LIMIT 1";
				$laprep = $this->sql->conn->prepare($LA_SQL);
				$laprep->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$laprep->execute();
				$fetchlaprep = $laprep->fetch(2);
				$LA_time = date("Y-m-d H:i:s", $fetchlaprep['time_stamp']);

				#Find Highest GPS Position
				$sql = "SELECT `wifi_gps`.`lat` AS `lat`, `wifi_gps`.`long` AS `long`, `wifi_gps`.`sats` AS `sats`, `wifi_signals`.`signal` AS `signal`, `wifi_signals`.`rssi` AS `rssi` FROM `wifi`.`wifi_signals` INNER JOIN `wifi`.`wifi_gps` on wifi_signals.gps_id = `wifi_gps`.`id` WHERE `wifi_signals`.`ap_hash` = ? And `wifi_gps`.`lat`<>'0.0000' ORDER BY cast(`wifi_signals`.`rssi` as int) DESC, `wifi_signals`.`signal` DESC, `wifi_gps`.`date` DESC, `wifi_gps`.`sats` DESC LIMIT 1";
				$resgps = $this->sql->conn->prepare($sql);
				$resgps->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$resgps->execute();
				$this->sql->checkError();
				$fetchgps = $resgps->fetch(2);
				if($fetchgps['lat'])
				{
					$high_lat = $fetchgps['lat'];
					$high_long = $fetchgps['long'];
					$high_sats = $fetchgps['sats'];
				}
				else
				{
					$high_lat = "0.0000";
					$high_long = "0.0000";
					$high_sats = "0";
				}

				#Create Signal History
				$sig_imp = implode("-", $compile_sig);
				if($prev_signals != "")
				{
					$new_signals = $prev_signals."-".$sig_imp;
				}else
				{
					$new_signals = $sig_imp;
				}

				#Update or Insert AP
				if(!$no_pointer)#Update AP
				{
					$sql = "UPDATE `wifi`.`wifi_pointers` SET `signals` = ? , `FA` = ? , `LA` = ? , `lat` = ? , `long` = ?, `sats` = ? WHERE `ap_hash` = ?";
					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(1, $new_signals, PDO::PARAM_STR);
					$prep->bindParam(2, $FA_time, PDO::PARAM_STR);
					$prep->bindParam(3, $LA_time, PDO::PARAM_STR);
					$prep->bindParam(4, $high_lat, PDO::PARAM_STR);
					$prep->bindParam(5, $high_long, PDO::PARAM_STR);
					$prep->bindParam(6, $high_sats, PDO::PARAM_STR);
					$prep->bindParam(7, $ap_hash, PDO::PARAM_STR);
					$prep->execute();
					if($this->sql->checkError() !== 0)
					{
						$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
						$this->logd("Error Updating AP Pointer Signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
						throw new ErrorException("Error Updating AP Pointer Signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
					}
					$this->verbosed("Updated AP Pointer {".$prev_id."}.", 2);
					$imported_aps[] = $prev_id.":1";
				}
				else#Insert AP
				{
					$sql = "INSERT INTO `wifi`.`wifi_pointers`
						( `id`, `ssid`, `mac`,`chan`,`sectype`,`radio`,`auth`,`encry`,
						`manuf`,`lat`,`long`,`alt`,`BTx`,`OTx`,`NT`,`label`,`LA`,`FA`,
						`username`,`ap_hash`, `signals`, `rssi_high`, `signal_high`)
						VALUES ( NULL,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,? )";
					if(explode("|", $user)[1] == "")
					{
						$user = str_replace("|", "", $user);
					}else
					{
						$user = explode("|", $user)[0];
					}
					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(1, $aps['ssid'], PDO::PARAM_STR);
					$prep->bindParam(2, $aps['mac'], PDO::PARAM_STR);
					$prep->bindParam(3, $aps['chan'], PDO::PARAM_INT);
					$prep->bindParam(4, $aps['sectype'], PDO::PARAM_INT);
					$prep->bindParam(5, $aps['radio'], PDO::PARAM_STR);
					$prep->bindParam(6, $aps['auth'], PDO::PARAM_STR);
					$prep->bindParam(7, $aps['encry'], PDO::PARAM_STR);
					$prep->bindParam(8, $aps['manuf'], PDO::PARAM_STR);
					$prep->bindParam(9, $high_lat, PDO::PARAM_STR);
					$prep->bindParam(10, $high_long, PDO::PARAM_STR);
					$prep->bindParam(11, $high_sats, PDO::PARAM_INT);
					$prep->bindParam(12, $aps['btx'], PDO::PARAM_STR);
					$prep->bindParam(13, $aps['otx'], PDO::PARAM_STR);
					$prep->bindParam(14, $aps['nt'], PDO::PARAM_STR);
					$prep->bindParam(15, $aps['label'], PDO::PARAM_STR);
					$prep->bindParam(16, $LA_time, PDO::PARAM_STR);
					$prep->bindParam(17, $FA_time, PDO::PARAM_STR);
					$prep->bindParam(18, $user, PDO::PARAM_STR);
					$prep->bindParam(19, $ap_hash, PDO::PARAM_STR);
					$prep->bindParam(20, $new_signals, PDO::PARAM_STR);
					$prep->bindParam(21, $rssi_high, PDO::PARAM_INT);
					$prep->bindParam(22, $sig_high, PDO::PARAM_INT);
					$prep->execute();
					if($this->sql->checkError())
					{
						$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
						$this->logd("Error insering wifi pointer. ".var_export($this->sql->conn->errorInfo(),1));
						throw new ErrorException("Error insering wifi pointer.\r\n".var_export($this->sql->conn->errorInfo(),1));
					}
					else
					{
						$imported_aps[] = $this->sql->conn->lastInsertId().":0";
						$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);
						#$this->logd("Inserted APs pointer. {".$this->sql->conn->lastInsertId()."}");
					}
				}
			}
			$this->verbosed("------------------------\r\n", 1);# Done with this AP.
		}
		#Finish off Import and give credit to the user.

		$imported = implode("-", $imported_aps);
		$date = date("Y-m-d H:i:s");

		$ret = array(
				'imported'=> $imported,
				'date'=>$date,
				'aps'=>$ap_count,
				'gps'=>$gps_count
			);
		return $ret;
	}
}