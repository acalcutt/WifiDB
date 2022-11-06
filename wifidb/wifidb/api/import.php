<?php
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');

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
			$file_ext       =   explode('.', $prefilename);
			$ext            =   strtolower($file_ext[1]);
			$rand           =   rand(00000000, 99999999);
			$uploadfolder   =   $dbcore->PATH.'import/up/';
			$filename       =   'APIupload_'.$rand.'_'.$prefilename;
			$file_orig		=	$_FILES['file']['name'];
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
