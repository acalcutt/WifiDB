<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/webauthn.inc.php';

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

final class WebauthnTest extends TestCase
{
    private const ORIGIN = 'https://wifidb.example.com';
    private const RP = 'wifidb.example.com';

    // ── CBOR ──

    public function testCborDecodesTheBasicTypes(): void
    {
        $this->assertSame(0, Cbor::Decode("\x00"));
        $this->assertSame(23, Cbor::Decode("\x17"));
        $this->assertSame(500, Cbor::Decode("\x19\x01\xf4"));
        $this->assertSame(-1, Cbor::Decode("\x20"));
        $this->assertSame(-7, Cbor::Decode("\x26"));
        $this->assertSame(-257, Cbor::Decode("\x39\x01\x00"));
        $this->assertSame("\x01\x02", Cbor::Decode("\x42\x01\x02"));
        $this->assertSame('none', Cbor::Decode("\x64none"));
        $this->assertTrue(Cbor::Decode("\xf5"));
        $this->assertFalse(Cbor::Decode("\xf4"));
        $this->assertNull(Cbor::Decode("\xf6"));
        $this->assertSame([1, 'a'], Cbor::Decode("\x82\x01\x61a"));
        $this->assertSame([1 => 2, -2 => 'x', 'fmt' => 'none'], Cbor::Decode("\xa3\x01\x02\x21\x61x\x63fmt\x64none"));
    }

    public function testCborRefusesWhatIsDamagedOrRisky(): void
    {
        foreach (["", "\x19\x01", "\x42\x01", "\x82\x01", "\x5f", "\xf9\x00\x00", "\xa1\x82\x01\x02\x03"] as $bad) {
            try {
                Cbor::Decode($bad);
                $this->fail('accepted ' . bin2hex($bad));
            } catch (WebauthnException $e) {
                $this->assertInstanceOf(WebauthnException::class, $e);
            }
        }
        $this->expectException(WebauthnException::class);
        Cbor::Decode(str_repeat("\x81", 40) . "\x00");   // nested far too deep
    }

    public function testBase64Url(): void
    {
        $bytes = random_bytes(33);
        $this->assertSame($bytes, Webauthn::Base64UrlDecode(Webauthn::Base64UrlEncode($bytes)));
        $this->assertDoesNotMatchRegularExpression('/[+\/=]/', Webauthn::Base64UrlEncode("\xfb\xff\xfe"));
        $this->expectException(WebauthnException::class);
        Webauthn::Base64UrlDecode('not valid!');
    }

    // ── registering a passkey ──

    public static function keyTypes(): array { return ['ES256 (P-256)' => ['ec'], 'RS256' => ['rsa']]; }

    #[DataProvider('keyTypes')]
    public function testRegistrationReturnsAKeyThatVerifiesSignIns(string $type): void
    {
        $passkey = new SoftwareAuthenticator($type);
        $challenge = Webauthn::NewChallenge();
        [$clientData, $attestation] = $passkey->register($challenge, self::ORIGIN, self::RP);
        $registered = Webauthn::VerifyRegistration($clientData, $attestation, $challenge, self::ORIGIN, self::RP);

        $this->assertSame(Webauthn::Base64UrlEncode($passkey->credentialId), $registered['credential_id']);
        $this->assertSame($type === 'ec' ? -7 : -257, $registered['alg']);
        $this->assertSame(0, $registered['sign_count']);
        $this->assertTrue($registered['user_verified']);
        $this->assertStringStartsWith('-----BEGIN PUBLIC KEY-----', $registered['public_key']);

        $signIn = Webauthn::NewChallenge();
        [$cd, $ad, $sig] = $passkey->assert($signIn, self::ORIGIN, self::RP, 1);
        $result = Webauthn::VerifyAssertion($cd, $ad, $sig, $signIn, self::ORIGIN, self::RP, $registered['public_key'], $registered['alg'], 0);
        $this->assertSame(1, $result['sign_count']);
    }

    public static function badRegistrations(): array
    {
        return [
            'another challenge'   => ['challenge', 'x'],
            'another origin'      => ['origin', 'https://evil.example.net'],
            'a sign-in, not a registration' => ['type', 'webauthn.get'],
            'a passkey for another site' => ['rp', 'evil.example.net'],
            'the user was not present' => ['flags', 0x40],
            'no credential data'  => ['flags', 0x01],
        ];
    }

    #[DataProvider('badRegistrations')]
    public function testRegistrationRefusesWhatIsWrong(string $what, $value): void
    {
        $passkey = new SoftwareAuthenticator();
        $challenge = Webauthn::NewChallenge();
        $expect = ['challenge' => $challenge, 'origin' => self::ORIGIN, 'rp' => self::RP];
        $args = ['challenge' => $challenge, 'origin' => self::ORIGIN, 'rp' => self::RP, 'flags' => 0x45, 'type' => 'webauthn.create'];
        $args[$what] = $value;
        if ($what === 'challenge') { $args['challenge'] = 'x'; }
        [$clientData, $attestation] = $passkey->register($args['challenge'], $args['origin'], $args['rp'], $args['flags'], 0, $args['type']);
        $this->expectException(WebauthnException::class);
        Webauthn::VerifyRegistration($clientData, $attestation, $expect['challenge'], $expect['origin'], $expect['rp']);
    }

