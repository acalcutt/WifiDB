<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
        <title>Wireless DataBase  *Alpha* 0.21 Build 2  --> Main Page</title>
        <meta name="description" content="A Wireless Database based off of scans from Vistumbler." /><meta name="keywords" content="WiFiDB, linux, windows, vistumbler, Wireless, database, db, php, mysql" />        <link rel="stylesheet" href="https://live.wifidb.net/wifidb/themes/wifidb/styles.css"/>
    </head>
    <body>
    <div align="center">
    <table width="100%" border="0" cellspacing="5" cellpadding="2">
        <tr style="background-color: #315573;">
            <td colspan="2">
                <table>
                    <tr>
                        <td style="width: 215px">
                            <a href="https://live.wifidb.net/wifidb"><img border="0" src="https://live.wifidb.net/wifidb/themes/wifidb/img/logo.png"></a>
                        </td>
                        <td width="100%" align="center">
                            <b>
                            <font style="size: 5;font-family: Arial;color: #FFFFFF;">
                                Wireless DataBase *Alpha* 0.21 Build 2
                            </font>
                            <br />
                            <br />
                            <a class="links" href="/" title="Root">[ Root ]</a> / 
                            <a class="links" href="/wifidb/">[ Wifidb ]</a> / 
                            <font size="2" style="font-family: Arial;color: #FFFFFF;">
                                <strong>Current Page</strong>
                            <font>
                        </td>
                        <td>
                            <img alt="" src="/wifidb/themes/wifidb/img/1x1_transparent.gif" width="130" height="1" />
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
        <!-------- Start Custom Navigation ------>
        <!--{include file="navigation.tpl"}-->
        <!-------- End Custom Navigation ------>
        <td style="background-color: #A9C6FA;width: 80%;vertical-align: top;" align="center">
        <table width="100%">
            <tr>
                <!-------- WiFiDB Login Bar ------>
                <td align="left">
                    {$wifidb_login_html|default:""}
                </td>
                <td align="right">
                    <a class="links" href="{$wifidb_host_url}login.php?{$wifidb_current_uri}">{$wifidb_login_label|default:'login'}</a>
                </td>
                <!-------------------------------->
            </tr>
        </table>
        <p align="center">
        <br>