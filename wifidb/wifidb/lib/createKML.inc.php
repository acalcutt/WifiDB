<?php
/**
 * Created by JetBrains PhpStorm.
 * User: sysferland
 * Date: 6/2/13
 * Time: 6:35 PM
 * To change this template use File | Settings | File Templates.
 */

class createKML
{
    /**
     * @param $url_base
     * @param $convert
     * @param $tilldead
     */
    public function __construct($core, &$convert, $tilldead = 2)
    {
        $this->URL_BASE     =   $core->URL_PATH;
        $this->convert      =   $convert;
        $this->vs1_out      =   $core->vs1_out;
        $this->open_path    =   "https://github.com/RIEI/Vistumbler/tree/master/Vistumbler/Images/open.png";
        $this->wep_path     =   "https://github.com/RIEI/Vistumbler/tree/master/Vistumbler/Images/secure-wep.png";
        $this->secure_path  =   "https://github.com/RIEI/Vistumbler/tree/master/Vistumbler/Images/secure.png";
        $this->SigMapTimeBeforeMarkedDead = $tilldead;

        $this->kml_array = array();
        $this->kml_array['ap_array'] = array();
        $this->kml_array['ap_siganl_array'] = array();
        $this->openstyle = '
        <Style id="openStyleDead">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>'.$this->open_path.'</href>
                </Icon>
            </IconStyle>
        </Style>';
        $this->wepstyle = '
        <Style id="wepStyleDead">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>'.$this->wep_path.'</href>
                </Icon>
            </IconStyle>
        </Style>';
        $this->securestyle = '
        <Style id="secureStyleDead">
            <IconStyle>
                <scale>0.5</scale>
                <Icon>
                    <href>'.$this->secure_path.'</href>
                </Icon>
            </IconStyle>
        </Style>';
        $this->tracklinestyle = '
        <Style id="Location">
            <LineStyle>
                <color>7f0000ff</color>
                <width>4</width>
            </LineStyle>
        </Style>';
        $this->style_data = $this->openstyle.$this->wepstyle.$this->securestyle.$this->tracklinestyle;
        $this->title = "Untitled";
        $this->users = "WiFiDB";
        $this->data = new stdClass();
        $this->data->apdata = array();
        $this->compile_data = "";
    }

    /**
     * @param array $data
     * @param string $name
     * @param int $open
     * @return string
     * @throws ErrorException
     */
    public function createFolder($data="", $name = "", $open = 1)
    {
        if($data === NULL)
        {
            throw new ErrorException("Name value for createKML::addFolder is empty.");
        }
        if(!is_int($open))
        {
            throw new ErrorException("Open value for createKML::addFolder is not an integer.");
        }
        if($name === "")
        {
            $name = "Unknown";
        }
        $tmp = "
        <Folder>
            <name>$name</name>
            <open>$open</open>
            $data
        </Folder>";
        return $tmp;
    }

    /**
     * @param null $data
     * @return int
     * @throws ErrorException
     */
    public function LoadData($data = NULL)
    {
        if($data === NULL)
        {
            throw new ErrorException("Access Point Data array for createKML::LoadData is empty.");
        }
        $this->data->apdata = $data;
        return 1;
    }

    /**
     * @param int $WithSignal
     * @return string
     * @throws ErrorException
     */
    public function PlotAllAPs($WithSignal = 1)
    {
        if(!is_int($WithSignal) || $WithSignal > 3 || $WithSignal < 0)
        {
            throw new ErrorException("WithSignal value for createKML::PlotAllAPs is not an integer or of the value 0, 1, 2, or 3.");
        }
        $data = "";
        $r = 0;
        foreach($this->data->apdata as $key=>$ap)
        {
            switch($WithSignal)
            {
                case 0:
                    $data .= $this->PlotAPpoint($key);
                    break;
                case 1:
                    $data .= $this->PlotAPpoint($key).$this->createFolder($this->PlotAPsignal($key), "Signal Points");
                    break;
                case 2:
                    $data .= $this->PlotAPpoint($key).$this->createFolder($this->PlotAPsignalTrail($key), "Signal Trail");
                    break;
                case 3:
                    $data .= $this->createFolder($this->PlotAPpoint($key).$this->createFolder($this->PlotAPsignal3D($key), "3D Signal Trail"), dbcore::normalize_ssid($ap['ssid']));
                    break;
            }
            $r = dbcore::RotateSpinner($r);
        }
        return $data;
    }

