<?php
use PHPUnit\Framework\TestCase;

// The site's version (lib/version.inc.php, shown in the footer) is the repository's VERSION: build/bump_version.py
// writes both, and this catches one changed without the other.
final class VersionTest extends TestCase
{
    public function testTheSiteShowsTheVersionInVERSION(): void
    {
        $version = trim(file_get_contents(dirname(__DIR__, 2) . '/VERSION'));
        $this->assertMatchesRegularExpression('/^\d+\.\d+\.\d+(-[0-9A-Za-z.]+)?$/', $version);
        $this->assertSame($version, require WIFIDB_LIB . '/version.inc.php');
    }
}
