<?php
error_reporting(E_ALL);

$file = $argv[1];
echo "Argvar 1: ".$argv[1]."\r\n";

$file_exp = explode(".", $file);
$filename = $file_exp[0];
if(is_dir("./".$filename."/"))
{
    echo "Folder ($filename) already exists, will not over write, ending.\r\n";
    die();
}

$zip = new ZipArchive;
if ($zip->open($file) === TRUE) {
    $zip->extractTo("./$filename/");
    $zip->close();
} else {
    die("Failed to extract the image files.");
}

echo "Extract Finished! Folder is : $filename
Lets open the data file and see what we have: $filename/data.csv\r\n";

if(!file_exists("$filename/Data.csv")){exit("$filename/Data.csv Does not exist, you must have done something wrong, or the script cannot read the file.\r\n");}

echo "Data.csv is there, lets make the build folder now.\r\n";
if(file_exists($filename."/build/")){recurse_del("./".$filename."/build"); mkdir("./".$filename."/build/");}
else{mkdir("./".$filename."/build/");}

if (($handle = fopen($file, "r")) !== FALSE)
{
    $data = array();
    $kv = 0;
    $r=0;
    $command  = "";
    while (($data1 = fgetcsv($handle, 1000, ",")) !== FALSE)
    {
        if($data1[0] == 0){continue;}
        $data[] = $data1;
        var_dump($data1);
        if($r===0){echo "|\r";}
        if($r===10){echo "/\r";}
        if($r===20){echo "-\r";}
        if($r===30){echo "\\\r";}
        if($r===40){echo "|\r";}
        if($r===50){echo "/\r";}
        if($r===60){echo "-\r";}
        if($r===70){echo "\\\r";$r=0;}
        $r++;
    }
    fclose($handle);
    echo "Done Reading file\r\n";
}
$count_data = count($data)+0;
echo $count_data."\r\n";

$command_base = "convert ";
$command_end = "+append ./build/pano_group_";
for($i = 0;$i < $count_data;)
{
    echo "\r\n---------------------------------------------------\r\n";
    #var_dump($data[$i]);
    echo $i."\r\n";
    $Front_Right = "./Images/".str_replace(" ", "\ ", $data[$i][3])." ";
    $Left = "./Images/".str_replace(" ", "\ ", $data[$i+1][3])." ";
    $Front_Left = "./Images/".str_replace(" ", "\ ", $data[$i+2][3])." ";
    $Front = "./Images/".str_replace(" ", "\ ", $data[$i+3][3])." ";

    $command = $command_base.$Left.$Front_Left.$Front.$Front_Right.$command_end.str_pad($data[$i][1], 6, 0, STR_PAD_LEFT).".jpg";
    echo $command."\r\n";

    exec($command, $output, $return_var);
    var_dump($return_var);
    var_dump($output);

    echo "\r\n---------------------------------------------------\r\n";
    $i=$i+4;
}
