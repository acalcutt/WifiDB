<?php
/*
cleantalk.inc.php, asks CleanTalk (cleantalk.org) whether a sign-up or a private message is spam. Used only for those two:
not for uploaded files, and not CleanTalk's whole-site firewall, which could block Vistumbler and other API clients.

It fails open: if CleanTalk can't be reached in a few seconds, answers with an error, or the key's subscription has run
out, the sign-up or message goes through (and 'checked' says it wasn't). The site never stops working because CleanTalk
is down. Turned on, with the key, on admin/settings.php.
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class CleanTalk
{
	const SERVER = 'https://moderate.cleantalk.org/api2.0';
	const API = 'https://api.cleantalk.org/';
	const TIMEOUT = 5;          // seconds; a sign-up waits this long at most
	const AGENT = 'php-wifidb-1.0';

	private $key;
	private $transport;
	private $server;
	private $api;

	/**
	 * @param string $key the access key
	 * @param callable|null $transport function ($url, $jsonBody or null for a GET, $timeout): the response body, or false
	 * @param string|null $server the check server, $api the account API (other addresses are for tests)
	 */
	public function __construct($key, $transport = null, $server = null, $api = null)
	{
		$this->key = (string)$key;
		$this->transport = $transport ? $transport : array($this, 'Http');
		$this->server = $server ? $server : self::SERVER;
		$this->api = $api ? $api : self::API;
	}

	/**
	 * A new account. $jsOn: the form's script ran (people's browsers run it, most spam bots don't). $submitTime: seconds
	 * between showing the form and sending it, or null if not known.
	 * @return array allow (bool), checked (bool: false if CleanTalk didn't answer and it was let through), reason
	 */
	public function CheckNewUser($email, $nickname, $ip, $jsOn, $submitTime = null, $userAgent = '')
	{
		return $this->Check('check_newuser', array(
			'sender_email'    => (string)$email,
			'sender_nickname' => (string)$nickname,
		), $ip, $jsOn, $submitTime, $userAgent);
	}

	/** A private message from a member. Same answer as CheckNewUser. */
	public function CheckMessage($message, $email, $nickname, $ip, $jsOn, $submitTime = null, $userAgent = '')
	{
		return $this->Check('check_message', array(
			'message'         => (string)$message,
			'sender_email'    => (string)$email,
			'sender_nickname' => (string)$nickname,
		), $ip, $jsOn, $submitTime, $userAgent);
	}

	private function Check($method, $fields, $ip, $jsOn, $submitTime, $userAgent)
	{
		$request = array(
			'method_name' => $method,
			'auth_key'    => $this->key,
			'agent'       => self::AGENT,
			'sender_ip'   => (string)$ip,
			'js_on'       => $jsOn ? 1 : 0,
			'sender_info' => json_encode(array('USER_AGENT' => (string)$userAgent)),
		) + $fields;
		if($submitTime !== null) { $request['submit_time'] = max(0, (int)$submitTime); }

		$body = call_user_func($this->transport, $this->server, json_encode($request), self::TIMEOUT);
		$answer = is_string($body) ? json_decode($body, true) : null;
		if(!is_array($answer) || !isset($answer['allow']))
		{
			return array('allow' => true, 'checked' => false, 'reason' => 'CleanTalk did not answer');
		}
		if(!empty($answer['errno']) || !empty($answer['inactive']))
		{
			$why = !empty($answer['errstr']) ? $answer['errstr'] : (!empty($answer['comment']) ? $answer['comment'] : 'the key is not active');
			return array('allow' => true, 'checked' => false, 'reason' => 'CleanTalk: '.self::Clean($why));
		}
		return array('allow' => (int)$answer['allow'] === 1, 'checked' => true, 'reason' => self::Clean(isset($answer['comment']) ? $answer['comment'] : ''));
	}

	/** Is the key one CleanTalk accepts? @return string 'ok', 'invalid' or 'unreachable' */
	public function CheckKey()
	{
		if($this->key === '') { return 'invalid'; }
		$url = $this->api.'?method_name=notice_paid_till&auth_key='.rawurlencode($this->key);
		$body = call_user_func($this->transport, $url, null, self::TIMEOUT);
		$answer = is_string($body) ? json_decode($body, true) : null;
		if(!is_array($answer)) { return 'unreachable'; }
		return (isset($answer['data']['valid']) && (int)$answer['data']['valid'] === 1) ? 'ok' : 'invalid';
	}

	/** CleanTalk's comments come wrapped in "*** ... ***"; this is shown to the person who was refused. */
	private static function Clean($text)
	{
		return trim(strip_tags((string)$text), " *\t\r\n");
	}

	/** The default transport: a POST of JSON (or a GET), with a hard time limit. */
	public function Http($url, $jsonBody, $timeout)
	{
		if(!function_exists('curl_init')) { return false; }
		$ch = curl_init($url);
		curl_setopt_array($ch, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_CONNECTTIMEOUT => min(3, $timeout),
			CURLOPT_TIMEOUT        => $timeout,
			CURLOPT_FOLLOWLOCATION => false,
		));
		if($jsonBody !== null)
		{
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonBody);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
		}
		$body = curl_exec($ch);
		$status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		return ($body === false || $status < 200 || $status >= 300) ? false : $body;
	}
}
