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

    public function __construct($core, $tilldead = 2)
    {
        $this->URL_BASE     =   $core->URL_PATH;
        $this->convert      =   $core->convert;
        $this->kml_out      =   $core->kml_out;
        $this->daemon_out   =   $core->daemon_out;
        $this->open_path    =   "https://raw.github.com/RIEI/Vistumbler/master/Vistumbler/Images/open.png";
        $this->wep_path     =   "https://raw.github.com/RIEI/Vistumbler/master/Vistumbler/Images/secure-wep.png";
        $this->secure_path  =   "https://raw.github.com/RIEI/Vistumbler/master/Vistumbler/Images/secure.png";
        $this->SigMapTimeBeforeMarkedDead = $tilldead;
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
        $this->SignalLevelStyle = '
    <Style id="SigCat1">
        <IconStyle>
            <scale>1.2</scale>
        </IconStyle>
        <LineStyle>
            <color>ff0000ff</color>
            <width>2</width>
        </LineStyle>
        <PolyStyle>
            <color>bf0000ff</color>
            <outline>0</outline>
            <opacity>75</opacity>
        </PolyStyle>
    </Style>
    <Style id="SigCat2">
        <IconStyle>
            <scale>1.2</scale>
        </IconStyle>
        <LineStyle>
            <color>ff0055ff</color>
            <width>2</width>
        </LineStyle>
        <PolyStyle>
            <color>bf0055ff</color>
            <outline>0</outline>
            <opacity>75</opacity>
        </PolyStyle>
    </Style>
    <Style id="SigCat3">
        <IconStyle>
            <scale>1.2</scale>
        </IconStyle>
        <LineStyle>
            <color>ff00ffff</color>
            <width>2</width>
        </LineStyle>
        <PolyStyle>
            <color>bf00ffff</color>
            <outline>0</outline>
            <opacity>75</opacity>
        </PolyStyle>
    </Style>
    <Style id="SigCat4">
        <IconStyle>
            <scale>1.2</scale>
        </IconStyle>
        <LineStyle>
            <color>ff01ffc8</color>
            <width>2</width>
        </LineStyle>
        <PolyStyle>
            <color>bf01ffc8</color>
            <outline>0</outline>
            <opacity>75</opacity>
        </PolyStyle>
    </Style>
    <Style id="SigCat5">
        <IconStyle>
            <scale>1.2</scale>
        </IconStyle>
        <LineStyle>
            <color>ff70ff48</color>
            <width>2</width>
        </LineStyle>
        <PolyStyle>
            <color>bf70ff48</color>
            <outline>0</outline>
            <opacity>75</opacity>
        </PolyStyle>
    </Style>
    <Style id="SigCat6">
        <IconStyle>
            <scale>1.2</scale>
        </IconStyle>
        <LineStyle>
            <color>ff3d8c27</color>
            <width>2</width>
        </LineStyle>
        <PolyStyle>
            <color>bf3d8c27</color>
            <outline>0</outline>
            <opacity>75</opacity>
        </PolyStyle>
    </Style>';
        $this->style_data = $this->openstyle.$this->wepstyle.$this->securestyle.$this->tracklinestyle.$this->SignalLevelStyle;
        $this->title = "Untitled";
        $this->users = "WiFiDB";
        $this->data = new stdClass();
        $this->data->apdata = array();
    }


    public function createFolder($name = "", $data = "", $open = 0, $radiofolder = 0)
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
        if($radiofolder)
        {
            $radiofolder = "
            <Style>
				<ListStyle>
					<listItemType>radioFolder</listItemType>            
				</ListStyle>          
            </Style>";
        }else
		{
			$radiofolder = "";
		}
        $tmp = "
        <Folder>$radiofolder
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

    public function PlotAllAPs($WithSignal = 1, $UseRSSI = 0, $named = 0)
    {
        if(!is_int($WithSignal) || $WithSignal > 4 || $WithSignal < 1)
        {
            throw new ErrorException("WithSignal value for createKML::PlotAllAPs is not an integer or of the value 0, 1, 2, or 3.");
        }
        $data = "";
        #$r = 0;
        foreach($this->data->apdata as $key=>$ap)
        {
            switch($WithSignal)
            {
                case 1:
                    $data .= $this->PlotAPpoint($key, $named);
                    break;
                case 2:
                    $data .= $this->PlotAPpoint($key, $named).$this->createFolder($this->PlotAPsignalTrail($key), "Signal Trail", 0);
                    break;
                case 3:
                    $data .= $this->createFolder($this->PlotAPpoint($key, $named).$this->createFolder($this->PlotAPsignal3D($key, $UseRSSI), "3D Signal Trail", 0), dbcore::normalize_ssid($ap['ssid']), 0);
                    break;
                case 4:
                    $data .= $this->createFolder($this->PlotAPpoint($key, $named).$this->createFolder($this->PlotAPsignalTrail($key), "Signal Trail", 0).$this->createFolder($this->PlotAPsignal3D($key, $UseRSSI), "3D Signal Trail", 0), dbcore::normalize_ssid($ap['ssid']), 0);
                    break;
            }
            #$r = dbcore::RotateSpinner($r);
        }
        return $data;
    }

    public function PlotAPpoint($hash = "", $named = 0)
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
        $gps_center = (int) round((count($this->data->apdata[$hash]['gdata'])/2))-1;

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
        if($named)
        {
            $named = "
            <name>".dbcore::normalize_ssid($this->data->apdata[$hash]['ssid'])."</name>";
        }else
        {
            $named = "";
        }
        $tmp = "
        <Placemark id=\"".$this->data->apdata[$hash]['mac']."_Placemark\">$named
            <styleUrl>".$sec_type_label."StyleDead</styleUrl>
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
                    <b>Latitude: </b>".$this->convert->dm2dd($this->data->apdata[$hash]['gdata'][$gps_center]['lat'])."<br />
                    <b>Longitude: </b>".$this->convert->dm2dd($this->data->apdata[$hash]['gdata'][$gps_center]['long'])."<br />
                    <b>Manufacturer: </b>".$this->data->apdata[$hash]['manuf']."<br />
                    <a href=\"".$this->URL_BASE."opt/fetch.php?id=".$this->data->apdata[$hash]['id']."\">WiFiDB Link</a>
                ]]>
            </description>
            <Point id=\"".$this->data->apdata[$hash]['mac']."_signal_gps\">
                <coordinates>".$this->convert->dm2dd($this->data->apdata[$hash]['gdata'][$gps_center]['long']).",".$this->convert->dm2dd($this->data->apdata[$hash]['gdata'][$gps_center]['lat']).",".$this->data->apdata[$hash]['gdata'][$gps_center]['alt']."</coordinates>
            </Point>
        </Placemark>";
        return $tmp;
    }

    public function PlotAPsignalTrail($hash = "")
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

        $tmp = "";
        $LastTimeInt = 0;
        $SigData = 0;
        $ExpString = "";

        foreach($this->data->apdata[$hash]['gdata'] as $gps)
        {
            $LastSigData = $SigData;
            $SigData = 1;
            $string = str_replace("-", "/", $gps['date'])." ".$gps['time'];
            $NewTimeInt = strtotime($string);

            $cal = ($NewTimeInt - $LastTimeInt);
            if($cal < 0)
            {
                $cal = -1*$cal;
            }
            If(($cal > $this->SigMapTimeBeforeMarkedDead) OR $LastSigData == 0)
            {
                if($LastSigData == 1)
                {
                    $tmp .= '</coordinates>
                        </LineString>
                    </Placemark>';
                }
                $tmp .= '<Placemark id="'.$this->data->apdata[$hash]['mac'].'_signal_trail">
            <styleUrl>Location</styleUrl>
            <LineString>
                <altitudeMode>relative</altitudeMode>
                <coordinates>';
                If($ExpString <> '' AND $cal <= $this->SigMapTimeBeforeMarkedDead)
                {
                    $tmp .= $ExpString;
                }
            }
            $gps_coords = $this->convert->dm2dd($gps['long']).",".$this->convert->dm2dd($gps['lat']);
            $ExpString = "
                    ".$gps_coords.",0";

            $tmp .= $ExpString;
            $LastTimeInt = $NewTimeInt;
        }
        if($tmp != "\r\n , ,")
        {
            $tmp .= '
                    </coordinates>
                </LineString>
            </Placemark>';
        }
        $ret = $tmp;
        return $ret;
    }

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
        $SigData = 0;
        $ExpString = "";

        foreach($this->data->apdata[$hash]['gdata'] as $gps)
        {
            $LastSigData = $SigData;
            $SigData = 1;
            $string = str_replace("-", "/", $gps['date'])." ".$gps['time'];
            $NewTimeInt = strtotime($string);
            $signal = (int) $gps['signal'];
            if($signal >= 0 And $signal <= 16)
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

            $cal = ($NewTimeInt - $LastTimeInt);
            if($cal < 0)
            {
                $cal = -1*$cal;
            }
            If($LastSigStrengthLevel <> $SigStrengthLevel OR ($cal > $this->SigMapTimeBeforeMarkedDead) OR $LastSigData == 0)
            {
                if($LastSigData == 1)
                {
                    $tmp .= '</coordinates>
                        </LineString>
                    </Placemark>';
                }
                $tmp .= '<Placemark>
                <styleUrl>'.$SigCat.'</styleUrl>
                <LineString>
                    <extrude>1</extrude>
                    <tessellate>0</tessellate>
                    <altitudeMode>relativeToGround</altitudeMode>
                    <coordinates>';
                If($ExpString <> '' AND $cal <= $this->SigMapTimeBeforeMarkedDead)
                {
                    $tmp .= $ExpString;
                }
            }
            $gps_coords = $this->convert->dm2dd($gps['long']).",".$this->convert->dm2dd($gps['lat']);
            if($UseRSSI == 1)
            {
                $ExpRSSIAlt = (100 + $gps['rssi'])."";
                $ExpString = "
                    ".$gps_coords.",".$ExpRSSIAlt;
            }else
            {
                $ExpString = "
                    ".$gps_coords.",".$gps['signal'];
            }
            $tmp .= $ExpString;
            $LastSigStrengthLevel = $SigStrengthLevel;
            $LastTimeInt = $NewTimeInt;
        }
        if($tmp != "\r\n , ,")
        {
            $tmp .= '
                    </coordinates>
                </LineString>
            </Placemark>';
        }
        $ret = $tmp;
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


    public function ClearData()
    {
        $this->data->apdata = array();
    }

    /**
     * @throws ErrorException
     */
    public function createKML($filename, $title, $alldata)
    {
        if($filename === "")
        {
            throw new ErrorException("Filename for export::createFinalKML was empty.");
        }
        if($title === "")
        {
            throw new ErrorException("Title for export::createFinalKML was empty.");
        }
        if($alldata === "")
        {
            throw new ErrorException("All AP data string is empty in export::createFinalKML");
        }

        $KML_DATA = $this->createKMLstructure($title, $alldata);
        if(file_put_contents($filename, $KML_DATA))
        {
            return 1;
        }else
        {
            return 0;
        }

    }
	
    public function createKMLstructure($title, $alldata)
    {
        $KML_DATA =
'<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
    <Document>
        <name>'.$title.'</name>
        '.$this->style_data.'
        '.$alldata.'
    </Document>
</kml>';

        Return $KML_DATA;
    }

    /*
     * Create a compressed file from a filename and the destination extention
     */
    public function CreateKMZ($file = "")
    {
        if($file === ""){return -1;}

        #create new kmz filename
        $parts = pathinfo($file);
        $parts_base = $parts['dirname'];
        $parts_name = $parts['filename'];
        $file_create = $parts_base."/".$parts_name.".kmz";

        #Create KMZ zip file
        $zip = new ZipArchive;
        $zip->open($file_create, ZipArchive::CREATE);
        #var_dump($zip->getStatusString());

        $zip->addFile($file, 'doc.kml');
        #var_dump($zip->getStatusString());

        $zip->close();

        if (file_exists($file_create)) {
            return $file_create;
        } else {
            return -2;
        }
    }

}