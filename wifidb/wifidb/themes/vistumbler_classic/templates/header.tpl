<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html lang="en">
    <head>
{if $wifidb_tracking|default:false}
<!-- Google consent mode: nothing uses cookies for analytics or personalized ads until the visitor allows it, and only
     the visitors Site Settings says to ask are asked (lib/js/cookie-consent.js) -->
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){ldelim}dataLayer.push(arguments);{rdelim}
  gtag('consent', 'default', {ldelim} 'ad_storage': 'denied', 'analytics_storage': 'denied', 'ad_user_data': 'denied', 'ad_personalization': 'denied' {rdelim});
  window.WIFIDB_CONSENT = {ldelim} ask: {if $wifidb_consent_ask}true{else}false{/if} {rdelim};
</script>
<script src="{$wifidb_host_url}lib/js/cookie-consent.js"></script>
{/if}
{if $wifidb_analytics_id|default:''}
<!-- Google tag (gtag.js) -->
<script async src="https://www.googletagmanager.com/gtag/js?id={$wifidb_analytics_id|escape:'url'}"></script>
<script>
  gtag('js', new Date());
  gtag('config', '{$wifidb_analytics_id|escape:'javascript'}', {ldelim} 'ip_version': '{$ip_version|default:""|escape:'javascript'}' {rdelim});
</script>
{/if}
        <link rel="stylesheet" href="{$themeurl}styles.css" />
        <link rel="stylesheet" href="{$wifidb_host_url}lib/js/sceditor/minified/themes/default.min.css" id="theme-style" />
        <script src="{$wifidb_host_url}lib/js/sceditor/minified/sceditor.min.js"></script>
        <script src="{$wifidb_host_url}lib/js/sceditor/minified/icons/monocons.js"></script>
        <script src="{$wifidb_host_url}lib/js/sceditor/minified/formats/bbcode.js"></script>
        <title>Wireless DataBase  {$wifidb_version_label|default:""}  --&gt; {$wifidb_page_label|default:""}</title>
        <meta name="description" content="{$wifidb_site_description|default:''|escape:'html':'UTF-8':false}" />
        {if $wifidb_noindex|default:false}<meta name="robots" content="noindex, nofollow" />{/if}
        {if $wifidb_google_verification|default:''}<meta name="google-site-verification" content="{$wifidb_google_verification|escape:'html':'UTF-8':false}" />{/if}
        {if $wifidb_bing_verification|default:''}<meta name="msvalidate.01" content="{$wifidb_bing_verification|escape:'html':'UTF-8':false}" />{/if}
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <!-- Open Graph -->
        <meta property="og:title" content="{$wifidb_page_label|default:""} | Vistumbler WiFiDB" />
        <meta property="og:type" content="website" />
        <meta property="og:url" content="{$wifidb_site_origin|default:''|escape:'html':'UTF-8':false}{$smarty.server.REQUEST_URI|escape:'html':'UTF-8':false}" />
        <meta property="og:image" content="{$themeurl}img/logo.png" />
        <meta property="og:description" content="{$wifidb_site_description|default:''|escape:'html':'UTF-8':false}" />
        <link rel="canonical" href="{$wifidb_site_origin|default:''|escape:'html':'UTF-8':false}{$smarty.server.REQUEST_URI|escape:'html':'UTF-8':false}" />

        {$wifidb_meta_header}
        {$redirect_func}
        <!-- QR loader for client-side QR generation (shared) -->
    </head>
    <body style="background-color: #145285" {$redirect_html}>
        {$install_header|default:""}
        {$wifidb_announce_header|default:""}
        
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
                                {if $wifidb_is_admin|default:false}
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}opt/scheduling.php?func=bad">Files Bad</a></div>
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}admin/settings.php">Site Settings</a></div>
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}admin/logins.php">Sign-in Providers</a></div>
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}admin/mail.php">Mail</a></div>
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}admin/backups.php">Backups</a></div>
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}admin/servers.php">Servers</a></div>
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}admin/traffic.php">Traffic</a></div>
                                {if $wifidb_admin_mode|default:false}
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}cp/admin_mode.php?func=off">Leave admin mode</a></div>
                                {else}
                                <div class="inside_text_bold"><a href="{$wifidb_host_url}cp/admin_mode.php">Admin mode</a></div>
                                {/if}
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
                                {if ($wifidb_login_logged_in|default:0) == 1}<a class="links" href="{$wifidb_host_url}cp/index.php">{$wifidb_login_user|escape:'html':'UTF-8':false}</a> | <a class="links" href="{$wifidb_host_url}cp/messages.php">Inbox{if $wifidb_message_unread_count gt 0} <b>({$wifidb_message_unread_count})</b>{/if}</a> | <a class="links" href="{$wifidb_host_url}cp/notifications.php">Alerts{if $wifidb_notification_unread_count|default:0 gt 0} <b>({$wifidb_notification_unread_count})</b>{/if}</a>{/if}
                            </td>
                            <td class="cell_top_mid" style="height: 20px" align="right">
                                <a class="links" href="{$wifidb_host_url}login.php{$wifidb_current_uri|default:""}">{$wifidb_login_label|default:'login'}</a>
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