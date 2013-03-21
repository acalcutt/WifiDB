<?php
#ini_set("memory_limit","3072M");
$Access = array();
$handle = fopen("/root/locate.txt", "r");
if ($handle)
{
    $i =0;
    while(($f = trim(fgets($handle, 4096))) !== false)
    {
        echo $i."\r\n";
        $i++;
        #echo $f;
        $exp = explode(" - - ", $f);
        $ip = $exp[0];
#        echo $ip."\r\n";
        if(!@$exp[1])
        {
            echo $f."\r\n";
            continue;
        }
        $exp1 = explode(' "GET', $exp[1]);
        
        $exp2 = explode('"-" ', $exp1[1]);
        $type = trim($exp2[1]);
        $exp3 = explode('HTTP', $exp2[0]);
        $get = trim($exp3[0]);
#        echo $get."\r\n";
        
        if(@!$Access[$exp[0]])
        {
            $Access[$exp[0]] = array();
        }
        
        $Access[$ip]["get"] = $get;
        $Access[$ip]["type"] = $type;
    }
    if (!feof($handle)) {
        echo "Error: unexpected fgets() fail\n";
    }
}
#fclose($handle);
echo "Sorting Array.\r\n";
sort($Access);
var_dump($Access);
echo "Writing array to file.\r\n";
set_array_to_file("/root/locate_arrray.txt",$Access,$string="\$Access");


function has_no_sub_arrays($array)
{
   if (!is_array($array)) {
      return true;
   }
   foreach ($array as $sub) {
      if (is_array($sub)) {
         return false;
      }
   }
   return true;
}
function set_array_to_file($file,$array,$string="\$array")
{
    if(!is_resource($file))
    {
        $fp = fopen($file, "w");
        fwrite($fp,"<?php\r\n");
    }
    else
    {
        $fp = $file;
    }
    fwrite($fp,$string."=array();\r\n");
    foreach ($array as $ind => $val)
    {
        $str=$string."[".quote($ind)."]";
        if (is_array($val))
        {
            if (has_no_sub_arrays($val))
            {
		fwrite($fp,$str."=".compress_array($val).";\r\n");
            }else
	    {
		set_array_to_file($fp,$val,$str);
            }
        }else
	{
            fwrite($fp,$str."=".quote($val).";\r\n");
        }
    }
    if(!is_resource($file))
    {
        fwrite($fp,"?>");
        fclose($fp);
    }
}
?>
