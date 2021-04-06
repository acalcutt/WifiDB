<?php
/*
createGeoJSON.inc.php, class to create GeoJSON/GeoJSON files
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
		$GeoJSON_DATA = '{"type":"FeatureCollection","features":['.$alldata."\n".']}';
		Return $GeoJSON_DATA;
	}
	
	public function CreateApFeature($ap_info_array, $tc = 0)
	{
		
		if($tc == 0){$tippecanoe = '';}else{$tippecanoe = '"tippecanoe":{"maxzoom":14,"minzoom":0},';}

		if(isset($ap_info_array['id'])){$id = '"id":"'.json_encode($ap_info_array['id'], JSON_NUMERIC_CHECK).'",';}else{$id = '';}
		if(isset($ap_info_array['live_id'])){$live_id = '"live_id":"'.json_encode($ap_info_array['live_id'], JSON_NUMERIC_CHECK).'",';}else{$live_id = '';}
		if(isset($ap_info_array['user'])){$user = '"user":'.json_encode($ap_info_array['user']).',';}else{$user = '';}
		if(isset($ap_info_array['signal'])){$sig = '"signal":'.json_encode($ap_info_array['signal'], JSON_NUMERIC_CHECK).',';}else{$sig = '';}
		if(isset($ap_info_array['rssi'])){$rssi = '"rssi":'.json_encode($ap_info_array['rssi'], JSON_NUMERIC_CHECK).',';}else{$rssi = '';}
		if(isset($ap_info_array['high_gps_sig'])){$high_gps_sig = '"high_gps_sig":'.json_encode($ap_info_array['high_gps_sig'], JSON_NUMERIC_CHECK).',';}else{$high_gps_sig = '';}
		if(isset($ap_info_array['high_gps_rssi'])){$high_gps_rssi = '"high_gps_rssi":'.json_encode($ap_info_array['high_gps_rssi'], JSON_NUMERIC_CHECK).',';}else{$high_gps_rssi = '';}
		if(isset($ap_info_array['manuf'])){$manuf = '"manuf":'.json_encode($ap_info_array['manuf']).',';}else{$manuf = '';}
		if(isset($ap_info_array['hist_date'])){$hist_date = '"hist_date":'.json_encode($ap_info_array['hist_date']).',';}else{$hist_date = '';}
		if(isset($ap_info_array['hist_file_id'])){$hist_file_id = '"hist_file_id":'.json_encode($ap_info_array['hist_file_id'], JSON_NUMERIC_CHECK).',';}else{$hist_file_id = '';}
		if(isset($ap_info_array['mac'])){$mac = '"mac":'.json_encode($ap_info_array['mac']).',';}else{$mac = '';}
		if(isset($ap_info_array['sectype'])){$sectype = '"sectype":'.json_encode($ap_info_array['sectype'], JSON_NUMERIC_CHECK).',';}else{$sectype = '';}
		if(isset($ap_info_array['NT'])){$NT = '"NT":'.json_encode($ap_info_array['NT']).',';}else{$NT = '';}
		if(isset($ap_info_array['radio'])){$radio = '"radio":'.json_encode($ap_info_array['radio']).',';}else{$radio = '';}
		if(isset($ap_info_array['chan'])){$chan = '"chan":'.json_encode($ap_info_array['chan']).',';}else{$chan = '';}
		if(isset($ap_info_array['auth'])){$auth = '"auth":'.json_encode($ap_info_array['auth']).',';}else{$auth = '';}
		if(isset($ap_info_array['encry'])){$encry = '"encry":'.json_encode($ap_info_array['encry']).',';}else{$encry = '';}
		if(isset($ap_info_array['BTx'])){$BTx = '"BTx":'.json_encode($ap_info_array['BTx']).',';}else{$BTx = '';}
		if(isset($ap_info_array['OTx'])){$OTx = '"OTx":'.json_encode($ap_info_array['OTx']).',';}else{$OTx = '';}
		if(isset($ap_info_array['points'])){$points = '"points":'.json_encode($ap_info_array['points']).',';}else{$points = '';}
		if(isset($ap_info_array['FA'])){$FA = '"FA":'.json_encode($ap_info_array['FA']).',';}else{$FA = '';}
		if(isset($ap_info_array['LA'])){$LA = '"LA":'.json_encode($ap_info_array['LA']).',';}else{$LA = '';}
		if(isset($ap_info_array['lat'])){$lat = '"lat":'.json_encode($ap_info_array['lat']).',';}else{$lat = '';}
		if(isset($ap_info_array['lon'])){$lon = '"lon":'.json_encode($ap_info_array['lon']).',';}else{$lon = '';}
		if(isset($ap_info_array['alt'])){$alt = '"alt":'.json_encode($ap_info_array['alt']).',';}else{$alt = '';}

		$name = '"name":'.json_encode(dbcore::formatSSID($ap_info_array['ssid']));
		$tmp = "\n".'{"type":"Feature",'.$tippecanoe.'"properties":{'.$id.$live_id.$user.$sig.$rssi.$manuf.$hist_date.$hist_file_id.$high_gps_sig.$high_gps_rssi.$mac.$sectype.$NT.$radio.$chan.$auth.$encry.$BTx.$OTx.$points.$FA.$LA.$lat.$lon.$alt.$name.'},"geometry":{"type":"Point","coordinates":['.json_encode($ap_info_array['lon'], JSON_NUMERIC_CHECK).','.json_encode($ap_info_array['lat'], JSON_NUMERIC_CHECK).']}}';

		return $tmp;
	}

	public function CreateApFeatureCollection($ap_feature_array, $tc = 0)
	{
		$layer_source = '';
		foreach($ap_feature_array as $ap_feature)
		{
			if($layer_source !== ''){$layer_source .=',';};
			$layer_source .=$this->CreateApFeature($ap_feature, $tc);
		}
		
		$layer_source = '{"type":"FeatureCollection","features":['.$layer_source."\n".']}';
		return $layer_source;
	}

	public function CreateApLabelLayer($source, $source_layer = "", $font = "Open Sans Regular", $size = 10, $visibility = "none")
	{
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"name","{name}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"mac","{mac}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"chan","{chan}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"FA","{FA}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"LA","{LA}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"points","{points}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"high_gps_sig","{high_gps_sig}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"high_gps_rssi","{high_gps_rssi}",$font,$size,$visibility);
		
		return $layer_source;
	}
	
	public function CreateLabelLayer($source, $source_layer = "", $type = "label", $field = "{ssid}", $font = "Open Sans Regular", $size = 10, $visibility = "none")
	{
		if ($source_layer) {$layer_source = "\n
		map.addLayer({
			'id': '".$source_layer."-".$type."',
			'source-layer': '".$source_layer."',";
		}else{$layer_source = "
		map.addLayer({
			'id': '".$source."-".$type."',";
		}

		$layer_source .= "\n
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

	public function CreateCellFeature($ap_info_array, $tc = 0)
	{
		
		if($tc == 0){
			$tippecanoe = '';
		}else{
			$tippecanoe = '"tippecanoe":{"maxzoom":19,"minzoom":0},';
		}		
		
		$ap_info_array['mac'] = json_encode($ap_info_array['mac']);
		$ap_info_array['ssid'] = json_encode(dbcore::formatSSID($ap_info_array['ssid']));
		$ap_info_array['authmode'] = json_encode($ap_info_array['authmode']);
		$tmp = "\n".'{"type":"Feature",'.$tippecanoe.'"properties":{"id":"'.$ap_info_array['id'].'","name":"'.$ap_info_array['name'].'","mac":'.$ap_info_array['mac'].',"ssid":'.$ap_info_array['ssid'].',"authmode":'.$ap_info_array['authmode'].',"chan":'.$ap_info_array['chan'].',"type":"'.$ap_info_array['type'].'","lat":"'.$ap_info_array['lat'].'","lon":"'.$ap_info_array['lon'].'","rssi":"'.$ap_info_array['rssi'].'","fa":"'.$ap_info_array['fa'].'","la":"'.$ap_info_array['la'].'","user":"'.$ap_info_array['user'].'","points":"'.$ap_info_array['points'].'"},"geometry":{"type":"Point","coordinates":['.$ap_info_array['lon'].','.$ap_info_array['lat'].']}}';

		return $tmp;
	}
	public function CreateLatestGeoJsonSource()
	{
		$layer_name = 'latests';
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_latest_ap',
			buffer: 0,
		});";
		
		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateDailyGeoJsonSource()
	{
		$layer_name = 'dailys';
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_daily',
			buffer: 0,
		});";
		
		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateApGeoJsonSource($ap_id)
	{
		$layer_name = "ap_".$ap_id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_ap&id=".$ap_id."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateUserAllGeoJsonSource($user, $from = NULL, $limit = NULL)
	{
		$layer_url = $this->URL_BASE."api/geojson.php?func=exp_user_all&user=".$user;
		if($from !== NULL And $limit !== NULL){$layer_url .=  "&from=".$from."&limit=".$limit;}
		$layer_name = "uas_".$user;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$layer_url."',
			buffer: 0,		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateListGeoJsonSource($id)
	{
		$layer_name = "list-".$id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_list&id=".$id."&all=1',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateLiveApGeoJsonSource($id)
	{
		$layer_name = "ap-".$id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_live_ap&id=".$id."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateSearchGeoJsonSource($search_str)
	{
		$layer_name = 'slist-'.uniqid();
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_search".$search_str."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateApSignalGeoJsonSource($ap_id, $list_id=0)
	{
		$layer_name = "aps_".$ap_id."-".$list_id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_ap_sig&id=".$ap_id."&list_id=".$list_id."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateApLayer($data_source, $data_source_layer = "", $open_color = "#1aff66", $wep_color = "#ffad33", $sec_color = "#ff1a1a",$base_radius = 2, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		if($data_source_layer){$layer_lname = $data_source_layer;}else{$layer_lname = $data_source;};
		$layer_source = "\n
		map.addLayer({
			'id': '".$layer_lname."',
			'type': 'circle',
			'source': '".$data_source."',\n";
		if($data_source_layer){$layer_source .= "			'source-layer': '".$data_source_layer."',\n";};
		$layer_source .= "			'layout': {
				'visibility': '".$visibility."'
			},
			'paint': {
				'circle-radius': {
					'base': ".$base_radius.",
					'stops': [
					[1, 1.5],
					[4, 2],
					[12, 2],
					[20, 20]
					]
				},
				'circle-color': {
					'property': 'sectype',
					'type': 'interval',
					'stops': [
						[1, '".$open_color."'],
						[2, '".$wep_color."'],
						[3, '".$sec_color."']
					]
				},
				'circle-opacity': ".$opacity.",
				'circle-blur': ".$blur."
			}
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}

	public function CreateApSigLayer($data_source, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		$layer_lname = $data_source;
		$layer_source = "\n
		map.addLayer({
			'id': '".$layer_lname."',
			'type': 'circle',
			'source': '".$data_source."',
			'layout': {
				'visibility': '".$visibility."'
			},
			'paint': {
				'circle-radius': {
					'base': 2,
					'stops': [
					[1, 1],
					[5, 2],
					[10, 3],
					[20, 20]
					]
				},
				'circle-color': {
					'property': 'signal',
					'stops': [
						[16, '#E42F00'],
						[30, '#FF0000'],
						[48, '#FF9200'],
						[64, '#FFEC00'],
						[80, '#80FF00'],
						[100, '#0D7600']
					]
				},
				'circle-opacity': ".$opacity.",
				'circle-blur': ".$blur."
			}
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}

	public function CreateCellLayer($source, $source_layer = "", $cell_color = "#885FCD", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{

		$layer_source = "\n
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
}