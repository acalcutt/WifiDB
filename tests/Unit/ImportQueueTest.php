<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/import_queue.inc.php';

// Putting an upload on the import queue, as the upload page and both APIs do. (With the shared files bucket, see
// proxmox/test-s3.sh and test-two-nodes.sh.)
final class ImportQueueTest extends TestCase
{
    private PDO $db;
    private string $dir;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $cols = 'id INTEGER PRIMARY KEY, file_name TEXT, file_orig TEXT, file_date TEXT, file_user TEXT, otherusers TEXT, notes TEXT, title TEXT, size TEXT, hash TEXT, type TEXT, node_name TEXT';
        $this->db->exec("CREATE TABLE files_tmp ($cols)");
        $this->db->exec("CREATE TABLE files_importing ($cols, tmp_id INTEGER)");
        $this->db->exec("CREATE TABLE files (id INTEGER PRIMARY KEY, hash TEXT)");
        $this->db->exec("CREATE TABLE site_settings (name TEXT PRIMARY KEY, value TEXT, updated_by TEXT, updated_at TEXT)");
        $this->dir = sys_get_temp_dir() . '/wifidb-queue-test-' . getmypid() . '-' . bin2hex(random_bytes(3));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/*') ?: [] as $f) { unlink($f); }
        rmdir($this->dir);
    }

    private function upload(string $name, string $hash): array
    {
        file_put_contents("$this->dir/$name", "data for $name");
        return ['file_name' => $name, 'file_orig' => $name, 'file_date' => '2026-11-01 12:00:00', 'file_user' => 'alice', 'otherusers' => '',
            'notes' => '', 'title' => 't', 'size' => '1 kB', 'hash' => $hash, 'type' => 'vistumbler'];
    }

    public function testAnUploadIsQueuedForTheNodeItCameInOnAndKept(): void
    {
        [$result, $id] = ImportQueue::Add($this->db, ['wifidb_nodename' => 'node-a'], $this->upload('1_a.vs1', 'h1'), "$this->dir/1_a.vs1");
        $this->assertSame('queued', $result);
        $this->assertSame('node-a', $this->db->query("SELECT node_name FROM files_tmp WHERE id = " . (int)$id)->fetchColumn());
        $this->assertFileExists("$this->dir/1_a.vs1", 'without the bucket the file stays on this node, which imports it');
    }

    public function testTheSameFileWaitingImportingOrImportedIsTurnedAwayAndItsCopyRemoved(): void
    {
        ImportQueue::Add($this->db, ['wifidb_nodename' => 'node-a'], $this->upload('1_a.vs1', 'h1'), "$this->dir/1_a.vs1");
        $this->assertSame(['waiting', ''], ImportQueue::Add($this->db, [], $this->upload('2_a.vs1', 'h1'), "$this->dir/2_a.vs1"));
        $this->assertFileDoesNotExist("$this->dir/2_a.vs1");

        $this->db->exec("INSERT INTO files_importing (hash) VALUES ('h2')");
        $this->assertSame('waiting', ImportQueue::Add($this->db, [], $this->upload('3_b.vs1', 'h2'), "$this->dir/3_b.vs1")[0]);

        $this->db->exec("INSERT INTO files (hash) VALUES ('h3')");
        $this->assertSame('imported', ImportQueue::Add($this->db, [], $this->upload('4_c.vs1', 'h3'), "$this->dir/4_c.vs1")[0]);
        $this->assertSame(1, (int)$this->db->query("SELECT COUNT(*) FROM files_tmp")->fetchColumn());
    }

    public function testAFailedInsertQueuesNothingAndSaysWhy(): void
    {
        $this->db->exec("DROP TABLE files_tmp");
        $this->db->exec("CREATE TABLE files_tmp (id INTEGER PRIMARY KEY, hash TEXT)");   # missing the other columns
        $said = [];
        [$result] = ImportQueue::Add($this->db, [], $this->upload('5_d.vs1', 'h5'), "$this->dir/5_d.vs1", function ($l) use (&$said) { $said[] = $l; });
        $this->assertSame('failed', $result);
        $this->assertFileDoesNotExist("$this->dir/5_d.vs1", 'nothing queued points at it');
        $this->assertStringContainsString('Could not queue the upload 5_d.vs1', $said[0] ?? '');
    }

    public function testNoNodeNameQueuesItForAnyNode(): void
    {
        ImportQueue::Add($this->db, [], $this->upload('6_e.vs1', 'h6'), "$this->dir/6_e.vs1");
        $this->assertNull($this->db->query("SELECT node_name FROM files_tmp")->fetchColumn());
    }

    public function testTheNodeNameComesFromTheDaemonConfigWhenTheSiteConfigHasNone(): void
    {
        file_put_contents("$this->dir/daemon.config.inc.php", "<?php\n\$daemon_config = array('wifidb_nodename' => 'node-b');\n");
        $this->assertSame('node-b', ImportQueue::NodeName(['wifidb_tools' => "$this->dir/"]));
        $this->assertSame('node-c', ImportQueue::NodeName(['wifidb_nodename' => 'node-c', 'wifidb_tools' => "$this->dir/"]), 'the site config wins');
        $this->assertSame('', ImportQueue::NodeName(['wifidb_tools' => "$this->dir/nowhere/"]));
    }
}
