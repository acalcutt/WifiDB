<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/migrate.inc.php';

final class MigratorTest extends TestCase
{
    private PDO $db;
    private string $dir;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->dir = __DIR__ . '/../fixtures/migrations';
    }

    private function migrator(?string $dir = null): Migrator
    {
        return new Migrator($this->db, 'sqlite', $dir ?? $this->dir, function () {});
    }

    /** A folder holding one sqlite migration per [name => sql] */
    private function folder(array $files): string
    {
        $dir = sys_get_temp_dir() . '/migrations-' . getmypid() . '-' . uniqid();
        mkdir("$dir/sqlite", 0777, true);
        foreach ($files as $name => $sql) { file_put_contents("$dir/sqlite/$name.sql", $sql); }
        return $dir;
    }

    private function remove(string $dir): void
    {
        array_map('unlink', glob("$dir/sqlite/*.sql"));
        rmdir("$dir/sqlite");
        rmdir($dir);
    }

    public function testSplitMysqlStatements(): void
    {
        $sql = "-- header; with a semicolon\nCREATE TABLE a (x int);\n/* c; */ INSERT INTO a VALUES ('p;q', \"r;s\");\nALTER TABLE `a;b` ADD y int;\n";
        $this->assertSame([
            'CREATE TABLE a (x int)',
            "INSERT INTO a VALUES ('p;q', \"r;s\")",
            'ALTER TABLE `a;b` ADD y int',
        ], Migrator::Split($sql, 'mysql'));
    }

    public function testSplitKeepsAnEscapedQuoteInsideAString(): void
    {
        $this->assertSame(["INSERT INTO a VALUES ('it\\'s; fine')", 'SELECT 1'], Migrator::Split("INSERT INTO a VALUES ('it\\'s; fine'); SELECT 1", 'mysql'));
    }

    public function testSplitSqlServerBatchesOnGo(): void
    {
        $sql = "CREATE TABLE [t](a int)\r\nGO\r\nCREATE INDEX i ON [t](a);\r\n  go  \r\n\r\nGO\r\nSELECT 1\r\nGO";
        $this->assertSame(['CREATE TABLE [t](a int)', 'CREATE INDEX i ON [t](a);', 'SELECT 1'], Migrator::Split($sql, 'sqlsrv'));
    }

    public function testAvailableFilesAreInOrder(): void
    {
        $this->assertSame(['0001_create', '0002_add_column'], $this->migrator()->Available());
    }

    public function testRunAppliesEverythingOnceAndInOrder(): void
    {
        $m = $this->migrator();
        $this->assertSame(['0001_create', '0002_add_column'], $m->Pending());
        $this->assertSame(['0001_create', '0002_add_column'], $m->Run());
        $this->assertSame([], $m->Pending());
        $row = $this->db->query('SELECT name, colour FROM widgets')->fetch(PDO::FETCH_ASSOC);
        $this->assertSame(['name' => 'one;two', 'colour' => 'red'], $row);
        $this->assertSame(['0001_create', '0002_add_column'], $m->Applied());
    }

    public function testRunningAgainDoesNothing(): void
    {
        $this->migrator()->Run();
        $this->assertSame([], $this->migrator()->Run());
        $this->assertSame(1, (int)$this->db->query('SELECT COUNT(*) FROM widgets')->fetchColumn());
    }

    public function testAMigrationAddedLaterIsTheOnlyOneApplied(): void
    {
        $dir = $this->folder(['0001_create' => "CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT);"]);
        $this->migrator($dir)->Run();
        file_put_contents("$dir/sqlite/0002_more.sql", "INSERT INTO widgets (name) VALUES ('two');");
        $this->assertSame(['0002_more'], $this->migrator($dir)->Run());
        $this->remove($dir);
    }

    public function testAFailureNamesTheStatementAndReleasesItsClaim(): void
    {
        $dir = $this->folder(['0001_bad' => "CREATE TABLE t (a int);\nINSERT INTO no_such_table VALUES (1);"]);
        $m = $this->migrator($dir);
        try {
            $m->Run();
            $this->fail('the failure was swallowed');
        } catch (MigrationException $e) {
            $this->assertStringContainsString('0001_bad failed', $e->getMessage());
            $this->assertStringContainsString('INSERT INTO no_such_table', $e->getMessage());
        }
        $this->assertSame(0, (int)$this->db->query("SELECT COUNT(*) FROM schema_migrations")->fetchColumn(), 'the claim is released');
        $this->assertSame(['0001_bad'], $m->Pending());
        $this->remove($dir);
    }

    public function testAnotherNodeApplyingItMakesThisOneWaitThenSkip(): void
    {
        $this->migrator()->Applied();   // creates the tracking table
        // another node has claimed 0001 and finishes it while this one waits
        $this->db->exec("INSERT INTO schema_migrations (id, started) VALUES ('0001_create', '2026-01-01 00:00:00')");
        $this->db->exec("CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT)");   // what the other node's 0001 makes
        $db = $this->db;
        $sleeper = function () use ($db) {
            $db->exec("UPDATE schema_migrations SET applied = '2026-01-01 00:00:05' WHERE id = '0001_create'");
        };
        $waiting = new Migrator($this->db, 'sqlite', $this->dir, $sleeper);
        $waiting->waitSeconds = 5;
        $this->assertSame(['0002_add_column'], $waiting->Run(), 'it only applies what the other node did not');
    }

    public function testItTakesOverWhenTheOtherNodeFailsAndLetsGo(): void
    {
        $dir = $this->folder(['0001_create' => "CREATE TABLE widgets (id INTEGER PRIMARY KEY, name TEXT);"]);
        $this->migrator($dir)->Applied();
        $this->db->exec("INSERT INTO schema_migrations (id, started) VALUES ('0001_create', '2026-01-01 00:00:00')");
        $db = $this->db;
        $sleeper = function () use ($db) { $db->exec("DELETE FROM schema_migrations WHERE id = '0001_create' AND applied IS NULL"); };
        $waiting = new Migrator($this->db, 'sqlite', $dir, $sleeper);
        $this->assertSame([], $waiting->Run(), 'it applied it itself after waiting, so the list of what it waited for is empty');
        $this->assertSame(['0001_create'], $waiting->Applied());
        $this->remove($dir);
    }

    public function testGivingUpWhenTheOtherNodeNeverFinishes(): void
    {
        $this->migrator()->Applied();
        $this->db->exec("INSERT INTO schema_migrations (id, started) VALUES ('0001_create', '2026-01-01 00:00:00')");
        $waiting = new Migrator($this->db, 'sqlite', $this->dir, function () {});
        $waiting->waitSeconds = 3;
        $this->expectException(MigrationException::class);
        $this->expectExceptionMessage('gave up waiting for 0001_create');
        $waiting->Run();
    }

    public function testTheRealMigrationsParseForBothDatabases(): void
    {
        $real = __DIR__ . '/../../wifidb/sql/migrations';
        foreach (['mysql', 'sqlsrv'] as $dialect) {
            $files = glob("$real/$dialect/*.sql");
            $this->assertNotEmpty($files, $dialect);
            foreach ($files as $file) {
                $statements = Migrator::Split(file_get_contents($file), $dialect);
                $this->assertNotEmpty($statements, $file);
                foreach ($statements as $statement) {
                    // SQL Server batches keep their leading comment lines
                    $statement = preg_replace('/^(\s*--[^\n]*\n)+/', '', $statement);
                    $this->assertMatchesRegularExpression('/^\s*(CREATE|ALTER|INSERT|UPDATE|DROP)\b/i', $statement, $file);
                }
            }
        }
        $this->assertSame(
            array_map('basename', glob("$real/mysql/*.sql")),
            array_map('basename', glob("$real/sqlsrv/*.sql")),
            'every migration exists for both databases'
        );
    }
}
