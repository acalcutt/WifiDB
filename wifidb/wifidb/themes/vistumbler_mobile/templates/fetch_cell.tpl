<!--
fetch.tpl: template for a single AP's data results.
Copyright (C) 2022 Andrew Calcutt

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
				{include file="topmenu.tpl"}
				<div class="center">
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
					<h1>{$wifidb_cid.ssid|escape:'htmlall'}
						{if $wifidb_cid.validgps eq 1}
							<a href="{$wifidb_host_url}opt/map.php?func=exp_cell&id={$wifidb_cid.id|escape:'htmlall'}" title="Show AP on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
							<a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_cid.id|escape:'htmlall'}" title="Show AP Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
							<a href="{$wifidb_host_url}api/geojson.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_cid.id|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
							<a href="{$wifidb_host_url}api/export.php?func=exp_cid&from=0&inc=25000&id={$wifidb_cid.id}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
							<a href="{$wifidb_host_url}api/gpx.php?func=exp_cell_sig&from=0&inc=25000&id={$wifidb_cid.id|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
						{else}
							<img width="20px" src="{$themeurl}img/globe_off.png">
							<img width="20px" src="{$themeurl}img/sigmap_off.png">
							<img width="20px" src="{$themeurl}img/json_off.png">
							<img width="20px" src="{$themeurl}img/kmz_off.png">
							<img width="20px" src="{$themeurl}img/gpx_off.png">
						{/if}
					</h1>
					<table align="center" width="50%" border="1" cellpadding="4" cellspacing="0">
						<tbody>
							<tr><td class="header" width="112">MAC Address</td><td class="light" width="439">{$wifidb_cid.mac|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112" height="26">Authmode</td><td class="light" width="439">{$wifidb_cid.auth|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">Type</td><td class="light" width="439">{$wifidb_cid.type|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">Channel #</td><td class="light" width="439">{$wifidb_cid.chan|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">Latitude</td><td class="light" width="439">{$wifidb_cid.lat_dm|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">Longitude</td><td class="light" width="439">{$wifidb_cid.lon_dm|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">First Active</td><td class="light" width="439">{$wifidb_cid.fa|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">Last Active</td><td class="light" width="439">{$wifidb_cid.la|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">High RSSI</td><td class="light" width="439">{$wifidb_cid.high_rssi|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">High RSSI w/GPS</td><td class="light" width="439">{$wifidb_cid.high_gps_rssi|escape:'htmlall'}</td></tr>
							<tr><td class="header" width="112">Points</td><td class="light" width="439">{$wifidb_cid.points|number_format:0}</td></tr>
							<tr><td class="header" width="112">User</td><td class="light" width="439"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_cid.user|escape:'htmlall'}">{$wifidb_cid.user|escape:'htmlall'}</a></td></tr>
							<tr>
								<td class="header" width="112">Export:</td><td class="light" width="439">
									{if $wifidb_cid.validgps eq 1}
										<a href="{$wifidb_host_url}opt/map.php?func=exp_cell&id={$wifidb_cid.id|escape:'htmlall'}" title="Show AP on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
										<a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_cid.id|escape:'htmlall'}" title="Show AP Signals on Map"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
										<a href="{$wifidb_host_url}api/geojson.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_cid.id|escape:'htmlall'}" title="Export to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
										<a href="{$wifidb_host_url}api/export.php?func=exp_cid&from=0&inc=25000&id={$wifidb_cid.id|escape:'htmlall'}" title="Export to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
										<a href="{$wifidb_host_url}api/gpx.php?func=exp_cell_sig&from=0&inc=25000&id={$wifidb_cid.id|escape:'htmlall'}" title="Export to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
									{else}
										<img width="20px" src="{$themeurl}img/globe_off.png">
										<img width="20px" src="{$themeurl}img/sigmap_off.png">
										<img width="20px" src="{$themeurl}img/json_off.png">
										<img width="20px" src="{$themeurl}img/kmz_off.png">
										<img width="20px" src="{$themeurl}img/gpx_off.png">
									{/if}
								</td>
							</tr>
						</tbody>
					</table>
					<br/>
					<table class="content_table">
						<tbody>
							<tr>
								<td class="subheading" colspan="10">Associated Lists</td>
							</tr>
							<tr class="sub_head">
								<td colspan="12" align="center">
								 {$pages_together}
								</td>
							</tr>
						</tbody>
						{foreach name=outer item=wifidb_assoc from=$wifidb_assoc_lists}
						<tbody>
							<tr>
								<th class="header">ID</th>
								<th class="header">List GPS</th>
								<th class="header">User</th>
								<th class="header">File</th>
								<th class="header">Title</th>
								<th class="header">Notes</th>
								<th class="header">Date</th>
								<th class="header">New/Update</th>
								<th class="header">AP GPS</th>
								<th class="header">{$wifidb_assoc.points|escape:'htmlall'} Points</th>
							</tr>
						</tbody>
						<tbody>
							<tr>
								<td class="light" align="center"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.id|escape:'htmlall'}">{$wifidb_assoc.id|escape:'htmlall'}</a></td>	
								<td class="light" align="center">
								{if $wifidb_assoc.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=user_list&from=0&inc=50000&id={$wifidb_assoc.id|escape:'htmlall'}" title="Show List on Map"><img width="20px" src="{$themeurl}img/globe_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_list&from=0&inc=50000&id={$wifidb_assoc.id|escape:'htmlall'}" title="Export List to JSON"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_list&from=0&inc=25000&id={$wifidb_assoc.id|escape:'htmlall'}" title="Export List to KMZ"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}api/gpx.php?func=exp_list&from=0&inc=25000&id={$wifidb_assoc.id|escape:'htmlall'}" title="Export List to GPX"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/globe_off.png">
									<img width="20px" src="{$themeurl}img/sigmap_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
									<img width="20px" src="{$themeurl}img/gpx_off.png">
								{/if}
								</td>
								<td class="light"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=alluserlists&amp;user={$wifidb_assoc.user|escape:'htmlall'}">{$wifidb_assoc.file_user|escape:'htmlall'}</a></td>
								<td class="light"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.id|escape:'htmlall'}">{$wifidb_assoc.file|escape:'htmlall'}</a></td>
								<td class="light"><a class="links" href="{$wifidb_host_url}opt/userstats.php?func=useraplist&amp;row={$wifidb_assoc.id|escape:'htmlall'}">{$wifidb_assoc.title|escape:'htmlall'}</a></td>
								<td class="light">{$wifidb_assoc.notes|escape:'htmlall'}</td>
								<td class="light">{$wifidb_assoc.date|escape:'htmlall'}</td>
								<td class="light">{$wifidb_assoc.nu|escape:'htmlall'}</td>
								<td class="light">
								{if $wifidb_assoc.validgps eq 1}
									<a href="{$wifidb_host_url}opt/map.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_cid.id|escape:'htmlall'}&file_id={$wifidb_assoc.id|escape:'htmlall'}" title="Show AP Signals on Map (for this file)"><img width="20px" src="{$themeurl}img/sigmap_on.png"></a>
									<a href="{$wifidb_host_url}api/geojson.php?func=exp_cell_sig&from=0&inc=50000&id={$wifidb_cid.id|escape:'htmlall'}&amp;file_id={$wifidb_assoc.id|escape:'htmlall'}" title="Export AP Signals to JSON (for this file)"><img width="20px" src="{$themeurl}img/json_on.png"></a>
									<a href="{$wifidb_host_url}api/export.php?func=exp_cid&from=0&inc=25000&id={$wifidb_cid.id|escape:'htmlall'}&file_id={$wifidb_assoc.id|escape:'htmlall'}" title="Export AP Signals to KMZ (for this file)"><img width="20px" src="{$themeurl}img/kmz_on.png"></a>
									<a href="{$wifidb_host_url}api/gpx.php?func=exp_cell_sig&from=0&inc=25000&id={$wifidb_cid.id|escape:'htmlall'}&amp;file_id={$wifidb_assoc.id|escape:'htmlall'}" title="Export AP Signals to GPX (for this file)"><img width="20px" src="{$themeurl}img/gpx_on.png"></a>
								{else}
									<img width="20px" src="{$themeurl}img/sigmap_off.png">
									<img width="20px" src="{$themeurl}img/json_off.png">
									<img width="20px" src="{$themeurl}img/kmz_off.png">
									<img width="20px" src="{$themeurl}img/gpx_off.png">
								{/if}
								</td>
								<td class="header" onclick="expandcontract('Row{$wifidb_assoc.id|escape:'htmlall'}','ClickIcon{$wifidb_assoc.id|escape:'htmlall'}')" id="ClickIcon{$wifidb_assoc.id|escape:'htmlall'}" style="cursor: pointer; cursor: hand;">+</td>
							</tr>
						</tbody>
						<tbody id="Row{$wifidb_assoc.id}" style="display:none">
							<tr class="header">
								<th class="header">Signal</th>
								<th class="header">RSSI</th>
								<th class="header">Lat</th>
								<th class="header">Long</th>
								<th class="header">Alt</th>
								<th class="header">Sats</th>
								<th class="header">ACC</th>
								<th class="header" colspan="2">Date</th>
							</tr>
							{foreach item=wifidb_cid_gps from=$wifidb_assoc.signals}
							<tr class="{cycle values="light,dark"}">
								<td>{$wifidb_cid_gps.Sig|escape:'htmlall'}</td>
								<td>{$wifidb_cid_gps.rssi|escape:'htmlall'}</td>
								<td>{$wifidb_cid_gps.Lat|escape:'htmlall'}</td>
								<td>{$wifidb_cid_gps.Lon|escape:'htmlall'}</td>
								<td>{$wifidb_cid_gps.Alt|escape:'htmlall'}</td>
								<td>{$wifidb_cid_gps.NumOfSats|escape:'htmlall'}</td>
								<td>{$wifidb_cid_gps.AccuracyMeters|escape:'htmlall'}</td>
								<td colspan="2">{$wifidb_cid_gps.hist_date}</td>
							</tr>
							{/foreach}
						</tbody>
						{if not $smarty.foreach.outer.last}
						<tbody>
						<tr">
							<th colspan="9"><br/></th>
						</tr>
						</tbody>
						{/if}
						{foreachelse}
						<tr class="light-centered">
							<td colspan="9"> There are no files associated this AP :/</td>
						</tr>    
						{/foreach}
						<tr class="sub_head">
							<td colspan="12" align="center">
							 {$pages_together}
							</td>
						</tr>
					</table>
					<br/>
					<table class="content_table">
						<tr>
							<td class="subheading">Geographical data ( datasource: Geonames.org )</td>
						</tr>
						<tr>
							<td>
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
										<tr class="{cycle values="light,dark"}">
											<td>{$wifidb_gi.id|escape:'htmlall'}</td>
											<td>{$wifidb_gi.asciiname|escape:'htmlall'}</td>	
											<td>{$wifidb_gi.admin1name|escape:'htmlall'}</td>
											<td>{$wifidb_gi.admin2name|escape:'htmlall'}</td>
											<td>{$wifidb_gi.country_code|escape:'htmlall'}</td>
											<td>{$wifidb_gi.timezone|escape:'htmlall'}</td>
											<td>{$wifidb_gi.lat_dm|escape:'htmlall'}</td>
											<td>{$wifidb_gi.lon_dm|escape:'htmlall'}</td>
											<td>{$wifidb_gi.miles|string_format:"%.2f"|escape:'htmlall'}mi</td>
											<td>{$wifidb_gi.kilometers|string_format:"%.2f"|escape:'htmlall'}km</td>
										</tr>
										{/foreach}
									</tbody>
								</table>
							</td>
						</tr>
					</table>
				</div>
			</div>
{include file="footer.tpl"}