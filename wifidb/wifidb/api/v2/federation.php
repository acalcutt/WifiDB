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

var_dump($_REQUEST);


switch(strtolower($_REQUEST['func']))
{
    case "listservers":
        echo "federation servers list</br>----------------------------------</br>";

        foreach($dbcore->federation->GetFedServersList() as $server)
        {
            foreach($server as $key=>$item)
            {
                var_dump($key, $item);
                echo "</br>";
            }
            echo "--------------</br>";
        }
    break;


    case "listdatatypes":
        echo "This Servers Supported Data Search Types.</br>----------------------------------</br>";
        foreach($dbcore->federation->DataTypes as $dataType)
        {
            echo $dataType." </br>\r\n";
        }
    break;

    default:
        $result = 1;
        $dbcore->mesg['error'] = "Unknown Function type. Whats the deal here, are you messing with my 2 bit mind?";
        break;
}

