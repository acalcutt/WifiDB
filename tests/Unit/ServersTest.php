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
}
