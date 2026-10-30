<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Renders an access point's page (fetch.tpl) with data, in both themes, and checks the signal charts come out and that
// nothing in the data can break out of the attributes the chart script reads.
final class SignalChartRenderTest extends TestCase
{
    private static string $compileDir;

    public static function setUpBeforeClass(): void
    {
        self::$compileDir = sys_get_temp_dir() . '/wifidb-render-' . getmypid();
        mkdir(self::$compileDir, 0777, true);
    }

    public static function tearDownAfterClass(): void
    {
        foreach (glob(self::$compileDir . '/*') ?: [] as $file) { @unlink($file); }
        @rmdir(self::$compileDir);
    }

    public static function themes(): array
    {
        return ['classic' => ['vistumbler_classic'], 'mobile' => ['vistumbler_mobile']];
    }

    private function render(string $theme, array $signals, string $title = 'A title'): string
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(WIFIDB_WEB . "/themes/$theme/templates");
        $smarty->setCompileDir(self::$compileDir);
        $smarty->setCaching(Smarty::CACHING_OFF);
        $smarty->muteUndefinedOrNullWarnings();
        $smarty->assign('wifidb_host_url', 'https://wifidb.example.com/');
        $smarty->assign('themeurl', 'https://wifidb.example.com/themes/' . $theme . '/');
        $smarty->assign('wifidb_ap', ['id' => 4242, 'ssid' => 'Cafe', 'validgps' => 0]);
        $smarty->assign('wifidb_chart_all', wifidb_json_safe(wifidb_signal_points($signals)));
        $smarty->assign('wifidb_assoc_lists', [[
            'id' => 77, 'validgps' => 0, 'nu' => 1, 'file_user' => 'alice', 'file' => 'f.vs1', 'title' => $title,
            'notes' => '', 'date' => '2024-01-01', 'points' => count($signals), 'signals' => $signals,
            'chart' => wifidb_json_safe(wifidb_signal_points($signals)),
        ]]);
        return $smarty->fetch('fetch.tpl');
    }

    private function readings(): array
    {
        return [
            ['Sig' => 40, 'RSSI' => -70, 'GPS_Date' => '2024-01-01 10:00:00', 'Lat' => '1', 'Lon' => '2'],
            ['Sig' => 55, 'RSSI' => -62, 'GPS_Date' => '2024-01-01 10:00:05', 'Lat' => '1', 'Lon' => '2'],
        ];
    }

    #[DataProvider('themes')]
    public function testThePageHasAChartForEveryFileAndOneForTheWholePage(string $theme): void
    {
        $html = $this->render($theme, $this->readings());
        $this->assertSame(2, substr_count($html, '<div class="sigchart"'), 'one for the page, one for the file');
        $this->assertStringContainsString('lib/js/chart.min.js', $html);
        $this->assertStringContainsString('lib/js/wifidb/signalchart.js', $html);
        $this->assertStringContainsString('api/wifi/signal.png?ap=4242"', $html, 'the whole history as a picture');
        $this->assertStringContainsString('api/wifi/signal.png?ap=4242&amp;file=77"', $html, "this file's readings as a picture");
        $this->assertStringContainsString('wifidbInitSignalCharts(document.getElementById(tbodyid))', $html, 'a file draws its chart when it is opened');
    }

    #[DataProvider('themes')]
    public function testTheChartDataSurvivesTheRoundTripThroughTheAttribute(string $theme): void
    {
        $html = $this->render($theme, $this->readings());
        $this->assertSame(2, preg_match_all('/data-points="([^"]*)"/', $html, $m), 'both charts have data');
        foreach ($m[1] as $attribute) {
            $rows = json_decode(html_entity_decode($attribute, ENT_QUOTES, 'UTF-8'), true);
            $this->assertSame([['2024-01-01 10:00:00', 40, -70], ['2024-01-01 10:00:05', 55, -62]], $rows);
        }
    }

    #[DataProvider('themes')]
    public function testHostileDataCannotBreakOutOfTheAttributes(string $theme): void
    {
        $evil = '"><script>alert(1)</script>';
        $html = $this->render($theme, [['Sig' => 40, 'RSSI' => null, 'GPS_Date' => $evil, 'Lat' => '1', 'Lon' => '2']], $evil);
        $this->assertStringNotContainsString('<script>alert(1)', $html);
        $this->assertSame(2, preg_match_all('/data-points="([^"]*)"/', $html, $m), 'both charts are still there');
        $rows = json_decode(html_entity_decode($m[1][1], ENT_QUOTES, 'UTF-8'), true);
        $this->assertIsArray($rows);
        $this->assertSame(19, strlen($rows[0][0]), 'a date is cut to its 19 characters, whatever it held');
    }

    #[DataProvider('themes')]
    public function testAPageWithNoReadingsStillRenders(string $theme): void
    {
        $html = $this->render($theme, []);
        $this->assertStringContainsString('data-points="[]"', $html);
    }
}
