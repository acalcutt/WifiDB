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
				<table class="content_table-centered">
					<tr>
						<td>
							<table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
								<tr>
									<th colspan="3" class="header">Legacy KMZ Full and Daily exports</th>
								</tr>
								<tr class="header">
									<td width="150px">Date</td>
									<td width="200px">Full DB KMZ</td>
									<td width="200px">Daily KMZ</td>
								</tr>
								<tr>
									<td colspan="5" class="dark">
								{foreach item=wifidb_kml from=$wifidb_kml_all_array}
										<table align="center" border="1" cellspacing="0" cellpadding="0" width="100%">
											<tr class="{$wifidb_kml.class}">
												<td width="150">
													<a class="links" href="{$wifidb_kml.link_url}">{$wifidb_kml.file}</a>
												</td>
												<td width="250px">
													<a class="links" href="{$wifidb_kml.file_url}">Non-Labeled SSIDs</a> - {$wifidb_kml.full_size}
													<br/>
													<a class="links" href="{$wifidb_kml.file_label_url}">Labeled SSIDs</a> - {$wifidb_kml.full_size_label}
												</td>
												<td width="250px">
													<a class="links" href="{$wifidb_kml.daily_url}">Non-Labeled SSIDs</a> - {$wifidb_kml.daily_size}
													<br/>
													<a class="links" href="{$wifidb_kml.daily_label_url}">Labeled SSIDs</a> - {$wifidb_kml.daily_size_label}
												</td>
											</tr>
										</table>
									{foreachelse}
										There are no KML files that have been generated yet.
									{/foreach}
									</td>
								</tr>
							</table>
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}