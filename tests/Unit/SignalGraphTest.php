<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/signalgraph.inc.php';

final class SignalGraphTest extends TestCase
{
    private function query(array $override = []): array
    {
        parse_str(WifiGraphInputTest::VISTUMBLER_QUERY, $q);
        return array_replace($q, $override);
    }

    private function dom(string $svg): DOMDocument
    {
        $dom = new DOMDocument();
        $this->assertTrue($dom->loadXML($svg), 'the SVG is well-formed XML');
        return $dom;
    }

    // ── reading Vistumbler's address ──

    public function testVistumblersAddressBecomesAGraph(): void
    {
        $spec = SignalGraph::FromQuery($this->query());
        $this->assertSame('My Net', $spec['ssid']);
        $this->assertSame('00:11:22:33:44:55', $spec['mac']);
        $this->assertSame('Cisco', $spec['man']);
        $this->assertSame('6', $spec['chan']);
        $this->assertSame([0, 45, 50, 0, 0, 62, 70], $spec['sig']);
        $this->assertSame('bar', $spec['type']);
        $this->assertSame([255, 255, 255], $spec['text']);
        $this->assertSame([0, 0, 0], $spec['bg']);
    }

    public function testNewStyleNamesAndOptionsWork(): void
    {
        $spec = SignalGraph::FromQuery(['ssid' => 'x', 'sig' => '10-20', 'type' => 'line', 'line' => '0:255:0', 'bg' => '255:255:255']);
        $this->assertSame('x', $spec['ssid']);
        $this->assertSame([10, 20], $spec['sig']);
        $this->assertSame('line', $spec['type']);
        $this->assertSame([0, 255, 0], $spec['line']);
        $this->assertSame([255, 255, 255], $spec['bg']);
    }

    public function testAnEmptyAddressStillMakesAGraph(): void
    {
        $spec = SignalGraph::FromQuery([]);
        $this->assertSame('UNNAMED', $spec['ssid']);
        $this->assertSame([0], $spec['sig']);
        $this->dom(SignalGraph::Svg($spec));
    }

    public function testRandomColorsAreTheSameEveryTime(): void
    {
        $a = SignalGraph::FromQuery($this->query(['line' => 'rand', 'text' => 'rand']));
        $b = SignalGraph::FromQuery($this->query(['line' => 'rand', 'text' => 'rand']));
        $this->assertSame($a['line'], $b['line'], 'so the picture can be cached');
        $this->assertSame($a['text'], $b['text']);
        $c = SignalGraph::FromQuery($this->query(['Mac' => 'aa:bb:cc:dd:ee:ff', 'line' => 'rand']));
        $this->assertNotSame($a['line'], $c['line'], 'a different access point gets a different color');
        foreach (array_merge($a['line'], $a['text']) as $v) { $this->assertGreaterThanOrEqual(25, $v); $this->assertLessThanOrEqual(220, $v); }
    }

    public function testBadColorsFallBackAndLargeOnesClamp(): void
    {
        $spec = SignalGraph::FromQuery($this->query(['line' => 'javascript:1', 'text' => '999:999:999', 'bg' => '-1:0:0']));
        $this->assertSame([255, 0, 0], $spec['line']);
        $this->assertSame([255, 255, 255], $spec['text']);
        $this->assertSame([0, 0, 0], $spec['bg']);
    }

    public function testArraysInTheAddressAreIgnored(): void
    {
        $spec = SignalGraph::FromQuery(['SSID' => ['a', 'b'], 'Sig' => ['1'], 'type' => ['line'], 'line' => ['1:2:3']]);
        $this->assertSame('UNNAMED', $spec['ssid']);
        $this->assertSame([0], $spec['sig']);
        $this->assertSame('bar', $spec['type']);
        $this->assertSame([255, 0, 0], $spec['line']);
    }

    // ── SVG ──

    public function testSvgIsWellFormedAndShowsTheData(): void
    {
        $svg = SignalGraph::Svg(SignalGraph::FromQuery($this->query()));
        $dom = $this->dom($svg);
        $this->assertSame('svg', $dom->documentElement->localName);
        $this->assertStringContainsString('SSID: My Net', $svg);
        $this->assertStringContainsString('Mac: 00:11:22:33:44:55', $svg);
        $this->assertSame(4, $dom->getElementsByTagName('rect')->length - 1, 'one bar for each of the 4 non-zero samples (and the background)');
    }

