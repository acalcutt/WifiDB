<!--

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
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<table style="width: 100%" cellpadding="0" cellspacing="0">
					<tr>
						<td align="left">
							{if $func eq "exp_ap_sig"}
							<div style='text-align: center;'>
								Signal History for <a target="_blank" href="{$wifidb_host_url}opt/fetch.php?id={$id}"><b>{$ssid}</b></a> (ID:{$id})<br>
							</div>
							{/if}
							<div id='map' style='float:left; width: 100%; height:65vh;'></div>
							<div>
								<div id='basemap'>
									<b>Map Style: </b>
									<input id='WDB_OSM' type='radio' name='rtoggle' value='WDB_OSM'{if $style eq "WDB_OSM"} checked='checked'{/if}>
									<label for='WDB_OSM'>WDB Light</label>
									<input id='WDB_DARK_MATTER' type='radio' name='rtoggle' value='WDB_DARK_MATTER'{if $style eq "WDB_DARK_MATTER"} checked='checked'{/if}>
									<label for='WDB_DARK_MATTER'>WDB Dark</label>
									<input id='WDB_BASIC' type='radio' name='rtoggle' value='WDB_BASIC'{if $style eq "WDB_BASIC"} checked='checked'{/if}>
									<label for='WDB_BASIC'>WDB Basic</label>
									<input id='WDB_ELEV' type='radio' name='rtoggle' value='WDB_ELEV'{if $style eq "WDB_ELEV"} checked='checked'{/if}>
									<label for='WDB_DARK_MATTER'>WDB JAXA Test</label>
								</div>
{if $func eq "exp_ap_sig"}
								<div id='siglabel'>
									Point 	Label: 
									<input id='lnone' type='radio' name='sltoggle' value='none' checked='checked' onclick="toggle_label()"{if $sig_label eq "none"} checked='checked'{/if}>
									<label for='lnone'>None</label>
									<input id='lsignal' type='radio' name='sltoggle' value='signal' onclick="toggle_label()"{if $sig_label eq "signal"} checked='checked'{/if}>
									<label for='lsignal'>Signal</label>
									<input id='lrssi' type='radio' name='sltoggle' value='rssi' onclick="toggle_label()"{if $sig_label eq "rssi"} checked='checked'{/if}>
									<label for='lrssi'>RSSI</label>
									<input id='ldate' type='radio' name='sltoggle' value='hist_date' onclick="toggle_label()"{if $sig_label eq "hist_date"} checked='checked'{/if}>
									<label for='ldate'>Date</label>
								</div>
{else}
								<div id='siglabel'>
									<b>Point Label: </b>
									<input id='lnone' type='radio' name='sltoggle' value='none' checked='checked' onclick="toggle_label()"{if $sig_label eq "none"} checked='checked'{/if}>
									<label for='lnone'>None</label>
									<input id='lssid' type='radio' name='sltoggle' value='ssid' onclick="toggle_label()"{if $sig_label eq "ssid"} checked='checked'{/if}>
									<label for='lssid'>SSID</label>
									<input id='lmac' type='radio' name='sltoggle' value='mac' onclick="toggle_label()"{if $sig_label eq "mac"} checked='checked'{/if}>
									<label for='lmac'>Mac</label>
									<input id='lchan' type='radio' name='sltoggle' value='chan' onclick="toggle_label()"{if $sig_label eq "chan"} checked='checked'{/if}>
									<label for='lchan'>Channel</label>
									<input id='lfa' type='radio' name='sltoggle' value='FA' onclick="toggle_label()"{if $sig_label eq "FA"} checked='checked'{/if}>
									<label for='lfa'>First Active</label>
									<input id='lla' type='radio' name='sltoggle' value='LA' onclick="toggle_label()"{if $sig_label eq "LA"} checked='checked'{/if}>
									<label for='lla'>Last Active</label>
									<input id='lp' type='radio' name='sltoggle' value='points' onclick="toggle_label()"{if $sig_label eq "points"} checked='checked'{/if}>
									<label for='lp'>Points</label>
									<input id='hs' type='radio' name='sltoggle' value='high_gps_sig' onclick="toggle_label()"{if $sig_label eq "high_gps_sig"} checked='checked'{/if}>
									<label for='hs'>High Signal</label>
									<input id='hr' type='radio' name='sltoggle' value='high_gps_rssi' onclick="toggle_label()"{if $sig_label eq "high_gps_rssi"} checked='checked'{/if}>
									<label for='hr'>High RSSI</label>
								</div>
								<div>
									<button id="latests" onClick="toggle_latest_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Latest</button>
									<button id="dailys" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Day</button>
									<button id="WifiDB_weekly" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Week</button>
									<button id="WifiDB_monthly" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Month</button>
									<button id="WifiDB_0to1year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Year</button>
									<br/>
									<button id="WifiDB_1to2year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 1-2 year</button>
									<button id="WifiDB_2to3year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 2-3 year</button>
									<button id="WifiDB_Legacy" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 3+ year</button>
									<button id="cell_networks" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Cell Netwotks</button>
								</div>
								<div>
									<button id="Follow_AP" onClick="toggleFollowLatest(this.id)">Follow Latest AP</button>
								</div>
{/if}									
								<div>
									<input type="text" placeholder="Address Search.." name="searchadrbox" id="searchadrbox">
									<button id="searchadr" onClick="searchadr()">Search</button>
								</div>

							</div>

							<script>

							var map = new mapboxgl.Map({
								container: 'map',
								style: '{$tileserver_gl_url}/styles/{$style}/style.json',
								center: {$centerpoint},
								zoom: {$zoom},
								pitch: {$pitch},
								bearing: {$bearing},
							});

							function GoToLatest() {
								var url = '{$wifidb_host_url}api/geojson.php?func=exp_latest_ap'
								console.log('url: ', url);
								map.getSource('latests').setData(url);
								var req = new XMLHttpRequest();
								req.overrideMimeType("application/json");
								req.open('GET', url, true);
								req.onload  = function() {
									console.log(req.responseText);
									var jsonResponse = JSON.parse(req.responseText);
									var lat = parseFloat(jsonResponse.features[0].properties.lat);
									var lng = parseFloat(jsonResponse.features[0].properties.lon);
									console.log('lat: ', lat);
									console.log('lng: ', lng);
									var lnglat = [lng.toFixed(6),lat.toFixed(6)];
									map.setCenter(lnglat);
									console.log('lnglat: ', lnglat);
									
								};
								req.send(null);	
							}
							
							var FollowLatest = false;
							var LatestTimer;
							function toggleFollowLatest(clicked_id) {
								var el = document.getElementById(clicked_id);
								if (FollowLatest) {
									clearInterval(LatestTimer);
									FollowLatest = false;
									el.firstChild.data = "Follow Latest AP"
								} else {
									GoToLatest()
									LatestTimer = setInterval(function () {
										GoToLatest()
									}, 2500);
									FollowLatest = true;
									el.firstChild.data = "Un-Follow Latest AP"
								}
							}
							
							// --- Start Map Style Selection ---
							var layerList = document.getElementById('basemap');
							var inputs = layerList.getElementsByTagName('input');

							function switchLayer(layer) {
								var layerId = layer.target.id;
								map.setStyle('{$tileserver_gl_url}/styles/' + layerId + '/style.json');
								const url = new URL(window.location.href);
								url.searchParams.set('style', layerId);
								window.history.replaceState(null, null, url); // or pushState
							}

							for (var i = 0; i < inputs.length; i++) {
								inputs[i].onclick = switchLayer;
							}
							// --- End Map Style Selection ---
							
							// --- Start Year Visibility Functions ---
							function toggle_layer_button(clicked_id)
							{
								var radios = document.getElementsByName('sltoggle');
								var el = document.getElementById(clicked_id);
								var btext = el.firstChild.data;
								var btext = btext.replace("Show", "");
								var btext = btext.replace("Hide", "");
							
								var visibility = map.getLayoutProperty(clicked_id, 'visibility');
								if (visibility === 'visible') {	
									map.setLayoutProperty(clicked_id, 'visibility', 'none');
									for (var i = 0, length = radios.length; i < length; i++) {
										if (radios[i].checked) {
											if (radios[i].value !== 'none') {
												map.setLayoutProperty(clicked_id + '-' + radios[i].value, 'visibility', 'none');
											}
										}
									}
									this.className = '';
									el.firstChild.data = "Show" + btext;
								} else {
									this.className = 'active';
									map.setLayoutProperty(clicked_id, 'visibility', 'visible');
									for (var i = 0, length = radios.length; i < length; i++) {
										if (radios[i].checked) {
											if (radios[i].value !== 'none') {
												map.setLayoutProperty(clicked_id + '-' + radios[i].value, 'visibility', 'visible');
											}
										}
									}
									el.firstChild.data = "Hide" + btext;
								}

							}

							function toggle_latest_layer_button(clicked_id)
							{
								var el = document.getElementById(clicked_id);
								var radios = document.getElementsByName('sltoggle');
								var btext = el.firstChild.data;
								var btext = btext.replace("Show", "");
								var btext = btext.replace("Hide", "");
							
								var visibility = map.getLayoutProperty(clicked_id, 'visibility');
								if (visibility === 'visible') {	
									map.setLayoutProperty(clicked_id, 'visibility', 'none');
									for (var i = 0, length = radios.length; i < length; i++) {
										if (radios[i].checked) {
											if (radios[i].value !== 'none') {
												map.setLayoutProperty(clicked_id + '-' + radios[i].value, 'visibility', 'none');
											}
										}
									}
									map.setLayoutProperty(clicked_id + '-latest', 'visibility', 'none');
									this.className = '';
									el.firstChild.data = "Show" + btext;
								} else {
									this.className = 'active';
									map.setLayoutProperty(clicked_id, 'visibility', 'visible');
									for (var i = 0, length = radios.length; i < length; i++) {
										if (radios[i].checked) {
											if (radios[i].value !== 'none') {
												map.setLayoutProperty(clicked_id + '-' + radios[i].value, 'visibility', 'visible');
											}
										}
									}
									map.setLayoutProperty(clicked_id + '-latest', 'visibility', 'visible');
									el.firstChild.data = "Hide" + btext;
								}

							}
							// --- End Year Visibility Functions ---

							function toggle_label() {
								const url = new URL(window.location.href);
								var radios = document.getElementsByName('sltoggle');
								var layers = [{$layer_name}]
								for (var i = 0, length = radios.length; i < length; i++) {
									if (typeof radios[i] !== 'undefined') {
										if (radios[i].checked) {
											if (radios[i].value !== 'none') {
												for (var j = 0, length2 = layers.length; j < length2; j++) {
													var layer_visibility = map.getLayoutProperty(layers[j], 'visibility');
													if (layer_visibility === 'visible') {	
														map.setLayoutProperty(layers[j] + '-' + radios[i].value, 'visibility', 'visible');
													}
												}
											}
											url.searchParams.set("sig_label", radios[i].value);
										} else {
											if (radios[i].value !== 'none') {
												for (var j = 0, length2 = layers.length; j < length2; j++) {
													map.setLayoutProperty(layers[j] + '-' + radios[i].value, 'visibility', 'none');
												}
											}
										}
									}
								}
								
								window.history.replaceState(null, null, url);
							}
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

							// Listen for every move event by the user
							const displayCenter = function () {
								const center = map.getCenter();
								const latitude = center.lat.toFixed(6);
								const longitude = center.lng.toFixed(6);
								const bearing = map.getBearing().toFixed(0);
								const pitch = map.getPitch().toFixed(0);
								const zoom = map.getZoom().toFixed(2);
								const url = new URL(window.location.href);
								url.searchParams.set('latitude', latitude);
								url.searchParams.set('longitude', longitude);
								url.searchParams.set('bearing', bearing);
								url.searchParams.set('pitch', pitch);
								url.searchParams.set('zoom', zoom);
								window.history.replaceState(null, null, url); // or pushState
							};

							function init() {
{$layer_source_all}
toggle_label()
							};

							map.once('style.load', function(e) {
								//Add GeoLocate button
								map.addControl(new mapboxgl.GeolocateControl({
								positionOptions: {
								enableHighAccuracy: true
								},
								trackUserLocation: true
								}));
								//Add Fullscreen Button
								const fs = new mapboxgl.FullscreenControl();
								map.addControl(fs)
								fs._fullscreenButton.classList.add('needsclick');
								//Add Navigation Control
								map.addControl(new mapboxgl.NavigationControl({
								  visualizePitch: true,
								  showZoom: true,
								  showCompass: true
								}));
								//Scale Bar
								var scale = new mapboxgl.ScaleControl({
									maxWidth: 80,
									unit: 'imperial'
								});
								map.addControl(scale);
								//Ad Inspect
								map.addControl(new MapboxInspect());
								//WifiDB Information Popup
								map.on('click', function(e) {

									var features = map.queryRenderedFeatures(e.point, {
										layers: [{$layer_name}]
									});
									if (!features.length) {
										return;
									}
									var feature = features[0];
									
									var text = '<ul>';
									if (feature.properties.id) text += '<li>SSID: <a target="_blank" href="{$wifidb_host_url}opt/fetch.php?id=' + feature.properties.id + '"><b>' + feature.properties.ssid + '</b></a></li>';
									if (feature.properties.live_id) text += '<li>SSID: <b>' + feature.properties.ssid + '</b></li>';
									if (feature.properties.live_id) text += '<li>Live ID: <b>' + feature.properties.live_id + '</b></li>';
									if (feature.properties.mac) text += '<li>Mac: <b>' + feature.properties.mac + '</b></li>';
									if (feature.properties.points) text  += '<li>Points: <a target="_blank" href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&id=' + feature.properties.id + '"><b>' + feature.properties.points + '</b></a></li>';
									if (feature.properties.signal) text += '<li>Signal: <b>' + feature.properties.signal + '</b></li>';
									if (feature.properties.rssi) text += '<li>RSSI: <b>' + feature.properties.rssi + '</b></li>';
									if (feature.properties.chan) text += '<li>Channel: <b>' + feature.properties.chan + '</b></li>';
									if (feature.properties.auth) text += '<li>Auth: <b>' + feature.properties.auth + '</b></li>';
									if (feature.properties.encry) text += '<li>Encryption: <b>' + feature.properties.encry + '</b></li>';
									if (feature.properties.manuf) text += '<li>Manufacturer: <b>' + feature.properties.manuf + '</b></li>';
									if (feature.properties.NT) text += '<li>Network Type: <b>' + feature.properties.NT + '</b></li>';
									if (feature.properties.radio) text += '<li>Radio Type: <b>' + feature.properties.radio + '</b></li>';
									if (feature.properties.FA) text += '<li>First: <b>' + feature.properties.FA + '</b></li>';
									if (feature.properties.LA) text += '<li>Last: <b>' + feature.properties.LA + '</b></li>';
									if (feature.properties.high_gps_sig) text += '<li>High Signal w/GPS: <b>' + feature.properties.high_gps_sig + '</b></li>';
									if (feature.properties.high_gps_rssi) text += '<li>High RSSI w/GPS: <b>' + feature.properties.high_gps_rssi + '</b></li>';
									if (feature.properties.hist_date) text += '<li>Date: <b>' + feature.properties.hist_date + '</b></li>';
									if (feature.properties.lat) text += '<li>Latitude: <b>' + feature.properties.lat + '</b></li>';
									if (feature.properties.lon) text += '<li>Logitude: <b>' + feature.properties.lon + '</b></li>';
									if (feature.properties.alt) text += '<li>Altitude: <b>' + feature.properties.alt + '</b></li>';
									if (feature.properties.hist_file_id) text += '<li>File ID: <a target="_blank" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row=' + feature.properties.hist_file_id + '"><b>' + feature.properties.hist_file_id + '</b></a></li>';
									if (feature.properties.user) text += '<li>Username: <a target="_blank" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.user + '"><b>' + feature.properties.user + '</b></a></li>';
									text += '</ul>';
									
									var popup = new mapboxgl.Popup()
										.setLngLat(map.unproject(e.point))
										.setHTML(text)
										.addTo(map);
								});
								
								map.on('click', function(e) {
									var features = map.queryRenderedFeatures(e.point, {
										layers: [{$cell_layer_name}]
									});
									if (!features.length) {
										return;
									}
									var feature = features[0];
									
									var text = '<ul>';
									if (feature.properties.id) text += '<li>ID: <b>' + feature.properties.id + '</b></li>';
									if (feature.properties.name) text += '<li>NAME: <b>' + feature.properties.name + '</b></li>';
									if (feature.properties.mac) text += '<li>MAC: <b>' + feature.properties.mac + '</b></li>';
									if (feature.properties.ssid) text += '<li>SSID: <b>' + feature.properties.ssid + '</b></li>';
									if (feature.properties.authmode) text += '<li>AUTHMODE: <b>' + feature.properties.authmode + '</b></li>';
									if (feature.properties.chan) text += '<li>CHAN: <b>' + feature.properties.chan + '</b></li>';
									if (feature.properties.type) text += '<li>TYPE: <b>' + feature.properties.type + '</b></li>';
									if (feature.properties.rssi) text += '<li>RSSI: <b>' + feature.properties.rssi + '</b></li>';
									if (feature.properties.lat) text += '<li>LATITUDE: <b>' + feature.properties.lat + '</b></li>';
									if (feature.properties.lon) text += '<li>LONGITUDE: <b>' + feature.properties.lon + '</b></li>';
									if (feature.properties.points) text += '<li>POINTS: <b>' + feature.properties.points + '</b></li>';
									if (feature.properties.FA) text += '<li>First Active: <b>' + feature.properties.fa + '</b></li>';
									if (feature.properties.LA) text += '<li>Last Active: <b>' + feature.properties.la + '</b></li>';
									if (feature.properties.user) text += '<li>Username: <a target="_blank" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.user + '"><b>' + feature.properties.user + '</b></a></li>';
									text += '</ul>';

									var popup = new mapboxgl.Popup()
										.setLngLat(map.unproject(e.point))
										.setHTML(text)
										.addTo(map);
								});

								// indicate that the symbols are clickableby changing the cursor style to 'pointer'.
								map.on('mousemove', function(e) {
									var features = map.queryRenderedFeatures(e.point, {
										layers: [{$layer_name},{$cell_layer_name}]
									});
									map.getCanvas().style.cursor = (features.length) ? 'pointer' : '';
								});
							});
							map.on('style.load', function () {
								// Reset toggle buttons since the layers reset on style change
{if $func ne "exp_ap_sig"}
								var toggleButtonIds = ['WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy','cell_networks'];
								for(var index in toggleButtonIds) {
									var clicked_id = toggleButtonIds[index];
									var el = document.getElementById(clicked_id);
									var btext = el.firstChild.data;
									var btext = btext.replace("Show", "");
									var btext = btext.replace("Hide", "");
									el.firstChild.data = "{if $default_hidden eq 1}Show{else}Hide{/if}" + btext;
								}
{/if}
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
							map.on('move', displayCenter);
							</script>
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}