<!--
Turn on admin mode (cp/admin_mode.php).
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				{if !$admin_has_two_factor}
				<table class="content_table">
					<tr>
						<td class="subheading">Admin mode</td>
					</tr>
					<tr class="light">
						<td>Admin actions need an authenticator app or a passkey on your account, so there is something to prove it is you at the moment you use them.
							<a href="{$wifidb_host_url}cp/security.php">Add one under Preferences, Security</a>, then come back.</td>
					</tr>
				</table>
				{else}
				<form method="post" action="{$wifidb_host_url}cp/admin_mode.php?func=proc&amp;return={$admin_return|escape:'url'}">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Admin mode</td>
						</tr>
						<tr>
							<td class="header" colspan="2">Admin actions (resetting and deleting imports, daemons and schedules) need a fresh code each time you turn admin mode on. A code that signed you in will not work here. It stays on until you have not used an admin page for {$admin_minutes} minutes, or you leave it.</td>
						</tr>
						{if $admin_error}<tr><td class="light-centered" colspan="2"><font color="Red">{$admin_error|escape:'html':'UTF-8':false}</font></td></tr>{/if}
						<tr class="dark">
							<td>Code</td>
							<td><input type="text" name="code" autocomplete="one-time-code" inputmode="numeric" autofocus></td>
						</tr>
						<tr class="light-centered">
							<td colspan="2">
								<input type="submit" value="Turn on admin mode">
								{if $admin_has_passkey}<button type="button" id="wifidb_admin_passkey" hidden>Use a passkey</button>{/if}
								<p id="wifidb_admin_msg"></p>
							</td>
						</tr>
					</table>
				</form>
				{/if}
			</div>
{if $admin_has_two_factor && $admin_has_passkey}
<script src="{$wifidb_host_url}lib/js/wifidb/passkeys.js"></script>
<script>
	var wifidbAdminReturn = '{$admin_return|escape:'url'}';
{literal}
	(function () {
		var button = document.getElementById('wifidb_admin_passkey');
		var msg = document.getElementById('wifidb_admin_msg');
		if (!button || !window.WifiDBPasskeys || !WifiDBPasskeys.supported()) { return; }
		button.hidden = false;
		button.addEventListener('click', function () {
			msg.textContent = 'Waiting for your passkey...';
			WifiDBPasskeys.signIn('admin_mode.php?func=passkey_begin&return=' + wifidbAdminReturn, 'admin_mode.php?func=passkey_finish&return=' + wifidbAdminReturn, {})
			.then(function (answer) {
				if (answer && answer.ok) { window.location = answer.redirect; }
				else { msg.textContent = 'That passkey did not work. It has to check who you are too (a PIN or fingerprint).'; }
			})
			.catch(function () { msg.textContent = 'The passkey was not used.'; });
		});
	})();
{/literal}
</script>
{/if}
{include file="footer.tpl"}
