<!--
Database.inc.php, holds the database interactive functions.
Copyright (C) 2014 Andrew Calcutt

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
{include file="user_cp_header.tpl"}
<table  BORDER=0 CELLPADDING=0 CELLSPACING=0 style="width: 100%">
	<tr>
		<td colspan="6" class="style4">
			<form method="post" action="?func=update_user_profile">
				<table  BORDER=1 CELLPADDING=2 CELLSPACING=0 style="width: 100%">
					<tr>
						<th width="30%" class="style4">Email</th>
						<td class="light"><input type="text" name="email" size="75%" value="{$user_cp_profile.email}"> Hide? <input name="h_email" type="checkbox" {$user_cp_profile.hide_email}></td>
					</tr>
					<tr>
						<th width="30%" class="style4">Website</th>
						<td class="light"><input type="text" name="website" size="75%" value="{$user_cp_profile.website}"></td>
					</tr>
					<tr>
						<th width="30%" class="style4">Vistumbler Version</th>
						<td class="light"><input type="text" name="Vis_ver" size="75%" value="{$user_cp_profile.Vis_ver}"></td>
					</tr>
					<tr>
						<th width="30%" class="style4">Api Key</th>
						<td class="light"><input type="text" name="apikey" size="75%" value="{$user_cp_profile.apikey}"></td>
					</tr>
					<tr class="style4">
						<td colspan="2">
							<p align="center">
								<input type="hidden" name="username" value="{$user_cp_profile.username}">
								<input type="hidden" name="user_id" value="{$user_cp_profile.id}">
								<input type="submit" value="Update Me!">
							</p>
						</td>
					</tr>
				</table>
			</form>
		</td>
	</tr>
</table>
{include file="user_cp_footer.tpl"}
{include file="footer.tpl"}