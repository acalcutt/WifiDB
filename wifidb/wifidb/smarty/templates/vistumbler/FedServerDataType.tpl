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
		<th>Site Name</th>
		<th>Site Address</th>
		<th>API Version</th>
	</tr>
	<tr>
		<td>
			<a href="/wifidb/opt/federation.php?func=listusers&FedServerID={$FedServer.id}" >{$FedServer.FriendlyName}</a>
		</td>
	</tr>
	<tr>
		<td>
			<a href="/wifidb/opt/federation.php?func=searchusers&FedServerID={$FedServer.id}" >{$FedServer.FriendlyName}</a>
		</td>
	</tr>
	<tr>
		<td>
			<a href="/wifidb/opt/federation.php?func=listimports&FedServerID={$FedServer.id}" >{$FedServer.FriendlyName}</a>
		</td>
	</tr>
	<tr>
		<td>
			<a href="/wifidb/opt/federation.php?func=searchimports&FedServerID={$FedServer.id}" >{$FedServer.FriendlyName}</a>
		</td>
	</tr>
</table>

{include file="footer.tpl"}