<!--
Database.inc.php, holds the database interactive functions.
Copyright (C) 2018 Andrew Calcutt

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
					<h2>Showing the last {$intervalt} of Live APs.</h2>
					<table class="content_table">
						<tr class="header">
							<td class="header">
								Select Window of time to view:
							</td>
							<td class="header">
								<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=1800">30 Minutes</a>
							</td>
							<td class="header">
								<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=3600">60 Minutes</a>
							</td>
							<td class="header">
								<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=7200">2 Hours</a>
							</td>
							<td class="header">
								<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=21600">6 Hours</a>
							</td>
							<td class="header">
								<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=86400">1 Day</a>
							</td>
							<td class="header">
								<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=604800">1 Week</a>
							</td>
						</tr>
					</table>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header">GPS</th>
								<th class="header">
									<a href="?sort=ssid&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=ssid&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									SSID
								</th>
								<th class="header">
									<a href="?sort=mac&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=mac&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Mac Address
								</th>
								<th class="header">
									<a href="?sort=auth&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=auth&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Authentication
								</th>
								<th class="header">
									<a href="?sort=encry&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=encry&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Encryption
								</th>
								<th class="header">
									<a href="?sort=radio&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=radio&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Radio
								</th>
								<th class="header">
									<a href="?sort=chan&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=chan&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Channel
								</th>
								<th class="header">
									<a href="?sort=fa&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=fa&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									First Active
								</th>
								<th class="header">
									<a href="?sort=la&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=la&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Last Active
								</th>
								<th class="header">
									<a href="?sort=username&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=username&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Username
								</th>
								<th class="header">
									<a href="?sort=label&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=label&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Label
								</th>										
								<th class="header">
									<a href="?sort=lat&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=lat&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Latitude
								</th>
								<th class="header">
									<a href="?sort=long&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
									<a href="?sort=long&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
									Longitude
								</th>
							</tr>
							{foreach name=outer item=wifidb_live_aps from=$wifidb_all_live_aps}
							<tr class="{$wifidb_live_aps.class}">
								<td class="{$wifidb_live_aps.class}" width="75px">
								{if $wifidb_live_aps.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_live_ap&labeled=0&id={$wifidb_live_aps.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
								{/if}
								</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.ssid}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.mac}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.auth}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.encry}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.radio}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.chan}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.fa}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.la}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.username}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.label}</td>											
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.lat}</td>
								<td class="{$wifidb_live_aps.class}">{$wifidb_live_aps.long}</td>											
							</tr>
							{/foreach}
						</tbody>
					</table>
					{$pages_together}
				</div>
			</div>
{include file="footer.tpl"}