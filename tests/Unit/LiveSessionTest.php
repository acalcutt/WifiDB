<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/live_session.inc.php';

// The live API's version 2 sessions (lib/live_session.inc.php): a member's session, and what it sent as a VS1 for the
// importer once it's finished.
final class LiveSessionTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec('CREATE TABLE live_titles (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, title TEXT, notes TEXT, session_id TEXT, started_at TEXT, completed_at TEXT)');
        $this->db->exec('CREATE TABLE live_users (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, session_id TEXT, title_id INTEGER)');
        $this->db->exec('CREATE TABLE live_gps (id INTEGER PRIMARY KEY AUTOINCREMENT, lat TEXT, "long" TEXT, sats INTEGER, hdp TEXT, alt TEXT, geo TEXT, kmh TEXT, mph TEXT, track TEXT, hist_date TEXT, session_id TEXT)');
        $this->db->exec('CREATE TABLE live_signals (id INTEGER PRIMARY KEY AUTOINCREMENT, ap_hash TEXT, "signal" INTEGER, rssi TEXT, gps_id INTEGER, time_stamp TEXT)');
        $this->db->exec('CREATE TABLE live_aps (id INTEGER PRIMARY KEY AUTOINCREMENT, ssid TEXT, mac TEXT, auth TEXT, encry TEXT, sectype INTEGER, radio TEXT, chan INTEGER, sig TEXT,
            username TEXT, session_id TEXT, ap_hash TEXT, "BTx" TEXT, "OTx" TEXT, "NT" TEXT, "Label" TEXT, "FA" TEXT, "LA" TEXT, lat TEXT, "long" TEXT)');
    }

    public function testASessionIsItsMembersUntilItsFinished(): void
    {
        $live = new LiveSession($this->db, 'pgsql');
        $id = $live->Start('alice', 'Morning drive', 'Route 9');
        $this->assertMatchesRegularExpression('/^[0-9a-f]{64}$/', $id);
        $s = $live->Find($id);
        $this->assertSame(['alice', 'Morning drive', 'Route 9'], [$s['username'], $s['title'], $s['notes']]);
        $this->assertNull($s['completed_at']);
        $this->assertNull($live->Find(str_repeat('0', 64)), 'an ID WiFiDB never handed out');
        $this->assertNull($live->Find('5e8c0d0b6a1f2e'), 'a version 1 ID, made up by the client');
        $this->assertNotSame('', $live->Find($live->Start('bob', '', ''))['title'], 'no title: when it started');
        $live->Finished($s['title_id']);
        $this->assertNotNull($live->Find($id)['completed_at']);
    }

    public function testWhatWasSentBecomesAVs1(): void
    {
        $live = new LiveSession($this->db, 'pgsql');
        $id = $live->Start('alice', 'Drive', '');
        $gps = $this->db->prepare('INSERT INTO live_gps (lat, "long", sats, hdp, alt, geo, kmh, mph, track, hist_date, session_id) VALUES (?, ?, 7, 1.2, 30, -33, 50, 31, 90, ?, ?)');
        $gps->execute(['4221.6000', '-7103.5000', '2026-11-03 14:00:01.250', $id]);
        $g1 = $this->db->lastInsertId();
        $gps->execute(['4221.7000', '-7103.6000', '2026-11-03 14:00:05.000', $id]);
        $g2 = $this->db->lastInsertId();
        $gps->execute(['1.0000', '1.0000', '2026-11-03 14:00:00', 'someone-elses']);
        $sig = $this->db->prepare('INSERT INTO live_signals (ap_hash, "signal", rssi, gps_id, time_stamp) VALUES (?, ?, ?, ?, ?)');
        $sig->execute(['h1', 60, '-70', $g1, '']);
        $s1 = $this->db->lastInsertId();
        $sig->execute(['h1', 80, '-55', $g2, '']);
        $s2 = $this->db->lastInsertId();
        $ap = $this->db->prepare('INSERT INTO live_aps (ssid, mac, auth, encry, sectype, radio, chan, sig, username, session_id, ap_hash, "BTx", "OTx", "NT", "Label")
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
        $ap->execute(['Cafe|Net', '00:11:22:33:44:55', 'WPA2-Personal', 'CCMP', 3, '802.11n', 6, "$s1|$g1~$s2|$g2", 'alice', $id, 'h1', '54', '', 'Infrastructure', '']);
        $ap->execute(['NoReadings', '00:11:22:33:44:66', 'Open', 'None', 1, '802.11g', 1, '', 'alice', $id, 'h2', '', '', 'Infrastructure', '']);

        [$vs1, $count] = $live->Vs1($id, 'test');
        $this->assertSame(1, $count, 'an access point without readings is left out');
        $lines = array_values(array_filter(explode("\r\n", $vs1), fn ($l) => $l !== '' && $l[0] !== '#'));
        $this->assertSame([
            '1|4221.6000|-7103.5000|7|1.2|30|-33|50|31|90|2026-11-03|14:00:01.250',
            '2|4221.7000|-7103.6000|7|1.2|30|-33|50|31|90|2026-11-03|14:00:05.000',
            'CafeNet|00:11:22:33:44:55||WPA2-Personal|CCMP|3|802.11n|6|54||80|-55|Infrastructure||1,60,-70\\2,80,-55',
        ], $lines);
        $this->assertCount(12, explode('|', $lines[0]), 'the importer reads a 12-field line as a GPS point');
        $this->assertCount(15, explode('|', $lines[2]), '...and a 15-field one as an access point');
    }
}
