<?php
/*
Copyright (C) 2015 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "export");

include('../lib/init.inc.php');

$labeled = $_REQUEST['labeled'];
if($labeled == 1){$labeled = 1;}else{$labeled = 0;}
$download = $_REQUEST['download'];

$results = $dbcore->export->ExportCurrentAPkmlApi($labeled);

if($download){header('Content-Disposition: attachment; filename="'.$download.'"');}
echo $results;

?>