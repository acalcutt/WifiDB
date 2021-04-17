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
					<span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php"><img src="{$themeurl}img/file-importing.png" style="vertical-align: middle;"/> Files Importing ({$importing_count})</a></span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=waiting"><img src="{$themeurl}img/file-waiting.png" style="vertical-align: middle;"/> Files Waiting ({$waiting_count})</a></span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=done"><img src="{$themeurl}img/file-complete.png" style="vertical-align: middle;"/> Files Completed ({$complete_count})</a></span>
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
							<td  class="header" colspan="1">Time Zone</td>
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
						<tr>
							<td  class="header" colspan="1">Local Time</td>
							<td class="light" colspan="1">{$curtime_local}</td>
						</tr>
					</table>
					<br />
					<table class="content_table">
						<tr class="subheading">
							<th colspan="7">Daemon Schedule</th>
						</tr>
						<tr class="header-centered">
							<th class="header-centered">ID</th>
							<th class="header-centered">NODE</th>
							<th class="header-centered">DAEMON</th>
							<th class="header-centered">INTERVAL</th>
							<th class="header-centered">STATUS</th>
							<th class="header-centered">PID</th>
							<th class="header-centered">NEXT RUN</th>
						</tr>
						{foreach item=wifidb_schedule from=$wifidb_schedules}
						<tr class="{$wifidb_schedule.color}">
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.id|escape:'htmlall'}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.nodename|escape:'htmlall'}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.daemon|escape:'htmlall'}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.interval|escape:'htmlall'} minutes</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.status|escape:'htmlall'}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.schedpid|escape:'htmlall'}</td>
							<td class="{$wifidb_schedule.color}">{$wifidb_schedule.nextrun_local|escape:'htmlall'}</td>
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
							<th class="header-centered">CPU</th>
							<th class="header-centered">MEM</th>
							<th class="header-centered">CMD</th>
							<th class="header-centered">UPDATED</th>
						</tr>
						{foreach item=wifidb_daemon from=$wifidb_daemons}
						<tr class="{$wifidb_daemon.color}">
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.nodename|escape:'htmlall'}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidfile|escape:'htmlall'}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pid|escape:'htmlall'}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidcpu|escape:'htmlall'}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidmem|escape:'htmlall'}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.pidcmd|escape:'htmlall'}</td>
							<td class="{$wifidb_daemon.color}">{$wifidb_daemon.lastupdatetime_local|escape:'htmlall'}</td>
						</tr>
						{foreachelse}
						<tr align="center">
							<td class="light-centered" colspan="7">
								Sorry there are no daemon PIDs...
							</td>
						</tr>
						{/foreach}
					</table>
				</div>
			</div>
{include file="footer.tpl"}