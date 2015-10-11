{include file="header.tpl"}
                                    <form method="post" action="/wifidb/login.php?func=reset_password_request_proc">
                                        <table align="center">
                                            <tbody>
                                                <tr>
                                                    <td colspan="2"><p align="center"><img src="{$wifidb_host_url}themes/{$wifidb_theme}/img/logo.png"></p></td>
                                                </tr>
                                                <tr>
                                                    <td>Username</td>
                                                    <td><input type="text" name="username_f"></td>
                                                </tr>
                                                <tr>
                                                    <td>E-mail Address</td>
                                                    <td><input type="text" name="email_f"></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <p align="center">
                                                            <input type="hidden" name="return" value="{$wifidb_host_url}index.php">
                                                            <input type="submit" value="Reset Password">
                                                        </p>
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </form>
{include file="footer.tpl"}