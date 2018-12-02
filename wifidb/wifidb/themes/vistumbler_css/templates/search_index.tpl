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
		<h2>Search for Access Points</h2>
                <form action="{$wifidb_host_url}opt/results.php?ord=ASC&amp;sort=ssid&amp;from=0&amp;to=25" method="post" enctype="multipart/form-data">
                    <table border="0" cellpadding="5">
                        <thead>
                            <tr>
                                <th align="right">SSID:</th>
                                <th align="left">
                                    <input type="text" name="ssid" size="40" id="ssid" onkeyup="doCompletion();"/>
                                </th>
                            </tr>
                            <tr>
                                <th align="right">MacAddress:</th>
                                <th align="left">
                                    <input type="text" name="mac" size="40" id="mac" onkeyup="doCompletion();"/>
                                </th>
                            </tr>
                            <tr>
                                <th align="right">Authentication:</th>
                                <th align="left">
                                    <input type="text" name="auth" size="40" id="auth" onkeyup="doCompletion();"/>
                                </th>
                            </tr>
                            <tr>
                                <th align="right">Encryption:</th>
                                <th align="left">
                                    <input type="text" name="encry" size="40" id="encry" onkeyup="doCompletion();"/>
                                </th>
                            </tr>
                            <tr>
                                <th align="right">Radio Type:</th>
                                <th align="left">
                                    <input type="text" name="radio" size="40" id="radio" onkeyup="doCompletion();"/>
                                </th>
                            </tr>
                            <tr>
                                <th align="right">Channel:</th>
                                <th align="left">
                                    <input type="text" name="chan" size="40" id="chan" onkeyup="doCompletion();"/>
                                </th>
                            </tr>
                            <tr>
                                <td align="center" colspan="2">
                                    <input type="submit" name="submit" value="Submit" style="width: 0.71in; height: 0.36in"/>
                                </td>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td align="center" id="auto-row" colspan="2">
                                    <table class="popupBox" style="display:none"></table>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </form>
<script language="JavaScript">
var req;
var isIE;

var ssidField;
var macField;
var authField;
var encryField;
var radioField;
var chanField;
var completeField;
var completeTable;
var autoRow;

init();
function init() {
    ssidField = document.getElementById("ssid");
    macField = document.getElementById("mac");
    authField = document.getElementById("auth");
    encryField = document.getElementById("encry");
    radioField = document.getElementById("radio");
    chanField = document.getElementById("chan");

    completeTable = document.createElement("table");
    completeTable.setAttribute("class", "popupBox");
    completeTable.setAttribute("style", "display: none");
    autoRow = document.getElementById("auto-row");
    autoRow.appendChild(completeTable);
    completeTable.style.top = getElementY(autoRow) + "px";
}

function doCompletion() {
    var url = "searchxml.php?action=complete&ssid=" + escape(ssidField.value)
            + "&mac=" + escape(macField.value)
            + "&auth=" + escape(authField.value)
            + "&encry=" + escape(encryField.value)
            + "&radio=" + escape(radioField.value)
            + "&chan=" + escape(chanField.value);
    req = initRequest();
    req.open("GET", url, true);
    req.onreadystatechange = callback;
    req.send(null);
}

function initRequest() {
    if (window.XMLHttpRequest) {
        if (navigator.userAgent.indexOf('MSIE') != -1) {
            isIE = true;
        }
        return new XMLHttpRequest();
    } else if (window.ActiveXObject) {
        isIE = true;
        return new ActiveXObject("Microsoft.XMLHTTP");
    }else {
        return 0;
    }
}

function callback() {
    clearTable();
    if (req.readyState == 4) {
        if (req.status == 200) {
            parseMessages(req.responseXML);
        }
    }
}

