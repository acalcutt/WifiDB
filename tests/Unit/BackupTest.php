<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/backup.inc.php';

// Export from one database and restore into another, with SQLite standing in for both (the boot test and
// tests/sqlsrv do MariaDB and SQL Server for real).
final class BackupTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/wifidb-backup-test-' . getmypid() . '-' . bin2hex(random_bytes(3));
        mkdir($this->dir, 0700, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/{,.}*', GLOB_BRACE) ?: [] as $f) { if (is_file($f)) { unlink($f); } }
        @rmdir($this->dir);
    }

    private function db(): PDO
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE TABLE user_info (id INTEGER PRIMARY KEY, username VARCHAR(255) NOT NULL UNIQUE, join_date DATETIME NULL, validated BIT NOT NULL DEFAULT 0)");
        $db->exec("CREATE TABLE stats_cache (id INTEGER PRIMARY KEY, geos BLOB NOT NULL, note TEXT NULL)");
        $db->exec("CREATE TABLE user_login_hashes (id INTEGER PRIMARY KEY, username TEXT, hash TEXT)");
        $db->exec("CREATE TABLE schedule (id INTEGER PRIMARY KEY, daemon TEXT)");
        return $db;
    }

    private function source(): PDO
    {
        $db = $this->db();
        $db->exec("CREATE TABLE user_imports (id INTEGER PRIMARY KEY, username TEXT)");  // only on the source
        $insert = $db->prepare("INSERT INTO user_info (id, username, join_date, validated) VALUES (?, ?, ?, ?)");
        $insert->execute([7, 'alice', '2026-01-02 03:04:05.0000000', 1]);
        $insert->execute([9, 'bob', '0000-00-00 00:00:00', 0]);
        $insert->execute([12, "o'neil ünïcode 📡", null, 0]);
        $blob = $db->prepare("INSERT INTO stats_cache (id, geos, note) VALUES (?, ?, ?)");
        $blob->bindValue(1, 1); $blob->bindValue(2, "\x00\xff\x10binary", PDO::PARAM_LOB); $blob->bindValue(3, "bad \xc3\x28 utf8");
        $blob->execute();
        $db->exec("INSERT INTO user_login_hashes (username, hash) VALUES ('alice', 'session')");
        $db->exec("INSERT INTO schedule (daemon) VALUES ('Import on the source')");
        $db->exec("INSERT INTO user_imports (username) VALUES ('alice')");
        return $db;
    }

    private function export(PDO $db, int $rowsPerPart = 1000): string
    {
        $backup = new WifidbBackup($db, 'sqlite');
        $backup->rowsPerPart = $rowsPerPart;
        $backup->clock = fn () => 1_800_000_000;
        $tar = $this->dir . '/wifidb-backup-20270115-080000.tar';
        $backup->Export($tar, ['site' => 'https://old.example.test/', 'migrations' => ['0001_two_factor']], ['two_factor_key' => 'k3y']);
        return $tar;
    }

    private function restore(PDO $target, string $tar, array $migrations = ['0001_two_factor']): array
    {
        $backup = new WifidbBackup($target, 'sqlite');
        $backup->tmpDir = $this->dir;
        return $backup->Restore($tar, $migrations, $this->dir . '/rejects.jsonl');
    }

    public function testTheBackupIsOneTarWithTheManifestLastAndNoServerTables(): void
    {
        $tar = $this->export($this->source());
        $this->assertFileExists($tar);
        $this->assertFileDoesNotExist($tar . '.partial');
        $this->assertDirectoryDoesNotExist($tar . '.parts');
        $names = array_keys((new BackupTar($tar, 'r'))->Members());
        $this->assertSame('manifest.json', end($names));
        $this->assertContains('secrets.json', $names);
        $this->assertContains('tables/user_info.0001.jsonl.gz', $names);
        $manifest = WifidbBackup::ReadManifest($tar);
        $this->assertSame(['stats_cache', 'user_imports', 'user_info'], array_keys($manifest['tables']));
        $this->assertSame(3, $manifest['tables']['user_info']['rows']);
        $this->assertSame(5, $manifest['rows']);
        $this->assertSame('sqlite', $manifest['source']['service']);
        $this->assertSame(['two_factor_key' => 'k3y'], WifidbBackup::ReadSecrets($tar));

        // a standard tar: PHP's own reader (and so tar itself) can list and read it
        $phar = new PharData($tar);
        $this->assertTrue(isset($phar['manifest.json']));
        $this->assertSame(json_decode(file_get_contents($phar['manifest.json']->getPathname()), true)['name'], 'wifidb-backup-20270115-080000');
    }

    public function testValuesAreWrittenTheSameWayWhateverTheDatabase(): void
    {
        $cols = [['d', 'datetime', true], ['b', 'binary', false], ['t', 'value', true], ['f', 'bool', false], ['s', 'skip', true]];
        $this->assertSame(
            ['2026-01-02 03:04:05', 'AP8=', ['x' => base64_encode("\xc3\x28")], 1, null],
            WifidbBackup::EncodeRow(['2026-01-02T03:04:05.000', "\x00\xff", "\xc3\x28", '1', 'rowversion'], $cols)
        );
        $this->assertSame('2026-01-02 03:04:05.12', WifidbBackup::NormalDate('2026-01-02 03:04:05.1200000'));
        $this->assertSame('datetime', WifidbBackup::Kind('datetime2(0)'));
        $this->assertSame('binary', WifidbBackup::Kind('varbinary'));
        $this->assertSame('value', WifidbBackup::Kind('nvarchar'));
    }

    public function testRestoreBringsEveryRowBackWithItsIdsAndBytes(): void
    {
        $tar = $this->export($this->source(), 2);   // user_info in two parts
        $this->assertContains('tables/user_info.0002.jsonl.gz', array_keys((new BackupTar($tar, 'r'))->Members()));

        $target = $this->db();
        $target->exec("INSERT INTO user_info (id, username) VALUES (1, 'the new server''s own admin')");
        $target->exec("INSERT INTO user_login_hashes (username, hash) VALUES ('admin', 'their session')");
        $target->exec("INSERT INTO schedule (daemon) VALUES ('Import on the new server')");
        $report = $this->restore($target, $tar);

        $this->assertSame(['id' => 7, 'username' => 'alice', 'join_date' => '2026-01-02 03:04:05', 'validated' => 1],
            $target->query("SELECT * FROM user_info WHERE id = 7")->fetch(PDO::FETCH_ASSOC));
        $this->assertSame([7, 9, 12], array_map('intval', $target->query("SELECT id FROM user_info ORDER BY id")->fetchAll(PDO::FETCH_COLUMN)), 'the old rows are gone, the ids kept');
        $this->assertSame("o'neil ünïcode 📡", $target->query("SELECT username FROM user_info WHERE id = 12")->fetchColumn());
        $row = $target->query("SELECT geos, note FROM stats_cache")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame("\x00\xff\x10binary", $row['geos']);
        $this->assertSame("bad \xc3\x28 utf8", $row['note'], 'text that is not valid UTF-8 comes back byte for byte');

        $this->assertSame(0, (int)$target->query("SELECT COUNT(*) FROM user_login_hashes")->fetchColumn(), 'everyone signs in again');
        $this->assertSame('Import on the new server', $target->query("SELECT daemon FROM schedule")->fetchColumn(), "the server's own schedule stays");
        $this->assertSame(['user_imports'], $report['missing_here']);
        $this->assertSame(['expected' => 3, 'loaded' => 3, 'rejected' => 0], $report['tables']['user_info']);
        $this->assertSame(0, $report['rejected']);
        $this->assertSame(1, $report['zero_dates']);
        $this->assertNull($target->query("SELECT join_date FROM user_info WHERE id = 9")->fetchColumn(), "MariaDB's zero date, which SQL Server can't hold, is no date");
        $this->assertFileDoesNotExist($this->dir . '/rejects.jsonl');
    }

    public function testARowTheTargetRefusesIsSetAsideNotFatal(): void
    {
        $tar = $this->export($this->source());
        $target = $this->db();
        $target->exec("DROP TABLE user_info");
        $target->exec("CREATE TABLE user_info (id INTEGER PRIMARY KEY, username VARCHAR(255) NOT NULL UNIQUE, join_date DATETIME NULL, validated BIT NOT NULL DEFAULT 0, CHECK (username <> 'bob'))");
        $report = $this->restore($target, $tar);
        $this->assertSame(['expected' => 3, 'loaded' => 2, 'rejected' => 1], $report['tables']['user_info']);
        $this->assertSame(0, $report['zero_dates'], 'only rows that went in are counted');
        $reject = json_decode(trim(file_get_contents($this->dir . '/rejects.jsonl')), true);
        $this->assertSame('user_info', $reject['table']);
        $this->assertSame('bob', $reject['row'][1]);
    }

    public function testATableTheBackupDoesNotHaveIsEmptiedAndReported(): void
    {
        $tar = $this->export($this->source());
        $target = $this->db();
        $target->exec("CREATE TABLE DB_stats (id INTEGER PRIMARY KEY, n INTEGER)");
        $target->exec("INSERT INTO DB_stats (n) VALUES (1)");
        $report = $this->restore($target, $tar);
        $this->assertSame(['DB_stats'], $report['not_in_backup']);
        $this->assertSame(0, (int)$target->query("SELECT COUNT(*) FROM DB_stats")->fetchColumn());
    }

    public function testADatabaseMissingAMigrationIsRefusedBeforeAnythingChanges(): void
    {
        $tar = $this->export($this->source());
        $target = $this->db();
        $target->exec("INSERT INTO user_info (id, username) VALUES (1, 'keep me')");
        try {
            $this->restore($target, $tar, []);
            $this->fail('restored without the migration');
        } catch (BackupException $e) {
            $this->assertStringContainsString('0001_two_factor', $e->getMessage());
        }
        $this->assertSame('keep me', $target->query("SELECT username FROM user_info")->fetchColumn());
    }

    public function testADamagedBackupIsRefusedBeforeAnythingChanges(): void
    {
        $tar = $this->export($this->source());
        $members = (new BackupTar($tar, 'r'))->Members();
        $fh = fopen($tar, 'r+b');
        fseek($fh, $members['tables/user_info.0001.jsonl.gz'][0] + 20);
        fwrite($fh, 'XX');
        fclose($fh);
        $target = $this->db();
        $target->exec("INSERT INTO user_info (id, username) VALUES (1, 'keep me')");
        try {
            $this->restore($target, $tar);
            $this->fail('restored a damaged backup');
        } catch (BackupException $e) {
            $this->assertStringContainsString('damaged', $e->getMessage());
        }
        $this->assertSame('keep me', $target->query("SELECT username FROM user_info")->fetchColumn());
    }

    public function testSomethingElseIsNotABackup(): void
    {
        $file = $this->dir . '/not-a-backup.tar';
        $phar = new PharData($file);
        $phar->addFromString('manifest.json', '{"format":"other"}');
        unset($phar);
        $this->expectException(BackupException::class);
        WifidbBackup::ReadManifest($file);
    }
}
