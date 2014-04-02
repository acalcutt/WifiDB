<!--
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

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
{include file="vistumbler_header.tpl"}
                        <tr>
                            <td class="cell_side_left">&nbsp;</td>
                            <td class="cell_color_centered" align="center" colspan="2">
                                <div align="center">
                                    <table border="1" width="100%" cellspacing="0">
                                    {foreach from=$live item=user}
                                        <tr class="{$user.color}">
                                            <td><a class="links" href="{$wifidb_host_url}opt/userstats.php?id={$user.username}">{$user.username}</a></td>
                                        </tr>
                                        <tr>
                                            <td>
                                            	<iframe src="live_list_user.php?user={$user.username}&ord=ASC&sort=SSID"></iframe>
                                            </td>
                                        </tr>
                                    {foreachelse}
                                        <tr>
                                            <td>Awww crap, no one is using the Live Access Point feature :(</td>
                                        </tr>
                                    {/foreach}
                                    </table>
                                </div>
                                <br>
                            </td>
                            <td class="cell_side_right">&nbsp;</td>
                        </tr>
{include file="vistumbler_footer.tpl"}