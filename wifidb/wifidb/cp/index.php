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
$sql0 = "SELECT * FROM `wifi`.`user_login_hashes` WHERE `username` = ?";
$result = $dbcore->sql->conn->prepare($sql0);
$result->bindParam(1, $username, PDO::PARAM_STR);
$result->execute();
$user_logons = $result->fetchAll();
$login_check = 0;
foreach($user_logons as $logon)
{
	$db_pass = $logon['hash'];
	if(crypt($cookie_pass, $db_pass) == $db_pass)
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
			header('Location: /wifidb/cp/?func=profile');
		break;
		
		case 'profile':

			$sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = ? LIMIT 1";
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
				$sql0 = "SELECT `id` FROM `wifi`.`user_info` WHERE `username` = '$username' LIMIT 1";
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$array = $result->fetch();
			$cp_profile = array();
			if($array['id']+0 === $user_id+0)
			{
				$sql1 = "UPDATE `wifi`.`user_info` SET `email` = '$email', `h_email` = '$h_email', `website` = '$website', `Vis_ver` = '$Vis_ver' WHERE `id` = '$user_id' LIMIT 1";
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
			
			$dbcore->redirect_page('/wifidb/cp/?func=profile', 2000);
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_msg.tpl');
		break;

		##-------------##
		case 'pref':
			$sql0 = "SELECT * FROM `wifi`.`user_info` WHERE `username` = '$username' LIMIT 1";
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
			$mail_updates = ((@$_POST['mail_updates']) == 'on' ? 1 : 0);
			$imports = ((@$_POST['imports']) == 'on' ? 1 : 0);
			$kmz = ((@$_POST['kmz']) == 'on' ? 1 : 0);
			$new_users = ((@$_POST['new_users']) == 'on' ? 1 : 0);
			$statistics = ((@$_POST['statistics']) == 'on' ? 1 : 0);
			$announcements = ((@$_POST['announcements']) == 'on' ? 1 : 0);
			$announce_comment = ((@$_POST['announce_comment']) == 'on' ? 1 : 0);
			$geonamed = ((@$_POST['geonamed']) == 'on' ? 1 : 0);
			$pub_geocache = ((@$_POST['pub_geocache']) == 'on' ? 1 : 0);
			$schedule = ((@$_POST['schedule']) == 'on' ? 1 : 0);
			$sql0 = "SELECT `id` FROM `wifi`.`user_info` WHERE `username` = '$username' LIMIT 1";
			$result = $dbcore->sql->conn->prepare($sql0);
			$result->execute();
			$array = $result->fetch();
			$cp_profile = array();
			if($array['id']+0 === $user_id+0)
			{
				$sql1 = "UPDATE `wifi`.`user_info` SET
															`mail_updates` = '$mail_updates',
															`schedule`	=	'$schedule',
															`imports` = '$imports',
															`kmz` = '$kmz',
															`new_users` = '$new_users',
															`statistics` = '$statistics',
															`announcements` = '$announcements',
															`announce_comment` = '$announce_comment',
															`geonamed` = '$geonamed',
															`pub_geocache` = '$pub_geocache'
															WHERE `id` = '$user_id'";
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
			
			$dbcore->redirect_page('/wifidb/cp/?func=pref', 2000);
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_msg.tpl');
		break;
	}
}
else
{
	$dbcore->redirect_page('/wifidb/login.php?return=%2Fwifidb%2Fcp', 2000);
	$cp_profile['message'] = "<br>You must be logged in to go into the User Control Panel. Redirecting to login page.<br><br>";
	$dbcore->smarty->assign('user_cp_profile', $cp_profile);
	$dbcore->smarty->display('user_cp_msg.tpl');
}
?>