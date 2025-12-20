<?php
global $screen_output;
$screen_output = "CLI";

$ft_start = microtime(1);
error_reporting(E_ALL | E_STRICT);
$startdate = "18-Feb-2012";
$lastedit = "18-Feb-2012";
require 'daemon/config.inc.php';
require $GLOBALS['wifidb_install']."/lib/database.inc.php";
require $GLOBALS['wifidb_install']."/lib/config.inc.php";

$sql = "SELECT id, ssid, sig FROM `live_aps`";
$return = mysql_query($sql, $conn);
while($array = mysql_fetch_array($return))
{
    #var_dump($array);
    echo "AP: [{$array['id']}] {$array['ssid']}\r\n";
    $sig_exp = explode("|", $array['sig']);
    foreach($sig_exp as $sig_gpsid)
    {
        $sig_gps_exp = explode("-", $sig_gpsid);
        $gps_id = $sig_gps_exp[1];
        $sql_gps = "select * from live_gps where `id` = '$gps_id'";
        $return1 = mysql_query($sql_gps, $conn);
        $gps_array = mysql_fetch_array($return1);
        if($gps_array['lat'] == "N 0000.0000")
        {continue;}
        if($gps_array['lat'] === NULL){die("Server is gone...");}
        var_dump($gps_array['lat']);
        var_dump($gps_array['long']);
        
        $sql1 = "UPDATE `live_aps` SET `lat` = '{$gps_array['lat']}', `long` = '{$gps_array['long']}' WHERE `id` = '{$array['id']}'";
        if(mysql_query($sql1))
        {
            echo "Updated AP...\r\n";
            break;
        }else
        {
            echo "Failed Update....\r\n";
            echo mysql_error($conn);
            die();
        }
        echo "--------------------------\r\n";
    }
    
}
$ft_stop = microtime(1);

?>
