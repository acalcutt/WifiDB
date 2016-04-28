<?php
/**
 * Created by PhpStorm.
 * User: pferland
 * Date: 4/16/2016
 * Time: 8:20 PM
 */

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "apiv2");

include('../../lib/init.inc.php');

#var_dump($_REQUEST);

switch(strtolower( $_REQUEST['func'] ))
{
    case "users":
        $dbcore->SearchUsers($_REQUEST['Value']);
        break;

    case "userlists":
        $dbcore->SearchUserList($_REQUEST['title'], $_REQUEST['user'], $_REQUEST['min_ap'], $_REQUEST['max_ap'], $_REQUEST['min_gps'], $_REQUEST['max_gps'], $_REQUEST['min_date'], $_REQUEST['max_date']);
        break;

    case "ap":
        $dbcore->Search($_REQUEST['ssid'], $_REQUEST['mac'], $_REQUEST['radio'], $_REQUEST['chan'], $_REQUEST['auth'], $_REQUEST['encry']);
        break;

    default:
        $result = 1;
        $dbcore->mesg['error'] = "Unknown Function type. Come on man, again?";
        break;
}

$dbcore->Output();