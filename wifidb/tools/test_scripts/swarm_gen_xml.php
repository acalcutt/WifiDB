<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");

$details = 0;
error_reporting(E_ALL|E_STRICT);
date_default_timezone_set('EST');

$header = '<?xml version="1.0"?>
<file_events>
';
$footer = "</file_events>";
$events = "";

require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$result = $dbcore->sql->conn->query("SELECT `points`,`title`,`username`,`date`,`aps` FROM `wifi`.`users_imports` order by `id` DESC");
#$array = $result->fetch_row();
#var_dump($array);
#die();
while($array = $result->fetch_row())
{
    $date = strtotime($array[3]);
    if($date == ""){continue;}
    switch($details)
    {
        case 1:
            $i = $array[4]+0;
            echo "User: ".$array[2]."\r\nDate: ".$date."\r\nTitle: ".$array[1]."\r\nAPs: ".$i."\r\n";
            while($i!=0)
            {
                #echo $i." ";
                $event = '<event date="'.$date.'000" filename="'.$array[1].$i.'" author="'.$array[2].'" />';
                $events .= $event."\r\n";
                echo ".";
                $i--;
            }
            break;
        default:
            $i = 0;
            $points_explode = explode("-", $array[0]);
            echo "User: ".$array[2]."\r\nDate: ".$array[3]."\r\nTitle: ".$array[1]."\r\n";
            $conn2 = new mysqli("172.16.1.18", "root", "saNsui20si", "wifi");
            foreach($points_explode as $key=>$point)
            {
                
                $point_exp = explode(",", $point);
                if(!@$point_exp[1])
                {
                    var_dump($point);
                    continue;
                }
                $id_exp = explode(":", $point_exp[1]);
                $id = $id_exp[0];
                
                $sql = "SELECT `sectype` FROM `wifi`.`wifi0` WHERE `id`='$id'";
                
                echo "\r\n{$sql}\r\n";
                
                $pointer_result = $conn2->query($sql);
                $ap_array = $pointer_result->fetch_row();
                $pointer_result->free();
                switch ($ap_array[0])
                {
                    case "1":
                        $sec_type = "open";
                        break;
                    case "2":
                        $sec_type = "wep";
                        break;
                    case "3":
                        $sec_type = "sec";
                        break;
                    default:
                        $sec_type = "open";
                        break;
                }
                $event = '<event date="'.$date.'000" filename="'.$id.'.'.$sec_type.'" author="'.$array[2].'" />';
                $events .= $event."\r\n";
                #if($key != 0) echo "-";
                #echo $id;
                $i++;
            }
            echo "APs: ".$i;
            #die();
            break;
    }
    echo "\r\n\r\n------------------------------------------------------------------\r\n\r\n";
}
$xml_all = $header.$events.$footer;
file_put_contents("wifidb_imports.xml", utf8_encode($xml_all));
?>