    public function testRegistrationRefusesAnUnsupportedKeyType(): void
    {
        $passkey = new SoftwareAuthenticator();
        $challenge = Webauthn::NewChallenge();
        // a COSE key with an algorithm we don't support (EdDSA, -8) in place of the real one
        $cose = SoftwareAuthenticator::map([[SoftwareAuthenticator::int(1), SoftwareAuthenticator::int(1)], [SoftwareAuthenticator::int(3), SoftwareAuthenticator::int(-8)]]);
        $authData = hash('sha256', self::RP, true) . chr(0x45) . pack('N', 0) . str_repeat("\0", 16) . pack('n', 4) . 'abcd' . $cose;
        $attestation = SoftwareAuthenticator::map([[SoftwareAuthenticator::text('authData'), SoftwareAuthenticator::bytes($authData)]]);
        $this->expectException(WebauthnException::class);
        Webauthn::VerifyRegistration(Webauthn::Base64UrlEncode($passkey->clientData('webauthn.create', $challenge, self::ORIGIN)),
            Webauthn::Base64UrlEncode($attestation), $challenge, self::ORIGIN, self::RP);
    }

    // ── signing in ──

    private function registered(string $type = 'ec'): array
    {
        $passkey = new SoftwareAuthenticator($type);
        $challenge = Webauthn::NewChallenge();
        [$cd, $att] = $passkey->register($challenge, self::ORIGIN, self::RP);
        return [$passkey, Webauthn::VerifyRegistration($cd, $att, $challenge, self::ORIGIN, self::RP)];
    }

    private function signIn(SoftwareAuthenticator $passkey, array $registered, int $stored, array $overrides = []): array
    {
        $challenge = $overrides['challenge'] ?? Webauthn::NewChallenge();
        $count = $overrides['count'] ?? $stored + 1;
        [$cd, $ad, $sig] = $passkey->assert($overrides['sign_challenge'] ?? $challenge, $overrides['origin'] ?? self::ORIGIN,
            $overrides['rp'] ?? self::RP, $count, $overrides['flags'] ?? 0x05, $overrides['key'] ?? null);
        return Webauthn::VerifyAssertion($cd, $ad, $sig, $challenge, self::ORIGIN, self::RP, $registered['public_key'],
            $registered['alg'], $stored, $overrides['require_uv'] ?? false);
    }

    public function testSignInWorksAndTheCounterAdvances(): void
    {
        [$passkey, $registered] = $this->registered();
        $this->assertSame(5, $this->signIn($passkey, $registered, 4)['sign_count']);
        $this->assertSame(6, $this->signIn($passkey, $registered, 5)['sign_count']);
    }

    public function testAuthenticatorsThatAlwaysSendZeroAreAccepted(): void
    {
        [$passkey, $registered] = $this->registered();
        $this->assertSame(0, $this->signIn($passkey, $registered, 0, ['count' => 0])['sign_count']);
    }

    public static function badSignIns(): array
    {
        return [
            'a signature by another key' => ['wrong_key'],
            'a different challenge'      => ['other_challenge'],
            'a different origin'         => ['origin'],
            'a different site'           => ['rp'],
            'the user not present'       => ['not_present'],
            'a counter that did not advance' => ['stale_counter'],
            'a counter that went back'   => ['old_counter'],
        ];
    }

    #[DataProvider('badSignIns')]
    public function testSignInRefusesWhatIsWrong(string $what): void
    {
        [$passkey, $registered] = $this->registered();
        $stored = 10;
        $overrides = match ($what) {
            'wrong_key'       => ['key' => (new SoftwareAuthenticator())->key],
            'other_challenge' => ['sign_challenge' => Webauthn::NewChallenge()],
            'origin'          => ['origin' => 'https://evil.example.net'],
            'rp'              => ['rp' => 'evil.example.net'],
            'not_present'     => ['flags' => 0x04],
            'stale_counter'   => ['count' => 10],
            'old_counter'     => ['count' => 3],
        };
        $this->expectException(WebauthnException::class);
        $this->signIn($passkey, $registered, $stored, $overrides);
    }

    public function testAdminModeCanDemandUserVerification(): void
    {
        [$passkey, $registered] = $this->registered();
        // a touch only (flags: user present) is not enough when verification is required...
        try {
            $this->signIn($passkey, $registered, 0, ['require_uv' => true, 'flags' => 0x01]);
            $this->fail('a touch alone was accepted');
        } catch (WebauthnException $e) {
            $this->assertStringContainsString('verify', $e->getMessage());
        }
        // ...a PIN or fingerprint (user verified) is
        $this->assertSame(1, $this->signIn($passkey, $registered, 0, ['require_uv' => true, 'flags' => 0x05])['sign_count']);
    }

    public function testRsaSignInWorks(): void
    {
        [$passkey, $registered] = $this->registered('rsa');
        $this->assertSame(1, $this->signIn($passkey, $registered, 0)['sign_count']);
    }
}
