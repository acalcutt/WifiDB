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
				{include file="topmenu.tpl"}
				<div class="center">
					<span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php"><img src="{$themeurl}img/file-importing.png" style="vertical-align: middle;"/> Files Importing</a> ({$importing_count})</span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=waiting"><img src="{$themeurl}img/file-waiting.png" style="vertical-align: middle;"/> Files Waiting</a> ({$waiting_count})</span> | <span class="nowrap"><a class="links" style="text-decoration: none;" href="{$wifidb_host_url}opt/scheduling.php?func=done"><img src="{$themeurl}img/file-complete.png" style="vertical-align: middle;"/> Files Completed</a> ({$complete_count})</span>
					<h2>{$mesg}</h2>
					Vistumbler 
					<a href="https://github.com/acalcutt/Vistumbler/wiki/Vistumbler-VS1-Format" target="_blank">VS1</a> /
					<a href="https://github.com/acalcutt/Vistumbler/wiki/Vistumbler-Detailed-CSV-Format" target="_blank">CSV</a>
					files are the main import formats.<br/>
					For Android, we support 
					<a href="https://play.google.com/store/apps/details?id=net.wigle.wigleandroid" target="_blank">WigleWifi CSV</a>,
					<a href="https://play.google.com/store/apps/details?id=com.Buckynet.Wifi.Scanner.Wireless.SWardriving&hl=en_US" target="_blank">SWardriving CSV</a>, and 
					<a href="https://github.com/raffaeleragni/android-wardrive4/releases" target="_blank">Wardrive DB/DB3</a><br><br>
					Username is optional, but it helps keep track of who has imported what Access Points<br><br>
				</div>
				<div style="text-align: center;">
					<div style="display: inline-block; text-align: left;">
						<form action="{$wifidb_host_url}import/?func=import" method="post" enctype="multipart/form-data">
							<ul class="wrapper">
								<li class="form-row><label for="title">Title of Import:</label><br /><input type="text" name="title" id="title"></li>
								<li class="form-row><label for="file">File:</label><br /><input type="FILE" name="file" id="file"></li>
								<li class="form-row><label for="user">Username:</label><br /><input type="text" name="user" id="user"></li>
								<li class="form-row><label for="otherusers">Other Users (Separate by a pipe "|" ):</label><br /><input type="text" name="otherusers" id="otherusers"></li>
								<li class="form-row><label for="notes">Notes:</label><br /><textarea name="notes" id="notes" rows="4" cols="30"></textarea></li>
								<li class="form-row><label for="type">Import Type:</label><br />
									<input type="radio" name="type" id="type" value="vistumbler" checked>Vistumbler VS1/VSZ/CSV/MDB<br />
									<input type="radio" name="type" id="type" value="wardrive">Wardrive DB/DB3<br />
									<input type="radio" name="type" id="type" value="wiglewificsv">WigleWifi CSV<br />
									<input type="radio" name="type" id="type" value="swardriving">SWardriving CSV<br />
								</li>
								<li>
									<br />
									{if $allowimports eq 1}
										<button type="submit">Submit</button>
									{else}
										Importing is currently disabled.
									{/if}
								</li>
							</ul>
						</form>
					</div>
				</div>
			</div>
{include file="footer.tpl"}