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
				<div class="center">
					<h2>{$mesg}</h2>
					<h2>Import Access Points</h2>
					Vistumbler 
					<a href="https://github.com/acalcutt/Vistumbler/wiki/Vistumbler-VS1-Format" target="_blank">VS1</a> /
					<a href="https://github.com/acalcutt/Vistumbler/wiki/Vistumbler-Detailed-CSV-Format" target="_blank">CSV</a>
					files are the main import formats.<br/>
					For Android, we support 
					<a href="https://play.google.com/store/apps/details?id=net.wigle.wigleandroid" target="_blank">WigleWifi CSV</a>,
					<a href="https://play.google.com/store/apps/details?id=com.Buckynet.Wifi.Scanner.Wireless.SWardriving&hl=en_US" target="_blank">SWardriving CSV</a>, and 
					<a href="https://github.com/raffaeleragni/android-wardrive4/releases" target="_blank">Wardrive DB/DB3</a><br><br>
					Username is optional, but it helps keep track of who has imported what Access Points<br><br>
				</div>
					<form action="{$wifidb_host_url}import/?func=import" method="post" enctype="multipart/form-data">				
						<table class="content_table-centered">
							<tbody>
							<tr height="40">
								<td class="header">
									Title of Import:
								</td>
								<td class="light">
									<a name="title"></a><input type="TEXT" name="title" size="28" style="width: 2.42in; height: 0.25in"/>
								</td>
							</tr>
							<tr height="40">
								<td class="header">
									File location:
								</td>
								<td class="light">
									<a name="file"></a><input type="FILE" name="file" size="56" style="width: 5.41in; height: 0.25in"/>
								</td>
							</tr>
							<tr height="40">
								<td class="header">
									Username:
								</td>
								<td class="light">
										<a name="user"></a>
										<input type="TEXT" name="user" value="{$wifidb_login_user|default:""}" size="28" style="width: 2.42in; height: 0.25in">
								</td>
							</tr>
							<tr>
							<tr height="40">
								<td class="header">
									Other Users:<br/>
										<font size=1>(Separate by a pipe "|" )</font>
								</td>
								<td class="light">
									<a name="otherusers"></a><input type="text" name="otherusers" size="56" style="width: 5.41in; height: 0.25in"/>
								</td>
							</tr>
							<tr>
								<td class="header">
									Notes:
								</td>
								<td class="light">
										<textarea name="notes" rows="4" cols="50" style="width: 4.42in; height: 1.01in"></textarea><br>
								</td>
							</tr>
							<tr>
								<td class="header">
									Import Type:
								</td>
								<td class="light">
										  <input type="radio" name="type" value="vistumbler" checked>Vistumbler VS1/VSZ/CSV/MDB<br>
										  <input type="radio" name="type" value="wardrive">Wardrive DB/DB3<br>
										  <input type="radio" name="type" value="wiglewificsv">WigleWifi CSV<br>
										  <input type="radio" name="type" value="swardriving">SWardriving CSV<br>
								</td>
							</tr>
							<tr class="light">
								<td class="light-centered" colspan = "2">
										{$import_button}
									
								</td>
							</tr>
						</tbody>
						</table>
					</form>
			</div>
{include file="footer.tpl"}