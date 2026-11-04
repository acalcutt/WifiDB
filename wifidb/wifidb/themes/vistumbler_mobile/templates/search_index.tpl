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
				{include file="topmenu.tpl"}
				<div class="center">
					<h2>Search for Access Points</h2>
					<form action="{$wifidb_host_url}opt/results.php?ord=DESC&amp;sort=AP_ID&amp;from=0&amp;inc=500" method="post" enctype="multipart/form-data">
						<table class="content_table-centered">
							<thead>
								<tr>
									<th class="header">SSID:</th>
									<th>
										<input type="text" name="ssid" size="40" id="ssid" onkeyup="doCompletion();"/>
										<select name="ssid_op" title="How to match SSID">
											<option value="equals" selected>Equals</option>
											<option value="contains">Contains</option>
											<option value="starts">Starts with</option>
											<option value="ends">Ends with</option>
										</select>
									</th>
								</tr>
								<tr>
									<th class="header">MacAddress:</th>
									<th>
										<input type="text" name="mac" size="40" id="mac" onkeyup="doCompletion();"/>
										<select name="mac_op" title="How to match MacAddress">
											<option value="equals" selected>Equals</option>
											<option value="contains">Contains</option>
											<option value="starts">Starts with</option>
											<option value="ends">Ends with</option>
										</select>
									</th>
								</tr>
								<tr>
									<th class="header">Authentication:</th>
									<th>
										<input type="text" name="auth" size="40" id="auth" onkeyup="doCompletion();"/>
										<select name="auth_op" title="How to match Authentication">
											<option value="equals" selected>Equals</option>
											<option value="contains">Contains</option>
											<option value="starts">Starts with</option>
											<option value="ends">Ends with</option>
										</select>
									</th>
								</tr>
								<tr>
									<th class="header">Encryption:</th>
									<th>
										<input type="text" name="encry" size="40" id="encry" onkeyup="doCompletion();"/>
										<select name="encry_op" title="How to match Encryption">
											<option value="equals" selected>Equals</option>
											<option value="contains">Contains</option>
											<option value="starts">Starts with</option>
											<option value="ends">Ends with</option>
										</select>
									</th>
								</tr>
								<tr>
									<th class="header">Radio Type:</th>
									<th>
										<input type="text" name="radio" size="40" id="radio" onkeyup="doCompletion();"/>
										<select name="radio_op" title="How to match Radio Type">
											<option value="equals" selected>Equals</option>
											<option value="contains">Contains</option>
											<option value="starts">Starts with</option>
											<option value="ends">Ends with</option>
										</select>
									</th>
								</tr>
								<tr>
									<th class="header">Channel:</th>
									<th>
										<input type="text" name="chan" size="40" id="chan" onkeyup="doCompletion();"/>
										<select name="chan_op" title="How to match Channel">
											<option value="equals" selected>Equals</option>
											<option value="contains">Contains</option>
											<option value="starts">Starts with</option>
											<option value="ends">Ends with</option>
										</select>
									</th>
								</tr>
								<tr>
									<td align="center" colspan="2">
										<input type="submit" name="submit" value="Submit" style="width: 0.71in; height: 0.36in"/>
									</td>
								</tr>
							</thead>
							<tbody>
								<tr>
									<td align="center" id="auto-row" colspan="2">
										<table class="popupBox" style="display:none"></table>
									</td>
								</tr>
							</tbody>
						</table>
					</form>
				</div>
			</div>
{include file="footer.tpl"}