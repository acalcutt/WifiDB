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
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<form method="post" action="?func=update_user_pref">
					<table class="content_table">
						<tr>
							<th width="30%" class="header">New Users</th>
							<td align="center" class="dark"><input name="new_users" type="checkbox" {$user_cp_profile.new_users}></td></td>
						</tr>
						<tr>
							<th width="30%" class="header">New KMZ Export</th>
							<td align="center" class="light"><input name="kmz" type="checkbox" {$user_cp_profile.kmz}></td></td>
						</tr>						
						<tr>
							<th width="30%" class="header">Import Started</th>
							<td align="center" class="dark"><input name="schedule" type="checkbox" {$user_cp_profile.schedule}></td></td>
						</tr>
						<tr>
							<th width="30%" class="header">Import Finished</th>
							<td align="center" class="light"><input name="imports" type="checkbox" {$user_cp_profile.imports}></td></td>
						</tr>
						<tr class="light-centered">
							<td colspan="2">
									<input type="hidden" name="username" value="{$user_cp_profile.username}">
									<input type="hidden" name="user_id" value="{$user_cp_profile.id}">
									<input type="submit" value="Update Me!">
							</td>
						</tr>
					</table>
				</form>
			</div>
{include file="footer.tpl"}