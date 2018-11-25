<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

echo "Load Geonames Tables Information.\r\n";

if(!@$argv[2])
{
    echo "You need to define a file to load with your table.
    --file=geonames/allcountries.txt
    --file=geonames/admin1CodesASCII.txt
    --file=geonames/admin2Codes.txt
    --file=geonames/countryInfo.txt
    Get your files from: http://download.geonames.org/export/dump/ \r\n";
}
if(@!$argv[1])
{
    exit("Need to used one of these switches (and only one, and it hast to be the first one):\r\n
        --admin1
        --admin2
        --geonames
        --countrynames\r\n");
}

$file_exp = explode("=", $argv[2]);
$file = $file_exp[1];
$validate = array("admin1","admin2","geonames","countrynames");
$load = str_replace("-", "", $argv[1]);

$contents = file($file);
$r = 0;
$l = 0;
$II = 1;
$sql = "";
echo "Loading WiFiDB config files.\r\n";
if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

foreach ($contents as $line)
{
	$sql = "";
	$line = trim(str_replace (array("\r\n", "\n", "\r"), ' ', $line));
    echo $line."\r\n";
    $tab_array = explode("\t", $line);
    $row = filter_var_array($tab_array, FILTER_SANITIZE_STRING);
    if(!@$row[1] || $line == 50)
    {
        continue;
    }
#Insert Row into Geonames table.
    switch($load)
    {
        case "admin1":
            $sql = "INSERT INTO `geonames_admin1` (`admin1`, `name`, `asciiname`, `geonameid`)
                VALUES ('{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}')";
            break;
        case "geonames":
            $sql = "INSERT INTO `geonames` (`geonameid`, `name`, `asciiname`, `alternatenames`, `latitude`, `longitude`,
    `feature_class`, `feature_code`, `country_code`, `cc2`, `admin1_code`, `admin2_code`, `admin3_code`, `admin4_code`,
    `population`, `elevation`, `gtopo30`, `timezone`, `mod_date`)
    VALUES ('{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}','{$row[4]}','{$row[5]}','{$row[6]}','{$row[7]}','{$row[8]}','{$row[9]}','{$row[10]}','{$row[11]}','{$row[12]}','{$row[13]}','{$row[14]}','{$row[15]}','{$row[16]}','{$row[17]}','{$row[18]}');";
            break;
        case "admin2":
            $sql = "INSERT INTO `geonames_admin2` (`admin2`, `name`, `asciiname`, `geonameid`)
                VALUES ('{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}')";
            break;
        case "countrynames":
            $sql = "INSERT INTO `geonames_country_names` (`ISO`, `ISO3`, `ISO-Numeric`, `fips`, `Country`, `Capital`, `Area`, `Population`, `Continent`, `tld`, `CurrencyCode`, `CurrencyName`, `Phone`, `Postal Code Format`, `Postal Code Regex`, `Languages`, `geonamesid`, `neighbors`, `EquivalentFipsCode`)
                VALUES ('{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}','{$row[4]}','{$row[5]}','{$row[6]}','{$row[7]}','{$row[8]}','{$row[9]}','{$row[10]}','{$row[11]}','{$row[12]}','{$row[13]}','{$row[14]}','{$row[15]}','{$row[16]}','{$row[17]}','{$row[18]}');";
            break;
    }
    echo $sql."\r\n";
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->execute();
    
    $l = 0;
    $II++;
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

function parseArgs($argv){
    array_shift($argv);
    $out = array();
    foreach ($argv as $arg){
        if (substr($arg,0,2) == '--'){
            $eqPos = strpos($arg,'=');
            if ($eqPos === false){
                $key = substr($arg,2);
                $out[$key] = isset($out[$key]) ? $out[$key] : true;
            } else {
                $key = substr($arg,2,$eqPos-2);
                $out[$key] = substr($arg,$eqPos+1);
            }
        } else if (substr($arg,0,1) == '-'){
            if (substr($arg,2,1) == '='){
                $key = substr($arg,1,1);
                $out[$key] = substr($arg,3);
            } else {
                $chars = str_split(substr($arg,1));
                foreach ($chars as $char){
                    $key = $char;
                    $out[$key] = isset($out[$key]) ? $out[$key] : true;
                }
            }
        } else {
            $out[] = $arg;
        }
    }
    return $out;
}