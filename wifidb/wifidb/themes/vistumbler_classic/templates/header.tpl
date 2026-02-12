<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
    <head>
<!-- Google tag (gtag.js) -->
{literal}
<script async src="https://www.googletagmanager.com/gtag/js?id=G-S1ZD9TQYZK"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('consent', 'default', {
    'ad_storage': 'denied',
    'analytics_storage': 'denied'
  });

{/literal}
  gtag('config', 'G-S1ZD9TQYZK', {ldelim} 'ip_version': '{$ip_version}' {rdelim});
{literal}
</script>
{/literal}
        <link rel="stylesheet" href="{$themeurl}styles.css" />
        <link rel="stylesheet" href="{$wifidb_host_url}lib/js/sceditor/minified/themes/default.min.css" id="theme-style" />
        <script src="{$wifidb_host_url}lib/js/sceditor/minified/sceditor.min.js"></script>
        <script src="{$wifidb_host_url}lib/js/sceditor/minified/icons/monocons.js"></script>
        <script src="{$wifidb_host_url}lib/js/sceditor/minified/formats/bbcode.js"></script>
        <title>Wireless DataBase  {$wifidb_version_label}  --&gt; {$wifidb_page_label}</title>
        {$wifidb_seo_content|default:""}
        <meta name="viewport" content="width=device-width, initial-scale=1">
        
        <!-- Open Graph -->
        <meta property="og:title" content="{$wifidb_page_label} | Vistumbler WiFiDB" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="https://wifidb.net{$smarty.server.REQUEST_URI}" />
        <meta property="og:image" content="{$themeurl}img/logo.png" />
        <meta property="og:description" content="Vistumbler WiFiDB is a global open-source wireless network database." />
        <link rel="canonical" href="https://wifidb.net{$smarty.server.REQUEST_URI}" />

        {$wifidb_meta_header}
        {$redirect_func}
        <!-- QR loader for client-side QR generation (shared) -->
        <script src="{$wifidb_host_url}lib/js/qrcode.min.js"></script>
    </head>
    <body style="background-color: #145285" {$redirect_html}>
        <!-- Cookie consent & consent-mode helper (shared) -->
        <script src="{$wifidb_host_url}lib/js/cookie-consent.js"></script>
        {$install_header}
        {$wifidb_announce_header}
        
        <table style="width: 90%; " class="no_border" align="center">
            <tr>
                <td>
                    <table>
                        <tr>
                            <td style="width: 228px">
                                <a href="http://www.wifidb.net">
                                <img alt="WifiDB Logo" src="{$themeurl}img/logo.png" class="no_border" /></a>
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
                                <img alt="" src="{$themeurl}img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                            <td class="cell_top_mid" style="height: 20px">
                                <img alt="" src="{$themeurl}img/1x1_transparent.gif" width="185" height="1" />
                            </td>
                            <td style="width: 10px" class="cell_top_right">
                                <img alt="" src="{$themeurl}img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                        </tr>
                        <tr width="185px">
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color">
								<div class="inside_dark_header">WiFiDB Links</div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}">Home</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=0">Map</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}stats.php">Stats</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}all.php?sort=AP_ID&ord=DESC&from=0&inc=100">List Points</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}import/">Import File</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/scheduling.php">Files Importing</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/scheduling.php?func=waiting">Files Waiting</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/scheduling.php?func=done">Files Completed</a></div>
                                {if $wifidb_login_priv_name == "Administrator"}
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}opt/scheduling.php?func=bad">Files Bad</a></div>
                                {/if}
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/scheduling.php?func=schedule">Schedule</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/search.php">Search</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/scheduling.php?func=daemon_kml">KMZ Exports</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/live.php">Live APs</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}opt/userstats.php?func=allusers&sort=user&ord=ASC&from=0&inc=100">Users</a></div>
								<div class="inside_text_bold"><a href="{$wifidb_host_url}themes/">Themes</a></div>
								<div class="inside_text_bold"><a href="https://forum.techidiots.net/forum/">Support Forum</a></div>
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
                                <img alt="" src="{$themeurl}img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                            <!-- ------ WiFiDB Login Bar ---- -->
                            <td class="cell_top_mid" style="height: 20px" align="left">
                                {if $wifidb_login_logged_in == 1}<a class="links" href="{$wifidb_host_url}cp/index.php">{$wifidb_login_user}</a> | <a class="links" href="{$wifidb_host_url}cp/messages.php">Inbox{if $wifidb_message_unread_count gt 0} <b>({$wifidb_message_unread_count})</b>{/if}</a>{/if}
                            </td>
                            <td class="cell_top_mid" style="height: 20px" align="right">
                                <a class="links" href="{$wifidb_host_url}login.php{$wifidb_current_uri}">{$wifidb_login_label|default:'login'}</a>
                            </td>
                            <!-- ---------------------------- -->
                            <td style="width: 10px" class="cell_top_right">
                                <img alt="" src="{$themeurl}img/1x1_transparent.gif" width="10" height="1" />
                            </td>
                        </tr>
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color_centered" align="center" colspan="2">
                                <div align="center">