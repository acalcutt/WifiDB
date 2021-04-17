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
					<span class="nowrap"><b><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php"><img src="{$themeurl}img/file-importing.png" style="vertical-align: middle;"/> Files Importing ({$importing_count})</a></b></span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=waiting"><img src="{$themeurl}img/file-waiting.png" style="vertical-align: middle;"/> Files Waiting ({$waiting_count})</a></span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=done"><img src="{$themeurl}img/file-complete.png" style="vertical-align: middle;"/> Files Completed ({$complete_count})</a></span>
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
							<td class="{$wifidb_imp.color}">{$wifidb_imp.id|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.title|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.file|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.notes|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.date|escape:'htmlall'}</td>
							
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
							<td class="{$wifidb_imp.color}">{$wifidb_imp.size|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.user|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.hash|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.ap|escape:'htmlall'}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.status|escape:'htmlall'}</td>
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
						<tr class="sub_head">
							<td colspan="12" align="center">
							 {$pages_together}
							</td>
						</tr>
					</table>
				</div>
			</div>
{include file="footer.tpl"}