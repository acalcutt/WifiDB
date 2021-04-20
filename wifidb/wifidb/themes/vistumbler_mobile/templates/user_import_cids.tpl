<!--
user_import_aps.tpl, user ap list smarty template.
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<div class="center">
					<table class="content_table">
						<tr class="header">
							<th class="header">ID</th>
							<th class="header">Title</th>
							<th class="header">Filename</th>
							<th class="header">Notes</th>
							<th class="header">Hash</th>
						</tr>
						<tr class="dark">
							<td class="dark">{$file_info.id}</td>
							<td class="dark">{$file_info.title|escape:'htmlall'}</td>
							<td class="dark">{$file_info.file|escape:'htmlall'}</td>
							<td class="dark">{$file_info.notes|escape:'htmlall'}</td>
							<td class="dark">{$file_info.hash|escape:'htmlall'}</td>
						</tr>
					</table>
					<table class="content_table">
						<tr class="header">
							<th class="header">Date</th>
							<th class="header">Size</th>
							<th class="header">APs/GPS Count</th>
							<th class="header">Efficiency</th>
							<th class="header">User(s)</th>

						</tr>
						<tr class="dark">
							<td class="dark">{$file_info.date|escape:'htmlall'}</td>	
							<td class="dark">{$file_info.size|escape:'htmlall'}</td>
							<td class="dark">{$file_info.aps|escape:'htmlall'} - {$file_info.gps|escape:'htmlall'}</td>
							<td class="dark">{$file_info.NewAPPercent|escape:'htmlall'}%</td>
							<td class="dark">
								<a href ="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user={$file_info.user|escape:'htmlall'}">{$file_info.user|escape:'htmlall'}</a><br>
							</td>
						</tr>
					</table>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<td width="40px"><b>GPS:</b></td>
								<td>
								{if $file_info.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&from=0&inc=50000&id={$file_info.id}" title="Show List on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&from=0&inc=50000&id={$file_info.id}" title="Export List to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
									<a href="{$wifidb_host_url}api/export.php?func=exp_list&from=0&inc=50000&id={$file_info.id}" title="Export List to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
							</tr>
						</tbody>
					</table>
					<br/>
<a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$file_info.id}" title="Show AP Points">[Access Points]</a> | {if $func eq 'cidlist'}<b>{/if}<a href="{$wifidb_host_url}opt/userstats.php?func=cidlist&row={$file_info.id}" title="Show Cell Points">[Cell Points]</a>{if $func eq 'cidlist'}</b>{/if} | {if $func eq 'btlist'}<b>{/if}<a href="{$wifidb_host_url}opt/userstats.php?func=btlist&row={$file_info.id}" title="Show Bluetooth Points">[BT Points]</a>{if $func eq 'btlist'}</b>{/if}
<br/><br/>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header" width="75px">
									<div>GPS</div>
									<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
								</th>
								<th class="header">
									<div>New</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=new&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'new' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=new&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'new' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>ID</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=cell_id&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'cell_id' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=cell_id&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'cell_id' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>SSID</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=ssid&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ssid' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=ssid&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ssid' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Mac Address</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=mac&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'mac' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=mac&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'mac' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Authmode</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=authmode&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'authmode' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=authmode&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'authmode' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Type</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=type&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'type' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=type&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'type' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Channel</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=chan&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'chan' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=chan&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'chan' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>First Active</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=fa&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=fa&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Last Active</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=la&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=la&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Network</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=network&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'network' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=network&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'network' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Country</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=country&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'country' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=country&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'country' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Points (List / Total)</div>
									<div>
										<a href="?func={$func}&row={$file_info.id}&sort=list_points&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'list_points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=list_points&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'list_points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a> / <a href="?func={$func}&row={$file_info.id}&sort=points&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func={$func}&row={$file_info.id}&sort=points&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
							</tr>
							{foreach name=outer item=wifidb_users_aps from=$points}
							<tr class="{cycle values="light,dark"}">
								<td class="cell_border">
								{if $wifidb_users_aps.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_cid&id={$wifidb_users_aps.id}" title="Show AP on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_users_aps.id}&file_id={$file_info.id}&from=0&inc=50000" title="Show AP Signals For This File on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_users_aps.id}&file_id={$file_info.id}&from=0&inc=50000" title="Export AP Signals For This File to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_cid&from=0&inc=50000&id={$wifidb_users_aps.id}" title="Export AP to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/sigmap_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
								<td class="cell_border">{$wifidb_users_aps.nu|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.id|escape:'htmlall'}</td>
								<td class="cell_border"><a href="{$wifidb_host_url}opt/fetch.php?func=cid&id={$wifidb_users_aps.id}" title="View AP Details">{$wifidb_users_aps.ssid|escape:'htmlall'}</a></td>
								<td class="cell_border">{$wifidb_users_aps.mac|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.auth|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.type|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.chan|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.fa|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.la|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.network|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.country|escape:'htmlall'}</td>
								<td class="cell_border">{$wifidb_users_aps.list_points|number_format:0} / {$wifidb_users_aps.points|number_format:0}</td>
							</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}