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
				<script>
				var map = new mapboxgl.Map({
					container: 'map',
					style: '{$style}',
					center: {$centerpoint},
					zoom: {$zoom},
				});
				function init() {
{$layer_source_all}
				};

				map.once('style.load', function(e) {
					init();
					map.addControl(new mapboxgl.NavigationControl());
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
								'<li>SSID: <a href="https://live.wifidb.net/wifidb/opt/fetch.php?id=' + feature.properties.id + '"><b>' + feature.properties.ssid + '</b></a></li>' +
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
								'<li>Username: <a href="https://live.wifidb.net/wifidb/opt/userstats.php?func=alluserlists&user=' + feature.properties.username + '"><b>' + feature.properties.username + '</b></a></li>' +
								'</ul>')
							.addTo(map);
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