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

$seed = $dbcore->sec->global_seed;
$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
$return = filter_input(INPUT_GET, 'return', FILTER_SANITIZE_SPECIAL_CHARS);

if($return == ''){$return = $dbcore->PATH;}

switch($func)
{
	case "login_proc":
            $username = filter_input(INPUT_POST, 'time_user', FILTER_SANITIZE_SPECIAL_CHARS);
            $password = filter_input(INPUT_POST, 'time_pass', FILTER_SANITIZE_SPECIAL_CHARS);
            $login = $dbcore->login($username, $password, $seed, 0);
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

                case"u_fail":
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
		$username = $_REQUEST['time_user'];
		$password = $_REQUEST['time_pass'];
		$password2 = $_REQUEST['time_pass2'];
		$email = $_REQUEST['time_email'];
                $dbcore->checkEmail($email);
		if($password !== $password2)
		{
                    $dbcore->smarty->assign('wifidb_create_message', 'Passwords did not match');
                    $dbcore->smarty->display('user_create.tpl');
                    exit();
		}else
		{
                    $create = $dbcore->sec->create_user($username, $password, $email, $user_array=array(0,0,0,1), $seed);
                    switch($create)
                    {
                        case 1:
                            if($dbcore->sec->email_validation)
                            {
                                $message = "User Created! You should be getting a Validation email soon, click on the link to confirm your account and to start you uploads!.";
                                if($dbcore->mail->mail_validation($email, $username))
                                {
                                    $message = "Email Validation has been enabled, check your email for a link and activate your account first.";
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
                            exit();
                        break;

                        case is_array($create):
                                list($er, $msg) = $create;
                                switch($er)
                                {
                                    case "create_tb":
                                        $message = $msg.'<BR>This is a serious error, contact Phil on the <a href="http://forum.techidiots.net/">forums</a><br>MySQL Error Message: '.$msg."<br><br><h1>D'oh!</h1>";
                                    break;

                                    case "dup_u":
                                        $message = '<h2><font color="red">There is a user already with that username or email address. Pick another one.</font></h2><BR>';
                                    break;

                                    case "err_email":
                                        $message = '<h2><font color="red">The email address you provided is not valid. Please enter a real email.</font></h2><BR>';
                                    break;

                                    case "un_err":
                                        $message = '<h2><font color="red">The username you provided is blank. How are you supposed to login?</font></h2><BR>';
                                    break;

                                    case "pw_err":
                                        $message = '<h2><font color="red">The password you provided is blank. How are you supposed to login?</font></h2><BR>';
                                    break;
                                }
                                $dbcore->smarty->assign('wifidb_create_message', $message);
                                $dbcore->smarty->assign('wifidb_create_message', $username);
                                $dbcore->smarty->assign('wifidb_create_message', $email);
                                $dbcore->smarty->display('user_create.tpl');
                                exit();
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

	case "reset_password_proc":
		pageheader("Security Page");
		require_once("lib/MAIL5.php");
		$username = filter_input(INPUT_POST, 'time_user', FILTER_SANITIZE_SPECIAL_CHARS);
		$password = filter_input(INPUT_POST, 'time_current_pwd', FILTER_SANITIZE_SPECIAL_CHARS);
		$newpassword = filter_input(INPUT_POST, 'time_new_pwd', FILTER_SANITIZE_SPECIAL_CHARS);
		$newpassword2 = filter_input(INPUT_POST, 'time_new_pwd_again', FILTER_SANITIZE_SPECIAL_CHARS);

		$from           =   $GLOBALS['admin_email'];
		$wifidb_smtp    =   $GLOBALS['wifidb_smtp'];
		$sender         =   $from;
		$sender_pass    =   $GLOBALS['wifidb_from_pass'];
		$mail           =   new MAIL5();
		$seed           =   $GLOBALS['login_seed'];

		$sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = '$username' LIMIT 1";
		$result = mysql_query($sql0, $conn);
		$newArray = mysql_fetch_array($result);
		$username_db = $newArray['username'];
		$user_email = $newArray['email'];
		$password_db = $newArray['password'];
		if($username_db == '')
		{
			?>
			<p align='center'><font color='red'><h2>Username was blank, try again.</h2></font></p>
			<p align='center'><font color='red'><h2>Reset forgoten password</h2></font></p>
			<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?func=reset_password_proc">
			<table align="center">
				<tr>
					<td colspan="2"><p align="center"><img src="themes/wifidb/img/logo.png"></p></td>
				</tr>
				<tr>
					<td>Username</td>
					<td><input type="text" name="time_user"></td>
				</tr>
				<tr>
					<td>Temp Password</td>
					<td><input type=PASSWORD name="time_current_pwd"></td>
				</tr>
				<tr>
					<td>New Password</td>
					<td><input type=PASSWORD name="time_new_pwd"></td>
				</tr>
				<tr>
					<td>Retype New Password</td>
					<td><input type=PASSWORD name="time_new_pwd_again"></td>
				</tr>
				<tr>
					<td colspan="2"><p align="center"><input type="submit" value="Re-set Password"></p></td>
				</tr>
			</table>
			</form>
			<?php
		}else
		{
			if($newpassword === $newpassword2)
			{
				$password = md5($password.$seed);
				if($password === $password_db)
				{
					$setpassword = md5($newpassword.$seed);
					$update = "UPDATE `wifi`.`user_info` SET `password` = '$setpassword' WHERE `username` = '$username_db'";
				#	echo $update."<BR>";
					if(mysql_query($update, $conn))
					{
						if(!$mail->from($from))
						{die("Failed to add From address\r\n");}
						if(!$mail->addto($user_email))
						{die("Failed to add To address\r\n");}

						if(!$mail->subject("WiFiDB User Password Reset"))
						{die("Failed to add subject\r\n");}

						$contents = "You have just reset your password, if you did not do this, i would email the admin...

Your account: $username

-WiFiDB Service";

						if(!$mail->text($contents))
						{die("Failed to add message\r\n");}

						$smtp_conn = $mail->connect($wifidb_smtp, 465, $sender, $sender_pass, 'tls', 10);
						if ($smtp_conn)
						{
							if($mail->send($smtp_conn))
							{
								?>
									<p align='center'><font color='green'><h2>Your password has been reset, you can now go login.</h2></font></p>
								<?php
							}else
							{
								?>
									<p align='center'><font color='red'><h2>Your password has been reset, but failed to send email to user.</h2></font></p>
								<?php
							}
						}else
						{
							?>
								<p align='center'><font color='red'><h2>Failed to connect to SMTP Host.</h2></font></p>
							<?php
						}
					}else
					{
						?><h2>Mysql Error:</h2><font color='red'> <?php echo mysql_error($conn); ?></font><?php
					}
				}else
				{
					?>
					<p align='center'><font color='red'><h2>Password did not match DB.</h2></font></p>
					<p align='center'><font color='red'><h2>Reset forgoten password</h2></font></p>
					<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>?func=reset_password_proc">
					<table align="center">
						<tr>
							<td colspan="2"><p align="center"><img src="themes/wifidb/img/logo.png"></p></td>
						</tr>
						<tr>
							<td>Username</td>
							<td><input type="text" name="time_user"></td>
						</tr>
						<tr>
							<td>Temp Password</td>
							<td><input type=PASSWORD name="time_current_pwd"></td>
						</tr>
						<tr>
							<td>New Password</td>
							<td><input type=PASSWORD name="time_new_pwd"></td>
						</tr>
						<tr>
							<td>Retype New Password</td>
							<td><input type=PASSWORD name="time_new_pwd_again"></td>
						</tr>
						<tr>
							<td colspan="2"><p align="center"><input type="submit" value="Re-set Password"></p></td>
						</tr>
					</table>
					</form>
					<?php
				}
			}else
			{
				$message = array(
                                                    "New Passwords did not match.",
                                                    "Reset forgoten password"
                                                );
			}
		}
	break;

	case "reset_user_pass":
		$dbcore->smarty->display("reset_password.tpl");
	break;
    
	default :
		$dbcore->smarty->display("login.tpl");
	break;
}
?>