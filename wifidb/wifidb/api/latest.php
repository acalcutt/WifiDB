<?php
/*
Copyright (C) 2015 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');

if((int)@$_REQUEST['labeled'] === 1){$labeled = 1;}else{$labeled = 0;}
if((int)@$_REQUEST['xml'] === 1){$xml = 1;}else{$xml = 0;}
$download = (empty($_REQUEST['download'])) ? 'latest.kmz' : $_REQUEST['download'];

$results = $dbcore->export->ExportCurrentAPkmlApi($labeled);

if($xml)
{
	header("Content-type: text/xml");
}
else
{
	header('Content-Type: application/octet-stream');
	header('Content-Disposition: attachment; filename="'.$download.'"');
	$dbcore->Zip->addFile($results, 'doc.kml');
	$results = $dbcore->Zip->getZipData();
}

echo $results;

?>
