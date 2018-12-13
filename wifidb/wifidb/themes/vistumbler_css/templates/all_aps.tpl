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
				<table class="content_table"">
					<tr class="header">
						<td class="header">
							GPS
						</td>
						<td class="header">
							<a href="?sort=SSID&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0"border="0" src="{$themeurl}img/down.png"></a>
							<a href="?sort=SSID&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							SSID
						</td>
						<td class="header">
							<a href="?sort=BSSID&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/down.png"></a>
							<a href="?sort=BSSID&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							MAC
						</td>
						<td class="header">
							<a href="?sort=CHAN&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/down.png"></a>
							<a href="?sort=CHAN&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							Channel
						</td>
						<td class="header">
							<a href="?sort=RADTYPE&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
							<a href="?sort=RADTYPE&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							Radio Type
						</td>
						<td class="header">
							<a href="?sort=AUTH&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
							<a href="?sort=AUTH&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							Authentication
						</td>
						<td class="header">
							<a href="?sort=ENCR&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
							<a href="?sort=ENCR&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							Encryption
						</td>
						<td class="header">
							<a href="?sort=FA&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
							<a href="?sort=FA&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							First Active
						</td>
						<td class="header">
							<a href="?sort=LA&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="{$themeurl}img/down.png"></a>
							<a href="?sort=LA&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="{$themeurl}img/up.png"></a>
							Last Active
						</td>
					</tr>
					{foreach name=outer item=wifidb_ap from=$wifidb_aps_all}
					<tr class="{$wifidb_ap.class}">
						<td class="{$wifidb_ap.class}" width="75px">
							{$wifidb_ap.globe_html}
						</td>
						<td class="{$wifidb_ap.class}">
							<a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_ap.id}" title="View AP Details">{$wifidb_ap.ssid}</a>
						</td>
						<td class="{$wifidb_ap.class}">
							{$wifidb_ap.mac}
						</td>
						<td class="{$wifidb_ap.class}">
							{$wifidb_ap.chan}
						</td>
						<td class="{$wifidb_ap.class}">
							{$wifidb_ap.radio}
						</td>
						<td class="{$wifidb_ap.class}">
							{$wifidb_ap.auth}
						</td>
						<td class="{$wifidb_ap.class}">
							{$wifidb_ap.encry}
						</td>
						<td class="{$wifidb_ap.class}">
							{$wifidb_ap.fa}
						</td>
						<td class="{$wifidb_ap.class}">
							{$wifidb_ap.la}
						</td>
					</tr>
					{foreachelse}
					<tr>
						<td align="center" colspan="9">
							<b>There are no Access Points imported as of yet, go grab some with Vistumbler and import them.<br />
							Come on... you know you want too.</b>
						</td>
					</tr>
					{/foreach}
					<tr class="sub_head">
						<td colspan="9" align="center">
						 {$pages_together}
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}