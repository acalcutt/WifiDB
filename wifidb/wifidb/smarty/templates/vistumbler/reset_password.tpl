<!--
reset_password.tpl, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
-->
{include file="vistumbler_header.tpl"}
                                    <form method="post" action="/wifidb/login.php?func=reset_password_proc">
                                        <table align="center">
                                            <tbody>
                                                <tr>
                                                    <td colspan="2"><p align="center"><img src="{$wifidb_host_url}themes/wifidb/img/logo.png"></p></td>
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
                                    </div>
                                    <br>
                                    </td>
                                    <td class="cell_side_right">&nbsp;</td>
                                </tr>
{include file="vistumbler_footer.tpl"}