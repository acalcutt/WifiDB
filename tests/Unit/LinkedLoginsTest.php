<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/site_settings.inc.php';
require_once WIFIDB_LIB . '/linked_logins.inc.php';

// The OAuth flow against a made-up provider (the $http hook answers instead of the network), and the links.
final class LinkedLoginsTest extends TestCase
{
    private PDO $db;
    private int $now;
    private string $key;
    /** @var array the requests made, each [method, url, headers, body] */
    private array $requests = [];
    /** @var array answers by URL prefix: [status, body] */
    private array $answers = [];

    protected function setUp(): void
    {
        $this->now = 1_800_000_000;
        $this->key = Totp::GenerateKey();
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE site_settings (name TEXT PRIMARY KEY, value TEXT, updated_by TEXT, updated_at TEXT)");
        $this->db->exec("CREATE TABLE user_linked_logins (provider TEXT NOT NULL, subject TEXT NOT NULL, username TEXT NOT NULL, email TEXT, display_name TEXT,
            created TEXT NOT NULL, last_used TEXT, PRIMARY KEY (provider, subject))");
        $this->db->exec("CREATE TABLE linked_login_states (token TEXT PRIMARY KEY, data TEXT NOT NULL, expires TEXT NOT NULL)");
        $this->settings()->Save([
            'login_google_enabled' => true, 'login_google_client_id' => 'google-id', 'login_google_secret' => 'google-secret',
            'login_github_enabled' => true, 'login_github_client_id' => 'github-id', 'login_github_secret' => 'github-secret',
        ], 'root');
        $this->answers = [
            'https://oauth2.googleapis.com/token' => [200, json_encode(['access_token' => 'google-access', 'token_type' => 'Bearer'])],
            'https://openidconnect.googleapis.com/v1/userinfo' => [200, json_encode(['sub' => '1098765', 'email' => 'pat@example.com', 'name' => 'Pat Example'])],
            'https://github.com/login/oauth/access_token' => [200, json_encode(['access_token' => 'github-access'])],
            'https://api.github.com/user' => [200, json_encode(['id' => 4242, 'login' => 'patcodes', 'name' => 'Pat'])],
        ];
    }

    private function settings(): SiteSettings
    {
        return new SiteSettings($this->db, ['two_factor_key' => $this->key], fn () => $this->now);
    }

    private function logins(): LinkedLogins
    {
        return new LinkedLogins($this->db, $this->settings(), 'https://wifidb.example.com/wifidb/', fn () => $this->now,
            function ($method, $url, $headers, $body) {
                $this->requests[] = [$method, $url, $headers, $body];
                return $this->answers[$url] ?? [404, ''];
            });
    }

    private static function query(string $url): array
    {
        parse_str((string)parse_url($url, PHP_URL_QUERY), $q);
        return $q;
    }

    public function testOnlyAProviderTurnedOnWithItsIdAndSecretIsOffered(): void
    {
        $this->assertSame(['google', 'github'], $this->logins()->EnabledProviders());
        $this->settings()->Save(['login_github_secret' => ''], 'root');
        $this->settings()->Save(['login_discord_enabled' => true, 'login_discord_client_id' => 'd'], 'root');
        $this->assertSame(['google'], $this->logins()->EnabledProviders(), 'no secret: not offered');
        $this->assertFalse($this->logins()->Enabled('nonsense'));
    }

    public function testTheSiteSettingsPageSavingDoesNotTouchTheProviders(): void
    {
        // admin/settings.php saves every one of its own settings, the unchecked ones as false
        $values = [];
        foreach (SiteSettings::SETTINGS as $name => $def) { $values[$name] = $def[0] === 'bool' ? false : ($def[0] === 'text' ? '' : null); }
        $this->settings()->Save($values, 'root');
        $this->assertSame(['google', 'github'], $this->logins()->EnabledProviders());
        $names = array_column($this->settings()->Describe(SiteSettings::LOGIN_SETTINGS), 'name');
        $this->assertContains('login_google_secret', $names);
        $this->assertNotContains('login_google_secret', array_column($this->settings()->Describe(), 'name'), 'not on the Site Settings page');
    }

