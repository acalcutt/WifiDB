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
        <meta http-equiv="refresh" content="15">
                <table class="content_table">
                    <tr class="style4">
                        <th colspan="2">Schedule Display Settings</th>
                    </tr>
                    <tr>
                        <td  class="style3" colspan="1">Page Refresh Rate</td>
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
                        <td  class="style3" colspan="1">Local Time Zone</td>
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
                    <tr class="style4">
                        <th colspan="7">Daemon Schedule</th>
                    </tr>
                    <tr class="style4">
                        <th>NODE</th>
                        <th>DAEMON</th>
                        <th>INTERVAL</th>
                        <th>STATUS</th>
                        <th>NEXT RUN(UTC)</th>
                        <th>NEXT RUN(Local)</th>
                    </tr>
                    {foreach item=wifidb_schedule from=$wifidb_schedules}
                    <tr align="center" bgcolor="{$wifidb_schedule.color}">
                        <td>{$wifidb_schedule.nodename}</td>
                        <td>{$wifidb_schedule.daemon}</td>
                        <td>{$wifidb_schedule.interval} minutes</td>
                        <td>{$wifidb_schedule.status}</td>
                        <td>{$wifidb_schedule.nextrun_utc}</td>
                        <td>{$wifidb_schedule.nextrun_local}</td>
                    </tr>
                    {foreachelse}
                    <tr align="center">
                        <td border="1" colspan="7">
                            Sorry there is nothing scheduled...
                        </td>
                    </tr> 
                    {/foreach}
                </table>
                <br />
                <table class="content_table">
                    <tr class="style4">
                        <th colspan="7">Daemon Status</th>
                    </tr>
                    <tr class="style4">
                        <th>NODE</th>
                        <th>PID FILE</th>
                        <th>PID</th>
                        <th>TIME</th>
                        <th>MEM</th>
                        <th>CMD</th>
                        <th>UPDATED</th>
                    </tr>
                    {foreach item=wifidb_daemon from=$wifidb_daemons}
                    <tr align="center" bgcolor="{$wifidb_daemon.color}">
                        <td>{$wifidb_daemon.nodename}</td>
                        <td>{$wifidb_daemon.pidfile}</td>
                        <td>{$wifidb_daemon.pid}</td>
                        <td>{$wifidb_daemon.pidtime}</td>
                        <td>{$wifidb_daemon.pidmem}</td>
                        <td>{$wifidb_daemon.pidcmd}</td>
                        <td>{$wifidb_daemon.date} (UTC)</td>
                    </tr>
                    {foreachelse}
                    <tr align="center">
                        <td border="1" colspan="7">
                            Sorry there are no daemon PIDs...
                        </td>
                    </tr>
                    {/foreach}
                </table>
                <br />
                <table class="content_table">
                    <tr class="style4">
                        <th border="1" colspan="7" align="center">Files being imported</th>
                    </tr>
                    <tr align="center">
                        <td border="1">
                            {foreach item=wifidb_imp from=$wifidb_importing}
                            <br>
                            <table style="background-color: {$wifidb_imp.color}" border="1"  width="100%">
                                <tr class="style4">
                                    <th>ID</th>
                                    <th>Filename</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>size</th>
                                </tr>
                                <tr style="background-color: {$wifidb_imp.color}">
                                    <td align="center">{$wifidb_imp.id}</td>
                                    <td align="center">{$wifidb_imp.file}</td>
                                    <td align="center">{$wifidb_imp.title}</td>
                                    <td align="center">{$wifidb_imp.date}</td>
                                    <td align="center">{$wifidb_imp.size}</td>
                                </tr>
                                <tr class="style4">
                                    <th style="background-color: {$wifidb_imp.color}"></th>
                                    <th>Hash Sum</th>
                                    <th>User</th>
                                    <th>Current SSID</th>
                                    <th>Status</th>
                                </tr>
                                <tr style="background-color: {$wifidb_imp.color}">
                                    <td></td>
                                    <td align="center">{$wifidb_imp.hash}</td>
                                    <td align="center">{$wifidb_imp.user}</td>
                                    {$wifidb_imp.last_cell}
                                </tr>
                            </table>
                            {foreachelse}
                                Sorry there are no files importing...
                            {/foreach}
                        </td>
                    </tr>
                </table>
				<br />
                <table class="content_table">
                    <tr class="style4">
                        <th border="1" colspan="7" align="center">Files waiting for import</th>
                    </tr>
                    <tr align="center">
                        <td border="1">
                            {foreach item=wifidb_wait from=$wifidb_waiting}
                            <br>
                            <table style="background-color: {$wifidb_wait.color}" border="1"  width="100%">
                                <tr class="style4">
                                    <th>ID</th>
                                    <th>Filename</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>size</th>
                                </tr>
                                <tr style="background-color: {$wifidb_wait.color}">
                                    <td align="center">{$wifidb_wait.id}</td>
                                    <td align="center">{$wifidb_wait.file}</td>
                                    <td align="center">{$wifidb_wait.title}</td>
                                    <td align="center">{$wifidb_wait.date}</td>
                                    <td align="center">{$wifidb_wait.size}</td>
                                </tr>
                                <tr class="style4">
                                    <th style="background-color: {$wifidb_wait.color}"></th>
                                    <th>Hash Sum</th>
                                    <th>User</th>
                                    <th colspan="2">Status</th>
                                </tr>
                                <tr style="background-color: {$wifidb_wait.color}">
                                    <td></td>
                                    <td align="center">{$wifidb_wait.hash}</td>
                                    <td align="center">{$wifidb_wait.user}</td>
                                    {$wifidb_wait.last_cell}
                                </tr>
                            </table>
                            {foreachelse}
                                Sorry there are no Imports waiting...
                            {/foreach}
                        </td>
                    </tr>
                </table>
{include file="footer.tpl"}