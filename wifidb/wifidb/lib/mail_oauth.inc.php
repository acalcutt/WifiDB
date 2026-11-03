<?php
/*
mail_oauth.inc.php, signing in to Gmail's or Google Workspace's mail server with a Google account (OAuth 2.0, XOAUTH2)
instead of a password. The Mail page (admin/mail.php) connects the account once, through the site's own OAuth client,
and keeps the refresh token Google gives (encrypted, with the site settings, so every node has it); wdbmail::Mailer
gives PHPMailer a MailOAuth, which trades that token for an access token when PHPMailer signs in.

Google takes the token back if the account's password changes, if it goes six months unused, or if someone removes the
site's access from the account; then sending fails, the log and the Mail page's check say so, and the account has to be
connected again.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/
use PHPMailer\PHPMailer\OAuthTokenProvider;

class MailOAuth implements OAuthTokenProvider
{
	/** Google's; mail_oauth_endpoints in config.inc.php points them somewhere else (tests) */
	const ENDPOINTS = array(
		'authorize' => 'https://accounts.google.com/o/oauth2/v2/auth',
		'token'     => 'https://oauth2.googleapis.com/token',
		'userinfo'  => 'https://openidconnect.googleapis.com/v1/userinfo',
		'revoke'    => 'https://oauth2.googleapis.com/revoke',
	);
	/** sending mail over SMTP needs all of Gmail (Google has no narrower scope for it); the rest says which account */
	const SCOPE = 'https://mail.google.com/ openid email';
	/** the connection started in this browser: its state, PKCE verifier and which account */
	const COOKIE = 'WiFiDB_mail_oauth';
	const TTL = 600;
	const TIMEOUT = 15;

	private $user;
	private $clientId;
	private $secret;
	private $refresh;
	private $endpoints;
	private $http;
	private $clock;
	private $access = '';
	private $expires = 0;
	private $error = '';

	/**
	 * @param string $user the Google account's address, which XOAUTH2 names
	 * @param callable|null $http function ($method, $url, $headers, $body) returning (status, body) (for tests)
	 * @param callable|null $clock returns the time as a Unix timestamp (for tests)
	 */
	public function __construct($user, $clientId, $secret, $refreshToken, array $endpoints = array(), $http = null, $clock = null)
	{
		$this->user = (string)$user;
		$this->clientId = (string)$clientId;
		$this->secret = (string)$secret;
		$this->refresh = (string)$refreshToken;
		$this->endpoints = $endpoints + self::ENDPOINTS;
		$this->http = $http;
		$this->clock = $clock;
	}

	/** What PHPMailer sends after AUTH XOAUTH2 */
	public function getOauth64()
	{
		return base64_encode('user='.$this->user."\001auth=Bearer ".$this->AccessToken()."\001\001");
	}

	/** Why the last access token couldn't be had, or '' */
	public function Error() { return $this->error; }

	public function User() { return $this->user; }

	/** An access token, kept until a minute before it runs out. @throws RuntimeException saying why there's none */
	public function AccessToken()
	{
		if($this->access !== '' && $this->Now() < $this->expires - 60) { return $this->access; }
		$this->error = '';
		if($this->refresh === '' || $this->clientId === '' || $this->secret === '')
		{
			$this->error = 'No Google account is connected for '.$this->user.' (or the OAuth client ID or secret is missing): connect one on the Mail page.';
			throw new RuntimeException($this->error);
		}
		try
		{
			list($status, $body) = self::Http($this->http, 'POST', $this->endpoints['token'],
				http_build_query(array('grant_type' => 'refresh_token', 'refresh_token' => $this->refresh,
					'client_id' => $this->clientId, 'client_secret' => $this->secret), '', '&'));
		}
		catch(RuntimeException $e)
		{
			$this->error = "Couldn't reach Google for ".$this->user."'s access token: ".$e->getMessage();
			throw new RuntimeException($this->error);
		}
		$answer = json_decode($body, true);
		if($status !== 200 || !is_array($answer) || empty($answer['access_token']) || !is_string($answer['access_token']))
		{
			$code = (is_array($answer) && isset($answer['error']) && is_string($answer['error'])) ? $answer['error'] : "HTTP $status";
			$said = (is_array($answer) && isset($answer['error_description']) && is_string($answer['error_description'])) ? ' ('.$answer['error_description'].')' : '';
			$this->error = "Google gave no access token for ".$this->user.": $code$said"
				.($code === 'invalid_grant' ? '. The connection was taken back (a changed password, six months unused, or access removed): connect the account again on the Mail page.' : '.');
			throw new RuntimeException($this->error);
		}
		$this->access = $answer['access_token'];
		$this->expires = $this->Now() + (isset($answer['expires_in']) ? (int)$answer['expires_in'] : 3600);
		return $this->access;
	}

	/**
	 * Off to Google to connect an account. $account: 'main' or 'signup'; $hint: the address to suggest.
	 * @return array (Google's address to send the browser to, the value for COOKIE)
	 */
	public static function Start($clientId, $redirect, $account, $hint = '', array $endpoints = array(), $now = null)
	{
		$endpoints += self::ENDPOINTS;
		$state = bin2hex(random_bytes(16));
		$verifier = rtrim(strtr(base64_encode(random_bytes(48)), '+/', '-_'), '=');
		$params = array(
			'client_id'             => $clientId,
			'redirect_uri'          => $redirect,
			'response_type'         => 'code',
			'scope'                 => self::SCOPE,
			'state'                 => $state,
			'code_challenge'        => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
			'code_challenge_method' => 'S256',
			'access_type'           => 'offline',   # a refresh token, to send mail later
			'prompt'                => 'consent',   # ...every time, not only the first
		);
		if($hint !== '') { $params['login_hint'] = $hint; }
		$cookie = base64_encode(json_encode(array('state' => $state, 'verifier' => $verifier, 'account' => $account,
			'expires' => ($now === null ? time() : $now) + self::TTL)));
		return array($endpoints['authorize'].'?'.http_build_query($params, '', '&', PHP_QUERY_RFC3986), $cookie);
	}

	/**
	 * Google sent the browser back. $error is its error parameter (access_denied when the person said no).
	 * @return array (the account: 'main' or 'signup', the refresh token, the Google account's address)
	 * @throws RuntimeException saying what went wrong
	 */
	public static function Finish($clientId, $secret, $redirect, $code, $state, $cookie, $error = '', array $endpoints = array(), $http = null, $now = null)
	{
		$endpoints += self::ENDPOINTS;
		$kept = json_decode((string)base64_decode((string)$cookie, true), true);
		if(!is_array($kept) || !isset($kept['state'], $kept['verifier'], $kept['account'], $kept['expires']) || !is_string($state)
			|| !hash_equals((string)$kept['state'], $state))
		{
			throw new RuntimeException("That didn't start in this browser. Please connect again.");
		}
		if(($now === null ? time() : $now) > (int)$kept['expires']) { throw new RuntimeException('That took too long. Please connect again.'); }
		if((string)$error !== '' || !is_string($code) || $code === '') { throw new RuntimeException('Google said no'.((string)$error !== '' ? " ($error)" : '').'.'); }

		list($status, $body) = self::Http($http, 'POST', $endpoints['token'], http_build_query(array('grant_type' => 'authorization_code',
			'code' => $code, 'client_id' => $clientId, 'client_secret' => $secret, 'redirect_uri' => $redirect,
			'code_verifier' => $kept['verifier']), '', '&'));
		$answer = json_decode($body, true);
		if($status !== 200 || !is_array($answer) || empty($answer['access_token']))
		{
			throw new RuntimeException("Google didn't accept the connection: ".((is_array($answer) && isset($answer['error'])) ? (string)$answer['error'] : "HTTP $status").'.');
		}
		if(empty($answer['refresh_token']) || !is_string($answer['refresh_token']))
		{
			throw new RuntimeException("Google gave no refresh token, so mail couldn't be sent later. Please connect again.");
		}
		list($status, $body) = self::Http($http, 'GET', $endpoints['userinfo'], null, array('Authorization: Bearer '.$answer['access_token']));
		$who = json_decode($body, true);
		if($status !== 200 || !is_array($who) || empty($who['email']) || !is_string($who['email']))
		{
			throw new RuntimeException("Google didn't say which account was connected. Please connect again.");
		}
		return array($kept['account'] === 'signup' ? 'signup' : 'main', $answer['refresh_token'], $who['email']);
	}

	/** Tells Google the site no longer needs the token. Best effort: it's forgotten here either way. */
	public static function Revoke($token, array $endpoints = array(), $http = null)
	{
		$endpoints += self::ENDPOINTS;
		try { self::Http($http, 'POST', $endpoints['revoke'], http_build_query(array('token' => (string)$token), '', '&')); }
		catch(RuntimeException $e) {}
	}

	private function Now() { return $this->clock ? (int)call_user_func($this->clock) : time(); }

	/** @return array (HTTP status, body) @throws RuntimeException when Google can't be reached */
	private static function Http($http, $method, $url, $body, array $headers = array())
	{
		$headers[] = 'Accept: application/json';
		if($method === 'POST') { $headers[] = 'Content-Type: application/x-www-form-urlencoded'; }
		if($http) { return call_user_func($http, $method, $url, $headers, $body); }
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
		if($answer === false) { throw new RuntimeException($error); }
		return array($status, (string)$answer);
	}
}
