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
        $this->lastedit	    = "2016-Apr-16";
        $this->vernum	    = "2.0";
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
            if($this->sec->ValidateAPIKey() > 0)
            {
                #var_dump($this->sec->login_check);
                #var_dump($this->sec->mesg);
            }else
            {
                $this->mesg = $this->sec->mesg;
                $this->Output();
            }
        }else
        {
            $this->privs = 1;
            $this->apikey = "APIKEysDisabled";
            $this->LoginLabel = $this->username;
            $this->username = "DumbDumb";
            $this->last_login = time();
            $this->login_check = 1;
            $this->login_val = "apilogin";
            $this->mesg['message'] = "Authentication Succeeded. (API Keys Disabled or using CLI.)";
            $this->logd("Authentication Succeeded. (API Keys Disabled or using CLI.)", "message");
        }
    }

    public function createPIDFile()
    {

    }

    public function GetLiveAP($ap_id = 0)
    {
        if($ap_id === 0)
        {
            $this->mesg['error'] = "AP ID was 0, that cant be...";
            return 0;
        }
        $APSelectSQL = "SELECT `ssid`, `mac`, `auth`, `encry`, `sectype`,
                          `chan`, `radio`, `BTx`, `OTx`, `NT`, `Label`, `FA`, `LA`
                        FROM `live_aps` WHERE `id` = ?";
        $ap_prep = $this->sql->conn->prepare($APSelectSQL);
        $ap_prep->bindParam(1, $ap_id, PDO::PARAM_STR);
        #var_dump("Before JOIN query: ".microtime(1));
        $this->sql->checkError($ap_prep->execute(), __LINE__, __FILE__);

        #var_dump("After JOIN query: ".microtime(1));
        $APFetch = $ap_prep->fetchAll(2);

        $SigHistSQL = "SELECT
                    `live_gps`.`lat`, `live_gps`.`long`, `live_gps`.`sats`, `live_gps`.`hdp`,
                    `live_gps`.`alt`, `live_gps`.`geo`, `live_gps`.`kmh`, `live_gps`.`mph`, `live_gps`.`track`, `live_gps`.`timestamp` AS `GPS_timestamp`,
                    `live_signals`.`signal`, `live_signals`.`rssi`, `live_signals`.`timestamp` AS `signal_timestamp`
                     FROM `live_aps` INNER JOIN `live_signals` ON
                         `live_signals`.`ap_id`=`live_aps`.`id` INNER JOIN
                         `live_gps` ON `live_gps`.`id`=`live_signals`.`gps_id` WHERE `live_aps`.`id` = ?";
        $ap_prep = $this->sql->conn->prepare($SigHistSQL);
        $ap_prep->bindParam(1, $ap_id, PDO::PARAM_STR);
        #var_dump("Before JOIN query: ".microtime(1));
        $this->sql->checkError($ap_prep->execute(), __LINE__, __FILE__);
        #var_dump("After JOIN query: ".microtime(1));
        $SignalDataFetch = $ap_prep->fetchAll(2);
        return array('apdata'=> $APFetch, 'gdata'=> $SignalDataFetch);
    }

    private function fetch_geoname($lat_low = "", $lat_high = "", $long_low = "", $long_high = "")
    {
        #
        $sql = "SELECT `geonameid`, `country code`, `admin1 code`, `admin2 code`, `asciiname`, `latitude`, `longitude`
		FROM `geonames`
		WHERE `latitude` >= ?
		AND `latitude` <= ?
		AND `longitude` <= ?
		AND `longitude` >= ?";

        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $lat_low, PDO::PARAM_STR);
        $result->bindParam(2, $lat_high, PDO::PARAM_STR);
        $result->bindParam(3, $long_low, PDO::PARAM_STR);
        $result->bindParam(4, $long_high, PDO::PARAM_STR);
        $this->sql->checkError($result->execute(), __LINE__, __FILE__);
        $geo_array = $result->fetchall(2);

        if(@$geo_array[0] == "")
        {
            return 0;
        }else
        {
            return $geo_array;
        }
    }

    public function GeoNames($lat, $long)
    {
        $lat_exp = explode(".", $lat);
        $long_exp = explode(".", $long);

        $lat_alt = substr($lat_exp[1], 0, 2); //Get the first two decimal places
        $lat = (float) $lat_exp[0].".".$lat_alt; //Recombine and cast as a float
        $lat_low = ($lat-0.01); // subtract 0.01 to get the first low Lat for searching
        $lat_high = ($lat+0.01); // add 0.01 to get the first high Lat for searching

        $long_alt = substr($long_exp[1], 0, 3); //Get the first three decimal places
        $long = (float) $long_exp[0].".".$long_alt; // recombine and cast as a float
        $long_low = ($long-0.001); // subtract 0.001 to get the first low Long for searching
        $long_high = ($long+0.001); // add 0.001 to get the first high Long fors searching
        $i =1;

        do{ //loop till we find a valid geoname, or we hit the wall set by the config.
            if($i == $this->GeoNamesLoopGiveUp){$this->mesg = "No Geoname found within a respectable area"; return 0;}
            $geo_array = $this->fetch_geoname($lat_low, $lat_high, $long_low, $long_high);
            $lat_low = ($lat_low-0.01); //prepare for the next loop if it is going to be needed. we are going to increse the search area a little bit.
            $lat_high = ($lat_high+0.01);
            $long_low = ($long_low-0.001);
            $long_high = ($long_high+0.001);
            $i++; // increment the search loop key
        }while($geo_array[0] == "");

        foreach($geo_array as $key=>$names)
        {
            //calculate out the differences between the Supplied Lat/Long and each of the Geonames Lat/Long found.
            $lat_calc = abs($names['latitude']-$lat);
            $long_calc = abs($names['longitude']-$long);
            $calcs[] = array($key, $lat_calc, $long_calc);
        }
        $sort = $this->subval_sort($calcs,1, 1); //Sort the Lats from lowest to highest
        $sort1 = $this->subval_sort($calcs,2, 1); // Sort the Longs from lowset to highest
        if($sort[0][0] != $sort1[0][0]) //check and see if the id's for each sort is the same, if not, we need to do some comparisons
        {
            $dist = $this->CalcDistance($lat, $long, $geo_array[$sort[0][0]]['latitude'], $geo_array[$sort[0][0]]['longitude'], 1); //distance of lowest lat geoname to the supplied lat/long
            $dist1 = $this->CalcDistance($lat, $long, $geo_array[$sort1[0][0]]['latitude'], $geo_array[$sort1[0][0]]['longitude'], 1); //distance of the lowest long geoname to the supplied lat/long
            if($dist < $dist1) //which ever one has the lowest distance is used.
            {
                $chosen = $sort[0];
            }else
            {
                $chosen = $sort1[0];
            }
        }else //lowset lat and lowset long id's match
        {
            $chosen = $sort[0];
        }

        $dist = $this->CalcDistance($lat, $long, $geo_array[$chosen[0]]['latitude'], $geo_array[$chosen[0]]['longitude']);
        $geo_array = $geo_array[$chosen[0]];
        if($geo_array['admin1 code'])
        {
            $admin1 = $geo_array['country code'].".".$geo_array['admin1 code'];

            $sql = "SELECT `asciiname` FROM `geonames_admin1` WHERE `admin1`= ?";
            $admin1_res = $this->sql->conn->prepare($sql);
            $admin1_res->bindParam(1, $admin1, PDO::PARAM_STR);
            $this->sql->checkError($admin1_res->execute(), __LINE__, __FILE__);
            $admin1_array = $admin1_res->fetch(1);
        }
        if(is_numeric($geo_array['admin2 code']))
        {
            $admin2 = $geo_array['country code'].".".$geo_array['admin1 code'].".".$geo_array['admin2 code'];
            $sql = "SELECT `asciiname` FROM `geonames_admin2` WHERE `admin2`= ? ";
            $admin2_res = $this->sql->conn->prepare($sql);
            $admin2_res->bindParam(1, $admin2, PDO::PARAM_STR);
            $this->sql->checkError($admin2_res->execute(), __LINE__, __FILE__);
            $admin2_array = $admin2_res->fetch(1);
        }
        $sql = "SELECT `Country` FROM `geonames_country_names` WHERE `ISO` LIKE ? LIMIT 1";
        $country_res = $this->sql->conn->prepare($sql);
        $code = $geo_array['country code']."%";
        $country_res->bindParam(1, $code, PDO::PARAM_STR);
        $this->sql->checkError($country_res->execute(), __LINE__, __FILE__);
        $country_array = $country_res->fetch(1);

        $this->mesg = array(
            'Country Code'=> str_replace("%20", " ", $geo_array['country code']),
            'Country Name'=> str_replace("%20", " ", $country_array['Country']),
            'Admin1 Code'=> str_replace("%20", " ", $geo_array['admin1 code']),
            'Admin1 Name'=>(@$admin1_array['asciiname'] ? str_replace("%20", " ", $admin1_array['asciiname']) : ""),
            'Admin2 Name'=>(@$admin2_array['asciiname'] ? str_replace("%20", " ", $admin2_array['asciiname']) : ""),
            'Area Name'=> str_replace("%20", " ", $geo_array['asciiname']),
            'miles'=>$dist[0],
            'km'=>$dist[1],
            'feet'=>$dist[0]*5280
        );
        return 1;
    }

    public function GetUserNameFromID($userid = 0)
    {
        if($userid === 0)
        {
            throw new Exception("User ID is empty.");
            return -1;
        }

        $sql = "SELECT `username` FROM `user_info` WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $userid, PDO::PARAM_INT );
        $prep->execute();
        $this->sql->checkError( $prep, __LINE__, __FILE__);
        $user = $prep->fetch(2);
        $this->mesg['user'] = $user;
        return $user['username'];
    }

    public function GetLocalUsers()
    {
        $sql = "SELECT `id`, `username` FROM `user_info` ORDER BY `username` ASC";
        $result = $this->sql->conn->query($sql);
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $users_all = $result->fetchAll(2);
        $this->mesg['users_all'] = $users_all;
        return $users_all;
    }

    public function GetLocalImports()
    {
        $sql = "SELECT `id`, `title`, `aps`, `gps`, `date` FROM `user_imports` ORDER BY `id` ASC";
        $result = $this->sql->conn->prepare($sql);
        $result->execute();
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $userslists = $result->fetchAll(2);
        $this->mesg['userslists'] = $userslists;
        return $userslists;
    }

    public function GetLocalUserLists($Username = "")
    {
        if($Username === "")
        {
            throw new Exception("Username is empty.");
        }
        $sql = "SELECT `id`, `title`, `aps`, `gps`, `date` FROM `user_imports` WHERE `username` = ? ORDER BY `username` ASC";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $Username, PDO::PARAM_STR);
        $result->execute();
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $userslists = $result->fetchAll(2);
        $this->mesg['userslists'] = $userslists;
        return $userslists;
    }

    public function GetLocalUserStats($Username = '')
    {
        $sql = "SELECT sum(`aps`), sum(`gps`) FROM user_imports WHERE username = ?";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $Username, PDO::PARAM_INT);
        $result->execute();
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $Stats = $result->fetchAll(2);
        $Return['TotlaAPs'] = $Stats['sum(`aps`)'];
        $Return['TotlaGPS'] = $Stats['sum(`gps`)'];

        $sql = "SELECT
 (SELECT `date` FROM user_imports WHERE username = ? ORDER BY `date`) as `first`,
 (SELECT `date` FROM user_imports WHERE username = ? ORDER BY `date` DESC ) as `last`";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $Username, PDO::PARAM_INT);
        $result->execute();
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $FirstLastImport = $result->fetchAll(2);
        $Return['FirstImport'] = $FirstLastImport['first'];
        $Return['LastImport'] = $FirstLastImport['last'];

        return $Return;
    }

    public function GetLocalUserData($Username = "")
    {
        $Results['UserStats'] = $this->GetLocalUserStats($Username);
        $Result['UserLists'] = $this->GetLocalUserLists($Username);
        return $Result;
    }

    public function GetLocalUserListData($ImportID = 0)
    {
        if($ImportID === 0)
        {
            throw new Exception("ImportID is empty.");
        }
        $sql = "SELECT `id`, `username`, `points`, `notes`, `title`, `date`, `aps`, `gps`, `hash`, `file_id`, `converted`, `prev_ext` FROM `user_imports` WHERE `id` = ?";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $ImportID, PDO::PARAM_INT);
        $result->execute();
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $import = $result->fetchAll(2);
        $this->mesg['import'] = $import;
        return $import;
    }

    public function GetAPsList()
    {
        $sql = "SELECT id, ap_hash, ssid, mac, chan, auth, radio, encry, sectype, lat, `long`, username, signal_high, rssi_high, alt, manuf
        FROM `wifi_pointers` ORDER BY id";
        $result = $this->sql->conn->query($sql);
        $aplist = $result->fetchAll(2);
        $this->mesg['aplist'] = $aplist;
        return $aplist;
    }

    public function GetAPData($APID = 0)
    {
        $sql = "SELECT id, ap_hash, ssid, mac, chan, auth, radio, encry, sectype, lat, `long`, username, signal_high, rssi_high, alt, manuf
        FROM `wifi_pointers` WHERE `id` = ? ORDER BY id";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $APID, PDO::PARAM_INT);
        $result->execute();
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $ap = $result->fetchAll(2);
        $this->mesg['ap'] = $ap;
        return $ap;
    }

    public function GetWaitingScheduleTable()
    {
        if($this->AllDateRange == 1) {
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files_tmp`");
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
                $this->mesg['error'] = "StartDate or EndDate are not set.";
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files_tmp` WHERE `date` >= ? AND `date` <= ?");
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
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files_importing`");
            $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
                $this->mesg['error'] = "StartDate or EndDate are not set.";
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files_importing` WHERE `date` >= ? AND `date` <= ?");
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
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files`");
            $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
                $this->mesg['error'] = "StartDate or EndDate are not set.";
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files` WHERE `date` >= ? AND `date` <= ?");
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
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files_bad`");
            $this->sql->checkError($schedule_prep->execute(), __LINE__, __FILE__);
        }else {
            if (($this->StartDate == "") OR ($this->EndDate == "")) {
                $this->mesg['error'] = "StartDate or EndDate are not set.";
                return -1;
            }
            $schedule_prep = $this->sql->conn->prepare("SELECT * FROM `files_bad` WHERE `date` >= ? AND `date` <= ?");
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
        $sql = "SELECT `nodename`, `pidfile`, `pid`, `pidtime`, `pidmem`, `pidcmd`, `date` FROM `daemon_pid_stats`";
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

            $this->mesg['daemons'] = $altered;
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
        $files_prep = $this->sql->conn->prepare("SELECT `id`, `hash`, `file`, `user`, `notes`, `title`, `size`, `date`, `converted`, `node_name`, `prev_ext`, `completed`, `aps`, `gps` FROM `files` WHERE `hash` = ? LIMIT 1");
        $files_prep->bindParam(1, $hash, PDO::PARAM_STR);
        $imp_prep = $this->sql->conn->prepare("SELECT `id`, `hash`, `file`, `user`, `notes`, `title`, `size`, `date`, `converted`, `prev_ext`, `importing`, `ap`, `tot` FROM `files_importing` WHERE `hash` = ? LIMIT 1");
        $imp_prep->bindParam(1, $hash, PDO::PARAM_STR);
        $tmp_prep = $this->sql->conn->prepare("SELECT `id`, `hash`, `file`, `user`, `notes`, `title`, `size`, `date`, `converted`, `prev_ext` FROM `files_tmp` WHERE `hash` = ? LIMIT 1");
        $tmp_prep->bindParam(1, $hash, PDO::PARAM_STR);
        $bad_prep = $this->sql->conn->prepare("SELECT `id`, `hash`, `file`, `user`, `notes`, `title`, `size`, `date`, `converted`, `thread_id`, `node_name`, `prev_ext`, `error_msg` FROM `files_bad` WHERE `hash` = ? LIMIT 1");
        $bad_prep->bindParam(1, $hash, PDO::PARAM_STR);

        $this->sql->checkError($files_prep->execute(), __LINE__, __FILE__);
        $this->sql->checkError($imp_prep->execute(), __LINE__, __FILE__);
        $this->sql->checkError($tmp_prep->execute(), __LINE__, __FILE__);
        $this->sql->checkError($bad_prep->execute(), __LINE__, __FILE__);

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
        if(substr($user, -1) == "|")
        {
            $user = str_replace("|", "", $user);
        }else
        {
            $exp = explode("|", $user);
            $user = $exp[0];
        }
        $tmp_prep = $this->sql->conn->prepare("SELECT `hash` FROM `files_tmp` WHERE `hash` = ? LIMIT 1");
        $tmp_prep->bindParam(1, $hash, PDO::PARAM_STR);
        $files_prep = $this->sql->conn->prepare("SELECT `hash` FROM `files` WHERE `hash` = ? LIMIT 1");
        $files_prep->bindParam(1, $hash, PDO::PARAM_STR);

        $this->sql->checkError($tmp_prep->execute(), __LINE__, __FILE__);
        $this->sql->checkError($files_prep->execute(), __LINE__, __FILE__);

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
                break;
            case "vsz":
                $task = "import";
                break;
            case "vscz":
                $task = "experimental";
                break;
            case "csv":
                $task = "import";
                break;
            case "db3":
                $task = "import";
                break;
            default:
                $task = "";
                break;
        }

        switch($task)
        {
            case "import":
                $this->mesg['import']["title"] = $title;
                $this->mesg['import']["user"] = $user;
                if($otherusers)
                {
                    $this->mesg['import']['otherusers'] = $otherusers;
                }
                $sql = "INSERT INTO `files_tmp`
								( `id`, `file`, `date`, `user`, `notes`, `title`, `size`, `hash`  )
						VALUES ( '', ?, ?, ?, ?, ?, ?, ?)";
                $result = $this->sql->conn->prepare( $sql );

                $result->bindValue(1, $filename, PDO::PARAM_STR);
                $result->bindValue(2, $date, PDO::PARAM_STR);
                $result->bindValue(3, $user."|".$otherusers, PDO::PARAM_STR);
                $result->bindValue(4, $notes, PDO::PARAM_STR);
                $result->bindValue(5, $title, PDO::PARAM_STR);
                $result->bindValue(6, $size, PDO::PARAM_STR);
                $result->bindValue(7, $hash, PDO::PARAM_STR);
                $this->sql->checkError($result->execute(), __LINE__, __FILE__);

                $this->mesg['import']["message"] = "File has been inserted for importing at a scheduled time.";
                $this->mesg['import']["importnum"] = $this->sql->conn->lastInsertId();
                $this->mesg['import']["filehash"] = $hash;
                break;
            default:
                $this->mesg = array("error" => "Failure.... File is not supported. Try one of the supported file http://live.wifidb.net/wifidb/import/?func=supported_files");
                break;
        }
        return 1;
    }

    public function InsertLiveAP($data = array())
    {
        if(empty($data)){$this->mesg = array("error"=>"Emtpy data set");return 0;}
        $ap_hash = md5($data['ssid'].$data['mac'].$data['chan'].$data['sectype'].$data['radio'].$data['auth'].$data['encry']);
        $LA = $data['date']." ".$data['time'];

        #var_dump("AP_HASH: ".$ap_hash);
        #var_dump($this->sec->SessionID);
        $sql = "SELECT `t1`.`id`, `t1`.`ssid`, `t1`.`mac`, `t1`.`chan`, `t1`.`sectype`, `t1`.`auth`, `t1`.`encry`, `t1`.`radio`, `t1`.`session_id`, `t1`.`lat`, `t1`.`long` FROM `live_aps` as `t1`
                  INNER JOIN `live_users` as `t2`
                  ON `t1`.`session_id` = ?
                  WHERE ap_hash = ?
                  LIMIT 1";

        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $this->sec->SessionID, PDO::PARAM_STR);
        $result->bindParam(2, $ap_hash, PDO::PARAM_STR);
        $this->sql->checkError($result->execute(), __LINE__, __FILE__);


        $array = $result->fetch(2);
        #var_dump($array);
        if(isset($array['id']))
        {
            $ap_id = $array['id'];
            $this->mesg[] = "Update_AP" ;

            $sql = "SELECT `id`, `lat`, `long` FROM `live_gps` WHERE `ap_id` = ? ORDER BY `timestamp` DESC";
            $result = $this->sql->conn->prepare($sql);
            $result->bindParam(1, $ap_id, PDO::PARAM_INT);
            $this->sql->checkError($result->execute(), __LINE__, __FILE__);
            $array = $result->fetch(2);
            if( (!strcmp($array['lat'], $data['lat'])) && (!strcmp($array['long'], $data['long'])) )
            {
                $this->mesg[] = "Old Location, New Signal";
                $gps_select = "SELECT id FROM `live_gps` WHERE `lat` = ? AND `long` = ?";
                $gps_prep = $this->sql->conn->prepare($gps_select);
                $gps_prep->bindParam(1, $array['lat'], PDO::PARAM_STR);
                $gps_prep->bindParam(2, $array['long'], PDO::PARAM_STR);

                $this->sql->checkError( $gps_prep->execute(), __LINE__, __FILE__);
                $fetch = $gps_prep->fetch(2);

                $sql_sig = "INSERT INTO `live_signals`
						(`id`, `signal`, `rssi`, `gps_id`, `ap_id`, `timestamp`)
						VALUES ('', ?, ?, ?, ?, ?)";
                $prep_sig = $this->sql->conn->prepare($sql_sig);
                $prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
                $prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_STR);
                $prep_sig->bindParam(3, $fetch['id'], PDO::PARAM_INT);
                $prep_sig->bindParam(4, $ap_id, PDO::PARAM_INT);
                $prep_sig->bindParam(5, $LA, PDO::PARAM_STR);
                $this->sql->checkError( $prep_sig->execute(), __LINE__, __FILE__);

                $this->mesg[] = "Added Signal data.";

                $this->mesg[] = "Lat/Long are the same, move a little you lazy bastard.";
                $sql = "UPDATE `live_aps` SET `LA` = ? WHERE `id` = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $LA, PDO::PARAM_STR);
                $prep->bindParam(2, $ap_id, PDO::PARAM_INT);
                $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);
                $this->mesg[] = "Updated AP Last Active and Signal.";
            }
            else
            {
                $this->mesg[] = "New_location";
                $sql = "INSERT INTO `live_gps` (`id`, `lat`, `long`, `sats`, `hdp`, `alt`, `geo`, `kmh`, `mph`, `track`, `timestamp`, `ap_id`)
											   VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $data['lat'], PDO::PARAM_STR);
                $prep->bindParam(2, $data['long'], PDO::PARAM_STR);
                $prep->bindParam(3, $data['sats'], PDO::PARAM_INT);
                $prep->bindParam(4, $data['hdp'], PDO::PARAM_INT);
                $prep->bindParam(5, $data['alt'], PDO::PARAM_INT);
                $prep->bindParam(6, $data['geo'], PDO::PARAM_INT);
                $prep->bindParam(7, $data['kmh'], PDO::PARAM_INT);
                $prep->bindParam(8, $data['mph'], PDO::PARAM_INT);
                $prep->bindParam(9, $data['track'], PDO::PARAM_INT);
                $prep->bindParam(10, $LA, PDO::PARAM_STR);
                $prep->bindParam(11, $ap_id, PDO::PARAM_INT);
                $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);
                $gps_id = $this->sql->conn->lastInsertId();

                $this->mesg[] = "Added GPS data.";

                $sql_sig = "INSERT INTO `live_signals`
					(`id`, `signal`, `rssi`, `gps_id`, `ap_id`, `timestamp`)
					VALUES ('', ?, ?, ?, ?, ?)";
                $prep_sig = $this->sql->conn->prepare($sql_sig);
                $prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
                $prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_INT);
                $prep_sig->bindParam(3, $gps_id, PDO::PARAM_INT);
                $prep_sig->bindParam(4, $ap_id, PDO::PARAM_INT);
                $prep_sig->bindParam(5, $LA, PDO::PARAM_STR);
                $this->sql->checkError( $prep_sig->execute(), __LINE__, __FILE__);

                $this->mesg[] = "Added Signal data.";

                $sql = "UPDATE `live_aps` SET `LA` = ?, `lat` = ?, `long` = ? WHERE `id` = ?";
                #echo $sql."<br /><br />";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $LA, PDO::PARAM_STR);
                $prep->bindParam(2, $data['lat'], 2);
                $prep->bindParam(3, $data['long'], 2);
                $prep->bindParam(4, $ap_id, 1);
                $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);

                $this->mesg[] = "Updated AP data.";
            }
        }else
        {
            $FA = $data['date']." ".$data['time'];
            $label = ( isset($data['Label']) ? $data['Label'] : "" );
            $insert_sql = "INSERT INTO `live_aps` (id, ssid, mac, auth, encry, sectype, radio, chan, session_id, ap_hash, BTx, OTx, NT, Label, FA, LA, lat, `long`)
                              VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $insert_prep = $this->sql->conn->prepare($insert_sql);
            $insert_prep->bindParam(1 , $data['ssid'], PDO::PARAM_STR);
            $insert_prep->bindParam(2 , $data['mac'], PDO::PARAM_STR);
            $insert_prep->bindParam(3 , $data['auth'], PDO::PARAM_STR);
            $insert_prep->bindParam(4 , $data['encry'], PDO::PARAM_STR);
            $insert_prep->bindParam(5 , $data['sectype'], PDO::PARAM_INT);
            $insert_prep->bindParam(6 , $data['radio'], PDO::PARAM_STR);
            $insert_prep->bindParam(7 , $data['chan'], PDO::PARAM_INT);
            $insert_prep->bindParam(8 , $_REQUEST['SessionID'], PDO::PARAM_STR);
            $insert_prep->bindParam(9 , $ap_hash, PDO::PARAM_STR);
            $insert_prep->bindParam(10 , $data['BTx'], PDO::PARAM_STR);
            $insert_prep->bindParam(11 , $data['OTx'], PDO::PARAM_STR);
            $insert_prep->bindParam(12 , $data['NT'], PDO::PARAM_STR);
            $insert_prep->bindParam(13 , $label, PDO::PARAM_STR);
            $insert_prep->bindParam(14 , $FA, PDO::PARAM_STR);
            $insert_prep->bindParam(15 , $LA, PDO::PARAM_STR);
            $insert_prep->bindParam(16 , $data['lat'], PDO::PARAM_STR);
            $insert_prep->bindParam(17 , $data['long'], PDO::PARAM_STR);
            $this->sql->checkError( $insert_prep->execute(), __LINE__, __FILE__);

            $ap_id = $this->sql->conn->lastInsertID();

            $this->mesg[] = "Added AP data.";

            $sql = "INSERT INTO `live_gps` (`id`, `lat`, `long`, `sats`, `hdp`, `alt`, `geo`, `kmh`, `mph`, `track`,`timestamp`, `ap_id`) VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
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
            $prep->bindParam(10, $LA, PDO::PARAM_STR);
            $prep->bindParam(11, $ap_id, PDO::PARAM_STR);
            $prep->execute();
            $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);

            $gps_id = $this->sql->conn->lastInsertId();

            $this->mesg[] = "Added GPS data.";

            $sql_sig = "INSERT INTO `live_signals`
						(`id`, `signal`, `rssi`, `gps_id`, `ap_id`, `timestamp`)
						VALUES ('', ?, ?, ?, ?, ?)";

            $prep_sig = $this->sql->conn->prepare($sql_sig);
            $prep_sig->bindParam(1, $data['sig'], PDO::PARAM_INT);
            $prep_sig->bindParam(2, $data['rssi'], PDO::PARAM_STR);
            $prep_sig->bindParam(3, $gps_id, PDO::PARAM_INT);
            $prep_sig->bindParam(4, $ap_id, PDO::PARAM_STR);
            $prep_sig->bindParam(5 , $LA, PDO::PARAM_STR);
            $this->sql->checkError( $prep_sig->execute(), __LINE__, __FILE__);

            $this->mesg[] = "Added Signal data.";

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
            $sql	=   "SELECT `signals` FROM `wifi_pointers` WHERE `mac` LIKE ? LIMIT 1";
            $result =   $this->sql->conn->prepare($sql);
            $result->bindParam(1, $macandsig[1]);
            $this->sql->checkError( $result->execute(), __LINE__, __FILE__);

            $array  =   $result->fetch(1);
            if($array['signals'] == ""){continue;}
            $sig_exp = explode("-", $array['signals']);
            foreach($sig_exp as $exp)
            {
                $ids_exp = explode(",", $exp);
                $gps_id = $ids_exp[0];

                $sql = "SELECT `lat`, `long`, `sats`, `date`, `time`
						FROM  `wifi_gps` WHERE `id` = '$gps_id' ";

                $result = $this->sql->conn->query($sql);
                if($this->sql->checkError($result, __LINE__, __FILE__))
                {
                    $this->mesg = array("error"=>"SQL Error, Check Logs.");
                    return -1;
                }

                $gpsarray = $result->fetch(2);
                if($gpsarray['long'] == "0.0000" || $gpsarray['long'] == ""){continue;}
                break;
            }
            if($gpsarray['sats'] > $pre_sat)
            {
                $use = array(
                    'lat'	=> $gpsarray['lat'],
                    'long'	=> $gpsarray['long'],
                    'date'	=> $gpsarray['date'],
                    'time'	=> $gpsarray['time'],
                    'sats'	=> $gpsarray['sats']
                );
                $pre_sat	=   $gpsarray['sats']+0;
            }
        }
        $this->mesg = $use;
        return $use;
    }

    public function GetTitleIDFromSessionID()
    {
        $sql = "SELECT `title_id` FROM `live_users` WHERE `session_id` = ?";
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
                $sql = "UPDATE `live_titles` SET `completed` = ? WHERE `id` = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $completed, PDO::PARAM_INT);
                $prep->bindParam(2, $TitleID, PDO::PARAM_INT);
                $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
                $this->mesg[] = "Session_Completed";
                return 2;
            }

            $sql = "SELECT `title_id` FROM `live_users` LEFT JOIN `live_titles` ON `live_users`.`session_id` = ? AND `live_titles`.`completed` = 0 WHERE `live_users`.`session_id` = ?";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(1, $_REQUEST['SessionID']);
            $prep->bindParam(2, $_REQUEST['SessionID']);
            $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
            $title_data = $prep->fetch(2);

            if(count($title_data) !== 1)
            {
                $this->mesg['error'] = "Session_Expired";
                return 0;
            }

            $title_id = $title_data['title_id'];
            #var_dump($title_id);
            if ($this->sec->login_check)
            {
                #var_dump("LoginCheck True");
                $sql = "SELECT `t1`.`id`, `t1`.`username`, `t1`.`session_id`, `t1`.`title_id`, `t2`.`title`, `t2`.`notes` FROM `live_users` AS `t1` LEFT JOIN `live_titles` AS `t2` ON `t2`.`id` = `t1`.`title_id` WHERE `username` = ?";
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
                    $sql = "UPDATE `live_titles` SET `timestamp` = ? WHERE id = ?";
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
                $sql = "UPDATE `live_titles` SET `timestamp` = ? WHERE `id` = ?";
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
                $this->mesg['error'] = "Completed flag was set, but no session ID to complete...";
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
            $insertTitle = "INSERT INTO `live_titles` (`id`, `title`, `notes`, `timestamp`, `completed` ) VALUES ('', ?, ?, ?, 0)";
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

        $sessionInsert = "INSERT INTO `live_users` (id, username, session_id, title_id) VALUES ('', ?, ?, ?)";
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
        $insertTitle = "INSERT INTO `live_titles` (id, title, notes) VALUES ('', ?, ?)";
        $prep_Title = $this->sql->conn->prepare($insertTitle);
        $prep_Title->bindParam(1, $date, PDO::PARAM_STR);
        $prep_Title->bindParam(2, $note, PDO::PARAM_STR);
        $this->sql->checkError($prep_Title->execute(), __LINE__, __FILE__);
        $TitleID = $this->sql->conn->lastInsertID();
        #var_dump("Title ID: " . $this->sql->conn->lastInsertID());

        $sessionInsert = "INSERT INTO `live_users` (id, username, session_id, title_id) VALUES ('', ?, ?, ?)";
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
        $sql2 = "SELECT * FROM `wifi_pointers` WHERE
				`ssid` LIKE ? AND
				`mac` LIKE ? AND
				`radio` LIKE ? AND
				`chan` LIKE ? AND
				`auth` LIKE ? AND
				`encry` LIKE ?";

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
        $result = $prep2->fetchAll(2);
        $this->mesg['AP_Result'] = $result;
        return $result;
    }

    public function SearchUsers($username = "")
    {
        $sql = "SELECT `username` FROM `user_info` WHERE `username` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $username, PDO::PARAM_STR);
        $prep->execute();
        $fetch = $prep->fetchAll(2);
        var_dump($fetch);
    }

    public function SearchUserList($title = "%", $user = "%", $min_ap = "%", $max_ap = "%", $min_gps = "%", $max_gps = "%", $min_date = "%", $max_date = "%")
    {
        $sql = "SELECT `title`, `username`, `points`, `notes`, `date`, `aps`, `gps`, `hash`, `file_id`, `converted`, `prev_ext` FROM `user_imports` WHERE
        `title` LIKE ? AND
        `username` LIKE ? ";

        if($min_ap != '%' && $max_ap != '%')
        {
            $sql .= " AND `aps` => ? AND `aps` <= ?";
        }

        if($min_gps != '%' && $max_gps != '%')
        {
            $sql .= " AND `gps` => ? AND `gps` <= ?";
        }

        if($min_date != '%' && $max_date != '%')
        {
            $sql .= " AND `date` BETWEEN ? AND ?";
        }

        $prep = $this->sql->conn->prepare($sql);
        $values = array();
        $values[] = $title;
        $values[] = $user;

        if($min_ap != '%' && $max_ap != '%') {
            $values[] = $min_ap;
            $values[] = $max_ap;
        }

        if($min_gps != '%' && $max_gps != '%') {
            $values[] = $min_gps;
            $values[] = $max_gps;
        }

        if($min_date != '%' && $max_date != '%') {
            $values[] = $min_date;
            $values[] = $max_date;
        }

        $prep->execute($values);

        $fetch = $prep->fetchAll(2);
        var_dump($fetch);
        $this->mesg['ImportListResult'] = $fetch;
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
