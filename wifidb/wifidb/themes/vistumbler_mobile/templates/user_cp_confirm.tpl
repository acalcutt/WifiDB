<!--
Confirm a second factor before preferences are saved (cp/confirm.php).
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<form method="post" action="{$wifidb_host_url}cp/confirm.php?func=proc&amp;return={$confirm_return|escape:'url'}">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Confirm it is you</td>
						</tr>
						<tr>
							<td class="header" colspan="2">You asked to be checked before your preferences are saved. Enter a code, or use a passkey; your preferences then stay open for 10 minutes.</td>
						</tr>
						{if $confirm_error}<tr><td class="light-centered" colspan="2"><font color="Red">{$confirm_error|escape:'html':'UTF-8':false}</font></td></tr>{/if}
						<tr class="dark">
							<td>Code</td>
							<td><input type="text" name="code" autocomplete="one-time-code" inputmode="numeric" autofocus></td>
						</tr>
						<tr class="light-centered">
							<td colspan="2">
								<input type="submit" value="Continue">
								{if $confirm_has_passkey}<button type="button" id="wifidb_confirm_passkey" hidden>Use a passkey</button>{/if}
								<p id="wifidb_confirm_msg"></p>
							</td>
						</tr>
					</table>
				</form>
			</div>
{if $confirm_has_passkey}
<script src="{$wifidb_host_url}lib/js/wifidb/passkeys.js"></script>
<script>
	var wifidbConfirmReturn = '{$confirm_return|escape:'url'}';
{literal}
	(function () {
		var button = document.getElementById('wifidb_confirm_passkey');
		var msg = document.getElementById('wifidb_confirm_msg');
		if (!button || !window.WifiDBPasskeys || !WifiDBPasskeys.supported()) { return; }
		button.hidden = false;
		button.addEventListener('click', function () {
			msg.textContent = 'Waiting for your passkey...';
			WifiDBPasskeys.signIn('confirm.php?func=passkey_begin&return=' + wifidbConfirmReturn, 'confirm.php?func=passkey_finish&return=' + wifidbConfirmReturn, {})
			.then(function (answer) {
				if (answer && answer.ok) { window.location = answer.redirect; }
				else { msg.textContent = 'That passkey did not work.'; }
			})
			.catch(function () { msg.textContent = 'The passkey was not used.'; });
		});
	})();
{/literal}
</script>
{/if}
{include file="footer.tpl"}
