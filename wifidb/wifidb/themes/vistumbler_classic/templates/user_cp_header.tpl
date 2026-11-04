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
				<table class="content_table">
					<tr>
						<td class="subheading">User Control Panel</td>
					</tr>
					<tr>
						<td class="header">
							<a href="?func=profile">Profile</a>
							|
							<a href="?func=pref">Email Preferences</a>
							|
							<a href="{$wifidb_host_url}cp/security.php">Security</a>
							|
							<a href="?func=myimports">My Imports</a>
						</td>
					</tr>
					{if $wifidb_is_admin|default:false}
					<tr>
						<td class="subheading">Admin</td>
					</tr>
					<tr>
						<td class="header">
							{if $wifidb_admin_mode|default:false}
							Admin mode is on.
							<a href="{$wifidb_host_url}cp/admin_mode.php?func=off">Leave admin mode</a>
							{else}
							<a href="{$wifidb_host_url}cp/admin_mode.php?return={"`$wifidb_host_url`cp/"|escape:'url'}">Turn on admin mode</a>
							{/if}
						</td>
					</tr>
					{/if}
				</table>