<!--
fetch.tpl: template for a single AP's data results.
Copyright (C) 2018 Andrew Calcutt

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
			<div class="main">
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
						<tr><td class="header" width="112"><p>MAC Address</p></td><td class="light" width="439"><p>{$wifidb_ap.mac}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Manufacture</p></td><td class="light" width="439"><p>{$wifidb_ap.manuf}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112" height="26"><p>Authentication</p></td><td class="light" width="439"><p>{$wifidb_ap.auth}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Encryption Type</p></td><td class="light" width="439"><p>{$wifidb_ap.encry}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Radio Type</p></td><td class="light" width="439"><p>{$wifidb_ap.radio}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Channel #</p></td><td class="light" width="439"><p>{$wifidb_ap.chan}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>BTx</p></td><td class="light" width="439"><p>{$wifidb_ap.btx}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>OTx</p></td><td class="light" width="439"><p>{$wifidb_ap.otx}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Network Type</p></td><td class="light" width="439"><p>{$wifidb_ap.nt}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>First Active</p></td><td class="light" width="439"><p>{$wifidb_ap.fa}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Last Active</p></td><td class="light" width="439"><p>{$wifidb_ap.la}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Latitude</p></td><td class="light" width="439"><p>{$wifidb_ap.lat}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Longitude</p></td><td class="light" width="439"><p>{$wifidb_ap.lon}</p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>User</p></td><td class="light" width="439"><p><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_ap.user}">{$wifidb_ap.user}</a></p></td></tr>
						<tr valign="TOP"><td class="header" width="112"><p>Export:</p></td><td class="light" width="439"><a class="links" href="{$wifidb_host_url}opt/map.php?func=exp_ap&amp;id={$wifidb_ap.id}&labeled=1">Map</a> | <a class="links" href="{$wifidb_host_url}api/geojson.php?func=exp_ap&amp;id={$wifidb_ap.id}">GeoJSON</a> | <a class="links" href="{$wifidb_host_url}api/export.php?func=exp_ap&amp;id={$wifidb_ap.id}&amp;from=0&amp;limit={$wifidb_ap.limit}">KMZ</a> | <a class="links" href="{$wifidb_host_url}graph/?id={$wifidb_ap.id}">Graph Signal</a></td>
						</tr>
					</tbody>
				</table>
				<br/>
				
				<table class="content_table">
					<tr>
						<td class="style1">Geographical data ( datasource: Geonames.org )</td>
					</tr>
					<tr>
						<td class="style1">
								
								<table align="center" width="100%" border="1" cellpadding="4" cellspacing="0">
									<tbody>
										<tr>
											<th class="header">ID</th>
											<th class="header">Closest Landmark</th>
											<th class="header">Admin1 Name</th>
											<th class="header">Admin2 Name</th>
											<th class="header">Country Name</th>
											<th class="header">Timezone</th>
											<th class="header">Latitude</th>
											<th class="header">Longitude</th>
											<th class="header">Distance(mi)</th>
											<th class="header">Distance(km)</th>
										</tr>
									</tbody>
									<tbody>
										{foreach name=outer item=wifidb_gi from=$wifidb_geonames}
										<tr class="{$wifidb_gi.class}">
											<td>{$wifidb_gi.id}</td>
											<td>{$wifidb_gi.asciiname}</td>	
											<td>{$wifidb_gi.admin1name}</td>
											<td>{$wifidb_gi.admin2name}</td>
											<td>{$wifidb_gi.country_code}</td>
											<td>{$wifidb_gi.timezone}</td>
											<td>{$wifidb_gi.latitude}</td>
											<td>{$wifidb_gi.longitude}</td>
											<td>{$wifidb_gi.miles|string_format:"%.2f"}mi</td>
											<td>{$wifidb_gi.kilometers|string_format:"%.2f"}km</td>
										</tr>
										{/foreach}
									</tbody>
								</table>
						</td>
					</tr>
				</table>
				<br/>

				<table class="content_table">
					<tr>
						<td class="style1">Associated Lists</td>
					</tr>
					<tr>
						<td class="style1">
								{foreach name=outer item=wifidb_assoc from=$wifidb_assoc_lists}
								<table align="center" width="100%" border="1" cellpadding="4" cellspacing="0">
									<tbody>
										<tr>
											<th class="header">ID</th>
											<th class="header">GPS</th>
											<th class="header">Title</th>
											<th class="header">User</th>
											<th class="header">Total APs</th>
											<th class="header">Date</th>
											<th class="header">New/Update</th>
											<th class="header">Export</th>
											<th class="header">Signal History</th>
										</tr>
									</tbody>
									<tbody>
										<tr>
											<td class="light" align="center"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.id}">{$wifidb_assoc.id}</a></td>	
											<td class="light" align="center">{$wifidb_assoc.globe}</td>
											<td class="light"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.id}">{$wifidb_assoc.title}</a></td>
											<td class="light"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_assoc.user}">{$wifidb_assoc.user}</a></td>
											<td class="light" align="center">{$wifidb_assoc.aps}</td>
											<td class="light">{$wifidb_assoc.date}</td>
											<td class="light">{$wifidb_assoc.nu}</td>
											<td class="light"><a class="links" href="{$wifidb_host_url}api/export.php?func=exp_list_ap_signal&amp;file_id={$wifidb_assoc.id}&amp;id={$wifidb_ap.id}">KMZ</a> | <a class="links" href="{$wifidb_host_url}graph/?func=graph_list_ap&amp;row={$wifidb_assoc.id}&amp;id={$wifidb_ap.id}">Graph Signal</a></td>
											<td class="header" onclick="expandcontract('Row{$wifidb_assoc.id}','ClickIcon{$wifidb_assoc.id}')" id="ClickIcon{$wifidb_assoc.id}" style="cursor: pointer; cursor: hand;">+</td>
										</tr>
									</tbody>
									<tbody id="Row{$wifidb_assoc.id}" style="display:none">
										<tr class="header">
											<th>Signal</th>
											<th>RSSI</th>
											<th>Lat</th>
											<th>Long</th>
											<th>Alt</th>
											<th>Sats</th>
											<th>Date</th>
								
										</tr>
										{foreach item=wifidb_ap_gps from=$wifidb_assoc.signals}
										<tr class="{$wifidb_ap_gps.class}">
											<td>{$wifidb_ap_gps.Sig}</td>
											<td>{$wifidb_ap_gps.RSSI}</td>
											<td>{$wifidb_ap_gps.Lat}</td>
											<td>{$wifidb_ap_gps.Lon}</td>
											<td>{$wifidb_ap_gps.Alt}</td>
											<td>{$wifidb_ap_gps.NumOfSats}</td>
											<td>{$wifidb_ap_gps.GPS_Date}</td>
								
										</tr>
										{/foreach}
									</tbody>
								</table>
								<br/>
								{foreachelse}
								<tr class="header">
									<td colspan="5"> There are no GPS Points for this AP :/</td>
								</tr>    
								{/foreach}
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}