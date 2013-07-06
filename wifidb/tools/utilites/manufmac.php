<?php
$ver = "1.3.0";
$LastEdit = "2013-July-4";
$author = "pferland";
$stime = time();

echo "-----------------------------------------------------------------------
| Starting creation of Vistumbler compatible Wireless Router Manuf List.
| By: $author
| Last Edit: $LastEdit
| http:\\\\www.wifidb.net
| Version: $ver
";

$debug = 0;
$cwd = getcwd();

$source="http://standards.ieee.org/develop/regauth/oui/oui.txt";
$manuf_list = array();
$phpfile = "manufactures.inc.php";
$vs1file = "manufactures.ini";

echo "Downloading and Opening the Source File from: \n----->".$source."\n|\n|";
$return = file($source);

$total_lines = count($return);

echo $total_lines."\r\n";
echo "Source File opened; starting conversion.\n|\n|";

foreach($return as $key=>$ret)
{
    #if($key == 40){die();}
    if($ret == "" || $ret == "  \r\n"){continue;}

    preg_match("/([A-F0-9].+)     (\(base 16\))\t\t([a-zA-Z].+)/", $ret, $matches);
	
    if(@$matches[2] != "(base 16)")
    {
        if($debug === 1)
        {
            echo "Erroneous data found, dropping\n| This is normal...\n| ";
        }
        continue;
    }
    if($matches[3] == "PRIVATE"){echo "Non Needed Manuf found...\n| ";continue;}

    $manuf_list[] = array(
                        "mac"   => str_pad($matches[1], 6, "0"),
                        "manuf" => $matches[3]
                    );
}
$total_manuf = count($manuf_list);
if($total_manuf > 0)
{
    echo "Manufactures and MAC Address' Found: $total_manuf\r\n";
}else{
    die("No Manufactures found... :(");
}

echo "Write Manufactures File for both Vistumbler and WiFiDB:\r\n";
$php_data = "<?php\r\n$"."manufactures=array(\r\n";
$ini_data = ";This file allows you to assign a manufacturer to a mac address(first 6 digits).\r\n[MANUFACTURERS]\r\n";
foreach($manuf_list as $manuf)
{
    #WiFiDB PHP Array
    $php_data .= '"'.$manuf['mac'].'"=>"'.addslashes($manuf['manuf']).'",'."\r\n";
    if($debug === 1){	echo $write."\n| ";}
    #Vistumbler INI
    if($debug == 1){echo $write."\r\n";}
    $ini_data .= $manuf['mac']."=".$manuf['manuf']."\r\n";
}
$php_data .= ");\r?>";

file_put_contents($vs1file, $ini_data);
file_put_contents($phpfile, $php_data);
#------------------------------------------------------------------------------------------------------#

$etime = time();
$diff_time = $etime - $stime;
$lines_p_min = $total_lines/$diff_time;
    echo "Total Manufactures found: ".$total_manuf."\n----------------\n"
    ."Start Time:.......".$stime."\n"
    ."End Time:.........".$etime."\n"
    ."Total Run Time:...".$diff_time."\n----------------\n"
    ."Total Lines:......".$total_lines."\n"
    ."Lines per min:....".$lines_p_min."\n"
	."----------------\nDone";
	
	
function file_put_contents($filename, $data, $file_append = false) {
  $fp = fopen($filename, (!$file_append ? 'w+' : 'a+'));
	if(!$fp) {
	  trigger_error('file_put_contents cannot write in file.', E_USER_ERROR);
	  return;
	}
  fputs($fp, $data);
  fclose($fp);
}