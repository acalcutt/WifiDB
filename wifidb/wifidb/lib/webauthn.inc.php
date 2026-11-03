<?php
/*
webauthn.inc.php, passkeys (WebAuthn) for two-factor sign-in and admin mode: a small CBOR reader and the checks for
registering a passkey and for signing in with one. Standalone: no config or database needed.

What is checked, following the WebAuthn spec's steps for "none" attestation (we ask for none, so the authenticator's
make and model are not recorded or trusted; a passkey is just a key pair the user holds):
  - the client data says the right ceremony, comes from the site's own origin and has the challenge we issued
  - the authenticator data is for this site (the hash of the relying party ID), and the user was present
  - for a sign-in, the signature over the authenticator data and the client data checks against the stored public key,
    and the signature counter has moved on (a copied passkey shows up as a counter that doesn't)
Supports ES256 (P-256) and RS256 keys, which is what browsers and security keys make.

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class WebauthnException extends RuntimeException {}

class Cbor
{
	const MAX_DEPTH = 16;

	/** Decodes the CBOR value at the start of $data (or at $pos). Floats and indefinite lengths are not supported. */
	public static function Decode($data, &$pos = 0, $depth = 0)
	{
		if($depth > self::MAX_DEPTH) { throw new WebauthnException('CBOR nested too deeply'); }
		if($pos >= strlen($data)) { throw new WebauthnException('CBOR ended early'); }
		$initial = ord($data[$pos++]);
		$major = $initial >> 5;
		$info = $initial & 31;

		if($major === 7)
		{
			if($info === 20) { return false; }
			if($info === 21) { return true; }
			if($info === 22 || $info === 23) { return null; }
			throw new WebauthnException('CBOR simple value not supported');
		}
		$value = self::Argument($data, $pos, $info);

		switch($major)
		{
			case 0: return $value;
			case 1: return -1 - $value;
			case 2:
			case 3:
				if($value > strlen($data) - $pos) { throw new WebauthnException('CBOR string runs past the end'); }
				$out = substr($data, $pos, $value);
				$pos += $value;
				return $out;
			case 4:
				if($value > strlen($data) - $pos) { throw new WebauthnException('CBOR array too long'); }
				$out = array();
				for($i = 0; $i < $value; $i++) { $out[] = self::Decode($data, $pos, $depth + 1); }
				return $out;
			case 5:
				if($value > strlen($data) - $pos) { throw new WebauthnException('CBOR map too long'); }
				$out = array();
				for($i = 0; $i < $value; $i++)
				{
					$key = self::Decode($data, $pos, $depth + 1);
					if(!is_int($key) && !is_string($key)) { throw new WebauthnException('CBOR map key not supported'); }
					$out[$key] = self::Decode($data, $pos, $depth + 1);
				}
				return $out;
			case 6:
				return self::Decode($data, $pos, $depth + 1);   // a tag: the value inside is what matters here
		}
		throw new WebauthnException('CBOR type not supported');
	}

	private static function Argument($data, &$pos, $info)
	{
		if($info < 24) { return $info; }
		$bytes = array(24 => 1, 25 => 2, 26 => 4, 27 => 8);
		if(!isset($bytes[$info])) { throw new WebauthnException('CBOR length not supported'); }
		$n = $bytes[$info];
		if($pos + $n > strlen($data)) { throw new WebauthnException('CBOR ended early'); }
		$raw = substr($data, $pos, $n);
		$pos += $n;
		if($n === 1) { return ord($raw); }
		if($n === 2) { return unpack('n', $raw)[1]; }
		if($n === 4) { return unpack('N', $raw)[1]; }
		$value = unpack('J', $raw)[1];
		if($value < 0) { throw new WebauthnException('CBOR number too large'); }
		return $value;
	}
}

class Webauthn
{
	const FLAG_UP = 0x01;   // user present
	const FLAG_UV = 0x04;   // user verified (PIN, fingerprint, ...)
	const FLAG_AT = 0x40;   // attested credential data follows
	const ALG_ES256 = -7;
	const ALG_RS256 = -257;

