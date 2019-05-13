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
								<th class="header" width="75px">Show/Hide</th>
								<th class="header" width="75px">GPS</th>
								<th class="header">Username</th>
								<th class="header">Imports</th> 
							</tr>
							{foreach name=outer item=wifidb_users from=$wifidb_imports_all}
							<tr class="{$wifidb_users.class}">
								<td class="{$wifidb_users.class}" onclick="expandcontract('Row{$wifidb_users.rowid}','ClickIcon{$wifidb_users.rowid}')" id="ClickIcon{$wifidb_users.rowid}" style="cursor: pointer; cursor: hand;">+</td>
								<td class="{$wifidb_users.class}">
								{if $wifidb_users.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_all&labeled=0&user={$wifidb_users.user}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?json=1&func=exp_user_all&user={$wifidb_users.user}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_user_netlink&user={$wifidb_users.user}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
								<td class="{$wifidb_users.class}"><a class="links" href="?func=alluserlists&user={$wifidb_users.user}">{$wifidb_users.user}</a></td>
								<td class="{$wifidb_users.class}">{$wifidb_users.imports}</td>
							</tr>
							<tr>
								<td colspan='4'>
									<table class="content_table">
										<tbody id="Row{$wifidb_users.rowid}" style="display:none">
										<tr class="header">
											<th class="header" width="100px">GPS</th>
											<th class="header">ID</th>
											<th class="header">File</th>
											<th class="header">Title</th>
											<th class="header">Number of APs</th>
											<th class="header">AP Efficiency</th>
											<th class="header">Imported On</th>
										</tr>
										{foreach name=outer item=wifidb_import from=$wifidb_users.files}
										<tr class="{$wifidb_import.class}">
											<td class="{$wifidb_import.class}">
											{if $wifidb_import.validgps eq 1}
												<a href="{$wifidb_host_url}opt/map.php?func=user_list&labeled=0&id={$wifidb_import.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
												<a href="{$wifidb_host_url}api/geojson.php?json=1&func=exp_list&id={$wifidb_import.id}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
												<a href="{$wifidb_host_url}api/export.php?func=exp_list&id={$wifidb_import.id}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
											{else}
												<img width="20px" src="{$themeurl}img/globe_off.png">
												<img width="20px" src="{$themeurl}img/json_off.png">
												<img width="20px" src="{$themeurl}img/kmz_off.png">
											{/if}
											</td>
											<td class="{$wifidb_import.class}"><a class="links" href="?func=useraplist&row={$wifidb_import.id}">{$wifidb_import.id}</a></td>
											<td class="{$wifidb_import.class}"><a class="links" href="?func=useraplist&row={$wifidb_import.id}">{$wifidb_import.file}</a></td>
											<td class="{$wifidb_import.class}"><a class="links" href="?func=useraplist&row={$wifidb_import.id}">{$wifidb_import.title}</a></td>
											<td class="{$wifidb_import.class}">{$wifidb_import.aps}</td>
											<td class="{$wifidb_import.class}">{$wifidb_import.NewAPPercent}</td>
											<td class="{$wifidb_import.class}">{$wifidb_import.date}</td>
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
			</div>
{include file="footer.tpl"}