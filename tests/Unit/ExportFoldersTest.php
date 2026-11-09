<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/dbcore.inc.php';
require_once WIFIDB_LIB . '/export.inc.php';

// The folders the export daemon saves its KMZ files in, which nothing else makes.
final class ExportFoldersTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/wifidb-export-test-' . getmypid() . '-' . bin2hex(random_bytes(3));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        exec('rm -rf ' . escapeshellarg($this->dir));
    }

    private function export(string $path): export
    {
        $export = (new ReflectionClass(export::class))->newInstanceWithoutConstructor();
        $export->PATH = $path;
        $export->daemon_out = $path . 'out/daemon/';
        return $export;
    }

    public function testANewInstallGetsEveryFolderTheExportSavesIn(): void
    {
        $this->export("$this->dir/")->CreateDaemonFolders();
        foreach (['kmz/full/labeled', 'kmz/full/unlabeled', 'kmz/incremental/labeled', 'kmz/incremental/unlabeled', 'daemon', 'daemon/history'] as $folder) {
            $this->assertDirectoryExists("$this->dir/out/$folder");
        }
    }

    public function testFoldersAlreadyThereAreLeftAlone(): void
    {
        $export = $this->export("$this->dir/");
        $export->CreateDaemonFolders();
        file_put_contents("$this->dir/out/kmz/full/labeled/full_db.kmz", 'kept');
        $export->CreateDaemonFolders();
        $this->assertSame('kept', file_get_contents("$this->dir/out/kmz/full/labeled/full_db.kmz"));
    }

    public function testAFolderThatCannotBeMadeStopsTheRunBeforeItStarts(): void
    {
        file_put_contents("$this->dir/out", 'a file where the folder should be');
        $this->expectExceptionMessage('Could not create the export folder');
        $this->export("$this->dir/")->CreateDaemonFolders();
    }
}
