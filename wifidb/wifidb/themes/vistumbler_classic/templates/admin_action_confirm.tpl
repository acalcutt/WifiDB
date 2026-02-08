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
								<th class="subheading" colspan="2">{if $action == 'reset_file'}Reset File for Re-Import{elseif $action == 'delete_file'}Delete File Permanently{elseif $action == 'reset_schedule'}Reset Daemon Schedule{elseif $action == 'run_schedule_now'}Run Schedule Now{elseif $action == 'delete_daemon_pid'}Delete Daemon PID{else}Confirm Action{/if}</th>
							</tr>
							{if $action == 'delete_daemon_pid'}
							<tr class="light">
								<td class="light" width="150px"><strong>Node:</strong></td>
								<td class="light">{$pid_info.nodename|escape:'htmlall'}</td>
							</tr>
							<tr class="dark">
								<td class="dark"><strong>PID File:</strong></td>
								<td class="dark">{$pid_info.pidfile|escape:'htmlall'}</td>
							</tr>
							<tr class="light">
								<td class="light"><strong>PID:</strong></td>
								<td class="light">{$pid_info.pid|escape:'htmlall'}</td>
							</tr>
							<tr class="dark">
								<td class="dark" colspan="2" style="padding: 15px;">
									<p style="font-weight: bold;">This action will:</p>
									<ul>
										<li>Delete the PID record from the database</li>
										<li>Queue a job to delete the PID file from disk</li>
									</ul>
								</td>
							</tr>
							<tr class="light">
								<td class="light" colspan="2" style="text-align: center; padding: 15px;">
									<a href="{$wifidb_host_url}opt/admin_action.php?action={$action}&amp;pidfile={$pidfile|escape:'url'}&amp;confirm=yes{if $return_url}&amp;return={$return_url|escape:'url'}{/if}" style="background-color: #cc0000; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">Confirm Delete</a>
									<a href="{if $return_url}{$return_url}{else}{$wifidb_host_url}opt/scheduling.php?func=schedule{/if}" style="background-color: #666; color: white; padding: 10px 20px; text-decoration: none;">Cancel</a>
								</td>
							</tr>
							{elseif $action == 'reset_schedule'}
							<tr class="light">
								<td class="light" width="150px"><strong>Schedule ID:</strong></td>
								<td class="light">{$schedule_info.id}</td>
							</tr>
							<tr class="dark">
								<td class="dark"><strong>Node:</strong></td>
								<td class="dark">{$schedule_info.nodename|escape:'htmlall'}</td>
							</tr>
							<tr class="light">
								<td class="light"><strong>Daemon:</strong></td>
								<td class="light">{$schedule_info.daemon|escape:'htmlall'}</td>
							</tr>
							<tr class="dark">
								<td class="dark"><strong>Current Status:</strong></td>
								<td class="dark">{$schedule_info.status|escape:'htmlall'}</td>
							</tr>
							<tr class="light">
								<td class="light"><strong>Interval:</strong></td>
								<td class="light">{$schedule_info.interval|escape:'htmlall'} minutes</td>
							</tr>
							<tr class="dark">
								<td class="dark" colspan="2" style="padding: 15px;">
									<p style="font-weight: bold;">This action will:</p>
									<ul>
										<li>Set the schedule status to "Waiting"</li>
										<li>Clear the PID</li>
										<li>Set the next run time to now + {$schedule_info.interval} minutes</li>
									</ul>
								</td>
							</tr>
							<tr class="light">
								<td class="light" colspan="2" style="text-align: center; padding: 15px;">
									<a href="{$wifidb_host_url}opt/admin_action.php?action={$action}&amp;schedule_id={$schedule_id}&amp;confirm=yes{if $return_url}&amp;return={$return_url|escape:'url'}{/if}" style="background-color: #4a6fa5; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">Confirm Reset</a>
									<a href="{if $return_url}{$return_url}{else}{$wifidb_host_url}opt/scheduling.php?func=schedule{/if}" style="background-color: #666; color: white; padding: 10px 20px; text-decoration: none;">Cancel</a>
								</td>
							</tr>
							{elseif $action == 'run_schedule_now'}
							<tr class="light">
								<td class="light" width="150px"><strong>Schedule ID:</strong></td>
								<td class="light">{$schedule_info.id}</td>
							</tr>
							<tr class="dark">
								<td class="dark"><strong>Node:</strong></td>
								<td class="dark">{$schedule_info.nodename|escape:'htmlall'}</td>
							</tr>
							<tr class="light">
								<td class="light"><strong>Daemon:</strong></td>
								<td class="light">{$schedule_info.daemon|escape:'htmlall'}</td>
							</tr>
							<tr class="dark">
								<td class="dark"><strong>Current Status:</strong></td>
								<td class="dark">{$schedule_info.status|escape:'htmlall'}</td>
							</tr>
							<tr class="light">
								<td class="light"><strong>Interval:</strong></td>
								<td class="light">{$schedule_info.interval|escape:'htmlall'} minutes</td>
							</tr>
							<tr class="dark">
								<td class="dark" colspan="2" style="padding: 15px;">
									<p style="font-weight: bold;">This action will:</p>
									<ul>
										<li>Set the schedule status to "Waiting"</li>
										<li>Clear the PID</li>
										<li>Set the next run time to NOW (Run Immediately)</li>
									</ul>
								</td>
							</tr>
							<tr class="light">
								<td class="light" colspan="2" style="text-align: center; padding: 15px;">
									<a href="{$wifidb_host_url}opt/admin_action.php?action={$action}&amp;schedule_id={$schedule_id}&amp;confirm=yes{if $return_url}&amp;return={$return_url|escape:'url'}{/if}" style="background-color: #2e8b57; color: white; padding: 10px 20px; text-decoration: none; margin-right: 10px;">Confirm Run Now</a>
									<a href="{if $return_url}{$return_url}{else}{$wifidb_host_url}opt/scheduling.php?func=schedule{/if}" style="background-color: #666; color: white; padding: 10px 20px; text-decoration: none;">Cancel</a>
								</td>
							</tr>
							{else}
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
							{/if}
						</tbody>
					</table>
				</div>
			</div>
{include file="footer.tpl"}
