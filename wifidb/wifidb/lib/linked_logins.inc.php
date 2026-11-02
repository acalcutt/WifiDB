<?php
/*
linked_logins.inc.php, signing in with a linked Google, GitHub, Discord or Facebook account.

A member links an outside account to theirs under Preferences, Security (with their password); from then on the sign-in
page's "Sign in with Google" signs them in without their password. Linking is the only way an outside account reaches a
WifiDB account: nothing is matched by e-mail address (whoever controls an address would get the account). Signing in
with an outside account nobody linked offers to sign in with a password and link it, or to sign up (the sign-up form and
its checks), and the new account gets it. A member whose sign-in asks for a second factor is still asked for it.

Nothing is on until an administrator turns a provider on at admin/logins.php, with the site's own OAuth client ID and
secret from that provider (kept with the site settings, the secret encrypted).

The sign-in is OAuth 2's authorization code flow, done on the server: the provider sends the browser back to
logins.php?callback=<provider> with a code, which is exchanged for an access token (with the secret, and PKCE for
Google) that is used once to ask who signed in. The state sent along names a row in linked_login_states and must match a
cookie set in the same browser when the sign-in started, so nobody can make someone else's browser sign in to their
account. The rows are in the database, not in one node's memory, so any node behind the load balancer can finish a
sign-in another started; they're kept by the SHA-256 of their token, so reading the table doesn't give the tokens.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

/** Signing in with the provider failed: the message is for the member, the detail for the log. */
class LinkedLoginException extends Exception
{
	public $detail;

	public function __construct($message, $detail = '')
	{
		parent::__construct($message);
		$this->detail = $detail;
	}
}

class LinkedLogins
{
	const PROVIDERS = array(
		'google' => array(
			'name'      => 'Google',
			'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
			'token'     => 'https://oauth2.googleapis.com/token',
			'userinfo'  => 'https://openidconnect.googleapis.com/v1/userinfo',
			'scope'     => 'openid email profile',
			'pkce'      => true,
			'console'   => 'https://console.cloud.google.com/apis/credentials',
			'name_keys' => array('name'),   # what to show the member as the account's name, the first one there
		),
		# (GitHub's and Discord's say only who it is, not an e-mail address)
		'github' => array(
			'name'      => 'GitHub',
			'authorize' => 'https://github.com/login/oauth/authorize',
			'token'     => 'https://github.com/login/oauth/access_token',
			'userinfo'  => 'https://api.github.com/user',
			'scope'     => 'read:user',
			'pkce'      => false,
			'console'   => 'https://github.com/settings/developers',
			'name_keys' => array('login', 'name'),
		),
		'discord' => array(
			'name'      => 'Discord',
			'authorize' => 'https://discord.com/oauth2/authorize',
			'token'     => 'https://discord.com/api/oauth2/token',
			'userinfo'  => 'https://discord.com/api/users/@me',
			'scope'     => 'identify',
			'pkce'      => false,
			'console'   => 'https://discord.com/developers/applications',
			'name_keys' => array('username', 'global_name'),
		),
		'facebook' => array(
			'name'      => 'Facebook',
			'authorize' => 'https://www.facebook.com/dialog/oauth',
			'token'     => 'https://graph.facebook.com/oauth/access_token',
			'userinfo'  => 'https://graph.facebook.com/me?fields=id,name,email',
			'scope'     => 'email public_profile',
			'pkce'      => false,
			'console'   => 'https://developers.facebook.com/apps/',
			'name_keys' => array('name'),
		),
	);
	const STATE_COOKIE = 'WiFiDB_login_state';   # the sign-in under way in this browser
	const LINK_COOKIE  = 'WiFiDB_link_pending';  # an outside account nobody linked, signed in with in this browser
	const STATE_TTL = 600;                       # seconds to finish at the provider, and to link one nobody had
	const TIMEOUT = 20;

	private $conn;
	private $settings;
	private $siteUrl;
	private $clock;
	private $http;
	private $endpoints;

