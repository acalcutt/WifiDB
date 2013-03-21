<?php

class security
{
    function __construct($config, $sql, $datetime_format)
    {
        $this->sql             = $sql;
        $this->logged_in_flag  = 0;
        $this->login_val       = "No Cookie";
        $this->login_check     = 0;
        $this->activatecode    = "";
        $this->priv_name       = "AnonCoward";
        $this->privs           = 0;
        $this->username        = "AnonCoward";
        $this->global_seed     = $config['login_seed'];
        $this->ver             = array(
                                        "login_check"       => "1.0",
                                        "ValidateAPIKey"    => "1.0",
                                    );
        $this->datetime_format =  $datetime_format;
    }
    
        
    function CreateUser($username="", $password="", $email="local@localhost.local", $permissions='0001', $seed="", $validate_user_flag = 1)
    {
        if($username == "")
        {
            return -1;
        }
        if($password == "")
        {
            return -1;
        }
        
        if($email == "local@localhost.local")
        {
            return -1;
        }
        
        if($seed == "")
        {
            $seed = $this->global_seed;
        }
        
        
        #now lets start creating the users info
        $sql = "INSERT INTO `wifi`.`users_info` (`id`, `username`, `email`, `password`, `cypher`, `uid`, `validated`, `permissions`, `last_login`, `last_active`,  `join_date`, `rank`, `api_key`)
                                         VALUES ('',    ?,          ?,       ?,         ?,          ?,      ?,          ?,              ?,              ?,           ?,             ?       ?    )";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $username, PDO::PARAM_STR);
        $prep->bindParam(2, $email, PDO::PARAM__STR);
        $prep->bindParam(3, $password, PDO::PARAM_STR);
        $prep->bindParam(4, $cypher, PDO::PARAM_STR);
        $prep->bindParam(5, $uid, PDO::PARAM_STR);
        $prep->bindParam(6, 0, PDO::PARAM_INT);
        $prep->bindParam(7, $permissions, PDO::PARAM_INT);
        $prep->bindParam(8, $last_login, PDO::PARAM_STR);
        $prep->bindParam(9, $last_active, PDO::PARAM_STR);
        $prep->bindParam(10, $join_date, PDO::PARAM_STR);
        $prep->bindParam(11, $rank, PDO::PARAM_STR);
        $prep->bindParam(12, $api_key, PDO::PARAM_STR);
        $prep->execute();
        $err = $this->sql->conn->errorInfo();
        
        if($err[0] != "00000")
        {
            $this->logd("Failed to create user with error: ".var_export($err, 1), "Error");
            return -1;
        }
        
        if($validate_user_flag == 1)
        {
            #Now that the user has been created successfully, we can create the users validation key, insert it into the validation table and email it to the user.
        }
        
    }
    
    
    function gen_keys($len = 12)
    {
        // http://snippets.dzone.com/posts/show/3123
        $base           =   'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
        $max            =   strlen($base)-1;
        $activatecode	=   '';
        mt_srand((double)microtime()*1000000);
        while (strlen($activatecode) < $len+1)
        {
            $activatecode .= $base{mt_rand(0,$max)};
            //$activatecode .= mt_rand(0,$max);
        }
        return $activatecode;
    }
    
    
    function Login($username = '', $password = '', $seed = '', $admin = 0, $no_save_login = 0)
    {
        if($seed === ''){$seed = $this->sec->seed;}
        if($admin === 1)
        {
            $cookie_name = 'WiFiDB_admin_login_yes';
            $cookie_seed = "@LOGGEDIN";

            if($this->URL_PATH != '')
            {$path  = '/'.$this->URL_PATH.'/cp/admin/';}
            else{$path  = '/cp/admin/';}
            $cookie_timeout = time()-3600;
        }else
        {
            $cookie_name = 'WiFiDB_login_yes';
            $cookie_seed = "@LOGGEDIN!";

            if($this->URL_PATH != '')
            {$path  = '/'.$this->URL_PATH.'/';}
            else{$path  = '/';}
        }
        $date = date($this->datetime_format);
        $pass_seed = md5($password.$seed); // TODO: Needs to be change to BCRYPT
        $sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
    #   echo $sql0;
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1 , $username);
        $result->execute();
        
