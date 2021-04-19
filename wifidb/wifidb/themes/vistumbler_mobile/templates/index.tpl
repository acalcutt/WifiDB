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
				<table class="content_table">
					<tbody>
						<tr>
							<td colspan="4" class="subheading">Statistics</td>
						</tr>
						<tr>
								<th class="header-centered">Total AP's</th>
								<th class="header-centered">Open APs</th>
								<th class="header-centered">WEP APs</th>
								<th class="header-centered">Secure APs</th>
						</tr>
						<tr>
								<td class="light-centered"><a href="{$wifidb_host_url}all.php?sort=ModDate&ord=DESC&from=0&to=500" title="All Users">{$total_aps|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=1&map_inc=200000" title="Open APs">{$open_aps|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=2&map_inc=200000" title="WEP APs">{$wep_aps|number_format}</a></td>
								<td class="light-centered"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=3&map_inc=200000" title="Secure APs">{$sec_aps|number_format}</a></td>
						</tr>
						<tr>
								<th class="header-centered">Total Users</th>
								<th class="header-centered">Last user to import</th>
								<th class="header-centered">Last AP added</th>
								<th class="header-centered">Last Import List</th>
						</tr>
						<tr>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/userstats.php?func=allusers" title="View All Users">{$total_users|number_format}</a>
								</td>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$new_import_user|escape:'htmlall'}" title="View User Details">{$new_import_user|escape:'htmlall'}</a>
								{if $user_validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_all&from=0&inc=50000&user={$new_import_user|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/geojson.php?func=user_all&from=0&inc=50000&user={$new_import_user|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}opt/export.php?func=user_all&from=0&inc=25000&user={$new_import_user|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/fetch.php?id={$new_ap_id|escape:'htmlall'}" title="View AP Details">{$new_ap_ssid}</a>
								{if $ap_validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$new_ap_id|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&from=0&inc=50000&id={$new_ap_id|escape:'htmlall'}" title="Show Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap_sig&from=0&inc=50000&id={$new_ap_id|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_ap&from=0&inc=25000&id={$new_ap_id|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/sigmap_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
								<td class="dark-centered">
									<a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$new_import_id|escape:'htmlall'}"  title="View List Details">{$new_import_title}</a>
								{if $list_validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&from=0&inc=50000&id={$new_import_id|escape:'htmlall'}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&from=0&inc=50000&id={$new_import_id|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
									<a href="{$wifidb_host_url}api/export.php?func=exp_list&from=0&inc=25000&id={$new_import_id|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
						</tr>
					</tbody>
				</table>
			</div>
{include file="footer.tpl"}