    /**
     * @param string $hash
     * @return string
     * @throws ErrorException
     */
    public function PlotAPpoint($hash = "")
    {
        if($hash === "")
        {
            throw new ErrorException("AP Hash pointer for createKML::PlotAPpoint is empty.");
        }
        if($this->data->apdata[$hash] === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPpoint is empty");
        }
        if($this->data->apdata[$hash]['gdata'] === NULL)
        {
            throw new ErrorException("gdata element in the data object for createKML::PlotAPpoint is empty");
        }
        $gps_center = round((count($this->data->apdata[$hash]['gdata'])/2));
        $gps = $this->data->apdata[$hash]['gdata'][$gps_center];
        switch($this->data->apdata[$hash]['sectype'])
        {
            case 1:
                $sec_type_label = "open";
                break;
            case 2:
                $sec_type_label = "wep";
                break;
            case 3;
                $sec_type_label = "secure";
                break;
            default:
                $sec_type_label = "open";
                break;
        }
        $tmp = "
        <Placemark id=\"".$this->data->apdata[$hash]['mac']."_Placemark\">
            <styleUrl>".$sec_type_label."StyleDead</styleUrl>
            <name>".dbcore::normalize_ssid($this->data->apdata[$hash]['ssid'])."</name>
            <description>
                <![CDATA[
                    <b>SSID: </b>".dbcore::normalize_ssid($this->data->apdata[$hash]['ssid'])."<br />
                    <b>Mac Address: </b>".$this->data->apdata[$hash]['mac']."<br />
                    <b>Network Type: </b>".$this->data->apdata[$hash]['NT']."<br />
                    <b>Radio Type: </b>".$this->data->apdata[$hash]['radio']."<br />
                    <b>Channel: </b>".$this->data->apdata[$hash]['chan']."<br />
                    <b>Authentication: </b>".$this->data->apdata[$hash]['auth']."<br />
                    <b>Encryption: </b>".$this->data->apdata[$hash]['encry']."<br />
                    <b>Basic Transfer Rates: </b>".$this->data->apdata[$hash]['BTx']."<br />
                    <b>Other Transfer Rates: </b>".$this->data->apdata[$hash]['OTx']."<br />
                    <b>First Active: </b>".$this->data->apdata[$hash]['FA']."<br />
                    <b>Last Updated: </b>".$this->data->apdata[$hash]['LA']."<br />
                    <b>Latitude: </b>".$this->data->apdata[$hash]['lat']."<br />
                    <b>Longitude: </b>".$this->data->apdata[$hash]['long']."<br />
                    <b>Manufacturer: </b>".$this->data->apdata[$hash]['manuf']."<br />
                    <a href=\"".$this->URL_BASE."opt/fetch.php?id=".$this->data->apdata[$hash]['id']."\">WiFiDB Link</a>
                ]]>
            </description>
            <Point id=\"".$this->data->apdata[$hash]['mac']."_signal_gps\">
                <coordinates>".$gps['long'].",".$gps['lat'].",".$gps['alt']."</coordinates>
            </Point>
        </Placemark>
        ";
        return $tmp;
    }


