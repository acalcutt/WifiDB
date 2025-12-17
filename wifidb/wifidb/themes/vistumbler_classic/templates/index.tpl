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
									<a href="{$wifidb_host_url}opt/export.php?func=alluserlists&from=0&inc=25000&user={$new_import_user|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
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
									<h3 style="margin: 0 0 10px 0; color: #333;">WiFi Networks Over Time</h3>
									   <canvas id="wifi_chart" style="width: 100%; height: 300px;"></canvas>
									<p style="font-size: 11px; color: #666; margin-top: 5px;">Mouse-over graph to see values. Select a range to zoom in, double-click to zoom out.</p>
								</div>

								   <div style="margin-bottom: 30px;">
									   <h3 style="margin: 0 0 10px 0; color: #333;">WiFi Encryption Over Time</h3>
									  <canvas id="encryption_chart" style="width: 100%; height: 300px;"></canvas>
									   <p style="font-size: 11px; color: #666; margin-top: 5px;">Shows percentage breakdown of Open (red), WEP (orange), and Secure (green) networks over time.</p>
									   <!-- Debug output for wifi_graph_data -->
									   <div style="margin-top: 10px;">
										   <strong>Debug: First 10 rows of WiFi Encryption Data</strong>
										   <table border="1" cellpadding="3" style="font-size: 11px; margin-top: 5px;">
											   <tr>
												   <th>Month</th>
												   <th>Open %</th>
												   <th>WEP %</th>
												   <th>Secure %</th>
											   </tr>
											   {foreach from=$wifi_graph_data item=row name=dbg}
												   {if $smarty.foreach.dbg.iteration <= 10}
												   <tr>
													   <td>{$row.month}</td>
													   <td>{$row.open_pct}</td>
													   <td>{$row.wep_pct}</td>
													   <td>{$row.secure_pct}</td>
												   </tr>
												   {/if}
											   {/foreach}
										   </table>
									   </div>
								   </div>

								   <div style="margin-bottom: 30px;">
									   <h3 style="margin: 0 0 10px 0; color: #333;">WiFi Authentication Over Time</h3>
									  <canvas id="auth_chart" style="width: 100%; height: 300px;"></canvas>
									   <p style="font-size: 11px; color: #666; margin-top: 5px;">Shows percentage breakdown of Open (green), Personal (blue), and Enterprise (purple) authentication over time.</p>
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

				   <!-- Include Chart.js library -->
				   <script src="{$themeurl}lib/chart.min.js"></script>
				<script>
				{literal}
				document.addEventListener('DOMContentLoaded', function() {
					// Parse the data from PHP
					var wifiData = {/literal}{$wifi_graph_data}{literal};
					var cellData = {/literal}{$cell_graph_data}{literal};
					var btData = {/literal}{$bt_graph_data}{literal};

					// Helper to format numbers
					function formatNumber(num) {
						if (num >= 1000000) return (num/1000000).toFixed(2) + 'M';
						if (num >= 1000) return (num/1000).toFixed(1) + 'K';
						return num.toString();
					}

					// WiFi Networks Chart (new per month + cumulative)
					if (wifiData && wifiData.length > 0) {
						var wifiChartData = "Date,New Networks,Cumulative Total\n";
						wifiData.forEach(function(row) {
							wifiChartData += row.month + "-01," + row.new_count + "," + row.cumulative + "\n";
						});

						new Dygraph(
							document.getElementById("wifi_chart"),
							wifiChartData,
							{
								legend: 'always',
								showRoller: true,
								rollPeriod: 1,
								ylabel: 'New Networks',
								y2label: 'Cumulative Total',
								series: {
									'New Networks': { axis: 'y1', color: '#666666' },
									'Cumulative Total': { axis: 'y2', color: '#cc0000' }
								},
								axes: {
									y: { axisLabelWidth: 60 },
									y2: { axisLabelWidth: 80, valueFormatter: formatNumber, axisLabelFormatter: formatNumber }
								},
								fillGraph: false,
								stackedGraph: false,
								drawPoints: false,
								strokeWidth: 2,
								highlightSeriesOpts: { strokeWidth: 3 }
							}
						);
					}

					// WiFi Encryption Percentage Chart
					if (wifiData && wifiData.length > 0) {
						var encryptionChartData = "Date,Open %,WEP %,Secure %\n";
						wifiData.forEach(function(row) {
							encryptionChartData += row.month + "-01," + row.open_pct + "," + row.wep_pct + "," + row.secure_pct + "\n";
						});

						new Dygraph(
							document.getElementById("encryption_chart"),
							encryptionChartData,
							{
								legend: 'always',
								ylabel: 'Percentage',
								   <script>
					// WiFi Networks Chart (new per month + cumulative)
					var wifiLabels = [
						{foreach from=$wifi_chart_data item=row}
							"{$row.month}-01",
						{/foreach}
					];
					var wifiNew = [
						{foreach from=$wifi_chart_data item=row}
							{$row.new_count},
						{/foreach}
					];
					var wifiCumulative = [
						{foreach from=$wifi_chart_data item=row}
							{$row.cumulative},
						{/foreach}
					];
					new Chart(document.getElementById('wifi_chart').getContext('2d'), {
						type: 'bar',
						data: {
							labels: wifiLabels,
							datasets: [
								{
									label: 'New Networks',
									data: wifiNew,
									backgroundColor: 'rgba(33, 150, 243, 0.5)',
									borderColor: 'rgba(33, 150, 243, 1)',
									borderWidth: 1,
									yAxisID: 'y',
								},
								{
									label: 'Cumulative Total',
									data: wifiCumulative,
									type: 'line',
									borderColor: '#4CAF50',
									backgroundColor: 'rgba(76, 175, 80, 0.2)',
									fill: false,
									yAxisID: 'y1',
									tension: 0.1
								}
							]
						},
						options: {
							responsive: true,
							interaction: { mode: 'index', intersect: false },
							stacked: false,
							plugins: {
								legend: { position: 'top' },
								title: { display: true, text: 'WiFi Networks Over Time' }
							},
							scales: {
								y: {
									type: 'linear',
									display: true,
									position: 'left',
									title: { display: true, text: 'New Networks' }
								},
								y1: {
									type: 'linear',
									display: true,
									position: 'right',
									grid: { drawOnChartArea: false },
									title: { display: true, text: 'Cumulative Total' }
								}
							}
						}
					});

					// WiFi Encryption Percentage Chart
					var encLabels = [
						{foreach from=$encryption_chart_data item=row}
							"{$row.month}-01",
						{/foreach}
					];
					var encOpen = [
						{foreach from=$encryption_chart_data item=row}
							{$row.open_pct},
						{/foreach}
					];
					var encWep = [
						{foreach from=$encryption_chart_data item=row}
							{$row.wep_pct},
						{/foreach}
					];
					var encSec = [
						{foreach from=$encryption_chart_data item=row}
							{$row.secure_pct},
						{/foreach}
					];
					new Chart(document.getElementById('encryption_chart').getContext('2d'), {
						type: 'line',
						data: {
							labels: encLabels,
							datasets: [
								{
									label: 'Open %',
									data: encOpen,
									backgroundColor: 'rgba(139, 195, 74, 0.5)',
									borderColor: '#8BC34A',
									fill: true,
									stack: 'Stack 0',
									tension: 0.1
								},
								{
									label: 'WEP %',
									data: encWep,
									backgroundColor: 'rgba(255, 193, 7, 0.5)',
									borderColor: '#FFC107',
									fill: true,
									stack: 'Stack 0',
									tension: 0.1
								},
								{
									label: 'Secure %',
									data: encSec,
									backgroundColor: 'rgba(33, 150, 243, 0.5)',
									borderColor: '#2196F3',
									fill: true,
									stack: 'Stack 0',
									tension: 0.1
								}
							]
						},
						options: {
							responsive: true,
							plugins: {
								legend: { position: 'top' },
								title: { display: true, text: 'WiFi Encryption Over Time' },
								tooltip: { mode: 'index', intersect: false }
							},
							interaction: { mode: 'index', intersect: false },
							scales: {
								y: {
									stacked: true,
									beginAtZero: true,
									max: 100,
									title: { display: true, text: '% of APs' }
								}
							}
						}
					});

					// WiFi Authentication Percentage Chart
					var authLabels = [
						{foreach from=$auth_chart_data_raw item=row}
							"{$row.month}-01",
						{/foreach}
					];
					var authOpen = [ {foreach from=$auth_chart_data_raw item=row}{$row.auth_open_pct},{/foreach} ];
					// WEP is folded into Open for auth chart; no separate WEP series
					var authWpa = [ {foreach from=$auth_chart_data_raw item=row}{if isset($row.auth_wpa_pct)}{$row.auth_wpa_pct}{else}0{/if},{/foreach} ];
					var authWpa2 = [ {foreach from=$auth_chart_data_raw item=row}{if isset($row.auth_wpa2_pct)}{$row.auth_wpa2_pct}{else}0{/if},{/foreach} ];
					var authOwe = [ {foreach from=$auth_chart_data_raw item=row}{if isset($row.auth_owe_pct)}{$row.auth_owe_pct}{else}0{/if},{/foreach} ];
					var authWpa3 = [ {foreach from=$auth_chart_data_raw item=row}{if isset($row.auth_wpa3_pct)}{$row.auth_wpa3_pct}{else}0{/if},{/foreach} ];
					new Chart(document.getElementById('auth_chart').getContext('2d'), {
						type: 'line',
						data: {
							labels: authLabels,
							datasets: [
								{
									label: 'Open %',
									data: authOpen,
									backgroundColor: 'rgba(76, 175, 80, 0.5)',
									borderColor: '#4CAF50',
									fill: true,
									tension: 0.1,
									stack: 'Stack 0'
								},

								{
									label: 'WPA %',
									data: authWpa,
									backgroundColor: 'rgba(255, 152, 0, 0.5)',
									borderColor: '#FF9800',
									fill: true,
									tension: 0.1,
									stack: 'Stack 0'
								},
								{
									label: 'WPA2 %',
									data: authWpa2,
									backgroundColor: 'rgba(33, 150, 243, 0.5)',
									borderColor: '#2196F3',
									fill: true,
									tension: 0.1,
									stack: 'Stack 0'
								},
								{
									label: 'OWE %',
									data: authOwe,
									backgroundColor: 'rgba(156, 39, 176, 0.5)',
									borderColor: '#9C27B0',
									fill: true,
									tension: 0.1,
									stack: 'Stack 0'
								},
								{
									label: 'WPA3 %',
									data: authWpa3,
									backgroundColor: 'rgba(63,81,181,0.5)',
									borderColor: '#3F51B5',
									fill: true,
									tension: 0.1,
									stack: 'Stack 0'
								}
							]
						},
						options: {
							responsive: true,
							plugins: {
								legend: { position: 'top' },
								title: { display: true, text: 'WiFi Authentication Over Time' },
								tooltip: { mode: 'index', intersect: false }
							},
							interaction: { mode: 'index', intersect: false },
							scales: { y: { stacked: true, beginAtZero: true, max: 100, title: { display: true, text: '% of APs' } } }
						}
					});

					// Cell Towers Chart
					var cellLabels = [
						{foreach from=$cell_chart_data item=row}
							"{$row.month}-01",
						{/foreach}
					];
					var cellNew = [
						{foreach from=$cell_chart_data item=row}
							{$row.new_count},
						{/foreach}
					];
					var cellCumulative = [
						{foreach from=$cell_chart_data item=row}
							{$row.cumulative},
						{/foreach}
					];
					new Chart(document.getElementById('cell_chart').getContext('2d'), {
						type: 'bar',
						data: {
							labels: cellLabels,
							datasets: [
								{
									label: 'New Towers',
									data: cellNew,
									backgroundColor: 'rgba(156, 39, 176, 0.5)',
									borderColor: '#9C27B0',
									borderWidth: 1,
									yAxisID: 'y',
								},
								{
									label: 'Cumulative Total',
									data: cellCumulative,
									type: 'line',
									borderColor: '#FF9800',
									backgroundColor: 'rgba(255, 152, 0, 0.2)',
									fill: false,
									yAxisID: 'y1',
									tension: 0.1
								}
							]
						},
						options: {
							responsive: true,
							interaction: { mode: 'index', intersect: false },
							stacked: false,
							plugins: {
								legend: { position: 'top' },
								title: { display: true, text: 'Cell Towers Over Time' }
							},
							scales: {
								y: {
									type: 'linear',
									display: true,
									position: 'left',
									title: { display: true, text: 'New Towers' }
								},
								y1: {
									type: 'linear',
									display: true,
									position: 'right',
									grid: { drawOnChartArea: false },
									title: { display: true, text: 'Cumulative Total' }
								}
							}
						}
					});

					// Bluetooth Chart
					var btLabels = [
						{foreach from=$bt_chart_data item=row}
							"{$row.month}-01",
						{/foreach}
					];
					var btNew = [
						{foreach from=$bt_chart_data item=row}
							{$row.new_count},
						{/foreach}
					];
					var btCumulative = [
						{foreach from=$bt_chart_data item=row}
							{$row.cumulative},
						{/foreach}
					];
					new Chart(document.getElementById('bt_chart').getContext('2d'), {
						type: 'bar',
						data: {
							labels: btLabels,
							datasets: [
								{
									label: 'New Devices',
									data: btNew,
									backgroundColor: 'rgba(233, 30, 99, 0.5)',
									borderColor: '#E91E63',
									borderWidth: 1,
									yAxisID: 'y',
								},
								{
									label: 'Cumulative Total',
									data: btCumulative,
									type: 'line',
									borderColor: '#00BCD4',
									backgroundColor: 'rgba(0, 188, 212, 0.2)',
									fill: false,
									yAxisID: 'y1',
									tension: 0.1
								}
							]
						},
						options: {
							responsive: true,
							interaction: { mode: 'index', intersect: false },
							stacked: false,
							plugins: {
								legend: { position: 'top' },
								title: { display: true, text: 'Bluetooth Devices Over Time' }
							},
							scales: {
								y: {
									type: 'linear',
									display: true,
									position: 'left',
									title: { display: true, text: 'New Devices' }
								},
								y1: {
									type: 'linear',
									display: true,
									position: 'right',
									grid: { drawOnChartArea: false },
									title: { display: true, text: 'Cumulative Total' }
								}
							}
						}
					});
								   </script>