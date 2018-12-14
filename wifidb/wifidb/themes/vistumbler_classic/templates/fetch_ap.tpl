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
                                <h1>{$wifidb_ap.ssid}<img width="20px" src="{$wifidb_host_url}img/globe_{$wifidb_ap_globe}.png"></h1>
                                <table align="center" width="569" border="1" cellpadding="4" cellspacing="0"></table>
                                <table align="center" width="569" border="1" cellpadding="4" cellspacing="0">
                                    <colgroup>
                                        <col width="112"/>
                                        <col width="439"/>
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            <td class="style4" width="112"><p>MAC Address</p></td><td class="light" width="439"><p>{$wifidb_ap_mac}</p></td>
                                        </tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Manufacture</p></td><td class="light" width="439"><p>{$wifidb_ap.manuf}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112" height="26"><p>Authentication</p></td><td class="light" width="439"><p>{$wifidb_ap.auth}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Encryption Type</p></td><td class="light" width="439"><p>{$wifidb_ap.encry}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Radio Type</p></td><td class="light" width="439"><p>{$wifidb_ap.radio}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Channel #</p></td><td class="light" width="439"><p>{$wifidb_ap.chan}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>BTx</p></td><td class="light" width="439"><p>{$wifidb_ap.btx}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>OTx</p></td><td class="light" width="439"><p>{$wifidb_ap.otx}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Network Type</p></td><td class="light" width="439"><p>{$wifidb_ap.nt}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>First Active</p></td><td class="light" width="439"><p>{$wifidb_ap.fa}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Last Active</p></td><td class="light" width="439"><p>{$wifidb_ap.la}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Label</p></td><td class="light" width="439"><p>{$wifidb_ap.label}</p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>User</p></td><td class="light" width="439"><p><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_ap.user}">{$wifidb_ap.user}</a></p></td></tr>
                                        <tr valign="TOP"><td class="style4" width="112"><p>Export:</p></td><td class="light" width="439"><a class="links" href="{$wifidb_host_url}graph/?row=1&amp;id={$wifidb_ap.id}">Graph Signal</a> [||] <a class="links" href="{$wifidb_host_url}opt/export.php?func=exp_all_signal,{$wifidb_ap.id}">KMZ</a></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br/>
                                <table align="center" width="85%" border="1" cellpadding="4" cellspacing="0" id="gps">
                                    <tbody>
                                        <tr>
                                            <td colspan="10" align="center">
                                                {foreach item=wifidb_ap_signal from=$wifidb_ap_signal_all}
                                                <table align="center" width="569" border="1" cellpadding="4" cellspacing="0">
                                                    <tbody>
                                                        <tr>
                                                            <td class="style4" onclick="expandcontract('Row{$wifidb_ap_signal.id}','ClickIcon{$wifidb_ap_signal.id}')" id="ClickIcon{$wifidb_ap_signal.id}" style="cursor: pointer; cursor: hand;">+</td>
                                                            <th colspan="6" class="style4">Signal History ({$wifidb_ap_signal.desc})</th>
                                                        </tr>
                                                    </tbody>
                                                    <tbody id="Row{$wifidb_ap_signal.id}" style="display:none">
                                                        <tr class="style4">
                                                            <th>Signal</th>
                                                            <th>Lat</th>
                                                            <th>Long</th>
                                                            <th>Sats</th>
                                                            <th>Date</th>
                                                            <th>Time</th>
                                                        </tr>
                                                        {foreach item=wifidb_ap_gps from=$wifidb_ap_signal.gps}
                                                        <tr class="{$wifidb_ap_gps.class}">
                                                            <td align="center">{$wifidb_ap_gps.signal}</td>
                                                            <td>{$wifidb_ap_gps.lat}</td>
                                                            <td>{$wifidb_ap_gps.long}</td>
                                                            <td align="center">{$wifidb_ap_gps.sats}</td>
                                                            <td>{$wifidb_ap_gps.date}</td>
                                                            <td>{$wifidb_ap_gps.time}</td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                                {foreachelse}
                                                <tr class="style4">
                                                    <td colspan="5"> There are no GPS Points for this AP :/</td>
                                                </tr>    
                                                {/foreach}
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br/>
                                <table align="center" width="569" border="1" cellpadding="4" cellspacing="0">
                                    <tbody>
                                        <tr class="style4">
                                            <th colspan="6">Associated Lists</th>
                                        </tr>
                                        <tr class="style4">
                                            <th>New/Update</th>
                                            <th>ID</th>
                                            <th>User</th>
                                            <th>Title</th>
                                            <th>Total APs</th>
                                            <th>Date</th>
                                        </tr>
                                        {foreach name=outer item=wifidb_assoc from=$wifidb_assoc_lists}
                                        <tr class="{$wifidb_assoc.class}">
                                            <td>{$wifidb_assoc.nu}</td>
                                            <td align="center">
                                                <a class="links" 
                                                   href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.id}">
                                                    {$wifidb_assoc.id}</a>
                                            </td>
                                            <td>
                                                <a class="links" 
                                                   href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_assoc.username}">
                                                    {$wifidb_assoc.username}
                                                </a>
                                            </td>
                                            <td>
                                                <a class="links" 
                                                   href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.title_id}">
                                                    {$wifidb_assoc.title}
                                                </a>
                                            </td>
                                            <td align="center">{$wifidb_assoc.aps}</td>
                                            <td>{$wifidb_assoc.date}</td>
                                        </tr>
                                        {foreachelse}
                                        <tr>
                                            <td colspan="6">There is no associated lists for this AP.</td>
                                        </tr>
                                        {/foreach}
                                    </tbody>
                                </table>
                                <br/>
{include file="footer.tpl"}