    public function testLineGraphDrawsOnePointPerSample(): void
    {
        $dom = $this->dom(SignalGraph::Svg(SignalGraph::FromQuery($this->query(['type' => 'line']))));
        $poly = $dom->getElementsByTagName('polyline')->item(0);
        $this->assertNotNull($poly);
        $this->assertCount(7, explode(' ', $poly->getAttribute('points')));
        $this->assertSame('20,460', explode(' ', $poly->getAttribute('points'))[0], 'a 0 signal sits on the baseline');
        $this->assertSame('26,280', explode(' ', $poly->getAttribute('points'))[1], 'a signal of 45 is 180px up, one step along');
    }

    public static function attacks(): array
    {
        return [
            'a script'       => ['</text><script>alert(1)</script>'],
            'an attribute'   => ['x" onload="alert(1)'],
            'an entity'      => ['&lt;b&gt;&amp;'],
            'xml declaration' => ['<?xml version="1.0"?>'],
            'bad utf-8'      => ["bad \xff \xfe bytes"],
        ];
    }

    #[DataProvider('attacks')]
    public function testAnythingInTheTextCannotBreakTheSvg(string $evil): void
    {
        $svg = SignalGraph::Svg(SignalGraph::FromQuery($this->query(['SSID' => $evil, 'Label' => $evil, 'Manuf' => $evil])));
        $dom = $this->dom($svg);
        $this->assertSame(0, $dom->getElementsByTagName('script')->length);
        foreach ($dom->getElementsByTagName('*') as $element) {
            foreach ($element->attributes as $attribute) {
                $this->assertStringStartsNotWith('on', $attribute->name, 'no event handlers');
            }
        }
    }

    public function testThePictureGrowsWithTheDataButIsBounded(): void
    {
        $few = SignalGraph::FromQuery($this->query());
        $this->assertSame(900, SignalGraph::Width($few));
        $many = SignalGraph::FromQuery($this->query(['Sig' => implode('-', array_fill(0, 500, '50'))]));
        $this->assertSame(500 * 6 + 40, SignalGraph::Width($many));
        $huge = SignalGraph::FromQuery($this->query(['Sig' => implode('-', array_fill(0, 100000, '50'))]));
        $this->assertCount(3000, $huge['sig']);
        $this->assertLessThanOrEqual(25000, SignalGraph::Width($huge));
    }

    public function testLongTextIsCut(): void
    {
        $spec = SignalGraph::FromQuery($this->query(['SSID' => str_repeat('A', 5000)]));
        $this->assertSame(64, strlen($spec['ssid']));
    }

    // ── PNG ──

    public function testPngIsARealImageOfTheRightSize(): void
    {
        if (!SignalGraph::GdAvailable()) { $this->markTestSkipped('PHP GD is not installed here'); }
        $png = SignalGraph::Png(SignalGraph::FromQuery($this->query()));
        $this->assertSame("\x89PNG\r\n\x1a\n", substr($png, 0, 8));
        $info = getimagesizefromstring($png);
        $this->assertSame([900, 480], [$info[0], $info[1]]);
        $this->assertSame('image/png', $info['mime']);
    }

    public function testPngBarsAndLinesBothDrawAndTheColorsAreUsed(): void
    {
        if (!SignalGraph::GdAvailable()) { $this->markTestSkipped('PHP GD is not installed here'); }
        foreach (['bar', 'line'] as $type) {
            $png = SignalGraph::Png(SignalGraph::FromQuery($this->query(['type' => $type, 'line' => '0:255:0', 'Sig' => '100-100-100'])));
            $im = imagecreatefromstring($png);
            $found = false;
            for ($x = 20; $x < 40 && !$found; $x++) {
                for ($y = 60; $y < 470; $y++) {
                    if ((imagecolorat($im, $x, $y) & 0xFFFFFF) === 0x00FF00) { $found = true; break; }
                }
            }
            $this->assertTrue($found, "$type graph has green in it");
        }
    }

    public function testPngSurvivesAnythingInTheText(): void
    {
        if (!SignalGraph::GdAvailable()) { $this->markTestSkipped('PHP GD is not installed here'); }
        $png = SignalGraph::Png(SignalGraph::FromQuery($this->query(['SSID' => "caf\u{e9} \u{4e2d}\u{6587} \xff", 'Label' => '<b>'])));
        $this->assertSame("\x89PNG", substr($png, 0, 4));
    }
}
