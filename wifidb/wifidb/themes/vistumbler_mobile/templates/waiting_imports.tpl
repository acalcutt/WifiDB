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
{include file="vistumbler_header.tpl"}
			<div class="main">
				<button type="button" id="sidebarCollapse" class="navbar-btn">
					<span></span>
					<span></span>
					<span></span>
				</button>
				<meta http-equiv="refresh" content="15">
				<table border="1" width="90%">
					<tbody>
					<tr class="header">
						<th colspan="4">Scheduled Imports</th>
					</tr>
					<tr>
						<td class="style3">Next Import scheduled on:</td>
						<td class="light">{$wifidb_next_utc}</td>
						<td class="light">{$wifidb_next_tz}  [ {$wifidb_next_timezn} ]</td>
					</tr>
					<tr>
						<td class="style3" colspan="1">Select Refresh Rate:</td>
						<td class="light" colspan="2">
							<form action="{$wifidb_host_url}opt/scheduling.php?func=refresh" method="post" enctype="multipart/form-data">
								<select name="refresh">  
									{$wifidb_refresh_opt}
								</select>
								<input type="SUBMIT" name="submit" value="Submit">
							</form>
						</td>
					</tr>
					</tbody>
				</table>
				<br>
				<table border="1" width="90%">
				<tbody>
				<tr class="header">
					<th colspan="4">Daemon Status: {$wifidb_daemon_status}</th>
				</tr>
				{$wifidb_daemon_status}
												
				</tbody>
				</table>
				{$wifidb_sched_notif}
				<br>
				<table border="1" width="90%">
					<tbody>
						<tr class="header">
							<th border="1" colspan="7" align="center">Files waiting for import</th>
						</tr>
						<tr class="light">
							{foreach name=outer item=wifidb_waiting from=$wifidb_waiting}
						
							<table style="background-color: lime" border="1" width="100%">
								<tbody>
								<tr class="header">
									<th>ID</th>
									<th>Filename</th>
									<th>Title</th>
									<th>Date</th>
									<th>size</th>
								</tr>
								<tr style="background-color: lime">
									<td align="center">{$wifidb_waiting.id}</td>
									<td align="center">{$wifidb_waiting.filename}</td>
									<td align="center">{$wifidb_waiting.title}</td>
									<td align="center">{$wifidb_waiting.date}</td>
									<td align="center">{$wifidb_waiting.size}</td>
								</tr>
								<tr class="header">
									<th style="background-color: lime">
									</th>
									<th>Hash Sum</th><th>User</th><th>Current SSID</th><th>AP / Total AP's</th></tr>
								<tr style="background-color: lime">
									<td></td>
									<td align="center">{$wifidb_waiting.hash}</td><!--Mmm-->
									<td align="center">{$wifidb_waiting.user}</td>
									<td align="center">
										<table align="center">
											<tbody>
											<tr>
												<td valign="center" align="right">{$wifidb_waiting.ssid|default:""}</td>
												<td valign="center" align="left"><img width="20px" src="../img/globe_{$wifidb_gps}.png"></td>
											</tr>
											<tr>
												<td align="center">{$wifidb_waiting.tot|default:"Waiting to import..."}</td>
											</tr>
											</tbody>
										</table>
									</td>
								</tr>
							</table>
									
							{/foreachelse}
							<td border="1" colspan="7" align="center">There are no files waiting to be imported, Go and import a file</td>
							{/foreach}
						</tr>
					</tbody>
				</table>
			</div>
{include file="vistumbler_footer.tpl"}