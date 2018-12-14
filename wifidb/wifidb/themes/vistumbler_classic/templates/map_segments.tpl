<!--
index.tpl: The Smarty Index template for WiFiDB.
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
{$user} has {$count} APs, which is greater than the {$limit} AP map limit. Please pick a smaller segment bellow.<br/><br/>
The segments are ordered by ModDate, with the last modified APs first.<br/><br/>
{for $multiplier=1 to $ldivs}
	<a class="links" href="{$wifidb_host_url}opt/map.php?func=user_all&labeled={$labeled}&user={$user}&from={($multiplier-1)*$limit}&limit={$limit}&clat={$clat}&clon={$clon}" title="View {$user} Map {$multiplier}">{$user} Map {$multiplier}</a><br/>
{/for}
<br/>
*Note* If the user has this many APs, the map may take a long time to load. Don't be surprised if you are looking at a blank map for a while, just let it load. It takes a while to dynamically generate this many access points. It will take a least as long as it took to load this page.
{include file="footer.tpl"}