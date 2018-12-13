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
							<td class="dark">{$wifidb_all_user_aps.title}</td>
							<td class="dark">{$wifidb_all_user_aps.file}</td>
							<td class="dark">{$wifidb_all_user_aps.notes}</td>
							<td class="dark">{$wifidb_all_user_aps.hash}</td>
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
							<td class="dark">{$wifidb_all_user_aps.date}</td>	
							<td class="dark">{$wifidb_all_user_aps.size}</td>
							<td class="dark">{$wifidb_all_user_aps.aps} - {$wifidb_all_user_aps.gps}</td>
							<td class="dark">{$wifidb_all_user_aps.NewAPPercent}%</td>
							<td class="dark">
								{foreach name=users_all item=user from=$wifidb_all_user_aps.user}
								<a href ="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user={$wifidb_all_user_aps.user}">{$wifidb_all_user_aps.user}</a><br>
								{/foreach}
							</td>
						</tr>
					</table>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<td width="190px"><b>Export This list To...</b></td>
								<td><a href="{$wifidb_host_url}opt/map.php?func=user_list&id={$wifidb_all_user_row}&labeled=1">Map</a> | <a href="{$wifidb_host_url}api/geojson.php?func=exp_list&amp;id={$wifidb_all_user_row}&amp;all=1">GeoJSON</a> | <a href="{$wifidb_host_url}api/export.php?func=exp_list&amp;id={$wifidb_all_user_row}&amp;all=1">KMZ</a></td>
							</tr>
						</tbody>
					</table>
					<br/>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header">AP ID</th>
								<th class="header">Update / New</th>
								<th class="header">GPS</th>
								<th class="header">SSID</th>
								<th class="header">Mac Address</th>
								<th class="header">Authentication</th>
								<th class="header">Encryption</th>
								<th class="header">Radio</th>
								<th class="header">Channel</th>
								<th class="header">First Active</th>
								<th class="header">Last Active</th>
							</tr>
							{foreach name=outer item=wifidb_users_aps from=$wifidb_all_user_aps.allaps}
							<tr class="{$wifidb_users_aps.class}">
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.id}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.un}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.globe_html}</td>
								<td class="{$wifidb_users_aps.class}"><a href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_users_aps.id}" title="View AP Details">{$wifidb_users_aps.ssid}</a></td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.mac}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.auth}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.encry}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.radio}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.chan}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.fa}</td>
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.la}</td>
							</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}