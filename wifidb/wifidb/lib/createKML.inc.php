<?php
/*
createKML.inc.php, class to create KML/KMZ files
Copyright (C) 2015 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
class createKML
{

	public function __construct($URL_PATH, $kml_out, $daemon_out, $tilldead = 5, $convertObj)
	{
		$this->URL_BASE	 =   $URL_PATH;
		$this->convert	  =   $convertObj;
		$this->kml_out	  =   $kml_out;
		$this->daemon_out   =   $daemon_out;
		$this->open_path	=   $URL_PATH."img/kml/ap/open.png";
		$this->wep_path	 =   $URL_PATH."img/kml/ap/secure-wep.png";
		$this->secure_path  =   $URL_PATH."img/kml/ap/secure.png";
		$this->open_path_dead	=   $URL_PATH."img/kml/ap/open_dead.png";
		$this->wep_path_dead	 =   $URL_PATH."img/kml/ap/secure-wep_dead.png";
		$this->secure_path_dead  =   $URL_PATH."img/kml/ap/secure_dead.png";
		$this->minlodpixels_32	=   $URL_PATH."img/kml/minlodpixels/32.png";
		$this->minlodpixels_64	 =   $URL_PATH."img/kml/minlodpixels/64.png";
		$this->minlodpixels_128  =   $URL_PATH."img/kml/minlodpixels/128.png";
		$this->minlodpixels_512	=   $URL_PATH."img/kml/minlodpixels/512.png";
		$this->minlodpixels_1024	 =   $URL_PATH."img/kml/minlodpixels/1024.png";
		$this->minlodpixels_2048  =   $URL_PATH."img/kml/minlodpixels/2048.png";
		$this->minlodpixels_4096  =   $URL_PATH."img/kml/minlodpixels/4096.png";
		$this->minlodpixels_8192 =   $URL_PATH."img/kml/minlodpixels/8192.png";
		$this->SigMapTimeBeforeMarkedDead = $tilldead;
		$this->PolyStyle = '
		<Style id="default">
			<PolyStyle>
				<fill>0</fill>
			</PolyStyle>
		</Style>';
		$this->folderstyle = '
		<Style id="check">
			<ListStyle>
				<listItemType>check</listItemType>
			</ListStyle>
		</Style>
		<Style id="radio">
			<ListStyle>
				<listItemType>radioFolder</listItemType>
			</ListStyle>
		</Style>';
		$this->openstyle = '
		<Style id="openStyle">
		<IconStyle>
			<scale>.75</scale>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/paddle/grn-blank.png</href>
			</Icon>
			<hotSpot x="32" y="1" xunits="pixels" yunits="pixels"/>
		</IconStyle>
		</Style>';
		$this->wepstyle = '
		<Style id="wepStyle">
		<IconStyle>
			<scale>.75</scale>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/paddle/orange-blank.png</href>
			</Icon>
			<hotSpot x="32" y="1" xunits="pixels" yunits="pixels"/>
		</IconStyle>
		</Style>';
		$this->securestyle = '
		<Style id="secureStyle">
		<IconStyle>
			<scale>.75</scale>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/paddle/red-blank.png</href>
			</Icon>
			<hotSpot x="32" y="1" xunits="pixels" yunits="pixels"/>
		</IconStyle>
		</Style>';
		$this->openstyledead = '
		<Style id="openStyleDead">
		<IconStyle>
			<scale>.75</scale>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/paddle/grn-circle.png</href>
			</Icon>
			<hotSpot x="32" y="1" xunits="pixels" yunits="pixels"/>
		</IconStyle>
		</Style>';
		$this->wepstyledead = '
		<Style id="wepStyleDead">
		<IconStyle>
			<scale>.75</scale>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/paddle/orange-circle.png</href>
			</Icon>
			<hotSpot x="32" y="1" xunits="pixels" yunits="pixels"/>
		</IconStyle>
		</Style>';
		$this->securestyledead = '
		<Style id="secureStyleDead">
		<IconStyle>
			<scale>.75</scale>
			<Icon>
				<href>http://maps.google.com/mapfiles/kml/paddle/red-circle.png</href>
			</Icon>
			<hotSpot x="32" y="1" xunits="pixels" yunits="pixels"/>
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
				<outline>1</outline>
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
				<outline>1</outline>
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
				<outline>1</outline>
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
				<outline>1</outline>
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
				<outline>1</outline>
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
				<outline>1</outline>
				<opacity>75</opacity>
			</PolyStyle>
		</Style>';
		$this->style_data = $this->PolyStyle.$this->folderstyle.$this->openstyle.$this->wepstyle.$this->securestyle.$this->openstyledead.$this->wepstyledead.$this->securestyledead.$this->tracklinestyle.$this->SignalLevelStyle;
		$this->title = "Untitled";
		$this->users = "WiFiDB";
		$this->data = new stdClass();
		$this->data->apdata = array();
		$this->data->placemarks = array();
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

	public function createFolder($name = "", $data = "", $open = 0, $radiofolder = 0, $visible = 1)
	{
		if($name != "")
		{
			$name = $this->stripInvalidXml($name);
			$name = "
			<name>$name</name>";
		}
		if($radiofolder == 1)
		{
			$radiofolder = "
			<styleUrl>#radio</styleUrl>";
		}else
		{
		   $radiofolder = "
			<styleUrl>#check</styleUrl>";
		}
		if($visible == 1)
		{
			$visible = "
			<visibility>1</visibility>";
		}
		else
		{
			$visible = "
			<visibility>0</visibility>";
		}
		
		$tmp = "<Folder>$visible$radiofolder$name
			<open>$open</open>
			$data
		</Folder>";
		return $tmp;
	}

	public function createNetworkLink($url = "", $title = "", $visibility = 0, $flytoview = 1, $refreshMode = "onInterval", $refreshInterval = 2, $radiofolder = 0, $regionkml = "")
	{
		if($radiofolder == 1)
		{
			$radiofolder = "
			<styleUrl>#radio</styleUrl>";
		}else
		{
		   $radiofolder = "
			<styleUrl>#check</styleUrl>";
		}
		$title = $this->stripInvalidXml($title);
		$tmp = '
		<NetworkLink>'.$radiofolder.$regionkml.'
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

	public function CreateApFeature($ap_info_array)
	{
		switch($ap_info_array['sectype'])
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
		
		if($ap_info_array['new_ap']){$icon_style = $sec_type_label."Style";}else{$icon_style = $sec_type_label."StyleDead";}
		
		$ssid = dbcore::formatSSID($this->stripInvalidXml($ap_info_array['ssid']));
		if(isset($ap_info_array['named']) && $ap_info_array['named'] == 1){$named = "\n			<name>".$ssid.'</name>';}else{$named = '';}
		if(isset($ap_info_array['id'])){$id = '				<b>SSID: </b><a href="'.$this->URL_BASE.'opt/fetch.php?id='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank">'.$ssid.'</a><br />'."\n";}else{$id = '';}
		if(isset($ap_info_array['live_id'])){$live_id_ssid = '				<b>SSID: </b>'.$this->stripInvalidXml($ap_info_array['ssid']).'<br />'."\n";}else{$live_id_ssid = '';}
		if(isset($ap_info_array['live_id'])){$live_id = '				<b>Live ID: </b>'.$this->stripInvalidXml($ap_info_array['live_id']).'<br />'."\n";}else{$live_id = '';}
		if(isset($ap_info_array['mac'])){$mac = '				<b>Mac: </b>'.$this->stripInvalidXml($ap_info_array['mac']).'<br />'."\n";}else{$mac = '';}
		if(isset($ap_info_array['chan'])){$chan = '				<b>Channel: </b>'.$this->stripInvalidXml($ap_info_array['chan']).'<br />'."\n";}else{$chan = '';}
		if(isset($ap_info_array['auth'])){$auth = '				<b>Authentication: </b>'.$this->stripInvalidXml($ap_info_array['auth']).'<br />'."\n";}else{$auth = '';}
		if(isset($ap_info_array['encry'])){$encry = '				<b>Encryption: </b>'.$this->stripInvalidXml($ap_info_array['encry']).'<br />'."\n";}else{$encry = '';}
		if(isset($ap_info_array['signal'])){$sig = '				<b>Signal: </b>'.$this->stripInvalidXml($ap_info_array['signal']).'<br />'."\n";}else{$sig = '';}
		if(isset($ap_info_array['rssi'])){$rssi = '				<b>RSSI: </b>'.$this->stripInvalidXml($ap_info_array['rssi']).'<br />'."\n";}else{$rssi = '';}
		if(isset($ap_info_array['high_gps_sig'])){$high_gps_sig = '				<b>High GPS Signal: </b>'.$this->stripInvalidXml($ap_info_array['high_gps_sig']).'<br />'."\n";}else{$high_gps_sig = '';}
		if(isset($ap_info_array['high_gps_rssi'])){$high_gps_rssi = '				<b>High GPS RSSI: </b>'.$this->stripInvalidXml($ap_info_array['high_gps_rssi']).'<br />'."\n";}else{$high_gps_rssi = '';}
		if(isset($ap_info_array['manuf'])){$manuf = '				<b>Manufacturer: </b>'.$this->stripInvalidXml($ap_info_array['manuf']).'<br />'."\n";}else{$manuf = '';}
		if(isset($ap_info_array['sectype'])){$sectype = '				<b>SecType: </b>'.$this->stripInvalidXml($ap_info_array['sectype']).'<br />'."\n";}else{$sectype = '';}
		if(isset($ap_info_array['NT'])){$NT = '				<b>Network Type: </b>'.$this->stripInvalidXml($ap_info_array['NT']).'<br />'."\n";}else{$NT = '';}
		if(isset($ap_info_array['radio'])){$radio = '				<b>Radio Type: </b>'.$this->stripInvalidXml($ap_info_array['radio']).'<br />'."\n";}else{$radio = '';}
		if(isset($ap_info_array['BTx'])){$BTx = '				<b>BTx: </b>'.$this->stripInvalidXml($ap_info_array['BTx']).'<br />'."\n";}else{$BTx = '';}
		if(isset($ap_info_array['OTx'])){$OTx = '				<b>OTx: </b>'.$this->stripInvalidXml($ap_info_array['OTx']).'<br />'."\n";}else{$OTx = '';}
		if(isset($ap_info_array['points'])){$points = '				<b>Points: </b><a href="'.$this->URL_BASE.'api/export.php?func=exp_list_ap_signal&id='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank">'.$this->stripInvalidXml($ap_info_array['points']).'</a><br />'."\n";}else{$points = '';}
		if(isset($ap_info_array['FA'])){$FA = '				<b>First Active: </b>'.$this->stripInvalidXml($ap_info_array['FA']).'<br />'."\n";}else{$FA = '';}
		if(isset($ap_info_array['LA'])){$LA = '				<b>Last Active: </b>'.$this->stripInvalidXml($ap_info_array['LA']).'<br />'."\n";}else{$LA = '';}
		if(isset($ap_info_array['hist_date'])){$hist_date = '				<b>Hist Date: </b>'.$this->stripInvalidXml($ap_info_array['hist_date']).'<br />'."\n";}else{$hist_date = '';}
		if(isset($ap_info_array['lat'])){$lat = '				<b>Latitude: </b>'.$this->stripInvalidXml($ap_info_array['lat']).'<br />'."\n";}else{$lat = '';}
		if(isset($ap_info_array['lon'])){$lon = '				<b>Longitude: </b>'.$this->stripInvalidXml($ap_info_array['lon']).'<br />'."\n";}else{$lon = '';}
		if(isset($ap_info_array['alt'])){$alt = '				<b>Altitude: </b>'.$this->stripInvalidXml($ap_info_array['alt']).'<br />'."\n";}else{$alt = '';}
		if(isset($ap_info_array['hist_file_id'])){$hist_file_id = '				<b>File ID: </b><a href="'.$this->URL_BASE.'opt/userstats.php?func=useraplist&row='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank"">'.$this->stripInvalidXml($ap_info_array['id']).'</a><br />'."\n";}else{$hist_file_id = '';}
		if(isset($ap_info_array['first_file_id'])){$first_file_id = '				<b>File ID: </b><a href="'.$this->URL_BASE.'opt/userstats.php?func=useraplist&row='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank"">'.$this->stripInvalidXml($ap_info_array['id']).'</a><br />'."\n";}else{$first_file_id = '';}
		if(isset($ap_info_array['user'])){$user = '				<b>User: </b>'.$this->stripInvalidXml($ap_info_array['user']).'<br />'."\n";}else{$user = '';}

		$tmp = "\n		<Placemark id=\"".$ssid."_".$ap_info_array['mac']."_Placemark\">$named
			<styleUrl>".$icon_style."</styleUrl>
			<description>
				<![CDATA[
".$id.$live_id_ssid.$live_id.$mac.$chan.$auth.$encry.$signal.$rssi.$high_gps_sig.$high_gps_rssi.$manuf.$sectype.$NT.$radio.$BTx.$OTx.$points.$FA.$LA.$hist_date.$lat.$lon.$alt.$hist_file_id.$first_file_id.$user."				]]>
			</description>
			<Point id=\"".$ssid."_".$ap_info_array['mac']."_gps\">
				<coordinates>".$ap_info_array['lon'].",".$ap_info_array['lat'].",".$ap_info_array['alt']."</coordinates>
			</Point>
		</Placemark>";
		return $tmp;
	}

	public function CreateApFeatureCollection($ap_feature_array)
	{
		$layer_source = '';
		foreach($ap_feature_array as $ap_feature)
		{
			$layer_source .=$this->CreateApFeature($ap_feature);
		}
		return $layer_source;
	}

	public function CreateApPlacemark($ap_info_array)
	{
		switch($ap_info_array['sectype'])
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

		$ap_ssid = dbcore::formatSSID($this->stripInvalidXml($ap_info_array['ssid']));
		if($ap_info_array['named'])
		{	
			$named = "			<name>".$ap_ssid."</name>";
		}else
		{
			$named = "";
		}

		if($ap_info_array['new_ap'])
		{
			$icon_style = $sec_type_label."Style";
		}else
		{
			$icon_style = $sec_type_label."StyleDead";
		}

		$tmp = "<Placemark id=\"".$ap_info_array['mac']."_Placemark\">$named
			<styleUrl>".$icon_style."</styleUrl>
			<description>
				<![CDATA[
					<b>SSID: </b>".$ap_ssid."<br />
					<b>Mac Address: </b>".$ap_info_array['mac']."<br />
					<b>Network Type: </b>".$ap_info_array['NT']."<br />
					<b>Radio Type: </b>".$ap_info_array['radio']."<br />
					<b>Channel: </b>".$ap_info_array['chan']."<br />
					<b>Authentication: </b>".$ap_info_array['auth']."<br />
					<b>Encryption: </b>".$ap_info_array['encry']."<br />
					<b>Basic Transfer Rates: </b>".$ap_info_array['BTx']."<br />
					<b>Other Transfer Rates: </b>".$ap_info_array['OTx']."<br />
					<b>First Active: </b>".$ap_info_array['FA']."<br />
					<b>Last Updated: </b>".$ap_info_array['LA']."<br />
					<b>Latitude: </b>".$ap_info_array['lat']."<br />
					<b>Longitude: </b>".$ap_info_array['lon']."<br />
					<b>Manufacturer: </b>".$ap_info_array['manuf']."<br />
					<a href=\"".$this->URL_BASE."opt/fetch.php?id=".$ap_info_array['id']."\">WiFiDB Link</a>
				]]>
			</description>
			<Point id=\"".$ap_info_array['mac']."_signal_gps\">
				<coordinates>".$ap_info_array['lon'].",".$ap_info_array['lat'].",".$ap_info_array['alt']."</coordinates>
			</Point>
		</Placemark>";
		return $tmp;
	}

	public function CreateApSignal3D($signal_array = array(), $visible = 1, $UseRSSI = 1)
	{
		$tmp = "";
		$NewTimeInt = -1;
		$SigStrengthLevel = 0;
		$SigData = 0;
		$ExpString = "";

		foreach($signal_array as $gps)
		{
			$signal = (int) $gps['Sig'];		
			$LastTimeInt = $NewTimeInt;
			$string = str_replace("-", "/", $gps['GPS_Date']);
			$NewTimeInt = strtotime($string);
			If($LastTimeInt == -1){$LastTimeInt = $NewTimeInt;}
			$LastSigStrengthLevel = $SigStrengthLevel;
			$LastSigData = $SigData;
			$SigData = 1;
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

			
			if(($LastSigStrengthLevel <> $SigStrengthLevel) OR (($NewTimeInt - $LastTimeInt) > $this->SigMapTimeBeforeMarkedDead) OR $LastSigData == 0)
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
				If($ExpString <> '' AND (($NewTimeInt - $LastTimeInt) <= $this->SigMapTimeBeforeMarkedDead)){$tmp .= $ExpString;}
			}		
			
			$gps_coords = $this->convert->dm2dd($gps['Lon']).",".$this->convert->dm2dd($gps['Lat']);
			if($UseRSSI == 1)
			{
				$ExpRSSIAlt = (100 + $gps['RSSI'])."";
				$ExpString = "
					".$gps_coords.",".$ExpRSSIAlt;
			}else
			{
				$ExpString = "
					".$gps_coords.",".$gps['Sig'];
			}
			$tmp .= $ExpString;
		}
		if($tmp != "")
		{
			$tmp .= '
					</coordinates>
				</LineString>
			</Placemark>';
		}
		$ret = $tmp;
		return $ret;
	}

	public function PlotRegionBox($box, $idName = '')
	{
		#var_dump($box, $this->convert->dm2dd($box[0]));
		if($idName != "")
		{
			$idLabel = 'id="'.$idName.'"';
		}
		else
		{
			$idLabel = 'id="'.uniqid().'"';
		}
		$data = '<Region '.$idLabel.'>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
					<minLodPixels>'.$box[4].'</minLodPixels>
					<maxLodPixels>'.$box[5].'</maxLodPixels>
					<minFadeExtent>0</minFadeExtent>
					<maxFadeExtent>0</maxFadeExtent>
				</Lod>
			</Region>';
  
		#var_dump($data);
		return $data;
	}
	
	public function minLodPixels($box, $visible = 0)
	{
		$data = '  <Folder>
			<visibility>'.$visible.'</visibility>
			<name>minLodPixels</name>
			<open>1</open>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>32</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>32</minLodPixels>
				  <maxLodPixels>64</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_32.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>64</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>64</minLodPixels>
				  <maxLodPixels>128</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_64.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>128</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>128</minLodPixels>
				  <maxLodPixels>512</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_128.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>512</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>512</minLodPixels>
				  <maxLodPixels>1024</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_512.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>1024</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>1024</minLodPixels>
				  <maxLodPixels>2048</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_1024.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>2048</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>2048</minLodPixels>
				  <maxLodPixels>4096</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_2048.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>4096</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>4096</minLodPixels>
				  <maxLodPixels>8192</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_4096.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

			<ScreenOverlay>
			  <visibility>0</visibility>
			  <name>8192</name>
			  <Region>
				<LatLonAltBox>
					<north>'.$box[0].'</north>
					<south>'.$box[1].'</south>
					<east>'.$box[2].'</east>
					<west>'.$box[3].'</west>
				</LatLonAltBox>
				<Lod>
				  <minLodPixels>8192</minLodPixels>
				  <maxLodPixels>-1</maxLodPixels>
				</Lod>
			  </Region>
			  <Icon>
				<href>'.$this->minlodpixels_8192.'</href>
			  </Icon>
			  <overlayXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <screenXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <rotationXY x="0" y="0" xunits="fraction" yunits="fraction"/>
			  <size x="0" y="1" xunits="fraction" yunits="fraction"/>
			</ScreenOverlay>

		  </Folder>
		  <Placemark>
			<visibility>0</visibility>
			<name>BBOX</name>
			<LineString>
			  <tessellate>1</tessellate>
			  <coordinates>
				'.$box[3].','.$box[0].'
				'.$box[2].','.$box[0].'
				'.$box[2].','.$box[1].'
				'.$box[3].','.$box[1].'
				'.$box[3].','.$box[0].'
			  </coordinates>
			</LineString>
		  </Placemark>';
		  
		return $data;
	}
	

	public function PlotBoundary($bounds = array())
	{
		list($North, $South, $East, $West) = explode(",", $bounds['box']);
		$placemark = '		<Placemark>
			<name>'.$bounds['name'].'</name>
			<styleUrl>#default</styleUrl>
			<Polygon>
				<outerBoundaryIs>
					<LinearRing>
						<coordinates>'.$bounds['polygon'].'</coordinates>
					</LinearRing>
				</outerBoundaryIs>
			</Polygon>
			<Region>
				<LatLonAltBox>
					<north>'.$this->convert->all2dm($this->convert->dm2dd($North)).'</north>
					<south>'.$this->convert->all2dm($this->convert->dm2dd($South)).'</south>
					<east>'.$this->convert->all2dm($this->convert->dm2dd($East)).'</east>
					<west>'.$this->convert->all2dm($this->convert->dm2dd($West)).'</west>
					<minAltitude>0</minAltitude>
					<maxAltitude>'.$bounds['distance'].'</maxAltitude>
				</LatLonAltBox>
				<Lod>
					<minLodPixels>'.$bounds['minLodPix'].'</minLodPixels>
					<maxLodPixels>-1</maxLodPixels>
					<minFadeExtent>0</minFadeExtent>
					<maxFadeExtent>0</maxFadeExtent>
				</Lod>
			</Region>
		</Placemark>
		';
		return $placemark;
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
		$file_create = $parts_base."/".$parts_name.".zip";

		#Create KMZ zip file
		$zip = new ZipArchive();
		$zip->open($file_create, ZipArchive::CREATE);
		#var_dump("FileCreate: ".$file_create);
		#var_dump($zip->getStatusString());
		$zip->addFile($file, 'doc.kml');
		#var_dump($zip->getStatusString());
		$zip->close();
		$new_filename = $parts_base."/".$parts_name.".kmz";
		rename($file_create, $new_filename);
		if (file_exists($new_filename)) {
			return $new_filename;
		} else {
			return -2;
		}
	}
	
	/**
	 * Removes invalid XML
	 *
	 * @access public
	 * @param string $value
	 * @return string
	 */
	function stripInvalidXml($value)
	{
		$ret = "";
		$current;
		if (empty($value)) 
		{
			return $ret;
		}

		$length = strlen($value);
		for ($i=0; $i < $length; $i++)
		{
			$current = ord($value{$i});
			if (($current == 0x9) ||
				($current == 0xA) ||
				($current == 0xD) ||
				(($current >= 0x20) && ($current <= 0xD7FF)) ||
				(($current >= 0xE000) && ($current <= 0xFFFD)) ||
				(($current >= 0x10000) && ($current <= 0x10FFFF)))
			{
				$ret .= chr($current);
			}
			else
			{
				$ret .= " ";
			}
		}
		$ret = str_replace(array('&', '<', '>', '\'', '"'), array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'), $ret);
		return $ret;
	}
}