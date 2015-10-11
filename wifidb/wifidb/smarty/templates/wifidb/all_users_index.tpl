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
			<table border="1" align="center">
                            <tbody>
                                <tr class="style4">
                                    <th>ID</th>
                                    <th>UserName</th>
                                    <th>Title</th>
                                    <th>Import Notes</th>
                                    <th>Number of APs</th>
                                    <th>Imported On</th>
                                </tr>
                                <tr>
                                {foreach name=outer item=wifidb_ap from=$wifidb_aps_all}
                                    <td>{$wifidb_users.id}</td>
                                    <td>{$wifidb_users.username}</td>
                                    <td>{$wifidb_users.title}</td>
                                    <td>{$wifidb_users.notes}</td>
                                    <td>{$wifidb_users.aps}</td>
                                    <td>{$wifidb_users.date}</td>
                                {foreachelse}
                                    There are no Imports, go find some of them wifis, I hear they have yummy packets.
                                {/foreach}
                                </tr>
                            </tbody>
                        </table>
                        <br/>
{include file="footer.tpl"}