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
                <table>
                    <tbody>
                        <tr>
                            <td>
                                <table border="1" align="center" width="100%">
                                    <tbody>
                                        <tr class="style4">
                                            <th colspan="2">Access Points For: <a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_all_user_aps.username}">{$wifidb_all_user_aps.username}</a></th>
                                        </tr>
                                        <tr class="sub_head">
                                            <td width="190px"><b>Total Access Points...</b></td>
                                            <td>{$wifidb_all_user_aps.total_aps}</td>
                                        </tr>
                                        <tr class="sub_head">
                                            <td width="190px"><b>Export This list To...</b></td>
                                            <td><a class="links" href="{$wifidb_host_url}opt/export.php?func=exp_user_all_kml&amp;user={$wifidb_all_user_aps.username}">KML</a></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br/>
                                <table border="1" align="center">
                                    <tbody>
                                        <tr class="style4">
                                            <th>AP ID</th>
                                            <th>GPS</th>
                                            <th>SSID</th>
                                            <th>Mac Address</th>
                                            <th>Authentication</th>
                                            <th>Encryption</th>
                                            <th>Radio</th>
                                            <th>Channel</th>
                                            <th>First Active</th>
                                            <th>Last Active</th>
                                        </tr>
{foreach name=outer item=wifidb_users_aps from=$wifidb_all_user_aps.allaps}
                                        <tr class="{$wifidb_users_aps.class}">
                                            <td align="center">{$wifidb_users_aps.id}</td>
                                            <td align="center"><img width="20px" src="{$wifidb_host_url}img/globe_{$wifidb_users_aps.globe}.png"></td>
                                            <td align="center"><a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_users_aps.id}">{$wifidb_users_aps.ssid}</a></td>
                                            <td>{$wifidb_users_aps.mac}</td>
                                            <td align="center">{$wifidb_users_aps.auth}</td>
                                            <td align="center">{$wifidb_users_aps.encry}</td>
                                            <td align="center">{$wifidb_users_aps.radio}</td>
                                            <td align="center">{$wifidb_users_aps.chan}</td>
                                            <td align="center">{$wifidb_users_aps.fa}</td>
                                            <td align="center">{$wifidb_users_aps.la}</td>
                                        </tr>
{/foreach}
                                    </tbody>
                                </table>
                            </td>
                        </tr>
                        <tr class="sub_head">
                            <td colspan="6" align="center">
                                {$pages_together}
                            </td>
                        </tr>
                    </tbody>
                </table>
                        <br/>
{include file="footer.tpl"}