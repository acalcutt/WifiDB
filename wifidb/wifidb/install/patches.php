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

include('../lib/database.inc.php');
pageheader("Patches Page");
echo '<title>Wireless DataBase *Alpha*'.$ver["wifidb"].' --> </title>';
echo '<h2>Patching scripts for WiFiDB<h4><b>----------------------------------------------</b>';
echo '<h4>If you have GPS points that are blank, or If your GPS dates are in the format [MM-DD-YYYY], Alter them to [YYYY-MM-DD], go <a class="links" href="patch_blank_gps/">here</a></h4><b>----------------------------------------------</b>';
echo '<h4>To prevent GPS points from having null data in the fields named `hdp` , `alt` , `geo` , kmh` , and `track`. Need to update `lu`, and `fa` to support miliseconds, go <a class="links" href="patch_gps_table/patch_gps_tbl.php">here</a><BR>This is a quick and dirty script that goes through every %_GPS table and alters the columns from the wrong type (float() to varchar(255), float was having issues with some of the values)<h4>';
footer($_SERVER['SCRIPT_FILENAME'])
?>