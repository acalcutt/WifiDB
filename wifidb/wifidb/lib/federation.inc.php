<?php

/**
 * Created by PhpStorm.
 * User: pferland
 * Date: 3/20/2016
 * Time: 5:35 PM
 */
class federation
{
    function __construct(&$dbcore, $config)
    {
        $this->sql                = &$dbcore->sql;
        $this->cli                = &$dbcore->cli;
        $this->mesg               = array();
        $this->DataTypes          = array("users", "userlists", "aps");
        $this->This_is_me         = $dbcore->This_is_me;
        $this->datetime_format    = $dbcore->datetime_format;
        $this->shared_key         = "";
        $this->FedHostURL         = "";
        $this->FedHostSharedKey   = "";
        $this->FedHostApiVer      = "";
    }

    function GetFedServersList()
    {
        $sql0 = "SELECT `id`, `FriendlyName`, `ServerAddress`, `APIVersion` FROM `federation_servers`";
        $result = $this->sql->conn->query($sql0);
        $this->sql->checkError($result, __LINE__, __FILE__);
        $newArray = $result->fetchAll(2);
        return $newArray;
    }

    function GetUserNameFromID($userid = 0)
    {
        $sql = "SELECT `username` FROM `user_info` WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $userid, PDO::PARAM_INT );
        $prep->execute();
        $this->sql->checkError( $prep, __LINE__, __FILE__);
        $user = $prep->fetch(2);

        return $user['username'];
    }

    function GetLocalUsers()
    {
        $sql = "SELECT `id`, `username` FROM `user_info` ORDER BY `username` ASC";
        $result = $this->sql->conn->query($sql);
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $users_all = $result->fetchAll(2);

        return $users_all;
    }

    function GetLocalUserLists($Username = "")
    {
        if($Username === "")
        {
            throw new Exception("Username is empty.");
        }
        $sql = "SELECT `id`, `title`, `aps`, `gps`, `date` FROM `user_imports` WHERE `username` = ? ORDER BY `username` ASC";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $Username, PDO::PARAM_STR);
        $result->execute();
        $this->sql->checkError( $result, __LINE__, __FILE__);
        $userslists = $result->fetchAll(2);

        return $userslists;
    }

    function SelectFedServer($id = 0)
    {
        if($id === 0)
        {
            return -1;
        }
        $sql = "SELECT * FROM `federation_servers` WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $this->FedServerID, PDO::PARAM_INT);
        $result = $prep->execute();
        $return = $result->fetch(2);
        $this->FedHostSharedKey = $return['SharedKeyRemote'];
        $this->FedHostURL = $return['ServerAddress'];
        $this->FedHostApiVer = $return['APIVersion'];
        return 0;
    }

    function GetFedAPLists()
    {
        $url = 'http://domain.com/get-post.php';
        $fields = array(
            'lname' => urlencode($_POST['last_name']),
            'fname' => urlencode($_POST['first_name']),
            'title' => urlencode($_POST['title']),
            'company' => urlencode($_POST['institution']),
            'age' => urlencode($_POST['age']),
            'email' => urlencode($_POST['email']),
            'phone' => urlencode($_POST['phone'])
        );
        $fields_string = "";
        foreach($fields as $key=>$value) { $fields_string .= $key.'='.$value.'&'; }
        rtrim($fields_string, '&');

        $ch = curl_init();

        curl_setopt($ch,CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_POST, count($fields));
        curl_setopt($ch,CURLOPT_POSTFIELDS, $fields_string);

        $result = curl_exec($ch);
        curl_close($ch);
    }

}