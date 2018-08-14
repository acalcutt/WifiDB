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
											<div id='map' style='float:left; width: 100%; height:75vh;'></div>
											<div id='basemap'>
												<input id='WDB_NE2' type='radio' name='rtoggle' value='WDB_NE2' checked='checked'>
												<label for='WDB_NE2'>Natural Earth II + OSM</label>
												<input id='WDB_OSM' type='radio' name='rtoggle' value='WDB_OSM'>
												<label for='WDB_OSM'>OSM Bright</label>
												<input id='WDB_KB' type='radio' name='rtoggle' value='WDB_KB'>
												<label for='WDB_KB'>Klokantech Basic</label>
											</div>
											<div>
												<input type="text" placeholder="Address Search.." name="searchadrbox" id="searchadrbox">
												<button id="searchadr" onClick="searchadr()">Search</button>
											</div>
											<div>
												{if $list eq 1}
													{if $labeled eq 1}
														<a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$id}&labeled=0">[View Un-Labeled]</a>
													{else}
														<a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$id}&labeled=1">[View Labeled]</a>
													{/if}
												{else}
													{if $labeled eq 1}
														<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$id}&labeled=0">[View Un-Labeled]</a>
													{else}
														<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$id}&labeled=1">[View Labeled]</a>
													{/if}
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

												// indicate that the symbols are clickableby changing the cursor style to 'pointer'.
												map.on('mousemove', function(e) {
													var features = map.queryRenderedFeatures(e.point, {
														layers: [{$layer_name}]
													});
													map.getCanvas().style.cursor = (features.length) ? 'pointer' : '';
												});
											});
											map.on('style.load', () => {
												// Reload dynamic layers since they are lost on style change
												const waiting = () => {
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
{include file="footer.tpl"}