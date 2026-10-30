<?php
/*
two_factor.inc.php, two-factor sign-in and admin mode.

Methods: authenticator apps (TOTP) and passkeys (WebAuthn), up to MAX_METHODS of each, and ten one-use recovery codes.
Where a second factor is asked for:
  - signing in, if the member has turned that on (user_info.mfa_login; on when they add their first method)
  - saving preferences, if they chose that (user_info.mfa_prefs)
  - turning on admin mode, ALWAYS, and only for members with a method. This is its own check: a code entered to sign
    in or in preferences never counts, and "remember this computer" doesn't apply. A code can't be used twice, so
    the code that signed you in can't turn admin mode on a moment later either.

What a session knows is kept in its user_login_hashes row (on the server, so a cookie can't claim it):
mfa_pending (password was right, the second factor isn't done: the session grants nothing), mfa_at (when it was
last done), admin_until (admin mode, until), and challenge (what a passkey or a new authenticator is being checked
against).

Uses plain SQL that MySQL, SQL Server and SQLite all take. Needs totp.inc.php and webauthn.inc.php.

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.
*/

class TwoFactor
{
	const MAX_METHODS = 10;            // of each kind
	const MAX_TRIES = 10;              // wrong codes per member per TRY_WINDOW seconds
	const TRY_WINDOW = 600;
	const CHALLENGE_TTL = 600;         // seconds a passkey or new-authenticator step may take
	const DEVICE_DAYS = 30;            // "remember this computer"
	const MFA_REFRESH_DAYS = 30;       // a long-lived session asks again after this long
	const PREFS_CONFIRM_MINUTES = 10;  // after confirming, preferences can be saved for this long
	const ADMIN_MODE_MINUTES = 30;     // admin mode ends after this long without an admin page

	private $conn;
	private $key;
	private $issuer;
	private $clock;
	public $rpId = '';
	public $origin = '';

	/**
	 * @param PDO $conn
	 * @param array $config two_factor_key (base64, 32 bytes), two_factor_issuer, hosturl (the site's address, which
	 *                      is the passkeys' origin and relying-party ID)
	 * @param callable|null $clock returns the time as a Unix timestamp (for tests)
	 */
	public function __construct($conn, $config = array(), $clock = null)
	{
		$this->conn = $conn;
		$this->key = isset($config['two_factor_key']) ? (string)$config['two_factor_key'] : '';
		$this->issuer = !empty($config['two_factor_issuer']) ? $config['two_factor_issuer'] : 'WifiDB';
		$this->clock = $clock;
		$site = parse_url(isset($config['hosturl']) ? $config['hosturl'] : '');
		if(!empty($site['host']))
		{
			$this->rpId = $site['host'];
			$this->origin = (isset($site['scheme']) ? $site['scheme'] : 'https') . '://' . $site['host'] . (isset($site['port']) ? ':' . $site['port'] : '');
		}
	}

	public function Now() { return $this->clock ? call_user_func($this->clock) : time(); }
	private function Stamp($time = null) { return gmdate('Y-m-d H:i:s', $time === null ? $this->Now() : $time); }
	private function Parse($stamp) { return $stamp ? strtotime($stamp . ' UTC') : 0; }

	private function Run($sql, $params = array())
	{
		$prep = $this->conn->prepare($sql);
		$prep->execute($params);
		return $prep;
	}

	// ── Methods ──────────────────────────────────────────────────────────────────────────────────────

	/** @return array[] id, type, name, created, last_used */
	public function Methods($username)
	{
		return $this->Run("SELECT id, type, name, created, last_used FROM user_two_factor WHERE username = ? ORDER BY id", array($username))->fetchAll(PDO::FETCH_ASSOC);
	}

	public function HasTwoFactor($username)
	{
		return (int)$this->Run("SELECT COUNT(*) FROM user_two_factor WHERE username = ?", array($username))->fetchColumn() > 0;
	}

	private function CountMethods($username, $type)
	{
		return (int)$this->Run("SELECT COUNT(*) FROM user_two_factor WHERE username = ? AND type = ?", array($username, $type))->fetchColumn();
	}

