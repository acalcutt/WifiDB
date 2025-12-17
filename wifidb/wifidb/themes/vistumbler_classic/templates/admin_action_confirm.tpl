<!--
Admin Action Confirmation Template
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<div class="center">
					<h2>Confirm Action</h2>
					<table class="content_table">
						<tbody>
							<tr class="subheading">
								<th class="subheading" colspan="2">{if $action == 'reset_file'}Reset File for Re-Import{elseif $action == 'delete_file'}Delete File Permanently{/if}</th>
							</tr>
							<tr class="light">
								<td class="light" width="150px"><strong>File ID:</strong></td>
								<td class="light">{$file_info.id}</td>
							</tr>
							<tr class="dark">
								<td class="dark"><strong>File Name:</strong></td>
								<td class="dark">{$file_info.file_orig|escape:'htmlall'}</td>
							</tr>
							<tr class="light">
								<td class="light"><strong>User:</strong></td>
								<td class="light">{$file_info.file_user|escape:'htmlall'}</td>
							</tr>
							<tr class="dark">
								<td class="dark"><strong>Title:</strong></td>
								<td class="dark">{$file_info.title|escape:'htmlall'}</td>
							</tr>
							<tr class="light">
								<td class="light" colspan="2" style="padding: 15px;">
									<p style="color: #cc0000; font-weight: bold;">Warning: This action will:</p>
									<ul>
										<li>Remove all WiFi AP data associated with this file</li>
										<li>Remove all GPS history for this file</li>
										<li>Remove all Cell/Bluetooth data associated with this file</li>
										{if $action == 'reset_file'}<li>Queue the file for re-import</li>{elseif $action == 'delete_file'}<li>This file will be permanently deleted from the database and not re-imported. The underlying uploaded file will be moved to <em>import/up/deleted</em> if present.</li>{/if}
									</ul>
									<p>APs/Cells that exist in other imports will be preserved and re-linked.</p>
								</td>
							</tr>
							<tr class="dark">
								<td class="dark" colspan="2" style="text-align: center; padding: 15px;">
									<a href="{$wifidb_host_url}opt/admin_action.php?action={$action}&amp;file_id={$file_id}&amp;confirm=yes{if $return_url}&amp;return={$return_url|escape:'url'}{/if}" style="background-color: #cc0000; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">{if $action == 'reset_file'}Confirm Reset{elseif $action == 'delete_file'}Confirm Delete{/if}</a>
									<a href="{if $return_url}{$return_url}{else}{$wifidb_host_url}{/if}" style="background-color: #666; color: white; padding: 10px 20px; text-decoration: none;">Cancel</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}
