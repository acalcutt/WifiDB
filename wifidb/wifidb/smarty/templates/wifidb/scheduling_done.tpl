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
<br>
<table border="1" width="90%">
    <tr class="style4">
        <th colspan="9" align="center">Files already imported</th>
    </tr>
    {foreach name=done_all item=wifidb_done from=$wifidb_done_all_array}
    <tr class="sub_head">
        <th>ID</th>
        <th>Filename</th>
        <th>Title</th>
        <th>User</th>
        <th>Size</th>
    </tr>
    <tr class="{$wifidb_done.class}">
        <td align="center">{$wifidb_done.id}</td>
        <td align="center"><a class="links" href="../opt/userstats.php?func=useraplist&row={$wifidb_done.user_row}">{$wifidb_done.file}</a></td>
        <td align="center">{$wifidb_done.title}</td>
        <td align="center"><a class="links" href ="../opt/userstats.php?func=alluserlists&user={$wifidb_done.user}">{$wifidb_done.user}</a></td>
        <td align="center">{$wifidb_done.size}</td>
    </tr>
    <tr class="sub_head">
        <th colspan="3">Hash</th>
        <th>Date</th>
        <th>APs/GPS Count</th>
    </tr>
    <tr class="{$wifidb_done.class}">
        <td align="center" colspan="3">{$wifidb_done.hash}</td>
        <td align="center">{$wifidb_done.date}</td>
        <td align="center">{$wifidb_done.aps}/{$wifidb_done.gps}</td>
    </tr>
    <tr class="sub_head"><td></td></tr>
    {foreachelse}
    <tr class="sub_head">
        <td>There are no Imports yet, go get some...</td>
    </tr>
    {/foreach}
</table>
{include file="footer.tpl"}