	/** Takes a method away. @return bool false if it wasn't theirs */
	public function RemoveMethod($username, $methodId)
	{
		$gone = $this->Run("DELETE FROM user_two_factor WHERE id = ? AND username = ?", array((int)$methodId, $username))->rowCount() > 0;
		if($gone && !$this->HasTwoFactor($username)) { $this->ResetTwoFactor($username); }
		return $gone;
	}

	/** Turns two-factor off for a member entirely: the lockout recovery and "remove the last method" */
	public function ResetTwoFactor($username)
	{
		$this->Run("DELETE FROM user_two_factor WHERE username = ?", array($username));
		$this->Run("DELETE FROM user_recovery_codes WHERE username = ?", array($username));
		$this->Run("UPDATE user_login_hashes SET admin_until = NULL, mfa_pending = 0 WHERE username = ?", array($username));
	}

	// ── Per-member settings ──────────────────────────────────────────────────────────────────────────

	public function AsksAtLogin($username)
	{
		return $this->HasTwoFactor($username) && (int)$this->Run("SELECT mfa_login FROM user_info WHERE username = ?", array($username))->fetchColumn() === 1;
	}

	public function AsksOnPreferences($username)
	{
		return $this->HasTwoFactor($username) && (int)$this->Run("SELECT mfa_prefs FROM user_info WHERE username = ?", array($username))->fetchColumn() === 1;
	}

	public function SetAsks($username, $atLogin, $onPreferences)
	{
		$this->Run("UPDATE user_info SET mfa_login = ?, mfa_prefs = ? WHERE username = ?", array($atLogin ? 1 : 0, $onPreferences ? 1 : 0, $username));
	}

	// ── Challenges (kept on the session) ─────────────────────────────────────────────────────────────

	private function SetChallenge($username, $sessionHash, $value)
	{
		$this->Run("UPDATE user_login_hashes SET challenge = ?, challenge_expires = ? WHERE username = ? AND hash = ?",
			array($value, $this->Stamp($this->Now() + self::CHALLENGE_TTL), $username, $sessionHash));
	}

	/** @return string|null what was set, if it hasn't expired. It is cleared either way: a challenge is for one try. */
	private function TakeChallenge($username, $sessionHash)
	{
		$row = $this->Run("SELECT challenge, challenge_expires FROM user_login_hashes WHERE username = ? AND hash = ?", array($username, $sessionHash))->fetch(PDO::FETCH_ASSOC);
		$this->Run("UPDATE user_login_hashes SET challenge = NULL, challenge_expires = NULL WHERE username = ? AND hash = ?", array($username, $sessionHash));
		if(!$row || $row['challenge'] === null || $this->Parse($row['challenge_expires']) < $this->Now()) { return null; }
		return $row['challenge'];
	}

	// ── Adding an authenticator app ──────────────────────────────────────────────────────────────────

	/** @return array secret (to show, for typing in), uri (for the QR code) */
	public function StartTotp($username, $sessionHash)
	{
		$secret = Totp::GenerateSecret();
		$this->SetChallenge($username, $sessionHash, 'totp:' . $secret);
		return array('secret' => $secret, 'uri' => Totp::Uri($this->issuer, $username, $secret));
	}

	/** @return array ok, error, recovery_codes (shown once, when this is the member's first method) */
	public function FinishTotp($username, $sessionHash, $name, $code)
	{
		$challenge = $this->TakeChallenge($username, $sessionHash);
		if($challenge === null || strncmp($challenge, 'totp:', 5) !== 0) { return array('ok' => false, 'error' => 'expired'); }
		if($this->CountMethods($username, 'totp') >= self::MAX_METHODS) { return array('ok' => false, 'error' => 'too_many'); }
		$secret = substr($challenge, 5);
		$step = Totp::Verify($secret, $code, 0, 1, $this->Now());
		if($step === false) { return array('ok' => false, 'error' => 'wrong_code'); }
		return $this->AddMethod($username, 'totp', $name, Totp::Protect($secret, $this->key), null, null, 0, $step);
	}

