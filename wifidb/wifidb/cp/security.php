<?php
/*
security.php, Preferences -> Security: the member's second factors (authenticator apps and passkeys), recovery codes,
and whether sign-in asks for one. See lib/two_factor.inc.php for what each of those means.

Changes are POSTs (the cookie is SameSite=Lax). Taking a method away, turning the sign-in question off and making new
recovery codes ask for the account password again, so a borrowed, signed-in browser can't undo the protection.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

global $switches;
$switches = array('screen'=>"HTML",'extras'=>'');
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "cp");

include('../lib/init.inc.php');
$dbcore->smarty->assign('wifidb_page_label', 'Security');
$func = (string)filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
$username = $dbcore->sec->LoginUser;

if(!$username)
{
	$dbcore->redirect_page("/wifidb/login.php?return=".urlencode("/wifidb/cp/".basename($_SERVER['REQUEST_URI'])), 1000);
	$dbcore->smarty->assign('user_cp_profile', array('message' => "<br>You must be logged in to change your security settings. Redirecting to login page.<br><br>"));
	$dbcore->smarty->display('user_cp_msg.tpl');
	exit;
}

$tf = $dbcore->sec->TwoFactor();
if(!$tf)
{
	$dbcore->smarty->assign('user_cp_profile', array('message' => "<br>Two-factor sign-in is not available yet.<br><br>"));
	$dbcore->smarty->display('user_cp_msg.tpl');
	exit;
}
$session = $dbcore->sec->SessionID;
$post = ($_SERVER['REQUEST_METHOD'] === 'POST');
$self = 'security.php';

$say = function ($text) use ($dbcore) {
	$dbcore->smarty->assign('user_cp_profile', array('message' => '<br>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'<br><br><a href="security.php">Back to security settings</a><br><br>'));
	$dbcore->smarty->display('user_cp_msg.tpl');
};

$passwordOk = function () use ($dbcore, $username) {
	$prep = $dbcore->sql->conn->prepare("SELECT password FROM user_info WHERE username = ?");
	$prep->execute(array($username));
	$hash = (string)$prep->fetchColumn();
	$given = isset($_POST['password']) ? (string)$_POST['password'] : '';
	return $given !== '' && $hash !== '' && password_verify($given, $hash);
};

// Tells the member, in the site, that their sign-in protection changed: if it wasn't them they will see it.
$tell = function ($title, $body) use ($dbcore, $username) {
	$dbcore->wdbmail->notify('security', $title, $body, $dbcore->URL_PATH.'cp/security.php', '', array($username));
};

$json = function ($data, $status = 200) {
	http_response_code($status);
	header('Content-Type: application/json');
	header('Cache-Control: no-store');
	echo json_encode($data);
};
$isJsonPost = $post && isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0;

$showCodes = function ($codes, $headline) use ($dbcore) {
	$dbcore->smarty->assign('wifidb_page_label', 'Recovery Codes');
	$dbcore->smarty->assign('recovery_codes', $codes);
	$dbcore->smarty->assign('recovery_headline', $headline);
	$dbcore->smarty->display('user_cp_security_codes.tpl');
};

try
{
	switch($func)
	{
		case 'totp_add':
			$start = $tf->StartTotp($username, $session);
			$dbcore->smarty->assign('wifidb_page_label', 'Add an Authenticator App');
			$dbcore->smarty->assign('totp_secret', trim(chunk_split($start['secret'], 4, ' ')));
			$dbcore->smarty->assign('totp_uri', $start['uri']);
			$dbcore->smarty->assign('totp_error', (string)filter_input(INPUT_GET, 'e', FILTER_SANITIZE_SPECIAL_CHARS) !== '');
			$dbcore->smarty->display('user_cp_security_totp.tpl');
			break;

		case 'totp_finish':
			if(!$post) { header("Location: $self"); exit; }
			$result = $tf->FinishTotp($username, $session, isset($_POST['name']) ? $_POST['name'] : '', isset($_POST['code']) ? $_POST['code'] : '');
			if(!$result['ok'])
			{
				if($result['error'] === 'wrong_code') { header("Location: $self?func=totp_add&e=1"); exit; }
				$say($result['error'] === 'too_many' ? 'You have as many authenticator apps as are allowed.' : 'That took too long. Start again.');
				break;
			}
			$tf->MfaDone($username, $session);   // this session has just proved it; it must not be asked again
			$tell('An authenticator app was added', 'An authenticator app was added to your account. If this was not you, change your password.');
			if(!empty($result['recovery_codes'])) { $showCodes($result['recovery_codes'], 'Authenticator app added. Two-factor sign-in is on.'); }
			else { $say('Authenticator app added.'); }
			break;

		case 'passkey_begin':
			if(!$isJsonPost) { $json(array('ok' => false, 'error' => 'bad_request'), 403); break; }
			$begin = $tf->StartPasskeyRegistration($username, $session);
			$begin['user'] = array('id' => Webauthn::Base64UrlEncode($username), 'name' => $username);
			$begin['rp_name'] = 'WifiDB';
			$json(array('ok' => true) + $begin);
			break;

		case 'passkey_finish':
			if(!$isJsonPost) { $json(array('ok' => false, 'error' => 'bad_request'), 403); break; }
			$in = json_decode(file_get_contents('php://input'), true);
			$in = is_array($in) ? $in : array();
			$result = $tf->FinishPasskeyRegistration($username, $session, (string)@$in['name'], (string)@$in['clientDataJSON'], (string)@$in['attestationObject']);
			if($result['ok'])
			{
				$tf->MfaDone($username, $session);
				$tell('A passkey was added', 'A passkey was added to your account. If this was not you, change your password.');
			}
			$json($result);
			break;

		case 'remove':
			if(!$post) { header("Location: $self"); exit; }
			if(!$passwordOk()) { $say('The password was not right, so nothing was removed.'); break; }
			if($tf->RemoveMethod($username, (int)@$_POST['id']))
			{
				$tell('A sign-in method was removed', 'An authenticator app or passkey was removed from your account. If this was not you, change your password.');
				$say('Removed.'.($tf->HasTwoFactor($username) ? '' : ' That was your last one, so two-factor sign-in is now off.'));
			}
			else { $say('That method was not found.'); }
			break;

		case 'settings':
			if(!$post) { header("Location: $self"); exit; }
			if(!$tf->HasTwoFactor($username)) { $say('Add an authenticator app or a passkey first.'); break; }
			$atLogin = isset($_POST['mfa_login']);
			$onPrefs = isset($_POST['mfa_prefs']);
			if(!$atLogin && $tf->AsksAtLogin($username) && !$passwordOk())
			{
				$say('Turning off the question at sign-in needs your password, and it was not right.');
				break;
			}
			$tf->SetAsks($username, $atLogin, $onPrefs);
			if(!$atLogin) { $tell('Two-factor sign-in was turned off', 'Signing in no longer asks for a second factor on your account. If this was not you, change your password.'); }
			$say('Saved.');
			break;

		case 'codes':
			if(!$post) { header("Location: $self"); exit; }
			if(!$tf->HasTwoFactor($username)) { $say('Add an authenticator app or a passkey first.'); break; }
			if(!$passwordOk()) { $say('The password was not right, so no new codes were made.'); break; }
			$tell('New recovery codes were made', 'New recovery codes were made for your account; the old ones no longer work. If this was not you, change your password.');
			$showCodes($tf->NewRecoveryCodes($username), 'New recovery codes. The old ones no longer work.');
			break;

		default:
			$dbcore->smarty->assign('security_methods', $tf->Methods($username));
			$dbcore->smarty->assign('security_has', $tf->HasTwoFactor($username));
			$dbcore->smarty->assign('security_asks_login', $tf->AsksAtLogin($username));
			$dbcore->smarty->assign('security_asks_prefs', $tf->AsksOnPreferences($username));
			$dbcore->smarty->assign('security_codes_left', $tf->RecoveryCodesLeft($username));
			$dbcore->smarty->display('user_cp_security.tpl');
			break;
	}
}
catch(Throwable $e)
{
	$dbcore->sec->logd("Security settings error for ".$username.": ".$e->getMessage(), "error");
	if($func === 'passkey_begin' || $func === 'passkey_finish') { $json(array('ok' => false, 'error' => 'server'), 500); }
	else { $say('Something went wrong. If you have not run the database migrations yet, run tools/utilites/migrate.php.'); }
}
