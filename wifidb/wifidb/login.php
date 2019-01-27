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


define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
if(isset($_REQUEST['return'])){$return = $_REQUEST['return'];}else{$return="";}

if($return == ''){$return = '/'.$dbcore->root;}
switch($func)
{
    case "login_proc":
        $username = $_POST['time_user'];
        $password = $_POST['time_pass'];
        $Bender_remember_me = (@$_POST['Bender_remmeber_me'] == "Yes" ? 1 : 0 );
        
        $dbcore->sec->Login($username, $password, $Bender_remember_me);
        switch($dbcore->sec->login_val)
        {
            case "locked":
                $message = 'This user is locked out. contact this WiFiDB\'s admin, or go to the <a href="http://forum.techidiots.net/">forums</a> and bitch to Phil.';
            break;

            case "validate":
                $message = 'This user is not validated yet. You should be getting an email soon if not already from the Database with a link to validate your email address first so that we can verify that you are in fact a real person. The administrator of the site has enabled this by default.';
            break;
            
            case "hash_tbl_fail":
                $message = "Failed to set Hash for the Login Cookie to the table...";
            break;

            case "p_fail":
                $message = 'Bad Username or Password!';
            break;

            case "u_fail":
                $message = 'Username does not exsist.';
            break;

            case "u_u_r_fail":
                $message = "Failed to update User row";
            break;

            case "good":
                $message = 'Login Successful!';
                $dbcore->redirect_page($return, 2000);
            break;

            case "cookie_fail":
                $message = "Set Cookie fail, check the bottom of the glass, or your browser.";
            break;

            default:
                $message = "Unknown Return.";
            break;
        }
        $dbcore->smarty->assign('message', $message);
        $dbcore->smarty->display('login_result.tpl');
    break;

    #---#
    case "logout":
        #$admin_cookie = (int) $_REQUEST['a_c']+0;
		if((int)@$_REQUEST['a_c'] === 1){$admin_cookie = 1;}else{$admin_cookie = 0;}
        if($admin_cookie === 1)
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
        list($cookie_pass_hash, $username) = explode(":", base64_decode($_COOKIE[$cookie_name]));
		if($dbcore->sql->service == "mysql")
			{$sql = "DELETE FROM `user_login_hashes` WHERE `username` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "DELETE FROM [user_login_hashes] WHERE [username] = ?";}
        $prep = $dbcore->sql->conn->prepare($sql);
        $prep->bindParam(1, $username, PDO::PARAM_STR);
        $prep->execute();
        $dbcore->sql->checkError();
        
        if(setcookie($cookie_name, "@LOGGEDOUT!:".$username, time()-3600, $path, $dbcore->sec->domain, $dbcore->sec->ssl))
        {
            $message = $msg;
            $dbcore->redirect_page("", 2000);
        }
        else
        {
            $message = "Could not log you out.. :-(";
        }
		if(strpos($return,'/wifidb/cp/') !== false){$return = '/'.$dbcore->root;}#Redirect control panel logout to homepage
		$dbcore->redirect_page($return, 2000);
        $dbcore->smarty->assign('message', $message);
        $dbcore->smarty->display('login_result.tpl');
    break;

    #---#
    case "create_user_form":
		$dbcore->smarty->assign('message', "");
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
            $dbcore->smarty->assign('message', 'Email is not valid.');
            $dbcore->smarty->display('create_user.tpl');
        }
        if($password !== $password2)
        {
            $dbcore->smarty->assign('message', 'Passwords did not match.');
            $dbcore->smarty->display('create_user.tpl');
        }else
        {
            #var_dump("Start Create User");
            $ret = $dbcore->sec->CreateUser($username, $password, $email);
            $message = $dbcore->sec->mesg['message'];
            #var_dump($message, $ret);
            switch($ret)
            {
                case 1:
					$subject = "Vistumbler WifiDB - User '$username' Created";
					$message = "New User '$username' Created.\r\nUser Information: ".$dbcore->URL_PATH."opt/userstats.php?func=alluserlists&user==$username \r\n";
					$dbcore->wdbmail->mail_users($message, $subject, "new_users", 0);
                    #User created!, now if the admin has enabled Email Confirmation before a user can be used, send it out, other wise let them login.
                    if($dbcore->sec->email_validation)
                    {
						$msg = "The WiFiDB requires confirmation before you can log in. Please click the following link to activate your account";
						$subject = "Vistumbler WifiDB - New User Confirmation";
                        if($dbcore->wdbmail->mail_validation('validate_user', $email, $username, $msg, $subject))
                        {
							$message = "<font color='Green'><h2>User Created! You should be getting a Confirmation email soon. Please click on the email link to confirm your account.</h2></font>";
                        }else
                        {
							$message = "<font color='Yellow'><h2>Email Confirmation has been enabled, but failed to send the email. Contact the Admins for help.</h2></font>";
                        }
                    }else
                    {
                        $message = "<font color='Green'><h2>User Created! Go ahead and login.</h2></font>";
                    }
                    $dbcore->smarty->assign('message', $message);
                    $dbcore->smarty->display('login_index.tpl');
                break;

                case 0:
                    #Failed to create a user for some reason, tell them why.
                    $dbcore->smarty->assign('message', $message);
                    $dbcore->smarty->display('create_user.tpl');
                break;
                default:
                    die("ummmmm...");
                    break;
            }
        }
    break;

    case "validate_user":
        $validate_code = filter_input(INPUT_GET, 'validate_code', FILTER_SANITIZE_STRING);
		$username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `username` FROM `user_validate` WHERE `code` = ?";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT [username] FROM [user_validate] WHERE [code] = ?";}
        $result = $dbcore->sql->conn->prepare($sql);
        $result->execute(array($validate_code));
        $v_array = $result->fetch(2);
		$success = 0;
        $db_username = $v_array['username'];
        if($db_username)
        {
			if($dbcore->sql->service == "mysql")
				{$update = "UPDATE `user_info` SET `validated` = '0' WHERE `username` = ?";}
			else if($dbcore->sql->service == "sqlsrv")
				{$update = "UPDATE [user_info] SET [validated] = '0' WHERE [username] = ?";}
            $result = $dbcore->sql->conn->prepare($update);
            $result->bindParam(1, $username);
            $result->execute();
            $err = $dbcore->sql->conn->errorCode();
			#echo $update."<br>";
			if($err == "00000")
            {
				if($dbcore->sql->service == "mysql")
					{$delete = "DELETE FROM `user_validate` WHERE `username` = ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$delete = "DELETE FROM [user_validate] WHERE [username] = ?";}
                $result = $dbcore->sql->conn->prepare($delete);
                $result->bindParam(1, $username);
                $result->execute();
                $err = $dbcore->sql->conn->errorCode();
				#echo $delete."<BR>";
                if($err == "00000")
                {
                    $message = "<font color='Green'><h2>Username: {$username}\r\n<BR>Has been activated! Go login -></h2></font>";
					$success = 1;
                }else
                {
                    $message = "<font color='Yellow'><h2>Username: {$username}\r\n<BR>Activated, but failed to remove from activation table, <br>
                    This isnt a critical issue, but should be looked into by an administrator.
                    <br>".var_export($dbcore->sql->conn->errorInfo())."</h2></font>";
					$success = 1;
                }
            }else
            {
                $message = "<font color='red'><h2>Username: {$username}\r\n<BR>Failed to activate...<br>".var_export($dbcore->sql->conn->errorInfo())."</h2></font>";
				$success = 0;
            }
        }else
        {
            $message = "<font color='red'><h2>Invalid Activation Code, Would you like to <a class='links' href='?func=revalidate&username=$username'>send another</a> validation code?.</h2></font>";
			$success = 0;
        }
        $dbcore->smarty->assign("message", $message);
        $dbcore->smarty->display("login_index.tpl");
    break;

    case "revalidate":
		$username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
		
		#Get users email address
		if($dbcore->sql->service == "mysql")
			{$sql0 = "SELECT `email` FROM `user_info` WHERE `username` LIKE ? LIMIT 1";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql0 = "SELECT TOP 1 [email] FROM [user_info] WHERE [username] LIKE ?";}
		$prep = $dbcore->sql->conn->prepare($sql0);
		$prep->bindParam(1, $username, PDO::PARAM_STR);
		$prep->execute();
		$newArray = $prep->fetch(2);
		$db_email = $newArray['email'];
		
		#If the user email address way found, send them a new validation email
        if($db_email)
        {
			$msg = "The WiFiDB requires confirmation before you can log in. Please click the following link to activate your account";
			$subject = "Vistumbler WifiDB - Account Confirmation";
			if($dbcore->wdbmail->mail_validation('validate_user', $db_email, $username, $msg, $subject))
			{
				$message = "<font color='Green'><h2>Confirmation has been re-sent! You should be getting a Confirmation email soon. Please click on the email link to confirm your account.</h2></font>";
			}else
			{
				$message = "<font color='Yellow'><h2>Email Confirmation has been enabled, but failed to send the email. Contact the Admins for help.</h2></font>";
			}
		}
		else
		{
			$message = "<font color='Red'><h2>Error: No email address found for $username. No confirmation can be sent.</h2></font>";
		}

        $dbcore->smarty->assign('message', $message);
        $dbcore->smarty->display('login_result.tpl');
    break;

    case "reset_user_pass_request":
        $dbcore->smarty->display("reset_password_request.tpl");
    break;

    case "reset_password_request_proc":
        $username   = $_REQUEST['username_f'];
        $email      = $_REQUEST['email_f'];
		if(!$username || !$email)
        {
            $dbcore->smarty->assign('message', "<font color='Red'><h2>Username or Email not specified</h2></font>");
            $dbcore->smarty->display('reset_password_request.tpl');
        }
        else
        {
			if($dbcore->sql->service == "mysql")
				{$sql0 = "SELECT `email` FROM `user_info` WHERE `username` LIKE ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql0 = "SELECT TOP 1 [email] FROM [user_info] WHERE [username] LIKE ?";}
			$prep = $dbcore->sql->conn->prepare($sql0);
			$prep->bindParam(1, $username, PDO::PARAM_STR);
			$prep->execute();
			$newArray = $prep->fetch(2);
			$db_email = $newArray['email'];
			if(strtolower($email) != strtolower($db_email))
			{
				$message = "Username or Email is incorrect.";
				$dbcore->logd("User failed to reset password. ".$message." ".$_SERVER['REMOTE_ADDR'] . var_export($dbcore, 1), "error");
				$dbcore->smarty->assign('message', "<font color='Red'><h2>".$message."</h2></font>");
				$dbcore->smarty->display('reset_password_request.tpl');
			}
			else
			{
				$msg = "To reset your wifidb password, click the following link. If you did not reset your password, please ignore this email.";
				$subject = "Vistumbler WifiDB - Password Reset Confirmation";
				if($dbcore->wdbmail->mail_validation('reset_password_validated', $email, $username, $msg, $subject))
				{
					$message = "<font color='Green'><h2>Password reset requested! You should be getting a Confirmation email soon, click on the email link to confirm your account.</h2></font>";
				}else
				{
					$message = "Email Confirmation has been enabled, but failed to send the email. Contact the Admins for help.";
				}
				$dbcore->smarty->assign("message", $message);
				$dbcore->smarty->display('login_result.tpl');				
			}
		}
    break;
	
    case "reset_password_validated":
		$username = filter_input(INPUT_GET, 'username', FILTER_SANITIZE_SPECIAL_CHARS);
        $validate_code = filter_input(INPUT_GET, 'validate_code', FILTER_SANITIZE_STRING);

		#Check if username and validation code exist
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `username` FROM `user_validate` WHERE `username` = ? AND `code` = ? LIMIT 1";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT TOP 1 [username] FROM [user_validate] WHERE [username] = ? AND [code] = ?";}
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $username);
		$result->bindParam(2, $validate_code);
		$result->execute();
        $v_array = $result->fetch(2);
        $db_username = $v_array['username'];
        if($db_username)
        {
			$dbcore->smarty->assign("username", $db_username);
			$dbcore->smarty->assign("validate_code", $validate_code);
			$dbcore->smarty->display("reset_password_validate.tpl");
		}
		else
		{
			$message = "<font color='red'><h2>Error. Username or Validation Code is incorrect or no longer valid.</h2></font>";
			$dbcore->smarty->assign('message', $message);
			$dbcore->smarty->assign("logon_return_url", $return);
			$dbcore->smarty->display('login_result.tpl');
		}
    break;
	
    case "reset_password_finish":
		$username = filter_input(INPUT_POST, 'usernameh', FILTER_SANITIZE_STRING);
        $validate_code = filter_input(INPUT_POST, 'validate_code', FILTER_SANITIZE_STRING);
        $newpassword = filter_input(INPUT_POST, 'newpassword', FILTER_SANITIZE_SPECIAL_CHARS);
        $newpassword2 = filter_input(INPUT_POST, 'newpassword2', FILTER_SANITIZE_SPECIAL_CHARS);
		
		#Check if username and validation code exist
		if($dbcore->sql->service == "mysql")
			{$sql = "SELECT `username` FROM `user_validate` WHERE `username` = ? AND `code` = ? LIMIT 1";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql = "SELECT TOP 1 [username] FROM [user_validate] WHERE [username] = ? AND [code] = ?";}
		$result = $dbcore->sql->conn->prepare($sql);
		$result->bindParam(1, $username);
		$result->bindParam(2, $validate_code);
		$result->execute();
        $v_array = $result->fetch(2);
        $db_username = $v_array['username'];
        if($db_username)
        {
			#Check if new password fields match
			if($newpassword === $newpassword2)
			{
				#Change the users password, validate them, and unlock account
				$salt               = $dbcore->sec->GenerateKey(29);
				$password_hashed    = crypt($newpassword, '$2a$07$'.$salt.'$');

				if($dbcore->sql->service == "mysql")
					{$update = "UPDATE `user_info` SET `password` = ?, validated = 0, locked = 0 WHERE `username` LIKE ?";}
				else if($dbcore->sql->service == "sqlsrv")
					{$update = "UPDATE [user_info] SET [password] = ?, validated = 0, locked = 0 WHERE [username] LIKE ?";}
				$prep1 = $dbcore->sql->conn->prepare($update);
				$prep1->bindParam(1, $password_hashed, PDO::PARAM_STR);
				$prep1->bindParam(2, $db_username, PDO::PARAM_STR);
				$prep1->execute();
				$uperr = $dbcore->sql->conn->errorCode();
				if($uperr == "00000")
				{
					#DELETE validation entry for this user
					if($dbcore->sql->service == "mysql")
						{$delete = "DELETE FROM `user_validate` WHERE `username` = ?";}
					else if($dbcore->sql->service == "sqlsrv")
						{$delete = "DELETE FROM [user_validate] WHERE [username] = ?";}
					$result = $dbcore->sql->conn->prepare($delete);
					$result->bindParam(1, $db_username);
					$result->execute();
					$delerr = $dbcore->sql->conn->errorCode();
					if($delerr == "00000")
					{
						$message = "<font color='Green'><h2>Password for {$db_username} has been updated!</h2></font>";
					}else
					{
						$message = "<font color='Yellow'><h2>Password for {$db_username} has been updated, but the user_validate entry was not deleted.</h2></font>";
					}
					$dbcore->smarty->assign("message", $message);
					$dbcore->smarty->display("login_index.tpl");					
				}else
				{
					$message = "<font color='red'><h2>Error. Failed to update password.</h2></font>";
					$dbcore->smarty->assign('message', $message);
					$dbcore->smarty->assign("username", $db_username);
					$dbcore->smarty->assign("validate_code", $validate_code);
					$dbcore->smarty->display("reset_password_validate.tpl");
				}
			}
			else
			{
				$message = "<font color='red'><h2>Error. Passwords do not match.</h2></font>";
				$dbcore->smarty->assign('message', $message);
				$dbcore->smarty->assign("username", $username);
				$dbcore->smarty->assign("validate_code", $validate_code);
				$dbcore->smarty->display("reset_password_validate.tpl");
			}
		}
		else
		{
			$message = "<font color='red'><h2>Error. Username or Validation Code is incorrect or no longer valid.</h2></font>";
			$dbcore->smarty->assign('message', $message);
			$dbcore->smarty->assign("logon_return_url", $return);
			$dbcore->smarty->display('login_result.tpl');
		}

    break;

    default :
		$dbcore->smarty->assign('message', "");
		$dbcore->smarty->assign("logon_return_url", $return);
        $dbcore->smarty->display("login_index.tpl");
    break;
}
?>