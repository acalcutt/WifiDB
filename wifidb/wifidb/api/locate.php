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

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'API');

include('../lib/init.inc.php');
$ver = "1.0.3";

$list		=   (@$_REQUEST['ActiveBSSIDs'] ? $_REQUEST['ActiveBSSIDs'] : "");
$dbcore->output =   (@$_REQUEST['output'] ? strtolower($_REQUEST['output']) : "json");
if($list == ''){ die("Try feeding me some good bits."); }

$listing        =   array();
$lists          =   explode("-", $list);

foreach($lists as $key=>$item)
{
    $t = explode("|", $item);
    $listing[$key] = array($t[1],$t[0]);
}

$listings = $dbcore->subval_sort($listing,1);

if(!@count($dbcore->Locate($listings)))
{
    echo "Import some aps";
}
else
{
    $dbcore->Output();
}



?>