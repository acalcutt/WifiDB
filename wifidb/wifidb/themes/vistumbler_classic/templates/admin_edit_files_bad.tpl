{include file="header.tpl"}
		<div class="main">
			{include file="topmenu.tpl"}
			<div class="center">
				<h2>Edit Rejected Import</h2>
				<form method="post" action="{$wifidb_host_url}opt/admin_action.php?action=edit_files_bad&amp;file_id={$file_id}{if $return_url}&amp;return={$return_url}{/if}">
					<table class="content_table">
						<tr class="subheading"><th class="subheading">Field</th><th class="subheading">Value</th></tr>
						<tr class="light"><td class="light"><strong>File ID</strong></td><td class="light">{$file_info.id}</td></tr>
						<tr class="dark"><td class="dark"><strong>Original Filename</strong></td><td class="dark">{$file_info.file_orig|escape:'htmlall'}</td></tr>
						<tr class="light"><td class="light"><strong>Stored Filename</strong></td><td class="light">{$file_info.file_name|escape:'htmlall'}</td></tr>
						<tr class="dark"><td class="dark"><strong>Title</strong></td><td class="dark"><input type="text" name="title" value="{$file_info.title|escape:'htmlall'}" style="width:100%"/></td></tr>
						<tr class="light"><td class="light"><strong>Type</strong></td><td class="light">
							<div class="radio-group">
								<label><input type="radio" name="type" value="vistumbler" {if $file_info.type == 'vistumbler'}checked{/if}>Vistumbler VS1/VSZ/CSV/MDB</label>
								<label><input type="radio" name="type" value="ns1" {if $file_info.type == 'ns1'}checked{/if}>NetStumbler NS1</label>
								<label><input type="radio" name="type" value="wardrive" {if $file_info.type == 'wardrive'}checked{/if}>Wardrive DB/DB3</label>
								<label><input type="radio" name="type" value="wiglewificsv" {if $file_info.type == 'wiglewificsv'}checked{/if}>WigleWifi CSV</label>
								<label><input type="radio" name="type" value="swardriving" {if $file_info.type == 'swardriving'}checked{/if}>SWardriving CSV</label>
								<label><input type="radio" name="type" value="kismet" {if $file_info.type == 'kismet'}checked{/if}>Kismet netxml</label>
							</div>
						</td></tr>
						<tr class="dark"><td class="dark"><strong>User</strong></td><td class="dark"><input type="text" name="file_user" value="{$file_info.file_user|escape:'htmlall'}" style="width:100%"/></td></tr>
						<tr class="light"><td class="light"><strong>Other Users</strong></td><td class="light"><input type="text" name="otherusers" value="{$file_info.otherusers|escape:'htmlall'}" style="width:100%"/></td></tr>
						<tr class="dark"><td class="dark"><strong>Notes</strong></td><td class="dark"><textarea name="notes" style="width:100%">{$file_info.notes|escape:'htmlall'}</textarea></td></tr>
						<tr class="light"><td class="light" colspan="2" style="text-align:center; padding:12px;">
							<button type="submit" style="padding:8px 16px;">Save Changes</button>
							<a href="{if $return_url}{$return_url}{else}{$wifidb_host_url}opt/scheduling.php?func=bad{/if}" style="margin-left:12px;">Cancel</a>
						</td></tr>
					</table>
				</form>
			</div>
		</div>
{include file="footer.tpl"}
