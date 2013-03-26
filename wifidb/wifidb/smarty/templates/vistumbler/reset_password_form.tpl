{include file="header.tpl"}
                                    <form method="post" action="/wifidb/login.php?func=reset_password_proc">
                                        <table align="center">
                                            <tbody>
                                                <tr>
                                                    <td colspan="2"><p align="center"><img src="{$wifidb_host_url}themes/{$wifidb_theme}/img/logo.png"></p></td>
                                                </tr>
                                                <tr>
                                                    <td>Username</td>
                                                    <td>
                                                        <input type="text" name="username">
                                                        <input type="hidden" name="token" value="{$token}">
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td>Password</td>
                                                    <td><input type="password" name="newpassword"></td>
                                                </tr>
                                                <tr>
                                                    <td>Password (again)</td>
                                                    <td><input type="password" name="newpassword2"></td>
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