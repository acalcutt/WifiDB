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
				<meta http-equiv="refresh" content="15">
				<div class="center">
					<span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php"><img src="{$themeurl}img/file-importing.png" style="vertical-align: middle;"/> Files Importing ({$importing_count})</a></span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=waiting"><img src="{$themeurl}img/file-waiting.png" style="vertical-align: middle;"/> Files Waiting ({$waiting_count})</a></span> | <span class="nowrap"><b><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=done"><img src="{$themeurl}img/file-complete.png" style="vertical-align: middle;"/> Files Completed ({$complete_count})</a></b></span>
					<table class="content_table"> 
						<tr class="header-centered">
							<th colspan="7" align="center">Files completed</th>
						</tr>
						{foreach item=wifidb_done from=$wifidb_done_all_array name=done}
						<tr class="header-centered">
							<th class="header"></th>
							<th class="header">ID</th>
							<th class="header">Title</th>
							<th class="header">Filename</th>					
							<th class="header">Notes</th>
							<th class="header" colspan="2">Hash</th>
						</tr>
						<tr style="background-color: {$wifidb_wait.color}">
							<td class="header"></td>
							<td class="{$wifidb_done.class}">{$wifidb_done.id}</td>
							<td class="{$wifidb_done.class}"><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$wifidb_done.id}">{$wifidb_done.title}</a></td>
							<td class="{$wifidb_done.class}"><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$wifidb_done.id}">{$wifidb_done.file}</a></td>
							<td class="{$wifidb_done.class}">{$wifidb_done.notes}</td>
							<td class="{$wifidb_done.class}" colspan="2">{$wifidb_done.hash}</td>
						</tr>
						<tr class="header-centered">
							<th class="header"></th>
							<th width="75px" class="header">GPS</th>
							<th class="header">Date</th>
							<th class="header">Size</th>
							<th class="header">APs/GPS Count</th>
							<th class="header">Efficiency</th>
							<th class="header">User(s)</th>
						</tr>
						<tr style="background-color: {$wifidb_wait.color}">
							<td class="header"></td>
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
						{if not $smarty.foreach.done.last}
						<tr class="content-centered">
							<th colspan="6"><br/></th>
						</tr>
						{/if}
						{foreachelse}
						<tr align="center">
							<td class="light-centered colspan="5">
								 Sorry there are no Imports completed...
							</td>
						</tr>
						{/foreach}
						<tr class="sub_head">
							<td colspan="12" align="center">
							 {$pages_together}
							</td>
						</tr>
					</table>
				</div>
			</div>
{include file="footer.tpl"}