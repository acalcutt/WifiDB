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
				<script language="JavaScript">
				// Row Hide function.
				// by tcadieux
				function expandcontract(tbodyid,ClickIcon)
				{
					if (document.getElementById(ClickIcon).innerHTML == "+")
					{
						document.getElementById(tbodyid).style.display = "";
						document.getElementById(ClickIcon).innerHTML = "-";
					}else{
						document.getElementById(tbodyid).style.display = "none";
						document.getElementById(ClickIcon).innerHTML = "+";
					}
				}
				</script>
				<h1>Stats For: All Users</h1>
				<table class="content_table">
					<tbody>
						<tr class="header">
							<th width="100px">Show/Hide</th>
							<th>Username</th>				
							<th width="100px">GPS</th>
							<th>Imports</th> 
						</tr>
						{foreach name=outer item=wifidb_users from=$wifidb_imports_all}
						<tr class="{$wifidb_users.class}">
							<td align="center" onclick="expandcontract('Row{$wifidb_users.rowid}','ClickIcon{$wifidb_users.rowid}')" id="ClickIcon{$wifidb_users.rowid}" style="cursor: pointer; cursor: hand;">+</td>
							<td align="center"><a class="links" href="?func=alluserlists&user={$wifidb_users.user}">{$wifidb_users.user}</a></td>
							<td align="center">{$wifidb_users.globe}</td>
							<td align="center">{$wifidb_users.imports}</td>
						</tr>
						<tr>
							<td colspan='4'>
								<table border="1" align="center" width="100%">
									<tbody id="Row{$wifidb_users.rowid}" style="display:none">
									<tr class="sub_head">
										<th width="100px">GPS</th>
										<th width="100px">Title</th>
										<th>Number of APs</th>
										<th>AP Efficiency</th>
										<th>Import Notes</th>
										<th>Imported On</th>
									</tr>
										{foreach name=outer item=wifidb_import from=$wifidb_users.files}
									<tr class="{$wifidb_import.class}">
										<td align="center">{$wifidb_import.globe}</td>
										<td align="center"><a class="links" href="?func=useraplist&row={$wifidb_import.id}">{$wifidb_import.title}</a></td>
										<td align="center">{$wifidb_import.aps}</td>
										<td align="center">{$wifidb_import.NewAPPercent}</td>
										<td align="center">{$wifidb_import.notes}</td>
										<td align="center">{$wifidb_import.date}</td>
									</tr>
										{/foreach}
									</tbody>
								</table>
							</td>
						</tr>
						{foreachelse}
							There are no Imports, go find some of them wifis, I hear they have yummy packets.
						{/foreach}
					</tbody>
				</table>
			</div>
{include file="footer.tpl"}