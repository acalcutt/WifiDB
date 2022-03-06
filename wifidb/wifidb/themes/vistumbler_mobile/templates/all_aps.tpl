<!--
all_aps.tpl: The Smarty AP List template for WiFiDB.
Copyright (C) 2022 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<div class="center">
<b><a href="{$wifidb_host_url}all.php" title="Show AP Points">[Access Points]</a></b> | <a href="{$wifidb_host_url}all.php?func=cid" title="Show Cell Points">[Cell Points]</a> | {if $func eq 'bt'}<b>{/if}<a href="{$wifidb_host_url}all.php?func=bt" title="Show Bluetooth Points">[BT Points]</a>
<br/><br/>
<b>{$points|number_format} Points</b>
<br/><br/>
{$pages_together}
					<table class="content_table"">
						<tr class="header">
							<td class="header">
								<div>GPS</div>
								<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
							</td>
							<td class="header">
								<div>ID</div>
								<div>
									<a href="?sort=AP_ID&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=AP_ID&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>SSID</div>
								<div>
									<a href="?sort=SSID&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=SSID&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>MAC</div>
								<div>
									<a href="?sort=BSSID&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=BSSID&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Channel</div>
								<div>
									<a href="?sort=CHAN&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=CHAN&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Authentication</div>
								<div>
									<a href="?sort=AUTH&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=AUTH&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Encryption</div>
								<div>
									<a href="?sort=ENCR&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=ENCR&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>						
							<td class="header">
								<div>Radio Type</div>
								<div>
									<a href="?sort=RADTYPE&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=RADTYPE&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Network Type</div>
								<div>
									<a href="?sort=NETTYPE&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'NETTYPE' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=NETTYPE&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'NETTYPE' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>First Active</div>
								<div>
									<a href="?sort=fa&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=fa&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Last Active</div>
								<div>
									<a href="?sort=la&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=la&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Points</div>
								<div>
									<a href="?sort=points&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?sort=points&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
						</tr>
						{foreach name=outer item=wifidb_ap from=$wifidb_aps_all}
						<tr class="{$wifidb_ap.class}">
							<td class="{$wifidb_ap.class}" width="75px">
							{if $wifidb_ap.ValidGPS eq 1}
								<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&labeled=0&id={$wifidb_ap.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&labeled=0&id={$wifidb_ap.id}&from=0&inc=50000" title="Show Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap_sig&id={$wifidb_ap.id}&from=0&inc=50000&json=0&labeled=0" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_ap&id={$wifidb_ap.id}&from=0&inc=25000&xml=0&labeled=0" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								<a href="{$wifidb_host_url}api/gpx.php?func=exp_ap_sig&id={$wifidb_ap.id}&from=0&inc=25000&xml=0&labeled=0" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
							{else}
								<img width="20px" src="{$themeurl}img/globe_off.png">
								<img width="20px" src="{$themeurl}img/sigmap_off.png">
								<img width="20px" src="{$themeurl}img/json_off.png">
								<img width="20px" src="{$themeurl}img/kmz_off.png">
								<img width="20px" src="{$themeurl}img/gpx_off.png">
							{/if}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.id|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								<a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_ap.id}" title="View AP Details">{$wifidb_ap.ssid|escape:'htmlall'}</a>
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.mac|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.chan|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.auth|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.encry|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.radio|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.nt|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.fa|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.la|escape:'htmlall'}
							</td>
							<td class="{$wifidb_ap.class}">
								{$wifidb_ap.points|number_format:0|escape:'htmlall'}
							</td>
						</tr>
						{foreachelse}
						<tr>
							<td align="center" colspan="9">
								<b>There are no Access Points imported as of yet, go grab some with Vistumbler and import them.<br />
								Come on... you know you want too.</b>
							</td>
						</tr>
						{/foreach}
					</table>
{$pages_together}
				</div>
			</div>
{include file="footer.tpl"}