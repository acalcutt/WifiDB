<?php
$switches = array('extras'=>'','screen'=>"CLI");

if(!(require('config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$dbcore->PATH = $daemon_config['wifidb_install'];
if($dbcore->PATH == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($dbcore->PATH)."/lib/init.inc.php";

$dbcore->verbosed("
WiFiDB 'Geoname Daemon'
Version: 2.0.0
- Daemon Start: 2010-06-23
- Last Daemon File Edit: 2011-Dec-13
( /tools/daemon/geonamed.php )
- By: Phillip Ferland ( pferland@randomintervals.com )
- http://www.randomintervals.com/wifidb/
");
$thread_id = @$argv[1];
$threads = @$argv[2];
$dbcore->verbosed("Start Gather of WiFiDB GeoNames");



if($thread_id != NULL)
{
    $dbcore->verbosed("Calculating the Threads rows to update...");
    $sql = "SELECT `id` FROM `wifi`.`wifi_pointers`";
    $result = $dbcore->sql->conn->query($sql);
    $rows = $result->rowCount();
    $thread_rows_each = round($rows/$threads);
    if($thread_id == 1)
    {
        $from = $thread_id;
        $inc = $thread_rows_each;
    }else
    {
        $thread_calc = ($thread_id-1)*$thread_rows_each;
        $from = $thread_calc;
        $inc = $thread_rows_each;
    }
    $sql = "SELECT `id`,`lat`,`long`,`ap_hash` FROM `wifi`.`wifi_pointers` WHERE `geonames_id` = '' AND `lat` != 'N 0.0000' AND `lat` != 'N 0000.0000' AND `lat` != 'N 0.0000000' ORDER BY `id` ASC LIMIT {$from}, {$inc}";
}else
{
    $sql = "SELECT `id`,`lat`,`long`,`ap_hash` FROM `wifi`.`wifi_pointers` WHERE `geonames_id` = '' AND `lat` != 'N 0.0000' AND `lat` != 'N 0000.0000' AND `lat` != 'N 0.0000000' ORDER BY `id` ASC";
}
echo $sql."\r\n";
$result = $dbcore->sql->conn->query($sql);
$dbcore->verbosed("Gathered Wtable data");
#$ap_array = $result->fetchall(PDO::FETCH_ASSOC);
echo "Rows that need updating: ".$result->rowCount()."\r\n";
sleep(4);
while($ap = $result->fetch(1))
{
#    var_dump($ap);
    $dbcore->verbosed($ap['id']." - ".$ap['ap_hash']);
    $lat = round($dbcore->convert_dm_dd($ap['lat']), 1);
    $long = round($dbcore->convert_dm_dd($ap['long']), 1);
    #if($lat == "0")
    #{
    #    var_dump($ap);
    #    die();
    #}
    $dbcore->verbosed("Lat - Long: ".$lat." [----] ".$long);
    $sql = "SELECT `geonameid`, `country code`, `admin1 code`, `admin2 code` FROM `wifi`.`geonames` WHERE `latitude` LIKE '$lat%' AND `longitude` LIKE '$long%' LIMIT 1";
    $dbcore->verbosed("Query Geonames Table to see if there is a location in an area that is equal to the geocord rounded to the first decimal.", 3);
    $geo_res = $dbcore->sql->conn->query($sql);
    $geo_array = $geo_res->fetch(PDO::FETCH_ASSOC);
    if(!$geo_array['geonameid'])
    {continue;}
    
    $dbcore->verbosed("Geoname ID: ".$geo_array['geonameid']);
    $admin1_array = array('id'=>'');
    $admin2_array = array('id'=>'');
    if($geo_array['admin1 code'])
    {
        $dbcore->verbosed("Admin1 Code is Numeric, need to query the admin1 table for more information.");
        $admin1 = $geo_array['country code'].".".$geo_array['admin1 code'];
        
        $sql = "SELECT `id` FROM `wifi`.`geonames_admin1` WHERE `admin1`='$admin1'";
        $admin1_res = $dbcore->sql->conn->query($sql);
        $admin1_array = $admin1_res->fetch(PDO::FETCH_ASSOC);
    }
    if(is_numeric($geo_array['admin2 code']))
    {
        $dbcore->verbosed("Admin2 Code is Numeric, need to query the admin2 table for more information.");
        $admin2 = $geo_array['country code'].".".$geo_array['admin1 code'].".".$geo_array['admin2 code'];
        $sql = "SELECT `id` FROM `wifi`.`geonames_admin2` WHERE `admin2`='$admin2'";
        $admin2_res = $dbcore->sql->conn->query($sql);
        $admin2_array = $admin2_res->fetch(PDO::FETCH_ASSOC);
    }

    $sql = "UPDATE `wifi`.`wifi_pointers` SET `geonames_id` = '{$geo_array['geonameid']}', `admin1_id` = '{$admin1_array['id']}', `admin2_id` = '{$admin2_array['id']}' WHERE `ap_hash` = '{$ap['ap_hash']}'";
#    echo $sql."\r\n";
    #die();
    if($dbcore->sql->conn->query($sql))
    {
        $dbcore->verbosed("Updated AP's Geolocation  [{$ap['id']}] ({$ap['ap_hash']})" , 2);
    }else
    {
        $dbcore->verbosed("Failed to update AP's Geolocation [{$ap['id']}] ({$ap['ap_hash']})", -1);
        var_dump($dbcore->sql->conn->errorInfo());
    }
    #die();
}




/*
 * 
SELECT
`wifi_pointers`.`id`,
`wifi_pointers`.`ssid`,
`wifi_pointers`.`mac`,
`wifi_pointers`.`chan`,
`wifi_pointers`.`radio`,
`wifi_pointers`.`auth`,
`wifi_pointers`.`encry`,
`wifi_pointers`.`lat`,
`wifi_pointers`.`long`,
`wifi_pointers`.`BTx`,
`wifi_pointers`.`OTx`,
`wifi_pointers`.`NT`,
`wifi_pointers`.`label`,
`wifi_pointers`.`FA`,
`wifi_pointers`.`LA`,
`wifi_pointers`.`username`,
`geonames`.`country code`,
`geonames_admin1`.`asciiname`,
`geonames_admin2`.`asciiname`
FROM
`wifi_pointers`,
`geonames`,
`geonames_admin1`,
`geonames_admin2`
WHERE
(
`wifi_pointers`.`geonames_id` = `geonames`.`geonameid` AND
`wifi_pointers`.`admin1_id` = `geonames_admin1`.`id` AND
`wifi_pointers`.`admin2_id` = `geonames_admin2`.`id` AND
`wifi_pointers`.`id` = '1200'
)
 *
 */
?>