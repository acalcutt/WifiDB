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
				<div class="center">
					<h2>Daemon Generated KMZ</h2>
					<br/>
					<table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
						<tr>
							<td class="subheading">KMZ Network Link</td>
						</tr>
						<tr class="light-centered">
							<td class="daemon_kml" colspan="4">
								{$wifidb_kml_head.update_kml}
							</td>
						</tr>
					</table>
					<br/>
					<table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
						<tr>
							<th colspan="4" class="subheading">Latest KMZ Files</th>
						</tr>
						<tr class="dark">
							<th class="header"></th>
							<th class="header">Download link</th>
							<th class="header" style="width: 43%">Date & Time</th>
							<th class="header" style="width: 11%">Size</th>
						</tr>
						<tr class="light">
							<th rowspan="2" style="width: 200px">Newest AP KMZ</th>
							<th style="width: 148px"><a href="{$wifidb_kml_head.newest_link}">Non-Labeled SSIDs</a></th>
							<td style="width: 43%; text-align: center">{$wifidb_kml_head.newest_date}</td>
							<td style="width: 11%; text-align: center">{$wifidb_kml_head.newest_size}</td>
						</tr>
						<tr class="light">
							<th style="width: 148px"><a href="{$wifidb_kml_head.newest_labeled_link}">Labeled SSIDs</a></th>
							<td style="width: 43%; text-align: center">{$wifidb_kml_head.newest_labeled_date}</td>
							<td style="width: 11%; text-align: center">{$wifidb_kml_head.newest_labeled_size}</td>
						</tr>
						<tr class="dark">
							<th rowspan="2" style="width: 200px">Incremental KMZ</th>
							<th style="width: 148px"><a href="{$wifidb_kml_head.daily_link}">Non-Labeled SSIDs</a></th>
							<td style="width: 43%; text-align: center">{$wifidb_kml_head.daily_date}</td>
							<td style="width: 11%; text-align: center">{$wifidb_kml_head.daily_size}</td>
						</tr>
						<tr class="dark">
							<th style="width: 148px"><a href="{$wifidb_kml_head.daily_labeled_link}">Labeled SSIDs</a></th>
							<td style="width: 43%; text-align: center">{$wifidb_kml_head.daily_labeled_date}</td>
							<td style="width: 11%; text-align: center">{$wifidb_kml_head.daily_labeled_size}</td>
						</tr>								
						<tr class="light">
							<th rowspan="2" style="width: 200px">Full KMZ</th>
							<th style="width: 148px"><a href="{$wifidb_kml_head.full_link}">Non-Labeled SSIDs</a></th>
							<td style="width: 43%; text-align: center">{$wifidb_kml_head.full_date}</td>
							<td style="width: 11%; text-align: center">{$wifidb_kml_head.full_size}</td>
						</tr>
						<tr class="light">
							<th style="width: 148px"><a href="{$wifidb_kml_head.full_labeled_link}">Labeled SSIDs</a></th>
							<td style="width: 43%; text-align: center">{$wifidb_kml_head.full_labeled_date}</td>
							<td style="width: 11%; text-align: center">{$wifidb_kml_head.full_labeled_size}</td>
						</tr>
					</table>
					<br/>
					<table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
						<tr>
							<th colspan="3" class="subheading">KMZ Export Archive</th>
						</tr>
						<tr>
							<td class="header">Full</td>
							<td class="light"><a href='https://live.wifidb.net/wifidb/opt/scheduling.php?func=full_kml'>Non-Labeled Archive</a></td>
							<td class="light"><a href='https://live.wifidb.net/wifidb/opt/scheduling.php?func=full_labeled_kml'>Labeled Archive</a></td>
						</tr>
						<tr>
							<td class="header">Incremental</td>
							<td class="dark"><a href='https://live.wifidb.net/wifidb/opt/scheduling.php?func=incremental_kml'>Non-Labeled Archive</a></td>
							<td class="dark"><a href='https://live.wifidb.net/wifidb/opt/scheduling.php?func=incremental_labeled_kml'>Labeled Archive</a></td>
						</tr>
						<tr>
							<td class="header">Legacy</td>
							<td class="light" colspan="2"><a href='https://live.wifidb.net/wifidb/opt/scheduling.php?func=legacy_kml'>Archive</a></td>
						</tr>
					</table>
				</div>
			</div>
{include file="footer.tpl"}