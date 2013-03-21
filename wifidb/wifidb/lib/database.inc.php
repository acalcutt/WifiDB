<?php
#Daetabase.inc.php, holds the database interactive functions.
#Copyright (C) 2011 Phil Ferland
#
#This program is free software; you can redistribute it and/or modify it under the terms
#of the GNU General Public License as published by the Free Software Foundation; either
#version 2 of the License, or (at your option) any later version.
#
#This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
#without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#See the GNU General Public License for more details.
#
#You should have received a copy of the GNU General Public License along with this program;
#if not, write to the
#
#   Free Software Foundation, Inc.,
#   59 Temple Place, Suite 330,
#   Boston, MA 02111-1307 USA
############################################################


#===========================================================#
#   WiFiDB Database Class that holds DB based functions     #
#===========================================================#
class database extends dbcore
{
    #===========================#
    #   __construct (default)   #
    #===========================#
    function __construct($config)
    {
        parent::__construct($config);
    }

    #=======================================================================================#
    #   Table_exists (Check to see if a table is in the DB before trying to read from it)   #
    #=======================================================================================#
    function table_exists($table="", $db="")
    {
        $result = $this->sql->conn->query("SHOW TABLES FROM ".$this->sql->db);
        while($temp = $result->fetch_array(1))
        {
            if ($temp['Tables_in_'.$this->sql->db] == $table)
            {
                return TRUE;
            }
        }
        return FALSE;
    }

    #=======================================================================#
    #   Grab the Manuf for a given MAC, return Unknown Manuf if not found   #
    #=======================================================================#
    function &manufactures($mac="")
    {
        include('manufactures.inc.php');
        if(count(explode(":", $mac)) > 1)
        {
            $mac = str_replace(":", "", $mac);
        }
        $man_mac = str_split($mac,6);
        if(isset($manufactures[$man_mac[0]]))
        {
            $manuf = $manufactures[$man_mac[0]];
        }
        else
        {
            $manuf = "Unknown Manufacture";
        }
        return $manuf;
    }

    #===============================================#
    #   import GPX (Import Garmin Based GPX files)  #
    #===============================================#
    function import_gpx($source="" , $user="Unknown" , $notes="No Notes" , $title="UNTITLED" )
    {
        $start = microtime(true);
        $times=date('Y-m-d H:i:s');

        if ($source == NULL){?><h2>You did not submit a file, please <A HREF="javascript:history.go(-1)"> [Go Back]</A> and do so.</h2> <?php die();}

        include('../lib/config.inc.php');

        $apdata  = array();
        $gpdata  = array();
        $signals = array();
        $sats_id = array();

        $fileex  = explode(".", $source);
        $return  = file($source);
        $count = count($return);
        $rettest = substr($return[1], 1, -1);

        if($rettest == 'gpx xmlns="http://www.topografix.com/GPX/1/1" creator="Vistumbler 9.3 Beta 2" version="1.1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd"')
        {
            echo $rettest."<br>";
        }else
        {
            echo '<h1>You need to upload a valid GPX file, go look up the santax for it, or the file that you saved is corrupted</h1>';
        }
    }

