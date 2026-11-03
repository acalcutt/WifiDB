<!--
Signed in with an outside account nobody linked (logins.php): sign in with a password to link it, or sign up.
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
                                    <form method="post" action="{$wifidb_host_url}logins.php?func=link_existing">
                                        <table align="center">
                                            <tbody>
                                                <tr>
                                                    <td colspan="2">
                                                        <h2>Link your {$linked_provider|escape:'html':'UTF-8':false} account</h2>
                                                        <p>The {$linked_provider|escape:'html':'UTF-8':false} account you signed in with{if $linked_who} ({$linked_who|escape:'html':'UTF-8':false}){/if} isn't linked to an account here.
                                                        Sign in with your username and password to link it, and from then on Sign in with {$linked_provider|escape:'html':'UTF-8':false} works too.</p>
                                                        {if $linked_wrong}<p><font color="Red">Bad username or password.</font></p>{/if}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Username</td>
                                                    <td><input type="text" name="time_user" autocomplete="username" required></td>
                                                </tr>
                                                <tr>
                                                    <td>Password</td>
                                                    <td><input type="password" name="time_pass" autocomplete="current-password" required></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><p align="center"><input type="hidden" name="token" value="{$linked_token|escape:'html':'UTF-8':false}"><input type="submit" value="Sign in and link"></p></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <p align="center">
                                                            {if $wifidb_signups_open}New here? <a class="links" href="{$wifidb_host_url}login.php?func=create_user_form">Create an account</a> and it gets this {$linked_provider|escape:'html':'UTF-8':false} account.<br>{/if}
                                                            <a class="links" href="{$wifidb_host_url}logins.php?func=cancel">Cancel</a>
                                                        </p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </form>
{include file="footer.tpl"}
