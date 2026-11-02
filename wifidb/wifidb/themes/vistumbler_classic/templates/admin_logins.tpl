<!--
Sign-in providers (admin/logins.php): which outside accounts members can sign in with.
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<form method="post" action="{$wifidb_host_url}admin/logins.php" autocomplete="off">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Sign-in providers</td>
						</tr>
						<tr>
							<td class="header" colspan="2">Members can link a Google, GitHub, Discord or Facebook account under Preferences, Security, and then sign in with it instead of their password (still with their second factor, if they have it on).
								Nothing is matched by e-mail address: only an account a member linked signs in to theirs.
								To turn one on, make an OAuth app in that provider's console, give it the address below as its redirect URI (authorized redirect URI, callback URL), and save its client ID and secret here.</td>
						</tr>
						{if $settings_message}<tr><td class="light-centered" colspan="2">{if $settings_problem}<font color="Red">{/if}{$settings_message|escape:'html':'UTF-8':false}{if $settings_problem}</font>{/if}</td></tr>{/if}
						{if $settings_unencrypted}<tr><td class="light-centered" colspan="2"><font color="Red">two_factor_key is not set in config.inc.php, so a secret saved here is stored unencrypted.</font></td></tr>{/if}
						{foreach $login_providers as $p}
						<tr>
							<td class="subheading" colspan="2">{$p.name|escape:'html':'UTF-8':false}{if $p.on} (on){/if}</td>
						</tr>
						<tr class="dark">
							<td width="35%"><strong>Redirect URI</strong><br><small><a href="{$p.console|escape:'html':'UTF-8':false}" target="_blank" rel="noopener">{$p.name|escape:'html':'UTF-8':false}'s console</a></small></td>
							<td><code>{$p.callback|escape:'html':'UTF-8':false}</code></td>
						</tr>
						<tr class="light">
							<td><label for="{$p.enabled.name}"><strong>{$p.enabled.label|escape:'html':'UTF-8':false}</strong></label></td>
							<td><input id="{$p.enabled.name}" name="{$p.enabled.name}" type="checkbox"{if $p.enabled.value} checked{/if}></td>
						</tr>
						<tr class="dark">
							<td><label for="{$p.client_id.name}"><strong>{$p.client_id.label|escape:'html':'UTF-8':false}</strong></label></td>
							<td><input id="{$p.client_id.name}" name="{$p.client_id.name}" type="text" size="50" value="{$p.client_id.value|escape:'html':'UTF-8':false}"></td>
						</tr>
						<tr class="light">
							<td><label for="{$p.secret.name}"><strong>{$p.secret.label|escape:'html':'UTF-8':false}</strong></label></td>
							<td>
								<input id="{$p.secret.name}" name="{$p.secret.name}" type="password" value="" size="50" placeholder="{if $p.secret.is_set}Set. Leave blank to keep it{else}Not set{/if}">
								{if $p.secret.is_set}<label><input name="clear_{$p.secret.name}" type="checkbox"> Remove it</label>{/if}
							</td>
						</tr>
						{/foreach}
						<tr class="light-centered">
							<td colspan="2"><input type="submit" value="Save"></td>
						</tr>
					</table>
				</form>
			</div>
{include file="footer.tpl"}
