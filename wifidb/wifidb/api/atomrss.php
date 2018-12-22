<?php
/*
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
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');
include("../lib/FeedWriter.php");

$TestFeed = new FeedWriter(ATOM);
$TestFeed->setTitle('Wireless Database ATOM RSS Feed');
$TestFeed->setLink($dbcore->URL_PATH."api/atomrss.php");
$TestFeed->setChannelElement('updated', date(DATE_ATOM , time()));
$TestFeed->setChannelElement('author', array('name'=>$dbcore->WDBadmin));

if($dbcore->sql->service == "mysql")
	{$query = "SELECT * FROM `files` ORDER BY `id` DESC";}
else if($dbcore->sql->service == "sqlsrv")
	{$query = "SELECT * FROM [files] ORDER BY [id] DESC";}
$result = $dbcore->sql->conn->query($query);

while($row = $result->fetch(2))
{
    $newItem = $TestFeed->createNewItem();
    $newItem->setTitle('"'.$row['user'].'" Imported \''.$row['title'].'\'');
    $newItem->setLink($dbcore->URL_PATH."opt/userstats.php?func=useraplist&amp;row=".$row['user_row']);
    $newItem->setDate(strtotime($row['date']));
    $newItem->setDescription("User: ".$row['user']."<br />
Title: ".$row['title']."<br />
Date: ".$row['date']."<br />
Filename: ".$row['file']."<br />
File Size: ".$row['size']." kb<br />
Notes: ".$row['notes']."<br />
<a href='".$dbcore->URL_PATH."opt/userstats.php?func=useraplist&amp;row=".$row['user_row']."'>Link</a>");
    $TestFeed->addItem($newItem);
}
$TestFeed->genarateFeed();
?>
