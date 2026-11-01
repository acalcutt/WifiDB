<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HelpersTest extends TestCase
{
    public static function sanitizeCases(): array
    {
        return [
            'plain text is unchanged'   => ['Home Network', 'Home Network'],
            'tags are stripped'         => ['<script>alert(1)</script>ok', 'alert(1)ok'],
            'single quote is encoded'   => ["it's", 'it&#39;s'],
            'double quote is encoded'   => ['say "hi"', 'say &#34;hi&#34;'],
            'quotes in attributes'      => ['<a href="x">y</a>', 'y'],
            'null becomes empty string' => [null, ''],
        ];
    }

    #[DataProvider('sanitizeCases')]
    public function testSanitizeString($input, string $expected): void
    {
        $this->assertSame($expected, wifidb_sanitize_string($input));
    }

    public function testResolveThemeAcceptsExistingFolder(): void
    {
        $this->assertSame('vistumbler_classic', wifidb_resolve_theme('vistumbler_classic', WIFIDB_WEB . '/', 'default'));
    }

    public static function badThemes(): array
    {
        return [
            'parent traversal' => ['../../etc'],
            'nested path'      => ['vistumbler_classic/templates'],
            'absolute path'    => ['/etc/passwd'],
            'missing folder'   => ['no_such_theme'],
            'empty'            => [''],
            'null'             => [null],
            'array'            => [['vistumbler_classic']],
            'a file, not dir'  => ['index.php'],
        ];
    }

    #[DataProvider('badThemes')]
    public function testResolveThemeFallsBackToDefault($requested): void
    {
        $this->assertSame('default', wifidb_resolve_theme($requested, WIFIDB_WEB . '/', 'default'));
    }

    // ── where to send someone back to ──

    public static function goodReturns(): array
    {
        return [
            'a path'                  => ['/wifidb/cp/index.php?func=myimports'],
            'the site root'           => ['/'],
            'a path with a query'     => ['/opt/scheduling.php?func=schedule&x=1'],
            'an address on this site' => ['https://wifidb.example.com/opt/map.php?id=3'],
            'this site, other case'   => ['HTTPS://WifiDB.Example.com/'],
            'with spaces around'      => ['  /cp/index.php  '],
        ];
    }

    #[DataProvider('goodReturns')]
    public function testSafeReturnKeepsAddressesOnThisSite(string $value): void
    {
        $this->assertSame(trim($value), wifidb_safe_return($value, 'wifidb.example.com', '/fallback'));
    }

    public static function badReturns(): array
    {
        return [
            'another site'             => ['https://evil.example.net/'],
            'a lookalike host'         => ['https://wifidb.example.com.evil.net/'],
            'userinfo trick'           => ['https://wifidb.example.com@evil.net/'],
            'protocol-relative'        => ['//evil.example.net/x'],
            'javascript'               => ['javascript:alert(1)'],
            'javascript, spaced'       => ["  JaVaScRiPt:alert(1)"],
            'data'                     => ['data:text/html,<script>alert(1)</script>'],
            'a backslash'              => ['/\evil.example.net'],
            'a backslash in an address' => ['https://wifidb.example.com\@evil.net'],
            'a line break'             => ["/ok\r\nLocation: https://evil.example.net"],
            'a null byte'              => ["/ok\0"],
            'no leading slash'         => ['evil.example.net/path'],
            'ftp'                      => ['ftp://wifidb.example.com/'],
            'empty'                    => [''],
            'only spaces'              => ['   '],
            'not a string'             => [['/ok']],
        ];
    }

    #[DataProvider('badReturns')]
    public function testSafeReturnRefusesAnythingElse($value): void
    {
        $this->assertSame('/fallback', wifidb_safe_return($value, 'wifidb.example.com', '/fallback'));
    }

    public function testSafeReturnWithNoSiteHostOnlyTrustsPaths(): void
    {
        $this->assertSame('/ok', wifidb_safe_return('/ok', '', '/fallback'));
        $this->assertSame('/fallback', wifidb_safe_return('https://wifidb.example.com/', '', '/fallback'));
    }

    // ── values written into an inline script ──

    public static function scriptBreakers(): array
    {
        return [
            'a quote'          => ["x'; alert(1); //"],
            'a double quote'   => ['x"; alert(1); //'],
            'a script end tag' => ['x</script><script>alert(1)</script>'],
            'an html comment'  => ['<!-- x'],
            'a line break'     => ["x\nalert(1)"],
            'an ampersand'     => ['a&b'],
            'a backslash'      => ['x' . chr(92)],
        ];
    }

    #[DataProvider('scriptBreakers')]
    public function testJsStringCannotBreakOutOfAScript(string $value): void
    {
        $js = wifidb_js_string($value);
        $this->assertStringStartsWith('"', $js);
        $this->assertStringEndsWith('"', $js);
        $this->assertStringNotContainsString("'", $js);
        $this->assertStringNotContainsString('<', $js);
        $this->assertStringNotContainsString('>', $js);
        $this->assertStringNotContainsString("\n", $js);
        $this->assertSame($value, json_decode($js), 'it still means the same string to JavaScript');
    }

    public function testJsStringKeepsSlashesReadable(): void
    {
        $this->assertSame('"/wifidb/cp/index.php"', wifidb_js_string('/wifidb/cp/index.php'));
    }

    public function testSqlServerIsEncryptedAndTrustsItsCertificateUnlessToldOtherwise(): void
    {
        $this->assertSame(';Encrypt=true;TrustServerCertificate=true', wifidb_sqlsrv_security([]), 'as it always was');
        $this->assertSame(';Encrypt=true;TrustServerCertificate=false', wifidb_sqlsrv_security(['sqlsrv_trust_server_certificate' => false]));
        $this->assertSame(';Encrypt=true;TrustServerCertificate=false', wifidb_sqlsrv_security(['sqlsrv_trust_server_certificate' => '0']));
        $this->assertSame(';Encrypt=false;TrustServerCertificate=true', wifidb_sqlsrv_security(['sqlsrv_encrypt' => 'no']));
        $this->assertSame(';Encrypt=true;TrustServerCertificate=true', wifidb_sqlsrv_security(['sqlsrv_trust_server_certificate' => 'maybe']), "a value it can't read leaves it as it was");
    }
}
