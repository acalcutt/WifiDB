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
                <table id="scheduling" border="1" width="90%">
                    <tr class="style4">
                        <th colspan="4">Scheduled Imports</th>
                    </tr>
                    <tr>
                        <td class="style3">Next Import scheduled on:</td>
                        <td class="light">{$wifidb_next_run.utc}</td>
                        <td class="light">{$wifidb_next_run.local}</td>
                    </tr>
                    <tr>
                        <td  class="style3" colspan="1">Select Refresh Rate:</td>
                        <td class="light" colspan="2">
                            <form action="scheduling.php?func=refresh" method="post" enctype="multipart/form-data">
                                <SELECT NAME="refresh">  
                                    {$wifidb_refresh_options}
                                </SELECT>
                                <INPUT TYPE=SUBMIT NAME="submit" VALUE="Submit">
                            </form>
                        </td>
                    </tr>
                </table>
                <br />
                <table border="1" width="90%">
                    <tr class="style4">
                        <th colspan="4">WiFiDB {$wifidb_daemon.OS} Daemon Status:</th>
                    </tr>
                    <tr class="style4">
                        <th>PID</th>
                        <th>TIME</th>
                        <th>Memory</th>
                        <th>CMD</th>
                    </tr>
                    <tr align="center" bgcolor="{$wifidb_daemon.color}">
                        <td>{$wifidb_daemon.pid}</td>
                        <td>{$wifidb_daemon.time}</td>
                        <td>{$wifidb_daemon.mem}</td>
                        <td>{$wifidb_daemon.cmd}</td>
                    </tr>
                </table>
                <br />
                <table border="1" width="90%">
                    <tr class="style4">
                        <th border="1" colspan="7" align="center">Files waiting for import</th>
                    </tr>
                    <tr align="center">
                        <td border="1">
                            {foreach item=wifidb_done from=$wifidb_done_all}
                            <br>
                            <table style="background-color: {$wifidb_done.color}" border="1" width="100%">
                                <tr class="style4">
                                    <th>ID</th>
                                    <th>Filename</th>
                                    <th>Title</th>
                                    <th>Date</th>
                                    <th>size</th>
                                </tr>
                                <tr style="background-color: {$wifidb_done.color}">
                                    <td align="center">{$wifidb_done.id}</td>
                                    <td align="center">{$wifidb_done.file}</td>
                                    <td align="center">{$wifidb_done.title}</td>
                                    <td align="center">{$wifidb_done.date}</td>
                                    <td align="center">{$wifidb_done.size}</td>
                                </tr>
                                <tr class="style4">
                                    <th style="background-color: {$wifidb_done.color}"></th>
                                    <th>Hash Sum</th>
                                    <th>User</th>
                                    <th>Current SSID</th>
                                    <th>AP / Total AP's</th>
                                </tr>
                                <tr style="background-color: {$wifidb_done.color}">
                                    <td></td>
                                    <td align="center">{$wifidb_done.hash}</td>
                                    <td align="center">{$wifidb_done.user}</td>
                                    {$wifidb_done.last_cell}
                                </tr>
                            </table>
                            {foreachelse}
                                Sorry there are no Imports waiting...
                            {/foreach}
                        </td>
                    </tr>
                </table>
{include file="footer.tpl"}