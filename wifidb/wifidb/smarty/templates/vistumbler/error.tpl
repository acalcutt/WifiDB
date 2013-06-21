<!--
Error.tpl, Is the default error showing page for WiFiDB.
Copyright (C) 2013 Phil Ferland

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
    <link rel="stylesheet" href="/wifidb/themes/vistumbler/styles.css" />
    <title>Wireless DataBase  {$wifidb_version_label}  --&gt; {$wifidb_page_label}</title>
    {$wifidb_meta_header}
    {$redirect_func}
</head>
<body style="background-color: #145285" {$redirect_html}>
{$install_header}
{$wifidb_announce_header}

<table style="width: 90%; " class="no_border" align="center">
    <tr>
        <td>
            <table>
                <tr>
                    <td style="width: 228px">
                        <a href="http://www.wifidb.net">
                            <img alt="Random Intervals Logo" src="{$wifidb_host_url}themes/vistumbler/img/logo.png" class="no_border" />
                        </a>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>
<table style="width: 90%" align="center">
    <tr>
        <td style="height: 114px" valign="top" class="center">
            <table style="width: 100%" cellpadding="0" cellspacing="0">
                <tr>
                    <td style="width: 10px; height: 20px" class="cell_top_left">
                        <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                    </td>
                    <td class="cell_top_mid" style="height: 20px" align="left">
                    </td>
                    <td class="cell_top_mid" style="height: 20px" align="right">
                    </td>
                    <td style="width: 10px" class="cell_top_right">
                        <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                    </td>
                </tr>
                <tr>
                    <td class="cell_side_left">&nbsp;</td>
                    <td class="cell_color_centered" align="center" colspan="2">
                        <div align="center">
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
                        </div>
                        <br>
                    </td>
                    <td class="cell_side_right">&nbsp;</td>
                </tr>
                <tr>
                    <td class="cell_side_left">&nbsp;</td>
                    <td colspan="2" class="cell_color_centered"></td>
                    <td class="cell_side_right">&nbsp;</td>
                </tr>
                <tr>
                    <td class="cell_bot_left">&nbsp;</td>
                    <td class="cell_bot_mid" colspan="2" align="center">&nbsp;</td>
                    <td class="cell_bot_right">&nbsp;</td>
                </tr>
            </table>
            <div class="inside_text_center" align=center>
                <strong>
                    Random Intervals Wireless DataBase<br />
                </strong>
            </div>
            <br />
        </td>
    </tr>
</table>
</body>
</html>