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
								<a href="{$wifidb_host_url}api/gpx.php?func=exp_cell_sig&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export Cell Signals to GPX{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{if $file_id ne 0}<a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&id={$id}" title="Show All AP Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>{/if}
								<br>
							</div>
{elseif $func eq "exp_ap_sig"}
							<div style='text-align: center;'>
								Signal History for <a href="{$wifidb_host_url}opt/fetch.php?id={$id}"><b>{$ssid}</b></a> (AP ID:{$id}{if $file_id ne 0} - File ID:{$file_id}{/if}{if $ldivs lte 1} - Points:{$point_count}{else} - Points:({$from} - {(($from / $inc) + 1) * $inc}){/if})
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap_sig&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export AP Signals to JSON{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_ap&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export AP Signals to KMZ{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								<a href="{$wifidb_host_url}api/gpx.php?func=exp_ap_sig&id={$id}{if $file_id ne 0}&file_id={$file_id}{/if}" title="Export AP Signals to GPX{if $file_id ne 0} (for this file){/if}"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{if $file_id ne 0}<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&id={$id}" title="Show All AP Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>{/if}
								<br>
							</div>
{elseif $func eq "user_all"}
							<div style='text-align: center;'>
								List APs for <a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user={$user}"><b>{$user}</b></a> ({if $ldivs lte 1}Points:{$point_count}{else}Points:({$from} - {(($from / $inc) + 1) * $inc}){/if})
									<a href="{$wifidb_host_url}opt/geojson.php?&func=user_all&user={$user}" title="Export User APs to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}opt/export.php?func=user_all&user={$user}" title="Export User APs to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}opt/gpx.php?func=user_all&user={$user}" title="Export User APs to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								<br>
							</div>
{elseif $func eq "user_list"}
							<div style='text-align: center;'>
								List APs for <a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$id}"><b>{$title}</b></a> (File ID:{$id}{if $ldivs lte 1} - Points:{$point_count}{else} - Points:({$from} - {(($from / $inc) + 1) * $inc}){/if})
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&id={$id}" title="Export List APs to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_list&id={$id}" title="Export List APs to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								<a href="{$wifidb_host_url}api/gpx.php?func=exp_list&id={$id}" title="Export List APs to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								<br>
							</div>
{elseif $func eq "exp_search"}
							<div style='text-align: center;'>
								Search Map {if $ldivs lte 1} - Points:{$point_count}{else} - Points:({$from} - {if ((($from / $inc) + 1) * $inc) gt $point_count}{$point_count}{else}{(($from / $inc) + 1) * $inc}{/if}){/if}<br>
							</div>
{/if}
							<div id='mapcontainer'>
								<div id='map'>
									<div id="controls">
										<table style="width: 100%">
											<tr>
												<td style="width: 40px"></td>
												<td>
													<span id="all_controls" class="all_controls">
														<div>
															<span class="inline nowrap controls-icon">Map Style:
																<select id="styles" class="dropdownSelect">
																  <option value="WDB_OSM"{if $style eq "WDB_OSM"} selected{/if}>3D</option>
																  <option value="WDB_BASIC"{if $style eq "WDB_BASIC"} selected{/if}>Basic</option>
																  <option value="WDB_DARK_MATTER"{if $style eq "WDB_DARK_MATTER"} selected{/if}>Dark</option>
																  <option value="WDB_SAT"{if $style eq "WDB_SAT"} selected{/if}>Satellite</option>
																</select>
															</span>
{if $func eq "exp_cell_sig"}				
															<span class="inline nowrap controls-icon">Point Label:
																<select id="pointlabels" class="dropdownSelect">
																  <option value="none"{if $sig_label eq "none"} selected{/if}>None</option>
																  <option value="rssi"{if $sig_label eq "rssi"} selected{/if}>RSSI</option>
																  <option value="hist_date"{if $sig_label eq "hist_date"} selected{/if}>Date</option>
																</select>
															</span>
{elseif $func eq "exp_ap_sig"}
															<span class="inline nowrap controls-icon">Point Label:
																<select id="pointlabels" class="dropdownSelect">
																  <option value="none"{if $sig_label eq "none"} selected{/if}>None</option>
																  <option value="signal"{if $sig_label eq "signal"} selected{/if}>Signal</option>
																  <option value="rssi"{if $sig_label eq "rssi"} selected{/if}>RSSI</option>
																  <option value="hist_date"{if $sig_label eq "hist_date"} selected{/if}>Date</option>
																</select>
															</span>
{else}
															<span class="inline nowrap controls-icon">Point Label:
																<select id="pointlabels" class="dropdownSelect">
																  <option value="none"{if $sig_label eq "none"} selected{/if}>None</option>
																  <option value="ssid"{if $sig_label eq "ssid"} selected{/if}>SSID</option>
																  <option value="mac"{if $sig_label eq "mac"} selected{/if}>Mac</option>
																  <option value="chan"{if $sig_label eq "chan"} selected{/if}>Channel</option>
																  <option value="fa"{if $sig_label eq "fa"} selected{/if}>First Active</option>
																  <option value="la"{if $sig_label eq "la"} selected{/if}>Last Active</option>
																  <option value="points"{if $sig_label eq "points"} selected{/if}>Points</option>
																  <option value="high_gps_sig"{if $sig_label eq "high_gps_sig"} selected{/if}>High Signal</option>
																  <option value="high_gps_rssi"{if $sig_label eq "high_gps_rssi"} selected{/if}>High RSSI</option>
																</select>
															</span>
{/if}
															<span class="inline nowrap controls-icon">
																	<input class="address-input" type="text" placeholder="Address Search.." name="searchadrbox" id="searchadrbox">
																	<button class="toggle-button" id="searchadr" onClick="searchadr()">Search</button>
															</span>
{if $func eq "wifidbmap"}
															<span class="inline nowrap">
																<button class="toggle-button track-button" id="track_toggle" onClick="toggle_track(this.id)">Enable Track</button>
																<button class="toggle-button track-button" id="track_download" onClick="track_download()">Download Track</button>
															</span>
															<span class="inline nowrap">
																<button class="toggle-button latest-button" id="Follow_AP" onClick="toggleFollowLatest(this.id)">Follow Latest</button>
																<button class="toggle-button latest-button" id="latests" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Latest</button>
															</span>
{/if}

{if $func eq "wifidbmap" || $func eq "user_list"}
															<button class="toggle-button" id="dailys" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Day</button>
															<button class="toggle-button" id="WifiDB_weekly" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Week</button>
															<button class="toggle-button" id="WifiDB_monthly" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Month</button>
															<button class="toggle-button" id="WifiDB_0to1year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Year</button>
															<button class="toggle-button" id="WifiDB_1to2year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 1-2 year</button>
															<button class="toggle-button" id="WifiDB_2to3year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 2-3 year</button>
									<button class="toggle-button" id="WifiDB_3to5year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 3-5 year</button>
									<button class="toggle-button" id="WifiDB_5to10year" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 5-10 year</button>
									<button class="toggle-button" id="WifiDB_10yrplus" onClick="toggle_layer_button(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} 10+ year</button>
															<button class="toggle-button" id="cell_networks" onClick="toggle_cell_group(this.id)">{if $default_hidden eq 1}Show{else}Hide{/if} Cell Networks</button>										<button class="toggle-button" id="heatmap_toggle" onClick="toggle_heatmap_group(this.id)">Show Heatmap</button>{/if}
														</div>
													</span>
												</td>
												<td style="width: 75px"></td>
											</tr>	
										</table>
									</div>
								</div>
							</div>
							<div>
				
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

							<script type="module">
		// maplibre-gl 6 is ESM only and exports no default, so this namespace
		// import is what stands in for the `maplibregl` global this file used
		// to get from a <script src>. Bare specifiers resolve through the
		// import map emitted by opt/map.php; the files are in lib/js.
		import * as maplibregl from 'maplibre-gl';
		import mlcontour from 'maplibre-contour';
		import MaplibreInspect from '@maplibre/maplibre-gl-inspect';
		import { Protocol } from 'pmtiles';
{if ($wifidb_swarm_debug|default:'') ne ''}
		// ?swarmdebug=1 -- what the server resolved for this request. Printed
		// whether or not any sources came back, since an empty list is exactly
		// the case worth explaining.
		console.info('[wifidb-swarm] server:', {$wifidb_swarm_debug});
{/if}
{if ($wifidb_swarm_sources|default:'[]') ne '[]'}
		// Pulls in WebTorrent, so it is imported only when there is a swarm
		// configured to make use of it.
		import { enableSwarm } from 'wifidb-swarm';
{/if}

		// Lets a style name a pmtiles:// source, so a bucket archive can be
		// read directly rather than through a tile endpoint. Registered before
		// any map is constructed, because MapLibre resolves source URLs while
		// building the style.
		//
		// `metadata: true` makes the protocol build its TileJSON from the
		// archive's own metadata — the vector_layers mvtd wrote — instead of
		// the minimal header-derived one, so layer names and fields come from
		// the archive rather than being restated here.
		//
		// pmtiles-torrent, when it is loaded, registers a TorrentSource on this
		// same protocol under the archive's URL. It does not replace any of
		// this: register and the swarm serves the bytes, do not and the
		// identical URL falls back to HTTP range reads.
		const pmtilesProtocol = new Protocol({ metadata: true });
		maplibregl.addProtocol('pmtiles', pmtilesProtocol.tile);

{if ($wifidb_swarm_sources|default:'[]') ne '[]'}
		// Joins each archive's swarm and registers it on the protocol above.
		// Resolves either way -- an archive the swarm cannot serve is simply
		// left unregistered and read over HTTP -- and init() waits on it,
		// because the protocol binds an HTTP source to any URL it does not
		// already know by the time the layers ask for it.
		//
		// Published to window so the transport can be checked from a console:
		// wifidbSwarm.stats() reports peers and bytes per bucket, which is the
		// difference between a swarm read and an HTTP read that merely passed
		// through this code.
		const swarmReady = enableSwarm({
			protocol: pmtilesProtocol,
			archives: {$wifidb_swarm_sources|default:'[]'},
{if $wifidb_swarm_wait_ms}
			// ?swarmwait=<seconds> -- a debugging budget, to find out whether a
			// peer ever answers rather than how long one takes.
			metadataTimeoutMs: {$wifidb_swarm_wait_ms},
			batchTimeoutMs: {$wifidb_swarm_wait_ms},
{/if}
		}).then(function (handle) {
			window.wifidbSwarm = handle;
			return handle;
		});
{else}
		const swarmReady = Promise.resolve(null);
{/if}

		//Maplibre map object
		// Routes a tile through the swarm when the archive it names has been
		// joined, and returns every other URL untouched. Sources stay ordinary
		// https TileJSON, so MapLibre parses them itself and anything else
		// reading this style -- maplibre-gl-inspect included -- keeps working;
		// registering a protocol for `https` would capture styles, glyphs and
		// sprites as well, and the pass-through case would mean reimplementing
		// the loader.
		//
		// Reads window.wifidbSwarm rather than closing over the handle: the
		// batch resolves after the map is constructed, and a tile requested
		// before then is simply fetched over HTTP, which is correct.
		function swarmTransformRequest(url) {
			var swarm = window.wifidbSwarm;
			if (!swarm || typeof swarm.rewrite !== 'function') return { url: url };
			return { url: swarm.rewrite(url) };
		}

		var map = new maplibregl.Map({
			transformRequest: swarmTransformRequest,
			container: 'map',
			style: '{$tileserver_gl_url}/styles/{$style}/style.json',
			center: {$centerpoint},
			zoom: {$zoom},
			pitch: {$pitch},
			bearing: {$bearing},
			attributionControl: false,
			maplibreLogo: false,
{if $ie eq 0}
			maxPitch: 85,
{/if}
		});

		map.addControl(new maplibregl.AttributionControl(), 'top-right');
		map.addControl(new maplibregl.LogoControl(),'top-left');
		
		/*
		var geocoder_api = {
			forwardGeocode: function (config) {
				var features = [];
				var address = document.getElementById('searchadrbox').value;
				var address = address.replace(/ /g, "+");
				var url =
					'https://nominatim.openstreetmap.org/search?q=' +
					config.query +
					'&format=geojson&polygon_geojson=1&addressdetails=1';
				console.log('url: ', url);
				var req = new XMLHttpRequest();
				req.overrideMimeType("application/json");
				req.open('GET', url, true);
				req.onload = function() {
					//console.log(req.responseText);
					var json = JSON.parse(req.responseText);
					console.log(json.features);
					for (var a = 0; a < json.features.length; a++) {
						var feature = json.features[a];
						console.log(feature);
						
						var center = [
							feature.bbox[0] +
							(feature.bbox[2] - feature.bbox[0]) / 2,
							feature.bbox[1] +
							(feature.bbox[3] - feature.bbox[1]) / 2
						];
						
						var point = {
							type: 'Feature',
							geometry: {
								type: 'Point',
								coordinates: center
							},
							place_name: feature.properties.display_name,
							properties: feature.properties,
							text: feature.properties.display_name,
							place_type: ['place'],
							center: center
						};
						features.push(point);
					}
				};
				req.send(null);
				console.log(features);
				return {
					features: features
				};
			}
		}
		map.addControl(
			new MaplibreGeocoder(geocoder_api, {
				maplibregl: maplibregl
			}), 'top-right'
		);		
		*/
		
		
		// --- Internet Explorer compatibility for MaplibreGLButtonControl ---
		function _classCallCheck(instance, Constructor) {
			if (!(instance instanceof Constructor)) {
				throw new TypeError("Cannot call a class as a function");
			}
		}

		function _defineProperties(target, props) {
			for (var i = 0; i < props.length; i++) {
				var descriptor = props[i];
				descriptor.enumerable = descriptor.enumerable || false;
				descriptor.configurable = true;
				if ("value" in descriptor) descriptor.writable = true;
				Object.defineProperty(target, descriptor.key, descriptor);
			}
		}

		function _createClass(Constructor, protoProps, staticProps) {
			if (protoProps) _defineProperties(Constructor.prototype, protoProps);
			if (staticProps) _defineProperties(Constructor, staticProps);
			return Constructor;
		}

		/* Code to add a custom button. Idea from Stack Overflow https://stackoverflow.com/a/51683226  */
		var MaplibreGLButtonControl = /*#__PURE__*/ function() {
			function MaplibreGLButtonControl(_ref) {
				var _ref$className = _ref.className,
					className = _ref$className === void 0 ? "" : _ref$className,
					_ref$id = _ref.id,
					id = _ref$id === void 0 ? "" : _ref$id,
					_ref$title = _ref.title,
					title = _ref$title === void 0 ? "" : _ref$title,
					_ref$eventHandler = _ref.eventHandler,
					eventHandler = _ref$eventHandler === void 0 ? evtHndlr : _ref$eventHandler;
				_classCallCheck(this, MaplibreGLButtonControl);
				this._className = className;
				this._id = id;
				this._title = title;
				this._eventHandler = eventHandler;
			}
			_createClass(MaplibreGLButtonControl, [{
				key: "onAdd",
				value: function onAdd(map) {
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
			}, {
				key: "onRemove",
				value: function onRemove() {
					this._container.parentNode.removeChild(this._container);
					this._map = undefined;
				}
			}]);
			return MaplibreGLButtonControl;
		}();

{if $default_marker}
		// Create a default Marker
		var marker = new maplibregl.Marker({ {if $sectype eq 1}color: 'green'{elseif $sectype eq 2}color: 'orange'{elseif $sectype eq 3}color: 'red'{else}color: 'purple'{/if}, scale: .5})
		.setLngLat({$default_marker})
		.addTo(map);
{/if}
		// --- Start Map Style Selection ---
		var styleList = document.getElementById('styles');
		styleList.addEventListener('change', function(e) {
			var styleId = e.target.value;
			map.setStyle('https://tiles.wifidb.net/styles/' + styleId + '/style.json');
{if $ie eq 0}
			var url = new URL(window.location.href);
			url.searchParams.set('style', styleId);
			window.history.replaceState(null, null, url); // or pushState
{/if}
		});
		// --- End Map Style Selection ---

		// --- Start Point Label Selection ---
		function toggle_label() {
			var point_labels = document.getElementById('pointlabels');
			var point_labels_selected = pointlabels.options[point_labels.selectedIndex].value;
			var layers = [{if $layer_name}{$layer_name}{/if}{if $layer_name && $cell_layer_name},{/if}{if $cell_layer_name}{$cell_layer_name}{/if}]
			for (var i = 0, length = point_labels.options.length; i < length; i++) {
				var option_text = point_labels.options[i].text;
				var option_value = point_labels.options[i].value;
				if (option_value === point_labels_selected) {
					if (option_value !== 'none') {
						for (var j = 0, length2 = layers.length; j < length2; j++) {
							if (layers[j] !== "latests") {
								var layer_visibility = map.getLayoutProperty(layers[j], 'visibility');
								if (layer_visibility === 'visible') {
									map.setLayoutProperty(layers[j] + '-' + option_value, 'visibility', 'visible');
								}else{
									map.setLayoutProperty(layers[j] + '-' + option_value, 'visibility', 'none');
								}
							}
						}
					}
{if $ie eq 0}
					var url = new URL(window.location.href);
					url.searchParams.set("sig_label", option_value);
					window.history.replaceState(null, null, url);
{/if}
				} else {
					if (option_value !== 'none') {
						for (var j = 0, length2 = layers.length; j < length2; j++) {
							if (layers[j] !== "latests") {
								map.setLayoutProperty(layers[j] + '-' + option_value, 'visibility', 'none');
							}
						}
					}
				}
			};
		};
		var pointlabelsList = document.getElementById('pointlabels');
		pointlabelsList.addEventListener('change', toggle_label);
		// --- End Point Label Selection ---

		//Scale Bar
		var scale = new maplibregl.ScaleControl({
			maxWidth: 80,
			unit: 'imperial'
		});
		map.addControl(scale, 'bottom-right');

{if $terrain ne 0}
		// --- Start Terrain Toggle ---
		if (maplibregl.supported()) {
			map.addControl(
				new maplibregl.TerrainControl({
					source: "terrain_source",
					exaggeration: 1,
					elevationOffset: 0
				})
			);
		}
		// --- End Terrain Toggle ---
{/if}
		// --- Start Control Toggle ---
		/* Toggle Control Function */
		function control_toggle() {
			var el = document.getElementById('menu_button');
			var ac = document.getElementById('all_controls');
			if (el.classList.contains('controls-hide-icon')) {
				el.title = 'Show Controls';
				el.classList.add('controls-show-icon');
				el.classList.remove('controls-hide-icon');
				ac.classList.add('hidden');
			} else {
				el.title = 'Hide Controls';
				el.classList.add('controls-hide-icon');
				el.classList.remove('controls-show-icon');
				ac.classList.remove('hidden');
			}
		}

		/* Toggle Control Button */
		var menu_button = new MaplibreGLButtonControl({
			className: "controls-hide-icon",
			id: "menu_button",
			title: "Hide Controls",
			eventHandler: control_toggle
		});
		map.addControl(menu_button, "bottom-left");
		// --- End Control Toggle ---
		
		//Add GeoLocate button
		var GeolocateControl = new maplibregl.GeolocateControl({
			positionOptions: {
				enableHighAccuracy: true
			},
			trackUserLocation: true
		});
		map.addControl(GeolocateControl);		
		
		//Create live track
		var track = false;
		var gpx_track_array= [];
		var track_geojson = {
			'type': 'FeatureCollection',
			'features': [
				{
					'type': 'Feature',
					'geometry': {
						'type': 'LineString',
						'coordinates': []
					}
				}
			]
		};

		//Update track geojson on geolocate event
		GeolocateControl.on('geolocate', function(e) {
			if (track) {
				if ((e.coords.latitude != null) && (e.coords.longitude != null)) {
					console.log(e);
					// append new coordinates to the lineString
					track_geojson.features[0].geometry.coordinates.push([e.coords.longitude,e.coords.latitude]);
					
					// then update the map
					map.getSource('track_line').setData(track_geojson);
					map.moveLayer('track_line_layer');
					
					// append new coordinates to gpx
					$trkpt_str = '<trkpt lat="' + e.coords.latitude + '" lon="' + e.coords.longitude + '">';
					if (e.coords.altitude != null) {
						$trkpt_str += '<ele>' + e.coords.altitude + '</ele>';
					}
					if (e.coords.speed != null) {
						$trkpt_str += '<speed>' + e.coords.speed + '</speed>';
					}
					if (e.coords.accuracy != null) {
						var hdop = e.coords.accuracy * 4;
						$trkpt_str += '<hdop>' + hdop + '</hdop>';
					}
					if (e.timestamp != null) {
						var date = new Date(e.timestamp).toISOString();
						$trkpt_str += '<time>'+ date + '</time>';
					}
					$trkpt_str += '</trkpt>\r';
					gpx_track_array.push([$trkpt_str]);
				}
			}
		});

		//Add Fullscreen Button
		var fs = new maplibregl.FullscreenControl();
		map.addControl(fs);

		//map.addControl(new MapLibreStyleSwitcherControl());
		
		fs._fullscreenButton.classList.add('needsclick'); //Add Navigation Control
		map.addControl(new maplibregl.NavigationControl({
			visualizePitch: true,
			showZoom: true,
			showCompass: true
		}));

		//Inspect Button
		map.addControl(new MaplibreInspect({
			// maplibre-gl 6 exports no global, so the plugin cannot construct a
			// Popup for itself. It asks for a ready-made one rather than for the
			// namespace -- `popup`, not `maplibregl`, which its own message says
			// and which is easy to read past.
			popup: new maplibregl.Popup({
				closeButton: false,
				closeOnClick: false,
			}),
			showMapPopupOnHover: false,
			showInspectMapPopupOnHover: false,
			selectThreshold: 5
		}));

		function GoToLatest() {
			var url = '{$wifidb_host_url}api/geojson.php?func=exp_latest_ap';
			console.log('url: ', url);
			map.getSource('latests').setData(url);
			var req = new XMLHttpRequest();
			req.overrideMimeType("application/json");
			req.open('GET', url, true);
			req.onload = function() {
				console.log(req.responseText);
				var jsonResponse = JSON.parse(req.responseText);
				var lat = parseFloat(jsonResponse.features[0].properties.lat);
				var lng = parseFloat(jsonResponse.features[0].properties.lon);
				console.log('lat: ', lat);
				console.log('lng: ', lng);
				var lnglat = [lng.toFixed(6), lat.toFixed(6)];
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
				el.firstChild.data = "Follow Latest";
			} else {
				GoToLatest();
				LatestTimer = setInterval(function() {
					GoToLatest();
				}, 2500);
				FollowLatest = true;
				el.firstChild.data = "Un-Follow Latest";
			}
		}
		// --- Start Year Visibility Functions ---
		function toggle_layer_button(clicked_id) {
			var radios = document.getElementsByName('sltoggle');
			var el = document.getElementById(clicked_id);
			var btext = el.firstChild.data;
			var btext = btext.replace("Show", "");
			var btext = btext.replace("Hide", "");
			var point_labels = document.getElementById('pointlabels');
			var point_labels_selected = pointlabels.options[point_labels.selectedIndex].value;
			var visibility = map.getLayoutProperty(clicked_id, 'visibility');
			if (visibility === 'visible') {
				map.setLayoutProperty(clicked_id, 'visibility', 'none');
				for (var i = 0, length = point_labels.options.length; i < length; i++) {
					var option_text = point_labels.options[i].text;
					var option_value = point_labels.options[i].value;
					if (option_value === point_labels_selected && option_value !== 'none') {
						map.setLayoutProperty(clicked_id + '-' + option_value, 'visibility', 'none');
					}
				}
				if(clicked_id === 'latests'){
					map.setLayoutProperty(clicked_id + '-latest', 'visibility', 'none');
				}
				el.firstChild.data = "Show" + btext;
				this.className = '';
			} else {
				map.setLayoutProperty(clicked_id, 'visibility', 'visible');
				if(clicked_id === 'latests'){
					map.setLayoutProperty(clicked_id + '-latests', 'visibility', 'visible');
					map.setLayoutProperty(clicked_id + '-latest', 'visibility', 'visible');
				}else{
					for (var i = 0, length = point_labels.options.length; i < length; i++) {
						var option_text = point_labels.options[i].text;
						var option_value = point_labels.options[i].value;
						if (option_value === point_labels_selected && option_value !== 'none') {
							map.setLayoutProperty(clicked_id + '-' + option_value, 'visibility', 'visible');
						}
					}
				}
				el.firstChild.data = "Hide" + btext;
				this.className = 'active';
			}
		}

		function toggle_cell_group(btn_id) {
			var cellBuckets = ['cell_daily','cell_weekly','cell_monthly',
			                   'cell_0to1year','cell_1to2year','cell_2to3year',
			                   'cell_3to5year','cell_5to10year','cell_10yrplus'];
			var el = document.getElementById(btn_id);
			var btext = el.firstChild.data;
			btext = btext.replace("Show", "").replace("Hide", "");
			var point_labels = document.getElementById('pointlabels');
			var point_labels_selected = point_labels ? point_labels.options[point_labels.selectedIndex].value : 'none';
			var currentVis = 'none';
			for (var i = 0; i < cellBuckets.length; i++) {
				try {
					var v = map.getLayoutProperty(cellBuckets[i], 'visibility');
					if (v !== undefined) { currentVis = v; break; }
				} catch(e) {}
			}
			var newVis = (currentVis === 'visible') ? 'none' : 'visible';
			for (var i = 0; i < cellBuckets.length; i++) {
				try { map.setLayoutProperty(cellBuckets[i], 'visibility', newVis); } catch(e) {}
				if (point_labels_selected && point_labels_selected !== 'none') {
					try { map.setLayoutProperty(cellBuckets[i] + '-' + point_labels_selected, 'visibility', newVis); } catch(e) {}
				}
			}
			el.firstChild.data = (newVis === 'visible' ? 'Hide' : 'Show') + btext;
		}

		function toggle_heatmap_group(btn_id) {
			var heatmapLayers = ['WifiDB_heatmap-heatmap', 'WifiDB_cell_heatmap-heatmap'];
			var el = document.getElementById(btn_id);
			var currentVis = 'none';
			for (var i = 0; i < heatmapLayers.length; i++) {
				try {
					var v = map.getLayoutProperty(heatmapLayers[i], 'visibility');
					if (v !== undefined) { currentVis = v; break; }
				} catch(e) {}
			}
			var newVis = (currentVis === 'visible') ? 'none' : 'visible';
			for (var i = 0; i < heatmapLayers.length; i++) {
				try { map.setLayoutProperty(heatmapLayers[i], 'visibility', newVis); } catch(e) {}
			}
			el.firstChild.data = (newVis === 'visible' ? 'Hide' : 'Show') + ' Heatmap';
		}

		function toggle_track(clicked_id) {
			var el = document.getElementById(clicked_id);
			if (track) {
				track = false;
				gpx_track_array.push(['</trkseg></trk>\r']);
				console.log("Track Disabled");
				el.firstChild.data = "Enable Track";
			} else {
				var date = new Date().toISOString();
				gpx_track_array.push(['<trk><name>' + date + '</name><desc></desc><trkseg>\r']);			
				track = true;
				console.log("Track Enabled");
				el.firstChild.data = "Disabled Track";
			}
		}
		
		function track_download() {
			var date = new Date().toISOString();
			download(create_gpx(), 'wdb_map_track_' + date + '.gpx', 'text/plain');
		}

		function download(content, fileName, contentType) {

			var a = document.createElement("a");
			a.href = URL.createObjectURL(new Blob([content]));
			a.setAttribute("download", fileName);
			document.body.appendChild(a);
			a.click();
			document.body.removeChild(a);
		}

		function create_gpx() {
			var track_state = track;
			if (track_state) {
				track = false;
				gpx_track_array.push(['</trkseg></trk>\r']);
			}
			var gpx = '<gpx xmlns="http://www.topografix.com/GPX/1/1" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.topografix.com/GPX/1/1 http://www.topografix.com/GPX/1/1/gpx.xsd" version="1.1" creator="wifidb.net"><metadata/>\r'
			for (var g = 0; g < gpx_track_array.length; ++g) {
				gpx += gpx_track_array[g];
			}
			gpx += '</gpx>'
			if (track_state) {
				var date = new Date().toISOString();
				gpx_track_array.push(['<trk><name>' + date + '</name><desc></desc><trkseg>\r']);	
				track = true;
			}
			return gpx;
		}

		// --- End Year Visibility Functions ---
		// --- Start Google Geocoding API Functions ---

		function searchadr() {
			var address = document.getElementById('searchadrbox').value;
			// Replace spaces with '+' for the URL
			var formattedAddress = encodeURIComponent(address);

			// Google Geocoding API Key (from config)
			var googleApiKey = '{$googleApiKey|escape:"javascript"}';

			// Google Geocoding API endpoint for forward geocoding (address to lat/lng)
			var url = 'https://maps.googleapis.com/maps/api/geocode/json?address=' + formattedAddress + '&key=' + googleApiKey;

			var req = new XMLHttpRequest();
			req.overrideMimeType("application/json");
			req.open('GET', url, true);

			req.onload = function() {
				if (req.status >= 200 && req.status < 300) {
					var jsonResponse = JSON.parse(req.responseText);

					if (jsonResponse.status === 'OK' && jsonResponse.results.length > 0) {
						var lat = jsonResponse.results[0].geometry.location.lat;
						var lng = jsonResponse.results[0].geometry.location.lng;
						var lnglat = [lng.toFixed(6), lat.toFixed(6)];
						map.setCenter(lnglat);
						console.log('Coordinates (lng, lat): ', lnglat);
					} else {
						alert('Could not find coordinates for the provided address. Please try again.');
					}
				} else {
					console.error('Google Geocoding API Request Failed. Status:', req.status);
				}
			};

			req.onerror = function() {
				console.error('Network error occurred while fetching Google Geocoding data.');
			};

			req.send(null);
		}

		// Event listener for the search input (press Enter to trigger search)
		var input = document.getElementById("searchadrbox");
		input.addEventListener("keyup", function(event) {
			event.preventDefault();
			if (event.keyCode === 13) {
				document.getElementById("searchadr").click();
			}
		});

		// --- End Google Geocoding API Functions ---
		// Listen for every move event by the user
{if $ie eq 0}
		var displayCenter = function displayCenter() {
			var center = map.getCenter();
			var latitude = center.lat.toFixed(6);
			var longitude = center.lng.toFixed(6);
			var bearing = map.getBearing().toFixed(0);
			var pitch = map.getPitch().toFixed(0);
			var zoom = map.getZoom().toFixed(2);
			var url = new URL(window.location.href);
			url.searchParams.set('latitude', latitude);
			url.searchParams.set('longitude', longitude);
			url.searchParams.set('bearing', bearing);
			url.searchParams.set('pitch', pitch);
			url.searchParams.set('zoom', zoom);
			window.history.replaceState(null, null, url); // or pushState
		};
{/if}

		function init() {
			// Only when the style change actually took our layers with it.
			//
			// This runs on style load so the WifiDB layers can be re-added after
			// a basemap switch, which discards them. MaplibreInspect also calls
			// setStyle, but as a *diff* update -- our sources survive it, the
			// event fires anyway, and every addSource below then throws
			// "already exists". Asking whether the layers are still there
			// distinguishes the two without having to know who called.
			if (map.getStyle().layers.some((l) => l.id.indexOf('WifiDB_') === 0)) {
				return;
			}
{$layer_source_all}
			toggle_label();
		};

		map.on('load', function () {
			map.addSource('track_line', {
				'type': 'geojson',
				'data': track_geojson
			});
			 
			// add the line which will be modified for the track
			map.addLayer({
				'id': 'track_line_layer',
				'type': 'line',
				'source': 'track_line',
				'layout': {
					'line-cap': 'round',
					'line-join': 'round'
				},
				'paint': {
					'line-color': '#ffff66',
					'line-width': 5,
					'line-opacity': 0.8
				}
			});

			//WifiDB Information Popup
{if $cell_layer_name}
			map.on('click', function(e) {
				var inspectStyle = map.getStyle().metadata['maplibregl-inspect:inspect'];
				if (!inspectStyle) {

					var queryBox;
					var selectThreshold = 5;
					if (selectThreshold === 0) {
					  queryBox = e.point;
					} else {
					  // set a bbox around the pointer
					  queryBox = [
						[
						e.point.x - selectThreshold,
						e.point.y + selectThreshold
						], // bottom left (SW)
						[
						e.point.x + selectThreshold,
						e.point.y - selectThreshold
						] // top right (NE)
					  ];
					}
				
					var features = map.queryRenderedFeatures(queryBox, {
						layers: [{$cell_layer_name}]
					});
					if (!features.length) {
						return;
					}
					
					var text = '';
					for (var i = 0; i < features.length; i++) {
						var feature = features[i];
						if (i !== 0) text += '<hr>';
						text += '<ul>';
						if (feature.properties.id) text += '<li>ID: <a href="{$wifidb_host_url}opt/fetch.php?func=cid&id=' + feature.properties.id + '"><b>' + feature.properties.id + '</b></a></li>';
						if (feature.properties.mapname) text += '<li>Name: <b>' + feature.properties.mapname + '</b></li>';
						if (feature.properties.name) text += '<li>Name: <b>' + feature.properties.name + '</b></li>';
						if (feature.properties.mac) text += '<li>Mac: <b>' + feature.properties.mac + '</b></li>';
						if (feature.properties.points) text += '<li>Points: <a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&id=' + feature.properties.id + '"><b>' + feature.properties.points + '</b></a></li>';
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
					}
					var popup = new maplibregl.Popup().setLngLat(map.unproject(e.point)).setHTML(text).addTo(map);
				}
			});
{/if}
{if $layer_name}
			map.on('click', function(e) {
				var inspectStyle = map.getStyle().metadata['maplibregl-inspect:inspect'];
				if (!inspectStyle) {
					var queryBox;
					var selectThreshold = 5;
					if (selectThreshold === 0) {
					  queryBox = e.point;
					} else {
					  // set a bbox around the pointer
					  queryBox = [
						[
						e.point.x - selectThreshold,
						e.point.y + selectThreshold
						], // bottom left (SW)
						[
						e.point.x + selectThreshold,
						e.point.y - selectThreshold
						] // top right (NE)
					  ];
					}
				
					var features = map.queryRenderedFeatures(queryBox, {
						layers: [{$layer_name}]
					});
					if (!features.length) {
						return;
					}
					
					var text = '';
					for (var i = 0; i < features.length; i++) {
						var feature = features[i];
						
						if (i !== 0) text += '<hr>';
						text += '<ul>';
						var apId = feature.properties.id_str || feature.properties.id;
						if (apId && feature.properties.ssid) text += '<li>SSID: <a href="{$wifidb_host_url}opt/fetch.php?id=' + apId + '"><b>' + feature.properties.ssid + '</b></a></li>';
						else if (feature.properties.ssid) text += '<li>SSID: <b>' + feature.properties.ssid + '</b></li>';
						if (feature.properties.live_id) text += '<li>Live ID: <b>' + feature.properties.live_id + '</b></li>';
						if (feature.properties.mac) text += '<li>Mac: <b>' + feature.properties.mac + '</b></li>';
						if (feature.properties.points) text += '<li>Points: <a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&id=' + apId + '"><b>' + feature.properties.points + '</b></a></li>'; 
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
					}
					

					var popup = new maplibregl.Popup().setLngLat(map.unproject(e.point)).setHTML(text).addTo(map);
				}
			});
{/if}
			// indicate that the symbols are clickableby changing the cursor style to 'pointer'.
			map.on('mousemove', function(e) {
				var inspectStyle = map.getStyle().metadata['maplibregl-inspect:inspect'];
				if (!inspectStyle) {
					var features = map.queryRenderedFeatures(e.point, {
						layers: [{if $layer_name}{$layer_name}{/if}{if $layer_name && $cell_layer_name},{/if}{if $cell_layer_name}{$cell_layer_name}{/if}]
					});
					map.getCanvas().style.cursor = (features.length) ? 'pointer' : '';
				}
			});
		});
		map.on('style.load', function() {
			// Reset toggle buttons since the layers reset on style change
{if $func eq "wifidbmap" || $func eq "user_list"}
			var toggleButtonIds = ['WifiDB_weekly', 'WifiDB_monthly', 'WifiDB_0to1year', 'WifiDB_1to2year', 'WifiDB_2to3year', 'WifiDB_3to5year', 'WifiDB_5to10year', 'WifiDB_10yrplus', 'cell_networks'];
			for (var index in toggleButtonIds) {
				var clicked_id = toggleButtonIds[index];
				var el = document.getElementById(clicked_id);
				if (!el) { continue; } // button absent in this view or stale cache
				var btext = el.firstChild.data;
				btext = btext.replace("Show", "");
				btext = btext.replace("Hide", "");
				el.firstChild.data = "{if $default_hidden eq 1}Show{else}Hide{/if}" + btext;
			} 
{/if}
			// Reload dynamic layers since they are lost on style change
			var waiting = function waiting() {
				if (!map.isStyleLoaded()) {
					setTimeout(waiting, 200);
				} else {
					// The layers cannot go in until the swarm has had its
					// chance to register, because whichever source binds to an
					// archive's URL first is the one every later read uses.
					// Already settled on a basemap switch, so this only defers
					// the first style load.
					swarmReady.then(init);
				}
			};
			waiting();
		});
{if $ie eq 0}
		map.on('move', displayCenter);
{/if}
		//Trigger map resize when menu button is clicked.
		$(".bt-menu-trigger").click(function() {
			$(this).toggleClass("buttonstyle").trigger('classChanged');
		});
		$(".bt-menu-trigger").on("classChanged", function() {
			$(document).ready(function() {
				map.resize();
			});
		});
							
		// ── Reachable from inline handlers ────────────────────────────────
		// A module has its own scope, so a function declared here is invisible
		// to an onClick attribute in the HTML above — the handler runs in
		// global scope and sees nothing. Under the old classic <script> these
		// were globals by default; they have to be published deliberately now.
		//
		// Only these seven, because only these are named by an on* attribute.
		// Anything else stays module-scoped, which is the point of the module.
		Object.assign(window, {
			toggle_layer_button,
			toggle_cell_group,
			toggle_heatmap_group,
			toggle_track,
			toggleFollowLatest,
			track_download,
			searchadr,
		});
</script>
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}