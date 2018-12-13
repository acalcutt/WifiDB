<!--

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
					<h2>Imports for: {$wifidb_user_details.user}</h2>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header">ID</th>
								<th class="header">Total APs</th>
								<th class="header">First Import</th>
								<th class="header">Last Import</th>
							</tr>
							<tr class="light">
								<td class="light">{$wifidb_user_details.user_id}</td>
								<td class="light"><a href="{$wifidb_host_url}opt/userstats.php?func=allap&amp;user={$wifidb_user_details.user}">{$wifidb_user_details.total_aps}</a></td>
								<td class="light">{$wifidb_user_details.first_import_date}</td>
								<td class="light">{$wifidb_user_details.newest_date}</td>
							</tr>
						</tbody>
					</table>
					<br/>
					<table class="content_table">
						<tbody>
							<tr class="subheading">
								<th class="subheading" colspan="6">Imported Files</th>
							</tr>
							<tr class="header">
								<th class="header">ID</th>
								<th class="header">GPS</th>
								<th class="header">Title</th>
								<th class="header">Total APs</th>
								<th class="header">Efficiency</th>
								<th class="header">Date</th>
							</tr>
							{foreach item=wifidb_user_prev from=$wifidb_user_details.other_imports}
							<tr class="{$wifidb_user_prev.class}">
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.id}</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.globe_html}</td>
								<td class="{$wifidb_user_prev.class}"><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_user_prev.id}&amp;user={$wifidb_user_details.user}">{$wifidb_user_prev.title}</a></td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.aps}</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.efficiency}%</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.date}</td>
							</tr>
							{/foreach}
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}