    public function testStartSendsTheBrowserToTheProviderWithStateAndPkce(): void
    {
        [$url, $token] = $this->logins()->Start('google', 'login', '', '/wifidb/all.php', true);
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        $q = self::query($url);
        $this->assertSame('google-id', $q['client_id']);
        $this->assertSame('https://wifidb.example.com/wifidb/logins.php?callback=google', $q['redirect_uri']);
        $this->assertSame('code', $q['response_type']);
        $this->assertSame('openid email profile', $q['scope']);
        $this->assertSame($token, $q['state']);
        $this->assertSame('S256', $q['code_challenge_method']);
        $this->assertSame('select_account', $q['prompt']);
        $this->assertArrayNotHasKey('client_secret', $q);

        $row = $this->db->query("SELECT token, data FROM linked_login_states")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(hash('sha256', $token), $row['token'], 'kept by its hash, not the token itself');
        $kept = json_decode($row['data'], true);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $kept['verifier'], true)), '+/', '-_'), '='), $q['code_challenge']);
        $this->assertSame(['/wifidb/all.php', true, 'login'], [$kept['return'], $kept['remember'], $kept['purpose']]);

        [$url] = $this->logins()->Start('github', 'login');
        $this->assertArrayNotHasKey('code_challenge', self::query($url), 'PKCE only where it is used');
    }

    public function testTheProvidersAddressesCanBePointedElsewhereForTests(): void
    {
        $fake = 'http://127.0.0.1:8099';
        $this->answers = ["$fake/token" => [200, json_encode(['access_token' => 'a'])], "$fake/userinfo" => [200, json_encode(['sub' => '9'])]];
        $l = new LinkedLogins($this->db, $this->settings(), 'https://wifidb.example.com/', fn () => $this->now,
            function ($method, $url, $headers, $body) { $this->requests[] = [$method, $url, $headers, $body]; return $this->answers[$url] ?? [404, '']; },
            ['google' => ['authorize' => "$fake/authorize", 'token' => "$fake/token", 'userinfo' => "$fake/userinfo", 'scope' => 'not this']]);
        [$url, $token] = $l->Start('google', 'login');
        $this->assertStringStartsWith("$fake/authorize?", $url);
        $this->assertSame('openid email profile', self::query($url)['scope'], 'only the addresses change');
        $this->assertSame('9', $l->Finish('google', 'c', $token, $token)[1]['subject']);
        $this->assertSame(["$fake/token", "$fake/userinfo"], array_column($this->requests, 1));
    }

    public function testAProviderThatIsOffCannotStart(): void
    {
        $this->expectException(LinkedLoginException::class);
        $this->logins()->Start('discord', 'login');
    }

    public function testFinishTradesTheCodeAndSaysWhoSignedIn(): void
    {
        [, $token] = $this->logins()->Start('google', 'link', 'alice', '/wifidb/cp/security.php');
        [$record, $identity] = $this->logins()->Finish('google', 'the-code', $token, $token);
        $this->assertSame(['link', 'alice'], [$record['purpose'], $record['username']]);
        $this->assertSame(['subject' => '1098765', 'email' => 'pat@example.com', 'name' => 'Pat Example'], $identity);

        [$method, $url, $headers, $body] = $this->requests[0];
        $this->assertSame(['POST', 'https://oauth2.googleapis.com/token'], [$method, $url]);
        parse_str($body, $form);
        $this->assertSame(['google-id', 'google-secret', 'the-code', 'authorization_code', 'https://wifidb.example.com/wifidb/logins.php?callback=google'],
            [$form['client_id'], $form['client_secret'], $form['code'], $form['grant_type'], $form['redirect_uri']]);
        $this->assertNotEmpty($form['code_verifier']);
        [$method, $url, $headers] = $this->requests[1];
        $this->assertSame('GET', $method);
        $this->assertContains('Authorization: Bearer google-access', $headers);
        $this->assertContains('User-Agent: WifiDB', $headers);
    }

    public function testGitHubsNumericIdAndLogin(): void
    {
        [, $token] = $this->logins()->Start('github', 'login');
        [, $identity] = $this->logins()->Finish('github', 'c', $token, $token);
        $this->assertSame(['subject' => '4242', 'email' => null, 'name' => 'patcodes'], $identity);
    }

    public function testAStateFromAnotherBrowserIsRefused(): void
    {
        [, $token] = $this->logins()->Start('google', 'login');
        try {
            $this->logins()->Finish('google', 'c', $token, 'someone-elses-cookie');
            $this->fail('a state that does not match the cookie must be refused');
        } catch (LinkedLoginException $e) {
            $this->assertStringContainsString("didn't start in this browser", $e->getMessage());
        }
        $this->assertSame([], $this->requests, 'nothing was sent to the provider');
    }

    public function testAStateWorksOnceAndNotAfterItsTime(): void
    {
        [, $token] = $this->logins()->Start('google', 'login');
        $this->logins()->Finish('google', 'c', $token, $token);
        try {
            $this->logins()->Finish('google', 'c', $token, $token);
            $this->fail('a used state must be refused');
        } catch (LinkedLoginException $e) {
            $this->assertStringContainsString('took too long', $e->getMessage());
        }

        [, $token] = $this->logins()->Start('google', 'login');
        $this->now += LinkedLogins::STATE_TTL + 1;
        $this->expectExceptionMessage('took too long');
        $this->logins()->Finish('google', 'c', $token, $token);
    }

    public function testAStateIsOnlyForTheProviderItStartedWith(): void
    {
        [, $token] = $this->logins()->Start('google', 'login');
        $this->expectExceptionMessage('took too long');
        $this->logins()->Finish('github', 'c', $token, $token);
    }

    public function testSayingNoAtTheProviderCancels(): void
    {
        [, $token] = $this->logins()->Start('google', 'login');
        $this->expectExceptionMessage('cancelled');
        $this->logins()->Finish('google', '', $token, $token, 'access_denied');
    }

    public function testACodeTheProviderRefusesIsAnError(): void
    {
        $this->answers['https://github.com/login/oauth/access_token'] = [200, json_encode(['error' => 'bad_verification_code'])];
        [, $token] = $this->logins()->Start('github', 'login');
        try {
            $this->logins()->Finish('github', 'c', $token, $token);
            $this->fail('no access token must be an error');
        } catch (LinkedLoginException $e) {
            $this->assertSame("GitHub didn't accept the sign-in. Please try again.", $e->getMessage());
            $this->assertStringContainsString('bad_verification_code', $e->detail, 'what the provider said goes to the log');
        }
        $this->assertCount(1, $this->requests, 'nobody was asked who signed in');
    }

    public function testOldStatesAreClearedAway(): void
    {
        $this->logins()->Start('google', 'login');
        $this->now += LinkedLogins::STATE_TTL + 1;
        $this->logins()->Start('google', 'login');
        $this->assertSame(1, (int)$this->db->query("SELECT COUNT(*) FROM linked_login_states")->fetchColumn());
    }

    public function testLinking(): void
    {
        $l = $this->logins();
        $pat = ['subject' => '1098765', 'email' => 'pat@example.com', 'name' => 'Pat Example'];
        $this->assertNull($l->UsernameFor('google', '1098765'));
        $l->Link('alice', 'google', $pat);
        $this->assertSame('alice', $l->UsernameFor('google', '1098765'));
        $l->Link('alice', 'google', $pat);   // the same again: nothing changes
        $this->assertCount(1, $l->LinksFor('alice'));
        $this->assertSame(['google', 'pat@example.com', 'Pat Example', gmdate('Y-m-d H:i:s', $this->now), null],
            array_values(array_intersect_key($l->LinksFor('alice')[0], array_flip(['provider', 'email', 'display_name', 'created', 'last_used']))));

        try {
            $l->Link('bob', 'google', $pat);
            $this->fail('one outside account, one member');
        } catch (LinkedLoginException $e) {
            $this->assertSame('That Google account is already linked to another account here.', $e->getMessage());
        }
        try {
            $l->Link('alice', 'google', ['subject' => '555', 'email' => null, 'name' => null]);
            $this->fail('one account from each provider');
        } catch (LinkedLoginException $e) {
            $this->assertStringContainsString('Unlink it first', $e->getMessage());
        }

        $this->now += 60;
        $l->Touch('google', '1098765');
        $this->assertSame(gmdate('Y-m-d H:i:s', $this->now), $l->LinksFor('alice')[0]['last_used']);
        $this->assertFalse($l->Unlink('bob', 'google'), 'only the member it is linked to can unlink it');
        $this->assertTrue($l->Unlink('alice', 'google'));
        $this->assertNull($l->UsernameFor('google', '1098765'));
    }

    public function testNothingIsMatchedByEmailAddress(): void
    {
        $l = $this->logins();
        $l->Link('alice', 'google', ['subject' => '1', 'email' => 'shared@example.com', 'name' => null]);
        $this->assertNull($l->UsernameFor('github', '2'), 'another provider with the same address finds nobody');
        $this->assertNull($l->UsernameFor('google', 'shared@example.com'));
    }

    public function testAnUnlinkedSignInIsKeptForThisBrowserUntilItIsLinked(): void
    {
        $l = $this->logins();
        $identity = ['subject' => '77', 'email' => null, 'name' => 'patcodes'];
        $token = $l->HoldUnlinked(['return' => '/wifidb/', 'remember' => true], 'github', $identity);
        $this->assertNull($l->Unlinked($token, 'other-cookie'));
        $held = $l->Unlinked($token, $token);
        $this->assertSame(['github', $identity, '/wifidb/', true, ''], [$held['provider'], $held['identity'], $held['return'], $held['remember'], $held['username']]);

        // the password was right, the second factor comes next: who it was is kept
        $this->now += LinkedLogins::STATE_TTL - 10;
        $l->UnlinkedProved($token, $held, 'alice');
        $this->now += 30;
        $this->assertSame('alice', $l->Unlinked($token, $token)['username'], 'and given its time again');

        $l->UnlinkedDone($token);
        $this->assertNull($l->Unlinked($token, $token));
    }
}
