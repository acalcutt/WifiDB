	{include file="header.tpl"}
	<style>
	/* Make scheduling tables responsive and wrap long text; keep auto layout like 'done' */
	.center { overflow-x: auto; }
	.content_table { table-layout: auto; width: 100%; box-sizing: border-box; }
	.content_table th, .content_table td { word-break: break-word; overflow-wrap: break-word; white-space: normal; }
	.badrow { background-color: #f8d7da; color: #000; }
	/* Column sizing to match Files Completed layout */
	.content_table td.idcol, .content_table th.idcol { width: 80px; }
	.content_table td.titlecol, .content_table th.titlecol { width: 220px; }
	.content_table td.filecol, .content_table th.filecol { width: 320px; }
	.content_table td.notescol, .content_table th.notescol { width: 160px; }
	.content_table td.typecol, .content_table th.typecol { width: 100px; }
	.content_table td.hashcol, .content_table th.hashcol { width: 260px; }
	</style>
			<div class="main">
				{include file="topmenu.tpl"}
				<script>var WIFIDB_BASE_URL = '{$wifidb_host_url}';</script>
				<script src="{$wifidb_host_url}lib/js/scheduling.live.js"></script>
				{literal}
				<script>document.addEventListener('DOMContentLoaded', function(){ if(typeof schedulingLiveInit === 'function'){ schedulingLiveInit({func:'bad', interval:15000}); }});</script>
				{/literal}
				<div class="center">
					<span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php"><img src="{$themeurl}img/file-importing.png" style="vertical-align: middle;"/> Files Importing ({$importing_count})</a></span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=waiting"><img src="{$themeurl}img/file-waiting.png" style="vertical-align: middle;"/> Files Waiting ({$waiting_count})</a></span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=done"><img src="{$themeurl}img/file-complete.png" style="vertical-align: middle;"/> Files Completed ({$complete_count})</a></span> {if ($wifidb_login_priv_name|default:"") == "Administrator"} | <span class="nowrap"><b><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=bad"><img src="{$themeurl}img/file-bad.png" style="vertical-align: middle;"/> Files Bad ({$bad_count})</a></b></span>{/if}
					<table class="content_table">
						<tr class="header-centered"><th colspan="8" align="center">Files Rejected / Bad Imports</th></tr>
						{foreach item=badfile from=$wifidb_bad name=bad}
						<tr class="header-centered"><th class="header"></th><th class="header">ID</th><th class="header">Title</th><th class="header">Type</th><th class="header">Filename</th><th class="header">Notes</th><th class="header" colspan="2">Hash</th></tr>
						<tr class="badrow"><td class="header"></td><td class="{$badfile.class} idcol">{$badfile.id}
							{if ($wifidb_login_priv_name|default:"") == "Administrator"}
								&nbsp;<a href="{$wifidb_host_url}opt/admin_action.php?action=reset_files_bad&amp;file_id={$badfile.id}&amp;return={$wifidb_host_url}opt/scheduling.php?func=bad" title="Reset and Re-import this bad file" style="font-size: 16px; margin-right:6px;">&#x21bb;</a>
								&nbsp;<a href="{$wifidb_host_url}opt/admin_action.php?action=edit_files_bad&amp;file_id={$badfile.id}&amp;return={$wifidb_host_url}opt/scheduling.php?func=bad" title="Edit this bad file" style="font-size: 16px; margin-right:6px;">&#9998;</a>
							{/if}
						</td><td class="{$badfile.class} titlecol">{$badfile.title|escape:'htmlall'}</td><td class="{$badfile.class} typecol">{$badfile.type|escape:'htmlall'}</td><td class="{$badfile.class} filecol">{if $badfile.file_name}{$badfile.file_name|escape:'htmlall'}{else}{$badfile.file|escape:'htmlall'}{/if}</td><td class="{$badfile.class} notescol">{$badfile.notes|escape:'htmlall'}</td><td class="{$badfile.class} hashcol" colspan="2">{$badfile.hash|escape:'htmlall'}</td></tr>
						<tr class="header-centered"><th class="header"></th><th width="95px" class="header">Error</th><th class="header">&nbsp;</th><th class="header">Date</th><th class="header">Size</th><th class="header">User</th><th class="header">&nbsp;</th><th class="header">&nbsp;</th></tr>
						<tr class="badrow"><td class="header"></td><td class="{$badfile.class}">{$badfile.error|escape:'htmlall'}</td><td class="{$badfile.class}"></td><td class="{$badfile.class}">{$badfile.date|escape:'htmlall'}</td><td class="{$badfile.class}">{$badfile.size|escape:'htmlall'}</td><td class="{$badfile.class}">{$badfile.user|escape:'htmlall'}</td><td class="{$badfile.class}"></td><td class="{$badfile.class}"></td></tr>
						{if not $smarty.foreach.bad.last}<tr class="content-centered"><th colspan="6"><br/></th></tr>{/if}
						{foreachelse}<tr align="center"><td class="light-centered" colspan="5">No rejected files.</td></tr>{/foreach}
						<tr class="sub_head"><td colspan="12" align="center">{$pages_together|default:""}</td></tr>
					</table>
				</div>
			</div>
{include file="footer.tpl"}
