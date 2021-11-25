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
{if $func eq "exp_cell_sig"}
							<div style='text-align: center;'>
								Signal History for <a href="{$wifidb_host_url}opt/fetch.php?func=cid&id={$id}"><b>{$ssid}</b></a> (Cell ID:{$id}{if $file_id ne 0} - File ID:{$file_id}{/if}{if $ldivs lte 1} - Points:{$point_count}{else} - Points:({$from} - {(($from / $inc) + 1) * $inc}){/if})
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_cell_sig&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export Cell Signals to JSON{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_cell_sig&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export Cell Signals to KMZ{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{if $file_id ne 0}<a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&id={$id}" title="Show All AP Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>{/if}
								<br>
							</div>
{elseif $func eq "exp_ap_sig"}
							<div style='text-align: center;'>
								Signal History for <a href="{$wifidb_host_url}opt/fetch.php?id={$id}"><b>{$ssid}</b></a> (AP ID:{$id}{if $file_id ne 0} - File ID:{$file_id}{/if}{if $ldivs lte 1} - Points:{$point_count}{else} - Points:({$from} - {(($from / $inc) + 1) * $inc}){/if})
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap_sig&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export AP Signals to JSON{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_ap&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export AP Signals to KMZ{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{if $file_id ne 0}<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&id={$id}" title="Show All AP Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>{/if}
								<br>
							</div>
{elseif $func eq "user_all"}
							<div style='text-align: center;'>
								List APs for <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user={$user}"><b>{$user}</b></a> ({if $ldivs lte 1}Points:{$point_count}{else}Points:({$from} - {(($from / $inc) + 1) * $inc}){/if})
									<a href="{$wifidb_host_url}opt/geojson.php?labeled=1&func=user_all&user={$user}" title="Export User APs to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}opt/export.php?func=user_all&user={$user}" title="Export User APs to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								<br>
							</div>
{elseif $func eq "user_list"}
							<div style='text-align: center;'>
								List APs for <a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$id}"><b>{$title}</b></a> (File ID:{$id}{if $ldivs lte 1} - Points:{$point_count}{else} - Points:({$from} - {(($from / $inc) + 1) * $inc}){/if})
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&id={$id}" title="Export List APs to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_list&id={$id}" title="Export List APs to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								<br>
							</div>
{elseif $func eq "exp_search"}
							<div style='text-align: center;'>
								Search Map {if $ldivs lte 1} - Points:{$point_count}{else} - Points:({$from} - {if ((($from / $inc) + 1) * $inc) gt $point_count}{$point_count}{else}{(($from / $inc) + 1) * $inc}{/if}){/if}<br>
							</div>
{/if}

							<div id='map' style='float:left; width: 100%; height:65vh;'>

								<div id='stylebackground'>
									
									Map Style:<select id="styles" class="dropdownSelect">
									  <option value="WDB_OSM">3D</option>
									  <option value="WDB_BASIC">Basic</option>
									  <option value="WDB_DARK_MATTER">Dark</option>
									</select>
								</div>
								
							</div>
							<div>
{if $func eq "exp_cell_sig"}
								<div id='siglabel'>
									Point 	Label: 
									<input id='lnone' type='radio' name='sltoggle' value='none' checked='checked' onclick="toggle_label()"{if $sig_label eq "none"} checked='checked'{/if}>
									<label for='lnone'>None</label>
									<input id='lrssi' type='radio' name='sltoggle' value='rssi' onclick="toggle_label()"{if $sig_label eq "rssi"} checked='checked'{/if}>
									<label for='lrssi'>RSSI</label>
									<input id='ldate' type='radio' name='sltoggle' value='hist_date' onclick="toggle_label()"{if $sig_label eq "hist_date"} checked='checked'{/if}>
									<label for='ldate'>Date</label>
								</div>
{elseif $func eq "exp_ap_sig"}
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
									<input id='lfa' type='radio' name='sltoggle' value='fa' onclick="toggle_label()"{if $sig_label eq "FA"} checked='checked'{/if}>
									<label for='lfa'>First Active</label>
									<input id='lla' type='radio' name='sltoggle' value='la' onclick="toggle_label()"{if $sig_label eq "LA"} checked='checked'{/if}>
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
{if $ldivs gt 1}
	{if $func eq "exp_ap_sig"}
								<div>
		{for $cdiv=1 to $ldivs}
			{if $from eq (($cdiv - 1) * $inc)}<b>{/if}
			{if $file_id ne 0}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&id={$id}&file_id={$file_id}&from={($cdiv - 1) * $inc}&inc={$inc}" title="Show AP Signals on Map for File ID {$file_id}">Points {($cdiv - 1) * $inc} - {if $cdiv eq $ldivs}{$point_count}{else}{$cdiv * $inc}{/if}</a><br>
			{else}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&id={$id}&from={($cdiv - 1) * $inc}&inc={$inc}" title="Show AP Signals on Map">Points {($cdiv - 1) * $inc} - {if $cdiv eq $ldivs}{$point_count}{else}{$cdiv * $inc}{/if}</a><br>
			{/if}
			{if $from eq (($cdiv - 1) * $inc)}</b>{/if}
		{/for}
								</div>
	{elseif $func eq "user_all"}
								<div>
		{for $cdiv=1 to $ldivs}
			{if $from eq (($cdiv - 1) * $inc)}<b>{/if}
									<a href="{$wifidb_host_url}opt/map.php?func=user_all&user={$user}&from={($cdiv - 1) * $inc}&inc={$inc}" title="Show User APs {($cdiv - 1) * $inc} - {$cdiv * $inc} on Map">Points {($cdiv - 1) * $inc} - {if $cdiv eq $ldivs}{$point_count}{else}{$cdiv * $inc}{/if}</a><br>
			{if $from eq (($cdiv - 1) * $inc)}</b>{/if}
		{/for}
								</div>
	{elseif $func eq "user_list"}
								<div>
		{for $cdiv=1 to $ldivs}
			{if $from eq (($cdiv - 1) * $inc)}<b>{/if}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$id}&from={($cdiv - 1) * $inc}&inc={$inc}" title="Show List APs {($cdiv - 1) * $inc} - {$cdiv * $inc} on Map">Points {($cdiv - 1) * $inc} - {if $cdiv eq $ldivs}{$point_count}{else}{$cdiv * $inc}{/if}</a><br>
			{if $from eq (($cdiv - 1) * $inc)}</b>{/if}
		{/for}
								</div>
	{elseif $func eq "exp_search"}
								<div>
		{for $cdiv=1 to $ldivs}
			{if $from eq (($cdiv - 1) * $inc)}<b>{/if}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_search&from={($cdiv - 1) * $inc}&inc={$inc}{$export_url}" title="Show Search APs {($cdiv - 1) * $inc} - {$cdiv * $inc} on Map">Points {($cdiv - 1) * $inc} - {if $cdiv eq $ldivs}{$point_count}{else}{$cdiv * $inc}{/if}</a><br>
			{if $from eq (($cdiv - 1) * $inc)}</b>{/if}
		{/for}
								</div>
	{/if}
{/if}

							</div>

							<script>

							var map = new maplibregl.Map({
								container: 'map',
								style: '{$tileserver_gl_url}/styles/{$style}/style.json',
								center: {$centerpoint},
								zoom: {$zoom},
								pitch: {$pitch},
								bearing: {$bearing},
{if $ie eq 0}
								maxPitch: 85,
{/if}
							});

							// --- Start Map Style Selection ---
							var layerList = document.getElementById('styles');
							var inputs = layerList.getElementsByTagName('input');

							layerList.addEventListener('change', (e) => {
								var styleId = e.target.value;
								map.setStyle('{$tileserver_gl_url}/styles/' + styleId + '/style.json');
								const url = new URL(window.location.href);
								url.searchParams.set('style', styleId);
								window.history.replaceState(null, null, url); // or pushState
							});
							// --- End Map Style Selection ---
							
{if $terrain ne 0}
							// --- Start Terrain Toggle ---
							/* Code to add a custom button. Idea from Stack Overflow https://stackoverflow.com/a/51683226  */
							class MaplibreGLButtonControl {
							  constructor({
								className = "",
								id = "",
								title = "",
								eventHandler = evtHndlr
							  }) {
								this._className = className;
								this._id = id;
								this._title = title;
								this._eventHandler = eventHandler;
							  }

							  onAdd(map) {
								this._btn = document.createElement("button");
								this._btn.id = this._id;
								this._btn.className = "maplibregl-ctrl-icon" + " " + this._className;
								this._btn.type = "button";
								this._btn.title = this._title;
								this._btn.onclick = this._eventHandler;

								this._container = document.createElement("div");
								this._container.className = "maplibregl-ctrl-group maplibregl-ctrl";
								this._container.appendChild(this._btn);

								return this._container;
							  }

							  onRemove() {
								this._container.parentNode.removeChild(this._container);
								this._map = undefined;
							  }
							}
							
							/* Toggle Terrain Function */
							function terrain_toggle(event) {
								var el = document.getElementById(event.target.id);

								if ( el.classList.contains('maplibregl-terrain') )
								{
									map.addTerrain("terrain");
									Show3d = true;
									el.title = "Hide 3d Terrain"
								} else {
									map.removeTerrain();
									Show3d = false;
									el.title = "Show 3d Terrain"
								}

								el.classList.toggle('maplibregl-terrain');
								el.classList.toggle('maplibregl-terrain-hide');

							}

							/* Toggle Terrain Button */
							const terrain_button = new MaplibreGLButtonControl({
							  className: "maplibregl-terrain",
							  id: "terrain_button",
							  title: "Show 3d Terrain",
							  eventHandler: terrain_toggle
							});

							map.addControl(terrain_button, "top-right");
							// --- End Terrain Toggle ---
{/if}
{if $default_marker}
							// Create a default Marker, colored black
							var marker = new maplibregl.Marker({ {if $sectype eq 1}color: 'green'{elseif $sectype eq 2}color: 'orange'{elseif $sectype eq 3}color: 'red'{else}color: 'purple'{/if}, scale: .5})
							.setLngLat({$default_marker})
							.addTo(map);
{/if}

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
								var layers = [{if $layer_name}{$layer_name}{/if}{if $layer_name && $cell_layer_name},{/if}{if $cell_layer_name}{$cell_layer_name}{/if}]
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
{if $ie eq 0}
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
{/if}

							function init() {
{$layer_source_all}
toggle_label()
							};

							map.once('style.load', function(e) {
								//Add GeoLocate button
								map.addControl(new maplibregl.GeolocateControl({
								positionOptions: {
								enableHighAccuracy: true
								},
								trackUserLocation: true
								}));
								//Add Fullscreen Button
								const fs = new maplibregl.FullscreenControl();
								map.addControl(fs)
								fs._fullscreenButton.classList.add('needsclick');
								//Add Navigation Control
								map.addControl(new maplibregl.NavigationControl({
								  visualizePitch: true,
								  showZoom: true,
								  showCompass: true
								}));
								//Scale Bar
								var scale = new maplibregl.ScaleControl({
									maxWidth: 80,
									unit: 'imperial'
								});
								map.addControl(scale);
								//Ad Inspect
								//map.addControl(new MaplibreInspect());
								map.addControl(new MaplibreInspect({
										showMapPopupOnHover: false,
										showInspectMapPopupOnHover: false,
										selectThreshold: 5
									})
								);
								//WifiDB Information Popup
{if $cell_layer_name}

								map.on('click', function(e) {
									var inspectStyle = map.getStyle().metadata['maplibregl-inspect:inspect'];
									if(!inspectStyle) {
										var features = map.queryRenderedFeatures(e.point, {
											layers: [{$cell_layer_name}]
										});
										if (!features.length) {
											return;
										}
										var feature = features[0];
										
										var text = '<ul>';
										if (feature.properties.id) text += '<li>ID: <a href="{$wifidb_host_url}opt/fetch.php?func=cid&id=' + feature.properties.id + '"><b>' + feature.properties.id + '</b></a></li>';
										if (feature.properties.mapname) text += '<li>Name: <b>' + feature.properties.mapname + '</b></li>';
										if (feature.properties.name) text += '<li>Name: <b>' + feature.properties.name + '</b></li>';
										if (feature.properties.mac) text += '<li>Mac: <b>' + feature.properties.mac + '</b></li>';
										if (feature.properties.points) text  += '<li>Points: <a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&id=' + feature.properties.id + '"><b>' + feature.properties.points + '</b></a></li>';
										if (feature.properties.ssid) text += '<li>SSID: <b>' + feature.properties.ssid + '</b></li>';
										if (feature.properties.authmode) text += '<li>AUTHMODE: <b>' + feature.properties.authmode + '</b></li>';
										if (feature.properties.chan) text += '<li>CHAN: <b>' + feature.properties.chan + '</b></li>';
										if (feature.properties.type) text += '<li>TYPE: <b>' + feature.properties.type + '</b></li>';
										if (feature.properties.rssi) text += '<li>RSSI: <b>' + feature.properties.rssi + '</b></li>';
										if (feature.properties.fa) text += '<li>First Active: <b>' + feature.properties.fa + '</b></li>';
										if (feature.properties.la) text += '<li>Last Active: <b>' + feature.properties.la + '</b></li>';									
										if (feature.properties.hist_date) text += '<li>Date: <b>' + feature.properties.hist_date + '</b></li>';
										if (feature.properties.lat) text += '<li>Latitude: <b>' + feature.properties.lat + '</b></li>';
										if (feature.properties.lon) text += '<li>Logitude: <b>' + feature.properties.lon + '</b></li>';
										if (feature.properties.alt) text += '<li>Altitude: <b>' + feature.properties.alt + ' m</b></li>';
										if (feature.properties.sats) text += '<li>GPS Sats: <b>' + feature.properties.sats + '</b></li>';
										if (feature.properties.accuracy) text += '<li>GPS Accuracy: <b>' + feature.properties.accuracy + ' m</b></li>';
										if (feature.properties.hdop) text += '<li>GPS HDOP: <b>' + feature.properties.hdop + '</b></li>';
										if (feature.properties.hist_file_id) text += '<li>File ID: <a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row=' + feature.properties.hist_file_id + '"><b>' + feature.properties.hist_file_id + '</b></a></li>';
										if (feature.properties.user) text += '<li>Username: <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.user + '"><b>' + feature.properties.user + '</b></a></li>';
										text += '</ul>';

										var popup = new maplibregl.Popup()
											.setLngLat(map.unproject(e.point))
											.setHTML(text)
											.addTo(map);
									}
								});
{/if}
{if $layer_name}

								map.on('click', function(e) {
									var inspectStyle = map.getStyle().metadata['maplibregl-inspect:inspect'];
									if(!inspectStyle) {
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
										if (feature.properties.mac) text += '<li>Mac: <b>' + feature.properties.mac + '</b></li>';
										if (feature.properties.points) text  += '<li>Points: <a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&id=' + feature.properties.id + '"><b>' + feature.properties.points + '</b></a></li>';
										if (feature.properties.signal) text += '<li>Signal: <b>' + feature.properties.signal + '</b></li>';
										if (feature.properties.rssi) text += '<li>RSSI: <b>' + feature.properties.rssi + '</b></li>';
										if (feature.properties.chan) text += '<li>Channel: <b>' + feature.properties.chan + '</b></li>';
										if (feature.properties.auth) text += '<li>Auth: <b>' + feature.properties.auth + '</b></li>';
										if (feature.properties.encry) text += '<li>Encryption: <b>' + feature.properties.encry + '</b></li>';
										if (feature.properties.manuf) text += '<li>Manufacturer: <b>' + feature.properties.manuf + '</b></li>';
										if (feature.properties.nt) text += '<li>Network Type: <b>' + feature.properties.nt + '</b></li>';
										if (feature.properties.radio) text += '<li>Radio Type: <b>' + feature.properties.radio + '</b></li>';
										if (feature.properties.fa) text += '<li>First: <b>' + feature.properties.fa + '</b></li>';
										if (feature.properties.la) text += '<li>Last: <b>' + feature.properties.la + '</b></li>';
										if (feature.properties.high_gps_sig) text += '<li>High Signal w/GPS: <b>' + feature.properties.high_gps_sig + '</b></li>';
										if (feature.properties.high_gps_rssi) text += '<li>High RSSI w/GPS: <b>' + feature.properties.high_gps_rssi + '</b></li>';
										if (feature.properties.hist_date) text += '<li>Date: <b>' + feature.properties.hist_date + '</b></li>';
										if (feature.properties.lat) text += '<li>Latitude: <b>' + feature.properties.lat + '</b></li>';
										if (feature.properties.lon) text += '<li>Logitude: <b>' + feature.properties.lon + '</b></li>';
										if (feature.properties.alt) text += '<li>Altitude: <b>' + feature.properties.alt + ' m</b></li>';
										if (feature.properties.sats) text += '<li>GPS Sats: <b>' + feature.properties.sats + '</b></li>';
										if (feature.properties.accuracy) text += '<li>GPS Accuracy: <b>' + feature.properties.accuracy + ' m</b></li>';
										if (feature.properties.hdop) text += '<li>GPS HDOP: <b>' + feature.properties.hdop + '</b></li>';
										if (feature.properties.hist_file_id) text += '<li>File ID: <a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row=' + feature.properties.hist_file_id + '"><b>' + feature.properties.hist_file_id + '</b></a> <a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&labeled=0&id={$id}&file_id=' + feature.properties.hist_file_id + '"><b>(Map)</b></a></li>';
										if (feature.properties.user) text += '<li>Username: <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user=' + feature.properties.user + '"><b>' + feature.properties.user + '</b></a></li>';
										text += '</ul>';
										
										var popup = new maplibregl.Popup()
											.setLngLat(map.unproject(e.point))
											.setHTML(text)
											.addTo(map);
									}
								});
{/if}

								// indicate that the symbols are clickableby changing the cursor style to 'pointer'.
								map.on('mousemove', function(e) {
								
									var inspectStyle = map.getStyle().metadata['maplibregl-inspect:inspect'];
									if(!inspectStyle) {
										var features = map.queryRenderedFeatures(e.point, {
											layers: [{if $layer_name}{$layer_name}{/if}{if $layer_name && $cell_layer_name},{/if}{if $cell_layer_name}{$cell_layer_name}{/if}]
										});
										map.getCanvas().style.cursor = (features.length) ? 'pointer' : '';
									}
								});
							});
							map.on('style.load', function () {
								// Reset toggle buttons since the layers reset on style change
{if $func ne "exp_ap_sig" &&  $func ne "exp_cell_sig"}
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
{if $ie eq 0}
							map.on('move', displayCenter);
{/if}

							//Trigger map resize when menu button is clicked.
							$(".bt-menu-trigger").click(function () {
								$(this).toggleClass("buttonstyle")
										.trigger('classChanged');
							});
					  
							$(".bt-menu-trigger").on(
								"classChanged", function () {
								$(document).ready( function () {
											map.resize();
									});
								}
							);
	
							</script>
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}