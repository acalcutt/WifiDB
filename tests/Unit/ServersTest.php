<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/servers.inc.php';

// admin/servers.php's data: the servers' reports, update requests, the warnings, and version order.
final class ServersTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE server_status (node_name TEXT PRIMARY KEY, version TEXT, reported_at TEXT, info TEXT, releases TEXT, releases_at TEXT,
            update_request INTEGER, update_version TEXT, update_state TEXT, update_message TEXT, update_at TEXT)");
        $this->db->exec("CREATE TABLE server_update_requests (id INTEGER PRIMARY KEY AUTOINCREMENT, version TEXT NOT NULL, backup INTEGER NOT NULL DEFAULT 0,
            backup_taken INTEGER NOT NULL DEFAULT 0, requested_by TEXT, requested_at TEXT, cancelled INTEGER NOT NULL DEFAULT 0)");
    }

    public static function versionOrder(): array
    {
        return [
            'patch'                      => ['0.40.0', '0.40.1'],
            'minor beats patch'          => ['0.40.9', '0.41.0'],
            'numbers, not text'          => ['0.9.0', '0.10.0'],
            'a pre-release is older'     => ['0.41.0-rc.1', '0.41.0'],
            'pre-releases in order'      => ['0.41.0-rc.2', '0.41.0-rc.10'],
            'beta before rc'             => ['0.41.0-beta.3', '0.41.0-rc.1'],
            'a leading v is ignored'     => ['v0.40.0', '0.40.1'],
            'not a version sorts oldest' => ['nonsense', '0.0.1'],
        ];
    }

    /** @dataProvider versionOrder */
    #[\PHPUnit\Framework\Attributes\DataProvider('versionOrder')]
    public function testVersionsCompareAsReleasesDo(string $older, string $newer): void
    {
        $this->assertLessThan(0, WifidbVersion::Compare($older, $newer));
        $this->assertGreaterThan(0, WifidbVersion::Compare($newer, $older));
        $this->assertSame(0, WifidbVersion::Compare($newer, $newer));
    }

    public function testAReportMakesTheRowThenUpdatesIt(): void
    {
        ServerStatus::Report($this->db, 'node-a', '0.40.0', ['load' => [0.5, 0.4, 0.3]]);
        ServerStatus::Report($this->db, 'node-a', '0.41.0', ['load' => [1, 1, 1]]);
        ServerStatus::SetUpdate($this->db, 'node-a', 3, '0.41.0', 'done', 'updated from 0.40.0');
        ServerStatus::SetReleases($this->db, 'node-a', [['version' => '0.41.0']], '');
        $rows = ServerStatus::All($this->db);
        $this->assertCount(1, $rows);
        $this->assertSame('0.41.0', $rows[0]['version']);
        $this->assertSame([1, 1, 1], $rows[0]['info']['load']);
        $this->assertSame('done', $rows[0]['update_state']);
        $this->assertSame('0.41.0', $rows[0]['releases'][0]['version'], 'the report and the updater keep each other\'s columns');
        ServerStatus::Forget($this->db, 'node-a');
        $this->assertSame([], ServerStatus::All($this->db));
    }

    public function testTheNewestRequestNotCalledOffIsTheOneToDo(): void
    {
        $this->assertNull(ServerStatus::LatestRequest($this->db));
        ServerStatus::Request($this->db, '0.41.0', false, 'alice');
        $id = ServerStatus::Request($this->db, '0.42.0', true, 'alice');
        $latest = ServerStatus::LatestRequest($this->db);
        $this->assertSame($id, (int)$latest['id']);
        $this->assertSame('0.42.0', $latest['version']);
        $this->assertSame(1, (int)$latest['backup']);
        ServerStatus::Cancel($this->db);
        $this->assertSame('0.41.0', ServerStatus::LatestRequest($this->db)['version'], 'calling off the newest leaves the one before');
    }

    public function testWarningsSayWhatNeedsAnEye(): void
    {
        $now = strtotime('2026-11-02 12:00:00 UTC');
        $row = ['reported_at' => '2026-11-02 11:59:30', 'version' => '0.41.0', 'update_state' => 'done', 'update_message' => '',
            'info' => ['cpus' => 2, 'load' => [1, 1, 1], 'memory' => ['total' => 100, 'available' => 50],
                'disks' => ['/srv/wifidb-data' => ['total' => 100, 'free' => 50]],
                'services' => ['apache2' => 'active', 'wifidb-websocketd' => 'off']]];
        $this->assertSame([], ServerStatus::Warnings($row, $now, '0.41.0'), 'a healthy, current server; a daemon switched off is fine');

        $bad = $row;
        $bad['reported_at'] = '2026-11-02 11:40:00';
        $bad['version'] = '0.40.0';
        $bad['info']['disks']['/srv/wifidb-data']['free'] = 5;
        $bad['info']['memory']['available'] = 2;
        $bad['info']['load'] = [9, 9, 9];
        $bad['info']['services']['apache2'] = 'failed';
        $bad['update_state'] = 'rolled_back';
        $bad['update_message'] = 'the site doesn\'t answer';
        $w = implode(' | ', ServerStatus::Warnings($bad, $now, '0.41.0'));
        foreach (['not heard from for 20 minutes', 'runs 0.40.0, older than 0.41.0', '/srv/wifidb-data is 95% full', 'almost out of memory',
                  'busy (load 9 on 2 CPUs)', 'apache2 is failed', 'failed, old version back'] as $expected) {
            $this->assertStringContainsString($expected, $w);
        }
        $this->assertSame(['has never reported'], ServerStatus::Warnings(['info' => []], $now));
    }

    public function testTheSwarmReportKeepsItsStateAndEachCategorysCurrentBuild(): void
    {
        // as pmtiles-swarm's /categories.xml has it (src/feed.js): an item for each category's current build
        $item = fn (string $hash, string $date, string ...$cats) => "<item><title>$hash.pmtiles</title><guid isPermaLink=\"false\">$hash</guid>"
            . "<pubDate>$date</pubDate>" . implode('', array_map(fn ($c) => "<category>$c</category>", $cats))
            . "<enclosure url=\"http://x/$hash.torrent\" length=\"1\" type=\"application/x-bittorrent\"/><pmtiles:infohash>$hash</pmtiles:infohash></item>";
        $xml = '<?xml version="1.0" encoding="UTF-8"?><rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:pmtiles="https://github.com/TechIdiots-LLC/pmtiles-swarm"><channel><title>t</title>'
            . $item('new', 'Tue, 03 Nov 2026 06:00:00 GMT', 'wifidb-weekly') . $item('d1', 'Tue, 03 Nov 2026 07:00:00 GMT', 'wifidb-daily') . '</channel></rss>';
        $newest = ServerStatus::CategoriesFeed($xml);
        $this->assertSame(['wifidb-daily' => ['hash' => 'd1', 'at' => '2026-11-03T07:00:00Z'], 'wifidb-weekly' => ['hash' => 'new', 'at' => '2026-11-03T06:00:00Z']], $newest);
        $this->assertNull(ServerStatus::CategoriesFeed('{"error":"authentication required"}'));

        $info = ServerStatus::SwarmInfo('active', ['status' => 'ok', 'engine' => 'libtorrent', 'version' => '0.107.2'], $newest, 'builds');
        $this->assertSame(['service' => 'active', 'role' => 'builds', 'status' => 'ok', 'version' => '0.107.2', 'categories' => 2, 'newest' => $newest], $info);

        $down = ServerStatus::SwarmInfo('active', ['status' => 'unavailable', 'version' => '0.107.2', 'error' => 'engine not ready'], null);
        $this->assertSame(['service' => 'active', 'role' => '', 'status' => 'unavailable', 'version' => '0.107.2', 'error' => 'engine not ready'], $down);
        $this->assertSame('no answer', ServerStatus::SwarmInfo('active', null, null)['status']);
    }

    public function testSwarmWarnings(): void
    {
        $now = strtotime('2026-11-03 12:00:00 UTC');
        $row = ['reported_at' => '2026-11-03 11:59:30', 'version' => '1.0.0', 'info' => ['services' => ['apache2' => 'active']]];
        $this->assertSame([], ServerStatus::Warnings($row, $now), 'a node without the swarm says nothing of it');

        $swarm = ['service' => 'active', 'role' => 'follows', 'status' => 'ok'];
        foreach ([
            'pmtiles-swarm is failed' => ['service' => 'failed'] + $swarm,
            "pmtiles-swarm isn't answering on port 8090" => ['status' => 'no answer'] + $swarm,
            'pmtiles-swarm says it is offline: taken out of rotation' => ['status' => 'offline', 'error' => 'taken out of rotation'] + $swarm,
        ] as $expected => $state) {
            $this->assertSame([$expected], ServerStatus::Warnings(['info' => $row['info'] + ['swarm' => $state]] + $row, $now));
        }
        $this->assertSame([], ServerStatus::Warnings(['info' => $row['info'] + ['swarm' => $swarm]] + $row, $now));
    }

    public function testAFollowerIsBehindOnlyOnceTheNewestBuildHasHadTimeToReachIt(): void
    {
        $now = strtotime('2026-11-03 12:00:00 UTC');
        $builder = ['role' => 'builds', 'newest' => [
            'wifidb-weekly' => ['hash' => 'w2', 'at' => '2026-11-03T06:00:00.000Z'],     // hours ago
            'wifidb-daily' => ['hash' => 'd9', 'at' => '2026-11-03T11:50:00.000Z'],      // ten minutes ago: still on its way
            'wifidb-monthly' => ['hash' => 'm1', 'at' => '2026-11-01T00:00:00.000Z'],
        ]];
        $follower = ['service' => 'active', 'role' => 'follows', 'status' => 'ok', 'newest' => [
            'wifidb-weekly' => ['hash' => 'w1', 'at' => '2026-11-02T06:05:00.000Z'],
            'wifidb-daily' => ['hash' => 'd8', 'at' => '2026-11-03T10:52:00.000Z'],
            'wifidb-monthly' => ['hash' => 'm1', 'at' => '2026-11-01T00:03:00.000Z'],
        ]];
        $this->assertSame(['wifidb-weekly'], ServerStatus::SwarmBehind($follower, $builder, $now));
        $row = ['reported_at' => '2026-11-03 11:59:30', 'info' => ['swarm' => $follower]];
        $this->assertSame(["pmtiles-swarm hasn't got the newest build of wifidb-weekly"], ServerStatus::Warnings($row, $now, '', $builder));
        $this->assertSame([], ServerStatus::Warnings(['info' => ['swarm' => ['role' => 'builds'] + $follower]] + $row, $now, '', $builder),
            'only a following node is compared');
    }
}
