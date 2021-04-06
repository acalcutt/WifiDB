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
					<h2>Imports for: {$wifidb_user_details.user}
					{if $wifidb_user_details.validgps eq 1}
						<a href="{$wifidb_host_url}opt/map.php?func=user_all&labeled=0&user={$wifidb_user_details.user}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
						<a href="{$wifidb_host_url}opt/geojson.php?json=1&labeled=1&func=user_all&user={$wifidb_user_details.user}&labeled=1" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
						<a href="{$wifidb_host_url}opt/export.php?func=user_all&user={$wifidb_user_details.user}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
					{else}
						<img width="20px" src="{$themeurl}img/globe_off.png">
						<img width="20px" src="{$themeurl}img/json_off.png">
						<img width="20px" src="{$themeurl}img/kmz_off.png">
					{/if}
					</h2>
					<table class="content_table">
						<tbody>
							<tr class="header">
								<th class="header">Files</th>
								<th class="header">New APs</th>
								<th class="header">APs Total</th>
								<th class="header">Points Total</th>
								<th class="header">Efficiency</th>
								<th class="header">First Import</th>
								<th class="header">Last Import</th>
								{if $wifidb_login_logged_in == 1 and $wifidb_user_details.regid}
								<th class="header">Actions</th>
								{/if}
								{if $wifidb_login_priv_name == "Administrator"}
								<th class="header">Admin</th>
								{/if}
							</tr>
							<tr class="light">
								<td class="light">{$wifidb_user_details.total_files|number_format:0}</td>
								<td class="light">{$wifidb_user_details.new_aps|number_format:0}</td>
								<td class="light"><a href="{$wifidb_host_url}opt/userstats.php?func=allap&amp;user={$wifidb_user_details.user}">{$wifidb_user_details.total_aps|number_format:0}</a></td>
								<td class="light">{$wifidb_user_details.total_gps|number_format:0}</td>
								<td class="light">{$wifidb_user_details.NewAPPercent}%</td>
								<td class="light">{$wifidb_user_details.first_import_date}</td>
								<td class="light">{$wifidb_user_details.newest_date}</td>
								{if $wifidb_login_logged_in == 1 and $wifidb_user_details.regid}<td class="light"><a class="links" href="{$wifidb_host_url}cp/messages.php?func=sendmsg&to={$wifidb_user_details.regid}"><img  title="Message [{$wifidb_user_details.user}]" width="20px" src="{$themeurl}img/send-message.png"></a></td>{/if}
								{if $wifidb_login_priv_name == "Administrator"}<td class="{$wifidb_users.class}"></td>{/if}
							</tr>
						</tbody>
					</table>
					<br/>
					<table class="content_table">
						<tbody>
							<tr class="subheading">
								<th class="subheading" {if $wifidb_login_priv_name == "Administrator"}colspan="10"{else}colspan="9"{/if}>Imported Files</th>
							</tr>
							<tr class="header">
								<th class="header" width="75px">
									<div>GPS</div>
									<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
								</th>
								<th class="header">
									<div>ID</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=id&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'id' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=id&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'id' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>File</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=file_orig&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'file_orig' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=file_orig&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'file_orig' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Title</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=title&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'title' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=title&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'title' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Notes</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=notes&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'notes' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=notes&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'notes' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>APs</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=aps&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'aps' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=aps&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'aps' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Points</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=gps&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'gps' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=gps&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'gps' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header">
									<div>Efficiency</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=NewAPPercent&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'NewAPPercent' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=NewAPPercent&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'NewAPPercent' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								<th class="header" width="150px">
									<div>Date</div>
									<div>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=date&ord=ASC"><img title="Ascending" height="15" width="15" border="0" src="{if $sort == 'date' && $ord == 'ASC'}{$themeurl}img/list_up_sel.png{else}{$themeurl}img/list_up.png{/if}"></a>
										<a href="?func=alluserlists&user={$wifidb_user_details.user}&sort=date&ord=DESC"><img title="Descending" height="15" width="15" border="0" src="{if $sort == 'date' && $ord == 'DESC'}{$themeurl}img/list_down_sel.png{else}{$themeurl}img/list_down.png{/if}"></a>
									</div>
								</th>
								{if $wifidb_login_priv_name == "Administrator"}
								<th class="header" width="150px">
									<div>Admin</div>
									<div><img height="15" width="15" border="0" src="{$themeurl}img/1x1_transparent.gif"></div>
								</th>
								{/if}
							</tr>
							{foreach item=wifidb_user_prev from=$wifidb_user_details.other_imports}
							<tr class="{$wifidb_user_prev.class}">
								<td class="{$wifidb_user_prev.class}">
								{if $wifidb_user_prev.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&labeled=0&id={$wifidb_user_prev.id}" title="Show on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?json=1&func=exp_list&id={$wifidb_user_prev.id}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>					
									<a href="{$wifidb_host_url}api/export.php?func=exp_list&id={$wifidb_user_prev.id}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
								{/if}
								</td>
								<td class="{$wifidb_user_prev.class}"><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_user_prev.id}&amp;user={$wifidb_user_details.user}">{$wifidb_user_prev.id}</a></td>
								<td class="{$wifidb_user_prev.class}"><a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_user_prev.id}&amp;user={$wifidb_user_details.user}">{$wifidb_user_prev.file}</a></td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.title}</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.notes}</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.aps|number_format:0}</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.gps|number_format:0}</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.efficiency}%</td>
								<td class="{$wifidb_user_prev.class}">{$wifidb_user_prev.date}</td>
								{if $wifidb_login_priv_name == "Administrator"}<td class="{$wifidb_users.class}"></td>{/if}
							</tr>
							{/foreach}
						</tbody>
					</table>
{$pages_together}
				</div>
			</div>
{include file="footer.tpl"}