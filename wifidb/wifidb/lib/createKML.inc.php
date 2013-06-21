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
    public function __construct()
    {
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
        $this->data->gdata = array();
        $this->data->network = array();
        $this->data->styles = array();
        $this->compile_data = "";

    }

    public function addFolder($data=array(), $name = "", $open = 1)
    {
        if($data === NULL)
        {
            throw new ErrorException("Name value for createKML::addFolder is empty.");
        }
        if($name === "")
        {
            throw new ErrorException("Name value for createKML::addFolder is empty.");
        }
        if($open === "")
        {
            throw new ErrorException("Open value for createKML::addFolder is empty.");
        }
        $tmp = "<Folder>".$name."\r\n";
        foreach($data as $element)
        {
            $tmp .= $element."\r\n";
        }
        $this->data->folders[] = $tmp."\t\t</Folder>";
    }

    public function addstyle($style = "")
    {
        if($style === "")
        {
            throw new ErrorException("Style value for createKML::addstyle is empty.");
        }
        $this->data->styles[] = $style;
    }

    public function createAP_KML($hash = "")
    {
        if($this->data->apdata === NULL)
        {
            throw new ErrorException("AP data is empty in array for export::createAP_KML");
        }

    }

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
        $gps_string = "";
        foreach($this->data->gdata as $gps)
        {
            $gps_string .= $gps['long'].",".$gps['lat'].",".$gps['alt']."\r\n";
        }
        $tmp = "
        <Placemark id=\"".$this->data->apdata['mac']."_signal\">
            <styleUrl>location</styleUrl>
            <Point id=\"".$this->data->apdata['mac']."_signal_gps\">
                <coordinates>$gps_string</coordinates>
            </Point>
        </Placemark>
        ";
        return $tmp;
    }

    public function createNetworkLink($url = "", $title = "", $visibility = 0, $flytoview = 1, $refreshMode = "onInterval", $refreshInterval = 2)
    {
        $this->data->network[] = '
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
    }

    public function compile_data()
    {

    }

    /*public function createAP($named = 1)
    {
        if($named)
        {
            $name_label = "\r\n            <name>".$ssid_name."</name>";
        }else
        {
            $name_label = "";
        }
        $this->kml_array['ap_array'][] = "
        <Placemark id=\"".$mac."_Label\">'.$name_label.'
            <description>
                <![CDATA[
                    <b>SSID: </b>".$ssid_name."<br />
                    <b>Mac Address: </b>".$mac."<br />
                    <b>Network Type: </b>".$nt."<br />
                    <b>Radio Type: </b>".$radio."<br />
                    <b>Channel: </b>".$ap_array['chan']."<br />
                    <b>Authentication: </b>".$auth."<br />
                    <b>Encryption: </b>".$encry."<br />
                    <b>Basic Transfer Rates: </b>".$btx."<br />
                    <b>Other Transfer Rates: </b>".$otx."<br />
                    <b>First Active: </b>".$fa."<br />
                    <b>Last Updated: </b>".$la."<br />
                    <b>Latitude: </b>".$lat."<br />
                    <b>Longitude: </b>".$long."<br />
                    <b>Manufacturer: </b>".$man."<br />
                    <a href=\"".$this->URL_PATH."/opt/fetch.php?id=".$id."\">WiFiDB Link</a>
                ]]>
            </description>
            <styleUrl>".$type."</styleUrl>
            <Point id=\"".$mac."_GPS\">
                <coordinates>".$long.",".$lat.",".$alt."</coordinates>
            </Point>
        </Placemark>
        ";

    }*/

    public function createFinalKML()
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