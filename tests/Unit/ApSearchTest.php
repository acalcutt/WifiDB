<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/searchconditions.inc.php';

// The AP search's conditions (search page, its map and exports, api/search.php): each field's match, blank fields
// left out, a value with % a LIKE pattern as given, and CHAN compared as text where the database needs it.
final class ApSearchTest extends TestCase
{
    public function testOpsComeFromTheRequestWithEqualsForAnythingElse(): void
    {
        $ops = WifidbApSearch::Ops(['ssid_op' => 'contains', 'mac_op' => 'starts', 'chan_op' => "ends' OR 1=1"]);
        $this->assertSame(['ssid' => 'contains', 'mac' => 'starts', 'radio' => 'equals', 'chan' => 'equals', 'auth' => 'equals', 'encry' => 'equals'], $ops);
        $this->assertSame('&ssid_op=contains&mac_op=starts&radio_op=equals&chan_op=equals&auth_op=equals&encry_op=equals', WifidbApSearch::OpsQuery($ops));
    }

    public function testEachDatabaseGetsItsOwnColumnNames(): void
    {
        $values = ['ssid' => 'Coffee', 'chan' => '6', 'auth' => ' '];
        $ops = ['ssid' => 'starts'];
        $this->assertSame(['where' => ['wap.SSID LIKE ?', 'wap.CHAN = ?'], 'params' => ['Coffee%', '6']],
            WifidbApSearch::Conditions('mysql', 'wap', $values, $ops), 'a blank field is left out');
        $this->assertSame(['wap."SSID" LIKE ?', 'wap."CHAN"::text = ?'], WifidbApSearch::Conditions('pgsql', 'wap', $values, $ops)['where']);
        $this->assertSame(['wifi_ap.SSID LIKE ?', 'CAST(wifi_ap.CHAN AS varchar(11)) = ?'], WifidbApSearch::Conditions('sqlsrv', 'wifi_ap', $values, $ops)['where']);
        $this->assertSame(['where' => [], 'params' => []], WifidbApSearch::Conditions('mysql', 'wap', [], []));
    }

    public static function searches(): array
    {
        return [
            'equals, the default'      => [['ssid' => 'Coffee'], [], ['Coffee']],
            'contains'                 => [['ssid' => 'offee'], ['ssid' => 'contains'], ['Coffee', 'Coffee Shop', 'MyCoffee']],
            'starts'                   => [['ssid' => 'Coffee'], ['ssid' => 'starts'], ['Coffee', 'Coffee Shop']],
            'ends'                     => [['ssid' => 'Coffee'], ['ssid' => 'ends'], ['Coffee', 'MyCoffee']],
            'a % is a pattern as typed' => [['ssid' => 'Coffee%'], ['ssid' => 'equals'], ['Coffee', 'Coffee Shop']],
            'fields together'          => [['ssid' => 'Coffee', 'chan' => '11'], ['ssid' => 'starts'], ['Coffee Shop']],
        ];
    }

    /** @dataProvider searches */
    #[\PHPUnit\Framework\Attributes\DataProvider('searches')]
    public function testWhatEachMatchFinds(array $values, array $ops, array $expected): void
    {
        $db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $db->exec('CREATE TABLE wifi_ap (SSID TEXT, BSSID TEXT, RADTYPE TEXT, CHAN INTEGER, AUTH TEXT, ENCR TEXT)');
        foreach ([['Coffee', 6], ['Coffee Shop', 11], ['MyCoffee', 1], ['Tea', 6]] as [$ssid, $chan]) {
            $db->prepare("INSERT INTO wifi_ap VALUES (?, '00:11:22:33:44:55', '802.11n', ?, 'WPA2', 'AES')")->execute([$ssid, $chan]);
        }
        $cond = WifidbApSearch::Conditions('mysql', 'wifi_ap', $values, WifidbApSearch::Ops(array_combine(
            array_map(fn ($f) => $f . '_op', array_keys($ops)), array_values($ops))));
        $prep = $db->prepare('SELECT SSID FROM wifi_ap WHERE ' . implode(' AND ', $cond['where']) . ' ORDER BY SSID');
        $prep->execute($cond['params']);
        $this->assertSame($expected, $prep->fetchAll(PDO::FETCH_COLUMN));
    }
}
