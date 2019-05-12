<!--
index.tpl: The Smarty Index template for WiFiDB.
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
				<button type="button" id="sidebarCollapse" class="navbar-btn">
					<span></span>
					<span></span>
					<span></span>
				</button>
				<div class="center">
					<br/>
					<a class="links" title="Import File" style="text-decoration: none;" href="{$wifidb_host_url}import/">
						<img src="{$themeurl}img/upload-cloud.png" style="vertical-align: middle;"/>
						<span style="vertical-align: middle; text-decoration: none;">Import File</span>
					</a>
					<a class="links" title="Map" style="text-decoration: none;" href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=0">
						<img src="{$themeurl}img/map.png" style="vertical-align: middle;"/>
						<span style="vertical-align: middle;">Map</span>
					</a>
					<a class="links" title="Imported Files" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=done">
						<img src="{$themeurl}img/list.png" style="vertical-align: middle;"/>
						<span style="vertical-align: middle;">Imported Files</span>
					</a>
					<a class="links" title="Stats" style="text-decoration: none;" href="{$wifidb_host_url}stats.php">
						<img src="{$themeurl}img/stats.png" style="vertical-align: middle;"/>
						<span style="vertical-align: middle;">Stats</span>
					</a>
					<a class="links" title="Export KMZ" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=daemon_kml">
						<img src="{$themeurl}img/download-cloud.png" style="vertical-align: middle;"/>
						<span style="vertical-align: middle;">Export KMZ</span>
					</a>
					<a class="links" title="Users" style="text-decoration: none;" href="{$wifidb_host_url}opt/userstats.php?func=allusers">
						<img src="{$themeurl}img/users.png" style="vertical-align: middle;"/>
						<span style="vertical-align: middle;">Users</span>
					</a>					
					<a class="links" title="Log In/Out" style="text-decoration: none;" href="{$wifidb_host_url}login.php{$wifidb_current_uri}">
						<img src="{$themeurl}img/user.png" style="vertical-align: middle;"/>
						<span style="vertical-align: middle;">{$wifidb_login_label|default:'Login'}</span>
					</a>
					<br/><br/>
					<table border="0" cellpadding="4" width="100%" border="2" id="details">
						<tr class="dark">
							<td>Project Description</td><td><a class="links" title="Vistumbler WifiDB" href="{$wifidb_host_url}">Vistumbler WifiDB</a> is a project to collect wireless accesss points gathered by <a class="links" title="Vistumbler" href="https://www.vistumbler.net">Vistumbler</a> or other wireless network scanners. It generates statistics and maps from user uploaded wireless scans. It allows users to keep track of their uploads.</td>
						</tr>
						<tr class="light">
							<td>Project Github</td><td><b><a class="links" title="Vistumbler WifiDB Github" href="https://github.com/acalcutt/WiFiDB">https://github.com/acalcutt/WiFiDB</a></b></td>
						</tr>
						<tr class="dark">
							<td>Project Author</td><td><a class="links" title="ACalcutt Github" href="https://github.com/acalcutt"><b>Andrew Calcutt</b></a>, based on <a class="links" title="PFerland Github" href="https://github.com/riei/wifidb/">Random Intervals Wireless Database</a> by <a class="links" title="PFerland Github" href="https://github.com/pferland"><b>Phil Ferland</b></a></td>
						</tr>
						<tr class="light">
							<td>Project Change Log</td><td><b><a class="links" title="Commits" href="https://github.com/acalcutt/WifiDB/commits/master">Commits</a>, <a class="links" title="Commits" href="ver.php">Version History</a></b></td>
						</tr>
						<tr class="dark">
							<td>Project Support</td><td><b><a class="links" title="Forum" href="https://forum.techidiots.net/forum/viewforum.php?f=44">WiFiDB Forum</a></b></td>
						</tr>
					</table>

				</div>
			</div>
{include file="footer.tpl"}