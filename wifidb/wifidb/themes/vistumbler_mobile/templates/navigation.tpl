<!--
navigation.tpl: The Smarty pae navigation template for WiFiDB.
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
-->
			<div>
				<nav id="sidebar">
					<ul class="list-unstyled components">
						<div class="inside_dark_header">WiFiDB Links</div>
						<li><a href="{$wifidb_host_url}">Home</a></li>
						<li><a href="{$wifidb_host_url}opt/map.php?func=wifidbmap&labeled=0">Map</a></li>
						<li><a href="{$wifidb_host_url}stats.php">Stats</a></li>
						<li><a href="{$wifidb_host_url}all.php?sort=AP_ID&ord=DESC&from=0&inc=100">List Points</a></li>
						<li><a href="{$wifidb_host_url}import/">Import File</a></li>
						<li><a href="{$wifidb_host_url}opt/scheduling.php">Files Importing</a></li>
						<li><a href="{$wifidb_host_url}opt/scheduling.php?func=waiting">Files Waiting</a></li>
						<li><a href="{$wifidb_host_url}opt/scheduling.php?func=done">Files Completed</a></li>
						<li><a href="{$wifidb_host_url}opt/scheduling.php?func=schedule">Schedule</a></li>
						<li><a href="{$wifidb_host_url}opt/search.php">Search</a></li>
						<li><a href="{$wifidb_host_url}opt/scheduling.php?func=daemon_kml">KMZ Exports</a></li>
						<li><a href="{$wifidb_host_url}opt/live.php">Live APs</a></li>
						<li><a href="{$wifidb_host_url}opt/userstats.php?func=allusers&sort=file_user&ord=ASC&from=0&inc=100">Users</a></li>
						<li><a href="{$wifidb_host_url}themes/">Themes</a></li>
						<li><a href="https://forum.techidiots.net/forum/">Support Forum</a></li>
						<li class="inside_dark_header">Other Projects</li>
						<li><a class="inside_text_bold" href="http://www.vistumbler.net">Vistumbler Home</a></li>
						<li><a href="http://www.techidiots.net/project-pages">TechIdiots Projects</a></li>					
						<li class="inside_dark_header">Ads</li>
					</ul>
					<div class="inside_text_center">
						<script async src="//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js"></script>
						<!-- Vistumbler - Responsive Right -->
						<ins class="adsbygoogle"
							 style="display:block"
							 data-ad-client="ca-pub-4275640341473005"
							 data-ad-slot="2546768734"
							 data-ad-format="auto"
							 data-full-width-responsive="true"></ins>
						<script>
							 (adsbygoogle = window.adsbygoogle || []).push({});
						</script>
						<script type="text/javascript">
							if(vidonate) {
							//Show donate images instead
								document.write('<a class="img" href = "http://donate.vistumbler.net/"><img src="{$themeurl}img/support_wdb_tall.png" alt="Donate to WifiDB"></a>');
							}
						</script>
					</div>

				</nav>
			</div>
