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
	else if($dbcore->sql->service == "pgsql")
		{$sql0 = "SELECT * FROM user_info WHERE username = ? LIMIT 1";}
	$result = $dbcore->sql->conn->prepare($sql0);
	$result->bindParam(1, $username, PDO::PARAM_STR);
	$result->execute();
	$userArray = $result->fetch();
	$user_id = $userArray['id'];

	// (pref and update_user_pref: Email Preferences' old addresses, still in sent e-mails' unsubscribe links)
	if($func === 'pref' || $func === 'update_user_pref') { $func = 'notifications'; }
	// A member who chose to be asked before using preferences confirms first (cp/confirm.php), then they stay open.
	if(in_array($func, array('profile', 'notifications', 'update_user_profile', 'new_apikey'), true))
	{
		$dbcore->sec->RequirePreferencesConfirmation($func === 'notifications' ? 'cp/index.php?func=notifications' : 'cp/index.php?func=profile');
	}

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
			$import_require_login = ((@$_POST['import_require_login']) == 'on' ? 1 : 0);
			
			if($email !== $userArray['email']){$dbcore->sec->logd("Update profile email for ".$username."(".$user_id.") from ".$userArray['email']." to ".$email, "message");}
			if($h_email !== (int)$userArray['h_email']){$dbcore->sec->logd("Update profile h_email for ".$username."(".$user_id.") from ".$userArray['h_email']." to ".$h_email, "message");}
			if($website !== $userArray['website']){$dbcore->sec->logd("Update profile website for ".$username."(".$user_id.") from ".$userArray['website']." to ".$website, "message");}
			if($Vis_ver !== $userArray['Vis_ver']){$dbcore->sec->logd("Update profile Vis_ver for ".$username."(".$user_id.") from ".$userArray['Vis_ver']." to ".$Vis_ver, "message");}
			if($import_require_login !== (int)$userArray['import_require_login']){$dbcore->sec->logd("Update profile import_require_login for ".$username."(".$user_id.") from ".$userArray['import_require_login']." to ".$import_require_login, "message");}
			
			$cp_profile = array();
			if($userArray['id'])
			{
				// (not the API key: that is only ever made new, below)
				$sql1 = "UPDATE user_info SET email = ?, h_email = ?, website = ?, ".$dbcore->sql->ident('Vis_ver')." = ?, import_require_login = ? WHERE id = ?";
				$result = $dbcore->sql->conn->prepare($sql1);
				$result->bindParam(1, $email, PDO::PARAM_STR);
				$result->bindParam(2, $h_email, PDO::PARAM_STR);
				$result->bindParam(3, $website, PDO::PARAM_STR);
				$result->bindParam(4, $Vis_ver, PDO::PARAM_STR);
				$result->bindParam(5, $import_require_login, PDO::PARAM_INT);
				$result->bindParam(6, $user_id, PDO::PARAM_INT);
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
		case 'new_apikey':
			// A new API key, and the old one stops working at once (a POST: the cookie is SameSite=Lax, so another site
			// can't send one). Vistumbler and the app need the new one.
			if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: index.php?func=profile'); exit; }
			$new_key = $dbcore->sec->GenerateKey(64);
			$result = $dbcore->sql->conn->prepare("UPDATE user_info SET apikey = ? WHERE id = ?");
			$ok = $result->execute(array($new_key, $user_id));
			if($ok)
			{
				$dbcore->sec->logd("New API key for ".$username."(".$user_id.")", "message");
				$dbcore->sec->Tell($username, 'Your API key was replaced', 'A new API key was made from your profile; the old one no longer works. If that was not you, change your password.', $dbcore->URL_PATH.'cp/index.php?func=profile');
			}
			$dbcore->redirect_page('/wifidb/cp/index.php?func=profile', 2000);
			$dbcore->smarty->assign('user_cp_profile', array('message' => $ok
				? "<br>Made a new API key. The old one no longer works: give Vistumbler and the app the new one.<br><br>"
				: "<br>The API key could not be changed. Try again.<br><br>"));
			$dbcore->smarty->display('user_cp_msg.tpl');
		break;

		##-------------##
		case 'notifications':
			// Each kind: an alert in the Inbox (on unless switched off), and e-mail (off unless chosen). lib/notify.inc.php
			require_once dirname(__FILE__).'/../lib/notify.inc.php';
			$saved = false;
			try
			{
				$notifier = new Notifier($dbcore->sql->conn);
				if($_SERVER['REQUEST_METHOD'] === 'POST')
				{
					foreach($notifier->Prefs($username) as $type => $info)
					{
						if($info['inbox_optional']) { $notifier->SetPref($username, $type, isset($_POST['inbox'][$type])); }
						if($info['emailable']) { $notifier->SetEmail($username, $type, isset($_POST['email'][$type])); }
					}
					$dbcore->sec->logd("Updated notification choices for ".$username."(".$user_id.")", "message");
					$saved = true;
				}
				$dbcore->smarty->assign('wifidb_page_label', 'Notifications');
				$dbcore->smarty->assign('notification_prefs', $notifier->Prefs($username));
				$dbcore->smarty->assign('notification_saved', $saved);
				$dbcore->smarty->display('user_cp_notification_settings.tpl');
			}
			catch(Throwable $e)
			{
				// most likely the migration that adds e-mail to notification_prefs hasn't run (tools/utilites/migrate.php)
				$dbcore->smarty->assign('user_cp_profile', array('message' => "<br>Notifications are not available yet.<br><br>"));
				$dbcore->smarty->display('user_cp_msg.tpl');
			}
		break;

		##-------------##
		case 'myimports':
			$sort = wifidb_input_string(INPUT_GET, 'sort');
			$ord = wifidb_input_string(INPUT_GET, 'ord');
			$from = filter_input(INPUT_GET, 'from', FILTER_SANITIZE_NUMBER_INT);
			$inc = filter_input(INPUT_GET, 'inc', FILTER_SANITIZE_NUMBER_INT);

			$sorts=array("id","file_orig","title","notes","aps","gps","NewAPPercent","date");
			if(!in_array($sort, $sorts)){$sort = "id";}
			$ords=array("ASC","DESC");
			if(!in_array($ord, $ords)){$ord = "DESC";}
			if(!is_numeric($from)){$from = 0;}
			if(!is_numeric($inc)){$inc = 100;}

			$dbcore->UsersLists($username, $sort, $ord, $from, $inc);

			// Re-generate pagination with correct func for control panel
			$total_files = $dbcore->user_all_imports_data['total_files'];
			$dbcore->GeneratePages($total_files, $from, $inc, $sort, $ord, 'myimports&');

			$dbcore->smarty->assign('wifidb_user_details', $dbcore->user_all_imports_data);
			$dbcore->smarty->assign('pages_together', $dbcore->pages_together);
			$dbcore->smarty->assign('sort', $sort);
			$dbcore->smarty->assign('ord', $ord);
			$dbcore->smarty->display('user_cp_myimports.tpl');
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
