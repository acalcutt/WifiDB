<?php
/*
mailcheck.inc.php, checks the mail settings in config.inc.php and says in plain words what would go wrong: settings
still at their example values, a DKIM key that is missing or unreadable (PHPMailer then sends the mail UNSIGNED, silently),
a DKIM record that is not in DNS or does not match the key, and addresses that do not line up with the signing domain.
Standalone: DNS is looked up through a function the caller gives, so it can be tested without a network.

Each result is [status, text], status being "ok", "warn" or "fail".

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class MailCheck
{
	/** @return string|null the public key as the one-line base64 a DKIM record carries, or null if the private key can't be read */
	public static function PublicKeyFromPrivate($privatePem, $passphrase = '')
	{
		$key = @openssl_pkey_get_private((string)$privatePem, (string)$passphrase);
		if($key === false) { return null; }
		$details = openssl_pkey_get_details($key);
		if(!$details || empty($details['key'])) { return null; }
		return preg_replace('/\s+/', '', preg_replace('/-----[A-Z ]+-----/', '', $details['key']));
	}

	/** @return int|null the key's size in bits */
	public static function KeyBits($privatePem, $passphrase = '')
	{
		$key = @openssl_pkey_get_private((string)$privatePem, (string)$passphrase);
		if($key === false) { return null; }
		$details = openssl_pkey_get_details($key);
		return $details && isset($details['bits']) ? (int)$details['bits'] : null;
	}

	/** The text of the TXT record to publish at <selector>._domainkey.<domain> */
	public static function DnsRecord($privatePem, $passphrase = '')
	{
		$p = self::PublicKeyFromPrivate($privatePem, $passphrase);
		return $p === null ? null : 'v=DKIM1; k=rsa; p=' . $p;
	}

	/** @return array tag => value from "v=DKIM1; k=rsa; p=..." (whitespace inside the key removed) */
	public static function ParseRecord($txt)
	{
		$tags = array();
		foreach(explode(';', (string)$txt) as $part)
		{
			$part = trim($part);
			if($part === '' || strpos($part, '=') === false) { continue; }
			list($k, $v) = explode('=', $part, 2);
			$tags[strtolower(trim($k))] = preg_replace('/\s+/', '', $v);
		}
		return $tags;
	}

	/** The domain part of an address ("Name <a@b.c>" and "a@b.c" both give "b.c"), lowercased */
	public static function DomainOf($address)
	{
		if(!preg_match('/@([A-Za-z0-9._-]+)\s*>?\s*$/', (string)$address, $m)) { return ''; }
		return strtolower(rtrim($m[1], '.'));
	}

	/** Does $domain equal $signer, or sit under it (mail.example.com under example.com)? */
	public static function Aligned($domain, $signer)
	{
		$domain = strtolower($domain); $signer = strtolower($signer);
		return $domain !== '' && $signer !== '' && ($domain === $signer || substr($domain, -strlen($signer) - 1) === '.' . $signer);
	}

	private static function IsExampleValue($v)
	{
		return $v === '' || stripos((string)$v, 'someemail') !== false || stripos((string)$v, 'supersecretpw') !== false || stripos((string)$v, '.foo.lan') !== false;
	}

	/** @param callable $dns function ($name) returning the TXT strings at that name (an array, empty when there are none) */
	public static function Check(array $config, $dns)
	{
		$out = array();
		$add = function ($status, $text) use (&$out) { $out[] = array($status, $text); };
		$get = function ($k) use ($config) { return isset($config[$k]) ? $config[$k] : ''; };

		// ── sending ──
		foreach(array('smtp' => '', 'signup_smtp' => 'sign-up mail ') as $prefix => $label)
		{
			if($prefix === 'signup_smtp' && !isset($config['signup_smtp_host'])) { $add('ok', 'Sign-up mail uses the main SMTP account (no separate signup_smtp_* settings).'); continue; }
			foreach(array('host', 'user', 'pass', 'from') as $field)
			{
				if(self::IsExampleValue($get($prefix . '_' . $field)))
				{
					$add('fail', ucfirst($label) . $prefix . '_' . $field . ' is empty or still the example value, so ' . ($label ?: 'mail ') . 'cannot be sent.');
				}
			}
		}
		if(!empty($config['email_validation']) && (self::IsExampleValue($get('smtp_host')) || self::IsExampleValue($get('signup_smtp_host') ?: $get('smtp_host'))))
		{
			$add('fail', 'email_validation is on but mail cannot be sent, so nobody can finish signing up. Set up SMTP or turn email_validation off.');
		}

		// ── DKIM ──
		$domain = strtolower(trim((string)$get('DKIM_domain')));
		$selector = trim((string)$get('DKIM_selector'));
		$path = (string)$get('DKIM_private');
		if($domain === '' || $selector === '' || $path === '' || stripos($domain, 'someemail') !== false)
		{
			$add('warn', 'DKIM signing is off (DKIM_domain, DKIM_selector and DKIM_private are not all set). Mail is sent unsigned; most receivers still accept it, a few put it in spam.');
		}
		else
		{
			if($path[0] !== '/' && !preg_match('/^[A-Za-z]:[\\\\\/]/', $path))
			{
				$add('warn', "DKIM_private is the relative path \"$path\": which folder that means depends on what is running (the web server and the daemons differ). Use a full path.");
			}
			$pem = is_file($path) && is_readable($path) ? file_get_contents($path) : false;
			if($pem === false)
			{
				$add('fail', "The DKIM key file \"$path\" is missing or not readable by this user. PHPMailer does not complain: it sends the mail UNSIGNED.");
			}
			else
			{
				$bits = self::KeyBits($pem, (string)$get('DKIM_passphrase'));
				$publicKey = self::PublicKeyFromPrivate($pem, (string)$get('DKIM_passphrase'));
				if($publicKey === null)
				{
					$add('fail', 'The DKIM key file is not a valid private key (or DKIM_passphrase is wrong).');
				}
				else
				{
					$add($bits >= 2048 ? 'ok' : ($bits >= 1024 ? 'warn' : 'fail'), "The DKIM key is readable, $bits bits" . ($bits >= 2048 ? '.' : ($bits >= 1024 ? ': 1024-bit keys are weak, use 2048.' : ': too short, receivers will reject it.')));
					$name = $selector . '._domainkey.' . $domain;
					$records = array_filter(array_map(function ($t) { return self::ParseRecord($t); }, (array)call_user_func($dns, $name)), function ($r) { return isset($r['p']) || (isset($r['v']) && stripos($r['v'], 'DKIM') === 0); });
					if(!$records)
					{
						$add('fail', "No DKIM record at $name. Receivers cannot check the signature, so it counts as failed. Publish this TXT record:\n    " . self::DnsRecord($pem, (string)$get('DKIM_passphrase')));
					}
					else
					{
						$match = false; $revoked = false; $testing = false;
						foreach($records as $r)
						{
							if(isset($r['p']) && $r['p'] === '') { $revoked = true; }
							if(isset($r['p']) && $r['p'] === $publicKey) { $match = true; $testing = isset($r['t']) && strpos($r['t'], 'y') !== false; }
						}
						if($match)
						{
							$add('ok', "The DKIM record at $name matches the key.");
							if($testing) { $add('warn', 'The record has t=y (testing): receivers are told to ignore failures. Remove it once mail verifies.'); }
						}
						elseif($revoked) { $add('fail', "The DKIM record at $name has an empty p= (the key is revoked)."); }
						else { $add('fail', "The DKIM record at $name is for a different key than the file. Publish this:\n    " . self::DnsRecord($pem, (string)$get('DKIM_passphrase'))); }
					}
				}
			}
			$identityDomain = self::DomainOf($get('DKIM_identity'));
			if($identityDomain !== '' && !self::Aligned($identityDomain, $domain))
			{
				$add('fail', "DKIM_identity ($identityDomain) is not under DKIM_domain ($domain): the signature is invalid.");
			}
			foreach(array('smtp_from' => 'smtp_from', 'signup_smtp_from' => 'signup_smtp_from') as $key => $label)
			{
				$fromDomain = self::DomainOf($get($key));
				if($fromDomain !== '' && !self::Aligned($fromDomain, $domain))
				{
					$add('warn', "$label ($fromDomain) is not under the DKIM domain ($domain), so the signature will not count toward DMARC for that mail.");
				}
			}
		}

		// ── the domain's other mail records ──
		$senderDomain = self::DomainOf($get('smtp_from'));
		if($senderDomain !== '' && !self::IsExampleValue($senderDomain))
		{
			$hasSpf = false;
			foreach((array)call_user_func($dns, $senderDomain) as $t) { if(stripos($t, 'v=spf1') === 0) { $hasSpf = true; } }
			$add($hasSpf ? 'ok' : 'warn', $hasSpf ? "$senderDomain publishes an SPF record." : "$senderDomain has no SPF record: receivers cannot tell which servers may send for it.");
			$hasDmarc = false;
			foreach((array)call_user_func($dns, '_dmarc.' . $senderDomain) as $t) { if(stripos($t, 'v=DMARC1') === 0) { $hasDmarc = true; } }
			$add($hasDmarc ? 'ok' : 'warn', $hasDmarc ? "$senderDomain publishes a DMARC policy." : "$senderDomain has no DMARC record (_dmarc.$senderDomain): add one, even just \"v=DMARC1; p=none\".");
		}
		return $out;
	}
}
