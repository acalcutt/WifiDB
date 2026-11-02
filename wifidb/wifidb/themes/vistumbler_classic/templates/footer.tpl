                                </div>
                                <br>
                            </td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td colspan="2" class="cell_color_centered"></td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
                        <tr>
                            <td class="cell_bot_left">&nbsp;</td>
                            <td class="cell_bot_mid" colspan="2" align="center">&nbsp;</td>
                            <td class="cell_bot_right">&nbsp;</td>
                        </tr>
                    </table>
                    <div class="inside_text_center" align=center>
                        <strong>
                            WifiDB {$wifidb_version_label|default:""} | © 2008–2026 TechIdiots LLC. All rights reserved.<br />
                            <a href="{$wifidb_host_url}">About</a> | <a href="{$wifidb_host_url}contact.php">Contact</a> | <a href="{$wifidb_host_url}privacy.php">Privacy Policy</a> | <a href="{$wifidb_host_url}terms.php">Terms of Use</a> | <a href="{$wifidb_host_url}cookies.php">Cookie Policy</a>
                            {if $wifidb_tracking|default:false} | <a href="{$wifidb_host_url}cookies.php" onclick="if (window.WifiDBCookieChoices) {ldelim} WifiDBCookieChoices(); return false; {rdelim}">Cookie choices</a>{/if}
                        </strong>
                        {if $wifidb_geoip_credit|default:false}<br /><small><a href="https://db-ip.com">IP Geolocation by DB-IP</a></small>{/if}
                    </div>
                    <br />

                </td>
            </tr>
        </table>
		{if $wifidb_adsense_slot|default:''}

        <table style="width: 90%" align="center">
            <tr>
                <td style="width: 165px; height: 300px" valign="top">
                </td>
				<td style="height: 300px" valign="top" class="center">
					<!-- Start ads (AdSense, from Site Settings) -->
						<div align="center">
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
						</div>
					<!-- End ads -->
                </td>
            </tr>
        </table>
		{/if}
    </body>
</html>