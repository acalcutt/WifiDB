<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/import_type.inc.php';

// Which importer an upload is for (lib/import_type.inc.php), on the cases lib/js/wifidb/import_type.js is tested with too.
final class ImportTypeTest extends TestCase
{
    public static function cases(): array
    {
        $out = [];
        foreach (json_decode(file_get_contents(__DIR__ . '/../fixtures/import_types.json'), true)['cases'] as $c) {
            $out[$c['why']] = [$c['name'], base64_decode($c['head']), $c['type']];
        }
        return $out;
    }

    /** @dataProvider cases */
    #[\PHPUnit\Framework\Attributes\DataProvider('cases')]
    public function testAFileIsTakenForWhatItIs(string $name, string $head, ?string $type): void
    {
        $this->assertSame($type, ImportType::Guess($name, $head));
        if ($type !== null) { $this->assertArrayHasKey($type, ImportType::TYPES, 'a type the importer knows'); }
    }

    public function testTheContentsWinOverTheName(): void
    {
        $this->assertSame('wiglewificsv', ImportType::Guess('drive.vs1', "WigleWifi-1.4,appRelease=2.53\r\n"));
        $this->assertSame('vistumbler', ImportType::Guess('upload.csv', "SSID,BSSID\r\n"), 'a CSV that says nothing is Vistumbler\'s');
    }

    public function testAFileOnDisk(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'wdbtype');
        file_put_contents($path, "BSSID,SSID,AuthMode,channel,frequency\n" . str_repeat("00:11:22:33:44:55,x,[ESS],6,2437\n", 500));
        $this->assertSame('swardriving', ImportType::GuessFile('scan.csv', $path));
        unlink($path);
        $this->assertSame('kismet', ImportType::GuessFile('scan.netxml', '/nonexistent/file'), 'a file that can\'t be read: its name');
    }
}
