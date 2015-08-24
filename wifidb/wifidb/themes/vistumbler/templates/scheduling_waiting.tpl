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
                <table border="1" width="90%">
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
                    <tr align="center">
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                </table>
                <br />
				<table border="1" width="90%">
					<tr class="style4">
						<th border="1" colspan="7" align="center">Files being imported</th>
					</tr>
					<tr id="import_active" align="center">
						<td border="1">

						</td>
					</tr>
				</table>
                <table border="1" width="90%">
                    <tr class="style4">
                        <th border="1" colspan="7" align="center">Files waiting for import</th>
                    </tr>
                    <tr id="import_waiting" align="center">
                        <td border="1">

                        </td>
                    </tr>
                </table>
{include file="footer.tpl"}