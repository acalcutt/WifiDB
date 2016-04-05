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

    case "getdata":

        switch(strtolower( $_REQUEST['DataType'] ))
        {
            case "users":
                $users = $dbcore->federation->GetLocalUsers();
                var_dump($users);
            break;

            case "user":
                $username = $dbcore->federation->GetUserNameFromID((int)$_REQUEST['userid']);
                var_dump($username);

                $userdata = $dbcore->federation->GetLocalUserLists($username);
                var_dump($userdata);
            break;

            case "userlist":
                $importdata = $dbcore->federation->GetUserListData( (int)$_REQUEST['ImportID'] );
                var_dump($importdata);
            break;

            case "aps":
                $APs = $dbcore->federation->GetAPsList();
                var_dump($Aps);
            break;

            case "ap":
                $APData = $dbcore->federation->GetAPData();
                var_dump($APData);
            break;
        }
    break;

    case "searchdata":

        switch(strtolower( $_REQUEST['SearchType'] ))
        {
            case "user":

                break;
            case "userlist":

                break;
            case "ap":

                break;
        }
        break;
}

