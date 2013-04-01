<?php
global $switches;
$switches = array('screen'=>"HTML",'extras'=>'API');

include('../lib/init.inc.php');
$api_key = (empty($_REQUEST['apikey'])) ? "" : $_REQUEST['apikey'];
$title = (empty($_REQUEST['title'])) ? "Untitled" : $_REQUEST['title'];
$notes = (empty($_REQUEST['notes'])) ? "No Notes" : $_REQUEST['notes'];
$user = (empty($_REQUEST['username'])) ? "Unknown" : $_REQUEST['username'];

$dbcore->output = (@$_REQUEST['output'] ? strtolower($_REQUEST['output']) : "json");
$date = date($dbcore->datetime_format);
$otherusers = (empty($_REQUEST['otherusers'])) ? "" : $_REQUEST['otherusers'];

$key_check = $dbcore->sec->ValidateAPIKey($user, $api_key);
if(!$key_check[0]){$dbcore->Output($key_check[1]);}

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
    'user'=>$user,
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