    #=======================#
    #   VS1 File import     #
    #=======================#
    function convert_vs1($source='', $out='file')
    {
    if($source == ''){die("cannot supply an empty string for the file source.");}
    //Access point and GPS Data Array
    $apdata=array();
    global $gpsdata;

    $dir = $GLOBALS['wifidb_install'].'/import/up/';
    // dfine time that the script started
    $start = date("H:i:s");
    // counters
    $c=0;
    $cc=0;
    $n=0;
    $nn=0;
    $N=0;
    $complete=0;

    $src=explode("/",$source);
    $f_max = count($src);
    $file_src = explode(".",$src[$f_max-1]);
    $file_type = strtolower($file_src[1]);

    $file_ext = $dir.$file_src[0].'.vs1';
    $filename = $file_ext;
    if($GLOBALS["debug"] == 1 ){echo $file_ext."\n".$filename."\n";}
    // define initial write and appends
    $filewrite = fopen($filename, "w");
    $fileappend = fopen($filename, "a");
    $return = file($source);
    $exp = explode("|", $return[0]);
    if($return[0][0] == "#")
    {
        $ext_e = explode(".", $source);
        $c_ext = count($ext_e)-1;
        $ext_e[$c_ext] = "vs1";
        $source = implode(".", $ext_e);
        return $source;
    }
    if(count($exp) == 17)
    {
        //Break out file into an Array
        $return = file($source);
        //create interval for progress
        $line = count($return);
        $stat_c = $line/97;
        if ($GLOBALS["debug"] ==1){echo $stat_c."\r\n";}
        if ($GLOBALS["debug"] ==1){echo $line."\r\n";}
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
                $datetime=explode(" ",$wifi[13]);
                $date=$datetime[0];
                $time=$datetime[1];

                // This is a temp array of data to be tested against the GPS array
                $gpsdata_t=array(
                                "lat"=> $wifi[8],
                                "long"=> $wifi[9],
                                "sats"=> "0",
                                "hdp"=> '0.0',
                                "alt"=> '0.0',
                                "geo"=> '-0.0',
                                "kmh"=> '0.0',
                                "mph"=> '0.0',
                                "track"=> '0.0',
                                "date"=>$date,
                                "time"=>$time
                                );
                $lat = smart($gpsdata_t['lat']);
                $long = smart($gpsdata_t['long']);
                $time = smart($gpsdata_t['time']);
                $date = smart($gpsdata_t['date']);
                $gps_test = $lat."".$long."".$date."".$time;

                // Create the Security Type number for the respective Access point
                if ($wifi[4]=="Open"&&$wifi[5]=="None"){$sectype="1";}
                if ($wifi[4]=="Open"&&$wifi[5]=="WEP"){$sectype="2";}
                if ($wifi[4]=="WPA-Personal" or $wifi[4] =="WPA2-Personal"){$sectype="3";}

                if ($GLOBALS["debug"] == 1 )
                {
                    echo "\n\n+-+-+-+-+-+-\n".$gpsdata_t["lat"]."+-\n".$gpsdata_t["long"]."+-\n".$gpsdata_t["sats"]."+-\n".$gpsdata_t["date"]."+-\n".$gpsdata_t["time"]."+-\n";
                }

                $gpschk =& database::check_gps_array($gpsdata, $gps_test);
                if ($gpschk[0]==0)
                {
                    if ($GLOBALS["debug"] ==1)
                    {echo "\$n = ".$n."\n\$N = ".$N."\n";}
                    $n++;
                    $N++;
                    $sig=$n.",".$wifi[3];
                    $gpsdata[$n]=array(
                                    "id"=>$n,
                                    "lat"=>$wifi[8],
                                    "long"=>$wifi[9],
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
                }elseif($gpschk===1)
                {
                    if ($GLOBALS["debug"] ==1)
                    {echo "\$n = ".$n."\n\$N = ".$N."\n";}
                    $N++;
                    $sig=$n.",".$wifi[3];
                    if ($GLOBALS["debug"] ==1 ){echo "\nduplicate GPS data, not entered into array\n";}
                    $apdata[$N]=array("ssid"=>$wifi[0],
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
                                                    "sig"=>$sig);
                    if ($GLOBALS["debug"] == 1 )
                    {
                        echo "Access Point Number: ".$N."\n";
                        echo "=-=-=-=-=-=-\n".$apdata[$N]["ssid"]."=-\n".$apdata[$N]["mac"]."=-\n".$apdata[$N]["auth"]."=-\n".$apdata[$N]["encry"]."=-\n".$apdata[$N]["sectype"]."=-\n".$apdata[$N]["radio"]."=-\n".$apdata[$N]["chan"]."=-\n".$apdata[$N]["btx"]."=-\n".$apdata[$N]["otx"]."=-\n".$apdata[$N]["nt"]."=-\n".$apdata[$N]["label"]."=-\n".$apdata[$N]["sig"]."\n";
                    }
                }
            }else
            {
                    echo "\nLine: ".$c." - Wrong data type, dropping row\n";
            }
            if(@$gpsdata_t[0])
            {
                unset($gpsdata_t[0]);
            }
        }
    }elseif($file_type == "db3")
    {
        $sep = '-';
        $dbh = new PDO("sqlite:$source"); // success
        $line = count($dbh->query('SELECT * FROM networks'));

        $stat_c = $line/100;
        if ($GLOBALS["debug"] ==1){echo $stat_c."\n";}
        if ($GLOBALS["debug"] ==1){echo $line."\n";}

        foreach ($dbh->query('SELECT * FROM networks') as $row)
        {
            list($ssid_t, $ssid) = make_ssid($row["ssid"]);
            $mac = strtoupper($row["bssid"]);
            $man = database::manufactures($mac);

            $Found_Capabilies = $row["capabilities"];

            If(stristr($Found_Capabilies, "WPA2-PSK-CCMP") Or stristr($Found_Capabilies, "WPA2-PSK-TKIP+CCMP"))
            {	$Found_AUTH = "WPA2-Personal";
                    $Found_ENCR = "CCMP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-PSK-CCMP") Or stristr($Found_Capabilies, "WPA-PSK-TKIP+CCMP"))
            {	$Found_AUTH = "WPA-Personal";
                    $Found_ENCR = "CCMP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA2-EAP-CCMP") Or stristr($Found_Capabilies, "WPA2-EAP-TKIP+CCMP"))
            {	$Found_AUTH = "WPA2-Enterprise";
                    $Found_ENCR = "CCMP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-EAP-CCMP") Or stristr($Found_Capabilies, "WPA-EAP-TKIP+CCMP"))
            {	$Found_AUTH = "WPA-Enterprise";
                    $Found_ENCR = "CCMP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA2-PSK-TKIP"))
            {	$Found_AUTH = "WPA2-Personal";
                    $Found_ENCR = "TKIP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-PSK-TKIP"))
            {	$Found_AUTH = "WPA-Personal";
                    $Found_ENCR = "TKIP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA2-EAP-TKIP"))
            {	$Found_AUTH = "WPA2-Enterprise";
                    $Found_ENCR = "TKIP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WPA-EAP-TKIP"))
            {	$Found_AUTH = "WPA-Enterprise";
                    $Found_ENCR = "TKIP";
                    $Found_SecType = 3;
            }ElseIf(stristr($Found_Capabilies, "WEP"))
            {	$Found_AUTH = "Open";
                    $Found_ENCR = "WEP";
                    $Found_SecType = 2;
            }Else
            {	$Found_AUTH = "Open";
                    $Found_ENCR = "None";
                    $Found_SecType = 1;
            }
            if(stristr($Found_Capabilies, "IBSS"))
            {
                    $nt = "Ad-Hoc";
            }else
            {
                    $nt = "Infrastructure";
            }
            $authen = $Found_AUTH;
            $encry = $Found_ENCR;
            $sectype = $Found_SecType;
            ###########################
            switch($row["frequency"]+0)
            {
                case 2412:
                        $chan = 1;
                        $radio = '802.11g';
                break;
                case 2417:
                        $chan = 2;
                        $radio = '802.11g';
                break;
                case 2422:
                        $chan = 3;
                        $radio = '802.11g';
                break;
                case 2427:
                        $chan = 4;
                        $radio = '802.11g';
                break;
                case 2432:
                        $chan = 5;
                        $radio = '802.11g';
                break;
                case 2437:
                        $chan = 6;
                        $radio = '802.11g';
                break;
                case 2442:
                        $chan = 7;
                        $radio = '802.11g';
                break;
                case 2447:
                        $chan = 8;
                        $radio = '802.11g';
                break;
                case 2452:
                        $chan = 9;
                        $radio = '802.11g';
                break;
                case 2457:
                        $chan = 10;
                        $radio = '802.11g';
                break;
                case 2462:
                        $chan = 11;
                        $radio = '802.11g';
                break;
                case 2467:
                        $chan = 12;
                        $radio = '802.11g';
                break;
                case 2472:
                        $chan = 13;
                        $radio = '802.11g';
                break;
                case 2484:
                        $chan = 14;
                        $radio = '802.11b';
                break;
                default:
                        $chan = 6;
                        $radio = 'g';
                break;
            }
            $level = (100+$row["level"]);
            $alt = $row["alt"];

            $time = str_split($row["timestamp"], 10);
            $timestamp = date("Y-m-d H:i:s", $time[0]);

            $table = $ssid_t.$sep.$mac.$sep.$sectype.$sep.$radio.$sep.$chan;
    #	if($ssid_t == "yellow"){ die(); }
            //format date and time
            $datetime=explode(" ",$timestamp);
            $date=$datetime[0];
            $time=$datetime[1];
            $lat = $row['lat'];
            $long = $row['lon'];
            echo $table."\r\n$timestamp - - $man\r\n$nt - $authen - $encry - $sectype - $lat - $long\r\n----------\r\n\r\n";
            // This is a temp array of data to be tested against the GPS array
            $gpsdata_t=array(
                            "lat"=>$lat,
                            "long"=>$long,
                            "sats"=>"0",
                            "hdp"=> '0.0',
                            "alt"=> $alt,
                            "geo"=> '-0.0',
                            "kmh"=> '0.0',
                            "mph"=> '0.0',
                            "track"=> '0.0',
                            "date"=>$date,
                            "time"=>$time
                            );
            $lat_t = smart($gpsdata_t['lat']);
            $long_t = smart($gpsdata_t['long']);
            $time_t = smart($gpsdata_t['time']);
            $date_t = smart($gpsdata_t['date']);
            $gps_test = $lat_t."".$long_t."".$date_t."".$time_t;
            if ($GLOBALS["debug"] == 1 )
            {
                    echo "\n\n+-+-+-+-+-+-\n".$gpsdata_t["lat"]."+-\n".$gpsdata_t["long"]."+-\n".$gpsdata_t["sats"]."+-\n".$gpsdata_t["date"]."+-\n".$gpsdata_t["time"]."+-\n";
            }
            $gpschk =& database::check_gps_array($gpsdata, $gps_test);
            if ($gpschk[0]==0)
            {
                if ($GLOBALS["debug"] ==1)
                {echo "\$n = ".$n."\n\$N = ".$N."\n";}
                $n++;
                $N++;
                $sig=$n.",".$level;
                $gpsdata[$n]=array(
                                "id"=>$n,
                                "lat"=>$lat,
                                "long"=>$long,
                                "sats"=>'0',
                                "hdp"=> '0.0',
                                "alt"=> $alt,
                                "geo"=> '-0.0',
                                "kmh"=> '0.0',
                                "mph"=> '0.0',
                                "track"=> '0.0',
                                "date"=>$date,
                                "time"=>$time
                                );
                $apdata[$N]=array(
                                "ssid"=>$ssid,
                                "mac"=>$mac,
                                "man"=>$man,
                                "auth"=>$authen,
                                "encry"=>$encry,
                                "sectype"=>$sectype,
                                "radio"=>$radio,
                                "chan"=>$chan,
                                "btx"=>"0",
                                "otx"=>"0",
                                "nt"=>$nt,
                                "label"=>"Unknown",
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
                if ($GLOBALS["debug"] ==1)
                {echo "\$n = ".$n."\n\$N = ".$N."\n";}
                $N++;
                $sig=$n.",".$level;
                if ($GLOBALS["debug"] ==1 ){echo "\nduplicate GPS data, not entered into array\n";}
                $apdata[$N]=array(
                                "ssid"=>$ssid,
                                "mac"=>$mac,
                                "man"=>$man,
                                "auth"=>$authen,
                                "encry"=>$encry,
                                "sectype"=>$sectype,
                                "radio"=>$radio,
                                "chan"=>$chan,
                                "btx"=>"0",
                                "otx"=>"0",
                                "nt"=>$nt,
                                "label"=>"Unlabeled",
                                "sig"=>$sig
                                );
                if ($GLOBALS["debug"] == 1 )
                {
                    echo "Access Point Number: ".$N."\n";
                    echo "=-=-=-=-=-=-\n".$apdata[$N]["ssid"]."=-\n".$apdata[$N]["mac"]."=-\n".$apdata[$N]["auth"]."=-\n".$apdata[$N]["encry"]."=-\n".$apdata[$N]["sectype"]."=-\n".$apdata[$N]["radio"]."=-\n".$apdata[$N]["chan"]."=-\n".$apdata[$N]["btx"]."=-\n".$apdata[$N]["otx"]."=-\n".$apdata[$N]["nt"]."=-\n".$apdata[$N]["label"]."=-\n".$apdata[$N]["sig"]."\n";
                }
            }
            unset($gpsdata_t[0]);
        }
    }
    #############################
    # Now write the VS1 file
    #############################
    if ($out == "file" or $out == "File" or $out=="FILE")
    {
        $n = 1;
        # Dump GPS data to VS1 File
        $h1 = "# Vistumbler VS1 - Detailed Export Version 3.0\r\n# Created By: RanInt WiFi DB Alpha \r\n# -------------------------------------------------\r\n# GpsID|Latitude|Longitude|NumOfSatalites|Date|Time\r\n# -------------------------------------------------\r\n";
        fwrite($fileappend, $h1);
        foreach( $gpsdata as $gps )
        {
        //	GPS Convertion  if needed, check for ddmm.mmmm and leave it alone, otherwise i am guessing its DD.mmmmm and that needs to be converted to ddmm.mmmm:
            $lat_epx = explode(" ", $gps['lat']);
            if(strlen($lat_exp[0])>3)
            {
                $lat  =& $gps['lat'];
                $long =& $gps['long'];
            }else
            {
                $lat  =& database::convert_dd_dm($gps['lat']);
                $long =& database::convert_dd_dm($gps['long']);
            }
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
    //	END GPS convert

            if ($GLOBALS["debug"] ==1 ){echo "Lat : ".$gps['lat']." - Long : ".$gps['long']."\n";}
            $gpsd = $n."|".$lat."|".$long."|".$gps["sats"]."|".$gps["hdp"]."|".$gps["alt"]."|".$gps["geo"]."|".$gps["kmh"]."|".$gps["mph"]."|".$gps["track"]."|".$gps["date"]."|".$gps["time"]."\r\n";
            if($GLOBALS["debug"] == 1){ echo $gpsd;}
            fwrite($fileappend, $gpsd);
            $n++;
        }
        $n=1;

        $ap_head = "# ---------------------------------------------------------------------------------------------------------------------------------------------------------\r\n# SSID|BSSID|MANUFACTURER|Authetication|Encryption|Security Type|Radio Type|Channel|Basic Transfer Rates|Other Transfer Rates|Network Type|Label|GpsID,SIGNAL\r\n# ---------------------------------------------------------------------------------------------------------------------------------------------------------\r\n";
        fwrite($fileappend, $ap_head);
        foreach($apdata as $ap)
        {
            $apd = $ap["ssid"]."|".$ap["mac"]."|".$ap["man"]."|".$ap["auth"]."|".$ap["encry"]."|".$ap["sectype"]."|".$ap["radio"]."|".$ap["chan"]."|".$ap["btx"]."|".$ap["otx"]."|".$ap["nt"]."|".$ap["label"]."|".$ap["sig"]."\r\n";
            if($GLOBALS["debug"] == 1){echo $apd;}
            fwrite($fileappend, $apd);
            $n++;

        }
        $end = date("H:i:s");
        $GPSS=count($gpsdata);
        $APS=count($apdata);
        echo "\n\n------------------------------\nTotal Number of Access Points : ".$APS."\nTotal Number of GPS Points : ".$GPSS."\n------------------------------\nDONE!\nStart Time : ".$start."\nStop Time : ".$end."\n-------";
        return $filename;
    }
    }

    #===============================================#
    #GPS check, make sure there are no duplicates   #
    #===============================================#
    function &check_gps_array($gpsarray, $test, $table='')
    {
        $start = microtime(true);
        include('config.inc.php');
        $conn1 = $GLOBALS['conn'];
        $db_st = $GLOBALS['db_st'];

        $count = count($gpsarray);
        if($count !=0)
        {
        foreach($gpsarray as $gps)
        {
            $id = $gps['id'];
            $lat = smart($gps['lat']);
            $long = smart($gps['long']);
            $time = smart($gps['time']);
            $date = smart($gps['date']);
            $gps_t 	= $lat."".$long."".$date."".$time;
            $gps_t = $gps_t;
            $test = $test;
        #	echo $gps_t."  ===  ".$test."\r\n";
            if ($gps_t===$test)
            {
                if ($GLOBALS["debug"]  == 1 )
                {
                    echo  "  SAME<br>";
                    echo  "  Array data: ".$gps_t."<br>";
                    echo  "  Testing data: ".$test."<br>.-.-.-.-.=.-.-.-.-.<br>";
                    echo  "-----=-----=-----<br>|<br>|<br>";
                }

                $lat_a = $gps['lat'];
                $long_a = $gps['long'];
                $time_a = $gps['time'];
                $date_a = $gps['date'];

                if($table != '')
                {
                    $sql11 = "SELECT * FROM `$db_st`.`$table` WHERE `lat` like '$lat_a' AND `long` like '$long_a' AND `date` like '$date_a' AND `time` like '$time_a' LIMIT 1";
                    $gpresult = mysql_query($sql11, $conn1);
                    $gpsdbarray = mysql_fetch_array($gpresult);

                    $id_ = $gpsdbarray['id'];
        #						echo $sql11."\n".$id_."\n";

                    $return = array(0=>1,1=>$id_);
                    return $return;
                    break;
                }else
                {
                    $return = array(0=>1,1=>0);
                    return $return;
                    break;
                }
            }else
            {
                    if ($GLOBALS["debug"]  == 1){
                            echo  "  NOT SAME<br>";
                            echo  "  Array data: ".$gps_t."<br>";
                            echo  "  Testing data: ".$test."<br>----<br>";
                            echo  "-----=-----<br>";
                    }
                    $return = array(0=>0,1=>0);
            }
        }
        }else
        {
        $return = array(0=>0,1=>0);
        }
        $end = microtime(true);
        if ($GLOBALS["bench"]  == 1)
        {
        #echo "Time is [Unix Epoc]<BR>";
        #echo "Start Time: ".$start."<BR>";
        #echo "  End Time: ".$end."<BR>";
        }
        return $return;
        }

    #=======================#
    #   AP History Fetch    #
    #=======================#
    function apfetch($id=0)
    {
    $apID = $id;
    $start = microtime(true);
    include('../lib/config.inc.php');
    $sqls = "SELECT * FROM `$db`.`$wtable` WHERE id='$id'";
    $result = mysql_query($sqls, $conn) or die(mysql_error($conn));
    $newArray = mysql_fetch_array($result);
    $ID = $newArray['id'];
    $tablerowid = 0;
    $macaddress = $newArray['mac'];
    $manuf = database::manufactures($macaddress);
    $mac = str_split($macaddress,2);
    $mac_full = $mac[0].":".$mac[1].":".$mac[2].":".$mac[3].":".$mac[4].":".$mac[5];
    $radio = $newArray['radio'];
    if($radio == "a")
        {$radio = "802.11a";}
    elseif($radio == "b")
        {$radio = "802.11b";}
    elseif($radio == "g")
        {$radio = "802.11g";}
    elseif($radio == "n")
        {$radio = "802.11n";}
    else
        {$radio = "802.11u";}
    list($ssid_ptb) = make_ssid($newArray["ssid"]);
    $table		=	$ssid_ptb.'-'.$newArray["mac"].'-'.$newArray["sectype"].'-'.$newArray["radio"].'-'.$newArray['chan'];
    $table_gps	=	$table.$gps_ext;
    $sql_gps = "select * from `$db_st`.`$table_gps` where `lat` NOT LIKE 'N 0.0000' limit 1";
    $resultgps = mysql_query($sql_gps, $conn);
    $lastgps = @mysql_fetch_array($resultgps);
    $lat_check = explode(" ", $lastgps['lat']);
    $lat_c = $lat_check[1]+0;
    if($lat_c != "0"){$gps_yes = 1;}else{$gps_yes = 0;}
    ?>
    <SCRIPT LANGUAGE="JavaScript">
    // Row Hide function.
    // by tcadieux
    function expandcontract(tbodyid,ClickIcon)
    {
        if (document.getElementById(ClickIcon).innerHTML == "+")
        {
            document.getElementById(tbodyid).style.display = "";
            document.getElementById(ClickIcon).innerHTML = "-";
        }else{
            document.getElementById(tbodyid).style.display = "none";
            document.getElementById(ClickIcon).innerHTML = "+";
        }
    }
    </SCRIPT>
    <h1><?php echo htmlentities($newArray['ssid'], ENT_QUOTES);
    if($gps_yes)
    {
    echo '<img width="20px" src="../img/globe_on.png">';
    }
    else
    {
    echo '<img width="20px" src="../img/globe_off.png">';
    }
    ?></h1>
    <TABLE align=center WIDTH=569 BORDER=1 CELLPADDING=4 CELLSPACING=0>
    <TABLE align=center WIDTH=569 BORDER=1 CELLPADDING=4 CELLSPACING=0>
    <COL WIDTH=112><COL WIDTH=439>
    <TR><TD class="style4" WIDTH=112><P>MAC Address</P></TD><TD class="light" WIDTH=439><P><?php echo $mac_full;?></P></TD></TR>
    <TR VALIGN=TOP><TD class="style4" WIDTH=112><P>Manufacture</P></TD><TD class="light" WIDTH=439><P><?php echo $manuf;?></P></TD></TR>
    <TR VALIGN=TOP><TD class="style4" WIDTH=112 HEIGHT=26><P>Authentication</P></TD><TD class="light" WIDTH=439><P><?php echo $newArray['auth'];?></P></TD></TR>
    <TR VALIGN=TOP><TD class="style4" WIDTH=112><P>Encryption Type</P></TD><TD class="light" WIDTH=439><P><?php echo $newArray['encry'];?></P></TD></TR>
    <TR VALIGN=TOP><TD class="style4" WIDTH=112><P>Radio Type</P></TD><TD class="light" WIDTH=439><P><?php echo $radio;?></P></TD></TR>
    <TR VALIGN=TOP><TD class="style4" WIDTH=112><P>Channel #</P></TD><TD class="light" WIDTH=439><P><?php echo $newArray['chan'];?></P></TD></TR>
    <tr class="style4">
        <td colspan="2" align="center" >
                <a class="links" href="../opt/export.php?func=exp_single_ap&row=<?php echo $ID;?>">Export this AP to KML</a>
        </td>
    </tr>
    </TABLE>
    <br>
    <TABLE align=center  WIDTH=85% BORDER=1 CELLPADDING=4 CELLSPACING=0 id="gps">
    <tr class="style4"><th colspan="10">Signal History</th></tr>
    <tr class="style4"><th>Row</th><th>Btx</th><th>Otx</th><th>First Active</th><th>Last Update</th><th>Network Type</th><th>Label</th><th>User</th><th>Signal</th><th>Plot</th></tr>
    <?php
    $start1 = microtime(true);
    $result = mysql_query("SELECT * FROM `$db_st`.`$table` ORDER BY `id`", $conn) or die(mysql_error($conn));
    $flip = 0;
    while ($field = mysql_fetch_array($result))
    {
    if($flip){$class="light";$flip=0;}else{$class="dark";$flip=1;}
    $row = $field["id"];
    $row_id = $row.','.$ID;
    $sig_exp = explode("-", $field["sig"]);
    $sig_size = count($sig_exp)-1;

    $first_ID = explode(",",$sig_exp[0]);
    $first = $first_ID[0];
    if($first == 0)
    {
            $first_ID = explode(",",$sig_exp[1]);
            $first = $first_ID[0];
    }

    $last_ID = explode(",",$sig_exp[$sig_size]);
    $last = $last_ID[0];
    if($last == 0)
    {
            $last_ID = explode(",",$sig_exp[$sig_size-1]);
            $last = $last_ID[0];
    }

    $sql1 = "SELECT * FROM `$db_st`.`$table_gps` WHERE `id`='$first'";
    $re = mysql_query($sql1, $conn) or die(mysql_error($conn));
    $gps_table_first = mysql_fetch_array($re);

    $date_first = $gps_table_first["date"];
    $time_first = $gps_table_first["time"];
    $fa = $date_first." ".$time_first;

    $sql2 = "SELECT * FROM `$db_st`.`$table_gps` WHERE `id`='$last'";
    $res = mysql_query($sql2, $conn) or die(mysql_error($conn));
    $gps_table_last = mysql_fetch_array($res);
    $date_last = $gps_table_last["date"];
    $time_last = $gps_table_last["time"];
    $lu = $date_last." ".$time_last;
    ?>
            <tr class="<?php echo $class; ?>"><td align="center"><?php echo $row; ?></td><td>
            <?php echo $field["btx"]; ?></td><td>
            <?php echo $field["otx"]; ?></td><td>
            <?php echo $fa; ?></td><td>
            <?php echo $lu; ?></td><td>
            <?php echo $field["nt"]; ?></td><td>
            <?php echo $field["label"]; ?></td><td>
            <a class="links" href="../opt/userstats.php?func=alluserlists&user=<?php echo $field["user"]; ?>"><?php echo $field["user"]; ?></a></td><td>
            <a class="links" href="../graph/?row=<?php echo $row; ?>&id=<?php echo $ID; ?>">Graph Signal</a></td><td><a class="links" href="export.php?func=exp_all_signal&row=<?php echo $row_id;?>">KML</a>
            </td></tr>
            <tr><td colspan="10" align="center">

            <table  align=center WIDTH=569 BORDER=1 CELLPADDING=4 CELLSPACING=0>
            <tr>
                <td class="style4" onclick="expandcontract('Row<?php echo $tablerowid;?>','ClickIcon<?php echo $tablerowid;?>')" id="ClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">+</td>
            <th colspan="6" class="style4">GPS History</th></tr>
            <tbody id="Row<?php echo $tablerowid;?>" style="display:none">
            <tr class="style4"><th>Row</th><th>Lat</th><th>Long</th><th>Sats</th><th>Date</th><th>Time</th></tr>
            <?php
            $signals = explode('-',$field['sig']);
            $flip_1 = 0;
            foreach($signals as $signal)
            {
                $sig_exp = explode(',',$signal);
                $id = $sig_exp[0]+0;
                if($id == 0){continue;}
                $start2 = microtime(true);
                $result1 = mysql_query("SELECT * FROM `$db_st`.`$table_gps` WHERE `id` = '$id'", $conn) or die(mysql_error($conn));
        #	$rows = mysql_num_rows($result1);
                if($flip_1){$class="light";$flip_1=0;}else{$class="dark";$flip_1=1;}
                while ($field = mysql_fetch_array($result1))
                {
                    ?>
                    <tr class="<?php echo $class; ?>"><td align="center">
                    <?php echo $field["id"]; ?></td><td>
                    <?php echo $field["lat"]; ?></td><td>
                    <?php echo $field["long"]; ?></td><td align="center">
                    <?php echo $field["sats"]; ?></td><td>
                    <?php echo $field["date"]; ?></td><td>
                    <?php echo $field["time"]; ?></td></tr>
                    <?php
                }
                $end2 = microtime(true);
            }
            ?>
            <tr class="style4"><td onclick="expandcontract('Row<?php echo $tablerowid;?>','ClickIcon<?php echo $tablerowid;?>')" id="ClickIcon<?php echo $tablerowid;?>" style="cursor: pointer; cursor: hand;">-</td><td colspan="5"></td></tr>
            </table>

            </td></tr>
            <?php
            $tablerowid++;
    }
    $end1 = microtime(true);
    ?>
    </table>
    <br>
    <TABLE align=center WIDTH=569 BORDER=1 CELLPADDING=4 CELLSPACING=0 >
    <?php
    #END GPSFETCH FUNC
    ?>
    <tr class="style4"><th colspan="6">Associated Lists</th></tr>
    <tr class="style4"><th>New/Update</th><th>ID</th><th>User</th><th>Title</th><th>Total APs</th><th>Date</th></tr>
    <?php
    $start3 = microtime(true);
    $result = mysql_query("SELECT * FROM `$db`.`$users_t`", $conn);
    while ($field = mysql_fetch_array($result))
    {
    if($field['points'] != '')
    {
        $APS = explode("-" , $field['points']);
        foreach ($APS as $AP)
        {
            if($AP == ''){continue;}
            $access = explode(",", $AP);
            $New_or_Update = $access[0];
            $access1 = explode(":",$access[1]);
            $user_list_id = $access1[0];
            if ( $apID  ==  $user_list_id )
            {
                $list[]=$field['id'].",".$New_or_Update;
            }
        }
    }
    }
    if(isset($list))
    {
    $flip_2 = 0;
    foreach($list as $aplist)
    {
        $exp = explode(",",$aplist);
        $apid = $exp[0];
        $new_update = $exp[1];
        $result = mysql_query("SELECT * FROM `$db`.`$users_t` WHERE `id`='$apid'", $conn);
        while ($field = mysql_fetch_array($result))
        {
            if($flip_2){$class="light";$flip_2=0;}else{$class="dark";$flip_2=1;}
            if($field["title"]==''){$field["title"]="Untitled";}
            $points = explode('-' , $field['points']);
            $total = count($points);
            ?>
            <tr class="<?php echo $class;?>">
                <td><?php if($new_update == 1){echo "Update";}else{echo "New";}?></td>
                <td align="center"><a class="links" href="userstats.php?func=useraplist&row=<?php echo $field["id"];?>"><?php echo $field["id"];?></a></td>
                <td><a class="links" href="userstats.php?func=alluserlists&user=<?php echo $field["username"];?>"><?php echo $field["username"];?></a></td>
                <td><a class="links" href="userstats.php?func=useraplist&row=<?php echo $field["id"];?>"><?php echo $field["title"];?></a></td>
                <td align="center"><?php echo $total;?></td><td><?php echo $field['date'];?></td>
            </tr>
            <?php
        }
    }
    }else
    {
    ?>
    <td colspan="5" align="center">There are no Other Lists with this AP in it.</td></tr>
    <?php

    }
    $end3 = microtime(true);
    mysql_close($conn);
    ?>
    </TABLE>
    <br/>
    <?php
    $end = microtime(true);
    if ($GLOBALS["bench"]  == 1)
    {
        echo "Time is [Unix Epoc]<BR>";
        echo "Total Start Time: ".$start."<BR>";
        echo "Total  End Time: ".$end."<BR>";
        echo "Start Time 1: ".$start1."<BR>";
        echo "  End Time 1: ".$end1."<BR>";
        echo "Start Time 2: ".$start2."<BR>";
        echo "  End Time 2: ".$end2."<BR>";
        echo "Start Time 3: ".$start3."<BR>";
        echo "  End Time 3: ".$end3."<BR>";
    }
#END IMPORT LISTS FETCH FUNC
}

    #===================================#
    #   Grab the stats for All Users    #
    #===================================#
    function all_users()
    {
    $start = microtime(true);
    include('config.inc.php');
    $users = array();
    $userarray = array();
    ?>
    <SCRIPT LANGUAGE="JavaScript">
    // Row Hide function.
    // by tcadieux
    function expandcontract(tbodyid,ClickIcon)
    {
        if (document.getElementById(ClickIcon).innerHTML == "+")
        {
            document.getElementById(tbodyid).style.display = "";
            document.getElementById(ClickIcon).innerHTML = "-";
        }else{
            document.getElementById(tbodyid).style.display = "none";
            document.getElementById(ClickIcon).innerHTML = "+";
        }
    }
    </SCRIPT>
    <h1>Stats For: All Users</h1>
    <table border="1" align="center">
    <tr class="style4">
    <th>ID</th><th>UserName</th><th>Title</th><th>Import Notes</th><th>Number of APs</th><th>Imported On</th></tr>
    <tr>
    <?php

    $sql = "SELECT * FROM `$db`.`$users_t` ORDER BY username ASC";
    $result = mysql_query($sql, $conn) or die(mysql_error($conn));
    $num = mysql_num_rows($result);
    if($num == 0)
    {
    echo '<tr><td colspan="6" align="center">There no Users, Import something.</td></tr></table>';
    $filename = $_SERVER['SCRIPT_FILENAME'];
    footer($filename);
    die();
    }
    while ($user_array = mysql_fetch_array($result))
    {
        $users[]=$user_array["username"];
    }
    $users = array_unique($users);
    $pre_user = "";
    $n=0;
    $row_color = 0;
    foreach($users as $user)
    {
        if($row_color == 1)
        {$row_color = 0; $color = "light";}
        else{$row_color = 1; $color = "dark";}
        $tablerowid = 0;
        $sql = "SELECT * FROM `$db`.`$users_t` WHERE `username`='$user'";
        $result = mysql_query($sql, $conn) or die(mysql_error($conn));
        while ($user_array = mysql_fetch_array($result))
        {
            $tablerowid++;
            $id = $user_array['id'];
            $username = $user_array['username'];
            if($pre_user === $username or $pre_user === ""){$n++;}else{$n=0;}
            if ($user_array['title'] === "" or $user_array['title'] === " "){ $user_array['title']="UNTITLED";}
            if ($user_array['date'] === ""){ $user_array['date']="No date, hmm..";}
            $search = array('\n','\r','\n\r');
            $user_array['notes'] = str_replace($search,"", $user_array['notes']);
            if ($user_array['notes'] == ""){ $user_array['notes']="No Notes, hmm..";}
            $notes = $user_array['notes'];
            $points = explode("-",$user_array['points']);
            $pc = count($points);
            if($user_array['points'] === ""){continue;}
            if($pre_user !== $username)
            {
                ?>
                <tr >
                    <td class="<?php echo $color;?>"><?php echo $user_array['id'];?></td>
                    <td class="<?php echo $color;?>"><a class="links" href="userstats.php?func=alluserlists&user=<?php echo $username;?>"><?php echo $username;?></a></td>
                    <td class="<?php echo $color;?>"><a class="links" href="userstats.php?func=useraplist&row=<?php echo $user_array["id"];?>"><?php echo $user_array['title'];?></a></td>
                    <td class="<?php echo $color;?>"><?php echo wordwrap($notes, 56, "<br />\n"); ?></td>
                    <td class="<?php echo $color;?>"><?php echo $pc;?></td>
                    <td class="<?php echo $color;?>"><?php echo $user_array['date'];?></td>
                </tr>
                <?php
            }else
            {
                ?>
                <tr>
                    <td></td>
                    <td></td>
                    <td class="<?php echo $color;?>"><a class="links" href="userstats.php?func=useraplist&row=<?php echo $user_array["id"];?>"><?php echo $user_array['title'];?></a></td>
                    <td class="<?php echo $color;?>"><?php echo wordwrap($notes, 56, "<br />\n"); ?></td>
                    <td class="<?php echo $color;?>"><?php echo $pc;?></td>
                    <td class="<?php echo $color;?>"><?php echo $user_array['date'];?></td>
                </tr>
                <?php
            }
            $pre_user = $username;
        }
        ?>
        <tr>
            <td></td>
        </tr>
        <?php
    }

    ?>
    </tr>
    </table>
    <br>
    <?php
    $end = microtime(true);
    if ($GLOBALS["bench"]  == 1)
    {
    echo "Time is [Unix Epoc]<BR>";
    echo "Start Time: ".$start."<BR>";
    echo "  End Time: ".$end."<BR>";
    }
    }

    #=======================================#
    #   Grab All the AP's for a given user  #
    #=======================================#
    function all_users_ap($user="")
    {
    $start = microtime(true);
    include('config.inc.php');
    $sql = "SELECT * FROM `$db`.`$users_t` WHERE `username`='$user'";
    $re = mysql_query($sql, $conn) or die(mysql_error($conn));
    while($user_array = mysql_fetch_array($re))
    {
        if($user_array["points"] != '')
        {
            $explode = explode("-",$user_array["points"]);
            foreach($explode as $explo)
            {
                $exp = explode(",",$explo);
                $flag = $exp[0];
                $ap_exp = explode(":",$exp[1]);
                $aps[] = array(
                                "flag"=>$flag,
                                "apid"=>$ap_exp[0],
                                "row"=>$ap_exp[1]
                                );
            }
        }
    }

    $sql = "SELECT * FROM `$db`.`$users_t` WHERE `username` LIKE '$user'";
    $other_imports = mysql_query($sql, $conn) or die(mysql_error($conn));
    while($imports = mysql_fetch_array($other_imports))
    {
        if($imports['points'] == ""){continue;}
        $points = explode("-",$imports['points']);
        foreach($points as $key=>$pt)
        {
            $pt_ex = explode(",", $pt);
            if($pt_ex[0] == 1)
            {
                unset($points[$key]);
            }
        }
        $pts_count = count($points);
        $total_aps[] = $pts_count;
    }
    $total = 0;
    foreach($total_aps as $totals)
    {
        $total += $totals;
    }
    ?>
    <table>
    <tr><td>
            <table border="1" align="center" width="100%">
                    <tr class="style4">
                            <th colspan='2'>Access Points For: <a class="links" href ="../opt/userstats.php?func=alluserlists&user=<?php echo $user;?>"><?php echo $user;?></a>
                    </tr>
                    <tr class="sub_head">
                            <td><b>Total Access Points...</b></td><td><?php echo $total;?></td>
                    </tr>
                    <tr class="sub_head">
                            <td><b>Export This list To...</b></td><td><a class="links" href="../opt/export.php?func=exp_user_all_kml&user=<?php echo $user;?>">KML</a></td>
                    </tr>
            </table>
            <br>
            <table border="1" align="center">
                    <tr class="style4">
                            <th>AP ID</th><th>Row</th><th>SSID</th><th>Mac Address</th><th>Authentication</th><th>Encryption</th><th>Radio</th><th>Channel</th>
                    </tr>
            <?php
            $flip = 0;
            foreach($aps as $ap)
            {
                if($ap['flag'] == "1"){continue;}
                if($flip){$style = "dark";$flip=0;}else{$style="light";$flip=1;}
                $apid = $ap['apid'];
                $row = $ap['row'];

                $sql = "SELECT * FROM `$db`.`$wtable` WHERE `ID`='$apid'";
                $res = mysql_query($sql, $conn) or die(mysql_error($conn));
                $ap_array = mysql_fetch_array($res);

                $ssid = $ap_array['ssid'];
                $mac = $ap_array['mac'];
                $chan = $ap_array['chan'];
                $radio = $ap_array['radio'];
                $auth = $ap_array['auth'];
                $encry = $ap_array['encry'];
                if($radio=="a")
                {$radio="802.11a";}
                elseif($radio=="b")
                {$radio="802.11b";}
                elseif($radio=="g")
                {$radio="802.11g";}
                elseif($radio=="n")
                {$radio="802.11n";}
                else
                {$radio="Unknown Radio";}
                ?>
                <tr class="<?php echo $style;?>">
                    <td align="center">
                <?php
                echo $apid;
                ?>
                    </td>
                    <td align="center">
                <?php
                echo $row;
                ?>
                    </td>
                    <td align="center">
                        <a class="links" href="fetch.php?id=<?php echo $apid;?>"><?php echo $ssid;?></a>
                    </td>
                    <td>
                        <?php echo $mac;?>
                    </td>
                    <td align="center">
                        <?php echo $auth;?>
                    </td>
                    <td align="center">
                        <?php echo $encry;?>
                    </td>
                    <td align="center">
                        <?php echo $radio;?>
                    </td>
                    <td align="center">
                        <?php echo $chan;?>
                    </td>
                </tr>
                <?php
            }
            ?>
            </table>
    </td></tr></table>
    <br>
    <?php
    $end = microtime(true);
    if ($GLOBALS["bench"]  == 1)
    {
        echo "Time is [Unix Epoc]<BR>";
        echo "Start Time: ".$start."<BR>";
        echo "  End Time: ".$end."<BR>";
    }
    }

    #===================================#
    #   Grab all user Import lists      #
    #===================================#
    function users_lists($username="")
    {
    $start = microtime(true);
    include('config.inc.php');
    $sql = "SELECT * FROM `$db`.`$users_t` WHERE `username` LIKE '$username' ORDER BY `id` DESC LIMIT 1";
    $user_query = mysql_query($sql, $conn) or die(mysql_error($conn));
    $user_last = mysql_fetch_array($user_query);
    $last_import_id = $user_last['id'];
    $user_aps = $user_last['aps'];
    $user_gps = $user_last['gps'];
    $last_import_title = $user_last['title'];
    $last_import_date = $user_last['date'];

    $sql = "SELECT * FROM `$db`.`$users_t` WHERE `username` LIKE '$username' ORDER BY `id` ASC LIMIT 1";
    $user_query = mysql_query($sql, $conn) or die(mysql_error($conn));
    $user_first = mysql_fetch_array($user_query);
    $user_ID = $user_first['id'];
    $first_import_date = $user_first['date'];

    $sql = "SELECT * FROM `$db`.`$users_t` WHERE `username` LIKE '$username'";
    $other_imports = mysql_query($sql, $conn) or die(mysql_error($conn));
    while($imports = mysql_fetch_array($other_imports))
    {
        if($imports['points'] == ""){continue;}
        $points = explode("-",$imports['points']);
        foreach($points as $key=>$pt)
        {
                $pt_ex = explode(",", $pt);
                if($pt_ex[0] == 1)
                {
                        unset($points[$key]);
                }
        }
        $pts_count = count($points);
        $total_aps[] = $pts_count;
    }
    $total = 0;
    if(count(@$total_aps))
    {
        foreach($total_aps as $totals)
        {
            $total += $totals;
        }
        ?>
        <table width="90%" border="1" align="center">
        <tr class="style4">
            <th colspan="4">Stats for : <?php echo $username;?></th>
        </tr>
        <tr class="sub_head">
            <th>ID</th><th>Total APs</th><th>First Import</th><th>Last Import</th>
        </tr>
        <tr class="dark">
            <td><?php echo $user_ID;?></td><td><a class="links" href="../opt/userstats.php?func=allap&user=<?php echo $username?>"><?php echo $total;?></a></td><td><?php echo $first_import_date;?></td><td><?php echo $last_import_date;?></td>
        </tr>
        </table>
        <br>

        <table width="90%" border="1" align="center">
        <tr class="style4">
            <th colspan="4">Last Import Details</th>
        </tr>
        <tr class="sub_head">
            <th>ID</th><th colspan="3">Title</th>
        </tr>
        <tr class="dark">
            <td align="center"><?php echo $last_import_id;?></td><td colspan="4" align="center"><a class="links" href="../opt/userstats.php?func=useraplist&row=<?php echo $last_import_id;?>"><?php echo $last_import_title;?></a></td>
        </tr>
        <tr class="sub_head">
            <th colspan="2">Date</th><th>Total APs</th><th>Total GPS</th>
        </tr>
        <tr class="dark">
            <td colspan="2" align="center"><?php echo $last_import_date;?></td><td align="center"><?php echo $user_aps; ?></td><td align="center"><?php echo $user_gps;?></td>
        </tr>
        </table>
        <br>

        <table width="90%" border="1" align="center">
        <tr class="style4">
            <th colspan="4">All Previous Imports</th>
        </tr>
        <tr class="sub_head">
            <th>ID</th><th>Title</th><th>Total APs</th><th>Date</th>
        </tr>

        <?php
        $sql = "SELECT * FROM `$db`.`$users_t` WHERE `username` LIKE '$username' AND `id` != '$last_import_id' ORDER BY `id` DESC";
        $other_imports = mysql_query($sql, $conn) or die(mysql_error($conn));
        $other_rows = mysql_num_rows($other_imports);
        if(@$others_rows != "0")
        {
            $flip = 0;
            while($imports = mysql_fetch_array($other_imports))
            {
                if($imports['points'] == ""){continue;}
                if($flip){$style = "dark";$flip=0;}else{$style="light";$flip=1;}
                $import_id = $imports['id'];
                $import_title = $imports['title'];
                $import_date = $imports['date'];
                $import_ap = $imports['aps'];
                ?>
                <tr class="<?php echo $style; ?>"><td><?php echo $import_id;?></td><td><a class="links" href="../opt/userstats.php?func=useraplist&row=<?php echo $import_id;?>"><?php echo $import_title;?></a></td><td><?php echo $import_ap;?></td><td><?php echo $import_date;?></td></tr>
                <?php
            }
        }else
        {
            ?>
                <tr class="light"><td colspan="4" align="center">There are no other Imports. Go get some.</td></tr>
            <?php
        }
        ?>
        </table>
        <?php
    }else
    {
    ?>
    <table width="90%" border="1" align="center">
            <tr class="light"><td colspan="4" align="center">There are no Imports for this user. Make them go get some.</td></tr>
    </table>
    <?php
    }
    }

    #===============================================#
    #   Grab the AP's for a given user's Import     #
    #===============================================#
    function user_ap_list($row=0)
    {
        $start = microtime(true);
        include('config.inc.php');
        $pagerow = 0;
        $sql = "SELECT * FROM `$db`.`$users_t` WHERE `id`='$row'";
        $result = mysql_query($sql, $conn) or die(mysql_error($conn));
        $user_array = mysql_fetch_array($result);
        $aps=explode("-",$user_array["points"]);
        $title = $user_array["title"];
        ?>
    <table><tr><td align="center">
        <table width="100%" border="1" align="center">
            <tr class="style4">
                <th colspan="2">
                    Access Points For: <a class="links" href ="../opt/userstats.php?func=alluserlists&user=<?php echo $user_array["username"]; ?>"><?php echo $user_array["username"]; ?></a>
                </th>
            </tr>
            <tr class="sub_head">
                <td><i><b>Title</b></i></td><td><b><?php echo $title; ?></b></td>
            </tr>
            <tr class="sub_head">
                <td><i><b>Imported On</b></i></td><td><b><?php echo $user_array["date"]; ?></b></td>
            </tr>
            <tr class="sub_head">
                <td><i><b>Total APs in List</b></i></td><td><b><?php echo $user_array["aps"]; ?></b></td>
            </tr>
            <tr class="sub_head">
                <td colspan="2"><CENTER><i><b>Notes</b></i></CENTER></td>
            </tr>
            <tr class="light">
                <td colspan="2"><CENTER><b><?php echo $user_array["notes"]; ?></b></CENTER></td>
            </tr>
            <tr class="dark">
                <td colspan="2"><CENTER><b><a href="export.php?func=exp_user_list&row=<?php echo $user_array["id"]; ?>">Export List</a></b></CENTER></td>
            </tr>
        <table>
        <br>
        <table border="1" align="center">
            <tr class="style4">
                <th>New/Update</th><th>AP ID</th><th>Row</th><th>SSID</th><th>Mac Address</th><th>Authentication</th><th>Encryption</th><th>Radio</th><th>Channel</th>
            </tr>
        <?php
        $flip = 0;
        foreach($aps as $ap)
        {
            #$pagerow++;
            $ap_exp = explode("," , $ap);
            if($ap_exp[0]==0){$flag = "N";}else{$flag = "U";}
            if($flip){$style = "dark";$flip=0;}else{$style="light";$flip=1;}
            $ap_and_row = explode(":",$ap_exp[1]);
            $apid = $ap_and_row[0];
            $row = $ap_and_row[1];
            $sql = "SELECT * FROM `$db`.`$wtable` WHERE `ID`='$apid'";
            $result = mysql_query($sql, $conn) or die(mysql_error($conn));
            while ($ap_array = mysql_fetch_array($result))
            {
                $ssid = $ap_array['ssid'];
                $mac = $ap_array['mac'];
                $chan = $ap_array['chan'];
                $radio = $ap_array['radio'];
                $auth = $ap_array['auth'];
                $encry = $ap_array['encry'];
                ?>
                <tr class="<?php echo $style;?>">
                <td align="center"><?php echo $flag;?></td>
                <td align="center"><?php echo $apid;?></td>
                <td align="center"><?php echo $row;?></td>
                <td align="center"><a class="links" href="fetch.php?id=<?php echo $apid; ?>"><?php echo $ssid; ?></a></td>
                <td align="center"><?php echo $mac;?></td>
                <td align="center"><?php echo $auth;?></td>
                <?php
                if($radio=="a")
                {$radio="802.11a";}
                elseif($radio=="b")
                {$radio="802.11b";}
                elseif($radio=="g")
                {$radio="802.11g";}
                elseif($radio=="n")
                {$radio="802.11n";}
                else
                {$radio="Unknown Radio";}
                ?>
                <td align="center"><?php echo $encry;?></td>
                <td align="center"><?php echo $radio;?></td>
                <td align="center"><?php echo $chan;?></td></tr>
            <?php
            }
        }
        ?>
    </table>
    </td></tr></table>
    <?php
        $end = microtime(true);
        if ($GLOBALS["bench"]  == 1)
        {
            echo "Time is [Unix Epoc]<BR>";
            echo "Start Time: ".$start."<BR>";
            echo "  End Time: ".$end."<BR>";
        }
    }

    #======================#
    #   DUMP VAR TO HTML   #
    #======================#
    function dump($value="" , $level=0)
    {
        if ($level==-1)
        {
            $trans[' ']='&there4;';
            $trans["\t"]='&rArr;';
            $trans["\n"]='&para;;';
            $trans["\r"]='&lArr;';
            $trans["\0"]='&oplus;';
            return strtr(htmlspecialchars($value),$trans);
        }
        if ($level==0) echo '<pre>';
        $type= gettype($value);
        echo $type;
        if ($type=='string')
        {
            echo '('.strlen($value).')';
            $value= dump($value,-1);
        }
        elseif ($type=='boolean') $value= ($value?'true':'false');
        elseif ($type=='object')
        {
            $props= get_class_vars(get_class($value));
            echo '('.count($props).') <u>'.get_class($value).'</u>';
            foreach($props as $key=>$val)
            {
                echo "\n".str_repeat("\t",$level+1).$key.' => ';
                dump($value->$key,$level+1);
            }
            $value= '';
        }
        elseif ($type=='array')
        {
            echo '('.count($value).')';
            foreach($value as $key=>$val)
            {
                echo "\n".str_repeat("\t",$level+1).dump($key,-1).' => ';
                dump($val,$level+1);
            }
            $value= '';
        }
        echo " <b>$value</b>";
        if ($level==0) echo '</pre>';
    }
}
#END DATABASE CLASS
?>