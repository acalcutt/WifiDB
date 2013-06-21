<?php
/**
 * Created by Phillip Ferland
 * Date: 5/27/13
 * Time: 7:06 PM
 *
 */

class convert
{
    public function __construct($dbcore)
    {
        $this->languages = $dbcore->lang;
        $this->core = $dbcore;
    }

    /**
     * @param int $sig_in
     * @return float
     */
    public function Sig2dBm($sig_in = 0)
    {
        $dBm = ((($this->dBmMaxSignal - $this->dBmDissociationSignal) * $sig_in) - (20 * $this->dBmMaxSignal) + (100 * $this->dBmDissociationSignal)) / 80;
        $dbm_out =  round($dBm);
        return $dbm_out;
    }

    /**
     * @param int $sig_in
     * @return float
     */
    public function dBm2Sig($sig_in = 0)
    {
        $SIG = 100 - 80 * ($this->dBmMaxSignal - $sig_in) / ($this->dBmMaxSignal - $this->dBmDissociationSignal);
        if($SIG < 0){$SIG = 0;}
        $round = round($SIG, 2);
        return $round;
    }

    /**
     * @param $source
     * @return array
     */
    public function csv($source = "")
    {
        $apdata = array();
        $gpsdata = array();
        if(($handle = fopen($source, "r")) !== FALSE)
        {
            $n = 1;
            while(($line = fgetcsv($handle, 1000, ",")) !== FALSE)
            {
                if($line[1] == "BSSID" && $line[2] == "MANUFACTURER" && $line[3] == "SIGNAL" && $line[4] == "High Signal"){continue;}
                $line_count = count($line);
                if($line_count < 26){echo "CSV file with less than 26 fields\r\n";break;}
                if(!@$this->languages->code)
                {
                    $this->languages->code = $this->languages->FindLanguageType($line[8]);
                }
                if ($line[7]===$this->languages->current_language[1]['SearchWords']['Open'] && $line[8]===$this->languages->current_language[1]['SearchWords']['None']){$sectype="1";}
                if ($line[7]===$this->languages->current_language[1]['SearchWords']['Open'] && $line[8]===$this->languages->current_language[1]['SearchWords']['WEP']){$sectype="2";}
                if ($line[8] !== $this->languages->current_language[1]['SearchWords']['None'] || $line[8] !== $this->languages->current_language[1]['SearchWords']['WEP']){$sectype="3";}

                $ap_hash = md5(
                    $line[0].
                    $line[1].
                    $line[10].
                    $sectype.
                    $line[9].
                    $line[7].
                    $line[8]);
                $gpsdata[$n] = array(
                    "id"=>$n,
                    "lat"=>$this->dd2dm($line[15]),
                    "long"=>$this->dd2dm($line[16]),
                    "sats"=>$line[17],
                    "hdp"=> $line[18],
                    "alt"=> $line[19],
                    "geo"=> $line[20],
                    "kmh"=> $line[21],
                    "mph"=> $line[22],
                    "track"=> $line[23],
                    "date"=>$line[24],
                    "time"=>$line[25]
                );
                if(is_array(@$apdata[$ap_hash]))
                {
                    $apdata[$ap_hash]['sig'][$n] = array($n,$line[3],$line[5]);
                }
                else
                {
                    $apdata[$ap_hash] = array(
                        "ssid"=>$line[0],
                        "mac"=>$line[1],
                        "man"=>$line[2],
                        "HighSig"=>$line[4],
                        "HighRSSI"=>$line[6],
                        "auth"=>$line[7],
                        "encry"=>$line[8],
                        "sectype"=>$sectype,
                        "radio"=>$line[9],
                        "chan"=>$line[10],
                        "btx"=>$line[11],
                        "otx"=>$line[12],
                        "nt"=>$line[13],
                        "label"=>$line[14],
                        "sig"=>array($n => array($n,$line[3],$line[5]))
                    );
                }
                $n++;
            }
            fclose($handle);
        }
        $apdata1 = array();
        foreach($apdata as $ap)
        {
            $apdata1[] = $ap;
        }
        #echo "Counts: ".count($apdata)." | ".count($gpsdata)."\r\n";
        $data = array($apdata1, $gpsdata);
        return $data;
    }

