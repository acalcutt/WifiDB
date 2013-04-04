<?php

class security
{
    #private $pass_hash;
    public  $username;
    function __construct($dbcore, $config)
    {
        $this->sql                = $dbcore->sql;
        $this->cli                = $dbcore->cli;
        $this->log_level          = 42;
        $this->This_is_me         = $dbcore->This_is_me;
        $this->datetime_format    = $dbcore->datetime_format;
        $this->login_val          = "No Cookie";
        $this->login_check        = 0;
        $this->LoginLabel         = "AnonCoward";
        $this->activatecode       = "";
        $this->priv_name          = "AnonCoward";
        $this->privs              = 0;
        $this->username           = "AnonCoward";
        $this->email_validation   = $dbcore->email_validation;
        $this->reserved_users     = $dbcore->reserved_users;
        $this->timeout            = $dbcore->timeout;
        $this->config_fails       = $config['config_fails'];
        $this->pass_hash        = "";
    }
    
    
    function logd($message = "", $type = "", $prefix = "")
    {
        return @dbcore::logd($message, $type, $prefix);
    }
    
    function define_priv_name($member)
    {
        $groups = explode("," , $member);
        foreach($groups as $group)
        {
            if($group == 'admins')
            {
                return "Administrator";
            }elseif($group == 'devs')
            {
                return "Developer";
            }elseif($group == 'mods')
            {
                return "Moderator";
            }elseif($group == 'users')
            {
                return "User";
            }
        }
    }
    
    // Only moved, not updated, NEED TO UPDATE!!!!!!!!!
    // UPDATE THIS YOU LAZY BASTARD.
    function check_privs($admin = 0)
    {
        if($admin == 1)
        {
            $cookie_seed = "@LOGGEDIN";
            list($cookie_pass_seed, $username) = explode(':', $_COOKIE['WiFiDB_admin_login_yes']);
        }else
        {
            $cookie_seed = "@LOGGEDIN!";
            list($cookie_pass_seed, $username) = explode(':', $_COOKIE['WiFiDB_login_yes']);
        }
        $sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1 , $username);
        $newArray = $result->fetch(2);
        $table_pass = crypt($newArray['password']);
        
