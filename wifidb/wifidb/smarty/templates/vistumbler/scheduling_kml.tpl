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
					<table width="700px" border="0" cellspacing="0" cellpadding="0" align="center">
                        <tr>
                            <td>
                                <table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
                                    <tr>
                                        <td class="style4">Daemon Generated KML<br><font size="2">All times are local system time.</font></td>
                                    </tr>
                                </table>
                                <table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
                                    <tr class="style4">
                                        <td class="daemon_kml" colspan="4">
                                            {$wifidb_kml_head.update_kml}
                                        </td>
                                    </tr>
                                    <tr class="dark">
                                        <th></th>
                                        <th>Download link</th>
                                        <th style="width: 43%">Date & Time</th>
                                        <th style="width: 11%">Size</th>
                                    </tr>
                                    <tr class="light">
                                        <th rowspan="2">Newest AP</th>
                                        <th style="width: 148px"><a href="{$wifidb_kml_head.newest_labeled_link}">Labeled</a></th>
                                        <td style="width: 43%; text-align: center">{$wifidb_kml_head.newest_labeled_date}</td>
                                        <td style="width: 11%; text-align: center">{$wifidb_kml_head.newest_labeled_size}</td>
                                    </tr>
                                    <tr class="light">
                                        <th style="width: 148px"><a href="{$wifidb_kml_head.newest_link}">Not Labeled</a></th>
                                        <td style="width: 43%; text-align: center">{$wifidb_kml_head.newest_date}</td>
                                        <td style="width: 11%; text-align: center">{$wifidb_kml_head.newest_size}</td>
                                    </tr>
                                    <tr class="dark">
                                        <th rowspan="2">Full DB</th>
                                        <th style="width: 148px"><a href="{$wifidb_kml_head.full_labeled_link}">Labeled</a></th>
                                        <td style="width: 43%; text-align: center">{$wifidb_kml_head.full_labeled_date}</td>
                                        <td style="width: 11%; text-align: center">{$wifidb_kml_head.full_labeled_size}</td>
                                    </tr>
                                    <tr class="dark">
                                        <th style="width: 148px"><a href="{$wifidb_kml_head.full_link}">Not Labeled</a></th>
                                        <td style="width: 43%; text-align: center">{$wifidb_kml_head.full_date}</td>
                                        <td style="width: 11%; text-align: center">{$wifidb_kml_head.full_size}</td>
                                    </tr>
                                    <tr class="light">
                                        <th rowspan="2">Daily DB</th>
                                        <th style="width: 148px"><a href="{$wifidb_kml_head.daily_labeled_link}">Labeled</a></th>
                                        <td style="width: 43%; text-align: center">{$wifidb_kml_head.daily_labeled_date}</td>
                                        <td style="width: 11%; text-align: center">{$wifidb_kml_head.daily_labeled_size}</td>
                                    </tr>
                                    <tr class="light">
                                        <th style="width: 148px"><a href="{$wifidb_kml_head.daily_link}">Not Labeled</a></th>
                                        <td style="width: 43%; text-align: center">{$wifidb_kml_head.daily_date}</td>
                                        <td style="width: 11%; text-align: center">{$wifidb_kml_head.daily_size}</td>
                                    </tr>
                                </table>
                                <br/>
                                <table border="1" cellspacing="0" cellpadding="0" style="width: 100%">
                                    <tr>
                                        <th colspan="3" class="style4">History</th>
                                    </tr>
                                    <tr class="style4">
                                        <td width="33%">Date</td>
                                        <td width="33%">Full DB KML</td>
                                        <td width="33%">Daily KML</td>
                                    </tr>
                                    <tr>
                                        <td colspan="5" class="dark">
                                    {foreach item=wifidb_kml from=$wifidb_kml_all_array}
                                            <table align="center" border="1" cellspacing="0" cellpadding="0" width="100%">
                                                <tr class="{$wifidb_kml.class}">
                                                    <td align="center" width="33%">
                                                        {$wifidb_kml.file}
                                                    </td>
                                                    <td width="33%">
                                                        <a class="links" href="{$wifidb_kml.file_url}">{$wifidb_kml.file_name}</a> - {$wifidb_kml.full_size}
                                                        <br/>
                                                        <a class="links" href="{$wifidb_kml.file_label_url}">{$wifidb_kml.file_label_name}</a> - {$wifidb_kml.full_size_label}
                                                    </td>
                                                    <td width="33%">
                                                        <a class="links" href="{$wifidb_kml.daily_url}">{$wifidb_kml.daily_name}</a> - {$wifidb_kml.daily_size}
                                                        <br/>
                                                        <a class="links" href="{$wifidb_kml.daily_label_url}">{$wifidb_kml.daily_label_name}</a> - {$wifidb_kml.daily_size_label}
                                                    </td>
                                                </tr>
                                            </table>
                                        {foreachelse}
                                            There are no KML files that have been generated yet.
                                        {/foreach}
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
{include file="footer.tpl"}