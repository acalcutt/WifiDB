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
<form action="genline.php" method="post" enctype="multipart/form-data">
    <h1>Graph an Access Points Signal history *Beta*</h1>
    <table style="width: 500px" cellspacing="3" cellpadding="0" class="style3">
        <tr>
            <td class="style2">
                <input name="ssid" type="hidden" value="{$AP_data.ssid}">
                <input name="mac" type="hidden" value="{$AP_data.mac}">
                <input name="man" type="hidden" value="{$AP_data.man}">
                <input name="auth" type="hidden" value="{$AP_data.auth}">
                <input name="encry" type="hidden" value="{$AP_data.encry}">
                <input name="radio" type="hidden" value="{$AP_data.radio}">
                <input name="chan" type="hidden" value="{$AP_data.chan}">
                <input name="lat" type="hidden" value="{$AP_data.lat}">
                <input name="long" type="hidden" value="{$AP_data.long}">
                <input name="btx" type="hidden" value="{$AP_data.btx}">
                <input name="otx" type="hidden" value="{$AP_data.otx}">
                <input name="fa" type="hidden" value="{$AP_data.fa}">
                <input name="lu" type="hidden" value="{$AP_data.lu}">
                <input name="nt" type="hidden" value="{$AP_data.nt}">
                <input name="label" type="hidden" value="{$AP_data.label}">
                <input name="sig" type="hidden" value="{$AP_data.sig}">
                <input name="name" type="hidden" value="{$AP_data.name}">
                Choose Graph Type:
                <select name="line" style="height: 22px; width: 139px">
                    <option{if $AP_data.line eq 'bar'} selected{/if} value="bar">Bar (Vertical)</option>
                    <option{if $AP_data.line eq 'line'} selected{/if} value="line">Line (Horizontal)</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="style2">
                Choose Text Color:
                <select name="text" style="height: 22px; width: 147px">
                    <option{if $AP_data.text eq '255:000:000'} selected{/if} value="255:000:000">Red</option>
                    <option{if $AP_data.text eq '000:255:000'} selected{/if} value="000:255:000">Green</option>
                    <option{if $AP_data.text eq '000:000:255'} selected{/if} value="000:000:255">Blue</option>
                    <option{if $AP_data.text eq '000:000:000'} selected{/if} value="000:000:000">Black</option>
                    <option{if $AP_data.text eq '255:255:000'} selected{/if} value="255:255:000">Yellow</option>
                    <option{if $AP_data.text eq '255:128:000'} selected{/if} value="255:128:000">Orange</option>
                    <option{if $AP_data.text eq '128:064:000'} selected{/if} value="128:064:000">Brown</option>
                    <option{if $AP_data.text eq '000:255:255'} selected{/if} value="000:255:255">Sky Blue</option>
                    <option{if $AP_data.text eq '064:000:128'} selected{/if} value="064:000:128">Purple</option>
                    <option{if $AP_data.text eq '128:128:128'} selected{/if} value="128:128:128">Grey</option>
                    <option{if $AP_data.text eq '226:012:243'} selected{/if} value="226:012:243">Pink</option>
                    <option{if $AP_data.text eq 'rand'} selected{/if} value="rand">Random</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="style2">
                Choose Background Color:
                <select name="bgc" style="height: 22px; width: 147px">
                    <option{if $AP_data.bgc eq '000:000:000'} selected{/if} value="000:000:000">Black</option>
                    <option{if $AP_data.bgc eq '255:255:255'} selected{/if} value="255:255:255">White</option>
                </select>

            </td>
        </tr>
        <tr>
            <td class="style2">
                Choose Graph Color:
                <select name="linec" style="width: 153px">
                    <option{if $AP_data.linec eq '255:000:000'} selected{/if} value="255:000:000">Red</option>
                    <option{if $AP_data.linec eq '000:255:000'} selected{/if} value="000:255:000">Green</option>
                    <option{if $AP_data.linec eq '000:000:255'} selected{/if} value="000:000:255">Blue</option>
                    <option{if $AP_data.linec eq '000:000:000'} selected{/if} value="000:000:000">Black</option>
                    <option{if $AP_data.linec eq '255:255:000'} selected{/if} value="255:255:000">Yellow</option>
                    <option{if $AP_data.linec eq '255:128:000'} selected{/if} value="255:128:000">Orange</option>
                    <option{if $AP_data.linec eq '128:064:000'} selected{/if} value="128:064:000">Brown</option>
                    <option{if $AP_data.linec eq '000:255:255'} selected{/if} value="000:255:255">Sky Blue</option>
                    <option{if $AP_data.linec eq '064:000:128'} selected{/if} value="064:000:128">Purple</option>
                    <option{if $AP_data.linec eq '28:128:128'} selected{/if} value="128:128:128">Grey</option>
                    <option{if $AP_data.linec eq '226:012:243'} selected{/if} value="226:012:243">Pink</option>
                    <option{if $AP_data.linec eq 'rand'} selected{/if} value="rand">Random</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="style2">
                <input name="Submit1" type="submit" value="Generate Graph" />
            </td>
        </tr>
    </table>
</form>

You can find your Wifi Graph here -> <a class="links" href="{$wifidb_host_url}{$graph_ret.1}">{$graph_ret.1}</a>
<br />
<img src="{$wifidb_host_url}{$graph_ret.1}"/>

{include file="footer.tpl"}