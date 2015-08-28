{include file="header.tpl"}
				<div align="center">
					<script language="JavaScript">
						// Row Hide function.
						// by tcadieux
						function expandcontract(tbodyid,ClickIcon)
						{
							if (document.getElementById(ClickIcon).innerHTML == "+")
							{
								document.getElementById(tbodyid).style.display = "";
								document.getElementById(ClickIcon).innerHTML = "-";
							}else{
								document.getElementById(tbodyid).style.display = "none";
								document.getElementById(ClickIcon).innerHTML = "+";
							}
						}
					</script>
					<h2>Showing the Last 1800 Seconds worth of APs.</h2>
					<table border="1" width="100%" cellspacing="0">
						<tbody><tr class="style4">
							<td>
								Select Window of time to view:
							</td>
							<td>
								<a href="?sort=chan&amp;ord=ASC&amp;from=0&amp;to=&amp;view=1800">30 Minutes</a>
							</td>
							<td>
								<a href="?sort=chan&amp;ord=ASC&amp;from=0&amp;to=&amp;view=3600">60 Minutes</a>
							</td>
							<td>
								<a href="?sort=chan&amp;ord=ASC&amp;from=0&amp;to=&amp;view=7200">2 Hours</a>
							</td>
							<td>
								<a href="?sort=chan&amp;ord=ASC&amp;from=0&amp;to=&amp;view=21600">6 Hours</a>
							</td>
							<td>
								<a href="?sort=chan&amp;ord=ASC&amp;from=0&amp;to=&amp;view=86400">1 Day</a>
							</td>
							<td>
								<a href="?sort=chan&amp;ord=ASC&amp;from=0&amp;to=&amp;view=604800">1 Week</a>
							</td>
						</tr>
						</tbody></table>
					<table border="1" width="100%" cellspacing="0">
						<tbody><tr class="style4">
							<td>Expand Graph</td>
							<td>Expand Map</td>
							<td>SSID<a href="?sort=SSID&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=SSID&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>MAC<a href="?sort=mac&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=mac&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>Chan<a href="?sort=chan&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=chan&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>Radio Type<a href="?sort=radio&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=radio&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>Authentication<a href="?sort=auth&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=auth&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>Encryption<a href="?sort=encry&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=encry&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>First Seen<a href="?sort=fa&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=fa&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>Last Seen<a href="?sort=lu&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=lu&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
							<td>Username<a href="?sort=username&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/down.png"></a><a href="?sort=username&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="../themes/vistumbler/img/up.png"></a></td>
						</tr>
						<tr>
							{foreachelse}
						<tr>
							<td>Awww crap, no one is using the Live Access Point feature :(</td>
						</tr>
						{/foreach}
							<td align="center" colspan="11">
								<b>There are no Access Points imported as of yet, go grab some with Vistumbler and import them.<br>
									Come on... you know you want too.</b>
							</td>
						</tr>
						</tbody></table>
				</div>