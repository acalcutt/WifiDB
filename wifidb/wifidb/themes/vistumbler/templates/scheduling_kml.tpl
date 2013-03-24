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
                                            <tr>
                                                <td>
                                                <table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
                                                    <tr>
                                                        <td class="style4">Daemon Generated KML<br><font size="2">All times are local system time.</font></td>
                                                    </tr>
                                                </table>

                                                <table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
                                                        <tr class="light">
                                                            <td class="daemon_kml" colspan="3">
                                                                {$wifidb_kml_head.update_kml}
                                                            </td>
                                                        </tr>
                                                        <tr class="light">
                                                            <td class="daemon_kml">Newest AP KML Last Edit: </td>
                                                            <td>{$wifidb_kml_head.newest_date}</td>
                                                            <td>{$wifidb_kml_head.newest_size}</td>
                                                        </tr>
                                                        <tr class="light">
                                                            <td class='daemon_kml'>Full KML Last Edit: </td>
                                                                <td>{$wifidb_kml_head.full_date}</td>
                                                                <td>{$wifidb_kml_head.full_size}</td>
                                                        </tr>
                                                        <tr class="light">
                                                            <td class='daemon_kml'>Daily KML Last Edit: </td>
                                                                <td>{$wifidb_kml_head.today_date}</td>
                                                                <td>{$wifidb_kml_head.today_size}</td>
                                                            </td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="3" class="style4">History</td>
                                                        </tr>
                                                        <tr>
                                                            <td colspan="3" class="dark">
                                                            {foreach item=wifidb_kml from=$wifidb_kml_all_array}
                                                                <table align="center" border="1" cellspacing="0" cellpadding="0" width="50%">
                                                                    <tr class="style4">
                                                                            <td width="33%">Date Created</td>
                                                                            <td width="33%">Last Edit Time</td>
                                                                            <td width="33%">Size</td>
                                                                    </tr>
                                                                    <tr class="dark">
                                                                        <td width="33%">
                                                                            <a class="links" href="{$wifidb_kml.file_url}">{$wifidb_kml.file}</a>
                                                                        </td>
                                                                        <td width="33%">{$wifidb_kml.time}</td>
                                                                        <td width="33%">{$wifidb_kml.size}</td>
                                                                    </tr>
                                                                </table>
                                                            {foreachelse}
                                                                There are no KML files that have been generated yet.
                                                            {/foreach}
                                                            </td>
                                                        </tr>
                                                    </table>
                                                </td>
                                            </tr>
                                        </table>
{include file="footer.tpl"}