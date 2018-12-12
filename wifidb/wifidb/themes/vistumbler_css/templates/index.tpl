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
<table class="content_table">
    <tbody>
        <tr>
            <td colspan="4" class="content_table_header">Statistics</td>
		</tr>
		<tr>
				<th class="content_table_header">Total AP's</th>
				<th class="content_table_header">Open APs</th>
				<th class="content_table_header">WEP APs</th>
				<th class="content_table_header">Secure APs</th>
		</tr>
		<tr>
				<td class="content_table_data_light"><a href="{$wifidb_host_url}opt/userstats.php?func=allusers" title="All Users">{$total_aps}</a></td>
				<td class="content_table_data_light"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=1&from=0&to=25" title="Open APs">{$open_aps}</a></td>
				<td class="content_table_data_light"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=2&from=0&to=25" title="WEP APs">{$wep_aps}</a></td>
				<td class="content_table_data_light"><a href="{$wifidb_host_url}opt/results.php?ord=DESC&sort=ModDate&sectype=3&from=0&to=25" title="Secure APs">{$sec_aps}</a></td>
		</tr>
		<tr>
				<th class="content_table_header">Total Users</th>
				<th class="content_table_header">Last user to import</th>
				<th class="content_table_header">Last AP added</th>
				<th class="content_table_header">Last Import List</th>
		</tr>
		<tr>
				<td class="content_table_data_dark">
					<a href="{$wifidb_host_url}opt/userstats.php?func=allusers" title="View All Users">{$total_users}</a>
				</td>
				<td class="content_table_data_dark">
					<a href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$new_import_user}" title="View User Details">{$new_import_user}</a>{$user_globe_html}
				</td>
				<td class="content_table_data_dark">
					<a href="{$wifidb_host_url}opt/fetch.php?id={$new_ap_id}" title="View AP Details">{$new_ap_ssid}</a>{$ap_globe_html}
				</td>
				<td class="content_table_data_dark">
					<a href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$new_import_id}"  title="View List Details">{$new_import_title}</a>{$list_globe_html}
				</td>
		</tr>
    </tbody>
</table>
{include file="footer.tpl"}