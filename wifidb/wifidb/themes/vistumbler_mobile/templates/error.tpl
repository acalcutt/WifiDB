<!--
Error.tpl, Is the default error showing page for WiFiDB.
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
				{include file="topmenu.tpl"}
				<div align="center">
					<table border='1'>
						<tr>
							<td class="dark">
								Error:
							</td>
							<td class="light">
								{$wifidb_error_mesg.Error}
							</td>
						</tr>
						<tr>
							<td class="dark">
								Message:
							</td>
							<td class="light">
								{$wifidb_error_mesg.Message}
							</td>
						</tr>
						<tr>
							<td class="dark">
								Code:
							</td>
							<td class="light">
								{$wifidb_error_mesg.Code}
							</td>
						</tr>
						<tr>
							<td class="dark">
								File:
							</td>
							<td class="light">
								{$wifidb_error_mesg.File}
							</td>
						</tr>
						<tr>
							<td class="dark">
								Line:
							</td>
							<td class="light">
								{$wifidb_error_mesg.Line}
							</td>
						</tr>
					</table>
				</div>
			</div>
{include file="footer.tpl"}