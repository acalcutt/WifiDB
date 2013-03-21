<?php
#Database.inc.php, holds the database interactive functions.
#Copyright (C) 2011 Phil Ferland
#
#This program is free software; you can redistribute it and/or modify it under the terms
#of the GNU General Public License as published by the Free Software Foundation; either
#version 2 of the License, or (at your option) any later version.
#
#This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
#without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
#See the GNU General Public License for more details.
#
#You should have received a copy of the GNU General Public License along with this program;
#if not, write to the
#
#   Free Software Foundation, Inc.,
#   59 Temple Place, Suite 330,
#   Boston, MA 02111-1307 USA

global $screen_output;
$screen_output = 'CLI';
include('../lib/database.inc.php');
echo '<title>Wireless DataBase *Alpha* '.$ver["wifidb"].' --> Install Page</title>';
?>
<link rel="stylesheet" href="../themes/wifidb/styles.css">
<body topmargin="10" leftmargin="0" rightmargin="0" bottommargin="10" marginwidth="10" marginheight="10">
<div align="center">
<table border="0" width="75%" cellspacing="10" cellpadding="2">
	<tr>
		<td bgcolor="#315573">
		<p align="center"><b><font size="5" face="Arial" color="#FFFFFF">
		Wireless DataBase *Alpha* <?php echo $ver["wifidb"]; ?></font>
		<font color="#FFFFFF" size="2">
            <a class="links" href="/">[Root] </a>/ <a class="links" href="/wifidb/">[WifiDB] </a>/
		</font></b>
		</td>
	</tr>
</table>
</div>
<div align="center">
<table border="0" width="75%" cellspacing="10" cellpadding="2" height="90"><tr>
<td width="17%" bgcolor="#304D80" valign="top">
<!--LINKS-->
</td>

<td width="80%" bgcolor="#A9C6FA" valign="top" align="center">
<!--BODY-->
<h2>WiFiDB Install / Upgrade / Or Patch</h2>
  <h4>Please Read <a class="links" target="_blank" href="notes.html">these notes</a> before doing anything.</h4>
<?php

if(function_exists('gd_info'))
{$gd = gd_info();	echo '<table><tr class="style4"><td><b><font color=#00ff00>GD Version: '.$gd['GD Version'].', is installed</font></b></td></tr></table>';}
else{ echo '<table><tr class="style4"><td><b><font color=#ff0000>You Do Not Have GD or GD2 installed, please install this or you will not beable to use the graphing feature!</font></b></td></tr></table>';}

if(class_exists(ZipArchive))
{echo '<table><tr class="style4"><td><b><font color=#00ff00>ZipArchive class is installed</font></b></td></tr></table>';}
else{ echo '<table><tr class="style4"><td><b><font color=#ff0000>You Do Not Have the ZipArchive class installed, please install this or you will not beable to use the Export Feature or the Daemon Generated KML.</font></b></td></tr></table>';}
?>
<table border="0"cellspacing="0" cellpadding="3">

  <tr>
    <td colspan="2" >Install WiFiDB from <a class="links" href="index2.php">scratch</a></td></tr>
  <tr>
    <td colspan="2" >Upgrade WiFiDB from a <a class="links" href="upgrade/">previous version</a></td></tr>
</TABLE>
<?php
$filename = $_SERVER['SCRIPT_FILENAME'];
$file_ex = explode("/", $filename);
$count = count($file_ex);
$file = $file_ex[($count)-1];
?>
</p>
</td>
</tr>
<tr>
<td bgcolor="#315573" height="23"><a href="../img/moon.png"><img border="0" src="../img/moon_tn.png"></a></td>
<td bgcolor="#315573" width="0" align="center">
<?php
if (file_exists($filename)) {?>
	<h6><i><u><?php echo $file;?></u></i> was last modified:  <?php echo date ("Y F d @ H:i:s", filemtime($filename));?></h6>
<?php
}
?>
</td>
</tr>
</table>
</body>
</html>