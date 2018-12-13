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
						<tbody>
							<tr class="header">
								<th class="header" colspan="2">Access Points For: <a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_all_user_aps.user}">{$wifidb_all_user_aps.user}</a></th>
							</tr>
							<tr class="dark">
								<td class="dark" width="190px"><b>Total Access Points...</b></td>
								<td class="dark">{$wifidb_all_user_aps.total_aps}</td>
							</tr>
							<tr class="dark">
								<td class="dark" width="190px"><b>Export This list To...</b></td>
								<td class="dark"><a class="links" href="{$wifidb_host_url}api/export.php?func=exp_user_netlink&amp;user={$wifidb_all_user_aps.user}">KMZ</a></td>
							</tr>
							<tr class="dark">
								<td class="dark" width="190px"><b>Access Point Efficiency...</b></td>
								<td class="dark">{$wifidb_all_user_aps.NewAPPercent}</td>
							</tr>
						</tbody>
					</table>
					<br/>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header">AP ID</th>
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
								<td class="{$wifidb_users_aps.class}">{$wifidb_users_aps.globe_html}</td>
								<td class="{$wifidb_users_aps.class}"><a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_users_aps.id}">{$wifidb_users_aps.ssid}</a></td>
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
{$pages_together}
				</div>
			</div>
{include file="footer.tpl"}