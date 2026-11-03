<!--
Mail (admin/mail.php): how the site sends mail and signs it, a check of the settings and DNS, and a test message.
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
{function name=mail_rows}
						{foreach $rows as $s}
						<tr class="{cycle values='dark,light'}">
							<td width="35%"><label for="{$s.name}"><strong>{$s.label|escape:'html':'UTF-8':false}</strong></label><br>
								<small>{if $s.source == 'saved'}Saved here{elseif $s.source == 'config'}From config.inc.php{else}Default{/if}</small></td>
							<td>
								{if $s.type == 'bool'}
								<input id="{$s.name}" name="{$s.name}" type="checkbox"{if $s.value} checked{/if}>
								{elseif $s.type == 'text'}
								<input id="{$s.name}" name="{$s.name}" type="text" size="40" value="{$s.value|escape:'html':'UTF-8':false}">
								{elseif $s.type == 'choice'}
								{foreach $s.options as $option => $optionLabel}
								<label><input name="{$s.name}" type="radio" value="{$option|escape:'html':'UTF-8':false}"{if $s.value == $option} checked{/if}> {$optionLabel|escape:'html':'UTF-8':false}</label><br>
								{/foreach}
								{elseif $s.name == 'DKIM_private_string'}
								<textarea id="{$s.name}" name="{$s.name}" rows="4" cols="60" placeholder="{if $s.is_set}Set. Leave blank to keep it{else}Not set: make one below, or paste a private key (PEM){/if}"></textarea>
								{if $s.is_set}<br><label><input name="clear_{$s.name}" type="checkbox"> Remove it</label>{/if}
								{else}
								<input id="{$s.name}" name="{$s.name}" type="password" value="" size="40" placeholder="{if $s.is_set}Set. Leave blank to keep it{else}Not set{/if}">
								{if $s.is_set}<label><input name="clear_{$s.name}" type="checkbox"> Remove it</label>{/if}
								{/if}
								{if $s.help}<br><small>{$s.help|escape:'html':'UTF-8':false}</small>{/if}
							</td>
						</tr>
						{/foreach}
{/function}
			<div class="main">
				{include file="topmenu.tpl"}
				<form method="post" action="{$wifidb_host_url}admin/mail.php" autocomplete="off">
					<input type="hidden" name="func" value="save">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Mail</td>
						</tr>
						<tr>
							<td class="header wrap" colspan="2">How the site sends mail: sign-up confirmations, password resets, and the notifications members ask for.
								These start from config.inc.php. Once saved here, the value here is used, on every server. Every change leaves the administrators a notice.
								{if $mail_validation}New accounts confirm their e-mail (Site Settings), so without working mail nobody can finish signing up.{/if}</td>
						</tr>
						{if $settings_message}<tr><td class="light-centered" colspan="2">{if $settings_problem}<font color="Red">{/if}{$settings_message|escape:'html':'UTF-8':false}{if $settings_problem}</font>{/if}</td></tr>{/if}
						{if $settings_unencrypted}<tr><td class="light-centered" colspan="2"><font color="Red">two_factor_key is not set in config.inc.php, so a password or key saved here is stored unencrypted.</font></td></tr>{/if}
						<tr>
							<td class="subheading" colspan="2">Mail account</td>
						</tr>
						<tr>
							<td class="header wrap" colspan="2">The account the site's mail goes out from.</td>
						</tr>
						{call name=mail_rows rows=$mail_main}
						<tr>
							<td class="subheading" colspan="2">Sign-up account (if wanted)</td>
						</tr>
						<tr>
							<td class="header wrap" colspan="2">Sign-up confirmations and password resets can go out from another account, so the notifications don't use up its sending limit. Anything left empty here is the mail account's.</td>
						</tr>
						{call name=mail_rows rows=$mail_signup}
						<tr>
							<td class="subheading" colspan="2">Signing in with a Google account (OAuth)</td>
						</tr>
						<tr>
							<td class="header wrap" colspan="2">For Gmail or Google Workspace, instead of an app password: make an OAuth client (a Web application) in a Google Cloud project, with the scope https://mail.google.com/ on its consent screen and the address below as its authorized redirect URI, and save its client ID and secret here; then connect the account below.
								For Workspace, make the consent screen Internal. For a gmail.com account, publish it (In production): one left in Testing gets tokens that stop working after 7 days.</td>
						</tr>
						<tr class="dark">
							<td width="35%"><strong>Authorized redirect URI</strong></td>
							<td><code>{$mail_oauth.redirect|escape:'html':'UTF-8':false}</code></td>
						</tr>
						{call name=mail_rows rows=$mail_oauth_settings}
						<tr>
							<td class="subheading" colspan="2">Signing (DKIM)</td>
						</tr>
						<tr>
							<td class="header wrap" colspan="2">A signature receivers check against a record in the domain's DNS, so the site's mail isn't taken for spam.
								Gmail and Google Workspace sign the mail they send for you already; this is for any other mail server, or for a signature of the site's own as well.</td>
						</tr>
						{call name=mail_rows rows=$mail_signing}
						<tr class="light-centered">
							<td colspan="2"><input type="submit" value="Save"></td>
						</tr>
					</table>
				</form>
				<table class="content_table">
					<tr>
						<td class="subheading" colspan="2">Google accounts</td>
					</tr>
					{foreach $mail_oauth.accounts as $a}
					<tr class="{cycle values='dark,light'}">
						<td width="35%"><strong>{$a.name|escape:'html':'UTF-8':false}</strong></td>
						<td>
							<form method="post" action="{$wifidb_host_url}admin/mail.php">
								<input type="hidden" name="account" value="{$a.id}">
								{if $a.set}
								Connected as {$a.connected|escape:'html':'UTF-8':false}.
								<input type="hidden" name="func" value="oauth_disconnect">
								<input type="submit" value="Disconnect">
								{else}
								{if $a.id == 'signup'}Not connected: it signs in as the mail account does.{else}Not connected.{/if}
								<input type="hidden" name="func" value="oauth_connect">
								<input type="submit" value="Connect with Google"{if !$mail_oauth.ready} disabled title="Save the OAuth client ID and secret first"{/if}>
								{/if}
							</form>
						</td>
					</tr>
					{/foreach}
				</table>
				<table class="content_table">
					<tr>
						<td class="subheading" colspan="2">Signing key</td>
					</tr>
					{if $mail_dkim.from}
					<tr class="dark">
						<td width="35%"><strong>The key in use</strong></td>
						<td>{if $mail_dkim.from == 'saved'}Saved here{else}The file {$mail_dkim.from|escape:'html':'UTF-8':false} (DKIM_private in config.inc.php){/if}{if $mail_dkim.bits}, {$mail_dkim.bits} bits{/if}</td>
					</tr>
					{if $mail_dkim.record}
					<tr class="light">
						<td><strong>Its DNS record</strong><br><small>Publish it as a TXT record{if $mail_dkim.name} named {$mail_dkim.name|escape:'html':'UTF-8':false}{/if}.</small></td>
						<td><textarea id="dkim_record" readonly rows="5" cols="60">{$mail_dkim.record|escape:'html':'UTF-8':false}</textarea></td>
					</tr>
					{else}
					<tr class="light">
						<td colspan="2"><font color="Red">The key can't be read{if $mail_dkim.from != 'saved'} (or DKIM_passphrase is wrong){/if}, so mail goes out unsigned.</font></td>
					</tr>
					{/if}
					{else}
					<tr class="dark">
						<td colspan="2">There is no signing key, so mail goes out unsigned.</td>
					</tr>
					{/if}
					<tr class="light">
						<td colspan="2">
							<form method="post" action="{$wifidb_host_url}admin/mail.php" autocomplete="off">
								<input type="hidden" name="func" value="make_key">
								Make a 2048-bit key with the selector <input name="selector" type="text" size="16" value="{$mail_dkim.new_selector|escape:'html':'UTF-8':false}">
								{if $mail_dkim.from == 'saved'}<label><input name="replace" type="checkbox"> Replace the key in use</label>{/if}
								<input type="submit" value="Make a key">
								<br><small>It is saved here and used at once; then publish its record. A new selector lets the new record go up beside the old one, which still checks mail already sent.</small>
							</form>
						</td>
					</tr>
				</table>
				<table class="content_table">
					<tr>
						<td class="subheading" colspan="2">Check and test</td>
					</tr>
					<tr class="dark">
						<td width="35%"><strong>Check the settings</strong><br><small>The account, the signing key, and the domain's DKIM, SPF and DMARC records in DNS, as saved.</small></td>
						<td>
							<form method="post" action="{$wifidb_host_url}admin/mail.php">
								<input type="hidden" name="func" value="check">
								<input type="submit" value="Check">
							</form>
						</td>
					</tr>
					{if $mail_results}
					{foreach $mail_results as $result}
					<tr class="light">
						<td>{if $result[0] == 'ok'}<font color="Green">OK</font>{elseif $result[0] == 'warn'}<font color="DarkOrange">Warning</font>{else}<font color="Red">Problem</font>{/if}</td>
						<td>{$result[1]|escape:'html':'UTF-8':false|nl2br}</td>
					</tr>
					{/foreach}
					{/if}
					<tr class="dark">
						<td><strong>Send a test message</strong><br><small>As saved.</small></td>
						<td>
							<form method="post" action="{$wifidb_host_url}admin/mail.php">
								<input type="hidden" name="func" value="test">
								To <input name="to" type="email" size="30" value="{$mail_to|escape:'html':'UTF-8':false}">
								from <label><input name="account" type="radio" value="main"{if !$mail_test || !$mail_test.signup} checked{/if}> the mail account</label>
								<label><input name="account" type="radio" value="signup"{if $mail_test && $mail_test.signup} checked{/if}> the sign-up account</label>
								<input type="submit" value="Send">
							</form>
						</td>
					</tr>
					{if $mail_test}
					<tr class="light">
						<td>{if $mail_test.sent}<font color="Green">Sent</font>{else}<font color="Red">Not sent</font>{/if}</td>
						<td>{if $mail_test.sent}Sent to {$mail_test.to|escape:'html':'UTF-8':false}. In the message's headers, dkim=pass in Authentication-Results means the signature verified.{else}{$mail_test.error|escape:'html':'UTF-8':false}{/if}
							{if $mail_test.lines}<br><small>What the mail server said (the login left out):</small>
							<pre style="white-space: pre-wrap">{$mail_test.lines|escape:'html':'UTF-8':false}</pre>{/if}</td>
					</tr>
					{/if}
				</table>
			</div>
{include file="footer.tpl"}
