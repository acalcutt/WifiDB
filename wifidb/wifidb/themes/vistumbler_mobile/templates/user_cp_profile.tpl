<!--
Copyright (C) 2022 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
-->
{include file="header.tpl"}
			<div class="main">
				{include file="topmenu.tpl"}
{include file="user_cp_header.tpl"}
				<form method="post" action="?func=update_user_profile">
					<table class="content_table">
						<tr>
							<th width="30%" class="dark">Email</th>
							<td class="light"><input type="text" name="email" size="75%" value="{$user_cp_profile.email}"> Hide? <input name="h_email" type="checkbox" {$user_cp_profile.hide_email}></td>
						</tr>
						<tr>
							<th width="30%" class="dark">Website</th>
							<td class="light"><input type="text" name="website" size="75%" value="{$user_cp_profile.website}"></td>
						</tr>
						<tr>
							<th width="30%" class="dark">Vistumbler Version</th>
							<td class="light"><input type="text" name="Vis_ver" size="75%" value="{$user_cp_profile.Vis_ver}"></td>
						</tr>
						<tr>
							<th width="30%" class="dark">Api Key</th>
							<td class="light"><input type="text" name="apikey" size="75%" value="{$user_cp_profile.apikey}"></td>
						</tr>
						<tr>
							<th width="30%" class="dark">API Key QR</th>
							<td class="light">
								{if $user_cp_profile.apikey}
									<div id="wifidb_link_area">
										<button type="button" id="wifidb_generate_link">Generate one-time link QR</button>
										<script>
											window.WIFIDB = window.WIFIDB || {};
											WIFIDB.theme_url = '{$themeurl}';
										</script>
										<script src="{$wifidb_host_url}lib/js/qrcode.min.js"></script>
										<div id="wifidb_link_qr" style="margin-top:8px"></div>
										<p id="wifidb_link_msg">This generates a one-time linking token (expires in 5 minutes).</p>
									</div>
									<script>
										window.WIFIDB = window.WIFIDB || {};
										WIFIDB.theme_url = '{$themeurl}';
										WIFIDB.host_url = '{$wifidb_host_url}';
									</script>
									{literal}
									<script>
									(function(){
										var btn = document.getElementById('wifidb_generate_link');
										var qrdiv = document.getElementById('wifidb_link_qr');
										var msg = document.getElementById('wifidb_link_msg');
										btn.addEventListener('click', function(e){
											try { e && e.preventDefault(); } catch(_) {}
											btn.disabled = true;
											btn.textContent = 'Generating...';
											fetch(WIFIDB.host_url + 'cp/linktoken.php', {credentials: 'same-origin'})
											.then(function(r){ return r.json(); })
											.then(function(data){
												if(data.redeem_url){
													try {
														if (!qrdiv) { throw new Error('qrdiv missing'); }
														if (typeof window.makeQRCode === 'function') {
															qrdiv.innerHTML = '';
															window.makeQRCode(qrdiv, data.redeem_url, {width:180, height:180});
														} else if (typeof window.QRCode === 'function') {
															qrdiv.innerHTML = '';
															new QRCode(qrdiv, {text: data.redeem_url, width:180, height:180});
														} else {
															qrdiv.innerHTML = '<div class="wifidb-qr-error">QR generation unavailable</div>';
														}
													} catch(e) {
														console.error('wifidb: QR generation error', e);
														qrdiv.innerHTML = '<div class="wifidb-qr-error">QR generation unavailable</div>';
													}
													msg.textContent = 'Scan this QR with the WifiDB client within 5 minutes to link.';
												} else {
													console.warn('wifidb: linktoken returned no redeem_url', data);
													msg.textContent = 'Failed to generate token.';
												}
											})
											.catch(function(e){
												console.error('wifidb: fetch/linktoken error', e);
												msg.textContent = 'Error generating token.';
											})
											.finally(function(){ btn.disabled = false; btn.textContent = 'Generate one-time link QR'; });
										});
									})();
									</script>
									{/literal}
								{else}
									<p>No API key set yet. Save profile to generate one.</p>
								{/if}
							</td>
						</tr>
						<tr>
							<th width="30%" class="dark">Require Login for Import</th>
							<td class="light"><input name="import_require_login" type="checkbox" {$user_cp_profile.import_require_login}></td>
						</tr>
						<tr class="light-centered">
							<td colspan="2">
									<input type="submit" value="Update Me!">
							</td>
						</tr>
					</table>
				</form>
			</div>
{include file="footer.tpl"}