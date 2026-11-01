<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/site_settings.inc.php';

final class SiteSettingsTest extends TestCase
{
    private PDO $db;
    private string $key;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE site_settings (name TEXT PRIMARY KEY, value TEXT, updated_by TEXT, updated_at TEXT)");
        $this->key = Totp::GenerateKey();
    }

    private function settings(array $config = []): SiteSettings
    {
        return new SiteSettings($this->db, $config + ['two_factor_key' => $this->key], fn () => 1_800_000_000);
    }

    public function testWithNothingSavedTheValuesComeFromConfigThenTheDefaults(): void
    {
        $s = $this->settings(['email_validation' => 0]);
        $this->assertFalse($s->Get('email_validation'));
        $this->assertSame('config', $s->Source('email_validation'));
        $this->assertTrue($s->Get('signups_open'), 'sign-ups stay open unless someone closes them');
        $this->assertSame('default', $s->Source('signups_open'));
        $this->assertFalse($s->Get('cleantalk_enabled'));
        $this->assertSame('', $s->Get('cleantalk_key'));
    }

    public function testASavedValueWinsOverConfig(): void
    {
        $this->assertSame(['signups_open'], $this->settings()->Save(['signups_open' => false], 'root'));
        $s = $this->settings(['signups_open' => 1]);
        $this->assertFalse($s->Get('signups_open'));
        $this->assertSame('saved', $s->Source('signups_open'));
        $row = $this->db->query("SELECT value, updated_by, updated_at FROM site_settings WHERE name = 'signups_open'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['value' => '0', 'updated_by' => 'root', 'updated_at' => gmdate('Y-m-d H:i:s', 1_800_000_000)], $row);
    }

    public function testSavingTheSameValueAgainChangesNothing(): void
    {
        $s = $this->settings();
        $s->Save(['signups_open' => true, 'email_validation' => true], 'root');
        $this->assertSame([], $s->Save(['signups_open' => true, 'email_validation' => true], 'other'));
        $this->assertSame(['email_validation'], $s->Save(['signups_open' => true, 'email_validation' => false], 'other'));
    }

    public function testASecretIsStoredEncryptedAndReadBack(): void
    {
        $s = $this->settings();
        $this->assertSame(['cleantalk_key'], $s->Save(['cleantalk_key' => '  abc123  '], 'root'));
        $stored = $this->db->query("SELECT value FROM site_settings WHERE name = 'cleantalk_key'")->fetchColumn();
        $this->assertStringStartsWith('enc:', $stored);
        $this->assertStringNotContainsString('abc123', $stored);
        $this->assertSame('abc123', $this->settings()->Get('cleantalk_key'));
    }

    public function testASecretLeftOutIsKeptAndAnEmptyOneClearsIt(): void
    {
        $s = $this->settings();
        $s->Save(['cleantalk_key' => 'abc123'], 'root');
        $this->assertSame([], $s->Save(['cleantalk_key' => null], 'root'));
        $this->assertSame('abc123', $s->Get('cleantalk_key'));
        $this->assertSame(['cleantalk_key'], $s->Save(['cleantalk_key' => ''], 'root'));
        $this->assertSame('', $s->Get('cleantalk_key'));
        $this->assertSame('saved', $s->Source('cleantalk_key'), 'cleared here, so a key in config no longer comes back');
    }

    public function testASecretSavedUnderAnotherKeyReadsAsNotSet(): void
    {
        $this->settings()->Save(['cleantalk_key' => 'abc123'], 'root');
        $other = new SiteSettings($this->db, ['two_factor_key' => Totp::GenerateKey()]);
        $this->assertSame('', $other->Get('cleantalk_key'));
    }

    public function testDescribeNeverShowsASecret(): void
    {
        $s = $this->settings();
        $s->Save(['cleantalk_key' => 'abc123'], 'root');
        $described = $s->Describe();
        $this->assertStringNotContainsString('abc123', json_encode($described));
        $byName = array_column($described, null, 'name');
        $this->assertTrue($byName['cleantalk_key']['is_set']);
        $this->assertTrue($byName['signups_open']['value']);
    }

    public function testUnknownNamesAreIgnored(): void
    {
        $this->assertSame([], $this->settings()->Save(['smtp_pass' => 'x', 'root' => 'y'], 'root'));
        $this->assertSame(0, (int)$this->db->query("SELECT COUNT(*) FROM site_settings")->fetchColumn());
    }

    public function testADatabaseWithoutTheTableFallsBackToConfig(): void
    {
        $bare = new SiteSettings(new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]), ['signups_open' => 0]);
        $this->assertFalse($bare->Get('signups_open'));
        $this->assertSame('config', $bare->Source('signups_open'));
    }
}
