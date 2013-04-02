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
        $this->kill_andrew  = 1;
        $this->startdate    = "2011-Apr-14";
        $this->lastedit     = "2013-Apr-01";
        $this->vernum       = "1.0";
        $this->Author       = "Phil Ferland";
    }
    
    public function GeoNames($lat, $long)
    {
        $lat_exp = explode(".", $lat);
        $long_exp = explode(".", $long);
        $lat_alt = substr($lat_exp[1], 0, 2);
        $lat = $lat_exp[0].".".$lat_alt;
        $long_alt = substr($long_exp[1], 0, 1);
        $long = $long_exp[0].".".$long_alt;
        $lat_low = ($lat-0.01)."";
        $lat_high = ($lat+0.01)."";
        $long_sql = $long."%";
        $sql = "select `geonameid`, `country code`, `admin1 code`, `admin2 code`, `asciiname` from wifi.geonames 
            where `latitude` >= ? AND `latitude` <= ? AND `longitude` LIKE ?";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $lat_low);
        $result->bindParam(2, $lat_high);
        $result->bindParam(3, $long_sql);
        $result->execute();
        $geo_array = $result->fetch(2);
        
        if($geo_array['admin1 code'])
        {
        #    echo "Admin1 Code is Numeric, need to query the admin1 table for more information.";
            $admin1 = $geo_array['country code'].".".$geo_array['admin1 code'];

            $sql = "SELECT `asciiname` FROM `wifi`.`geonames_admin1` WHERE `admin1`= ?";
            $admin1_res = $this->sql->conn->prepare($sql);
            $admin1_res->bindParam(1, $admin1, PDO::PARAM_STR);
            $admin1_res->execute();
            $admin1_array = $admin1_res->fetch(1);
            #var_dump($admin1_array);
        }
        if(is_numeric($geo_array['admin2 code']))
        {
        #    echo "Admin2 Code is Numeric, need to query the admin2 table for more information.";
            $admin2 = $geo_array['country code'].".".$geo_array['admin1 code'].".".$geo_array['admin2 code'];
            $sql = "SELECT `asciiname` FROM `wifi`.`geonames_admin2` WHERE `admin2`= ? ";
            $admin2_res = $this->sql->conn->prepare($sql);
            $admin2_res->bindParam(1, $admin2, PDO::PARAM_STR);
            $admin2_res->execute();
            $admin2_array = $admin2_res->fetch(1);
        }
        #var_dump($admin2_array);
        $sql = "SELECT `Country` FROM `wifi`.`geonames_country_names` WHERE `ISO` LIKE ? LIMIT 1";
        $country_res = $this->sql->conn->prepare($sql);
        $country_res->bindParam(1, $geo_array['country code']."%", PDO::PARAM_STR);
        $country_res->execute();
        $country_array = $country_res->fetch(1);
        
        $this->mesg = array('Geonames'=>array(
                'Country Code'=>$geo_array['country code'],
                'Country Name'=>$country_array['Country'],
                'Admin1 Code'=>$geo_array['admin1 code'],
                'Admin1 Name'=>(@$admin1_array['asciiname'] ? $admin1_array['asciiname'] : ""),
                'Admin2 Name'=>(@$admin2_array['asciiname'] ? $admin2_array['asciiname'] : ""),
                'Area Name'=>$geo_array['asciiname']
            ));
        return 1;
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
            $this->mesg[] = "File Hash already waiting for import: $hash";
            return -1;
        }
        if($files_ret['hash'] != "")
        {
            $this->mesg[] = "File Hash already exists in WiFiDB:  $hash";
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
                $this->mesg[] = "Title: ".$title;
                $this->mesg[] = "Imported By: ".$user;
                if($otherusers)
                {
                    $this->mesg[] = "With help from: ".$otherusers;
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
                    $this->mesg[] = "File has been inserted for importing at a scheduled time. Import Number: {$this->sql->conn->lastInsertId()}, File Hash: {$hash}";
                }else
                {
                    $this->mesg[] = "There was an error inserting file for scheduled import.\r\n".
                    var_export($this->sql->conn->errorInfo());
                }
            break;
            default:
                $this->mesg[] = "Failure.... File is not supported. Try one of the supported file http://live.wifidb.net/wifidb/import/?func=supported_files";
            break;
        }
        return 1;
    }
    
    public function InsertLiveAP($data = array())
    {
        if(empty($data)){return array("Emtpy data set");}
        
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
            $this->mesg[] = "Error selecting AP data: ".var_export($this->sql->conn->errorInfo(), 1);
            return -1;
        }
        $array = $result->fetch(2);
        if(@$array['id'])
        {
            $AP_id = $array['id'];
            $this->mesg[] = "It's an old AP :/";

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
                $this->mesg[] = "Selecting data from Live GPS Table: ".var_export($this->sql->conn->errorInfo(), 1);
                return -1;
            }
            $array = $result->fetch(2);

            #list($lat, $long) = format_gps($lat, $long);

            if( (!strcmp($array['lat'], $data['lat'])) && (!strcmp($array['long'], $data['long'])) )
            {
                $sig = $all_sigs."|".$data['sig']."-".$id;
                $this->mesg[] = "Lat/Long are the same, move a little you lazy bastard.";
                $sql = "UPDATE `wifi`.`live_aps` SET `LA` = ?, `sig` = ? WHERE `id` = ?";
                $prep = $this->sql->conn->prepare($sql);
                $prep->bindParam(1, $data['date']." ".$data['time'], PDO::PARAM_INT);
                $prep->bindParam(2, $sig, PDO::PARAM_STR);
                $prep->bindParam(3, $AP_id, PDO::PARAM_INT);
                $prep->execute();
                $err = $this->sql->conn->errorCode();
                if($err !== "00000")
                {
                    $this->mesg[] = "Error updating AP data: ".var_export($this->sql->conn->errorInfo(), 1);
                    return -1;
                }else
                {
                    $this->mesg[] = "Updated AP Last Active and Signal.";
                }
            }else
            {
                $this->mesg[] = "Lat/Long are different, what aboot the Sats and Date/Time, Eh?<br />";
                $url_time   = strtotime($data['date']." ".$data['time']);
                $wifi_time    = strtotime($array['date']." ".$array['time']);
                $timecalc   = ($url_time - $wifi_time);
                #echo $timecalc."<br />";
                if($timecalc > 2)
                {
                    $this->mesg[] = "Oooo its time is newer o_0, lets go insert it<br />";
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
                        $this->mesg[] = "Error adding GPS data: ".var_export($this->sql->conn->errorInfo(), 1);
                        return -1;
                    }else
                    {
                        $this->mesg[] = "Added GPS data.";
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
                        $this->mesg[] = "Error updating AP data: ".var_export($this->sql->conn->errorInfo(), 1);
                        return -1;
                    }else
                    {
                        $this->mesg[] = "Updated AP data.";
                    }
                }else
                {
                    $this->mesg[] = "What are you thinking? You cant have more then a second resolution. >:(<br />Give a man some room to breathe.";
                }
            }
        }else
        {
            $this->mesg[] = "Add new AP. :]";

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
                $this->mesg[] = "Error adding GPS data: ".var_export($this->sql->conn->errorInfo(), 1);
                return -1;
            }else
            {
                $this->mesg[] = "Added GPS data.";
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
                $this->mesg[] = "Error adding GPS data: ".var_export($this->sql->conn->errorInfo(), 1);
                return -1;
            }else
            {
                $this->mesg[] = "Added AP data.";
            }
        }
        return 1;
    }
    
    public function Locate($list = array())
    {
        $sql    =   "SELECT `lat`, `long` FROM `wifi`.`wifi_pointers` WHERE `mac` LIKE ':mac' LIMIT 1";
        $result =   $this->sql->conn->prepare($sql);
        $pre_sat = 0;
        $use = array();
        foreach($list as $macandsig)
        {
            $mac    =   str_replace(":" , "" , $macandsig[1]);
            $result->execute(array(":mac"=>$mac));
            $err = $this->sql->conn->errorCode();
            if($err !== "00000")
            {
                $this->logd("Error Selecting AP Data.".  var_export($this->sql->conn->errorInfo(), 1));
                $this->Output(array("Error Selecting AP Data.".  var_export($this->sql->conn->errorInfo(), 1)));
                
            }
            $array  =   $result->fetch(1);
            if($array['mac'] === ''){continue;}
            if($array['long'] === "E 0.0000" || $array['long'] === "E 0000.0000"){continue;}
            if($array['sats'] > $pre_sat)
            {
                $use = array(
                    'lat'	=> $array['lat'],
                    'long'	=> $array['long'],
                    'date'	=> $array['date'],
                    'time'	=> $array['time'],
                    'sats'	=> $array['sats']
                    );
            }
            $pre_sat	=   $array['sats'];
        }
        $this->mesg = $use;
        return $use;
    }

    public function Output($mesg = NULL)
    {
        if($mesg !== NULL || $mesg[0] !== NULL)
        {
            $this->mesg = $mesg;
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
    
    public function SearchAP($details = array())
    {
        if($details === NULL){return -1;}
        $sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE ";
        foreach($details as $key=>$val)
        {
            if($val === "" || $val === NULL)
            {
                continue;
                $vals = "%";
            }
            $sql .= "`$key` LIKE :$key AND ";
        }
        $sql = substr_replace($sql, "", -4);
        $prep = $this->sql->conn->prepare($sql);
        foreach($details as $key=>$val)
        {
            if($val === "" || $val === NULL)
            {
                continue;
                $vals = "%";
            }else
            {
                $vals = "%".$val."%";
            }
            $keys = ":".$key;
            $data[$keys] = $vals;
            #$prep->bindParam($keys, $vals, PDO::PARAM_STR);
        }
        $prep->execute($data);
        $this->mesg = $prep->fetchAll(2);
        return 1;
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
