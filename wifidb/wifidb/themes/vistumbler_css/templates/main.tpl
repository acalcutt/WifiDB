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
				<div class="center">
					<h2>Vistumbler WiFiDB</h2>
					<table border="0" cellpadding="4" width="100%" border="2" id="details">
						<tr class="dark">
							<td>Project Description</td><td><a class="links" title="Vistumbler WifiDB" href="{$wifidb_host_url}">Vistumbler WifiDB</a> is a project to gather wireless accesss points gathered by <a class="links" title="Vistumbler" href="https://www.vistumbler.net">Vistumbler</a> or other wireless network scanners. It generates statistics and maps from user uploaded wireless scans. It allows users to keep track of their uploads.</td>
						</tr>
						<tr class="light">
							<td>Project Github</td><td><b><a class="links" title="Vistumbler WifiDB Github" href="https://github.com/acalcutt/WiFiDB">https://github.com/acalcutt/WiFiDB</a></b></td>
						</tr>
						<tr class="dark">
							<td>Project Author</td><td><a class="links" title="ACalcutt Github" href="https://github.com/acalcutt"><b>Andrew Calcutt</b></a>, based on work by <a class="links" title="PFerland Github" href="https://github.com/pferland"><b>Phil Ferland</b></a></td>
						</tr>
						<tr class="light">
							<td>Project Change Log</td><td><b><a class="links" title="Commits" href="https://github.com/acalcutt/WifiDB/commits/master">Commits</a>, <a class="links" title="Commits" href="ver.php">Version History</a></b></td>
						</tr>
						<tr class="dark">
							<td>Project Support</td><td><b><a class="links" title="Forum" href="https://forum.techidiots.net/forum/viewforum.php?f=44">WiFiDB Forum</a></b></td>
						</tr>
					</table>
					<br/>
					<a class="links" title="Import" href="{$wifidb_host_url}import/"><img alt="Import" src="{$themeurl}img/upload-cloud.png">Import Files</a>
					<a class="links" title="Map" href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=0"><img alt="Map" src="{$themeurl}img/map.png">Map</a>
					<a class="links" title="Stats" href="{$wifidb_host_url}stats.php"><img alt="Stats" src="{$themeurl}img/stats.png">Stats</a>
					<a class="links" title="Export KMZ" href="{$wifidb_host_url}opt/scheduling.php?func=daemon_kml"><img alt="Export KMZ" src="{$themeurl}img/download-cloud.png">Export KMZ</a>
					<a class="links" title="Log In/Out" href="{$wifidb_host_url}login.php{$wifidb_current_uri}"><img alt="Log In/Out" src="{$themeurl}img/log-in.png">{$wifidb_login_label|default:'Login'}</a>
					<br/>
				</div>
			</div>
{include file="footer.tpl"}