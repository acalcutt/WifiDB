<?php
/*
api.inc.php, holds the WiFiDB API functions.
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
class api extends dbcore
{
	function __construct($config)
	{
		parent::__construct($config);
		$this->startdate	= "2011-Apr-14";
		$this->lastedit	 = "2013-Apr-21";
		$this->vernum	   = "1.0";
		$this->Author	   = "Phil Ferland";
		$this->contact	  = "pferland@randomintervals.com";
		$this->output	   = (@$_REQUEST['output']	? strtolower($_REQUEST['output']) : "json");
	$this->username	 = (@$_REQUEST['username']  ? @$_REQUEST['username'] : "AnonCoward" );
		$this->apikey	   = (@$_REQUEST['apikey']	? @$_REQUEST['apikey'] : "");
		$this->session_id   = (@$_REQUEST['SessionID'] ? @$_REQUEST['SessionID'] : "");
		$this->output	   = (@$_REQUEST['output']	? strtolower($_REQUEST['output']) : "json");
		$this->message	  = "";
		$this->GeoNamesLoopGiveUp = $config['GeoNamesLoopGiveUp'];
		//Lets see if we can find a user with this name.
		//If so, lets check to see if the API key they provided is correct.
		$this->use_keys = 0; // 0 = DEV USE ONLY SET TO 1 for PRODUCTION USE, other wise bad things happen.
		if($this->use_keys)
		{
			$key_result = $this->sec->ValidateAPIKey();
			if(!$key_result[0]){ $this->Output($key_result[1]); }
		}
	}

	public function GeoNames($lat, $long)
	{
		$lat_search = bcdiv($lat, 1, 1);
		$long_search = bcdiv($long, 1, 1);
		
		if($this->sql->service == "mysql")
			{
				$sql = "SELECT  id, asciiname, country_code, admin1_code, admin2_code, timezone, latitude, longitude, \n"
					. "(3959 * acos(cos(radians('".$Latdd."')) * cos(radians(`latitude`)) * cos(radians(`longitude`) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(`latitude`)))) AS `miles`,\n"
					. "(6371 * acos(cos(radians('".$Latdd."')) * cos(radians(`latitude`)) * cos(radians(`longitude`) - radians('".$Londd."')) + sin(radians('".$Latdd."')) * sin(radians(`latitude`)))) AS `kilometers`\n"
					. "FROM `geonames` \n"
					. "WHERE `latitude` LIKE '".$lat_search."%' AND `longitude` LIKE '".$long_search."%' ORDER BY `kilometers` ASC LIMIT 1";
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
				{$sql = "SELECT `name` FROM `geonames_admin1` WHERE `admin1` = ?";}
			else if($this->sql->service == "sqlsrv")
				{$sql = "SELECT [name] FROM [geonames_admin1] WHERE [admin1] = ?";}
			$prep_geonames = $this->sql->conn->prepare($sql);
			$prep_geonames->bindParam(1, $admin1, PDO::PARAM_STR);
			$prep_geonames->execute();
			$Admin1Array = $prep_geonames->fetch(2);

			$admin2 = $GeonamesArray['country_code'].".".$GeonamesArray['admin1_code'].".".$GeonamesArray['admin2_code'];
			if($this->sql->service == "mysql")
				{$sql = "SELECT `name` FROM `geonames_admin2` WHERE `admin2` = ?";}
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

	public function GetAnnouncement()
	{
		$result = $this->sql->conn->query("SELECT auth,title,body,date,comments FROM annunc WHERE set = '1'");
		$array = $result->fetch(2);
		if($this->sql->checkError() || $array['body'] == "")
		{
			$this->Output(array("error"=>array("details"=>var_dump($this->sql->conn->errorInfo()))));
			return 0;
		}
		return $array;
	}

	public function CheckHash($hash)
	{
		if($hash == "")
		{
			$this->mesg = array(array("error"=>"No hash has been given to check. there is nothing to do here, my job is done."));
			return -1;
		}
		else
		{
			if($this->sql->service == "mysql")
				{$files_prep = $this->sql->conn->prepare("SELECT hash FROM files WHERE hash = ? LIMIT 1");}
			else if($this->sql->service == "sqlsrv")
				{$files_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files WHERE hash = ?");}
			$files_prep->bindParam(1, $hash, PDO::PARAM_STR);

			if($this->sql->service == "mysql")
				{$imp_prep = $this->sql->conn->prepare("SELECT hash FROM files_importing WHERE hash = ? LIMIT 1");}
			else if($this->sql->service == "sqlsrv")
				{$imp_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files_importing WHERE hash = ?");}
			$imp_prep->bindParam(1, $hash, PDO::PARAM_STR);

			if($this->sql->service == "mysql")
				{$tmp_prep = $this->sql->conn->prepare("SELECT hash FROM files_tmp WHERE hash = ? LIMIT 1");}
			else if($this->sql->service == "sqlsrv")
				{$tmp_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files_tmp WHERE hash = ?");}
			$tmp_prep->bindParam(1, $hash, PDO::PARAM_STR);

			if($this->sql->service == "mysql")
				{$bad_prep = $this->sql->conn->prepare("SELECT hash FROM files_bad WHERE hash = ? LIMIT 1");}
			else if($this->sql->service == "sqlsrv")
				{$bad_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files_bad WHERE hash = ?");}
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
				$this->mesg = array(array("imported"=>"File Already Imported"));
			}
			elseif($imp_ret['hash'] != "")
			{
				$this->mesg = array(array("importing"=>"File Being Imported"));
			}
			elseif($tmp_ret['hash'] != "")
			{
				$this->mesg = array(array("waiting"=>"Waiting For Import"));
			}
			elseif($bad_ret['hash'] != "")
			{
				$this->mesg = array(array("bad"=>"Bad File"));
			}
			else
			{
				$this->mesg = array(array("unknown"=>"Hash not found in WifiDB"));
			}
			return 1;
		}
	}
	
	public function ImportVS1($details = array())
	{
		$user		   = $details['user'];
		$otherusers	 = $details['otherusers'];
		$date		   = $details['file_date'];
		$title		  = $details['title'];
		$notes		  = $details['notes'];
		$size		   = $details['size'];
		$hash		   = $details['hash'];
		$ext			= $details['ext'];
		$filename	   = $details['file_name'];
		$file_orig	   = $details['file_orig'];

		if($this->sql->service == "mysql")
			{$tmp_prep = $this->sql->conn->prepare("SELECT hash FROM files_tmp WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$tmp_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files_tmp WHERE hash = ?");}
		$tmp_prep->bindParam(1, $hash, PDO::PARAM_STR);
		if($this->sql->service == "mysql")
			{$files_prep = $this->sql->conn->prepare("SELECT hash FROM files WHERE hash = ? LIMIT 1");}
		else if($this->sql->service == "sqlsrv")
			{$files_prep = $this->sql->conn->prepare("SELECT TOP 1 hash FROM files WHERE hash = ?");}
		$files_prep->bindParam(1, $hash, PDO::PARAM_STR);

		$tmp_prep->execute();
		$files_prep->execute();
		$tmp_ret = $tmp_prep->fetch(2);
		$files_ret = $files_prep->fetch(2);
		if($tmp_ret['hash'] != "")
		{
			$this->mesg = array("error"=>"File Hash already waiting for import: $hash");
			return -1;
		}
		if($files_ret['hash'] != "")
		{
			$this->mesg = array("error"=>"File Hash already exists in WiFiDB:  $hash");
			return -1;
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
			case "netxml":
				$task = "import";
				$type = "kismet";
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
					{$sql = "INSERT INTO [files_tmp]([file_name], [file_orig], [file_date], [file_user], [otherusers], [notes], [title], [size], [hash], [type]) VALUES ( ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";}

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
				$error = $this->sql->conn->errorCode();
				if($error[0] == "00000")
				{
					$this->mesg = array("message" => "File has been inserted for importing at a scheduled time.","importnum" => $this->sql->conn->lastInsertId(),"filehash" => $hash,"title" => $title,"user" => $user);
				}else
				{
					$this->mesg = array("error" => array("desc" => "There was an error inserting file for scheduled import.", "details" => var_export($this->sql->conn->errorInfo(), 1)));
				}
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
				$prep_sig = $this->sql->conn->prepare($sql_sig);
				$prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
				$prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_STR);
				$prep_sig->bindParam(3, $id, PDO::PARAM_INT);
				$prep_sig->bindParam(4, $ap_hash, PDO::PARAM_STR);
				$prep_sig->bindParam(5, $data['hist_date'], PDO::PARAM_INT);
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
				$this->mesg = "Lat/Long are the same, move a little you lazy bastard.";
				$sql = "UPDATE live_aps SET LA = ?, sig = ? WHERE id = ?";
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $data['hist_date'], PDO::PARAM_STR);
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
				$url_time   = $data['hist_date'];
				$wifi_time	= $array['hist_date'];
				$timecalc   = ($url_time - $wifi_time);
				$this->mesg = "Oooo its time is newer o_0, lets go insert it ;)";
				$sql = "INSERT INTO live_gps (lat, long, sats, hdp, alt, geo, kmh, mph, track, hist_date, session_id)
						VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
				$prep->bindParam(10, $data['hist_date'], PDO::PARAM_STR);
				$prep->bindParam(11, $data['session_id'], PDO::PARAM_STR);
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
				$prep_sig = $this->sql->conn->prepare($sql_sig);
				$prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
				$prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_INT);
				$prep_sig->bindParam(3, $data['gps_id'], PDO::PARAM_INT);
				$prep_sig->bindParam(4, $ap_hash, PDO::PARAM_STR);
				$prep_sig->bindParam(5, $data['hist_date'], PDO::PARAM_INT);
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
				$prep = $this->sql->conn->prepare($sql);
				$prep->bindParam(1, $sig, PDO::PARAM_STR);
				$prep->bindParam(2, $data['hist_date'], PDO::PARAM_STR);
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
			$sql = "INSERT INTO live_gps (lat, long, sats, hdp, alt, geo, kmh, mph, track, hist_date, session_id)
												   VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
			$prep->bindParam(10, $data['hist_date'], PDO::PARAM_STR);
			$prep->bindParam(11, $data['session_id'], PDO::PARAM_STR);
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
			$prep_sig = $this->sql->conn->prepare($sql_sig);
			$prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
			$prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_STR);
			$prep_sig->bindParam(3, $gps_id, PDO::PARAM_INT);
			$prep_sig->bindParam(4, $ap_hash, PDO::PARAM_STR);
			$prep_sig->bindParam(5, $data['hist_date'], PDO::PARAM_INT);
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
			$sql = "INSERT INTO  live_aps ( ssid, mac,  chan, radio, auth, encry, sectype,
				BTx, OTx, NT, label, sig, username, FA, LA, lat, long, session_id, ap_hash)
											VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? )";
			$chan = (int)$data['chan'];
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
			$prep->bindParam(14, $data['hist_date'], PDO::PARAM_STR);
			$prep->bindParam(15, $data['hist_date'], PDO::PARAM_STR);
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

		switch(@$this->output)
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
		$prep2->execute();
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
?>
