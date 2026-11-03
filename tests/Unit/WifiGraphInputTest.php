<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once WIFIDB_WEB . '/api/wifi/functions.php';

// The signal graph page (api/wifi) is opened by Vistumbler with a long address built from an access point's data, and
// by anyone who edits that address. These cover what reaches HTML and GD from it.
final class WifiGraphInputTest extends TestCase
{
    // The address Vistumbler builds (VistumblerMDB/Vistumbler.au3, _ViewInWifiDbGraph_Open): values are NOT URL-encoded,
    // the oldest signal comes first, gaps where the AP was marked dead are filled with "-0", and the whole address is cut
    // at 2048 characters at a dash, so Sig is last.
    public const VISTUMBLER_QUERY = 'SSID=My Net&Mac=00:11:22:33:44:55&Manuf=Cisco&Auth=WPA2-Personal&Encry=CCMP&radio=802.11n&Chn=6'
        . '&Lat=N 43.1234&Long=W 70.1234&BTx=54&OTx=54&FA=2024-01-02 10:11:12&LU=2024-01-02 10:12:12&NT=Infrastructure&Label=Unknown'
        . '&Sig=0-45-50-0-0-62-70';

    public function testVistumblersAddressStillParsesTheWayThePageReadsIt(): void
    {
        parse_str(self::VISTUMBLER_QUERY, $q);
        foreach (['SSID', 'Mac', 'Manuf', 'Auth', 'Encry', 'radio', 'Chn', 'Lat', 'Long', 'BTx', 'OTx', 'FA', 'LU', 'NT', 'Label', 'Sig'] as $name) {
            $this->assertArrayHasKey($name, $q, "the page reads $name");
        }
        $this->assertSame('My Net', $q['SSID']);
        $this->assertSame('0-45-50-0-0-62-70', wifi_sig_spec($q['Sig']));
    }

    public static function dangerousText(): array
    {
        return [
            'a script'          => ['<script>alert(1)</script>'],
            'a quote break'     => ['x" onmouseover="alert(1)'],
            'a single quote'    => ["x' onfocus='alert(1)"],
            'an ampersand'      => ['a&b<c>'],
        ];
    }

    #[DataProvider('dangerousText')]
    public function testHtmlEscapingStopsMarkupAndAttributeBreakouts(string $value): void
    {
        $out = h($value);
        $this->assertStringNotContainsString('<', $out);
        $this->assertStringNotContainsString('>', $out);
        $this->assertStringNotContainsString('"', $out);
        $this->assertStringNotContainsString("'", $out);
        $this->assertSame($value, html_entity_decode($out, ENT_QUOTES, 'UTF-8'), 'it still reads the same to a person');
    }

    public function testHtmlEscapingSurvivesBrokenUtf8(): void
    {
        $this->assertSame('x&#039;', h("x'"));
        $this->assertIsString(h("bad \xff byte"));
    }

    public function testTextIsCleanedAndLimited(): void
    {
        $this->assertSame('a b c', wifi_clean_text("a\nb\r\tc"));
        $this->assertSame(64, strlen(wifi_clean_text(str_repeat('x', 500))));
        $this->assertSame(5, mb_strlen(wifi_clean_text('ÅÅÅÅÅÅÅÅ', 5)));
        $this->assertSame('', wifi_clean_text(null));
    }

    public static function colors(): array
    {
        return [
            'a good color'       => ['010:200:255', '000:000:000', '010:200:255'],
            'short numbers'      => ['1:2:3', '000:000:000', '001:002:003'],
            'too large clamps'   => ['999:300:256', '000:000:000', '255:255:255'],
            'random is kept'     => ['rand', '000:000:000', 'rand'],
            'junk uses default'  => ['red', '255:000:000', '255:000:000'],
            'injection'          => ['1:2:3<script>', '255:255:255', '255:255:255'],
            'negative'           => ['-1:2:3', '000:000:000', '000:000:000'],
            'missing'            => ['', '064:064:064', '064:064:064'],
        ];
    }

    #[DataProvider('colors')]
    public function testColorsAreValidatedBeforeTheyReachGd(string $given, string $default, string $expected): void
    {
        $this->assertSame($expected, wifi_color_spec($given, $default));
    }

    public function testSignalListKeepsOnlyWholeNumbersZeroToOneHundred(): void
    {
        $this->assertSame('0-45-50', wifi_sig_spec('0-45-50'));
        $this->assertSame('100-100-7', wifi_sig_spec('100-250-7'), 'over 100 is clamped to 100');
        $this->assertSame('1-3', wifi_sig_spec('1-x-3--<script>'), 'anything that is not a whole number is dropped');
        $this->assertSame('0', wifi_sig_spec(''), 'an empty list is one zero, so there is something to draw');
        $this->assertSame('0', wifi_sig_spec('abc'));
        $this->assertSame('5', wifi_sig_spec('-5-'));
    }

    public function testSignalListIsBounded(): void
    {
        $many = implode('-', array_fill(0, 100000, '50'));
        $this->assertCount(3000, explode('-', wifi_sig_spec($many)));
        $this->assertCount(10, explode('-', wifi_sig_spec($many, 10)));
    }

    // ── the form page (genline.php) now points at the image address instead of writing a file ──

    private function shown(array $post, string $type = 'bar'): string
    {
        $_POST = $post;
        ob_start();
        wifi_show_graph($type);
        $html = ob_get_clean();
        $_POST = [];
        return $html;
    }

    public function testTheFormPageShowsAnImageFromTheImageAddress(): void
    {
        $html = $this->shown(['ssid' => 'My Net', 'mac' => '00:11:22:33:44:55', 'sig' => '0-45-50', 'text' => '255:255:255', 'bgc' => '000:000:000', 'linec' => '255:000:000'], 'line');
        $this->assertSame(1, preg_match('/<img src="(signal\.php\?[^"]+)"/', $html, $m));
        $url = html_entity_decode($m[1], ENT_QUOTES);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('line', $q['type']);
        $this->assertSame('My Net', $q['SSID']);
        $this->assertSame('0-45-50', $q['Sig']);
        $this->assertSame('000:000:000', $q['bg']);
        $this->assertSame('255:000:000', $q['line']);
        $this->assertStringContainsString('<h1>My Net</h1>', $html);
        $this->assertStringNotContainsString('tmp/', $html, 'no file is written or linked');
    }

    public function testTheFormPageStaysSafeWithHostileInput(): void
    {
        $html = $this->shown(['ssid' => '"><script>alert(1)</script>', 'sig' => ['x'], 'mac' => "a\r\nb"]);
        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringNotContainsString('" ><', $html);
        $this->assertSame(1, preg_match('/<img src="([^"]*)"/', $html), 'the attribute is still intact');
    }

    public function testTheFormPageWithNothingPostedStillWorks(): void
    {
        $html = $this->shown([]);
        $this->assertStringContainsString('<h1>UNNAMED</h1>', $html);
        $this->assertStringContainsString('signal.php?type=bar', $html);
    }
}
