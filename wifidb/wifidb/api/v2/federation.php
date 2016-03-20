<?php
/**
 * Created by PhpStorm.
 * User: pferland
 * Date: 3/20/2016
 * Time: 5:39 PM
 */

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "apiv2");

include('../../lib/init.inc.php');
echo "federation servers list</br>----------------------------------</br>";

$FedServers = $dbcore->federation->GetFedServersList();

foreach($FedServers as $server)
{
    foreach($server as $key=>$item)
    {
        var_dump($key, $item);
        echo "</br>";
    }
    echo "--------------</br>";
}