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
				<form method="post" action="/wifidb/login.php?func=login_proc">
					<table align="center">
						<tbody>
							<tr>
								<td colspan="2">
									<p align="center"><img src="{$wifidb_host_url}themes/{$wifidb_theme}/img/logo.png"></p>
									{$message}
								</td>
							</tr>
							<tr>
								<td>Username</td>
								<td><input type="text" name="time_user"></td>
							</tr>
							<tr>
								<td>Password</td>
								<td><input type="password" name="time_pass"></td>
							</tr>
							<tr>
								<td colspan="2"><p align="center"><input type="hidden" name="return" value="{$logon_return_url}"><input type="submit" value="Login"></p></td>
							</tr>
							<tr>
								<td colspan="2">
									<p align="center">
										<a class="links" href="{$wifidb_host_url}login.php?func=create_user_form">Create a user account</a><br>
										<a class="links" href="{$wifidb_host_url}login.php?func=reset_user_pass_request">Forgot your password?</a>
									</p>
								</td>
							</tr>
						</tbody>
					</table>
				</form>
			</div>
{include file="footer.tpl"}