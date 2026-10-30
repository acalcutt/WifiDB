<?php
/*
confirm.php, the check before preferences are saved, for a member who chose that under Preferences -> Security.
An authenticator or recovery code, or a passkey, and then the preferences open for PREFS_CONFIRM_MINUTES. This is its
own check: it does not turn on admin mode, and a code that signed the member in cannot be reused here.
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

$func = (string)filter_input(INPUT_GET, 'func', FILTER_SANITIZE_SPECIAL_CHARS);
$root_path = ($dbcore->root != '' ? '/'.trim($dbcore->root, '/') : '');
$return = wifidb_safe_return(isset($_REQUEST['return']) ? $_REQUEST['return'] : '', $dbcore->sec->domain, '');
if($return == '') { $return = $root_path.'/cp/index.php?func=profile'; }
$username = $dbcore->sec->LoginUser;
$tf = $username ? $dbcore->sec->TwoFactor() : null;
$session = $dbcore->sec->SessionID;
$post = ($_SERVER['REQUEST_METHOD'] === 'POST');

$json = function ($data, $status = 200) {
	http_response_code($status);
	header('Content-Type: application/json');
	header('Cache-Control: no-store');
	echo json_encode($data);
};

if(!$username || !$tf)
{
	if($func === 'passkey_begin' || $func === 'passkey_finish') { $json(array('ok' => false, 'error' => 'not_signed_in'), 403); exit; }
	header('Location: '.$root_path.'/login.php?return='.urlencode($root_path.'/cp/confirm.php?return='.urlencode($return)));
	exit;
}

try
{
	switch($func)
	{
		case 'proc':
			if(!$post) { header('Location: confirm.php?return='.urlencode($return)); exit; }
			$proof = $tf->ConfirmPreferencesWithCode($username, $session, isset($_POST['code']) ? $_POST['code'] : '');
			if($proof['ok']) { header('Location: '.$return); exit; }
			$dbcore->sec->logd("Preferences check failed for ".var_export($username, 1).": ".$proof['error'], "error");
			header('Location: confirm.php?e='.urlencode($proof['error']).'&return='.urlencode($return));
			exit;

		case 'passkey_begin':
		case 'passkey_finish':
			$isJson = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0;
			if(!$post || !$isJson) { $json(array('ok' => false, 'error' => 'bad_request'), 403); break; }
			if($func === 'passkey_begin') { $json(array('ok' => true) + $tf->StartPasskeyAssertion($username, $session)); break; }
			$in = json_decode(file_get_contents('php://input'), true);
			$in = is_array($in) ? $in : array();
			$proof = $tf->ConfirmPreferencesWithPasskey($username, $session, (string)@$in['id'], (string)@$in['clientDataJSON'], (string)@$in['authenticatorData'], (string)@$in['signature']);
			$json($proof['ok'] ? array('ok' => true, 'redirect' => $return) : array('ok' => false, 'error' => $proof['error']));
			break;

		default:
			if(!$tf->NeedsPreferencesCheck($username, $session)) { header('Location: '.$return); exit; }
			$hasPasskey = false;
			foreach($tf->Methods($username) as $method) { if($method['type'] === 'passkey') { $hasPasskey = true; } }
			$errors = array(
				'wrong_code' => 'That code did not work. Try the newest one from your app, or a recovery code.',
				'throttled'  => 'Too many wrong tries. Wait a few minutes and try again.',
			);
			$e = (string)filter_input(INPUT_GET, 'e', FILTER_SANITIZE_SPECIAL_CHARS);
			$dbcore->smarty->assign('wifidb_page_label', 'Confirm It Is You');
			$dbcore->smarty->assign('confirm_return', $return);
			$dbcore->smarty->assign('confirm_error', isset($errors[$e]) ? $errors[$e] : '');
			$dbcore->smarty->assign('confirm_has_passkey', $hasPasskey);
			$dbcore->smarty->display('user_cp_confirm.tpl');
			break;
	}
}
catch(Throwable $e)
{
	$dbcore->sec->logd("Preferences check error for ".$username.": ".$e->getMessage(), "error");
	if($func === 'passkey_begin' || $func === 'passkey_finish') { $json(array('ok' => false, 'error' => 'server'), 500); }
	else { header('Location: '.$return); }
}
