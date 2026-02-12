{include file="header.tpl"} 
                                    <font color="green"><h2>Create User</h2></font>
                                    <h3>{$message}</h3>
                                        <form method="post" action="{$wifidb_host_url}login.php?func=create_user_proc">
                                            <table align="center">
                                                <tr>
                                                    <td colspan="2"><p align="center"><img src="{$themeurl}img/logo.png"></p></td>
                                                </tr>
                                                <tr>
                                                    <td>Username</td>
                                                    <td><input type="text" name="time_user" value=""></td>
                                                </tr>
                                                <tr>
                                                    <td>Password</td>
                                                    <td><input type="password" name="time_pass"></td>
                                                </tr>
                                                <tr>
                                                    <td>Password (again)</td>
                                                    <td><input type="password" name="time_pass2"></td>
                                                </tr>
                                                <tr>
                                                    <td>Email</td>
                                                    <td><input type="text" name="time_email" value=""></td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2">
                                                        <label>
                                                            <input type="checkbox" name="agree_terms" value="yes" required>
                                                            I agree to the <a href="{$wifidb_host_url}terms.php" target="_blank">Terms of Use</a> and <a href="{$wifidb_host_url}privacy.php" target="_blank">Privacy Policy</a>
                                                        </label>
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td colspan="2"><p align="center"><input type="submit" value="Create Me!"></p></td>
                                                </tr>
                                            </table>
                                        </form>
{include file="footer.tpl"}