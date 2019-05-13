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
				{include file="topmenu.tpl"}
				<div class="center">
					<h3><a href="{$wifidb_host_url}opt/scheduling.php">Files Importing</a> ({$importing_count}) | <a href="{$wifidb_host_url}opt/scheduling.php?func=waiting">Files Waiting</a> ({$waiting_count}) | <b>Files Completed ({$complete_count})</b></h3>
					<table class="content_table">
						<tr>
							<td>
								{foreach name=done_all item=wifidb_done from=$wifidb_done_all_array}
								<table class="content_table">
									<tr class="header">
										<th class="header">ID</th>
										<th class="header">Title</th>
										<th class="header">Filename</th>					
										<th class="header">Notes</th>
										<th class="header">Hash</th>
									</tr>
									<tr class="{$wifidb_done.class}">
										<td class="{$wifidb_done.class}">{$wifidb_done.id}</td>
										<td class="{$wifidb_done.class}"><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$wifidb_done.id}">{$wifidb_done.title}</a></td>
										<td class="{$wifidb_done.class}"><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$wifidb_done.id}">{$wifidb_done.file}</a></td>
										<td class="{$wifidb_done.class}">{$wifidb_done.notes}</td>
										<td class="{$wifidb_done.class}">{$wifidb_done.hash}</td>					
									</tr>
								</table>
								<table class="content_table">
									<tr class="header">
										<th width="75px" class="header">GPS</th>
										<th class="header">Date</th>
										<th class="header">Size</th>
										<th class="header">APs/GPS Count</th>
										<th class="header">Efficiency</th>
										<th class="header">User(s)</th>
									</tr>
									<tr class="{$wifidb_done.class}">
										<td width="75px" class="{$wifidb_done.class}">
										{if $wifidb_done.validgps eq 1}
											<a href="{$wifidb_host_url}opt/map.php?func=user_list&labeled=0&id={$wifidb_done.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
											<a href="{$wifidb_host_url}api/geojson.php?json=1&func=exp_list&id={$wifidb_done.id}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
											<a href="{$wifidb_host_url}api/export.php?func=exp_list&id={$wifidb_done.id}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
										{else}
											<img width="20px" src="{$themeurl}img/globe_off.png">
											<img width="20px" src="{$themeurl}img/json_off.png">
											<img width="20px" src="{$themeurl}img/kmz_off.png">
										{/if}
										</td>
										<td class="{$wifidb_done.class}">{$wifidb_done.date}</td>
										<td class="{$wifidb_done.class}">{$wifidb_done.size}</td>
										<td class="{$wifidb_done.class}">{$wifidb_done.aps} - {$wifidb_done.gps}</td>
										<td class="{$wifidb_done.class}">{$wifidb_done.efficiency}%</td>
										<td class="{$wifidb_done.class}">
											{foreach name=users_all item=user from=$wifidb_done.user}
											<a class="links" href ="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user={$user}">{$user}</a><br>
											{/foreach}
										</td>
									</tr>
								</table>
								<br/>
								{foreachelse}
								<tr class="header">
									<td>There are no Imports yet, go get some...</td>
								</tr>
								{/foreach}
							</td>
						</tr>
					</table>
				</div>
			</div>
{include file="footer.tpl"}