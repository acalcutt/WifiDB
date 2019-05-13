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
				<meta http-equiv="refresh" content="15">
				<div class="center">
					<h3><b>Files Importing ({$importing_count})</b> | <a href="{$wifidb_host_url}opt/scheduling.php?func=waiting">Files Waiting</a> ({$waiting_count}) | <a href="{$wifidb_host_url}opt/scheduling.php?func=done">Files Completed</a> ({$complete_count})</h3>
					<table class="content_table">
						<tr class="header-centered">
							<th colspan="6" align="center">Files being imported</th>
						</tr>
						{foreach item=wifidb_imp from=$wifidb_importing name=importing}
						<tr class="header-centered">
							<th class="{$wifidb_imp.color}"></th>
							<th class="header-centered">ID</th>
							<th class="header-centered">Title</th>
							<th class="header-centered">Filename</th>
							<th class="header-centered">Notes</th>
							<th class="header-centered">Date</th>
							
						</tr>
						<tr class="{$wifidb_imp.color}">
							<td class="{$wifidb_imp.color}"></td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.id}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.title}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.file}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.notes}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.date}</td>
							
						</tr>
						<tr class="header-centered">
							<th class="{$wifidb_imp.color}"></th>
							<th class="header-centered">Size</th>
							<th class="header-centered">User</th>
							<th class="header-centered">Hash Sum</th>
							<th class="header-centered">Current</th>
							<th class="header-centered">Status</th>
						</tr>
						<tr class="green">
							<td class="{$wifidb_imp.color}"></td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.size}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.user}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.hash}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.ap}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.status}</td>
						</tr>
						{if not $smarty.foreach.importing.last}
						<tr class="content-centered">
							<th colspan="6"><br/></th>
						</tr>
						{/if}
						{foreachelse}
						<tr align="center">
							<td class="light-centered colspan="5">
								 Sorry there are no files importing...
							</td>
						</tr>
						{/foreach}
					</table>
				</div>
			</div>
{include file="footer.tpl"}