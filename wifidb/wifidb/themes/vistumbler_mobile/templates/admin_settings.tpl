<!--
Site settings (admin/settings.php).
This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
				<form method="post" action="{$wifidb_host_url}admin/settings.php" autocomplete="off">
					<table class="content_table">
						<tr>
							<td class="subheading" colspan="2">Site settings</td>
						</tr>
						<tr>
							<td class="header" colspan="2">These start from config.inc.php. Once saved here, the value here is used. Every change leaves the administrators a notice.</td>
						</tr>
						{if $settings_message}<tr><td class="light-centered" colspan="2">{if $settings_problem}<font color="Red">{/if}{$settings_message|escape:'html':'UTF-8':false}{if $settings_problem}</font>{/if}</td></tr>{/if}
						{if $settings_unencrypted}<tr><td class="light-centered" colspan="2"><font color="Red">two_factor_key is not set in config.inc.php, so a key saved here is stored unencrypted.</font></td></tr>{/if}
						{foreach $site_settings as $s}
						<tr class="{cycle values='dark,light'}">
							<td width="35%"><label for="{$s.name}"><strong>{$s.label|escape:'html':'UTF-8':false}</strong></label><br>
								<small>{if $s.source == 'saved'}Saved here{elseif $s.source == 'config'}From config.inc.php{else}Default{/if}</small></td>
							<td>
								{if $s.type == 'bool'}
								<input id="{$s.name}" name="{$s.name}" type="checkbox"{if $s.value} checked{/if}>
								{else}
								<input id="{$s.name}" name="{$s.name}" type="password" value="" size="40" placeholder="{if $s.is_set}Set. Leave blank to keep it{else}Not set{/if}">
								{if $s.is_set}<label><input name="clear_{$s.name}" type="checkbox"> Remove it</label>{/if}
								{/if}
								<br><small>{$s.help|escape:'html':'UTF-8':false}</small>
							</td>
						</tr>
						{/foreach}
						<tr class="light-centered">
							<td colspan="2"><input type="submit" value="Save"></td>
						</tr>
					</table>
				</form>
			</div>
{include file="footer.tpl"}
