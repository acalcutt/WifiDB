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
					<h2>Imported Files</h2>
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
										<td width="75px" class="{$wifidb_done.class}">{$wifidb_done.globe_html}</td>
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