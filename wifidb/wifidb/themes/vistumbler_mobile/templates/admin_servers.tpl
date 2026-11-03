<!--
Servers (admin/servers.php): every server's last report, and updating them to a release.
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<table class="content_table">
					<tr>
						<td class="subheading" colspan="6">Servers</td>
					</tr>
					<tr>
						<td class="header wrap" colspan="6">Each server reports every minute. One that stops reporting is called out after five minutes and drops off after a week.</td>
					</tr>
					{if $servers_message}<tr><td class="light-centered" colspan="6">{if $servers_problem}<font color="Red">{/if}{$servers_message|escape:'html':'UTF-8':false}{if $servers_problem}</font>{/if}</td></tr>{/if}
					<tr class="header">
						<td>Server</td><td>Version</td><td>Last report</td><td>Load, memory and disks</td><td>Services</td><td></td>
					</tr>
					{foreach $servers as $s}
					<tr class="{cycle values='dark,light'}">
						<td><strong>{$s.node|escape:'html':'UTF-8':false}</strong>{if $s.php}<br><small>PHP {$s.php|escape:'html':'UTF-8':false}</small>{/if}</td>
						<td>{$s.version|escape:'html':'UTF-8':false}{if $s.update}<br><small>{if $s.updating}<strong>{/if}{$s.update|escape:'html':'UTF-8':false}{if $s.updating}</strong>{/if}</small>{/if}</td>
						<td>{$s.seen|escape:'html':'UTF-8':false}</td>
						<td><small>{if $s.load}load {$s.load|escape:'html':'UTF-8':false}<br>{/if}{if $s.memory}{$s.memory|escape:'html':'UTF-8':false}<br>{/if}{foreach $s.disks as $d}{$d|escape:'html':'UTF-8':false}<br>{/foreach}</small></td>
						<td><small>{foreach $s.services as $name => $state}{$name|escape:'html':'UTF-8':false}: {if $state != 'active' && $state != 'off'}<font color="Red">{/if}{$state|escape:'html':'UTF-8':false}{if $state != 'active' && $state != 'off'}</font>{/if}<br>{/foreach}</small></td>
						<td><form method="post" action="{$wifidb_host_url}admin/servers.php"><input type="hidden" name="action" value="forget"><input type="hidden" name="node" value="{$s.node|escape:'html':'UTF-8':false}"><input type="submit" value="Forget"></form></td>
					</tr>
					{if $s.warnings}
					<tr class="light"><td></td><td colspan="5"><font color="Red">{foreach $s.warnings as $w}{$w|escape:'html':'UTF-8':false}{if !$w@last}; {/if}{/foreach}</font></td></tr>
					{/if}
					{foreachelse}
					<tr class="light"><td colspan="6">No server has reported yet. Each one reports from cron (tools/cron/server_status) once its database has migration 0008.</td></tr>
					{/foreach}
				</table>
				<br>
				<table class="content_table">
					<tr>
						<td class="subheading" colspan="2">Updates</td>
					</tr>
					<tr>
						<td class="header wrap" colspan="2">This server runs {$servers_running|escape:'html':'UTF-8':false}. An update goes to every server, one at a time: each downloads the release's update package, checks it against the release's SHA256SUMS, waits for its running jobs, installs it, applies the database migrations and checks the site answers, and puts its old version back if it doesn't. Only Proxmox containers update this way (docs/updates.md).</td>
					</tr>
					{if $servers_request}
					<tr class="dark">
						<td width="25%"><strong>Asked for</strong></td>
						<td>v{$servers_request.version|escape:'html':'UTF-8':false}, by {$servers_request.requested_by|escape:'html':'UTF-8':false} at {$servers_request.requested_at|escape:'html':'UTF-8':false} UTC{if $servers_request.backup}, after a backup{/if}
							<form method="post" action="{$wifidb_host_url}admin/servers.php" style="display:inline"><input type="hidden" name="action" value="cancel"> <input type="submit" value="Call it off"></form></td>
					</tr>
					{/if}
					{if $servers_releases_error}<tr class="light"><td colspan="2"><font color="Red">The servers couldn't read the release list: {$servers_releases_error|escape:'html':'UTF-8':false}</font></td></tr>{/if}
					{if $servers_releases}
					<tr class="light">
						<td><strong>Update to</strong></td>
						<td>
							<form method="post" action="{$wifidb_host_url}admin/servers.php">
								<input type="hidden" name="action" value="update">
								<select name="version">
									{foreach $servers_releases as $rel}<option value="{$rel.version|escape:'html':'UTF-8':false}">v{$rel.version|escape:'html':'UTF-8':false}{if $rel.prerelease} (pre-release){/if}{if $rel.date} — {$rel.date|truncate:10:'':true|escape:'html':'UTF-8':false}{/if}</option>{/foreach}
								</select>
								<label><input type="checkbox" name="backup"> back up the site first</label>
								<input type="submit" value="Update every server">
							</form>
							<small>The newest is first. A backup of a big database takes a long time and a lot of disk; skip it if the database is backed up another way.</small>
						</td>
					</tr>
					{elseif !$servers_releases_error}
					<tr class="light"><td colspan="2">No release list yet: each server's updater reads it hourly (or run <code>wifidb-update --releases</code> on one).</td></tr>
					{/if}
				</table>
			</div>
{include file="footer.tpl"}
