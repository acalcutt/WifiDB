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
<br>
<table border="1" width="90%">
	<tr class="style4">
		<th colspan="9" align="center">Files already imported</th>
	</tr>
	<tr>
		<td>
			{foreach name=done_all item=wifidb_done from=$wifidb_done_all_array}
			<table border="1" style="width:100%;">
				<tr class="sub_head">
					<th>ID</th>
					<th>Date</th>
					<th>APs/GPS Count</th>
					<th>Efficiency</th>
					<th>Title</th>
					<th>User(s)</th>
				</tr>
				<tr class="{$wifidb_done.class}">
					<td align="center">{$wifidb_done.id}</td>
					<td align="center">{$wifidb_done.date}</td>
					<td align="center">{$wifidb_done.aps} - {$wifidb_done.gps}</td>
					<td align="center">{$wifidb_done.efficiency}%</td>
					<td align="center">{$wifidb_done.title}</td>
					<td align="center">
						{foreach name=users_all item=user from=$wifidb_done.user}
						<a class="links" href ="{$wifidb_host_url}opt/userstats.php?func=alluserlists&user={$user}">{$user}</a><br>
						{/foreach}
					</td>
				</tr>
			</table>
			<table border="1" style="width:100%;">
				<tr class="sub_head">
					<th colspan="2">Filename</th>
					<th>Size</th>
					<th>Hash</th>
				</tr>
				<tr class="{$wifidb_done.class}">
					<td align="center" colspan="2"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&row={$wifidb_done.user_row}">{$wifidb_done.file}</a></td>
					<td align="center">{$wifidb_done.size}</td>
					<td align="center">{$wifidb_done.hash}</td>
				</tr>
			</table>
			<br/>
			{foreachelse}
			<tr class="sub_head">
				<td>There are no Imports yet, go get some...</td>
			</tr>
			{/foreach}
		</td>
	</tr>
</table>
{include file="footer.tpl"}