<?php
/*
createGeoJSON.inc.php, class to create GeoJSON/GeoJSON files
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
class createGeoJSON
{

	public function __construct($URL_PATH, $GeoJSON_out, $daemon_out, $tilldead = 5, $convertObj)
	{
		$this->URL_BASE	 =   $URL_PATH;
		$this->convert	  =   $convertObj;
		$this->GeoJSON_out	  =   $GeoJSON_out;
		$this->daemon_out   =   $daemon_out;
		$this->title = "Untitled";
		$this->users = "WiFiDB";
		$this->data = new stdClass();
		$this->data->apdata = array();
		$this->data->placemarks = array();
	}

	public function createGeoJSONstructure($alldata)
	{
		$GeoJSON_DATA = '{"type":"FeatureCollection","features":['.$alldata.']}';
		Return $GeoJSON_DATA;
	}

	public function CreateApFeature($ap_info_array)
	{
		
		if($ap_info_array['ssid'] == '')
		{
			$ap_info_array['ssid'] = '[Blank SSID]';
		}
		elseif(!ctype_print($ap_info_array['ssid']))
		{
			$ap_info_array['ssid'] = '['.dbcore::normalize_ssid($ap_info_array['ssid']).']';
		}
		else
		{
			$ap_info_array['ssid'] = dbcore::normalize_ssid($ap_info_array['ssid']);
		}

		$tmp = '{"type":"Feature","tippecanoe":{"maxzoom":19,"minzoom":3},"properties":{"id":"'.$ap_info_array['id'].'","username":"'.$ap_info_array['username'].'","ssid":"'.$ap_info_array['ssid'].'","mac":"'.$ap_info_array['mac'].'","sectype":'.$ap_info_array['sectype'].',"NT":"'.$ap_info_array['NT'].'","radio":"'.$ap_info_array['radio'].'","chan":"'.$ap_info_array['chan'].'","auth":"'.$ap_info_array['auth'].'","encry":"'.$ap_info_array['encry'].'","BTx":"'.$ap_info_array['BTx'].'","OTx":"'.$ap_info_array['OTx'].'","FA":"'.$ap_info_array['FA'].'","LA":"'.$ap_info_array['LA'].'","lat":"'.$ap_info_array['lat'].'","long":"'.$ap_info_array['long'].'","alt":"'.$ap_info_array['alt'].'","manuf":"'.$ap_info_array['manuf'].'"},"geometry":{"type":"Point","coordinates":['.$ap_info_array['long'].','.$ap_info_array['lat'].']}}';

		return $tmp;
	}
	
	
	public function CreateListMapLayer($id, $named=0)
	{
		$layer_sname = "list-".$id;
		$layer_lname = "listl-".$id;
		$layer_source = "	        									map.addSource('".$layer_sname."', {
													type: 'geojson',
													data: 'https://live.wifidb.net/wifidb/api/geojson.php?func=exp_list&row=".$id."&all=1',
													buffer: 0,
												});

												map.addLayer({
													'id': '".$layer_lname."',
													'type': 'circle',
													'source': '".$layer_sname."',
													'paint': {
														'circle-color': {
															property: 'sectype',
															type: 'interval',
															stops: [
																[1, 'green'],
																[2, 'orange'],
																[3, 'red']
															]
														},
														'circle-radius': 3,
														'circle-opacity': 1,
														'circle-blur': 0.5
													}
												});
";

		if ($named) {$layer_source .= $this->CreateLabelLayer($layer_sname);}

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}
	
	public function CreateApMapLayer($id, $named=0)
	{
		$layer_sname = "ap-".$id;
		$layer_lname = "apl-".$id;
		$layer_source = "        										map.addSource('".$layer_sname."', {
													type: 'geojson',
													data: 'https://live.wifidb.net/wifidb/api/geojson.php?func=exp_ap&id=".$id."',
													buffer: 0,
												});

												map.addLayer({
													'id': '".$layer_lname."',
													'type': 'circle',
													'source': '".$layer_sname."',
													'paint': {
														'circle-color': {
															property: 'sectype',
															type: 'interval',
															stops: [
																[1, 'green'],
																[2, 'orange'],
																[3, 'red']
															]
														},
														'circle-radius': 3,
														'circle-opacity': 1,
														'circle-blur': 0.5
													}
												});
";
		if ($named) {$layer_source .= $this->CreateLabelLayer($layer_sname);}

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}
	
	public function CreateLabelLayer($source, $source_layer = "")
	{
		if ($source_layer) {
			$layer_source = "        										map.addLayer({
													'id': '".$source_layer."-label',
													'type': 'symbol',
													'source': '".$source."',
													'source-layer': '".$source_layer."',
";
		}
		else
		{
			$layer_source = "        										map.addLayer({
													'id': '".$source."-label',
													'type': 'symbol',
													'source': '".$source."',
";
		}

		$layer_source .= "													'layout': {
														'text-field': '{ssid}',
														'text-font': ['Open Sans Regular'],
														'text-size': 10,
														'text-justify':'left'
													}
												});
";
		
		return $layer_source;
	}
}