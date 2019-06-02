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
		
				{if $func eq 'inbox'}<b>{/if}<a href="{$wifidb_host_url}cp/messages.php?func=inbox">Received</a>{if $func eq 'inbox'}</b>{/if} | {if $func eq 'outbox'}<b>{/if}<a href="{$wifidb_host_url}cp/messages.php?func=outbox">Sent</a>{if $func eq 'outbox'}</b>{/if} | <a href="{$wifidb_host_url}cp/messages.php?func=sendmsg">Send Message</a> 
				<table class="content_table"">
					<tr class="header">
						<td class="header">
							<div>Title</div>
						</td>
						<td class="header">
							<div>From</div>
						</td>
						<td class="header">
							<div>To</div>
						</td>	
						<td class="header">
							<div>Timestamp</div>
						</td>
						<td class="header">
							<div>Action</div>
						</td>
					</tr>
					{foreach name=outer item=message from=$inbox_messages}
					<tr class="{$message.class}">
						<td class="{$message.class}">
						{if $message.read eq 1}
							<a href="{$wifidb_host_url}cp/messages.php?func=replymsg&id={$message.id}">{if $message.title eq ''}[Blank Title]{else}{$message.title}{/if}</a> 
						{else}
							<a href="{$wifidb_host_url}cp/messages.php?func=replymsg&id={$message.id}"><b>{if $message.title eq ''}[Blank Title]{else}{$message.title}{/if}</b></a> 
						{/if}
						</td>
						<td class="{$message.class}">
						{if $message.user1read eq 1}
							{$message.username1}
						{else}
							<b>{$message.username1}</b>
						{/if}
						</td>
						<td class="{$wifidb_ap.class}">
						{if $message.user2read eq 1}
							{$message.username2}
						{else}
							<b>{$message.username2}</b>
						{/if}
						</td>						
						<td class="{$message.class}">
							{$message.stimestamp}
						</td>
						<td class="{$message.class}">
							<a href="{$wifidb_host_url}cp/messages.php?func=mark-read&id={$message.id}" title="Mark Read"><img width="20px" src="{$themeurl}img/mark-read.png"></a>
							<a href="{$wifidb_host_url}cp/messages.php?func=mark-unread&id={$message.id}" title="Mark Un-Read"><img width="20px" src="{$themeurl}img/mark-unread.png"></a>
							<a href="{$wifidb_host_url}cp/messages.php?func=delmsg&id={$message.id}" title="Delete Message"><img width="20px" src="{$themeurl}img/delete.png"></a>
						</td>
					</tr>
					{foreachelse}
					<tr>
						<td align="center" colspan="9">
							There are no messages in your inbox...
						</td>
					</tr>
					{/foreach}
				</table>
			</div>
{include file="footer.tpl"}