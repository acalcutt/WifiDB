<?php
/*
Export.inc.php, holds the WiFiDB exporting functions.
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

class export extends dbcore
{
    public function __construct($config, $convert)
    {
        parent::__construct($config);
        $this->convert = $convert;
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
        $this->ver_array['export'] = array(
            "last_edit"             =>  "12-05-2013",
            "ExportAll"             =>  "2.0",
            "ExportAllDaily"        =>  "1.0",
            "ExportSingleAP"        =>  "1.0",
            "CreateKMZ"             =>  "1.0",
            "ExportCurrentAPkml"    =>  "1.0",
            "GenerateDaemonKMLData" =>  "1.0",
            "GenerateDaemonKMLLinks"=>  "1.0",
            "HistoryKMLLink"        =>  "1.0",
            "FulldbKMLLink"         =>  "1.0",
            "DailydbKMLLink"        =>  "1.0",
            "GenerateUpdateKML"     =>  "1.0",
            "ExportAllVS1"          =>  "2.0",
            "ExportAllGPX"          =>  "2.0",
            );
    }


    /*
     * Export to Google KML File
     */
    public function ExportAll()
    {
        $sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `lat` != 'N 0.0000' AND `lat` != 'N 0000.0000' AND `lat` != 'N 0.0000000' AND `lat` != 'N 00' ORDER by `id` ASC";
        $result = $this->sql->conn->query($sql);

        if($this->sql->checkError())
        {
            $this->verbosed("There was an error running the SQL");
            throw new ErrorException("There was an error running the SQL".var_export($this->sql->conn->errorInfo(), 1));
        }
        $NN = $result->rowCount();
        $this->verbosed("APs with GPS: ".$NN);
        $Odata = "";
        $Wdata = "";
        $Sdata = "";
        $open_count = 0;
        $wep_count = 0;
        $sec_count = 0;
        
        if($this->named)
        {
            $this->verbosed("Starting Export of Labeled Full KML.");
            $labeled = "_label";
        }else
        {
            $this->verbosed("Starting Export of Non-Labeled Full KML.");
            $labeled = "";
        }
        $daily_folder = $this->PATH.'out/daemon/'.date($this->date_format);
        if(!@file_exists($daily_folder))
        {
            $this->verbosed("Need to make a daily export folder...", 1);
            if(!@mkdir($daily_folder))
            {
                $this->verbosed("Error making new daily export folder...", -1);
            }
        }else
        {
            if(file_exists($daily_folder."/full_db".$labeled.".kml") && file_exists($daily_folder."/full_db".$labeled.".kmz")){$this->verbosed("Full DB Export for (".date($this->date_format).") already exists."); return -1;}
        }
        $this->verbosed("Compiling Data for Export.");
        while($array = $result->fetch(2))
        {
            $ssid = preg_replace('/[\x00-\x1F\x7F]/', '', $array['ssid']);
            
            $lat = $this->convert->dm2dd($array['lat']);
            $long = $this->convert->dm2dd($array['long']);
            
            $ssid_name = str_replace("", "", htmlentities($ssid, ENT_QUOTES));
            if($this->named)
            {
                $place_label = "<name>".$ssid_name."</name>";
            }else
            {
                $place_label = "";
            }
            switch($array['sectype'])
            {
                case 1:
                    $Odata .= "<Placemark id=\"".$array['mac']."\">
    ".$place_label."
    <description>
        <![CDATA[<b>SSID: </b>".$ssid."<br />
            <b>Mac Address: </b>".$array['mac']."<br />
            <b>Network Type: </b>".$array['NT']."<br />
            <b>Radio Type: </b>".$array['radio']."<br />
            <b>Channel: </b>".$array['chan']."<br />
            <b>Authentication: </b>".$array['auth']."<br />
            <b>Encryption: </b>".$array['encry']."<br />
            <b>Basic Transfer Rates: </b>".$array['BTx']."<br />
            <b>Other Transfer Rates: </b>".$array['OTx']."<br />
            <b>First Active: </b>".$array['FA']."<br />
            <b>Last Updated: </b>".$array['LA']."<br />
            <b>Latitude: </b>".$lat."<br />
            <b>Longitude: </b>".$long."<br />
            <b>Manufacturer: </b>".$array['manuf']."<br />
            <a href=\"".$this->URL_PATH."opt/fetch.php?id=".$array['id']."\">WiFiDB Link</a>
        ]]>
    </description>
    <styleUrl>openStyleDead</styleUrl>
    <Point id=\"".$array['mac']."_GPS\">
        <coordinates>".$long.",".$lat.",".$array['alt']."</coordinates>
    </Point>
</Placemark>\r\n";
                    $open_count++;
                    break;
                case 2:
                    $Wdata .= "<Placemark id=\"".$array['mac']."\">
    ".$place_label."
    <description>
        <![CDATA[<b>SSID: </b>".$ssid."<br />
            <b>Mac Address: </b>".$array['mac']."<br />
            <b>Network Type: </b>".$array['NT']."<br />
            <b>Radio Type: </b>".$array['radio']."<br />
            <b>Channel: </b>".$array['chan']."<br />
            <b>Authentication: </b>".$array['auth']."<br />
            <b>Encryption: </b>".$array['encry']."<br />
            <b>Basic Transfer Rates: </b>".$array['BTx']."<br />
            <b>Other Transfer Rates: </b>".$array['OTx']."<br />
            <b>First Active: </b>".$array['FA']."<br />
            <b>Last Updated: </b>".$array['LA']."<br />
            <b>Latitude: </b>".$lat."<br />
            <b>Longitude: </b>".$long."<br />
            <b>Manufacturer: </b>".$array['manuf']."<br />
            <a href=\"".$this->URL_PATH."opt/fetch.php?id=".$array['id']."\">WiFiDB Link</a>
        ]]>
    </description>
    <styleUrl>wepStyleDead</styleUrl>
    <Point id=\"".$array['mac']."_GPS\">
        <coordinates>".$long.",".$lat.",".$array['alt']."</coordinates>
    </Point>
</Placemark>\r\n";
                    $wep_count++;
                    break;
                case 3:
                    $Sdata .= "<Placemark id=\"".$array['mac']."\">
    ".$place_label."
    <description>
        <![CDATA[<b>SSID: </b>".$ssid."<br />
            <b>Mac Address: </b>".$array['mac']."<br />
            <b>Network Type: </b>".$array['NT']."<br />
            <b>Radio Type: </b>".$array['radio']."<br />
            <b>Channel: </b>".$array['chan']."<br />
            <b>Authentication: </b>".$array['auth']."<br />
            <b>Encryption: </b>".$array['encry']."<br />
            <b>Basic Transfer Rates: </b>".$array['BTx']."<br />
            <b>Other Transfer Rates: </b>".$array['OTx']."<br />
            <b>First Active: </b>".$array['FA']."<br />
            <b>Last Updated: </b>".$array['LA']."<br />
            <b>Latitude: </b>".$lat."<br />
            <b>Longitude: </b>".$long."<br />
            <b>Manufacturer: </b>".$array['manuf']."<br />
            <a href=\"".$this->URL_PATH."opt/fetch.php?id=".$array['id']."\">WiFiDB Link</a>
        ]]>
    </description>
    <styleUrl>secureStyleDead</styleUrl>
    <Point id=\"".$array['mac']."_GPS\">
        <coordinates>".$long.",".$lat.",".$array['alt']."</coordinates>
    </Point>
</Placemark>\r\n";
                    $sec_count++;
                    break;
            }
        }
        
        $full_kml_file = $daily_folder."/full_db".$labeled.".kml";
        $this->verbosed("Writing the Full KML File. ($NN APs) : ".$full_kml_file);
        file_put_contents($full_kml_file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<kml xmlns=\"$this->KML_SOURCE_URL\">
<!--exp_all_db_kml-->
    <Document>
        <name>WiFiDB Full DB Export (".date($this->date_format).")</name>
        <Style id=\"openStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->open_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"wepStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->WEP_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"secureStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->WPA_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"Location\">
            <LineStyle>
                <color>7f0000ff</color>
                <width>4</width>
            </LineStyle>
        </Style>
        <Folder>
            <name>Access Points</name>
            <description>APs: ".$NN."</description>
            <Folder>
                <name>WiFiDB Access Points</name>
                    <Folder>
                        <name>Open Access Points</name>
                        <description>APs: ".$open_count."</description>
                            ".$Odata."
                    </Folder>
                    <Folder>
                        <name>WEP Access Points</name>
                        <description>APs: ".$wep_count."</description>
                            ".$Wdata."
                    </Folder>
                    <Folder>
                        <name>Secure Access Points</name>
                        <description>APs: ".$sec_count."</description>
                            ".$Sdata."
                    </Folder>
            </Folder>
        </Folder>
     </Document>
</kml>");
        
        @link($daily_folder.'/full_db'.$labeled.'.kml', $this->PATH.'out/daemon/full_db'.$labeled.'.kml');
        $this->verbosed("Starting to compress the KML to a KMZ.");
        if($this->CreateKMZ($daily_folder.'/full_db'.$labeled.'.kml'))
        {
            $this->verbosed("Failed to Zip up the KML to a KMZ file :/");
        }
        @link($daily_folder.'/full_db'.$labeled.'.kmz', $this->PATH.'out/daemon/full_db'.$labeled.'.kmz');

        chmod($daily_folder.'/full_db'.$labeled.'.kml', 0664);
        chmod($this->PATH.'out/daemon/full_db'.$labeled.'.kml', 0664);
        chmod($this->PATH.'out/daemon/full_db'.$labeled.'.kmz', 0664);
        chmod($daily_folder.'/full_db'.$labeled.'.kmz', 0664);

        return $daily_folder;
    }
    
    /*
     * Export All Daily Aps to KML
     */
    public function ExportAllDaily()
    {
        $date = date($this->date_format);
        $date_search = $date."%";
        $select_daily = "SELECT `id` , `points`, `username`, `title`, `date` FROM `wifi`.`user_imports` WHERE `date` LIKE ?";
        $prep = $this->sql->conn->prepare($select_daily);
        $prep->bindParam(1, $date_search, PDO::PARAM_STR);
        $prep->execute();

        if($this->sql->checkError())
        {
            $this->verbosed("There was an error running the SQL".var_export($this->sql->conn->errorInfo(), 1));
            Throw new ErrorException("There was an error running the SQL".var_export($this->sql->conn->errorInfo(), 1));
        }
        if($prep->rowCount() < 1)
        {
            return -1;
        }
        
        if($this->named)
        {
            $this->verbosed("Start of Exporting Labeled Daily KML.");
            $labeled = "_label";
        }else
        {
            $this->verbosed("Start of Exporting Non-Labeled Daily KML.");
            $labeled = "";
        }
        $fetch_imports = $prep->fetchAll();

        $NN = 0;
        $imports = array();

        $sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `lat` != 'N 0.0000' AND `lat` != 'N 0000.0000' AND `lat` != 'N 0.0000000' AND `id` = ?";
        $result = $this->sql->conn->prepare($sql);
        foreach($fetch_imports as $import)
        {
            $open_count = 0;
            $wep_count = 0;
            $sec_count = 0;
            $Odata = "";
            $Wdata = "";
            $Sdata = "";

            $stage_pts = explode("-", $import['points']);
            foreach($stage_pts as $point)
            {
                $data = explode(":", $point);
                $result->bindParam(1, $data[1], PDO::PARAM_INT);
                $result->execute();

                if($this->sql->checkError())
                {
                    $this->verbosed("There was an error running the SQL".var_export($this->sql->conn->errorInfo(), 1));
                    Throw new ErrorException("There was an error running the SQL".var_export($this->sql->conn->errorInfo(), 1));
                }
                $array = $result->fetch();
                $NN++;
                $ssid = preg_replace('/[\x00-\x1F\x7F]/', '', $array['ssid']);

                $lat = $this->convert->dm2dd($array['lat']);

                $long = $this->convert->dm2dd($array['long']);

                $ssid_name = str_replace("", "", htmlentities($ssid, ENT_QUOTES));
                if($this->named)
                {
                    $place_label = "<name>".$ssid_name."</name>";
                }  else {
                    $place_label = "";
                }
                switch($array['sectype'])
                {
                    case 1:
                        $Odata .= "<Placemark id=\"".$array['mac']."\">
        ".$place_label."
        <description>
            <![CDATA[<b>SSID: </b>".$ssid."<br />
                <b>Mac Address: </b>".$array['mac']."<br />
                <b>Network Type: </b>".$array['NT']."<br />
                <b>Radio Type: </b>".$array['radio']."<br />
                <b>Channel: </b>".$array['chan']."<br />
                <b>Authentication: </b>".$array['auth']."<br />
                <b>Encryption: </b>".$array['encry']."<br />
                <b>Basic Transfer Rates: </b>".$array['BTx']."<br />
                <b>Other Transfer Rates: </b>".$array['OTx']."<br />
                <b>First Active: </b>".$array['FA']."<br />
                <b>Last Updated: </b>".$array['LA']."<br />
                <b>Latitude: </b>".$lat."<br />
                <b>Longitude: </b>".$long."<br />
                <b>Manufacturer: </b>".$array['manuf']."<br />
                <a href=\"".$this->URL_PATH."opt/fetch.php?id=".$array['id']."\">WiFiDB Link</a>
            ]]>
        </description>
        <styleUrl>openStyleDead</styleUrl>
        <Point id=\"".$array['mac']."_GPS\">
            <coordinates>".$long.",".$lat.",".$array['alt']."</coordinates>
        </Point>
    </Placemark>\r\n";
                        $open_count++;
                        break;
                    case 2:
                        $Wdata .= "<Placemark id=\"".$array['mac']."\">
        ".$place_label."
        <description>
            <![CDATA[<b>SSID: </b>".$ssid."<br />
                <b>Mac Address: </b>".$array['mac']."<br />
                <b>Network Type: </b>".$array['NT']."<br />
                <b>Radio Type: </b>".$array['radio']."<br />
                <b>Channel: </b>".$array['chan']."<br />
                <b>Authentication: </b>".$array['auth']."<br />
                <b>Encryption: </b>".$array['encry']."<br />
                <b>Basic Transfer Rates: </b>".$array['BTx']."<br />
                <b>Other Transfer Rates: </b>".$array['OTx']."<br />
                <b>First Active: </b>".$array['FA']."<br />
                <b>Last Updated: </b>".$array['LA']."<br />
                <b>Latitude: </b>".$lat."<br />
                <b>Longitude: </b>".$long."<br />
                <b>Manufacturer: </b>".$array['manuf']."<br />
                <a href=\"".$this->URL_PATH."opt/fetch.php?id=".$array['id']."\">WiFiDB Link</a>
            ]]>
        </description>
        <styleUrl>wepStyleDead</styleUrl>
        <Point id=\"".$array['mac']."_GPS\">
            <coordinates>".$long.",".$lat.",".$array['alt']."</coordinates>
        </Point>
    </Placemark>\r\n";
                        $wep_count++;
                        break;
                    case 3:
                        $Sdata .= "<Placemark id=\"".$array['mac']."\">
        ".$place_label."
        <description>
            <![CDATA[<b>SSID: </b>".$ssid."<br />
                <b>Mac Address: </b>".$array['mac']."<br />
                <b>Network Type: </b>".$array['NT']."<br />
                <b>Radio Type: </b>".$array['radio']."<br />
                <b>Channel: </b>".$array['chan']."<br />
                <b>Authentication: </b>".$array['auth']."<br />
                <b>Encryption: </b>".$array['encry']."<br />
                <b>Basic Transfer Rates: </b>".$array['BTx']."<br />
                <b>Other Transfer Rates: </b>".$array['OTx']."<br />
                <b>First Active: </b>".$array['FA']."<br />
                <b>Last Updated: </b>".$array['LA']."<br />
                <b>Latitude: </b>".$lat."<br />
                <b>Longitude: </b>".$long."<br />
                <b>Manufacturer: </b>".$array['manuf']."<br />
                <a href=\"".$this->URL_PATH."opt/fetch.php?id=".$array['id']."\">WiFiDB Link</a>
            ]]>
        </description>
        <styleUrl>secureStyleDead</styleUrl>
        <Point id=\"".$array['mac']."_GPS\">
            <coordinates>".$long.",".$lat.",".$array['alt']."</coordinates>
        </Point>
    </Placemark>\r\n";
                        $sec_count++;
                        break;
                }
            }

            $imports[] = "<Folder>
                <name>".$import['username']." - ".$import['title']."</name>
                    <Folder>
                        <name>Open Access Points</name>
                        <description>APs: ".$open_count."</description>
                            ".$Odata."
                    </Folder>
                    <Folder>
                        <name>WEP Access Points</name>
                        <description>APs: ".$wep_count."</description>
                            ".$Wdata."
                    </Folder>
                    <Folder>
                        <name>Secure Access Points</name>
                        <description>APs: ".$sec_count."</description>
                            ".$Sdata."
                    </Folder>
            </Folder>";

        }

        $all_imports = implode("\r\n\t\t\t", $imports);
        $daily_folder = $this->PATH.'out/daemon/'.date($this->date_format);
        if(!@file_exists($daily_folder))
        {
            $this->verbosed("Need to make a daily export folder...", 1);
            if(!@mkdir($daily_folder))
            {
                $this->verbosed("Error making new daily export folder...", -1);
                Throw new ErrorException("Error Making new Daily Export Folder. - ".$php_errormsg." - ".$daily_folder);
            }
        }


        $full_kml_file = $daily_folder."/daily_db".$labeled.".kml";
        $this->verbosed("Writing the Daily KML File: ".$full_kml_file);
        file_put_contents($full_kml_file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<kml xmlns=\"$this->KML_SOURCE_URL\">
    <Document>
        <name>Daily DB ($date)</name>
        <Style id=\"openStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->open_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"wepStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->WEP_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"secureStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->WPA_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"Location\">
            <LineStyle>
                <color>7f0000ff</color>
                <width>4</width>
            </LineStyle>
        </Style>
        <Folder>
            <name>WiFiDB Daily Imports</name>
            <description>APs: ".$NN."</description>
            $all_imports
        </Folder>
     </Document>
</kml>");
        
        @link($daily_folder.'/daily_db'.$labeled.'.kml', $this->PATH.'out/daemon/daily_db'.$labeled.'.kml');
        $this->verbosed("Starting to compress the KML to a KMZ.");
        if($this->CreateKMZ($daily_folder.'/daily_db'.$labeled.'.kml'))
        {
            $this->verbosed("Failed to compress the Daily KMZ file :(", -1);
            Throw New ErrorException("Failed to compress the Daily KMZ file :(");
        }
        @link($daily_folder.'/daily_db'.$labeled.'.kmz', $this->PATH.'out/daemon/daily_db'.$labeled.'.kmz');


        chmod($daily_folder.'/daily_db'.$labeled.'.kml', 0750);
        chmod($this->PATH.'out/daemon/daily_db'.$labeled.'.kml', 0750);

        return $daily_folder;
    }

    /*
     * Export All Daily Aps to KML
     */
    public function ExportSingleAP( $id = 0, $from = 0, $limit = 0)
    {
        $sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $id, PDO::PARAM_INT);
        $prep->execute();
        if($this->sql->checkError() !== 0)
        {
            throw new ErrorException("Could not select ID from pointers Table.");
        }
        $fetch = $prep->fetch(2);
        switch($fetch['sectype'])
        {
            case "1":
                $type = "openStyleDead";
            break;

            case "2":
                $type = "wepStyleDead";
            break;

            case "3":
                $type = "secureStyleDead";
            break;
        }

        $sql = "SELECT * FROM `wifi`.`wifi_signals` WHERE `ap_hash` = ? LIMIT $from, $limit";
        $prep2 = $this->sql->conn->prepare($sql);
        $prep2->bindParam(1, $fetch['ap_hash'], PDO::PARAM_STR);
        $prep2->execute();
        if($this->sql->checkError() !== 0)
        {
            throw new ErrorException("Could not select from Signals Table.");
        }
        $signals = $prep2->fetchAll(2);

        $SignalData = "";
        $sql = "SELECT * FROM `wifi`.`wifi_gps` WHERE `id` = ?";
        $prep3 = $this->sql->conn->prepare($sql);
        foreach($signals as $signal)
        {

            $prep3->bindParam(1, $signal['gps_id'], PDO::PARAM_INT);
            $prep3->execute();
            if($this->sql->checkError() !== 0)
            {
                throw new ErrorException("Could not select from GPS Table.");
            }
            $gps = $prep3->fetch(2);
            $SignalData .= "
                    <Placemark id=\"".$signal['id']."\">
                        ".$signal['id']."
                        <description>
                            <![CDATA[
                                <b>Signal Percent: </b>".$signal['signal']."<br />
                                <b>RSSI Signal: </b>".$signal['rssi']."<br />
                                <b>Date: </b>".$gps['date']."<br />
                                <b>Time: </b>".$gps['time']."<br />
                            ]]>
                        </description>
                        <styleUrl>$type</styleUrl>
                        <Point id=\"".$fetch['mac']."_GPS\">
                            <coordinates>".$this->convert->dm2dd($gps['long']).",".$this->convert->dm2dd($gps['lat']).",".$gps['alt']."</coordinates>
                        </Point>
                    </Placemark>
";
            $alt = $gps['alt'];
        }
        $file = $fetch['ap_hash']."_".str_pad(rand(0, 999999), 6, "0").".kml";
        $kml_file = $this->PATH."out/kml/single/".$file;
        file_put_contents($kml_file, "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<kml xmlns=\"$this->KML_SOURCE_URL\">
<!--export single ap kml-->
    <Document>
        <name>".htmlentities($fetch['ssid'], ENT_QUOTES)." (".date($this->date_format).")</name>
        <Style id=\"openStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->open_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"wepStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->WEP_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"secureStyleDead\">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>".$this->WPA_loc."</href>
                </Icon>
            </IconStyle>
        </Style>
        <Style id=\"Location\">
            <LineStyle>
                <color>7f0000ff</color>
                <width>4</width>
            </LineStyle>
        </Style>
        <Folder>
            <name>Access Point Center</name>
            <description>Best Signal For All AP History</description>
            <Placemark id=\"".$fetch['mac']."\">
                ".htmlentities($fetch['ssid'], ENT_QUOTES)."
                <description>
                    <![CDATA[<b>SSID: </b>".htmlentities($fetch['ssid'], ENT_QUOTES)."<br />
                        <b>Mac Address: </b>".$fetch['mac']."<br />
                        <b>Network Type: </b>".$fetch['NT']."<br />
                        <b>Radio Type: </b>".$fetch['radio']."<br />
                        <b>Channel: </b>".$fetch['chan']."<br />
                        <b>Authentication: </b>".$fetch['auth']."<br />
                        <b>Encryption: </b>".$fetch['encry']."<br />
                        <b>Basic Transfer Rates: </b>".$fetch['BTx']."<br />
                        <b>Other Transfer Rates: </b>".$fetch['OTx']."<br />
                        <b>First Active: </b>".$fetch['FA']."<br />
                        <b>Last Updated: </b>".$fetch['LA']."<br />
                        <b>Latitude: </b>".$fetch['lat']."<br />
                        <b>Longitude: </b>".$fetch['long']."<br />
                        <b>Manufacturer: </b>".$fetch['manuf']."<br />
                        <a href=\"".$this->URL_PATH."opt/fetch.php?id=".$fetch['id']."\">WiFiDB Link</a>
                    ]]>
                </description>
                <styleUrl>$type</styleUrl>
                <Point id=\"".$fetch['mac']."_GPS\">
                    <coordinates>".$this->convert->dm2dd($fetch['long']).",".$this->convert->dm2dd($fetch['lat']).",".$alt."</coordinates>
                </Point>
            </Placemark>
            <Folder>
                <name>Signal Points</name>
                <description>Selected Export History for AP's signal points</description>
                ".$SignalData."
            </Folder>
        </Folder>
     </Document>
</kml>");

        $ret = array(
            'mesg' => "Export for AP: ".$fetch['ssid']." was Successful.",
            'link' => $this->URL_PATH."out/kml/single/".$file,
            'name' => $file
        );
        return $ret;
    }

    /*
     * Create a compressed file from a filename and the destination extention
     */
    public function CreateKMZ($file = "")
    {
        if($file === ""){return 1;}
        $file_exp = explode(".", $file);
        $file_create = $file_exp[0].'.kmz';
        
        $zip = new ZipArchive;
        $zip->open($file_create, ZipArchive::CREATE);
        #var_dump($zip->getStatusString());
        
        $zip->addFile($file, 'doc.kml');
        #var_dump($zip->getStatusString());
        
        $zip->close();
        return 0;
    }
    
    /*
     * Export to Garmin GPX File
     */
    public function ExportGPXAll()
    {
        $this->verbosed("Starting GPX Export of WiFiDB.");
        $sql = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `lat` != 'N 0.0000' AND `lat` != 'N 0000.0000' AND `lat` != 'N 0.0000000' AND `lat` != 'N 00' ORDER by `id` ASC";
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
            
            $type = $aparray['sectype'];
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
            $date = $aparray["date"];
            $time = $aparray["time"];
            $alt = $aparray['alt'] * 3.28;
            $lat = $this->convert->dm2dd($aparray['lat']);
            $long = $this->convert->dm2dd($aparray['long']);

            $file_data .= "<wpt lat=\"".$lat."\" lon=\"".$long."\">\r\n"
                                            ."<ele>".$alt."</ele>\r\n"
                                            ."<time>".$date."T".$time."Z</time>\r\n"
                                            ."<name>".$aparray['ssid']."</name>\r\n"
                                            ."<cmt>".$aparray['mac']."</cmt>\r\n"
                                            ."<desc>".$aparray['label']."</desc>\r\n"
                                            ."<sym>".$color."</sym>\r\n<extensions>\r\n"
                                            ."<gpxx:WaypointExtension xmlns:gpxx=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.garmin.com/xmlschemas/GpxExtensions/v3 http://www.garmin.com/xmlschemas/GpxExtensions/v3/GpxExtensionsv3.xsd\">\r\n"
                                            ."<gpxx:DisplayMode>SymbolAndName</gpxx:DisplayMode>\r\n<gpxx:Categories>\r\n"
                                            ."<gpxx:Category>Category ".$type."</gpxx:Category>\r\n</gpxx:Categories>\r\n</gpxx:WaypointExtension>\r\n</extensions>\r\n</wpt>\r\n\r\n";
            if($aparray['rssi'])
            {
                $signals = explode("\\", $aparray['signals']);
            }else
            {
                $signals = explode("-",$aparray['sig']);
            }
            
            $file_data .= "<trk>\r\n<name>GPS Track</name>\r\n<trkseg>\r\n";
            foreach($signals as $signal)
            {
                $sig_exp    = explode(",",$signal);
                $gpsid      = $sig_exp[0];
                
                $sql = "SELECT * FROM `wifi`.`wifi_gps` WHERE `id` = ? LIMIT 1";
                $prepgps = $this->sql->conn->prepare($sql);
                $prepgps->bindParam(1, $gpsid, PDO::PARAM_INT);
                $prepgps->execute();
                $gps = $prepgps->fetch(2);
                
                $alt = $gps['alt'] * 3.28;

                $lat =& $this->convert->dm2dd($gps['lat']);

                $long =& $this->convert->dm2dd($gps['long']);
                $file_data .= "<trkpt lat=\"".$lat."\" lon=\"".$long."\">\r\n"
                                        ."<ele>".$alt."</ele>\r\n"
                                        ."<time>".$date."T".$time."Z</time>\r\n"
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
     * Export to Vistumbler VS1 File
     */
    public function ExportCurrentAPkml($verbose = 0)
    {
        $KML_SOURCE_URL = "http://www.opengis.net/kml/2.2";
        $KML_folder = $this->PATH."/out/daemon/";
        $filename = $KML_folder."newestAP.kml";
        $filename_label = $KML_folder."newestAP_label.kml";

        $sql = "SELECT * FROM `wifi`.`wifi_pointers` ORDER BY `id` DESC LIMIT 1";
        $result = $this->sql->conn->query($sql);
        $ap_array = $result->fetch(2);
        $this->verbosed('Start export of Newest AP: '.$ap_array["ssid"]>"\n".$filename."\n".$filename_label, $verbose, "CLI");
        
        $man    = $ap_array['manuf'];
        $id     = $ap_array['id'];

        $ssid_name = str_replace("", "", htmlentities($ap_array['ssid'], ENT_QUOTES));

        $mac	= $ap_array['mac'];
        $radio	= $ap_array['radio'];
        switch($ap_array['sectype'])
        {
            case 1:
                $type = "#openStyleDead";
                $auth = "Open";
                $encry = "None";
                break;
            case 2:
                $type = "#wepStyleDead";
                $auth = "Open";
                $encry = "WEP";
                break;
            case 3:
                $type = "#secureStyleDead";
                $auth = "WPA-Personal";
                $encry = "TKIP-PSK";
                break;
        }
        switch($ap_array['radio'])
        {
            case "a":
                $radio="802.11a";
                break;
            case "b":
                $radio="802.11b";
                break;
            case "g":
                $radio="802.11g";
                break;
            case "n":
                $radio="802.11n";
                break;
            default:
                $radio="Unknown Radio";
                break;
        }
        $otx = $ap_array["OTx"];
        $btx = $ap_array["BTx"];
        $nt = $ap_array['NT'];

        $lat_exp = explode(".", $ap_array['lat']);

        $test = $lat_exp[1];

        if($test == "0000")
        {$zero = 1;}
        else
        {
            if(strlen($test) > 4)
            {
                $lat = $ap_array['lat'];
                $long = $ap_array['long'];
            }else
            {
                $lat = $this->convert->dm2dd($ap_array['lat']);
                $long = $this->convert->dm2dd($ap_array['long']);
            }
            $fa = $ap_array["FA"];
            $la = $ap_array["LA"];
            $alt = $ap_array['alt'];
            $zero = 0;
        }
        if($zero == 1)
        {
            $this->verbosed('Didnt Find any, not writing AP to file.', $verbose, "CLI");
        }else
        {
            $this->verbosed('Found some, writing KML File.', $verbose, "CLI");
            $Odata = "<Placemark id=\"".$mac."\">\r\n	<description><![CDATA[<b>SSID: </b>".$ssid_name."<br /><b>Mac Address: </b>".$mac."<br /><b>Network Type: </b>".$nt."<br /><b>Radio Type: </b>".$radio."<br /><b>Channel: </b>".$ap_array['chan']."<br /><b>Authentication: </b>".$auth."<br /><b>Encryption: </b>".$encry."<br /><b>Basic Transfer Rates: </b>".$btx."<br /><b>Other Transfer Rates: </b>".$otx."<br /><b>First Active: </b>".$fa."<br /><b>Last Updated: </b>".$la."<br /><b>Latitude: </b>".$lat."<br /><b>Longitude: </b>".$long."<br /><b>Manufacturer: </b>".$man."<br /><a href=\"".$this->URL_PATH."/opt/fetch.php?id=".$id."\">WiFiDB Link</a>]]></description>\r\n	<styleUrl>".$type."</styleUrl>\r\n<Point id=\"".$mac."_GPS\">\r\n<coordinates>".$long.",".$lat.",".$alt."</coordinates>\r\n</Point>\r\n</Placemark>\r\n";
            
            $Ddata  =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<kml xmlns=\"$KML_SOURCE_URL\"><!--exp_all_db_kml-->\r\n<Document>\r\n<name>RanInt WifiDB KML Newset AP</name>\r\n";
            $Ddata .= "<Style id=\"openStyleDead\">\r\n<IconStyle>\r\n<scale>0.5</scale>\r\n<Icon>\r\n<href>".$this->open_loc."</href>\r\n</Icon>\r\n</IconStyle>\r\n	</Style>\r\n";
            $Ddata .= "<Style id=\"wepStyleDead\">\r\n<IconStyle>\r\n<scale>0.5</scale>\r\n<Icon>\r\n<href>".$this->WEP_loc."</href>\r\n</Icon>\r\n</IconStyle>\r\n</Style>\r\n";
            $Ddata .= "<Style id=\"secureStyleDead\">\r\n<IconStyle>\r\n<scale>0.5</scale>\r\n<Icon>\r\n<href>".$this->WPA_loc."</href>\r\n</Icon>\r\n</IconStyle>\r\n</Style>\r\n";
            $Ddata .= '<Style id="Location"><LineStyle><color>7f0000ff</color><width>4</width></LineStyle></Style>';
            $Ddata .= "\r\n".$Odata."\r\n";
            $Ddata = $Ddata."</Document>\r\n</kml>";


            $filewrite  =   fopen($filename, "w");
            if($filewrite)
            {
                $fileappend =   fopen($filename, "a");
                fwrite($fileappend, $Ddata);
                fclose($fileappend);
            }else
            {
                $this->verbosed('Could not write Placer file ('.$filename.'), check permissions.', $verbose, "CLI");
            }
            #####################################
            $Odata = "<Placemark id=\"".$mac."_Label\">\r\n	<name>".$ssid_name."</name>\r\n	<description><![CDATA[<b>SSID: </b>".$ssid_name."<br /><b>Mac Address: </b>".$mac."<br /><b>Network Type: </b>".$nt."<br /><b>Radio Type: </b>".$radio."<br /><b>Channel: </b>".$ap_array['chan']."<br /><b>Authentication: </b>".$auth."<br /><b>Encryption: </b>".$encry."<br /><b>Basic Transfer Rates: </b>".$btx."<br /><b>Other Transfer Rates: </b>".$otx."<br /><b>First Active: </b>".$fa."<br /><b>Last Updated: </b>".$la."<br /><b>Latitude: </b>".$lat."<br /><b>Longitude: </b>".$long."<br /><b>Manufacturer: </b>".$man."<br /><a href=\"".$this->URL_PATH."/opt/fetch.php?id=".$id."\">WiFiDB Link</a>]]></description>\r\n	<styleUrl>".$type."</styleUrl>\r\n<Point id=\"".$mac."_GPS\">\r\n<coordinates>".$long.",".$lat.",".$alt."</coordinates>\r\n</Point>\r\n</Placemark>\r\n";

            $Ddata  =  "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n<kml xmlns=\"$KML_SOURCE_URL\"><!--exp_all_db_kml-->\r\n<Document>\r\n<name>RanInt WifiDB KML Newset AP</name>\r\n";
            $Ddata .= "<Style id=\"openStyleDead\">\r\n<IconStyle>\r\n<scale>0.5</scale>\r\n<Icon>\r\n<href>".$this->open_loc."</href>\r\n</Icon>\r\n</IconStyle>\r\n	</Style>\r\n";
            $Ddata .= "<Style id=\"wepStyleDead\">\r\n<IconStyle>\r\n<scale>0.5</scale>\r\n<Icon>\r\n<href>".$this->WEP_loc."</href>\r\n</Icon>\r\n</IconStyle>\r\n</Style>\r\n";
            $Ddata .= "<Style id=\"secureStyleDead\">\r\n<IconStyle>\r\n<scale>0.5</scale>\r\n<Icon>\r\n<href>".$this->WPA_loc."</href>\r\n</Icon>\r\n</IconStyle>\r\n</Style>\r\n";
            $Ddata .= '<Style id="Location"><LineStyle><color>7f0000ff</color><width>4</width></LineStyle></Style>';
            $Ddata .= "\r\n".$Odata."\r\n";
            $Ddata = $Ddata."</Document>\r\n</kml>";

            $filewrite_l = fopen($filename_label, "w");
            if($filewrite_l)
            {
                $fileappend_label = fopen($filename_label, "a");
                fwrite($fileappend_label, $Ddata);
                fclose($fileappend_label);
            }else
            {
                $this->verbosed('Could not write Placer file ('.$filename_label.'), check permissions.', $verbose, "CLI");
            }
            $this->recurse_chown_chgrp($KML_folder, $this->apache_user, $this->apache_group);
            $this->recurse_chmod($KML_folder, 0750);

            $this->verbosed('File has been written and is ready.', $verbose, "CLI");
        }
    }

    /*
     * Generate the Daily Daemon KML files
     */
    public function GenerateDaemonKMLData()
    {
        $this->named = 0;
        $this->ExportAll();
        $this->named = 1;
        $this->ExportAll();
        
        $this->named = 0;
        $this->ExportAllDaily();
        $this->named = 1;
        $this->ExportAllDaily();
        
        if($this->HistoryKMLLink() === -1)
        {
            $this->verbosed("Failed to Create Daemon History KML Links", -1);
        }else
        {
            $this->verbosed("Created Daemon History KML Links");
        }
        
        if($this->GenerateUpdateKML() === -1)
        {
            $this->verbosed("Failed to Create Update.kml File", -1);
        }else
        {
            $this->verbosed("Created Update.kml File");
        }
        return 1;
    }
    
    /*
     * Create the Archival KML links
     */
    public function HistoryKMLLink()
    {
        $this->daemon_folder_stats = array();
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
            $output = $daemon_export.'history/'.$key.'.kml';
            $current_year = date("Y")+0;
            if(file_exists($output) && $key != $current_year)
            {
                $generated[] = $key.'.kml';
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
                    if(file_exists($daemon_export.$day.'/daily_db.kml'))
                    {
                        $daily_db_kml_nl = '
                        <NetworkLink>
                                <name>Daily KML</name>
                                <visibility>0</visibility>
                                <Link>
                                        <href>'.$this->URL_PATH.'out/daemon/'.$day.'/daily_db.kml</href>
                                </Link>
                        </NetworkLink>';
                    }else
                    {
                        $daily_db_kml_nl = '';
                    }
                    
                    if(file_exists($daemon_export.$day.'/daily_db_label.kml'))
                    {
                        $daily_db_kml_label_nl = '
                        <NetworkLink>
                                <name>Daily Labeled KML</name>
                                <visibility>0</visibility>
                                <Link>
                                        <href>'.$this->URL_PATH.'out/daemon/'.$day.'/daily_db_label.kml</href>
                                </Link>
                        </NetworkLink>';
                    }else
                    {
                        $daily_db_kml_label_nl = '';
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
                    
                    if(file_exists($daemon_export.$day.'/full_db.kml'))
                    {
                        $full_db_kml_nl = '
                        <NetworkLink>
                                <name>Full DB KML</name>
                                <visibility>0</visibility>
                                <Link>
                                        <href>'.$this->URL_PATH.'out/daemon/'.$day.'/full_db.kml</href>
                                </Link>
                        </NetworkLink>';
                    }else
                    {
                        $full_db_kml_nl = '';
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
                    
                    if(file_exists($daemon_export.$day.'/full_db_label.kml'))
                    {
                        $full_db_label_kml_nl = '
                        <NetworkLink>
                                <name>Full DB Labeled KML</name>
                                <visibility>0</visibility>
                                <Link>
                                        <href>'.$this->URL_PATH.'out/daemon/'.$day.'/full_db_label.kml</href>
                                </Link>
                        </NetworkLink>';
                    }else
                    {
                        $full_db_label_kml_nl = '';
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
                        <open>0</open>'.$daily_db_kml_nl.
                            $daily_db_kmz_nl.
                            $daily_db_kml_label_nl.
                            $daily_db_kmz_label_nl.
                            $full_db_kml_nl.
                            $full_db_kmz_nl.
                            $full_db_label_kml_nl.
                            $full_db_label_kmz_nl.'
                </Folder>';
                }
                $kml_data .= '</Folder>';
            }
            $kml_data .= '</Folder></kml>';
            
            file_put_contents($output, $kml_data);
            $generated[] = $key.'.kml';
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
        file_put_contents($output, $kml_data);
    }
    
    /*
     * Generate the updated KML Link
     */
    public function GenerateUpdateKML()
    {
        $kml_data = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
<Folder>
    <name>WiFiDB Network Link</name>
    <open>1</open>
    <Folder>
        <name>Newest Data</name>
        <visibility>0</visibility>
                <Folder>
                        <name>Newest AP</name>
                        <open>1</open>
                        <NetworkLink>
                                <name>Newest AP (No Label)</name>
                                <visibility>0</visibility>
                                <flyToView>1</flyToView>
                                <Link>
                                        <href>'.$this->URL_PATH.'out/daemon/newestAP.kml</href>
                                        <refreshMode>onInterval</refreshMode>
                                        <refreshInterval>2</refreshInterval>
                                </Link>
                        </NetworkLink>
                        <NetworkLink>
                                <name>Newest AP (Labeled)</name>
                                <flyToView>1</flyToView>
                                <Link>
                                        <href>'.$this->URL_PATH.'out/daemon/newestAP_label.kml</href>
                                        <refreshMode>onInterval</refreshMode>
                                        <refreshInterval>2</refreshInterval>
                                </Link>
                        </NetworkLink>
                </Folder>
        <NetworkLink>
            <name>Full DataBase</name>
            <visibility>0</visibility>
            <Link>
                <href>'.$this->URL_PATH.'out/daemon/full_db.kml</href>
                <refreshMode>onInterval</refreshMode>
                <refreshInterval>86404</refreshInterval>
            </Link>
        </NetworkLink>
        <NetworkLink>
            <name>Daily DataBase</name>
            <visibility>0</visibility>
            <Link>
                <href>'.$this->URL_PATH.'out/daemon/daily_db.kml</href>
                <refreshMode>onInterval</refreshMode>
                <refreshInterval>3604</refreshInterval>
            </Link>
                </NetworkLink>
        </Folder>
        <NetworkLink>
                <name>Archived History</name>
                <visibility>0</visibility>
                <Link>
                        <href>'.$this->URL_PATH.'out/daemon/history.kml</href>
                        <refreshMode>onInterval</refreshMode>
                        <refreshInterval>86400</refreshInterval>
                </Link>
        </NetworkLink>
</Folder>
</kml>
';
        if(file_put_contents($this->PATH."out/daemon/update.kml", $kml_data))
        {
            return 1;
        }else
        {
            return 0;
        }
    }

    /*
     * Export to Vistumbler VS1 File
     *
     * TODO: NEEDS TO BE RE-WRITTEN
     */
    public function exp_vs1($export = "", $user = "", $row = 0, $screen = "HTML")
    {
        $vs1_out   = $this->PATH."/out/VS1/";
        $gps_array = array();
        switch ($export)
        {
                case "exp_user_all":
                        if($screen == "HTML"){echo '<table><tr class="style4"><th style="border-style: solid; border-width: 1px">Start Export Of $user</th></tr>';}
                        $sql_		= "SELECT * FROM `wifi`.`$users_t` WHERE `username` = '$user' ORDER by `id` ASC";
                        $result_2	= mysql_query($sql_, $conn) or die(mysql_error($conn));
                        echo "Rows: ".mysql_num_rows($result_2)."\r\n";
                        $username = $list_array['username'];
                        $nn =-1;
                        $n =1;	# GPS Array KEY -has to start at 1 vistumbler will error out if the first GPS point has a key of 0
                        while($list_array = mysql_fetch_array($result_2))
                        {
                                if($list_array["points"] == ''){continue;}
                                $points = explode("-", $list_array['points']);
                                $title = $list_array['title'];
                        #	echo "Starting AP Export.\r\n";
                                foreach($points as $point)
                                {
                        #		echo $point."\r\n";
                                        $nn++;
                                        $point_exp = explode(",", $point);
                                        $pnt = explode(":", $point_exp[1]);
                                        $rows = $pnt[1];
                                        $APID = $pnt[0];
                                        $sql_1	= "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = '$APID' LIMIT 1";
                                        $result_1	= mysql_query($sql_1, $conn) or die(mysql_error($conn));
                                        $ap_array = mysql_fetch_array($result_1);
                                        #var_dump($ap_array);
                                        $manuf = $this->findManuf($ap_array['mac']);
                                        switch($ap_array['sectype'])
                                                {
                                                        case 1:
                                                                $type = "#openStyleDead";
                                                                $auth = "Open";
                                                                $encry = "None";
                                                                break;
                                                        case 2:
                                                                $type = "#wepStyleDead";
                                                                $auth = "Open";
                                                                $encry = "WEP";
                                                                break;
                                                        case 3:
                                                                $type = "#secureStyleDead";
                                                                $auth = "WPA-Personal";
                                                                $encry = "TKIP-PSK";
                                                                break;
                                                }
                                        switch($ap_array['radio'])
                                                {
                                                        case "a":
                                                                $radio="802.11a";
                                                                break;
                                                        case "b":
                                                                $radio="802.11b";
                                                                break;
                                                        case "g":
                                                                $radio="802.11g";
                                                                break;
                                                        case "n":
                                                                $radio="802.11n";
                                                                break;
                                                        default:
                                                                $radio="Unknown Radio";
                                                                break;
                                                }
                        #		echo $ap_array['id']." -- ".$ap_array['ssid']."\r\n";
                                        $ssid_edit = html_entity_decode($ap_array['ssid']);
                                        list($ssid_t, $ssid_f, $ssid)  = make_ssid($ssid_edit);
                                #	$ssid_t = $ssid_array[0];
                                #	$ssid_f = $ssid_array[1];
                                #	$ssid = $ssid_array[2];
                                        $table	=	$ssid_t.'-'.$ap_array['mac'].'-'.$ap_array['sectype'].'-'.$ap_array['radio'].'-'.$ap_array['chan'];
                                        $sql1 = "SELECT * FROM `wifi_st`.`$table` WHERE `id` = '$rows'";
                                        $result1 = mysql_query($sql1, $conn) or die(mysql_error($conn));
                                        $newArray = mysql_fetch_array($result1);
#					echo $nn."<BR>";
                                        $otx	= $newArray["otx"];
                                        $btx	= $newArray["btx"];
                                        $nt		= $newArray['nt'];
                                        $label	= $newArray['label'];
                                        $signal	= $newArray['sig'];
                                        $aps[$nn]	= array(
                                                                                'id'		=>	$ap_array['id'],
                                                                                'ssid'		=>	$ssid_t,
                                                                                'mac'		=>	$ap_array['mac'],
                                                                                'sectype'	=>	$ap_array['sectype'],
                                                                                'r'			=>	$radio,
                                                                                'radio'		=>	$ap_array['radio'],
                                                                                'chan'		=>	$ap_array['chan'],
                                                                                'man'		=>	$manuf,
                                                                                'type'		=>	$type,
                                                                                'auth'		=>	$auth,
                                                                                'encry'		=>	$encry,
                                                                                'label'		=>	$label,
                                                                                'nt'		=>	$nt,
                                                                                'btx'		=>	$btx,
                                                                                'otx'		=>	$otx,
                                                                                'sig'		=>	$signal
                                                                                );

                                        $sig		=	$aps[$nn]['sig'];
                                        $signals	=	explode("-", $sig);
        #				echo $sig."<BR>";
                                        $table_gps		=	$aps[$nn]['ssid'].'-'.$aps[$nn]['mac'].'-'.$aps[$nn]['sectype'].'-'.$aps[$nn]['radio'].'-'.$aps[$nn]['chan'].$gps_ext;
                        #		echo $table_gps."\r\n";
                                        foreach($signals as $key=>$val)
                                        {
                                                $sig_exp = explode(",", $val);
                                                $gps_id	= $sig_exp[0];

                                                $sql1 = "SELECT * FROM `wifi_st`.`$table_gps` WHERE `id` = '$gps_id'";
                                                $result1 = mysql_query($sql1, $conn) or die(mysql_error($conn));
                                                $gps_table = mysql_fetch_array($result1);
                                                $gps_array[$n]	=	array(
                                                                                                "lat" => $gps_table['lat'],
                                                                                                "long" => $gps_table['long'],
                                                                                                "sats" => $gps_table['sats'],
                                                                                                "hdp" => $gps_table['hdp'],
                                                                                                "alt" => $gps_table['alt'],
                                                                                                "geo" => $gps_table['geo'],
                                                                                                "kmh" => $gps_table['kmh'],
                                                                                                "mph" => $gps_table['mph'],
                                                                                                "track" => $gps_table['track'],
                                                                                                "date" => $gps_table['date'],
                                                                                                "time" => $gps_table['time']
                                                                                                );
                                                $n++;
                                                $signals[] = $n.",".$sig_exp[1];
                                        }
                                        echo $nn."-".$n."==";
                                        $sig_new = implode("-", $signals);
                                        $aps[$nn]['sig'] = $sig_new;
                                        unset($signals);
                                }
                        }
                break;

                case "exp_user_list":
                        $sql_		= "SELECT * FROM `wifi`.`$users_t` WHERE `id` = '$row' LIMIT 1";
                        $result_1	= mysql_query($sql_, $conn) or die(mysql_error($conn));
                        $list_array = mysql_fetch_array($result_1);
                        if($list_array["points"] == ''){$return = array(0=>0, 1=>"Empty return"); return $return;}
                        $points = explode("-", $list_array['points']);
                        $title = $list_array['title'];
                        $username = $list_array['username'];
                        if($screen == "HTML"){echo '<table><tr class="style4"><th style="border-style: solid; border-width: 1px">Start Export Of $title by $username</th></tr>';}
                #	echo "Starting AP Export.\r\n";
                        $nn=-1;
                        foreach($points as $point)
                        {
                #		echo $point."\r\n";
                                $nn++;
                #		echo $nn."\r\n";
                                $point_exp = explode(",", $point);
                                $pnt = explode(":", $point_exp[1]);
                                $rows = $pnt[1];
                                $APID = $pnt[0];
                                $sql_1	= "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = '$APID' LIMIT 1";
                                $result_1	= mysql_query($sql_1, $conn) or die(mysql_error($conn));
                                $ap_array = mysql_fetch_array($result_1);
                                #var_dump($ap_array);
                                $manuf = $this->findManuf($ap_array['mac']);
                                switch($ap_array['sectype'])
                                        {
                                                case 1:
                                                        $type = "#openStyleDead";
                                                        $auth = "Open";
                                                        $encry = "None";
                                                        break;
                                                case 2:
                                                        $type = "#wepStyleDead";
                                                        $auth = "Open";
                                                        $encry = "WEP";
                                                        break;
                                                case 3:
                                                        $type = "#secureStyleDead";
                                                        $auth = "WPA-Personal";
                                                        $encry = "TKIP-PSK";
                                                        break;
                                        }
                                switch($ap_array['radio'])
                                        {
                                                case "a":
                                                        $radio="802.11a";
                                                        break;
                                                case "b":
                                                        $radio="802.11b";
                                                        break;
                                                case "g":
                                                        $radio="802.11g";
                                                        break;
                                                case "n":
                                                        $radio="802.11n";
                                                        break;
                                                default:
                                                        $radio="Unknown Radio";
                                                        break;
                                        }
                #		echo $ap_array['id']." -- ".$ap_array['ssid']."\r\n";
                                $ssid_edit = html_entity_decode($ap_array['ssid']);
                                list($ssid_t, $ssid_f, $ssid)  = make_ssid($ssid_edit);
                        #	$ssid_t = $ssid_array[0];
                        #	$ssid_f = $ssid_array[1];
                        #	$ssid = $ssid_array[2];
                                $table	=	$ssid_t.'-'.$ap_array['mac'].'-'.$ap_array['sectype'].'-'.$ap_array['radio'].'-'.$ap_array['chan'];
                                $sql1 = "SELECT * FROM `wifi_st`.`$table` WHERE `id` = '$rows'";
                                $result1 = mysql_query($sql1, $conn) or die(mysql_error($conn));
                                $newArray = mysql_fetch_array($result1);
#					echo $nn."<BR>";
                                $otx	= $newArray["otx"];
                                $btx	= $newArray["btx"];
                                $nt		= $newArray['nt'];
                                $label	= $newArray['label'];
                                $signal	= $newArray['sig'];
                                $aps[$nn]	= array(
                                                                        'id'		=>	$ap_array['id'],
                                                                        'ssid'		=>	$ssid_t,
                                                                        'mac'		=>	$ap_array['mac'],
                                                                        'sectype'	=>	$ap_array['sectype'],
                                                                        'r'			=>	$radio,
                                                                        'radio'		=>	$ap_array['radio'],
                                                                        'chan'		=>	$ap_array['chan'],
                                                                        'man'		=>	$manuf,
                                                                        'type'		=>	$type,
                                                                        'auth'		=>	$auth,
                                                                        'encry'		=>	$encry,
                                                                        'label'		=>	$label,
                                                                        'nt'		=>	$nt,
                                                                        'btx'		=>	$btx,
                                                                        'otx'		=>	$otx,
                                                                        'sig'		=>	$signal
                                                                        );

                                $n			=	1;	# GPS Array KEY -has to start at 1 vistumbler will error out if the first GPS point has a key of 0
                                $sig		=	$aps[$nn]['sig'];
                                $signals	=	explode("-", $sig);
#				echo $sig."<BR>";
                                $table_gps		=	$aps[$nn]['ssid'].'-'.$aps[$nn]['mac'].'-'.$aps[$nn]['sectype'].'-'.$aps[$nn]['radio'].'-'.$aps[$nn]['chan'].$gps_ext;
                #		echo $table_gps."\r\n";
                                foreach($signals as $key=>$val)
                                {
                                        $sig_exp = explode(",", $val);
                                        $gps_id	= $sig_exp[0];

                                        $sql1 = "SELECT * FROM `wifi_st`.`$table_gps` WHERE `id` = '$gps_id'";
                                        $result1 = mysql_query($sql1, $conn) or die(mysql_error($conn));
                                        $gps_table = mysql_fetch_array($result1);
                                        $gps_array[$n]	=	array(
                                                                                        "lat" => $gps_table['lat'],
                                                                                        "long" => $gps_table['long'],
                                                                                        "sats" => $gps_table['sats'],
                                                                                        "hdp" => $gps_table['hdp'],
                                                                                        "alt" => $gps_table['alt'],
                                                                                        "geo" => $gps_table['geo'],
                                                                                        "kmh" => $gps_table['kmh'],
                                                                                        "mph" => $gps_table['mph'],
                                                                                        "track" => $gps_table['track'],
                                                                                        "date" => $gps_table['date'],
                                                                                        "time" => $gps_table['time']
                                                                                        );
                                        $n++;
                                        $signals[] = $n.",".$sig_exp[1];
                                }
                                $sig_new = implode("-", $signals);
                                $aps[$nn]['sig'] = $sig_new;
                                echo $nn."-".$n."==";
                                unset($signals);
                        }
                break;

                case "exp_all_db_vs1":
                        $n	=	1;
                        $nn	=	1; # AP Array key
                        if($screen == "HTML"){echo '<table><tr class="style4"><th style="border-style: solid; border-width: 1px">Start of WiFi DB export to VS1</th></tr>';}
                        $sql_		= "SELECT * FROM `wifi`.`wifi_pointers`";
                        $result_	= mysql_query($sql_, $conn) or die(mysql_error($conn));
                        while($ap_array = mysql_fetch_array($result_))
                        {
                                $manuf = $this->findManuf($ap_array['mac']);
                                switch($ap_array['sectype'])
                                        {
                                                case 1:
                                                        $type = "#openStyleDead";
                                                        $auth = "Open";
                                                        $encry = "None";
                                                        break;
                                                case 2:
                                                        $type = "#wepStyleDead";
                                                        $auth = "Open";
                                                        $encry = "WEP";
                                                        break;
                                                case 3:
                                                        $type = "#secureStyleDead";
                                                        $auth = "WPA-Personal";
                                                        $encry = "TKIP-PSK";
                                                        break;
                                        }
                                switch($ap_array['radio'])
                                        {
                                                case "a":
                                                        $radio="802.11a";
                                                        break;
                                                case "b":
                                                        $radio="802.11b";
                                                        break;
                                                case "g":
                                                        $radio="802.11g";
                                                        break;
                                                case "n":
                                                        $radio="802.11n";
                                                        break;
                                                default:
                                                        $radio="Unknown Radio";
                                                        break;
                                        }
                                $ssid_edit = html_entity_decode($ap_array['ssid']);
                                list($ssid_t, $ssid_f, $ssid)  = make_ssid($ssid_edit);
                                $table	=	$ssid_t.'-'.$ap_array['mac'].'-'.$ap_array['sectype'].'-'.$ap_array['radio'].'-'.$ap_array['chan'];
                                $sql	=	"SELECT * FROM `wifi_st`.`$table`";
                                $result	=	mysql_query($sql, $conn) or die(mysql_error($conn));
                                $rows	=	mysql_num_rows($result);

                                $sql1 = "SELECT * FROM `wifi_st`.`$table` WHERE `id` = '$rows'";
                                $result1 = mysql_query($sql1, $conn) or die(mysql_error($conn));
                                $newArray = mysql_fetch_array($result1);
#					echo $nn."<BR>";
                                $otx	= $newArray["otx"];
                                $btx	= $newArray["btx"];
                                $nt		= $newArray['nt'];
                                $label	= $newArray['label'];
                                $signal	= $newArray['sig'];
                                $aps[$nn]	= array(
                                                                        'id'		=>	$ap_array['id'],
                                                                        'ssid'		=>	$ap_array['ssid'],
                                                                        'mac'		=>	$ap_array['mac'],
                                                                        'sectype'	=>	$ap_array['sectype'],
                                                                        'r'			=>	$ap_array['radio'],
                                                                        'radio'		=>	$radio,
                                                                        'chan'		=>	$ap_array['chan'],
                                                                        'man'		=>	$manuf,
                                                                        'type'		=>	$type,
                                                                        'auth'		=>	$auth,
                                                                        'encry'		=>	$encry,
                                                                        'label'		=>	$label,
                                                                        'nt'		=>	$nt,
                                                                        'btx'		=>	$btx,
                                                                        'otx'		=>	$otx,
                                                                        'sig'		=>	$signal
                                                                        );
                                $sig		=	$ap['sig'];
                                $signals	=	explode("-", $sig);
#				echo $sig."<BR>";
                                $table_gps		=	$table.$gps_ext;
                                echo $table_gps."\r\n";
                                foreach($signals as $sign)
                                {
                                        $sig_exp = explode(",", $sign);
                                        $gps_id	= $sig_exp[0];

                                        $sql1 = "SELECT * FROM `wifi_st`.`$table_gps` WHERE `id` = '$gps_id'";
                                        $result1 = mysql_query($sql1, $conn) or die(mysql_error($conn));
                                        $gps_table = mysql_fetch_array($result1);
                                        $gps_array[$n]	=	array(
                                                                                        "lat" => $gps_table['lat'],
                                                                                        "long" => $gps_table['long'],
                                                                                        "sats" => $gps_table['sats'],
                                                                                        "hdp" => $gps_table['hdp'],
                                                                                        "alt" => $gps_table['alt'],
                                                                                        "geo" => $gps_table['geo'],
                                                                                        "kmh" => $gps_table['kmh'],
                                                                                        "mph" => $gps_table['mph'],
                                                                                        "track" => $gps_table['track'],
                                                                                        "date" => $gps_table['date'],
                                                                                        "time" => $gps_table['time']
                                                                                        );
                                        $n++;
                                        $signals[] = $n.",".$sig_exp[1];
                                }
                                $sig_new = implode("-", $signals);
                                $aps[$nn]['sig'] = $sig_new;

                                echo $nn."-".$n."==";
                                unset($signals);
                                $nn++;
                        }
                break;
        }
        if(count($aps) > 1 && count($gps_array) > 1)
        {
                $date		=	date('Y-m-d_H-i-s');

                if(@$row != 0)
                {
                        $file_ext	=	$date."_".$title.".vs1";
                }elseif(@$user != "")
                {
                        $file_ext	=	$date."_".$user.".vs1";
                }else
                {
                        $file_ext	=	$date."_FULL_DB.vs1";
                }
                $filename	=	$vs1_out.$file_ext;
        #	echo $filename."\r\n";
                // define initial write and appends
                fopen($filename, "w");
                $fileappend	=	fopen($filename, "a");

                $h1 = "#  Vistumbler VS1 - Detailed Export Version 3.0
# Created By: RanInt WiFiDB ".$ver['wifidb']."
# -------------------------------------------------
# GpsID|Latitude|Longitude|NumOfSatalites|HorizontalDilutionOfPrecision|Altitude(m)|HeightOfGeoidAboveWGS84Ellipsoid(m)|Speed(km/h)|Speed(MPH)|TrackAngle(Deg)|Date(UTC y-m-d)|Time(UTC h:m:s.ms)
# -------------------------------------------------
";
                fwrite($fileappend, $h1);

                foreach( $gps_array as $key=>$gps )
                {
                        $lat	=	$gps['lat'];
                        $long	=	$gps['long'];
                        $sats	=	$gps['sats'];

                        $hdp	= $gps['hdp'];
                        if($hdp == ''){$hdp = 0;}

                        $alt	= $gps['alt'];
                        if($alt == ''){$alt = 0;}

                        $geo	= $gps['geo'];
                        if($geo == ''){$geo = "-0";}

                        $kmh	= $gps['kmh'];
                        if($kmh == ''){$kmh = 0;}

                        $mph	= $gps['mph'];
                        if($mph == ''){$mph = 0;}

                        $track	= $gps['track'];
                        if($track == ''){$track = 0;}

                        $date	=	$gps['date'];
                        $time	=	$gps['time'];
                        $gpsd = $key."|".$lat."|".$long."|".$sats."|".$hdp."|".$alt."|".$geo."|".$kmh."|".$mph."|".$track."|".$date."|".$time."\r\n";
                        fwrite($fileappend, $gpsd);
                }
                $ap_head = "# ---------------------------------------------------------------------------------------------------------------------------------------------------------
# SSID|BSSID|MANUFACTURER|Authetication|Encryption|Security Type|Radio Type|Channel|Basic Transfer Rates|Other Transfer Rates|Network Type|Label|GpsID,SIGNAL
# ---------------------------------------------------------------------------------------------------------------------------------------------------------
";
                fwrite($fileappend, $ap_head);
                foreach($aps as $ap)
                {
                        $apd = $ap['ssid']."|".$ap['mac']."|".$ap['man']."|".$ap['auth']."|".$ap['encry']."|".$ap['sectype']."|".$ap['radio']."|".$ap['chan']."|".$ap['btx']."|".$ap['otx']."|".$ap['nt']."|".$ap['label']."|".$ap['sig']."\r\n";
                        fwrite($fileappend, $apd);
                }
                fclose($fileappend);
                $end 	=	date("H:i:s");
                $GPSS	=	count($gps_array);
                $APSS	=	count($aps);
                $return = array(0=>1, 1=>$file_ext);
                if($screen == "HTML")
                {
                        echo '<tr><td style="border-style: solid; border-width: 1px">Wrote # GPS Points: '.$GPSS.'</td></tr>';
                        echo '<tr><td style="border-style: solid; border-width: 1px">Wrote # Access Points: '.$APSS.'</td></tr>';
                        echo '<tr><td style="border-style: solid; border-width: 1px">Your Vistumbler VS1 file is ready,<BR>you can download it from <a class="links" href="'.$filename.'">Here</a></td><td></td></tr></table>';
                }
        }else
        {
                $return = array(0=>0, 1=>"Empty arrays. :/");
                if($screen == "HTML")
                {
                        echo '<tr><td style="border-style: solid; border-width: 1px">Failed to export.</td><td></td></tr></table>';
                }
        }
        return $return;
    }







    public function UserList($row = 0)
    {
        if($row === 0)
        {
            throw new ErrorException("Row value for export::UserList() is empty.");
            return 0;
        }
        $data = array();
        $sql = "SELECT * FROM `wifi`.`user_imports` WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $row, PDO::PARAM_INT);
        $prep->execute();
        $fetch = $prep->fetch();

        $points = explode("-", $fetch['points']);
        $sql2 = "SELECT * FROM `wifi`.`wifi_pointers` WHERE `id` = ?";
        $prep2 = $this->sql->conn->prepare($sql2);

        $sql3 = "SELECT `wifi_signals`.*, `wifi_gps`.*
                FROM `wifi`.`wifi_signals`
                LEFT JOIN `wifi`.`wifi_gps`
                ON `wifi_gps`.`ap_hash` = `wifi_signals`.`ap_hash`
                WHERE `wifi_signals`.`id` = ?";
        $prep3 = $this->sql->conn->prepare($sql3);
        foreach($points as $point)
        {
            list($id, $new_old) = explode(":", $point);
            #var_dump($id,$new_old);

            $prep2->bindParam(1, $id, PDO::PARAM_INT);
            $prep2->execute();
            $this->sql->checkError();
            $ap_fetch = $prep2->fetch(2);
            $data[$ap_fetch['ap_hash']]['apdata'] = $ap_fetch;
            $prep3->bindParam(1, $ap_fetch['ap_hash']);
            $prep3->execute();
            $sig_gps_data = $prep3->fetchAll(2);
            #var_dump($sig_gps_data);
            $data[$ap_fetch['ap_hash']]['gdata'] = $sig_gps_data;
            var_dump($data);
            die();
        }
        return $data;
    }
}
