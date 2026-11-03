<?php
require_once WIFIDB_LIB . "/webauthn.inc.php";

/** A passkey, made the way a browser's authenticator makes them, so the verifier can be tried without a browser. */
final class SoftwareAuthenticator
{
    public $key;
    public string $credentialId;
    public string $type;

    public function __construct(string $type = 'ec')
    {
        $this->type = $type;
        $this->credentialId = random_bytes(32);
        $this->key = $type === 'ec'
            ? openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1'])
            : openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_RSA, 'private_key_bits' => 2048]);
    }

    // ── a minimal CBOR writer ──
    public static function uint(int $major, int $n): string
    {
        if ($n < 24) { return chr(($major << 5) | $n); }
        if ($n < 256) { return chr(($major << 5) | 24) . chr($n); }
        return chr(($major << 5) | 25) . pack('n', $n);
    }
    public static function int(int $n): string { return $n >= 0 ? self::uint(0, $n) : self::uint(1, -1 - $n); }
    public static function bytes(string $s): string { return self::uint(2, strlen($s)) . $s; }
    public static function text(string $s): string { return self::uint(3, strlen($s)) . $s; }
    public static function map(array $pairs): string { return self::uint(5, count($pairs)) . implode('', array_map(fn ($p) => $p[0] . $p[1], $pairs)); }

    private function cosePublicKey(): string
    {
        $d = openssl_pkey_get_details($this->key);
        if ($this->type === 'ec') {
            return self::map([[self::int(1), self::int(2)], [self::int(3), self::int(-7)], [self::int(-1), self::int(1)],
                [self::int(-2), self::bytes(str_pad($d['ec']['x'], 32, "\0", STR_PAD_LEFT))],
                [self::int(-3), self::bytes(str_pad($d['ec']['y'], 32, "\0", STR_PAD_LEFT))]]);
        }
        return self::map([[self::int(1), self::int(3)], [self::int(3), self::int(-257)],
            [self::int(-1), self::bytes($d['rsa']['n'])], [self::int(-2), self::bytes($d['rsa']['e'])]]);
    }

    public function clientData(string $type, string $challenge, string $origin): string
    {
        return json_encode(['type' => $type, 'challenge' => $challenge, 'origin' => $origin, 'crossOrigin' => false]);
    }

    /** @return array [clientDataJSON, attestationObject], base64url */
    public function register(string $challenge, string $origin, string $rpId, int $flags = 0x45, int $count = 0, string $type = 'webauthn.create'): array
    {
        $authData = hash('sha256', $rpId, true) . chr($flags) . pack('N', $count) . str_repeat("\0", 16)
            . pack('n', strlen($this->credentialId)) . $this->credentialId . $this->cosePublicKey();
        $attestation = self::map([[self::text('fmt'), self::text('none')], [self::text('attStmt'), self::map([])],
            [self::text('authData'), self::bytes($authData)]]);
        return [Webauthn::Base64UrlEncode($this->clientData($type, $challenge, $origin)), Webauthn::Base64UrlEncode($attestation)];
    }

    /** @return array [clientDataJSON, authenticatorData, signature], base64url */
    public function assert(string $challenge, string $origin, string $rpId, int $count, int $flags = 0x05, $signWith = null): array
    {
        $clientData = $this->clientData('webauthn.get', $challenge, $origin);
        $authData = hash('sha256', $rpId, true) . chr($flags) . pack('N', $count);
        openssl_sign($authData . hash('sha256', $clientData, true), $signature, $signWith ?? $this->key, OPENSSL_ALGO_SHA256);
        return [Webauthn::Base64UrlEncode($clientData), Webauthn::Base64UrlEncode($authData), Webauthn::Base64UrlEncode($signature)];
    }
}
