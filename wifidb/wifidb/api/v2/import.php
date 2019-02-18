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
			$title = (empty($_REQUEST['title'])) ? "Untitled" : $_REQUEST['title'];
			$notes = (empty($_REQUEST['notes'])) ? "No Notes" : $_REQUEST['notes'];

			$date = date($dbcore->datetime_format);
			$otherusers = (empty($_REQUEST['otherusers'])) ? "" : $_REQUEST['otherusers'];

            if(!@$_FILES['file']['tmp_name']){ $dbcore->mesg = array("error"=> "No upload file found :("); $dbcore->Output();}

			$tmp  = $_FILES['file']['tmp_name'];
			$size = $_FILES['file']['size'];
			if($size == "0"){$dbcore->mesg = array("error"=> "Size of file is only 0B, come one man...." ); $dbcore->Output();}

			$hash           =   hash_file('md5', $tmp);
			$prefilename    =   str_replace(" ", "_", $_FILES['file']['name']);
			$file_ext       =   explode('.', $prefilename);
			$ext            =   strtolower($file_ext[1]);
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
				'otherusers'=>$otherusers,
				'size'=>$dbcore->format_size($size),
				'notes'=>$notes,
				'ext'=>$ext,
				'filename'=>$filename,
				'file_orig'=>$file_orig,
				'hash'=>$hash,
				'date'=>$date
			);

			$dbcore->ImportVS1($details);
			$dbcore->Output();
}
