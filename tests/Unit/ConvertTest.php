<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/dbcore.inc.php';
require_once WIFIDB_LIB . '/convert.inc.php';

// dbcore does not declare the signal limits (PHP 8.2 deprecates creating them on the fly), so declare them here.
class ConvertUnderTest extends convert
{
    public $dBmMaxSignal = -30;
    public $dBmDissociationSignal = -85;
}

// These conversions need no database, so the object is built without running dbcore constructor.
final class ConvertTest extends TestCase
{
    private ConvertUnderTest $convert;

    protected function setUp(): void
    {
        $this->convert = (new ReflectionClass(ConvertUnderTest::class))->newInstanceWithoutConstructor();
    }

    public function testSignalConversionEndpoints(): void
    {
        $this->assertEquals(-30, $this->convert->Sig2dBm(100));
        $this->assertEquals(-99, $this->convert->Sig2dBm(0));
        $this->assertEquals(100, $this->convert->dBm2Sig(-30));
        $this->assertEquals(20, $this->convert->dBm2Sig(-85));
        $this->assertEquals(0, $this->convert->dBm2Sig(-120), 'values below the floor clamp to 0');
    }

    public function testSignalConversionRoundTrips(): void
    {
        foreach ([0, 25, 50, 75, 100] as $sig) {
            $this->assertEqualsWithDelta($sig, $this->convert->dBm2Sig($this->convert->Sig2dBm($sig)), 1);
        }
    }

    public static function degreeMinuteCases(): array
    {
        return [
            'decimal degrees to DMM'    => ['43.1234567', '4307.4074'],
            'negative decimal degrees'  => ['-70.5000000', '-7030.0000'],
            'already DMM'               => ['4307.4074', '4307.4074'],
            'N prefix is dropped'       => ['N 4307.4074', '4307.4074'],
            'S prefix becomes negative' => ['S 4307.4074', '-4307.4074'],
        ];
    }

    #[DataProvider('degreeMinuteCases')]
    public function testAll2dm(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->convert->all2dm($input));
    }

    public static function decimalDegreeCases(): array
    {
        return [
            'DMM to decimal degrees' => ['4307.4074', '43.1234567'],
            'negative'               => ['-7030.0000', '-70.5000000'],
            'empty input'            => ['', '0.0000000'],
            'no decimal point'       => ['4307', '0.0000000'],
        ];
    }

    #[DataProvider('decimalDegreeCases')]
    public function testDm2dd(string $input, string $expected): void
    {
        $this->assertSame($expected, $this->convert->dm2dd($input));
    }

    public static function frequencyCases(): array
    {
        return [
            'channel 6'       => [6, [6, '802.11g']],
            'channel 36'      => [36, [36, '802.11n']],
            '2.4 GHz MHz'     => [2437, [6, '802.11g']],
            'channel 14 freq' => [2484, [14, '802.11g']],
            '5 GHz MHz'       => [5180, [36, '802.11n']],
            '6 GHz MHz'       => [5955, [1, '802.11ax']],
            'zero'            => [0, [0, '802.11g']],
        ];
    }

    #[DataProvider('frequencyCases')]
    public function testFindFreq($input, array $expected): void
    {
        $this->assertSame($expected, $this->convert->findFreq($input));
    }

    public function testFormatSsid(): void
    {
        $this->assertSame('[Blank SSID]', $this->convert->formatSSID(''));
        $this->assertSame('Cafe WiFi', $this->convert->formatSSID('Cafe WiFi'));
        $this->assertSame("[bad\x01name]", $this->convert->formatSSID("bad\x01name"));
    }
}
