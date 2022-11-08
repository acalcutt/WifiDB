<?php
/*
Copyright (C) 2022 Andrew Calcutt 2011 Phil Ferland

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

$func = filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);

$username = $dbcore->sec->LoginUser;
if($username)
{
	if($dbcore->sql->service == "mysql")
		{$sql0 = "SELECT * FROM user_info WHERE username = ? LIMIT 1";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sql0 = "SELECT TOP 1 * FROM user_info WHERE username = ?";}
	$result = $dbcore->sql->conn->prepare($sql0);
	$result->bindParam(1, $username, PDO::PARAM_STR);
	$result->execute();
	$userArray = $result->fetch();
	$user_id = $userArray['id'];

	switch($func)
	{
		case '':
			header('Location: /wifidb/cp/index.php?func=profile');
		break;

		case 'profile':
			$cp_profile = array();
			$cp_profile['email'] = $userArray['email'];
			$cp_profile['website'] = $userArray['website'];
			$cp_profile['Vis_ver'] = $userArray['Vis_ver'];
			$cp_profile['apikey'] = $userArray['apikey'];
			if($userArray['import_require_login']){$cp_profile['import_require_login'] = 'checked';}else{$cp_profile['import_require_login'] = 'unchecked';};
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
			$apikey = htmlentities(addslashes($_POST['apikey']),ENT_QUOTES);
			$import_require_login = ((@$_POST['import_require_login']) == 'on' ? 1 : 0);
			
			if($email !== $userArray['email']){$dbcore->sec->logd("Update profile email for ".$username."(".$user_id.") from ".$userArray['email']." to ".$email, "message");}
			if($h_email !== (int)$userArray['h_email']){$dbcore->sec->logd("Update profile h_email for ".$username."(".$user_id.") from ".$userArray['h_email']." to ".$h_email, "message");}
			if($website !== $userArray['website']){$dbcore->sec->logd("Update profile website for ".$username."(".$user_id.") from ".$userArray['website']." to ".$website, "message");}
			if($Vis_ver !== $userArray['Vis_ver']){$dbcore->sec->logd("Update profile Vis_ver for ".$username."(".$user_id.") from ".$userArray['Vis_ver']." to ".$Vis_ver, "message");}
			if($apikey !== $userArray['apikey']){$dbcore->sec->logd("Update profile apikey for ".$username."(".$user_id.")", "message");}
			if($import_require_login !== (int)$userArray['import_require_login']){$dbcore->sec->logd("Update profile import_require_login for ".$username."(".$user_id.") from ".$userArray['import_require_login']." to ".$import_require_login, "message");}
			
			$cp_profile = array();
			if($userArray['id'])
			{
				$sql1 = "UPDATE user_info SET email = ?, h_email = ?, website = ?, Vis_ver = ?, apikey = ?, import_require_login = ? WHERE id = ?";
				$result = $dbcore->sql->conn->prepare($sql1);
				$result->bindParam(1, $email, PDO::PARAM_STR);
				$result->bindParam(2, $h_email, PDO::PARAM_STR);
				$result->bindParam(3, $website, PDO::PARAM_STR);
				$result->bindParam(4, $Vis_ver, PDO::PARAM_STR);
				$result->bindParam(5, $apikey, PDO::PARAM_STR);
				$result->bindParam(6, $import_require_login, PDO::PARAM_INT);
				$result->bindParam(7, $user_id, PDO::PARAM_INT);
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
			if($userArray['schedule']){$cp_profile['schedule'] = 'checked';}else{$cp_profile['schedule'] = 'unchecked';};
			if($userArray['imports']){$cp_profile['imports'] = 'checked';}else{$cp_profile['imports'] = 'unchecked';};
			if($userArray['kmz']){$cp_profile['kmz'] = 'checked';}else{$cp_profile['kmz'] = 'unchecked';};
			if($userArray['new_users']){$cp_profile['new_users'] = 'checked';}else{$cp_profile['new_users'] = 'unchecked';};

			$dbcore->smarty->assign('user_cp_profile', $cp_profile);
			$dbcore->smarty->display('user_cp_email_prefs.tpl');

		break;

		##-------------##
		case 'update_user_pref':
			$schedule = ((@$_POST['schedule']) == 'on' ? 1 : 0);
			$imports = ((@$_POST['imports']) == 'on' ? 1 : 0);
			$kmz = ((@$_POST['kmz']) == 'on' ? 1 : 0);
			$new_users = ((@$_POST['new_users']) == 'on' ? 1 : 0);
			
			if($schedule !== (int)$userArray['schedule']){$dbcore->sec->logd("Update email pref schedule for ".$username."(".$user_id.") from ".$userArray['schedule']." to ".$schedule, "message");}
			if($imports !== (int)$userArray['imports']){$dbcore->sec->logd("Update email pref imports for ".$username."(".$user_id.") from ".$userArray['imports']." to ".$imports, "message");}
			if($kmz !== (int)$userArray['kmz']){$dbcore->sec->logd("Update email pref kmz for ".$username."(".$user_id.") from ".$userArray['kmz']." to ".$kmz, "message");}
			if($new_users !== (int)$userArray['new_users']){$dbcore->sec->logd("Update email pref new_users for ".$username."(".$user_id.") from ".$userArray['new_users']." to ".$new_users, "message");}

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