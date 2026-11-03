<!--
The second step of signing in (login.php?func=mfa): an authenticator or recovery code, or a passkey.
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
                                    <form method="post" action="{$wifidb_host_url}login.php?func=mfa_proc&amp;return={$mfa_return|escape:'url'}">
                                        <table align="center">
                                            <tbody>
                                                <tr>
                                                    <td colspan="2">
                                                        <p align="center"><img src="{$wifidb_host_url}themes/{$wifidb_theme}/img/logo.png"></p>
                                                        <p align="center">Hello, <b>{$mfa_user|escape:'html':'UTF-8':false}</b>. One more step.</p>
                                                        {if $mfa_error}<p align="center"><font color="Red">{$mfa_error|escape:'html':'UTF-8':false}</font></p>{/if}
                                                        <p id="wifidb_mfa_passkey_msg" align="center"></p>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Code</td>
                                                    <td><input type="text" name="code" id="wifidb_mfa_code" autocomplete="one-time-code" inputmode="numeric" autofocus></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><small>The 6 digits from your authenticator app, or one of your recovery codes.</small></td>
                                                </tr>
                                                {if $mfa_can_remember}
                                                <tr>
                                                    <td colspan="2"><label><input type="checkbox" name="remember" id="wifidb_mfa_remember" value="yes"> Remember this computer for 30 days</label></td>
                                                </tr>
                                                {/if}
                                                <tr>
                                                    <td colspan="2"><p align="center"><input type="submit" value="Continue">
                                                    {if $mfa_has_passkey}<button type="button" id="wifidb_mfa_passkey" hidden>Use a passkey</button>{/if}</p></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <p align="center"><a class="links" href="{$wifidb_host_url}login.php?func=logout&amp;return={$mfa_return|escape:'url'}">Cancel and sign out</a></p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </form>
{if $mfa_has_passkey}
                                    <script src="{$wifidb_host_url}lib/js/wifidb/passkeys.js"></script>
                                    <script>
                                    (function () {
                                        var button = document.getElementById('wifidb_mfa_passkey');
                                        var msg = document.getElementById('wifidb_mfa_passkey_msg');
                                        if (!button || !window.WifiDBPasskeys || !WifiDBPasskeys.supported()) { return; }
                                        button.hidden = false;
                                        button.addEventListener('click', function () {
                                            var remember = document.getElementById('wifidb_mfa_remember');
                                            msg.textContent = 'Waiting for your passkey...';
                                            WifiDBPasskeys.signIn('{$wifidb_host_url}login.php?func=mfa_passkey_begin&return={$mfa_return|escape:'url'}',
                                                '{$wifidb_host_url}login.php?func=mfa_passkey_finish&return={$mfa_return|escape:'url'}',
                                                { remember: !!(remember && remember.checked) })
                                            .then(function (answer) {
                                                if (answer && answer.ok) { window.location = answer.redirect; }
                                                else { msg.textContent = 'That passkey did not work.'; }
                                            })
                                            .catch(function () { msg.textContent = 'The passkey was not used.'; });
                                        });
                                    })();
                                    </script>
{/if}
{include file="footer.tpl"}