    /**
     * @param string $hash
     * @return string
     * @throws ErrorException
     */
    public function PlotAPsignal($hash = "", $useRSSI = 0)
    {
        if($hash === "")
        {
            throw new ErrorException("AP Hash pointer for createKML::PlotAPsignal is empty.");
        }
        if($this->data->apdata[$hash] === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPsignal is empty");
        }
        if($this->data->apdata[$hash]['gdata'] === NULL)
        {
            throw new ErrorException("gdata element in the data object for createKML::PlotAPsignal is empty");
        }
        switch($this->data->apdata[$hash]['sectype'])
        {
            case 1:
                $sec_type_label = "open";
                break;
            case 2:
                $sec_type_label = "wep";
                break;
            case 3;
                $sec_type_label = "secure";
                break;
            default:
                $sec_type_label = "open";
                break;
        }

        $tmp = "";
        foreach($this->data->apdata[$hash]['gdata'] as $key=>$gps)
        {
            $tmp .= "
        <Placemark id=\"".$this->data->apdata['mac']."_signal_$key\">
            <styleUrl>".$sec_type_label."StyleDead</styleUrl>
            <Point id=\"".$this->data->apdata['mac']."_signal_gps_$key\">
                <coordinates>".$gps['long'].",".$gps['lat'].",".$gps['alt']."</coordinates>
            </Point>
        </Placemark>
        ";
        }
        return $tmp;
    }

    /**
     * @param string $hash
     * @return string
     * @throws ErrorException
     */
    public function PlotAPsignalTrail($hash = "", $useRSSI = 0)
    {
        if($hash === "")
        {
            throw new ErrorException("AP Hash pointer for createKML::PlotAPsignalTrail is empty.");
        }
        if($this->data->apdata[$hash] === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPsignalTrail is empty");
        }
        if($this->data->apdata[$hash]['gdata'] === NULL)
        {
            throw new ErrorException("gdata element in the data object for createKML::PlotAPsignalTrail is empty");
        }
        $tmp = "

            ";
        foreach($this->data['gdata'] as $key=>$gps)
        {
            $tmp .= "";
        }
        $ret = "
        <Placemark id=\"".$this->data->apdata['mac']."_signal_trail\">
            <styleUrl>location</styleUrl>
            <LineString>
                <altitudeMode>absolute</altitudeMode>
                <coordinates>
                $tmp
                </coordinates>
            </LineString>
        </Placemark>
        ";
        return $ret;
    }


