<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "export");

if(!(require('../daemon/config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
$wdb_install = $daemon_config['wifidb_install'];
if($wdb_install == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require($wdb_install)."/lib/init.inc.php";

$header = '<?xml version="1.0"?>
<file_events>
';
$footer = "</file_events>";
$events = "";
$result = $dbcore->sql->conn->query("SELECT `points`,`title`,`username`,`date`,`aps` FROM `users_imports` order by `id` DESC");
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
            $conn2 = new mysqli("192.168.1.18", "root", "saNsui20si", "wifi");
            $points_explode = explode("-", $array[0]);
            echo "User: ".$array[2]."\r\nDate: ".$array[3]."\r\nTitle: ".$array[1]."\r\n";
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

                $pointer_result = $conn2->query("SELECT `sectype` FROM `wifi0` WHERE `id`='$id'");
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
