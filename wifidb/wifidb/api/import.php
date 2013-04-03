<?php
global $switches;
$switches = array('screen'=>"HTML",'extras'=>'API');

include('../lib/init.inc.php');
$title = (empty($_REQUEST['title'])) ? "Untitled" : $_REQUEST['title'];
$notes = (empty($_REQUEST['notes'])) ? "No Notes" : $_REQUEST['notes'];

$date = date($dbcore->datetime_format);
$otherusers = (empty($_REQUEST['otherusers'])) ? "" : $_REQUEST['otherusers'];

if(!@$_FILES['file']['tmp_name']){$dbcore->Output("No upload file found :(");}

$tmp  = $_FILES['file']['tmp_name'];
$size = $dbcore->format_size($tmp);
if($size == "0B"){die("Size of file is only 0B, come one man....");}

$hash           =   hash_file('md5', $tmp);
$prefilename    =   str_replace(" ", "_", $_FILES['file']['name']);
$file_ext       =   explode('.', $prefilename);
$ext            =   strtolower($file_ext[1]);
$rand           =   rand(00000000, 99999999);
$uploadfolder   =   $dbcore->PATH.'import/up/';
$filename       =   'APIupload_'.$rand.'_'.$prefilename;
$uploadfile     =   $uploadfolder.$filename;

if(!copy($tmp, $uploadfile)){$dbcore->Output('Failure to Move file to Upload Dir ('.$uploadfolder.'), check the folder permisions if you are using Linux.');}

chmod($uploadfile, 0600);

$details = array(
    'title'=>$title,
    'user'=>$dbcore->username,
    'otherusers'=>$otherusers,
    'size'=>$size,
    'notes'=>$notes,
    'ext'=>$ext,
    'filename'=>$filename,
    'hash'=>$hash,
    'date'=>$date
);

$dbcore->ImportVS1($details);
$dbcore->Output();
?>
