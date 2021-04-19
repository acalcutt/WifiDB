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
							<td class="dark">{$wifidb_all_user_aps.id}</td>
							<td class="dark">{$wifidb_all_user_aps.title|escape:'htmlall'}</td>
							<td class="dark">{$wifidb_all_user_aps.file|escape:'htmlall'}</td>
							<td class="dark">{$wifidb_all_user_aps.notes|escape:'htmlall'}</td>
							<td class="dark">{$wifidb_all_user_aps.hash|escape:'htmlall'}</td>
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
							<td class="dark">{$wifidb_all_user_aps.date|escape:'htmlall'}</td>	
							<td class="dark">{$wifidb_all_user_aps.size|escape:'htmlall'}</td>
							<td class="dark">{$wifidb_all_user_aps.aps|escape:'htmlall'} - {$wifidb_all_user_aps.gps|escape:'htmlall'}</td>
							<td class="dark">{$wifidb_all_user_aps.NewAPPercent|escape:'htmlall'}%</td>
							<td class="dark">
								{foreach name=users_all item=user from=$wifidb_all_user_aps.user}
								<a href ="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user={$wifidb_all_user_aps.user|escape:'htmlall'}">{$wifidb_all_user_aps.user|escape:'htmlall'}</a><br>
								{/foreach}
							</td>
						</tr>
					</table>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<td width="40px"><b>GPS:</b></td>
								<td>
								{if $wifidb_all_user_aps.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&from=0&inc=50000&id={$wifidb_all_user_aps.id}" title="Show List on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&from=0&inc=50000&id={$wifidb_all_user_aps.id}" title="Export List to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
									<a href="{$wifidb_host_url}api/export.php?func=exp_list&from=0&inc=50000&id={$wifidb_all_user_aps.id}" title="Export List to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
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
{if $cids}
<b><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$wifidb_all_user_aps.id}" title="Show AP Points">[Access Points]</a></b> | <a href="{$wifidb_host_url}opt/userstats.php?func=cidlist&row={$wifidb_all_user_aps.id}" title="Show Cell Points">[Cell Points]</a> | <a href="{$wifidb_host_url}opt/userstats.php?func=btlist&row={$wifidb_all_user_aps.id}" title="Show Bluetooth Points">[BT Points]</a>
<br/><br/>
{/if}
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
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=New&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'New' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=New&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'New' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>ID</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=AP_ID&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=AP_ID&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>SSID</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=SSID&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=SSID&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Mac Address</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=BSSID&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=BSSID&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Authentication</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=AUTH&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=AUTH&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Encryption</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=ENCR&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=ENCR&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Radio Type</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=RADTYPE&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=RADTYPE&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Channel</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=CHAN&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=CHAN&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>First Active</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=fa&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=fa&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Last Active</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=la&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=la&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Points (List / Total)</div>
									<div>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=list_points&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'list_points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=list_points&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'list_points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a> / <a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=points&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=useraplist&row={$wifidb_all_user_aps.id}&sort=points&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
							</tr>
							{foreach name=outer item=wifidb_users_aps from=$wifidb_all_user_aps.allaps}
							<tr class="{$wifidb_users_aps.class}">
								<td class="{$wifidb_users_aps.class}">
								{if $wifidb_users_aps.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&id={$wifidb_users_aps.id}" title="Show AP on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap_sig&from=0&inc=50000&id={$wifidb_users_aps.id}&file_id={$wifidb_all_user_aps.id}&from=0&inc=50000" title="Show AP Signals For This File on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_ap_sig&from=0&inc=50000&id={$wifidb_users_aps.id}&file_id={$wifidb_all_user_aps.id}&from=0&inc=50000" title="Export AP Signals For This File to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_ap&from=0&inc=50000&id={$wifidb_users_aps.id}" title="Export AP to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/sigmap_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.un|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.id|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}"><a href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_users_aps.id}" title="View AP Details">{$wifidb_users_aps.ssid|escape:'htmlall'}</a></td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.mac|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.auth|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.encry|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.radio|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.chan|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.fa|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.la|escape:'htmlall'}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.list_points|number_format:0} / {$wifidb_users_aps.points|number_format:0}</td>
							</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}