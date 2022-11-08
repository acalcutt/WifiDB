<!--
Copyright (C) 2022 Andrew Calcutt

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
{include file="user_cp_header.tpl"}
				<form method="post" action="?func=update_user_profile">
					<table class="content_table">
						<tr>
							<th width="30%" class="dark">Email</th>
							<td class="light"><input type="text" name="email" size="75%" value="{$user_cp_profile.email}"> Hide? <input name="h_email" type="checkbox" {$user_cp_profile.hide_email}></td>
						</tr>
						<tr>
							<th width="30%" class="dark">Website</th>
							<td class="light"><input type="text" name="website" size="75%" value="{$user_cp_profile.website}"></td>
						</tr>
						<tr>
							<th width="30%" class="dark">Vistumbler Version</th>
							<td class="light"><input type="text" name="Vis_ver" size="75%" value="{$user_cp_profile.Vis_ver}"></td>
						</tr>
						<tr>
							<th width="30%" class="dark">Api Key</th>
							<td class="light"><input type="text" name="apikey" size="75%" value="{$user_cp_profile.apikey}"></td>
						</tr>
						<tr>
							<th width="30%" class="dark">Require Login for Import</th>
							<td class="light"><input name="import_require_login" type="checkbox" {$user_cp_profile.import_require_login}></td>
						</tr>
						<tr class="light-centered">
							<td colspan="2">
									<input type="submit" value="Update Me!">
							</td>
						</tr>
					</table>
				</form>
			</div>
{include file="footer.tpl"}