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
	function __construct($config, $convert_obj = NULL, $verbose)
	{
		if($convert_obj === NULL)
		{die("Convert Object is null...");}
		parent::__construct($config);
		$this->verbose = $verbose;
		$this->convert = $convert_obj;
		$this->log_level	= $config['log_level'];
		$this->log_interval = $config['log_interval'];
		$this->dBmMaxSignal	  = $config['dBmMaxSignal'];
		$this->dBmDissociationSignal	  = $config['dBmDissociationSignal'];
		$this->rssi_signals_flag = 0;
		$this->ImportID = 0;
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
	
	private function UpdateFileValidGPS($file_id)
	{
		#Find if file had Valid GPS
		if($this->sql->service == "mysql")
		{
			$sql = "SELECT `wifi_hist`.`Hist_ID`\n"
				. "FROM `wifi_hist`\n"
				. "LEFT JOIN `wifi_gps` ON `wifi_hist`.`GPS_ID` = `wifi_gps`.`GPS_ID`\n"
				. "WHERE `wifi_hist`.`File_ID` = ? And `wifi_gps`.`GPS_ID` IS NOT NULL And `wifi_gps`.`Lat` != '0.0000'\n"
				. "LIMIT 1";
		}
		else if($this->sql->service == "sqlsrv")
		{
			$sql = "SELECT TOP 1 [wifi_hist].[Hist_ID]\n"
				. "FROM [wifi_hist]\n"
				. "LEFT JOIN [wifi_gps] ON [wifi_hist].[GPS_ID] = [wifi_gps].[GPS_ID]\n"
				. "WHERE [wifi_hist].[File_ID] = ? And [wifi_gps].[GPS_ID] IS NOT NULL And [wifi_gps].[Lat] != '0.0000'";
		}
		$prepvgps = $this->sql->conn->prepare($sql);
		$prepvgps->bindParam(1, $file_id, PDO::PARAM_INT);
		$prepvgps->execute();
		$prepvgps_fetch = $prepvgps->fetch(2);
		if($prepvgps_fetch)
		{
			$ValidGPS = 1;
			if($this->sql->service == "mysql")
				{$sql = "UPDATE `files` SET `ValidGPS` = ? WHERE `id` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "UPDATE [files] SET [ValidGPS] = ? WHERE [id] = ?";}
			$prepvgpsu = $this->sql->conn->prepare($sql);
			$prepvgpsu->bindParam(1, $ValidGPS, PDO::PARAM_INT);
			$prepvgpsu->bindParam(2, $file_id, PDO::PARAM_INT);
			$prepvgpsu->execute();
		}
	}
	
	private function UpdateHighPoints($file_importing_id, $ap_id, $FirstDate = NULL, $LastDate = NULL, $HighSig = 0, $HighRSSI = -99, $HighRSSIwGPS = -99)
	{
		if($HighSig == ""){$HighSig = 0;}
		if($HighRSSI == ""){$HighRSSI = -99;}
		if($HighRSSIwGPS == ""){$HighRSSIwGPS = -99;}
		$text = "Updating High Points";
		if($this->sql->service == "mysql")
			{$sql = "UPDATE `files_importing` SET `tot` = ? WHERE `id` = ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "UPDATE [files_importing] SET [tot] = ? WHERE [id] = ?";}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $text, PDO::PARAM_STR);
		$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
		$prep->execute();
		if($this->sql->service == "mysql")
		{
			$sql = "SELECT \n"
				. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL And `Hist_Date` <= ? ORDER BY Hist_Date Asc LIMIT 1) As `FA_id`,\n"
				. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL And `Hist_Date` >= ? ORDER BY Hist_Date DESC LIMIT 1) As `LA_id`,\n"
				. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL And `Sig` >= ? ORDER BY Sig DESC, `Hist_Date` DESC LIMIT 1) As `HighSig_id`,\n"
				. "(SELECT Hist_ID FROM `wifi_hist` WHERE `AP_ID` = `wap`.`AP_ID` And `Hist_date` IS NOT NULL And `RSSI` >= ? ORDER BY RSSI DESC, `Hist_Date` DESC LIMIT 1) As `HighRSSI_id`,\n"
				. "(SELECT `wifi_hist`.`GPS_ID`\n"
				. "    FROM `wifi_hist`\n"
				. "    INNER JOIN `wifi_gps` ON `wifi_hist`.`GPS_ID` = `wifi_gps`.`GPS_ID`\n"
				. "    WHERE `wifi_hist`.`RSSI` >= ? And `wifi_hist`.`AP_ID` = `wap`.`AP_ID` And `wifi_hist`.`Hist_date` IS NOT NULL And `wifi_gps`.`Lat` != '0.0000'\n"
				. "    ORDER BY `wifi_hist`.`RSSI` DESC, `wifi_hist`.`Hist_Date` DESC, `wifi_gps`.`NumOfSats` DESC\n"
				. "    LIMIT 1) As `HighGps_id`\n"
				. "FROM `wifi_ap` As `wap`\n"
				. "WHERE `wap`.`AP_ID` = ?";
		}
		else if($this->sql->service == "sqlsrv")
		{
			$sql = "SELECT\n"
				. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL And [Hist_Date] <= ? ORDER BY Hist_Date Asc) AS [FA_id],\n"
				. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL And [Hist_Date] >= ? ORDER BY Hist_Date DESC) AS [LA_id],\n"
				. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL And [Sig] >= ? ORDER BY Sig DESC, [Hist_Date] DESC) AS [HighSig_id],\n"
				. "(SELECT TOP 1 [Hist_ID] FROM [wifi_hist] WHERE [AP_ID] = [wap].[AP_ID] And [Hist_date] IS NOT NULL And [RSSI] >= ? ORDER BY RSSI DESC, [Hist_Date] DESC) AS [HighRSSI_id],\n"
				. "(SELECT TOP 1 [wifi_hist].[GPS_ID]\n"
				. "	FROM [wifi_hist]\n"
				. "	INNER JOIN [wifi_gps] ON [wifi_hist].[GPS_ID] = [wifi_gps].[GPS_ID]\n"
				. "	WHERE [wifi_hist].[RSSI] >= ? AND [wifi_hist].[AP_ID] = [wap].[AP_ID] AND [wifi_hist].[Hist_Date] IS NOT NULL AND [wifi_gps].[Lat] != '0.0000'\n"
				. "	ORDER BY [wifi_hist].[RSSI] DESC, [wifi_hist].[Hist_Date] DESC, [wifi_gps].[NumOfSats] DESC) As [HighGps_id]\n"
				. "FROM [wifi_ap] As [wap]\n"
				. "WHERE [wap].[AP_ID] = ?";
		}
		$resgps = $this->sql->conn->prepare($sql);
		$resgps->bindParam(1, $FirstDate, PDO::PARAM_STR);
		$resgps->bindParam(2, $LastDate, PDO::PARAM_STR);
		$resgps->bindParam(3, $HighSig, PDO::PARAM_INT);
		$resgps->bindParam(4, $HighRSSI, PDO::PARAM_INT);
		$resgps->bindParam(5, $HighRSSIwGPS, PDO::PARAM_INT);
		$resgps->bindParam(6, $ap_id, PDO::PARAM_INT);
		$resgps->execute();
		$fetchgps = $resgps->fetch(2);
		$FA_id = $fetchgps['FA_id'];
		$LA_id = $fetchgps['LA_id'];
		$HighSig_id = $fetchgps['HighSig_id'];
		$HighRSSI_id = $fetchgps['HighRSSI_id'];
		$HighGps_id = $fetchgps['HighGps_id'];
		

		#Update AP IDs
		if($this->sql->service == "mysql")
			{$sql = "UPDATE `wifi_ap` SET `FirstHist_ID` = ? , `LastHist_ID` = ? , `HighRSSI_ID` = ?, `HighSig_ID` = ? , `HighGps_ID` = ? WHERE `AP_ID` = ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "UPDATE [wifi_ap] SET [FirstHist_ID] = ? , [LastHist_ID] = ? , [HighRSSI_ID] = ?, [HighSig_ID] = ? , [HighGps_ID] = ? WHERE [AP_ID] = ?";}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $FA_id, PDO::PARAM_INT);
		$prep->bindParam(2, $LA_id, PDO::PARAM_INT);
		$prep->bindParam(3, $HighRSSI_id, PDO::PARAM_INT);
		$prep->bindParam(4, $HighSig_id, PDO::PARAM_INT);
		$prep->bindParam(5, $HighGps_id, PDO::PARAM_INT);
		$prep->bindParam(6, $ap_id, PDO::PARAM_INT);
		$prep->execute();
		if($this->sql->checkError() !== 0)
		{
			$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
			//$this->logd("Error Updating AP Hist IDs.\r\n".var_export($this->sql->conn->errorInfo(),1));
			throw new ErrorException("Error Updating AP Hist IDs.\r\n".var_export($this->sql->conn->errorInfo(),1));
		}
		
		$text = "";
		if($this->sql->service == "mysql")
			{$sql = "UPDATE `files_importing` SET `tot` = ? WHERE `id` = ?";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "UPDATE [files_importing] SET [tot] = ? WHERE [id] = ?";}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $text, PDO::PARAM_STR);
		$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
		$prep->execute();
		
		$this->verbosed("Updated AP Pointer {".$ap_id."}.", 2);
		$this->verbosed("------------------------\r\n", 1);# Done with this AP.
	}

	public function import_wardrive3($source="", $file_id, $file_importing_id)
	{
		if(!file_exists($source))
		{
			return array(-1, "File does not exist");
		}
		
		$dbh = new PDO("sqlite:$source");
		$dbh->setAttribute(PDO::ATTR_ERRMODE,
			PDO::ERRMODE_EXCEPTION);
		$APQuery = $dbh->query("SELECT * FROM networks");
		$all_aps = $APQuery->fetchAll();
		$File_lcount = count($all_aps);
		$imported_aps = array();
		$NewAPs = 0;
		$ap_count = 0;
		$gps_count = 0;
		foreach ($all_aps as $key => $ap)
		{
			$fBSSID = strtoupper($ap['bssid']);
			if(!$this->validateMacAddress($fBSSID)){continue;}
			$fSSID = $ap['ssid'];
			$fFrequency = $ap["frequency"];
			$fCapabilities = $ap["capabilities"];
			$fRSSI = $ap['level'];
			if($fRSSI == 0){$fRSSI = -99;}
			$fSignal = $this->convert->dBm2Sig($fRSSI);
			$fLat = $this->convert->all2dm(number_format($ap['lat'], 7));
			$fLon = $this->convert->all2dm(number_format($ap['lon'], 7));
			$fAlt = $ap['alt'];
			$fDate = date("Y-m-d H:i:s", substr($ap['timestamp'], 0, -3));
			
			list($authen, $encry, $sectype, $nt) = $this->convert->findCapabilities($fCapabilities);
			list($chan, $radio) = $this->convert->findFreq($fFrequency);
			
			$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
			
			$calc = "Line: ".($key+1)." / ".$File_lcount;
			if($this->sql->service == "mysql")
				{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = ? WHERE `id` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = ? WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
			$prep->bindParam(3, $file_importing_id, PDO::PARAM_INT);
			$prep->execute();
			if($this->sql->checkError() !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				//$this->logd("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}
			
			if($this->sql->service == "mysql")
				{$sql = "SELECT `AP_ID`, `FLAGS` FROM `wifi_ap` WHERE `ap_hash` = ? LIMIT 1";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [AP_ID], [FLAGS] FROM [wifi_ap] WHERE [ap_hash] = ?";}
			$res = $this->sql->conn->prepare($sql);
			$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
			$res->execute();
			$this->sql->checkError();
			$fetch = $res->fetch(2);
			$new = 0;
			$ap_id = "";
			if($fetch['AP_ID'])
			{
				$ap_id	= $fetch['AP_ID'];
				$ap_FLAGS	= $fetch['FLAGS'];
				if($ap_FLAGS == "" && $fCapabilities != "")
				{
					if($this->sql->service == "mysql")
						{$sql = "UPDATE `wifi_ap` SET `FLAGS` = ? WHERE `AP_ID` = ?";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "UPDATE [wifi_ap] SET [FLAGS] = ? WHERE [AP_ID] = ?";}
					$prepu = $this->sql->conn->prepare($sql);
					$prepu->bindParam(1, $fCapabilities, PDO::PARAM_STR);
					$prepu->bindParam(2, $ap_id, PDO::PARAM_INT);
					$prepu->execute();
				}
			}
			else
			{
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_ap` (`BSSID`, `SSID`, `CHAN`, `AUTH`, `ENCR`, `SECTYPE`, `RADTYPE`, `NETTYPE`, `FLAGS`, `ap_hash`, `File_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_ap] ([BSSID], [SSID], [CHAN], [AUTH], [ENCR], [SECTYPE], [RADTYPE], [NETTYPE], [FLAGS], [ap_hash], [File_ID]) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}
						
				$prep = $this->sql->conn->prepare($sql);
				#var_dump($aps);
				$prep->bindParam(1, $fBSSID, PDO::PARAM_STR);
				$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
				$prep->bindParam(3, $chan, PDO::PARAM_INT);
				$prep->bindParam(4, $authen, PDO::PARAM_STR);		
				$prep->bindParam(5, $encry, PDO::PARAM_STR);
				$prep->bindParam(6, $sectype, PDO::PARAM_INT);
				$prep->bindParam(7, $radio, PDO::PARAM_STR);
				$prep->bindParam(8, $nt, PDO::PARAM_STR);
				$prep->bindParam(9, $fCapabilities, PDO::PARAM_STR);
				$prep->bindParam(10, $ap_hash, PDO::PARAM_STR);
				$prep->bindParam(11, $file_id, PDO::PARAM_INT);
				$prep->execute();
				if($this->sql->checkError())
				{
					$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Error insering wifi pointer. ".var_export($this->sql->conn->errorInfo(),1));
					throw new ErrorException("Error insering wifi pointer.\r\n".var_export($this->sql->conn->errorInfo(),1));
				}
				$ap_id = $this->sql->conn->lastInsertId();
				$imported_aps[] = $ap_id.":0";
				$new = 1;
				$NewAPs++;
				$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);			
			}
			if($ap_id == ""){continue;}
			$ap_count++;

			if($this->sql->service == "mysql")
				{$sql = "INSERT INTO `wifi_gps` ( `File_ID`, `Lat`, `Lon`, `Alt`, `GPS_Date`) VALUES (?, ?, ?, ?, ?)";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "INSERT INTO [wifi_gps] ( [File_ID], [Lat], [Lon], [Alt], [GPS_Date]) VALUES (?, ?, ?, ?, ?)";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1,$file_id, PDO::PARAM_INT);
			$prep->bindParam(2,$fLat, PDO::PARAM_STR);
			$prep->bindParam(3,$fLon, PDO::PARAM_STR);
			$prep->bindParam(4,$fAlt, PDO::PARAM_STR);
			$prep->bindParam(5,$fDate,PDO::PARAM_STR);
			$prep->execute();
			if($this->sql->checkError())
			{
				echo "Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1);
				$this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
				//$this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
			}
			$gps_id = $this->sql->conn->lastInsertId();	
			if($gps_id == ""){continue;}
			$gps_count++;

			if($this->sql->service == "mysql")
				{$sql = "INSERT INTO `wifi_hist` (`AP_ID`, `GPS_ID`, `File_ID`, `Sig`, `RSSI`, `New`, `Hist_Date`) VALUES (?, ?, ?, ?, ?, ?, ?)";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES (?, ?, ?, ?, ?, ?, ?)";}
			$preps = $this->sql->conn->prepare($sql);
			$preps->bindParam(1, $ap_id, PDO::PARAM_INT);
			$preps->bindParam(2, $gps_id, PDO::PARAM_INT);
			$preps->bindParam(3, $file_id, PDO::PARAM_INT);
			$preps->bindParam(4, $fSignal, PDO::PARAM_INT);
			$preps->bindParam(5, $fRSSI, PDO::PARAM_INT);
			$preps->bindParam(6, $new, PDO::PARAM_INT);
			$preps->bindParam(7, $fDate, PDO::PARAM_STR);
			$preps->execute();
			
			$hist_id = $this->sql->conn->lastInsertId();
			if($hist_id == ""){continue;}
			
			#Update High GPS, First Seen, Last Seen, High Sig, High RSSI
			if($fLat != "0.0000" && $fLon != "0.0000" && $fRSSI > -99){$fRSSIwGPS = $fRSSI;}else{$fRSSIwGPS = -99;}
			$this->UpdateHighPoints($file_importing_id, $ap_id, $fDate, $fDate, $fSignal, $fRSSI, $fRSSIwGPS);
		}
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		#Finish off Import and give credit to the user.
		$imported = implode("-", $imported_aps);
		$date = date("Y-m-d H:i:s");
		
		$ret = array(
				'imported'=> $imported,
				'date'=>$date,
				'aps'=>$ap_count,
				'gps'=>$gps_count,
				'newaps'=>$NewAPs
			);
		return $ret;
	}
	
	public function import_wardrive4($source="", $file_id, $file_importing_id)
	{
		if(!file_exists($source))
		{
			return array(-1, "File does not exist");
		}
		
		$dbh = new PDO("sqlite:$source");
		$dbh->setAttribute(PDO::ATTR_ERRMODE,
			PDO::ERRMODE_EXCEPTION);

		$APQuery = $dbh->query("SELECT * FROM `wifi`");
		if($dbh->errorCode() != "00000")
		{
			return array(-1, "File does not have any access points");
		}
		$all_aps = $APQuery->fetchAll(2);
		$File_lcount = count($all_aps);
		$imported_aps = array();
		$NewAPs = 0;
		$ap_count = 0;
		$gps_count = 0;
		foreach($all_aps as $key => $ap)
		{
			$fid = $ap['_id'];
			$fBSSID = strtoupper($ap['bssid']);
			if(!$this->validateMacAddress($fBSSID)){continue;}
			$fSSID = $ap['ssid'];
			$fFrequency = $ap["frequency"];
			$fCapabilities = $ap["capabilities"];

			list($authen, $encry, $sectype, $nt) = $this->convert->findCapabilities($fCapabilities);
			list($chan, $radio) = $this->convert->findFreq($fFrequency);
			
			$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
			
			$calc = "Line: ".($key+1)." / ".$File_lcount;
			if($this->sql->service == "mysql")
				{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = ? WHERE `id` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = ? WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
			$prep->bindParam(3, $file_importing_id, PDO::PARAM_INT);
			$prep->execute();
			if($this->sql->checkError() !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				//$this->logd("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}
			
			if($this->sql->service == "mysql")
				{$sql = "SELECT `AP_ID`, `FLAGS` FROM `wifi_ap` WHERE `ap_hash` = ? LIMIT 1";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [AP_ID], [FLAGS] FROM [wifi_ap] WHERE [ap_hash] = ?";}
			$res = $this->sql->conn->prepare($sql);
			$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
			$res->execute();
			$this->sql->checkError();
			$fetch = $res->fetch(2);
			$new = 0;
			$ap_id = "";
			if($fetch['AP_ID'])
			{
				$ap_id	= $fetch['AP_ID'];
				$ap_FLAGS	= $fetch['FLAGS'];
				if($ap_FLAGS == "" && $fCapabilities != "")
				{
					if($this->sql->service == "mysql")
						{$sql = "UPDATE `wifi_ap` SET `FLAGS` = ? WHERE `AP_ID` = ?";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "UPDATE [wifi_ap] SET [FLAGS] = ? WHERE [AP_ID] = ?";}
					$prepu = $this->sql->conn->prepare($sql);
					$prepu->bindParam(1, $fCapabilities, PDO::PARAM_STR);
					$prepu->bindParam(2, $ap_id, PDO::PARAM_INT);
					$prepu->execute();
				}
			}
			else
			{
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_ap` (`BSSID`, `SSID`, `CHAN`, `AUTH`, `ENCR`, `SECTYPE`, `RADTYPE`, `NETTYPE`, `FLAGS`, `ap_hash`, `File_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_ap] ([BSSID], [SSID], [CHAN], [AUTH], [ENCR], [SECTYPE], [RADTYPE], [NETTYPE], [FLAGS], [ap_hash], [File_ID]) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}		
				$prep = $this->sql->conn->prepare($sql);
				#var_dump($aps);
				$prep->bindParam(1, $fBSSID, PDO::PARAM_STR);
				$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
				$prep->bindParam(3, $chan, PDO::PARAM_INT);
				$prep->bindParam(4, $authen, PDO::PARAM_STR);		
				$prep->bindParam(5, $encry, PDO::PARAM_STR);
				$prep->bindParam(6, $sectype, PDO::PARAM_INT);
				$prep->bindParam(7, $radio, PDO::PARAM_STR);
				$prep->bindParam(8, $nt, PDO::PARAM_STR);
				$prep->bindParam(9, $fCapabilities, PDO::PARAM_STR);
				$prep->bindParam(10, $ap_hash, PDO::PARAM_STR);
				$prep->bindParam(11, $file_id, PDO::PARAM_INT);
				$prep->execute();
				if($this->sql->checkError())
				{
					$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Error insering wifi pointer. ".var_export($this->sql->conn->errorInfo(),1));
					throw new ErrorException("Error insering wifi pointer.\r\n".var_export($this->sql->conn->errorInfo(),1));
				}
				$ap_id = $this->sql->conn->lastInsertId();
				$imported_aps[] = $ap_id.":0";
				$new = 1;
				$NewAPs++;
				$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);			
			}
			if($ap_id == ""){continue;}
			$ap_count++;
				
			$sql1 = "SELECT * FROM `wifispot` WHERE `fk_wifi` = '$fid'";
			$gps_query = $dbh->query($sql1);
			$gps_fetch = $gps_query->fetchAll(2);
			foreach($gps_fetch as $point)
			{
				$fRSSI = $point['level'];
				if($fRSSI == 0){$fRSSI = -99;}
				$fSignal = $this->convert->dBm2Sig($fRSSI);
				$fLat = $this->convert->all2dm(number_format($point['lat'], 7));
				$fLon = $this->convert->all2dm(number_format($point['lon'], 7));
				$fAlt = $point['alt'];
				$fDate = date("Y-m-d H:i:s", substr($point['timestamp'], 0, -3));

				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_gps` (`File_ID`, `Lat`, `Lon`, `Alt`, `GPS_Date`) VALUES (?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_gps] ([File_ID], [Lat], [Lon], [Alt], [GPS_Date]) VALUES (?, ?, ?, ?, ?)";}	
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1,$file_id, PDO::PARAM_INT);
				$prep->bindParam(2,$fLat, PDO::PARAM_STR);
				$prep->bindParam(3,$fLon, PDO::PARAM_STR);
				$prep->bindParam(4,$fAlt, PDO::PARAM_STR);
				$prep->bindParam(5,$fDate,PDO::PARAM_STR);
				$prep->execute();
				if($this->sql->checkError())
				{
					echo "Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1);
					$this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
					throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
				}
				$gps_id = $this->sql->conn->lastInsertId();	
				if($gps_id == ""){continue;}
				$gps_count++;
				
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_hist` (`AP_ID`, `GPS_ID`, `File_ID`, `Sig`, `RSSI`, `New`, `Hist_Date`) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES (?, ?, ?, ?, ?, ?, ?)";}	
				$preps = $this->sql->conn->prepare($sql);
				$preps->bindParam(1, $ap_id, PDO::PARAM_INT);
				$preps->bindParam(2, $gps_id, PDO::PARAM_INT);
				$preps->bindParam(3, $file_id, PDO::PARAM_INT);
				$preps->bindParam(4, $fSignal, PDO::PARAM_INT);
				$preps->bindParam(5, $fRSSI, PDO::PARAM_INT);
				$preps->bindParam(6, $new, PDO::PARAM_INT);
				$preps->bindParam(7, $fDate, PDO::PARAM_STR);
				$preps->execute();
				
				$hist_id = $this->sql->conn->lastInsertId();
				if($hist_id == ""){continue;}
				
				#Update High GPS, First Seen, Last Seen, High Sig, High RSSI
				if($fLat != "0.0000" && $fLon != "0.0000" && $fRSSI > -99){$fRSSIwGPS = $fRSSI;}else{$fRSSIwGPS = -99;}
				$this->UpdateHighPoints($file_importing_id, $ap_id, $fDate, $fDate, $fSignal, $fRSSI, $fRSSIwGPS);
			}
		}
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		#Finish off Import and give credit to the user.
		$imported = implode("-", $imported_aps);
		$date = date("Y-m-d H:i:s");
		
		$ret = array(
				'imported'=> $imported,
				'date'=>$date,
				'aps'=>$ap_count,
				'gps'=>$gps_count,
				'newaps'=>$NewAPs
			);
		return $ret;
	}
	
	public function import_wigglewificsv($source="", $file_id, $file_importing_id)
	{
		if(!file_exists($source))
		{
			return array(-1, "File does not exist");
		}

		# Open the file and dump its contents into an array. probably should re think this part...
		$file_contents = @file_get_contents($source);
		$file_contents = mb_convert_encoding($file_contents, 'UTF-8', mb_detect_encoding($file_contents, 'UTF-8, ISO-8859-1', true));
		$file_contents = str_replace("\xEF\xBB\xBF",'',$file_contents);// Remove Byte Order Mark
		if($file_contents === "")
		{
			return array(-1, "File was empty, or error opening file.");
		}
		$File_return	 = explode("\n", $file_contents);
		$File_lcount = count($File_return);

		# Now lets loop through the file and see what we have.
		$this->verbosed("Compiling data from file to array:", 3);
		$imported_aps = array();
		$newhashes = array();
		$NewAPs = 0;
		$ap_count = 0;
		$gps_count = 0;
		
		foreach($File_return as $key => $file_line)
		{
			$apinfo = str_getcsv ($file_line);
			
			$fBSSID = strtoupper($apinfo[0]);
			if(!$this->validateMacAddress($fBSSID)){continue;}
			$fSSID = $apinfo[1];
			$fAuthMode = $apinfo[2];
			$fDate = $apinfo[3];
			if(substr($fDate, 0, 4) == "1969"){continue;}//Fix for bad date
			$fchannel = $apinfo[4];
			$fRSSI = $apinfo[5];
			if($fRSSI == 0){$fRSSI = -99;}//Fix for 0 RSSI causing bad sig calculation
			$fSignal = $this->convert->dBm2Sig($fRSSI);
			$fLat = $this->convert->all2dm(number_format($apinfo[6],7));
			$fLon = $this->convert->all2dm(number_format($apinfo[7],7));
			$fAltitudeMeters = $apinfo[8];
			$fAccuracy = $apinfo[9];
			$fType = $apinfo[10];
			if($fType !== "WIFI"){continue;}
			
			list($authen, $encry, $sectype, $nt) = $this->convert->findCapabilities($fAuthMode);
			list($chan, $radio) = $this->convert->findFreq($fchannel);
			
			$ap_hash = md5($fSSID.$fBSSID.$fchannel.$sectype.$authen.$encry);
			
			$calc = "Line: ".($key+1)." / ".$File_lcount;
			if($this->sql->service == "mysql")
				{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = ? WHERE `id` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = ? WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
			$prep->bindParam(3, $file_importing_id, PDO::PARAM_INT);
			$prep->execute();
			if($this->sql->checkError() !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				//$this->logd("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}

			if (($timestamp = strtotime($fDate)) !== false) {
				$GpsDate = date("Y-m-d H:i:s", $timestamp);

				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_gps` (`File_ID`, `Lat`, `Lon`, `Alt`, `AccuracyMeters`, `GPS_Date`) VALUES (?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_gps] ([File_ID], [Lat], [Lon], [Alt], [AccuracyMeters], [GPS_Date]) VALUES (?, ?, ?, ?, ?, ?)";}	
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1,$file_id, PDO::PARAM_INT);
				$prep->bindParam(2,$fLat, PDO::PARAM_STR);
				$prep->bindParam(3,$fLon, PDO::PARAM_STR);
				$prep->bindParam(4,$fAltitudeMeters, PDO::PARAM_STR);
				$prep->bindParam(5,$fAccuracy, PDO::PARAM_STR);
				$prep->bindParam(6,$GpsDate,PDO::PARAM_STR);
				$prep->execute();
				if($this->sql->checkError())
				{
					echo "Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1);
					$this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
					throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
				}
				$gps_id = $this->sql->conn->lastInsertId();
				
				if($gps_id == ""){continue;}
				$gps_count++;
				
				if($this->sql->service == "mysql")
					{$sql = "SELECT `AP_ID`, `FLAGS` FROM `wifi_ap` WHERE `ap_hash` = ? LIMIT 1";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "SELECT TOP 1 [AP_ID], [FLAGS] FROM [wifi_ap] WHERE [ap_hash] = ?";}
				$res = $this->sql->conn->prepare($sql);
				$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$res->execute();
				$this->sql->checkError();
				$fetch = $res->fetch(2);
				$ap_id = "";
				if($fetch['AP_ID'])
				{
					$ap_id	= $fetch['AP_ID'];
					$ap_FLAGS	= $fetch['FLAGS'];
					if($ap_FLAGS == "" && $fAuthMode != "")
					{
						if($this->sql->service == "mysql")
							{$sql = "UPDATE `wifi_ap` SET `FLAGS` = ? WHERE `AP_ID` = ?";}
						else if($this->sql->service == "sqlsrv")
							{$sql = "UPDATE [wifi_ap] SET [FLAGS] = ? WHERE [AP_ID] = ?";}
						$prepu = $this->sql->conn->prepare($sql);
						$prepu->bindParam(1, $fAuthMode, PDO::PARAM_STR);
						$prepu->bindParam(2, $ap_id, PDO::PARAM_INT);
						$prepu->execute();
					}
				}
				else
				{	
					if($this->sql->service == "mysql")
						{$sql = "INSERT INTO `wifi_ap` (`BSSID`, `SSID`, `CHAN`, `AUTH`, `ENCR`, `SECTYPE`, `RADTYPE`, `NETTYPE`, `FLAGS`, `ap_hash`, `File_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "INSERT INTO [wifi_ap] ([BSSID], [SSID], [CHAN], [AUTH], [ENCR], [SECTYPE], [RADTYPE], [NETTYPE], [FLAGS], [ap_hash], [File_ID]) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}
					$prep = $this->sql->conn->prepare($sql);
					#var_dump($aps);
					$prep->bindParam(1, $fBSSID, PDO::PARAM_STR);
					$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
					$prep->bindParam(3, $fchannel, PDO::PARAM_INT);
					$prep->bindParam(4, $authen, PDO::PARAM_STR);		
					$prep->bindParam(5, $encry, PDO::PARAM_STR);
					$prep->bindParam(6, $sectype, PDO::PARAM_INT);
					$prep->bindParam(7, $radio, PDO::PARAM_STR);
					$prep->bindParam(8, $nt, PDO::PARAM_STR);
					$prep->bindParam(9, $fAuthMode, PDO::PARAM_STR);
					$prep->bindParam(10, $ap_hash, PDO::PARAM_STR);
					$prep->bindParam(11, $file_id, PDO::PARAM_INT);
					$prep->execute();
					if($this->sql->checkError())
					{
						$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
						//$this->logd("Error insering wifi pointer. ".var_export($this->sql->conn->errorInfo(),1));
						throw new ErrorException("Error insering wifi pointer.\r\n".var_export($this->sql->conn->errorInfo(),1));
					}
					$ap_id = $this->sql->conn->lastInsertId();
					$imported_aps[] = $ap_id.":0";
					$NewAPs++;
					$newhashes[$ap_hash] = 1;
					$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);
					#//$this->logd("Inserted APs pointer. {".$this->sql->conn->lastInsertId()."}");					
				}
				
				if($ap_id == ""){continue;}
				$ap_count++;
				if(isset($newhashes[$ap_hash]) && $newhashes[$ap_hash] == 1){$new = 1;}else{$new = 0;}
				
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_hist` (`AP_ID`, `GPS_ID`, `File_ID`, `Sig`, `RSSI`, `New`, `Hist_Date`) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				$preps = $this->sql->conn->prepare($sql);
				$preps->bindParam(1, $ap_id, PDO::PARAM_INT);
				$preps->bindParam(2, $gps_id, PDO::PARAM_INT);
				$preps->bindParam(3, $file_id, PDO::PARAM_INT);
				$preps->bindParam(4, $fSignal, PDO::PARAM_INT);
				$preps->bindParam(5, $fRSSI, PDO::PARAM_INT);
				$preps->bindParam(6, $new, PDO::PARAM_INT);
				$preps->bindParam(7, $GpsDate, PDO::PARAM_STR);
				$preps->execute();
				
				$hist_id = $this->sql->conn->lastInsertId();
				if($hist_id == ""){continue;}
				
				#Update High GPS, First Seen, Last Seen, High Sig, High RSSI
				if($fLat != "0.0000" && $fLon != "0.0000" && $fRSSI > -99){$fRSSIwGPS = $fRSSI;}else{$fRSSIwGPS = -99;}
				$this->UpdateHighPoints($file_importing_id, $ap_id, $GpsDate, $GpsDate, $fSignal, $fRSSI, $fRSSIwGPS);
			}
		}
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		#Finish off Import and give credit to the user.
		$imported = implode("-", $imported_aps);
		$date = date("Y-m-d H:i:s");
		
		$ret = array(
				'imported'=> $imported,
				'date'=>$date,
				'aps'=>$ap_count,
				'gps'=>$gps_count,
				'newaps'=>$NewAPs
			);
		return $ret;
	}
	
	public function import_swardriving($source="", $file_id, $file_importing_id)
	{
		if(!file_exists($source))
		{
			return array(-1, "File does not exist");
		}

		# Open the file and dump its contents into an array. probably should re think this part...
		$file_contents = @file_get_contents($source);
		$file_contents = mb_convert_encoding($file_contents, 'UTF-8', mb_detect_encoding($file_contents, 'UTF-8, ISO-8859-1', true));
		$file_contents = str_replace("\xEF\xBB\xBF",'',$file_contents);// Remove Byte Order Mark
		if($file_contents === "")
		{
			return array(-1, "File was empty, or error opening file.");
		}
		$File_return	 = explode("\n", $file_contents);
		$File_lcount = count($File_return);

		# Now lets loop through the file and see what we have.
		$this->verbosed("Compiling data from file to array:", 3);
		$imported_aps = array();
		$NewAPs = 0;
		$ap_count = 0;
		$gps_count = 0;
		
		foreach($File_return as $key => $file_line)
		{	
			# Read CSV
			$apinfo = str_getcsv ($file_line);
			
			$fBSSID = strtoupper($apinfo[0]);
			if(!$this->validateMacAddress($fBSSID)){continue;}
			$fSSID = $apinfo[1];
			$fAuthMode = $apinfo[2];
			$fchannel = $apinfo[3];
			$ffrequency = $apinfo[4];
			$fDate1 = $apinfo[5];
			$fDate2 = $apinfo[6];
			$fLat = $this->convert->all2dm(number_format($apinfo[7],7));
			$fLon = $this->convert->all2dm(number_format($apinfo[8],7));
			$fAltitudeMeters = $apinfo[9];
			$fDate = $fDate1.','.$fDate2;
			$fSignal = 0;
			$fRSSI = -99;
			
			list($authen, $encry, $sectype, $nt) = $this->convert->findCapabilities($fAuthMode);
			list($chan, $radio) = $this->convert->findFreq($ffrequency);
			
			$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
			
			$calc = "Line: ".($key+1)." / ".$File_lcount;
			if($this->sql->service == "mysql")
				{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = ? WHERE `id` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = ? WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
			$prep->bindParam(3, $file_importing_id, PDO::PARAM_INT);
			$prep->execute();
			if($this->sql->checkError() !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				//$this->logd("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}

			if (($timestamp = strtotime($fDate)) !== false) {
				$GpsDate = date("Y-m-d H:i:s", $timestamp);

				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_gps` (`File_ID`, `Lat`, `Lon`, `Alt`, `GPS_Date`) VALUES (?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_gps] ([File_ID], [Lat], [Lon], [Alt], [GPS_Date]) VALUES (?, ?, ?, ?, ?)";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1,$file_id, PDO::PARAM_INT);
				$prep->bindParam(2,$fLat, PDO::PARAM_STR);
				$prep->bindParam(3,$fLon, PDO::PARAM_STR);
				$prep->bindParam(4,$fAltitudeMeters, PDO::PARAM_STR);
				$prep->bindParam(5,$GpsDate,PDO::PARAM_STR);
				$prep->execute();
				if($this->sql->checkError())
				{
					echo "Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1);
					$this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
					throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
				}
				$gps_id = $this->sql->conn->lastInsertId();
				
				if($gps_id == ""){continue;}
				$gps_count++;
				
				if($this->sql->service == "mysql")
					{$sql = "SELECT `AP_ID`, `FLAGS` FROM `wifi_ap` WHERE `ap_hash` = ? LIMIT 1";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "SELECT TOP 1 [AP_ID], [FLAGS] FROM [wifi_ap] WHERE [ap_hash] = ?";}
				$res = $this->sql->conn->prepare($sql);
				$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$res->execute();
				$this->sql->checkError();
				$fetch = $res->fetch(2);
				$new = 0;
				$ap_id = "";
				if($fetch['AP_ID'])
				{
					$ap_id	= $fetch['AP_ID'];
					$ap_FLAGS	= $fetch['FLAGS'];
					if($ap_FLAGS == "" && $fAuthMode != "")
					{
						if($this->sql->service == "mysql")
							{$sql = "UPDATE `wifi_ap` SET `FLAGS` = ? WHERE `AP_ID` = ?";}
						else if($this->sql->service == "sqlsrv")
							{$sql = "UPDATE [wifi_ap] SET [FLAGS] = ? WHERE [AP_ID] = ?";}
						$prepu = $this->sql->conn->prepare($sql);
						$prepu->bindParam(1, $fAuthMode, PDO::PARAM_STR);
						$prepu->bindParam(2, $ap_id, PDO::PARAM_INT);
						$prepu->execute();
					}
				}
				else
				{
					if($this->sql->service == "mysql")
						{$sql = "INSERT INTO `wifi_ap` (`BSSID`, `SSID`, `CHAN`, `AUTH`, `ENCR`, `SECTYPE`, `RADTYPE`, `NETTYPE`, `FLAGS`, `ap_hash`, `File_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "INSERT INTO [wifi_ap] ([BSSID], [SSID], [CHAN], [AUTH], [ENCR], [SECTYPE], [RADTYPE], [NETTYPE], [FLAGS], [ap_hash], [File_ID]) VALUES (?,?,?,?,?,?,?,?,?,?,?)";}
					$prep = $this->sql->conn->prepare($sql);
					#var_dump($aps);
					$prep->bindParam(1, $fBSSID, PDO::PARAM_STR);
					$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
					$prep->bindParam(3, $chan, PDO::PARAM_INT);
					$prep->bindParam(4, $authen, PDO::PARAM_STR);		
					$prep->bindParam(5, $encry, PDO::PARAM_STR);
					$prep->bindParam(6, $sectype, PDO::PARAM_INT);
					$prep->bindParam(7, $radio, PDO::PARAM_STR);
					$prep->bindParam(8, $nt, PDO::PARAM_STR);
					$prep->bindParam(9, $fAuthMode, PDO::PARAM_STR);
					$prep->bindParam(10, $ap_hash, PDO::PARAM_STR);
					$prep->bindParam(11, $file_id, PDO::PARAM_INT);
					$prep->execute();
					if($this->sql->checkError())
					{
						$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
						//$this->logd("Error insering wifi pointer. ".var_export($this->sql->conn->errorInfo(),1));
						throw new ErrorException("Error insering wifi pointer.\r\n".var_export($this->sql->conn->errorInfo(),1));
					}
					$ap_id = $this->sql->conn->lastInsertId();
					$imported_aps[] = $ap_id.":0";
					$new = 1;
					$NewAPs++;
					$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);
					#//$this->logd("Inserted APs pointer. {".$this->sql->conn->lastInsertId()."}");					
				}
				
				if($ap_id == ""){continue;}
				$ap_count++;

				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_hist` (`AP_ID`, `GPS_ID`, `File_ID`, `Sig`, `RSSI`, `New`, `Hist_Date`) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				$preps = $this->sql->conn->prepare($sql);
				$preps->bindParam(1, $ap_id, PDO::PARAM_INT);
				$preps->bindParam(2, $gps_id, PDO::PARAM_INT);
				$preps->bindParam(3, $file_id, PDO::PARAM_INT);
				$preps->bindParam(4, $fSignal, PDO::PARAM_INT);
				$preps->bindParam(5, $fRSSI, PDO::PARAM_INT);
				$preps->bindParam(6, $new, PDO::PARAM_INT);
				$preps->bindParam(7, $GpsDate, PDO::PARAM_STR);
				$preps->execute();
				
				$hist_id = $this->sql->conn->lastInsertId();
				if($hist_id == ""){continue;}
				
				#Update High GPS, First Seen, Last Seen, High Sig, High RSSI
				if($fLat != "0.0000" && $fLon != "0.0000" && $fRSSI > -99){$fRSSIwGPS = $fRSSI;}else{$fRSSIwGPS = -99;}
				$this->UpdateHighPoints($file_importing_id, $ap_id, $GpsDate, $GpsDate, $fSignal, $fRSSI, $fRSSIwGPS);
					
			}
		}
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		#Finish off Import and give credit to the user.
		$imported = implode("-", $imported_aps);
		$date = date("Y-m-d H:i:s");
		
		$ret = array(
				'imported'=> $imported,
				'date'=>$date,
				'aps'=>$ap_count,
				'gps'=>$gps_count,
				'newaps'=>$NewAPs
			);
		return $ret;
	}

	public function import_vs1($source="", $file_id, $file_importing_id)
	{
		if(!file_exists($source))
		{
			return array(-1, "File does not exist");
		}
		
		$increment_ids = 0;
		$apdata = array();
		$gdata = array();
		# We need to check and see if the file location was passed, if not fail gracefully.
		if($source == NULL)
		{
			//$this->logd("The file that needs to be imported was not included in the import function.", "Error");
			$this->verbosed("The file that needs to be imported was not included in the import function", -1);
			throw new ErrorException;
		}
		# Open the file and dump its contents into an array. probably should re think this part...
		$file_contents = @file_get_contents($source);
		$file_contents = mb_convert_encoding($file_contents, 'UTF-8', mb_detect_encoding($file_contents, 'UTF-8, ISO-8859-1', true));// Ensures content is UTF-8
		$file_contents = str_replace("\xEF\xBB\xBF",'',$file_contents);// Remove Byte Order Mark
		if($file_contents === "")
		{
			return array(-1, "File was empty, or error opening file.");
		}
		$File_return	 = explode("\r\n", $file_contents);

		# Now lets loop through the file and see what we have.
		$this->verbosed("Compiling data from file to array:", 3);
		foreach($File_return as $key => $file_line)
		{
			#Skip empty line
			if($file_line == ""){continue;}
			
			#Skip commended line
			$first_char = mb_substr(trim($file_line),0,1);
			if($first_char == "#"){continue;}
			
			#Split data line
			$file_line_exp = explode("|",$file_line);
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
					$gps_date = $gps_line[4];
					list($s1,$s2,$s3) = explode("-",$gps_date);
					if (strlen($s1) == 2){$gps_date = $s3.'-'.$s1.'-'.$s2;}
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
								'date'	=>  $gps_date,
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
					$gps_date = $gps_line[10];
					list($s1,$s2,$s3) = explode("-",$gps_date);
					if (strlen($s1) == 2){$gps_date = $s3.'-'.$s1.'-'.$s2;}
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
								'date'	=>  $gps_date,
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
					if($highestSignal == ""){$highestSignal = 0;}
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
					
					
					if(is_numeric($ap_line[10]))#Check if line 10 id HighSig or Manufacturer
					{
						#Detailed Export Version 4.0, Current vistumbler format (correctly formatted)
						$apdata[] = array(
							'ap_hash'   => "",
							'ssid'	  =>  $ap_line[0],
							'mac'	   =>  $ap_line[1],
							'manuf'	 =>  $this->findManuf($ap_line[1]),
							'auth'	  =>  $ap_line[3],
							'encry'	 =>  $ap_line[4],
							'sectype'   =>  (int) $ap_line[5],
							'radio'	 =>  $ap_line[6],
							'chan'	  =>  (int) $ap_line[7],
							'btx'	   =>  $ap_line[8],
							'otx'	   =>  $ap_line[9],
							'HighSig'   =>  $ap_line[10],
							'HighRSSI'  =>  $ap_line[11],
							'nt'		=>  $ap_line[12],
							'label'	 =>  $ap_line[13],
							'signals'   =>  $ap_line[14]
							);
					}
					else if(is_numeric($ap_line[7]))
					{
						#Detailed Export Version 4.0, Vistumbler v10.6 Beta 16.2 (incorrectly formatted)
						$highestRSSI = $this->convert->Sig2dBm($ap_line[2]);
						$apdata[] = array(
							'ap_hash'   => "",
							'ssid'	  =>  $ap_line[0],
							'mac'	   =>  $ap_line[1],
							'HighSig'   =>  $ap_line[2],
							'auth'	  =>  $ap_line[3],
							'encry'	 =>  $ap_line[4],
							'sectype'   =>  (int) $ap_line[5],
							'radio'	 =>  $ap_line[6],
							'chan'	  =>  (int) $ap_line[7],
							'btx'	   =>  $ap_line[8],
							'otx'	   =>  $ap_line[9],
							'manuf'	 =>  $this->findManuf($ap_line[1]),
							'label'	 =>  $ap_line[11],
							'nt'		=>  $ap_line[12],
							'HighRSSI'  =>  $highestRSSI,
							'signals'   =>  $ap_line[14]
							);
					}
					else if(is_numeric($ap_line[11]))
					{
						#Detailed Export Version 4.0,RanInt WiFiDB Alpha (incorrectly formatted)
						$highestRSSI = $this->convert->Sig2dBm($ap_line[2]);
						$apdata[] = array(
							'ap_hash'   => "",
							'ssid'	  =>  $ap_line[0],
							'mac'	   =>  $ap_line[1],
							'HighSig'   =>  $ap_line[2],
							'label'	 =>  $ap_line[3],
							'auth'	  =>  $ap_line[4],
							'sectype'   =>  (int) $ap_line[5],
							'encry'	 =>  $ap_line[6],
							'radio'	 =>  $ap_line[7],
							'chan'	  =>  (int) $ap_line[8],
							'otx'	   =>  $ap_line[9],
							'manuf'	 =>  $this->findManuf($ap_line[1]),
							'HighRSSI'  =>  $ap_line[11],
							'btx'	   =>  $ap_line[12],
							'nt'		=>  $ap_line[13],
							'signals'   =>  $ap_line[14]
							);
					}
					$this->rssi_signals_flag = 1;
					break;

				default:
					echo "Import Line Error---------------\r\n";
					echo $file_line."\r\n";
					echo "--------------------------------\r\n";
					//$this->logd("Error parsing File.\r\n".var_export($file_line_alt, 1), "Error");
					$this->verbosed($file_line_exp_count."\r\nummm.... wrong number of columns... I'm going to ignore this line:/\r\n", -1);
					break;
			}
		}
		if(count($apdata) === 0)
		{
			$this->verbosed("File did not have an valid AP data, dropping file.", -1);
			//$this->logd("File did not have an valid AP data, dropping file.", "Warning");
			return array(-1, "File does not have any valid AP data.");
		}
		if(count($gdata) === 0)
		{
			$this->verbosed("File did not have an valid GPS data, dropping file.", -1);
			//$this->logd("File did not have an valid GPS data, dropping file.", "Warning");
			return array(-1, "File does not have any valid GPS data.");
		}

		$vs1data = array('gpsdata'=>$gdata, 'apdata'=>$apdata);
		$ap_count = count($vs1data['apdata']);
		$gps_count = count($vs1data['gpsdata']);
		$this->verbosed("Importing GPS data [$gps_count]", 2);
		foreach($vs1data['gpsdata'] as $key=>$gps)
		{
			$calc = "GPS: ".($key+1)." / ".$gps_count;
			if($this->sql->service == "mysql")
				{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = 'Importing GPS Data' WHERE `id` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = 'Importing GPS Data' WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
			$prep->execute();
			if($this->sql->checkError() !== 0)
			{
				$this->verbosed("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), -1);
				//$this->logd("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Error Updating Temp Files Table for current GPS.\r\n".var_export($this->sql->conn->errorInfo(),1));
			}

			if($this->sql->service == "mysql")
				{$sql = "INSERT INTO `wifi_gps` (`File_ID`, `File_GPS_ID`, `Lat`, `Lon`, `NumOfSats`, `HorDilPitch`, `Alt`, `Geo`, `KPH`, `MPH`, `TrackAngle`, `GPS_Date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "INSERT INTO [wifi_gps] ([File_ID], [File_GPS_ID], [Lat], [Lon], [NumOfSats], [HorDilPitch], [Alt], [Geo], [KPH], [MPH], [TrackAngle], [GPS_Date]) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}
			$datetime = $gps['date']." ".$gps['time'];
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1,$file_id, PDO::PARAM_INT);
			$prep->bindParam(2,$gps['id'], PDO::PARAM_INT);
			$prep->bindParam(3,$gps['lat'], PDO::PARAM_STR);
			$prep->bindParam(4,$gps['long'], PDO::PARAM_STR);
			$prep->bindParam(5,$gps['sats'],PDO::PARAM_INT);
			$prep->bindParam(6,$gps['hdp'],PDO::PARAM_STR);
			$prep->bindParam(7,$gps['alt'],PDO::PARAM_STR);
			$prep->bindParam(8,$gps['geo'],PDO::PARAM_STR);
			$prep->bindParam(9,$gps['kmh'],PDO::PARAM_STR);
			$prep->bindParam(10,$gps['mph'],PDO::PARAM_STR);
			$prep->bindParam(11,$gps['track'],PDO::PARAM_STR);
			$prep->bindParam(12,$datetime,PDO::PARAM_STR);
			$prep->execute();
			if($this->sql->checkError())
			{
				$this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
				//$this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
				throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
			}
			//$r = $this->RotateSpinner($r);
		}

		$this->verbosed("Importing AP Data [$ap_count]:", 2);
		$imported_aps = array();
		$NewAPs = 0;
		foreach($vs1data['apdata'] as $key=>$aps)
		{
			$ap_num = $key + 1;
			$calc = "AP: ".($ap_num)." / ".$ap_count;
			if($this->sql->service == "mysql")
				{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = ? WHERE `id` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = ? WHERE [id] = ?";}
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $calc, PDO::PARAM_STR);
			$prep->bindParam(2, $aps['ssid'], PDO::PARAM_STR);
			$prep->bindParam(3, $file_importing_id, PDO::PARAM_INT);
			$prep->execute();

			$ap_hash = md5($aps['ssid'].$aps['mac'].$aps['chan'].$aps['sectype'].$aps['auth'].$aps['encry']);

			if(strlen($aps['ssid']) < 7){$pad_ssid = 20;}else{$pad_ssid = strlen($aps['ssid']);}
			if(strlen($aps['chan']) < 7){$pad_chan = 20;}else{$pad_chan = strlen($aps['chan']);}
			if(strlen($aps['radio']) < 7){$pad_radio = 20;}else{$pad_radio = strlen($aps['radio']);}
			if(strlen($aps['encry']) < 7){$pad_encr = 20;}else{$pad_encr = strlen($aps['encry']);}
			$ssid = str_pad($aps['ssid'], $pad_ssid);
			$chan = str_pad($aps['chan'], $pad_chan);
			$radio = str_pad($aps['radio'], $pad_radio);
			$encry = str_pad($aps['encry'], $pad_encr);
			$this->verbosed("------------------------
			File AP/Total: {$ap_num}/{$ap_count}
			SSID:  {$ssid} | MAC: {$aps['mac']}
			CHAN:  {$chan} | SECTYPE: {$aps['sectype']}
			RADIO: {$radio}| AUTH: {$aps['auth']}
			ENCRY: {$encry}| APHASH:".$ap_hash, 1);
			#//$this->logd("Starting Import of AP ({$ap_hash}), {$aps['ssid']} ");

			if($this->sql->service == "mysql")
				{$sql = "SELECT `AP_ID`, `RADTYPE` FROM `wifi_ap` WHERE `ap_hash` = ? LIMIT 1";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 [AP_ID], [RADTYPE] FROM [wifi_ap] WHERE [ap_hash] = ?";}
			$res = $this->sql->conn->prepare($sql);
			$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
			$res->execute();
			$this->sql->checkError();

			$fetch = $res->fetch(2);
			$new = 0;
			$ap_id = 0;
			if($fetch['AP_ID'])
			{
				$ap_id	= $fetch['AP_ID'];
				#The Vistumbler radio type is more accurate. Use this files radio type if it is different from whats in the db.
				$ap_RADTYPE	= $fetch['RADTYPE'];
				if($aps['radio'] != $ap_RADTYPE && $aps['radio'] != "")
				{
					if($this->sql->service == "mysql")
						{$sql = "UPDATE `wifi_ap` SET `RADTYPE` = ? WHERE `AP_ID` = ?";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "UPDATE [wifi_ap] SET [RADTYPE] = ? WHERE [AP_ID] = ?";}
					$prepu = $this->sql->conn->prepare($sql);
					$prepu->bindParam(1, $aps['radio'], PDO::PARAM_STR);
					$prepu->bindParam(2, $ap_id, PDO::PARAM_INT);
					$prepu->execute();
				}
			}
			else
			{
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_ap` (`BSSID`, `SSID`, `CHAN`, `AUTH`, `ENCR`, `SECTYPE`, `RADTYPE`, `NETTYPE`, `BTX`, `OTX`, `ap_hash`, `File_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_ap] ([BSSID], [SSID], [CHAN], [AUTH], [ENCR], [SECTYPE], [RADTYPE], [NETTYPE], [BTX], [OTX], [ap_hash], [File_ID]) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $aps['mac'], PDO::PARAM_STR);
				$prep->bindParam(2, $aps['ssid'], PDO::PARAM_STR);
				$prep->bindParam(3, $aps['chan'], PDO::PARAM_INT);
				$prep->bindParam(4, $aps['auth'], PDO::PARAM_STR);
				$prep->bindParam(5, $aps['encry'], PDO::PARAM_STR);
				$prep->bindParam(6, $aps['sectype'], PDO::PARAM_INT);
				$prep->bindParam(7, $aps['radio'], PDO::PARAM_STR);
				$prep->bindParam(8, $aps['nt'], PDO::PARAM_STR);
				$prep->bindParam(9, $aps['btx'], PDO::PARAM_STR);
				$prep->bindParam(10, $aps['otx'], PDO::PARAM_STR);
				$prep->bindParam(11, $ap_hash, PDO::PARAM_STR);
				$prep->bindParam(12, $file_id, PDO::PARAM_INT);
				$prep->execute();
				if($this->sql->checkError())
				{
					$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Error insering wifi pointer. ".var_export($this->sql->conn->errorInfo(),1));
					throw new ErrorException("Error insering wifi pointer.\r\n".var_export($this->sql->conn->errorInfo(),1));
				}
				else
				{
					$ap_id = $this->sql->conn->lastInsertId();
					$new = 1;	
					$imported_aps[] = $ap_id.":0";
					$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);
					#//$this->logd("Inserted APs pointer. {".$this->sql->conn->lastInsertId()."}");
				}
				$NewAPs++;
			}

			$HighRSSI = -99;
			$HighRSSIwGPS = -99;
			$HighSig = 0;
			$FirstDate = "";
			$LastDate = "";
			//Import Wifi Signals
			if($this->rssi_signals_flag){$ap_sig_exp = explode("\\", $aps['signals']);}else{$ap_sig_exp = explode("-", $aps['signals']);}
			$hist_count = count($ap_sig_exp);
			$this->verbosed("Starting Import of Wifi Signal ( ".$hist_count." Signal Points )... ", 1);
			foreach($ap_sig_exp as $key2=>$sig_gps_id)
			{
				$hist_num = $key2 + 1;
				$calc = "AP: ".($ap_num)." / ".$ap_count." (".$hist_num." / ".$hist_count.")";
				if($this->sql->service == "mysql")
					{$sql = "UPDATE `files_importing` SET `tot` = ? WHERE `id` = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "UPDATE [files_importing] SET [tot] = ? WHERE [id] = ?";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $calc, PDO::PARAM_STR);
				$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
				$prep->execute();
				
				$sig_gps_exp = explode(",", $sig_gps_id);
				$file_gps_id = $sig_gps_exp[0];
				if($file_gps_id == ""){continue;}
				$signal = $sig_gps_exp[1];
				if($signal == ""){$signal = 0;}
				if($this->rssi_signals_flag){if($sig_gps_exp[2] == "Ltd."){$rssi = $this->convert->Sig2dBm($signal);}}#fix for old incorrectly formatted file 
				if($this->rssi_signals_flag){$rssi = $sig_gps_exp[2];}else{$rssi = $this->convert->Sig2dBm($signal);}
				if($rssi > $HighRSSI){$HighRSSI = $rssi;}
				if($signal > $HighSig){$HighSig = $signal;}
				
				
				if($this->sql->service == "mysql")
					{$GID_SQL = "SELECT `GPS_ID`, `GPS_Date`, `Lat`, `Lon` FROM `wifi_gps` WHERE `File_ID` = ? AND `File_GPS_ID` = ? LIMIT 1";}
				else if($this->sql->service == "sqlsrv")
					{$GID_SQL = "SELECT TOP 1 [GPS_ID], [GPS_Date], [Lat], [Lon] FROM [wifi_gps] WHERE [File_ID] = ? AND [File_GPS_ID] = ?";}
				$gidprep = $this->sql->conn->prepare($GID_SQL);
				$gidprep->bindParam(1, $file_id, PDO::PARAM_INT);
				$gidprep->bindParam(2, $file_gps_id, PDO::PARAM_INT);
				$gidprep->execute();
				$fetchgidprep = $gidprep->fetch(2);
				$gps_id = $fetchgidprep['GPS_ID'];
				$datetime = $fetchgidprep['GPS_Date'];
				$gps_lat = $fetchgidprep['Lat'];
				$gps_lon = $fetchgidprep['Lat'];
				if($gps_id == ""){continue;}
				if ($FirstDate > $datetime || $FirstDate == ""){$FirstDate = $datetime;}
				if ($LastDate < $datetime || $LastDate == ""){$LastDate = $datetime;}
				if ($gps_lat != "0.0000" && $gps_lon != "0.0000" && $rssi > $HighRSSIwGPS){$HighRSSIwGPS = $rssi;}

				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_hist` (`AP_ID`, `GPS_ID`, `File_ID`, `Sig`, `RSSI`, `New`, `Hist_Date`) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				$preps = $this->sql->conn->prepare($sql);
				$preps->bindParam(1, $ap_id, PDO::PARAM_INT);
				$preps->bindParam(2, $gps_id, PDO::PARAM_INT);
				$preps->bindParam(3, $file_id, PDO::PARAM_INT);
				$preps->bindParam(4, $signal, PDO::PARAM_INT);
				$preps->bindParam(5, $rssi, PDO::PARAM_INT);
				$preps->bindParam(6, $new, PDO::PARAM_INT);
				$preps->bindParam(7, $datetime, PDO::PARAM_STR);
				$preps->execute();

				if($this->sql->checkError() !== 0)
				{
					$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
					throw new ErrorException("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
				}

				#$r = $this->RotateSpinner($r);
			}
			
			#Update High GPS, First Seen, Last Seen, High Sig, High RSSI
			$this->UpdateHighPoints($file_importing_id, $ap_id, $FirstDate, $LastDate, $HighSig, $HighRSSI, $HighRSSIwGPS);
		}
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		#Finish off Import and give credit to the user.
		$imported = implode("-", $imported_aps);
		$date = date("Y-m-d H:i:s");

		$ret = array(
				'imported'=> $imported,
				'date'=>$date,
				'aps'=>$ap_count,
				'gps'=>$gps_count,
				'newaps'=>$NewAPs
			);
		return $ret;
	}
	
	public function import_vistumblermdb($source="", $file_id, $file_importing_id)
	{
		if(!file_exists($source))
		{
			return array(-1, "File does not exist");
		}
		
		$apdata = array();
		$gdata = array();
		$hdata = array();
		$imported_aps = array();
		$NewAPs = 0;
		$ap_count = 0;
		$gps_count = 0;
		
		$table = "GPS";
		$command = '/usr/bin/mdb-export '.$source.' '.$table.' 2>&1';
		$output = shell_exec($command);
		$gps_return	 = explode("\n", $output);
		$gps_lcount = count($gps_return);
		foreach ($gps_return as $key => $gps)
		{
			if($key==0){continue;}
			$GPS_Arr = str_getcsv ($gps);
			$GPS_Row_Count = count($GPS_Arr);
			if($GPS_Row_Count == 12)
			{
				
				$og_id = (int) $GPS_Arr[0];
				$og_lat = $this->convert->all2dm($GPS_Arr[1]);
				$og_lon = $this->convert->all2dm($GPS_Arr[2]);
				$og_sats = (int) $GPS_Arr[3];
				$og_hdp = (float) $GPS_Arr[4];
				$og_alt = (float) $GPS_Arr[5];
				$og_geo = (float) $GPS_Arr[6];
				$og_kmh = (float) $GPS_Arr[7];
				$og_mph = (float) $GPS_Arr[8];
				$og_track = (float) $GPS_Arr[9];
				$og_datetime = $GPS_Arr[10]." ".$GPS_Arr[11];
				
				$calc = "GPS: ".($key+1)." / ".$gps_lcount;
				if($this->sql->service == "mysql")
					{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = 'Importing GPS Data' WHERE `id` = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = 'Importing GPS Data' WHERE [id] = ?";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $calc, PDO::PARAM_STR);
				$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
				$prep->execute();

				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO `wifi_gps` (`File_ID`, `File_GPS_ID`, `Lat`, `Lon`, `NumOfSats`, `HorDilPitch`, `Alt`, `Geo`, `KPH`, `MPH`, `TrackAngle`, `GPS_Date`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_gps] ([File_ID], [File_GPS_ID], [Lat], [Lon], [NumOfSats], [HorDilPitch], [Alt], [Geo], [KPH], [MPH], [TrackAngle], [GPS_Date]) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1,$file_id, PDO::PARAM_INT);
				$prep->bindParam(2,$og_id, PDO::PARAM_INT);
				$prep->bindParam(3,$og_lat, PDO::PARAM_STR);
				$prep->bindParam(4,$og_lon, PDO::PARAM_STR);
				$prep->bindParam(5,$og_sats,PDO::PARAM_INT);
				$prep->bindParam(6,$og_hdp,PDO::PARAM_STR);
				$prep->bindParam(7,$og_alt,PDO::PARAM_STR);
				$prep->bindParam(8,$og_geo,PDO::PARAM_STR);
				$prep->bindParam(9,$og_kmh,PDO::PARAM_STR);
				$prep->bindParam(10,$og_mph,PDO::PARAM_STR);
				$prep->bindParam(11,$og_track,PDO::PARAM_STR);
				$prep->bindParam(12,$og_datetime,PDO::PARAM_STR);
				$prep->execute();
				if($this->sql->checkError())
				{
					$this->verbosed("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), -1);
					//$this->logd("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1), "Error");
					throw new ErrorException("Failed Insert of GPS.".var_export($this->sql->conn->errorInfo(),1));
				}
				$new_gps_id = $this->sql->conn->lastInsertId();
				$gdata[$og_id] = array(
							'old_gps_id'	=>  (int) $og_id,
							'new_gps_id'	=>  (int) $new_gps_id,
							'lat'	=>  (int) $og_lat,
							'lon'	=>  (int) $og_lon
				);
				$gps_count++;
			}
		}
		
		$table = "AP";
		$command = '/usr/bin/mdb-export '.$source.' '.$table.' 2>&1';
		$output = shell_exec($command);
		$ap_return	 = explode("\n", $output);
		$ap_lcount = count($ap_return);
		foreach ($ap_return as $key => $ap)
		{
			if($key==0){continue;}	
			$AP_Arr = str_getcsv ($ap);
			$AP_Row_Count = count($AP_Arr);
			if($AP_Row_Count == 31)
			{
				$oa_id = $AP_Arr[0];
				$oa_mac = $AP_Arr[3];
				$oa_ssid = $AP_Arr[4];
				$oa_chan = (int) $AP_Arr[5];
				$oa_auth = $AP_Arr[6];
				$oa_encry = $AP_Arr[7];
				$oa_sectype = (int) $AP_Arr[8];	
				$oa_nt = $AP_Arr[9];
				$oa_radio = $AP_Arr[10];
				$oa_btx = $AP_Arr[11];
				$oa_otx = $AP_Arr[12];
				$ap_hash = md5($oa_ssid.$oa_mac.$oa_chan.$oa_sectype.$oa_auth.$oa_encry);
				
				$calc = "AP: ".($key+1)." / ".$ap_lcount;
				if($this->sql->service == "mysql")
					{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = ? WHERE `id` = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = ? WHERE [id] = ?";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $calc, PDO::PARAM_STR);
				$prep->bindParam(2, $oa_ssid, PDO::PARAM_STR);
				$prep->bindParam(3, $file_importing_id, PDO::PARAM_INT);
				$prep->execute();
				
				if($this->sql->service == "mysql")
					{$sql = "SELECT `AP_ID`, `BTX`, `OTX` FROM `wifi_ap` WHERE `ap_hash` = ? LIMIT 1";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "SELECT TOP 1 [AP_ID], [BTX], [OTX] FROM [wifi_ap] WHERE [ap_hash] = ?";}
				$res = $this->sql->conn->prepare($sql);
				$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
				$res->execute();
				$this->sql->checkError();
				$fetch = $res->fetch(2);
				$new = 0;
				$ap_id = 0;
				if($fetch['AP_ID'])
				{
					$ap_id	= $fetch['AP_ID'];
					$ap_BTX	= $fetch['BTX'];
					$ap_OTX	= $fetch['OTX'];
					if(($ap_BTX == "" && $oa_btx != "") || ($ap_OTX == "" && $oa_otx != ""))
					{
						if($this->sql->service == "mysql")
							{$sql = "UPDATE `wifi_ap` SET `BTX` = ?, `OTX` = ? WHERE `AP_ID` = ?";}
						else if($this->sql->service == "sqlsrv")
							{$sql = "UPDATE [wifi_ap] SET [BTX] = ?, [OTX] = ? WHERE [AP_ID] = ?";}
						$prepu = $this->sql->conn->prepare($sql);
						$prepu->bindParam(1, $oa_btx, PDO::PARAM_STR);
						$prepu->bindParam(2, $oa_otx, PDO::PARAM_STR);
						$prepu->bindParam(3, $ap_id, PDO::PARAM_INT);
						$prepu->execute();
					}
				}
				else
				{
					if($this->sql->service == "mysql")
						{$sql = "INSERT INTO `wifi_ap` (`BSSID`, `SSID`, `CHAN`, `AUTH`, `ENCR`, `SECTYPE`, `RADTYPE`, `NETTYPE`, `BTX`, `OTX`, `ap_hash`, `File_ID`) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "INSERT INTO [wifi_ap] ([BSSID], [SSID], [CHAN], [AUTH], [ENCR], [SECTYPE], [RADTYPE], [NETTYPE], [BTX], [OTX], [ap_hash], [File_ID]) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";}	
					$prep = $this->sql->conn->prepare($sql);
					#var_dump($aps);
					$prep->bindParam(1, $oa_mac, PDO::PARAM_STR);
					$prep->bindParam(2, $oa_ssid, PDO::PARAM_STR);
					$prep->bindParam(3, $oa_chan, PDO::PARAM_INT);
					$prep->bindParam(4, $oa_auth, PDO::PARAM_STR);
					$prep->bindParam(5, $oa_encry, PDO::PARAM_STR);
					$prep->bindParam(6, $oa_sectype, PDO::PARAM_INT);
					$prep->bindParam(7, $oa_radio, PDO::PARAM_STR);
					$prep->bindParam(8, $oa_nt, PDO::PARAM_STR);
					$prep->bindParam(9, $oa_btx, PDO::PARAM_STR);
					$prep->bindParam(10, $oa_otx, PDO::PARAM_STR);
					$prep->bindParam(11, $ap_hash, PDO::PARAM_STR);
					$prep->bindParam(12, $file_id, PDO::PARAM_INT);
					$prep->execute();
					if($this->sql->checkError())
					{
						$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
						//$this->logd("Error insering wifi pointer. ".var_export($this->sql->conn->errorInfo(),1));
						throw new ErrorException("Error insering wifi pointer.\r\n".var_export($this->sql->conn->errorInfo(),1));
					}
					else
					{
						$ap_id = $this->sql->conn->lastInsertId();
						$new = 1;	
						$imported_aps[] = $ap_id.":0";
						$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);
						#//$this->logd("Inserted APs pointer. {".$this->sql->conn->lastInsertId()."}");
					}
					$NewAPs++;
				}

				
				$apdata[$oa_id] = array(
					'new'	=>  (int) $new,
					'old_ap_id'	=>  (int) $oa_id,
					'new_ap_id'	=>  (int) $ap_id
				);
				$ap_count++;
			}
		}
		
		$table = "Hist";
		$command = '/usr/bin/mdb-export '.$source.' '.$table.' 2>&1';
		$output = shell_exec($command);
		$hist_return	 = explode("\n", $output);
		$hist_lcount = count($hist_return);
		foreach ($hist_return as $key => $hist)
		{
			if($key==0){continue;}	
			$Hist_Arr = str_getcsv ($hist);
			$Hist_Row_Count = count($Hist_Arr);
			if($Hist_Row_Count == 7)
			{
				$oh_id = (int) $Hist_Arr[0];
				$oh_ap_id = (int) $Hist_Arr[1];
				$oh_gps_id = (int) $Hist_Arr[2];
				$oh_sig = (int) $Hist_Arr[3];
				$oh_rssi = (int) $Hist_Arr[4];
				$oh_datetime = $Hist_Arr[5]." ".$Hist_Arr[6];
				
				$gps_id_arr = $gdata[$oh_gps_id];
				$new_gps_id = $gps_id_arr['new_gps_id'];
				$gps_lat = $gps_id_arr['lat'];
				$gps_lon = $gps_id_arr['lon'];
				
				$ap_id_arr = $apdata[$oh_ap_id];
				$new_ap_id = $ap_id_arr['new_ap_id'];
				$new = $ap_id_arr['new'];
				
				$calc = "HIST: ".($key+1)." / ".$hist_lcount;
				if($this->sql->service == "mysql")
					{$sql = "UPDATE `files_importing` SET `tot` = ?, `ap` = 'Importing HIST Data' WHERE `id` = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = 'Importing HIST Data' WHERE [id] = ?";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $calc, PDO::PARAM_STR);
				$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
				$prep->execute();
				
				if($gps_id_arr != "" && $new_ap_id != "")
				{
					$harr = $hdata[$new_ap_id];
					$FirstDate = $harr['FirstDate'];
					$LastDate = $harr['LastDate'];
					$HighRSSI = $harr['HighRSSI'];
					$HighRSSIwGPS = $harr['HighRSSI'];
					$HighSig = $harr['HighSig'];
					if($HighRSSI == ""){$HighRSSI == -99;}
					if($HighRSSIwGPS == ""){$HighRSSIwGPS == -99;}
					if($HighSig == ""){$HighSig == 0;}
						
					if($oh_datetime > $LastDate || $LastDate == ""){$LastDate = $oh_datetime;}
					if($oh_datetime < $FirstDate || $FirstDate == ""){$FirstDate = $oh_datetime;}
					if($oh_rssi > $HighRSSI){$HighRSSI = $oh_rssi;}
					if($oh_sig > $HighSig){$HighSig = $oh_sig;}
					if($gps_lat != "" && $gps_lon != "" && $gps_lat != "0.0000" && $gps_lon != "0.0000" && $oh_rssi > $HighRSSIwGPS){$HighRSSIwGPS = $oh_rssi;}

					$hdata[$new_ap_id] = array(
						'ap_id'	=>  $new_ap_id,
						'FirstDate'	=>  $FirstDate,
						'LastDate'	=>  $LastDate,
						'HighRSSI'	=>  $HighRSSI,
						'HighRSSIwGPS'	=>  $HighRSSIwGPS,
						'HighSig'	=>  $HighSig
					);
					
					if($this->sql->service == "mysql")
						{$sql = "INSERT INTO `wifi_hist` (`AP_ID`, `GPS_ID`, `File_ID`, `Sig`, `RSSI`, `New`, `Hist_Date`) VALUES (?, ?, ?, ?, ?, ?, ?)";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES (?, ?, ?, ?, ?, ?, ?)";}	
					$preps = $this->sql->conn->prepare($sql);
					$preps->bindParam(1, $new_ap_id, PDO::PARAM_INT);
					$preps->bindParam(2, $new_gps_id, PDO::PARAM_INT);
					$preps->bindParam(3, $file_id, PDO::PARAM_INT);
					$preps->bindParam(4, $oh_sig, PDO::PARAM_INT);
					$preps->bindParam(5, $oh_rssi, PDO::PARAM_INT);
					$preps->bindParam(6, $new, PDO::PARAM_INT);
					$preps->bindParam(7, $oh_datetime, PDO::PARAM_STR);
					$preps->execute();

					if($this->sql->checkError() !== 0)
					{
						$this->verbosed(var_export($this->sql->conn->errorInfo(),1), -1);
						//$this->logd("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
						throw new ErrorException("Error inserting wifi signal.\r\n".var_export($this->sql->conn->errorInfo(),1));
					}
				}
			}
		}
		
		foreach ($hdata as $key => $ap)
		{
			$ap_id = $ap['ap_id'];
			$FirstDate = $ap['FirstDate'];
			$LastDate = $ap['LastDate'];
			$HighRSSI = $ap['HighRSSI'];
			$HighRSSIwGPS = $ap['HighRSSIwGPS'];
			$HighSig = $ap['HighSig'];
			$this->UpdateHighPoints($file_importing_id, $ap_id, $FirstDate, $LastDate, $HighSig, $HighRSSI, $HighRSSIwGPS);
		}
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		#Finish off Import and give credit to the user.
		$imported = implode("-", $imported_aps);
		$date = date("Y-m-d H:i:s");

		$ret = array(
				'imported'=> $imported,
				'date'=>$date,
				'aps'=>$ap_count,
				'gps'=>$gps_count,
				'newaps'=>$NewAPs
			);
		return $ret;
	}	
	
}