	// ── Adding a passkey ─────────────────────────────────────────────────────────────────────────────

	/** @return array challenge (base64url), exclude (credential IDs they already have), rp_id, user */
	public function StartPasskeyRegistration($username, $sessionHash)
	{
		$challenge = Webauthn::NewChallenge();
		$this->SetChallenge($username, $sessionHash, 'pkreg:' . $challenge);
		return array('challenge' => $challenge, 'rp_id' => $this->rpId, 'exclude' => $this->CredentialIds($username));
	}

	public function FinishPasskeyRegistration($username, $sessionHash, $name, $clientDataB64, $attestationB64)
	{
		$challenge = $this->TakeChallenge($username, $sessionHash);
		if($challenge === null || strncmp($challenge, 'pkreg:', 6) !== 0) { return array('ok' => false, 'error' => 'expired'); }
		if($this->CountMethods($username, 'passkey') >= self::MAX_METHODS) { return array('ok' => false, 'error' => 'too_many'); }
		try
		{
			$key = Webauthn::VerifyRegistration($clientDataB64, $attestationB64, substr($challenge, 6), $this->origin, $this->rpId);
		}
		catch(WebauthnException $e)
		{
			return array('ok' => false, 'error' => 'passkey_rejected', 'detail' => $e->getMessage());
		}
		if((int)$this->Run("SELECT COUNT(*) FROM user_two_factor WHERE type = 'passkey' AND secret = ?", array($key['credential_id']))->fetchColumn() > 0)
		{
			return array('ok' => false, 'error' => 'already_added');
		}
		return $this->AddMethod($username, 'passkey', $name, $key['credential_id'], $key['public_key'], $key['alg'], $key['sign_count'], 0);
	}

	private function AddMethod($username, $type, $name, $secret, $publicKey, $alg, $signCount, $lastStep)
	{
		$name = trim(strip_tags((string)$name));
		if($name === '') { $name = $type === 'totp' ? 'Authenticator app' : 'Passkey'; }
		$first = !$this->HasTwoFactor($username);
		$this->Run("INSERT INTO user_two_factor (username, type, name, secret, public_key, alg, sign_count, last_step, created) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
			array($username, $type, mb_substr($name, 0, 100), $secret, $publicKey, $alg, (int)$signCount, (int)$lastStep, $this->Stamp()));
		$result = array('ok' => true);
		if($first)
		{
			$this->SetAsks($username, true, false);
			$result['recovery_codes'] = $this->NewRecoveryCodes($username);
		}
		return $result;
	}

	// ── Recovery codes ───────────────────────────────────────────────────────────────────────────────

	/** Replaces the member's recovery codes. @return string[] the new codes, to show once */
	public function NewRecoveryCodes($username)
	{
		$this->Run("DELETE FROM user_recovery_codes WHERE username = ?", array($username));
		$codes = Totp::GenerateRecoveryCodes();
		foreach($codes as $code)
		{
			$this->Run("INSERT INTO user_recovery_codes (username, code_hash, created) VALUES (?, ?, ?)", array($username, Totp::HashRecoveryCode($code), $this->Stamp()));
		}
		return $codes;
	}

	public function RecoveryCodesLeft($username)
	{
		return (int)$this->Run("SELECT COUNT(*) FROM user_recovery_codes WHERE username = ? AND used IS NULL", array($username))->fetchColumn();
	}

	// ── Confirming before preferences are saved ──────────────────────────────────────────────────────

	/** Does saving preferences need a second factor right now? Only for a member who chose that and has a method. */
	public function NeedsPreferencesCheck($username, $sessionHash)
	{
		if(!$this->AsksOnPreferences($username)) { return false; }
		$until = $this->Run("SELECT prefs_until FROM user_login_hashes WHERE username = ? AND hash = ?", array($username, $sessionHash))->fetchColumn();
		return !$until || $this->Parse($until) <= $this->Now();
	}

