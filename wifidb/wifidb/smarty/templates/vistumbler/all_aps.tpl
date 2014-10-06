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
{include file="header.tpl"}
                                    <table border="1" width="100%" cellspacing="0">
                                        <tr class="style4">
                                            <td>
                                                SSID<a href="?sort=SSID&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0"border="0" src="themes/{$wifidb_theme}/img/down.png"></a>
                                                <a href="?sort=SSID&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/up.png"></a>
                                            </td>
                                            <td>
                                                MAC<a href="?sort=mac&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/down.png"></a>
                                                <a href="?sort=mac&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/up.png"></a>
                                            </td>
                                            <td>
                                                Channel<a href="?sort=chan&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/down.png"></a>
                                                <a href="?sort=chan&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/up.png"></a>
                                            </td>
                                            <td>
                                                Radio Type<a href="?sort=radio&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="themes/{$wifidb_theme}/img/down.png"></a>
                                                <a href="?sort=radio&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/up.png"></a></td>
                                            <td>
                                                Authentication<a href="?sort=auth&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="themes/{$wifidb_theme}/img/down.png"></a>
                                                <a href="?sort=auth&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/up.png"></a>
                                            </td>
                                            <td>
                                                Encryption<a href="?sort=encry&ord=ASC&from={$from}&to={$inc}"><img height="15" width="15" border="0" src="themes/{$wifidb_theme}/img/down.png"></a>
                                                <a href="?sort=encry&ord=DESC&from={$from}&to={$inc}"><img height="15" width="15" border="0"src="themes/{$wifidb_theme}/img/up.png"></a>
                                            </td>
                                        </tr>
                                        {foreach name=outer item=wifidb_ap from=$wifidb_aps_all}
                                        <tr class="{$wifidb_ap.class}">
                                            <td align="center">
                                                <a class="links" href="{$wifidb_host_url}opt/fetch.php?id={$wifidb_ap.id}">{$wifidb_ap.ssid}</a>
                                            </td>
                                            <td align="center">
                                                {$wifidb_ap.mac}
                                            </td>
                                            <td align="center">
                                                {$wifidb_ap.chan}
                                            </td>
                                            <td align="center">
                                                {$wifidb_ap.radio}
                                            </td>
                                            <td align="center">
                                                {$wifidb_ap.auth}
                                            </td>
                                            <td align="center">
                                                {$wifidb_ap.encry}
                                            </td>
                                        </tr>
                                        {foreachelse}
                                        <tr>
                                            <td align="center" colspan="6">
                                                <b>There are no Access Points imported as of yet, go grab some with Vistumbler and import them.<br />
                                                Come on... you know you want too.</b>
                                            </td>
                                        </tr>
                                        {/foreach}
                                        <tr class="sub_head">
                                            <td colspan="6" align="center">
                                             {$pages_together}
                                            </td>
                                        </tr>
                                    </table>
{include file="footer.tpl"}