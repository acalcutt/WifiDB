<!--
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

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
-->
{include file="header.tpl"}
	<table style="width: 100%" cellpadding="0" cellspacing="0">
		<tr>
			<td align="left">
				<div id='map' style='float:left; width: 100%; height:80vh;'></div>
				<div id='basemap'>
					<input id='NE2' type='radio' name='rtoggle' value='NE2' checked='checked'>
					<label for='NE2'>Natural Earth II + OSM</label>
					<input id='osm-bright' type='radio' name='rtoggle' value='osm-bright'>
					<label for='osm-bright'>OSM Bright</label>
					<input id='klokantech-basic' type='radio' name='rtoggle' value='klokantech-basic'>
					<label for='klokantech-basic'>Klokantech Basic</label>
				</div>
				<div>
					<input type="text" placeholder="Address Search.." name="searchadrbox" id="searchadrbox">
					<button id="searchadr" onClick="searchadr()">Search</button>
				</div>
				<script>
				var map = new mapboxgl.Map({
					container: 'map',
					style: '{$style}',
					center: {$centerpoint},
					zoom: {$zoom},
				});
				
				var layerList = document.getElementById('basemap');
				var inputs = layerList.getElementsByTagName('input');

				function switchLayer(layer) {
					var layerId = layer.target.id;
					map.setStyle('https://omt.wifidb.net/styles/' + layerId + '/style.json');
				}

				for (var i = 0; i < inputs.length; i++) {
					inputs[i].onclick = switchLayer;
				}
				
				function searchadr()
				{
					var address = document.getElementById('searchadrbox').value;
					var address = address.replace(/ /g, "+");
					var url = 'https://maps.google.com/maps/api/geocode/json?sensor=false&address=' + address
					$.getJSON(url, function (data) {
						for(var i=0;i<data.results.length;i++) {
							var lat = data.results[i].geometry.location.lat;
							var lng = data.results[i].geometry.location.lng;
							var lnglat = [lng.toFixed(6),lat.toFixed(6)];
							map.setCenter(lnglat);
						}
					});
				}
				var input = document.getElementById("searchadrbox");
				input.addEventListener("keyup", function(event) {
				  // Cancel the default action, if needed
				  event.preventDefault();
				  // Number 13 is the "Enter" key on the keyboard
				  if (event.keyCode === 13) {
					// Trigger the button element with a click
					document.getElementById("searchadr").click();
				  }
				});

				function init() {
{$layer_source_all}
				};

				map.once('style.load', function(e) {
					init();
					//Add Fullscreen Button
					const fs = new mapboxgl.FullscreenControl();
					map.addControl(fs)
					fs._fullscreenButton.classList.add('needsclick');
					//Add Navigation Control
					map.addControl(new mapboxgl.NavigationControl());
					//WifiDB Information Popup
					map.on('click', function(e) {
						var features = map.queryRenderedFeatures(e.point, {
							layers: [{$layer_name}]
						});
						if (!features.length) {
							return;
						}
						var feature = features[0];

						var popup = new mapboxgl.Popup()
							.setLngLat(map.unproject(e.point))
							.setHTML('<ul>' +
								'<li>SSID: <a href="{$wifidb_host_url}opt/fetch.php?id=' + feature.properties.id + '"><b>' + feature.properties.ssid + '</b></a></li>' +
								'<li>MAC: <b>' + feature.properties.mac + '</b></li>' +
								'<li>CHAN: <b>' + feature.properties.chan + '</b></li>' +
								'<li>NETWORK TYPE: <b>' + feature.properties.NT + '</b></li>' +
								'<li>ENCRYPTION: <b>' + feature.properties.encry + '</b></li>' +
								'<li>RADIO TYPE: <b>' + feature.properties.radio + '</b></li>' +
								'<li>BASIC TX: <b>' + feature.properties.BTx + '</b></li>' +
								'<li>OTHER TX: <b>' + feature.properties.OTx + '</b></li>' +
								'<li>LATITUDE: <b>' + feature.properties.lat + '</b></li>' +
								'<li>LONGITUDE: <b>' + feature.properties.long + '</b></li>' +
								'<li>ALTITUDE: <b>' + feature.properties.alt + '</b></li>' +
								'<li>First Active: <b>' + feature.properties.FA + '</b></li>' +
								'<li>Last Active: <b>' + feature.properties.LA + '</b></li>' +
								'<li>Username: <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.username + '"><b>' + feature.properties.username + '</b></a></li>' +
								'</ul>')
							.addTo(map);
					});
					
				map.on('style.load', () => {
				  const waiting = () => {
					if (!map.isStyleLoaded()) {
					  setTimeout(waiting, 200);
					} else {
					  init();
					}
				  };
				  waiting();
				});

					//Hide loading bar once tiles from geojson are loaded
					map.on('data', function(e) {})

					// Use the same approach as above to indicate that the symbols are clickable
					// by changing the cursor style to 'pointer'.
					map.on('mousemove', function(e) {
						var features = map.queryRenderedFeatures(e.point, {
							layers: [{$layer_name}]
						});
						map.getCanvas().style.cursor = (features.length) ? 'pointer' : '';
					});
				});
				</script>
			</td>
		</tr>
	</table>
{include file="footer.tpl"}