	/**
	 * @param PDO $conn
	 * @param SiteSettings $settings where admin/logins.php keeps the providers' IDs and secrets
	 * @param string $siteUrl the site's address ending in / (URL_PATH): the provider sends the browser back under it
	 * @param callable|null $clock returns the time as a Unix timestamp (for tests)
	 * @param callable|null $http function($method, $url, $headers, $body) returning array(status, body) (for tests)
	 * @param array $endpoints provider => its authorize, token and userinfo addresses, instead of the real ones (for tests)
	 */
	public function __construct($conn, $settings, $siteUrl, $clock = null, $http = null, $endpoints = array())
	{
		$this->conn = $conn;
		$this->settings = $settings;
		$this->siteUrl = rtrim((string)$siteUrl, '/').'/';
		$this->clock = $clock;
		$this->http = $http;
		$this->endpoints = is_array($endpoints) ? $endpoints : array();
	}

	private function Info($provider)
	{
		$info = self::PROVIDERS[$provider];
		if(isset($this->endpoints[$provider]) && is_array($this->endpoints[$provider]))
		{
			$info = array_merge($info, array_intersect_key($this->endpoints[$provider], array_flip(array('authorize', 'token', 'userinfo'))));
		}
		return $info;
	}

	public function Now() { return $this->clock ? call_user_func($this->clock) : time(); }
	private function Stamp($time = null) { return gmdate('Y-m-d H:i:s', $time === null ? $this->Now() : $time); }

	private function Run($sql, $params = array())
	{
		$prep = $this->conn->prepare($sql);
		$prep->execute($params);
		return $prep;
	}

	public static function Known($provider) { return is_string($provider) && isset(self::PROVIDERS[$provider]); }
	public static function Name($provider) { return self::Known($provider) ? self::PROVIDERS[$provider]['name'] : ''; }

	/** Has an administrator turned it on, with its ID and secret? */
	public function Enabled($provider)
	{
		if(!self::Known($provider)) { return false; }
		return $this->settings->Get('login_'.$provider.'_enabled') && $this->settings->Get('login_'.$provider.'_client_id') !== ''
			&& $this->settings->Get('login_'.$provider.'_secret') !== '';
	}

	/** @return string[] the providers members can sign in with, in the order they're shown */
	public function EnabledProviders()
	{
		return array_values(array_filter(array_keys(self::PROVIDERS), array($this, 'Enabled')));
	}

	/** Where the provider sends the browser back: the address to give it as the app's redirect URI. */
	public function CallbackUrl($provider)
	{
		return $this->siteUrl.'logins.php?callback='.rawurlencode($provider);
	}

	#---# The links

	/** @return array the member's linked accounts: provider, subject, email, display_name, created, last_used */
	public function LinksFor($username)
	{
		return $this->Run("SELECT provider, subject, email, display_name, created, last_used FROM user_linked_logins WHERE username = ? ORDER BY provider",
			array($username))->fetchAll(PDO::FETCH_ASSOC);
	}

	/** @return string|null the member this outside account is linked to */
	public function UsernameFor($provider, $subject)
	{
		$name = $this->Run("SELECT username FROM user_linked_logins WHERE provider = ? AND subject = ?", array($provider, (string)$subject))->fetchColumn();
		return $name === false ? null : (string)$name;
	}

	/**
	 * Links an outside account ($identity from Finish) to the member. Refused if it's linked to someone else, or the
	 * member already has one from that provider.
	 */
	public function Link($username, $provider, array $identity)
	{
		$name = self::Name($provider);
		$other = $this->UsernameFor($provider, $identity['subject']);
		if($other !== null && strcasecmp($other, $username) !== 0)
		{
			throw new LinkedLoginException("That $name account is already linked to another account here.");
		}
		if($other !== null) { return; }
		foreach($this->LinksFor($username) as $link)
		{
			if($link['provider'] === $provider) { throw new LinkedLoginException("Your account already has a $name account linked. Unlink it first."); }
		}
		$cut = function ($text) { return ($text === null || $text === '') ? null : mb_substr((string)$text, 0, 255); };
		try
		{
			$this->Run("INSERT INTO user_linked_logins (provider, subject, username, email, display_name, created) VALUES (?, ?, ?, ?, ?, ?)",
				array($provider, (string)$identity['subject'], $username, $cut(@$identity['email']), $cut(@$identity['name']), $this->Stamp()));
		}
		catch(PDOException $e)
		{
			# linked at the same moment from another browser
			throw new LinkedLoginException("That $name account is already linked.", $e->getMessage());
		}
	}

