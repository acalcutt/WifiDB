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
    public $gps_count;
    public $ap_count;
    public $vs1data;
    private $ap_hash;
    public $file_importing_id;
    public $file_id;
    public $ImportID;
    public $rssi_signals_flag;
    public $dBmMaxSignal;
    public $dBmDissociationSignal;
    public $log_interval;
    public $convert;
    public $verbose;

	function __construct($config, $convert_obj = NULL, $verbose, &$SQL)
	{
		if($convert_obj === NULL)
		{die("Convert Object is null...");}
		parent::__construct($config, $SQL);
		$this->verbose = $verbose;
		$this->convert = $convert_obj;
		$this->log_level	= $config['log_level'];
		$this->log_interval = $config['log_interval'];
		$this->dBmMaxSignal	  = $config['dBmMaxSignal'];
		$this->dBmDissociationSignal	  = $config['dBmDissociationSignal'];
		$this->rssi_signals_flag = 0;
		$this->ImportID = 0;
        $this->file_id = 0;
        $this->vs1data = array();
        $this->gps_count = 0;
        $this->ap_count = 0;
        $this->file_importing_id = 0;
	}


    public function DoesAPWander( $aps )
    {
        if($aps === NULL)
        {
            throw new Exception("AP data Parameter not set.");
        }
        $SigHist = $this->GetAPSignalHistory($this->ap_hash, $this->file_id);
        $NotThisImportSigHist = $this->GetAPSignalHistoryAllBut($this->ap_hash, $this->file_id);
        #var_dump($SigHist, $NotThisImportSigHist);

        return 0;
    }

    public function GetAPSignalHistoryAllBut()
    {
        $SQL = "SELECT * FROM wifi_signals WHERE `ap_hash` = ? AND `file_id` = ?";
        $prep = $this->sql->conn->prepare($SQL);
        $prep->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
        $prep->bindParam(2, $this->file_id, PDO::PARAM_INT);

        $prep->execute();

        return $prep->fetchAll(2);
    }

    public function GetAPSignalHistory()
    {
        if($this->file_id === NULL)
        {
            $SQL = "SELECT * FROM wifi_signals WHERE `ap_hash` = ?";
            $prep = $this->sql->conn->prepare($SQL);
            $prep->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
        }else
        {
            $SQL = "SELECT * FROM wifi_signals WHERE `ap_hash` = ? AND `file_id` = ?";
            $prep = $this->sql->conn->prepare($SQL);
            $prep->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
            $prep->bindParam(2, $this->file_id, PDO::PARAM_INT);

        }
        $prep->execute();
        return $prep->fetchAll(2);
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


    public function CreateDataArrayFromVS1($source)
    {
        var_dump($source);
        $increment_ids = 0;
        $apdata = array();
        $gdata = array();
        # Open the file and dump its contents into an array. probably should re think this part...
        $file_contents = @file_get_contents($source);
        if(!$file_contents)
        {
            throw new Exception("CreateDataArrayFromVS1 Source File was empty or had an error opening file, or it plum doesn't exist.");
        }

        $File_return = explode("\r\n", utf8_decode($file_contents));

        # Now lets loop through the file and see what we have.
        $this->verbosed("Compiling data from file to array:", 3);
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
                    #echo "---------------------6 columns!----------------";
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
                    #trigger_error("12 columns!", E_USER_NOTICE);
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
                    #echo "---------------------13 columns!----------------";
                    #This is to generate a sanitized and sane array for each AP from the old VS1 format.
                    $ap_line = $file_line_exp;
                    if(!$this->validateMacAddress($ap_line[1]))
                    {
                        #trigger_error("Bad MACADDRESS...", E_USER_NOTICE);
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
                    #echo "---------------------15 columns!----------------";
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
        $vs1data = array('gpsdata'=>$gdata, 'apdata'=>$apdata);
        $ap_count = count($vs1data['apdata']);
        $gps_count = count($vs1data['gpsdata']);
        $ret = array($vs1data, $ap_count, $gps_count);
        return $ret;
    }

    public function ImportGPSData()
    {
        foreach($this->vs1data['gpsdata'] as $key=>$gps)
        {
            #echo ",";
            $calc = "GPS: ".($key+1)." / ".$this->gps_count;
            $sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = 'Importing GPS Data' WHERE `id` = ?";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(1, $calc, PDO::PARAM_STR);
            $prep->bindParam(2, $this->file_importing_id, PDO::PARAM_INT);
            if($this->sql->checkError( $prep->execute(), __LINE__, __FILE__) !== 0)
            {
                $this->verbosed("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
                $this->logd("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
                throw new ErrorException("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
            }

            $sql = "INSERT INTO `wifi_gps` ( `id`, `lat`, `long`, `sats`, `hdp`, `alt`, `geo`, `kmh`, `mph`, `track`, `date`, `time`)
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
            if($this->sql->checkError( $prep->execute(), __LINE__, __FILE__))
            {
                $this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
                $this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
                throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
            }
            #var_dump( $this->sql->conn->lastInsertId() );
            $this->vs1data['gpsdata'][$key]['import_id'] = $this->sql->conn->lastInsertId();

            //$r = $this->RotateSpinner($r);
        }
    }

    public function ImportSignals($aps)
    {
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

        #var_dump($ap_sig_exp);
        foreach($ap_sig_exp as $sig_gps_id) // Import AP Signal history and link GPS.
        {
            $sig_gps_exp = explode(",", $sig_gps_id);
            #echo ".";
            if($sig_gps_exp[1] == ""){$this->verbosed("Bad Signal Data."); echo "Bad Signal Data."; continue;}

            $gps_id = $sig_gps_exp[0];
            $signal = $sig_gps_exp[1];
            #var_dump($signal);
            if($signal > $sig_high)
            {
                $sig_high = $signal;
            }
            if(empty($this->vs1data['gpsdata'][$gps_id]) ){$this->verbosed("Bad GPS Data."); echo "Bad GPS Data.";  continue;}

            #echo $vs1data['gpsdata'][$gps_id]['date']." ".$vs1data['gpsdata'][$gps_id]['time']."\n";

            $date_exp = explode("-", $this->vs1data['gpsdata'][$gps_id]['date']);
            if(strlen($date_exp[0]) === 2)
            {
                $this->vs1data['gpsdata'][$gps_id]['date'] = $date_exp[2]."-".$date_exp[0]."-".$date_exp[1];
            }

            #var_dump($datetime, $this->file_id);

            $rssi = $this->convert->Sig2dBm($signal);

            $sql = "INSERT INTO `wifi_signals` (`id`, `ap_hash`, `signal`, `rssi`, `gps_id`, `date`, `time`, `file_id`) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?)";
            $preps = $this->sql->conn->prepare($sql);
            $preps->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
            $preps->bindParam(2, $signal, PDO::PARAM_INT);
            $preps->bindParam(3, $rssi, PDO::PARAM_STR);
            $preps->bindParam(4, $this->vs1data['gpsdata'][$gps_id]['import_id'], PDO::PARAM_INT);
            $preps->bindParam(5, $this->vs1data['gpsdata'][$gps_id]['date'], PDO::PARAM_STR);
            $preps->bindParam(6, $this->vs1data['gpsdata'][$gps_id]['time'], PDO::PARAM_STR);
            $preps->bindParam(7, $this->file_id, PDO::PARAM_INT);
            if($this->sql->checkError( $preps->execute(), __LINE__, __FILE__) !== 0)
            {
                $this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
                $this->logd("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
                throw new ErrorException("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
            }
            #var_dump("-----------> ".$this->vs1data['gpsdata'][$gps_id]['import_id'].",".$this->sql->conn->lastInsertId());
            $compile_sig[] = $this->vs1data['gpsdata'][$gps_id]['import_id'].",".$this->sql->conn->lastInsertId();
            #$r = $this->RotateSpinner($r);
        }
        #var_dump(count($compile_sig));
        return $compile_sig;
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
		if(!file_exists($source))
		{
			return array(-1, "File does not exist");
		}
		$r = 0;

		# We need to check and see if the file location was passed, if not fail gracefully.
		if($source == NULL)
		{
			$this->logd("The file that needs to be imported was not included in the import function.", "Error");
			$this->verbosed("The file that needs to be imported was not included in the import function", -1);
			throw new ErrorException;
		}

		list($this->vs1data, $this->ap_count, $this->gps_count) = $this->CreateDataArrayFromVS1($source);


        if($this->ap_count === 0)
        {
            $this->verbosed("File did not have an valid AP data, dropping file. $source from user: $user.", -1);
            $this->logd("File did not have an valid AP data, dropping file. $source from user: $user.", "Warning");
            return array(-1, "File does not have any valid AP data.");
        }
        if($this->gps_count === 0)
        {
            $this->verbosed("File did not have an valid GPS data, dropping file. $source from user: $user.", -1);
            $this->logd("File did not have an valid GPS data, dropping file. $source from user: $user.", "Warning");
            return array(-1, "File does not have any valid GPS data.");
        }

        $this->verbosed("Importing GPS data [$this->gps_count]", 2);
		$this->ImportGPSData();

		$this->verbosed("Importing AP Data [$this->ap_count]:", 2);
		$imported_aps = array();
        $NewAPs = 0;
		foreach($this->vs1data['apdata'] as $key=>$aps)
		{
			$calc = "AP: ".($key+1)." / ".$this->ap_count;
            #echo $calc."\r\n";
			$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = ? WHERE `id` = ?";
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $aps['ssid'], PDO::PARAM_STR);
			$prep->bindParam(3, $this->file_importing_id, PDO::PARAM_INT);
			if($this->sql->checkError( $prep->execute(), __LINE__, __FILE__) !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current AP.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				$this->logd("Error Updating Temp Files Table for current AP.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current AP.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}

			$this->ap_hash = md5($aps['ssid'].$aps['mac'].$aps['chan'].$aps['sectype'].$aps['radio'].$aps['auth'].$aps['encry']);

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
			File AP/Total: {$key_c}/{$this->ap_count}
			SSID:  {$ssid} | MAC: {$aps['mac']}
			CHAN:  {$chan} | SECTYPE: {$aps['sectype']}
			RADIO: {$radio}| AUTH: {$aps['auth']}
			ENCRY: {$encry}| APHASH:".$this->ap_hash, 1);
			#$this->logd("Starting Import of AP ({$this->ap_hash}), {$aps['ssid']} ");

			$sql = "SELECT `id`, `LA`, `wander_rating` FROM `wifi_pointers` WHERE `ap_hash` = ? LIMIT 1";
			$res = $this->sql->conn->prepare($sql);
			$res->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
			$this->sql->checkError( $res->execute(), __LINE__, __FILE__);

			$fetch = $res->fetch(2);
			if($fetch['id'])
			{
                $wander_rating = (int)$fetch['wander_rating'];
				$prev_id	  = $fetch['id'];
				$no_pointer   = 0;
			}
            else
			{
                $wander_rating = 0;
				$prev_id	  = 0;
				$no_pointer   = 1;
                $NewAPs++;
			}
            $this->verbosed("Starting Import of AP Signals... ", 1);

            $compile_sig = $this->ImportSignals($aps, $this->file_id); // Done Importing Signals and updating GPS.

            #var_dump(count($compile_sig));
            #echo "\r\n";
			if(count($compile_sig) < 1 )
			{
				$this->verbosed("This AP has No valid GPS in the file, this means a corrupted file. APs with corrupted data will not have signal data until there is valid GPS data.", -1);
				#$this->logd("This AP has No valid GPS in the file, this means a corrupted file. APs with corrupted data will not have signal data until there is valid GPS data.");
			}else
			{
                $FA_time = $this->GetAPFirstActiveTime($this->ap_hash);

                $LA_time = $this->GetAPLastActiveTime($this->ap_hash);

				#Find Highest GPS Position
				list($high_lat, $high_long, $high_sats, $high_alt, $sig_high) = $this->GetAPStrongestGPSPoint($this->ap_hash);
                #var_dump($fetchgps);

				#Update or Insert AP
				if(!$no_pointer)#Update AP
				{
                    #echo "U\r\n";
					$rssi = $this->convert->Sig2dBm($sig_high);

					$sql = "UPDATE `wifi_pointers` SET `FA` = ? , `LA` = ? , `lat` = ? , `long` = ?, `alt` = ?, `rssi_high` = ?, `signal_high` = ? WHERE `ap_hash` = ?";
					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(1, $FA_time, PDO::PARAM_STR);
					$prep->bindParam(2, $LA_time, PDO::PARAM_STR);
					$prep->bindParam(3, $high_lat, PDO::PARAM_STR);
					$prep->bindParam(4, $high_long, PDO::PARAM_STR);
					$prep->bindParam(5, $high_alt, PDO::PARAM_INT);
					$prep->bindParam(6, $rssi, PDO::PARAM_INT);
					$prep->bindParam(7, $sig_high, PDO::PARAM_INT);
					$prep->bindParam(8, $this->ap_hash, PDO::PARAM_STR);
					if($this->sql->checkError( $prep->execute(), __LINE__, __FILE__) !== 0)
					{
						$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
						$this->logd("Error Updating AP Pointer Signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
						throw new ErrorException("Error Updating AP Pointer Signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
					}
					$this->verbosed("Updated AP Pointer {".$prev_id."}.", 2);
					$imported_aps[] = $prev_id.":1";


                    $this->verbosed("Now, lets check to see if the AP has wandered.", 2);

                    $sql = "select DATEDIFF(`LA`, `FA`) as DD from wifi_pointers WHERE ap_hash = ?";
                    $prep = $this->sql->conn->prepare($sql);
                    $prep->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
                    $prep->execute();
                    $DateRangeRet = $prep->fetch(2);

                    if((int)$DateRangeRet['DD'] > 1)
                    {
                        if($this->DoesAPWander($this->ap_hash, $this->file_id, $aps))
                        {
                            $wander_rating++;
                            $this->verbosed("Look at that, it has. Its new Rating is: .".$wander_rating, 2);
                        }
                    }

                }
				else#Insert AP
				{
                    #echo "N\r\n";
					$sql = "INSERT INTO `wifi_pointers`
						( `id`, `ssid`, `mac`,`chan`,`sectype`,`radio`,`auth`,`encry`,
						`manuf`,`lat`,`long`,`alt`,`BTx`,`OTx`,`NT`,`label`,`LA`,`FA`,
						`username`,`ap_hash`, `rssi_high`, `signal_high`)
						VALUES ( NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
					if(@$user[-1] == "|")
					{
						$user = str_replace("|", "", $user);
					}else
					{
						$user = explode("|", $user)[0];
					}
					$rssi = $this->convert->Sig2dBm($sig_high);

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
					$prep->bindParam(19, $this->ap_hash, PDO::PARAM_STR);
					$prep->bindParam(20, $rssi, PDO::PARAM_INT);
					$prep->bindParam(21, $sig_high, PDO::PARAM_INT);
					if($this->sql->checkError( $prep->execute(), __LINE__, __FILE__))
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
				'aps'=>$this->ap_count,
				'gps'=>$this->gps_count,
                'newaps'=>$NewAPs
			);
		return $ret;
	}

    public function GetAPStrongestGPSPoint()
    {
        $sql = "SELECT `wifi_gps`.`lat` AS `lat`, `wifi_gps`.`long` AS `long`, `wifi_gps`.`alt` AS `alt`, `wifi_gps`.`sats` AS `sats`, `wifi_signals`.`signal` AS `signal`, `wifi_signals`.`rssi` AS `rssi` FROM `wifi_signals` INNER JOIN `wifi_gps` on wifi_signals.gps_id = `wifi_gps`.`id` WHERE `wifi_signals`.`ap_hash` = ? And `wifi_gps`.`lat`<>'0.0000' ORDER BY cast(`wifi_signals`.`rssi` as SIGNED) DESC, `wifi_signals`.`signal` DESC, `wifi_gps`.`date` DESC, `wifi_gps`.`time` DESC, `wifi_gps`.`sats` DESC LIMIT 1";
        $resgps = $this->sql->conn->prepare($sql);
        $resgps->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
        $this->sql->checkError( $resgps->execute(), __LINE__, __FILE__);
        $fetchgps = $resgps->fetch(2);

        if($fetchgps['lat'])
        {
            $high_lat = $fetchgps['lat'];
            $high_long = $fetchgps['long'];
            $high_sats = $fetchgps['sats'];
            $sig_high = $fetchgps['signal'];
            $high_alt = $fetchgps['alt'];
        }
        else
        {
            $high_lat = "0.0000";
            $high_long = "0.0000";
            $high_sats = "0";
            $sig_high = "0";
            $high_alt = "0";
        }
        return array($high_lat, $high_long, $high_sats, $high_alt, $sig_high);
    }

    public function GetAPLastActiveTime()
    {
        #Find New Last Seen Timestamp
        $LA_SQL = "SELECT `date`, `time` FROM `wifi_signals` WHERE `ap_hash` = ? ORDER BY `date` DESC, `time` DESC LIMIT 1";
        $laprep = $this->sql->conn->prepare($LA_SQL);
        $laprep->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
        $laprep->execute();
        $fetchlaprep = $laprep->fetch(2);
        return $fetchlaprep['date']." ".$fetchlaprep['time'];
    }

    public function GetAPFirstActiveTime()
    {
        #Find New First Seen Timestamp
        $FA_SQL = "SELECT `date`, `time` FROM `wifi_signals` WHERE `ap_hash` = ? ORDER BY `date` ASC, `time` ASC LIMIT 1";
        $faprep = $this->sql->conn->prepare($FA_SQL);
        $faprep->bindParam(1, $this->ap_hash, PDO::PARAM_STR);
        $faprep->execute();
        $fetchfaprep = $faprep->fetch(2);
        return $fetchfaprep['date']." ".$fetchfaprep['time'];
    }
}