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
$userArray = []; 
foreach($user_logons as $logon)
{
	$db_pass = $logon['hash'];
	if($db_pass == $cookie_pass)
	{
		$login_check = 1;
		if($dbcore->sql->service == "mysql")
			{$sql0 = "SELECT * FROM user_info WHERE username = ? LIMIT 1";}
		else if($dbcore->sql->service == "sqlsrv")
			{$sql0 = "SELECT TOP 1 * FROM user_info WHERE username = ?";}
		$result = $dbcore->sql->conn->prepare($sql0);
		$result->bindParam(1, $username, PDO::PARAM_STR);
		$result->execute();
		$userArray = $result->fetch();
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

		case 'profile':
			$cp_profile = array();
			$cp_profile['email'] = $userArray['email'];
			$cp_profile['website'] = $userArray['website'];
			$cp_profile['Vis_ver'] = $userArray['Vis_ver'];
			$cp_profile['username'] = $userArray['username'];
			$cp_profile['id'] = $userArray['id'];
			$cp_profile['apikey'] = $userArray['apikey'];
			if($userArray['h_email']){$cp_profile['hide_email'] = 'checked';}else{$cp_profile['hide_email'] = 'unchecked';};
			
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_profile.tpl');

		break;
		
		##-------------##
		case "update_user_profile":
			$email = htmlentities(addslashes($_POST['email']),ENT_QUOTES);
			$h_email = addslashes($_POST['h_email']);
			if($h_email == "on"){$h_email = 1;}else{$h_email = 0;}
			$website = htmlentities(addslashes($_POST['website']),ENT_QUOTES);
			$Vis_ver = htmlentities(addslashes($_POST['Vis_ver']),ENT_QUOTES);
			$cp_profile = array();
			if($userArray['id'])
			{
				$sql1 = "UPDATE user_info SET email = ?, h_email = ?, website = ?, Vis_ver = ? WHERE id = ?";
				$result = $dbcore->sql->conn->prepare($sql1);
				$result->bindParam(1, $email, PDO::PARAM_STR);
				$result->bindParam(2, $h_email, PDO::PARAM_STR);
				$result->bindParam(3, $website, PDO::PARAM_STR);
				$result->bindParam(4, $Vis_ver, PDO::PARAM_STR);
				$result->bindParam(5, $userArray['id'], PDO::PARAM_INT);
				if($result->execute())
				{
					$cp_profile['message'] = "<br>Updated $username's Profile<br><br>";
				}else
				{
					$cp_profile['message'] = "<br>There was a serious error: ".$result->errorInfo()."<br><br>";
				}
			}else
			{
				$cp_profile['message'] = "<br>You are not logged in. Please log in and try again.<br><br>";
			}
			
			$dbcore->redirect_page('/wifidb/cp/index.php?func=profile', 2000);
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_msg.tpl');
		break;

		##-------------##
		case 'pref':

			$cp_profile = array();
			if($userArray['mail_updates']){$cp_profile['mail_updates'] = 'checked';}else{$cp_profile['mail_updates'] = 'unchecked';};
			if($userArray['announcements']){$cp_profile['announcements'] = 'checked';}else{$cp_profile['announcements'] = 'unchecked';};
			if($userArray['announce_comment']){$cp_profile['announce_comment'] = 'checked';}else{$cp_profile['announce_comment'] = 'unchecked';};
			if($userArray['pub_geocache']){$cp_profile['pub_geocache'] = 'checked';}else{$cp_profile['pub_geocache'] = 'unchecked';};
			if($userArray['new_users']){$cp_profile['new_users'] = 'checked';}else{$cp_profile['new_users'] = 'unchecked';};
			if($userArray['schedule']){$cp_profile['schedule'] = 'checked';}else{$cp_profile['schedule'] = 'unchecked';};
			if($userArray['imports']){$cp_profile['imports'] = 'checked';}else{$cp_profile['imports'] = 'unchecked';};
			if($userArray['kmz']){$cp_profile['kmz'] = 'checked';}else{$cp_profile['kmz'] = 'unchecked';};
			if($userArray['geonamed']){$cp_profile['geonamed'] = 'checked';}else{$cp_profile['geonamed'] = 'unchecked';};
			if($userArray['statistics']){$cp_profile['statistics'] = 'checked';}else{$cp_profile['statistics'] = 'unchecked';};
			$cp_profile['username'] = $userArray['username'];
			$cp_profile['id'] = $userArray['id'];
			
			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_email_prefs.tpl');

		break;

		##-------------##
		case 'update_user_pref':
			$schedule = ((@$_POST['schedule']) == 'on' ? 1 : 0);
			$imports = ((@$_POST['imports']) == 'on' ? 1 : 0);
			$kmz = ((@$_POST['kmz']) == 'on' ? 1 : 0);
			$new_users = ((@$_POST['new_users']) == 'on' ? 1 : 0);

			$cp_profile = array();
			if($userArray['id'])
			{
				$sql1 = "UPDATE user_info SET schedule = ?, imports = ?, kmz = ?, new_users = ? WHERE id = ?";
				$result = $dbcore->sql->conn->prepare($sql1);
				$result->bindParam(1, $schedule, PDO::PARAM_INT);
				$result->bindParam(2, $imports, PDO::PARAM_INT);
				$result->bindParam(3, $kmz, PDO::PARAM_INT);
				$result->bindParam(4, $new_users, PDO::PARAM_INT);
				$result->bindParam(5, $userArray['id'], PDO::PARAM_INT);
				if($result->execute())
				{
					$cp_profile['message'] = "<br>Updated $username's Preferences<br><br>";
				}else
				{
					$cp_profile['message'] = "<br>There was a serious error: ".$result->errorInfo()."<br><br>";
				}
			}else
			{
				$cp_profile['message'] = "<br>You are not logged in. Please log in and try again.<br><br>";
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