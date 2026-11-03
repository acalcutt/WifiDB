<!--
Preferences -> Security: authenticator apps, passkeys, recovery codes, linked accounts. See cp/security.php.
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
						<td class="subheading" colspan="5">Two-factor sign-in</td>
					</tr>
					<tr>
						<td class="header wrap" colspan="5">
							{if $security_has}
								Signing in {if $security_asks_login}asks{else}does not ask{/if} for a second factor. Turning on admin mode always does.
							{else}
								Add an authenticator app or a passkey and signing in will ask for it after the password.
							{/if}
						</td>
					</tr>
					{foreach item=m from=$security_methods name=methods}
					<tr class="{if $smarty.foreach.methods.iteration is odd}dark{else}light{/if}">
						<td>{if $m.type eq 'passkey'}Passkey{else}Authenticator app{/if}</td>
						<td>{$m.name|escape:'html':'UTF-8':false}</td>
						<td>Added {$m.created|escape:'html':'UTF-8':false} UTC</td>
						<td>{if $m.last_used}Last used {$m.last_used|escape:'html':'UTF-8':false} UTC{else}Not used yet{/if}</td>
						<td>
							<form method="post" action="{$wifidb_host_url}cp/security.php?func=remove" style="display:inline">
								<input type="hidden" name="id" value="{$m.id|escape:'html':'UTF-8':false}">
								<input type="password" name="password" placeholder="Your password" autocomplete="current-password" required>
								<input type="submit" value="Remove">
							</form>
						</td>
					</tr>
					{/foreach}
					<tr>
						<td class="light-centered" colspan="5">
							<a href="{$wifidb_host_url}cp/security.php?func=totp_add">Add an authenticator app</a>
							<span id="wifidb_passkey_area" hidden>
								| <input type="text" id="wifidb_passkey_name" placeholder="Name this passkey" maxlength="100"> <button type="button" id="wifidb_passkey_add">Add a passkey</button>
							</span>
							<div id="wifidb_passkey_msg"></div>
							<div id="wifidb_passkey_codes" hidden>
								<p><b>Your recovery codes.</b> Each works once if you lose your device. They are shown only now; keep them somewhere safe.</p>
								<pre id="wifidb_passkey_codes_list"></pre>
								<p><a href="{$wifidb_host_url}cp/security.php">Done</a></p>
							</div>
						</td>
					</tr>
				</table>
				{if $security_has}
				<form method="post" action="{$wifidb_host_url}cp/security.php?func=settings">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">When to ask</td>
						</tr>
						<tr class="dark">
							<td><label for="mfa_login">Ask when I sign in</label></td>
							<td align="center"><input id="mfa_login" name="mfa_login" type="checkbox"{if $security_asks_login} checked{/if}></td>
						</tr>
						<tr class="light">
							<td><label for="mfa_prefs">Ask before I change my preferences</label></td>
							<td align="center"><input id="mfa_prefs" name="mfa_prefs" type="checkbox"{if $security_asks_prefs} checked{/if}></td>
						</tr>
						{if $security_asks_login}
						<tr class="dark">
							<td>Your password, to turn the sign-in question off</td>
							<td align="center"><input type="password" name="password" autocomplete="current-password"></td>
						</tr>
						{/if}
						<tr class="light-centered">
							<td colspan="2"><input type="submit" value="Save"></td>
						</tr>
					</table>
				</form>
				<form method="post" action="{$wifidb_host_url}cp/security.php?func=codes">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Recovery codes</td>
						</tr>
						<tr class="dark">
							<td>You have {$security_codes_left} unused recovery code{if $security_codes_left neq 1}s{/if}. Making new ones cancels the old.</td>
							<td align="center"><input type="password" name="password" placeholder="Your password" autocomplete="current-password" required> <input type="submit" value="Make new codes"></td>
						</tr>
					</table>
				</form>
				{/if}
				{if $security_linked || $security_linkable}
				<table class="content_table">
					<tr>
						<td class="subheading" colspan="4">Linked accounts</td>
					</tr>
					<tr>
						<td class="header wrap" colspan="4">
							{if $security_linked_now}Your {$security_linked_now|escape:'html':'UTF-8':false} account is linked. {/if}
							Sign in with a linked account instead of your password. Signing in still asks for a second factor if you have it on.
						</td>
					</tr>
					{foreach item=l from=$security_linked name=linked}
					<tr class="{if $smarty.foreach.linked.iteration is odd}dark{else}light{/if}">
						<td>{$l.name|escape:'html':'UTF-8':false}</td>
						<td>{if $l.display_name}{$l.display_name|escape:'html':'UTF-8':false}{/if}{if $l.display_name && $l.email} {/if}{if $l.email}({$l.email|escape:'html':'UTF-8':false}){/if}</td>
						<td>Linked {$l.created|escape:'html':'UTF-8':false} UTC{if $l.last_used}, last used {$l.last_used|escape:'html':'UTF-8':false} UTC{/if}</td>
						<td>
							<form method="post" action="{$wifidb_host_url}cp/security.php?func=unlink" style="display:inline">
								<input type="hidden" name="provider" value="{$l.provider|escape:'html':'UTF-8':false}">
								<input type="password" name="password" placeholder="Your password" autocomplete="current-password" required>
								<input type="submit" value="Unlink">
							</form>
						</td>
					</tr>
					{/foreach}
					{foreach item=p from=$security_linkable name=linkable}
					<tr class="{if $smarty.foreach.linkable.iteration is odd}light{else}dark{/if}">
						<td>{$p.name|escape:'html':'UTF-8':false}</td>
						<td colspan="2">Not linked</td>
						<td>
							<form method="post" action="{$wifidb_host_url}cp/security.php?func=link" style="display:inline">
								<input type="hidden" name="provider" value="{$p.id|escape:'html':'UTF-8':false}">
								<input type="password" name="password" placeholder="Your password" autocomplete="current-password" required>
								<input type="submit" value="Link a {$p.name|escape:'html':'UTF-8':false} account">
							</form>
						</td>
					</tr>
					{/foreach}
				</table>
				{/if}
			</div>
<script src="{$wifidb_host_url}lib/js/wifidb/passkeys.js"></script>
{literal}
<script>
(function () {
	var area = document.getElementById('wifidb_passkey_area');
	var add = document.getElementById('wifidb_passkey_add');
	var msg = document.getElementById('wifidb_passkey_msg');
	if (!area || !window.WifiDBPasskeys || !WifiDBPasskeys.supported()) { return; }
	area.hidden = false;
	add.addEventListener('click', function () {
		msg.textContent = 'Waiting for your device...';
		WifiDBPasskeys.register('security.php?func=passkey_begin', 'security.php?func=passkey_finish',
			document.getElementById('wifidb_passkey_name').value)
		.then(function (answer) {
			if (!answer || !answer.ok) {
				msg.textContent = answer && answer.error === 'already_added' ? 'That passkey is already added.' : 'The passkey was not added.';
				return;
			}
			if (answer.recovery_codes) {
				document.getElementById('wifidb_passkey_codes_list').textContent = answer.recovery_codes.join('\n');
				document.getElementById('wifidb_passkey_codes').hidden = false;
				msg.textContent = 'Passkey added. Two-factor sign-in is on.';
			} else { window.location = 'security.php'; }
		})
		.catch(function () { msg.textContent = 'The passkey was not added.'; });
	});
})();
</script>
{/literal}
{include file="footer.tpl"}
