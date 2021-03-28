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
							<div>
								{if $func eq "user_list"}
									{if $labeled eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$id}&labeled=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$id}&labeled=1">[View Labeled]</a>
									{/if}
									{if $channels eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$id}&channels=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$id}&channels=1">[View Channels]</a>
									{/if}
								{elseif $func eq "wifidbmap"}
									{if $labeled eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=1">[View Labeled]</a>
									{/if}
									{if $channels eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&channels=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&channels=1">[View Channels]</a>
									{/if}
								{elseif $func eq "exp_search"}
									{if $labeled eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_search{$export_url}&labeled=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_search{$export_url}&labeled=1">[View Labeled]</a>
									{/if}
									{if $channels eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_search{$export_url}&channels=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_search{$export_url}&channels=1">[View Channels]</a>
									{/if}
								{elseif $func eq "exp_ap_sig"}
									<a id="toggle_signal_link" onclick="toggle_signal()" href="#">[Show Signals]</a>
									<a id="toggle_rssi_link" onclick="toggle_rssi()" href="#">[Show RSSI]</a>
								{elseif $func eq "exp_ap"}
									{if $labeled eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$id}&labeled=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$id}&labeled=1">[View Labeled]</a>
									{/if}
									{if $channels eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$id}&channels=0">[View Un-Labeled]</a>
									{else}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$id}&channels=1">[View Channels]</a>
									{/if}
								{/if}
							</div>
							<div id='map' style='float:left; width: 100%; height:65vh;'></div>
							<div id='basemap'>
								<input id='WDB_OSM' type='radio' name='rtoggle' value='WDB_OSM' checked='checked'>
								<label for='WDB_OSM'>WDB Light</label>
								<input id='WDB_DARK_MATTER' type='radio' name='rtoggle' value='WDB_DARK_MATTER'>
								<label for='WDB_DARK_MATTER'>WDB Dark</label>
								<input id='WDB_BASIC' type='radio' name='rtoggle' value='WDB_BASIC'>
								<label for='WDB_BASIC'>WDB Basic</label>
								<input id='WDB_ELEV' type='radio' name='rtoggle' value='WDB_ELEV'>
								<label for='WDB_DARK_MATTER'>WDB JAXA Test</label>
							</div>
							<div>
								<button id="latest" onClick="toggle_latest_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Latest</button>
								<button id="daily" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Day</button>
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
								<input type="text" placeholder="Address Search.." name="searchadrbox" id="searchadrbox">
								<button id="searchadr" onClick="searchadr()">Search</button>
							</div>
							<div>
								<button id="Follow_AP" onClick="toggleFollowLatest(this.id)">Follow Latest AP</button>
							</div>
							<script>

							var map = new mapboxgl.Map({
								container: 'map',
								style: '{$style}',
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

							var ShowSignal = false;
							function toggle_signal() {
								const url = new URL(window.location.href);
								if (ShowSignal) {
									map.setLayoutProperty('{$signal_source_name}' + '-signal', 'visibility', 'none');
									var el = document.getElementById("toggle_signal_link");
									el.innerHTML = "Show Signal"
									ShowSignal = false;
									url.searchParams.set('signal', 0);
								} else {
									map.setLayoutProperty('{$signal_source_name}' + '-signal', 'visibility', 'visible');
									var el = document.getElementById("toggle_signal_link");
									el.innerHTML = "Hide Signal"
									
									map.setLayoutProperty('{$signal_source_name}' + '-rssi', 'visibility', 'none');
									var el = document.getElementById("toggle_rssi_link");
									el.innerHTML = "Show RSSI"
									ShowSignal = true;
									url.searchParams.set('signal', 1);
									url.searchParams.delete('rssi');
								}
								window.history.replaceState(null, null, url); // or pushState
							}

							var ShowRSSI = false;
							function toggle_rssi() {
								const url = new URL(window.location.href);
								if (ShowRSSI) {
									map.setLayoutProperty('{$signal_source_name}' + '-rssi', 'visibility', 'none');
									var el = document.getElementById("toggle_rssi_link");
									el.innerHTML = "Show RSSI"
									ShowRSSI = false;
									url.searchParams.set('rssi', 0);
								} else {
									map.setLayoutProperty('{$signal_source_name}' + '-rssi', 'visibility', 'visible');
									var el = document.getElementById("toggle_rssi_link");
									el.innerHTML = "Hide RSSI"
									
									map.setLayoutProperty('{$signal_source_name}' + '-signal', 'visibility', 'none');
									var el = document.getElementById("toggle_signal_link");
									el.innerHTML = "Show Signal"
									ShowRSSI = true;
									url.searchParams.set('rssi', 1);
									url.searchParams.delete('signal');
								}
								window.history.replaceState(null, null, url); // or pushState
							}
							
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
{if $labeled eq 1 }
									map.setLayoutProperty(clicked_id + '-label', 'visibility', 'none');
{/if}
{if $channels eq 1 }
									map.setLayoutProperty(clicked_id + '-channel', 'visibility', 'none');
{/if}
									this.className = '';
									el.firstChild.data = "Show" + btext;
								} else {
									this.className = 'active';
									map.setLayoutProperty(clicked_id, 'visibility', 'visible');
{if $labeled eq 1}
									map.setLayoutProperty(clicked_id + '-label', 'visibility', 'visible');
{/if}
{if $channels eq 1}
									map.setLayoutProperty(clicked_id + '-channel', 'visibility', 'visible');
{/if}
									el.firstChild.data = "Hide" + btext;
								}

							}

							function toggle_latest_layer_button(clicked_id)
							{
								var el = document.getElementById(clicked_id);
								var btext = el.firstChild.data;
								var btext = btext.replace("Show", "");
								var btext = btext.replace("Hide", "");
							
								var visibility = map.getLayoutProperty(clicked_id, 'visibility');
								if (visibility === 'visible') {	
									map.setLayoutProperty(clicked_id, 'visibility', 'none');
									map.setLayoutProperty(clicked_id + 's-label', 'visibility', 'none');
									map.setLayoutProperty(clicked_id + 's-channel', 'visibility', 'none');
									this.className = '';
									el.firstChild.data = "Show" + btext;
								} else {
									this.className = 'active';
									map.setLayoutProperty(clicked_id, 'visibility', 'visible');
									map.setLayoutProperty(clicked_id + 's-label', 'visibility', 'visible');
									map.setLayoutProperty(clicked_id + 's-channel', 'visibility', 'visible');
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

							// Listen for every move event by the user
							const displayCenter = () => {
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
{if $signal eq 1}toggle_signal();{/if}
{if $rssi eq 1}toggle_rssi();{/if}
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
									if (feature.properties.id) text += '<li>SSID: <a href="{$wifidb_host_url}opt/fetch.php?id=' + feature.properties.id + '"><b>' + feature.properties.ssid + '</b></a></li>';
									if (feature.properties.live_id) text += '<li>SSID: <b>' + feature.properties.ssid + '</b></li>';
									if (feature.properties.live_id) text += '<li>Live ID: <b>' + feature.properties.live_id + '</b></li>';
									if (feature.properties.mac) text += '<li>MAC: <b>' + feature.properties.mac + '</b></li>';
									if (feature.properties.manuf) text += '<li>MANUFACTURER: <b>' + feature.properties.manuf + '</b></li>';
									if (feature.properties.chan) text += '<li>CHAN: <b>' + feature.properties.chan + '</b></li>';
									if (feature.properties.NT) text += '<li>NETWORK TYPE: <b>' + feature.properties.NT + '</b></li>';
									if (feature.properties.encry) text += '<li>ENCRYPTION: <b>' + feature.properties.encry + '</b></li>';
									if (feature.properties.radio) text += '<li>RADIO TYPE: <b>' + feature.properties.radio + '</b></li>';
									if (feature.properties.BTx) text += '<li>BASIC TX: <b>' + feature.properties.BTx + '</b></li>';
									if (feature.properties.OTx) text += '<li>OTHER TX: <b>' + feature.properties.OTx + '</b></li>';
									if (feature.properties.lat) text += '<li>LATITUDE: <b>' + feature.properties.lat + '</b></li>';
									if (feature.properties.lon) text += '<li>LONGITUDE: <b>' + feature.properties.lon + '</b></li>';
									if (feature.properties.alt) text += '<li>ALTITUDE: <b>' + feature.properties.alt + '</b></li>';
									if (feature.properties.points) text += '<li>POINTS: <b>' + feature.properties.points + '</b></li>';
									if (feature.properties.FA) text += '<li>First Active: <b>' + feature.properties.FA + '</b></li>';
									if (feature.properties.LA) text += '<li>Last Active: <b>' + feature.properties.LA + '</b></li>';
									if (feature.properties.hist_date) text += '<li>Point Date: <b>' + feature.properties.hist_date + '</b></li>';
									if (feature.properties.signal) text += '<li>Point Signal: <b>' + feature.properties.signal + '</b></li>';
									if (feature.properties.rssi) text += '<li>Point RSSI: <b>' + feature.properties.rssi + '</b></li>';
									if (feature.properties.hist_file_id) text += '<li>File ID: <a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row=' + feature.properties.hist_file_id + '"><b>' + feature.properties.hist_file_id + '</b></a></li>';
									if (feature.properties.user) text += '<li>Username: <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.user + '"><b>' + feature.properties.user + '</b></a></li>';
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
									if (feature.properties.user) text += '<li>Username: <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.user + '"><b>' + feature.properties.user + '</b></a></li>';
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
								var toggleButtonIds = ['WifiDB_weekly','WifiDB_monthly','WifiDB_0to1year','WifiDB_1to2year','WifiDB_2to3year','WifiDB_Legacy','cell_networks'];
								for(var index in toggleButtonIds) {
									var clicked_id = toggleButtonIds[index];
									var el = document.getElementById(clicked_id);
									var btext = el.firstChild.data;
									var btext = btext.replace("Show", "");
									var btext = btext.replace("Hide", "");
									el.firstChild.data = "{if $default_hidden eq 1}Show{else}Hide{/if}" + btext;
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
							map.on('move', displayCenter);
							</script>
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}