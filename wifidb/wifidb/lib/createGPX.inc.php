<?php
/*
createGPX.inc.php, class to create GPX/GPX files
Copyright (C) 2021 Andrew Calcutt

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
class createGPX
{

	public function __construct($URL_PATH)
	{
		$this->URL_BASE	 =   $URL_PATH;
	}

	public function createGPXstructure($alldata)
	{
		$GPX_DATA = '<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:gpxx="http://www.garmin.com/xmlschemas/GpxExtensions/v3"  xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" version="1.1" creator="wifidb.net"><metadata/>'.$alldata."\n</gpx>";
		Return $GPX_DATA;
	}
	
	public function CreateApFeature($ap_info_array, $tc = 0)
	{
		switch($ap_info_array['sectype'])
		{
			case 1:
				$sec_type_colorhex = "#008000";
				break;
			case 2:
				$sec_type_colorhex = "#ffa500";
				break;
			case 3;
				$sec_type_colorhex = "#ff0000";
				break;
			default:
				$sec_type_colorhex = "#008000";
				break;
		}

		$ssid = $this->stripInvalidXml($ap_info_array['ssid']);
		if(isset($ap_info_array['named']) && $ap_info_array['named'] == 1){$named = "<name>".$ssid.'</name>';}else{$named = '<name> </name>';}
		if(isset($ap_info_array['id'])){$id = '<b>SSID: </b><a href="'.$this->URL_BASE.'opt/fetch.php?id='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank">'.$ssid.'</a><br />'."";}else{$id = '';}
		if(isset($ap_info_array['live_id'])){$live_id_ssid = '<b>SSID: </b>'.$this->stripInvalidXml($ap_info_array['ssid']).'<br />'."";}else{$live_id_ssid = '';}
		if(isset($ap_info_array['live_id'])){$live_id = '<b>Live ID: </b>'.$this->stripInvalidXml($ap_info_array['live_id']).'<br />'."";}else{$live_id = '';}
		if(isset($ap_info_array['mac'])){$mac = '<b>Mac: </b>'.$this->stripInvalidXml($ap_info_array['mac']).'<br />'."";}else{$mac = '';}
		if(isset($ap_info_array['chan'])){$chan = '<b>Channel: </b>'.$this->stripInvalidXml($ap_info_array['chan']).'<br />'."";}else{$chan = '';}
		if(isset($ap_info_array['auth'])){$auth = '<b>Authentication: </b>'.$this->stripInvalidXml($ap_info_array['auth']).'<br />'."";}else{$auth = '';}
		if(isset($ap_info_array['encry'])){$encry = '<b>Encryption: </b>'.$this->stripInvalidXml($ap_info_array['encry']).'<br />'."";}else{$encry = '';}
		if(isset($ap_info_array['type'])){$type = '<b>Type: </b>'.$this->stripInvalidXml($ap_info_array['type']).'<br />'."";}else{$type = '';}
		if(isset($ap_info_array['signal'])){$sig = '<b>Signal: </b>'.$this->stripInvalidXml($ap_info_array['signal']).'<br />'."";}else{$sig = '';}
		if(isset($ap_info_array['rssi'])){$rssi = '<b>RSSI: </b>'.$this->stripInvalidXml($ap_info_array['rssi']).'<br />'."";}else{$rssi = '';}
		if(isset($ap_info_array['high_gps_sig'])){$high_gps_sig = '<b>High GPS Signal: </b>'.$this->stripInvalidXml($ap_info_array['high_gps_sig']).'<br />'."";}else{$high_gps_sig = '';}
		if(isset($ap_info_array['high_gps_rssi'])){$high_gps_rssi = '<b>High GPS RSSI: </b>'.$this->stripInvalidXml($ap_info_array['high_gps_rssi']).'<br />'."";}else{$high_gps_rssi = '';}
		if(isset($ap_info_array['manuf'])){$manuf = '<b>Manufacturer: </b>'.$this->stripInvalidXml($ap_info_array['manuf']).'<br />'."";}else{$manuf = '';}
		if(isset($ap_info_array['sectype'])){$sectype = '<b>SecType: </b>'.$this->stripInvalidXml($ap_info_array['sectype']).'<br />'."";}else{$sectype = '';}
		if(isset($ap_info_array['nt'])){$NT = '<b>Network Type: </b>'.$this->stripInvalidXml($ap_info_array['nt']).'<br />'."";}else{$NT = '';}
		if(isset($ap_info_array['radio'])){$radio = '<b>Radio Type: </b>'.$this->stripInvalidXml($ap_info_array['radio']).'<br />'."";}else{$radio = '';}
		if(isset($ap_info_array['btx'])){$BTx = '<b>BTx: </b>'.$this->stripInvalidXml($ap_info_array['btx']).'<br />'."";}else{$BTx = '';}
		if(isset($ap_info_array['otx'])){$OTx = '<b>OTx: </b>'.$this->stripInvalidXml($ap_info_array['otx']).'<br />'."";}else{$OTx = '';}
		if(isset($ap_info_array['points'])){$points = '<b>Points: </b><a href="'.$this->URL_BASE.'api/export.php?func=exp_ap_sig&id='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank">'.$this->stripInvalidXml($ap_info_array['points']).'</a><br />'."";}else{$points = '';}
		if(isset($ap_info_array['fa'])){$FA = '<b>First Active: </b>'.$this->stripInvalidXml($ap_info_array['fa']).'<br />'."";}else{$FA = '';}
		if(isset($ap_info_array['la'])){$LA = '<b>Last Active: </b>'.$this->stripInvalidXml($ap_info_array['la']).'<br />'."";}else{$LA = '';}
		if(isset($ap_info_array['hist_date'])){$hist_date = '<b>Hist Date: </b>'.$this->stripInvalidXml($ap_info_array['hist_date']).'<br />'."";}else{$hist_date = '';}
		if(isset($ap_info_array['lat'])){$lat = '<b>Latitude: </b>'.$this->stripInvalidXml($ap_info_array['lat']).'<br />'."";}else{$lat = '';}
		if(isset($ap_info_array['lon'])){$lon = '<b>Longitude: </b>'.$this->stripInvalidXml($ap_info_array['lon']).'<br />'."";}else{$lon = '';}
		if(isset($ap_info_array['alt'])){$alt = '<b>Altitude: </b>'.$this->stripInvalidXml($ap_info_array['alt']).'<br />'."";}else{$alt = '';}
		if(isset($ap_info_array['hist_file_id'])){$hist_file_id = '<b>File ID: </b><a href="'.$this->URL_BASE.'opt/userstats.php?func=useraplist&row='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank"">'.$this->stripInvalidXml($ap_info_array['id']).'</a><br />'."";}else{$hist_file_id = '';}
		if(isset($ap_info_array['first_file_id'])){$first_file_id = '<b>File ID: </b><a href="'.$this->URL_BASE.'opt/userstats.php?func=useraplist&row='.$this->stripInvalidXml($ap_info_array['id']).'" target="_blank"">'.$this->stripInvalidXml($ap_info_array['id']).'</a><br />'."";}else{$first_file_id = '';}
		if(isset($ap_info_array['user'])){$user = '<b>User: </b>'.$this->stripInvalidXml($ap_info_array['user']).'<br />'."";}else{$user = '';}
		
		$cdata = $id.$live_id_ssid.$live_id.$mac.$chan.$auth.$type.$encry.$sig.$rssi.$high_gps_sig.$high_gps_rssi.$manuf.$sectype.$NT.$radio.$BTx.$OTx.$points.$FA.$LA.$hist_date.$lat.$lon.$alt.$hist_file_id.$first_file_id.$user;
		$tmp = "\n<wpt lat=\"".$ap_info_array['lat']."\" lon=\"".$ap_info_array['lon']."\">".$named."<desc><![CDATA[".$cdata."]]></desc>";
		if(isset($ap_info_array['hist_date'])){
			$datetime = date('c', strtotime($ap_info_array['hist_date']));
			$tmp .= "<time>".$datetime."</time>";
		}
		if(isset($ap_info_array['alt'])){
			$tmp .= "<ele>".$ap_info_array['alt']."</ele>";
		}
		$tmp .= "<sym>circle</sym><extensions><color>".$sec_type_colorhex."</color></extensions>";
		$tmp .= "</wpt>";
		return $tmp;
	}

	public function CreateApFeatureCollection($ap_feature_array, $tc = 0)
	{
		$layer_source = '';
		foreach($ap_feature_array as $ap_feature)
		{
			$layer_source .=$this->CreateApFeature($ap_feature);
		}
		return $layer_source;
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
			$current = ord($value[$i]);
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