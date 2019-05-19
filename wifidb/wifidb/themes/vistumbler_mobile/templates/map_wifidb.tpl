<!--

Copyright (C) 2018 Andrew Calcutt

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
			<div class="main">
				{include file="topmenu.tpl"}
				<table style="width: 100%" cellpadding="0" cellspacing="0">
					<tr>
						<td align="left">
							<div id='map' style='float:left; width: 100%; height:65vh;'></div>
							<div id='basemap'>
								<input id='WDB_OSM' type='radio' name='rtoggle' value='WDB_OSM'>
								<label for='WDB_OSM'>Open Street Map</label>
								<input id='WDB_ESRI' type='radio' name='rtoggle' value='WDB_ESRI'>
								<label for='WDB_ESRI'>ESRI World Imagery</label>
								<input id='WDB_ESRIOSM' type='radio' name='rtoggle' value='WDB_ESRIOSM' checked='checked'>
								<label for='WDB_ESRIOSM'>World Imagery + Open Street Map</label>
							</div>
							<div>
								<button id="daily" onClick="toggle_layer_button(this.id)">Hide Daily</button>
								<button id="WifiDB_0to1year" onClick="toggle_layer_button(this.id)">Hide 0-1 year</button>
								<button id="WifiDB_1to2year" onClick="toggle_layer_button(this.id)">Hide 1-2 year</button>
								<button id="WifiDB_2to3year" onClick="toggle_layer_button(this.id)">Hide 2-3 year</button>
								<button id="WifiDB_Legacy" onClick="toggle_layer_button(this.id)">Hide 3+ year</button>
							</div>
							<div>
								<input type="text" placeholder="Address Search.." name="searchadrbox" id="searchadrbox">
								<button id="searchadr" onClick="searchadr()">Search</button>
							</div>
							<div>
								{if $labeled eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=0">[View Un-Labeled]</a>
								{else}
									<a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=1">[View Labeled]</a>
								{/if}
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
{if $labeled eq 1}
									map.setLayoutProperty(clicked_id + '-label', 'visibility', 'none');
{/if}
									this.className = '';
									el.firstChild.data = "Show" + btext;
								} else {
									this.className = 'active';
									map.setLayoutProperty(clicked_id, 'visibility', 'visible');
{if $labeled eq 1}
									map.setLayoutProperty(clicked_id + '-label', 'visibility', 'visible');
{/if}
									el.firstChild.data = "Hide" + btext;
								}

							}
							// --- End Year Visibility Functions ---
							
							// --- Start Address Search Box Functions ---
							function searchadr()
							{
								var address = document.getElementById('searchadrbox').value;
								var address = address.replace(/ /g, "+");
								var url = 'https://geocoder.api.here.com/6.2/geocode.json?app_id=PosJ3G7XOlfZLXeYgxeZ&app_code=4yaMcu0yxndGUH6X1_vHAw&searchtext=' + address
								console.log('url: ', url);
								var req = new XMLHttpRequest();
								req.overrideMimeType("application/json");
								req.open('GET', url, true);
								req.onload  = function() {
									console.log(req.responseText);
									var jsonResponse = JSON.parse(req.responseText);
									var lat = jsonResponse.Response.View[0].Result[0].Location.DisplayPosition.Latitude;
									var lng = jsonResponse.Response.View[0].Result[0].Location.DisplayPosition.Longitude;
									var lnglat = [lng.toFixed(6),lat.toFixed(6)];
									map.setCenter(lnglat);
									console.log('lnglat: ', lnglat);
								};
								req.send(null);							
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
											'<li>MANUFACTURER: <b>' + feature.properties.manuf + '</b></li>' +
											'<li>CHAN: <b>' + feature.properties.chan + '</b></li>' +
											'<li>NETWORK TYPE: <b>' + feature.properties.NT + '</b></li>' +
											'<li>ENCRYPTION: <b>' + feature.properties.encry + '</b></li>' +
											'<li>RADIO TYPE: <b>' + feature.properties.radio + '</b></li>' +
											'<li>BASIC TX: <b>' + feature.properties.BTx + '</b></li>' +
											'<li>OTHER TX: <b>' + feature.properties.OTx + '</b></li>' +
											'<li>LATITUDE: <b>' + feature.properties.lat + '</b></li>' +
											'<li>LONGITUDE: <b>' + feature.properties.lon + '</b></li>' +
											'<li>ALTITUDE: <b>' + feature.properties.alt + '</b></li>' +
											'<li>First Active: <b>' + feature.properties.FA + '</b></li>' +
											'<li>Last Active: <b>' + feature.properties.LA + '</b></li>' +
											'<li>Username: <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.user + '"><b>' + feature.properties.user + '</b></a></li>' +
											'</ul>')
										.addTo(map);
								});

								// indicate that the symbols are clickableby changing the cursor style to 'pointer'.
								map.on('mousemove', function(e) {
									var features = map.queryRenderedFeatures(e.point, {
										layers: [{$layer_name}]
									});
									map.getCanvas().style.cursor = (features.length) ? 'pointer' : '';
								});
							});
							map.on('style.load', function () {
								// Reset toggle buttons since the layers reset on style change
								var toggleButtonIds = ['WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy'];
								for(var index in toggleButtonIds) {
									var clicked_id = toggleButtonIds[index];
									var el = document.getElementById(clicked_id);
									var btext = el.firstChild.data;
									var btext = btext.replace("Show", "");
									var btext = btext.replace("Hide", "");
									el.firstChild.data = "Hide" + btext;
								}
								// Reload dynamic layers since they are lost on style change
								const waiting = function () {
									if (!map.isStyleLoaded()) {
									  setTimeout(waiting, 200);
									} else {
									  init();
									}
								};
								waiting();
							});
							</script>
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}