    #===============================#
    #   Convert GeoCord DecDeg to DegMin    #
    #===============================#
    /**
     * @param string $geocord_in
     * @return string
     */
    public function dd2dm($geocord_in="")
    {
        $neg = FALSE;
        $geocord_exp = explode(".", $geocord_in);
        $geo_front = $geocord_exp[0];
        $geo_back = $geocord_exp[1];

        $pattern[0] = '/N /';
        $pattern[1] = '/E /';
        $replacement = "";
        $geo_front = preg_replace($pattern, $replacement, $geo_front);

        $pattern_neg[0] = '/S /';
        $pattern_neg[1] = '/W /';
        $replacement_neg = "-";
        $geo_front = preg_replace($pattern_neg, $replacement_neg, $geo_front);

        if($geo_front[0] == "-")
        {
            $neg = TRUE;
        }
        #var_dump($geo_front);
        if(strlen($geo_front) >= 4)
        {
            $out = $geo_front.'.'.$geo_back;
            return $out;
        }

        // 4.146255 |----| 4 || 146255
        $geocord_dec = "0.".$geo_back;
        if($geocord_dec == "0.0000")
        {
            $ret = ($neg ? "-0000.0000" : "0000.0000");
            return $ret;
        }

        // 4.146255 |----| 4 || 0.146255
        $geocord_mult = $geocord_dec*60;
        // 4.146255 |----| 4 || (0.146255)*60 = 8.7753
        $mult = explode(".",$geocord_mult);

        if( strlen($mult[0]) < 2 )
        {
            $geocord_mult = "0".$geocord_mult;
        }
        // 4.146255 |----| 4 || 08.7753
        $geocord_out = $geo_front.$geocord_mult;
        // 4.146255 |----| 408.7753

        $geocord_o = explode(".", $geocord_out);
        if(!@$geocord_o[1])
        {
            var_dump($geocord_in);
            var_dump($php_errormsg);
            die();
        }
        if( strlen($geocord_o[1]) > 4 ) //undefined offset ??? wtf...
        {
            $geocord_out = $geocord_o[0].".".substr($geocord_o[1], 0 , 4);
        }
        #var_dump($geocord_in, $geocord_out);
        return $geocord_out;
    }

    /**
     * @param string $geocord_in
     * @return int|string
     * @throws ErrorException
     */
    public function dm2dd($geocord_in = "")
    {
        $geocord_in_exp = explode(".", $geocord_in);
        if(strlen($geocord_in_exp[1]) > 4){return $geocord_in;}

        $start = microtime(true);
        //	GPS Convertion :
        $neg=FALSE;
        $geocord_exp = explode(".", $geocord_in);//replace any Letter Headings with Numeric Headings
        if($geocord_exp[0][0] === "S" or $geocord_exp[0][0] === "W"){$neg = TRUE;}
        $patterns[0] = '/N /';
        $patterns[1] = '/E /';
        $patterns[2] = '/S /';
        $patterns[3] = '/W /';
        $replacements = "";
        $geocord_in = preg_replace($patterns, $replacements, $geocord_in);
        $geocord_exp = explode(".", $geocord_in);
        if($geocord_exp[0][0] === "-"){$geocord_exp[0] = 0 - $geocord_exp[0];$neg = TRUE;}

        if(!@$geocord_exp[1])
        {
            var_dump($geocord_in);
        }
        // 428.7753 ---- 428 - 7753
        $geocord_dec = "0.".$geocord_exp[1];
        // 428.7753 ---- 428 - 0.7753
        $len = strlen($geocord_exp[0]);
        #		echo $len.'<BR>';
        $geocord_min = substr($geocord_exp[0],-2,3);
        #		echo $geocord_min.'<BR>';
        // 428.7753 ---- 4 - 28 - 0.7753
        $geocord_min = $geocord_min+$geocord_dec;
        // 428.7753 ---- 4 - 28.7753
        $geocord_div = $geocord_min/60;
        // 428.7753 ---- 4 - (28.7753)/60 = 0.4795883
        switch($len)
        {
            case 2:
                $geocord_deg = 0;
                #			echo $geocord_deg.'<br>';
                break;

            case 3:
                $geocord_deg = substr($geocord_exp[0], 0,1);
                #			echo $geocord_deg.'<br>';
                break;

            case 4:
                $geocord_deg = substr($geocord_exp[0], 0,2);
                #			echo $geocord_deg.'<br>';
                break;

            case 5:
                $geocord_deg = substr($geocord_exp[0], 0,3);
                #			echo $geocord_deg.'<br>';
                break;

            default:
                throw new ErrorException("Unexpected Geocord front length encountered in dm2dd");
                break;
        }
        if(!isset($geocord_deg))
        {
            echo $geocord_in."\r\n";
            return -1;
        }
        $geocord_out = $geocord_deg + $geocord_div;
        // 428.7753 ---- 4.4795883
        if($neg === TRUE){$geocord_out = "-".$geocord_out;}
        $geocord_out = substr($geocord_out, 0,10);
        #var_dump($geocord_out);
        return $geocord_out;
    }

