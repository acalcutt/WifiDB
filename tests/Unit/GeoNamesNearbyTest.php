<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/geonames.inc.php';

// The places nearest a point (lib/geonames.inc.php): an access point's "places nearby" and api/geonames.php.
final class GeoNamesNearbyTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec('CREATE TABLE geonames (id INTEGER PRIMARY KEY, asciiname TEXT, country_code TEXT, admin1_code TEXT, admin2_code TEXT,
            timezone TEXT, latitude TEXT, longitude TEXT, lat REAL, lon REAL)');
        foreach ([
            ['Across the line', 42.40050, -71.05000],   // 60 m from the point, but in the next 0.1 degree square
            ['Same square', 42.35000, -71.05000],
            ['A hill', 42.39000, -71.04000],
            ['Lonely ranch', 31.00000, -110.00000],
        ] as $i => [$name, $lat, $lon]) {
            $this->db->prepare("INSERT INTO geonames VALUES (?, ?, 'US', 'MA', '025', 'America/New_York', ?, ?, ?, ?)")
                ->execute([$i + 1, $name, (string)$lat, (string)$lon, $lat, $lon]);
        }
    }

    public function testTheNearestComesFirstWhicheverSquareItIsIn(): void
    {
        $near = GeoNamesNearby::Nearest($this->db, 42.3999, -71.05);
        $this->assertSame(['Across the line', 'A hill', 'Same square'], array_column($near, 'asciiname'));
        $this->assertEqualsWithDelta(0.07, $near[0]['kilometers'], 0.01);
        $this->assertEqualsWithDelta($near[0]['kilometers'] / 1.609344, $near[0]['miles'], 1e-9);
        $this->assertArrayNotHasKey('lat', $near[0], 'the same keys the queries gave');
        $this->assertSame('42.4005', $near[0]['latitude']);
    }

    public function testPagingAndAPlaceFarOff(): void
    {
        $this->assertSame(['A hill'], array_column(GeoNamesNearby::Nearest($this->db, 42.3999, -71.05, 1, 1), 'asciiname'));
        $this->assertSame(['Lonely ranch'], array_column(GeoNamesNearby::Nearest($this->db, 30.0, -109.5), 'asciiname'),
            'a degree away: found by a wider box');
        $this->assertSame([], GeoNamesNearby::Nearest($this->db, -45.0, 170.0), 'nothing within a few hundred kilometres');
        $this->assertSame([], GeoNamesNearby::Nearest($this->db, 0.1, 0.1), 'a box edge at 0 (1.4E-17 as a float)');
    }

    public function testDistance(): void
    {
        $this->assertEqualsWithDelta(306, GeoNamesNearby::Kilometers(42.3601, -71.0589, 40.7128, -74.0060), 2, 'Boston to New York');
        $this->assertSame(0.0, GeoNamesNearby::Kilometers(10, 20, 10, 20));
    }
}
