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
					<h2>Files Waiting For Import</h2>
					<table class="content_table">
						<tr class="subheading">
							<th colspan="2">Schedule Display Settings</th>
						</tr>
						<tr>
							<td  class="header" colspan="1">Page Refresh Rate</td>
							<td class="light" colspan="1">
								<form action="scheduling.php?func=refresh" method="post" enctype="multipart/form-data">
									<SELECT NAME="refresh">  
										{$wifidb_refresh_options}
									</SELECT>
									<INPUT TYPE=SUBMIT NAME="submit" VALUE="Submit">
								</form>
							</td>
						</tr>
						<tr>
							<td  class="header" colspan="1">Local Time Zone</td>
							<td class="light" colspan="1">
								<form action="scheduling.php?func=timezone" method="post" enctype="multipart/form-data">
									<SELECT NAME="timezone">  
										{$wifidb_timezone_options}
									</SELECT>
									<input type="checkbox" name="dst" value="1" {$wifidb_dst_options}>DST
									<INPUT TYPE=SUBMIT NAME="submit" VALUE="Submit">
								</form>
							</td>
						</tr>
					</table>
					<br />
					<table class="content_table">
						<tr class="subheading">
							<th colspan="7">Daemon Schedule</th>
						</tr>
						<tr class="header-centered">
							<th class="header-centered">NODE</th>
							<th class="header-centered">DAEMON</th>
							<th class="header-centered">INTERVAL</th>
							<th class="header-centered">STATUS</th>
							<th class="header-centered">NEXT RUN(UTC)</th>
							<th class="header-centered">NEXT RUN(Local)</th>
						</tr>
						{foreach item=wifidb_schedule from=$wifidb_schedules}
						<tr class="{$wifidb_schedule.color}">
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.nodename}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.daemon}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.interval} minutes</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.status}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.nextrun_utc}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.nextrun_local}</td>
						</tr>
						{foreachelse}
						<tr class="light-centered">
							<td class="light-centered" colspan="7">
								Sorry there is nothing scheduled...
							</td>
						</tr> 
						{/foreach}
					</table>
					<br />
					<table class="content_table">
						<tr class="subheading">
							<th colspan="7">Daemon Status</th>
						</tr>
						<tr class="header-centered">
							<th class="header-centered">NODE</th>
							<th class="header-centered">PID FILE</th>
							<th class="header-centered">PID</th>
							<th class="header-centered">TIME</th>
							<th class="header-centered">MEM</th>
							<th class="header-centered">CMD</th>
							<th class="header-centered">UPDATED</th>
						</tr>
						{foreach item=wifidb_daemon from=$wifidb_daemons}
						<tr class="{$wifidb_daemon.color}">
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.nodename}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidfile}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pid}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidtime}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidmem}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidcmd}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.date} (UTC)</td>
						</tr>
						{foreachelse}
						<tr align="center">
							<td class="light-centered" colspan="7">
								Sorry there are no daemon PIDs...
							</td>
						</tr>
						{/foreach}
					</table>
					<br />
					<table class="content_table">
						<tr class="subheading">
							<th colspan="6" align="center">Files being imported</th>
						</tr>
						{foreach item=wifidb_imp from=$wifidb_importing name=importing}
						<tr class="header-centered">
							<th class="{$wifidb_imp.color}"></th>
							<th class="header-centered">ID</th>
							<th class="header-centered">Title</th>
							<th class="header-centered">Filename</th>
							<th class="header-centered">Notes</th>
							<th class="header-centered">Size</th>
						</tr>
						<tr class="{$wifidb_imp.color}">
							<td class="{$wifidb_imp.color}"></td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.id}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.title}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.file}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.notes}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.size}</td>
						</tr>
						<tr class="header-centered">
							<th class="{$wifidb_imp.color}"></th>
							<th class="header-centered">Date</th>
							<th class="header-centered">User</th>
							<th class="header-centered">Hash Sum</th>
							<th class="header-centered">Current SSID</th>
							<th class="header-centered">Status</th>
						</tr>
						<tr class="green">
							<td class="{$wifidb_imp.color}"></td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.date}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.user}</td>
							<td class="{$wifidb_imp.color}">{$wifidb_imp.hash}</td>
							{$wifidb_imp.last_cell}
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
					<br />
					<table class="content_table">
						<tr class="header-centered">
							<th colspan="6" align="center">Files waiting for import</th>
						</tr>
						{foreach item=wifidb_wait from=$wifidb_waiting name=waiting}
						<tr class="header-centered">
							<th class="yellow"></th>
							<th class="header-centered">ID</th>
							<th class="header-centered">Title</th>
							<th class="header-centered">Filename</th>
							<th class="header-centered">Notes</th>
							<th class="header-centered">Size</th>
						</tr>
						<tr style="background-color: {$wifidb_wait.color}">
							<td class="yellow"></td>
							<td class="yellow">{$wifidb_wait.id}</td>
							<td class="yellow">{$wifidb_wait.title}</td>
							<td class="yellow">{$wifidb_wait.file}</td>
							<td class="yellow">{$wifidb_wait.notes}</td>
							<td class="yellow">{$wifidb_wait.size}</td>
						</tr>
						<tr class="header-centered">
							<th class="yellow"></th>
							<th class="header-centered">Date</th>
							<th class="header-centered">User</th>
							<th class="header-centered">Hash Sum</th>
							<th class="header-centered" colspan="2">Status</th>
						</tr>
						<tr style="background-color: {$wifidb_wait.color}">
							<td class="yellow"></td>
							<td class="yellow">{$wifidb_wait.date}</td>
							<td class="yellow">{$wifidb_wait.user}</td>
							<td class="yellow">{$wifidb_wait.hash}</td>
							{$wifidb_wait.last_cell}
						</tr>
						{if not $smarty.foreach.waiting.last}
						<tr class="content-centered">
							<th colspan="6"><br/></th>
						</tr>
						{/if}
						{foreachelse}
						<tr align="center">
							<td class="light-centered colspan="5">
								 Sorry there are no Imports waiting...
							</td>
						</tr>
						{/foreach}
					</table>
				</div>
			</div>
{include file="footer.tpl"}