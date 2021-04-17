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
					<h1>User List</h1>
{$pages_together}
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header" width="75px">
									<div>#</div>
									<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
								</th>
								<th class="header" width="75px">
									<div>GPS</div>
									<div>
										<a href="?func=allusers&sort=ValidGPS&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ValidGPS' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=ValidGPS&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ValidGPS' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Username</div>
									<div>
										<a href="?func=allusers&sort=user&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'user' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=user&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'user' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Imports</div>
									<div>
										<a href="?func=allusers&sort=FileCount&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'FileCount' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=FileCount&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'FileCount' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>First Import</div>
									<div>
										<a href="?func=allusers&sort=FirstImport&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'FirstImport' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=FirstImport&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'FirstImport' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Last Import</div>
									<div>
										<a href="?func=allusers&sort=LastImport&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'LastImport' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=LastImport&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'LastImport' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>APs Total</div>
									<div>
										<a href="?func=allusers&sort=ApCount&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'ApCount' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=ApCount&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'ApCount' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Points Total</div>
									<div>
										<a href="?func=allusers&sort=GpsCount&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'GpsCount' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=GpsCount&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'GpsCount' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Efficiency</div>
									<div>
										<a href="?func=allusers&sort=NewAPPercent&ord=ASC&from={$from}&inc={$inc}"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'NewAPPercent' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=allusers&sort=NewAPPercent&ord=DESC&from={$from}&inc={$inc}"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'NewAPPercent' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								{if $wifidb_login_logged_in == 1}
								<th class="header" width="75px">
									<div>Actions</div>
									<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
								</th>
								{/if}
								{if $wifidb_login_priv_name == "Administrator"}
								<th class="header" width="75px">
									<div>Admin</div>
									<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
								</th>
								{/if}
							</tr>
							{foreach name=outer item=wifidb_users from=$wifidb_imports_all}
							<tr class="{$wifidb_users.class}">
								<td class="{$wifidb_users.class}">{$wifidb_users.rowid}</td>
								<td class="{$wifidb_users.class}">
								{if $wifidb_users.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_all&from=0&inc=50000&user={$wifidb_users.user}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}opt/geojson.php?func=user_all&from=0&inc=50000&user={$wifidb_users.user}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}opt/export.php?func=user_all&from=0&inc=25000&user={$wifidb_users.user}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
								<td class="{$wifidb_users.class}"><a class="links" href="?func=alluserlists&user={$wifidb_users.user}">{$wifidb_users.user}</a></td>
								<td class="{$wifidb_users.class}">{$wifidb_users.filecount|number_format:0}</td>
								<td class="{$wifidb_users.class}">{$wifidb_users.firstimport}</td>
								<td class="{$wifidb_users.class}">{$wifidb_users.lastimport}</td>
								<td class="{$wifidb_users.class}">{$wifidb_users.apcount|number_format:0}</td>
								<td class="{$wifidb_users.class}">{$wifidb_users.gpscount|number_format:0}</td>
								<td class="{$wifidb_users.class}">{$wifidb_users.newappercent}%</td>
								{if $wifidb_login_logged_in == 1}<td class="{$wifidb_users.class}">{if $wifidb_users.regid}<a class="links" href="{$wifidb_host_url}cp/messages.php?func=sendmsg&to={$wifidb_users.regid}"><img  title="Message [{$wifidb_users.user}]" width="20px" src="{$themeurl}img/send-message.png"></a>{/if}</td>{/if}
								{if $wifidb_login_priv_name == "Administrator"}<td class="{$wifidb_users.class}"></td>{/if}
							</tr>
							{foreachelse}
								There are no Imports, go find some of them wifis, I hear they have yummy packets.
							{/foreach}
						</tbody>
					</table>
{$pages_together}
				</div>			
			</div>
{include file="footer.tpl"}