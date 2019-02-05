<?php
/*
fetch.php, fetches a single AP's details.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "EXPORT");

include('../lib/init.inc.php');
$id = $_GET['id'];

$results = $dbcore->APFetch($id);
$dbcore->smarty->assign('wifidb_page_label', "Access Point Page ({$results[0]})");
$dbcore->smarty->assign('wifidb_assoc_lists', $results[1]);
$dbcore->smarty->assign('wifidb_ap', $results[2]);
$dbcore->smarty->assign('wifidb_geonames', $results[3]);
$dbcore->smarty->display('fetch.tpl');

?>