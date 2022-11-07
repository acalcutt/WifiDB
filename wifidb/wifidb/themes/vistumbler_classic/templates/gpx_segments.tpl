<!--
index.tpl: The Smarty Index template for WiFiDB.
Copyright (C) 2018 Andrew Calcutt Calcutt

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
				{$user} has {$count} APs, which is greater than the {$inc} AP gpx limit. Please pick a smaller segment below.<br/><br/>
				The segments are ordered by ModDate, with the last modified APs first.<br/><br/>
				{for $multiplier=1 to $ldivs}
					<a class="links" href="{$wifidb_host_url}api/gpx.php?&xml={$xml}?&labeled={$labeled}&func=exp_user_all&user={$user}&from={($multiplier-1)*$inc}&inc={$inc}">{$user} GPX {$multiplier}</a><br/>
				{/for}
				<br/>
			</div>
{include file="footer.tpl"}