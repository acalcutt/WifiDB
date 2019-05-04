<?php
/*
apiv2.inc.php, holds the WiFiDB API V2 functions.
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
class apiv2 extends dbcore
{
    function __construct($config, &$SQL)
    {
        parent::__construct($config, $SQL);
        $this->startdate	= "2016-Jan-10";
        $this->lastedit	    = "2016-Jan-10";
        $this->vernum	    = "2.1";
        $this->Author	    = "Phil Ferland";
        $this->contact	    = "pferland@randomintervals.com";
        $this->output	    = (@$_REQUEST['output']	? strtolower($_REQUEST['output']) : "json");
        $this->username	    = (@$_REQUEST['username']  ? @$_REQUEST['username'] : "AnonCoward" );
        #var_dump($this->username);
        $this->EnableAPIKey = $config['EnableAPIKey'];
        $this->mesg	        = array();
        $this->GeoNamesLoopGiveUp = $config['GeoNamesLoopGiveUp'];
        $this->verbose      = 1;
        #$this->EnableAPIKey = 0;
        if($this->EnableAPIKey && !(SWITCH_SCREEN === "CLI"))
        {
            $this->sec->ValidateAPIKey();
            if(!$this->sec->login_check)
            {
                $this->mesg = $this->sec->mesg;
                $this->Output();
            }
            #var_dump($this->sec->login_check);
            #var_dump($this->sec->mesg);
        }else
        {
            #var_dump($this->sec->login_check);
            #var_dump($this->sec->mesg);
        }
    }

    public function createPIDFile()
    {

    }

    public function GetLiveAP($ap_id = 0)
    {
        if($ap_id === 0)
        {
			$this->mesg = array("error" => "AP ID was 0, that cant be...");
            return 0;
        }
        $APSelectSQL = "SELECT ssid, mac, auth, encry, sectype,
                          chan, radio, BTx, OTx, NT, Label, FA, LA
                        FROM live_aps WHERE id = ?";
        $ap_prep = $this->sql->conn->prepare($APSelectSQL);
        $ap_prep->bindParam(1, $ap_id, PDO::PARAM_STR);
        #var_dump("Before JOIN query: ".microtime(1));
        $this->sql->checkError($ap_prep->execute(), __LINE__, __FILE__);

        #var_dump("After JOIN query: ".microtime(1));
        $APFetch = $ap_prep->fetchAll(2);

        $SigHistSQL = "SELECT
                    live_gps.lat, live_gps.long, live_gps.sats, live_gps.hdp,
                    live_gps.alt, live_gps.geo, live_gps.kmh, live_gps.mph, live_gps.track, live_gps.timestamp AS GPS_timestamp,
                    live_signals.signal, live_signals.rssi, live_signals.timestamp AS signal_timestamp
                     FROM live_aps INNER JOIN live_signals ON
                         live_signals.ap_id=live_aps.id INNER JOIN
                         live_gps ON live_gps.id=live_signals.gps_id WHERE live_aps.id = ?";
        $ap_prep = $this->sql->conn->prepare($SigHistSQL);
        $ap_prep->bindParam(1, $ap_id, PDO::PARAM_STR);
        #var_dump("Before JOIN query: ".microtime(1));
        $this->sql->checkError($ap_prep->execute(), __LINE__, __FILE__);
        #var_dump("After JOIN query: ".microtime(1));
        $SignalDataFetch = $ap_prep->fetchAll(2);
        return array('apdata'=> $APFetch, 'gdata'=> $SignalDataFetch);
    }

	public function GeoNames($lat, $long)
	{
		$lat_search = bcdiv($lat, 1, 1);
		$long_search = bcdiv($long, 1, 1);
		
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT  id, asciiname, country_code, admin1_code, admin2_code, timezone, latitude, longitude, \n"
					. "(3959 * acos(cos(radians('".$Latdd."')) * cos(radians(latitude)) * cos(radians(longitude) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(latitude)))) AS miles,\n"
					. "(6371 * acos(cos(radians('".$Latdd."')) * cos(radians(latitude)) * cos(radians(longitude) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(latitude)))) AS kilometers\n"
					. "FROM geonames \n"
					. "WHERE latitude LIKE '".$lat_search."%' AND longitude LIKE '".$long_search."%' ORDER BY kilometers ASC LIMIT 1";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT TOP 1 [id], [asciiname], [country_code], [admin1_code], [admin2_code], [timezone], [latitude], [longitude], \n"
					. "(3959 * acos(cos(radians('".$Latdd."')) * cos(radians([latitude])) * cos(radians([longitude]) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians([latitude])))) AS [miles],\n"
					. "(6371 * acos(cos(radians('".$Latdd."')) * cos(radians([latitude])) * cos(radians([longitude]) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians([latitude])))) AS [kilometers]\n"
					. "FROM [geonames] \n"
					. "WHERE [latitude] LIKE '".$lat_search."%' AND [longitude] LIKE '".$long_search."%' ORDER BY [kilometers] ASC";
				#echo $sql;
			}
		$geoname_res = $this->sql->conn->query($sql);
		$GeonamesArray = $geoname_res->fetch(2);
		if($GeonamesArray['id'])
		{
			$admin1 = $GeonamesArray['country_code'].".".$GeonamesArray['admin1_code'];
			if($this->sql->service == "mysql")
				{$sql = "SELECT name FROM geonames_admin1 WHERE admin1 = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT [name] FROM [geonames_admin1] WHERE [admin1] = ?";}
			$prep_geonames = $this->sql->conn->prepare($sql);
			$prep_geonames->bindParam(1, $admin1, PDO::PARAM_STR);
			$prep_geonames->execute();
			$Admin1Array = $prep_geonames->fetch(2);

			$admin2 = $GeonamesArray['country_code'].".".$GeonamesArray['admin1_code'].".".$GeonamesArray['admin2_code'];
			if($this->sql->service == "mysql")
				{$sql = "SELECT name FROM geonames_admin2 WHERE admin2 = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT [name] FROM [geonames_admin2] WHERE [admin2] = ?";}
			$prep_geonames = $this->sql->conn->prepare($sql);
			$prep_geonames->bindParam(1, $admin2, PDO::PARAM_STR);
			$prep_geonames->execute();
			$Admin2Array = $prep_geonames->fetch(2);
			
			if($this->sql->service == "mysql")
				{$sql = "SELECT Country FROM geonames_country_names WHERE ISO LIKE ? LIMIT 1";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT TOP 1 Country FROM geonames_country_names WHERE ISO LIKE ?";}
			$country_res = $this->sql->conn->prepare($sql);
			$code = $GeonamesArray['country_code']."%";
			$country_res->bindParam(1, $code, PDO::PARAM_STR);
			$country_res->execute();
			$country_array = $country_res->fetch(1);
			
			$this->mesg = array(
				'Country Code'=> str_replace("%20", " ", $GeonamesArray['country_code']),
				'Country Name'=> str_replace("%20", " ", $country_array['Country']),
				'Admin1 Code'=> str_replace("%20", " ", $GeonamesArray['admin1_code']),
				'Admin1 Name'=>(@$Admin1Array['name'] ? str_replace("%20", " ", $Admin1Array['name']) : ""),
				'Admin2 Name'=>(@$Admin2Array['name'] ? str_replace("%20", " ", $Admin2Array['name']) : ""),
				'Area Name'=> str_replace("%20", " ", $GeonamesArray['asciiname']),
				'miles'=>$GeonamesArray['miles'],
				'km'=>$GeonamesArray['kilometers'],
				'feet'=>$GeonamesArray['miles']*5280
			);
		}
		else
		{
			$this->mesg = "No Geonames Found";
		}
		return 1;
	}

    public function GetWaitingScheduleTable()
    {
        if($this->AllDateRange == 1) {
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files_tmp");
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
				$this->mesg = array("error" => "StartDate or EndDate are not set.");
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files_tmp WHERE date >= ? AND date <= ?");
            $schedule_prep->bindParam(1, $this->StartDate, PDO::PARAM_STR);
            $schedule_prep->bindParam(2, $this->EndDate, PDO::PARAM_STR);
        }
        $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        $return = $schedule_prep->fetchAll(2);
        if(count($return) < 1)
        {
            $this->mesg['schedule'] = "No Imports running.";
        }else
        {
            $i = 1;
            $altered = array();
            foreach($return as $value)
            {
                $altered["Waiting".$i] = $value;
                $i++;
            }

            $this->mesg['schedule'] = $altered;
        }
        return 0;
    }

    public function GetImportingScheduleTable()
    {
        if($this->AllDateRange === 1) {
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files_importing");
            $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
				$this->mesg = array("error" => "StartDate or EndDate are not set.");
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files_importing WHERE date >= ? AND date <= ?");
            $schedule_prep->bindParam(1, $this->StartDate, PDO::PARAM_STR);
            $schedule_prep->bindParam(2, $this->EndDate, PDO::PARAM_STR);
        }
        $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        $return = $schedule_prep->fetch(2);

        if(count($return) < 1)
        {
            $this->mesg['schedule'] = "No Imports running.";
        }else
        {
            $this->mesg['schedule'] = $return;
        }
        return 0;
    }

    public function GetFinishedScheduleTable()
    {
        if($this->AllDateRange === 1) {
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files");
            $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
				$this->mesg = array("error" => "StartDate or EndDate are not set.");
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files WHERE date >= ? AND date <= ?");
            $schedule_prep->bindParam(1, $this->StartDate, PDO::PARAM_STR);
            $schedule_prep->bindParam(2, $this->EndDate, PDO::PARAM_STR);
        }
        $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        $return = $schedule_prep->fetch(2);

        if(count($return) < 1)
        {
            $this->mesg['schedule'] = "No Imports running.";
        }else
        {
            $this->mesg['schedule'] = $return;
        }
        return 0;
    }

    public function GetBadScheduleTable()
    {
        if($this->AllDateRange === 1) {
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files_bad");
            $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
				$this->mesg = array("error" => "StartDate or EndDate are not set.");
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM files_bad WHERE date >= ? AND date <= ?");
            $schedule_prep->bindParam(1, $this->StartDate, PDO::PARAM_STR);
            $schedule_prep->bindParam(2, $this->EndDate, PDO::PARAM_STR);
        }
        $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        $return = $schedule_prep->fetch(2);

        if(count($return) < 1)
        {
            $this->mesg['schedule'] = "No Imports running.";
        }else
        {
            $this->mesg['schedule'] = $return;
        }
        return 0;
    }

    public function GetDaemonStatuses()
    {
        $sql = "SELECT nodename, pidfile, pid, pidtime, pidmem, pidcmd, date FROM daemon_pid_stats";
        $result = $this->sql->conn->query($sql);
        $result->execute();
        $fetch = $result->fetchAll(2);
        //var_dump($fetch);
        if(count($fetch) < 1)
        {
            $this->mesg['daemons'] = "No Daemons running.";
        }else
        {
            $i = 1;
            $altered = array();
            foreach($fetch as $value)
            {
                $altered["daemon".$i] = $value;
                $i++;
            }

			$this->mesg = array("daemons" => $altered);
        }
        return 0;
    }

    public function CheckHash($hash)
    {
        if($hash == "")
        {
            $this->mesg = array("error"=>"No hash has been given to check. there is nothing to do here, my job is done.");
            return -1;
        }
		
		if($this->sql->service == "mysql")
			{$files_prep = $this->sql->conn->prepare("SELECT id, UPPER(hash) AS hash, file, user, notes, title, size, date, converted, node_name, prev_ext, completed, aps, gps FROM files WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$files_prep = $this->sql->conn->prepare("SELECT TOP 1 id, UPPER(hash) AS hash, [file], [user], notes, title, size, date, converted, node_name, prev_ext, completed, aps, gps FROM files WHERE hash = ?");}
		$files_prep->bindParam(1, $hash, PDO::PARAM_STR);

		if($this->sql->service == "mysql")
			{$imp_prep = $this->sql->conn->prepare("SELECT id, UPPER(hash) AS hash, file, user, notes, title, size, date, converted, prev_ext, importing, ap, tot FROM files_importing WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$imp_prep = $this->sql->conn->prepare("SELECT TOP 1 id, UPPER(hash) AS hash, [file], [user], notes, title, size, date, converted, prev_ext, importing, ap, tot FROM files_importing WHERE hash = ?");}
		$imp_prep->bindParam(1, $hash, PDO::PARAM_STR);

		if($this->sql->service == "mysql")
			{$tmp_prep = $this->sql->conn->prepare("SELECT id, UPPER(hash) AS hash, file, user, notes, title, size, date, converted, prev_ext FROM files_tmp WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$tmp_prep = $this->sql->conn->prepare("SELECT TOP 1 id, UPPER(hash) AS hash, [file], [user], notes, title, size, date, converted, prev_ext FROM files_tmp WHERE hash = ?");}
		$tmp_prep->bindParam(1, $hash, PDO::PARAM_STR);

		if($this->sql->service == "mysql")
			{$bad_prep = $this->sql->conn->prepare("SELECT id, UPPER(hash) AS hash, file, user, notes, title, size, date, converted, thread_id, node_name, prev_ext, error_msg FROM files_bad WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$bad_prep = $this->sql->conn->prepare("SELECT TOP 1 id, UPPER(hash) AS hash, [file], [user], notes, title, size, date, converted, thread_id, node_name, prev_ext, error_msg FROM files_bad WHERE hash = ?");}
		$bad_prep->bindParam(1, $hash, PDO::PARAM_STR);

		$files_prep->execute();
		$imp_prep->execute();
		$tmp_prep->execute();
		$bad_prep->execute();
		
		$files_ret = $files_prep->fetch(2);
		$imp_ret = $imp_prep->fetch(2);
		$tmp_ret = $tmp_prep->fetch(2);
		$bad_ret = $bad_prep->fetch(2);

        if($files_ret['hash'] != "")
        {
            if($imp_ret['hash'] != "")
            {
                $this->mesg['scheduling'] = array("importing"=>$imp_ret);
            }else{
                $this->mesg['scheduling'] = array("finished"=>$files_ret);
            }
        }
        elseif($tmp_ret['hash'] != "")
        {
            $this->mesg['scheduling'] = array("waiting"=>$tmp_ret);
        }
        elseif($bad_ret['hash'] != "")
        {
            $this->mesg['scheduling'] = array("bad"=>$bad_ret);
        }
        else
        {
            $this->mesg['scheduling'] = array("unknown"=>"Hash not found in WifiDB");
        }
        return 1;
    }

    public function ImportVS1($details = array())
    {
        $user		   = $details['user'];
        $otherusers	 = $details['otherusers'];
        $date		   = $details['date'];
        $title		  = $details['title'];
        $notes		  = $details['notes'];
        $size		   = $details['size'];
        $hash		   = $details['hash'];
        $ext			= $details['ext'];
        $filename	   = $details['filename'];
		$file_orig	   = $details['file_orig'];

        if(substr($user, -1) == "|")
        {
            $user = str_replace("|", "", $user);
        }else
        {
            $exp = explode("|", $user);
            $user = $exp[0];
        }
		if($this->sql->service == "mysql")
			{$tmp_prep = $this->sql->conn->prepare("SELECT hash FROM files_tmp WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$tmp_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files_tmp WHERE hash = ?");}
		$tmp_prep->bindParam(1, $hash, PDO::PARAM_STR);
		$tmp_prep->execute();
		if($this->sql->service == "mysql")
			{$files_prep = $this->sql->conn->prepare("SELECT hash FROM files WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$files_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files WHERE hash = ?");}
		$files_prep->bindParam(1, $hash, PDO::PARAM_STR);
		$files_prep->execute();

        $tmp_ret = $tmp_prep->fetch(2);
        $files_ret = $files_prep->fetch(2);
        if($tmp_ret['hash'] != "")
        {
			$this->mesg = array("error" => "File Hash already waiting for import: ".$hash);
            return -1;
        }
        if($files_ret['hash'] != "")
        {
			$this->mesg = array("error" => "File Hash already exists in WiFiDB:  $hash");
            return -1;
        }
        $this->mesg['import']["title"] = $title;
        $this->mesg['import']["user"] = $user;
        if($otherusers)
        {
            $this->mesg['import']['otherusers'] = $otherusers;
        }

        switch($ext)
        {
			case "vs1":
				$task = "import";
				$type = "vistumbler";
			break;
			case "txt":
				$task = "import";
				$type = "vistumbler";
			break;
			case "vsz":
				$task = "import";
				$type = "vistumbler";
			break;
			case "csv":
				$task = "import";
				$type = "vistumbler";
			break;
			case "mdb":
				$task = "import";
				$type = "vistumbler";
			break;			
			case "db3":
				$task = "import";
				$type = "wardrive";
			break;
			case "db":
				$task = "import";
				$type = "wardrive";
			break;
			default:
				$task = "";
				$type = "";
			break;
        }

        switch($task)
        {
            case "import":
				if($this->sql->service == "mysql")
					{$sql = "INSERT INTO files_tmp(file, file_orig, date, user, otherusers, notes, title, size, hash, type) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}
				else if($this->sql->service == "sqlsrv")
					{$sql = "INSERT INTO [files_tmp]([file], [file_orig], [date], [user], [otherusers], [notes], [title], [size], [hash], [type]) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}

				$result = $this->sql->conn->prepare( $sql );
				$result->bindValue(1, $filename, PDO::PARAM_STR);
				$result->bindValue(2, $file_orig, PDO::PARAM_STR);
				$result->bindValue(3, $date, PDO::PARAM_STR);
				$result->bindValue(4, $user, PDO::PARAM_STR);
				$result->bindValue(5, $otherusers, PDO::PARAM_STR);
				$result->bindValue(6, $notes, PDO::PARAM_STR);
				$result->bindValue(7, $title, PDO::PARAM_STR);
				$result->bindValue(8, $size, PDO::PARAM_STR);
				$result->bindValue(9, $hash, PDO::PARAM_STR);
				$result->bindValue(10, $type, PDO::PARAM_STR);
				$result->execute();
				$this->mesg['import']["message"] = "File has been inserted for importing at a scheduled time.";
				$this->mesg['import']["importnum"] = $this->sql->conn->lastInsertId();
				$this->mesg['import']["filehash"] = $hash;
				return 1;
                break;
            default:
                $this->mesg = array("error" => "Failure.... File is not supported. Try one of the supported file http://wifidb.net/wifidb/import/?func=supported_files");
                break;
        }
        return 1;
    }

	public function InsertLiveAP($data = array())
	{
		if(empty($data)){$this->mesg = array("error"=>"Emtpy data set");return 0;}
		$data['mac'] = preg_replace('/..(?!$)/', '$0:', $data['mac']);
		$ap_hash = md5($data['ssid'].$data['mac'].$data['chan'].$data['sectype'].$data['auth'].$data['encry']);

				
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT id, ssid, mac, chan, sectype, auth, encry, radio, session_id, sig, lat, long FROM
						live_aps
						WHERE ap_hash = ?
						AND session_id = ?
						AND username = ? LIMIT 1";
			}
		else if($this->sql->service == "sqlsrv")
			{
				$sql = "SELECT TOP 1 id, ssid, mac, chan, sectype, auth, encry, radio, session_id, sig, lat, long FROM
						live_aps
						WHERE ap_hash = ?
						AND session_id = ?
						AND username = ?";
			}
		$result = $this->sql->conn->prepare($sql);
		$result->bindParam(1, $ap_hash, PDO::PARAM_STR);
		$result->bindParam(2, $data['session_id'], PDO::PARAM_STR);
		$result->bindParam(3, $data['username'], PDO::PARAM_STR);
		$result->execute();
		$err = $this->sql->conn->errorCode();
		if($err !== "00000")
		{
			$this->mesg = array("error"=>array("desc"=>"Error selecting AP data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
			return -1;
		}
		$array = $result->fetch(2);
		if(@$array['id'])
		{
			$AP_id = $array['id'];
			$this->mesg = "It's an old AP :/" ;

			$all_sigs = $array['sig'];

			$sig_exp = explode("~", $all_sigs);

			$sig_c = count($sig_exp)-1;
			if(!$sig_c)
			{
				$sig_exp_id = explode("|", $array['sig']);
				$id = $sig_exp_id[1];
			}else
			{
				$sig_exp_id = explode("|", $sig_exp[$sig_c]);
				$id = $sig_exp_id[1];
			}

			$sql = "SELECT * FROM live_gps WHERE id = ?";
			$result = $this->sql->conn->prepare($sql);
			$result->bindParam(1, $id, PDO::PARAM_STR);
			$result->execute();
			$err = $this->sql->conn->errorCode();
			if($err !== "00000")
			{
				$this->mesg = array("error"=>array("desc"=>"Error selecting data from Live GPS Table.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
				return -1;
			}
			$array = $result->fetch(2);
			if( (!strcmp($array['lat'], $data['lat'])) && (!strcmp($array['long'], $data['long'])) )
			{
				$sql_sig = "INSERT INTO live_signals
						(signal, rssi, gps_id, ap_hash, time_stamp)
						VALUES (?, ?, ?, ?, ?)";
				$time_stamp = $data['date']." ".$data['time'];
				$prep_sig = $this->sql->conn->prepare($sql_sig);
				$prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
				$prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_STR);
				$prep_sig->bindParam(3, $id, PDO::PARAM_INT);
				$prep_sig->bindParam(4, $ap_hash, PDO::PARAM_STR);
				$prep_sig->bindParam(5, $time_stamp, PDO::PARAM_INT);
				$prep_sig->execute();
				$err = $this->sql->conn->errorCode();
				if($err !== "00000")
				{
					$this->mesg = array("error"=>array("desc"=>"Error adding Signal data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
					return -1;
				}else
				{
					$this->mesg = "Added Signal data.";
				}

				$sig = $all_sigs."~".$this->sql->conn->lastInsertId()."|".$id;
				$date_time = $data['date']." ".$data['time'];
				$this->mesg = "Lat/Long are the same, move a little you lazy bastard.";
				$sql = "UPDATE live_aps SET LA = ?, sig = ? WHERE id = ?";
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $date_time, PDO::PARAM_STR);
				$prep->bindParam(2, $sig, PDO::PARAM_STR);
				$prep->bindParam(3, $AP_id, PDO::PARAM_INT);
				$prep->execute();
				$err = $this->sql->conn->errorCode();
				if($err !== "00000")
				{
					$this->mesg = array("error"=>array("desc"=>"Error updating AP data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
					return -1;
				}else
				{
					$this->mesg = "Updated AP Last Active and Signal.";
				}
			}else
			{
				$this->mesg = "Lat/Long are different, what aboot the Sats and Date/Time, Eh?";
				$url_time   = $data['date']." ".$data['time'];
				$wifi_time	= $array['date']." ".$array['time'];
				$timecalc   = ($url_time - $wifi_time);
				$this->mesg = "Oooo its time is newer o_0, lets go insert it ;)";
				$sql = "INSERT INTO live_gps (lat, long, sats, hdp, alt, geo, kmh, mph, track, date, time, session_id)
											   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $data['lat'], PDO::PARAM_INT);
				$prep->bindParam(2, $data['long'], PDO::PARAM_INT);
				$prep->bindParam(3, $data['sats'], PDO::PARAM_INT);
				$prep->bindParam(4, $data['hdp'], PDO::PARAM_INT);
				$prep->bindParam(5, $data['alt'], PDO::PARAM_INT);
				$prep->bindParam(6, $data['geo'], PDO::PARAM_INT);
				$prep->bindParam(7, $data['kmh'], PDO::PARAM_INT);
				$prep->bindParam(8, $data['mph'], PDO::PARAM_INT);
				$prep->bindParam(9, $data['track'], PDO::PARAM_INT);
				$prep->bindParam(10, $data['date'], PDO::PARAM_STR);
				$prep->bindParam(11, $data['time'], PDO::PARAM_STR);
				$prep->bindParam(12, $data['session_id'], PDO::PARAM_STR);
				$prep->execute();
				$gps_id = $this->sql->conn->lastInsertId();
				$err = $this->sql->conn->errorCode();
				if($err !== "00000")
				{
					$this->mesg = array("error"=>array("desc"=>"Error adding GPS data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
					return -1;
				}else
				{
					$this->mesg = "Added GPS data.";
				}

				$sql_sig = "INSERT INTO live_signals
					(signal, rssi, gps_id, ap_hash, time_stamp)
					VALUES (?, ?, ?, ?, ?)";
				$time_stamp = $data['date']." ".$data['time'];
				$prep_sig = $this->sql->conn->prepare($sql_sig);
				$prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
				$prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_INT);
				$prep_sig->bindParam(3, $data['gps_id'], PDO::PARAM_INT);
				$prep_sig->bindParam(4, $ap_hash, PDO::PARAM_STR);
				$prep_sig->bindParam(5, $time_stamp, PDO::PARAM_INT);
				$prep_sig->execute();

				$err = $this->sql->conn->errorCode();
				if($err !== "00000")
				{
					$this->mesg = array("error"=>array("desc"=>"Error adding Signal data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
					return -1;
				}else
				{
					$this->mesg = "Added Signal data.";
				}

				$sig = $all_sigs."~".$this->sql->conn->lastInsertId()."|".$gps_id;

				$sql = "UPDATE live_aps SET sig = ?, LA = ?, lat = ?, long = ? WHERE id = ?";
				#echo $sql."<br /><br />";
				$date_time = $data['date']." ".$data['time'];
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $sig, PDO::PARAM_STR);
				$prep->bindParam(2, $date_time, PDO::PARAM_STR);
				$prep->bindParam(3, $data['lat'], 2);
				$prep->bindParam(4, $data['long'], 2);
				$prep->bindParam(5, $AP_id, 1);
				$prep->execute();
				$err = $this->sql->conn->errorCode();
				if($err !== "00000")
				{
					$this->mesg = array("error"=>array("desc"=>"Error updating AP data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
					return -1;
				}else
				{
					$this->mesg = "Updated AP data.";
				}
			}
		}else
		{
			$this->mesg = "Add new AP. :]";
			$sql = "INSERT INTO live_gps (lat, long, sats, hdp, alt, geo, kmh, mph, track, date, time, session_id)
												   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $data['lat'], PDO::PARAM_STR);
			$prep->bindParam(2, $data['long'], PDO::PARAM_STR);
			$prep->bindParam(3, $data['sats'], PDO::PARAM_INT);
			$prep->bindParam(4, $data['hdp'], PDO::PARAM_INT);
			$prep->bindParam(5, $data['alt'], PDO::PARAM_INT);
			$prep->bindParam(6, $data['geo'], PDO::PARAM_INT);
			$prep->bindParam(7, $data['kmh'], PDO::PARAM_INT);
			$prep->bindParam(8, $data['mph'], PDO::PARAM_INT);
			$prep->bindParam(9, $data['track'], PDO::PARAM_STR);
			$prep->bindParam(10, $data['date'], PDO::PARAM_STR);
			$prep->bindParam(11, $data['time'], PDO::PARAM_STR);
			$prep->bindParam(12, $data['session_id'], PDO::PARAM_STR);
			$prep->execute();

			$gps_id = $this->sql->conn->lastInsertId();

			$err = $this->sql->conn->errorCode();
			if($err !== "00000")
			{
				$this->mesg = array("error"=>array("desc"=>"Error adding GPS data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
				return -1;
			}else
			{
				$this->mesg = "Added GPS data.";
			}

			$sql_sig = "INSERT INTO live_signals
						(signal, rssi, gps_id, ap_hash, time_stamp)
						VALUES (?, ?, ?, ?, ?)";
			$time_stamp = $data['date']." ".$data['time'];
			$prep_sig = $this->sql->conn->prepare($sql_sig);
			$prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
			$prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_STR);
			$prep_sig->bindParam(3, $gps_id, PDO::PARAM_INT);
			$prep_sig->bindParam(4, $ap_hash, PDO::PARAM_STR);
			$prep_sig->bindParam(5, $time_stamp, PDO::PARAM_INT);
			$prep_sig->execute();

			$err = $this->sql->conn->errorCode();
			if($err !== "00000")
			{
				$this->mesg = array("error"=>array("desc"=>"Error adding Signal data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
				return -1;
			}else
			{
				$this->mesg = "Added Signal data.";
			}
			$sig = $this->sql->conn->lastInsertId()."|".$gps_id;
			$date_time = $data['date']." ".$data['time'];
			$sql = "INSERT INTO  live_aps ( ssid, mac,  chan, radio, auth, encry, sectype,
				BTx, OTx, NT, label, sig, username, FA, LA, lat, long, session_id, ap_hash)
											VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
			$chan = (int)$data['chan'];
			var_dump($chan, $data['username']);
			$prep = $this->sql->conn->prepare($sql);
			$prep->bindParam(1, $data['ssid'], PDO::PARAM_STR);
			$prep->bindParam(2, $data['mac'], PDO::PARAM_STR);
			$prep->bindParam(3, $chan, PDO::PARAM_INT);
			$prep->bindParam(4, $data['radio'], PDO::PARAM_STR);
			$prep->bindParam(5, $data['auth'], PDO::PARAM_STR);
			$prep->bindParam(6, $data['encry'], PDO::PARAM_STR);
			$prep->bindParam(7, $data['sectype'], PDO::PARAM_INT);
			$prep->bindParam(8, $data['BTx'], PDO::PARAM_STR);
			$prep->bindParam(9, $data['OTx'], PDO::PARAM_STR);
			$prep->bindParam(10, $data['NT'], PDO::PARAM_STR);
			$prep->bindParam(11, $data['label'], PDO::PARAM_STR);
			$prep->bindParam(12, $sig, PDO::PARAM_STR);
			$prep->bindParam(13, $data['username'], PDO::PARAM_STR);
			$prep->bindParam(14, $date_time, PDO::PARAM_STR);
			$prep->bindParam(15, $date_time, PDO::PARAM_STR);
			$prep->bindParam(16, $data['lat'], PDO::PARAM_STR);
			$prep->bindParam(17, $data['long'], PDO::PARAM_STR);
			$prep->bindParam(18, $data['session_id'], PDO::PARAM_STR);
			$prep->bindParam(19, $ap_hash, PDO::PARAM_STR);
			$prep->execute();
			$err = $this->sql->conn->errorCode();
			if($err !== "00000")
			{
				$this->mesg = array("error"=>array("desc"=>"Error adding GPS data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
				return -1;
			}else
			{
				$this->mesg = "Added AP data.";
			}
		}
		return 1;
	}

	public function Locate()
	{
		$listing		=   array();
		$lists		  =   explode("-", $this->LocateList);

		foreach($lists as $key=>$item)
		{
			$t = explode("|", $item);
			$listing[$key] = array($t[1],$t[0]);
		}

		$listings = $this->subval_sort($listing, 0);

		$pre_sat = 0;
		$use = array();
		foreach($listings as $macandsig)
		{
			if($this->sql->service == "mysql")
				{
					$sql = "SELECT wifi_gps.Lat, wifi_gps.Lon, wifi_gps.Alt, wifi_gps.NumOfSats, wifi_gps.GPS_Date\n"
						. "FROM wifi_ap\n"
						. "INNER JOIN wifi_gps ON wifi_gps.GPS_ID = wifi_ap.HighGps_ID\n"
						. "WHERE wifi_ap.HighGps_ID IS NOT NULL AND wifi_ap.BSSID LIKE ?\n"
						. "ORDER BY wifi_gps.NumOfSats DESC LIMIT 1";
				}
			else if($this->sql->service == "sqlsrv")
				{
					$sql = "SELECT TOP 1 wifi_gps.Lat, wifi_gps.Lon, wifi_gps.Alt, wifi_gps.NumOfSats, wifi_gps.GPS_Date\n"
						. "FROM wifi_ap\n"
						. "INNER JOIN wifi_gps ON wifi_gps.GPS_ID = wifi_ap.HighGps_ID\n"
						. "WHERE wifi_ap.HighGps_ID IS NOT NULL AND wifi_ap.BSSID LIKE ?\n"
						. "ORDER BY wifi_gps.NumOfSats DESC";
				}

			$result =   $this->sql->conn->prepare($sql);
			$result->bindParam(1, $macandsig[1]);
			$result->execute();
			$this->sql->checkError();
			$array  =   $result->fetch(1);
			if($array['Lat'])
			{
				$dt = new DateTime($array['GPS_Date']);
				$use = array(
					'lat'	=> $array['Lat'],
					'long'	=> $array['Lon'],
					'date'	=> $dt->format('m-d-Y'),
					'time'	=> $dt->format('H:i:s'),
					'sats'	=> $array['NumOfSats']
					);
				$this->mesg = $use;
				return $use;
			}
		}
	}

    public function GetTitleIDFromSessionID()
    {
        $sql = "SELECT title_id FROM live_users WHERE session_id = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $_REQUEST['SessionID'], PDO::PARAM_STR);
        $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
        $fetch = $prep->fetch(2);
        return $fetch['title_id'];
    }

    public function ManageLiveSession($date = "", $time = "")
    {
        if(isset($_REQUEST['SessionID']))
        {
            #var_dump("SessionID Set.");
            $timestamp = $date." ".$time;
            if(isset($_REQUEST['completed']))
            {
                #var_dump("Completed Set");
                $TitleID = $this->GetTitleIDFromSessionID();
                $completed = (int)$_REQUEST['completed'];
                $sql = "UPDATE live_titles SET completed = ? WHERE id = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $completed, PDO::PARAM_INT);
                $prep->bindParam(2, $TitleID, PDO::PARAM_INT);
                $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
                $this->mesg[] = "Session_Completed";
                return 2;
            }

            $sql = "SELECT title_id FROM live_users LEFT JOIN live_titles ON live_users.session_id = ? AND live_titles.completed = 0 WHERE live_users.session_id = ?";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(1, $_REQUEST['SessionID']);
            $prep->bindParam(2, $_REQUEST['SessionID']);
            $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
            $title_data = $prep->fetch(2);

            if(count($title_data) !== 1)
            {
				$this->mesg = array("error" => "Session_Expired");
                return 0;
            }

            $title_id = $title_data['title_id'];
            #var_dump($title_id);
            if ($this->sec->login_check)
            {
                #var_dump("LoginCheck True");
                $sql = "SELECT t1.id, t1.username, t1.session_id, t1.title_id, t2.title, t2.notes FROM live_users AS t1 LEFT JOIN live_titles AS t2 ON t2.id = t1.title_id WHERE username = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $this->username, PDO::PARAM_STR);
                $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
                $fetch = $prep->fetch(2);
                #var_dump($fetch);
                if ($fetch)
                {
                    #var_dump($timestamp);
                    #var_dump("Title Update");
                    $this->sec->SessionID = $fetch['session_id'];
                    $sql = "UPDATE live_titles SET timestamp = ? WHERE id = ?";
                    $prep = $this->sql->conn->prepare($sql);
                    $prep->bindParam(1, $timestamp, PDO::PARAM_STR);
                    $prep->bindParam(2, $title_id, PDO::PARAM_INT);
                    $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
                    #var_dump("Fetched WDBSessionID: " . $this->sec->SessionID);
                    return 1;
                } else {
                    #var_dump("Title Insert.");
                    $this->InsertLiveTitle();
                }
            } else {
                #var_dump("LoginCheck False");
                $this->sec->SessionID = $_REQUEST['SessionID'];

                #var_dump("Timestamp: ".$timestamp);
                $sql = "UPDATE live_titles SET timestamp = ? WHERE id = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $timestamp, PDO::PARAM_STR);
                $prep->bindParam(2, $title_id, PDO::PARAM_INT);
                $this->sql->checkError($prep->execute(), __LINE__, __FILE__);

                #var_dump("Old API Generate: ".$this->OldAPIGenerate());

                if(WDB_DEBUG) {
                    $this->mesg['debug'][] = "User Not Logged In. Or using Old Live API.";
                }
                return 0;
            }
        }elseif(isset($_REQUEST['title']))
        {
            #var_dump("New Title.");
            $this->InsertLiveTitle();
            return 1;
        }else{
            if(isset($_REQUEST['completed']))
            {
				$this->mesg = array("error" => "Completed flag was set, but no session ID to complete...");
                return 0;
            }
            $this->OldAPIGenerate();
            return 1;
        }
    }

    public function InsertLiveTitle()
    {
        if(!isset($_REQUEST['title'])) {

            $this->sec->SessionID = preg_replace("/[^a-zA-Z0-9]+/", "", $_REQUEST['SessionID']); //SessionID's should be letters and numbers only. Remove anything else.
            $this->OldAPIGenerate();
            return 0;
        } else {
            $timestamp = date("Y-m-d H:i:s");
            $insertTitle = "INSERT INTO live_titles (title, notes, timestamp, completed ) VALUES (?, ?, ?, 0)";
            $prep_Title = $this->sql->conn->prepare($insertTitle);
            $prep_Title->bindParam(1, $_REQUEST['title'], PDO::PARAM_STR);
            $prep_Title->bindParam(2, $_REQUEST['notes'], PDO::PARAM_STR);
            $prep_Title->bindParam(3, $timestamp, PDO::PARAM_STR);

            $this->sql->checkError($prep_Title->execute(), __LINE__, __FILE__);
            $TitleID = $this->sql->conn->lastInsertID();
            #var_dump("Title ID: " . $this->sql->conn->lastInsertID());
        }
        $this->sec->SessionID = $this->sec->GenerateKey(64);
        #var_dump("Generated: " . $this->sec->SessionID);

        $sessionInsert = "INSERT INTO live_users (id, username, session_id, title_id) VALUES ('', ?, ?, ?)";
        $prep_user = $this->sql->conn->prepare($sessionInsert);
        $prep_user->bindParam(1, $this->sec->username, PDO::PARAM_STR);
        $prep_user->bindParam(2, $this->sec->SessionID, PDO::PARAM_STR);
        $prep_user->bindParam(3, $TitleID, PDO::PARAM_INT);

        $this->sql->checkError($prep_user->execute(), __LINE__, __FILE__);
        $this->mesg["SessionID"] = $this->sec->SessionID;
        return 1;
    }

    public function OldAPIGenerate()
    {
        $date = date("Y-m-d H:i:s");
        $this->sec->SessionID = $this->sec->GenerateKey(64);
        $note = "Live Imports Using Old API";
        $insertTitle = "INSERT INTO live_titles (id, title, notes) VALUES ('', ?, ?)";
        $prep_Title = $this->sql->conn->prepare($insertTitle);
        $prep_Title->bindParam(1, $date, PDO::PARAM_STR);
        $prep_Title->bindParam(2, $note, PDO::PARAM_STR);
        $this->sql->checkError($prep_Title->execute(), __LINE__, __FILE__);
        $TitleID = $this->sql->conn->lastInsertID();
        #var_dump("Title ID: " . $this->sql->conn->lastInsertID());

        $sessionInsert = "INSERT INTO live_users (id, username, session_id, title_id) VALUES ('', ?, ?, ?)";
        $prep_user = $this->sql->conn->prepare($sessionInsert);
        $prep_user->bindParam(1, $this->username, PDO::PARAM_STR);
        $prep_user->bindParam(2, $this->sec->SessionID, PDO::PARAM_STR);
        $prep_user->bindParam(3, $TitleID, PDO::PARAM_INT);
        $this->sql->checkError($prep_user->execute(), __LINE__, __FILE__);
    }

    public function Output($mesg = NULL)
    {
        if($mesg !== NULL || $mesg[0] !== NULL)
        {
            if(is_string($mesg))
            {
                $this->mesg = array($mesg);
            }else
            {
                $this->mesg = $mesg;
            }
        }
        if(empty($this->mesg)){return array("Empty Data Set.");}

        switch(strtolower($this->output))
        {
            case "json":
                echo json_encode($this->mesg);
                break;
            case "xml":
                echo $this->xml->ArrayToXML($this->mesg);
                break;
            case "raw":
                echo $this->recursive_raw("|", "[", "]", $this->mesg);
                break;
            default:
                echo json_encode($this->mesg);
                break;
        }
        exit();
    }

    public function Search($ssid, $mac, $radio, $chan, $auth, $encry)
    {
        $sql2 = "SELECT * FROM wifi_pointers WHERE
				ssid LIKE ? AND
				mac LIKE ? AND
				radio LIKE ? AND
				chan LIKE ? AND
				auth LIKE ? AND
				encry LIKE ?";

        $prep2 = $this->sql->conn->prepare($sql2);

        $ssid = $ssid."%";
        $prep2->bindParam(1, $ssid, PDO::PARAM_STR);
        $mac = $mac."%";
        $prep2->bindParam(2, $mac, PDO::PARAM_STR);
        $radio = $radio."%";
        $prep2->bindParam(3, $radio, PDO::PARAM_STR);
        $chan = $chan."%";
        $prep2->bindParam(4, $chan, PDO::PARAM_STR);
        $auth = $auth."%";
        $prep2->bindParam(5, $auth, PDO::PARAM_STR);
        $encry = $encry."%";
        $prep2->bindParam(6, $encry, PDO::PARAM_STR);
        $this->sql->checkError($prep2->execute(), __LINE__, __FILE__);
        $total_rows = $prep2->rowCount();
        if(!$total_rows)
        {
            $this->mesg = "No AP's Found";
            return 0;
        }
        $row_color = 0;
        $results_all = array();
        $i=0;
        while ($newArray = $prep2->fetch(2))
        {
            if($row_color == 1)
            {
                $row_color = 0;
                $results_all[$i]['class'] = "light";
            }
            else
            {
                $row_color = 1;
                $results_all[$i]['class'] = "dark";
            }
            $results_all[$i]['id'] = $newArray['id'];
            $results_all[$i]['ssid'] = $newArray['ssid'];
            $results_all[$i]['mac'] = $newArray['mac'];
            $results_all[$i]['sectype'] = $newArray['sectype'];
            $results_all[$i]['chan'] = $newArray['chan'];
            $results_all[$i]['auth'] = $newArray['auth'];
            $results_all[$i]['encry'] = $newArray['encry'];
            $results_all[$i]['radio'] = $newArray['radio'];
            $results_all[$i]['BTx']=$newArray['BTx'];
            $results_all[$i]['OTx']=$newArray['OTx'];
            $results_all[$i]['label']=$newArray['label'];
            $results_all[$i]['FA']=$newArray['FA'];
            $results_all[$i]['LA']=$newArray['LA'];
            $results_all[$i]['NT'] = $newArray['NT'];
            $results_all[$i]['manuf']=$newArray['manuf'];
            $results_all[$i]['geonames_id']=$newArray['geonames_id'];
            $results_all[$i]['admin1_id']=$newArray['admin1_id'];
            $results_all[$i]['admin2_id']=$newArray['admin2_id'];
            $results_all[$i]['username']=$newArray['username'];
            $results_all[$i]['ap_hash'] = $newArray['ap_hash'];
            $i++;
        }
        $this->mesg = $results_all;
        return $results_all;
    }

    private function recursive_raw($sep = "", $open = "", $close = "", $data = array())
    {
        if($sep === ""){$sep = "|";}
        if($open === ""){$open = "[";}
        if($close === ""){$close = "]";}
        if($data === NULL){return -1;}

        foreach($data as $val)
        {
            if(is_array($val))
            {
                foreach($val as $key=>$v)
                {
                    if(is_array($v))
                    {
                        $val[$key] = $this->recursive_raw($sep, $open, $close, $v);
                    }
                }
                $res[] = $open.implode($sep, $val).$close;
            }else
            {
                $res[] = $val;
            }

        }
        return $open.implode($sep, $res).$close;
    }
}
