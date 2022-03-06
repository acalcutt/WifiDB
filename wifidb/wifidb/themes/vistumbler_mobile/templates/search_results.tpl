<!--
Database.inc.php, holds the database interactive functions.
Copyright (C) 2022 Andrew Calcutt
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
				{include file="topmenu.tpl"}
				<div class="center">
				<b>Search Results: {$total_rows|default:"0"|number_format} Points</b> (
								<a title="(Right Click - Save Links As Bookmark)" class="links" href="{$wifidb_host_url}opt/results.php?&ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}">Save Link</a> |
								<a class="links" href="{$wifidb_host_url}opt/map.php?func=exp_search&inc={$map_inc}&ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}">Map</a> |
								<a class="links" href="{$wifidb_host_url}api/geojson.php?func=exp_search&ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&json=0&labeled=0">JSON</a> |
								<a class="links" href="{$wifidb_host_url}api/export.php?func=exp_search&ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&xml=0&labeled=0">KMZ</a> |
								<a class="links" href="{$wifidb_host_url}api/gpx.php?func=exp_search&ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&xml=0&labeled=0">GPX</a> )
								<br/><br/>
					{$page_list}
				<table class="content_table">
					<tbody>
						<tr class="header">
							<td width="75px">GPS
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=AP_ID&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=AP_ID&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								ID
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=SSID&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=SSID&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								SSID
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=BSSID&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=BSSID&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								MAC
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=CHAN&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=CHAN&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								Chan
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=RADTYPE&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=RADTYPE&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								Radio Type
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=AUTH&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=AUTH&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								Authentication
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=ENCR&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=ENCR&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								Encryption
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=FA&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'FA' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=FA&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'FA' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								First Active
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=LA&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'LA' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=LA&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'LA' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								Last Active
							</td>
							<td class="header">
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=points&ord=ASC&from={$from}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
								<a href="?ssid={$ssid_search}&mac={$mac_search}&radio={$radio_search}&chan={$chan_search}&auth={$auth_search}&encry={$encry_search}&sectype={$sectype_search}&sort=points&ord=DESC&from={$from}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								Points
							</td>
						</tr>
						{foreach item=result from=$results_all}
						<tr class="{$result.class}">
							<td class="{$result.class}">
							{if $result.validgps eq 1}
								<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&labeled=0&id={$result.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&labeled=0&id={$result.id}" title="Show Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap&id={$result.id}&json=0&labeled=0" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_ap_netlink&id={$result.id}&xml=0&labeled=0" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								<a href="{$wifidb_host_url}api/gpx.php?xml=1&func=exp_ap&id={$result.id}&xml=0&labeled=0" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
							{else}
								<img width="20px" src="{$themeurl}img/globe_off.png">
								<img width="20px" src="{$themeurl}img/sigmap_off.png">
								<img width="20px" src="{$themeurl}img/json_off.png">
								<img width="20px" src="{$themeurl}img/kmz_off.png">
								<img width="20px" src="{$themeurl}img/gpx_off.png">
							{/if}
							</td>
							<td class="{$result.class}">{$result.id}</td>
							<td class="{$result.class}"><a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$result.id}" title="View AP Details">{$result.ssid}</a></td>
							<td class="{$result.class}">{$result.mac}</td>
							<td class="{$result.class}">{$result.chan}</td>
							<td class="{$result.class}">{$result.radio}</td>
							<td class="{$result.class}">{$result.auth}</td>
							<td class="{$result.class}">{$result.encry}</td>
							<td class="{$result.class}">{$result.fa}</td>
							<td class="{$result.class}">{$result.la}</td>
							<td class="{$result.class}">{$result.points}</td>
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