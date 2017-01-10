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
        --geoname
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
if(!(require('/etc/wifidb/daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

echo "Config files loaded. Going to try and connect to the SQL Server...\r\n";
$conn = mysqli_connect($config['host'], $config['db_user'], $config['db_pwd'], "wifi");
if(mysqli_connect_errno())
{
    die(mysqli_connect_errno());
}
echo "Connected, now lets LOAD ALL THE DATA!!!!\r\n";
foreach ($contents as $line)
{
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
            $sql = "INSERT INTO `wifi`.`geonames_admin1` (`id`, `admin1`, `name`, `asciiname`, `geonameid`)
                VALUES ('', '{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}')";
            break;
        case "geoname":
            $sql = "INSERT INTO `wifi`.`geonames` (`id`, `geonameid`, `name`, `asciiname`, `alternatenames`, `latitude`, `longitude`,
    `feature_class`, `feature_code`, `country_code`, `cc2`, `admin1_code`, `admin2_code`, `admin3_code`, `admin4_code`,
    `population`, `elevation`, `gtopo30`, `timezone`, `mod_date`)
    VALUES ('', '{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}','{$row[4]}','{$row[5]}','{$row[6]}','{$row[7]}','{$row[8]}','{$row[9]}','{$row[10]}','{$row[11]}','{$row[12]}','{$row[13]}','{$row[14]}','{$row[15]}','{$row[16]}','{$row[17]}','{$row[18]}');";
            break;
        case "admin2":
            $sql = "INSERT INTO `wifi`.`geonames_admin2` (`id`, `admin2`, `name`, `asciiname`, `geonameid`)
                VALUES ('', '{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}')";
            break;
        case "countrynames":
            $sql = "INSERT INTO `wifi`.`geonames_country_names` (`id`, `ISO`, `ISO3`, `ISO-Numeric`, `fips`, `Country`, `Capital`, `Area`, `Population`, `Continent`, `tld`, `CurrencyCode`, `CurrencyName`, `Phone`, `Postal Code Format`, `Postal Code Regex`, `Languages`, `geonamesid`, `neighbors`, `EquivalentFipsCode`)
                VALUES ('', '{$row[0]}','{$row[1]}','{$row[2]}','{$row[3]}','{$row[4]}','{$row[5]}','{$row[6]}','{$row[7]}','{$row[8]}','{$row[9]}','{$row[10]}','{$row[11]}','{$row[12]}','{$row[13]}','{$row[14]}','{$row[15]}','{$row[16]}','{$row[17]}','{$row[18]}');";
            break;
    }
    echo $sql."\r\n";
    #var_dump($row);
    #echo count($row)."\r\n";
    #die();

    if(!mysqli_query($conn, $sql))
    {
        echo mysqli_error($conn)."\r\n";
        die();
        #mysqli_close($conn);
        echo ".";
    }
    else
    {
        echo "$II Inserted!\r\n";
    }
    $sql = "";
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
mysqli_close($conn);






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
?>






<!--
CREATE TABLE `wifi`.`geonames_country_names` 
(
    `id` int(255) NOT NULL AUTO_INCREMENT, 
    `ISO` varchar(255) NOT NULL, 
    `ISO3` varchar(255) NOT NULL, 
    `ISO-Numeric` varchar(255) NOT NULL, 
    `fips` varchar(255) NOT NULL, 
    `Country` varchar(255) NOT NULL, 
    `Capital` varchar(255) NOT NULL, 
    `Area` varchar(255) NOT NULL, 
    `Population` varchar(255) NOT NULL, 
    `Continent` varchar(255) NOT NULL, 
    `tld` varchar(255) NOT NULL, 
    `CurrencyCode` varchar(255) NOT NULL, 
    `CurrencyName` varchar(255) NOT NULL, 
    `Phone` varchar(255) NOT NULL, 
    `Postal Code Format` varchar(255) NOT NULL, 
    `Postal Code Regex` varchar(255) NOT NULL, 
    `Languages` varchar(255) NOT NULL, 
    `geonamesid` varchar(255) NOT NULL, 
    `neighbors` varchar(255) NOT NULL, 
    `EquivalentFipsCode` varchar(255) NOT NULL,
    
     PRIMARY KEY (`id`), 
     INDEX (`id`)
)
ENGINE = InnoDB CHARACTER SET utf8 COLLATE utf8_bin;

-->
