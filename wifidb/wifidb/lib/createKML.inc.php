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
     */
    public function __construct($url_base, $convert)
    {
        $this->URL_BASE = $url_base;
        $this->convert = $convert;
        $this->open_path = "https://github.com/RIEI/Vistumbler/tree/master/Vistumbler/Images/open.png";
        $this->wep_path = "https://github.com/RIEI/Vistumbler/tree/master/Vistumbler/Images/secure-wep.png";
        $this->secure_path = "https://github.com/RIEI/Vistumbler/tree/master/Vistumbler/Images/secure.png";
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
    public function createFolder($data=array(), $name = "", $open = 1)
    {
        if($data === NULL)
        {
            throw new ErrorException("Name value for createKML::addFolder is empty.");
        }
        if($name === "")
        {
            throw new ErrorException("Name value for createKML::addFolder is empty.");
        }
        if(!is_int($open))
        {
            throw new ErrorException("Open value for createKML::addFolder is not an integer.");
        }
        $tmp = "<Folder>".$name."\r\n";
        foreach($data as $element)
        {
            $tmp .= $element."\r\n";
        }
        $temp = $tmp."\t\t</Folder>";
        return $temp;
    }

    /**
     * @param null $file_name
     * @throws ErrorException
     */
    public function createKML($file_name = NULL)
    {
        if($this->data->apdata === NULL)
        {
            throw new ErrorException("AP data is empty in array for export::createAP_KML");
        }
        if($file_name === NULL)
        {
            throw new ErrorException("Filename variable is empty createKML::createAP_KML");
        }

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
        foreach($this->data->apdata as $key=>$ap)
        {
            switch($WithSignal)
            {
                case 0:
                    $data .= $this->PlotAPpoint($key)."\r\n";
                    break;
                case 1:
                    $data .= $this->PlotAPpoint($key)."\r\n".$this->PlotAPsignal($key);
                    break;
                case 2:
                    $data .= $this->PlotAPpoint($key)."\r\n".$this->PlotAPsignalTrail($key);
                    break;
                case 3:
                    $data .= $this->PlotAPpoint($key)."\r\n".$this->PlotAPsignal3D($key);
                    break;
            }
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
            throw new ErrorException("AP Hash pointer for createKML::PlotAPsignal is empty.");
        }
        if($this->data->gdata === NULL)
        {
            throw new ErrorException("gdata element in the data object for createKML::PlotAPsignal is empty");
        }
        if($this->data->apdata === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPsignal is empty");
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
        <Placemark id=\"".$this->data->apdata[$hash]['mac']."_signal_$key\">
            <styleUrl>".$sec_type_label."StyleDead</styleUrl>
            <name>".$this->data->apdata[$hash]['ssid']."</name>
            <description>
                <![CDATA[
                    <b>SSID: </b>".$this->data->apdata[$hash]['ssid']."<br />
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
            <Point id=\"".$this->data->apdata[$hash]['mac']."_signal_gps_$key\">
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
    public function PlotAPsignal($hash = "")
    {
        if($hash === "")
        {
            throw new ErrorException("AP Hash pointer for createKML::PlotAPsignal is empty.");
        }
        if($this->data->gdata === NULL)
        {
            throw new ErrorException("gdata element in the data object for createKML::PlotAPsignal is empty");
        }
        if($this->data->apdata === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPsignal is empty");
        }
        $tmp = "";
        foreach($this->data->gdata as $key=>$gps)
        {
            $tmp .= "
        <Placemark id=\"".$this->data->apdata['mac']."_signal_$key\">
            <styleUrl>location</styleUrl>
            <name>".$this->data->apdata[$hash]['ssid']." Signal</name>
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
    public function PlotAPsignalTrail($hash = "")
    {
        if($hash === "")
        {
            throw new ErrorException("AP Hash pointer for createKML::PlotAPsignal is empty.");
        }
        if($this->data->gdata === NULL)
        {
            throw new ErrorException("gdata element in the data object for createKML::PlotAPsignal is empty");
        }
        if($this->data->apdata === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPsignal is empty");
        }
        $tmp = "

            ";
        foreach($this->data->gdata as $key=>$gps)
        {
            $tmp .= "";
        }
        $ret = "
        <Placemark id=\"".$this->data->apdata['mac']."_signal_trail\">
            <styleUrl>location</styleUrl>
            <name>".$this->data->apdata[$hash]['ssid']." Signal trail</name>
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
    public function PlotAPsignal3D($hash = "")
    {
        if($hash === "")
        {
            throw new ErrorException("AP Hash pointer for createKML::PlotAPsignal is empty.");
        }
        if($this->data->gdata === NULL)
        {
            throw new ErrorException("gdata element in the data object for createKML::PlotAPsignal is empty");
        }
        if($this->data->apdata === NULL)
        {
            throw new ErrorException("apdata element in the data object for createKML::PlotAPsignal is empty");
        }
        $tmp = "";
        foreach($this->data->apdata[$hash]->gdata as $key=>$gps)
        {
            $signal = (int) $gps['signal'];
            If($signal >= 1 And $signal <= 16)
            {
                $SigStrengthLevel = 1
                $SigCat = '#SigCat1'
            } elseif($signal >= 17 And $signal <= 32)
            {
                $SigStrengthLevel = 2
                $SigCat = '#SigCat2'
            } elseif $signal >= 33 And $signal <= 48)
                $SigStrengthLevel = 3
                $SigCat = '#SigCat3'
            } elseif $signal >= 49 And $signal <= 64)
            {
                $SigStrengthLevel = 4
                $SigCat = '#SigCat4'
            } elseif $signal >= 65 And $signal <= 80)
            {
                $SigStrengthLevel = 5
                $SigCat = '#SigCat5'
            } elseif($signal >= 80 And $signal <= 100)
            {
                $SigStrengthLevel = 6
                $SigCat = '#SigCat6'
            }
            $tmp .= "
                    ".$this->convert->dm2dd($gps['lat']).",".$this->convert->dm2dd($gps['long']).",".$gps['signal'];


            $lastSignalLevel = $SigStrengthLevel;
        }
        $ret = "
        <Placemark id=\"".$this->data->apdata['mac']."_signal_trail_3D\">
            <styleUrl>#SigCat1</styleUrl>
            <name>".$this->data->apdata[$hash]['ssid']." Signal Trail 3D</name>
            <LineString>
                <extrude>1</extrude>
                <tessellate>1</tessellate>
                <altitudeMode>relativeToGround</altitudeMode>
                <coordinates>
                    $tmp
                </coordinates>
            </LineString>
        </Placemark>";
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
     *
     */
    public function compile_data()
    {

    }


    /**
     * @throws ErrorException
     */
    public function createKML()
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
        <name>'.$this->title.'</name>
        '.$this->style_data.'
        '.$this->data.'
';
    }


}