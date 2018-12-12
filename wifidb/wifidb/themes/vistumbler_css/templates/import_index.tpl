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
							<h2>{$mesg}</h2>
							<h2>Import Access Points</h2>
							Vistumbler 
							<a class="links" href="https://github.com/acalcutt/Vistumbler/wiki/Vistumbler-VS1-Format" target="_blank">VS1</a> /
							<a class="links" href="https://github.com/acalcutt/Vistumbler/wiki/Vistumbler-Detailed-CSV-Format" target="_blank">CSV</a>
							files are the main import formats.<br/>
							For Android, we support 
							<a class="links" href="https://play.google.com/store/apps/details?id=net.wigle.wigleandroid" target="_blank">WiggleWifi CSV</a>,
							<a class="links" href="https://play.google.com/store/apps/details?id=com.Buckynet.Wifi.Scanner.Wireless.SWardriving&hl=en_US" target="_blank">SWardriving CSV</a>, and 
							<a class="links" href="https://github.com/raffaeleragni/android-wardrive4/releases" target="_blank">Wardrive DB/DB3</a><br><br>
							Username is optional, but it helps keep track of who has imported what Access Points<br><br>

						</td>
					</tr>
				</table>
					<form action="{$wifidb_host_url}import/?func=import" method="post" enctype="multipart/form-data">				
						<table class="content_table-centered">
							<tbody>
							<tr height="40">
								<td class="header">
									<p>Title of Import:
									</p>
								</td>
								<td class="light">
									<p><a name="title"></a><input type="TEXT" name="title" size="28" style="width: 2.42in; height: 0.25in"/></p>
								</td>
							</tr>
							<tr height="40">
								<td class="header">
									<p>File location:
									</p>
								</td>
								<td class="light">
									<p><a name="file"></a><input type="FILE" name="file" size="56" style="width: 5.41in; height: 0.25in"/></p>
								</td>
							</tr>
							<tr height="40">
								<td class="header">
									<p>Username:
									</p>
								</td>
								<td class="light">
									<p>
										<a name="user"></a>
										{$import_username_field|default:'<input type="text" name="username" size="56" style="width: 5.41in; height: 0.25in"/>'}
									</p>
								</td>
							</tr>
							<tr>
							<tr height="40">
								<td class="header">
									<p>Other Users:<br/>
										<font size=1>(Separate by a pipe "|" )</font>
									</p>
								</td>
								<td class="light">
									<p><a name="otherusers"></a><input type="text" name="otherusers" size="56" style="width: 5.41in; height: 0.25in"/></p>
								</td>
							</tr>
							<tr>
								<td class="header">
									<p>Notes:
									</p>
								</td>
								<td class="light">
									<p>
										<textarea name="notes" rows="4" cols="50" style="width: 4.42in; height: 1.01in"></textarea><br>
									</p>
								</td>
							</tr>
							<tr>
								<td class="header">
									<p>Import Type:
									</p>
								</td>
								<td class="light">
									<p>
										  <input type="radio" name="type" value="vistumbler" checked>Vistumbler VS1/VSZ/CSV/MDB<br>
										  <input type="radio" name="type" value="wardrive4">Wardrive4 DB<br>
										  <input type="radio" name="type" value="wigglewificsv">WiggleWifi CSV<br>
										  <input type="radio" name="type" value="swardriving">SWardriving CSV<br>
									</p>
								</td>
							</tr>
							<tr class="light">
								<td>&nbsp;</td><td>
									<p>
										{$import_button}
									</p>
								</td>
							</tr>
						</tbody>
						</table>
					</form>
			</div>
{include file="footer.tpl"}