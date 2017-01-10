<!--
fetch.tpl: template for a single AP's data results.
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
                                <h1>{$wifidb_ap.ssid}{$wifidb_ap_globe_html}</h1>
                                <table align="center" width="569" border="1" cellpadding="4" cellspacing="0"></table>
                                <table align="center" width="569" border="1" cellpadding="4" cellspacing="0">
                                    <tbody>
                                        <tr><td class="style4" width="112"><p>MAC Address</p></td><td class="light" width="439"><p>{$wifidb_ap.mac}</p></td></tr>
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
                                        <tr valign="TOP"><td class="style4" width="112"><p>Export:</p></td><td class="light" width="439"><a class="links" href="{$wifidb_host_url}api/export.php?func=exp_ap&amp;id={$wifidb_ap.id}&amp;from=0&amp;limit={$wifidb_ap.limit}">KMZ</a> | <a class="links" href="{$wifidb_host_url}graph/?id={$wifidb_ap.id}">Graph Signal</a></td>
                                        </tr>
                                    </tbody>
                                </table>
                                <br/>
								
								<table width="85%" border="1" cellpadding="2" cellspacing="0">
									<tr>
										<td class="style1">Geographical data ( datasource: Geonames.org )</td>
									</tr>
									<tr>
										<td class="style1">
												<table align="center" width="100%" border="1" cellpadding="4" cellspacing="0">
													<tbody>
														<tr>
															<th class="style4">ID</th>
															<th class="style4">Closest Landmark</th>
															<th class="style4">Admin1 Name</th>
															<th class="style4">Admin2 Name</th>
															<th class="style4">Country Name</th>
															<th class="style4">Timezone</th>
														</tr>
													</tbody>
													<tbody>
														<tr>
															<td class="light" align="center">{$wifidb_geonames.id}</td>
															<td class="light" align="center">{$wifidb_geonames.asciiname}</td>	
															<td class="light" align="center">{$wifidb_admin1.name}</td>
															<td class="light" align="center">{$wifidb_admin2.name}</td>
															<td class="light" align="center">{$wifidb_geonames.country_code}</td>
															<td class="light" align="center">{$wifidb_geonames.timezone}</td>
														</tr>
													</tbody>
												</table>
										</td>
									</tr>
								</table>
                                <br/>													

								<table width="85%" border="1" cellpadding="2" cellspacing="0">
									<tr>
										<td class="style1">Associated Lists</td>
									</tr>
									<tr>
										<td class="style1">
												{foreach name=outer item=wifidb_assoc from=$wifidb_assoc_lists}
												<table align="center" width="100%" border="1" cellpadding="4" cellspacing="0">
													<tbody>
														<tr>
															<th class="style4">ID</th>
															<th class="style4">Title</th>
															<th class="style4">User</th>			
															<th class="style4">Total APs</th>		
															<th class="style4">Date</th>
															<th class="style4">New/Update</th>
															<th class="style4">Export</th>
															<th class="style4">Signal History</th>
														</tr>
													</tbody>
													<tbody>
														<tr>
															<td class="light" align="center"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.id}">{$wifidb_assoc.id}</a></td>		
															<td class="light"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.title_id}">{$wifidb_assoc.title}</a></td>
															<td class="light"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_assoc.username}">{$wifidb_assoc.username}</a></td>
															<td class="light" align="center">{$wifidb_assoc.aps}</td>
															<td class="light">{$wifidb_assoc.date}</td>
															<td class="light">{$wifidb_assoc.nu}</td>
															<td class="light"><a class="links" href="{$wifidb_host_url}api/export.php?func=exp_list_ap_signal&amp;row={$wifidb_assoc.id}&amp;id={$wifidb_ap.id}">KMZ</a> | <a class="links" href="{$wifidb_host_url}graph/?func=graph_list_ap&amp;row={$wifidb_assoc.id}&amp;id={$wifidb_ap.id}">Graph Signal</a></td>			
															<td class="style4" onclick="expandcontract('Row{$wifidb_assoc.id}','ClickIcon{$wifidb_assoc.id}')" id="ClickIcon{$wifidb_assoc.id}" style="cursor: pointer; cursor: hand;">+</td>
														</tr>
													</tbody>
													<tbody id="Row{$wifidb_assoc.id}" style="display:none">
														<tr class="style4">
															<th>Signal</th>
															<th>RSSI</th>
															<th>Lat</th>
															<th>Long</th>
															<th>Alt</th>
															<th>Sats</th>
															<th>Date</th>
															<th>Time</th>
												
														</tr>
														{foreach item=wifidb_ap_gps from=$wifidb_assoc.signals}
														<tr class="{$wifidb_ap_gps.class}">
															<td class="light" align="center">{$wifidb_ap_gps.signal}</td>
															<td class="light">{$wifidb_ap_gps.rssi}</td>
															<td class="light">{$wifidb_ap_gps.lat}</td>
															<td class="light">{$wifidb_ap_gps.long}</td>
															<td class="light">{$wifidb_ap_gps.alt}</td>
															<td class="light">{$wifidb_ap_gps.sats}</td>
															<td class="light">{$wifidb_ap_gps.date}</td>
															<td class="light">{$wifidb_ap_gps.time}</td>
												
														</tr>
														{/foreach}
													</tbody>
												</table>
												<br/>
												{foreachelse}
												<tr class="style4">
													<td colspan="5"> There are no GPS Points for this AP :/</td>
												</tr>    
												{/foreach}
										</td>
									</tr>
								</table>
                                <br/>
{include file="footer.tpl"}