<?php
error_reporting(E_ALL|E_STRICT|E_DEPRECATED);



$folder_list = $argv[1];
echo "Folder List: ".$argv[1]."\r\n";

$folder_list = explode(":", $folder_list);

var_dump($folder_list);
die();

$date = date("Y-m-d-H-i-s");
$buildfolder = "./build-{$date}/";

if(is_dir($buildfolder))
{
    recurse_del($buildfolder);
    echo "removed old folder of same name\r\n";
}

$command_base = "convert ";
$command_end = "+append ./build/pano_group_";

for($i = 0; $i < $count_data; )
{
    echo "\r\n---------------------------------------------------\r\n";
    echo $i."\r\n";
    $Front_Right = "./Images/".str_replace(" ", "\ ", $data[$i][3])." ";
    $Left = "./Images/".str_replace(" ", "\ ", $data[$i+1][3])." ";
    $Front_Left = "./Images/".str_replace(" ", "\ ", $data[$i+2][3])." ";
    $Front = "./Images/".str_replace(" ", "\ ", $data[$i+3][3])." ";

    $command = $command_base.$Left.$Front_Left.$Front.$Front_Right.$command_end.str_pad($data[$i][1], 6, 0, STR_PAD_LEFT).".jpg";
    echo $command."\r\n";

    #exec($command, $output, $return_var);
    #var_dump($return_var);
    #var_dump($output);

    echo "\r\n---------------------------------------------------\r\n";
    $i=$i+4;
}





function recurse_del($path)
{
    echo "Opening Folder: $path\r\n";
    if ($handle = opendir($path))
    {
        while (false !== ($entry = readdir($handle)))
        {
            if($entry == "." || $entry == ".."){continue;}
            if(is_dir($path."/".$entry))
            {
                echo "Is a Folder.\r\n";
                recurse_del($path."/".$entry);
            }else
            {
                echo "Delete: ".$path."/".$entry."\r\n";
                unlink($path."/".$entry);
            }
        }
        closedir($handle);
        rmdir($path);
    }
}
?>
