<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/traffic.inc.php';

// Page views, unique visitors and events: written to each node's spool, then added to the counts in the database.
final class TrafficTest extends TestCase
{
    private PDO $db;
    private string $dir;
    private const BROWSER = 'Mozilla/5.0 (X11; Linux x86_64) Firefox/140.0';
    private int $t;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE traffic_counts (kind TEXT NOT NULL, item TEXT NOT NULL, span TEXT NOT NULL, period TEXT NOT NULL,
            pageviews INTEGER NOT NULL DEFAULT 0, visitors INTEGER NOT NULL DEFAULT 0, PRIMARY KEY (kind, item, span, period))");
        $this->db->exec("CREATE TABLE traffic_visitors (kind TEXT NOT NULL, item TEXT NOT NULL, span TEXT NOT NULL, period TEXT NOT NULL,
            visitor TEXT NOT NULL, PRIMARY KEY (kind, item, span, period, visitor))");
        $this->dir = sys_get_temp_dir() . '/wifidb-traffic-' . getmypid() . '-' . mt_rand();
        $this->t = gmmktime(10, 15, 0, 11, 2, 2026);
    }

    protected function tearDown(): void
    {
        foreach (glob("{$this->dir}*/*") ?: [] as $f) { unlink($f); }
        foreach (glob("{$this->dir}*") ?: [] as $d) { rmdir($d); }
    }

    private function node(string $suffix = ''): Traffic { return new Traffic($this->dir . $suffix, 'shared-key'); }

    private function visit(Traffic $node, string $ip, string $page = 'index.php', string $user = '', ?string $country = 'DE', int $offset = 0, array $extra = []): bool
    {
        return $node->Page($page, $user, $extra + ['REQUEST_METHOD' => 'GET', 'REMOTE_ADDR' => $ip, 'HTTP_USER_AGENT' => self::BROWSER], $country, $this->t + $offset);
    }

    private function counted(string $kind, string $item, string $span, int $start): array
    {
        $p = $this->db->prepare("SELECT visitors, pageviews FROM traffic_counts WHERE kind = ? AND item = ? AND span = ? AND period = ?");
        $p->execute([$kind, $item, $span, gmdate('Y-m-d H:i:s', $start)]);
        return array_map('intval', $p->fetch(PDO::FETCH_NUM) ?: [0, 0]);
    }

    public function testOnlyPagesPeopleLookAtAreCounted(): void
    {
        $node = $this->node();
        $this->assertTrue($this->visit($node, '203.0.113.1'));
        $this->assertFalse($this->visit($node, '203.0.113.1', 'index.php', '', 'DE', 0, ['REQUEST_METHOD' => 'POST']));
        $this->assertFalse($this->visit($node, '203.0.113.1', 'index.php', '', 'DE', 0, ['HTTP_USER_AGENT' => 'Googlebot/2.1']));
        $this->assertFalse($this->visit($node, '203.0.113.1', 'index.php', '', 'DE', 0, ['HTTP_USER_AGENT' => 'curl/8.5']));
        $this->assertFalse($this->visit($node, '203.0.113.1', 'index.php', '', 'DE', 0, ['HTTP_SEC_PURPOSE' => 'prefetch']));
        $this->assertFalse($this->visit($node, '203.0.113.1', 'index.php', '', 'DE', 0, ['HTTP_USER_AGENT' => '']));
        $this->assertSame(1, $node->Flush($this->db));
        $this->assertStringNotContainsString('203.0.113.1', implode('', glob($this->dir . '/*') ?: []) . $this->db->query("SELECT group_concat(visitor) FROM traffic_visitors")->fetchColumn(),
            'no address is kept');
    }

    public function testPageViewsAndUniqueVisitorsByHourDayAndMonth(): void
    {
        $node = $this->node();
        $this->visit($node, '203.0.113.1', 'index.php');
        $this->visit($node, '203.0.113.1', 'opt/map.php');
        $this->visit($node, '203.0.113.2', 'index.php', '', 'US');
        $this->visit($node, '203.0.113.3', 'index.php', 'alice', null, 3600);   // the next hour, country not known
        $this->assertSame(4, $node->Flush($this->db));

        $hour = $this->t - $this->t % 3600;
        $day = $this->t - $this->t % 86400;
        $month = gmmktime(0, 0, 0, 11, 1, 2026);
        $this->assertSame([2, 3], $this->counted('site', '', 'hour', $hour));
        $this->assertSame([1, 1], $this->counted('site', '', 'hour', $hour + 3600));
        $this->assertSame([3, 4], $this->counted('site', '', 'day', $day));
        $this->assertSame([3, 4], $this->counted('site', '', 'month', $month));
        $this->assertSame([1, 2], $this->counted('country', 'DE', 'day', $day));
        $this->assertSame([1, 1], $this->counted('country', '--', 'day', $day), "a country that couldn't be told");
        $this->assertSame([0, 3], $this->counted('page', 'index.php', 'day', $day), 'pages: views only');
    }

    public function testAVisitorSeenOnTwoNodesCountsOnce(): void
    {
        $a = $this->node('-a');
        $b = $this->node('-b');
        $this->visit($a, '203.0.113.9');
        $this->visit($b, '203.0.113.9', 'stats.php', '', 'DE', 60);
        $a->Flush($this->db);
        $b->Flush($this->db);
        $this->assertSame([1, 2], $this->counted('site', '', 'day', $this->t - $this->t % 86400));
        // and a member is the same visitor wherever they sign in from
        $this->visit($a, '198.51.100.1', 'index.php', 'bob');
        $this->visit($b, '198.51.100.2', 'index.php', 'bob');
        $a->Flush($this->db);
        $b->Flush($this->db);
        $this->assertSame([2, 4], $this->counted('site', '', 'day', $this->t - $this->t % 86400));
    }

    public function testWhatPeopleDid(): void
    {
        $node = $this->node();
        $node->Event('signup', $this->t);
        $node->Event('login', $this->t);
        $node->Event('login', $this->t + 7200);
        $this->assertFalse($node->Event('nonsense', $this->t));
        $node->Flush($this->db);
        $totals = Traffic::EventTotals($this->db, 30, $this->t + 7200);
        $this->assertSame(1, $totals['signup']);
        $this->assertSame(2, $totals['login']);
        $this->assertSame(0, $totals['import_done']);
    }

    public function testHistoryHasEveryPeriodNewestFirst(): void
    {
        $node = $this->node();
        $this->visit($node, '203.0.113.1');
        $this->visit($node, '203.0.113.1', 'index.php', '', 'DE', -86400);
        $node->Flush($this->db);
        $days = Traffic::History($this->db, 'site', '', 'day', 3, $this->t);
        $this->assertSame([[$this->t - $this->t % 86400, 1, 1], [$this->t - $this->t % 86400 - 86400, 1, 1], [$this->t - $this->t % 86400 - 172800, 0, 0]], $days);
        $months = Traffic::History($this->db, 'site', '', 'month', 2, $this->t);
        $this->assertSame([gmmktime(0, 0, 0, 11, 1, 2026), 1, 2], $months[0]);
        $this->assertSame(gmmktime(0, 0, 0, 10, 1, 2026), $months[1][0]);
        $this->assertSame([['DE', 1, 2]], Traffic::Top($this->db, 'country', 'month', gmmktime(0, 0, 0, 11, 1, 2026)));
    }

    public function testARunThatDidntFinishIsAddedNextTime(): void
    {
        $node = $this->node();
        $this->visit($node, '203.0.113.1');
        rename("{$this->dir}/pending.log", "{$this->dir}/flushing-20261102101500-1.log");   // moved aside, then the run died
        $this->visit($node, '203.0.113.2');
        $this->assertSame(2, $node->Flush($this->db));
        $this->assertSame([], glob("{$this->dir}/*.log"));
    }

    public function testPruningForgetsVisitorsOfPeriodsThatAreOver(): void
    {
        $node = $this->node();
        $this->visit($node, '203.0.113.1');
        $node->Flush($this->db);
        $this->assertSame(3, (int)$this->db->query("SELECT COUNT(*) FROM traffic_visitors WHERE kind = 'site'")->fetchColumn());
        Traffic::Prune($this->db, $this->t + 3 * 86400);
        $this->assertSame(['month'], $this->db->query("SELECT span FROM traffic_visitors WHERE kind = 'site'")->fetchAll(PDO::FETCH_COLUMN));
        Traffic::Prune($this->db, $this->t + 40 * 86400);
        $this->assertSame(0, (int)$this->db->query("SELECT COUNT(*) FROM traffic_visitors")->fetchColumn());
        $this->assertSame([0, 0], $this->counted('site', '', 'hour', $this->t - $this->t % 3600), 'hourly counts go after 35 days');
        $this->assertSame([1, 1], $this->counted('site', '', 'day', $this->t - $this->t % 86400), 'daily ones stay');
    }
}
