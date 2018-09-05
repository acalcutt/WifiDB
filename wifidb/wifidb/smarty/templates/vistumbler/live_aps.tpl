<!--
Database.inc.php, holds the database interactive functions.
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
                <table>
                    <tbody>
                        <tr>
                            <td>
								<div align="center">
									<h2>Showing the last {$intervalt} of Live APs.</h2>
									<table border="1" width="100%" cellspacing="0">
										<tr class="style4">
											<td>
												Select Window of time to view:
											</td>
											<td>
												<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=1800">30 Minutes</a>
											</td>
											<td>
												<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=3600">60 Minutes</a>
											</td>
											<td>
												<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=7200">2 Hours</a>
											</td>
											<td>
												<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=21600">6 Hours</a>
											</td>
											<td>
												<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=86400">1 Day</a>
											</td>
											<td>
												<a href="?sort={$sort}&ord={$ord}&from={$from}&to={$to}&view=604800">1 Week</a>
											</td>
										</tr>
									</table>
									<table border="1" align="center">
										<tbody>
											<tr class="style4">
												<th>GPS</th>
												<th>SSID
													<a href="?sort=ssid&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=ssid&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Mac Address
													<a href="?sort=mac&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=mac&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Authentication
													<a href="?sort=auth&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=auth&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Encryption
													<a href="?sort=encry&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=encry&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Radio
													<a href="?sort=radio&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=radio&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Channel
													<a href="?sort=chan&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=chan&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>First Active
													<a href="?sort=fa&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=fa&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Last Active
													<a href="?sort=la&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=la&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Username
													<a href="?sort=username&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=username&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Label
													<a href="?sort=label&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=label&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>										
												<th>Latitude
													<a href="?sort=lat&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=lat&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
												<th>Longitude
													<a href="?sort=long&ord=ASC&from={$from}&to={$to}"><img height="15" width="15" border="0"border="0" src="../themes/{$wifidb_theme}/img/down.png"></a>
													<a href="?sort=long&ord=DESC&from={$from}&to={$to}"><img height="15" width="15" border="0"src="../themes/{$wifidb_theme}/img/up.png"></a>
												</th>
											</tr>
											{foreach name=outer item=wifidb_live_aps from=$wifidb_all_live_aps}
											<tr class="{$wifidb_live_aps.class}">
												<td align="center">{$wifidb_live_aps.globe_html}</td>
												<td align="center">{$wifidb_live_aps.ssid}</td>
												<td align="center">{$wifidb_live_aps.mac}</td>
												<td align="center">{$wifidb_live_aps.auth}</td>
												<td align="center">{$wifidb_live_aps.encry}</td>
												<td align="center">{$wifidb_live_aps.radio}</td>
												<td align="center">{$wifidb_live_aps.chan}</td>
												<td align="center">{$wifidb_live_aps.fa}</td>
												<td align="center">{$wifidb_live_aps.la}</td>
												<td align="center">{$wifidb_live_aps.username}</td>
												<td align="center">{$wifidb_live_aps.label}</td>											
												<td align="center">{$wifidb_live_aps.lat}</td>
												<td align="center">{$wifidb_live_aps.long}</td>											
											</tr>
											{/foreach}
											<tr class="sub_head">
												<td colspan="9" align="center">
												 {$pages_together}
												</td>
											</tr>
										</tbody>
									</table>
								</div>
                            </td>
                        </tr>
                    </tbody>
                </table>
                        <br/>
{include file="footer.tpl"}