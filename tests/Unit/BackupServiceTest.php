<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/backups.inc.php';

// The backups folder: transfer keys, the queue, and backups copied in by hand.
final class BackupServiceTest extends TestCase
{
    private string $dir;
    private BackupService $s;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/wifidb-backups-test-' . getmypid() . '-' . bin2hex(random_bytes(3));
        $this->s = new BackupService(['backup_dir' => $this->dir]);
        $this->assertTrue($this->s->Ready());
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    public function testATransferKeyWorksUntilItIsRevokedOrReplaced(): void
    {
        $this->assertFalse($this->s->CheckTransferKey(''));
        $key = $this->s->CreateTransferKey('root');
        $this->assertStringStartsWith('wdbk_', $key);
        $this->assertTrue($this->s->CheckTransferKey($key));
        $this->assertFalse($this->s->CheckTransferKey($key . 'x'));
        $this->assertStringNotContainsString($key, file_get_contents($this->dir . '/transfer-key.json'), 'only a hash is kept');
        $info = $this->s->TransferKeyInfo();
        $this->assertSame('root', $info['created_by']);
        $this->assertArrayNotHasKey('sha256', $info);
        $this->assertFalse($info['expired']);

        $second = $this->s->CreateTransferKey('root');
        $this->assertFalse($this->s->CheckTransferKey($key), 'a new key replaces the old one');
        $this->assertTrue($this->s->CheckTransferKey($second));
        $this->s->RevokeTransferKey();
        $this->assertFalse($this->s->CheckTransferKey($second));
        $this->assertNull($this->s->TransferKeyInfo());
    }

    public function testAnExpiredKeyIsRefused(): void
    {
        $key = $this->s->CreateTransferKey('root');
        $file = $this->dir . '/transfer-key.json';
        $data = json_decode(file_get_contents($file), true);
        $data['expires'] = time() - 1;
        file_put_contents($file, json_encode($data));
        $this->assertFalse($this->s->CheckTransferKey($key));
        $this->assertTrue($this->s->TransferKeyInfo()['expired']);
    }

    public function testAQueuedImportNeverShowsItsKey(): void
    {
        $this->s->Queue('import', 'root', ['url' => 'https://old.example.test', 'key' => 'wdbk_secret']);
        $pending = $this->s->Pending();
        $this->assertSame('import', $pending[0]['action']);
        $this->assertArrayNotHasKey('key', $pending[0]);
        $this->assertTrue((bool)$this->s->Busy());
    }

    public function testABackupCopiedInByHandIsListedFromItsManifest(): void
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE TABLE user_info (id INTEGER PRIMARY KEY, username TEXT)");
        $db->exec("INSERT INTO user_info (username) VALUES ('alice'), ('bob')");
        $name = 'wifidb-backup-20270101-000000';
        (new WifidbBackup($db, 'sqlite'))->Export($this->dir . "/$name.tar", ['site' => 'https://old.example.test/', 'migrations' => ['0001_x']]);
        file_put_contents($this->dir . '/not-a-backup.tar', 'x');

        $list = $this->s->Backups();
        $this->assertCount(1, $list);
        $this->assertSame($name, $list[0]['name']);
        $this->assertSame('done', $list[0]['state']);
        $this->assertSame('copied in', $list[0]['kind']);
        $this->assertSame(2, $list[0]['rows']);
        $this->assertSame('https://old.example.test/', $list[0]['source']['site']);
        $this->assertFileExists($this->dir . "/$name.json", 'its status is kept for next time');

        $public = BackupService::PublicSummary($list[0]);
        $this->assertArrayNotHasKey('migrations', $public['source'], 'another server sees where it came from, not its insides');
    }

    public function testDeleteRemovesTheFileAndItsStatus(): void
    {
        $name = 'wifidb-backup-20270101-000000';
        file_put_contents($this->dir . "/$name.tar", 'x');
        file_put_contents($this->dir . "/$name.json", json_encode(['state' => 'done']));
        $this->s->Delete($name);
        $this->assertFileDoesNotExist($this->dir . "/$name.tar");
        $this->assertFileDoesNotExist($this->dir . "/$name.json");
        $this->expectException(BackupException::class);
        $this->s->Delete('../config/web.overrides');
    }
}
