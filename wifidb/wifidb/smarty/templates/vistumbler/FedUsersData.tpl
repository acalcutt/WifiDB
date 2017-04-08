<!--
fetch.tpl: template for a single AP's data results.
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

<table class="style5" width="90%">
	<tr class="contents_pane td">
		<td colspan="3" class="inside_dark_header" style="height: 23px">List Of Federation Servers</td>
	</tr>
	<tr class="style4 ha">
		<th>Username</th>
		<th>Total APs</th>
		<th>Sign Up Date</th>
	</tr>
	{foreach name='FedUsers' item=FedUser from=$FedUsers}
	<tr>
		<td>
			<a href="/wifidb/opt/federation.php?func=FedServerUserImports&UserID={$FedUser.id}&FedServerID={$FedServerId}" >{$FedUser.Username}</a>
		</td>
		<td>
			{$FedUser.TotalAPs}
		</td>
		<td>
			{$FedUser.StartDate}
		</td>
	</tr>
	{foreachelse}
	<tr>
		<td colspan="3">
			No Results Returned From Federation Server.
		</td>
	</tr>
	{/foreach}
</table>

{include file="footer.tpl"}