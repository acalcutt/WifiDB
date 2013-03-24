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
								<table border="1" width="90%">
									<tbody>
									<tr class="style4">									
										<th colspan="9" align="center">Files already imported</th>
									</tr>
									<tr>
										<td width="90%" align="center">
										{foreach name=outer item=wifidb_imported from=$wifidb_imported_all}
											<table>
												<tbody>
												<tr class="sub_head">
													<th>ID</th>
													<th>Filename</th>
													<th>Date</th>
													<th>Username</th>
													<th>Title</th>
												</tr>
												<tr class="{$wifidb_imported_class}">
													<td>{$wifidb_imported.id}</td>
													<td>{$wifidb_imported.filename}</td>
													<td>{$wifidb_imported.date}</td>
													<td>{$wifidb_imported.username}</td>
													<td>{$wifidb_imported.title}</td>
												</tr>
												<tr class="sub_head">
													<th></th>
													<th>Total AP's</th>
													<th>Total GPS</th>
													<th>Size</th>
													<th>Hash Sum</th>
												</tr>
												<tr class="{$wifidb_imported_class}">
													<td>{$wifidb_imported.aps}</td>
													<td>{$wifidb_imported.gps}</td>
													<td>{$wifidb_imported.size}</td>
													<td>{$wifidb_imported.hash}</td>
												</tr>
												</tbody>
											</table>
										{foreachelse}
											There where no files that where imported, Go and import a file
										{/foreach}
										</td>
									</tr>
									</tbody>
								</table>
							</td>
							<td class="cell_side_right">&nbsp;</td>
						</tr>
{include file="footer.tpl"}