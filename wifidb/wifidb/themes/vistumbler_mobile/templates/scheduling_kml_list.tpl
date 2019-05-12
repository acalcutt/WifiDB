<!--

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
					<table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
						<tr>
							<th colspan="2" class="subheading">{$wifidb_page_label}</th>
						</tr>
						<tr class="header">
							<td width="150px">Name</td>
							<td width="200px">Size</td>
						</tr>
						{foreach item=wifidb_kml from=$files}
						<tr class="{$wifidb_kml.class}">
							<td><a class="links" href="{$wifidb_kml.fileurl}">{$wifidb_kml.filename}</a></td>
							<td>{$wifidb_kml.filesize}</td>
						</tr>								
						{/foreach}
					</table>
				</div>
			</div>
{include file="footer.tpl"}