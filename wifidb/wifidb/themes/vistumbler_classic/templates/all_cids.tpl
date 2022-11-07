<!--
all_aps.tpl: The Smarty AP List template for WiFiDB.
Copyright (C) 2021 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<div class="center">
<a href="{$wifidb_host_url}all.php" title="Show AP Points">[Access Points]</a> | {if $func eq 'cid'}<b>{/if}<a href="{$wifidb_host_url}all.php?func=cid" title="Show Cell Points">[Cell Points]</a>{if $func eq 'cid'}</b>{/if} | {if $func eq 'bt'}<b>{/if}<a href="{$wifidb_host_url}all.php?func=bt" title="Show Bluetooth Points">[BT Points]</a>{if $func eq 'bt'}</b>{/if}
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
									<a href="?func={$func}&sort=cell_id&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'cell_id' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=cell_id&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'cell_id' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>SSID</div>
								<div>
									<a href="?func={$func}&sort=ssid&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ssid' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=ssid&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ssid' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>MAC</div>
								<div>
									<a href="?func={$func}&sort=mac&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'mac' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=mac&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'mac' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Channel</div>
								<div>
									<a href="?func={$func}&sort=chan&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'chan' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=chan&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'chan' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>AuthMode</div>
								<div>
									<a href="?func={$func}&sort=authmode&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'authmode' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=authmode&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'authmode' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Type</div>
								<div>
									<a href="?func={$func}&sort=type&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'type' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=type&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'type' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>						
							<td class="header">
								<div>First Active</div>
								<div>
									<a href="?func={$func}&sort=fa&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=fa&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Last Active</div>
								<div>
									<a href="?func={$func}&sort=la&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=la&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							{if $func eq 'cid'}
							<td class="header">
								<div>Network</div>
								<div>
									<a href="?func={$func}&sort=network&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'network' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=network&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'network' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							<td class="header">
								<div>Country</div>
								<div>
									<a href="?func={$func}&sort=country&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'country' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=country&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'country' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
							{/if}
							<td class="header">
								<div>Points</div>
								<div>
									<a href="?func={$func}&sort=points&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
									<a href="?func={$func}&sort=points&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
								</div>
							</td>
						</tr>
						{foreach name=outer item=wifidb_ap from=$wifidb_aps_all}
						<tr class="{cycle values="light,dark"}">
							<td class="{$wifidb_ap.class}" width="75px">
							{if $wifidb_ap.ValidGPS eq 1}
								<a href="{$wifidb_host_url}opt/map.php?func=exp_cid&labeled=0&id={$wifidb_ap.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								<a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&labeled=0&id={$wifidb_ap.id}&from=0&inc=50000" title="Show Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
								<a href="{$wifidb_host_url}api/geojson.php?func=exp_cell_sig&id={$wifidb_ap.id}&from=0&inc=50000&json=0&labeled=0" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
								<a href="{$wifidb_host_url}api/export.php?func=exp_cid&id={$wifidb_ap.id}&from=0&inc=25000&xml=0&labeled=0" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								<a href="{$wifidb_host_url}api/gpx.php?func=exp_cell_sig&id={$wifidb_ap.id}&from=0&inc=25000&xml=0&labeled=0" title="Export to JSON"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
							{else}
								<img width="20px" src="{$themeurl}img/globe_off.png">
								<img width="20px" src="{$themeurl}img/sigmap_off.png">
								<img width="20px" src="{$themeurl}img/json_off.png">
								<img width="20px" src="{$themeurl}img/kmz_off.png">
								<img width="20px" src="{$themeurl}img/gpx_off.png">
							{/if}
							</td>
							<td class="cell_border">
								{$wifidb_ap.id|escape:'htmlall'}
							</td>
							<td class="cell_border">
								<a class="links" href="{$wifidb_host_url}opt/fetch.php?func={$func}&id={$wifidb_ap.id}" title="View AP Details">{$wifidb_ap.ssid|escape:'htmlall'}</a>
							</td>
							<td class="cell_border">
								{$wifidb_ap.mac|escape:'htmlall'}
							</td>
							<td class="cell_border">
								{$wifidb_ap.chan|escape:'htmlall'}
							</td>
							<td class="cell_border">
								{$wifidb_ap.authmode|escape:'htmlall'}
							</td>
							<td class="cell_border">
								{$wifidb_ap.type|escape:'htmlall'}
							</td>
							<td class="cell_border">
								{$wifidb_ap.fa|escape:'htmlall'}
							</td>
							<td class="cell_border">
								{$wifidb_ap.la|escape:'htmlall'}
							</td>
							{if $func eq 'cid'}
							<td class="cell_border">
								{$wifidb_ap.network|escape:'htmlall'}
							</td>
							<td class="cell_border">
								{$wifidb_ap.country|escape:'htmlall'}
							</td>
							{/if}
							<td class="cell_border">
								{$wifidb_ap.points|number_format}
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