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

$fileToZip="License.txt";
$fileToZip1="CreateZipFileMac.inc.php";
$fileToZip2="CreateZipFile.inc.php";

$directoryToZip="./"; // This will zip all the file(s) in this present working directory

$outputDir="/"; //Replace "/" with the name of the desired output directory.
$zipName="test.zip";


// Code to Zip a single file
$dbcore->CreateZipFile->addDirectory($outputDir);
$fileContents=file_get_contents($fileToZip);
$dbcore->CreateZipFile->addFile($fileContents, $outputDir.$fileToZip);

$createZipFile->forceDownload($zipName);
@unlink($zipName);


/*

//Code toZip a directory and all its files/subdirectories
$createZipFile->zipDirectory($directoryToZip,$outputDir);

$rand=md5(microtime().rand(0,999999));
$zipName=$rand."_".$zipName;
$fd=fopen($zipName, "wb");
$out=fwrite($fd,$createZipFile->getZippedfile());
fclose($fd);
$createZipFile->forceDownload($zipName);
@unlink($zipName);
*/