        if($table_pass == $cookie_pass_seed)
        {
            $groups = array(3=>$newArray['admins'],2=>$newArray['devs'],1=>$newArray['mods'],0=>$newArray['users']);
            $this->privs = implode("",$groups);
            $this->privs+0;
#		echo $privs;
            if($this->privs >= 1000)
                {$this->priv_name = "Administrator";}
            elseif($this->privs >= 100)
                {$this->priv_name = "Developer";}
            elseif($this->privs >= 10)
                {$this->priv_name = "Moderator";}
            else
                {$this->priv_name = "User";}
        }
        else
        {
            $this->check_error = "Wrong pass or no Cookie, go get one.";
        }
    }
    
    function CheckReservedUser($username = '')
    {
        if($username == ''){return -1;}
        $reserved = explode(":", $this->reserved_users);
        foreach($reserved as $resv)
        {
            if($username == $resv)
            {return 1;}
        }
        return 0;
    }
        
    function CreateUser($username = "", $password = "", $email = "local@localhost.local")
    {
        if($username == "")
        {
            $ret = array(0, "Username is empty.");
            return $ret;
        }
        if($password == "")
        {
            $ret = array(0, "Password is empty.");
            return $ret;
        }
        if($email == "local@localhost.local")
        {
            $ret = array(0, "Email is empty.");
            return $ret;
        }
        $salt               = $this->GenerateKey(29);
        $password_hashed    = crypt($password, '$2x$07$'.$salt.'$');
        $uid                = implode(str_split($this->GenerateKey(25), 5), "-");
        $join_date          = date($this->datetime_format);
        $api_key            = $this->GenerateKey(64);
        
        #now lets start creating the users info
        $sql = "INSERT INTO `wifi`.`user_info` (`id`, `username`, `password`, `salt`, `uid`, `validated`, 
                                        `locked`, `permissions`, `email`, `join_date`, `apikey`) 
                                        VALUES (NULL, ?, ?, ?, ?, ?, '0', '0001', ?, ?, ?)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $username, PDO::PARAM_STR);
        $prep->bindParam(2, $password_hashed, PDO::PARAM_STR);
        $prep->bindParam(3, $salt, PDO::PARAM_STR);
        $prep->bindParam(4, $uid, PDO::PARAM_STR);
        $prep->bindParam(5, $this->email_validation, PDO::PARAM_STR);
        $prep->bindParam(6, $email, PDO::PARAM_STR);
        $prep->bindParam(7, $join_date, PDO::PARAM_STR);
        $prep->bindParam(8, $api_key, PDO::PARAM_STR);
        $prep->execute();
        
        if($this->sql->checkError() !== 0)
        {
            $password = "";
            $this->logd("Failed to create user with error: ".var_export($this->sql->conn->errorInfo(), 1)." </br>\r\n ". var_dump(get_defined_vars()), "Error");
            echo "Failed to create user with error: ".var_export($this->sql->conn->errorInfo(), 1)." </br>\r\n ". var_dump(get_defined_vars());
            $message = array(0, "Failed to create user :(");
            return $message;
        }else
        {
            $this->logd("User created! $username : $email : $join_date", "Info");
            #var_dump(get_defined_vars());
            $message = array(1, "User created!$username : $email : $join_date");
            return $message;
        }
    }
    
    
    function GenerateKey($len = 16)
    {
        // http://snippets.dzone.com/posts/show/3123
        $base           =   'ABCDEFGHKLMNOPQRSTWXYZabcdefghjkmnpqrstwxyz123456789';
        $max            =   strlen($base)-1;
        $activatecode	=   '';
        mt_srand((double)microtime()*1000000);
        while( strlen($activatecode) < $len ){$activatecode .= $base{mt_rand(0,$max)};}
        return $activatecode;
    }
    
    function Login($username = '', $password = '', $Bender_remember_me = 0, $authoritah = 0 )
    {
        $sql0 = "SELECT `id`, `validated`, `locked`, `login_fails`, `username`, `password`, `salt` FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1 , $username);
        $result->execute();
        
        $newArray = $result->fetch(2);
        if($newArray['validated'] == "1")
        {
            $this->login_val = 'validate';
            $this->login_check = 0;
            return 0;
        }
        if($newArray['locked'] == "1")
        {
            $this->login_val = 'locked';
            $this->login_check = 0;
            return 0;
        }
        $id = $newArray['id'];
        $db_pass = $newArray['password'];
        $fails = $newArray['login_fails'];
        $this->username = $newArray['username'];
        
        $pass_hash = crypt($password, '$2x$07$'.$newArray['salt'].'$');
        if($db_pass === $pass_hash)
        {
            $salt = $this->GenerateKey(22);
            if(!$this->cli)
            {
                if(!$Bender_remember_me)
                {
                    $cookie_timeout = time();
                }else
                {
                    $cookie_timeout = time()+$this->timeout;
                }
                if($authoritah === 1)
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

                if(!setcookie($cookie_name, crypt($this->GenerateKey(64), '$2x$07$'.$salt.'$').":".$this->username, $cookie_timeout, $path))
                {
                    $this->login_val = "Bad Cookie";
                    $this->login_check = 0;
                    return 0;
                }
            }
            $utime = time()+$this->timeout;
            $this->pass_hash = crypt($this->GenerateKey(64), '$2x$07$'.$salt.'$');
            $sql = "INSERT INTO `wifi`.`user_login_hashes` (`id`, `username`, `hash`, `salt`, `utime`) VALUES ('', ?, ?, ?, ?)";
            $prep = $this->sql->conn->prepare($sql);
            $prep->bindParam(1, $this->username, PDO::PARAM_STR);
            $prep->bindParam(2, $this->pass_hash, PDO::PARAM_STR);
            $prep->bindParam(3, $salt, PDO::PARAM_STR);
            $prep->bindParam(4, $utime, PDO::PARAM_INT);
            $prep->execute();
            if($this->sql->checkError())
            {
                $this->login_val    = "Failed to set API login hash to table.";
                $this->login_check  = 0;
                return 0;
            }
            
            $date = date($this->datetime_format);
            $sql1 = "UPDATE `wifi`.`user_info` SET `login_fails` = '0', `last_login` = ? WHERE `id` = ? ";
            $prep = $this->sql->conn->prepare($sql1);
            $prep->bindParam(1, $date, PDO::PARAM_STR);
            $prep->bindParam(2, $id, PDO::PARAM_INT);
            $prep->execute();
            
            if(!$this->sql->checkError())
            {
                $this->login_val = "good";
                $this->login_check = 1;
                return 1;
            }else
            {
                $this->login_val = "u_u_r_fail";
                $this->login_check = 0;
                return 0;
            }
        }else
        {
            if($this->username != '' || $this->username != "AnonCoward" || $this->username != "unknown")
            {
                $fails++;
                $to_go = $this->config_fails - $fails;
                if($fails >= $this->config_fails)
                {
                    $sql1 = "UPDATE `wifi`.`user_info` SET `locked` = '1' WHERE `id` = ? LIMIT 1";
                    $prepare = $this->sql->conn->prepare($sql1);
                    $prepare->bindParam(1, $id);
                    $prepare->execute();
                    $this->login_val = "locked";
                    $this->login_check = 0;
                    return 0;
                }else
                {
                    $sql1 = "UPDATE `wifi`.`user_info` SET `login_fails` = ? WHERE `id` = ? LIMIT 1";
                    $prepare = $this->sql->conn->prepare($sql1);
                    $prepare->bindParam(1, $fails);
                    $prepare->bindParam(2, $id);
                    $prepare->execute();
                    $this->login_val = array("p_fail", $to_go);
                    $this->login_check = 0;
                    return 0;
                }
            }else
            {
                $this->login_val = "u_fail";
                $this->login_check = 0;
                return 0;
            }
        }
    }
    
    function APILoginCheck($username = '', $hash = '', $salt = '', $authoritah = 0)
    {
        # hash is the hashed password + salt from the API.
        # salt is the salt that the API used.
        # admin is for logging into the admin console
        if($username != '')
        {
            $sql0 = "SELECT * FROM `wifi`.`user_login_hash` WHERE `username` = ? LIMIT 1";
            $result = $this->sql->conn->prepare($sql0);
            $result->bindParam(1, $username, PDO::PARAM_STR);
            $result->execute();
            $newArray = $result->fetch(2);            
            $db_pass = $newArray['hash'];
            $salt = $newArray['salt'];
            var_dump($newArray);
            if(crypt($hash, '$2x$07$'.$salt.'$') === $db_pass)
            {
                $this->privs = $this->check_privs();
                $this->LoginLabel = $newArray['username'];
                $this->login_val = $newArray['username'];
                $this->username = $newArray['username_db'];
                $this->last_login = $newArray['last_login'];
                $this->login_check = 1;
                return 1;
            }else
            {
                $this->LoginLabel = "";
                $this->login_val = "Bad Cookie Password";
                $this->login_check = 0;
                return -1;
            }
        }else
        {
            $this->LoginLabel = "";
            $this->login_val = "No Cookie";
            $this->login_check = 0;
            return -1;
        }
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
            $this->login_val = "No Cookie";
            $this->login_check = 0;
            return -1;
        }
        if(@isset($_COOKIE[$cookie_name]))
        {
            list($cookie_pass_seed, $username) = explode(':', $_COOKIE[$cookie_name]);
            if($username != '')
            {
            #   echo $username;
                $sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
                $result = $this->sql->conn->prepare($sql0);
                $result->bindParam(1, $username, PDO::PARAM_STR);
                $result->execute();
                $newArray = $result->fetch(2);
                #var_dump($newArray);
                $db_pass = $newArray['password'];
                if(crypt($db_pass, '$2x$07$'.$newArray['salt'].'$') === $cookie_pass_seed)
                {
                    $this->privs = $this->check_privs();
                    $this->LoginLabel = $newArray['username'];
                    $this->login_val = $newArray['username'];
                    $this->username = $newArray['username_db'];
                    $this->last_login = $newArray['last_login'];
                    $this->login_check = 1;
                    return 1;
                }else
                {
                    $this->LoginLabel = "";
                    $this->login_val = "Bad Cookie Password";
                    $this->login_check = 0;
                    return -1;
                }
            }else
            {
                $this->LoginLabel = "";
                $this->login_val = "No Cookie";
                $this->login_check = 0;
                return -1;
            }
        }else
        {
            $this->LoginLabel = "";
            $this->login_val = "No Cookie";
            $this->login_check = 0;
            return -1;
        }
    }
    
    function UnlockUser($id)
    {
        if($id === 0)
        {
            return 0;
        }
        $sql = "UPDATE `wifi`.`user_info` SET `locked` = '0', `login_fails` = '0' WHERE `id` = ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $id, PDO::PARAM_INT);
        $prep->execute();
        if($this->sql->checkError())
        {
            $this->logd("Error Unlocking user id: $id ". var_export($this->sql->conn->errorInfo(), 1), "error");
            return 0;
        }
        return 1;
    }
    
 /**
 * (WiFiDB 0.30)<br/>
 * Checks the Users API key and sees if it is valid or not.
 * @link http://www.wifidb.net/manual/en/function.security.ValidateAPIKey.php
 * @param string $username <p>
 * The Username to be checked
 * </p>
 * @param string $apikey <p>
 * The api key to be checked.
 * </p>
 * @return <b>1</b> if the key has been validated, <b>Array</b> and <b>[0]</b> is the code, 
 * <b>[1]</b> is the message.
 */
    function ValidateAPIKey()
    {
        if($this->username === "" || $this->username === "Unknown" || $this->username === "AnonCoward")
        {
            $this->message = "Invalid Username set.";
            $array = array(0, $this->message);
            return $array;
        }
        if($this->apikey === "")
        {
            $this->message = "Invalid API Key set.";
            $array = array(0, $this->message);
            return $array;
        }
        $sql = "SELECT `locked`, `validated`, `disabled`, `apikey` FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
        $result = $this->sql->conn->prepare($sql);
        $result->bindParam(1, $this->username, PDO::PARAM_STR);
        $result->execute();
        $err = $this->sql->conn->errorCode();
        if($err !== "00000")
        {
            $this->logd("Error selecting Users API key.".var_export($this->sql->conn->errorInfo(),1));
            $array =  array(0, "Error Selecting User API Key");
            return $array;
        }
        $key = $result->fetch(2);
        if($key['apikey'] !== $this->apikey)
        {
            $this->message = "Authentication Failed.";
            $array =  array(0, $this->message);
            return $array;
        }
        if($key['locked'])
        {
            $this->message = "Account Locked.";
            $array =  array(0, $this->message);
            return $array;
        }
        if($key['disabled'])
        {
            $this->message = "Account Disabled.";
            $array =  array(0, $this->message);
            return $array;
        }
        if($key['validated'])
        {
            $this->message = "User not validated yet.";
            $array =  array(0, $this->message);
            return $array;
        }
        return 1;
    }
}
?>