	/** A code typed for this: an authenticator or recovery code. Does not turn on admin mode. @return array ok, error */
	public function ConfirmPreferencesWithCode($username, $sessionHash, $code)
	{
		$proof = $this->VerifyCode($username, $code);
		return $proof['ok'] ? $this->SetPreferencesConfirmed($username, $sessionHash) : $proof;
	}

	public function ConfirmPreferencesWithPasskey($username, $sessionHash, $credentialId, $clientDataB64, $authenticatorDataB64, $signatureB64)
	{
		$proof = $this->VerifyPasskey($username, $sessionHash, $credentialId, $clientDataB64, $authenticatorDataB64, $signatureB64);
		return $proof['ok'] ? $this->SetPreferencesConfirmed($username, $sessionHash) : $proof;
	}

	private function SetPreferencesConfirmed($username, $sessionHash)
	{
		$changed = $this->Run("UPDATE user_login_hashes SET prefs_until = ? WHERE username = ? AND hash = ? AND mfa_pending = 0",
			array($this->Stamp($this->Now() + self::PREFS_CONFIRM_MINUTES * 60), $username, $sessionHash))->rowCount();
		return $changed > 0 ? array('ok' => true) : array('ok' => false, 'error' => 'no_session');
	}

	// ── Checking a code or a passkey ─────────────────────────────────────────────────────────────────

	private function Throttled($username)
	{
		$since = $this->Stamp($this->Now() - self::TRY_WINDOW);
		return (int)$this->Run("SELECT COUNT(*) FROM two_factor_attempts WHERE username = ? AND attempted > ?", array($username, $since))->fetchColumn() >= self::MAX_TRIES;
	}

	private function Fail($username, $error)
	{
		$this->Run("INSERT INTO two_factor_attempts (username, attempted) VALUES (?, ?)", array($username, $this->Stamp()));
		$this->Run("DELETE FROM two_factor_attempts WHERE attempted < ?", array($this->Stamp($this->Now() - 86400)));
		return array('ok' => false, 'error' => $error);
	}

	/**
	 * Checks an authenticator code or a recovery code.
	 * @return array ok, via ('totp' or 'recovery'), error ('throttled' or 'wrong_code')
	 */
	public function VerifyCode($username, $code)
	{
		if($this->Throttled($username)) { return array('ok' => false, 'error' => 'throttled'); }
		$code = trim((string)$code);

		if(Totp::LooksLikeRecoveryCode($code))
		{
			$normalized = Totp::NormalizeRecoveryCode($code);
			$rows = $this->Run("SELECT id, code_hash FROM user_recovery_codes WHERE username = ? AND used IS NULL", array($username))->fetchAll(PDO::FETCH_ASSOC);
			foreach($rows as $row)
			{
				if(password_verify($normalized, $row['code_hash']))
				{
					$this->Run("UPDATE user_recovery_codes SET used = ? WHERE id = ?", array($this->Stamp(), $row['id']));
					return array('ok' => true, 'via' => 'recovery');
				}
			}
			return $this->Fail($username, 'wrong_code');
		}

		$methods = $this->Run("SELECT id, secret, last_step FROM user_two_factor WHERE username = ? AND type = 'totp'", array($username))->fetchAll(PDO::FETCH_ASSOC);
		foreach($methods as $method)
		{
			$secret = Totp::Unprotect($method['secret'], $this->key);
			if($secret === false) { continue; }
			$step = Totp::Verify($secret, $code, (int)$method['last_step'], 1, $this->Now());
			if($step !== false)
			{
				$this->Run("UPDATE user_two_factor SET last_step = ?, last_used = ? WHERE id = ?", array($step, $this->Stamp(), $method['id']));
				return array('ok' => true, 'via' => 'totp');
			}
		}
		return $this->Fail($username, 'wrong_code');
	}

	/** The passkeys' IDs and the challenge to send the browser for a sign-in with one */
	public function StartPasskeyAssertion($username, $sessionHash)
	{
		$challenge = Webauthn::NewChallenge();
		$this->SetChallenge($username, $sessionHash, 'pkget:' . $challenge);
		return array('challenge' => $challenge, 'rp_id' => $this->rpId, 'allow' => $this->CredentialIds($username));
	}

