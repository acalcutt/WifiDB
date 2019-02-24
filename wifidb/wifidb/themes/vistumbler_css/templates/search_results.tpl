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
			<div class="main">
				<div class="center">
				<h2>Total APs found: {$total_rows|default:"0"}</h2>
				<table class="content_table">
					<tbody>
						<tr>
							<td align="center" colspan="10">
								<a title="(Right Click - Save Links As Bookmark)" class="links" href="{$wifidb_host_url}opt/results.php?{$save_url}">Save Link</a> | 
								<a class="links" href="{$wifidb_host_url}opt/map.php?func=exp_search{$export_url}">Map</a> | 
								<a class="links" href="{$wifidb_host_url}api/geojson.php?func=exp_search&json=1{$export_url}">JSON</a> |								
								<a class="links" href="{$wifidb_host_url}api/export.php?func=exp_search{$export_url}">KMZ</a>
							</td>
						</tr>
						<tr class="header">
							<td width="75px">GPS
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=AP_ID&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=AP_ID&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								ID
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=SSID&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=SSID&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								SSID
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=mac&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=mac&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								MAC
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=chan&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=chan&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								Chan
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=radio&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=radio&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								Radio Type
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=auth&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=auth&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								Authentication
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=encry&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=encry&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								Encryption
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=FA&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=FA&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								First Active
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=LA&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
								<a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=LA&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$themeurl}img/up.png"></a>
								Last Active
							</td>
						</tr>
						{foreach item=result from=$results_all}
						<tr class="{$result.class}">
							<td class="{$result.class}">
							{if $result.validgps eq 1}
								<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&labeled=0&id={$result.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								<a href="{$wifidb_host_url}api/geojson.php?json=1&func=exp_ap&id={$result.id}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
								<a href="{$wifidb_host_url}api/export.php?func=exp_ap_netlink&id={$result.id}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
							{else}
								<img width="20px" src="{$themeurl}img/globe_off.png">
								<img width="20px" src="{$themeurl}img/json_off.png">
								<img width="20px" src="{$themeurl}img/kmz_off.png">
							{/if}
							</td>
							<td class="{$result.class}">{$result.id}</td>
							<td class="{$result.class}"><a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$result.id}" title="View AP Details">{$result.ssid}</a></td>
							<td class="{$result.class}">{$result.mac}</td>
							<td class="{$result.class}">{$result.chan}</td>
							<td class="{$result.class}">{$result.radio}</td>
							<td class="{$result.class}">{$result.auth}</td>
							<td class="{$result.class}">{$result.encry}</td>
							<td class="{$result.class}">{$result.FA}</td>
							<td class="{$result.class}">{$result.LA}</td>
						</tr>
						{foreachelse}
						<tr align="center">
							<td border="1" colspan="9">{$mesg}</td>
						</tr>
						{/foreach}
					</tbody>
				</table>
					{$page_list}
				</div>			
			</div>
{include file="footer.tpl"}