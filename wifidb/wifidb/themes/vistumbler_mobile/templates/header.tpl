<!DOCTYPE html>
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
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>{$wifidb_page_label|default:""} | Vistumbler WiFiDB</title>
	<meta name="description" content="{$wifidb_site_description|default:''|escape:'html':'UTF-8':false}" />
	{if $wifidb_noindex|default:false}<meta name="robots" content="noindex, nofollow" />{/if}
	{if $wifidb_google_verification|default:''}<meta name="google-site-verification" content="{$wifidb_google_verification|escape:'html':'UTF-8':false}" />{/if}
	{if $wifidb_bing_verification|default:''}<meta name="msvalidate.01" content="{$wifidb_bing_verification|escape:'html':'UTF-8':false}" />{/if}

	<!-- Open Graph -->
	<meta property="og:title" content="{$wifidb_page_label|default:""} | Vistumbler WiFiDB" />
	<meta property="og:type" content="website" />
	<meta property="og:url" content="{$wifidb_site_origin|default:''|escape:'html':'UTF-8':false}{$smarty.server.REQUEST_URI|escape:'html':'UTF-8':false}" />
	<meta property="og:image" content="{$themeurl}img/logo.png" />
	<meta property="og:description" content="{$wifidb_site_description|default:''|escape:'html':'UTF-8':false}" />

	<link rel="canonical" href="{$wifidb_site_origin|default:''|escape:'html':'UTF-8':false}{$smarty.server.REQUEST_URI|escape:'html':'UTF-8':false}" />

	{$wifidb_meta_header|default:""}
	{$redirect_func|default:""}
	<!-- QR loader for client-side QR generation -->
	<link rel="stylesheet" href="{$themeurl}html5style.css" />
	<link rel="stylesheet" href="{$wifidb_host_url}lib/js/sceditor/minified/themes/default.min.css" id="theme-style" />
	<script src="{$wifidb_host_url}lib/js/sceditor/minified/sceditor.min.js"></script>
	<script src="{$wifidb_host_url}lib/js/sceditor/minified/icons/monocons.js"></script>
	<script src="{$wifidb_host_url}lib/js/sceditor/minified/formats/bbcode.js"></script>
</head>
<body {$redirect_html|default:""} {$OnLoad|default:""}>
	<script type="text/javascript">
	<!--
	    var vidonate = true;
	//-->
	</script>
	<script type="text/javascript" src="{$themeurl}lib/adframe.js"></script>
	<!-- Start Collapsible Menu Scripts-->
	<script src="{$wifidb_host_url}lib/js/jquery-4.0.0.min.js"></script>
    <script type="text/javascript">
        $(document).ready(function () {
            $('.bt-menu-trigger').on('click', function () {
                $('#sidebar').toggleClass('active');
				$('.main').toggleClass('active');
				$('.foot').toggleClass('active');
                $(this).toggleClass('bt-menu-alt');
            });
        });
    </script>
	<!-- End Collapsible Menu Scripts -->
	<div class="wrap">
		<div class="head">{$install_header|default:""}{$wifidb_announce_header|default:""}
			<div class="lefthead">
				<a href="http://www.wifidb.net/"><img alt="WifiDB Logo" src="{$themeurl}img/logo.png"></a>
			</div>
			<div class="righthead">
				{if ($wifidb_login_logged_in|default:0) == 1}<a class="links" href="{$wifidb_host_url}cp/index.php">{$wifidb_login_user|escape:'html':'UTF-8':false}</a> | {assign var=inbox_unread value=($wifidb_message_unread_count|default:0) + ($wifidb_notification_unread_count|default:0)}<a class="links" href="{$wifidb_host_url}cp/messages.php">Inbox{if $inbox_unread gt 0} <b>({$inbox_unread})</b>{/if}</a> | {/if}
				<a class="links" href="{$wifidb_host_url}login.php{$wifidb_current_uri|default:""}">{$wifidb_login_label|default:'login'}</a>
			</div>
		</div>
	</div>
	<div class='bodywrap'>
{include file="navigation.tpl"}