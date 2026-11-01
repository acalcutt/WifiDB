<!--
Backups (admin/backups.php).
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<table class="content_table">
					<tr><td class="subheading">Backups</td></tr>
					<tr><td class="header">A backup holds the whole database (every member, import, access point and setting) and the key that unlocks the saved authenticator apps. One made on SQL Server restores on MariaDB and the other way round, so it also moves a site to a new server. Uploaded files and exports aren't in it.</td></tr>
					{if $backups_message}<tr><td class="light-centered">{if $backups_problem}<font color="Red">{/if}{$backups_message|escape:'html':'UTF-8':false}{if $backups_problem}</font>{/if}</td></tr>{/if}
					{if !$backups_ready}<tr><td class="light-centered"><font color="Red">The backups folder ({$backups_dir|escape:'html':'UTF-8':false}) can't be written, so nothing here can run. Set backup_dir in config.inc.php.</font></td></tr>{/if}
					{if $backups_stale}<tr><td class="light-centered"><font color="Red">A request has waited more than five minutes. Check that cron runs tools/cron/backups each minute.</font></td></tr>{/if}
				</table>

				{if $backups_pending || $backups_job}
				<table class="content_table">
					<tr><td class="subheading">Status</td></tr>
					{foreach $backups_pending as $p}
					<tr class="light"><td>Waiting to start: {$p.action|escape:'html':'UTF-8':false} {$p.name|default:''|escape:'html':'UTF-8':false}, asked for by {$p.requested_by|escape:'html':'UTF-8':false}</td></tr>
					{/foreach}
					{if $backups_job}
					<tr class="dark"><td>
						<strong>{$backups_job.action|escape:'html':'UTF-8':false}</strong> {$backups_job.name|default:''|escape:'html':'UTF-8':false}: {$backups_job.state|escape:'html':'UTF-8':false}
						(started {$backups_job.started|escape:'html':'UTF-8':false} UTC{if $backups_job.finished|default:''}, finished {$backups_job.finished|escape:'html':'UTF-8':false} UTC{/if})
						{if $backups_job.error|default:''}<br><font color="Red">{$backups_job.error|escape:'html':'UTF-8':false}</font>{/if}
						{if $backups_job.log_text|default:''}<pre class="backup-log">{$backups_job.log_text|escape:'html':'UTF-8':false}</pre>{/if}
					</td></tr>
					{/if}
					{if $backups_busy}
					<tr class="light"><td><small>This page reloads every 10 seconds while something runs.</small><script>setTimeout(function () { location.reload(); }, 10000);</script></td></tr>
					{/if}
				</table>
				{/if}

				<form method="post" action="{$wifidb_host_url}admin/backups.php">
					<input type="hidden" name="action" value="export">
					<table class="content_table">
						<tr><td class="subheading">Make a backup</td></tr>
						<tr class="light"><td>The site stays up while it runs, so changes made meanwhile may be only partly in it. It's written to {$backups_dir|escape:'html':'UTF-8':false}. <input type="submit" value="Back up now"></td></tr>
					</table>
				</form>

				<table class="content_table">
					<tr><td class="subheading" colspan="6">Backups on this server</td></tr>
					{if $backups_list}
					<tr class="header"><th>Backup</th><th>Made (UTC)</th><th>Status</th><th>Size</th><th>From</th><th></th></tr>
					{foreach $backups_list as $b}
					<tr class="{cycle values='light,dark'}">
						<td><code>{$b.name|escape:'html':'UTF-8':false}</code>{if $b.kind|default:''}<br><small>{$b.kind|escape:'html':'UTF-8':false}</small>{/if}</td>
						<td>{$b.created|default:''|escape:'html':'UTF-8':false}</td>
						<td>{$b.state|default:''|escape:'html':'UTF-8':false}
							{if ($b.state|default:'') == 'running'}<br><small>{$b.step|default:''|escape:'html':'UTF-8':false}</small>
							{elseif $b.error|default:''}<br><small><font color="Red">{$b.error|escape:'html':'UTF-8':false}</font></small>{/if}</td>
						<td>{$b.size_text|escape:'html':'UTF-8':false}{if $b.rows|default:0}<br><small>{$b.rows|number_format} rows</small>{/if}</td>
						<td>{$b.source.site|default:''|escape:'html':'UTF-8':false}{if $b.source.service|default:''}<br><small>{if $b.source.service == 'sqlsrv'}SQL Server{else}MariaDB{/if}</small>{/if}</td>
						<td>
							{if ($b.state|default:'') == 'done'}
							<a href="{$wifidb_host_url}admin/backups.php?func=download&amp;name={$b.name|escape:'url'}">Download</a>{if $b.sha256|default:''}<br><small title="SHA-256 of the whole file">SHA-256 {$b.sha256|truncate:12:'…'|escape:'html':'UTF-8':false}</small>{/if}
							<form method="post" action="{$wifidb_host_url}admin/backups.php">
								<input type="hidden" name="action" value="restore"><input type="hidden" name="name" value="{$b.name|escape:'html':'UTF-8':false}">
								<input type="text" name="confirm" size="14" placeholder="{$backups_domain|escape:'html':'UTF-8':false}" title="Type this site's domain to confirm">
								<input type="submit" value="Restore">
							</form>
							{/if}
							{if ($b.state|default:'') != 'running'}
							<form method="post" action="{$wifidb_host_url}admin/backups.php">
								<input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="{$b.name|escape:'html':'UTF-8':false}">
								<input type="submit" value="Delete">
							</form>
							{/if}
						</td>
					</tr>
					{/foreach}
					<tr><td colspan="6"><small>Restore replaces all of this site's data with the backup and signs everyone out; afterwards you sign in with an account from the backup. Type this site's domain to confirm. The scheduled imports and exports wait until it's done.</small></td></tr>
					{else}
					<tr class="light"><td colspan="6">No backups yet.</td></tr>
					{/if}
				</table>

				<table class="content_table">
					<tr><td class="subheading">From the command line</td></tr>
					<tr class="light"><td><pre class="backup-log">php tools/utilites/backup.php export
php tools/utilites/backup.php list
php tools/utilites/backup.php restore wifidb-backup-YYYYMMDD-HHMMSS --yes</pre>
						<small>A downloaded backup can be copied (scp or rsync) into {$backups_dir|escape:'html':'UTF-8':false} on another server: it shows up here and restores the same way. See docs/backups.md.</small></td></tr>
				</table>
			</div>
{include file="footer.tpl"}
