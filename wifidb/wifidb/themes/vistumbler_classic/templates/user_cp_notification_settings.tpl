<!--
Notifications: for each kind, an alert in the Inbox and/or an e-mail (cp/index.php?func=notifications, lib/notify.inc.php).
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<form method="post" action="{$wifidb_host_url}cp/index.php?func=notifications">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="3">Notifications</td>
						</tr>
						<tr>
							<td class="header wrap" colspan="3">What the site tells you about. <b>Inbox</b> puts an alert under Inbox &rarr; <a href="{$wifidb_host_url}cp/notifications.php">Alerts</a>; <b>Email</b> also sends it to your e-mail address. Sign-up and password e-mails always come.</td>
						</tr>
						{if $notification_saved}<tr><td class="light-centered" colspan="3">Saved.</td></tr>{/if}
						<tr>
							<th class="header"></th>
							<th class="header">Inbox</th>
							<th class="header">Email</th>
						</tr>
						{foreach item=p key=type from=$notification_prefs name=prefs}
						<tr>
							<th width="40%" class="header">{$p.label|escape:'html':'UTF-8':false}</th>
							<td align="center" class="{if $smarty.foreach.prefs.iteration is odd}dark{else}light{/if}">{if $p.inbox_optional}<input name="inbox[{$type|escape:'html':'UTF-8':false}]" type="checkbox"{if $p.on} checked{/if}>{else}<small>always</small>{/if}</td>
							<td align="center" class="{if $smarty.foreach.prefs.iteration is odd}dark{else}light{/if}">{if $p.emailable}<input name="email[{$type|escape:'html':'UTF-8':false}]" type="checkbox"{if $p.email} checked{/if}>{else}<small>&ndash;</small>{/if}</td>
						</tr>
						{/foreach}
						<tr class="light-centered">
							<td colspan="3"><input type="submit" value="Save"></td>
						</tr>
					</table>
				</form>
			</div>
{include file="footer.tpl"}
