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
                                        <h2>Exports Page</h2>
                                        <form action="{$wifidb_host_url}opt/export.php?func=exp_user_list" method="post" enctype="multipart/form-data">
                                            <table border="1" cellspacing="0" cellpadding="3" align="center">
                                                <tbody>
                                                    <tr class="style4">
                                                        <th colspan="2">Export a Users Import List to KML</th>
                                                    </tr>
                                                    <tr class="light">
                                                        <td>User Import List: </td>
                                                        <td>
                                                            <select name="row">

                                                                {foreach name=outer item=export_titles from=$wifidb_export_imports_all}
                                                                <option value="{$export_titles.id}">User: {$export_titles.username} - Title: {$export_titles.title} - # APs: {$export_titles.aps} - # Date: {$export_titles.date}</option>
                                                                {foreachelse}
                                                                <option value="">No Imports</option>
                                                                {/foreach}
                                                            </select>
                                                        </td>
                                                        </tr>
                                                        <tr class="light">
                                                            <td colspan="2" align="right">
                                                                <input type="submit" value="Export This Users List">
                                                            </td>
                                                        </tr>
                                                </tbody>
                                            </table>
                                        </form>
                                        <form action="{$wifidb_host_url}opt/export.php?func=exp_user_all_kml" method="post" enctype="multipart/form-data">
                                            <table border="1" cellspacing="0" cellpadding="3" align="center">
                                                <tbody>
                                                    <tr class="style4">
                                                        <th colspan="2">Export All Access Points for a User</th>
                                                    </tr>
                                                <tr class="light">
                                                    <td>Username: </td>
                                                    <td>
                                                        <select name="user">
                                                            {foreach name=outer item=export_user from=$wifidb_export_users_all}
                                                            <option value="{$export_user}">{$export_user}</option>
                                                            {foreachelse}
                                                            <option value="">No Users</option>
                                                            {/foreach}
                                                        </select>
                                                    </td>
                                                </tr>
                                                <tr class="light">
                                                    <td colspan="2" align="right">
                                                        <input type="submit" value="Export This Users Access points">
                                                    </td>
                                                </tr>
                                                </tbody>
                                            </table>
                                        </form>
{include file="footer.tpl"}