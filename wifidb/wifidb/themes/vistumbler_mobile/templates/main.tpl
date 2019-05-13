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
			<div class="main">{$install_header}{$wifidb_announce_header}
				{include file="topmenu.tpl"}
				<div class="center">
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