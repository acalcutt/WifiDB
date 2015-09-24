<?php

class security
{
    #private $pass_hash;
    public  $username;
    function __construct(&$dbcore, $config)
    {
        $this->sql                = &$dbcore->sql;
        $this->cli                = &$dbcore->cli;
        $this->mesg               = array();
        $this->log_level          = 42;
        $this->This_is_me         = $dbcore->This_is_me;
        $this->datetime_format    = $dbcore->datetime_format;
        $this->EnableAPIKey       = 1; #$config['EnableAPIKey'];
        $this->login_val          = "No Cookie";
        $this->last_login         = 0;
        $this->login_check        = 0;
        $this->LoginLabel         = "AnonCoward";
        $this->activatecode       = "";
        $this->priv_name          = "AnonCoward";
        $this->privs              = 0;
        $this->username           = "AnonCoward";
        $this->apikey             = "";
        $this->email_validation   = $dbcore->email_validation;
        $this->reserved_users     = $dbcore->reserved_users;
        $this->timeout            = $dbcore->timeout;
        $this->config_fails       = $config['config_fails'];
        $this->HOSTURL            = $dbcore->HOSTURL;
        $this->root               = $dbcore->root;
        $this->URL_PATH           = $dbcore->URL_PATH;
        $this->SessionID          = "";
        $ssl_flag                 = parse_url($this->URL_PATH, PHP_URL_SCHEME);
        if($ssl_flag == "https")
        {
            $this->ssl = "1";
        }else
        {
            $this->ssl = "0";
        }
        $this->domain     = parse_url($this->URL_PATH, PHP_URL_HOST);
        $folder = parse_url($this->URL_PATH, PHP_URL_PATH);
        $this->PATH = $folder;
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

    function check_privs($admin = 0)
    {
        if($admin == 1)
        {
            list($cookie_pass_seed, $username) = explode(':', base64_decode(@$_COOKIE['WiFiDB_admin_login_yes']));
        }else
        {
            @list($cookie_pass_seed, $username) = explode(':', base64_decode(@$_COOKIE['WiFiDB_login_yes']));
        }
        #var_dump($username);
        $sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1 , $username);
        $this->sql->checkError($result->execute(), __LINE__, __FILE__);
        $newArray = $result->fetch(2);

        #var_dump($newArray);
        $sql1 = "SELECT * FROM `wifi`.`user_login_hashes` WHERE `username` = ? ORDER BY `id` DESC LIMIT 1";
        $prep = $this->sql->conn->prepare($sql1);
        $prep->bindParam(1, $username, PDO::PARAM_STR);
        $this->sql->checkError($prep->execute(), __LINE__, __FILE__);
        $this->sql->checkError();
        $result = $prep->fetch(2);
        #var_dump($result['hash']);
        if($result['hash'] == $cookie_pass_seed)
        {
            $this->privs = (int)$newArray['permissions'];
            #var_dump($this->privs);
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
        return 0;
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
            $this->mesg[] = "Username is empty.";
            return -1;
        }
        if($password == "")
        {
            $this->mesg[] = "Password is empty.";
            return -1;
        }
        if($email == "local@localhost.local")
        {
            $this->mesg[] = "Email is empty.";
            return -1;
        }
        $salt               = $this->GenerateKey(29);
        $password_hashed    = crypt($password, '$2a$07$'.$salt.'$');
        $uid                = implode(str_split($this->GenerateKey(25), 5), "-");
        $join_date          = date($this->datetime_format);
        $api_key            = $this->GenerateKey(64);
        
        #now lets start creating the users info
        $sql = "INSERT INTO `wifi`.`user_info` (`id`, `username`, `password`, `uid`, `validated`, 
                                        `locked`, `permissions`, `email`, `join_date`, `apikey`) 
                                        VALUES (NULL, ?, ?, ?, ?, '0', '0001', ?, ?, ?)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $username, PDO::PARAM_STR);
        $prep->bindParam(2, $password_hashed, PDO::PARAM_STR);
        $prep->bindParam(3, $uid, PDO::PARAM_STR);
        $prep->bindParam(4, $this->email_validation, PDO::PARAM_STR);
        $prep->bindParam(5, $email, PDO::PARAM_STR);
        $prep->bindParam(6, $join_date, PDO::PARAM_STR);
        $prep->bindParam(7, $api_key, PDO::PARAM_STR);
        $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);

        $this->logd("User created! $username : $email : $join_date", "Info");
        #var_dump(get_defined_vars());
        $this->mesg[] = "User created! | $username : $email : $join_date";
        return 1;
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

    function GenerateSessionCookie($Bender_remember_me = 0, $authoritah = 0)
    {
        if(!$Bender_remember_me)
        {
            $cookie_timeout = time()+$this->timeout;
        }else
        {
            $cookie_timeout = time()+(60*60*24*364.25);
        }
        $sql = "INSERT INTO `wifi`.`user_login_hashes` (`id`, `username`, `hash`, `utime`) VALUES ('', ?, ?, ?)";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $this->username, PDO::PARAM_STR);
        $prep->bindParam(2, $this->SessionID, PDO::PARAM_STR);
        $prep->bindParam(3, $cookie_timeout, PDO::PARAM_INT);
        $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);

        if(!$this->cli)
        {
            if($authoritah)
            {
                $cookie_name = 'WiFiDB_admin_login_yes';

                if($this->URL_PATH != '')
                {$path  = '/'.$this->root.'/cp/admin/';}
                else{$path  = '/cp/admin/';}
                $cookie_timeout = time()-3600;
            }else
            {
                $cookie_name = 'WiFiDB_login_yes';

                if($this->URL_PATH != '')
                {$path  = '/'.$this->root.'/';}
                else{$path  = '/';}
            }
            if(!setcookie($cookie_name, base64_encode($this->SessionID.":".$this->username), $cookie_timeout, $this->path, $this->domain, $this->ssl))
            {
                $this->login_val = "cookie_fail";
                $this->login_check = 0;
                $this->mesg[] = "Failed to set Cookie.";
                return 0;
            }else
            {
               #var_dump("COOKIES!!!!!!!");
            }
        }
    }

    function Login($username = '', $password = '', $Bender_remember_me = 0, $authoritah = 0 )
    {
        if($username == '' || $username == "AnonCoward" || $username == "unknown")
        {
            # Username Fail.
            $this->login_val = "u_fail";
            $this->logd("Failed to login user: ". var_export($username, 1), "error");
            $this->login_check = 0;
            $this->mesg[] = "Username not defined, or is AnonCoward.";
            return 0;
        }
        
        $sql0 = "SELECT `id`, `validated`, `locked`, `login_fails`, `username`, `password` FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1 , $username);
        $this->sql->checkError( $result->execute(), __LINE__, __FILE__);
        
        $newArray = $result->fetch(2);
        $validate = $newArray['validated']+0;
        if($validate === 1)
        {
            $this->login_val = 'NotValidated';
            $this->login_check = 0;
            $this->logd("Failed to login user, not validated: ". var_export($username, 1), "error");
            $this->mesg[] = "User is not validated yet.";
            return 0;
        }
        $locked = $newArray['locked']+0;
        if($locked === 1)
        {
            $this->login_val = 'locked';
            $this->login_check = 0;
            $this->logd("Failed to login, user locked: ". var_export($username, 1), "error");
            $this->mesg[] = "User has been locked.";
            return 0;
        }
        $id = $newArray['id'];
        $db_pass = $newArray['password'];
        $fails = $newArray['login_fails'];
        $this->username = $newArray['username'];
        $pass_hash = crypt($password, $newArray['password']);
        if($db_pass === $pass_hash)
        {
            if(!$this->GenerateSessionCookie($Bender_remember_me, $authoritah))
            {
                $this->logd("Failed to generate session cookie.", "error");
                return 0;
            }
            
            $date = date($this->datetime_format);
            $sql1 = "UPDATE `wifi`.`user_info` SET `login_fails` = '0', `last_login` = ? WHERE `id` = ? ";
            $prep = $this->sql->conn->prepare($sql1);
            $prep->bindParam(1, $date, PDO::PARAM_STR);
            $prep->bindParam(2, $id, PDO::PARAM_INT);
            $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);
            
            $this->login_val = "good";
            $this->login_check = 1;
            $this->logd("User has successfully logged in: ". var_export($username, 1), "error");
            $this->mesg[] = "User is logged in.";
            return 1;
        }else
        {
            #Failed Password check.
            $fails++;
            if($fails >= $this->config_fails)
            {
                #Failed too many times, lock the account.
                $sql1 = "UPDATE `wifi`.`user_info` SET `locked` = '1' WHERE `id` = ? LIMIT 1";
                $prepare = $this->sql->conn->prepare($sql1);
                $prepare->bindParam(1, $id);
                $this->sql->checkError( $prepare->execute(), __LINE__, __FILE__);
                $this->login_val = "locked";
                $this->login_check = 0;
                $this->mesg[] = "User is locked.";
                $this->logd("User is locked: ".$username , "error");
                return 1;
            }else
            {
                # Increment the failed count.
                $sql1 = "UPDATE `wifi`.`user_info` SET `login_fails` = ? WHERE `id` = ? LIMIT 1";
                $prepare = $this->sql->conn->prepare($sql1);
                $prepare->bindParam(1, $fails);
                $prepare->bindParam(2, $id);
                $this->sql->checkError( $prepare->execute(), __LINE__, __FILE__);

                $this->login_val = "p_fail";
                $this->login_check = 0;
                $this->mesg[] = "Username or Password is incorrect.";
                $this->logd("Incorrect password for user : ".$username." | ".$password, "error");
                return 1;
            }
        }
    }
    
    function APILoginCheck($username = '', $apikey = '', $authoritah = 0)
    {
        if($username != '')
        {
            $sql0 = "SELECT * FROM `wifi`.`user_login_hash` WHERE `username` = ? LIMIT 1";
            $result = $this->sql->conn->prepare($sql0);
            $result->bindParam(1, $username, PDO::PARAM_STR);
            $this->sql->checkError( $result->execute(), __LINE__, __FILE__);
            $newArray = $result->fetch(2);
            if($this->EnableAPIKey) {
                if ($apikey === $newArray['apikey']) {
                    $this->privs = $this->check_privs();
                    $this->apikey = $newArray['apikey'];
                    $this->LoginLabel = $newArray['username'];
                    $this->login_val = $newArray['username'];
                    $this->username = $newArray['username'];
                    $this->last_login = $newArray['last_login'];
                    $this->login_check = 1;
                    return 1;
                } else {
                    $this->LoginLabel = "";
                    $this->login_val = "Bad API Key.";
                    $this->login_check = 0;
                    return -1;
                }
            }else{
                $this->privs = $this->check_privs();
                $this->LoginLabel = $username;
                $this->login_val = $username;
                $this->username = $username;
                $this->login_check = 1;
            }
        }else
        {
            $this->LoginLabel = "";
            $this->login_val = "No Username.";
            $this->login_check = 0;
            return -1;
        }
    }
    
    function LoginCheck($authoritah = 0)
    {
		$return_url = $_SERVER['REQUEST_URI'];
		if($_SERVER['PHP_SELF'] == '/'.$this->root.'/login.php'){$return_url = '/'.$this->root.'/';};#Set return url to main page if this is the login page.
		
        $time = time()-1;
        $sql = "DELETE FROM `wifi`.`user_login_hashes` WHERE `utime` < ?";
        $prep = $this->sql->conn->prepare($sql);
        $prep->bindParam(1, $time, PDO::PARAM_INT);
        $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);
        if($authoritah == 1)
        {
            $cookie_name = 'WiFiDB_admin_login_yes';
        }else
        {
            $cookie_name = 'WiFiDB_login_yes';
        }
        
        if(!@isset($_COOKIE[$cookie_name]))
        {
            $this->LoginLabel = "";
			$this->LoginHtml = "";
			$this->LoginUri = '?return='.urlencode($return_url);
            $this->login_val = "No Cookie";
            $this->login_check = 0;
            return -1;
        }
        list($cookie_pass, $username) = explode(':', base64_decode($_COOKIE[$cookie_name], 1));
        if($username == '' || $username == "AnonCoward" || $username == "unknown")
        {
            # Username Fail.
            $this->LoginLabel = "";
			$this->LoginHtml = "";
			$this->LoginUri = '?return='.urlencode($return_url);
            $this->login_val = "u_fail";
            $this->login_check = 0;
            return 0;
        }
        $sql0 = "SELECT * FROM `wifi`.`user_login_hashes` WHERE `username` = ? ORDER BY `id` DESC LIMIT 1";
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1, $username, PDO::PARAM_STR);
        $this->sql->checkError( $result->execute(), __LINE__, __FILE__);
        $logon = $result->fetch(2);

        #var_dump($newArray, $db_pass, $cookie_pass, crypt($cookie_pass, $db_pass));
        if($logon['hash'] == $cookie_pass)
        {
            $this->check_privs();
            $this->LoginLabel = "Logout";
            $this->LoginHtml = 'Welcome, <a class="links" href="'.$this->HOSTURL.$this->root.'/cp/">'.$logon['username'].'</a>';
            $this->LoginUri = '?func=logout&return='.urlencode($return_url);
            $this->login_val = $logon['username'];
            $this->username = $logon['username'];
            $this->login_check = 1;
            return 1;
        }
        $this->LoginLabel = "";
		$this->LoginHtml = "";
		$this->LoginUri = '?return='.urlencode($return_url);
        $this->login_val = "Bad Cookie Password";
        $this->login_check = 0;
        return -1;
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
        $this->sql->checkError( $prep->execute(), __LINE__, __FILE__);
        return 1;
    }
    
    function ValidateAPIKey()
    {
        $username = @$_REQUEST['username'];
        $apikey = @$_REQUEST['apikey'];

        if($this->EnableAPIKey)
        {
            if($username === "AnonCoward" && $apikey === "scaredycat")
            {
                $this->login_check = 1;
                $this->login_val = "apilogin";
                return 1;
            }

            if($username === "" || $username === "Unknown")
            {
                $this->mesg['error'] = "Invalid Username set.";
                $this->login_val = "failed";
                $this->login_check = 0;
                return -1;
            }
            if($apikey === "")
            {
                $this->mesg['error'] = "Invalid API Key set.";
                $this->login_val = "failed";
                $this->login_check = 0;
                return -2;
            }
            $sql = "SELECT `locked`, `validated`, `disabled`, `apikey` FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
            $result = $this->sql->conn->prepare($sql);
            $result->bindParam(1, $username, PDO::PARAM_STR);
            $this->sql->checkError( $result->execute(), __LINE__, __FILE__);

            $key = $result->fetch(2);
            if($key['apikey'] !== $apikey)
            {
                $this->mesg['error'] = "Authentication Failed.";
                $this->login_val = "failed";
                $this->login_check = 0;
                $this->logd("Error selecting Users API key.".var_export($this->sql->conn->errorInfo(),1));
                return -2;
            }elseif($key['locked'])
            {
                $this->mesg['error'] = "Account Locked.";
                $this->login_val = "locked";
                $this->login_check = 0;
                $this->logd("Error selecting Users API key.".var_export($this->sql->conn->errorInfo(),1));
                return -3;
            }elseif($key['disabled'])
            {
                $this->mesg['error'] = "Account Disabled.";
                $this->login_val = "disabeld";
                $this->login_check = 0;
                $this->logd("Error selecting Users API key.".var_export($this->sql->conn->errorInfo(),1));
                return -4;
            }elseif($key['validated'])
            {
                $this->mesg['error'] = "User not validated yet.";
                $this->login_val = "NotValidated";
                $this->login_check = 0;
                $this->logd("Error selecting Users API key.".var_export($this->sql->conn->errorInfo(),1));
                return -5;
            }else
            {
                $this->username = $username;
                $this->privs = $this->check_privs();
                $this->apikey = $apikey;
                $this->LoginLabel = $username;
                $this->login_val = $username;
                $this->username = $username;
                $this->last_login = time();
                $this->login_check = 1;
                $this->login_val = "apilogin";
                $this->logd("Authentication Succeeded.", "message");
                return 1;
            }
        }else
        {
            $this->username = $username;
            $this->privs = 1;
            $this->apikey = "APIKEysDisabled";
            $this->LoginLabel = $username;
            $this->login_val = $username;
            $this->username = $username;
            $this->last_login = time();
            $this->login_check = 1;
            $this->login_val = "apilogin";
            $this->mesg['message'] = "Authentication Succeeded. (API Keys Disabled.)";
            $this->logd("Authentication Succeeded. (API Keys Disabled.)", "message");
        }
        return 0;
    }
}