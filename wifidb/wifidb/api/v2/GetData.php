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

#var_dump($_REQUEST);

switch(strtolower($_REQUEST['func']))
{
    case "users":
        $result = $dbcore->GetLocalUsers();
    break;

    case "userdata":
        $username = $dbcore->GetUserNameFromID((int)$_REQUEST['userid']);
        $result = $dbcore->GetLocalUserData($username);
    break;

    case "imports":
        $result = $dbcore->GetLocalImports();
        break;

    case "importdata":
        $result = $dbcore->GetLocalUserListData( (int)$_REQUEST['ImportID'] );
    break;

    case "aps":
        $result = $dbcore->GetAPsList();
    break;

    case "ap":
        $result = $dbcore->GetAPData( (int)$_REQUEST['APID'] );
    break;

    default:
        $result = 1;
        $dbcore->mesg['error'] = "Unknown Function type. Do we really have to go over this again?";
        break;
}

#var_dump($result);
$dbcore->Output();