<?php
/*
Export.inc.php, holds the WiFiDB exporting functions.
Copyright (C) 2022 Andrew Calcutt 2012 Phil Ferland

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
class export extends dbcore
{
	public function __construct($config, $createGPXObj, $createKMLObj, $createGeoJSONObj, $convertObj, $ZipObj, $sql = null){
		parent::__construct($config, $sql);

		$this->convert = $convertObj;
		$this->createGPX = $createGPXObj;
		$this->createKML = $createKMLObj;
		$this->createGeoJSON = $createGeoJSONObj;
		$this->Zip = $ZipObj;
		// Reuse this object's connection for the mailer rather than opening another.
		$this->wdbmail = new wdbmail($config, $this->sql);
		$this->daemon_folder_stats = array();
		$this->named = 0;
		$this->month_names  = array(
			1=>'January',
			2=>'February',
			3=>'March',
			4=>'April',
			5=>'May',
			6=>'June',
			7=>'July',
			8=>'August',
			9=>'September',
			10=>'October',
			11=>'November',
			12=>'December',
		);
		$this->ver_array['export']  =   array(
			"last_edit"			 =>  "2015-10-11",
			"ExportDaemonKMZ"		  =>  "1.0",
			"ExportSingleAP"		=>  "1.0",
			"ExportCurrentAP"	=>  "1.0",
			"ExportApSignal3d"	=>  "1.0",	
			"UserAllArray"		=>  "1.0",
			"UserListArray"		=>  "1.0",
			"FindBox"	=>  "1.0",
			"distance"	=>  "2.0",
			"get_point"	=>  "2.0",
			"CreateBoundariesKML"	=>  "1.0",
			"ExportGPXAll"	=>  "1.0",			
			"GenerateDaemonKMLData" =>  "2.0",
			"HistoryKMLLink"		=>  "1.0",
			"GenerateUpdateKML"	 =>  "1.0",
		);
	}

	public function ApArray($id, $named=0, $new_ap=0, $valid_gps=0)
	{
		$Import_Map_Data = "";
		$latlongarray = array();
		$ap_array = array();
		$apcount = 0;
		
		$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.OTX, wap.FLAGS, wap.fa, wap.la, wap.points, wap.high_sig, wap.high_gps_sig, wap.high_gps_rssi, wap.high_rssi,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon,\n"
			. "wGPS.Alt As Alt,\n"
			. "wf.file_user As file_user\n"
			. "FROM wifi_ap AS wap\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
			. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
			. "WHERE wap.AP_ID = ?";
		if($valid_gps){$sql .= " AND wap.HighGps_ID IS NOT NULL";}

		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			if($ap['Lat'] == '' && $ap['Lon'] == ''){$validgps=0;}else{$validgps=1;}
			#Get AP GeoJSON
			$ap_info = array(
			"id" => $ap['AP_ID'],
			"new_ap" => $new_ap,
			"named" => $named,
			"mac" => $ap['BSSID'],
			"ssid" => $this->formatSSID($ap['SSID']),
			"chan" => $ap['CHAN'],
			"radio" => $ap['RADTYPE'],
			"nt" => $ap['NETTYPE'],
			"sectype" => $ap['SECTYPE'],
			"auth" => $ap['AUTH'],
			"encry" => $ap['ENCR'],
			"btx" => $ap['BTX'],
			"otx" => $ap['OTX'],
			"flags" => $ap['FLAGS'],
			"fa" => $ap['fa'],
			"la" => $ap['la'],
			"points" => $ap['points'],
			"high_sig" => $ap['high_gps_sig'],
			"high_rssi" => $ap['high_gps_rssi'],
			"high_gps_sig" => $ap['high_gps_sig'],
			"high_gps_rssi" => $ap['high_gps_rssi'],
			"lat" => $this->convert->dm2dd($ap['Lat']),
			"lon" => $this->convert->dm2dd($ap['Lon']),
			"lat_dm" => $ap['Lat'],
			"lon_dm" => $ap['Lon'],
			"validgps" => $validgps,
			"alt" => $ap['Alt'],
			"manuf"=>$this->findManuf($ap['BSSID']),
			"user" => $ap['file_user']
			);
			$ap_array[] = $ap_info;
			$apcount++;
			
			$latlon_info = array(
			"lat" => $this->convert->dm2dd($ap['Lat']),
			"long" => $this->convert->dm2dd($ap['Lon']),
			);
			$latlongarray[] = $latlon_info;
		}
		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlongarray,
		);
		
		return $ret_data;
	}

	public function ExportCurrentApArray($named=0, $new_icons=0)
	{
		$latlongarray = array();
		$ap_array = array();
		$apcount = 0;
		
		if($this->sql->service == "mysql")
			{$sql = "SELECT AP_ID, SSID, ap_hash FROM wifi_ap WHERE HighGps_ID IS NOT NULL ORDER BY AP_ID DESC LIMIT 1";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT TOP 1 [AP_ID], [SSID], [ap_hash] FROM [wifi_ap] WHERE [HighGps_ID] IS NOT NULL ORDER BY [AP_ID] DESC";}
		else if($this->sql->service == "pgsql")
			{$sql = 'SELECT "AP_ID", "SSID", ap_hash FROM wifi_ap WHERE "HighGps_ID" IS NOT NULL ORDER BY "AP_ID" DESC LIMIT 1';}
		$result = $this->sql->conn->query($sql);
		$result->execute();
		$ap_array = $result->fetch(2);
		if($ap_array['AP_ID'])
		{
			$ApArray = $this->ApArray($ap_array['AP_ID'], $named, $new_icons);
			$apcount = $ApArray['count'];
			$ap_array = $ApArray['data'];
			$latlongarray = $ApArray['latlongarray'];
		}
		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlongarray
		);
		
		Return $ret_data;
	}

	public function UserListArray($file_id, $from = NULL, $inc = NULL, $sort = "AP_ID", $ord = "DESC", $named=0, $new_ap=0, $only_new=0, $valid_gps = 0)
	{
		$latlon_array = array();
		$ap_array = array();
		$apcount = 0;

		#Get File Info
		if($this->sql->service == "pgsql")
			{$sql = 'SELECT id, file_orig, file_user, file_date, title, notes, hash, "NewAPPercent", aps, gps, size, "ValidGPS" FROM files WHERE id= ?';}
		else
			{$sql = "SELECT id, file_orig, file_user, file_date, title, notes, hash, NewAPPercent, aps, gps, size, ValidGPS FROM files WHERE id= ?";}
		$prepf = $this->sql->conn->prepare($sql);
		$prepf->bindParam(1,$file_id, PDO::PARAM_INT);
		$prepf->execute();
		$file_array = $prepf->fetch(2) ?: array();
		$file_info = array(
			"id" => $file_array['id'] ?? null,
			"file" => $file_array['file_orig'] ?? null,
			"user" => $file_array['file_user'] ?? null,
			"date" => $file_array['file_date'] ?? null,
			"title" => $file_array['title'] ?? null,
			"notes" => $file_array['notes'] ?? null,
			"hash" => $file_array['hash'] ?? null,
			"validgps" => $file_array['ValidGPS'] ?? null,
			"aps" => $file_array['aps'] ?? null,
			"gps" => $file_array['gps'] ?? null,
			"size" => $file_array['size'] ?? null,
			"NewAPPercent" => $file_array['NewAPPercent'] ?? null,
		);

		#Get AP Info — single JOIN query replaces the original two-query N+1 pattern.
		# The original code did one query to get wifi_hist+wifi_ap rows, then a separate
		# SELECT per row to fetch wifi_gps coords and file_user.  This merges everything
		# into a single query at the cost of a larger GROUP BY list.
		$sort_sql = $this->sql->sortIdent($sort);
		if($this->sql->service == "pgsql")
		{
			$sql = 'SELECT wh."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR", wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX", wap.fa, wap.la, wap.points, wap."HighGps_ID",'."\n"
				. "wap.high_gps_sig, wap.high_gps_rssi, wap.high_sig, wap.high_rssi,\n"
				. 'wGPS."Lat" As "Lat", wGPS."Lon" As "Lon", wGPS."Alt" As "Alt",'."\n"
				. "wf.file_user AS file_user,\n"
				. 'MAX(wh."New") AS new, COUNT(wh."Hist_Date") As list_points'."\n"
				. "FROM wifi_hist AS wh\n"
				. 'LEFT JOIN wifi_ap  AS wap  ON wh."AP_ID"    = wap."AP_ID"'."\n"
				. 'LEFT JOIN wifi_gps AS wGPS ON wGPS."GPS_ID" = wap."HighGps_ID"'."\n"
				. 'LEFT JOIN files    AS wf   ON wf.id       = wap."File_ID"'."\n"
				. 'WHERE wh."File_ID" = ?';
			if($only_new == 1){$sql .= ' AND wh."New" = 1';}
			if($valid_gps){$sql .= ' AND wap."HighGps_ID" IS NOT NULL';}
			$sql .= "\n".'GROUP BY wh."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR", wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX", wap.fa, wap.la, wap.points, wap."HighGps_ID",'."\n"
				. "wap.high_gps_sig, wap.high_gps_rssi, wap.high_sig, wap.high_rssi,\n"
				. 'wGPS."Lat", wGPS."Lon", wGPS."Alt", wf.file_user'."\n"
				. "ORDER BY {$sort_sql} {$ord}";
		}
		else
		{
			$sql = "SELECT wh.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.HighGps_ID,\n"
				. "wap.high_gps_sig, wap.high_gps_rssi, wap.high_sig, wap.high_rssi,\n"
				. "wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt,\n"
				. "wf.file_user AS file_user,\n"
				. "MAX(wh.New) AS new, COUNT(wh.Hist_Date) As list_points\n"
				. "FROM wifi_hist AS wh\n"
				. "LEFT JOIN wifi_ap  AS wap  ON wh.AP_ID    = wap.AP_ID\n"
				. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
				. "LEFT JOIN files    AS wf   ON wf.id       = wap.File_ID\n"
				. "WHERE wh.File_ID = ?";
			if($only_new == 1){$sql .= " AND wh.New = 1";}
			if($valid_gps){$sql .= " AND wap.HighGps_ID IS NOT NULL";}
			$sql .= "\nGROUP BY wh.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.HighGps_ID,\n"
				. "wap.high_gps_sig, wap.high_gps_rssi, wap.high_sig, wap.high_rssi,\n"
				. "wGPS.Lat, wGPS.Lon, wGPS.Alt, wf.file_user\n"
				. "ORDER BY {$sort_sql} {$ord}";
		}
		if($from !== NULL && $inc !== NULL){
			if($this->sql->service == "mysql"){$sql .=  "\nLIMIT ".$from.", ".$inc;}
			else if($this->sql->service == "pgsql"){$sql .=  "\nLIMIT ".$inc." OFFSET ".$from;}
			else if($this->sql->service == "sqlsrv"){$sql .=  "\nOFFSET ".$from." ROWS FETCH NEXT ".$inc." ROWS ONLY";}
		}

		$prep_AP_IDS = $this->sql->conn->prepare($sql);
		$prep_AP_IDS->bindParam(1,$file_id, PDO::PARAM_INT);
		$prep_AP_IDS->execute();
		$filepointer = $prep_AP_IDS->fetchAll();
		foreach($filepointer as $ap)
		{
			if($ap['Lat'] == '' && $ap['Lon'] == ''){$validgps=0;}else{$validgps=1;}
			if($ap['new'] == 1){$new='New';}else{$new='Update';}
			#Get AP GeoJSON
			$ap_info = array(
			"id" => $ap['AP_ID'],
			"nu" => $new,
			"new_ap" => $new_ap,
			"named" => $named,
			"mac" => $ap['BSSID'],
			"ssid" => $this->formatSSID($ap['SSID']),
			"chan" => $ap['CHAN'],
			"radio" => $ap['RADTYPE'],
			"nt" => $ap['NETTYPE'],
			"sectype" => $ap['SECTYPE'],
			"auth" => $ap['AUTH'],
			"encry" => $ap['ENCR'],
			"btx" => $ap['BTX'],
			"otx" => $ap['OTX'],
			"fa" => $ap['fa'],
			"la" => $ap['la'],
			"points" => $ap['points'],
			"list_points" => $ap['list_points'],
			"high_sig" => $ap['high_sig'],
			"high_rssi" => $ap['high_rssi'],
			"high_gps_sig" => $ap['high_gps_sig'],
			"high_gps_rssi" => $ap['high_gps_rssi'],
			"lat" => $this->convert->dm2dd($ap['Lat']),
			"lon" => $this->convert->dm2dd($ap['Lon']),
			"lat_dm" => $ap['Lat'],
			"lon_dm" => $ap['Lon'],
			"validgps" => $validgps,
			"alt" => $ap['Alt'],
			"manuf"=>$this->findManuf($ap['BSSID']),
			"user" => $ap['file_user'],
			);
			$ap_array[] = $ap_info;
			$apcount++;
			
			$latlon_info = array(
			"lat" => $this->convert->dm2dd($ap['Lat']),
			"long" => $this->convert->dm2dd($ap['Lon']),
			);
			$latlon_array[] = $latlon_info;
		}

		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlon_array,
			"file_info" => $file_info
		);
		
		return $ret_data;
	}

	public function UserAllArray($user, $from = NULL, $inc = NULL, $named=0, $new_ap=0)
	{
		$Import_Map_Data = "";
		$latlon_array = array();
		$ap_array = array();
		$apcount = 0;
		$retry = true;
		while ($retry)
		{
			try 
			{
				if($this->sql->service == "mysql")
					{
						$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,\n"
							. "wGPS.Lat As Lat,\n"
							. "wGPS.Lon As Lon,\n"
							. "wf.file_user AS file_user\n"
							. "FROM wifi_ap AS wap\n"
							. "LEFT JOIN wifi_gps As wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
							. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
							. "WHERE \n"
							. "    wap.HighGps_ID IS NOT NULL And\n"
							. "    wap.File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND file_user LIKE ?)\n"
							. "ORDER BY wap.ModDate DESC";
							if($from !== NULL && $inc !== NULL){$sql .=  " LIMIT ".$from.", ".$inc;}
					}
				else if($this->sql->service == "sqlsrv")
					{
						$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,\n"
							. "wGPS.Lat As Lat,\n"
							. "wGPS.Lon As Lon,\n"
							. "wf.file_user AS file_user\n"
							. "FROM wifi_ap AS wap\n"
							. "LEFT JOIN wifi_gps As wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
							. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
							. "WHERE \n"
							. "    wap.HighGps_ID IS NOT NULL And\n"
							. "    wap.File_ID IN (SELECT id FROM files WHERE ValidGPS = 1 AND file_user LIKE ?)\n"
							. "ORDER BY wap.ModDate DESC";
						if($from !== NULL && $inc !== NULL){$sql .=  " OFFSET ".$from." ROWS FETCH NEXT ".$inc." ROWS ONLY";}
					}
				else if($this->sql->service == "pgsql")
					{
						$sql = 'SELECT wap."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR", wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX", wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,'."\n"
							. 'wGPS."Lat" As "Lat",'."\n"
							. 'wGPS."Lon" As "Lon",'."\n"
							. "wf.file_user AS file_user\n"
							. "FROM wifi_ap AS wap\n"
							. 'LEFT JOIN wifi_gps As wGPS ON wGPS."GPS_ID" = wap."HighGps_ID"'."\n"
							. 'LEFT JOIN files AS wf ON wf.id = wap."File_ID"'."\n"
							. "WHERE \n"
							. '    wap."HighGps_ID" IS NOT NULL And'."\n"
							. '    wap."File_ID" IN (SELECT id FROM files WHERE "ValidGPS" = 1 AND file_user LIKE ?)'."\n"
							. 'ORDER BY wap."ModDate" DESC';
						if($from !== NULL && $inc !== NULL){$sql .=  " LIMIT ".$inc." OFFSET ".$from;}
					}
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $user, PDO::PARAM_STR);
				$prep->execute();
				$appointer = $prep->fetchAll();
				$retry = false;
			}
			catch (Exception $e) 
			{
				$retry = $this->sql->isPDOException($this->sql->conn, $e);
				$cell_id = 0;
			}
		}
		foreach($appointer as $apinfo)
		{
			$apcount++;
			#Get AP GeoJSON
			$ap_info = array(
			"id" => $apinfo['AP_ID'],
			"new_ap" => $new_ap,
			"named" => $named,
			"mac" => $apinfo['BSSID'],
			"ssid" => $this->formatSSID($apinfo['SSID']),
			"chan" => $apinfo['CHAN'],
			"radio" => $apinfo['RADTYPE'],
			"nt" => $apinfo['NETTYPE'],
			"sectype" => $apinfo['SECTYPE'],
			"auth" => $apinfo['AUTH'],
			"encry" => $apinfo['ENCR'],
			"btx" => $apinfo['BTX'],
			"otx" => $apinfo['OTX'],
			"fa" => $apinfo['fa'],
			"la" => $apinfo['la'],
			"points" => $apinfo['points'],
			"high_gps_sig" => $apinfo['high_gps_sig'],
			"high_gps_rssi" => $apinfo['high_gps_rssi'],
			"lat" => $this->convert->dm2dd($apinfo['Lat']),
			"lon" => $this->convert->dm2dd($apinfo['Lon']),
			"alt" => $apinfo['Alt'],
			"manuf"=>$this->findManuf($apinfo['BSSID']),
			"user" => $apinfo['file_user']
			);
			$ap_array[] = $ap_info;
			
			$latlon_info = array(
			"lat" => $this->convert->dm2dd($apinfo['Lat']),
			"long" => $this->convert->dm2dd($apinfo['Lon']),
			);
			$latlon_array[] = $latlon_info;
		}
		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlon_array,
		);
		
		return $ret_data;
	}

	/**
	 * Return APs whose best-GPS point falls within a lat/lon bounding box and
	 * whose last-active date (wap.la) falls within [start_date, end_date).
	 * Coordinates must be in NMEA Degrees-Minutes format (DDMM.MMMM) to match
	 * the storage format used in wifi_gps.  Pass null for start_date or end_date
	 * to leave that bound open.  Returns the same ap_info array format as the
	 * other export functions so GeoJSON/MVT callers get consistent output.
	 *
	 * Pagination modes:
	 *   $from = null, $last_id = null   → one-shot TOP(n)/LIMIT n, no ordering
	 *                                     (used by per-tile callers).
	 *   $from = int                     → OFFSET/FETCH pagination (legacy; slow
	 *                                     at deep offsets — avoid for large scans).
	 *   $last_id = int                  → keyset pagination: WHERE AP_ID > ?
	 *                                     ORDER BY AP_ID LIMIT n.  Stays O(n)
	 *                                     per page no matter how deep — required
	 *                                     for million-row daemon scans.  Pass
	 *                                     0 (or any int <= smallest AP_ID) to
	 *                                     start; pass the max AP_ID returned
	 *                                     from the previous page to advance.
	 */
	public function BboxDateArray($lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
	                               $start_date = null, $end_date = null,
	                               $from = null, $inc = 5000, $last_id = null)
	{
		$params = [$lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm];

		if ($this->sql->service === 'sqlsrv') {
			// ── Keyset pagination branch (daemon scans, large date windows) ──
			// Drive from wifi_ap ordered by AP_ID (PK clustered index) so each
			// page is a cheap range-seek + nested-loop lookup that early-terminates
			// at TOP(n).  Independent of scan depth — unlike OFFSET/FETCH which
			// re-sorts the full join on every page.
			if ($last_id !== null) {
				$sql = "SELECT TOP (" . (int)$inc . ") wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR,
				               wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX,
				               wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,
				               wGPS.Lat AS Lat, wGPS.Lon AS Lon, wGPS.Alt AS Alt,
				               wf.file_user
				        FROM wifi_ap AS wap
				        INNER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID
				        LEFT  JOIN files    AS wf   ON wf.id        = wap.File_ID
				        WHERE wap.AP_ID > ?
				          AND wap.HighGps_ID IS NOT NULL
				          AND wap.points     IS NOT NULL
				          AND wGPS.Lat BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))
				          AND wGPS.Lon BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))";

				// Rebuild params: keyset needs (last_id, bbox...) in that order.
				$params = [(int)$last_id, $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm];

				if ($start_date !== null && $end_date !== null) {
					$sql .= ' AND wap.la >= ? AND wap.la < ?';
					$params[] = $start_date;
					$params[] = $end_date;
				} elseif ($start_date !== null) {
					$sql .= ' AND wap.la >= ?';
					$params[] = $start_date;
				} elseif ($end_date !== null) {
					$sql .= ' AND wap.la < ?';
					$params[] = $end_date;
				}

				$sql .= ' ORDER BY wap.AP_ID OPTION (LOOP JOIN, FORCE ORDER)';
			} else {
			// Drive from the GPS bbox subquery so SQL Server uses nested loops
			// through IX_wifi_ap_HighGps_covering into wifi_ap rather than
			// scanning millions of date-range wifi_ap rows and hash-joining to wifi_gps.
			//
			// Explicit CAST removes the nvarchar implicit conversion so the
			// IX_wifi_gps_LatLon statistics are used accurately for cardinality.
			//
			// When $from is null (no pagination — always the case for tile requests)
			// we use SELECT TOP (n) with no ORDER BY.  This lets SQL Server early-
			// terminate the nested loop as soon as n rows have been found rather than
			// completing all 800k+ iterations and then sorting before OFFSET/FETCH.
			// Tile thinning handles distribution so ordering is not required.
			//
			// OPTION (LOOP JOIN, FORCE ORDER) prevents the optimizer from reverting
			// to the hash-join plan regardless of its cardinality estimates.
			if ($from === null) {
				$top_clause = 'TOP (' . (int)$inc . ') ';
			} else {
				$top_clause = '';
			}

			$sql = "SELECT {$top_clause}wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR,
			               wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX,
			               wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,
			               wGPS.Lat AS Lat, wGPS.Lon AS Lon, wGPS.Alt AS Alt,
			               wf.file_user
			        FROM (SELECT GPS_ID, Lat, Lon, Alt
			              FROM wifi_gps
			              WHERE Lat BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))
			                AND Lon BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))
			             ) AS wGPS
			        INNER JOIN wifi_ap AS wap ON wap.HighGps_ID = wGPS.GPS_ID
			        LEFT JOIN files AS wf ON wf.id = wap.File_ID
			        WHERE wap.points IS NOT NULL";

			if ($start_date !== null && $end_date !== null) {
				$sql .= ' AND wap.la >= ? AND wap.la < ?';
				$params[] = $start_date;
				$params[] = $end_date;
			} elseif ($start_date !== null) {
				$sql .= ' AND wap.la >= ?';
				$params[] = $start_date;
			} elseif ($end_date !== null) {
				$sql .= ' AND wap.la < ?';
				$params[] = $end_date;
			}

			if ($from === null) {
				// No ORDER BY — TOP + early termination, order irrelevant for tile thinning.
				$sql .= ' OPTION (LOOP JOIN, FORCE ORDER)';
			} else {
				$sql .= ' ORDER BY wap.AP_ID'
				      . ' OFFSET ' . (int)$from . ' ROWS FETCH NEXT ' . (int)$inc . ' ROWS ONLY'
				      . ' OPTION (LOOP JOIN, FORCE ORDER)';
			}
			} // end of OFFSET/one-shot branch

		} else if ($this->sql->service === 'pgsql') {
			// Same plan shape as the MySQL branch below. Two Postgres-specific
			// differences: mixed-case columns must be quoted, and every bbox
			// parameter is bound with PARAM_STR, so the numeric Lat/Lon columns
			// need an explicit CAST -- Postgres will not compare numeric to text.
			$sql = 'SELECT wap."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR",
			               wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX",
			               wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,
			               wGPS."Lat" AS "Lat", wGPS."Lon" AS "Lon", wGPS."Alt" AS "Alt",
			               wf.file_user
			        FROM wifi_ap AS wap
			        LEFT JOIN wifi_gps AS wGPS ON wGPS."GPS_ID" = wap."HighGps_ID"
			        LEFT JOIN files    AS wf   ON wf.id        = wap."File_ID"
			        WHERE wap."HighGps_ID" IS NOT NULL
			          AND wap.points IS NOT NULL
			          AND wGPS."Lat" BETWEEN CAST(? AS numeric(9,4)) AND CAST(? AS numeric(9,4))
			          AND wGPS."Lon" BETWEEN CAST(? AS numeric(9,4)) AND CAST(? AS numeric(9,4))';

			if ($last_id !== null) {
				$sql .= ' AND wap."AP_ID" > ?';
				$params[] = (int)$last_id;
			}

			if ($start_date !== null && $end_date !== null) {
				$sql .= ' AND wap.la >= ? AND wap.la < ?';
				$params[] = $start_date;
				$params[] = $end_date;
			} elseif ($start_date !== null) {
				$sql .= ' AND wap.la >= ?';
				$params[] = $start_date;
			} elseif ($end_date !== null) {
				$sql .= ' AND wap.la < ?';
				$params[] = $end_date;
			}

			if ($last_id !== null) {
				// Keyset pagination: ordered scan along the PK; no OFFSET cost.
				$sql .= ' ORDER BY wap."AP_ID" LIMIT ' . (int)$inc;
			} elseif ($from !== null) {
				$sql .= ' ORDER BY wap."AP_ID" LIMIT ' . (int)$inc . ' OFFSET ' . (int)$from;
			} else {
				$sql .= ' ORDER BY wap."AP_ID" LIMIT ' . (int)$inc;
			}
		} else {
			// MySQL
			$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR,
			               wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX,
			               wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,
			               wGPS.Lat AS Lat, wGPS.Lon AS Lon, wGPS.Alt AS Alt,
			               wf.file_user
			        FROM wifi_ap AS wap
			        LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID
			        LEFT JOIN files    AS wf   ON wf.id        = wap.File_ID
			        WHERE wap.HighGps_ID IS NOT NULL
			          AND wap.points IS NOT NULL
			          AND wGPS.Lat BETWEEN ? AND ?
			          AND wGPS.Lon BETWEEN ? AND ?";

			if ($last_id !== null) {
				$sql .= ' AND wap.AP_ID > ?';
				$params[] = (int)$last_id;
			}

			if ($start_date !== null && $end_date !== null) {
				$sql .= ' AND wap.la >= ? AND wap.la < ?';
				$params[] = $start_date;
				$params[] = $end_date;
			} elseif ($start_date !== null) {
				$sql .= ' AND wap.la >= ?';
				$params[] = $start_date;
			} elseif ($end_date !== null) {
				$sql .= ' AND wap.la < ?';
				$params[] = $end_date;
			}

			if ($last_id !== null) {
				// Keyset pagination: ordered scan along PK; no OFFSET cost.
				$sql .= ' ORDER BY wap.AP_ID LIMIT ' . (int)$inc;
			} elseif ($from !== null) {
				$sql .= ' ORDER BY wap.AP_ID LIMIT ' . (int)$from . ',' . (int)$inc;
			} else {
				$sql .= ' ORDER BY wap.AP_ID LIMIT ' . (int)$inc;
			}
		}

		$prep = $this->sql->conn->prepare($sql);
		foreach ($params as $i => $val) {
			$prep->bindValue($i + 1, $val, PDO::PARAM_STR);
		}
		$prep->execute();
		$fetch_aps = $prep->fetchAll();

		$ap_array    = [];
		$latlon_array = [];

		foreach ($fetch_aps as $ap) {
			$ap_info = [
				'id'            => $ap['AP_ID'],
				'new_ap'        => 1,
				'named'         => 0,
				'mac'           => $ap['BSSID'],
				'ssid'          => $this->formatSSID($ap['SSID']),
				'chan'           => $ap['CHAN'],
				'radio'         => $ap['RADTYPE'],
				'nt'            => $ap['NETTYPE'],
				'sectype'       => $ap['SECTYPE'],
				'auth'          => $ap['AUTH'],
				'encry'         => $ap['ENCR'],
				'btx'           => $ap['BTX'],
				'otx'           => $ap['OTX'],
				'fa'            => $ap['fa'],
				'la'            => $ap['la'],
				'points'        => $ap['points'],
				'high_gps_sig'  => $ap['high_gps_sig'],
				'high_gps_rssi' => $ap['high_gps_rssi'],
				'lat'           => $this->convert->dm2dd($ap['Lat']),
				'lon'           => $this->convert->dm2dd($ap['Lon']),
				'alt'           => $ap['Alt'],
				'manuf'         => $this->findManuf($ap['BSSID']),
				'user'          => $ap['file_user'],
			];
			$ap_array[]    = $ap_info;
			$latlon_array[] = ['lat' => $ap_info['lat'], 'long' => $ap_info['lon']];
		}

		return [
			'count'        => count($ap_array),
			'data'         => $ap_array,
			'latlon_array' => $latlon_array,
		];
	}

	public function DateArray($start_date, $end_date, $named = 0, $new_ap = 0, $from = NULL, $inc = NULL, $valid_gps = 0)
	{
		$start_date = (empty($start_date)) ? date("Y-m-d H:i:s") : date('Y-m-d H:i:s',strtotime($start_date));
		$end_date = (empty($end_date)) ? date("Y-m-d H:i:s") : date('Y-m-d H:i:s',strtotime($end_date));
		
		#Get lists from the date specified
		$date_search = $date."%";
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.FLAGS, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_sig, wap.high_rssi, wap.high_gps_sig, wap.high_gps_rssi, wap.File_ID, wGPS.Lat, wGPS.Lon, wGPS.Alt, wf.file_user\n"
					. "FROM wifi_ap AS wap\n"
					. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT OUTER JOIN files AS wf ON wf.id = wap.File_ID\n"
					. "WHERE AP_ID IN\n"
					. "    (SELECT DISTINCT(wh.AP_ID)\n"
					. "		FROM wifi_hist AS wh\n"
					. "		INNER JOIN files AS wf ON wf.id = wh.File_ID\n"
					. "		INNER JOIN wifi_ap AS wap ON wap.AP_ID = wh.AP_ID\n"
					. "		WHERE (wf.completed = 1) AND (wf.date BETWEEN ? AND ?)\n"
					. "    )\n";
				if($valid_gps){$sql .= "	AND wap.HighGps_ID IS NOT NULL\n";}
				$sql .= "ORDER BY la DESC";
				
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.FLAGS, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_sig, wap.high_rssi, wap.high_gps_sig, wap.high_gps_rssi, wap.File_ID, wGPS.Lat, wGPS.Lon, wGPS.[Alt], wf.file_user\n"
					. "FROM wifi_ap AS wap\n"
					. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
					. "LEFT OUTER JOIN files AS wf ON wf.id = wap.File_ID\n"
					. "WHERE AP_ID IN\n"
					. "    (SELECT DISTINCT(wh.AP_ID)\n"
					. "		FROM wifi_hist AS wh\n"
					. "		INNER JOIN files AS wf ON wf.id = wh.File_ID\n"
					. "		INNER JOIN wifi_ap AS wap ON wap.AP_ID = wh.AP_ID\n"
					. "		WHERE (wf.completed = 1) AND (wf.file_date >= ? AND wf.file_date <= ?)\n"
					. "    )\n";
				if($valid_gps){$sql .= "	AND wap.HighGps_ID IS NOT NULL\n";}
				$sql .= "ORDER BY la DESC";
				if($from !== NULL){$sql .=  " OFFSET ".$from." ROWS";}
				if($inc !== NULL){$sql .=  " FETCH NEXT ".$inc." ROWS ONLY";}
			}
		else if($this->sql->service == "pgsql")
			{
				$sql = 'SELECT wap."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR", wap."FLAGS", wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX", wap.fa, wap.la, wap.points, wap.high_sig, wap.high_rssi, wap.high_gps_sig, wap.high_gps_rssi, wap."File_ID", wGPS."Lat", wGPS."Lon", wGPS."Alt", wf.file_user'."\n"
					. "FROM wifi_ap AS wap\n"
					. 'LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS."GPS_ID" = wap."HighGps_ID"'."\n"
					. 'LEFT OUTER JOIN files AS wf ON wf.id = wap."File_ID"'."\n"
					. 'WHERE wap."AP_ID" IN'."\n"
					. '    (SELECT DISTINCT(wh."AP_ID")'."\n"
					// Inner aliases are suffixed (wf2/wap2) rather than reusing the
					// outer wf/wap names, which Postgres allows but which makes the
					// three dialect branches harder to read side by side.
					. "		FROM wifi_hist AS wh\n"
					. '		INNER JOIN files AS wf2 ON wf2.id = wh."File_ID"'."\n"
					. '		INNER JOIN wifi_ap AS wap2 ON wap2."AP_ID" = wh."AP_ID"'."\n"
					. "		WHERE (wf2.completed = 1) AND (wf2.file_date >= ? AND wf2.file_date <= ?)\n"
					. "    )\n";
				if($valid_gps){$sql .= '	AND wap."HighGps_ID" IS NOT NULL'."\n";}
				$sql .= "ORDER BY la DESC";
				if($inc !== NULL){$sql .=  " LIMIT ".$inc;}
				if($from !== NULL){$sql .=  " OFFSET ".$from;}
			}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $start_date, PDO::PARAM_STR);
		$prep->bindParam(2, $end_date, PDO::PARAM_STR);
		$prep->execute();
		$fetch_aps = $prep->fetchAll();
		$latlon_array = array();
		$ap_array = array();
		$apcount = 0;
		foreach($fetch_aps as $apinfo)
		{
			$apcount++;
			#Get AP GeoJSON
			$ap_info = array(
			"id" => $apinfo['AP_ID'],
			"new_ap" => $new_ap,
			"named" => $named,
			"mac" => $apinfo['BSSID'],
			"ssid" => $this->formatSSID($apinfo['SSID']),
			"chan" => $apinfo['CHAN'],
			"radio" => $apinfo['RADTYPE'],
			"nt" => $apinfo['NETTYPE'],
			"sectype" => $apinfo['SECTYPE'],
			"auth" => $apinfo['AUTH'],
			"encry" => $apinfo['ENCR'],
			"btx" => $apinfo['BTX'],
			"otx" => $apinfo['OTX'],
			"fa" => $apinfo['fa'],
			"la" => $apinfo['la'],
			"points" => $apinfo['points'],
			"high_sig" => $apinfo['high_sig'],
			"high_rssi" => $apinfo['high_rssi'],
			"high_gps_sig" => $apinfo['high_gps_sig'],
			"high_gps_rssi" => $apinfo['high_gps_rssi'],
			"lat" => $this->convert->dm2dd($apinfo['Lat']),
			"lon" => $this->convert->dm2dd($apinfo['Lon']),
			"alt" => $apinfo['Alt'],
			"manuf"=>$this->findManuf($apinfo['BSSID']),
			"user" => $apinfo['file_user'],
			"first_file_id" => $apinfo['File_ID']
			);
			$ap_array[] = $ap_info;
			
			$latlon_info = array(
			"lat" => $this->convert->dm2dd($apinfo['Lat']),
			"long" => $this->convert->dm2dd($apinfo['Lon']),
			);
			$latlon_array[] = $latlon_info;
		}
		
		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlon_array" => $latlon_array
		);
		
		return $ret_data;
	}

	public function SigHistArray($ap_id, $file_id, $from = NULL, $inc = NULL, $valid_gps = 0, $named = 0)
	{
		$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon,\n"
			. "wGPS.Alt As Alt,\n"
			. "wf.file_user As file_user\n"
			. "FROM wifi_ap AS wap\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
			. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
			. "WHERE wap.AP_ID = ?";
		if($valid_gps){$sql .=" AND wap.HighGps_ID IS NOT NULL";}

		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $ap_id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			if($this->sql->service == "pgsql")
			{
				$sql = 'SELECT wh."Sig", wh."RSSI", wh."Hist_Date", wGPS."Lat", wGPS."Lon", wGPS."Alt", wGPS."NumOfSats", wGPS."AccuracyMeters", wGPS."HorDilPitch", wh."File_ID", wf.file_user'."\n"
					. "FROM wifi_hist AS wh\n"
					. 'LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS."GPS_ID" = wh."GPS_ID"'."\n"
					. 'LEFT OUTER JOIN files AS wf ON wf.id = wh."File_ID"'."\n";
				if($file_id)
					{$sql .= 'WHERE wGPS."Lat" <> 0.0000 AND wh."AP_ID" = ? And wh."File_ID" = ?'."\n";}
				else
					{$sql .= 'WHERE wGPS."Lat" <> 0.0000 AND wh."AP_ID" = ?'."\n";}
				$sql .= 'ORDER BY wh."Hist_Date" DESC';
			}
			else
			{
				$sql = "SELECT wh.Sig, wh.RSSI, wh.Hist_Date, wGPS.Lat, wGPS.Lon, wGPS.Alt, wGPS.NumOfSats, wGPS.AccuracyMeters, wGPS.HorDilPitch, wh.File_ID, wf.file_user\n"
					. "FROM wifi_hist AS wh\n"
					. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wh.GPS_ID\n"
					. "LEFT OUTER JOIN files AS wf ON wf.id = wh.File_ID\n";
				if($file_id)
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ? And wh.File_ID = ?\n";}
				else
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND wh.AP_ID = ?\n";}
				$sql .= "ORDER BY wh.Hist_Date DESC";
			}
			if($from !== NULL && $inc !== NULL){
				if($this->sql->service == "mysql"){$sql .=  "\nLIMIT ".$from.", ".$inc;}
				else if($this->sql->service == "pgsql"){$sql .=  "\nLIMIT ".$inc." OFFSET ".$from;}
				else if($this->sql->service == "sqlsrv"){$sql .=  "\nOFFSET ".$from." ROWS FETCH NEXT ".$inc." ROWS ONLY";}
			}
			$prep2 = $this->sql->conn->prepare($sql);
			$prep2->bindParam(1, $ap['AP_ID'], PDO::PARAM_INT);
			if($file_id){$prep2->bindParam(2, $file_id, PDO::PARAM_INT);}
			$prep2->execute();
			$histpointer = $prep2->fetchAll();
			$apcount = 0;
			foreach($histpointer as $hist)
			{
				if($hist['Lat'] == '' && $hist['Lon'] == ''){$validgps=0;}else{$validgps=1;}
				#Get AP GeoJSON
				$ap_info = array(
				"id" => $ap['AP_ID'],
				"named" => $named,
				"mac" => $ap['BSSID'],
				"ssid" => $this->formatSSID($ap['SSID']),
				"chan" => $ap['CHAN'],
				"sectype" => $ap['SECTYPE'],
				"auth" => $ap['AUTH'],
				"encry" => $ap['ENCR'],
				"lat" => $this->convert->dm2dd($hist['Lat']),
				"lon" => $this->convert->dm2dd($hist['Lon']),
				"lat_dm" => $hist['Lat'],
				"lon_dm" => $hist['Lon'],
				"validgps" => $validgps,
				"alt" => $hist['Alt'],
				"sats" => $hist['NumOfSats'],
				"accuracy" => $hist['AccuracyMeters'],
				"hdop" => $hist['HorDilPitch'],
				"user" => $hist['file_user'],
				"signal" => $hist['Sig'],
				"rssi" => $hist['RSSI'],
				"hist_date" => $hist['Hist_Date'],
				"hist_file_id" => $hist['File_ID']
				);
				
				$ap_array[] = $ap_info;
				$apcount++;
				
				$latlon_info = array(
				"lat" => $this->convert->dm2dd($hist['Lat']),
				"long" => $this->convert->dm2dd($hist['Lon']),
				);
				$latlon_array[] = $latlon_info;
			}
		}
		
		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlon_array,
		);
		
		return $ret_data;
	}

	public function CellArray($id, $named=0, $new_ap=0, $valid_gps = 0)
	{
		$Import_Map_Data = "";
		$latlon_array = array();
		$ap_array = array();
		$apcount = 0;

		if($this->sql->service == "pgsql")
		{
			$sql = "SELECT cid.cell_id, cid.mac, cid.authmode, cid.ssid, cid.chan, cid.authmode, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points, cell_carriers.network, cell_carriers.country,\n"
				. "wGPS.\"Lat\" As \"Lat\",\n"
				. "wGPS.\"Lon\" As \"Lon\",\n"
				. "wGPS.\"Alt\" As \"Alt\",\n"
				. "wf.file_user As file_user\n"
				. "FROM cell_id AS cid\n"
				. "LEFT JOIN wifi_gps AS wGPS ON wGPS.\"GPS_ID\" = cid.highgps_id\n"
				. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
				. "LEFT OUTER JOIN cell_carriers ON CAST(mcc AS varchar) = substring(cid.mac,0,4) AND CAST(mnc AS varchar) = REPLACE(substring(cid.mac,4,3), '_', '')\n"
				. "WHERE cid.cell_id = ?";
		}
		else
		{
			$sql = "SELECT cid.cell_id, cid.mac, cid.authmode, cid.ssid, cid.chan, cid.authmode, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points, cell_carriers.network, cell_carriers.country,\n"
				. "wGPS.Lat As Lat,\n"
				. "wGPS.Lon As Lon,\n"
				. "wGPS.Alt As Alt,\n"
				. "wf.file_user As file_user\n"
				. "FROM cell_id AS cid\n"
				. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = cid.highgps_id\n"
				. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
				. "LEFT OUTER JOIN cell_carriers ON CAST(mcc AS varchar) = substring(cid.mac,0,4) AND CAST(mnc AS varchar) = REPLACE(substring(cid.mac,4,3), '_', '')\n"
				. "WHERE cid.cell_id = ?";
		}
		if($valid_gps){$sql .=" AND cid.highgps_id IS NOT NULL";}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			if($ap['Lat'] == '' && $ap['Lon'] == ''){$validgps=0;}else{$validgps=1;}
			if($ap['network']){$name = $this->formatSSID($ap['network']);}else{$name = $this->formatSSID($ap['ssid']);}
			#Get AP GeoJSON
			$ap_info = array(
			"id" => $ap['cell_id'],
			"new_ap" => $new_ap,
			"named" => $named,
			"mac" => $ap['mac'],
			"mapname" => $this->formatSSID($name),
			"network" => $ap['network'],
			"country" => $ap['country'],
			"ssid" => $this->formatSSID($ap['ssid']),
			"chan" => $ap['chan'],
			"auth" => $ap['authmode'],
			"type" => $ap['type'],
			"fa" => $ap['fa'],
			"la" => $ap['la'],
			"points" => $ap['points'],
			"high_rssi" => $ap['high_rssi'],
			"high_gps_rssi" => $ap['high_gps_rssi'],
			"lat" => $this->convert->dm2dd($ap['Lat']),
			"lon" => $this->convert->dm2dd($ap['Lon']),
			"lat_dm" => $ap['Lat'],
			"lon_dm" => $ap['Lon'],
			"validgps" => $validgps,
			"alt" => $ap['Alt'],
			"user" => $ap['file_user']
			);
			$ap_array[] = $ap_info;
			$apcount++;
			
			$latlon_info = array(
			"lat" => $this->convert->dm2dd($ap['Lat']),
			"long" => $this->convert->dm2dd($ap['Lon']),
			);
			$latlon_array[] = $latlon_info;
		}
		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlon_array,
		);
		
		return $ret_data;
	}

	/**
	 * Fetch cell towers within a lat/lon bounding box (DM format), excluding BT/BLE.
	 * Supports both MySQL and MSSQL (SQL Server).
	 *
	 * $lat_min_dm/$lat_max_dm/$lon_min_dm/$lon_max_dm — bounding box in NMEA DM format.
	 * $inc      — max rows to return.
	 * $last_id  — keyset pagination cursor (cell_id of last row from previous page).
	 *             Pass null for the first page or for one-shot on-demand tile requests.
	 *
	 * Returns ['count' => int, 'data' => [row, ...], 'latlon_array' => [...]]
	 * Each row has: id, mac, ssid, authmode, chan, type, fa, la, points, rssi, lat, lon, user
	 */
	public function BboxCellArray($lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
	                               $inc = 5000, $last_id = null,
	                               $start_date = null, $end_date = null)
	{
		if ($this->sql->service === 'sqlsrv') {
			if ($last_id !== null) {
				// ── Keyset pagination (daemon bulk scan) ────────────────────────
				// Drive from cell_id PK ordered by cell_id; bbox applied as filter.
				$sql = "SELECT TOP (" . (int)$inc . ")
				               ci.cell_id, ci.mac, ci.ssid, ci.authmode, ci.chan, ci.type,
				               ci.fa, ci.la, ci.points, ci.high_gps_rssi AS rssi,
				               g.Lat AS lat, g.Lon AS lon, f.file_user AS [user]
				        FROM cell_id AS ci
				        INNER JOIN wifi_gps AS g ON g.GPS_ID = ci.highgps_id
				        INNER JOIN files    AS f ON f.id     = ci.file_id
				        WHERE ci.cell_id > ?
				          AND ci.highgps_id IS NOT NULL
				          AND ci.type NOT IN ('BT','BLE')
				          AND g.Lat BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))
				          AND g.Lon BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))";
				$params = [(int)$last_id, $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm];
				if ($start_date !== null && $end_date !== null) {
					$sql .= ' AND ci.la >= ? AND ci.la < ?';
					$params[] = $start_date; $params[] = $end_date;
				} elseif ($start_date !== null) {
					$sql .= ' AND ci.la >= ?'; $params[] = $start_date;
				} elseif ($end_date !== null) {
					$sql .= ' AND ci.la < ?'; $params[] = $end_date;
				}
				$sql .= ' ORDER BY ci.cell_id OPTION (LOOP JOIN, FORCE ORDER)';
			} else {
				// ── One-shot bbox (on-demand tile request) ──────────────────────
				// Drive from GPS bbox sub-query; TOP + no ORDER BY for early termination.
				$sql = "SELECT TOP (" . (int)$inc . ")
				               ci.cell_id, ci.mac, ci.ssid, ci.authmode, ci.chan, ci.type,
				               ci.fa, ci.la, ci.points, ci.high_gps_rssi AS rssi,
				               g.Lat AS lat, g.Lon AS lon, f.file_user AS [user]
				        FROM (SELECT GPS_ID, Lat, Lon
				              FROM wifi_gps
				              WHERE Lat BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))
				                AND Lon BETWEEN CAST(? AS decimal(9,4)) AND CAST(? AS decimal(9,4))
				             ) AS g
				        INNER JOIN cell_id AS ci ON ci.highgps_id = g.GPS_ID
				        INNER JOIN files   AS f  ON f.id          = ci.file_id
				        WHERE ci.highgps_id IS NOT NULL
				          AND ci.type NOT IN ('BT','BLE')";
				$params = [$lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm];
				if ($start_date !== null && $end_date !== null) {
					$sql .= ' AND ci.la >= ? AND ci.la < ?';
					$params[] = $start_date; $params[] = $end_date;
				} elseif ($start_date !== null) {
					$sql .= ' AND ci.la >= ?'; $params[] = $start_date;
				} elseif ($end_date !== null) {
					$sql .= ' AND ci.la < ?'; $params[] = $end_date;
				}
				$sql .= ' OPTION (LOOP JOIN, FORCE ORDER)';
			}
		} else if ($this->sql->service === 'pgsql') {
			// wifi_gps.Lat/Lon are mixed case and numeric; the bbox params are bound
			// with PARAM_STR, so they need an explicit CAST as well as the quoting.
			// "user" is a reserved word in Postgres and must stay quoted as an alias.
			$sql = 'SELECT ci.cell_id, ci.mac, ci.ssid, ci.authmode, ci.chan, ci.type,
			               ci.fa, ci.la, ci.points, ci.high_gps_rssi AS rssi,
			               g."Lat" AS lat, g."Lon" AS lon, f.file_user AS "user"
			        FROM cell_id AS ci
			        INNER JOIN wifi_gps AS g ON g."GPS_ID" = ci.highgps_id
			        INNER JOIN files    AS f ON f.id     = ci.file_id
			        WHERE ci.highgps_id IS NOT NULL
			          AND ci.type NOT IN (\'BT\',\'BLE\')
			          AND g."Lat" BETWEEN CAST(? AS numeric(9,4)) AND CAST(? AS numeric(9,4))
			          AND g."Lon" BETWEEN CAST(? AS numeric(9,4)) AND CAST(? AS numeric(9,4))';
			$params = [$lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm];

			if ($last_id !== null) {
				$sql .= ' AND ci.cell_id > ?';
				$params[] = (int)$last_id;
			}
			if ($start_date !== null && $end_date !== null) {
				$sql .= ' AND ci.la >= ? AND ci.la < ?';
				$params[] = $start_date; $params[] = $end_date;
			} elseif ($start_date !== null) {
				$sql .= ' AND ci.la >= ?'; $params[] = $start_date;
			} elseif ($end_date !== null) {
				$sql .= ' AND ci.la < ?'; $params[] = $end_date;
			}
			$sql .= ' ORDER BY ci.cell_id LIMIT ' . (int)$inc;
		} else {
			// ── MySQL ────────────────────────────────────────────────────────────
			$sql = "SELECT ci.cell_id, ci.mac, ci.ssid, ci.authmode, ci.chan, ci.type,
			               ci.fa, ci.la, ci.points, ci.high_gps_rssi AS rssi,
			               g.Lat AS lat, g.Lon AS lon, f.file_user AS `user`
			        FROM cell_id AS ci
			        INNER JOIN wifi_gps AS g ON g.GPS_ID = ci.highgps_id
			        INNER JOIN files    AS f ON f.id     = ci.file_id
			        WHERE ci.highgps_id IS NOT NULL
			          AND ci.type NOT IN ('BT','BLE')
			          AND g.Lat BETWEEN ? AND ?
			          AND g.Lon BETWEEN ? AND ?";
			$params = [$lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm];

			if ($last_id !== null) {
				$sql .= ' AND ci.cell_id > ?';
				$params[] = (int)$last_id;
			}
			if ($start_date !== null && $end_date !== null) {
				$sql .= ' AND ci.la >= ? AND ci.la < ?';
				$params[] = $start_date; $params[] = $end_date;
			} elseif ($start_date !== null) {
				$sql .= ' AND ci.la >= ?'; $params[] = $start_date;
			} elseif ($end_date !== null) {
				$sql .= ' AND ci.la < ?'; $params[] = $end_date;
			}
			$sql .= ' ORDER BY ci.cell_id LIMIT ' . (int)$inc;
		}

		$prep = $this->sql->conn->prepare($sql);
		foreach ($params as $i => $val) {
			$prep->bindValue($i + 1, $val, PDO::PARAM_STR);
		}
		$prep->execute();
		$fetch_rows = $prep->fetchAll();

		$cell_array   = [];
		$latlon_array = [];
		foreach ($fetch_rows as $row) {
			$cell_info = [
				'id'       => (int)$row['cell_id'],
				'mac'      => (string)$row['mac'],
				'ssid'     => $this->formatSSID((string)$row['ssid']),
				'authmode' => (string)$row['authmode'],
				'chan'      => (string)$row['chan'],
				'type'     => (string)$row['type'],
				'fa'       => (string)$row['fa'],
				'la'       => (string)$row['la'],
				'points'   => (int)$row['points'],
				'rssi'     => (int)$row['rssi'],
				'lat'      => $this->convert->dm2dd($row['lat']),
				'lon'      => $this->convert->dm2dd($row['lon']),
				'user'     => (string)$row['user'],
			];
			$cell_array[]   = $cell_info;
			$latlon_array[] = ['lat' => $cell_info['lat'], 'long' => $cell_info['lon']];
		}

		return [
			'count'        => count($cell_array),
			'data'         => $cell_array,
			'latlon_array' => $latlon_array,
		];
	}

	public function CellUserListArray($file_id, $from = NULL, $inc = NULL, $sort = "cell_id", $ord = "DESC", $named=0, $new_ap=0, $only_new=0, $valid_gps = 0, $exclude = "'BT','BLE'", $include = "")
	{
		$latlon_array = array();
		$ap_array = array();
		$apcount = 0;

		#Get File Info
		if($this->sql->service == "pgsql")
			{$sql = 'SELECT id, file_orig, file_user, file_date, title, notes, hash, "NewAPPercent", aps, gps, size, "ValidGPS" FROM files WHERE id= ?';}
		else
			{$sql = "SELECT id, file_orig, file_user, file_date, title, notes, hash, NewAPPercent, aps, gps, size, ValidGPS FROM files WHERE id= ?";}
		$prepf = $this->sql->conn->prepare($sql);
		$prepf->bindParam(1,$file_id, PDO::PARAM_INT);
		$prepf->execute();
		$file_array = $prepf->fetch(2) ?: array();
		$file_info = array(
			"id" => $file_array['id'] ?? null,
			"file" => $file_array['file_orig'] ?? null,
			"user" => $file_array['file_user'] ?? null,
			"date" => $file_array['file_date'] ?? null,
			"title" => $file_array['title'] ?? null,
			"notes" => $file_array['notes'] ?? null,
			"hash" => $file_array['hash'] ?? null,
			"validgps" => $file_array['ValidGPS'] ?? null,
			"aps" => $file_array['aps'] ?? null,
			"gps" => $file_array['gps'] ?? null,
			"size" => $file_array['size'] ?? null,
			"NewAPPercent" => $file_array['NewAPPercent'] ?? null,
		);

		#Get Cell Info
		$sql = "SELECT cell_hist.cell_id, cell_id.ssid, cell_id.mac, cell_id.authmode, cell_id.type, cell_id.chan, cell_id.points, cell_hist.new, cell_carriers.network, cell_carriers.country, MIN(cell_hist.hist_date) as fa, MAX(cell_hist.hist_date) as la, COUNT(cell_hist.hist_date) As list_points\n"
			. "FROM cell_hist\n"
			. "LEFT JOIN cell_id ON cell_hist.cell_id = cell_id.cell_id\n"
			. "LEFT OUTER JOIN cell_carriers ON CAST(mcc AS varchar) = substring(cell_id.mac,0,4) AND CAST(mnc AS varchar) = REPLACE(substring(cell_id.mac,4,3), '_', '')\n"
			. "WHERE cell_hist.file_id = ?\n";
		if($exclude){$sql .= "AND cell_id.type NOT IN (".$exclude.")\n";}
		if($include){$sql .= "AND cell_id.type IN (".$include.")\n";}
		$sort_sql = $this->sql->sortIdent($sort);
		$sql .= "GROUP BY cell_hist.cell_id, cell_id.ssid, cell_id.mac, cell_id.authmode, cell_id.type, cell_id.chan, cell_id.points, cell_hist.new, cell_carriers.network, cell_carriers.country\n"
			. "ORDER BY {$sort_sql} {$ord}";
		if($from !== NULL && $inc !== NULL){
			if($this->sql->service == "mysql"){$sql .=  "\nLIMIT ".$from.", ".$inc;}
			else if($this->sql->service == "pgsql"){$sql .=  "\nLIMIT ".$inc." OFFSET ".$from;}
			else if($this->sql->service == "sqlsrv"){$sql .=  "\nOFFSET ".$from." ROWS FETCH NEXT ".$inc." ROWS ONLY";}
		}
		$prep_AP_IDS = $this->sql->conn->prepare($sql);
		$prep_AP_IDS->bindParam(1,$file_id, PDO::PARAM_INT);
		$prep_AP_IDS->execute();
		$cidpointer = $prep_AP_IDS->fetchAll();
		foreach($cidpointer as $cid)
		{
			if($this->sql->service == "pgsql")
			{
				$sql = "SELECT cid.cell_id, cid.mac, cid.authmode, cid.ssid, cid.chan, cid.authmode, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points,\n"
					. "wGPS.\"Lat\" As \"Lat\",\n"
					. "wGPS.\"Lon\" As \"Lon\",\n"
					. "wGPS.\"Alt\" As \"Alt\",\n"
					. "wf.file_user As file_user\n"
					. "FROM cell_id AS cid\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.\"GPS_ID\" = cid.highgps_id\n"
					. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
					. "WHERE cid.cell_id = ?";
			}
			else
			{
				$sql = "SELECT cid.cell_id, cid.mac, cid.authmode, cid.ssid, cid.chan, cid.authmode, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points,\n"
					. "wGPS.Lat As Lat,\n"
					. "wGPS.Lon As Lon,\n"
					. "wGPS.Alt As Alt,\n"
					. "wf.file_user As file_user\n"
					. "FROM cell_id AS cid\n"
					. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = cid.highgps_id\n"
					. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
					. "WHERE cid.cell_id = ?";
			}
			$result = $this->sql->conn->prepare($sql);
			$result->bindParam(1, $cid['cell_id'], PDO::PARAM_INT);
			$result->execute();
			$appointer = $result->fetchAll();
			foreach($appointer as $ap)
			{
				if($ap['Lat'] == '' && $ap['Lon'] == ''){$validgps=0;}else{$validgps=1;}
				if($cid['new'] == 1){$new='New';}else{$new='Update';}
				if($cid['network']){$name = $this->formatSSID($cid['network']);}else{$name = $this->formatSSID($ap['ssid']);}
				#Get AP GeoJSON
				$ap_info = array(
				"id" => $ap['cell_id'],
				"nu" => $new,
				"new_ap" => $new_ap,
				"named" => $named,
				"mac" => $ap['mac'],
				"mapname" => $this->formatSSID($name),
				"network" => $cid['network'],
				"country" => $cid['country'],
				"ssid" => $this->formatSSID($ap['ssid']),
				"chan" => $ap['chan'],
				"auth" => $ap['authmode'],
				"type" => $ap['type'],
				"fa" => $ap['fa'],
				"la" => $ap['la'],
				"points" => $cid['points'],
				"list_points" => $cid['list_points'],
				"high_rssi" => $ap['high_rssi'],
				"high_gps_rssi" => $ap['high_gps_rssi'],
				"lat" => $this->convert->dm2dd($ap['Lat']),
				"lon" => $this->convert->dm2dd($ap['Lon']),
				"lat_dm" => $ap['Lat'],
				"lon_dm" => $ap['Lon'],
				"validgps" => $validgps,
				"alt" => $ap['Alt'],
				"user" => $ap['file_user']
				);
				$ap_array[] = $ap_info;
				$apcount++;
				
				$latlon_info = array(
				"lat" => $this->convert->dm2dd($ap['Lat']),
				"long" => $this->convert->dm2dd($ap['Lon']),
				);
				$latlon_array[] = $latlon_info;
			}
		}

		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlon_array,
			"file_info" => $file_info
		);
		
		return $ret_data;
	}

	public function CellSigHistArray($cell_id, $file_id, $from = NULL, $inc = NULL, $valid_gps = 0, $named = 0)
	{

		if($this->sql->service == "pgsql")
		{
			$sql = "SELECT cid.cell_id, cid.mac, cid.ssid, cid.chan, cid.authmode, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points,\n"
				. "wGPS.\"Lat\" As \"Lat\",\n"
				. "wGPS.\"Lon\" As \"Lon\",\n"
				. "wGPS.\"Alt\" As \"Alt\",\n"
				. "wf.file_user As file_user\n"
				. "FROM cell_id AS cid\n"
				. "LEFT JOIN wifi_gps AS wGPS ON wGPS.\"GPS_ID\" = cid.highgps_id\n"
				. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
				. "WHERE cid.cell_id = ?";
		}
		else
		{
			$sql = "SELECT cid.cell_id, cid.mac, cid.ssid, cid.chan, cid.authmode, cid.type, cid.high_rssi, cid.high_gps_rssi, cid.fa, cid.la, cid.points,\n"
				. "wGPS.Lat As Lat,\n"
				. "wGPS.Lon As Lon,\n"
				. "wGPS.Alt As Alt,\n"
				. "wf.file_user As file_user\n"
				. "FROM cell_id AS cid\n"
				. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = cid.highgps_id\n"
				. "LEFT JOIN files AS wf ON wf.id = cid.file_id\n"
				. "WHERE cid.cell_id = ?";
		}
		if($valid_gps){$sql .=" AND cid.highgps_id IS NOT NULL";}
		$prep = $this->sql->conn->prepare($sql);
		$prep->bindParam(1, $cell_id, PDO::PARAM_INT);
		$prep->execute();
		$appointer = $prep->fetchAll();
		foreach($appointer as $ap)
		{
			if($this->sql->service == "pgsql")
			{
				$sql = "SELECT ch.rssi, ch.hist_date, wGPS.\"Lat\", wGPS.\"Lon\", wGPS.\"Alt\", ch.file_id, wf.file_user\n"
					. "FROM cell_hist AS ch\n"
					. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.\"GPS_ID\" = ch.gps_id\n"
					. "LEFT OUTER JOIN files AS wf ON wf.id = ch.file_id\n";
				if($file_id)
					{$sql .= "WHERE wGPS.\"Lat\" <> 0.0000 AND ch.cell_id = ? And ch.file_id = ?\n";}
				else
					{$sql .= "WHERE wGPS.\"Lat\" <> 0.0000 AND ch.cell_id = ?\n";}
			}
			else
			{
				$sql = "SELECT ch.rssi, ch.hist_date, wGPS.Lat, wGPS.Lon, wGPS.Alt, ch.file_id, wf.file_user\n"
					. "FROM cell_hist AS ch\n"
					. "LEFT OUTER JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = ch.gps_id\n"
					. "LEFT OUTER JOIN files AS wf ON wf.id = ch.file_id\n";
				if($file_id)
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND ch.cell_id = ? And ch.file_id = ?\n";}
				else
					{$sql .= "WHERE wGPS.Lat <> '0.0000' AND ch.cell_id = ?\n";}
			}
			$sql .= "ORDER BY ch.hist_date DESC";
			if($from !== NULL && $inc !== NULL){
				if($this->sql->service == "mysql"){$sql .=  "\nLIMIT ".$from.", ".$inc;}
				else if($this->sql->service == "pgsql"){$sql .=  "\nLIMIT ".$inc." OFFSET ".$from;}
				else if($this->sql->service == "sqlsrv"){$sql .=  "\nOFFSET ".$from." ROWS FETCH NEXT ".$inc." ROWS ONLY";}
			}
			$prep2 = $this->sql->conn->prepare($sql);
			$prep2->bindParam(1, $ap['cell_id'], PDO::PARAM_INT);
			if($file_id){$prep2->bindParam(2, $file_id, PDO::PARAM_INT);}
			$prep2->execute();
			$histpointer = $prep2->fetchAll();
			$apcount = 0;
			foreach($histpointer as $hist)
			{
				if($hist['Lat'] == '' && $hist['Lon'] == ''){$validgps=0;}else{$validgps=1;}
				#Get AP GeoJSON
				$ap_info = array(
				"id" => $ap['cell_id'],
				"named" => $named,
				"mac" => $ap['mac'],
				"ssid" => $this->formatSSID($ap['ssid']),
				"chan" => $ap['chan'],
				"auth" => $ap['authmode'],
				"type" => $ap['type'],
				"lat" => $this->convert->dm2dd($hist['Lat']),
				"lon" => $this->convert->dm2dd($hist['Lon']),
				"lat_dm" => $hist['Lat'],
				"lon_dm" => $hist['Lon'],
				"validgps" => $validgps,
				"alt" => $hist['Alt'],
				"sats" => $hist['NumOfSats'],
				"accuracy" => $hist['AccuracyMeters'],
				"hdop" => $hist['HorDilPitch'],
				"user" => $hist['file_user'],
				"rssi" => $hist['rssi'],
				"hist_date" => $hist['hist_date'],
				"hist_file_id" => $hist['file_id']
				);
				
				$ap_array[] = $ap_info;
				$apcount++;

				$latlon_info = array(
				"lat" => $this->convert->dm2dd($hist['Lat']),
				"long" => $this->convert->dm2dd($hist['Lon']),
				);
				$latlon_array[] = $latlon_info;
			}
		}
		
		$ret_data = array(
			"count" => $apcount,
			"data" => $ap_array,
			"latlongarray" => $latlon_array,
		);
		
		return $ret_data;
	}

	public function SearchArray($ssid, $mac, $radio, $chan, $auth, $encry, $sectype, $ord, $sort, $named = 0, $new_ap = 0, $from = NULL, $inc = NULL, $valid_gps = 0)
	{
		$ssid = "%".$ssid."%";
		$mac = "%".$mac."%";
		$radio = "%".$radio."%";
		$chan = "%".$chan."%";
		$auth = "%".$auth."%";
		$encry = "%".$encry."%";
		

		if($this->sql->service == "pgsql")
		{
			// CHAN is an integer column; Postgres will not LIKE-compare it without
			// an explicit cast, unlike MySQL and SQL Server.
			$sql_count = 'SELECT COUNT("AP_ID") As "ApCount"'."\n"
				. "FROM wifi_ap\n"
				. "WHERE\n"
				. '"BSSID" <> \'00:00:00:00:00:00\' AND'."\n"
				. "fa IS NOT NULL AND fa != '1970-01-01 00:00:00.000' AND\n"
				. '"SSID" LIKE ? AND'."\n"
				. '"BSSID" LIKE ? AND'."\n"
				. '"RADTYPE" LIKE ? AND'."\n"
				. '"CHAN"::text LIKE ? AND'."\n"
				. '"AUTH" LIKE ? AND'."\n"
				. '"ENCR" LIKE ? '."\n";
			if($valid_gps){$sql_count .= ' AND "HighGps_ID" IS NOT NULL';}
			if($sectype){$sql_count .= ' AND "SECTYPE" =  ?';}
		}
		else
		{
			$sql_count = "SELECT COUNT(AP_ID) As ApCount\n"
				. "FROM wifi_ap\n"
				. "WHERE\n"
				. "BSSID <> '00:00:00:00:00:00' AND\n"
				. "fa IS NOT NULL AND fa != '1970-01-01 00:00:00.000' AND\n"
				. "SSID LIKE ? AND\n"
				. "BSSID LIKE ? AND\n"
				. "RADTYPE LIKE ? AND\n"
				. "CHAN LIKE ? AND\n"
				. "AUTH LIKE ? AND\n"
				. "ENCR LIKE ? \n";
			if($valid_gps){$sql_count .=" AND HighGps_ID IS NOT NULL";}
			if($sectype){$sql_count .=" AND SECTYPE =  ?";}
		}
		$prep1 = $this->sql->conn->prepare($sql_count);
		$prep1->bindParam(1, $ssid, PDO::PARAM_STR);
		$prep1->bindParam(2, $mac, PDO::PARAM_STR);
		$prep1->bindParam(3, $radio, PDO::PARAM_STR);
		$prep1->bindParam(4, $chan, PDO::PARAM_STR);
		$prep1->bindParam(5, $auth, PDO::PARAM_STR);
		$prep1->bindParam(6, $encry, PDO::PARAM_STR);
		if($sectype){$prep1->bindParam(7, $sectype, PDO::PARAM_INT);}
		$prep1->execute();
		$AP_ID_Count = $prep1->fetch(2);
		$total_rows = $AP_ID_Count['ApCount'];
		
		$sort_sql = $this->sql->sortIdent($sort);
		if($this->sql->service == "pgsql")
		{
			$sql = 'SELECT wap."ModDate", wap."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR", wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX", wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,'."\n"
				. 'wGPS."Lat" As "Lat",'."\n"
				. 'wGPS."Lon" As "Lon",'."\n"
				. 'wGPS."Alt" As "Alt",'."\n"
				. "wf.file_user As file_user\n"
				. "FROM wifi_ap AS wap\n"
				. 'LEFT JOIN wifi_gps AS wGPS ON wGPS."GPS_ID" = wap."HighGps_ID"'."\n"
				. 'LEFT JOIN files AS wf ON wf.id = wap."File_ID"'."\n"
				. "WHERE\n"
				. 'wap."BSSID" <> \'00:00:00:00:00:00\' AND'."\n"
				. "wap.fa IS NOT NULL AND wap.fa != '1970-01-01 00:00:00.000' AND\n"
				. 'wap."SSID" LIKE ? AND'."\n"
				. 'wap."BSSID" LIKE ? AND'."\n"
				. 'wap."RADTYPE" LIKE ? AND'."\n"
				. 'wap."CHAN"::text LIKE ? AND'."\n"
				. 'wap."AUTH" LIKE ? AND'."\n"
				. 'wap."ENCR" LIKE ?'."\n";
			if($valid_gps){$sql .= ' AND wap."HighGps_ID" IS NOT NULL';}
			if($sectype){$sql .= ' AND wap."SECTYPE" =  ?';}
		}
		else
		{
			$sql = "SELECT wap.ModDate, wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi,\n"
				. "wGPS.Lat As Lat,\n"
				. "wGPS.Lon As Lon,\n"
				. "wGPS.Alt As Alt,\n"
				. "wf.file_user As file_user\n"
				. "FROM wifi_ap AS wap\n"
				. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
				. "LEFT JOIN files AS wf ON wf.id = wap.File_ID\n"
				. "WHERE\n"
				. "BSSID <> '00:00:00:00:00:00' AND\n"
				. "fa IS NOT NULL AND fa != '1970-01-01 00:00:00.000' AND\n"
				. "wap.SSID LIKE ? AND\n"
				. "wap.BSSID LIKE ? AND\n"
				. "wap.RADTYPE LIKE ? AND\n"
				. "wap.CHAN LIKE ? AND\n"
				. "wap.AUTH LIKE ? AND\n"
				. "wap.ENCR LIKE ?\n";
			if($valid_gps){$sql .=" AND wap.HighGps_ID IS NOT NULL";}
			if($sectype){$sql .=" AND wap.SECTYPE =  ?";}
		}
		$sql .= " ORDER BY $sort_sql $ord";
		if($from !== NULL && $inc !== NULL){
			if($this->sql->service == "mysql"){$sql .=  " LIMIT ".$from.", ".$inc;}
			else if($this->sql->service == "pgsql"){$sql .=  " LIMIT ".$inc." OFFSET ".$from;}
			else if($this->sql->service == "sqlsrv"){$sql .=  " OFFSET ".$from." ROWS FETCH NEXT ".$inc." ROWS ONLY";}
		}

		$prep2 = $this->sql->conn->prepare($sql);
		$prep2->bindParam(1, $ssid, PDO::PARAM_STR);
		$prep2->bindParam(2, $mac, PDO::PARAM_STR);
		$prep2->bindParam(3, $radio, PDO::PARAM_STR);
		$prep2->bindParam(4, $chan, PDO::PARAM_STR);
		$prep2->bindParam(5, $auth, PDO::PARAM_STR);
		$prep2->bindParam(6, $encry, PDO::PARAM_STR);
		if($sectype){$prep2->bindParam(7, $sectype, PDO::PARAM_INT);}
		$prep2->execute();

		$latlon_array = array();
		$ap_array = array();
		$apcount = 0;
		$class = "light";
		$fetch_imports = $prep2->fetchAll();
		foreach($fetch_imports as $newArray)
		{
			$apcount++;
			if($newArray['Lat'] == "" && $newArray['Lon'] == ""){$validgps = 0;}else{$validgps = 1;}

			$ap_info = array(
			"id" => $newArray['AP_ID'],
			"new_ap" => $new_ap,
			"named" => $named,
			"mac" => $newArray['BSSID'],
			"ssid" => $this->formatSSID($newArray['SSID']),
			"chan" => $newArray['CHAN'],
			"radio" => $newArray['RADTYPE'],
			"nt" => $newArray['NETTYPE'],
			"sectype" => $newArray['SECTYPE'],
			"auth" => $newArray['AUTH'],
			"encry" => $newArray['ENCR'],
			"btx" => $newArray['BTX'],
			"otx" => $newArray['OTX'],
			"fa" => $newArray['fa'],
			"la" => $newArray['la'],
			"points" => $newArray['points'],
			"high_gps_sig" => $newArray['high_gps_sig'],
			"high_gps_rssi" => $newArray['high_gps_rssi'],
			"lat" => $this->convert->dm2dd($newArray['Lat']),
			"lon" => $this->convert->dm2dd($newArray['Lon']),
			"lat_dm" => $newArray['Lat'],
			"lon_dm" => $newArray['Lon'],
			"alt" => $newArray['Alt'],
			"manuf"=>$this->findManuf($newArray['BSSID']),
			"user" => $newArray['file_user'],
			"class" => $class,
			"validgps" => $validgps
			);

			$ap_array[] = $ap_info;
			
			$latlon_info = array(
			"lat" => $this->convert->dm2dd($newArray['Lat']),
			"long" => $this->convert->dm2dd($newArray['Lon']),
			);
			$latlon_array[] = $latlon_info;

			if($class == "light"){$class = "dark";}else{$class = "light";}
		}

		$ret_data = array(
			"count" => $apcount,
			"total_rows" => $total_rows,
			"data" => $ap_array,
			"latlongarray" => $latlon_array,
		);
		
		return $ret_data;
	}

	function GeoNamesArray($Latdd, $Londd, $from = NULL, $inc = NULL)
	{
		$lat_search = bcdiv($Latdd, 1, 1);
		$long_search = bcdiv($Londd, 1, 1);
		$list_geonames = array();
		
		if($this->sql->service == "pgsql")
		{
			// geonames.latitude/longitude are varchar; Postgres will not implicitly
			// convert them for radians() the way MySQL and SQL Server do.
			$sql = "SELECT  id, asciiname, country_code, admin1_code, admin2_code, timezone, latitude, longitude, \n"
				. "(3959 * acos(cos(radians('".$Latdd."')) * cos(radians(latitude::double precision)) * cos(radians(longitude::double precision) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(latitude::double precision)))) AS miles,\n"
				. "(6371 * acos(cos(radians('".$Latdd."')) * cos(radians(latitude::double precision)) * cos(radians(longitude::double precision) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(latitude::double precision)))) AS kilometers\n"
				. "FROM geonames \n"
				. "WHERE latitude LIKE '".$lat_search."%' AND longitude LIKE '".$long_search."%' ORDER BY kilometers ASC";
		}
		else
		{
			$sql = "SELECT  id, asciiname, country_code, admin1_code, admin2_code, timezone, latitude, longitude, \n"
				. "(3959 * acos(cos(radians('".$Latdd."')) * cos(radians(latitude)) * cos(radians(longitude) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(latitude)))) AS miles,\n"
				. "(6371 * acos(cos(radians('".$Latdd."')) * cos(radians(latitude)) * cos(radians(longitude) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(latitude)))) AS kilometers\n"
				. "FROM geonames \n"
				. "WHERE latitude LIKE '".$lat_search."%' AND longitude LIKE '".$long_search."%' ORDER BY kilometers ASC";
		}
		if($from !== NULL && $inc !== NULL){
			if($this->sql->service == "mysql"){$sql .=  "\nLIMIT ".$from.", ".$inc;}
			else if($this->sql->service == "pgsql"){$sql .=  "\nLIMIT ".$inc." OFFSET ".$from;}
			else if($this->sql->service == "sqlsrv"){$sql .=  "\nOFFSET ".$from." ROWS FETCH NEXT ".$inc." ROWS ONLY";}
		}
		$geoname_res = $this->sql->conn->query($sql);
		while ($GeonamesArray = $geoname_res->fetch(1))
		{
			if($GeonamesArray['id'] !== '')
			{
				$admin1 = $GeonamesArray['country_code'].".".$GeonamesArray['admin1_code'];
				if($this->sql->service == "mysql")
					{$sql = "SELECT `name` FROM `geonames_admin1` WHERE `admin1` = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "SELECT [name] FROM [geonames_admin1] WHERE [admin1] = ?";}
				else if($this->sql->service == "pgsql")
					{$sql = "SELECT name FROM geonames_admin1 WHERE admin1 = ?";}
				$prep_geonames = $this->sql->conn->prepare($sql);
				$prep_geonames->bindParam(1, $admin1);
				$prep_geonames->execute();
				$Admin1Array = $prep_geonames->fetch(2) ?: array();

				$admin2 = $GeonamesArray['country_code'].".".$GeonamesArray['admin1_code'].".".$GeonamesArray['admin2_code'];
				if($this->sql->service == "mysql")
					{$sql = "SELECT `name` FROM `geonames_admin2` WHERE `admin2` = ?";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "SELECT [name] FROM [geonames_admin2] WHERE [admin2] = ?";}
				else if($this->sql->service == "pgsql")
					{$sql = "SELECT name FROM geonames_admin2 WHERE admin2 = ?";}
				$prep_geonames = $this->sql->conn->prepare($sql);
				$prep_geonames->bindParam(1, $admin2);
				$prep_geonames->execute();
				$Admin2Array = $prep_geonames->fetch(2) ?: array();
				
				$list_geonames[]= array(
					'id'=>$GeonamesArray['id'],
					'asciiname'=>htmlspecialchars($GeonamesArray['asciiname'], ENT_QUOTES, 'UTF-8'),
					'country_code'=>htmlspecialchars($GeonamesArray['country_code'], ENT_QUOTES, 'UTF-8'),
					'timezone'=>htmlspecialchars($GeonamesArray['timezone'], ENT_QUOTES, 'UTF-8'),
					'miles'=>htmlspecialchars($GeonamesArray['miles'], ENT_QUOTES, 'UTF-8'),
					'kilometers'=>htmlspecialchars($GeonamesArray['kilometers'], ENT_QUOTES, 'UTF-8'),
					'lat'=>htmlspecialchars(number_format($GeonamesArray['latitude'],7), ENT_QUOTES, 'UTF-8'),
					'lon'=>htmlspecialchars(number_format($GeonamesArray['longitude'],7), ENT_QUOTES, 'UTF-8'),
					'lat_dm'=>htmlspecialchars($this->convert->all2dm(number_format($GeonamesArray['latitude'],7)), ENT_QUOTES, 'UTF-8'),
					'lon_dm'=>htmlspecialchars($this->convert->all2dm(number_format($GeonamesArray['longitude'],7)), ENT_QUOTES, 'UTF-8'),
					'admin1name'=>htmlspecialchars($Admin1Array['name'] ?? '', ENT_QUOTES, 'UTF-8'),
					'admin2name'=>htmlspecialchars($Admin2Array['name'] ?? '', ENT_QUOTES, 'UTF-8')
				);
			}
		}
		
		Return $list_geonames;
	}

	public function ExportDaemonKMZ($kmz_filepath, $type = "full", $only_new = 0, $new_icons = 0, $symlink_name = "")
	{
		$this->verbosed("Compiling Data for ".$type." Export. Labeled:".$this->named);

		if($type == "full")
		{
			if($this->sql->service == "pgsql")
			{
				$user_query = "SELECT DISTINCT(file_user) FROM files WHERE completed = 1 And \"ValidGPS\" = 1 ORDER BY file_user ASC";
				$user_list_query = "SELECT id, file_user, title, file_date FROM files WHERE file_user LIKE ? And completed = 1 And \"ValidGPS\" = 1";
			}
			else
			{
				$user_query = "SELECT DISTINCT(file_user) FROM files WHERE completed = 1 And ValidGPS = 1 ORDER BY file_user ASC";
				$user_list_query = "SELECT id, file_user, title, file_date FROM files WHERE file_user LIKE ? And completed = 1 And ValidGPS = 1";
			}
		}
		elseif($type == "daily")
		{
			#Get the last full export id
			$sql = "SELECT last_export_file FROM settings WHERE id = 1";
			$id_query = $this->sql->conn->query($sql);
			$id_fetch = $id_query->fetch(2) ?: array();
			$last_export_file = isset($id_fetch['last_export_file']) ? $id_fetch['last_export_file'] : 0;

			if($this->sql->service == "pgsql")
			{
				$user_query = "SELECT DISTINCT(file_user) FROM files WHERE completed = 1 And \"ValidGPS\" = 1 And id > '$last_export_file' ORDER BY file_user ASC";
				$user_list_query = "SELECT id, file_user, title, file_date FROM files WHERE completed = 1 And \"ValidGPS\" = 1 And file_user LIKE ? AND id > '$last_export_file' ORDER BY id DESC";
			}
			else
			{
				$user_query = "SELECT DISTINCT(file_user) FROM files WHERE completed = 1 And ValidGPS = 1 And id > '$last_export_file' ORDER BY file_user ASC";
				$user_list_query = "SELECT id, file_user, title, file_date FROM files WHERE completed = 1 And ValidGPS = 1 And file_user LIKE ? AND id > '$last_export_file' ORDER BY id DESC";
			}
		}	
		$this->verbosed($user_query);
		$this->verbosed($user_list_query);
		$ZipC = clone $this->Zip;
		
		#Get list of users and go through them
		$results="";
		$lists = 0;
		$prep_user = $this->sql->conn->query($user_query);
		$fetch_user = $prep_user->fetchAll();
		$prep_user_list = $this->sql->conn->prepare($user_list_query);
		foreach($fetch_user as $user)
		{
			#Get users lists and go through them
			$user_results = "";
			$user_files = 0;
			$username = $user['file_user'];
			$this->verbosed("---------------------".$username."---------------------");
			$prep_user_list->bindParam(1, $username, PDO::PARAM_STR);
			$prep_user_list->execute();
			$fetch_imports = $prep_user_list->fetchAll();
			foreach($fetch_imports as $import)
			{
				$id = $import['id'];
				$this->verbosed($username." - ".$import['file_date']." - ".$id." - ".$import['title']);
				$title = preg_replace(array('/\s/', '/\.[\.]+/', '/[^\w_\.\-]/'), array('_', '.', ''), $id.'_'.$import['title']);
				$UserListArray = $this->UserListArray($id, NULL, NULL, "AP_ID", "DESC", $this->named, $new_icons, $only_new);
				$AP_PlaceMarks = $this->createKML->CreateApFeatureCollection($UserListArray['data']);
				if($AP_PlaceMarks)
				{
					$final_box = $this->FindBox($UserListArray['latlongarray']);
					$KML_region = $this->createKML->PlotRegionBox($final_box, uniqid());
					$list_results = $KML_region.$AP_PlaceMarks;
					$list_results = $AP_PlaceMarks;

					#Create List KML Structure
					$list_results = $this->createKML->createFolder($title, $list_results, 0);
					$list_results = $this->createKML->createKMLstructure($title, $list_results);

					#Add list kml into final kmz
					if($this->named){$list_kml_name = $username."_".$title."_label.kml";}else{$list_kml_name = $username."_".$title.".kml";}
					$ZipC->addFile($list_results, 'files/'.$list_kml_name);
					unset($list_results);
					
					#Create Network Link to this kml for the final doc.kml
					$Netlink_region = $this->createKML->PlotRegionBox($final_box, uniqid());
					$user_results .= $this->createKML->createNetworkLink('files/'.$list_kml_name, $title.' ( List ID:'.$id.')' , 1, 0, "onChange", 86400, 0, $Netlink_region);

					#Increment variables (duh)
					++$user_files;
					++$lists;
				}
			}
			#If this user had results, create a folder with their data
			if($user_results){$results .= $this->createKML->createFolder($username.' ('.$user_files.' Files)', $user_results, 0);}
			unset($user_results);
		}
		#Create the final KMZ
		if($results == ""){$results = $this->createKML->createFolder("No Exports with GPS", $results, 0);}else{$results = $this->createKML->createFolder("All Exports", $results, 1);}
		#$regions_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/boundaries.kml', "Regions to save precious CPU cycles.", 1, 0, "once", 60);
		#$results .= $this->createKML->createFolder("WifiDB Newest AP", $regions_link, 1, 1);
		
		$results = $this->createKML->createFolder($type." Database Export", $results, 1);
		$results = $this->createKML->createKMLstructure("WiFiDB ".$type." Database Export", $results);
		
		$this->verbosed("Writing the ".$type." KMZ File. ($lists Lists) : ".$kmz_filepath);
		$ZipC->addFile($results, 'doc.kml');
		$ZipC->setZipFile($kmz_filepath);
		$ZipC->getZipFile();
		if (file_exists($kmz_filepath)) 
		{
			$this->verbosed("KMZ created at ".$kmz_filepath);
			chmod($kmz_filepath, 0664);
			if($symlink_name != "")
			{
				$link = $this->daemon_out.basename($symlink_name);
				$this->verbosed('Creating symlink from "'.$kmz_filepath.'" to "'.$link.'"');
				unlink($link);
				symlink($kmz_filepath, $link);
				chmod($link, 0664);
			}
			
			Return true;
		}
		else
		{
			$this->verbosed("KMZ file does not exist :/ ");
			Return false;
		}
	}

	function FindBox($points = array())
	{
		$North = NULL;
		$South = NULL;
		$East = NULL;
		$West = NULL;
		foreach($points as $elements)
		{
			$lat = $elements['lat'];
			$long = $elements['long'];
			
			if($North == NULL)
			{
				$North = $lat;
			}
			if($South == NULL)
			{
				$South = $lat;
			}

			if($East == NULL)
			{
				$East = $long;
			}
			if($West == NULL)
			{
				$West = $long;
			}

			if((float)$North < (float)$lat)
			{
				$North = $lat;
			}
			if((float)$South > (float)$lat)
			{
				$South = $lat;
			}
			if((float)$East < (float)$long)
			{
				$East = $long;
			}
			if((float)$West > (float)$long)
			{
				$West = $long;
			}
		}

		if(($this->distance($North, $East, $South, $East) <= 2) && ($this->distance($North, $East, $North, $West) <= 2))
		{
			$minLodPixels = 8;
			$RegionSize = 2;
			list($center_lat, $unused) = $this->get_midpoint($North, $East, $South, $East);
			list($unused, $center_lon) = $this->get_midpoint($North, $East, $North, $West);
			list($North, $unused) = $this->get_point($center_lat, $center_lon, 0, $RegionSize/2);
			list($South, $unused) = $this->get_point($center_lat, $center_lon, 180, $RegionSize/2);
			list($unused, $West) = $this->get_point($center_lat, $center_lon, 270, $RegionSize/2);
			list($unused, $East) = $this->get_point($center_lat, $center_lon, 90, $RegionSize/2);
		}
		elseif(($this->distance($North, $East, $South, $East) <= 4) && ($this->distance($North, $East, $North, $West) <= 4))
		{
			$minLodPixels = 16;
			$RegionSize = 4;
		}
		elseif(($this->distance($North, $East, $South, $East) <= 8) && ($this->distance($North, $East, $North, $West) <= 8))
		{
			$minLodPixels = 32;
			#$RegionSize = 8;
		}
		elseif(($this->distance($North, $East, $South, $East) <= 16) && ($this->distance($North, $East, $North, $West) <= 16))
		{
			$minLodPixels = 64;
			#$RegionSize = 16;
		}
		elseif(($this->distance($North, $East, $South, $East) <= 32) && ($this->distance($North, $East, $North, $West) <= 32))
		{
			$minLodPixels = 128;
			#$RegionSize = 32;
		}
		elseif(($this->distance($North, $East, $South, $East) <= 64) && ($this->distance($North, $East, $North, $West) <= 64))
		{
			$minLodPixels = 256;
			#$RegionSize = 64;
		}
		elseif(($this->distance($North, $East, $South, $East) <= 128) && ($this->distance($North, $East, $North, $West) <= 128))
		{
			$minLodPixels = 512;
			#$RegionSize = 128;
		}
		elseif(($this->distance($North, $East, $South, $East) <= 256) && ($this->distance($North, $East, $North, $West) <= 256))
		{
			$minLodPixels = 1024;
			#$RegionSize = 256;
		}
		else
		{
			$minLodPixels = 2048;
			#$RegionSize = 512;
		}
		
		$maxLodPixels = -1;
		
		return array( $North, $South, $East, $West, $minLodPixels, $maxLodPixels);
	}

	function distance($lat1, $lon1, $lat2, $lon2)
	{
		$radius = 6371;#radius in kilometers
		$rad1 = deg2rad($lat1);
		$rad2 = deg2rad($lat2);
		$del1 =  deg2rad($lat2-$lat1);
		$del2 =  deg2rad($lon2-$lon1);
		$a = sin($del1/2) * sin($del1/2) + cos($rad1) * cos($rad2) * sin($del2/2) * sin($del2/2);
		$c = 2 * atan2(sqrt($a), sqrt(1-$a));
		$d = $radius * $c;
		
		Return $d;
	}

	function get_point($lat1, $lon1, $bearing, $distance)
	{
		$radius = 6371;#radius in kilometers
		$rlat1 = deg2rad($lat1); 
		$rlon1 = deg2rad($lon1);
		$radial = deg2rad($bearing);
		$lat_rad = asin(sin($rlat1) * cos($distance/$radius) + cos($rlat1) * sin($distance/$radius) * cos($radial));
		$dlon_rad = atan(sin($radial) * sin($distance/$radius) * cos($rlat1)) / (cos($distance/$radius) - sin($rlat1) * sin($lat_rad));
		$lon_rad = fmod(($rlon1 + $dlon_rad + M_PI), 2 * M_PI) - M_PI;
		
		$coord[0] = rad2deg($lat_rad);
		$coord[1] = rad2deg($lon_rad);	
		return $coord;
	}
	
	function get_midpoint($lat1, $lon1, $lat2, $lon2)
	{
		$rlat1 = deg2rad($lat1);
		$rlat2 = deg2rad($lat2);
		$rlon1 = deg2rad($lon1);
		$rlon2 = deg2rad($lon2);
		$Bx = cos($rlat2) * cos($rlon2-$rlon1);
		$By = cos($rlat2) * sin($rlon2-$rlon1);
		$rlat3 = atan2(sin($rlat1) + sin($rlat2),sqrt((cos($rlat1)+$Bx)*(cos($rlat1)+$Bx) + $By*$By));
		$rlon3 = $rlon1 + atan2($By, cos($rlat1) + $Bx);
		
		$coord[0] = rad2deg($rlat3);
		$coord[1] = rad2deg($rlon3);	
		return $coord;
	}

	public function CreateBoundariesKML()
	{
		$boundaries_kml_file = $this->PATH.'out/daemon/boundaries.kml';
		$this->verbosed("Generating World Boundaries KML File : ".$boundaries_kml_file);

		if($this->sql->service == "mysql" || $this->sql->service == "pgsql")
			{$sql = "SELECT * FROM boundaries";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT * FROM [boundaries]";}
		$results = $this->sql->conn->query($sql);
		$fetched = $results->fetchAll(2);
		$KML_data = "";
		foreach($fetched as $boundary)
		{
			$KML_data .= $this->createKML->PlotBoundary($boundary);
		}

		$KMLFolderdata = $this->createKML->createFolder("World Boundaries", $KML_data, 0);
		$this->createKML->createKML($boundaries_kml_file, "World Boundaries", $KMLFolderdata);
		chmod($boundaries_kml_file, 0664);
		return $boundaries_kml_file;
	}

	/*
	 * Export to Garmin GPX File
	 */
	public function ExportGPXAll()
	{
		$this->verbosed("Starting GPX Export of WiFiDB.");

		$sql = "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points,\n"
			. "wGPS.Lat As Lat,\n"
			. "wGPS.Lon As Lon,\n"
			. "wGPS.Alt As Alt,\n"
			. "wf.file_user As file_user\n"
			. "FROM wifi_ap AS wap\n"
			. "LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID\n"
			. "LEFT JOIN files AS wf ON wap.File_ID = wf.id\n"
			. "WHERE wap.AP_ID = ? And wap.HighGps_ID IS NOT NULL";

		$prep = $this->sql->conn->execute($sql);
		$aparray_all = $prep->fetchAll(2);
		$this->verbosed("Pointers Table Queried.");
		$err = $this->sql->conn->errorCode();
		if($err[0] !== "00000")
		{
			$this->logd("Error fetching from Pointers table to generate GPX All: ".var_export($this->sql->conn->errorInfo(), 1));
			$this->verbosed("Error Fetching data from Pointers Table :(", -1);
			return -1;
		}

		foreach($aparray_all as $aparray)
		{
			$file_data  = "<?xml version=\"1.0\" encoding=\"UTF-8\" standalone=\"no\" ?>
<gpx xmlns=\"http://www.topografix.com/GPX/1/1\"
	creator=\"WiFiDB 0.16 Build 2\"
	version=\"1.1\"
	xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\"
	xsi:schemaLocation=\"http://www.topografix.com/GPX/1/1\">";
			// write file header buffer var

			$type = $aparray['SECTYPE'];
			switch($type)
			{
				case 1:
					$color = "Navaid, Green";
					break;
				case 2:
					$color = "Navaid, Amber";
					break;
				case 3:
					$color = "Navaid, Red";
					break;
				default:
					$color = "Navaid, Green";
					break;
			}
			$date = date("Y-m-d\TH:i:s.000\Z", strtotime($aparray["la"]));
			$alt = $aparray['Alt'] * 3.28;
			$lat = $this->convert->dm2dd($aparray['Lat']);
			$lon = $this->convert->dm2dd($aparray['Lon']);

			$file_data .= "<wpt lat=\"".$lat."\" lon=\"".$lon."\">\r\n"
				."<ele>".$alt."</ele>\r\n"
				."<time>".$date."</time>\r\n"
				."<name>".$aparray['SSID']."</name>\r\n"
				."<cmt>".$aparray['BSSID']."</cmt>\r\n"
				."<desc>".$this->findManuf($aparray['BSSID'])."</desc>\r\n"
				."<sym>".$color."</sym>\r\n<extensions>\r\n"
				."<gpxx:WaypointExtension xmlns:gpxx=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensions/v3/GpxExtensionsv3.xsd\">\r\n"
				."<gpxx:DisplayMode>SymbolAndName</gpxx:DisplayMode>\r\n<gpxx:Categories>\r\n"
				."<gpxx:Category>Category ".$type."</gpxx:Category>\r\n</gpxx:Categories>\r\n</gpxx:WaypointExtension>\r\n</extensions>\r\n</wpt>\r\n\r\n";

			#Get AP Signal History for this AP
			if($this->sql->service == "mysql")
				{
					$sql = "SELECT\n"
						. "wifi_hist.Sig, wifi_hist.RSSI, wifi_hist.Hist_Date,\n"
						. "wifi_gps.Lat, wifi_gps.Lon, wifi_gps.NumOfSats, wifi_gps.HorDilPitch, wifi_gps.Alt, \n"
						. "wifi_gps.Geo, wifi_gps.KPH, wifi_gps.MPH, wifi_gps.TrackAngle, wifi_gps.GPS_Date\n"
						. "FROM wifi_hist\n"
						. "LEFT JOIN wifi_gps ON wifi_hist.GPS_ID = wifi_gps.GPS_ID\n"
						. "WHERE wifi_hist.AP_ID = ? AND wifi_gps.Lat != '0.0000'\n"
						. "ORDER BY wifi_gps.GPS_Date ASC";
				}
			else if($this->sql->service == "sqlsrv")
				{
					$sql = "SELECT\n"
						. "[wifi_hist].[Sig], [wifi_hist].[RSSI], [wifi_hist].[Hist_Date],\n"
						. "[wifi_gps].[Lat], [wifi_gps].[Lon], [wifi_gps].[NumOfSats], [wifi_gps].[HorDilPitch], [wifi_gps].[Alt], \n"
						. "[wifi_gps].[Geo], [wifi_gps].[KPH], [wifi_gps].[MPH], [wifi_gps].[TrackAngle], [wifi_gps].[GPS_Date]\n"
						. "FROM [wifi_hist]\n"
						. "LEFT JOIN [wifi_gps] ON [wifi_hist].[GPS_ID] = [wifi_gps].[GPS_ID]\n"
						. "WHERE [wifi_hist].[AP_ID] = ? AND [wifi_gps].[Lat] != '0.0000'\n"
						. "ORDER BY [wifi_gps].[GPS_Date] ASC";
				}
			else if($this->sql->service == "pgsql")
				{
					$sql = "SELECT\n"
						. 'wifi_hist."Sig", wifi_hist."RSSI", wifi_hist."Hist_Date",'."\n"
						. 'wifi_gps."Lat", wifi_gps."Lon", wifi_gps."NumOfSats", wifi_gps."HorDilPitch", wifi_gps."Alt", '."\n"
						. 'wifi_gps."Geo", wifi_gps."KPH", wifi_gps."MPH", wifi_gps."TrackAngle", wifi_gps."GPS_Date"'."\n"
						. "FROM wifi_hist\n"
						. 'LEFT JOIN wifi_gps ON wifi_hist."GPS_ID" = wifi_gps."GPS_ID"'."\n"
						. 'WHERE wifi_hist."AP_ID" = ? AND wifi_gps."Lat" != 0.0000'."\n"
						. 'ORDER BY wifi_gps."GPS_Date" ASC';
				}
			$ap_query = $this->sql->conn->prepare($sql);
			$ap_query->bindParam(1, $aparray['AP_ID'], PDO::PARAM_INT);
			$ap_query->execute();
			$sig_gps_data = $ap_query->fetchAll(2);
			if(count($sig_gps_data) > 0)
			{
				$lat =& $this->convert->dm2dd($sig_gps_data['Lat']);
				$lon =& $this->convert->dm2dd($sig_gps_data['Lon']);
				$alt = $sig_gps_data['alt'] * 3.28;
				$date = date("Y-m-d\TH:i:s.000\Z", strtotime($sig_gps_data["GPS_Date"]));
				$file_data .= "<trkpt lat=\"".$lat."\" lon=\"".$lon."\">\r\n"
					."<ele>".$alt."</ele>\r\n"
					."<time>".$date."</time>\r\n"
					."</trkpt>\r\n";
			}
			$this->verbosed('Plotted AP: '.$aparray['ssid']);
		}

		$file_data .= "</trkseg>\r\n</trk></gpx>";
		$file_ext = "wifidb_".date($this->datetime_format).".gpx";
		$filename = ($this->gpx_out.$file_ext);
		$filewrite = fopen($filename, "w");
		if($filewrite == FALSE)
		{
			$this->logd("Error trying to write the GPX file: $filename");
			$this->verbosed("Error trying to write the GPX file: $filename  :(", -1);
			return -1;
		}
		$fileappend = fopen($filename, "a");
		fwrite($fileappend, $file_data);
		fclose($fileappend);

		#chmod($daily_folder.'/full_db'.$labeled.'.kml', 0750);

		return 1;
	}

	/*
	 * Generate the Daily Daemon KML files
	 */
	public function GenerateDaemonKMLData($verbose = 0)
	{
		$this->verbose = $verbose;
		$ForcedFullRun = 1;
		$full_folder = $this->PATH.'out/kmz/full/';
		$daily_folder = $this->PATH.'out/kmz/incremental/';
		$full_folder_url = $this->URL_PATH.'out/kmz/full/';
		$daily_folder_url = $this->URL_PATH.'out/kmz/incremental/';
		$filedate = date("Y-m-d_H-i-s");
		
		#Find if there has been a full export in the last 32 days. If there is a file less than 32 days, disable the forced full export.
		$full_files = glob($full_folder."labeled/*");
		$now   = time();
		foreach ($full_files as $full_file) 
		{
			if (is_file($full_file)) 
			{
				if ($now - filemtime($full_file) <= 60 * 60 * 24 * 32) {$ForcedFullRun = 0;}
			}
		}
		
		#Get the id of the latest imported file with gps
		if($this->sql->service == "mysql")
			{$sql = "SELECT id FROM files WHERE completed = 1 And ValidGPS = 1 ORDER BY file_date DESC LIMIT 1";}
		else if($this->sql->service == "sqlsrv")
			{$sql = "SELECT TOP 1 id FROM files WHERE completed = 1 And ValidGPS = 1 ORDER BY file_date DESC";}
		else if($this->sql->service == "pgsql")
			{$sql = "SELECT id FROM files WHERE completed = 1 And \"ValidGPS\" = 1 ORDER BY file_date DESC LIMIT 1";}
		$id_query = $this->sql->conn->query($sql);
		$id_fetch = $id_query->fetch(2);
		$Last_File_ID = $id_fetch['id'];
		
		#If a file with vaid gps was found, 
		if($Last_File_ID != '')
		{
			$Full_Exported = 0;
			$Full_Labeled_Exported = 0;
			$Incremental_Exported = 0;
			$Incremental_Labeled_Exported = 0;
			
			#Generate Full KMZ if it is the first of the month or full run forced.
			if(date('j') === '1' || $ForcedFullRun == 1)
			{
				#Generate Full Un-Labeled KMZ if it doesn't already exist
				$this->named = 0;
				$kmz_full_filepath = $full_folder."unlabeled/full_db_".$filedate.".kmz";
				$kmz_full_urlpath = $full_folder_url."unlabeled/full_db_".$filedate.".kmz";
				if(!file_exists($kmz_full_filepath))
				{
					$this->verbosed("Generating Full DB KML - ".$kmz_full_filepath);
					$this->ExportDaemonKMZ($kmz_full_filepath, "full", 1, 0, "full_db.kmz");
					if(file_exists($kmz_full_filepath)){$Full_Exported = 1;}
				}

				#Generate Full Labeled KMZ if it doesn't already exist
				$this->named = 1;
				$kmz_full_labeled_filepath = $full_folder."labeled/full_db_".$filedate."_labeled.kmz";
				$kmz_full_labeled_urlpath = $full_folder_url."labeled/full_db_".$filedate.".kmz";
				if(!file_exists($kmz_full_labeled_filepath))
				{
					$this->verbosed("Generating Full DB Labeled KML - ".$kmz_full_labeled_filepath);
					$this->ExportDaemonKMZ($kmz_full_labeled_filepath, "full", 1, 0, "full_db_labeled.kmz");
					if(file_exists($kmz_full_labeled_filepath)){$Full_Labeled_Exported = 1;}
				}
				
				#Set last full export id into the settings table
				$sql = "UPDATE settings SET last_export_file = ? WHERE id = 1";
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $Last_File_ID, PDO::PARAM_INT);
				$prep->execute();
			}

			#Generate Daily KML
			$this->named = 0;
			$kmz_increm_filepath = $daily_folder."unlabeled/daily_db_".$filedate.".kmz";
			$kmz_increm_urlpath = $daily_folder_url."unlabeled/daily_db_".$filedate.".kmz";
			$this->verbosed("Generating Daily KMZ - ".$kmz_increm_filepath);
			$this->ExportDaemonKMZ($kmz_increm_filepath, "daily" ,0 ,1, "daily_db.kmz");
			if(file_exists($kmz_increm_filepath)){$Incremental_Exported = 1;}
			
			#Generate Daily Labeled KML
			$this->named = 1;
			$kmz_increm_labeled_filepath = $daily_folder."labeled/daily_db_".$filedate."_labeled.kmz";
			$kmz_increm_labeled_urlpath = $daily_folder_url."labeled/daily_db_".$filedate."_labeled.kmz";
			$this->verbosed("Generating Daily Labeled KMZ - ".$kmz_increm_labeled_filepath);
			$this->ExportDaemonKMZ($kmz_increm_labeled_filepath, "daily" ,0 ,1, "daily_db_labeled.kmz");
			if(file_exists($kmz_increm_filepath)){$Incremental_Labeled_Exported = 1;}
			
			#Email Users
			if($Full_Exported || $Full_Labeled_Exported || $Incremental_Exported || $Incremental_Labeled_Exported) 
			{
				$subject = "Vistumbler WifiDB - New KMZ Exports";
				$message = "New KMZ Exports for $filedate. \r\nWifiDB Network Link: ".$this->URL_PATH."api/export.php?func=exp_combined_netlink \r\n";
				if($Full_Exported){$message .= "Full Export Download: $kmz_full_urlpath \r\n";}
				if($Full_Labeled_Exported){$message .= "Full Labeled Export Download: $kmz_full_labeled_urlpath \r\n";}
				if($Incremental_Exported){$message .= "Incremental Export Download: $kmz_increm_urlpath \r\n";}
				if($Incremental_Labeled_Exported){$message .= "Incremental Labeled Export Download: $kmz_increm_labeled_urlpath \r\n";}
				$this->wdbmail->mail_users($message, $subject, "kmz", 0);
			}

			#Generate History KML
			if($this->HistoryKMLLink() === -1)
			{
				$this->verbosed("Failed to Create Daemon History KML Links", -1);
			}else
			{
				$this->verbosed("Created Daemon History KML Links");
			}

			#Generate Update KML
			if($this->GenerateUpdateKML() === -1)
			{
				$this->verbosed("Failed to Create Update.kml File", -1);
			}else
			{
				$this->verbosed("Created Update.kml File");
			}
		}
		return 1;
	}

	/*
	 * Create the Archival KML links
	 */
	public function HistoryKMLLink()
	{
		$this->daemon_folder_stats['history'] = array();
		$daemon_export = $this->PATH."out/daemon/";
		$dir = opendir($daemon_export);
		$files = array();
		while ($file = readdir($dir))
		{
			if($file == "." || $file == ".." || $file == ".svn"){continue;}
			if(is_dir($daemon_export.$file))
			{
				$files[] = $file;
			}
		}
		sort($files);
		closedir($dir);

		foreach($files as $entry)
		{
			$matches = array();
			preg_match("/([0-9]{4}\-[0-9]{2}\-[0-9]{2})/", $entry, $matches, PREG_OFFSET_CAPTURE);
			if(@$matches[0])
			{
				$date_exp = explode("-", $entry);
				$year = $date_exp[0]+0;
				$month = $date_exp[1]+0;
				$day = $date_exp[2]+0;
				$month_label = $this->month_names[$month];
				$this->daemon_folder_stats['history'][$year][$month_label][$day] = $entry;
			}

		}
		$generated = array();
		foreach($this->daemon_folder_stats['history'] as $key=>$year)
		{
			$output = $daemon_export.'history/'.$key.'.kmz';
			$current_year = date("Y")+0;
			if(file_exists($output) && $key != $current_year)
			{
				$generated[] = $key.'.kmz';
				continue;
			}
			$kml_data = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Folder>
		<name>'.$key.'</name>
		<open>0</open>';

			foreach($year as $key1=>$month)
			{
				$kml_data .= '
		<Folder>
				<name>'.$key1.'</name>
				<open>0</open>';
				foreach($month as $key2=>$day)
				{
					if(file_exists($daemon_export.$day.'/daily_db.kmz'))
					{
						$daily_db_kmz_nl = '
						<NetworkLink>
								<name>Daily KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/daily_db.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$daily_db_kmz_nl = '';
					}

					if(file_exists($daemon_export.$day.'/daily_db_label.kmz'))
					{
						$daily_db_kmz_label_nl = '
						<NetworkLink>
								<name>Daily Labeled KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/daily_db_label.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$daily_db_kmz_label_nl = '';
					}

					if(file_exists($daemon_export.$day.'/full_db.kmz'))
					{
						$full_db_kmz_nl = '
						<NetworkLink>
								<name>Full DB KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/full_db.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$full_db_kmz_nl = '';
					}

					if(file_exists($daemon_export.$day.'/full_db_label.kmz'))
					{
						$full_db_label_kmz_nl = '
						<NetworkLink>
								<name>Full DB Labeled KMZ</name>
								<visibility>0</visibility>
								<Link>
										<href>'.$this->URL_PATH.'out/daemon/'.$day.'/full_db_label.kmz</href>
								</Link>
						</NetworkLink>';
					}else
					{
						$full_db_label_kmz_nl = '';
					}

					$kml_data .= '
				<Folder>
						<name>'.$key2.'</name>
						<open>0</open>'.$daily_db_kmz_nl.
						$daily_db_kmz_label_nl.
						$full_db_kmz_nl.
						$full_db_label_kmz_nl.'
				</Folder>';
				}
				$kml_data .= '</Folder>';
			}
			$kml_data .= '</Folder></kml>';
			
			$this->Zip->addFile($kml_data, 'doc.kml');
			$this->Zip->setZipFile($output);
			$this->Zip->getZipFile();
			
			if (file_exists($output)) 
			{
				$this->verbosed("KMZ created at ".$output);
				chmod($output, 0664);
			}
			else
			{
				$this->verbosed("Failed to Create KMZ file :/ ");
			}		

			$generated[] = $key.'.kmz';
		}

		$kml_data = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Folder>
		<name>WiFiDB Archive</name>
		<open>0</open>';
		foreach($generated as $year)
		{
			$year_name = str_replace(".kml", "", $year);
			$kml_data .= '
				<NetworkLink>
						<name>'.$year_name.'</name>
						<visibility>0</visibility>
						<Link>
								<href>'.$this->URL_PATH.'out/daemon/history/'.$year.'</href>
						</Link>
				</NetworkLink>';
		}
		$kml_data .= '
</Folder>
</kml>';
		$output = $daemon_export.'history.kml';
		
		$this->Zip->addFile($kml_data, 'doc.kml');
		$this->Zip->setZipFile($output);
		$this->Zip->getZipFile();
		
		if (file_exists($output)) 
		{
			$this->verbosed("KMZ created at ".$output);
			chmod($output, 0664);
		}
		else
		{
			$this->verbosed("Failed to Create KMZ file :/ ");
		}		
	}

	/*
	 * Generate the updated KML Link
	 */
	public function GenerateUpdateKML()
	{
		$full_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/full_db.kmz', "Full DB Export (No Label)", 0, 0, "onInterval", 3600).
			$this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/full_db_label.kmz', "Full DB Export (Label)", 0, 0, "onInterval", 3600);
		$full_folder = $this->createKML->createFolder("WifiDB Full DB Export", $full_link, 1, 1);

		$daily_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/daily_db.kmz', "Daily DB Export (No Label)", 1, 0, "onInterval", 3600).
			$this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/daily_db_label.kmz', "Daily DB Export (Label)", 0, 0, "onInterval", 3600);
		$daily_folder = $this->createKML->createFolder("WifiDB Daily DB Export", $daily_link, 1, 1);

		$new_AP_link = $this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=0',"Newest AP w/ Fly To (No Label)", 0, 1, "onInterval", 60).
			$this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=1',"Newest AP w/ Fly To (Labeled)", 0, 1, "onInterval", 60).
			$this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=0',"Newest AP (No Label)", 0, 0, "onInterval", 60).
			$this->createKML->createNetworkLink($this->URL_PATH.'api/latest.php?labeled=1',"Newest AP (Labeled)", 1, 0, "onInterval", 60);
		$new_AP_folder = $this->createKML->createFolder("WifiDB Newest AP", $new_AP_link, 1, 1);

		//$archive_link = $this->createKML->createNetworkLink($this->URL_PATH.'out/daemon/history.kmz', "Archived History", 0, 0, "onInterval", 86400);
		//$archive_folder = $this->createKML->createFolder("Historical Archives", $archive_link, 1);

		//$KML_data = $full_folder.$daily_folder.$new_AP_folder.$regions_folder;#.$archive_folder;
		$KML_data = $new_AP_folder.$daily_folder.$full_folder;#.$archive_folder;
		$KML_data = $this->createKML->createKMLstructure("Combined KMZ Network Link", $KML_data);
		
		$kmz_filename = $this->daemon_out.'update.kmz';
		$this->verbosed("Writing KMZ : ".$kmz_filename);
		$this->Zip->addFile($KML_data, 'doc.kml');
		$this->Zip->setZipFile($kmz_filename);
		$this->Zip->getZipFile();
		
		if (file_exists($kmz_filename)) 
		{
			$this->verbosed("KMZ created at ".$kmz_filename);
			chmod($kmz_filename, 0664);
		}
		else
		{
			$this->verbosed("Failed create KMZ file :/ ");
		}
		
		return $kmz_filename;
	}
}
