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
				<br />
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
					</tr>
					<tr>
						<td class="light">
							{if $message.title eq ''}[Blank Title]{else}{$message.title}{/if}
						</td>
						<td class="light">
							{$message.username1}
						</td>
						<td class="light">
							{$message.username2}
						</td>						
						<td class="light">
							{$message.stimestamp}
						</td>
					</tr>
					<tr class="header">
						<td class="header" colspan=4>
							<div>Message</div>
						</td>
					</tr>
					<tr>
						<td class="light-wrapword" colspan=4>
							<textarea id="message" name="message" style="width:100%;" disabled>{$message.message}</textarea>
						</td>						
					</tr>
				</table>
				<script>
					var textarea = document.getElementById('message');
					sceditor.create(textarea, {
						format: 'bbcode',
						icons: 'monocons',
						autoExpand: true,
						readOnly: true,
						toolbar: '',
						resizeMaxHeight: -1,
						style: '{$themeurl}lib/sceditor/minified/themes/content/default.min.css'
					});
				</script>
				<a href="{$wifidb_host_url}cp/index.php?func=sendmsg&thread_id={$message.thread_id|escape:'url'}&to={$message.uid1|escape:'url'}&title={$message.title|escape:'url'}" target="_blank">Reply</a> 
			</div>
{include file="footer.tpl"}