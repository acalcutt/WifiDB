<!--
Shows recovery codes once (cp/security.php).
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<table class="content_table">
					<tr>
						<td class="subheading">{$recovery_headline|escape:'html':'UTF-8':false}</td>
					</tr>
					<tr>
						<td class="header">Keep these recovery codes somewhere safe. Each works once if you lose your device, and this is the only time they are shown.</td>
					</tr>
					<tr class="dark">
						<td align="center"><pre>{foreach item=code from=$recovery_codes}{$code|escape:'html':'UTF-8':false}
{/foreach}</pre></td>
					</tr>
					<tr class="light-centered">
						<td><a href="{$wifidb_host_url}cp/security.php">Done</a></td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}