function append(apID,ssid,mac,auth,encry,radio,chan,rowclass) {

    var row;
    var cell;
    var linkElement;
    if (isIE) {
        completeTable.style.display = 'block';
        row = completeTable.insertRow(completeTable.rows.length);
        row.className = rowclass;
        cell = row.insertCell(0);
    } else {
        completeTable.style.display = 'table';
        row = document.createElement("tr");
        cell = document.createElement("td");
        row.appendChild(cell);
        row.className = rowclass;
        completeTable.appendChild(row);
    }

    row.className = "popupCell";

    linkElement = document.createElement("a");
    linkElement.className = "popupItem";
    linkElement.setAttribute("href", "fetch.php?id=" + apID);
    linkElement.appendChild(document.createTextNode(ssid));
    cell.appendChild(document.createTextNode(ssid));

    cell1 = document.createElement("td");
    row.appendChild(cell1);
    completeTable.appendChild(row);
    cell1.appendChild(document.createTextNode(mac));

    cell2 = document.createElement("td");
    row.appendChild(cell2);
    completeTable.appendChild(row);
    cell2.appendChild(document.createTextNode(auth));

    cell3 = document.createElement("td");
    row.appendChild(cell3);
    completeTable.appendChild(row);
    cell3.appendChild(document.createTextNode(encry));

    cell4 = document.createElement("td");
    row.appendChild(cell4);
    completeTable.appendChild(row);
    cell4.appendChild(document.createTextNode(radio));

    cell5 = document.createElement("td");
    row.appendChild(cell5);
    completeTable.appendChild(row);
    cell5.appendChild(document.createTextNode(chan));

    cell6 = document.createElement("td");
    row.appendChild(cell6);
    completeTable.appendChild(row);

    imgElement1 = document.createElement("img");
    imgElement1.className = "popupItem";
    imgElement1.setAttribute("src", "../img/arrow_right.png");
    imgElement1.setAttribute("width", "24px");

    linkElement1 = document.createElement("a");
    linkElement1.setAttribute("href", "fetch.php?id=" + apID);
    linkElement1.appendChild(imgElement1);

    cell6.appendChild(linkElement1);
}

function clearTable() {
    if (completeTable.getElementsByTagName("tr").length > 0) {
        completeTable.style.display = 'none';
        for (loop = completeTable.childNodes.length -1; loop >= 0 ; loop--) {
            completeTable.removeChild(completeTable.childNodes[loop]);
        }
    }
}

function getElementY(element){

    var targetTop = 0;

    if (element.offsetParent) {
        while (element.offsetParent) {
            targetTop += element.offsetTop;
            element = element.offsetParent;
        }
    } else if (element.y) {
        targetTop += element.y;
    }
    return targetTop;
}

function parseMessages(responseXML) {

    // no matches returned
    if (responseXML == null) {
        return false;
    } else {

        var waps = responseXML.getElementsByTagName("waps")[0];

        if (waps.childNodes.length > 0) {
            completeTable.setAttribute("bordercolor", "black");
            completeTable.setAttribute("border", "1");
            var flip;
            var myclass;
            for (loop = 0; loop < waps.childNodes.length; loop++) {
                var ap = waps.childNodes[loop];
                if(flip == 0)
                {
                    myclass = 'dark';
                    flip=0;
                }else{
                    myclass = 'light';
                    flip=0;
                }
                var apId = ap.getElementsByTagName("id")[0];
                var ssid = ap.getElementsByTagName("ssid")[0];
                var mac = ap.getElementsByTagName("mac")[0];
                var auth = ap.getElementsByTagName("auth")[0];
                var encry = ap.getElementsByTagName("encry")[0];
                var radio = ap.getElementsByTagName("radio")[0];
                var chan = ap.getElementsByTagName("chan")[0];
                append(apId.childNodes[0].nodeValue,
                        ssid.childNodes[0].nodeValue,
                        mac.childNodes[0].nodeValue,
                        auth.childNodes[0].nodeValue,
                        encry.childNodes[0].nodeValue,
                        radio.childNodes[0].nodeValue,
                        chan.childNodes[0].nodeValue,
                        myclass
                );
            }
        }
        return true;
    }
}
</script>

{include file="footer.tpl"}