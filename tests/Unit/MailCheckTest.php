<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/mailcheck.inc.php';

final class MailCheckTest extends TestCase
{
    private string $dir;
    private string $pem;
    private string $keyFile;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/mailcheck-' . getmypid() . '-' . uniqid();
        mkdir($this->dir);
        $key = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($key, $exported);
        $this->pem = $exported;
        $this->keyFile = $this->dir . '/dkim.pem';
        file_put_contents($this->keyFile, $this->pem);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) { @unlink($f); }
        @rmdir($this->dir);
    }

    private function config(array $override = []): array
    {
        return array_replace([
            'email_validation' => 1,
            'smtp_host' => 'smtp.mail.example', 'smtp_user' => 'wifidb@example.test', 'smtp_pass' => 'a-real-password', 'smtp_from' => 'wifidb@example.test',
            'DKIM_domain' => 'example.test', 'DKIM_selector' => 'wifidb', 'DKIM_private' => $this->keyFile, 'DKIM_passphrase' => '',
            'DKIM_identity' => 'wifidb@example.test',
        ], $override);
    }

    /** a DNS stand-in: name => TXT strings */
    private function dns(array $records): callable
    {
        return fn ($name) => $records[$name] ?? [];
    }

    private function goodDns(): array
    {
        return [
            'wifidb._domainkey.example.test' => [MailCheck::DnsRecord($this->pem)],
            'example.test' => ['v=spf1 include:_spf.mail.example ~all'],
            '_dmarc.example.test' => ['v=DMARC1; p=none'],
        ];
    }

    private function statuses(array $results): array { return array_map(fn ($r) => $r[0], $results); }

    private function text(array $results): string { return implode("\n", array_map(fn ($r) => $r[1], $results)); }

    // ── the pieces ──

    public function testThePublicKeyAndTheRecordComeFromThePrivateKey(): void
    {
        $p = MailCheck::PublicKeyFromPrivate($this->pem);
        $this->assertMatchesRegularExpression('/^[A-Za-z0-9+\/=]{300,}$/', $p, 'one line of base64, no PEM markers');
        $this->assertSame('v=DKIM1; k=rsa; p=' . $p, MailCheck::DnsRecord($this->pem));
        $this->assertSame(2048, MailCheck::KeyBits($this->pem));
        $this->assertNull(MailCheck::PublicKeyFromPrivate('not a key'));
        $this->assertNull(MailCheck::DnsRecord(''));
    }

    public function testRecordsAreParsedWhateverTheSpacing(): void
    {
        $this->assertSame(['v' => 'DKIM1', 'k' => 'rsa', 'p' => 'MIGfMA0GCSq'], MailCheck::ParseRecord("v=DKIM1;  k=rsa; p=MIGf MA0G\n CSq ;"));
        $this->assertSame([], MailCheck::ParseRecord(''));
    }

    public static function addresses(): array
    {
        return [['wifidb@example.test', 'example.test'], ['WifiDB <Wifi@Mail.Example.TEST>', 'mail.example.test'], ['no at sign', ''], ['', '']];
    }

    #[DataProvider('addresses')]
    public function testTheDomainOfAnAddress(string $address, string $domain): void
    {
        $this->assertSame($domain, MailCheck::DomainOf($address));
    }

    public function testAlignment(): void
    {
        $this->assertTrue(MailCheck::Aligned('example.test', 'example.test'));
        $this->assertTrue(MailCheck::Aligned('mail.example.test', 'example.test'));
        $this->assertFalse(MailCheck::Aligned('evilexample.test', 'example.test'), 'a longer name that merely ends the same');
        $this->assertFalse(MailCheck::Aligned('example.test', 'mail.example.test'));
        $this->assertFalse(MailCheck::Aligned('', 'example.test'));
    }

    // ── the whole check ──

    public function testAGoodSetupIsAllGreen(): void
    {
        $results = MailCheck::Check($this->config(), $this->dns($this->goodDns()));
        $this->assertNotContains('fail', $this->statuses($results), $this->text($results));
        $this->assertNotContains('warn', $this->statuses($results), $this->text($results));
        $this->assertStringContainsString('matches the key', $this->text($results));
    }

    public function testNoRecordInDnsIsAFailureThatSaysWhatToPublish(): void
    {
        $dns = $this->goodDns();
        unset($dns['wifidb._domainkey.example.test']);
        $results = MailCheck::Check($this->config(), $this->dns($dns));
        $this->assertContains('fail', $this->statuses($results));
        $this->assertStringContainsString('No DKIM record at wifidb._domainkey.example.test', $this->text($results));
        $this->assertStringContainsString(MailCheck::DnsRecord($this->pem), $this->text($results), 'it prints the record to publish');
    }

    public function testARecordForADifferentKeyIsAFailure(): void
    {
        $other = openssl_pkey_new(['private_key_bits' => 2048, 'private_key_type' => OPENSSL_KEYTYPE_RSA]);
        openssl_pkey_export($other, $otherPem);
        $dns = $this->goodDns();
        $dns['wifidb._domainkey.example.test'] = [MailCheck::DnsRecord($otherPem)];
        $results = MailCheck::Check($this->config(), $this->dns($dns));
        $this->assertStringContainsString('different key than the file', $this->text($results));
        $this->assertContains('fail', $this->statuses($results));
    }

    public function testARevokedKeyAndATestingFlag(): void
    {
        $dns = $this->goodDns();
        $dns['wifidb._domainkey.example.test'] = ['v=DKIM1; k=rsa; p='];
        $this->assertStringContainsString('revoked', $this->text(MailCheck::Check($this->config(), $this->dns($dns))));
        $dns['wifidb._domainkey.example.test'] = [MailCheck::DnsRecord($this->pem) . '; t=y'];
        $results = MailCheck::Check($this->config(), $this->dns($dns));
        $this->assertStringContainsString('t=y (testing)', $this->text($results));
        $this->assertNotContains('fail', $this->statuses($results));
    }

    public function testAMissingKeyFileIsAFailureBecauseMailGoesOutUnsigned(): void
    {
        $results = MailCheck::Check($this->config(['DKIM_private' => $this->dir . '/typo.pem']), $this->dns($this->goodDns()));
        $this->assertStringContainsString('sends the mail UNSIGNED', $this->text($results));
        $this->assertContains('fail', $this->statuses($results));
    }

    public function testARelativeKeyPathIsAWarning(): void
    {
        $cwd = getcwd();
        chdir($this->dir);
        try {
            $results = MailCheck::Check($this->config(['DKIM_private' => 'dkim.pem']), $this->dns($this->goodDns()));
        } finally { chdir($cwd); }
        $this->assertStringContainsString('relative path "dkim.pem"', $this->text($results));
    }

    public function testAFileThatIsNotAKeyIsAFailure(): void
    {
        file_put_contents($this->dir . '/junk.pem', 'hello');
        $results = MailCheck::Check($this->config(['DKIM_private' => $this->dir . '/junk.pem']), $this->dns($this->goodDns()));
        $this->assertStringContainsString('not a valid private key', $this->text($results));
    }

    public function testAnIdentityOutsideTheSigningDomainIsAFailure(): void
    {
        $results = MailCheck::Check($this->config(['DKIM_identity' => 'wifidb@other.test']), $this->dns($this->goodDns()));
        $this->assertStringContainsString('DKIM_identity (other.test) is not under DKIM_domain', $this->text($results));
    }

    public function testAFromAddressOutsideTheSigningDomainIsAWarning(): void
    {
        // the case of a Gmail or Workspace address: the signature is fine but does not count for that mail
        $results = MailCheck::Check($this->config(['smtp_from' => 'someone@gmail.com']), $this->dns($this->goodDns()));
        $this->assertStringContainsString('smtp_from (gmail.com) is not under the DKIM domain', $this->text($results));
    }

    public function testTheExampleValuesAreCaughtAndSayWhySignUpBreaks(): void
    {
        $results = MailCheck::Check(['email_validation' => 1, 'smtp_host' => 'smtp.someemail.com', 'smtp_user' => 'wifidb@someemail.com',
            'smtp_pass' => 'supersecretpw', 'smtp_from' => 'wifidb@someemail.com', 'DKIM_domain' => 'someemail.com', 'DKIM_selector' => 'wdb',
            'DKIM_private' => 'private.pem'], $this->dns([]));
        $text = $this->text($results);
        $this->assertStringContainsString('smtp_host is empty or still the example value', $text);
        $this->assertStringContainsString('nobody can finish signing up', $text);
        $this->assertStringContainsString('DKIM signing is off', $text, 'the example DKIM values count as off, not as a broken key');
    }

    public function testDkimOffIsOnlyAWarning(): void
    {
        $results = MailCheck::Check($this->config(['DKIM_domain' => '', 'DKIM_private' => '']), $this->dns($this->goodDns()));
        $this->assertStringContainsString('DKIM signing is off', $this->text($results));
        $this->assertNotContains('fail', $this->statuses($results));
    }

    public function testASeparateSignupAccountIsCheckedToo(): void
    {
        $results = MailCheck::Check($this->config(['signup_smtp_host' => 'smtp.someemail.com', 'signup_smtp_user' => 'a@b.c', 'signup_smtp_pass' => 'x', 'signup_smtp_from' => 'a@b.c']), $this->dns($this->goodDns()));
        $this->assertStringContainsString('Sign-up mail signup_smtp_host is empty or still the example value', $this->text($results));
        $plain = MailCheck::Check($this->config(), $this->dns($this->goodDns()));
        $this->assertStringContainsString('Sign-up mail uses the main SMTP account', $this->text($plain));
    }

    public function testMissingSpfAndDmarcAreWarnings(): void
    {
        $dns = $this->goodDns();
        unset($dns['example.test'], $dns['_dmarc.example.test']);
        $text = $this->text(MailCheck::Check($this->config(), $this->dns($dns)));
        $this->assertStringContainsString('has no SPF record', $text);
        $this->assertStringContainsString('has no DMARC record', $text);
    }
}
