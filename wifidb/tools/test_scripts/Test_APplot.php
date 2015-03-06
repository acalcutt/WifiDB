<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "export");
error_reporting("E_ALL");

require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );
$dbcore->verbosed("Testing KML Plot AP 3D Signal Track.");

switch($argv[1])
{
    case "user":
        $data = $dbcore->UserList((int)$argv[2]);

        #$tmp_data = $dbcore->SingleAP(604);
        #$dbcore->createKML->LoadData($tmp_data[1]);


        $dbcore->createKML->LoadData($data);
        $date = date("Y-m-d_H-i-s");

        $folder_data = $dbcore->createKML->createFolder($dbcore->createKML->PlotAllAPs(4, 1, 0), $argv[3]);
        $kml_data = $dbcore->createKML->createFolder($folder_data, "WiFiDB Export on ".$date);
        $dbcore->createKML->createKML("test/".$argv[3]."_".rand(000000,999999), $kml_data);
    break;

    case "ap":
        $row = (int)$argv[2];

        $sql = "SELECT * FROM `wifi`.`user_imports` WHERE `id` = ?";
        $prep = $dbcore->sql->conn->prepare($sql);
        $prep->bindParam(1, $row, PDO::PARAM_INT);
        $prep->execute();
        $dbcore->sql->checkError(__LINE__, __FILE__);
        $fetch = $prep->fetch();

        if($fetch['points'] == "")
        {
            throw new ErrorException("User Import selected is empty, try again.");
        }

        $points = explode("-", $fetch['points']);
        foreach($points as $point)
        {
            $tmp_data = $dbcore->SingleAP((int)$point);
            var_dump($tmp_data[1][$tmp_data[0]]['ssid']);
            #die();
            $dbcore->createKML->LoadData($tmp_data[1]);

            $date = date("Y-m-d_H-i-s");

            $folder_data = $dbcore->createKML->createFolder($dbcore->createKML->PlotAllAPs(3, 1, 0), $tmp_data[1][$tmp_data[0]]['ssid']);
            $kml_data = $dbcore->createKML->createFolder($folder_data, "WiFiDB Export on ".$date);
            $dbcore->createKML->createKML("test/".$tmp_data[1][$tmp_data[0]]['ssid']."_".rand(000000,999999), $kml_data);

        }
    break;

    case "single":
        $tmp_data = $dbcore->SingleAP((int)$argv[2]);
        var_dump($tmp_data[1][$tmp_data[0]]['ssid']);
        #die();
        $dbcore->createKML->LoadData($tmp_data[1]);

        $date = date("Y-m-d_H-i-s");

        $folder_data = $dbcore->createKML->createFolder($dbcore->createKML->PlotAllAPs(3, 1, 0), $tmp_data[1][$tmp_data[0]]['ssid']);
        $kml_data = $dbcore->createKML->createFolder($folder_data, "WiFiDB Export on ".$date);
        $dbcore->createKML->createKML("test/".$tmp_data[1][$tmp_data[0]]['ssid']."_".rand(000000,999999), $kml_data);
    break;

    default:
        echo "Unknown switch given.\r\nusage: Test_APplot3D.php [user|ap] [###] [title for user export]\r\n";
    break;
}
