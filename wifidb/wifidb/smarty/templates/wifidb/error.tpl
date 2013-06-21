<!--
Error.tpl, Is the default error showing page for WiFiDB.
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
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
    <title>Wireless DataBase  *Alpha*  --> Error Page</title>
    <meta name="description" content="A Wireless Database based off of scans from Vistumbler." /><meta name="keywords" content="WiFiDB, linux, windows, vistumbler, Wireless, database, db, php, mysql" />        <link rel="stylesheet" href="https://live.wifidb.net/wifidb/themes/wifidb/styles.css"/>
</head>
<body>
    <table width="100%" border="0" cellspacing="5" cellpadding="2">
        <tr style="background-color: #315573;">
            <td>
                <table>
                    <tr>
                        <td style="width: 215px">
                            <a href="{$wifidb_host_url}">
                                <img border="0" src="{$wifidb_host_url}themes/wifidb/img/logo.png">
                            </a>
                        </td>
                        <td width="100%" align="center">
                            <b>
                                <font style="size: 5;font-family: Arial;color: #FFFFFF;">
                                    Wireless DataBase *Alpha*  --> Error Page
                                </font>
                        </td>
                        <td>
                            <img src="{$wifidb_host_url}themes/wifidb/img/1x1_transparent.gif" width="130" height="1" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <tr>
            <td style="background-color: #A9C6FA;vertical-align: top;" align="center">
                <table width="100%">
                    <tr>
                        <td>
                        </td>
                    </tr>
                </table>
                <p align="center">
                    <br>
                    <table border='1'>
                        <tr>
                            <td class="dark">
                                Error:
                            </td>
                            <td class="light">
                                {$wifidb_error_mesg.Error}
                            </td>
                        </tr>
                        <tr>
                            <td class="dark">
                                Message:
                            </td>
                            <td class="light">
                                {$wifidb_error_mesg.Message}
                            </td>
                        </tr>
                        <tr>
                            <td class="dark">
                                Code:
                            </td>
                            <td class="light">
                                {$wifidb_error_mesg.Code}
                            </td>
                        </tr>
                        <tr>
                            <td class="dark">
                                File:
                            </td>
                            <td class="light">
                                {$wifidb_error_mesg.File}
                            </td>
                        </tr>
                        <tr>
                            <td class="dark">
                                Line:
                            </td>
                            <td class="light">
                                {$wifidb_error_mesg.Line}
                            </td>
                        </tr>
                    </table>
                </p>
                <br>
            </td>
        </tr>
        <tr>
            <td bgcolor="#315573" height="23"></td>
        </tr>
    </table>
</body>
</html>