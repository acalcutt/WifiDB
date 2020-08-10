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
				{include file="topmenu.tpl"}
				<div class="center">
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header" colspan="2">Access Points For: <a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_all_user_aps.user}">{$wifidb_all_user_aps.user}</a></th>
							</tr>
							<tr class="dark">
								<td class="dark" width="190px"><b>Access Points (New / Total)</b></td>
								<td class="dark">{$wifidb_all_user_aps.new_aps} / {$wifidb_all_user_aps.total_aps}</td>
							</tr>
							<tr class="dark">
								<td class="dark" width="190px"><b>Access Point Efficiency</b></td>
								<td class="dark">{$wifidb_all_user_aps.efficiency|string_format:"%.2f"}%</td>
							</tr>
							<tr class="dark">
								<td class="dark" width="190px"><b>Export To</b></td>
								<td class="dark"><a class="links" href="{$wifidb_host_url}api/export.php?func=exp_user_netlink&amp;user={$wifidb_all_user_aps.user}">KMZ</a></td>
							</tr>
						</tbody>
					</table>
					<br/>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header" width="75px">
									<div>GPS</div>
									<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
								</th>
								<th class="header">
									<div>ID</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=AP_ID&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=AP_ID&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AP_ID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>SSID</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=SSID&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=SSID&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'SSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Mac Address</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=BSSID&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=BSSID&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'BSSID' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Authentication</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=AUTH&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=AUTH&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'AUTH' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Encryption</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=ENCR&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=ENCR&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ENCR' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Radio</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=RADTYPE&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=RADTYPE&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'RADTYPE' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Channel</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=CHAN&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=CHAN&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'CHAN' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>First Active</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=fa&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=fa&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'fa' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Last Active</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=la&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=la&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'la' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Points</div>
									<div>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=points&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allap&user={$wifidb_all_user_aps.user}&sort=points&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'points' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
							</tr>
{foreach name=outer item=wifidb_users_aps from=$wifidb_all_user_aps.allaps}
							<tr class="{$wifidb_users_aps.class}">
								<td class="{$wifidb_users_aps.class}">
								{if $wifidb_users_aps.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_ap&labeled=0&id={$wifidb_users_aps.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?json=1&func=exp_ap&id={$wifidb_users_aps.id}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
									<a href="{$wifidb_host_url}api/export.php?func=exp_ap&id={$wifidb_users_aps.id}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>							
								<td class="{$wifidb_users_aps.class}"><a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_users_aps.id}">{$wifidb_users_aps.id}</a></td>
								<td class="{$wifidb_users_aps.class}"><a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_users_aps.id}">{$wifidb_users_aps.ssid}</a></td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.mac}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.auth}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.encry}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.radio}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.chan}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.fa}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.la}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.points}</td>
							</tr>
{/foreach}
						</tbody>
					</table>
{$pages_together}
				</div>
			</div>
{include file="footer.tpl"}