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

$list		=   (@$_GET['ActiveBSSIDs'] ? $_GET['ActiveBSSIDs'] : "");
$dbcore->output =   (@$_GET['output'] ? strtolower($_GET['output']) : "raw");
if($list == ''){ die("Try feeding me some good bits."); }

$listing        =   array();
$lists          =   explode("-", $list);

foreach($lists as $key=>$item)
{
    $t = explode("|", $item);
    $listing[$key] = array($t[1],$t[0]);
}

$listing = $dbcore->subval_sort($listing,1);

$dbcore->Locate($listing);

if(!@count($use))
{
    echo "\r\n+Import some aps";
}else{
    $dbcore->Output();
    /*
    switch($dbcore->output)
    {
	case "xml":
	    echo "<xml>\r\n\t<locate>\r\n\t\t<lat>".$use['lat']."</lat>\r\n\t\t<long>".$use['long']."</long>\r\n\t\t<sats>".$use['sats']."</sats>\r\n\t\t<date>".$use['date']."</date>\r\n\t\t<time>".$use['time']."</time>\r\n\t</locate>\r\n</xml>";
	    break;
	default:
	    echo $use['lat']."|".$use['long']."|".$use['sats']."|".$use['date']."|".$use['time'];
	    break;
    }
     * 
     */
}



?>