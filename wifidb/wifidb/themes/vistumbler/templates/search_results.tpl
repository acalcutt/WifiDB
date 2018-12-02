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
<h2>Search Results</h2>
<p align="center">Total APs found: {$total_rows|default:"0"}</p>
<table border="1" width="100%" cellspacing="0">
    <tbody>
        <tr>
            <td align="center" colspan="9">
                <a title="(Right Click - Save Links As Bookmark)" class="links" href="{$wifidb_host_url}opt/results.php?{$save_url}">Save for later</a><br>
                <a class="links" href="{$wifidb_host_url}opt/export.php?func=exp_search{$export_url}">Export to KMZ</a>
            </td>
        </tr>
        <tr class="style4">
            <td>GPS
            </td>
            <td>SSID
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=SSID&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=SSID&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
            <td>MAC
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=mac&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=mac&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
            <td>Chan
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=chan&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=chan&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
            <td>Radio Type
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=radio&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=radio&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
            <td>Authentication
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=auth&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=auth&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
            <td>Encryption
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=encry&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=encry&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
            <td>First Active
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=FA&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=FA&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
            <td>Last Active
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=LA&amp;ord=ASC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/down.png"></a>
                <a href="?ssid={$ssid_search}&amp;mac={$mac_search}&amp;radio={$radio_search}&amp;chan={$chan_search}&amp;auth={$auth_search}&amp;encry={$encry_search}&amp;sort=LA&amp;ord=DESC&amp;from={$from}&amp;to={$to}"><img height="15" width="15" border="0" src="{$wifidb_host_url}themes/vistumbler/img/up.png"></a>
            </td>
        </tr>
        {foreach item=result from=$results_all}
        <tr class="{$result.class}">
			<td>{$result.globe_html}</td>
            <td><a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$result.id}" title="View AP Details">{$result.ssid}</a></td>
            <td>{$result.mac}</td>
            <td>{$result.chan}</td>
            <td>{$result.radio}</td>
            <td>{$result.auth}</td>
            <td>{$result.encry}</td>
			<td>{$result.FA}</td>
			<td>{$result.LA}</td>
        </tr>
        {foreachelse}
        <tr align="center">
			<td border="1" colspan="9">{$mesg}</td>
		</tr>
        {/foreach}
    </tbody>
</table>
<br/>
{$page_list}
<br/>
{include file="footer.tpl"}