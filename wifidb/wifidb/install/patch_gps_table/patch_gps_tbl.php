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

include('../../lib/config.inc.php');
mysql_select_db($db_st, $conn);
$result = mysql_query("show tables LIKE '%_GPS'", $conn);
while($array = mysql_fetch_row($result))
{
	$table = $array[0];
	echo $table."<BR>";
	$sql = "ALTER TABLE `$db_st`.`$table` MODIFY `hdp` varchar(255) NOT NULL ,MODIFY `alt` varchar(255) NOT NULL ,MODIFY `geo` varchar(255) NOT NULL ,MODIFY `kmh` varchar(255) NOT NULL ,MODIFY `mph` varchar(255) NOT NULL ,MODIFY`track` varchar(255) ,MODIFY `fa` varchar(255) NOT NULL ,MODIFY`lu` varchar(255) NOT NULL";
	$insert = mysql_query($sql, $conn);
	if($insert)
	{
		echo "Success..........Altered `$db_st`.`$table` to fix null gps data in the `hdp` , `alt` , `geo` , kmh` , and `track`<BR>";
	}
	else
	{
		echo "Failure..........Alter `$db_st`.`$table`;<br><br>".mysql_error($conn);
	}
}

?>