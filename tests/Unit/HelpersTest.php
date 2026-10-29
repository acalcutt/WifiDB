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
}
