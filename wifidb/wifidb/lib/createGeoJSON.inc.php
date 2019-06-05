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

	public function CreateCellFeature($ap_info_array, $tc = 0)
	{
		
		if($tc == 0){
			$tippecanoe = '';
		}else{
			$tippecanoe = '"tippecanoe":{"maxzoom":19,"minzoom":3},';
		}		
		
		$ap_info_array['mac'] = json_encode($ap_info_array['mac']);
		$ap_info_array['ssid'] = json_encode(dbcore::formatSSID($ap_info_array['ssid']));
		$ap_info_array['authmode'] = json_encode($ap_info_array['authmode']);
		$tmp = '{"type":"Feature",'.$tippecanoe.'"properties":{"id":"'.$ap_info_array['id'].'","name":"'.$ap_info_array['name'].'","mac":'.$ap_info_array['mac'].',"ssid":'.$ap_info_array['ssid'].',"authmode":'.$ap_info_array['authmode'].',"chan":'.$ap_info_array['chan'].',"type":"'.$ap_info_array['type'].'","lat":"'.$ap_info_array['lat'].'","lon":"'.$ap_info_array['lon'].'","rssi":"'.$ap_info_array['rssi'].'","fa":"'.$ap_info_array['fa'].'","la":"'.$ap_info_array['la'].'","user":"'.$ap_info_array['user'].'","points":"'.$ap_info_array['points'].'"},"geometry":{"type":"Point","coordinates":['.$ap_info_array['lon'].','.$ap_info_array['lat'].']}}';

		return $tmp;
	}
	
	public function CreateApFeature($ap_info_array, $tc = 0)
	{
		
		if($tc == 0){
			$tippecanoe = '';
		}else{
			$tippecanoe = '"tippecanoe":{"maxzoom":19,"minzoom":3},';
		}		
		
		$ap_info_array['ssid'] = json_encode(dbcore::formatSSID($ap_info_array['ssid']));
		$ap_info_array['user'] = json_encode($ap_info_array['user']);
		$tmp = '{"type":"Feature",'.$tippecanoe.'"properties":{"id":"'.$ap_info_array['id'].'","live_id":"'.$ap_info_array['live_id'].'","user":'.$ap_info_array['user'].',"ssid":'.$ap_info_array['ssid'].',"mac":"'.$ap_info_array['mac'].'","sectype":'.$ap_info_array['sectype'].',"NT":"'.$ap_info_array['NT'].'","radio":"'.$ap_info_array['radio'].'","chan":"'.$ap_info_array['chan'].'","auth":"'.$ap_info_array['auth'].'","encry":"'.$ap_info_array['encry'].'","BTx":"'.$ap_info_array['BTx'].'","OTx":"'.$ap_info_array['OTx'].'","points":"'.$ap_info_array['points'].'","FA":"'.$ap_info_array['FA'].'","LA":"'.$ap_info_array['LA'].'","lat":"'.$ap_info_array['lat'].'","lon":"'.$ap_info_array['lon'].'","alt":"'.$ap_info_array['alt'].'","manuf":"'.$ap_info_array['manuf'].'"},"geometry":{"type":"Point","coordinates":['.$ap_info_array['lon'].','.$ap_info_array['lat'].']}}';

		return $tmp;
	}

	public function CreateApLayer($source, $source_layer = "", $open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{

		$layer_source = "
													map.addLayer({
														'id': '".$source_layer."',
														'type': 'circle',
														'source': '".$source."',
														'source-layer': '".$source_layer."',
														'layout': {
															 'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});";
		return $layer_source;
	}
	
	public function CreateCellLayer($source, $source_layer = "", $cell_color = "#885FCD", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{

		$layer_source = "
													map.addLayer({
														'id': '".$source_layer."',
														'type': 'circle',
														'source': '".$source."',
														'source-layer': '".$source_layer."',
														'layout': {
															 'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': '".$cell_color."',
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});";
		return $layer_source;
	}
	
	public function CreateLabelLayer($source, $source_layer = "", $type = "label", $field = "{ssid}", $font = "Open Sans Regular", $size = 10, $visibility = "visible")
	{
		if ($source_layer) {
			$layer_source = "
													map.addLayer({
														'id': '".$source_layer."-".$type."',
														'source-layer': '".$source_layer."',";
		}else{
			$layer_source = "
													map.addLayer({
														'id': '".$source."-".$type."',";
		}

		$layer_source .= "
														'source': '".$source."',
														'type': 'symbol',
														'layout': {
															'text-field': '".$field."',
															'text-font': ['".$font."'],
															'text-size': ".$size.",
															'visibility': '".$visibility."'
														},
														'paint': {
															'text-halo-blur': 1,
															'text-color': '#000000',
															'text-halo-width': 2,
															'text-halo-color': '#FFFFFF'
														  }
													});";
		
		return $layer_source;
	}
	
	public function CreateListGeoJsonLayer($id, $labeled=0, $open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_sname = "list-".$id;
		$layer_lname = "listl-".$id;
		$layer_source = "
													map.addSource('".$layer_sname."', {
														type: 'geojson',
														data: '".$this->URL_BASE."api/geojson.php?func=exp_list&id=".$id."&all=1',
														buffer: 0,
													});

													map.addLayer({
														'id': '".$layer_lname."',
														'type': 'circle',
														'source': '".$layer_sname."',
														'layout': {
															'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});
";

		if ($labeled) {$layer_source .= $this->CreateLabelLayer($layer_sname);}

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		"source_name" => $layer_sname
		);
		
		return $ret_data;
	}
	
	public function CreateSearchGeoJsonLayer($search_str, $open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_sname = 'slist-'.uniqid();
		$layer_lname = 'slistl-'.uniqid();
		$layer_source = "
													map.addSource('".$layer_sname."', {
														type: 'geojson',
														data: '".$this->URL_BASE."api/geojson.php?func=exp_search".$search_str."',
														buffer: 0,
													});

													map.addLayer({
														'id': '".$layer_lname."',
														'type': 'circle',
														'source': '".$layer_sname."',
														'layout': {
															'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});
";
		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		"source_name" => $layer_sname
		);
		
		return $ret_data;
	}
	
	public function CreateUserAllGeoJsonLayer($user, $labeled=0, $from = NULL, $limit = NULL, $open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_url = $this->URL_BASE."api/geojson.php?func=exp_user_all&user=".$user;
		if($from !== NULL And $limit !== NULL){$layer_url .=  "&from=".$from."&limit=".$limit;}
		$layer_sname = "list-".$user;
		$layer_lname = "listl-".$user;
		$layer_source = "
													map.addSource('".$layer_sname."', {
														type: 'geojson',
														data: '".$layer_url."',
														buffer: 0,
													});

													map.addLayer({
														'id': '".$layer_lname."',
														'type': 'circle',
														'source': '".$layer_sname."',
														'layout': {
															'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});
";

		if ($labeled) {$layer_source .= $this->CreateLabelLayer($layer_sname);}

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}
	
	public function CreateDailyGeoJsonLayer($open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_sname = 'dailys';
		$layer_lname = 'daily';
		$layer_source = "
													map.addSource('".$layer_sname."', {
														type: 'geojson',
														data: '".$this->URL_BASE."api/geojson.php?func=exp_daily',
														buffer: 0,
													});

													map.addLayer({
														'id': '".$layer_lname."',
														'type': 'circle',
														'source': '".$layer_sname."',
														'layout': {
															'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});
";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		"source_name" => $layer_sname
		);
		
		return $ret_data;
	}
	
	public function CreateLatestGeoJsonLayer($open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_sname = 'latests';
		$layer_lname = 'latest';
		$layer_source = "
													map.addSource('".$layer_sname."', {
														type: 'geojson',
														data: '".$this->URL_BASE."api/geojson.php?func=exp_latest_ap',
														buffer: 0,
													});

													map.addLayer({
														'id': '".$layer_lname."',
														'type': 'circle',
														'source': '".$layer_sname."',
														'layout': {
															'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});
";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		"source_name" => $layer_sname
		);
		
		return $ret_data;
	}
	
	public function CreateApGeoJsonLayer($id, $labeled=0, $open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_sname = "ap-".$id;
		$layer_lname = "apl-".$id;
		$layer_source = "
													map.addSource('".$layer_sname."', {
														type: 'geojson',
														data: '".$this->URL_BASE."api/geojson.php?func=exp_ap&id=".$id."',
														buffer: 0,
													});

													map.addLayer({
														'id': '".$layer_lname."',
														'type': 'circle',
														'source': '".$layer_sname."',
														'layout': {
															'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});";
		if ($labeled) {$layer_source .= $this->CreateLabelLayer($layer_sname);}

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}
	
	public function CreateLiveApGeoJsonLayer($id, $labeled=0, $open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_sname = "ap-".$id;
		$layer_lname = "apl-".$id;
		$layer_source = "
													map.addSource('".$layer_sname."', {
														type: 'geojson',
														data: '".$this->URL_BASE."api/geojson.php?func=exp_live_ap&id=".$id."',
														buffer: 0,
													});

													map.addLayer({
														'id': '".$layer_lname."',
														'type': 'circle',
														'source': '".$layer_sname."',
														'layout': {
															'visibility': '".$visibility."'
														},
														'paint': {
															'circle-color': {
																'property': 'sectype',
																'type': 'interval',
																'stops': [
																	[1, '".$open_color."'],
																	[2, '".$wep_color."'],
																	[3, '".$sec_color."']
																]
															},
															'circle-radius': ".$radius.",
															'circle-opacity': ".$opacity.",
															'circle-blur': ".$blur."
														}
													});";
		if ($labeled) {$layer_source .= $this->CreateLabelLayer($layer_sname);}

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}

}