	/** @return bool whether there was one to unlink */
	public function Unlink($username, $provider)
	{
		return $this->Run("DELETE FROM user_linked_logins WHERE username = ? AND provider = ?", array($username, $provider))->rowCount() > 0;
	}

	public function Touch($provider, $subject)
	{
		$this->Run("UPDATE user_linked_logins SET last_used = ? WHERE provider = ? AND subject = ?", array($this->Stamp(), $provider, (string)$subject));
	}

	#---# The sign-ins under way (linked_login_states)

	private static function NewToken() { return rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '='); }
	private static function Key($token) { return hash('sha256', (string)$token); }

	private function Put(array $data, $ttl)
	{
		$this->Run("DELETE FROM linked_login_states WHERE expires < ?", array($this->Stamp()));
		$token = self::NewToken();
		$this->Run("INSERT INTO linked_login_states (token, data, expires) VALUES (?, ?, ?)",
			array(self::Key($token), json_encode($data), $this->Stamp($this->Now() + $ttl)));
		return $token;
	}

	/** @return array|null what was kept, if it's still there and not out of time */
	private function Fetch($token)
	{
		if(!is_string($token) || $token === '') { return null; }
		$row = $this->Run("SELECT data, expires FROM linked_login_states WHERE token = ?", array(self::Key($token)))->fetch(PDO::FETCH_ASSOC);
		if(!$row || strtotime($row['expires'].' UTC') < $this->Now()) { return null; }
		$data = json_decode($row['data'], true);
		return is_array($data) ? $data : null;
	}

	private function Drop($token)
	{
		if(is_string($token) && $token !== '') { $this->Run("DELETE FROM linked_login_states WHERE token = ?", array(self::Key($token))); }
	}

	/** The token and the cookie must be the same, from this browser. */
	private static function Same($token, $cookie)
	{
		return is_string($token) && is_string($cookie) && $token !== '' && hash_equals($cookie, $token);
	}

	#---# The OAuth flow

	/**
	 * Off to the provider. $purpose: 'login', or 'link' (for $username, signed in, who gave their password).
	 * @return array (the provider's sign-in address, the token for STATE_COOKIE)
	 */
	public function Start($provider, $purpose, $username = '', $return = '', $remember = false)
	{
		if(!$this->Enabled($provider)) { throw new LinkedLoginException("Signing in with that isn't available."); }
		$info = $this->Info($provider);
		$record = array('provider' => $provider, 'purpose' => $purpose, 'username' => (string)$username, 'return' => (string)$return,
			'remember' => (bool)$remember);
		if($info['pkce']) { $record['verifier'] = self::NewToken().self::NewToken(); }
		$token = $this->Put($record, self::STATE_TTL);
		$params = array(
			'client_id'     => $this->settings->Get('login_'.$provider.'_client_id'),
			'redirect_uri'  => $this->CallbackUrl($provider),
			'response_type' => 'code',
			'scope'         => $info['scope'],
			'state'         => $token,
		);
		if($info['pkce'])
		{
			$params['code_challenge'] = rtrim(strtr(base64_encode(hash('sha256', $record['verifier'], true)), '+/', '-_'), '=');
			$params['code_challenge_method'] = 'S256';
		}
		if($provider === 'google') { $params['prompt'] = 'select_account'; }
		return array($info['authorize'].'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986), $token);
	}

	/**
	 * The provider sent the browser back. $error is its error parameter (access_denied when the person said no).
	 * @return array (what Start kept: provider, purpose, username, return, remember; who signed in: subject, email, name)
	 */
	public function Finish($provider, $code, $state, $cookieState, $error = '')
	{
		if(!self::Same($state, $cookieState)) { throw new LinkedLoginException("That sign-in didn't start in this browser. Please start again."); }
		$record = $this->Fetch($state);
		$this->Drop($state);   # once only
		if(!$record || $record['provider'] !== $provider) { throw new LinkedLoginException('That sign-in took too long. Please start again.'); }
		if((string)$error !== '' || !is_string($code) || $code === '') { throw new LinkedLoginException('The sign-in was cancelled.', (string)$error); }
		if(!$this->Enabled($provider)) { throw new LinkedLoginException("Signing in with that isn't available."); }
		$info = $this->Info($provider);
		$name = $info['name'];
		$form = array(
			'client_id'     => $this->settings->Get('login_'.$provider.'_client_id'),
			'client_secret' => $this->settings->Get('login_'.$provider.'_secret'),
			'redirect_uri'  => $this->CallbackUrl($provider),
			'code'          => $code,
			'grant_type'    => 'authorization_code',
		);
		if($info['pkce']) { $form['code_verifier'] = (string)@$record['verifier']; }
		list($status, $body) = $this->Http('POST', $info['token'], array('Accept: application/json', 'Content-Type: application/x-www-form-urlencoded'),
			http_build_query($form, '', '&'), $name);
		$answer = json_decode($body, true);
		$access = (is_array($answer) && isset($answer['access_token']) && is_string($answer['access_token'])) ? $answer['access_token'] : '';
		if($status !== 200 || $access === '')
		{
			throw new LinkedLoginException("$name didn't accept the sign-in. Please try again.", "token exchange: HTTP $status ".substr((string)$body, 0, 200));
		}
		list($status, $body) = $this->Http('GET', $info['userinfo'], array('Accept: application/json', 'Authorization: Bearer '.$access), null, $name);
		$person = json_decode($body, true);
		if($status !== 200 || !is_array($person)) { throw new LinkedLoginException("$name didn't say who signed in. Please try again.", "who: HTTP $status"); }
		$subject = isset($person['sub']) ? $person['sub'] : (isset($person['id']) ? $person['id'] : null);
		if(!(is_string($subject) || is_int($subject)) || (string)$subject === '') { throw new LinkedLoginException("$name didn't say who signed in. Please try again."); }
		$shown = null;
		foreach($info['name_keys'] as $key)
		{
			if(isset($person[$key]) && is_scalar($person[$key]) && (string)$person[$key] !== '') { $shown = (string)$person[$key]; break; }
		}
		$email = (isset($person['email']) && is_string($person['email'])) ? $person['email'] : null;
		return array($record, array('subject' => (string)$subject, 'email' => $email, 'name' => $shown));
	}

	/**
	 * Signed in at the provider with an account nobody linked: kept for a few minutes, so the person can sign in with a
	 * password (or sign up) and link it in one go. @return string the token for LINK_COOKIE
	 */
	public function HoldUnlinked(array $record, $provider, array $identity)
	{
		return $this->Put(array('provider' => $provider, 'identity' => $identity, 'return' => (string)@$record['return'],
			'remember' => !empty($record['remember']), 'username' => ''), self::STATE_TTL);
	}

	/** @return array|null what HoldUnlinked kept (provider, identity, return, remember, username), from this browser only */
	public function Unlinked($token, $cookieToken)
	{
		return self::Same($token, $cookieToken) ? $this->Fetch($token) : null;
	}

	/**
	 * The password was right for $username, but its second factor comes before the link: kept, with who it is, for the
	 * time that takes (logins.php?func=finish_link links it after).
	 */
	public function UnlinkedProved($token, array $held, $username)
	{
		$held['username'] = (string)$username;
		$this->Run("UPDATE linked_login_states SET data = ?, expires = ? WHERE token = ?",
			array(json_encode($held), $this->Stamp($this->Now() + self::STATE_TTL), self::Key($token)));
	}

	public function UnlinkedDone($token) { $this->Drop($token); }

	#---#

	/** @return array (HTTP status, body) */
	private function Http($method, $url, array $headers, $body, $name)
	{
		$headers[] = 'User-Agent: WifiDB';   # GitHub's API refuses a request without one
		if($this->http) { return call_user_func($this->http, $method, $url, $headers, $body); }
		$ch = curl_init($url);
		curl_setopt_array($ch, array(CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false, CURLOPT_CONNECTTIMEOUT => self::TIMEOUT,
			CURLOPT_TIMEOUT => self::TIMEOUT, CURLOPT_HTTPHEADER => $headers));
		if($method === 'POST')
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, (string)$body);
		}
		$answer = curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
		$error = curl_error($ch);
		if($answer === false) { throw new LinkedLoginException("Couldn't reach $name. Please try again.", $error); }
		return array($status, (string)$answer);
	}
}
