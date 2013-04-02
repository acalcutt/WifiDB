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

ini_set('display_errors', 0);
error_reporting(E_ALL & E_STRICT);
global $switches;
$switches = array('screen'=>"HTML",'extras'=>"");

include('lib/init.inc.php');

$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
$return = filter_input(INPUT_GET, 'return', FILTER_SANITIZE_SPECIAL_CHARS);

if($return == ''){$return = $dbcore->PATH;}

switch($func)
{
    case "login_proc":
        $username = filter_input(INPUT_POST, 'time_user', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, 'time_pass', FILTER_SANITIZE_SPECIAL_CHARS);
        $Bender_remember_me = filter_input(INPUT_POST, 'Bender_remmeber_me', FILTER_SANITIZE_SPECIAL_CHARS);
        $login = $dbcore->Login($username, $password, $Bender_remember_me);
        switch($login)
        {
            case "locked":
                $message = array('This user is locked out. contact this WiFiDB\'s admin, or go to the <a href="http://forum.techidiots.net/">forums</a> and bitch to Phil.');
            break;

            case "validate":
                $message = array('This user is not validated yet. You should be getting an email soon if not already from the Database with a link to validate your email address first so that we can verify that you are in fact a real person. The administrator of the site has enabled this by default.');
            break;

            case is_array($login):
            $to_go = $login[1];
            $message = array(
                'Bad Username or Password!',
                $to_go
            );
            break;

            case "u_fail":
                $message = array('Username does not exsist.');
            break;

            case "u_u_r_fail":
                $message = array("Failed to update User row");
            break;

            case "good":
                $dbcore->redirect_page($return, 2000, 'Login Successful!');
            break;

            case "cookie_fail":
                $message = array("Set Cookie fail, check the bottom of the glass, or your browser.");
            break;

            default:
                $message = array("Unknown Return.");
            break;
        }
    break;

    #---#
    case "logout_proc":
        $username = filter_input(INPUT_POST, 'time_user', FILTER_SANITIZE_SPECIAL_CHARS);
        $password = filter_input(INPUT_POST, 'time_pass', FILTER_SANITIZE_SPECIAL_CHARS);
        $admin_cookie = filter_input(INPUT_GET, 'a_c', FILTER_SANITIZE_SPECIAL_CHARS);
        if($admin_cookie==1)
        {
            $cookie_name = 'WiFiDB_admin_login_yes';
            $msg = 'Admin Logout Successful!';
            if($dbcore->root != '')
            {$path  = '/'.$dbcore->root.'/cp/admin/';}
            else{$path  = '/cp/admin/';}
        }else
        {
            $cookie_name = 'WiFiDB_login_yes';
            $msg = 'Logout Successful!';
            if($dbcore->root != '')
            {$path  = '/'.$dbcore->root.'/';}
            else{$path  = '/';}
        }
        if(setcookie($cookie_name, md5("@LOGGEDOUT!").":".$username, time()-3600, $path))
        {
            redirect_page($GLOBALS['UPATH'], 2000, $msg);
        }
        else
        {
            $message = array("Could not log you out.. :-(");
        }
    break;

    #---#
    case "create_user_form":
        $dbcore->smarty->display('create_user.tpl');
    break;

    #---#
    case "create_user_proc":
        $username   = $_REQUEST['time_user'];
        $password   = $_REQUEST['time_pass'];
        $password2  = $_REQUEST['time_pass2'];
        $email      = $_REQUEST['time_email'];
        if(!$dbcore->checkEmail($email))
        {
            $dbcore->smarty->assign('wifidb_create_message', 'Email is not valid.');
            $dbcore->smarty->display('create_user.tpl');
        }
        if($password !== $password2)
        {
            $dbcore->smarty->assign('wifidb_create_message', 'Passwords did not match.');
            $dbcore->smarty->display('create_user.tpl');
        }else
        {
            $ret = $dbcore->sec->CreateUser($username, $password, $email);
            #die();
            var_dump($ret);
            die();
            $return = $ret[0];
            $message = $ret[1];

            switch($ret)
            {
                case 1:
                    #User created!, now if the admin has enabled Email Validation before a user can be used, send it out, other wise let them login.
                    if($dbcore->sec->email_validation)
                    {
                        if($dbcore->wdbmail->mail_validation($email, $username))
                        {
                            $message = "User Created! You should be getting a Validation email soon, click on the link to confirm your account and to start you uploads!.";
                        }else
                        {
                            $message = "Email Validation has been enabled, but failed to send the email. Contact the Admins for help.";
                        }
                    }else
                    {
                        $message = "User Created! Go ahead and login.";
                    }
                    $dbcore->smarty->assign('wifidb_create_message', $message);
                    $dbcore->smarty->display('login_user.tpl');
                break;

                case 0:
                    #Failed to create a user for some reason, tell them why.
                    $dbcore->smarty->assign('wifidb_create_message', $message);
                    $dbcore->smarty->display('user_create.tpl');
                break;
                default:
                    die("ummmmm...");
                    break;
            }
        }
    break;

    case "validate_user":
        $validate_code = filter_input(INPUT_GET, 'validate_code', FILTER_SANITIZE_STRING);
        $sql = "SELECT * FROM `wifi`.`user_validate` WHERE `code` = ?";
        $result = $dbcore->sql->conn->prepare($sql);
        $result->execute(array($validate_code));
        $v_array = $result->fetch(2);
        $username = $v_array['username'];
        if($username)
        {
            $update = "UPDATE `wifi`.`user_info` SET `validated` = '0' WHERE `username` = ?";
            $result = $dbcore->sql->conn->prepare($update);
            $result->bindParam(1, $username);
            $result->execute();
            $err = $dbcore->sql->conn->errorCode();
#		echo $update."<br>";
            if($err[0] == "00000")
            {
                $delete = "DELETE FROM `wifi`.`user_validate` WHERE `username` = ?";
                $result = $dbcore->sql->conn->prepare($delete);
                $result->bindParam(1, $username);
                $result->execute();
                $err = $dbcore->sql->conn->errorCode();
#		
        #	echo $delete."<BR>";
                if($err[0] == "00000")
                {
                    $message = "<font color='Green'><h2>Username: {$username}\r\n<BR>Has been activated! Go login -></h2></font>";
                }else
                {
                    $message = "<font color='Yellow'><h2>Username: {$username}\r\n<BR>Activated, but failed to remove from activation table, <br>
                    This isnt a critical issue, but should be looked into by an administrator.
                    <br>".var_export($dbcore->sql->conn->errorInfo())."</h2></font>";
                }
            }else
            {
                $message = "<font color='red'><h2>Username: {$username}\r\n<BR>Failed to activate...<br>".var_export($dbcore->sql->conn->errorInfo())."</h2></font>";
            }
        }else
        {
            $message = "<font color='red'><h2>Invalid Activation Code, Would you like to <a class='links' href='?func=revalidate'>send another</a> validation code?.</h2></font>";
        }
    break;

    case "revalidate_proc":
            $seed = $dbcore->global_seed;
            $pass_seed = md5($_POST['time_pass'].$seed);

            $sql = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
            $result = $dbcore->sql->conn->prepare($sql);
            $result->execute(array($_POST['time_user']));
            $newArray = $result->fetch(2);

            $username_db = $newArray['username'];
            $user_email = $newArray['email'];
            $user_pwd_db = $newArray['password'];
    #	echo $pass_seed." == ".$user_pwd_db."<BR>";
            if($pass_seed == $user_pwd_db)
            {
                if($dbcore->mail->mail_validation($user_email, $username))
                {
                    $message = "<font color='green'><h2>Validation Email sent again.</h2></font>";
                }else
                {
                    $message = "<font color='red'><h2>Failed to send Validation Email.</h2></font>";
                }
            }else
            {
                $message = "<font color='red'><h2>You entered the wrong password.</h2></font>";
            }
    break;

    case "revalidate":

        $message = "Resend User Email Validation Code";
        $dbcore->smarty->assign('wifidb_create_message', $message);
        $dbcore->smarty->display('login_user.tpl');
        exit();
    break;

    case "reset_password_form":
        $token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
        $dbcore->smarty->assign("token", $token);
        $dbcore->smarty->display("reset_password_form.tpl");
    break;

    case "reset_password_proc":
        $username = filter_input(INPUT_POST, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $token = filter_input(INPUT_POST, 'token', FILTER_SANITIZE_SPECIAL_CHARS);
        $newpassword = filter_input(INPUT_POST, 'newpassword', FILTER_SANITIZE_SPECIAL_CHARS);
        $newpassword2 = filter_input(INPUT_POST, 'newpassword2', FILTER_SANITIZE_SPECIAL_CHARS);

        $from           =   $this->WDBadmin;
        $wifidb_smtp    =   $this->smtp;
        $sender         =   $from;
        $sender_pass    =   $dbcore->smtp_pass;
        $seed           =   $dbcore->login_seed;
        $success        =   0;
        $sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
        $prep = $dbcore->sql->conn->prepare($sql0);
        $prep->bindParam(1, $username, PDO::PARAM_STR);
        $prep->execute();
        $err = $dbcore->sql->conn->errorCode();
        if($err[0] !== "00000")
        {
            throw new Exception($this->sql->conn->errorInfo());
        }
        $newArray = $prep->fetch(2);
        $username_db = $newArray['username'];
        $user_email = $newArray['email'];
        $password_db = $newArray['password'];
        if($username_db == '')
        {
            $message = "";
            $dbcore->smarty->display("reset_password_form.tpl");
            die();
        }else
        {
            if($newpassword === $newpassword2)
            {
                $password = sha1($password.$seed);
                if($password === $password_db)
                {
                    $setpassword = sha1($newpassword.$seed);
                    $update = "UPDATE `wifi`.`user_info` SET `password` = ? WHERE `username` = ?";
                #   echo $update."<BR>";
                    $prep1 = $dbcore->sql->conn->prepare($update);
                    $prep1->bindParam(1, $setpassword.":sha1", PDO::PARAM_STR);
                    $prep1->bindParam(2, $username_db, PDO::PARAM_STR);
                    $prep1->execute();

                    $err = $dbcore->sql->conn->errorCode();
                    if($err[0] === "00000")
                    {
                        #clear the token from the table.
                        $remove = "DELETE FROM `wifi`.`reset_token` where `token` = ? and `username` = ?";
                        $prep2 = $dbcore->sql->conn->prepare($remove);
                        $prep2->bindParam(1, $token, PDO::PARAM_STR);
                        $prep2->bindParam(2, $username, PDO::PARAM_STR);
                        $prep2->execute();
                        $err = $this->sql->conn->errorCode();
                        if($err[0] !== "00000")
                        {
                            $dbcore->logd("Error removing user password reset token from table: ".var_export($dbcore->sql->conn->errorInfo(), 1).var_export($dbcore, 1), "error");
                        }
                        if($dbcore->mail->from($from))
                        {$dbcore->logd("Failed to add From address". var_export($dbcore,1), "error");}
                        if(!$dbcore->mail->addto($user_email))
                        {$dbcore->logd("Failed to add To address". var_export($dbcore,1), "error");}

                        if(!$dbcore->mail->subject("WiFiDB User Password Reset"))
                        {$dbcore->logd("Failed to add subject". var_export($dbcore,1), "error");}

                        $contents = "You have just reset your password, if you did not do this, I would email the admin...

Your account: $username_db

-WiFiDB Service";

                        if(!$dbcore->mail->text($contents))
                        {$dbcore->logd("Failed to add message". var_export($dbcore,1), "error");}

                        $smtp_conn = $dbcore->mail->connect($wifidb_smtp, 465, $sender, $sender_pass, 'tls', 10);
                        if ($smtp_conn)
                        {
                            if($dbcore->mail->send($smtp_conn))
                            {
                                $message = "Your password has been reset, you can now go login.";
                            }else
                            {
                                $message = "Your password has been reset, but failed to send confirmation email.";
                            }
                        }else
                        {
                            $dbcore->logd("Failed to connect to SMTP Host". var_export($dbcore,1), "error");
                            $message = "Failed to connect to SMTP Host.";
                        }
                        $success =  1;
                    }else
                    {
                        $message = "There was an error, please try again.";
                        $dbcore->logd("SQL Error: ". var_export($dbcore->sql->conn->errorInfo(),1). var_export($dbcore, 1));
                    }
                }else
                {
                    $message = "Username or Password did not match.";
                    $dbcore->logd("User failed to reset password. ". $_SERVER['REMOTE_ADDR'] . var_export($dbcore, 1), "error");
                }
            }else
            {
                $message = "New Passwords did not match.";
            }
        }
        if($success) $dbcore-redirect_page("", 5);
        $dbcore->smarty->assign("message", $message);
        $dbcore->smarty->display("login_results.tpl");
    break;

    case "reset_user_pass_request":
        $dbcore->smarty->display("reset_password_request.tpl");
    break;

    case "reset_password_request_proc":

    break;

    default :
        $dbcore->smarty->display("login_index.tpl");
    break;
}
?>