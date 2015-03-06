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
<table width="700px" border="1" cellspacing="0" cellpadding="0" align="center">
    <tbody>
        <tr>
            <td>
                <table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
                    <tbody>
                        <tr>
                            <td class="style4">Daemon Generated KMZ<br>
                                <font size="2">All times are local system time.</font>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
                    <tbody>
                        <tr class="light">
                            <td class="daemon_kml" colspan="3">
                                <a class="links" href="{$wifidb_host_url}out/daemon/update.kml">Current WiFiDB Network Link</a>
                            </td>
                        </tr>
                        <tr class="light">
                            <td class="daemon_kml">Newest AP KML Last Edit: </td>
                            <td>{$wifidb_daemon_newest_lastedit}</td>
                            <td>{$wifidb_daemon_newest_size}</td>
                        </tr>
                        <tr class="light">
                            <td class="daemon_kml">Full KML Last Edit: </td>
                            <td>{$wifidb_daemon_full_lastedit}</td>
                            <td>{$wifidb_daemon_full_size}</td>
                        </tr>
                        <tr class="light">
                            <td class="daemon_kml">Daily KML Last Edit: </td>
                            <td>{$wifidb_daemon_daily_lastedit}</td>
                            <td>{$wifidb_daemon_daily_size}</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="style4">History</td>
                        </tr>
                        <tr>
                            <td colspan="3" class="dark">
                                <table align="center" border="1" cellspacing="0" cellpadding="0" width="50%">
                                    <tbody>
                                        <tr class="style4">
                                            <td width="33%">Date Created</td>
                                            <td width="33%">Last Edit Time</td>
                                            <td width="33%">Size</td>
                                        </tr>
                                        <tr class="dark">
                                            <td width="33%"><a class="links" href="{$wifidb_host_url}out/daemon/{$wifidb_daemon_kmz.date}/fulldb.kmz">{$wifidb_daemon_kmz.date}</a></td>
                                            <td width="33%">{$wifidb_daemon_kmz.lastedit}</td>
                                            <td width="33%">{$wifidb_daemon_kmz.size}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </td>
        </tr>
    </tbody>
</table>
{include file="footer.tpl"}