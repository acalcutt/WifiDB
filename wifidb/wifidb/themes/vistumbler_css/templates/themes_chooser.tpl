<!--

Copyright (C) 2018 Andrew Calcutt

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
				<script type="text/javascript">

				/***********************************************
				* Dynamic Ajax Content- © Dynamic Drive DHTML code library (www.dynamicdrive.com)
				* This notice MUST stay intact for legal use
				* Visit Dynamic Drive at http://www.dynamicdrive.com/ for full source code
				***********************************************/

					var loadedobjects=""
					var rootdomain="http://"+window.location.hostname

					function ajaxpage(url, containerid)
					{
						var page_request = false
						if (window.XMLHttpRequest) // if Mozilla, Safari etc
							page_request = new XMLHttpRequest()
						else if (window.ActiveXObject){ // if IE
							try {
								page_request = new ActiveXObject("Msxml2.XMLHTTP")
							} 
							catch (e){
								try{
									page_request = new ActiveXObject("Microsoft.XMLHTTP")
								}
								catch (e){
								}
							}
						}
						else
							return false
							page_request.onreadystatechange=function(){
							loadpage(page_request, containerid)
							}
							page_request.open('GET', url, true)
							page_request.send(null)
					}

					function loadpage(page_request, containerid){
					if (page_request.readyState == 4 && (page_request.status==200 || window.location.href.indexOf("http")==-1))
					document.getElementById(containerid).innerHTML=page_request.responseText
				}
				</script>

				<h2>Themes Switchboard</h2>
				<table width="100%">
					<tr>
						<td>
							<img alt="" src="/wifidb/themes/wifidb/img/1x1_transparent.gif" width="100%" height="1" />
						</td>
					</tr>
					<tr>
						<td id="leftcolumn">
						{foreach item=wifidb_theme from=$wifidb_themes_all}
							[ <a class="links" href="javascript:ajaxpage('themes_template.php?theme={$wifidb_theme}', 'rightcolumn');">{$wifidb_theme}</a> ]
						{/foreach}
						</td>
					</tr>
					<tr>
						<td id="rightcolumn" align="center">
							<h3>Choose a Theme to preview.</h3>
						</td>
					</tr>
				</table>
			</div>
{include file="footer.tpl"}