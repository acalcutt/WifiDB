<?php
/*
logins.php, signing in with a linked Google, GitHub, Discord or Facebook account (lib/linked_logins.inc.php):
  ?func=start&provider=google   off to the provider (the sign-in page's buttons)
  ?callback=google              where the provider sends the browser back
  ?func=link_existing (POST)    signs in with a password to link the outside account just used, which nobody had linked
  ?func=finish_link             ...and links it once that sign-in's second factor is done
  ?func=cancel                  forgets that outside account
Linking one from Preferences, Security starts in cp/security.php, as it needs the password.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');
header('Cache-Control: no-store');   # these pages carry a sign-in's own token
$dbcore->smarty->assign('wifidb_page_label', 'Sign In');
$sec = $dbcore->sec;
$root_path = ($dbcore->root != '' ? '/'.trim($dbcore->root, '/') : '');
$login_page = $root_path.'/login.php';

$get = function ($name) { return (isset($_GET[$name]) && is_string($_GET[$name])) ? $_GET[$name] : ''; };
$posted = function ($name) { return (isset($_POST[$name]) && is_string($_POST[$name])) ? $_POST[$name] : ''; };
$cookie = function ($name) { return (isset($_COOKIE[$name]) && is_string($_COOKIE[$name])) ? $_COOKIE[$name] : ''; };
$back = function ($return) use ($sec, $root_path) { return wifidb_safe_return($return, $sec->domain, $root_path.'/'); };

$say = function ($text, $link = '', $linkText = '') use ($dbcore) {
	$message = '<h2>'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</h2>';
	if($link !== '') { $message .= '<a class="links" href="'.htmlspecialchars($link, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($linkText, ENT_QUOTES, 'UTF-8').'</a>'; }
	$dbcore->smarty->assign('message', $message);
	$dbcore->smarty->display('login_result.tpl');
	exit;
};

// Signed in at the provider with an outside account nobody linked: sign in with a password to link it, or sign up.
$showUnlinked = function ($provider, array $identity, $token, $wrong) use ($dbcore, $sec) {
	$who = $identity['name'] !== null ? $identity['name'] : (string)$identity['email'];
	if($identity['name'] !== null && $identity['email'] !== null) { $who .= ' ('.$identity['email'].')'; }
	$dbcore->smarty->assign('wifidb_page_label', 'Link Your Account');
	$dbcore->smarty->assign('linked_provider', LinkedLogins::Name($provider));
	$dbcore->smarty->assign('linked_who', $who);
	$dbcore->smarty->assign('linked_token', $token);
	$dbcore->smarty->assign('linked_wrong', $wrong);
	$dbcore->smarty->assign('wifidb_signups_open', $sec->Setting('signups_open'));
	$dbcore->smarty->display('login_linked.tpl');
	exit;
};

// After a sign-in (with the outside account, or the password that links it): the second factor if the member asks for
// one, else back where they were going.
$signedIn = function ($return) use ($sec, $back, $say, $login_page) {
	switch($sec->login_val)
	{
		case 'good':
			header('Location: '.$back($return));
			exit;
		case 'mfa_required':
			header('Location: '.$login_page.'?func=mfa&return='.urlencode($back($return)));
			exit;
		case 'NotValidated':
			$say('This account has not been confirmed yet. Use the link in the e-mail it was sent, then sign in.');
		case 'locked':
			$say("This account is locked. Contact this WiFiDB's administrators.");
		default:
			$say('Signing in did not work. Please try again.', $login_page, 'Sign in');
	}
};

$logins = $sec->LinkedLogins();
$tellLinked = function ($username, $provider) use ($dbcore, $sec) {
	$name = LinkedLogins::Name($provider);
	$sec->logd("A ".$name." account was linked to ".var_export($username, 1), "message");
	$sec->Tell($username, "A $name account was linked", "A $name account was linked to yours, and can be used to sign in. If this was not you, unlink it under Preferences, Security and change your password.",
		$dbcore->URL_PATH.'cp/security.php');
};

$callback = $get('callback');
$func = ($callback !== '') ? 'callback' : $get('func');
try
{
	switch($func)
	{
		case 'start':
			$return = $back($get('return'));
			if($sec->login_check) { header('Location: '.$return); exit; }
			list($url, $token) = $logins->Start($get('provider'), 'login', '', $return, $get('remember') === '1');
			$sec->LinkedLoginCookie(LinkedLogins::STATE_COOKIE, $token, LinkedLogins::STATE_TTL);
			header('Location: '.$url);
			exit;

		case 'callback':
			if(!LinkedLogins::Known($callback)) { http_response_code(404); $say('There is no such sign-in.'); }
			$name = LinkedLogins::Name($callback);
			$state = $cookie(LinkedLogins::STATE_COOKIE);
			$sec->LinkedLoginCookie(LinkedLogins::STATE_COOKIE, '');
			list($record, $identity) = $logins->Finish($callback, $get('code'), $get('state'), $state, $get('error'));

			if($record['purpose'] === 'link')
			{
				# from Preferences, Security, by the member who gave their password there
				if(!$sec->login_check || strcasecmp($sec->LoginUser, $record['username']) !== 0)
				{
					$say('Sign in to the account you want to link it to, then try again.', $login_page.'?return='.urlencode($root_path.'/cp/security.php'), 'Sign in');
				}
				$logins->Link($sec->LoginUser, $callback, $identity);
				$tellLinked($sec->LoginUser, $callback);
				header('Location: '.$root_path.'/cp/security.php?linked='.urlencode($callback));
				exit;
			}

			$username = $logins->UsernameFor($callback, $identity['subject']);
			if($username === null)
			{
				$token = $logins->HoldUnlinked($record, $callback, $identity);
				$sec->LinkedLoginCookie(LinkedLogins::LINK_COOKIE, $token, LinkedLogins::STATE_TTL);
				$showUnlinked($callback, $identity, $token, false);
			}
			$sec->LoginLinked($username, $record['remember'] ? 1 : 0, "Linked $name account");
			if($sec->login_val === 'good' || $sec->login_val === 'mfa_required')
			{
				$logins->Touch($callback, $identity['subject']);
				require_once 'lib/traffic.inc.php';
				Traffic::Note($config, 'login');   # admin/traffic.php
			}
			$signedIn($record['return']);
			break;

		case 'link_existing':
			if($_SERVER['REQUEST_METHOD'] !== 'POST') { header('Location: '.$login_page); exit; }
			$token = $posted('token');
			$held = $logins->Unlinked($token, $cookie(LinkedLogins::LINK_COOKIE));
			if(!$held || $held['username'] !== '') { $say('That sign-in took too long. Please start again.', $login_page, 'Sign in'); }
			# the sign-in form's own checks: a wrong password counts towards locking the account
			$sec->Login($posted('time_user'), $posted('time_pass'), $held['remember'] ? 1 : 0);
			if($sec->login_val === 'p_fail' || $sec->login_val === 'u_fail') { $showUnlinked($held['provider'], $held['identity'], $token, true); }
			if($sec->login_val === 'mfa_required')
			{
				# linked once the second factor is done too
				$logins->UnlinkedProved($token, $held, $sec->username);
				header('Location: '.$login_page.'?func=mfa&return='.urlencode($root_path.'/logins.php?func=finish_link'));
				exit;
			}
			if($sec->login_val !== 'good') { $signedIn($held['return']); }
			$logins->UnlinkedDone($token);
			$sec->LinkedLoginCookie(LinkedLogins::LINK_COOKIE, '');
			$logins->Link($sec->username, $held['provider'], $held['identity']);
			$tellLinked($sec->username, $held['provider']);
			$signedIn($held['return']);
			break;

		case 'finish_link':
			$token = $cookie(LinkedLogins::LINK_COOKIE);
			$held = $logins->Unlinked($token, $token);
			if(!$held || $held['username'] === '' || !$sec->login_check || strcasecmp($sec->LoginUser, $held['username']) !== 0)
			{
				$say('That sign-in took too long. Please start again.', $login_page, 'Sign in');
			}
			$logins->UnlinkedDone($token);
			$sec->LinkedLoginCookie(LinkedLogins::LINK_COOKIE, '');
			$logins->Link($sec->LoginUser, $held['provider'], $held['identity']);
			$tellLinked($sec->LoginUser, $held['provider']);
			header('Location: '.$back($held['return']));
			exit;

		case 'cancel':
			$logins->UnlinkedDone($cookie(LinkedLogins::LINK_COOKIE));
			$sec->LinkedLoginCookie(LinkedLogins::LINK_COOKIE, '');
			header('Location: '.$login_page);
			exit;

		default:
			header('Location: '.$login_page);
			exit;
	}
}
catch(LinkedLoginException $e)
{
	$sec->logd("Linked sign-in failed: ".$e->getMessage().($e->detail !== '' ? ' ('.$e->detail.')' : ''), "error");
	$say($e->getMessage(), $sec->login_check ? $root_path.'/cp/security.php' : $login_page, $sec->login_check ? 'Back to security settings' : 'Sign in');
}
catch(Throwable $e)
{
	$sec->logd("Linked sign-in error: ".$e->getMessage(), "error");
	$say('Something went wrong. Please try again.', $login_page, 'Sign in');
}
