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

	public function __construct($URL_PATH, $GeoJSON_out, $daemon_out, $convertObj, $tilldead = 5)
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
		
		if($tc == 0){$tippecanoe = '';}else{$tippecanoe = '"tippecanoe":{"maxzoom":19,"minzoom":0},';}

		if(isset($ap_info_array['named']) && $ap_info_array['named'] == 1){$name = '"name":'.json_encode(dbcore::formatSSID($ap_info_array['ssid'])).',';}else{$name = '';}
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
		if(isset($ap_info_array['first_file_id'])){$first_file_id = '"first_file_id":'.json_encode($ap_info_array['first_file_id'], JSON_NUMERIC_CHECK).',';}else{$first_file_id = '';}
		if(isset($ap_info_array['mac'])){$mac = '"mac":'.json_encode($ap_info_array['mac']).',';}else{$mac = '';}
		if(isset($ap_info_array['mapname'])){$mapname = '"mapname":'.json_encode($ap_info_array['mapname']).',';}else{$mapname = '';}
		if(isset($ap_info_array['sectype'])){$sectype = '"sectype":'.json_encode($ap_info_array['sectype'], JSON_NUMERIC_CHECK).',';}else{$sectype = '';}
		if(isset($ap_info_array['nt'])){$NT = '"nt":'.json_encode($ap_info_array['nt']).',';}else{$NT = '';}
		if(isset($ap_info_array['radio'])){$radio = '"radio":'.json_encode($ap_info_array['radio']).',';}else{$radio = '';}
		if(isset($ap_info_array['chan'])){$chan = '"chan":'.json_encode($ap_info_array['chan']).',';}else{$chan = '';}
		if(isset($ap_info_array['auth'])){$auth = '"auth":'.json_encode($ap_info_array['auth']).',';}else{$auth = '';}
		if(isset($ap_info_array['encry'])){$encry = '"encry":'.json_encode($ap_info_array['encry']).',';}else{$encry = '';}
		if(isset($ap_info_array['type'])){$type = '"type":'.json_encode($ap_info_array['type']).',';}else{$type = '';}
		if(isset($ap_info_array['btx'])){$BTx = '"btx":'.json_encode($ap_info_array['btx']).',';}else{$BTx = '';}
		if(isset($ap_info_array['otx'])){$OTx = '"otx":'.json_encode($ap_info_array['otx']).',';}else{$OTx = '';}
		if(isset($ap_info_array['points'])){$points = '"points":'.json_encode($ap_info_array['points']).',';}else{$points = '';}
		if(isset($ap_info_array['fa'])){$FA = '"fa":'.json_encode($ap_info_array['fa']).',';}else{$FA = '';}
		if(isset($ap_info_array['la'])){$LA = '"la":'.json_encode($ap_info_array['la']).',';}else{$LA = '';}
		if(isset($ap_info_array['lat'])){$lat = '"lat":'.json_encode($ap_info_array['lat']).',';}else{$lat = '';}
		if(isset($ap_info_array['lon'])){$lon = '"lon":'.json_encode($ap_info_array['lon']).',';}else{$lon = '';}
		if(isset($ap_info_array['alt'])){$alt = '"alt":'.json_encode($ap_info_array['alt']).',';}else{$alt = '';}
		if(isset($ap_info_array['sats'])){$sats = '"sats":'.json_encode($ap_info_array['sats']).',';}else{$sats = '';}
		if(isset($ap_info_array['accuracy'])){$accuracy = '"accuracy":'.json_encode($ap_info_array['accuracy']).',';}else{$accuracy = '';}
		if(isset($ap_info_array['hdop'])){$hdop = '"hdop":'.json_encode($ap_info_array['hdop']).',';}else{$hdop = '';}
		$ssid = '"ssid":'.json_encode($ap_info_array['ssid']);

		$tmp = "\n".'{"type":"Feature",'.$tippecanoe.'"properties":{'.$name.$id.$live_id.$user.$sig.$rssi.$manuf.$hist_date.$hist_file_id.$first_file_id.$high_gps_sig.$high_gps_rssi.$mac.$mapname.$sectype.$NT.$radio.$chan.$auth.$encry.$type.$BTx.$OTx.$points.$FA.$LA.$lat.$lon.$alt.$sats.$accuracy.$hdop.$ssid.'},"geometry":{"type":"Point","coordinates":['.json_encode($ap_info_array['lon'], JSON_NUMERIC_CHECK).','.json_encode($ap_info_array['lat'], JSON_NUMERIC_CHECK).']}}';

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
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"ssid","{ssid}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"mac","{mac}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"chan","{chan}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"fa","{fa}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"la","{la}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"points","{points}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"high_gps_sig","{high_gps_sig}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"high_gps_rssi","{high_gps_rssi}",$font,$size,$visibility);
		
		return $layer_source;
	}

	public function CreateCellLabelLayer($source, $source_layer = "", $font = "Open Sans Regular", $size = 10, $visibility = "none")
	{
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"ssid","{mapname}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"mac","{mac}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"chan","{chan}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"fa","{fa}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"la","{la}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"points","{points}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"high_gps_rssi","{rssi}",$font,$size,$visibility);
		$layer_source .= $this->CreateLabelLayer($source,$source_layer,"high_gps_sig","{rssi}",$font,$size,$visibility);
		
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

	public function CreateHeatMapLayer($data_source, $data_source_layer = "", $visibility = "none")
	{
		$layer_type = 'heatmap';
		if($data_source_layer){$layer_lname = $data_source_layer."-".$layer_type;}else{$layer_lname = $data_source."-".$layer_type;};
		
		if ($data_source_layer) {$layer_source = "\n
		map.addLayer(
			{
				'id': '".$layer_lname."',
				'source-layer': '".$data_source_layer."',";
		}else{$layer_source = "
		map.addLayer(
			{
				'id': '".$layer_lname."',";
		};			

		$layer_source .= "
				'source': '".$data_source."',
				'type': '".$layer_type."',
				'layout': {
					'visibility': '".$visibility."'
				},
				'paint': {
					'heatmap-color': [
						'interpolate',
						['linear'],
						['heatmap-density'],
						0,    'rgba(33,102,172,0)',
						0.3,  'rgb(103,169,207)',
						0.5,  'rgb(209,229,240)',
						0.7,  'rgb(253,219,199)',
						0.85, 'rgb(239,138,98)',
						1,    'rgb(178,24,43)'
					],
					'heatmap-radius': [
						'interpolate',
						['linear'],
						['zoom'],
						0, 2,
						9, 20
					]
				}
			})";

		
		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
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

	public function CreateCellGeoJsonSource($cell_id)
	{
		$layer_name = "cidp_".$cell_id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_cid&id=".$cell_id."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateUserAllGeoJsonSource($user, $from = NULL, $inc = NULL)
	{
		$layer_url = $this->URL_BASE."api/geojson.php?func=exp_user_all&user=".$user;
		if($from !== NULL && $inc !== NULL){$layer_url .=  "&from=".$from."&inc=".$inc;}
		$layer_name = "uas_".$user;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$layer_url."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateListGeoJsonSource($id, $from = NULL, $inc = NULL)
	{
		$layer_url = $this->URL_BASE."api/geojson.php?func=exp_list&id=".$id;
		if($from !== NULL && $inc !== NULL){$layer_url .=  "&from=".$from."&inc=".$inc;}
		$layer_name = "list_".$id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$layer_url."',
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

	public function CreateApSignalGeoJsonSource($ap_id, $file_id=0, $from=0, $inc=50000)
	{
		$layer_name = "aps_".$ap_id."-".$file_id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_ap_sig&id=".$ap_id."&file_id=".$file_id."&from=".$from."&inc=".$inc."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateCellSignalGeoJsonSource($cell_id, $file_id=0, $from=0, $inc=50000)
	{
		$layer_name = "cs_".$cell_id."-".$file_id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$this->URL_BASE."api/geojson.php?func=exp_cell_sig&id=".$cell_id."&file_id=".$file_id."&from=".$from."&inc=".$inc."',
			buffer: 0,
		});";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}

	public function CreateCellListGeoJsonSource($id, $from = NULL, $inc = NULL)
	{
		$layer_url = $this->URL_BASE."api/geojson.php?func=exp_cid_list&id=".$id;
		if($from !== NULL && $inc !== NULL){$layer_url .=  "&from=".$from."&inc=".$inc;}
		$layer_name = "clist_".$id;
		$layer_source = "\n
		map.addSource('".$layer_name."', {
			type: 'geojson',
			data: '".$layer_url."',
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
					'base': 1.5,
					'stops': [
					[1, ".($base_radius * 0.5)."],
					[4, ".$base_radius."],
					[12, ".$base_radius."],
					[20, 20]
					]
				},
				'circle-color': [
					'case',
					['==', ['get', 'sectype'], 2], '".$wep_color."',
					['==', ['get', 'sectype'], 3], '".$sec_color."',
					'".$open_color."'
				],
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
				'circle-radius': ['interpolate',['exponential',2],['zoom'],1,1,5,2,10,3,20,20],
				'circle-color': ['interpolate',['linear'],['get','rssi'],
					-120,'#464646',-100,'#E42F00',-88,'#FF0000',
					-74,'#FF9200',-64,'#FFEC00',-52,'#80FF00',-40,'#0D7600'
				],
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

	public function CreateCellSigLayer($data_source, $opacity = 1, $blur = 0.5, $visibility = "visible")
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
				'circle-radius': ['interpolate',['exponential',2],['zoom'],1,1,5,2,10,3,20,20],
				'circle-color': ['interpolate',['linear'],['get','rssi'],
					-140,'#E42F00',-120,'#FF0000',-100,'#FF9200',
					-80,'#FFEC00',-60,'#80FF00',-44,'#0D7600'
				],
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

	public function CreateCellLayer($data_source, $data_source_layer = "", $cell_color = "#885FCD", $radius = 3, $opacity = 1, $blur = 0.5, $visibility = "visible")
	{
		if($data_source_layer){$layer_lname = $data_source_layer;}else{$layer_lname = $data_source;};
		$layer_source = "\n
		if (!map.getLayer('".$layer_lname."')) {
		map.addLayer({
			'id': '".$layer_lname."',
			'type': 'circle',
			'source': '".$data_source."',\n";
		if($data_source_layer){$layer_source .= "			'source-layer': '".$data_source_layer."',\n";};
		$layer_source .= "			'layout': {
				'visibility': '".$visibility."'
			},
			'paint': {
				'circle-color': '".$cell_color."',
				'circle-radius': ".$radius.",
				'circle-opacity': ".$opacity.",
				'circle-blur': ".$blur."
			}
		});
		}";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_lname,
		);
		
		return $ret_data;
	}

	// ── Internal tile source helpers ──────────────────────────────────────────
	// These three methods replace the old "WifiDB" / "WifiDB_newest" shared
	// GeoJSON sources (which depended on the external tiles.wifidb.net tileserver)
	// with per-bucket vector tile sources served by the local mvtd/mltd daemons
	// via tilejson.php → out/tiles/{bucket}/.

	/**
	 * Create a MapLibre vector-tile source + circle layer for one history bucket.
	 * Source ID and layer ID are both "WifiDB_{$bucket}" so they match the
	 * toggle-button IDs already in map.tpl.
	 *
	 * @param string $bucket       Bucket key, e.g. "weekly", "0to1year", "10yrplus"
	 * @param string $open_color   Circle colour for open networks  (sectype = 1)
	 * @param string $wep_color    Circle colour for WEP networks   (sectype = 2)
	 * @param string $sec_color    Circle colour for secure networks (sectype = 3)
	 * @param float  $base_radius  Base value for circle-radius stops
	 * @param float  $opacity      circle-opacity
	 * @param float  $blur         circle-blur
	 * @param string $visibility   'visible' or 'none'
	 * @return array ['layer_source' => JS string, 'layer_name' => layer/source ID]
	 */
	public function CreateMvtBucketLayers(
		string $bucket,
		string $open_color  = '#1aff66',
		string $wep_color   = '#ffad33',
		string $sec_color   = '#ff1a1a',
		float  $base_radius = 2,
		float  $opacity     = 1,
		float  $blur        = 0.5,
		string $visibility  = 'none'
	): array {
		$source_id    = 'WifiDB_' . $bucket;
		$layer_source = "\n
		if (!map.getSource('" . $source_id . "')) {
			map.addSource('" . $source_id . "', {
				type: 'vector',
				url: '" . $this->URL_BASE . "api/tilejson.php?bucket=" . $bucket . "'
			});
		}
		if (!map.getLayer('" . $source_id . "')) {
		map.addLayer({
			'id': '" . $source_id . "',
			'type': 'circle',
			'source': '" . $source_id . "',
			'source-layer': '" . $bucket . "',
			'layout': {
				'visibility': '" . $visibility . "'
			},
			'paint': {
				'circle-radius': {
					'base': 1.5,
					'stops': [[1," . ($base_radius * 0.5) . "],[4," . $base_radius . "],[12," . $base_radius . "],[20,20]]
				},
				'circle-color': [
					'case',
					['==', ['get', 'sectype'], 2], '" . $wep_color . "',
					['==', ['get', 'sectype'], 3], '" . $sec_color . "',
					'" . $open_color . "'
				],
				'circle-opacity': " . $opacity . ",
				'circle-blur': " . $blur . "
			}
		});
		}";
		return ['layer_source' => $layer_source, 'layer_name' => $source_id];
	}

	/**
	 * Create MapLibre symbol (label) layers for one history bucket.
	 * Label IDs are "WifiDB_{$bucket}-{type}" (e.g. "WifiDB_weekly-ssid") so
	 * the toggle_label() JS function in map.tpl can show/hide them correctly.
	 *
	 * @return string Raw JS to append to $layer_source_all
	 */
	public function CreateMvtBucketLabelLayers(
		string $bucket,
		string $font       = 'Open Sans Regular',
		int    $size       = 10,
		string $visibility = 'none'
	): string {
		$source_id    = 'WifiDB_' . $bucket;
		$source_layer = $bucket;
		$fields = [
			'ssid'          => '{ssid}',
			'mac'           => '{mac}',
			'chan'          => '{chan}',
			'fa'            => '{fa}',
			'la'            => '{la}',
			'points'        => '{points}',
			'high_gps_sig'  => '{high_gps_sig}',
			'high_gps_rssi' => '{high_gps_rssi}',
		];
		$out = '';
		foreach ($fields as $type => $field) {
			$out .= "\n
		if (!map.getLayer('" . $source_id . "-" . $type . "')) {
		map.addLayer({
			'id': '" . $source_id . "-" . $type . "',
			'source': '" . $source_id . "',
			'source-layer': '" . $source_layer . "',
			'type': 'symbol',
			'layout': {
				'text-field': '" . $field . "',
				'text-font': ['" . $font . "'],
				'text-size': " . $size . ",
				'visibility': '" . $visibility . "'
			},
			'paint': {
				'text-halo-blur': 1,
				'text-color': '#000000',
				'text-halo-width': 2,
				'text-halo-color': '#FFFFFF'
			}
		});
		}";
		}
		return $out;
	}

	/**
	 * Create a MapLibre vector-tile source + heatmap layer for one history bucket.
	 * Layer ID is "WifiDB_{$bucket}-heatmap".
	 *
	 * $weight_field, when non-empty, names a numeric tile property (e.g. the
	 * 'age_days' tag carried by the combined 'heatmap'/'cell_heatmap' buckets)
	 * used to drive heatmap-weight by recency — recent points contribute full
	 * weight, older points fade out. Left empty for the existing per-age-bucket
	 * heatmap layers, which have no per-feature age spread to weight by.
	 *
	 * @return array ['layer_source' => JS string, 'layer_name' => layer ID]
	 */
	public function CreateMvtBucketHeatmap(string $bucket, string $visibility = 'visible', string $weight_field = ''): array {
		$source_id    = 'WifiDB_' . $bucket;
		$layer_id     = $source_id . '-heatmap';
		$weight_paint = ($weight_field !== '')
			? "'heatmap-weight': ['interpolate',['linear'],['get','" . $weight_field . "'],0,1,3650,0.05],"
			: '';
		$layer_source = "\n
		if (!map.getSource('" . $source_id . "')) {
			map.addSource('" . $source_id . "', {
				type: 'vector',
				url: '" . $this->URL_BASE . "api/tilejson.php?bucket=" . $bucket . "'
			});
		}
		map.addLayer({
			'id': '" . $layer_id . "',
			'type': 'heatmap',
			'source': '" . $source_id . "',
			'source-layer': '" . $bucket . "',
			'layout': {
				'visibility': '" . $visibility . "'
			},
			'paint': {
				" . $weight_paint . "
				'heatmap-color': ['interpolate',['linear'],['heatmap-density'],
					0,'rgba(33,102,172,0)',0.3,'rgb(103,169,207)',
					0.5,'rgb(209,229,240)',0.7,'rgb(253,219,199)',
					0.85,'rgb(239,138,98)',1,'rgb(178,24,43)'
				],
				'heatmap-radius': ['interpolate',['linear'],['zoom'],0,2,9,20]
			}
		});";
		return ['layer_source' => $layer_source, 'layer_name' => $layer_id];
	}

	/**
	 * Create the single combined all-ages WiFi heatmap layer (bucket 'heatmap'),
	 * weighted by recency via the 'age_days' tile property. Replaces stacking
	 * 9 separate per-bucket heatmap layers, which only showed the topmost
	 * bucket's data where ages overlapped spatially.
	 */
	public function CreateWifiHeatmapAllAges(string $visibility = 'visible'): array {
		return $this->CreateMvtBucketHeatmap('heatmap', $visibility, 'age_days');
	}

	/**
	 * Create the single combined all-ages cell-tower heatmap layer
	 * (bucket 'cell_heatmap'), weighted by recency via 'age_days'.
	 * Replaces stacking 9 separate per-bucket cell heatmap layers.
	 */
	public function CreateCellHeatmapAllAges(string $visibility = 'visible'): array {
		return $this->CreateMvtBucketHeatmap('cell_heatmap', $visibility, 'age_days');
	}
	// ── Cell network MVT helpers ──────────────────────────────────────────────
	// Replace the old GeoJSON-based cell layer (WifiDB_cells source from
	// tiles.wifidb.net style) with a per-source vector tile source served
	// by cell_mvtd.php / cell_mvt.php via tilejson.php?bucket=cell_networks.
	//
	// Source ID: WifiDB_cells  (unchanged — matches existing popup/click handlers)
	// Layer ID:  cell_networks (unchanged — matches toggle button ID)
	// Source-layer: cell_networks

	/**
	 * Create a MapLibre vector-tile source + circle layer for the cell_networks bucket.
	 */
	public function CreateMvtCellLayers(string $visibility = 'visible'): array {
		// Oldest → newest so newest (cell_daily) renders on top.
		// Color and radius graduate: newest = lightest purple + largest,
		// oldest = darkest purple + smallest — matching VistumblerMAUI BucketStyles.
		$cell_specs = [
			'cell_10yrplus'  => ['#3d2266', 1.5],
			'cell_5to10year' => ['#4d2b80', 2.0],
			'cell_3to5year'  => ['#5e3599', 2.25],
			'cell_2to3year'  => ['#6f40b3', 2.5],
			'cell_1to2year'  => ['#7a4dc0', 2.75],
			'cell_0to1year'  => ['#885fcd', 3.0],
			'cell_monthly'   => ['#885fcd', 3.0],
			'cell_weekly'    => ['#9d78d8', 3.0],
			'cell_daily'     => ['#b296e3', 3.0],
		];
		$combined = '';
		foreach ($cell_specs as $cb => [$color, $radius]) {
			$src = 'WifiDB_' . $cb;
			$combined .= "\n
		if (!map.getSource('" . $src . "')) {
			map.addSource('" . $src . "', {
				type: 'vector',
				url: '" . $this->URL_BASE . "api/tilejson.php?bucket=" . $cb . "'
			});
		}
		if (!map.getLayer('" . $cb . "')) {
		map.addLayer({
			'id': '" . $cb . "',
			'type': 'circle',
			'source': '" . $src . "',
			'source-layer': '" . $cb . "',
			'layout': {
				'visibility': '" . $visibility . "'
			},
			'paint': {
				'circle-radius': {
					'base': 1.5,
					'stops': [[1," . ($radius * 0.5) . "],[4," . $radius . "],[12," . $radius . "],[20,20]]
				},
				'circle-color': '" . $color . "',
				'circle-opacity': 1,
				'circle-blur': 0.5
			}
		});
		}";
		}
		$layer_names_js = "'cell_daily','cell_weekly','cell_monthly','cell_0to1year','cell_1to2year','cell_2to3year','cell_3to5year','cell_5to10year','cell_10yrplus'";
		return ['layer_source' => $combined, 'layer_names_js' => $layer_names_js, 'layer_name' => 'cell_networks'];
	}

	/**
	 * Create MapLibre symbol (label) layers for all 9 cell bucket layers.
	 * Label IDs are "{bucket}-{type}" matching the toggle_label() convention.
	 */
	public function CreateMvtCellLabelLayers(
		string $font       = 'Open Sans Regular',
		int    $size       = 10,
		string $visibility = 'none'
	): string {
		$cell_buckets = ['cell_daily','cell_weekly','cell_monthly',
		                 'cell_0to1year','cell_1to2year','cell_2to3year',
		                 'cell_3to5year','cell_5to10year','cell_10yrplus'];
		// type key → tile property
		$fields = [
			'ssid'          => '{ssid}',
			'mac'           => '{mac}',
			'chan'          => '{chan}',
			'fa'            => '{fa}',
			'la'            => '{la}',
			'points'        => '{points}',
			'high_gps_rssi' => '{rssi}',
			'high_gps_sig'  => '{rssi}',
		];
		$out = '';
		foreach ($cell_buckets as $cb) {
			$src = 'WifiDB_' . $cb;
			foreach ($fields as $type => $field) {
				$out .= "\n
		if (!map.getLayer('" . $cb . "-" . $type . "')) {
		map.addLayer({
			'id': '" . $cb . "-" . $type . "',
			'source': '" . $src . "',
			'source-layer': '" . $cb . "',
			'type': 'symbol',
			'layout': {
				'text-field': '" . $field . "',
				'text-font': ['" . $font . "'],
				'text-size': " . $size . ",
				'visibility': '" . $visibility . "'
			},
			'paint': {
				'text-halo-blur': 1,
				'text-color': '#000000',
				'text-halo-width': 2,
				'text-halo-color': '#FFFFFF'
			}
		});
		}";
			}
		}
		return $out;
	}

	/**
	 * Create a MapLibre vector-tile source + heatmap layer for all 9 cell bucket layers.
	 * Layer IDs are "{bucket}-heatmap".
	 */
	public function CreateMvtCellHeatmap(string $visibility = 'visible'): array {
		$cell_buckets = ['cell_daily','cell_weekly','cell_monthly',
		                 'cell_0to1year','cell_1to2year','cell_2to3year',
		                 'cell_3to5year','cell_5to10year','cell_10yrplus'];
		$combined = '';
		foreach ($cell_buckets as $cb) {
			$src = 'WifiDB_' . $cb;
			$combined .= "\n
		if (!map.getSource('" . $src . "')) {
			map.addSource('" . $src . "', {
				type: 'vector',
				url: '" . $this->URL_BASE . "api/tilejson.php?bucket=" . $cb . "'
			});
		}
		if (!map.getLayer('" . $cb . "-heatmap')) {
		map.addLayer({
			'id': '" . $cb . "-heatmap',
			'type': 'heatmap',
			'source': '" . $src . "',
			'source-layer': '" . $cb . "',
			'layout': {
				'visibility': '" . $visibility . "'
			},
			'paint': {
				'heatmap-color': ['interpolate',['linear'],['heatmap-density'],
					0,'rgba(33,102,172,0)',0.3,'rgb(103,169,207)',
					0.5,'rgb(209,229,240)',0.7,'rgb(253,219,199)',
					0.85,'rgb(239,138,98)',1,'rgb(178,24,43)'
				],
				'heatmap-radius': ['interpolate',['linear'],['zoom'],0,2,9,20]
			}
		});
		}";
		}
		$heatmap_names_js = "'cell_daily-heatmap','cell_weekly-heatmap','cell_monthly-heatmap','cell_0to1year-heatmap','cell_1to2year-heatmap','cell_2to3year-heatmap','cell_3to5year-heatmap','cell_5to10year-heatmap','cell_10yrplus-heatmap'";
		return ['layer_source' => $combined, 'layer_names_js' => $heatmap_names_js, 'layer_name' => 'cell_networks-heatmap'];
	}
}