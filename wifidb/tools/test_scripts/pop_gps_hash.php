<?php
$dsn = 'mysql:host=172.16.1.28';
$user = 'root';
$password = 'W!res191';
$conn = new PDO($dsn, $user, $password);
$result = $conn->query("SELECT id,gps_id FROM `wifi`.`wifi_signals`");
$i=0;
while($array = $result->fetch(2))
{
    $i++;
    echo "---------------------\r\n{$i}\r\n";
    $result1 = $conn->query("SELECT date,time FROM `wifi`.`wifi_gps` where `id` = {$array['gps_id']}");
    $array2 = $result1->fetch(2);
    $sql = "UPDATE `wifi`.`wifi_signals` SET `date`='{$array2['date']}', `time` = '{$array2['time']}' WHERE `id` = '{$array['id']}' ";
    $conn->query($sql);
    $err = $conn->errorInfo();
    
    if($err[0] != '00000')
    {
        die();
        var_dump($err);
    }
}
?>