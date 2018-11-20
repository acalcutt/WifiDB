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
		<p align="center">
                    <script language="JavaScript">
			// Row Hide function.
			// by tcadieux
			function expandcontract(tbodyid,ClickIcon)
			{
				if (document.getElementById(ClickIcon).innerHTML == "+")
				{
					document.getElementById(tbodyid).style.display = "";
					document.getElementById(ClickIcon).innerHTML = "-";
				}else{
					document.getElementById(tbodyid).style.display = "none";
					document.getElementById(ClickIcon).innerHTML = "+";
				}
			}
			</script>
			</p>
                        <h1>Stats For: All Users</h1>
			<table border="1" align="center" width="90%">
                            <tbody>
                                <tr class="style4">
                                    <th width="50px">Show/Hide</th>
                                    <th width="50px">ID</th>
                                    <th>Username</th>
                                    <th>Imports</th> 
                                </tr>
                                {foreach name=outer item=wifidb_users from=$wifidb_imports_all}
                                <tr class="{$wifidb_users.class}">
                                    <td align="center" onclick="expandcontract('Row{$wifidb_users.rowid}','ClickIcon{$wifidb_users.rowid}')" id="ClickIcon{$wifidb_users.rowid}" style="cursor: pointer; cursor: hand;">+</td>
                                    <td align="center">{$wifidb_users.id}</td>
                                    <td align="center"><a class="links" href="?func=alluserlists&user={$wifidb_users.username}">{$wifidb_users.username}</a></td>
                                    <td align="center">{$wifidb_users.imports}</td>
                                </tr>
                                <tbody id="Row{$wifidb_users.rowid}" style="display:none">
                                <tr class="sub_head">
                                    <th width="100px">Title</th>
                                    <th>Number of APs</th>
                                    <th>AP Efficiency</th>
									<th>Import Notes</th>
									<th>Imported On</th>
                                </tr>
                                    {foreach name=outer item=wifidb_import from=$wifidb_users.data}
                                <tr class="{$wifidb_import.class}">
                                    <td align="center"><a class="links" href="?func=useraplist&row={$wifidb_import.id}">{$wifidb_import.title}</a></td>
                                    <td align="center">{$wifidb_import.aps}</td>
									<td align="center">{$wifidb_import.NewAPPercent}</td>
                                    <td align="center">{$wifidb_import.notes}</td>
                                    <td align="center">{$wifidb_import.date}</td>
                                </tr>
                                    {/foreach}
                                </tbody>
                                {foreachelse}
                                    There are no Imports, go find some of them wifis, I hear they have yummy packets.
                                {/foreach}
                            </tbody>
                        </table>
                        <br/>
{include file="footer.tpl"}