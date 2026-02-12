<?php
/*
/api/v2/import.php
Copyright (C) 2016 Phil Ferland

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
define("SWITCH_EXTRAS", "apiv2");

include('../../lib/init.inc.php');

if(isset($_REQUEST['func'])){$func = $_REQUEST['func'];}else{$func=NULL;}
switch($func)
{
		case "check_hash":
            $hash = (empty($_REQUEST['hash'])) ? NULL : $_REQUEST['hash'];
            $dbcore->CheckHash(strtolower($hash));
			$dbcore->Output();
		default:
			if($dbcore->rebuild)
			{
				$dbcore->Output("Imports are disabled because the database is being rebuilt. Please stay in your seat until the ride has come to a complete stop.");
			}
			$date = date($dbcore->datetime_format);
			$title = (empty($_REQUEST['title'])) ? $date : $_REQUEST['title'];
			$notes = (empty($_REQUEST['notes'])) ? "No Notes" : $_REQUEST['notes'];
			$otherusers = (empty($_REQUEST['otherusers'])) ? "" : $_REQUEST['otherusers'];

            if(!@$_FILES['file']['tmp_name']){ $dbcore->mesg = array("error"=> "No upload file found :("); $dbcore->Output();}

			$tmp  = $_FILES['file']['tmp_name'];
			$size = $_FILES['file']['size'];
			if($size == "0"){$dbcore->mesg = array("error"=> "Size of file is only 0B, come one man...." ); $dbcore->Output();}

			$hash           =   hash_file('md5', $tmp);
			$prefilename    =   str_replace(" ", "_", $_FILES['file']['name']);
			$path_parts     =   pathinfo($prefilename);
			$ext            =   strtolower(@$path_parts['extension']);
			// If a CSV upload, try to detect Wigle CSV format by peeking at the file contents
			if($ext === 'csv'){
				$first_lines = @file($tmp, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
				if($first_lines && count($first_lines) > 0){
					$line0 = $first_lines[0];
					$line1 = (count($first_lines) > 1) ? $first_lines[1] : "";
					// Wigle CSV files include a pre-header like "WigleWifi-1.6" on the first line,
					// and a header row with "MAC" and "SSID" on the next line. Detect that.
					if(strpos($line0, 'WigleWifi') !== false || (stripos($line1, 'MAC') !== false && stripos($line1, 'SSID') !== false)){
						$ext = 'wiglecsv';
					}
				}
			}
			$rand           =   rand(00000000, 99999999);
			$uploadfolder   =   $dbcore->PATH.'import/up/';
			$filename       =   'APIupload_'.$rand.'_'.$prefilename;
			$file_orig 	=	$_FILES['file']['name'];
			$uploadfile     =   $uploadfolder.$filename;

			if(!copy($tmp, $uploadfile))
			{ $dbcore->mesg = array("error"=> 'Failure to Move file to Upload Dir ('.$uploadfolder.'), check the folder permissions if you are using Linux.'); $dbcore->Output();}

			chmod($uploadfile, 0600);

			$details = array(
				'title'=>$title,
				'user'=>$dbcore->username,
				'apikey'=>$dbcore->apikey,
				'otherusers'=>$otherusers,
				'size'=>$dbcore->format_size($size),
				'notes'=>$notes,
				'ext'=>$ext,
				'file_name'=>$filename,
				'file_orig'=>$file_orig,
				'hash'=>$hash,
				'file_date'=>$date
			);

			$dbcore->ImportVS1($details);
			$dbcore->Output();
}
