			<div class="foot">
				<div class="inside_text_center">
				WifiDB {$wifidb_version_label|default:""} | © 2008–2026 TechIdiots LLC. All rights reserved.<br/>
				<a href="{$wifidb_host_url}">About</a> | <a href="{$wifidb_host_url}contact.php">Contact</a> | <a href="{$wifidb_host_url}privacy.php">Privacy Policy</a> | <a href="{$wifidb_host_url}terms.php">Terms of Use</a> | <a href="{$wifidb_host_url}cookies.php">Cookie Policy</a>
				{if $wifidb_tracking|default:false} | <a href="{$wifidb_host_url}cookies.php" onclick="if (window.WifiDBCookieChoices) {ldelim} WifiDBCookieChoices(); return false; {rdelim}">Cookie choices</a>{/if}
				{if $wifidb_geoip_credit|default:false}<br/><small><a href="https://db-ip.com">IP Geolocation by DB-IP</a></small>{/if}
				<br/>
				{if $wifidb_adsense_slot|default:''}
				<!-- Start ads (AdSense, from Site Settings) -->
					<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client={$wifidb_adsense_client|escape:'url'}"
						 crossorigin="anonymous"></script>
					<ins class="adsbygoogle"
						 style="display:block"
						 data-ad-client="{$wifidb_adsense_client|escape:'html':'UTF-8':false}"
						 data-ad-slot="{$wifidb_adsense_slot|escape:'html':'UTF-8':false}"
						 data-ad-format="auto"
						 data-full-width-responsive="true"></ins>
					<script>
					(adsbygoogle = window.adsbygoogle || []).push({ldelim}{rdelim});
					</script>
				{/if}
					<script type="text/javascript">
						if(vidonate) {
						//Show donate images instead
							document.write('<a class="img" href = "http://donate.vistumbler.net/"><img src="{$themeurl}img/support_wdb_wide.png" alt="Donate to WifiDB"></a><a class="img" href = "http://donate.vistumbler.net/"><img src="{$themeurl}img/support_vi_wide.png" alt="Donate to Vistumbler"></a>');
						}
					</script>
				</div>
				<!-- End ads -->
			</div>
		</div>
</body>
</html>