	public static function Base64UrlEncode($binary)
	{
		return rtrim(strtr(base64_encode($binary), '+/', '-_'), '=');
	}

	public static function Base64UrlDecode($text)
	{
		$text = (string)$text;
		if(!preg_match('/^[A-Za-z0-9_-]*$/', $text)) { throw new WebauthnException('not base64url'); }
		$binary = base64_decode(strtr($text, '-_', '+/') . str_repeat('=', (4 - strlen($text) % 4) % 4), true);
		if($binary === false) { throw new WebauthnException('not base64url'); }
		return $binary;
	}

	/** A challenge to send to the browser (base64url), and keep to check what comes back */
	public static function NewChallenge()
	{
		return self::Base64UrlEncode(random_bytes(32));
	}

	/**
	 * Checks a new passkey.
	 * @return array credential_id (base64url), public_key (PEM), alg, sign_count, user_verified
	 * @throws WebauthnException
	 */
	public static function VerifyRegistration($clientDataB64, $attestationObjectB64, $challenge, $origin, $rpId)
	{
		self::CheckClientData(self::Base64UrlDecode($clientDataB64), 'webauthn.create', $challenge, $origin);

		$attestation = Cbor::Decode(self::Base64UrlDecode($attestationObjectB64));
		if(!is_array($attestation) || !isset($attestation['authData']) || !is_string($attestation['authData']))
		{
			throw new WebauthnException('no authenticator data');
		}
		$auth = self::ParseAuthData($attestation['authData'], $rpId);
		if(!($auth['flags'] & self::FLAG_AT) || $auth['rest'] === '') { throw new WebauthnException('no credential in the response'); }

		// attested credential data: AAGUID (16), credential id length (2), credential id, the public key (COSE)
		$rest = $auth['rest'];
		if(strlen($rest) < 18) { throw new WebauthnException('credential data too short'); }
		$idLength = unpack('n', substr($rest, 16, 2))[1];
		if($idLength < 1 || $idLength > 1023 || strlen($rest) < 18 + $idLength) { throw new WebauthnException('bad credential id'); }
		$credentialId = substr($rest, 18, $idLength);
		$pos = 18 + $idLength;
		$cose = Cbor::Decode($rest, $pos);
		if(!is_array($cose)) { throw new WebauthnException('bad public key'); }
		list($pem, $alg) = self::CoseToPem($cose);

		return array(
			'credential_id' => self::Base64UrlEncode($credentialId),
			'public_key'    => $pem,
			'alg'           => $alg,
			'sign_count'    => $auth['sign_count'],
			'user_verified' => (bool)($auth['flags'] & self::FLAG_UV),
		);
	}

	/**
	 * Checks a sign-in with a passkey.
	 * @param bool $requireUserVerification demand a PIN or biometric, not just a touch (admin mode asks for it)
	 * @return array sign_count (store it), user_verified
	 * @throws WebauthnException
	 */
	public static function VerifyAssertion($clientDataB64, $authenticatorDataB64, $signatureB64, $challenge, $origin, $rpId,
		$publicKeyPem, $alg, $storedSignCount, $requireUserVerification = false)
	{
		$clientData = self::Base64UrlDecode($clientDataB64);
		self::CheckClientData($clientData, 'webauthn.get', $challenge, $origin);

		$authData = self::Base64UrlDecode($authenticatorDataB64);
		$auth = self::ParseAuthData($authData, $rpId);
		if($requireUserVerification && !($auth['flags'] & self::FLAG_UV)) { throw new WebauthnException('the passkey did not verify the user'); }

		$signed = $authData . hash('sha256', $clientData, true);
		$ok = openssl_verify($signed, self::Base64UrlDecode($signatureB64), $publicKeyPem, OPENSSL_ALGO_SHA256);
		if($ok !== 1) { throw new WebauthnException('the signature does not match'); }

		// A counter that doesn't move on means the passkey may have been copied. Some authenticators always send 0.
		if(($auth['sign_count'] > 0 || $storedSignCount > 0) && $auth['sign_count'] <= $storedSignCount)
		{
			throw new WebauthnException('the passkey counter went backwards');
		}
		return array('sign_count' => $auth['sign_count'], 'user_verified' => (bool)($auth['flags'] & self::FLAG_UV));
	}

