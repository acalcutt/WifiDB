<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/security.inc.php';

// GenerateKey and HashPassword use no state, so the class is built without its database-backed constructor.
final class SecurityTest extends TestCase
{
    private security $sec;

    protected function setUp(): void
    {
        $this->sec = (new ReflectionClass(security::class))->newInstanceWithoutConstructor();
    }

    public function testGenerateKeyHasRequestedLengthAndAlphabet(): void
    {
        foreach ([1, 12, 29, 64, 128] as $len) {
            $key = $this->sec->GenerateKey($len);
            $this->assertSame($len, strlen($key));
            $this->assertMatchesRegularExpression('/^[A-HK-NO-TW-Zabcdefghjkmnpqrstwxyz1-9]+$/', $key);
        }
    }

    public function testGenerateKeyIsNotRepeated(): void
    {
        $keys = [];
        for ($i = 0; $i < 200; $i++) {
            $keys[] = $this->sec->GenerateKey(32);
        }
        $this->assertCount(200, array_unique($keys));
    }

    public function testHashPasswordVerifies(): void
    {
        $hash = $this->sec->HashPassword('correct horse');
        $this->assertTrue(password_verify('correct horse', $hash));
        $this->assertFalse(password_verify('wrong', $hash));
    }

    public function testHashPasswordUsesCostTwelve(): void
    {
        $info = password_get_info($this->sec->HashPassword('x'));
        $this->assertSame('2y', $info['algo']);
        $this->assertSame(12, $info['options']['cost']);
    }

    public function testLegacyCryptHashesStillVerifyAndNeedRehash(): void
    {
        // Hashes stored before the upgrade were made with crypt() at cost 7.
        $legacy = crypt('old password', '$2a$07$' . 'abcdefghijklmnopqrstuv' . '$');
        $this->assertTrue(password_verify('old password', $legacy));
        $this->assertTrue(password_needs_rehash($legacy, PASSWORD_BCRYPT, ['cost' => 12]));
    }
}
