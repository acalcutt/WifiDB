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
				<b>
                                    <font size="6">Shared Geocaches </font>
                                </b><br>
                                <table border="1" cellpadding="2" cellspacing="0" style="width: 95%">
                                    <tbody>
                                        <tr>
                                            <th class="style3">ID<a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=id&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/down.png"></a>
                                                <a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=id&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/up.png"></a>
                                            </th>
                                            <th class="style3">Name<a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=name&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/down.png"></a>
                                                <a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=name&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/up.png"></a>
                                            </th>
                                            <th class="style3">Lat<a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=lat&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/down.png"></a>
                                                <a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=lat&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/up.png"></a>
                                            </th>
                                            <th class="style3">Long<a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=long&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/down.png"></a>
                                                <a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=long&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/up.png"></a>
                                            </th>
                                            <th class="style3">Catagory<a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=cat&amp;ord=ASC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/down.png"></a>
                                                <a href="?func=boeyes&amp;boeye_func=list_all&amp;sort=cat&amp;ord=DESC&amp;from=0&amp;to=100"><img height="15" width="15" border="0" src="http://192.168.1.27/wifidb/themes/vistumbler/img/up.png"></a>
                                            </th>
                                        </tr>
                                        <tr class="light">
                                            <td colspan="5" align="center">
                                                <b>There are no Shared Geocaches. Go import some.</b>
                                            </td>
                                        </tr>
                                        <tr class="sub_head">
                                            <td colspan="5">
                                                <center><br/>Page: &lt;  [<a class="links" href="?func=boeyes&amp;boeye_func=list_all&amp;from=0&amp;to=100&amp;sort=id&amp;ord=ASC">First</a>] -  <i><u>1</u></i> -  [<a class="links" href="?func=boeyes&amp;boeye_func=list_all&amp;from=0&amp;to=100&amp;sort=id&amp;ord=ASC">Last</a>] &gt;</center>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
{include file="footer.tpl"}