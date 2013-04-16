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
        $this->startdate    = "2011-Apr-14";
        $this->lastedit     = "2013-Apr-01";
        $this->vernum       = "1.0";
        $this->Author       = "Phil Ferland";
        $this->contact      = "pferland@randomintervals.com";
        $this->output       = (@$_REQUEST['output']    ? strtolower($_REQUEST['output']) : "json");
        $this->username     = (@$_REQUEST['username']  ? $_REQUEST['username'] : "" );
        $this->apikey       = (@$_REQUEST['apikey']    ? $_REQUEST['apikey'] : "");
        $this->session_id   = (@$_REQUEST['SessionID'] ? $_REQUEST['SessionID'] : "");
        $this->output       = (@$_REQUEST['output']    ? strtolower($_REQUEST['output']) : "json");
        $this->message      = "";
        $this->GeoNamesLoopGiveUp = $config['GeoNamesLoopGiveUp'];
        //Lets see if we can find a user with this name.
        //If so, lets check to see if the API key they provided is correct.
        $this->use_keys = 0; // DEV USE ONLY
        if($this->use_keys)
        {
            $key_result = $this->sec->ValidateAPIKey();
            if(!$key_result[0]){ $this->Output($key_result[1]); }
        }
    }
    
    private function fetch_geoname($lat_low = "", $lat_high = "", $long_low = "", $long_high = "")
    {
        #  
        $sql = "SELECT `geonameid`, `country code`, `admin1 code`, `admin2 code`, `asciiname`, `latitude`, `longitude`
        FROM `wifi`.`geonames` 
        WHERE `latitude` >= ?
        AND `latitude` <= ?
        AND `longitude` <= ?
        AND `longitude` >= ?";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $lat_low, PDO::PARAM_STR);
        $result->bindParam(2, $lat_high, PDO::PARAM_STR);
        $result->bindParam(3, $long_low, PDO::PARAM_STR);
        $result->bindParam(4, $long_high, PDO::PARAM_STR);
        $result->execute();
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

            $sql = "SELECT `asciiname` FROM `wifi`.`geonames_admin1` WHERE `admin1`= ?";
            $admin1_res = $this->sql->conn->prepare($sql);
            $admin1_res->bindParam(1, $admin1, PDO::PARAM_STR);
            $admin1_res->execute();
            $admin1_array = $admin1_res->fetch(1);
        }
        if(is_numeric($geo_array['admin2 code']))
        {
            $admin2 = $geo_array['country code'].".".$geo_array['admin1 code'].".".$geo_array['admin2 code'];
            $sql = "SELECT `asciiname` FROM `wifi`.`geonames_admin2` WHERE `admin2`= ? ";
            $admin2_res = $this->sql->conn->prepare($sql);
            $admin2_res->bindParam(1, $admin2, PDO::PARAM_STR);
            $admin2_res->execute();
            $admin2_array = $admin2_res->fetch(1);
        }
        $sql = "SELECT `Country` FROM `wifi`.`geonames_country_names` WHERE `ISO` LIKE ? LIMIT 1";
        $country_res = $this->sql->conn->prepare($sql);
        $code = $geo_array['country code']."%";
        $country_res->bindParam(1, $code, PDO::PARAM_STR);
        $country_res->execute();
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
    
    public function GetAnnouncement()
    {
        $result = $this->sql->conn->query("SELECT `auth`,`title`,`body`,`date`,`comments` FROM `wifi`.`annunc` WHERE `set` = '1'");
        $array = $result->fetch(2);
        if($this->sql->checkError() || $array['body'] == "")
        {
            $this->Output(array("error"=>array("details"=>var_dump($this->sql->conn->errorInfo()))));
            return 0;
        }
        return $array;
    }
    
    public function ImportVS1($details = array())
    {
        $user           = $details['user'];
        $otherusers     = $details['otherusers'];
        $date           = $details['date'];
        $title          = $details['title'];
        $notes          = $details['notes'];
        $size           = $details['size'];
        $hash           = $details['hash'];
        $ext            = $details['ext'];
        $filename       = $details['filename'];
        
        $tmp_prep = $this->sql->conn->prepare("SELECT `hash` FROM `wifi`.`files_tmp` WHERE `hash` = ? LIMIT 1");
        $tmp_prep->bindParam(1, $hash, PDO::PARAM_STR);
        $files_prep = $this->sql->conn->prepare("SELECT `hash` FROM `wifi`.`files` WHERE `hash` = ? LIMIT 1");
        $files_prep->bindParam(1, $hash, PDO::PARAM_STR);
        
        $tmp_prep->execute();
        $files_prep->execute();
        $tmp_ret = $tmp_prep->fetch(2);
        $files_ret = $files_prep->fetch(2);
        if($tmp_ret['hash'] != "")
        {
            $this->mesg[] = array("error"=>"File Hash already waiting for import: $hash");
            return -1;
        }
        if($files_ret['hash'] != "")
        {
            $this->mesg[] = array("error"=>"File Hash already exists in WiFiDB:  $hash");
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
                $this->mesg["title"] = $title;
                $this->mesg["user"] = $user;
                if($otherusers)
                {
                    $this->mesg['otherusers'] = $otherusers;
                }
                $sql = "INSERT INTO `wifi`.`files_tmp` 
                                ( `id`, `file`, `date`, `user`, `notes`, `title`, `size`, `hash`  )
                        VALUES ( '', ?, ?, ?, ?, ?, ?, ?)";
                $result = $this->sql->conn->prepare( $sql );

                $result->bindValue(1, $filename, PDO::PARAM_STR);
                $result->bindValue(2, $date, PDO::PARAM_STR);
                $result->bindValue(3, $user.";".$otherusers, PDO::PARAM_STR);
                $result->bindValue(4, $notes, PDO::PARAM_STR);
                $result->bindValue(5, $title, PDO::PARAM_STR);
                $result->bindValue(6, $size, PDO::PARAM_STR);
                $result->bindValue(7, $hash, PDO::PARAM_STR);
                $result->execute();
                $error = $this->sql->conn->errorCode();
                if($error[0] == "00000")
                {
                    $this->mesg["message"] = "File has been inserted for importing at a scheduled time.";
                    $this->mesg["importnum"] = $this->sql->conn->lastInsertId();
                    $this->mesg["filehash"] = $hash;
                }else
                {
                    $this->mesg = array("error" => array("desc" => "There was an error inserting file for scheduled import.", "details" => var_export($this->sql->conn->errorInfo(), 1)));
                    ;
                }
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
        
        $sql = "SELECT `id`, `ssid`, `mac`, `chan`, `sectype`, `auth`, `encry`, `radio`, `session_id`, `sig`, `lat`, `long` FROM
                `wifi`.`live_aps`
                WHERE `mac` = ?
                AND `ssid` = ?
                AND `chan` = ?
                AND `sectype` = ?
                AND `radio` = ?
                AND `session_id` = ?
                AND `username` = ? LIMIT 1";

        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $data['mac'], PDO::PARAM_STR);
        $result->bindParam(2, $data['ssid'], PDO::PARAM_STR);
        $result->bindParam(3, $data['chan'], PDO::PARAM_INT);
        $result->bindParam(4, $data['sectype'], PDO::PARAM_INT);
        $result->bindParam(5, $data['radio'], PDO::PARAM_STR);
        $result->bindParam(6, $data['session_id'], PDO::PARAM_STR);
        $result->bindParam(7, $data['username'], PDO::PARAM_STR);
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
            $this->mesg['message'][] = "It's an old AP :/" ;

            $all_sigs = $array['sig'];

            $sig_exp = explode("|", $all_sigs);

            $sig_c = count($sig_exp)-1;
            if(!$sig_c)
            {
                $sig_exp_id = explode("-", $array['sig']);
                $id = $sig_exp_id[1];
                $signal = $sig_exp_id[0];
            }else
            {
                $sig_exp_id = explode("-", $sig_exp[$sig_c]);
                $id = $sig_exp_id[1];
                $signal = $sig_exp_id[0];
            }

            $sql = "SELECT * FROM `wifi`.`live_gps` WHERE `id` = ?";
            $result = $this->sql->conn->prepare($sql);
            $result->bindParam(1, $id, PDO::PARAM_INT);
            $result->execute();
            $err = $this->sql->conn->errorCode();
            if($err !== "00000")
            {
                $this->mesg = array("error"=>array("desc"=>"Error selecting data from Live GPS Table.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
                return -1;
            }
            $array = $result->fetch(2);

            #list($lat, $long) = format_gps($lat, $long);

            if( (!strcmp($array['lat'], $data['lat'])) && (!strcmp($array['long'], $data['long'])) )
            {
                $sig = $all_sigs."|".$data['sig']."-".$id;
                $this->mesg['message'][] = "Lat/Long are the same, move a little you lazy bastard.";
                $sql = "UPDATE `wifi`.`live_aps` SET `LA` = ?, `sig` = ? WHERE `id` = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $data['date']." ".$data['time'], PDO::PARAM_INT);
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
                    $this->mesg["message"][] = "Updated AP Last Active and Signal.";
                }
            }else
            {
                $this->mesg[0]["message"] = "Lat/Long are different, what aboot the Sats and Date/Time, Eh?";
                $url_time   = strtotime($data['date']." ".$data['time']);
                $wifi_time    = strtotime($array['date']." ".$array['time']);
                $timecalc   = ($url_time - $wifi_time);
                #echo $timecalc."<br />";
                if($timecalc > 2)
                {
                    $this->mesg["message"][] = "Oooo its time is newer o_0, lets go insert it";
                    $sql = "INSERT INTO `wifi`.`live_gps` (`id`, `lat`, `long`, `sats`, `hdp`, `alt`, `geo`, `kmh`, `mph`, `track`, `date`, `time`, `session_id`)
                                                   VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

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
                    $prep->bindParam(10, $data['date'], PDO::PARAM_STR);
                    $prep->bindParam(11, $data['time'], PDO::PARAM_STR);
                    $prep->bindParam(12, $data['session_id'], PDO::PARAM_STR);
                    $prep->execute();
                    $err = $this->sql->conn->errorCode();
                    if($err !== "00000")
                    {
                        $this->mesg[] = array("error"=>array("desc"=>"Error adding GPS data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
                        return -1;
                    }else
                    {
                        $this->mesg["message"][] = "Added GPS data.";
                    }
                    
                    $sig = $all_sigs."|".$data['sig']."-".$this->sql->conn->insert_id;

                    $sql = "UPDATE `wifi`.`live_aps` SET `sig` = ?, `LA` = ?, `lat` = ?, `long` = ? WHERE `id` = ?";
                    #echo $sql."<br /><br />";
                    $prep = $this->sql->conn->prepare($sql);
                    $prep->bindParam(1, $sig, PDO::PARAM_STR);
                    $prep->bindParam(2, $data['date']." ".$data['time'], PDO::PARAM_STR);
                    $prep->bindParam(3, $data['lat'], 2);
                    $prep->bindParam(4, $data['long'], 2);
                    $prep->bindParam(5, $AP_id, 1);
                    $prep->execute();
                    $err = $this->sql->conn->errorCode();
                    if($err !== "00000")
                    {
                        $this->mesg[] = array("error"=>array("desc"=>"Error updating AP data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
                        return -1;
                    }else
                    {
                        $this->mesg["message"][] = "Updated AP data.";
                    }
                }else
                {
                    $this->mesg["message"][] = "What are you thinking? You cant have more then a second resolution. >:( Give a man some room to breathe.";
                }
            }
        }else
        {
            $this->mesg["message"][] = "Add new AP. :]";

            #list($lat, $long) = $this->format_gps($lat, $long);

            $sql = "INSERT INTO `wifi`.`live_gps` (`id`, `lat`, `long`, `sats`, `hdp`, `alt`, `geo`, `kmh`, `mph`, `track`, `date`, `time`)
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
            $prep->bindParam(9, $data['track'], PDO::PARAM_STR);
            $prep->bindParam(10, $data['date'], PDO::PARAM_STR);
            $prep->bindParam(11, $data['time'], PDO::PARAM_STR);
            $prep->execute();
            $err = $this->sql->conn->errorCode();
            if($err !== "00000")
            {
                $this->mesg[] = array("error"=>array("desc"=>"Error adding GPS data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
                return -1;
            }else
            {
                $this->mesg["message"][] = "Added GPS data.";
            }
            $sig = $data['sig']."-".$this->sql->conn->lastInsertId();
            $date_time = $data['date']." ".$data['time'];
            $sql = "INSERT INTO  `wifi`.`live_aps` ( `id`, `ssid`, `mac`,  `chan`, `radio`, `auth`, `encry`, `sectype`,
                `BTx`, `OTx`, `NT`, `label`, `sig`, `username`, `FA`, `LA`, `lat`, `long`, `session_id`)
                                            VALUES ('', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ? ) ";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(1, $data['ssid'], PDO::PARAM_STR);
            $prep->bindParam(2, $data['mac'], PDO::PARAM_STR);
            $prep->bindParam(3, $data['chan'], PDO::PARAM_STR);
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
            $prep->execute();
            $err = $this->sql->conn->errorCode();
            if($err !== "00000")
            {
                $this->mesg[] = array("error"=>array("desc"=>"Error adding GPS data.", "details"=>var_export($this->sql->conn->errorInfo(), 1)));
                return -1;
            }else
            {
                $this->mesg["message"][] = "Added AP data.";
            }
        }
        return 1;
    }
    
    public function Locate()
    {
        $listing        =   array();
        $lists          =   explode("-", $this->LocateList);

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
            $sql    =   "SELECT `signals` FROM `wifi`.`wifi_pointers` WHERE `mac` LIKE ? LIMIT 1";
            $result =   $this->sql->conn->prepare($sql);
            $result->bindParam(1, $macandsig[1]);
            $result->execute();
            $this->sql->checkError();
            
            $array  =   $result->fetch(1);
            if($array['signals'] == ""){continue;}
            $sig_exp = explode("-", $array['signals']);
            foreach($sig_exp as $exp)
            {
                $ids_exp = explode(",", $exp);
                $gps_id = $ids_exp[0];

                $sql = "SELECT `lat`, `long`, `sats`, `date`, `time` 
                        FROM  `wifi`.`wifi_gps` WHERE `id` = '$gps_id' ";
                
                $result = $this->sql->conn->query($sql);
                if($this->sql->checkError())
                {
                    $this->mesg[] = array("error"=>var_export($this->sql->conn->errorInfo(), 1));
                    return -1;
                }

                $gpsarray = $result->fetch(2);
                if($gpsarray['long'] == "E 0.0000" || $gpsarray['long'] == "E 0000.0000" || $gpsarray['long'] == ""){continue;}
                break;
            }
            if($gpsarray['sats'] >= $pre_sat)
            {
                $use = array(
                    'lat'	=> $gpsarray['lat'],
                    'long'	=> $gpsarray['long'],
                    'date'	=> $gpsarray['date'],
                    'time'	=> $gpsarray['time'],
                    'sats'	=> $gpsarray['sats']
                    );
            }
            $pre_sat	=   $gpsarray['sats']+0;
        }
        $this->mesg = $use;
        return $use;
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
        $sql2 = "SELECT * FROM `wifi`.`wifi_pointers` WHERE 
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
