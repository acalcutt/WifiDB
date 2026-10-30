<?php
/*
totp.inc.php, time-based one-time passwords (RFC 6238) for two-factor sign-in, with the helpers around them:
base32 (what authenticator apps use for the secret), recovery codes, and encrypting secrets stored in the database.
Standalone: no config or database needed.

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class Totp
{
	const PERIOD = 30;
	const DIGITS = 6;
	const SECRET_BYTES = 20;      // 160 bits, what the RFC recommends for SHA-1
	const RECOVERY_CODES = 10;

	const BASE32 = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

	public static function Base32Encode($binary)
	{
		$bits = '';
		foreach (str_split($binary) as $char) { $bits .= str_pad(decbin(ord($char)), 8, '0', STR_PAD_LEFT); }
		$out = '';
		foreach (str_split($bits, 5) as $chunk) { $out .= self::BASE32[bindec(str_pad($chunk, 5, '0', STR_PAD_RIGHT))]; }
		return $out;
	}

	/** @return string|false the bytes, or false if $text isn't base32 */
	public static function Base32Decode($text)
	{
		$text = strtoupper(str_replace(array(' ', '-', '='), '', (string)$text));
		if($text === '' || strspn($text, self::BASE32) !== strlen($text)) { return false; }
		$bits = '';
		foreach (str_split($text) as $char) { $bits .= str_pad(decbin(strpos(self::BASE32, $char)), 5, '0', STR_PAD_LEFT); }
		$out = '';
		foreach (str_split($bits, 8) as $byte) { if(strlen($byte) === 8) { $out .= chr(bindec($byte)); } }
		return $out;
	}

	/** A new secret, base32 */
	public static function GenerateSecret()
	{
		return self::Base32Encode(random_bytes(self::SECRET_BYTES));
	}

	/** The code for a base32 secret at a time step (a step is PERIOD seconds since the epoch) */
	public static function Code($secret, $step, $digits = self::DIGITS)
	{
		$key = self::Base32Decode($secret);
		if($key === false) { return false; }
		$hash = hash_hmac('sha1', pack('N*', 0, $step), $key, true);
		$offset = ord($hash[19]) & 0x0f;
		$value = ((ord($hash[$offset]) & 0x7f) << 24) | (ord($hash[$offset + 1]) << 16) | (ord($hash[$offset + 2]) << 8) | ord($hash[$offset + 3]);
		return str_pad((string)($value % (10 ** $digits)), $digits, '0', STR_PAD_LEFT);
	}

	public static function Step($time = null)
	{
		return intdiv($time === null ? time() : $time, self::PERIOD);
	}

	/**
	 * Checks a code, allowing $window steps either side for clock drift.
	 * $lastStep is the last step already accepted for this secret: codes at or before it are refused, so a code
	 * can't be used twice.
	 * @return int|false the step the code matched (store it as the new last step), or false
	 */
	public static function Verify($secret, $code, $lastStep = 0, $window = 1, $time = null)
	{
		$code = preg_replace('/\s+/', '', (string)$code);
		if(!preg_match('/^\d{' . self::DIGITS . '}$/', $code)) { return false; }
		$now = self::Step($time);
		for($i = -$window; $i <= $window; $i++)
		{
			$step = $now + $i;
			if($step <= $lastStep) { continue; }
			$expected = self::Code($secret, $step);
			if($expected !== false && hash_equals($expected, $code)) { return $step; }
		}
		return false;
	}

	/** The otpauth:// address an authenticator app takes (as a QR code, or typed in) */
	public static function Uri($issuer, $account, $secret)
	{
		return 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . $secret
			. '&issuer=' . rawurlencode($issuer) . '&algorithm=SHA1&digits=' . self::DIGITS . '&period=' . self::PERIOD;
	}

	// ── Recovery codes ───────────────────────────────────────────────────────────────────────────────

	/** @return string[] RECOVERY_CODES codes like "k3f9-x2mq-7d4p" (shown once; only hashes are stored) */
	public static function GenerateRecoveryCodes($count = self::RECOVERY_CODES)
	{
		$alphabet = 'abcdefghjkmnpqrstuvwxyz23456789';
		$codes = array();
		for($i = 0; $i < $count; $i++)
		{
			$parts = array();
			for($p = 0; $p < 3; $p++)
			{
				$part = '';
				for($c = 0; $c < 4; $c++) { $part .= $alphabet[random_int(0, strlen($alphabet) - 1)]; }
				$parts[] = $part;
			}
			$codes[] = implode('-', $parts);
		}
		return $codes;
	}

	public static function NormalizeRecoveryCode($code)
	{
		return strtolower(preg_replace('/[^a-z0-9]/i', '', (string)$code));
	}

	public static function HashRecoveryCode($code)
	{
		return password_hash(self::NormalizeRecoveryCode($code), PASSWORD_BCRYPT, array('cost' => 10));
	}

	public static function LooksLikeRecoveryCode($code)
	{
		return strlen(self::NormalizeRecoveryCode($code)) === 12;
	}

	// ── Storing secrets ──────────────────────────────────────────────────────────────────────────────

	/**
	 * Encrypts a secret for the database with the site's key ($config['two_factor_key'], base64 of 32 bytes).
	 * Without a key the secret is stored as it is, which a stolen copy of the database could use: the Proxmox template
	 * makes a key at first boot.
	 */
	public static function Protect($secret, $base64Key)
	{
		$key = self::Key($base64Key);
		if($key === null) { return $secret; }
		$nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		return 'enc:' . base64_encode($nonce . sodium_crypto_secretbox($secret, $nonce, $key));
	}

	/** @return string|false the secret, or false if it can't be read (wrong key, damaged) */
	public static function Unprotect($stored, $base64Key)
	{
		if(strncmp($stored, 'enc:', 4) !== 0) { return $stored; }
		$key = self::Key($base64Key);
		$raw = base64_decode(substr($stored, 4), true);
		if($key === null || $raw === false || strlen($raw) <= SODIUM_CRYPTO_SECRETBOX_NONCEBYTES) { return false; }
		$nonce = substr($raw, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
		return sodium_crypto_secretbox_open(substr($raw, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES), $nonce, $key);
	}

	public static function GenerateKey()
	{
		return base64_encode(random_bytes(SODIUM_CRYPTO_SECRETBOX_KEYBYTES));
	}

	private static function Key($base64Key)
	{
		if(!is_string($base64Key) || $base64Key === '') { return null; }
		$key = base64_decode($base64Key, true);
		return ($key !== false && strlen($key) === SODIUM_CRYPTO_SECRETBOX_KEYBYTES) ? $key : null;
	}
}
