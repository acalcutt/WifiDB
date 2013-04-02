{include file="header.tpl"} 
                                    <font color="green"><h2>Create User</h2></font>
                                        <form method="post" action="/wifidb/login.php?func=create_user_proc">
                                            <table align="center">
                                                <tr>
                                                    <td colspan="2"><p align="center"><img src="themes/wifidb/img/logo.png"></p></td>
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
                                                    <td colspan="2"><p align="center"><input type="submit" value="Create Me!"></p></td>
                                                </tr>
                                            </table>
                                        </form>
{include file="footer.tpl"}