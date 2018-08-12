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
					<input id='WifiDB_NE2' type='radio' name='rtoggle' value='WifiDB_NE2' checked='checked'>
					<label for='WifiDB_NE2'>Natural Earth II + OSM</label>
					<input id='WifiDB' type='radio' name='rtoggle' value='WifiDB'>
					<label for='WifiDB'>OSM Bright</label>
					<input id='WifiDB_KB' type='radio' name='rtoggle' value='WifiDB_KB'>
					<label for='WifiDB_KB'>Klokantech Basic</label>
				</div>
				<div>
					<button id="WifiDB_0to1year" onClick="toggle_layer_button(this.id)">Hide 0-1 year</button>
					<button id="WifiDB_1to2year" onClick="toggle_layer_button(this.id)">Hide 1-2 year</button>
					<button id="WifiDB_2to3year" onClick="toggle_layer_button(this.id)">Hide 2-3 year</button>
					<button id="WifiDB_Legacy" onClick="toggle_layer_button(this.id)">Hide 3+ year</button>
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
				
				// --- Start Map Style Selection ---
				var layerList = document.getElementById('basemap');
				var inputs = layerList.getElementsByTagName('input');

				function switchLayer(layer) {
					var layerId = layer.target.id;
					map.setStyle('https://omt.wifidb.net/styles/' + layerId + '/style.json');
				}

				for (var i = 0; i < inputs.length; i++) {
					inputs[i].onclick = switchLayer;
				}
				// --- End Map Style Selection ---
				
				// --- Start Year Visibility Functions ---
				function toggle_layer_button(clicked_id)
				{
					var el = document.getElementById(clicked_id);
					var btext = el.firstChild.data;
					var btext = btext.replace("Show", "");
					var btext = btext.replace("Hide", "");
				
					var visibility = map.getLayoutProperty(clicked_id, 'visibility');
					if (visibility === 'visible') {	
						map.setLayoutProperty(clicked_id, 'visibility', 'none');
						this.className = '';
						el.firstChild.data = "Show" + btext;
					} else {
						this.className = 'active';
						map.setLayoutProperty(clicked_id, 'visibility', 'visible');
						el.firstChild.data = "Hide" + btext;
					}

				}
				
				map.on('style.load', () => {
					var toggleButtonIds = [ 'WifiDB_0to1year', 'WifiDB_1to2year', 'WifiDB_2to3year', 'WifiDB_Legacy' ];
					for(var index in toggleButtonIds) {
						var clicked_id = toggleButtonIds[index];
						var el = document.getElementById(clicked_id);
						var btext = el.firstChild.data;
						var btext = btext.replace("Show", "");
						var btext = btext.replace("Hide", "");
						el.firstChild.data = "Hide" + btext;
					}
				});
				// --- End Year Visibility Functions ---
				
				// --- Start Address Search Box Functions ---
				function searchadr()
				{
					var address = document.getElementById('searchadrbox').value;
					var address = address.replace(/ /g, "+");
					var url = 'https://maps.google.com/maps/api/geocode/json?sensor=false&address=' + address
					fetch(url)
						.then(res => res.json())
						.then((data) => {
							console.log('Output: ', data);
							for(var i=0;i<data.results.length;i++) {
								var lat = data.results[i].geometry.location.lat;
								var lng = data.results[i].geometry.location.lng;
								var lnglat = [lng.toFixed(6),lat.toFixed(6)];
								map.setCenter(lnglat);
								console.log('lnglat: ', lnglat);
							}
							
					}).catch(err => console.error(err));
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
				// --- End Address Search Box Functions ---
				
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