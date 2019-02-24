<!DOCTYPE html>
<html>
<head>
	<link rel="stylesheet" href="{$themeurl}html5style.css" />
	<title>Wireless DataBase  {$wifidb_version_label}  --&gt; {$wifidb_page_label}</title>
	{$WebSocketScripts|default:""}
	{$wifidb_meta_header|default:""}
	{$redirect_func|default:""}
</head>
<body {$redirect_html|default:""} {$OnLoad|default:""}>
	<div class="wrap">
		<div class="head">{$install_header}{$wifidb_announce_header}
			<div class="lefthead">
				<a href="http://www.wifidb.net/"><img alt="WifiDB Logo" src="{$themeurl}img/logo.png"></a>
			</div>
			<div class="righthead">
				{$wifidb_login_html|default:""}{if $wifidb_login_html != ''} | {/if}<a class="links" href="{$wifidb_host_url}login.php{$wifidb_current_uri}">{$wifidb_login_label|default:'login'}</a>
			</div>
		</div>
		<div class='bodywrap'>
{include file="navigation.tpl"}