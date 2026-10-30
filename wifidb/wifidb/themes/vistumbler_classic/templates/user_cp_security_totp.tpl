<!--
Add an authenticator app (cp/security.php?func=totp_add).
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<form method="post" action="{$wifidb_host_url}cp/security.php?func=totp_finish">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Add an authenticator app</td>
						</tr>
						<tr>
							<td class="header" colspan="2">Scan this with an authenticator app (Aegis, Google Authenticator, 1Password, ...), or type the key in, then enter the 6 digits it shows.</td>
						</tr>
						{if $totp_error}<tr><td class="light-centered" colspan="2"><font color="Red">That code did not match. Try the newest one.</font></td></tr>{/if}
						<tr class="dark">
							<td colspan="2" align="center"><div id="wifidb_totp_qr"></div></td>
						</tr>
						<tr class="light">
							<td>Key</td>
							<td><code>{$totp_secret|escape:'html':'UTF-8':false}</code></td>
						</tr>
						<tr class="dark">
							<td>Name (so you can tell them apart)</td>
							<td><input type="text" name="name" maxlength="100" placeholder="Phone"></td>
						</tr>
						<tr class="light">
							<td>Code</td>
							<td><input type="text" name="code" autocomplete="one-time-code" inputmode="numeric" required autofocus></td>
						</tr>
						<tr class="light-centered">
							<td colspan="2"><input type="submit" value="Add"> <a href="{$wifidb_host_url}cp/security.php">Cancel</a></td>
						</tr>
					</table>
				</form>
			</div>
<script src="{$wifidb_host_url}lib/js/qrcode-generator/qrcode.js"></script>
<script src="{$wifidb_host_url}lib/js/wifidb/qrcode.js"></script>
<script>
	window.makeQRCode(document.getElementById('wifidb_totp_qr'), '{$totp_uri|escape:'javascript'}', {literal}{ width: 200, height: 200 }{/literal});
</script>
{include file="footer.tpl"}
