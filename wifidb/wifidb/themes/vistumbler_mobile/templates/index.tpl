<!--
index.tpl: The Smarty Index template for WiFiDB.
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}

				<!-- Date Range Filter -->
				<table class="content_table">
					<tbody>
						<tr>
							<td colspan="6" class="subheading">Filter by Date Range</td>
						</tr>
						<tr>
							<td colspan="6" class="light-centered" style="padding: 10px;">
								<form method="get" action="{$wifidb_host_url}stats.php" style="display: inline;">
									<input type="hidden" name="top" value="{$top_n}">
									<button type="submit" name="range" value="all" style="margin: 2px; padding: 5px 10px;{if $date_range eq 'all'} background-color: #4CAF50; color: white;{/if}">All Time</button>
									<button type="submit" name="range" value="30days" style="margin: 2px; padding: 5px 10px;{if $date_range eq '30days'} background-color: #4CAF50; color: white;{/if}">Last 30 Days</button>
									<button type="submit" name="range" value="90days" style="margin: 2px; padding: 5px 10px;{if $date_range eq '90days'} background-color: #4CAF50; color: white;{/if}">Last 90 Days</button>
									<button type="submit" name="range" value="6months" style="margin: 2px; padding: 5px 10px;{if $date_range eq '6months'} background-color: #4CAF50; color: white;{/if}">Last 6 Months</button>
									<button type="submit" name="range" value="1year" style="margin: 2px; padding: 5px 10px;{if $date_range eq '1year'} background-color: #4CAF50; color: white;{/if}">Last Year</button>
									<button type="submit" name="range" value="2years" style="margin: 2px; padding: 5px 10px;{if $date_range eq '2years'} background-color: #4CAF50; color: white;{/if}">Last 2 Years</button>
								</form>
								<form method="get" action="{$wifidb_host_url}stats.php" style="display: inline; margin-left: 20px;">
									<input type="hidden" name="top" value="{$top_n}">
									<input type="hidden" name="range" value="custom">
									<label>From: <input type="date" name="start_date" value="{$start_date}" style="padding: 3px;"></label>
									<label>To: <input type="date" name="end_date" value="{$end_date}" style="padding: 3px;"></label>
									<button type="submit" style="margin: 2px; padding: 5px 10px;">Apply</button>
								</form>
							</td>
						</tr>
						{if $date_filter_label neq 'All Time'}
						<tr>
							<td colspan="6" class="dark-centered"><strong>Showing: {$date_filter_label}</strong>{if $start_date} (from {$start_date}){/if}{if $end_date} (to {$end_date}){/if}</td>
						</tr>
						{/if}
					</tbody>
				</table>

				<table class="content_table">
					<tbody>
						<tr>
							<td colspan="7" class="subheading">Statistics</td>
						</tr>
						<tr>
								<th class="header-centered">Total Users</th>
								<th class="header-centered">Total WiFi AP's</th>
								<th class="header-centered">Open APs</th>
								<th class="header-centered">WEP APs</th>
								<th class="header-centered">Secure APs</th>
								<th class="header-centered">Total Cell Towers</th>
								<th class="header-centered">Total Bluetooth</th>
						</tr>
						<tr>
								<td class="light-centered"><a href="{$wifidb_host_url}opt/userstats.php?func=allusers" title="View All Users">{$total_users|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}all.php?sort=ModDate&ord=DESC&from=0&to=500" title="All WiFi APs">{$total_aps|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=1&map_inc=200000" title="Open APs">{$open_aps|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=2&map_inc=200000" title="WEP APs">{$wep_aps|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=3&map_inc=200000" title="Secure APs">{$sec_aps|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}all.php?func=cid&sort=cell_id&ord=DESC&from=0&inc=250" title="All Cell Towers">{$cell_count|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}all.php?func=bt&sort=cell_id&ord=DESC&from=0&inc=250" title="All Bluetooth Devices">{$bt_count|number_format}</a></td>
						</tr>
						<tr>
								<th class="header-centered" colspan="2">Last user to import</th>
								<th class="header-centered" colspan="2">Last AP added</th>
								<th class="header-centered" colspan="3">Last Import List</th>
						</tr>
						<tr>
								<th class="header-centered">Any</th>
								<th class="header-centered">With GPS</th>
								<th class="header-centered">Any</th>
								<th class="header-centered">With GPS</th>
								<th class="header-centered">Any</th>
								<th class="header-centered" colspan="2">With GPS</th>
						</tr>
						<tr>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$new_import_user|escape:'htmlall'}" title="View User Details">{$new_import_user|escape:'htmlall'}</a>
								{if $user_validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_all&from=0&inc=50000&user={$new_import_user|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/geojson.php?func=user_all&from=0&inc=50000&user={$new_import_user|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}opt/export.php?func=user_all&from=0&inc=25000&user={$new_import_user|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}opt/gpx.php?func=user_all&from=0&inc=25000&user={$new_import_user|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
									<img width="20px" src="{$themeurl}img/gpx_off.png">
								{/if}
								</td>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$new_import_user_withgps|escape:'htmlall'}" title="View User Details">{$new_import_user_withgps|escape:'htmlall'}</a>
								{if $new_import_user_withgps}
									<a href="{$wifidb_host_url}opt/map.php?func=user_all&from=0&inc=50000&user={$new_import_user_withgps|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/geojson.php?func=user_all&from=0&inc=50000&user={$new_import_user_withgps|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}opt/export.php?func=alluserlists&from=0&inc=25000&user={$new_import_user_withgps|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}opt/gpx.php?func=user_all&from=0&inc=25000&user={$new_import_user_withgps|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{/if}
								</td>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/fetch.php?id={$new_ap_id|escape:'htmlall'}" title="View AP Details">{$new_ap_ssid}</a>
								{if $ap_validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$new_ap_id|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&from=0&inc=50000&id={$new_ap_id|escape:'htmlall'}" title="Show Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap_sig&from=0&inc=50000&id={$new_ap_id|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_ap&from=0&inc=25000&id={$new_ap_id|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}api/gpx.php?func=exp_ap_sig&from=0&inc=25000&id={$new_ap_id|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/sigmap_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
									<img width="20px" src="{$themeurl}img/gpx_off.png">
								{/if}
								</td>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/fetch.php?id={$new_ap_id_withgps|escape:'htmlall'}" title="View AP Details">{$new_ap_ssid_withgps}</a>
								{if $new_ap_id_withgps}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$new_ap_id_withgps|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&from=0&inc=50000&id={$new_ap_id_withgps|escape:'htmlall'}" title="Show Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap_sig&from=0&inc=50000&id={$new_ap_id_withgps|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_ap&from=0&inc=25000&id={$new_ap_id_withgps|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}api/gpx.php?func=exp_ap_sig&from=0&inc=25000&id={$new_ap_id_withgps|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{/if}
								</td>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$new_import_id|escape:'htmlall'}"  title="View List Details">{$new_import_title}</a>
								{if $list_validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&from=0&inc=50000&id={$new_import_id|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&from=0&inc=50000&id={$new_import_id|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_list&from=0&inc=25000&id={$new_import_id|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}api/gpx.php?func=exp_list&from=0&inc=25000&id={$new_import_id|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
									<img width="20px" src="{$themeurl}img/gpx_off.png">
								{/if}
								</td>
								<td class="dark-centered" colspan="2">
									<a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$new_import_id_withgps|escape:'htmlall'}"  title="View List Details">{$new_import_title_withgps}</a>
								{if $new_import_id_withgps}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&from=0&inc=50000&id={$new_import_id_withgps|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&from=0&inc=50000&id={$new_import_id_withgps|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_list&from=0&inc=25000&id={$new_import_id_withgps|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}api/gpx.php?func=exp_list&from=0&inc=25000&id={$new_import_id_withgps|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{/if}
								</td>
						</tr>
					</tbody>
				</table>

				{if $top_wifi|@count > 0}
				<table class="content_table">
					<tbody>
						<tr>
							<td colspan="6" class="subheading">Top {$top_n} WiFi Networks (by observations)</td>
						</tr>
						<tr>
							<th class="header-centered">#</th>
							<th class="header-centered">SSID</th>
							<th class="header-centered">BSSID</th>
							<th class="header-centered">Auth</th>
							<th class="header-centered">Encryption</th>
							<th class="header-centered">Points</th>
						</tr>
						{foreach from=$top_wifi item=wifi key=idx}
						<tr>
							<td class="{cycle values="light-centered,dark-centered"}">{$idx+1}</td>
							<td class="{cycle values="light-centered,dark-centered"}">
								<a href="{$wifidb_host_url}opt/fetch.php?id={$wifi.id|escape:'htmlall'}" title="View AP Details">{$wifi.ssid}</a>
								{if $wifi.validgps eq 1}
								<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$wifi.id|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								{/if}
							</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$wifi.bssid}</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$wifi.auth}</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$wifi.encr}</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$wifi.points|number_format}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
				{/if}

				{if $top_cell|@count > 0}
				<table class="content_table">
					<tbody>
						<tr>
							<td colspan="5" class="subheading">Top {$top_n} Cell Towers (by observations)</td>
						</tr>
						<tr>
							<th class="header-centered">#</th>
							<th class="header-centered">Name</th>
							<th class="header-centered">ID</th>
							<th class="header-centered">Type</th>
							<th class="header-centered">Points</th>
						</tr>
						{foreach from=$top_cell item=cell key=idx}
						<tr>
							<td class="{cycle values="light-centered,dark-centered"}">{$idx+1}</td>
							<td class="{cycle values="light-centered,dark-centered"}">
								<a href="{$wifidb_host_url}opt/fetch.php?func=cid&id={$cell.id|escape:'htmlall'}" title="View Cell Details">{$cell.ssid}</a>
								{if $cell.validgps eq 1}
								<a href="{$wifidb_host_url}opt/map.php?func=exp_cell&id={$cell.id|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								{/if}
							</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$cell.mac}</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$cell.type}</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$cell.points|number_format}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
				{/if}

				{if $top_bt|@count > 0}
				<table class="content_table">
					<tbody>
						<tr>
							<td colspan="5" class="subheading">Top {$top_n} Bluetooth Devices (by observations)</td>
						</tr>
						<tr>
							<th class="header-centered">#</th>
							<th class="header-centered">Name</th>
							<th class="header-centered">MAC</th>
							<th class="header-centered">Type</th>
							<th class="header-centered">Points</th>
						</tr>
						{foreach from=$top_bt item=bt key=idx}
						<tr>
							<td class="{cycle values="light-centered,dark-centered"}">{$idx+1}</td>
							<td class="{cycle values="light-centered,dark-centered"}">
								<a href="{$wifidb_host_url}opt/fetch.php?func=cid&id={$bt.id|escape:'htmlall'}" title="View Bluetooth Details">{$bt.ssid}</a>
								{if $bt.validgps eq 1}
								<a href="{$wifidb_host_url}opt/map.php?func=exp_cell&id={$bt.id|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								{/if}
							</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$bt.mac}</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$bt.type}</td>
							<td class="{cycle values="light-centered,dark-centered"}">{$bt.points|number_format}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
				{/if}

				<!-- Statistics Over Time Graphs -->
				<table class="content_table">
					<tbody>
						<tr>
							<td colspan="6" class="subheading">Statistics Over Time</td>
						</tr>
						<tr>
							<td colspan="6" class="light" style="padding: 20px;">
								<div style="margin-bottom: 30px;">
									<h3 style="margin: 0 0 10px 0; color: #333;">WiFi Encryption Over Time</h3>
									<!-- Chart.js WiFi Encryption Chart -->
									<canvas id="encryption_chart" style="width: 100%; height: 300px;"></canvas>
									<p style="font-size: 11px; color: #666; margin-top: 5px;">Shows percentage breakdown of Open (red), WEP (orange), and Secure (green) networks over time.</p>
								</div>

								<div style="margin-bottom: 30px;">
									<h3 style="margin: 0 0 10px 0; color: #333;">WiFi Networks Over Time</h3>
									<canvas id="wifi_chart" style="width: 100%; height: 300px;"></canvas>
									<p style="font-size: 11px; color: #666; margin-top: 5px;">Mouse-over graph to see values. Select a range to zoom in, double-click to zoom out.</p>
								</div>

								<div style="margin-bottom: 30px;">
									<h3 style="margin: 0 0 10px 0; color: #333;">Cell Towers Over Time</h3>
									<canvas id="cell_chart" style="width: 100%; height: 250px;"></canvas>
								</div>

								<div>
									<h3 style="margin: 0 0 10px 0; color: #333;">Bluetooth Devices Over Time</h3>
									<canvas id="bt_chart" style="width: 100%; height: 250px;"></canvas>
								</div>
							</td>
						</tr>
					</tbody>
				</table>


				<!-- Include Dygraphs library -->
				<script src="{$themeurl}lib/chart.min.js"></script>
				<script>
				{literal}
				document.addEventListener('DOMContentLoaded', function() {
					// Parse the data from PHP
					var wifiData = {/literal}{$wifi_graph_data}{literal};
					var cellData = {/literal}{$cell_graph_data}{literal};
					var btData = {/literal}{$bt_graph_data}{literal};

					// WiFi Encryption Percentage Chart (Chart.js)
					if (wifiData && wifiData.length > 0 && window.Chart) {
						var labels = wifiData.map(function(row) { return row.month + "-01"; });
						var openData = wifiData.map(function(row) { return row.open_pct; });
						var wepData = wifiData.map(function(row) { return row.wep_pct; });
						var secureData = wifiData.map(function(row) { return row.secure_pct; });
						var ctx = document.getElementById('encryption_chart').getContext('2d');
						new Chart(ctx, {
							type: 'line',
							data: {
								labels: labels,
								datasets: [
									{
										label: 'Open %',
										data: openData,
										backgroundColor: 'rgba(204,0,0,0.2)',
										borderColor: 'rgba(204,0,0,1)',
										fill: true,
										stack: 'Stack 0',
										tension: 0.1
									},
									{
										label: 'WEP %',
										data: wepData,
										backgroundColor: 'rgba(255,140,0,0.2)',
										borderColor: 'rgba(255,140,0,1)',
										fill: true,
										stack: 'Stack 0',
										tension: 0.1
									},
									{
										label: 'Secure %',
										data: secureData,
										backgroundColor: 'rgba(0,204,102,0.2)',
										borderColor: 'rgba(0,204,102,1)',
										fill: true,
										stack: 'Stack 0',
										tension: 0.1
									}
								]
							},
							options: {
								responsive: true,
								plugins: {
									legend: { display: true },
									title: {
										display: false
									},
									tooltip: {
										mode: 'index',
										intersect: false
									}
								},
								interaction: {
									mode: 'index',
									intersect: false
								},
								scales: {
									x: {
										title: { display: false },
										ticks: { maxTicksLimit: 10 }
									},
									y: {
										stacked: true,
										min: 0,
										max: 100,
										title: { display: true, text: 'Percentage' }
									}
								}
							}
						});
					}

					// WiFi Networks Chart (Chart.js)
					if (wifiData && wifiData.length > 0 && window.Chart) {
						var wifiLabels = wifiData.map(function(row) { return row.month + "-01"; });
						var wifiNew = wifiData.map(function(row) { return row.new_count; });
						var wifiCumulative = wifiData.map(function(row) { return row.cumulative; });
						var ctxWifi = document.getElementById('wifi_chart').getContext('2d');
						new Chart(ctxWifi, {
							type: 'line',
							data: {
								labels: wifiLabels,
								datasets: [
									{
										label: 'New Networks',
										data: wifiNew,
										backgroundColor: 'rgba(102,102,102,0.2)',
										borderColor: 'rgba(102,102,102,1)',
										yAxisID: 'y',
										fill: true,
										tension: 0.1,
										pointRadius: 0
									},
									{
										label: 'Cumulative Total',
										data: wifiCumulative,
										backgroundColor: 'rgba(204,0,0,0.1)',
										borderColor: 'rgba(204,0,0,1)',
										yAxisID: 'y2',
										fill: false,
										tension: 0.1,
										pointRadius: 0
									}
								]
							},
							options: {
								responsive: true,
								plugins: {
									legend: { display: true },
									title: { display: false }
								},
								scales: {
									y: {
										type: 'linear',
										position: 'left',
										title: { display: true, text: 'New Networks' }
									},
									y2: {
										type: 'linear',
										position: 'right',
										grid: { drawOnChartArea: false },
										title: { display: true, text: 'Cumulative Total' }
									},
									x: {
										ticks: { maxTicksLimit: 10 }
									}
								}
							}
						});
					}

					// Cell Towers Chart (Chart.js)
					if (cellData && cellData.length > 0 && window.Chart) {
						var cellLabels = cellData.map(function(row) { return row.month + "-01"; });
						var cellNew = cellData.map(function(row) { return row.new_count; });
						var cellCumulative = cellData.map(function(row) { return row.cumulative; });
						var ctxCell = document.getElementById('cell_chart').getContext('2d');
						new Chart(ctxCell, {
							type: 'line',
							data: {
								labels: cellLabels,
								datasets: [
									{
										label: 'New Towers',
										data: cellNew,
										backgroundColor: 'rgba(102,102,102,0.2)',
										borderColor: 'rgba(102,102,102,1)',
										yAxisID: 'y',
										fill: true,
										tension: 0.1,
										pointRadius: 0
									},
									{
										label: 'Cumulative Total',
										data: cellCumulative,
										backgroundColor: 'rgba(136,95,205,0.1)',
										borderColor: 'rgba(136,95,205,1)',
										yAxisID: 'y2',
										fill: false,
										tension: 0.1,
										pointRadius: 0
									}
								]
							},
							options: {
								responsive: true,
								plugins: {
									legend: { display: true },
									title: { display: false }
								},
								scales: {
									y: {
										type: 'linear',
										position: 'left',
										title: { display: true, text: 'New Towers' }
									},
									y2: {
										type: 'linear',
										position: 'right',
										grid: { drawOnChartArea: false },
										title: { display: true, text: 'Cumulative Total' }
									},
									x: {
										ticks: { maxTicksLimit: 10 }
									}
								}
							}
						});
					} else {
						document.getElementById("cell_chart").outerHTML = '<p style="color: #999; text-align: center;">No cell tower data available</p>';
					}

					// Bluetooth Chart (Chart.js)
					if (btData && btData.length > 0 && window.Chart) {
						var btLabels = btData.map(function(row) { return row.month + "-01"; });
						var btNew = btData.map(function(row) { return row.new_count; });
						var btCumulative = btData.map(function(row) { return row.cumulative; });
						var ctxBt = document.getElementById('bt_chart').getContext('2d');
						new Chart(ctxBt, {
							type: 'line',
							data: {
								labels: btLabels,
								datasets: [
									{
										label: 'New Devices',
										data: btNew,
										backgroundColor: 'rgba(102,102,102,0.2)',
										borderColor: 'rgba(102,102,102,1)',
										yAxisID: 'y',
										fill: true,
										tension: 0.1,
										pointRadius: 0
									},
									{
										label: 'Cumulative Total',
										data: btCumulative,
										backgroundColor: 'rgba(0,102,204,0.1)',
										borderColor: 'rgba(0,102,204,1)',
										yAxisID: 'y2',
										fill: false,
										tension: 0.1,
										pointRadius: 0
									}
								]
							},
							options: {
								responsive: true,
								plugins: {
									legend: { display: true },
									title: { display: false }
								},
								scales: {
									y: {
										type: 'linear',
										position: 'left',
										title: { display: true, text: 'New Devices' }
									},
									y2: {
										type: 'linear',
										position: 'right',
										grid: { drawOnChartArea: false },
										title: { display: true, text: 'Cumulative Total' }
									},
									x: {
										ticks: { maxTicksLimit: 10 }
									}
								}
							}
						});
					} else {
						document.getElementById("bt_chart").outerHTML = '<p style="color: #999; text-align: center;">No Bluetooth data available</p>';
					}
				});
				{/literal}
				</script>
			</div>
{include file="footer.tpl"}
