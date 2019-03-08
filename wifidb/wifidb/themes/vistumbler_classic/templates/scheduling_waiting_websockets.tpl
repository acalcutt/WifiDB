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
			<div class="main">
				<meta http-equiv="refresh" content="15">
				<div class="center">
				<table border="1" width="90%">
						<tbody id="daemon_imports">
						<tr class="style4">
							<th colspan="4">Scheduled Imports</th>
						</tr>
						<tr>
							<td  class="style3" colspan="1">Select Refresh Rate:</td>
							<td class="light" colspan="2">
								<form action="scheduling.php?func=refresh" method="post" enctype="multipart/form-data">
									<SELECT NAME="refresh">
										{$wifidb_refresh_options|default:"<option>Error Fetching Refresh Values</option>"}
									</SELECT>
									<INPUT TYPE=SUBMIT NAME="submit" VALUE="Submit">
								</form>
							</td>
						</tr>
						<tr>
							<td class="style3" colspan="1">Local Time Zone</td>
							<td class="light" colspan="1">
								<form action="scheduling.php?func=timezone" method="post" enctype="multipart/form-data">
									<select name="timezone">
										{$wifidb_timezone_options|default:"<option>Error Fetching TimeZones</option>"}
									</select>
									<input type="checkbox" name="dst" value="1" checked="">DST
									<input type="SUBMIT" name="submit" value="Submit">
								</form>
							</td>
						</tr>
					</table>
					<br />
					<table border="1" width="90%">
						<tbody id="daemon_schedule">
							<tr class="style4">
								<th colspan="7">Daemon Schedule</th>
							</tr>
							<tr class="style4">
								<th>Node</th>
								<th>Daemon</th>
								<th>Interval</th>
								<th>Status</th>
								<th>Next Run (UTC)</th>
								<th>Next Run (Local)</th>
							</tr>
							<tr align="center">
								<td colspan="6">
									WebSocket Daemon is Not Running, or is attempting connection.
								</td>
							</tr>
						</tbody>
					</table>
					<br />
					<table border="1" width="90%">
						<tbody id="daemon_stats">
							<tr class="style4">
								<th colspan="7">Daemon Status</th>
							</tr>
							<tr class="style4">
								<th>Node</th>
								<th>PID File</th>
								<th>PID</th>
								<th>Time</th>
								<th>MEM</th>
								<th>CMD</th>
								<th>Updated</th>
							</tr>
							<tr align="center">
								<td colspan="7">
									WebSocket Daemon is Not Running, or is attempting connection.
								</td>
							</tr>
						</tbody>
					</table>
					<br />
					<table border="1" width="90%">
						<tbody id="import_active">
							<tr class="style4">
								<th border="1" colspan="10" align="center">Files being imported</th>
							</tr>
							<tr>
								<th>ID</th>
								<th>File Name</th>
								<th>User Name</th>
								<th>Title</th>
								<th>File Size</th>
								<th>DateTime</th>
								<th>File Hash</th>
								<th>Current AP</th>
								<th>This of Total</th>
							</tr>
							<tr align="center">
								<td colspan="9">
									WebSocket Daemon is Not Running, or is attempting connection.
								</td>
							</tr>
						</tbody>
					</table>
					<br />
					<table border="1" width="90%">
						<tbody id="import_waiting">
							<tr class="style4">
								<th border="1" colspan="8" align="center">Files waiting for import</th>
							</tr>
							<tr>
								<th>ID</th>
								<th>File Name</th>
								<th>User Name</th>
								<th>Title</th>
								<th>File Size</th>
								<th>DateTime</th>
								<th>File Hash</th>
							</tr>
							<tr align="center">
								<td colspan="7">
									WebSocket Daemon is Not Running, or is attempting connection.
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}