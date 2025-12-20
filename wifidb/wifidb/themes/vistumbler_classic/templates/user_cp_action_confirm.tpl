<!--
User CP Action Confirmation Template
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<div class="center">
					<h2>Confirm Delete Import</h2>
					<table class="content_table">
						<tbody>
							<tr class="subheading">
								<th class="subheading" colspan="2">Delete Your Import</th>
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
								<td class="light"><strong>Title:</strong></td>
								<td class="light">{$file_info.title|escape:'htmlall'}</td>
							</tr>
							<tr class="dark">
								<td class="dark" colspan="2" style="padding: 15px;">
									<p style="color: #cc0000; font-weight: bold;">Warning: This action will permanently delete this import including:</p>
									<ul>
										<li>All WiFi AP data associated with this file</li>
										<li>All GPS history for this file</li>
										<li>All Cell/Bluetooth data associated with this file</li>
									</ul>
									<p>APs/Cells that exist in other imports will be preserved and re-linked to those imports.</p>
									<p><strong>This action cannot be undone.</strong></p>
								</td>
							</tr>
							<tr class="light">
								<td class="light" colspan="2" style="text-align: center; padding: 15px;">
									<a href="{$wifidb_host_url}cp/user_action.php?action={$action}&amp;file_id={$file_id}&amp;confirm=yes{if $return_url}&amp;return={$return_url|escape:'url'}{/if}" style="background-color: #cc0000; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">Confirm Delete</a>
									<a href="{if $return_url}{$return_url}{else}{$wifidb_host_url}cp/index.php?func=myimports{/if}" style="background-color: #666; color: white; padding: 10px 20px; text-decoration: none;">Cancel</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}
