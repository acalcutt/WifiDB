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

		$tmp = '{"type":"Feature","properties":{"SSID:":"'.dbcore::normalize_ssid($ap_info_array['ssid']).'","Mac Address":"'.$ap_info_array['mac'].'","SecType":'.$ap_info_array['sectype'].',"Network Type":"'.$ap_info_array['NT'].'","Radio Type":"'.$ap_info_array['radio'].'","Channel":"'.$ap_info_array['chan'].'","Authentication":"'.$ap_info_array['auth'].'","Encryption":"'.$ap_info_array['encry'].'","Basic Transfer Rates":"'.$ap_info_array['BTx'].'","Other Transfer Rates":"'.$ap_info_array['OTx'].'","First Active":"'.$ap_info_array['FA'].'","Last Updated":"'.$ap_info_array['LA'].'","Latitude":"'.$ap_info_array['lat'].'","Longitude":"'.$ap_info_array['long'].'","Manufacturer":"'.$ap_info_array['manuf'].'"},"geometry":{"type":"Point","coordinates":['.$ap_info_array['long'].','.$ap_info_array['lat'].']}}';

		return $tmp;
	}
	
	
	public function CreateMapLayer($id)
	{
		$layer_source = "        map.addSource('ap-".$id."', {
            type: 'geojson',
            data: 'https://live.wifidb.net/wifidb/api/geojson.php?func=exp_list&row=".$id."&all=1',
            buffer: 0,
        });

        map.addLayer({
            'id': 'apl-".$id."',
            'type': 'circle',
            'source': 'ap-".$id."',
            'paint': {
                'circle-color': {
                    property: 'SecType',
                    type: 'interval',
                    stops: [
                        [1, 'green'],
                        [2, 'orange'],
                        [3, 'red']
                    ]
                },
                'circle-radius': 4,
                'circle-opacity': 0.8,
                'circle-blur': 0.5
            }
        });
";	
		$layer_name = "'apl-".$id."'";

		$ret_data = array(
		"layer_source" => $layer_source,
		"layer_name" => $layer_name,
		);
		
		return $ret_data;
	}
}