<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
    <head>
        <link rel="stylesheet" href="{$wifidb_host_url}themes/vistumbler/styles.css" />
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
                                <img alt="Random Intervals Logo" src="{$wifidb_host_url}themes/vistumbler/img/logo.png" class="no_border" /></a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <table style="width: 90%" align="center">
            <tr>
                <td style="width: 165px; height: 114px" valign="top">
                    <table style="width: 100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width: 10px; height: 20px" class="cell_top_left">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                            <td class="cell_top_mid" style="height: 20px">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="185" height="1" />
                            </td>
                            <td style="width: 10px" class="cell_top_right">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                        </tr>
                        <tr width="185px">
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color">
                                <div class="inside_dark_header">WiFiDB Links</div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}">Main Page</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}all.php?sort=ModDate&ord=DESC&from=0&to=500">AP List</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=0">AP Map</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}import/">Import</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}opt/scheduling.php?func=done">Imported Files</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}opt/scheduling.php">Files Waiting for Import</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}opt/scheduling.php?func=daemon_kml">Daemon Generated KMZ</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}opt/export.php?func=index">Export</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}opt/search.php">Search</a></strong></div>
                                <!--<div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}themes/">Themes</a></strong></div>-->
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}opt/userstats.php?func=allusers">View All Users</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a class="links" href="http://forum.techidiots.net/forum/viewforum.php?f=47">Help / Support</a></strong></div>
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}ver.php">WiFiDB Version</a></strong></div>
								<!--
                                {if $login_val eq "1"}
                                <div class="inside_text_bold"><strong>
                                    <a href="{$wifidb_host_url}login.php?func=logout&return=%2Fwifidb%2F">Log Out</a></strong></div>
                                {/if}
								-->
                                    
                                <!--=========================-->
                            </td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="cell_bot_left">&nbsp;</td>
                            <td class="cell_bot_mid">&nbsp;</td>
                            <td class="cell_bot_right">&nbsp;</td>
                        </tr>
                        
                        <!-- CUSTOM NAV -->
                        {include file="navigation.tpl"}
                        <!-- END CUSTOM NAV-->
                    </table>
                </td>
                    <td style="height: 114px" valign="top" class="center">
                    <table style="width: 100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td style="width: 10px; height: 20px" class="cell_top_left">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                            <!-- ------ WiFiDB Login Bar ---- -->
                            <td class="cell_top_mid" style="height: 20px" align="left">
                                {$wifidb_login_html|default:""}
                            </td>
                            <td class="cell_top_mid" style="height: 20px" align="right">
                                <a class="links" href="{$wifidb_host_url}login.php{$wifidb_current_uri}">{$wifidb_login_label|default:'login'}</a>
                            </td>
                            <!-- ---------------------------- -->
                            <td style="width: 10px" class="cell_top_right">
                                <img alt="" src="{$wifidb_host_url}themes/vistumbler/img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                        </tr>
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color_centered" align="center" colspan="2">
                                <div align="center">