        $newArray = $result->fetch(2);
        if($newArray['validated'] === 1)
        {
            $this->sec->login_val = 'validate';
            return -1;
        }
        if($newArray['login_fails'] === $this->config_fails || $newArray['locked'] === 1)
        {
            $this->sec->login_val = 'locked';
            return -1;
        }
        $id = $newArray['id'];
        $db_pass = $newArray['password'];
        $fails = $newArray['login_fails'];
        $username_db = $newArray['username'];
        if($db_pass === $pass_seed)
        {
            if($no_save_login === 1)
            {
                $cookie_timeout = time()-3600;
            }else
            {
                $cookie_timeout = time()+$GLOBALS['timeout'];
            }
            if(setcookie($cookie_name, md5($pass_seed.$cookie_seed).":".$username, $cookie_timeout, $path)) // TODO: needs to be changed to BCRYPT
            {
                $sql0 = "SELECT `last_active` FROM `wifi`.`user_info` WHERE `id` = :id LIMIT 1";
                $result = $this->sql->conn->prepare($sql0);
                $result->bindParam(":id", $id);
                $result->execute();
                
                $array = $result->fetch(2);
                $last_active = $array['last_active'];
                $sql1 = "UPDATE `wifi`.`user_info` SET `login_fails` = '0', `last_active` = '$last_active', `last_login` = '$date' WHERE `$user_logins_table`.`id` = '$id' LIMIT 1";
                if($this->sql->conn->query($sql1))
                {
                    $this->sec->login_val = "good";
                    return 1;
                }else
                {
                    $this->sec->login_val = "u_u_r_fail";
                    return -1;
                }
            }else
            {
                $this->sec->login_val = "cookie_fail";
                return -1;
            }
        }else
        {
            if($username_db != '')
            {
                $fails++;
                $to_go = $this->config_fails - $fails;
            #   echo $fails.' - '.$this->config_fails;
                if($fails >= $this->config_fails)
                {
                    $sql1 = "UPDATE `wifi`.`user_info` SET `locked` = '1' WHERE `id` = ? LIMIT 1";
                    $prepare = $this->sql->conn->prepare($sql1);
                    $prepare->bindParam(1, $fails);
                    $prepare->execite();
                    $this->sec->login_val = "locked";
                    return -1;
                }else
                {
                    $sql1 = "UPDATE `wifi`.`user_info` = ? WHERE `id` = ? LIMIT 1";
                    $prepare = $this->sql->conn->prepare($sql1);
                    $prepare->bindParam(1, $fails);
                    $prepare->bindParam(2, $id);
                    $prepare->execite();
                    $this->sec->login_val = array("p_fail", $to_go);
                    return -2;
                }
            }else
            {
                $this->sec->login_val = "u_fail";
                return -1;
            }
        }
        return 0;
    }
    
    function LoginCheck($admin = 0)
    {
        if($admin == 1)
        {
            $cookie_name = 'WiFiDB_admin_login_yes';
            $cookie_seed = "@LOGGEDIN";
        }else
        {
            $cookie_name = 'WiFiDB_login_yes';
            $cookie_seed = "@LOGGEDIN!";
        }
        
        if($admin && !@isset($_COOKIE[$cookie_name]))
        {
            $this->sec->login_val = "No Cookie";
            $this->sec->login_check = 0;
            return -1;
        }
        if(@isset($_COOKIE[$cookie_name]))
        {
            list($cookie_pass_seed, $username) = explode(':', $_COOKIE[$cookie_name]);
            if($username != '')
            {
            #   echo $username;
                $sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ':username' LIMIT 1";
                $result = $this->sql->conn->prepare($sql0);
                $result->bindParam(':username', $username, 2);
                $result->execute();
                $newArray = $result->fetch(2);
                #var_dump($newArray);
                $db_pass = $newArray['password'];
                if(md5($db_pass.$cookie_seed) === $cookie_pass_seed)
                {
                    $this->privs = $this->check_privs();
                    $this->logged_in_flag = 1;
                    $this->login_val = $newArray['username'];
                    $this->username = $newArray['username_db'];
                    $this->last_login = $newArray['last_login'];
                    $this->login_check = 1;
                    return 1;
                }else
                {
                    $this->logged_in_flag = 0;
                    $this->login_val = "Bad Cookie Password";
                    $this->login_check = 0;
                    return -1;
                }
            }else
            {
                $this->login_val = "No Cookie";
                $this->login_check = 0;
                return -1;
            }
        }else
        {
            $this->login_val = "No Cookie";
            $this->login_check = 0;
            return -1;
        }
    }
    
    function ValidateAPIKey($username, $apikey)
    {
        $sql = "SELECT `locked`, `validated`, `disabled`, `apikey` FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $username, PDO::PARAM_STR);
        $result->execute();
        $err = $this->sql->conn->errorCode();
        if($err !== "00000")
        {
            $this->logd("Error selecting Users API key.".var_export($this->sql->conn->errorInfo(),1));
            return array(0, "Error Selecting User API Key");
        }
        $key = $result->fetch(2);
        if($key['apikey'] !== $apikey)
        {
            return array(0, "Authentication Failed.");
        }
        if($key['locked'])
        {
            return array(0, "Account Locked.");
        }
        if($key['disabled'])
        {
            return array(0, "Account Disabled.");
        }
        if($key['validated'])
        {
            return array(0, "User not validated yet.");
        }
        return 1;
    }
}
?>