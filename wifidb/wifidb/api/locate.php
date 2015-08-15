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
define("SWITCH_SCREEN", "API");
define("SWITCH_EXTRAS", "locate");

include('../lib/init.inc.php');

$dbcore->LocateList     = (@$_REQUEST['ActiveBSSIDs'] ? $_REQUEST['ActiveBSSIDs'] : "");

if($dbcore->LocateList == ''){ die("Try feeding me some good bits."); }

$locate = $dbcore->Locate();

if(!@count($locate))
{
    $dbcore->Output(array("error"=>"Position Not Found, Import Some APs"));
}
else
{
    $dbcore->Output();
}



?>