	private static function CheckClientData($json, $type, $challenge, $origin)
	{
		$data = json_decode($json, true);
		if(!is_array($data)) { throw new WebauthnException('bad client data'); }
		if(($data['type'] ?? null) !== $type) { throw new WebauthnException('wrong kind of request'); }
		if(!is_string($data['challenge'] ?? null) || !hash_equals((string)$challenge, $data['challenge'])) { throw new WebauthnException('wrong challenge'); }
		if(!is_string($data['origin'] ?? null) || !hash_equals($origin, $data['origin'])) { throw new WebauthnException('wrong origin'); }
	}

	private static function ParseAuthData($authData, $rpId)
	{
		if(strlen($authData) < 37) { throw new WebauthnException('authenticator data too short'); }
		if(!hash_equals(hash('sha256', $rpId, true), substr($authData, 0, 32))) { throw new WebauthnException('the passkey is for another site'); }
		$flags = ord($authData[32]);
		if(!($flags & self::FLAG_UP)) { throw new WebauthnException('the user was not present'); }
		return array('flags' => $flags, 'sign_count' => unpack('N', substr($authData, 33, 4))[1], 'rest' => substr($authData, 37));
	}

	/** @return array [PEM public key, COSE algorithm] */
	private static function CoseToPem($cose)
	{
		$kty = $cose[1] ?? null;
		$alg = $cose[3] ?? null;
		if($kty === 2 && $alg === self::ALG_ES256 && ($cose[-1] ?? null) === 1)
		{
			$x = $cose[-2] ?? ''; $y = $cose[-3] ?? '';
			if(strlen($x) !== 32 || strlen($y) !== 32) { throw new WebauthnException('bad P-256 key'); }
			$der = hex2bin('3059301306072a8648ce3d020106082a8648ce3d030107034200') . "\x04" . $x . $y;
			return array(self::Pem($der), self::ALG_ES256);
		}
		if($kty === 3 && $alg === self::ALG_RS256)
		{
			$n = $cose[-1] ?? ''; $e = $cose[-2] ?? '';
			if(strlen($n) < 128 || $e === '') { throw new WebauthnException('bad RSA key'); }
			$key = self::DerSequence(self::DerInteger($n) . self::DerInteger($e));
			$algorithm = self::DerSequence(hex2bin('06092a864886f70d010101') . "\x05\x00");
			$der = self::DerSequence($algorithm . "\x03" . self::DerLength(strlen($key) + 1) . "\x00" . $key);
			return array(self::Pem($der), self::ALG_RS256);
		}
		throw new WebauthnException('this kind of passkey is not supported');
	}

	private static function Pem($der)
	{
		return "-----BEGIN PUBLIC KEY-----\n" . chunk_split(base64_encode($der), 64, "\n") . "-----END PUBLIC KEY-----\n";
	}

	private static function DerLength($n)
	{
		if($n < 128) { return chr($n); }
		$bytes = ltrim(pack('N', $n), "\0");
		return chr(0x80 | strlen($bytes)) . $bytes;
	}

	private static function DerInteger($binary)
	{
		$binary = ltrim($binary, "\0");
		if($binary === '' || (ord($binary[0]) & 0x80)) { $binary = "\0" . $binary; }
		return "\x02" . self::DerLength(strlen($binary)) . $binary;
	}

	private static function DerSequence($content)
	{
		return "\x30" . self::DerLength(strlen($content)) . $content;
	}
}
