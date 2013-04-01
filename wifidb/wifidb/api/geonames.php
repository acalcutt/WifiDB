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

$out		=   (@$_REQUEST['output'] ? $_REQUEST['output'] : "json");
$lat		=   (@$_REQUEST['lat'] ? $_REQUEST['lat'] : 0);
$long		=   (@$_REQUEST['long'] ? $_REQUEST['long'] : 0);
$username       =   (@$_REQUEST['username'] ? $_REQUEST['username'] : "" );
$apikey         =   (@$_REQUEST['apikey'] ? $_REQUEST['apikey'] : "");
$dbcore->output = $out;

if($lat == "" || $long == "")
{
    $dbcore->mesg = "No lat or long supplied...";
    $dbcore->Output();
}

$result = $dbcore->sec->ValidateAPIKey($username, $apikey);
if(!$result[0])
{
    $dbcore->Output($result[1]);
}else
{
    $dbcore->GeoNames($lat,$long);
    $dbcore->Output();
}

?>