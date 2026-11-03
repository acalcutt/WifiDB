<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/mvt.inc.php';

// How a finished tile archive is put in place (mvt_publish_archive): pmtiles-swarm watches the folder for new names,
// and a node mirroring the archives saves them under the same names, so each build needs a name of its own.
final class MvtArchiveTest extends TestCase
{
    private string $dir;

    protected function setUp(): void
    {
        $this->dir = sys_get_temp_dir() . '/wifidb-mvt-' . getmypid() . '-' . bin2hex(random_bytes(4));
        mkdir($this->dir);
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob($this->dir . '/*') ?: []);
        rmdir($this->dir);
    }

    private function publish(string $bytes, string $bucket = 'daily', int $keep = 2): ?string
    {
        file_put_contents("{$this->dir}/{$bucket}.pmtiles.building", $bytes);
        return mvt_publish_archive("{$this->dir}/{$bucket}.pmtiles.building", $this->dir, $bucket, $keep);
    }

    public function testEachBuildGetsANameOfItsOwnAndTheStableNameFollows(): void
    {
        $first = $this->publish('build A');
        $this->assertMatchesRegularExpression('#/daily-\d{14}\.pmtiles$#', (string)$first, 'the date and the time, UTC');
        sleep(1);
        $second = $this->publish('build B');
        $this->assertNotSame($first, $second, 'an hourly rebuild the same day is a new file, not a replaced one');
        $this->assertSame('build A', file_get_contents($first), 'the first build is left as the swarm saw it');
        $this->assertSame('build B', file_get_contents("{$this->dir}/daily.pmtiles"));
        $this->assertSame(fileinode($second), fileinode("{$this->dir}/daily.pmtiles"), 'the stable name is a hard link');
        $this->assertFileDoesNotExist("{$this->dir}/daily.pmtiles.building");
    }

    public function testOlderBuildsAreRetiredIncludingOnesNamedByTheDateAlone(): void
    {
        // from before the time was in the name: today's sorts below today's new builds
        $today = gmdate('Ymd');
        file_put_contents("{$this->dir}/daily-20260101.pmtiles", 'old');
        file_put_contents("{$this->dir}/daily-{$today}.pmtiles", 'today, by the date alone');
        file_put_contents("{$this->dir}/cell_daily-20260101.pmtiles", 'another bucket');
        $newest = $this->publish('new');
        $kept = glob("{$this->dir}/daily-[0-9]*.pmtiles");
        sort($kept);
        $this->assertSame(["{$this->dir}/daily-{$today}.pmtiles", $newest], $kept);
        $this->assertFileExists("{$this->dir}/cell_daily-20260101.pmtiles", "another bucket's builds are its own");

        $this->publish('one only', 'daily', 0);
        $this->assertCount(1, glob("{$this->dir}/daily-[0-9]*.pmtiles"), 'keep never reaches zero');
    }
}