    /**
     * @param string $hash
     * @return string
     * @throws ErrorException
     */
    public function PlotAPsignal3D($hash = "", $UseRSSI = 0)
    {
        if($hash === "")
        {
            throw new ErrorException("AP Hash pointer for createKML::PlotAPsignal3D is empty.");
        }
        if($this->data->apdata[$hash] === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPsignal3D is empty");
        }
        if(empty($this->data->apdata[$hash]['gdata']))
        {
            return "";
        }

        $tmp = "";
        $LastTimeInt = 0;
        $LastSigStrengthLevel = 0;
        foreach($this->data->apdata[$hash]['gdata'] as $key=>$gps)
        {
            if($gps['long'] == '0000.0000' || $gps['lat'] == '0000.0000' || $gps['long'] == "" || $gps['lat'] == "" || $gps['date'] == "" || $gps['time'] == "")
            {
                continue;
            }
            $string = str_replace("-", "/", $gps['date'])." ".$gps['time'];
            $NewTimeInt = strtotime($string);
            $signal = (int) $gps['signal'];
            If($signal >= 1 And $signal <= 16)
            {
                $SigStrengthLevel = 1;
                $SigCat = '#SigCat1';
            } elseif($signal >= 17 And $signal <= 32)
            {
                $SigStrengthLevel = 2;
                $SigCat = '#SigCat2';
            } elseif($signal >= 33 And $signal <= 48)
            {
                $SigStrengthLevel = 3;
                $SigCat = '#SigCat3';
            } elseif($signal >= 49 And $signal <= 64)
            {
                $SigStrengthLevel = 4;
                $SigCat = '#SigCat4';
            } elseif($signal >= 65 And $signal <= 80)
            {
                $SigStrengthLevel = 5;
                $SigCat = '#SigCat5';
            } elseif($signal >= 80 And $signal <= 100)
            {
                $SigStrengthLevel = 6;
                $SigCat = '#SigCat6';
            }

            #echo "-----\r\n$LastSigStrengthLevel != $SigStrengthLevel || ($NewTimeInt - $LastTimeInt) > $this->SigMapTimeBeforeMarkedDead\r\n------";

            if(($LastSigStrengthLevel != $SigStrengthLevel || ($NewTimeInt - $LastTimeInt) > $this->SigMapTimeBeforeMarkedDead) && $key != 0)
            {
                #echo "\r\n($NewTimeInt - $LastTimeInt) = ".($NewTimeInt - $LastTimeInt)."\r\n";
                $tmp .= '
                    </coordinates>
                </LineString>
            </Placemark>
            <Placemark>
                <styleUrl>'.$SigCat.'</styleUrl>
                <LineString>
                    <extrude>1</extrude>
                    <tessellate>1</tessellate>
                    <altitudeMode>relativeToGround</altitudeMode>
                    <coordinates>';
            }
            if($key == 0)
            {
                $tmp .= '
            <Placemark>
                <styleUrl>'.$SigCat.'</styleUrl>
                <LineString>
                    <extrude>1</extrude>
                    <tessellate>1</tessellate>
                    <altitudeMode>relativeToGround</altitudeMode>
                    <coordinates>';
            }

            $gps_coords = $this->convert->dm2dd($gps['lat'])." , ".$this->convert->dm2dd($gps['long']);
            var_dump($gps_coords);
            if($gps_coords == " , ")
            {
                var_dump($gps);
                die();
            }
            if($UseRSSI == 1)
            {
                $ExpRSSIAlt = (100 + $gps['rssi'])."";
                $ExpString = "
                    ".$gps_coords." , ".$ExpRSSIAlt;
            }else
            {
                $ExpString = "
                    ".$gps_coords." , ".$gps['signal'];
            }
            $tmp .= $ExpString;
            $LastSigStrengthLevel = $SigStrengthLevel;
            $LastTimeInt = $NewTimeInt;
        }
        if($tmp != ",," || $tmp != "")
        {
            $tmp .= '
                    </coordinates>
                </LineString>
            </Placemark>';
        }
        $ret = $tmp;
        #var_dump($ret);
        return $ret;
    }


    /**
     * @param string $url
     * @param string $title
     * @param int $visibility
     * @param int $flytoview
     * @param string $refreshMode
     * @param int $refreshInterval
     * @return string
     */
    public function createNetworkLink($url = "", $title = "", $visibility = 0, $flytoview = 1, $refreshMode = "onInterval", $refreshInterval = 2)
    {
        $tmp = '
        <NetworkLink>
                <name>'.$title.'</name>
                <visibility>'.$visibility.'</visibility>
                <flyToView>'.$flytoview.'</flyToView>
                <Link>
                        <href>'.$url.'</href>
                        <refreshMode>'.$refreshMode.'</refreshMode>
                        <refreshInterval>'.$refreshInterval.'</refreshInterval>
                </Link>
        </NetworkLink>';
        return $tmp;
    }

    /**
     * @throws ErrorException
     */
    public function createKML($title, $alldata)
    {
        if($title === "")
        {
            throw new ErrorException("Title for export::createFinalKML was empty.");
        }
        if($alldata === "")
        {
            throw new ErrorException("All AP data string is empty in export::createFinalKML");
        }

        $KML_DATA =
'<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
    <Document>
        <name>'.$title.'</name>
        '.$this->style_data.'
        '.$alldata.'
    </Document>
</kml>';
        file_put_contents($this->vsl_out.$title.".kml", $KML_DATA);

echo "File: ".$this->vsl_out.$title.".kml\r\n";
    }


}