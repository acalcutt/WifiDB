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

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

include('../lib/init.inc.php');
$dbcore->smarty->assign('wifidb_page_label', 'User Control Panel');

#$theme = $GLOBALS['theme'];
$theme = "vistumbler";
$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);

list($cookie_pass, $username) = explode(':', base64_decode($_COOKIE['WiFiDB_login_yes'], 1));
#echo $username;
#echo $cookie_pass;

#Check if user is logged in
$sql0 = "SELECT * FROM user_login_hashes WHERE username = ?";
$result = $dbcore->sql->conn->prepare($sql0);
$result->bindParam(1, $username, PDO::PARAM_STR);
$result->execute();
$user_logons = $result->fetchAll();
$login_check = 0;
foreach($user_logons as $logon)
{
	$db_pass = $logon['hash'];
	if($db_pass == $cookie_pass)
	{
		$login_check = 1;
	}
}

#echo $login_check;

if($login_check)
{
	switch($func)
	{
		##-------------##
		case '':
			header('Location: /wifidb/cp/index.php?func=profile');
		break;
		
		case 'inbox':
			$sql = "SELECT pm.id, pm.title, pm.stimestamp, pm.message, pm.user1read, pm.user2read, u1.username AS username1, u2.username AS username2\n"
				. "FROM pm\n"
				. "INNER JOIN user_info AS u1 ON pm.user1 = u1.id\n"
				. "INNER JOIN user_info AS u2 ON pm.user2 = u2.id\n"
				. "WHERE (u1.username LIKE ? OR u2.username LIKE ?)\n"
				. "ORDER BY pm.stimestamp DESC";
			$result = $dbcore->sql->conn->prepare($sql);
			$result->bindParam(1, $username, PDO::PARAM_STR);
			$result->bindParam(2, $username, PDO::PARAM_STR);
			$result->execute();

			$row_color = 0;
			$n=0;
			$inbox_messages = array();
			while ( $array = $result->fetch(2) )
			{
				if($row_color == 1){$row_color = 0; $color = "light";}else{$row_color = 1; $color = "dark";}
				
				$markread = 0;
				if((strcasecmp($array['username1'], $username) == 0) && $array['user1read'] == 1)
					{$markread = 1;}
				elseif((strcasecmp($array['username2'], $username) == 0) && $array['user2read'] == 1)
					{$markread = 1;}

				$inbox_messages[$n]['class'] = $color;
				$inbox_messages[$n]['id'] = $array['id'];
				$inbox_messages[$n]['stimestamp'] = $array['stimestamp'];
				$inbox_messages[$n]['read'] = $markread;
				$inbox_messages[$n]['user1read'] = $array['user1read'];
				$inbox_messages[$n]['user2read'] = $array['user2read'];
				$inbox_messages[$n]['title'] = htmlspecialchars($array['title'], ENT_QUOTES, 'UTF-8');
				$inbox_messages[$n]['message'] = htmlspecialchars($array['message'], ENT_QUOTES, 'UTF-8');
				$inbox_messages[$n]['username1'] = htmlspecialchars($array['username1'], ENT_QUOTES, 'UTF-8');
				$inbox_messages[$n]['username2'] = htmlspecialchars($array['username2'], ENT_QUOTES, 'UTF-8');

				$n++;
			}
			$dbcore->smarty->assign('inbox_messages', $inbox_messages);
			$dbcore->smarty->display('user_cp_inbox.tpl');

		break;
		
		case 'replymsg':
			$id = $_GET['id'];
			
			$sql = "SELECT pm.id, pm.thread_id, pm.title, pm.stimestamp, pm.message, pm.user1read, pm.user2read, u1.username AS username1, u2.username AS username2, u1.id AS uid1, u2.id AS uid2\n"
				. "FROM pm INNER JOIN\n"
				. "user_info AS u1 ON pm.user1 = u1.id INNER JOIN\n"
				. "user_info AS u2 ON pm.user2 = u2.id\n"
				. "WHERE pm.id = ?";
			$result = $dbcore->sql->conn->prepare($sql);
			$result->bindParam(1, $id, PDO::PARAM_INT);
			$result->execute();
			$array = $result->fetch(2);
			
			if((strcasecmp($array['username1'], $username) == 0) || (strcasecmp($array['username2'], $username) == 0))
			{
				$dbcore->smarty->assign('message', $array);
				$dbcore->smarty->display('user_cp_replymsg.tpl');				
			}

		break;
		
		case 'sendmsg':
			if(@$_GET['thread_id']){$thread_id = $_GET['thread_id'];}else{$thread_id = uniqid('',TRUE);}
			if(@$_GET['to']){$to = $_GET['to'];}else{$to = '';}
			if(@$_GET['title']){$title = $_GET['title'];}else{$title = '';}

			$smt = $dbcore->sql->conn->prepare('SELECT id, username FROM user_info WHERE username = ?');
			$smt->bindParam(1, $username, PDO::PARAM_STR);
			$smt->execute();
			$touser = $smt->fetch(2);

			$smt = $dbcore->sql->conn->prepare('SELECT id, username FROM user_info WHERE disabled = 0 AND validated = 0 ORDER BY username');
			$smt->execute();
			$fromusers = $smt->fetchAll();

			$dbcore->smarty->assign('thread_id', $thread_id);
			$dbcore->smarty->assign('to', $to);
			$dbcore->smarty->assign('title', $title);
			$dbcore->smarty->assign('touser', $touser);
			$dbcore->smarty->assign('fromusers', $fromusers);
			$dbcore->smarty->assign('from', $username);
			$dbcore->smarty->display('user_cp_sendmsg.tpl');

		break;
		
		case 'sendmsg_submit':
		
			if(@$_REQUEST['thread_id']){$thread_id = $_REQUEST['thread_id'];}else{$thread_id = uniqid('',TRUE);}
			if(@$_REQUEST['from_id']){$from_id = $_REQUEST['from_id'];}else{$from_id = "";}
			if(@$_REQUEST['to_id']){$to_id = $_REQUEST['to_id'];}else{$to_id = "";}
			if(@$_REQUEST['subject']){$subject = $_REQUEST['subject'];}else{$subject = "";}	
			if(@$_REQUEST['message']){$message = $_REQUEST['message'];}else{$message = "";}
			$timestamp = date('Y-m-d G:i:s');
			
			if($message)
			{
				if($from_id)
				{
					$smt = $dbcore->sql->conn->prepare('SELECT username FROM user_info WHERE id = ?');
					$smt->bindParam(1, $from_id, PDO::PARAM_INT);
					$smt->execute();
					$from_user_arr = $smt->fetch(2);
					if(strcasecmp(@$from_user_arr['username'], $username) == 0)
					{
						if($to_id)
						{
							$smt = $dbcore->sql->conn->prepare('SELECT username FROM user_info WHERE id = ?');
							$smt->bindParam(1, $to_id, PDO::PARAM_INT);
							$smt->execute();
							$to_user_arr = $smt->fetch(2);
							if(@$to_user_arr['username'])
							{
								echo $thread_id;
								
								try 
								{
									$smt = $dbcore->sql->conn->prepare('INSERT INTO pm (thread_id, title, user1, user2, message, stimestamp, user1read) VALUES (?, ?, ?, ?, ?, ?, 1)');
									$smt->bindParam(1, $thread_id);
									$smt->bindParam(2, $subject);
									$smt->bindParam(3, $from_id);
									$smt->bindParam(4, $to_id);
									$smt->bindParam(5, $message);
									$smt->bindParam(6, $timestamp);
									$smt->execute();
									$dbcore->redirect_page('/wifidb/cp/index.php?func=inbox', 2000);
									$cp_profile['message'] = "<br>Message Sent<br><br>";
								} 
								catch (Exception $e) 
								{
									$dbcore->redirect_page('/wifidb/cp/index.php?func=sendmsg', 2000);
									$cp_profile['message'] = "<br>Error sending message ".$e->getMessage()."<br><br>";
								}
							}
							else
							{
								$dbcore->redirect_page('/wifidb/cp/index.php?func=sendmsg', 2000);
								$cp_profile['message'] = "<br>To username was not found<br><br>";
							}
						}
						else
						{
							$dbcore->redirect_page('/wifidb/cp/index.php?func=sendmsg', 2000);
							$cp_profile['message'] = "<br>no 'to' uid was given<br><br>";
						}
					}
					else
					{
						$dbcore->redirect_page('/wifidb/cp/index.php?func=sendmsg', 2000);
						$cp_profile['message'] = "From user does not match the currently logged in user<br><br>";
					}
				}
				else
				{
					$dbcore->redirect_page('/wifidb/cp/index.php?func=sendmsg', 2000);
					$cp_profile['message'] = "<br>no 'from' uid was given<br><br>";
				}
			}
			else
			{
				$dbcore->redirect_page('/wifidb/cp/index.php?func=sendmsg', 2000);
				$cp_profile['message'] = "<br>no message was given<br><br>";
			}

			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_msg.tpl');

		break;
		
		case 'profile':
			if($dbcore->sql->service == "mysql")
				{$sql0 = "SELECT * FROM user_info WHERE username = ? LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql0 = "SELECT TOP 1 * FROM user_info WHERE username = ?";}
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->bindParam(1, $username, PDO::PARAM_STR);
			$result->execute();
			$user = $result->fetch();

			$cp_profile = array();
			$cp_profile['email'] = $user['email'];
			$cp_profile['website'] = $user['website'];
			$cp_profile['Vis_ver'] = $user['Vis_ver'];
			$cp_profile['username'] = $user['username'];
			$cp_profile['id'] = $user['id'];
			$cp_profile['apikey'] = $user['apikey'];
			if($user['h_email']){$cp_profile['hide_email'] = 'checked';}else{$cp_profile['hide_email'] = 'unchecked';};
			
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_profile.tpl');

		break;
		
		##-------------##
		case "update_user_profile":
			$username = addslashes(strtolower($_POST['username']));
			$user_id = addslashes(strtolower($_POST['user_id']));
				$email = htmlentities(addslashes($_POST['email']),ENT_QUOTES);
			$h_email = addslashes($_POST['h_email']);
			if($h_email == "on"){$h_email = 1;}else{$h_email = 0;}
				$website = htmlentities(addslashes($_POST['website']),ENT_QUOTES);
			$Vis_ver = htmlentities(addslashes($_POST['Vis_ver']),ENT_QUOTES);
			if($dbcore->sql->service == "mysql")
				{$sql0 = "SELECT id FROM user_info WHERE username = '$username' LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql0 = "SELECT TOP 1 id FROM user_info WHERE username = '$username'";}
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$array = $result->fetch();
			$cp_profile = array();
			if($array['id']+0 === $user_id+0)
			{
				$sql1 = "UPDATE user_info SET email = '$email', h_email = '$h_email', website = '$website', Vis_ver = '$Vis_ver' WHERE id = '$user_id'";
				$result = $dbcore->sql->conn->prepare($sql1);
				if($result->execute())
				{
					$cp_profile['message'] = "<br>Updated $username's Profile<br><br>";
				}else
				{
					$cp_profile['message'] = "<br>There was a serious error: ".$result->errorInfo()."<br><br>";
				}
			}else
			{
				$cp_profile['message'] = "<br>User ID's did not match, there was an error, contact the support forums for more help<br><br>";
			}
			
			$dbcore->redirect_page('/wifidb/cp/index.php?func=profile', 2000);
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_msg.tpl');
		break;

		##-------------##
		case 'pref':
			if($dbcore->sql->service == "mysql")
				{$sql0 = "SELECT * FROM user_info WHERE username = '$username' LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql0 = "SELECT TOP 1 * FROM user_info WHERE username = '$username'";}
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$newArray = $result->fetch();				

			$cp_profile = array();
			if($newArray['mail_updates']){$cp_profile['mail_updates'] = 'checked';}else{$cp_profile['mail_updates'] = 'unchecked';};
			if($newArray['announcements']){$cp_profile['announcements'] = 'checked';}else{$cp_profile['announcements'] = 'unchecked';};
			if($newArray['announce_comment']){$cp_profile['announce_comment'] = 'checked';}else{$cp_profile['announce_comment'] = 'unchecked';};
			if($newArray['pub_geocache']){$cp_profile['pub_geocache'] = 'checked';}else{$cp_profile['pub_geocache'] = 'unchecked';};
			if($newArray['new_users']){$cp_profile['new_users'] = 'checked';}else{$cp_profile['new_users'] = 'unchecked';};
			if($newArray['schedule']){$cp_profile['schedule'] = 'checked';}else{$cp_profile['schedule'] = 'unchecked';};
			if($newArray['imports']){$cp_profile['imports'] = 'checked';}else{$cp_profile['imports'] = 'unchecked';};
			if($newArray['kmz']){$cp_profile['kmz'] = 'checked';}else{$cp_profile['kmz'] = 'unchecked';};
			if($newArray['geonamed']){$cp_profile['geonamed'] = 'checked';}else{$cp_profile['geonamed'] = 'unchecked';};
			if($newArray['statistics']){$cp_profile['statistics'] = 'checked';}else{$cp_profile['statistics'] = 'unchecked';};
			$cp_profile['username'] = $newArray['username'];
			$cp_profile['id'] = $newArray['id'];
			
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_email_prefs.tpl');

		break;

		##-------------##
		case 'update_user_pref':
			$username = addslashes(strtolower($_POST['username']));
			$user_id = addslashes(strtolower($_POST['user_id']));
			$imports = ((@$_POST['imports']) == 'on' ? 1 : 0);
			$kmz = ((@$_POST['kmz']) == 'on' ? 1 : 0);
			$new_users = ((@$_POST['new_users']) == 'on' ? 1 : 0);
			$schedule = ((@$_POST['schedule']) == 'on' ? 1 : 0);
			if($dbcore->sql->service == "mysql")
				{$sql0 = "SELECT id FROM user_info WHERE username = '$username' LIMIT 1";}
			else if($dbcore->sql->service == "sqlsrv")
				{$sql0 = "SELECT TOP 1 id FROM user_info WHERE username = '$username'";}
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$array = $result->fetch();
			$cp_profile = array();
			if($array['id']+0 === $user_id+0)
			{
				$sql1 = "UPDATE user_info SET
															schedule	=	'$schedule',
															imports = '$imports',
															kmz = '$kmz',
															new_users = '$new_users'
															WHERE id = '$user_id'";
				$result = $dbcore->sql->conn->prepare($sql1);
				if($result->execute())
				{
					$cp_profile['message'] = "<br>Updated $username's Preferences<br><br>";
				}else
				{
					$cp_profile['message'] = "<br>There was a serious error: ".$result->errorInfo()."<br><br>";
				}
			}else
			{
				$cp_profile['message'] = "<br>User ID's did not match, there was an error, contact the <a href='http://forum.techidiots.net/forum/viewforum.php?f=47'>support forums</a> for more help.<br><br>";
			}
			
			$dbcore->redirect_page('/wifidb/cp/index.php?func=pref', 2000);
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_msg.tpl');
		break;
	}
}
else
{
	$dbcore->redirect_page("/wifidb/login.php?return=".urlencode("/wifidb/cp/".basename($_SERVER['REQUEST_URI'])), 1000);
	$cp_profile['message'] = "<br>You must be logged in to go into the User Control Panel. Redirecting to login page.<br><br>";
	$dbcore->smarty->assign('user_cp_profile', $cp_profile);
	$dbcore->smarty->display('user_cp_msg.tpl');
}
?>