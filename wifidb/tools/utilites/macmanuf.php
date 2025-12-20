#!/usr/bin/php
<?php
$source = 'http://standards-oui.ieee.org/oui.txt';
$author = 'Andrew Calcutt';
$ver = '0.1';
$debug = 0;

echo "-----------------------------------------------------------------------\n";
echo "| Starting import of manufacturers into the wifidb.\n| By: $author\n| https:\\\\wifidb.net\n| Version: $ver\n";

$manuf_list = array();

echo "Downloading and Opening the Source File from: \n----->".$source."\n|\n|";
$oui_text = file_get_contents($source);
$oui_arr = explode(PHP_EOL,$oui_text);
$total_lines = count($oui_arr);


foreach($oui_arr as $ret)
{
	
	$test = substr($ret, 11, 5);
	if ($test != "(hex)"){if($debug === 1){echo "Erroneous data found, dropping\n| This is normal...\n| ";} continue;}
	$retexp = explode("(hex)",$ret);
	$Man_mac = trim($retexp[0]);
	$man_mac = explode("-",$Man_mac);
	$Man_mac = implode("",$man_mac);
	$Manuf = trim($retexp[1]);
	echo $Man_mac." - ".$Manuf.PHP_EOL;
	if($Manuf == "PRIVATE")
    {
        if($debug)
        {
            echo "Unneeded Manuf found...\n| ";
        }
        continue;
    }
	$manuf_list[] = array(
						"mac" 	=> $Man_mac,
						"manuf"	=> addslashes($Manuf)
						);
}

$total_manuf = count($manuf_list);
if(!($total_manuf > 0))
{
    die("No Manufactures were found..\n");
}
echo $total_manuf." Manufactures and MAC Address' found...\n";
