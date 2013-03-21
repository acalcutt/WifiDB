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

$out		=   (@$_GET['output'] ? $_GET['output'] : "json");
$lat		=   (@$_GET['lat'] ? $_GET['lat'] : 0);
$long		=   (@$_GET['long'] ? $_GET['long'] : 0);
$username       =   (@$_GET['username'] ? $_GET['username'] : "" );
$apikey         =   (@$_GET['apikey'] ? $_GET['apikey'] : "");
$dbcore->output = $out;

if($lat == "" OR $long == "")
{
    $dbcore->mesg[] = "No lat or long supplied...";
    $dbcore->Output();
}

$result = $dbcore->sec->ValidateAPIKey($username, $apikey);
if(is_array($result))
{
    $dbcore->mesg[] = $result[1];
    $dbcore->Output();
}else
{
    $dbcore->GeoNames($lat,$long);
    $dbcore->Output();
}

?>