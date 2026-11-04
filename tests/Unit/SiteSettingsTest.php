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
        $this->assertSame([], $this->settings()->Save(['db_pwd' => 'x', 'DKIM_private' => '/etc/shadow', 'root' => 'y'], 'root'));
        $this->assertSame(0, (int)$this->db->query("SELECT COUNT(*) FROM site_settings")->fetchColumn());
    }

    public function testAChoiceIsOneOfItsOptions(): void
    {
        $s = $this->settings();
        $this->assertSame('region', $s->Get('analytics_consent'), "the cookie banner is asked where it's required unless set");
        $this->assertSame([], $s->Save(['analytics_consent' => 'sometimes'], 'root'), 'not an option: nothing saved');
        $this->assertSame(['analytics_consent'], $s->Save(['analytics_consent' => 'off'], 'root'));
        $this->assertSame('off', $this->settings()->Get('analytics_consent'));
        $this->assertSame('off', $this->settings(['analytics_consent' => 'ask'])->Get('analytics_consent'), 'saved wins over config');
        $fresh = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->assertSame('ask', (new SiteSettings($fresh, ['analytics_consent' => 'ask']))->Get('analytics_consent'));
        $this->assertSame('region', (new SiteSettings($fresh, ['analytics_consent' => 'bogus']))->Get('analytics_consent'), 'not an option: the default');
        $options = array_column($s->Describe(), null, 'name')['analytics_consent']['options'];
        $this->assertSame(['region', 'ask', 'off'], array_keys($options));
    }

    public function testIdsAreTakenOutOfWhatGoogleGives(): void
    {
        $tag = "<script async src=\"https://www.googletagmanager.com/gtag/js?id=G-S1ZD9TQYZK\"></script>\n<script>gtag('config', 'G-S1ZD9TQYZK');</script>";
        $this->assertSame(['G-S1ZD9TQYZK', ''], SiteSettings::Clean('analytics_id', $tag));
        $this->assertSame(['G-ABC123XYZ', ''], SiteSettings::Clean('analytics_id', ' g-abc123xyz '));
        $this->assertNotSame('', SiteSettings::Clean('analytics_id', 'UA-12345-1')[1], 'old Universal Analytics IDs are refused');
        $this->assertSame(['ca-pub-2350008141216245', ''], SiteSettings::Clean('adsense_client', 'pub-2350008141216245'));
        $this->assertSame(['ca-pub-2350008141216245', ''], SiteSettings::Clean('adsense_client', '<script src="...adsbygoogle.js?client=ca-pub-2350008141216245"></script>'));
        $this->assertNotSame('', SiteSettings::Clean('adsense_client', 'my publisher')[1]);
        $this->assertSame(['6299844992', ''], SiteSettings::Clean('adsense_slot', '<ins class="adsbygoogle" data-ad-client="ca-pub-1" data-ad-slot="6299844992"></ins>'));
        $this->assertSame(['6299844992', ''], SiteSettings::Clean('adsense_slot', '6299844992'));
        $this->assertSame(['', ''], SiteSettings::Clean('analytics_id', '  '), 'empty turns it off');
        $this->assertSame(['garage', ''], SiteSettings::Clean('backup_s3_region', ' garage '), 'others are only trimmed');
    }

    public function testTheMapsElevationTilesAreATileAddress(): void
    {
        $url = 'https://tiles.example.org/dem/{z}/{x}/{y}.png';
        $this->assertSame([$url, ''], SiteSettings::Clean('map_dem_url', " $url "));
        $this->assertSame(['', ''], SiteSettings::Clean('map_dem_url', ''), 'empty: no contour lines');
        foreach (['https://tiles.example.org/dem/{z}/{x}.png', 'javascript:alert(1)//{z}{x}{y}', "https://x/{z}/{x}/{y}.png'+alert(1)+'", "https://x/tiles.json'+alert(1)+'"] as $bad) {
            $this->assertNotSame('', SiteSettings::Clean('map_dem_url', $bad)[1], $bad);
        }
        $this->assertSame('https://tiles.wifidb.net/data/jaxa_terrarium/{z}/{x}/{y}.png', $this->settings()->Get('map_dem_url'), 'what the map used before');
    }

    public function testTheMapsElevationTilesCanBeATileJson(): void
    {
        $url = 'https://swarm.example.org/latest/terrain/tiles.json#torrent=https%3A%2F%2Fswarm.example.org%2Flatest%2Fterrain%2Farchive.torrent&magnet=magnet%3A%3Fxt%3Durn%3Abtih%3A767d';
        $this->assertSame([$url, ''], SiteSettings::Clean('map_dem_url', $url), 'a pmtiles-swarm tiles.json, #torrent= and all');
        $this->assertSame('auto', $this->settings()->Get('map_dem_encoding'), 'the encoding the TileJSON gives, unless set');
        $this->assertSame('', $this->settings()->Get('map_dem_maxzoom'), "and its maxzoom");
        $this->assertSame(['12', ''], SiteSettings::Clean('map_dem_maxzoom', ' 12 '));
        $this->assertSame(['0', ''], SiteSettings::Clean('map_dem_maxzoom', '00'));
        foreach (['23', '-1', '12.5', 'twelve'] as $bad) {
            $this->assertNotSame('', SiteSettings::Clean('map_dem_maxzoom', $bad)[1], $bad);
        }
    }

    public function testWhatSearchEnginesRead(): void
    {
        $this->assertSame(['abcDEF123_-xyz', ''], SiteSettings::Clean('google_site_verification', '<meta name="google-site-verification" content="abcDEF123_-xyz" />'));
        $this->assertSame(['0123456789ABCDEF0123456789ABCDEF', ''], SiteSettings::Clean('bing_site_verification', '<meta name="msvalidate.01" content="0123456789ABCDEF0123456789ABCDEF" />'));
        $this->assertNotSame('', SiteSettings::Clean('google_site_verification', 'not "a" code')[1]);
        $this->assertSame(['A wireless database, of networks.', ''], SiteSettings::Clean('site_description', "  A <b>wireless</b>   database,\n of networks. "));
        $this->assertNotSame('', SiteSettings::Clean('site_description', str_repeat('x', 301))[1]);
        $this->assertSame(["google.com, pub-1, DIRECT\nexample.com, 2, RESELLER", ''], SiteSettings::Clean('ads_txt', "google.com, pub-1, DIRECT\r\nexample.com, 2, RESELLER\r\n"));

        $s = $this->settings();
        $this->assertStringStartsWith('Vistumbler WiFiDB is', $s->Get('site_description'));
        $this->assertFalse($s->Get('search_noindex'));
        $this->assertSame(['ads_txt'], $s->Save(['ads_txt' => "line one\nline two"], 'root'));
        $this->assertSame("line one\nline two", $this->settings()->Get('ads_txt'), 'several lines kept as they are');
        $this->assertSame('lines', array_column($s->Describe(), null, 'name')['ads_txt']['type']);
    }

    public function testADatabaseWithoutTheTableFallsBackToConfig(): void
    {
        $bare = new SiteSettings(new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]), ['signups_open' => 0]);
        $this->assertFalse($bare->Get('signups_open'));
        $this->assertSame('config', $bare->Source('signups_open'));
        $none = new SiteSettings(null, ['smtp_host' => 'smtp.config.test']);
        $this->assertSame(['smtp_host' => 'smtp.config.test'], $none->MailConfig(['smtp_host' => 'smtp.config.test']), 'or no database at all');
    }

    public function testTheMailSettingsSavedOnTheMailPageAreLaidOverConfig(): void
    {
        $config = ['smtp_host' => 'smtp.config.test', 'smtp_port' => 587, 'smtp_pass' => 'config-pass', 'smtp_secure' => 'tls',
            'signup_smtp_host' => 'smtp.signup-config.test', 'signup_smtp_user' => 'signup-config', 'smtp_debug' => 0, 'email_validation' => 1];
        $this->assertSame($config, $this->settings($config)->MailConfig($config), 'nothing saved: config.inc.php as it is');

        $this->settings($config)->Save(['smtp_host' => 'smtp.saved.test', 'smtp_port' => '465', 'smtp_secure' => 'ssl', 'smtp_pass' => 'saved-pass',
            'signup_smtp_host' => '', 'signup_smtp_secure' => 'none', 'DKIM_private_string' => "-----BEGIN PRIVATE KEY-----\nabc\n", 'email_validation' => false,
            'smtp_oauth_token' => 'rt-google'], 'root');
        $mail = $this->settings($config)->MailConfig($config);
        $this->assertSame('smtp.saved.test', $mail['smtp_host']);
        $this->assertSame(465, $mail['smtp_port'], 'a port is a number, as PHPMailer wants');
        $this->assertSame('ssl', $mail['smtp_secure']);
        $this->assertSame('saved-pass', $mail['smtp_pass'], 'the password, decrypted');
        $this->assertArrayNotHasKey('signup_smtp_host', $mail, 'a sign-up setting saved empty is the main account\'s');
        $this->assertSame('signup-config', $mail['signup_smtp_user'], 'one not saved is still config.inc.php\'s');
        $this->assertSame('', $mail['signup_smtp_secure'], 'None, for PHPMailer');
        $this->assertSame("-----BEGIN PRIVATE KEY-----\nabc", $mail['DKIM_private_string']);
        $this->assertFalse($mail['email_validation']);
        $this->assertSame(0, $mail['smtp_debug'], 'what the page has no say in is left alone');
        $this->assertArrayNotHasKey('smtp_from_name', $mail, 'nor is a default added');
        $stored = $this->db->query("SELECT value FROM site_settings WHERE name = 'DKIM_private_string'")->fetchColumn();
        $this->assertStringStartsWith('enc:', $stored, 'the signing key is kept encrypted');
        $this->assertSame('rt-google', $mail['smtp_oauth_token'], 'a connected Google account\'s token too');
        $this->assertStringStartsWith('enc:', $this->db->query("SELECT value FROM site_settings WHERE name = 'smtp_oauth_token'")->fetchColumn());
        $this->assertNotContains('smtp_oauth_token', array_column($this->settings()->Describe(SiteSettings::MAIL_SETTINGS), 'name'), 'it isn\'t a field on the page');
    }

    public function testTheMailPageStartsFromConfig(): void
    {
        $s = array_column($this->settings(['smtp_host' => 'smtp.config.test', 'smtp_secure' => '', 'smtp_authtype' => 'LOGIN'])
            ->Describe(SiteSettings::MAIL_SETTINGS), null, 'name');
        $this->assertSame(['smtp.config.test', 'config'], [$s['smtp_host']['value'], $s['smtp_host']['source']]);
        $this->assertSame('', $s['smtp_secure']['value'], 'no encryption is one of the choices');
        $this->assertSame('LOGIN', $s['smtp_authtype']['value']);
        $this->assertSame(['wifidb', 'default'], [$s['DKIM_selector']['value'], $s['DKIM_selector']['source']]);
        $this->assertNull($s['smtp_pass']['value'], 'a password is never shown');
    }

    public function testMailValuesAreChecked(): void
    {
        $this->assertSame(['465', ''], SiteSettings::Clean('smtp_port', ' 465 '));
        $this->assertNotSame('', SiteSettings::Clean('smtp_port', '70000')[1]);
        $this->assertNotSame('', SiteSettings::Clean('smtp_port', 'abc')[1]);
        $this->assertSame(['wifidb@example.test', ''], SiteSettings::Clean('smtp_from', 'wifidb@example.test'));
        $this->assertNotSame('', SiteSettings::Clean('smtp_from', 'WiFiDB <wifidb@example.test>')[1], 'the name goes in From name');
        $this->assertNotSame('', SiteSettings::Clean('smtp_host', 'smtp.example.test extra')[1]);
        $this->assertSame(['example.test', ''], SiteSettings::Clean('DKIM_domain', 'Example.TEST.'));
        $this->assertNotSame('', SiteSettings::Clean('DKIM_domain', 'not a domain')[1]);
        $this->assertSame(['wifidb202611', ''], SiteSettings::Clean('DKIM_selector', 'wifidb202611'));
        $this->assertNotSame('', SiteSettings::Clean('DKIM_selector', 'two words')[1]);
        $this->assertNotSame('', SiteSettings::Clean('smtp_from_name', "WiFiDB\r\nBcc: x@example.test")[1], 'no header injection');
        $this->assertSame(['', ''], SiteSettings::Clean('smtp_host', '  '), 'empty is always allowed');
    }
}
