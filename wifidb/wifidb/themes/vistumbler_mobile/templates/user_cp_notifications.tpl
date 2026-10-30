<!--
Notifications: the member's in-app notifications. Changes are POSTs, see cp/notifications.php.
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
						<td class="subheading" colspan="3">Notifications{if $notification_unread gt 0} ({$notification_unread} unread){/if}</td>
					</tr>
					<tr>
						<td class="header" colspan="3">
							<form method="post" action="{$wifidb_host_url}cp/notifications.php?func=markall" style="display:inline"><input type="submit" value="Mark all read"{if $notification_unread eq 0} disabled{/if}></form>
							| <a href="{$wifidb_host_url}cp/notifications.php?func=settings">Settings</a>
						</td>
					</tr>
					{foreach item=n from=$notifications name=list}
					<tr class="{if $smarty.foreach.list.iteration is odd}dark{else}light{/if}">
						<td>
							{if $n.unread}<b>{/if}{if $n.link}<a href="{$n.link|escape:'html':'UTF-8':false}">{$n.title|escape:'html':'UTF-8':false}</a>{else}{$n.title|escape:'html':'UTF-8':false}{/if}{if $n.unread}</b>{/if}
							{if $n.body}<br><small>{$n.body|escape:'html':'UTF-8':false|nl2br}</small>{/if}
						</td>
						<td>{$n.created|escape:'html':'UTF-8':false} UTC</td>
						<td>
							{if $n.unread}<form method="post" action="{$wifidb_host_url}cp/notifications.php?func=markread&amp;page={$notification_page}" style="display:inline"><input type="hidden" name="id" value="{$n.id|escape:'html':'UTF-8':false}"><input type="submit" value="Mark read"></form>{/if}
							<form method="post" action="{$wifidb_host_url}cp/notifications.php?func=delete&amp;page={$notification_page}" style="display:inline"><input type="hidden" name="id" value="{$n.id|escape:'html':'UTF-8':false}"><input type="submit" value="Delete"></form>
						</td>
					</tr>
					{foreachelse}
					<tr>
						<td align="center" colspan="3">You have no notifications.</td>
					</tr>
					{/foreach}
					{if $notification_pages gt 1}
					<tr>
						<td align="center" colspan="3">
							{if $notification_page gt 1}<a href="{$wifidb_host_url}cp/notifications.php?page={$notification_page-1}">&laquo; Newer</a>{/if}
							Page {$notification_page} of {$notification_pages}
							{if $notification_page lt $notification_pages}<a href="{$wifidb_host_url}cp/notifications.php?page={$notification_page+1}">Older &raquo;</a>{/if}
						</td>
					</tr>
					{/if}
				</table>
			</div>
{include file="footer.tpl"}
