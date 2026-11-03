<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/health.inc.php';

final class HealthTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/health-' . getmypid() . '-' . uniqid();
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->dir . '/{,.}*', GLOB_BRACE) ?: [] as $f) { if (is_file($f)) { unlink($f); } }
        @chmod($this->dir, 0755);
        @rmdir($this->dir);
    }

    private function db(): callable
    {
        return fn () => new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    public function testReadyWhenTheDatabaseAnswersAndFoldersAreWritable(): void
    {
        $r = wifidb_readiness($this->db(), ['uploads' => $this->dir]);
        $this->assertTrue($r['ok']);
        $this->assertSame([], $r['problems']);
        $this->assertSame([], glob($this->dir . '/.ready-*'), 'it cleans up after itself');
    }

    public function testNotReadyWhenTheDatabaseCannotBeReached(): void
    {
        $r = wifidb_readiness(function () { throw new PDOException('SQLSTATE[HY000] [2002] Connection refused to db.internal:3306 as secretuser'); }, ['uploads' => $this->dir]);
        $this->assertFalse($r['ok']);
        $this->assertSame(['database unreachable'], $r['problems'], 'a short reason, with no host or user in it');
    }

    public function testNotReadyWhenAFolderIsMissing(): void
    {
        $r = wifidb_readiness($this->db(), ['uploads' => $this->dir . '/nope']);
        $this->assertSame(['uploads missing'], $r['problems']);
    }

    public function testNotReadyWhenAFolderCannotBeWrittenTo(): void
    {
        if (function_exists('posix_geteuid') && posix_geteuid() === 0) { $this->markTestSkipped('root can write anywhere'); }
        chmod($this->dir, 0555);
        $r = wifidb_readiness($this->db(), ['generated' => $this->dir]);
        $this->assertSame(['generated not writable'], $r['problems']);
    }

    public function testEveryProblemIsReported(): void
    {
        $r = wifidb_readiness(function () { throw new RuntimeException('x'); }, ['uploads' => $this->dir . '/a', 'generated' => $this->dir . '/b']);
        $this->assertSame(['database unreachable', 'uploads missing', 'generated missing'], $r['problems']);
    }

    public function testItReportsHowLongItTook(): void
    {
        $this->assertIsInt(wifidb_readiness($this->db(), [])['ms']);
    }
}
