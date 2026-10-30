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

    private function mailer(array $override = []): wdbmail
    {
        if (!class_exists('security', false)) { eval('class security { function __construct($a = null, $b = null) {} }'); }
        if (!class_exists('SQL', false)) { eval('class SQL { function __construct($a = null) {} }'); }
        require_once WIFIDB_WEB . '/../tools/daemon/lib/wdbmail.inc.php';
        return new wdbmail(array_replace([
            'hosturl' => 'https://wifidb.example', 'root' => '', 'wifidb_email_updates' => 1, 'email_validation' => 1,
            'smtp_debug' => 0, 'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_user' => 'main-user', 'smtp_pass' => 'x',
            'smtp_from' => 'wifidb@example.test', 'smtp_replyto' => '', 'smtp_secure' => 'tls', 'smtp_auth' => true,
            'smtp_authtype' => 'LOGIN', 'smtp_options' => [],
            'DKIM_domain' => 'example.test', 'DKIM_private' => $this->keyFile, 'DKIM_selector' => 'wifidb', 'DKIM_passphrase' => '',
            'DKIM_identity' => 'wifidb@example.test', 'DKIM_copyHeaderFields' => true,
            'ListUnsubscribe' => '<postmaster@example.test>', 'XMailer' => 'WifiDB',
        ], $override), new stdClass());
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
}