	/** @param bool $requireUserVerification demand a PIN or fingerprint, not just a touch (admin mode does) */
	public function VerifyPasskey($username, $sessionHash, $credentialId, $clientDataB64, $authenticatorDataB64, $signatureB64, $requireUserVerification = false)
	{
		if($this->Throttled($username)) { return array('ok' => false, 'error' => 'throttled'); }
		$challenge = $this->TakeChallenge($username, $sessionHash);
		if($challenge === null || strncmp($challenge, 'pkget:', 6) !== 0) { return array('ok' => false, 'error' => 'expired'); }
		$method = $this->Run("SELECT id, public_key, alg, sign_count FROM user_two_factor WHERE username = ? AND type = 'passkey' AND secret = ?",
			array($username, (string)$credentialId))->fetch(PDO::FETCH_ASSOC);
		if(!$method) { return $this->Fail($username, 'unknown_passkey'); }
		try
		{
			$result = Webauthn::VerifyAssertion($clientDataB64, $authenticatorDataB64, $signatureB64, substr($challenge, 6), $this->origin,
				$this->rpId, $method['public_key'], (int)$method['alg'], (int)$method['sign_count'], $requireUserVerification);
		}
		catch(WebauthnException $e)
		{
			return $this->Fail($username, 'passkey_rejected');
		}
		$this->Run("UPDATE user_two_factor SET sign_count = ?, last_used = ? WHERE id = ?", array($result['sign_count'], $this->Stamp(), $method['id']));
		return array('ok' => true, 'via' => 'passkey');
	}

	private function CredentialIds($username)
	{
		return $this->Run("SELECT secret FROM user_two_factor WHERE username = ? AND type = 'passkey' ORDER BY id", array($username))->fetchAll(PDO::FETCH_COLUMN);
	}

	// ── What a session knows ─────────────────────────────────────────────────────────────────────────

	/** Marks a new session as waiting for the second factor: it grants nothing until MfaDone */
	public function MarkPending($username, $sessionHash)
	{
		$this->Run("UPDATE user_login_hashes SET mfa_pending = 1, mfa_at = NULL, admin_until = NULL WHERE username = ? AND hash = ?", array($username, $sessionHash));
	}

	public function MfaDone($username, $sessionHash)
	{
		$this->Run("UPDATE user_login_hashes SET mfa_pending = 0, mfa_at = ? WHERE username = ? AND hash = ?", array($this->Stamp(), $username, $sessionHash));
	}

	public function IsPending($username, $sessionHash)
	{
		return (int)$this->Run("SELECT mfa_pending FROM user_login_hashes WHERE username = ? AND hash = ?", array($username, $sessionHash))->fetchColumn() === 1;
	}

	/**
	 * Is a session that has been through (or doesn't need) the second factor too old to keep trusting? A member who
	 * asks for a code at login is asked again once the last one is MFA_REFRESH_DAYS old, even in a long-lived session.
	 */
	public function MfaStale($username, $sessionHash)
	{
		if(!$this->AsksAtLogin($username)) { return false; }
		$at = $this->Run("SELECT mfa_at FROM user_login_hashes WHERE username = ? AND hash = ?", array($username, $sessionHash))->fetchColumn();
		return !$at || $this->Parse($at) < $this->Now() - self::MFA_REFRESH_DAYS * 86400;
	}

	// ── Remember this computer ───────────────────────────────────────────────────────────────────────

	/** A signed token for a cookie; it stops working if the password or the member's methods change. '' without a key. */
	public function DeviceToken($username)
	{
		if($this->key === '') { return ''; }
		$expires = $this->Now() + self::DEVICE_DAYS * 86400;
		return Webauthn::Base64UrlEncode($username) . '.' . $expires . '.' . $this->DeviceMac($username, $expires);
	}