    /**
     * @param $source
     * @return string
     */
    public function extractVSZ($source = "")
    {
        $dir = $this->PATH."import/up/";
        $file_exp = explode(".", $source);
        $folder_exp = explode("/", $file_exp[0]);
        $c = count($folder_exp)-1;
        $folder = $folder_exp[$c];
        $rand_folder = rand(000000, 999999).'_'.$folder;
        $extract_path = $dir."convert/".$rand_folder;

        $this->verbosed("Make Extract folder for this file: $source\r\n$extract_path");
        if(@mkdir($extract_path))
        {
            $this->verbosed("Folder created!", 2);
        }else
        {
            $this->verbosed("Failed to create the folder", -1);
        }
        $zip = new ZipArchive;
        $zip->open($source, ZIPARCHIVE::CREATE);
        $zip->extractTo($extract_path, array($zip->getNameIndex(0)));
        rename($extract_path."/data.vs1", $extract_path."/$folder.vs1");
        return $extract_path."/$folder.vs1";
    }

    /**
     * @param string $Found_Capabilities
     * @return array
     */
    private function findCapabilities($Found_Capabilities = "")
    {
        If(stristr($Found_Capabilities, "WPA2-PSK-CCMP") Or stristr($Found_Capabilities, "WPA2-PSK-TKIP+CCMP"))
        {	$Found_AUTH = "WPA2-Personal";
            $Found_ENCR = "CCMP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WPA-PSK-CCMP") Or stristr($Found_Capabilities, "WPA-PSK-TKIP+CCMP"))
        {	$Found_AUTH = "WPA-Personal";
            $Found_ENCR = "CCMP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WPA2-EAP-CCMP") Or stristr($Found_Capabilities, "WPA2-EAP-TKIP+CCMP"))
        {	$Found_AUTH = "WPA2-Enterprise";
            $Found_ENCR = "CCMP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WPA-EAP-CCMP") Or stristr($Found_Capabilities, "WPA-EAP-TKIP+CCMP"))
        {	$Found_AUTH = "WPA-Enterprise";
            $Found_ENCR = "CCMP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WPA2-PSK-TKIP"))
        {	$Found_AUTH = "WPA2-Personal";
            $Found_ENCR = "TKIP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WPA-PSK-TKIP"))
        {	$Found_AUTH = "WPA-Personal";
            $Found_ENCR = "TKIP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WPA2-EAP-TKIP"))
        {	$Found_AUTH = "WPA2-Enterprise";
            $Found_ENCR = "TKIP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WPA-EAP-TKIP"))
        {	$Found_AUTH = "WPA-Enterprise";
            $Found_ENCR = "TKIP";
            $Found_SecType = 3;
        }ElseIf(stristr($Found_Capabilities, "WEP"))
        {	$Found_AUTH = "Open";
            $Found_ENCR = "WEP";
            $Found_SecType = 2;
        }Else
        {	$Found_AUTH = "Open";
            $Found_ENCR = "None";
            $Found_SecType = 1;
        }
        if(stristr($Found_Capabilities, "IBSS"))
        {
            $nt = "Ad-Hoc";
        }else
        {
            $nt = "Infrastructure";
        }
        $out = array($Found_AUTH, $Found_ENCR, $Found_SecType, $nt);
        return $out;
    }

    /**
     * @param $frequency
     * @return array
     */
    private function findFreq($frequency = 0)
    {
        switch($frequency)
        {
            case 2412:
                $chan = 1;
                $radio = "802.11g";
                break;
            case 2417:
                $chan = 2;
                $radio = "802.11g";
                break;
            case 2422:
                $chan = 3;
                $radio = "802.11g";
                break;
            case 2427:
                $chan = 4;
                $radio = "802.11g";
                break;
            case 2432:
                $chan = 5;
                $radio = "802.11g";
                break;
            case 2437:
                $chan = 6;
                $radio = "802.11g";
                break;
            case 2442:
                $chan = 7;
                $radio = "802.11g";
                break;
            case 2447:
                $chan = 8;
                $radio = "802.11g";
                break;
            case 2452:
                $chan = 9;
                $radio = "802.11g";
                break;
            case 2457:
                $chan = 10;
                $radio = "802.11g";
                break;
            case 2462:
                $chan = 11;
                $radio = "802.11g";
                break;
            case 2467:
                $chan = 12;
                $radio = "802.11g";
                break;
            case 2472:
                $chan = 13;
                $radio = "802.11g";
                break;
            case 2484:
                $chan = 14;
                $radio = "802.11g";
                break;
            case 5180:
                $chan = 36;
                $radio = "802.11n";
                break;
            case 5200:
                $chan = 40;
                $radio = "802.11n";
                break;
            case 5220:
                $chan = 44;
                $radio = "802.11n";
                break;
            case 5240:
                $chan = 48;
                $radio = "802.11n";
                break;
            case 5260:
                $chan = 52;
                $radio = "802.11n";
                break;
            case 5280:
                $chan = 56;
                $radio = "802.11n";
                break;
            case 5300:
                $chan = 60;
                $radio = "802.11n";
                break;
            case 5320:
                $chan = 64;
                $radio = "802.11n";
                break;
            case 5500:
                $chan = 100;
                $radio = "802.11n";
                break;
            case 5520:
                $chan = 104;
                $radio = "802.11n";
                break;
            case 5540:
                $chan = 108;
                $radio = "802.11n";
                break;
            case 5560:
                $chan = 112;
                $radio = "802.11n";
                break;
            case 5580:
                $chan = 116;
                $radio = "802.11n";
                break;
            case 5600:
                $chan = 120;
                $radio = "802.11n";
                break;
            case 5620:
                $chan = 124;
                $radio = "802.11n";
                break;
            case 5640:
                $chan = 128;
                $radio = "802.11n";
                break;
            case 5660:
                $chan = 132;
                $radio = "802.11n";
                break;
            case 5680:
                $chan = 136;
                $radio = "802.11n";
                break;
            case 5700:
                $chan = 140;
                $radio = "802.11n";
                break;
            case 5745:
                $chan = 149;
                $radio = "802.11n";
                break;
            case 5765:
                $chan = 153;
                $radio = "802.11n";
                break;
            case 5785:
                $chan = 157;
                $radio = "802.11n";
                break;
            case 5805:
                $chan = 161;
                $radio = "802.11n";
                break;
            case 5825:
                $chan = 165;
                $radio = "802.11n";
                break;
            default:
                $chan = 6;
                $radio = "802.11g";
                break;
        }
        $out = array($chan, $radio);
        return $out;
    }

    /**
     * @param string $source
     * @return int|string
     * @throws ErrorException
     */
    public function main($source = "")
    {
        if($source == "")
        {
            throw new ErrorException("File to convert is blank...");
            return 0;
        }
        $file_parts = pathinfo($source);
        $extension = strtolower($file_parts['extension']);
        switch($extension)
        {
            case "db":
                $data = $this->wardrive4($source);
                break;
            case "db3":
                $data = $this->wardrive3($source);
                break;
            case "csv":
                $data = $this->csv($source);
                break;
            case "vsz":
                $data = $this->extractVSZ($source);
                break;
            default:
                $this->verbosed("Unsupported File Type of : $extension", -1);
                $this->logd("Unsupported File Type of : $extension", $this->This_is_me);
                break;
        }
        if(!is_string($data))
        {
            $filename = $this->WriteVS1File($source, $data);
        }else
        {
            $filename = $data;
        }
        $parts = pathinfo($filename);
        $copy = $this->PATH.'import/up/'.$parts['basename'];
        #var_dump($filename, $copy);
        copy($filename, $copy);
        return $copy;
    }


    /**
     * @param $source
     */
    public function txt($source)
    {
        $apdata = array();
        $gpsdata = array();
        $return = file($source);

        //create interval for progress
        $line = count($return);
        $stat_c = $line/97;
        $complete = 0;
        $n=0;
        $N=0;
        $c = 0;
        $cc = 0;
        if ($this->debug ==1){echo $stat_c."\r\n";}
        if ($this->debug ==1){echo $line."\r\n";}
        // Start the main loop
        foreach($return as $ret)
        {
            $c++;
            $cc++;
            if ($ret[0] == "#"){continue;}
            $wifi = explode("|",$ret);
            $ret_count = count($wifi);
            if ($ret_count == 17)// test to see if the data is in correct format
            {
                if ($cc >= $stat_c)
                {
                    $cc=0;
                    $complete++;
                    echo $complete."% - ";
                    if ($complete == 100 ){ echo "\r\n\r\n";}
                }
                //format date and time
                $datetime = explode(" ",$wifi[13]);
                $date = $datetime[0];
                $time = $datetime[1];

                // Create the Security Type number for the respective Access point
                if ($wifi[4]=="Open"&&$wifi[5]=="None"){$sectype="1";}
                if ($wifi[4]=="Open"&&$wifi[5]=="WEP"){$sectype="2";}
                if ($wifi[4]=="WPA-Personal" or $wifi[4] =="WPA2-Personal"){$sectype="3";}

                if ($GLOBALS["debug"] ==1)
                {echo "\$n = ".$n."\n\$N = ".$N."\n";}
                $n++;
                $N++;
                $sig=$n.",".$wifi[3];
                $gpsdata[$n]=array(
                    "id"=>$n,
                    "lat"=>$this->dd2dm($wifi[8]),
                    "long"=>$this->dd2dm($wifi[9]),
                    "sats"=>'0',
                    "hdp"=> '0.0',
                    "alt"=> '0.0',
                    "geo"=> '-0.0',
                    "kmh"=> '0.0',
                    "mph"=> '0.0',
                    "track"=> '0.0',
                    "date"=>$date,
                    "time"=>$time
                );

                $apdata[$N]=array(
                    "ssid"=>$wifi[0],
                    "mac"=>$wifi[1],
                    "man"=>$wifi[2],
                    "auth"=>$wifi[4],
                    "encry"=>$wifi[5],
                    "sectype"=>$sectype,
                    "radio"=>$wifi[6],
                    "chan"=>$wifi[7],
                    "btx"=>$wifi[10],
                    "otx"=>$wifi[11],
                    "nt"=>$wifi[14],
                    "label"=>$wifi[15],
                    "sig"=>$sig
                );
                if ($GLOBALS["debug"] == 1 )
                {
                    echo "\n\n+_+_+_+_+_+_\n".$gpsdata[$n]["lat"]."  +_\n".$gpsdata[$n]["long"]."  +_\n".$gpsdata[$n]["sats"]."  +_\n".$gpsdata[$n]["date"]."  +_\n".$gpsdata[$n]["time"]."  +_\n";
                    echo "Access Point Number: ".$N."\n";
                    echo "=-=-=-=-=-=-\n".$apdata[$N]["ssid"]."  =-\n".$apdata[$N]["mac"]."  =-\n".$apdata[$N]["auth"]."  =-\n".$apdata[$N]["encry"]."  =-\n".$apdata[$N]["sectype"]."  =-\n".$apdata[$N]["radio"]."  =-\n".$apdata[$N]["chan"]."  =-\n".$apdata[$N]["btx"]."  =-\n".$apdata[$N]["otx"]."  =-\n".$apdata[$N]["nt"]."  =-\n".$apdata[$N]["label"]."  =-\n".$apdata[$N]["sig"]."\n";
                }

            }else
            {
                echo "\nLine: ".$c." - Wrong data type, dropping row\n";
            }
        }
    }


    /**
     * @param $source
     * @return array
     */
    public function wardrive3($source)
    {
        $apdata = array();
        $gpsdata = array();
        $dbh = new PDO("sqlite:$source"); // success
        $dbh->setAttribute(PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION);
        $thing = $dbh->query("SELECT * FROM networks");
        $all_aps = $thing->fetchAll();
        $n=0;
        foreach ($all_aps as $row)
        {
            list($authen, $encry, $sectype, $nt) = $this->findCapabilities($row["capabilities"]);
            list($chan, $radio) = $this->findFreq($row['Frequency']);
            #echo "$timestamp - - $man\r\n$nt - $authen - $encry - $sectype - $lat - $long\r\n----------\r\n\r\n";
            $n++;
            $gpsdata[$n]=array(
                "id"=>$n,
                "lat"=>$this->dd2dm($row['lat']),
                "long"=>$this->dd2dm($row['lon']),
                "sats"=>'0',
                "hdp"=> '0.0',
                "alt"=> $row['alt'],
                "geo"=> '-0.0',
                "kmh"=> '0.0',
                "mph"=> '0.0',
                "track"=> '0.0',
                "date"=>date($this->date_format, substr($row["timestamp"], 0, -3)),
                "time"=>date($this->time_format, substr($row["timestamp"], 0, -3))
            );
            $apdata[$n]=array(
                "ssid"=>$row['ssid'],
                "mac"=>$row['bssid'],
                "man"=>$this->core->findManuf($row['bssid']),
                "auth"=>$authen,
                "encry"=>$encry,
                "sectype"=>$sectype,
                "radio"=>$radio,
                "chan"=>$chan,
                "btx"=>"0",
                "otx"=>"0",
                'highsig'=>$this->dBm2Sig($row['level']),
                'highRSSI'=>$row['level'],
                "nt"=>$nt,
                "label"=>"Unknown",
                "sig"=>array($n,$this->dBm2Sig($row['level']),$row['level'])
            );
            #echo "Access Point Number: ".$N."\n";
        }
        #echo "Counts: ".count($apdata)." | ".count($gpsdata)."\r\n";
        $data = array($apdata, $gpsdata);
        return $data;
    }


    /**
     * @param $source
     * @return array
     */
    public function wardrive4($source)
    {
        $apdata = array();
        $gdata = array();
        $dbh = new PDO("sqlite:$source");
        $dbh->setAttribute(PDO::ATTR_ERRMODE,
            PDO::ERRMODE_EXCEPTION);

        $APQuery = $dbh->query("SELECT * FROM `wifi`");
        $all_aps = $APQuery->fetchAll(2);

        foreach($all_aps as $ap)
        {
            #echo "--------------------------\r\n";
            #var_dump($ap);
            list($authen, $encry, $sectype, $nt) = $this->findCapabilities($ap["capabilities"]);
            list($chan, $radio) = $this->findFreq($ap['Frequerncy']);
            $apdata[$ap['_id']] = array(
                'ssid'=>$ap['ssid'],
                'mac'=>$ap['bssid'],
                'man'=>$this->core->findManuf($ap['bssid']),
                'auth'=>$authen,
                'encry'=>$encry,
                'sectype'=>$sectype,
                'chan'=>$chan,
                'radio'=>$radio,
                'btx'=>'0',
                'otx'=>'0',
                'highsig'=>$this->dBm2Sig($ap['level']),
                'highRSSI'=>$ap['level'],
                'nt'=>$nt,
                'label'=>'Unknown'
            );
            $apdata[$ap['_id']]['sig'] = array();
            $id = $ap['_id'];
            $sql1 = "SELECT * FROM `wifispot` WHERE `fk_wifi` = '$id'";
            #echo $sql1."\r\n";
            $gps_query = $dbh->query($sql1);
            $gps_fetch = $gps_query->fetchAll(2);
            $n=0;
            foreach($gps_fetch as $point)
            {
                $n++;
                $apdata[$ap['_id']]['sig'][] = $point['_id'].",".$this->dBm2Sig($point['level']).",".$point['level'];
                $gdata[$point['_id']] = array(
                    "id"=>$n,
                    'lat' => $this->dd2dm($point['lat']),
                    'long'=> $this->dd2dm($point['lon']),
                    'sats'=> 0,
                    "hdp"=> '0.0',
                    'alt' => $point['alt'],
                    "geo"=> '-0.0',
                    "kmh"=> '0.0',
                    "mph"=> '0.0',
                    "track"=> '0.0',
                    'date'=> date($this->date_format, substr($point['timestamp'], 0, -3)),
                    'time'=> date($this->time_format, substr($point['timestamp'], 0, -3)),
                );
            }
        }

        $out = array($apdata, $gdata);
        return $out;
    }

    /**
     * @param string $source
     * @param array $data
     * @return int|string
     */
    public function WriteVS1File($source = "", $data = array())
    {
        if($data[1] == NULL){return 0;}
        if($source == ""){return 0;}
        $dir = $this->PATH.'import/up/convert/';
        $file_parts = pathinfo($source);
        $filename = rand(000000,999999).'_'.$file_parts['filename'].'.vs1';
        $fullfile = $dir.$filename;

        # Dump GPS data to VS1 File
        $h1 = "# Vistumbler VS1 - Detailed Export Version 4.0
# Created By: RanInt WiFiDB Alpha
# ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- #
# GpsID|Latitude|Longitude|NumOfSatalites|HorizontalDilutionOfPrecision|Altitude(m)|HeightOfGeoidAboveWGS84Ellipsoid(m)|Speed(km/h)|Speed(MPH)|TrackAngle(Deg)|Date(UTC y-m-d)|Time(UTC h:m:s.ms)
# ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- #
";
        $gpsd = $h1;
        $n=1;
        foreach( $data[1] as $gps )
        {
            //GPS Convertion  if needed, check for ddmm.mmmm and leave it alone, otherwise i am guessing its DD.mmmmm and that needs to be converted to ddmm.mmmm:
            if($gps['lat'] != "0.0000000" || $gps['lat'] != "N 0.00000" ||$gps['lat'] != "N 0000.0000")
            {
                $exp = explode(".", $gps['lat']);

                if(strlen($exp[1]) > 4)
                {
                    $lat  = $gps['lat'];
                    $long = $gps['long'];
                }else
                {
                    $lat  = $this->dd2dm($gps['lat']);
                    $long = $this->dd2dm($gps['long']);
                }
                #echo $gps['lat']." - ".$lat."\r\n";
                #echo $gps['long']." - ".$long."\r\n---------------------------\r\n";
                if(substr($lat,0,1) == "-")
                {
                    $lat = "S ".str_replace("-", "", $lat);
                }else
                {
                    $lat = "N ".$lat;
                }
                if(substr($long,0,1) == "-")
                {
                    $long = "W ".str_replace("-", "", $long);
                }else
                {
                    $long = "E ".$long;
                }
                //END GPS convert
            }
            else
            {
                $lat = $gps['lat'];
                $long = $gps['long'];
            }
            $gpsd .= $n."|".$lat."|".$long."|".$gps["sats"]."|".$gps["hdp"]."|".$gps["alt"]."|".$gps["geo"]."|".$gps["kmh"]."|".$gps["mph"]."|".$gps["track"]."|".$gps["date"]."|".$gps["time"]."\r\n";
            $n++;
        }
        $ap_head = "#------------------------------------------------------------------------------------------------------------------------------------------------------------------- #
# SSID|BSSID|MANUFACTURER|Authentication|Encryption|Security Type|Radio Type|Channel|Basic Transfer Rates|Other Transfer Rates|Network Type|High Signal|High RSSI|Label|GID,SIGNAL,RSSI
# -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- #
";
        $apd = $gpsd.$ap_head;
        foreach($data[0] as $ap)
        {
            $apd .= $ap["ssid"]."|".$ap["mac"]."|".$ap["man"]."|".$ap["auth"]."|".$ap["encry"]."|".$ap["sectype"]."|".$ap["radio"]."|".$ap["chan"]."|".$ap["btx"]."|".$ap["otx"]."|".$ap['highsig']."|".$ap['highRSSI']."|".$ap["nt"]."|".$ap["label"]."|".implode("\\", $ap["sig"])."\r\n";
        }
        file_put_contents($fullfile, $apd);
        return $fullfile;
    }

}