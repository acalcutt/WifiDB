<?php
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../Support/DkimVerifier.php';

// Builds the real wdbmail class (its two database classes replaced by empty stand-ins, so this needs no database) and
// signs one message from each mailer. Each test runs in its own process because of those stand-ins.
final class WdbmailDkimTest extends TestCase
{
    private string $dir;
    private string $keyFile;
    private string $publicKey;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/wdbmail-' . getmypid() . '-' . uniqid();
        mkdir($this->dir);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $pem);
        $this->keyFile = $this->dir . '/dkim.pem';
        file_put_contents($this->keyFile, $pem);
        $this->publicKey = openssl_pkey_get_details($key)['key'];
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) { @unlink($f); }
        @rmdir($this->dir);
    }

    private function config(array $override = []): array
    {
        return array_replace([
            'hosturl' => 'https://wifidb.example', 'root' => '', 'wifidb_email_updates' => 1, 'email_validation' => 1,
            'smtp_debug' => 0, 'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_user' => 'main-user', 'smtp_pass' => 'x',
            'smtp_from' => 'wifidb@example.test', 'smtp_replyto' => '', 'smtp_secure' => 'tls', 'smtp_auth' => true,
            'smtp_authtype' => 'LOGIN', 'smtp_options' => [],
            'DKIM_domain' => 'example.test', 'DKIM_private' => $this->keyFile, 'DKIM_selector' => 'wifidb', 'DKIM_passphrase' => '',
            'DKIM_identity' => 'wifidb@example.test', 'DKIM_copyHeaderFields' => true,
            'ListUnsubscribe' => '<postmaster@example.test>', 'XMailer' => 'WifiDB', 'two_factor_key' => base64_encode(str_repeat('k', 32)),
        ], $override);
    }

    /** @param PDO|null $conn a database with site_settings, for what the Mail page saved */
    private function mailer(array $override = [], ?PDO $conn = null): wdbmail
    {
        if (!class_exists('security', false)) { eval('class security { function __construct($a = null, $b = null) {} }'); }
        if (!class_exists('SQL', false)) { eval('class SQL { function __construct($a = null) {} }'); }
        require_once WIFIDB_WEB . '/../tools/daemon/lib/wdbmail.inc.php';
        $sql = new stdClass();
        if ($conn !== null) { $sql->conn = $conn; }
        return new wdbmail($this->config($override), $sql);
    }

    private function saved(array $values): PDO
    {
        require_once WIFIDB_LIB . '/site_settings.inc.php';
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE TABLE site_settings (name TEXT PRIMARY KEY, value TEXT, updated_by TEXT, updated_at TEXT)");
        (new SiteSettings($db, $this->config()))->Save($values, 'root');
        return $db;
    }

    private function raw(PHPMailer\PHPMailer\PHPMailer $mailer, string $body = "Please validate: https://wifidb.example/login.php?validate_code=abc123\r\n"): string
    {
        $mailer->addAddress('someone@example.net');
        $mailer->Subject = 'Welcome to WifiDB';
        $mailer->Body = $body;
        $mailer->preSend();
        return $mailer->getSentMIMEMessage();
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testBothMailersSignAndTheSignaturesVerify(): void
    {
        $m = $this->mailer();
        foreach (['mail' => $m->mail, 'signup mail' => $m->signupmail] as $which => $mailer) {
            $raw = $this->raw($mailer);
            $tags = DkimVerifier::Tags($raw);
            $this->assertSame('example.test', $tags['d'] ?? null, "$which is signed for the domain");
            $this->assertSame('wifidb', $tags['s'] ?? null);
            $this->assertSame('rsa-sha256', $tags['a'] ?? null);
            $this->assertSame([true, 'ok'], DkimVerifier::Verify($raw, $this->publicKey), "$which verifies");
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testTheVerifierIsNotJustSayingYes(): void
    {
        $raw = $this->raw($this->mailer()->mail);
        $this->assertSame([false, 'body hash does not match'], DkimVerifier::Verify(str_replace('abc123', 'evil99', $raw), $this->publicKey), 'a changed body');
        $this->assertFalse(DkimVerifier::Verify(str_replace('Subject: Welcome', 'Subject: Hacked', $raw), $this->publicKey)[0], 'a changed signed header');
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        $this->assertSame([false, 'signature does not verify'], DkimVerifier::Verify($raw, openssl_pkey_get_details($other)['key']), "someone else's key");
        $this->assertSame([false, 'no DKIM-Signature header'], DkimVerifier::Verify("From: a@b.c\r\n\r\nhello\r\n", $this->publicKey));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAMissingKeyFileMeansUnsignedMailWithNoError(): void
    {
        // the trap docs/email.md and check_mail.php warn about
        $m = $this->mailer(['DKIM_private' => $this->dir . '/no-such-key.pem']);
        foreach ([$m->mail, $m->signupmail] as $mailer) {
            $this->assertStringNotContainsString('DKIM-Signature', $this->raw($mailer));
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testSignupMailUsesTheMainAccountUnlessItHasItsOwn(): void
    {
        $m = $this->mailer();
        $this->assertSame('smtp.example.test', $m->signupmail->Host);
        $this->assertSame('main-user', $m->signupmail->Username);

        $s = $this->mailer(['signup_smtp_host' => 'smtp.signup.test', 'signup_smtp_port' => 465, 'signup_smtp_user' => 'signup-user',
            'signup_smtp_pass' => 'y', 'signup_smtp_from' => 'signup@example.test', 'signup_smtp_replyto' => '', 'signup_smtp_secure' => 'ssl',
            'signup_smtp_auth' => true, 'signup_smtp_authtype' => 'LOGIN', 'signup_smtp_options' => [], 'signup_smtp_debug' => 0]);
        $this->assertSame('smtp.signup.test', $s->signupmail->Host);
        $this->assertSame('signup-user', $s->signupmail->Username);
        $this->assertSame('smtp.example.test', $s->mail->Host, 'the main account is untouched');
        $this->assertSame([true, 'ok'], DkimVerifier::Verify($this->raw($s->signupmail), $this->publicKey), 'and its mail is signed all the same');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testWhatTheMailPageSavedWins(): void
    {
        $m = $this->mailer([], $this->saved(['smtp_host' => 'smtp.saved.test', 'smtp_port' => '2525', 'smtp_pass' => 'saved-pass',
            'smtp_from_name' => 'WiFiDB', 'signup_smtp_user' => 'signup-saved', 'smtp_authtype' => '']));
        $this->assertSame('smtp.saved.test', $m->mail->Host);
        $this->assertSame(2525, $m->mail->Port);
        $this->assertSame('saved-pass', $m->mail->Password);
        $this->assertSame('', $m->mail->AuthType, 'the best the server offers');
        $this->assertSame('main-user', $m->mail->Username, 'what wasn\'t saved is config.inc.php\'s');
        $this->assertStringContainsString('From: WiFiDB <wifidb@example.test>', $this->raw($m->mail));
        $this->assertSame('smtp.saved.test', $m->signupmail->Host, 'the sign-up account takes the main one\'s server');
        $this->assertSame('signup-saved', $m->signupmail->Username, '...but its own username');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAKeySavedOnTheMailPageSignsWithNoFile(): void
    {
        $m = $this->mailer(['DKIM_private' => '', 'DKIM_passphrase' => 'for the file'],
            $this->saved(['DKIM_private_string' => file_get_contents($this->keyFile)]));
        foreach (['mail' => $m->mail, 'signup mail' => $m->signupmail] as $which => $mailer) {
            $this->assertSame([true, 'ok'], DkimVerifier::Verify($this->raw($mailer), $this->publicKey), "$which verifies");
        }
    }

    /** The stand-in mail server (proxmox/test-smtp-sink.php), on a free port. @return array (the process, its port) */
    private function sink(): array
    {
        $sink = proc_open([PHP_BINARY, dirname(__DIR__, 2) . '/proxmox/test-smtp-sink.php', '0', $this->dir . '/sink'],
            [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
        $port = (int)fgets($pipes[1]);
        $this->assertGreaterThan(0, $port, 'the stand-in mail server started');
        return [$sink, $port];
    }

    private function stopSink($sink): void
    {
        proc_terminate($sink);
        proc_close($sink);
        foreach (glob($this->dir . '/sink/*') ?: [] as $f) { @unlink($f); }
        @rmdir($this->dir . '/sink');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAGoogleAccountSignsInWithItsAccessTokenNotAPassword(): void
    {
        $m = $this->mailer([], $this->saved(['smtp_authtype' => 'XOAUTH2', 'smtp_user' => 'me@gmail.test', 'smtp_oauth_client_id' => 'client-1',
            'smtp_oauth_secret' => 'secret-1', 'smtp_oauth_token' => 'rt-main', 'signup_smtp_user' => 'signup@gmail.test', 'signup_smtp_oauth_token' => 'rt-signup']));
        $this->assertSame('XOAUTH2', $m->mail->AuthType);
        $this->assertInstanceOf(MailOAuth::class, $m->mail->getOAuth());
        $this->assertSame('me@gmail.test', $m->mail->getOAuth()->User());
        $this->assertSame('XOAUTH2', $m->signupmail->AuthType);
        $this->assertSame('signup@gmail.test', $m->signupmail->getOAuth()->User(), 'the sign-up account as its own Google account');

        [$sink, $port] = $this->sink();
        try {
            foreach (['at-good' => [200, json_encode(['access_token' => 'at-good'])],
                      'revoked' => [400, json_encode(['error' => 'invalid_grant'])]] as $case => $answer) {
                $mail = wdbmail::Mailer($this->config(['smtp_host' => '127.0.0.1', 'smtp_port' => $port, 'smtp_secure' => '', 'smtp_authtype' => 'XOAUTH2',
                    'smtp_user' => 'me@gmail.test']));
                $mail->setOAuth(new MailOAuth('me@gmail.test', 'client-1', 'secret-1', 'rt-main', [], fn () => $answer));
                $mail->addAddress('someone@example.net');
                $mail->Subject = 'hello';
                $mail->Body = 'hello';
                if ($case === 'at-good') {
                    $this->assertTrue($mail->send(), $mail->ErrorInfo);
                    $this->assertSame("user=me@gmail.test auth=Bearer at-good  \n", file_get_contents($this->dir . '/sink.auth'), 'signed in with the access token');
                } else {
                    $this->assertFalse($mail->send());
                    $this->assertStringContainsString('invalid_grant', wdbmail::Why($mail), "Google's reason, not only PHPMailer's");
                    $this->assertStringContainsString('connect the account again', wdbmail::Why($mail));
                }
            }
        } finally {
            $this->stopSink($sink);
            @unlink($this->dir . '/sink.auth');
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testATestMessageGoesThroughWithTheLoginLeftOutOfWhatIsShown(): void
    {
        $this->mailer();   # loads wdbmail
        [$sink, $port] = $this->sink();
        try {
            [$sent, $error, $lines] = wdbmail::Test($this->config(['smtp_host' => '127.0.0.1', 'smtp_port' => $port, 'smtp_secure' => '',
                'smtp_user' => 'secret-user', 'smtp_pass' => 'secret-pass']), 'someone@example.net');
            $this->assertTrue($sent, $error);
            $said = implode("\n", $lines);
            $this->assertStringContainsString('AUTH LOGIN (the login is left out)', $said);
            $this->assertStringContainsString('RCPT TO:<someone@example.net>', $said);
            $this->assertStringContainsString('250 OK queued', $said);
            foreach (['secret-user', 'secret-pass'] as $secret) {
                $this->assertStringNotContainsString(base64_encode($secret), $said);
                $this->assertStringNotContainsString($secret, $said);
            }
            $this->assertStringNotContainsString('This is a test message', $said, 'nor the message');
            $received = file_get_contents($this->dir . '/sink/001.eml');
            $this->assertStringContainsString('Subject: WiFiDB mail test', $received);
            $this->assertSame([true, 'ok'], DkimVerifier::Verify($received, $this->publicKey), 'signed, as the site\'s mail is');
        } finally {
            $this->stopSink($sink);
        }

        [$sent, $error] = wdbmail::Test($this->config(['smtp_host' => '127.0.0.1', 'smtp_port' => 1, 'smtp_secure' => '']), 'someone@example.net');
        $this->assertFalse($sent, 'nothing listening');
        $this->assertNotSame('', $error, 'and it says why');
    }
}
