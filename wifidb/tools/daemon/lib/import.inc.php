<?PHP
/*
import.inc.php, holds the WiFiDB Importing functions.
Copyright (C) 2019 Andrew Calcutt, 2015 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
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
	 * @param string $bssid
	 * @return bool
	 */
	private function validateMacAddress($bssid = "")
	{
		return (preg_match('/([a-fA-F0-9]{2}[:|\-]?){6}/', $bssid) == 1);
	}
	
	private function ImportCellData($file_id, $file_importing_id, $cell_arr)
	{
		$cell_count = count($cell_arr);
		$this->verbosed("Importing Cell/BT Data [$cell_count]:", 2);
		$calc = "$cell_count Cells/BTs";
		$this->UpdateImportingStatus($file_importing_id, 'Importing CELL/BT Data', $calc);
		
		$NewCells = 0;
		$Cell_Num = 0;
		$Cell_Hists = 0;
		foreach($cell_arr as $key=>$cells)
		{
			$Cell_Num++;
			$cell_id = 0;
			$new = 0;
			if($this->sql->service == "sqlsrv")
			{			
				$retry = true;
				while ($retry)
				{
					try 
					{
						
						$sql = "MERGE INTO cell_id WITH (HOLDLOCK)\n"
							. "	USING (SELECT :s_mac AS mac, :s_ssid AS ssid, :s_authmode AS authmode, :s_chan AS chan, :s_type AS type) AS newcell (mac, ssid, authmode, chan, type)\n"
							. "		ON cell_id.mac = newcell.mac AND cell_id.ssid = newcell.ssid AND cell_id.authmode = newcell.authmode AND cell_id.chan = newcell.chan AND cell_id.type = newcell.type\n"
							. "	WHEN MATCHED THEN\n"
							. "		UPDATE SET cell_id.ModDate = getdate()\n"
							. "	WHEN NOT MATCHED THEN\n"
							. "		INSERT (file_id, mac, ssid, authmode, chan, type, cell_hash)\n"
							. "		VALUES (:file_id, :mac, :ssid, :authmode, :chan, :type, :cell_hash)\n"
							. 'OUTPUT INSERTED.cell_id, $action;';
							
						$prep = $this->sql->conn->prepare($sql);
						$prep->bindParam(':s_mac', $cells['bssid']);
						$prep->bindParam(':s_ssid', $cells['ssid']);
						$prep->bindParam(':s_authmode', $cells['flags']);
						$prep->bindParam(':s_chan', $cells['chan']);
						$prep->bindParam(':s_type', $cells['type']);
						$prep->bindParam(':file_id', $file_id);
						$prep->bindParam(':mac', $cells['bssid']);
						$prep->bindParam(':ssid', $cells['ssid']);
						$prep->bindParam(':authmode', $cells['flags']);
						$prep->bindParam(':chan', $cells['chan']);		
						$prep->bindParam(':type', $cells['type']);
						$prep->bindParam(':cell_hash', $cells['cell_hash']);
						
						$prep->execute();
						$addresult = $prep->fetch(2);
						$cell_id = $addresult['cell_id'];
						$ap_action = $addresult['$action'];
						if($ap_action == "INSERT")
						{
							$new = 1;
							$NewCells++;
						}
						$retry = false;
					}
					catch (Exception $e) 
					{
						$retry = $this->sql->isPDOException($this->sql->conn, $e);
						$cell_id = 0;
					}
				}
			}
			
			if($cell_id != 0)
			{
				$cell_sig_exp = explode("\\", $cells['signals']);
				$SigArrSize = count($cell_sig_exp);
				$calc = "CELL/BT: ".($Cell_Num)." / ".$cell_count." (".$SigArrSize." Points)";
				$this->UpdateImportingStatus($file_importing_id, $calc, $cells['ssid']);

				#Go through points and import them
				$this->verbosed("Starting Import of Cell Signals ( ".$SigArrSize." Signal Points )... ", 1);
				
				#Prepared statement - insert ap signal history
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO cell_hist (cell_id, gps_id, file_id, rssi, new, hist_date) VALUES ";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [cell_hist] ([cell_id], [gps_id], [file_id], [rssi], [new], [hist_date]) VALUES ";}		

				$HighRSSIwGPS = -1000;
				$Insert_Size = 0;
				$Insert_Limit = 1; //SQL Server supports a maximum of 2100 parameters. 2100 / 6 parameters = 350
				$SigCount = 0;
				$ValArray = array();
				foreach($cell_sig_exp as $key2=>$sig_gps_id)
				{
					$SigCount++;
					$Cell_Hists++;
					#Format the date
					$sig_gps_exp = explode(",", $sig_gps_id);
					$file_gps_id = $sig_gps_exp[0];
					if($file_gps_id != "")
					{
						
						$rssi = @$sig_gps_exp[1];

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
						if($gps_id != "")
						{
							if($gps_lat == ""){$gps_lat = "0.0000";}
							if($gps_lon == ""){$gps_lon = "0.0000";}
							if($gps_lat != "0.0000" && $gps_lon != "0.0000" && $rssi > $HighRSSIwGPS){$HighRSSIwGPS = $rssi;}

							$Insert_Size++;
							$ValArray[] = array((int) $cell_id, (int) $gps_id, (int) $file_id, $rssi, $new, $datetime);
						}
					}
					if($SigArrSize === $SigCount || $Insert_Size >= $Insert_Limit)
					{
						if($Insert_Size)
						{
							$paramArray = array();
							$sqlArray = array();
							foreach($ValArray as $row)// $sqlArray will look like: ["(?,?,?)", "(?,?,?)", ... ]. $paramArray will basically be a flattened version of $sig_values.
							{
								$sqlArray[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
								foreach($row as $element)
								{
									$paramArray[] = $element;
								}
							}

							$retry = true;
							while ($retry)
							{
								try {
									$sql_prep = $sql.implode(',', $sqlArray);
									echo $sql_prep."\r\n";
									var_dump($paramArray);
									$stmt = $this->sql->conn->prepare($sql_prep);
									$stmt->execute($paramArray);
									echo "Insert Size: $Insert_Size - $SigCount / $SigArrSize \r\n";
									$retry = false;
								}
								catch (Exception $e) {
									$retry = $this->sql->isPDOException($this->sql->conn, $e);
								}
							}
							
							$Insert_Size = 0;
							$ValArray = array();
						}
					}
				}
				$calc = "CELL/BT: ".($Cell_Num)." / ".$cell_count;
				$this->UpdateCellHighPoints($file_importing_id, $cell_id, $calc);
			}
		}
		$ret = array(
			'cells'=>$Cell_Num,
			'cells_hist'=>$Cell_Hists,
			'newcells'=>$NewCells
		);
		return $ret;
	}
	
	private function ImportApData($file_id, $file_importing_id, $gps_arr, $ap_arr, $override_radio = 0, $override_flags = 0)
	{
		$gps_count = count($gps_arr);
		$ap_count = count($ap_arr);
		
		$this->verbosed("Importing GPS data [$gps_count Points]", 2);
		$calc = $gps_count." GPS Points";
		$this->UpdateImportingStatus($file_importing_id, 'Importing GPS Data', $calc);

		if($this->sql->service == "mysql")
			{$sql = "INSERT INTO `wifi_gps` (`File_ID`, `File_GPS_ID`, `Lat`, `Lon`, `NumOfSats`, `AccuracyMeters`, `HorDilPitch`, `Alt`, `Geo`, `KPH`, `MPH`, `TrackAngle`, `GPS_Date`) VALUES ";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "INSERT INTO [wifi_gps] ([File_ID], [File_GPS_ID], [Lat], [Lon], [NumOfSats], [AccuracyMeters], [HorDilPitch], [Alt], [Geo], [KPH], [MPH], [TrackAngle], [GPS_Date]) VALUES ";}

		$Insert_Size = 0;
		$Insert_Limit = 150; //SQL Server supports a maximum of 2100 parameters. 2100 / 14 parameters = 150
		$lcount = 0;
		$ValArray = array();
		foreach($gps_arr as $key=>$gps)
		{
			$lcount++;
			$Insert_Size++;
			$ValArray[] = array($file_id, $gps['id'], $gps['lat'], $gps['lon'], $gps['sats'], $gps['acc'], $gps['hdp'], $gps['alt'], $gps['geo'], $gps['kmh'], $gps['mph'], $gps['track'], $gps['datetime']);
			
			if($lcount === $gps_count || $Insert_Size >= $Insert_Limit)
			{
				$paramArray = array();
				$sqlArray = array();
				foreach($ValArray as $row)// $sqlArray will look like: ["(?,?,?)", "(?,?,?)", ... ]. $paramArray will basically be a flattened version of $sig_values.
				{
					$sqlArray[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
					foreach($row as $element)
					{
						$paramArray[] = $element;
					}
				}

				$retry = true;
				while ($retry)
				{
					try {
						$sql_prep = $sql.implode(',', $sqlArray);
						//echo $sql_prep."\r\n";
						//var_dump($paramArray);
						$stmt = $this->sql->conn->prepare($sql_prep);
						$stmt->execute($paramArray);
						echo "Insert GPS Size: $Insert_Size - $lcount / $gps_count \r\n";
						$retry = false;
					}
					catch (Exception $e) {
						$retry = $this->sql->isPDOException($this->sql->conn, $e);
					}
				}

				$Insert_Size = 0;
				$ValArray = array();
			}
		}
		$this->verbosed("Importing AP Data [$ap_count]:", 2);
		$calc = $ap_count." APs";
		$this->UpdateImportingStatus($file_importing_id, 'Importing AP Data', $calc);
		$NewAPs = 0;
		$AP_Num = 0;
		foreach($ap_arr as $key=>$aps)
		{
			$AP_Num++;
			$retry = true;
			$new = 0;
			$ap_id = 0;			
			$addresult = $this->InsertAp($file_id, $aps['bssid'], $aps['ssid'], $aps['chan'], $aps['auth'], $aps['encry'], $aps['sectype'], $aps['radio'], $aps['nt'], $aps['btx'], $aps['otx'], $aps['flags']);
			if($addresult)
			{
				$ap_id = $addresult['AP_ID'];
				$ap_action = $addresult['$action'];
				$ap_RADTYPE = $addresult['RADTYPE'];
				$ap_FLAGS = $addresult['FLAGS'];
				if($ap_action == "INSERT")
				{
					$new = 1;
					$NewAPs++;
				}
				if($override_radio == 1 && $aps['radio'] != $ap_RADTYPE && $aps['radio'] != "")
				{
					$retry = true;
					while ($retry)
					{
						try 
						{
							if($this->sql->service == "mysql")
								{$sql = "UPDATE wifi_ap SET RADTYPE = ? WHERE AP_ID = ?";}
							else if($this->sql->service == "sqlsrv")
								{$sql = "UPDATE [wifi_ap] SET [RADTYPE] = ? WHERE [AP_ID] = ?";}
							$prepu = $this->sql->conn->prepare($sql);
							$prepu->bindParam(1, $aps['radio'], PDO::PARAM_STR);
							$prepu->bindParam(2, $ap_id, PDO::PARAM_INT);
							$prepu->execute();
							$retry = false;
						}
						catch (Exception $e) 
						{
							$retry = $this->sql->isPDOException($this->sql->conn, $e);
						}
					}
				}
				if($override_flags == 1 && $aps['flags'] != $ap_FLAGS && $aps['flags'] != "")
				{
					$retry = true;
					while ($retry)
					{
						try 
						{
							if($this->sql->service == "mysql")
								{$sql = "UPDATE wifi_ap SET FLAGS = ? WHERE AP_ID = ?";}
							else if($this->sql->service == "sqlsrv")
								{$sql = "UPDATE [wifi_ap] SET [FLAGS] = ? WHERE [AP_ID] = ?";}
							$prepu = $this->sql->conn->prepare($sql);
							$prepu->bindParam(1, $aps['FLAGS'], PDO::PARAM_STR);
							$prepu->bindParam(2, $ap_id, PDO::PARAM_INT);
							$prepu->execute();
							$retry = false;
						}
						catch (Exception $e) 
						{
							$retry = $this->sql->isPDOException($this->sql->conn, $e);
						}
					}
				}
			}
			
			$HighRSSIwGPS = -99;
			//Import Wifi Signals
			if($this->rssi_signals_flag){$ap_sig_exp = explode("\\", $aps['signals']);}else{$ap_sig_exp = explode("-", $aps['signals']);}
			$SigArrSize = count($ap_sig_exp);
			$calc = "AP: ".($AP_Num)." / ".$ap_count." (".$SigArrSize." Points)";
			$this->UpdateImportingStatus($file_importing_id, $calc, $aps['ssid']);

			#Go through points and import them
			$this->verbosed("Starting Import of Wifi Signal ( ".$SigArrSize." Signal Points )... ", 1);
			
			#Prepared statement - insert ap signal history
			if($this->sql->service == "mysql")
				{$sql = "INSERT INTO wifi_hist (AP_ID, GPS_ID, File_ID, Sig, RSSI, New, Hist_Date) VALUES ";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES ";}		

			$Insert_Size = 0;
			$Insert_Limit = 250; //SQL Server supports a maximum of 2100 parameters. 2100 / 8 parameters = 262.5
			$SigCount = 0;
			$ValArray = array();
			foreach($ap_sig_exp as $key2=>$sig_gps_id)
			{
				$SigCount++;
				#Format the date
				$sig_gps_exp = explode(",", $sig_gps_id);
				$file_gps_id = $sig_gps_exp[0];
				if($file_gps_id != "")
				{
					$signal = @$sig_gps_exp[1];
					$rssi = @$sig_gps_exp[2];
					if($signal == ""){$signal = 0;}
					if($this->rssi_signals_flag)
						{if(!is_numeric(@$sig_gps_exp[2])){$rssi = $this->convert->Sig2dBm($signal);}}#fix for old incorrectly formatted file}
					else
						{$rssi = $this->convert->Sig2dBm($signal);}
					if($rssi == ""){$rssi = -99;}

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
					if($gps_id != "")
					{
						if($gps_lat == ""){$gps_lat = "0.0000";}
						if($gps_lon == ""){$gps_lon = "0.0000";}
						if($gps_lat != "0.0000" && $gps_lon != "0.0000" && $rssi > $HighRSSIwGPS){$HighRSSIwGPS = $rssi;}

						$Insert_Size++;
						$ValArray[] = array($ap_id, $gps_id, $file_id, $signal, $rssi, $new, $datetime);
					}
				}
				if($SigArrSize === $SigCount || $Insert_Size >= $Insert_Limit)
				{
					if($Insert_Size)
					{
						$paramArray = array();
						$sqlArray = array();
						foreach($ValArray as $row)// $sqlArray will look like: ["(?,?,?)", "(?,?,?)", ... ]. $paramArray will basically be a flattened version of $sig_values.
						{
							$sqlArray[] = '(' . implode(',', array_fill(0, count($row), '?')) . ')';
							foreach($row as $element)
							{
								$paramArray[] = $element;
							}
						}

						$retry = true;
						while ($retry)
						{
							try {
								$sql_prep = $sql.implode(',', $sqlArray);
								//echo $sql_prep."\r\n";
								//var_dump($paramArray);
								$stmt = $this->sql->conn->prepare($sql_prep);
								$stmt->execute($paramArray);
								echo "Insert Size: $Insert_Size - $SigCount / $SigArrSize \r\n";
								$retry = false;
							}
							catch (Exception $e) {
								$retry = $this->sql->isPDOException($this->sql->conn, $e);
							}
						}
						
						$Insert_Size = 0;
						$ValArray = array();
					}
				}
			}
			#Update High GPS, First Seen, Last Seen, High Sig, High RSSI
			$this->UpdateHighPoints($file_importing_id, $ap_id, $HighRSSIwGPS, $calc);
		}
		
		$ret = array(
			'aps'=>$ap_count,
			'gps'=>$gps_count,
			'newaps'=>$NewAPs
		);
		return $ret;
	}
	
	private function InsertAp($File_ID, $BSSID, $SSID, $CHAN, $AUTH, $ENCR, $SECTYPE, $RADTYPE, $NETTYPE, $BTX, $OTX, $FLAGS)
	{
		$ap_hash = md5($SSID.$BSSID.$CHAN.$SECTYPE.$AUTH.$ENCR);
		
		if($this->sql->service == "sqlsrv")
		{			
			$retry = true;
			while ($retry)
			{
				try 
				{
					$sql = "MERGE INTO wifi_ap WITH (HOLDLOCK)\n"
						. "	USING (SELECT :s_bssid AS BSSID, :s_ssid AS SSID, :s_chan AS CHAN, :s_sectype AS SECTYPE, :s_auth AS AUTH, :s_encr AS ENCR) AS newap (BSSID, SSID, CHAN, SECTYPE, AUTH, ENCR)\n"
						. "		ON wifi_ap.BSSID = newap.BSSID AND wifi_ap.SSID = newap.SSID AND wifi_ap.CHAN = newap.CHAN AND wifi_ap.SECTYPE = newap.SECTYPE AND wifi_ap.AUTH = newap.AUTH AND wifi_ap.ENCR = newap.ENCR\n"
						. "	WHEN MATCHED THEN\n"
						. "		UPDATE SET wifi_ap.ModDate = getdate()\n"
						. "	WHEN NOT MATCHED THEN\n"
						. "		INSERT (BSSID, SSID, CHAN, AUTH, ENCR, SECTYPE, RADTYPE, NETTYPE, BTX, OTX, FLAGS, ap_hash, File_ID)\n"
						. "		VALUES (:BSSID, :SSID, :CHAN, :AUTH, :ENCR, :SECTYPE, :RADTYPE, :NETTYPE, :BTX, :OTX, :FLAGS, :ap_hash, :File_ID)\n"
						. 'OUTPUT INSERTED.AP_ID, $action, INSERTED.RADTYPE, INSERTED.FLAGS;';
						
					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(':s_bssid', $BSSID, PDO::PARAM_STR);
					$prep->bindParam(':s_ssid', $SSID, PDO::PARAM_STR);
					$prep->bindParam(':s_chan', $CHAN, PDO::PARAM_INT);
					$prep->bindParam(':s_sectype', $SECTYPE, PDO::PARAM_INT);
					$prep->bindParam(':s_auth', $AUTH, PDO::PARAM_STR);
					$prep->bindParam(':s_encr', $ENCR, PDO::PARAM_STR);
					$prep->bindParam(':BSSID', $BSSID, PDO::PARAM_STR);
					$prep->bindParam(':SSID', $SSID, PDO::PARAM_STR);
					$prep->bindParam(':CHAN', $CHAN, PDO::PARAM_INT);
					$prep->bindParam(':AUTH', $AUTH, PDO::PARAM_STR);		
					$prep->bindParam(':ENCR', $ENCR, PDO::PARAM_STR);
					$prep->bindParam(':SECTYPE', $SECTYPE, PDO::PARAM_INT);
					$prep->bindParam(':RADTYPE', $RADTYPE, PDO::PARAM_STR);
					$prep->bindParam(':NETTYPE', $NETTYPE, PDO::PARAM_STR);
					$prep->bindParam(':BTX', $BTX, PDO::PARAM_STR);
					$prep->bindParam(':OTX', $OTX, PDO::PARAM_STR);
					$prep->bindParam(':FLAGS', $FLAGS, PDO::PARAM_STR);
					$prep->bindParam(':ap_hash', $ap_hash, PDO::PARAM_STR);
					$prep->bindParam(':File_ID', $File_ID, PDO::PARAM_INT);
					$prep->execute();
					$return = $prep->fetch(2);
					$retry = false;
				}
				catch (Exception $e) 
				{
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
					$return = 0;
				}
			}
		}
		return $return;
	}
	
	private function InsertGps($File_ID, $g_id, $g_lat, $g_lon, $g_sats, $g_hdp, $g_alt, $g_geo, $g_kmh, $g_mph, $g_track, $g_AccuracyMeters, $g_datetime)
	{
		$retry = true;
		while ($retry)
		{
			try {
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO wifi_gps (File_ID, File_GPS_ID, Lat, Lon, NumOfSats, HorDilPitch, Alt, Geo, KPH, MPH, TrackAngle, AccuracyMeters, GPS_Date) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_gps] ([File_ID], [File_GPS_ID], [Lat], [Lon], [NumOfSats], [HorDilPitch], [Alt], [Geo], [KPH], [MPH], [TrackAngle], [AccuracyMeters], [GPS_Date]) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1,$File_ID, PDO::PARAM_INT);
				$prep->bindParam(2,$g_id, PDO::PARAM_INT);
				$prep->bindParam(3,$g_lat, PDO::PARAM_STR);
				$prep->bindParam(4,$g_lon, PDO::PARAM_STR);
				$prep->bindParam(5,$g_sats,PDO::PARAM_INT);
				$prep->bindParam(6,$g_hdp,PDO::PARAM_STR);
				$prep->bindParam(7,$g_alt,PDO::PARAM_STR);
				$prep->bindParam(8,$g_geo,PDO::PARAM_STR);
				$prep->bindParam(9,$g_kmh,PDO::PARAM_STR);
				$prep->bindParam(10,$g_mph,PDO::PARAM_STR);
				$prep->bindParam(11,$g_track,PDO::PARAM_STR);
				$prep->bindParam(12,$g_AccuracyMeters, PDO::PARAM_STR);
				$prep->bindParam(13,$g_datetime,PDO::PARAM_STR);
				$prep->execute();
				$return = $this->sql->conn->lastInsertId();
				$retry = false;
			}
			catch (Exception $e) 
			{
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
				$return = 0;
			}
		}
		return $return;
	}
	
	private function InsertHist($File_ID, $ap_id, $gps_id, $fSignal, $fRSSI, $new, $fDate)
	{
		$retry = true;
		while ($retry)
		{
			try 
			{
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO wifi_hist (AP_ID, GPS_ID, File_ID, Sig, RSSI, New, Hist_Date) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [wifi_hist] ([AP_ID], [GPS_ID], [File_ID], [Sig], [RSSI], [New], [Hist_Date]) VALUES (?, ?, ?, ?, ?, ?, ?)";}
				$preps = $this->sql->conn->prepare($sql);
				$preps->bindParam(1, $ap_id, PDO::PARAM_INT);
				$preps->bindParam(2, $gps_id, PDO::PARAM_INT);
				$preps->bindParam(3, $File_ID, PDO::PARAM_INT);
				$preps->bindParam(4, $fSignal, PDO::PARAM_INT);
				$preps->bindParam(5, $fRSSI, PDO::PARAM_INT);
				$preps->bindParam(6, $new, PDO::PARAM_INT);
				$preps->bindParam(7, $fDate, PDO::PARAM_STR);
				$preps->execute();
				$return = $this->sql->conn->lastInsertId();
				$retry = false;
			}
			catch (Exception $e)
			{
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
				$return = 0;
			}
		}
		return $return;
	}
	
	private function UpdateImportingStatus($file_importing_id, $calc, $fSSID)
	{
		$retry = true;
		while ($retry)
		{
			try 
			{
				if($this->sql->service == "mysql")
					{$sql = "UPDATE files_importing SET tot = ?, ap = ? WHERE id = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "UPDATE [files_importing] SET [tot] = ?, [ap] = ? WHERE [id] = ?";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $calc, PDO::PARAM_STR);
				$prep->bindParam(2, $fSSID, PDO::PARAM_STR);
				$prep->bindParam(3, $file_importing_id, PDO::PARAM_INT);
				$prep->execute();
				$retry = false;
			}
			catch (Exception $e) 
			{
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
			}
		}
	}
	
	private function UpdateFileValidGPS($file_id)
	{
		$retry = true;
		while ($retry)
		{
			try {
				#Find if file had Valid GPS
				if($this->sql->service == "mysql")
				{
					$sql = "SELECT wifi_hist.Hist_ID\n"
						. "FROM wifi_hist\n"
						. "LEFT JOIN wifi_gps ON wifi_hist.GPS_ID = wifi_gps.GPS_ID\n"
						. "WHERE wifi_hist.File_ID = ? And wifi_gps.GPS_ID IS NOT NULL And wifi_gps.Lat != '0.0000'\n"
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
				$retry = false;
			}
			catch (Exception $e) {
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
			}
		}
		$prepvgps_fetch = $prepvgps->fetch(2);
		if($prepvgps_fetch)
		{
			$ValidGPS = 1;
			
			$retry = true;
			while ($retry)
			{
				try {
					$sql = "UPDATE files SET ValidGPS = ? WHERE id = ?";
					$prepvgpsu = $this->sql->conn->prepare($sql);
					$prepvgpsu->bindParam(1, $ValidGPS, PDO::PARAM_INT);
					$prepvgpsu->bindParam(2, $file_id, PDO::PARAM_INT);
					$prepvgpsu->execute();
					$retry = false;
				}
				catch (Exception $e) {
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
				}
			}

		}
	}
	
	private function UpdateHighPoints($file_importing_id, $ap_id, $HighRSSIwGPS = -99, $msg = "")
	{
		if($HighRSSIwGPS == ""){$HighRSSIwGPS = -99;}
		
		#Get AP Info, SSID and old High GPS RSSI value
		$retry = true;
		while ($retry)
		{
			try {
				$sql = "SELECT SSID, high_gps_rssi FROM wifi_ap WHERE AP_ID = ?";
				$gaip = $this->sql->conn->prepare($sql);
				$gaip->bindParam(1, $ap_id, PDO::PARAM_INT);
				$gaip->execute();
				$retry = false;
			}
			catch (Exception $e) {
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
			}
		}
		$fetchgai = $gaip->fetch(2);
		$ssid = $fetchgai['SSID'];
		$high_gps_rssi = $fetchgai['high_gps_rssi'];

		#Update Import Status
		$text = "$msg (UHP)";
		$this->UpdateImportingStatus($file_importing_id, $text, $ssid);

		#Update First Active, Last Active, High RSSI, High Signal and point count
		$retry = true;
		while ($retry)
		{
			try {
				$sql = "SELECT Min(Hist_Date) AS fa, Max(Hist_Date) AS la, Max(RSSI) AS high_rssi, Max(Sig) AS high_sig, Count(Hist_Date) AS points FROM wifi_hist WHERE AP_ID = ? GROUP BY AP_ID";
				$hpp = $this->sql->conn->prepare($sql);
				$hpp->bindParam(1, $ap_id, PDO::PARAM_INT);
				$hpp->execute();
				$retry = false;
			}
			catch (Exception $e) {
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
			}
		}
		$fetchhp = $hpp->fetch(2);
		if($fetchhp['fa'] || $fetchhp['la'] || $fetchhp['high_rssi'] || $fetchhp['high_sig'] || $fetchhp['points'])
		{
			$retry = true;
			while ($retry)
			{
				try {
					$sql = "UPDATE wifi_ap SET fa = ? , la = ? , high_rssi = ? , high_sig = ? , points = ? WHERE AP_ID = ?";
					$uhpp = $this->sql->conn->prepare($sql);
					$uhpp->bindParam(1, $fetchhp['fa'], PDO::PARAM_STR);
					$uhpp->bindParam(2, $fetchhp['la'], PDO::PARAM_STR);
					$uhpp->bindParam(3, $fetchhp['high_rssi'], PDO::PARAM_INT);
					$uhpp->bindParam(4, $fetchhp['high_sig'], PDO::PARAM_INT);
					$uhpp->bindParam(5, $fetchhp['points'], PDO::PARAM_INT);
					$uhpp->bindParam(6, $ap_id, PDO::PARAM_INT);
					$uhpp->execute();
					$retry = false;
				}
				catch (Exception $e) {
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
				}
			}
		}
		
		#Update High GPS ID, High GPS RSSI
		if($HighRSSIwGPS >= $high_gps_rssi || $high_gps_rssi == "")
		{
			$retry = true;
			while ($retry)
			{
				try {
					if($this->sql->service == "mysql")
						{
							$sql = "SELECT wifi_hist.GPS_ID, wifi_hist.RSSI, wifi_hist.Sig\n"
								. "FROM wifi_hist\n"
								. "INNER JOIN wifi_gps ON wifi_hist.GPS_ID = wifi_gps.GPS_ID\n"
								. "WHERE wifi_hist.AP_ID = ? And wifi_hist.Hist_date IS NOT NULL And wifi_gps.Lat != '0.0000'\n"
								. "ORDER BY wifi_hist.RSSI DESC, wifi_hist.Hist_Date DESC, wifi_gps.NumOfSats DESC, wifi_gps.AccuracyMeters ASC, wifi_hist.Hist_ID ASC\n"
								. "LIMIT 1";
						}
					else if($this->sql->service == "sqlsrv")
						{
							$sql = "SELECT TOP 1 wifi_hist.GPS_ID, wifi_hist.RSSI, wifi_hist.Sig\n"
								. "FROM wifi_hist\n"
								. "INNER JOIN wifi_gps ON wifi_hist.GPS_ID = wifi_gps.GPS_ID\n"
								. "WHERE wifi_hist.AP_ID = ? And wifi_hist.Hist_date IS NOT NULL And wifi_gps.Lat != '0.0000'\n"
								. "ORDER BY wifi_hist.RSSI DESC, wifi_hist.Hist_Date DESC, wifi_gps.NumOfSats DESC, wifi_gps.AccuracyMeters ASC, wifi_hist.Hist_ID ASC";
						}
					$hgp = $this->sql->conn->prepare($sql);
					$hgp->bindParam(1, $ap_id, PDO::PARAM_INT);
					$hgp->execute();
					$retry = false;
				}
				catch (Exception $e) {
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
				}
			}
			$fetchhg = $hgp->fetch(2);
			if($fetchhg['GPS_ID'] || $fetchhg['RSSI'] || $fetchhg['Sig'])
			{
				$retry = true;
				while ($retry)
				{
					try {
						$sql = "UPDATE wifi_ap SET HighGps_ID = ? , high_gps_rssi = ? , high_gps_sig = ? WHERE AP_ID = ?";
						$uhgp = $this->sql->conn->prepare($sql);
						$uhgp->bindParam(1, $fetchhg['GPS_ID'], PDO::PARAM_INT);
						$uhgp->bindParam(2, $fetchhg['RSSI'], PDO::PARAM_INT);
						$uhgp->bindParam(3, $fetchhg['Sig'], PDO::PARAM_INT);
						$uhgp->bindParam(4, $ap_id, PDO::PARAM_INT);
						$uhgp->execute();
						$retry = false;
					}
					catch (Exception $e) {
						$retry = $this->sql->isPDOException($this->sql->conn, $e);
					}
				}
			}
		}

		$this->verbosed("Updated AP Pointer {".$ap_id."}.", 2);
		$this->verbosed("------------------------\r\n", 1);# Done with this AP.
	}
	
	private function UpdateCellHighPoints($file_importing_id, $cell_id, $msg = "")
	{
		$retry = true;
		while ($retry)
		{
			try {
				$text = "$msg (UCHP)";
				if($this->sql->service == "mysql")
					{$sql = "UPDATE files_importing SET tot = ? WHERE id = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "UPDATE [files_importing] SET [tot] = ? WHERE [id] = ?";}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $text, PDO::PARAM_STR);
				$prep->bindParam(2, $file_importing_id, PDO::PARAM_INT);
				$prep->execute();
				$retry = false;
			}
			catch (Exception $e) {
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
			}
		}
		
		#Update First Active, Last Active, High RSSI, High Signal and point count
		$retry = true;
		while ($retry)
		{
			try {
				$sql = "SELECT Min(hist_date) AS fa, Max(hist_date) AS la, Max(rssi) AS high_rssi, Count(hist_date) AS points FROM cell_hist WHERE cell_id = ? GROUP BY cell_id";
				$hpp = $this->sql->conn->prepare($sql);
				$hpp->bindParam(1, $cell_id, PDO::PARAM_INT);
				$hpp->execute();
				$retry = false;
			}
			catch (Exception $e) {
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
			}
		}
		$fetchhp = $hpp->fetch(2);
		if($fetchhp['fa'] || $fetchhp['la'] || $fetchhp['high_rssi'] || $fetchhp['points'])
		{
			$retry = true;
			while ($retry)
			{
				try {
					$sql = "UPDATE cell_id SET fa = ? , la = ? , high_rssi = ? , points = ? WHERE cell_id = ?";
					$uhpp = $this->sql->conn->prepare($sql);
					$uhpp->bindParam(1, $fetchhp['fa'], PDO::PARAM_STR);
					$uhpp->bindParam(2, $fetchhp['la'], PDO::PARAM_STR);
					$uhpp->bindParam(3, $fetchhp['high_rssi'], PDO::PARAM_INT);
					$uhpp->bindParam(4, $fetchhp['points'], PDO::PARAM_INT);
					$uhpp->bindParam(5, $cell_id, PDO::PARAM_INT);
					$uhpp->execute();
					$retry = false;
				}
				catch (Exception $e) {
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
				}
			}
		}
		
		$retry = true;
		while ($retry)
		{
			try {
				if($this->sql->service == "mysql")
				{
					$sql = "SELECT cell_hist.gps_id, cell_hist.rssi\n"
						. "FROM cell_hist\n"
						. "INNER JOIN wifi_gps ON cell_hist.gps_id = wifi_gps.GPS_ID\n"
						. "WHERE cell_hist.cell_id = ? And cell_hist.hist_date IS NOT NULL And wifi_gps.Lat != '0.0000'\n"
						. "ORDER BY cell_hist.rssi DESC, cell_hist.hist_date DESC, wifi_gps.NumOfSats DESC, wifi_gps.AccuracyMeters ASC, cell_hist.cell_hist_id ASC LIMIT 1";
				}
				else if($this->sql->service == "sqlsrv")
				{
					$sql = "SELECT TOP 1 cell_hist.gps_id, cell_hist.rssi\n"
						. "FROM cell_hist\n"
						. "INNER JOIN wifi_gps ON cell_hist.gps_id = wifi_gps.GPS_ID\n"
						. "WHERE cell_hist.cell_id = ? And cell_hist.hist_date IS NOT NULL And wifi_gps.Lat != '0.0000'\n"
						. "ORDER BY cell_hist.rssi DESC, cell_hist.hist_date DESC, wifi_gps.NumOfSats DESC, wifi_gps.AccuracyMeters ASC, cell_hist.cell_hist_id ASC";
				}
				$resgps = $this->sql->conn->prepare($sql);
				$resgps->bindParam(1, $cell_id, PDO::PARAM_INT);
				$resgps->execute();
				$retry = false;
			}
			catch (Exception $e) {
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
			}
		}
		$fetchgps = $resgps->fetch(2);
		if($fetchgps['gps_id'])
		{
			#Update cell ids
			$retry = true;
			while ($retry)
			{
				try {
					if($this->sql->service == "mysql")
						{$sql = "UPDATE cell_id SET highgps_id = ?, high_gps_rssi = ? WHERE cell_id = ?";}
					else if($this->sql->service == "sqlsrv")
						{$sql = "UPDATE [cell_id] SET [highgps_id] = ?, [high_gps_rssi] = ? WHERE [cell_id] = ?";}
					$prep = $this->sql->conn->prepare($sql);
					$prep->bindParam(1, $fetchgps['gps_id'], PDO::PARAM_INT);
					$prep->bindParam(2, $fetchgps['rssi'], PDO::PARAM_INT);
					$prep->bindParam(3, $cell_id, PDO::PARAM_INT);
					$prep->execute();
					$retry = false;
				}
				catch (Exception $e) {
					$retry = $this->sql->isPDOException($this->sql->conn, $e);
				}
			}
		}

		$this->verbosed("Updated Cell ID {".$cell_id."}.", 2);
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
		$gdata = array();
		$apdata = array();
		$gid = 0;
		$calc = "$File_lcount Lines";
		$this->UpdateImportingStatus($file_importing_id, 'Processing', $calc);
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
			
			$gps_hash = md5($fLat.$fLon.$fAlt.$fDate);
			$garr = @$gdata[$gps_hash];
			$fgid = @$garr['id'];
			if(!$fgid){$gid++;$fgid=$gid;}
			$gdata[$gps_hash] = array(
				'id'	=>  $fgid,
				'lat'	=>  $fLat,
				'lon'	=>  $fLon,
				'sats'	=>  null,
				'acc'	=>  null,
				'hdp'	=>  null,
				'alt'	=>  $fAlt,
				'geo'	=>  null,
				'kmh'	=>  null,
				'mph'	=>  null,
				'track'	=>  null,
				'datetime'	=>  $fDate
			);
			
			$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
			$aarr = @$apdata[$ap_hash];
			$fsigs = @$aarr['signals'];
			if($fsigs){$fsigs .= "\\";}
			$fsigs .= $fgid.",".$fSignal.",".$fRSSI;
			$apdata[$ap_hash] = array(
				'ap_hash'   => $ap_hash,
				'ssid'	  =>  $fSSID,
				'bssid'	   =>  $fBSSID,
				'manuf'	 =>  $this->findManuf($fBSSID),
				'auth'	  =>  $authen,
				'encry'	 =>  $encry,
				'sectype'   =>  $sectype,
				'flags'   =>  $fCapabilities,
				'radio'	 =>  $radio,
				'chan'	  =>  $chan,
				'btx'	   =>  null,
				'otx'	   =>  null,
				'nt'		=>  $nt,
				'signals'   =>  $fsigs
			);
		}
		#Import AP/GPS Arrays
		$this->rssi_signals_flag = 1;
		$AP_Import = $this->ImportApData($file_id, $file_importing_id, $gdata, $apdata, 0, 1);
		
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		$ret = array(
				'aps'=>$AP_Import['aps'],
				'gps'=>$AP_Import['gps'],
				'newaps'=>$AP_Import['newaps'],
				'cells'=>0,
				'cells_hist'=>0,
				'newcells'=>0
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

		$APQuery = $dbh->query("SELECT * FROM wifi");
		if($dbh->errorCode() != "00000")
		{
			return array(-1, "File does not have any access points");
		}
		$all_aps = $APQuery->fetchAll(2);
		$File_lcount = count($all_aps);
		$gdata = array();
		$apdata = array();
		$gid = 0;
		foreach($all_aps as $key => $ap)
		{
			$calc = "Line: ".($key+1)." / ".$File_lcount;
			$this->UpdateImportingStatus($file_importing_id, $calc, $fSSID);			
			
			$fid = $ap['_id'];
			$fBSSID = strtoupper($ap['bssid']);
			if(!$this->validateMacAddress($fBSSID)){continue;}
			$fSSID = $ap['ssid'];
			$fFrequency = $ap["frequency"];
			$fCapabilities = $ap["capabilities"];

			list($authen, $encry, $sectype, $nt) = $this->convert->findCapabilities($fCapabilities);
			list($chan, $radio) = $this->convert->findFreq($fFrequency);

			$sql1 = "SELECT * FROM wifispot WHERE fk_wifi = '$fid'";
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

				$gps_hash = md5($fLat.$fLon.$fAlt.$fDate);
				$garr = @$gdata[$gps_hash];
				$fgid = @$garr['id'];
				if(!$fgid){$gid++;$fgid=$gid;}
				$gdata[$gps_hash] = array(
					'id'	=>  $fgid,
					'lat'	=>  $fLat,
					'lon'	=>  $fLon,
					'sats'	=>  null,
					'acc'	=>  null,
					'hdp'	=>  null,
					'alt'	=>  $fAlt,
					'geo'	=>  null,
					'kmh'	=>  null,
					'mph'	=>  null,
					'track'	=>  null,
					'datetime'	=>  $fDate
				);
				
				$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
				$aarr = @$apdata[$ap_hash];
				$fsigs = @$aarr['signals'];
				if($fsigs){$fsigs .= "\\";}
				$fsigs .= $fgid.",".$fSignal.",".$fRSSI;
				$apdata[$ap_hash] = array(
					'ap_hash'   => $ap_hash,
					'ssid'	  =>  $fSSID,
					'bssid'	   =>  $fBSSID,
					'manuf'	 =>  $this->findManuf($fBSSID),
					'auth'	  =>  $authen,
					'encry'	 =>  $encry,
					'sectype'   =>  $sectype,
					'flags'   =>  $fCapabilities,
					'radio'	 =>  $radio,
					'chan'	  =>  $chan,
					'btx'	   =>  null,
					'otx'	   =>  null,
					'nt'		=>  $nt,
					'signals'   =>  $fsigs
				);
			}
		}
		
		#Import AP/GPS Arrays
		$this->rssi_signals_flag = 1;
		$AP_Import = $this->ImportApData($file_id, $file_importing_id, $gdata, $apdata, 0, 1);
		
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		$ret = array(
				'aps'=>$AP_Import['aps'],
				'gps'=>$AP_Import['gps'],
				'newaps'=>$AP_Import['newaps'],
				'cells'=>0,
				'cells_hist'=>0,
				'newcells'=>0
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
		$calc = "$File_lcount Lines";
		$this->UpdateImportingStatus($file_importing_id, 'Processing', $calc);
		$gdata = array();
		$apdata = array();
		$gid = 0;
		foreach($File_return as $key => $file_line)
		{	
			# Read CSV
			$apinfo = str_getcsv ($file_line);
			
			$fBSSID = strtoupper($apinfo[0]);
			if(!$this->validateMacAddress($fBSSID)){continue;}
			$fSSID = $apinfo[1];
			$fCapabilities = $apinfo[2];
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
			
			list($authen, $encry, $sectype, $nt) = $this->convert->findCapabilities($fCapabilities);
			list($chan, $radio) = $this->convert->findFreq($ffrequency);
			
			$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
			
			if (($timestamp = strtotime($fDate)) !== false) {
				$GpsDate = date("Y-m-d H:i:s", $timestamp);
				
				$gps_hash = md5($fLat.$fLon.$fAltitudeMeters.$GpsDate);
				$garr = @$gdata[$gps_hash];
				$fgid = @$garr['id'];
				if(!$fgid){$gid++;$fgid=$gid;}
				$gdata[$gps_hash] = array(
					'id'	=>  $fgid,
					'lat'	=>  $fLat,
					'lon'	=>  $fLon,
					'sats'	=>  null,
					'acc'	=>  null,
					'hdp'	=>  null,
					'alt'	=>  $fAltitudeMeters,
					'geo'	=>  null,
					'kmh'	=>  null,
					'mph'	=>  null,
					'track'	=>  null,
					'datetime'	=>  $GpsDate
				);
				
				$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
				$aarr = @$apdata[$ap_hash];
				$fsigs = @$aarr['signals'];
				if($fsigs){$fsigs .= "\\";}
				$fsigs .= $fgid.",".$fSignal.",".$fRSSI;
				$apdata[$ap_hash] = array(
					'ap_hash'   => $ap_hash,
					'ssid'	  =>  $fSSID,
					'bssid'	   =>  $fBSSID,
					'manuf'	 =>  $this->findManuf($fBSSID),
					'auth'	  =>  $authen,
					'encry'	 =>  $encry,
					'sectype'   =>  $sectype,
					'flags'   =>  $fCapabilities,
					'radio'	 =>  $radio,
					'chan'	  =>  $chan,
					'btx'	   =>  null,
					'otx'	   =>  null,
					'nt'		=>  $nt,
					'signals'   =>  $fsigs
				);	
			}
		}
		#Import AP/GPS Arrays
		$this->rssi_signals_flag = 1;
		$AP_Import = $this->ImportApData($file_id, $file_importing_id, $gdata, $apdata, 0, 1);
		
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		$ret = array(
				'aps'=>$AP_Import['aps'],
				'gps'=>$AP_Import['gps'],
				'newaps'=>$AP_Import['newaps'],
				'cells'=>0,
				'cells_hist'=>0,
				'newcells'=>0
		);
		return $ret;
	}

	public function import_wiglewificsv($source="", $file_id, $file_importing_id)
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
		$calc = "$File_lcount Lines";
		$this->UpdateImportingStatus($file_importing_id, 'Processing', $calc);
		$gdata = array();
		$apdata = array();
		$cdata = array();
		$gid = 0;
		$NewCellIds = 0;
		$cell_count = 0;
		$cell_hist_count = 0;		
		
		foreach($File_return as $key => $file_line)
		{
			$apinfo = str_getcsv ($file_line);
			if(strpos($apinfo[0], 'WigleWifi') !== false && strpos($apinfo[1], 'appRelease') !== false){continue;}
			if(strpos($apinfo[0], 'MAC') !== false && strpos($apinfo[1], 'SSID') !== false){continue;}			
			$fBSSID = strtoupper(@$apinfo[0]);
			$fSSID = @$apinfo[1];
			$fCapabilities = @$apinfo[2];
			$fDate = @$apinfo[3];
			$fchannel = @$apinfo[4];
			$fRSSI = @$apinfo[5];
			$fLat = $this->convert->all2dm(number_format(@$apinfo[6],7));
			$fLon = $this->convert->all2dm(number_format(@$apinfo[7],7));
			$fAltitudeMeters = @$apinfo[8];
			$fAccuracy = @$apinfo[9];
			$fType = @$apinfo[10];

			if(substr($fDate, 0, 4) == "1969"){continue;}//Continue on bad date
			if (($timestamp = strtotime($fDate)) !== false) 
			{
				$GpsDate = date("Y-m-d H:i:s", $timestamp);
				$gps_hash = md5($fLat.$fLon.$fAltitudeMeters.$fAccuracy.$fDate);
				$garr = @$gdata[$gps_hash];
				$fgid = @$garr['id'];
				if(!$fgid){$gid++;$fgid=$gid;}
				$gdata[$gps_hash] = array(
					'id'	=>  $fgid,
					'lat'	=>  $fLat,
					'lon'	=>  $fLon,
					'sats'	=>  null,
					'acc'	=>  $fAccuracy,
					'hdp'	=>  null,
					'alt'	=>  $fAltitudeMeters,
					'geo'	=>  null,
					'kmh'	=>  null,
					'mph'	=>  null,
					'track'	=>  null,
					'datetime'	=>  $fDate
				);

				if($fType == "WIFI")
				{
					if(!$this->validateMacAddress($fBSSID)){continue;}
					
					if($fRSSI == 0){$fRSSI = -99;}//Fix for 0 RSSI causing bad sig calculation
					$fSignal = $this->convert->dBm2Sig($fRSSI);
					list($authen, $encry, $sectype, $nt) = $this->convert->findCapabilities($fCapabilities);
					list($chan, $radio) = $this->convert->findFreq($fchannel);

					$ap_hash = md5($fSSID.$fBSSID.$chan.$sectype.$authen.$encry);
					$aarr = @$apdata[$ap_hash];
					$fsigs = @$aarr['signals'];
					if($fsigs){$fsigs .= "\\";}
					$fsigs .= $fgid.",".$fSignal.",".$fRSSI;
					$apdata[$ap_hash] = array(
						'ap_hash'   => $ap_hash,
						'ssid'	  =>  $fSSID,
						'bssid'	   =>  $fBSSID,
						'manuf'	 =>  $this->findManuf($fBSSID),
						'auth'	  =>  $authen,
						'encry'	 =>  $encry,
						'sectype'   =>  $sectype,
						'flags'   =>  $fCapabilities,
						'radio'	 =>  $radio,
						'chan'	  =>  $chan,
						'btx'	   =>  null,
						'otx'	   =>  null,
						'nt'		=>  $nt,
						'signals'   =>  $fsigs
					);
				}
				else
				{
					$cell_hash = md5($fBSSID.$fSSID.$fCapabilities.$fchannel.$fType);
					$carr = @$cdata[$cell_hash];
					$fsigs = @$carr['signals'];
					if($fsigs){$fsigs .= "\\";}
					$fsigs .= $fgid.",".$fRSSI;
					$cdata[$cell_hash] = array(
						'cell_hash'   => $cell_hash,
						'ssid'	  =>  $fSSID,
						'bssid'	   =>  $fBSSID,
						'flags'   =>  $fCapabilities,
						'chan'	  =>  $fchannel,
						'type'	 =>  $fType,
						'signals'   =>  $fsigs
					);
				}

			}
		}	
		#Import AP/GPS Arrays
		$this->rssi_signals_flag = 1;
		$AP_Import = $this->ImportApData($file_id, $file_importing_id, $gdata, $apdata, 0, 1);
		$Cell_Import = $this->ImportCellData($file_id, $file_importing_id, $cdata);

		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		$ret = array(
				'aps'=>$AP_Import['aps'],
				'gps'=>$AP_Import['gps'],
				'newaps'=>$AP_Import['newaps'],
				'cells'=>$Cell_Import['cells'],
				'cells_hist'=>$Cell_Import['cells_hist'],
				'newcells'=>$Cell_Import['newcells']
		);
		return $ret;
	}
		
	public function import_vistumblercsv($source="", $file_id, $file_importing_id)
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
		$gdata = array();
		$apdata = array();
		$gid = 0;
		foreach($File_return as $key => $file_line)
		{
			$line = str_getcsv ($file_line);
			if($line[1] == "BSSID" && $line[2] == "MANUFACTURER" && $line[3] == "SIGNAL" && $line[4] == "High Signal"){continue;} #tis the header, skip it..
			$line_count = count($line);
			if($line_count != 26){echo "csv line does not have 26 fields\r\n";continue;}else{echo "$line_count lines\r\n";}

			if ($line[7]==='Open' && $line[8]==='None'){$sectype="1";}
			if ($line[7]==='Open' && $line[8]==='WEP'){$sectype="2";}
			if ($line[8] !== 'None' && $line[8] !== 'WEP'){$sectype="3";}

			$ssid = @$line[0];
			$bssid = @$line[1];
			$sig = @$line[3];
			$RSSI = @$line[5];
			$auth = @$line[7];
			$encr = @$line[8];
			$sectype = @$sectype;
			$radio = @$line[9];
			$chan = @$line[10];
			$btx = @$line[11];
			$otx = @$line[12];
			$nt = @$line[13];
			$label = @$line[14];
			$lat = $this->convert->all2dm(number_format(@$line[15], 7));
			$lon = $this->convert->all2dm(number_format(@$line[16], 7));
			$sats = @$line[17];
			$hdp = @$line[18];
			$alt = @$line[19];
			$geo = @$line[20];
			$kmh = @$line[21];
			$mph = @$line[22];
			$track = @$line[23];
			$date = @$line[24];
			$time = @$line[25];
			$datetime = $date." ".$time;
			
			
			$gps_hash = md5($lat.$lon.$sats.$hdp.$alt.$geo.$kmh.$mph.$track.$datetime);
			$garr = @$gdata[$gps_hash];
			$fgid = @$garr['id'];
			if(!$fgid)
			{
				$gid++;
				$fgid = $gid;
			}
			$gdata[$gps_hash] = array(
				'id'	=>  $fgid,
				'lat'	=>  $lat,
				'lon'	=>  $lon,
				'sats'	=>  $sats,
				'acc'	=>  null,
				'hdp'	=>  $hdp,
				'alt'	=>  $alt,
				'geo'	=>  $geo,
				'kmh'	=>  $kmh,
				'mph'	=>  $mph,
				'track'	=>  $track,
				'datetime'	=>  $datetime
			);

			$ap_hash = md5($ssid.$bssid.$chan.$sectype.$auth.$encr);
			$aarr = @$apdata[$ap_hash];
			$fsigs = @$aarr['signals'];
			if($fsigs){$fsigs .= "\\";}
			$fsigs .= $fgid.",".$sig.",".$RSSI;
			$apdata[$ap_hash] = array(
				'ap_hash'   => $ap_hash,
				'ssid'	  =>  $ssid,
				'bssid'	   =>  $bssid,
				'manuf'	 =>  $this->findManuf($bssid),
				'auth'	  =>  $auth,
				'encry'	 =>  $encr,
				'sectype'   =>  $sectype,
				'flags'   =>  null,
				'radio'	 =>  $radio,
				'chan'	  =>  $chan,
				'btx'	   =>  $btx,
				'otx'	   =>  $otx,
				'nt'		=>  $nt,
				'signals'   =>  $fsigs
			);
		}
		
		#Import AP/GPS Arrays
		$this->rssi_signals_flag = 1;
		$AP_Import = $this->ImportApData($file_id, $file_importing_id, $gdata, $apdata, 1, 0);
		
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);
		
		$ret = array(
				'aps'=>$AP_Import['aps'],
				'gps'=>$AP_Import['gps'],
				'newaps'=>$AP_Import['newaps'],
				'cells'=>0,
				'cells_hist'=>0,
				'newcells'=>0
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
					$gps_time = $gps_line[5];
					list($s1,$s2,$s3) = explode("-",$gps_date);
					if (strlen($s1) == 2){$gps_date = $s3.'-'.$s1.'-'.$s2;}
					$datetime = $gps_date." ".$gps_time;
					$gdata[$gps_line[0]] = array(
								'import_id' => 0,
								'id'	=>  (int) $gps_line[0],
								'lat'	=>  $this->convert->all2dm($gps_line[1]),
								'lon'	=>  $this->convert->all2dm($gps_line[2]),
								'sats'	=>  (int) $gps_line[3],
								'acc'	=>  null,
								'hdp'   =>  null,
								'alt'   =>  null,
								'geo'   =>  null,
								'kmh'   =>  null,
								'mph'   =>  null,
								'track' =>  null,
								'datetime'	=>  $datetime
					);
					break;
				case 12:
					#trigger_error("12 columns!", E_USER_NOTICE);
					#This is the current version of the VS1 export, sanitize and order it into an array.
					$gps_line = $file_line_exp;
					if($gps_line[1] == "" || $gps_line[2] == ""){continue;}
					if($gps_line[0] == 0){$increment_ids = 1;}
					if($increment_ids){$gps_line[0]++;}
					
					#Fix for bad track angle in old file
					$trackangle = $gps_line[9];
					if(!is_numeric($trackangle) || $trackangle > 360){$trackangle = 0;}
					
					#fix incorect formatted date/time/gps from phils old conversions
					if (strpos($gps_line[11], '-') !== false) {
						$gps_date = $gps_line[11];
						$gps_time = "00:00:00.000";
						
						$lat = "0.0000";
						$lon = "0.0000";
					}
					else
					{
						$gps_date = $gps_line[10];
						$gps_time = $gps_line[11];
						$lat = $gps_line[1];
						$lon = $gps_line[2];
					}
					$datetime = $gps_date." ".$gps_time;
					
					#Fix for speeds that are way to high to be correct
					$kmh = (float) $gps_line[7];if($kmh > 1900){$kmh = 0;};
					$mph = (float) $gps_line[8];if($mph > 1200){$mph = 0;};

					list($s1,$s2,$s3) = explode("-",$gps_date);
					if (strlen($s1) == 2){$gps_date = $s3.'-'.$s1.'-'.$s2;}
					$gdata[$gps_line[0]] = array(
								'import_id' => 0,
								'id'	=>  (int) $gps_line[0],
								'lat'	=>  $this->convert->all2dm($lat),
								'lon'	=>  $this->convert->all2dm($lon),
								'sats'	=>  (int) $gps_line[3],
								'acc'	=>  null,
								'hdp'	=>  (float) $gps_line[4],
								'alt'	=>  (float) $gps_line[5],
								'geo'	=>  (float) $gps_line[6],
								'kmh'	=>  $kmh,
								'mph'	=>  $mph,
								'track'	=>  (float) $trackangle,
								'datetime'	=>  $datetime
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
					$CleanedSignal = preg_replace("/[^0-9,-]/", "", $ap_line[12]); #Fix for old file with % in signal.
					$highestSignal = $this->FindHighestSig($CleanedSignal);
					if($highestSignal == ""){$highestSignal = 0;}
					$highestRSSI = $this->convert->Sig2dBm($highestSignal);
					$apdata[] = array(
								'ap_hash'   => "",
								'ssid'	  =>  $ap_line[0],
								'bssid'	   =>  $ap_line[1],
								'auth'	  =>  $ap_line[3],
								'encry'	 =>  $ap_line[4],
								'sectype'   =>  (int) $ap_line[5],
								'flags'   =>  null,
								'radio'	 =>  $ap_line[6],
								'manuf'	 =>  $this->findManuf($ap_line[1]),
								'chan'	  =>  (int) $ap_line[7],
								'btx'	   =>  $ap_line[8],
								'otx'	   =>  $ap_line[9],
								'nt'		=>  $ap_line[10],
								'HighSig'   =>  $highestSignal,
								'HighRSSI'  =>  $highestRSSI,
								'label'	 =>  $ap_line[11],
								'signals'   =>  $CleanedSignal
							);
					$this->rssi_signals_flag = 0;
					break;
				case 15:
					#echo "---------------------15 columns!----------------";
					#This is to generate a sanitized and sane array for each AP from the new VS1 format.
					$ap_line = $file_line_exp;
					if(!$this->validateMacAddress($ap_line[1]))
					{
						$this->verbosed("MAC Address for the AP SSID of {$ap_line[0]} was not valid, dropping AP.");
						break;
					}
					
					
					if(is_numeric($ap_line[10]))#Check if line 10 id HighSig or Manufacturer
					{
						#Detailed Export Version 4.0, Current vistumbler format (correctly formatted)
						$apdata[] = array(
							'ap_hash'   => "",
							'ssid'	  =>  $ap_line[0],
							'bssid'	   =>  $ap_line[1],
							'manuf'	 =>  $this->findManuf($ap_line[1]),
							'auth'	  =>  $ap_line[3],
							'encry'	 =>  $ap_line[4],
							'sectype'   =>  (int) $ap_line[5],
							'flags'   =>  null,
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
							'bssid'	   =>  $ap_line[1],
							'HighSig'   =>  $ap_line[2],
							'auth'	  =>  $ap_line[3],
							'encry'	 =>  $ap_line[4],
							'sectype'   =>  (int) $ap_line[5],
							'flags'   =>  null,
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
							'bssid'	   =>  $ap_line[1],
							'HighSig'   =>  $ap_line[2],
							'label'	 =>  $ap_line[3],
							'auth'	  =>  $ap_line[4],
							'sectype'   =>  (int) $ap_line[5],
							'flags'   =>  null,
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

		$AP_Import = $this->ImportApData($file_id, $file_importing_id, $gdata, $apdata, 1, 0);
		
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);


		$ret = array(
				'aps'=>$AP_Import['aps'],
				'gps'=>$AP_Import['gps'],
				'newaps'=>$AP_Import['newaps'],
				'cells'=>0,
				'cells_hist'=>0,
				'newcells'=>0
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
				$this->UpdateImportingStatus($file_importing_id, $calc, 'Importing GPS Data');
				
				$new_gps_id = $this->InsertGps($file_id, $og_id, $og_lat, $og_lon, $og_sats, $og_hdp, $og_alt, $og_geo, $og_kmh, $og_mph, $og_track, null, $og_datetime);
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
				$this->UpdateImportingStatus($file_importing_id, $calc, $oa_ssid);
				
				$retry = true;
				while ($retry)
				{
					try {
						if($this->sql->service == "mysql")
							{$sql = "SELECT AP_ID, BTX, OTX FROM wifi_ap WHERE ap_hash = ? LIMIT 1";}
						else if($this->sql->service == "sqlsrv")
							{$sql = "SELECT TOP 1 [AP_ID], [BTX], [OTX] FROM [wifi_ap] WHERE [ap_hash] = ?";}
						$res = $this->sql->conn->prepare($sql);
						$res->bindParam(1, $ap_hash, PDO::PARAM_STR);
						$res->execute();
						$retry = false;
					}
					catch (Exception $e) {
						$retry = $this->sql->isPDOException($this->sql->conn, $e);
					}
				}
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
						$retry = true;
						while ($retry)
						{
							try {
								if($this->sql->service == "mysql")
									{$sql = "UPDATE wifi_ap SET BTX = ?, OTX = ? WHERE AP_ID = ?";}
								else if($this->sql->service == "sqlsrv")
									{$sql = "UPDATE [wifi_ap] SET [BTX] = ?, [OTX] = ? WHERE [AP_ID] = ?";}
								$prepu = $this->sql->conn->prepare($sql);
								$prepu->bindParam(1, $oa_btx, PDO::PARAM_STR);
								$prepu->bindParam(2, $oa_otx, PDO::PARAM_STR);
								$prepu->bindParam(3, $ap_id, PDO::PARAM_INT);
								$prepu->execute();
								$retry = false;
							}
							catch (Exception $e) {
								$retry = $this->sql->isPDOException($this->sql->conn, $e);
							}
						}
					}
				}
				else
				{
					$retry = true;
					while ($retry)
					{
						try {
							if($this->sql->service == "mysql")
								{$sql = "INSERT INTO wifi_ap (BSSID, SSID, CHAN, AUTH, ENCR, SECTYPE, RADTYPE, NETTYPE, BTX, OTX, ap_hash, File_ID) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)";}
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
							$retry = false;
						}
						catch (Exception $e) {
							$retry = $this->sql->isPDOException($this->sql->conn, $e);
						}
					}
					$ap_id = $this->sql->conn->lastInsertId();
					$new = 1;	
					$this->verbosed("Inserted APs Pointer {".$this->sql->conn->lastInsertId()."}.", 2);
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
				$this->UpdateImportingStatus($file_importing_id, $calc, 'Importing HIST Data');
				
				if($gps_id_arr != "" && $new_ap_id != "")
				{
					$harr = @$hdata[$new_ap_id];
					$HighRSSIwGPS = @$harr['HighRSSIwGPS'];
					if($HighRSSIwGPS == ""){$HighRSSIwGPS == -99;}
					if($gps_lat != "" && $gps_lon != "" && $gps_lat != "0.0000" && $gps_lon != "0.0000" && $oh_rssi > $HighRSSIwGPS){$HighRSSIwGPS = $oh_rssi;}
					$hdata[$new_ap_id] = array(
						'ap_id'	=>  $new_ap_id,
						'HighRSSIwGPS'	=>  $HighRSSIwGPS
					);
					
					$this->InsertHist($file_id, $new_ap_id, $new_gps_id, $oh_sig, $oh_rssi, $new, $oh_datetime);
				}
			}
		}
		
		$h_lcount = count($hdata);
		$h_ccount = 0;
		foreach ($hdata as $key => $ap)
		{
			$h_ccount++;
			$calc = "AP: ".($h_ccount)." / ".$h_lcount;
			$ap_id = $ap['ap_id'];
			$HighRSSIwGPS = $ap['HighRSSIwGPS'];
			$this->UpdateHighPoints($file_importing_id, $ap_id, $HighRSSIwGPS, $calc);
		}
		#Find if file had Valid GPS
		$this->UpdateFileValidGPS($file_id);

		$ret = array(
				'aps'=>$ap_count,
				'gps'=>$gps_count,
				'newaps'=>$NewAPs,
				'cells'=>0,
				'cells_hist'=>0,
				'newcells'=>0
		);
		return $ret;
	}	
	
}

