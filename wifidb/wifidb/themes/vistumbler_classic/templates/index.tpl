<!--
index.tpl: The Smarty Index template for WiFiDB.
Copyright (C) 2013 Phil Ferland

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
<table width="85%" border="1" cellpadding="2" cellspacing="0">
    <tbody>
        <tr>
            <td colspan="4" class="style1"><strong><em>Statistics</em></strong></td>
    </tr>
    <tr class="style3"><td class="style2" colspan="4"></td></tr>
    <tr>
            <th class="style3" style="width: 100px">Total AP's</th>
            <th class="style3">Open APs</th>
            <th class="style3">WEP APs</th>
            <th class="style3">Secure APs</th>
    </tr>
    <tr class="light">
            <td align="center" class="style2"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=allusers" title="All Users">{$total_aps}</a></td>
            <td align="center" class="style2"><a class="links" href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=1&from=0&to=25" title="Open APs">{$open_aps}</a></td>
            <td align="center" class="style2"><a class="links" href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=2&from=0&to=25" title="WEP APs">{$wep_aps}</a></td>
            <td align="center" class="style2"><a class="links" href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=3&from=0&to=25" title="Secure APs">{$sec_aps}</a></td>
    </tr>
    <tr class="style3"><td class="style2" colspan="4"></td></tr>
    <tr>
            <th class="style3" style="width: 100px">Total Users</th>
            <th class="style3">Last user to import</th>
            <th class="style3">Last AP added</th>
            <th class="style3">Last Import List</th>
    </tr>
    <tr class="dark">
            <td align="center" class="style2" style="width: 100px"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=allusers" title="View All Users">{$total_users}</a></td>
            <td align="center" class="style2">
                <p align="center">
                <table>
                    <tbody>
                        <tr>
                            <td align="right" width="100%">
                                <a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$new_import_user}" title="View User Details">{$new_import_user}</a>
                            </td>
                            <td align="left">
                                {$user_globe_html}
                            </td>
                        </tr>
                    </tbody>
                </table>
                </p>
            </td>
            <td align="center" class="style2">
                <p align="center">
                <table>
                    <tbody>
                        <tr>
                            <td align="right" width="100%">
                                <a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$new_ap_id}" title="View AP Details">{$new_ap_ssid}</a>
                            </td>
                            <td align="left">
                                {$ap_globe_html}
                            </td>
                        </tr>
                    </tbody>
                </table>
                </p>
            </td>
            <td align="center" class="style2">
                <p align="center">
                <table>
                    <tbody>
                        <tr>
                            <td align="right" width="100%">
                                <a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$new_import_id}"  title="View List Details">{$new_import_title}</a>
                            </td>
                            <td align="left">
                                {$list_globe_html}
                            </td>
                        </tr>
                    </tbody>
                </table>
                </p>
            </td>
    </tr>
    </tbody>
</table>
{include file="footer.tpl"}