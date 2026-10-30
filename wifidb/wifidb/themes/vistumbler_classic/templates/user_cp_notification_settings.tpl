<!--
Notification settings: which in-app notifications the member wants. See cp/notifications.php.
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<form method="post" action="{$wifidb_host_url}cp/notifications.php?func=settings">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Notification Settings</td>
						</tr>
						<tr>
							<td class="header" colspan="2"><a href="{$wifidb_host_url}cp/notifications.php">Back to notifications</a>. Errors and warnings for administrators are always shown. E-mail is chosen separately, under Email Preferences.</td>
						</tr>
						{if $notification_saved}<tr><td class="light-centered" colspan="2">Saved.</td></tr>{/if}
						{foreach item=p key=type from=$notification_prefs name=prefs}
						<tr>
							<th width="30%" class="header">{$p.label|escape:'html':'UTF-8':false}</th>
							<td align="center" class="{if $smarty.foreach.prefs.iteration is odd}dark{else}light{/if}"><input name="type[{$type|escape:'html':'UTF-8':false}]" type="checkbox"{if $p.on} checked{/if}></td>
						</tr>
						{/foreach}
						<tr class="light-centered">
							<td colspan="2"><input type="submit" value="Save"></td>
						</tr>
					</table>
				</form>
			</div>
{include file="footer.tpl"}
