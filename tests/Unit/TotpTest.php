<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/totp.inc.php';

final class TotpTest extends TestCase
{
    // RFC 6238 appendix B: the SHA-1 secret is the ASCII string "12345678901234567890"
    private const RFC_SECRET = 'GEZDGNBVGY3TQOJQGEZDGNBVGY3TQOJQ';

    public static function rfcVectors(): array
    {
        // time => the RFC's 8-digit code; the 6-digit code is its last six digits
        return [
            'time 59'          => [59, '287082'],
            'time 1111111109'  => [1111111109, '081804'],
            'time 1111111111'  => [1111111111, '050471'],
            'time 1234567890'  => [1234567890, '005924'],
            'time 2000000000'  => [2000000000, '279037'],
            'time 20000000000' => [20000000000, '353130'],
        ];
    }

    #[DataProvider('rfcVectors')]
    public function testCodesMatchTheRfc(int $time, string $expected): void
    {
        $this->assertSame($expected, Totp::Code(self::RFC_SECRET, Totp::Step($time)));
    }

    public function testBase32RoundTrips(): void
    {
        foreach ([1, 5, 10, 20, 33] as $length) {
            $bytes = random_bytes($length);
            $this->assertSame($bytes, Totp::Base32Decode(Totp::Base32Encode($bytes)));
        }
        $this->assertSame('12345678901234567890', Totp::Base32Decode(self::RFC_SECRET));
    }

    public function testBase32DecodeIsForgiving(): void
    {
        $this->assertSame('12345678901234567890', Totp::Base32Decode(strtolower('GEZD GNBV GY3T QOJQ GEZD GNBV GY3T QOJQ')));
        $this->assertFalse(Totp::Base32Decode('not base32 !'));
        $this->assertFalse(Totp::Base32Decode(''));
    }

    public function testGeneratedSecretsAreBase32AndDifferent(): void
    {
        $a = Totp::GenerateSecret();
        $b = Totp::GenerateSecret();
        $this->assertMatchesRegularExpression('/^[A-Z2-7]{32}$/', $a);
        $this->assertNotSame($a, $b);
    }

    public function testVerifyAcceptsTheCurrentCodeAndReturnsItsStep(): void
    {
        $time = 1111111109;
        $step = Totp::Verify(self::RFC_SECRET, '081804', 0, 1, $time);
        $this->assertSame(Totp::Step($time), $step);
    }

    public function testVerifyToleratesOneStepOfClockDriftEitherWay(): void
    {
        $time = 1111111109;
        $before = Totp::Code(self::RFC_SECRET, Totp::Step($time) - 1);
        $after = Totp::Code(self::RFC_SECRET, Totp::Step($time) + 1);
        $this->assertNotFalse(Totp::Verify(self::RFC_SECRET, $before, 0, 1, $time));
        $this->assertNotFalse(Totp::Verify(self::RFC_SECRET, $after, 0, 1, $time));
        $tooOld = Totp::Code(self::RFC_SECRET, Totp::Step($time) - 3);
        $this->assertFalse(Totp::Verify(self::RFC_SECRET, $tooOld, 0, 1, $time));
    }

    public function testACodeCannotBeUsedTwice(): void
    {
        $time = 1111111109;
        $step = Totp::Verify(self::RFC_SECRET, '081804', 0, 1, $time);
        $this->assertNotFalse($step);
        $this->assertFalse(Totp::Verify(self::RFC_SECRET, '081804', $step, 1, $time), 'the same step is refused once used');
    }

    public static function badCodes(): array
    {
        return ['wrong code' => ['000000'], 'too short' => ['08180'], 'too long' => ['0818044'],
                'letters' => ['08180a'], 'empty' => [''], 'with spaces is fine only if digits' => ['0818 0x']];
    }

    #[DataProvider('badCodes')]
    public function testVerifyRefusesBadCodes(string $code): void
    {
        $this->assertFalse(Totp::Verify(self::RFC_SECRET, $code, 0, 1, 1111111109));
    }

    public function testVerifyIgnoresSpacesInTheCode(): void
    {
        $this->assertNotFalse(Totp::Verify(self::RFC_SECRET, '081 804', 0, 1, 1111111109));
    }

    public function testUriHasWhatAnAuthenticatorAppNeeds(): void
    {
        $uri = Totp::Uri('WifiDB', 'alice@example.com', self::RFC_SECRET);
        $this->assertStringStartsWith('otpauth://totp/WifiDB%3Aalice%40example.com?', $uri);
        $this->assertStringContainsString('secret=' . self::RFC_SECRET, $uri);
        $this->assertStringContainsString('issuer=WifiDB', $uri);
        $this->assertStringContainsString('digits=6', $uri);
        $this->assertStringContainsString('period=30', $uri);
    }

    public function testRecoveryCodes(): void
    {
        $codes = Totp::GenerateRecoveryCodes();
        $this->assertCount(10, $codes);
        $this->assertCount(10, array_unique($codes));
        foreach ($codes as $code) {
            $this->assertMatchesRegularExpression('/^[a-z2-9]{4}-[a-z2-9]{4}-[a-z2-9]{4}$/', $code);
            $this->assertTrue(Totp::LooksLikeRecoveryCode($code));
        }
        $this->assertFalse(Totp::LooksLikeRecoveryCode('123456'));
    }

    public function testRecoveryCodesAreStoredAsHashesAndCheckedLeniently(): void
    {
        $code = 'k3f9-x2mq-7d4p';
        $hash = Totp::HashRecoveryCode($code);
        $this->assertStringNotContainsString('k3f9', $hash);
        // typed with other capitals, spaces or no dashes
        foreach (['K3F9-X2MQ-7D4P', 'k3f9 x2mq 7d4p', 'k3f9x2mq7d4p'] as $typed) {
            $this->assertTrue(password_verify(Totp::NormalizeRecoveryCode($typed), $hash), $typed);
        }
        $this->assertFalse(password_verify(Totp::NormalizeRecoveryCode('k3f9-x2mq-7d4q'), $hash));
    }

    public function testSecretsAreEncryptedWithTheSiteKey(): void
    {
        $key = Totp::GenerateKey();
        $stored = Totp::Protect(self::RFC_SECRET, $key);
        $this->assertStringStartsWith('enc:', $stored);
        $this->assertStringNotContainsString(self::RFC_SECRET, $stored);
        $this->assertSame(self::RFC_SECRET, Totp::Unprotect($stored, $key));
        $this->assertNotSame($stored, Totp::Protect(self::RFC_SECRET, $key), 'a fresh nonce each time');
    }

    public function testTheWrongKeyCannotReadASecret(): void
    {
        $stored = Totp::Protect(self::RFC_SECRET, Totp::GenerateKey());
        $this->assertFalse(Totp::Unprotect($stored, Totp::GenerateKey()));
        $this->assertFalse(Totp::Unprotect($stored, ''));
        $this->assertFalse(Totp::Unprotect('enc:' . base64_encode('short'), Totp::GenerateKey()));
    }

    public function testWithoutAKeyTheSecretIsStoredAsItIs(): void
    {
        $this->assertSame(self::RFC_SECRET, Totp::Protect(self::RFC_SECRET, ''));
        $this->assertSame(self::RFC_SECRET, Totp::Unprotect(self::RFC_SECRET, ''));
    }
}
