<?php
/*
Database.inc.php, holds the database interactive functions.
Copyright (C) 2011 Phil Ferland

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

ou should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
class security extends wdbmail
{
    

    

    #######################################
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

    #######################################
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
    #   echo $sql0;
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1 , $username);
        $newArray = $result->fetch(2);
        $table_pass = md5($newArray['password'].$cookie_seed); // TODO: CHANGE TO BCRYPT
        
        if($table_pass == $cookie_pass_seed)
        {
            $groups = array(3=>$newArray['admins'],2=>$newArray['devs'],1=>$newArray['mods'],0=>$newArray['users']);
            $this->sec->privs = implode("",$groups);
            $this->sec->privs+0;
#		echo $privs;
            if($this->sec->privs >= 1000)
                {$this->sec->priv_name = "Administrator";}
            elseif($this->sec->privs >= 100)
                {$this->sec->priv_name = "Developer";}
            elseif($this->sec->privs >= 10)
                {$this->sec->priv_name = "Moderator";}
            else
                {$this->sec->priv_name = "User";}
        }
        else
        {
            $this->sec->check_error = "Wrong pass or no Cookie, go get one.";
        }
    }

    #######################################
    function login($username = '', $password = '', $seed = '', $admin = 0, $no_save_login = 0)
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
        $date = date("Y-m-d G:i:s");
        $pass_seed = md5($password.$seed); // TODO: Needs to be change to BCRYPT
        $sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
    #   echo $sql0;
        $result = $this->sql->conn->prepare($sql0);
        $result->bindParam(1 , $username);
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
                    $sql1 = "UPDATE `wifi`.`user_info` SET `locked` = '1' WHERE `id` = '$id' LIMIT 1";
                    $this->sql->conn->query($sql1);
                    $this->sec->login_val = "locked";
                    return -1;
                }else
                {
                    $sql1 = "UPDATE `wifi`.`user_info` = '$fails' WHERE `id` = '$id' LIMIT 1";
                    $this->sql->conn->query($sql1);
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

    #######################################
    function check_user_reserved($username = '')
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
    
    function CreateUser($username="", $password="", $email="local@localhost.local", $user_array=array(0,0,0,1), $seed="", $validate_user_flag = 1)
    {
        
    }
}
?>