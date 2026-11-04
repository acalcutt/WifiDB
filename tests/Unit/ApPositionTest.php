<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/position.inc.php';

final class ApPositionTest extends TestCase
{
    // about 43.1 N, 70.9 W; 0.0001 degrees of latitude is about 11 m
    private const LAT = 43.1;
    private const LON = -70.9;

    private function at(float $north, float $east, ?int $dbm, array $extra = []): array
    {
        return ['lat' => self::LAT + $north / 111320, 'lon' => self::LON + $east / (111320 * cos(deg2rad(self::LAT))), 'dbm' => $dbm] + $extra;
    }

    public function testNothingUsableGivesNothing(): void
    {
        $this->assertNull(ApPosition::Estimate([]));
        $this->assertNull(ApPosition::Estimate([['lat' => 0, 'lon' => 0, 'dbm' => -50]]), 'no fix');
    }

    public function testOneReadingIsWhereItWas(): void
    {
        $est = ApPosition::Estimate([$this->at(0, 0, -60)]);
        $this->assertEqualsWithDelta(self::LAT, $est['lat'], 1e-9);
        $this->assertEqualsWithDelta(self::LON, $est['lon'], 1e-9);
        $this->assertNull($est['accuracy'], 'one reading with no accuracy of its own says nothing about it');
        $this->assertSame(1, $est['points']);
    }

    public function testReadingsAllRoundPutItInTheMiddle(): void
    {
        // driven past on four sides, as strong on each: the device is in the middle, not at any one reading
        $est = ApPosition::Estimate([$this->at(50, 0, -60), $this->at(-50, 0, -60), $this->at(0, 50, -60), $this->at(0, -50, -60)]);
        $this->assertLessThan(1, ApPosition::Meters(self::LAT, self::LON, $est['lat'], $est['lon']));
        $this->assertEqualsWithDelta(50, $est['accuracy'], 1);
        $this->assertSame(4, $est['points']);
    }

    public function testStrongerReadingsPullItCloser(): void
    {
        $est = ApPosition::Estimate([$this->at(0, 0, -50), $this->at(100, 0, -62)]);
        $d = ApPosition::Meters(self::LAT, self::LON, $est['lat'], $est['lon']);
        // 12 dB weaker is a quarter of the weight: 100 m * 0.25 / 1.25
        $this->assertEqualsWithDelta(20, $d, 1);
    }

    public function testFarWeakerReadingsAreLeftOut(): void
    {
        $est = ApPosition::Estimate([$this->at(0, 0, -50), $this->at(500, 0, -80)]);
        $this->assertSame(1, $est['points'], 'more than 15 dB weaker than the strongest');
        $this->assertLessThan(1, ApPosition::Meters(self::LAT, self::LON, $est['lat'], $est['lon']));
    }

    public function testPoorFixesAreLeftOutWhenThereAreGoodOnes(): void
    {
        $est = ApPosition::Estimate([$this->at(0, 0, -60, ['accuracy' => 5]), $this->at(300, 0, -50, ['accuracy' => 400])]);
        $this->assertSame(1, $est['points']);
        $this->assertEqualsWithDelta(5, $est['accuracy'], 0.01, 'the fix it used was good to 5 m');
        $only = ApPosition::Estimate([$this->at(300, 0, -50, ['hdop' => 90])]);
        $this->assertSame(1, $only['points'], 'but used when there is nothing better');
    }

    public function testItWorksAcrossTheDateLine(): void
    {
        $est = ApPosition::Estimate([['lat' => 10, 'lon' => 179.9999, 'dbm' => -60], ['lat' => 10, 'lon' => -179.9999, 'dbm' => -60]]);
        $this->assertEqualsWithDelta(180, abs($est['lon']), 0.001, 'not halfway round the world at 0');
    }

    public function testAltitudeIsAveragedWhenGiven(): void
    {
        $est = ApPosition::Estimate([$this->at(0, 0, -60, ['alt' => 100]), $this->at(10, 0, -60, ['alt' => 120]), $this->at(5, 0, -60)]);
        $this->assertEqualsWithDelta(110, $est['alt'], 0.01);
    }

    public function testDegreesAndMinutesBothWays(): void
    {
        $this->assertEqualsWithDelta(43.123456, ApPosition::DmToDd(ApPosition::DdToDm(43.123456)), 0.000002);
        $this->assertSame('-7053.4000', ApPosition::DdToDm(-70.89));
        $this->assertEqualsWithDelta(-70.89, ApPosition::DmToDd('-7053.4000'), 1e-9);
        $this->assertSame('4300.0000', ApPosition::DdToDm(42.999999999), 'minutes that round to 60 carry over');
    }

    public function testSignalPercentTurnsIntoDbmAsTheSiteDoes(): void
    {
        $this->assertSame(-30, ApPosition::SigToDbm(100));
        $this->assertSame(-85, ApPosition::SigToDbm(20));
    }

    public function testUpdateStoresItAndEmptiesItWithNoFix(): void
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec("CREATE TABLE wifi_ap (AP_ID INTEGER PRIMARY KEY, est_lat TEXT, est_lon TEXT, est_alt REAL, est_accuracy REAL, est_points INTEGER)");
        $db->exec("CREATE TABLE wifi_gps (GPS_ID INTEGER PRIMARY KEY, Lat TEXT, Lon TEXT, Alt REAL, AccuracyMeters REAL, HorDilPitch REAL)");
        $db->exec("CREATE TABLE wifi_hist (Hist_ID INTEGER PRIMARY KEY, AP_ID INTEGER, GPS_ID INTEGER, RSSI INTEGER, Sig INTEGER)");
        $db->exec("INSERT INTO wifi_ap (AP_ID) VALUES (1), (2)");
        $db->exec("INSERT INTO wifi_gps VALUES (1, '4306.0000', '-7054.0000', 10, 5, 1), (2, '4306.0100', '-7054.0000', 12, 5, 1), (3, '0.0000', '0.0000', 0, 0, 0)");
        // AP 1: two readings, one by signal percentage only; AP 2: a reading with no fix
        $db->exec("INSERT INTO wifi_hist (AP_ID, GPS_ID, RSSI, Sig) VALUES (1, 1, -60, 0), (1, 2, NULL, 64), (2, 3, -50, 90)");

        $p = new ApPosition($db, 'sqlsrv');
        $row = $p->Update(1);
        $this->assertSame(2, $row['points']);
        $stored = $db->query("SELECT est_lat, est_lon, est_points FROM wifi_ap WHERE AP_ID = 1")->fetch(PDO::FETCH_ASSOC);
        $this->assertGreaterThan(4306.0, (float)$stored['est_lat']);
        $this->assertLessThan(4306.01, (float)$stored['est_lat']);
        $this->assertEqualsWithDelta(-7054.0, (float)$stored['est_lon'], 0.00001);
        $this->assertSame(2, (int)$stored['est_points']);

        $this->assertNull($p->Update(2));
        $this->assertNull($db->query("SELECT est_lat FROM wifi_ap WHERE AP_ID = 2")->fetchColumn());
    }
}
