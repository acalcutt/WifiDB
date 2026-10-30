<?php
/*
admin_mode.php, turning admin mode on (and off) for an Administrator's session.
Admin mode is what the admin buttons and pages need. It asks for its own authenticator or recovery code, or a passkey,
every time: a code that signed the member in, or confirmed their preferences, cannot be reused here, and "remember this
computer" does not apply. It ends after TwoFactor::ADMIN_MODE_MINUTES without an admin page, or when the member leaves it.
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
if($return == '') { $return = $root_path.'/opt/scheduling.php'; }
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

if(!$username)
{
	if($func === 'passkey_begin' || $func === 'passkey_finish') { $json(array('ok' => false, 'error' => 'not_signed_in'), 403); exit; }
	header('Location: '.$root_path.'/login.php?return='.urlencode($root_path.'/cp/admin_mode.php?return='.urlencode($return)));
	exit;
}
if(!$dbcore->sec->IsAdministrator())
{
	if($func === 'passkey_begin' || $func === 'passkey_finish') { $json(array('ok' => false, 'error' => 'forbidden'), 403); exit; }
	header("HTTP/1.1 403 Forbidden");
	die("Access denied. Administrator privileges required.");
}

try
{
	switch($func)
	{
		case 'off':
			if($tf) { $tf->DisableAdminMode($username, $session); }
			header('Location: '.$root_path.'/');
			exit;

		case 'proc':
			if(!$post || !$tf) { header('Location: admin_mode.php?return='.urlencode($return)); exit; }
			$proof = $tf->StepUpWithCode($username, $session, isset($_POST['code']) ? $_POST['code'] : '');
			if($proof['ok'])
			{
				$dbcore->sec->logd("Admin mode on for ".$username, "Info");
				header('Location: '.$return);
				exit;
			}
			$dbcore->sec->logd("Admin mode refused for ".var_export($username, 1).": ".$proof['error'], "error");
			header('Location: admin_mode.php?e='.urlencode($proof['error']).'&return='.urlencode($return));
			exit;

		case 'passkey_begin':
		case 'passkey_finish':
			$isJson = isset($_SERVER['CONTENT_TYPE']) && stripos($_SERVER['CONTENT_TYPE'], 'application/json') === 0;
			if(!$post || !$isJson || !$tf) { $json(array('ok' => false, 'error' => 'bad_request'), 403); break; }
			if($func === 'passkey_begin') { $json(array('ok' => true) + $tf->StartPasskeyAssertion($username, $session)); break; }
			$in = json_decode(file_get_contents('php://input'), true);
			$in = is_array($in) ? $in : array();
			$proof = $tf->StepUpWithPasskey($username, $session, (string)@$in['id'], (string)@$in['clientDataJSON'], (string)@$in['authenticatorData'], (string)@$in['signature']);
			if($proof['ok']) { $dbcore->sec->logd("Admin mode on for ".$username." (passkey)", "Info"); }
			$json($proof['ok'] ? array('ok' => true, 'redirect' => $return) : array('ok' => false, 'error' => $proof['error']));
			break;

		default:
			$hasTwoFactor = $tf && $tf->HasTwoFactor($username);
			if($hasTwoFactor && $dbcore->sec->AdminModeActive(true)) { header('Location: '.$return); exit; }
			$hasPasskey = false;
			if($hasTwoFactor)
			{
				foreach($tf->Methods($username) as $method) { if($method['type'] === 'passkey') { $hasPasskey = true; } }
			}
			$errors = array(
				'wrong_code' => 'That code did not work. Wait for the next one from your app, or use a recovery code. The code that signed you in cannot be used again.',
				'throttled'  => 'Too many wrong tries. Wait a few minutes and try again.',
			);
			$e = (string)filter_input(INPUT_GET, 'e', FILTER_SANITIZE_SPECIAL_CHARS);
			$dbcore->smarty->assign('wifidb_page_label', 'Admin Mode');
			$dbcore->smarty->assign('admin_return', $return);
			$dbcore->smarty->assign('admin_error', isset($errors[$e]) ? $errors[$e] : '');
			$dbcore->smarty->assign('admin_has_two_factor', $hasTwoFactor);
			$dbcore->smarty->assign('admin_has_passkey', $hasPasskey);
			$dbcore->smarty->assign('admin_minutes', $tf ? TwoFactor::ADMIN_MODE_MINUTES : 30);
			$dbcore->smarty->display('admin_mode.tpl');
			break;
	}
}
catch(Throwable $e)
{
	$dbcore->sec->logd("Admin mode error for ".$username.": ".$e->getMessage(), "error");
	if($func === 'passkey_begin' || $func === 'passkey_finish') { $json(array('ok' => false, 'error' => 'server'), 500); }
	else { header('Location: '.$root_path.'/'); }
}
