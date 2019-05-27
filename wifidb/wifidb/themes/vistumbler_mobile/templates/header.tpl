<!DOCTYPE html>
<html>
<head>
	<title>Wireless DataBase  {$wifidb_version_label}  --&gt; {$wifidb_page_label}</title>
	{$WebSocketScripts|default:""}
	{$wifidb_meta_header|default:""}
	{$redirect_func|default:""}
	<link rel="stylesheet" href="{$themeurl}html5style.css" />
	<link rel="stylesheet" href="{$themeurl}lib/sceditor/minified/themes/default.min.css" id="theme-style" />
	<script src="{$themeurl}lib/sceditor/minified/sceditor.min.js"></script>
	<script src="{$themeurl}lib/sceditor/minified/icons/monocons.js"></script>
	<script src="{$themeurl}lib/sceditor/minified/formats/bbcode.js"></script>
</head>
<body {$redirect_html|default:""} {$OnLoad|default:""}>
	<!-- Start Collapsible Menu Scripts-->
	<script src="{$themeurl}lib/jquery-3.4.1.slim.js" integrity="sha256-BTlTdQO9/fascB1drekrDVkaKd9PkwBymMlHOiG+qLI=" crossorigin="anonymous"></script>
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