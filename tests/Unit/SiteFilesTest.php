<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/site_settings.inc.php';
require_once WIFIDB_LIB . '/site_files.inc.php';

// robots.txt, ads.txt and the sitemaps, from the site's address, Site Settings and its finished imports.
final class SiteFilesTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE site_settings (name TEXT PRIMARY KEY, value TEXT, updated_by TEXT, updated_at TEXT)");
        $this->db->exec("CREATE TABLE files (id INTEGER PRIMARY KEY, file_user TEXT, file_date TEXT, completed INTEGER)");
        $add = $this->db->prepare("INSERT INTO files (id, file_user, file_date, completed) VALUES (?, ?, ?, ?)");
        foreach ([[1, 'alice', '2026-01-05 10:00:00', 1], [2, 'bob & co', '2026-02-01 09:00:00', 1], [3, 'alice', '2026-03-09 08:00:00', 1],
                  [4, 'carol', '2026-04-01 00:00:00', 0], [5, '', '2026-04-02 00:00:00', 1]] as $row) {
            $add->execute($row);
        }
    }

    private function settings(array $saved = []): SiteSettings
    {
        $s = new SiteSettings($this->db, []);
        if ($saved) { $s->Save($saved, 'root'); }
        return new SiteSettings($this->db, []);
    }

    private function files(array $saved = [], string $site = 'https://wifidb.example.com/', int $per = 50000): SiteFiles
    {
        return new SiteFiles($this->db, 'sqlite', $this->settings($saved), $site, 'https://wifidb.example.com/', $per);
    }

    public function testRobotsKeepsCrawlersOutOfMembersPagesAndNamesTheSitemap(): void
    {
        $robots = $this->files()->Robots();
        $this->assertStringContainsString("Disallow: /cp/\n", $robots);
        $this->assertStringContainsString("Disallow: /opt/export.php\n", $robots);
        $this->assertStringContainsString("Sitemap: https://wifidb.example.com/sitemap.xml\n", $robots);
        $this->assertStringNotContainsString('wifidb.net', $robots);
    }

    public function testASiteInAFolderKeepsItsOwnPathsOut(): void
    {
        $robots = $this->files([], 'https://example.com/wifidb/')->Robots();
        $this->assertStringContainsString("Disallow: /wifidb/cp/\n", $robots);
        $this->assertStringContainsString('<loc>https://example.com/wifidb/stats.php</loc>', $this->files([], 'https://example.com/wifidb/')->Sitemap('pages'));
    }

    public function testKeepingSearchEnginesOutTurnsEveryoneAwayAndHasNoSitemaps(): void
    {
        $files = $this->files(['search_noindex' => true]);
        $this->assertSame("User-agent: *\nDisallow: /\n", preg_replace('/^#.*\n/', '', $files->Robots()));
        $this->assertNull($files->Serve('sitemap.xml'));
        $this->assertNull($files->Serve('sitemap-pages.xml'));
        $this->assertNotNull($files->Serve('robots.txt'));
    }

    public function testAdsTxtIsTheSettingOrAdsensesLineOrNone(): void
    {
        $this->assertNull($this->files()->Serve('ads.txt'), 'no ads: no ads.txt');
        $this->assertSame("google.com, pub-2350008141216245, DIRECT, f08c47fec0942fa0\n",
            $this->files(['adsense_client' => 'ca-pub-2350008141216245'])->AdsTxt());
        $this->assertSame("google.com, pub-1, DIRECT, f08c47fec0942fa0\nexample.com, 42, RESELLER\n",
            $this->files(['ads_txt' => "google.com, pub-1, DIRECT, f08c47fec0942fa0\r\nexample.com, 42, RESELLER\r\n"])->AdsTxt(), 'the setting, as written');
    }

    public function testTheIndexListsEachSitemapPiece(): void
    {
        $index = $this->files([], 'https://wifidb.example.com/', 1)->Index();
        // two members with finished imports (bob & co, alice) and four finished imports, one to a piece
        foreach (['pages', 'members-1', 'members-2', 'imports-1', 'imports-2', 'imports-3', 'imports-4'] as $map) {
            $this->assertStringContainsString("<loc>https://wifidb.example.com/sitemap-$map.xml</loc>", $index);
        }
        $this->assertStringNotContainsString('members-3', $index);
        $this->assertStringNotContainsString('imports-5', $index);
    }

    public function testMembersAndImportsWithTheirDates(): void
    {
        $members = $this->files()->Sitemap('members');
        $this->assertStringContainsString('<loc>https://wifidb.example.com/opt/userstats.php?func=alluserlists&amp;user=alice</loc><lastmod>2026-03-09</lastmod>', $members);
        $this->assertStringContainsString('user=bob%20%26%20co</loc>', $members, 'names are encoded in the address, and that escaped in the XML');
        $this->assertStringNotContainsString('carol', $members, 'only members with a finished import');
        $imports = $this->files()->Sitemap('imports');
        $this->assertSame(4, substr_count($imports, '<url>'), 'every finished import, the unnamed one too');
        $this->assertStringContainsString('<loc>https://wifidb.example.com/opt/userstats.php?func=useraplist&amp;row=2</loc><lastmod>2026-02-01</lastmod>', $imports);
        $this->assertNull($this->files()->Sitemap('imports', 2), 'no second piece');
    }

    public function testPiecesSplitTheList(): void
    {
        $files = $this->files([], 'https://wifidb.example.com/', 2);
        $this->assertSame(2, substr_count($files->Sitemap('imports', 1), '<url>'));
        $this->assertSame(2, substr_count($files->Sitemap('imports', 2), '<url>'));
        $this->assertStringContainsString('row=5', $files->Sitemap('imports', 2));
        $this->assertNull($files->Serve('sitemap-imports-3.xml'));
        $this->assertNull($files->Serve('sitemap-other.xml'));
        $this->assertNull($files->Serve('../config.inc.php'));
    }

    public function testWithoutTheDatabaseOnlyThePagesAreListed(): void
    {
        $files = new SiteFiles(null, 'mysql', $this->settings(), 'https://wifidb.example.com/', 'https://wifidb.example.com/');
        $this->assertStringNotContainsString('members', $files->Index());
        $this->assertStringContainsString('stats.php', $files->Sitemap('pages'));
    }
}
