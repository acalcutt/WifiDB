<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/releases.inc.php';

// Reading the release lists GitLab and GitHub give, and checking a downloaded package against SHA256SUMS.
final class ReleasesTest extends TestCase
{
    private static function link(string $name, string $tag): array
    {
        return ['name' => $name, 'url' => "https://git.example/api/v4/projects/7/packages/generic/wifidb/x/$name",
            'direct_asset_url' => "https://git.example/o/WifiDB/-/releases/$tag/downloads/$name"];
    }

    public function testGitLabReleasesWithAnUpdatePackageNewestFirst(): void
    {
        $data = [
            ['tag_name' => 'v0.40.0', 'released_at' => '2026-10-01T00:00:00Z',
                'assets' => ['links' => [self::link('WifiDB-v0.40.0-proxmox-amd64.tar.zst', 'v0.40.0')]]],   // before update packages
            ['tag_name' => 'v0.42.0-rc.1', 'released_at' => '2026-12-01T00:00:00Z',
                'assets' => ['links' => [self::link('WifiDB-v0.42.0-rc.1-update.tar.gz', 'v0.42.0-rc.1'), self::link('SHA256SUMS', 'v0.42.0-rc.1')]]],
            ['tag_name' => 'v0.41.0', 'released_at' => '2026-11-10T00:00:00Z',
                'assets' => ['links' => [self::link('WifiDB-v0.41.0-proxmox-amd64.tar.zst', 'v0.41.0'),
                    self::link('WifiDB-v0.41.0-update.tar.gz', 'v0.41.0'), self::link('SHA256SUMS', 'v0.41.0')]]],
            ['tag_name' => 'v0.43.0', 'upcoming_release' => true, 'assets' => ['links' => []]],
        ];
        $list = WifidbReleases::ParseGitLab($data);
        $this->assertSame(['0.42.0-rc.1', '0.41.0'], array_column($list, 'version'));
        $this->assertTrue($list[0]['prerelease']);
        $this->assertFalse($list[1]['prerelease']);
        $this->assertSame('https://git.example/o/WifiDB/-/releases/v0.41.0/downloads/WifiDB-v0.41.0-update.tar.gz', $list[1]['package'],
            'the permanent release link, not the registry\'s');
        $this->assertSame('0.41.0', WifidbReleases::Newest($list)['version'], 'the newest that isn\'t a pre-release');
        $this->assertSame('0.42.0-rc.1', WifidbReleases::Newest($list, true)['version']);
        $this->assertSame('0.41.0', WifidbReleases::Find($list, 'v0.41.0')['version']);
        $this->assertNull(WifidbReleases::Find($list, '0.40.0'));
    }

    public function testGitHubReleasesSkipDrafts(): void
    {
        $asset = fn ($name, $tag) => ['name' => $name, 'browser_download_url' => "https://github.com/o/WifiDB/releases/download/$tag/$name"];
        $data = [
            ['tag_name' => 'v0.41.0', 'prerelease' => false, 'draft' => false, 'published_at' => '2026-11-10T00:00:00Z',
                'assets' => [$asset('WifiDB-v0.41.0-update.tar.gz', 'v0.41.0'), $asset('SHA256SUMS', 'v0.41.0')]],
            ['tag_name' => 'v0.42.0', 'draft' => true, 'assets' => [$asset('WifiDB-v0.42.0-update.tar.gz', 'v0.42.0'), $asset('SHA256SUMS', 'v0.42.0')]],
            ['tag_name' => 'v0.41.1', 'prerelease' => false, 'draft' => false, 'assets' => [$asset('WifiDB-v0.41.1-update.tar.gz', 'v0.41.1')]],   // no sums
        ];
        $list = WifidbReleases::ParseGitHub($data);
        $this->assertSame(['0.41.0'], array_column($list, 'version'));
        $this->assertSame('https://github.com/o/WifiDB/releases/download/v0.41.0/SHA256SUMS', $list[0]['sums']);
    }

    public function testTheSourceAndItsTokenComeFromTheUpdaterSettings(): void
    {
        $gitlab = new WifidbReleases(['source' => 'gitlab', 'gitlab_url' => 'https://gitlab.techidiots.net/', 'gitlab_project' => 'techidiots-llc/WifiDB', 'token' => 't0k']);
        $this->assertSame('https://gitlab.techidiots.net/api/v4/projects/techidiots-llc%2FWifiDB/releases?per_page=50', $gitlab->ListUrl());
        $this->assertSame(['PRIVATE-TOKEN: t0k'], $gitlab->AuthHeaders());
        $github = new WifidbReleases(['source' => 'github', 'github_repo' => 'owner/WifiDB', 'token' => 't0k']);
        $this->assertSame('https://api.github.com/repos/owner/WifiDB/releases?per_page=50', $github->ListUrl());
        $this->assertSame(['Authorization: Bearer t0k'], $github->AuthHeaders());
        $this->assertSame([], (new WifidbReleases(['source' => 'gitlab', 'gitlab_url' => 'https://g', 'gitlab_project' => 'x']))->AuthHeaders());
        $this->expectException(ReleaseException::class);
        (new WifidbReleases(['source' => 'github']))->ListUrl();
    }

    public function testAPackageIsKeptOnlyIfItMatchesSha256Sums(): void
    {
        $dir = sys_get_temp_dir() . '/wifidb-releases-' . getmypid() . '-' . bin2hex(random_bytes(3));
        mkdir($dir);
        $package = 'the package';
        $release = ['version' => '0.41.0', 'package_name' => 'WifiDB-v0.41.0-update.tar.gz', 'package' => 'https://x/p', 'sums' => 'https://x/s'];
        $r = new WifidbReleases(['source' => 'gitlab', 'gitlab_url' => 'https://x', 'gitlab_project' => 'p']);
        try {
            $r->get = function ($url) use (&$package) {
                return $url === 'https://x/s'
                    ? [200, hash('sha256', 'the package') . "  WifiDB-v0.41.0-proxmox-amd64.tar.zst\n" . hash('sha256', 'the package') . " *WifiDB-v0.41.0-update.tar.gz\n"]
                    : [200, $package];
            };
            $path = $r->Download($release, $dir);
            $this->assertSame('the package', file_get_contents($path));

            $package = 'something else';
            unlink($path);
            try {
                $r->Download($release, $dir);
                $this->fail('a package that doesn\'t match was kept');
            } catch (ReleaseException $e) {
                $this->assertStringContainsString("isn't the one in SHA256SUMS", $e->getMessage());
                $this->assertFileDoesNotExist("$dir/WifiDB-v0.41.0-update.tar.gz");
            }
        } finally {
            array_map('unlink', glob("$dir/*") ?: []);
            rmdir($dir);
        }
    }

    public function testSha256SumsLines(): void
    {
        $h = str_repeat('a', 64);
        $this->assertSame(['WifiDB-v1.0.0-update.tar.gz' => $h, 'SHA256SUMS.sig' => $h],
            WifidbReleases::ParseSums("$h  WifiDB-v1.0.0-update.tar.gz\r\n" . strtoupper($h) . " *dir/SHA256SUMS.sig\nnot a line\n"));
    }
}