	public function DeviceTrusted($username, $token)
	{
		if($this->key === '' || !is_string($token)) { return false; }
		$parts = explode('.', $token);
		if(count($parts) !== 3 || !ctype_digit($parts[1]) || (int)$parts[1] < $this->Now()) { return false; }
		try { if(Webauthn::Base64UrlDecode($parts[0]) !== $username) { return false; } }
		catch(WebauthnException $e) { return false; }
		return hash_equals($this->DeviceMac($username, (int)$parts[1]), $parts[2]);
	}

	private function DeviceMac($username, $expires)
	{
		$password = (string)$this->Run("SELECT password FROM user_info WHERE username = ?", array($username))->fetchColumn();
		$methods = $this->Run("SELECT id, created FROM user_two_factor WHERE username = ? ORDER BY id", array($username))->fetchAll(PDO::FETCH_ASSOC);
		$fingerprint = hash('sha256', json_encode($methods));
		return hash_hmac('sha256', $username . '|' . $expires . '|' . $password . '|' . $fingerprint, base64_decode($this->key) ?: $this->key);
	}

	// ── Admin mode ───────────────────────────────────────────────────────────────────────────────────

	/**
	 * Turns admin mode on for a session, with an authenticator or recovery code typed for this and nothing else. Only
	 * a member with two-factor set up can; the code can't have been used before, so the one that signed them in doesn't
	 * count. @return array ok, error ('two_factor_required', 'wrong_code', 'throttled')
	 */
	public function StepUpWithCode($username, $sessionHash, $code)
	{
		if(!$this->HasTwoFactor($username)) { return array('ok' => false, 'error' => 'two_factor_required'); }
		$proof = $this->VerifyCode($username, $code);
		return $proof['ok'] ? $this->SetAdminMode($username, $sessionHash) : $proof;
	}

	/** The same with a passkey, which must verify the person (a PIN or fingerprint), not only be touched. */
	public function StepUpWithPasskey($username, $sessionHash, $credentialId, $clientDataB64, $authenticatorDataB64, $signatureB64)
	{
		if(!$this->HasTwoFactor($username)) { return array('ok' => false, 'error' => 'two_factor_required'); }
		$proof = $this->VerifyPasskey($username, $sessionHash, $credentialId, $clientDataB64, $authenticatorDataB64, $signatureB64, true);
		return $proof['ok'] ? $this->SetAdminMode($username, $sessionHash) : $proof;
	}

	private function SetAdminMode($username, $sessionHash)
	{
		$until = $this->Now() + self::ADMIN_MODE_MINUTES * 60;
		$changed = $this->Run("UPDATE user_login_hashes SET admin_until = ? WHERE username = ? AND hash = ? AND mfa_pending = 0", array($this->Stamp($until), $username, $sessionHash))->rowCount();
		return $changed > 0 ? array('ok' => true, 'until' => $until) : array('ok' => false, 'error' => 'no_session');
	}

	public function DisableAdminMode($username, $sessionHash)
	{
		$this->Run("UPDATE user_login_hashes SET admin_until = NULL WHERE username = ? AND hash = ?", array($username, $sessionHash));
	}

	/** Is admin mode on for this session? Each check while it's on pushes the end out again (idle timeout). */
	public function AdminModeActive($username, $sessionHash, $slide = true)
	{
		$row = $this->Run("SELECT admin_until, mfa_pending FROM user_login_hashes WHERE username = ? AND hash = ?", array($username, $sessionHash))->fetch(PDO::FETCH_ASSOC);
		if(!$row || (int)$row['mfa_pending'] === 1 || !$row['admin_until']) { return false; }
		$until = $this->Parse($row['admin_until']);
		if($until <= $this->Now()) { return false; }
		if($slide && $until < $this->Now() + self::ADMIN_MODE_MINUTES * 60)
		{
			$this->Run("UPDATE user_login_hashes SET admin_until = ? WHERE username = ? AND hash = ?", array($this->Stamp($this->Now() + self::ADMIN_MODE_MINUTES * 60), $username, $sessionHash));
		}
		return true;
	}
}
