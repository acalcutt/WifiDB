<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/notify.inc.php';

final class NotifierTest extends TestCase
{
    private PDO $db;
    private Notifier $n;
    private int $now = 1_800_000_000;

    protected function setUp(): void
    {
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE user_info (username TEXT, admin TEXT DEFAULT '0', disabled TEXT DEFAULT '0', validated TEXT DEFAULT '0')");
        $this->db->exec("CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, type TEXT, title TEXT, body TEXT, link TEXT, created TEXT, read_at TEXT)");
        $this->db->exec("CREATE TABLE notification_prefs (username TEXT, type TEXT, in_app INTEGER, PRIMARY KEY (username, type))");
        foreach ([['alice', 0, 0, 0], ['bob', 0, 0, 0], ['root', 1, 0, 0], ['gone', 1, 1, 0], ['pending', 1, 0, 1]] as $u) {
            $this->db->prepare("INSERT INTO user_info (username, admin, disabled, validated) VALUES (?, ?, ?, ?)")->execute($u);
        }
        $this->n = new Notifier($this->db, fn () => $this->now);
    }

    public function testTypeWithAnOwnerTellsOnlyThatMember(): void
    {
        $this->assertSame(1, $this->n->Notify('import_finished', 'Done', 'body', '/wifidb/opt/userstats.php', ['alice']));
        $this->assertSame(1, $this->n->UnreadCount('alice'));
        $this->assertSame(0, $this->n->UnreadCount('bob'));
    }

    public function testAdminAlertsGoToEnabledAdminsOnly(): void
    {
        $this->assertSame(1, $this->n->Notify('admin_alert', 'Import failed'));
        $this->assertSame(1, $this->n->UnreadCount('root'));
        $this->assertSame(0, $this->n->UnreadCount('gone'), 'a disabled admin');
        $this->assertSame(0, $this->n->UnreadCount('pending'), 'an admin who has not validated');
        $this->assertSame(0, $this->n->UnreadCount('alice'));
    }

    public function testAdminAlertsCannotBeSwitchedOff(): void
    {
        $this->assertFalse($this->n->SetPref('root', 'admin_alert', false));
        $this->db->exec("INSERT INTO notification_prefs VALUES ('root', 'admin_alert', 0)");
        $this->assertSame(1, $this->n->Notify('admin_alert', 'Still told'));
        $this->assertArrayNotHasKey('admin_alert', $this->n->Prefs('root'));
    }

    public function testATypeThatIsOffByDefaultNeedsOptIn(): void
    {
        $this->assertSame(0, $this->n->Notify('kmz', 'New export'));
        $this->n->SetPref('bob', 'kmz', true);
        $this->assertSame(1, $this->n->Notify('kmz', 'New export'));
        $this->assertSame(1, $this->n->UnreadCount('bob'));
    }

    public function testATypeThatIsOnByDefaultCanBeOptedOutOf(): void
    {
        $this->n->SetPref('root', 'new_user', false);
        $this->assertSame(0, $this->n->Notify('new_user', 'New member'));
        $this->n->SetPref('root', 'new_user', true);
        $this->assertSame(1, $this->n->Notify('new_user', 'New member'));
        $this->assertSame(1, $this->n->Notify('import_started', 'Started', '', '', ['alice']));
        $this->n->SetPref('alice', 'import_started', false);
        $this->assertSame(0, $this->n->Notify('import_started', 'Started', '', '', ['alice']), 'a named member who opted out');
    }

    public function testSettingAPrefTwiceKeepsOneRow(): void
    {
        $this->n->SetPref('bob', 'kmz', true);
        $this->n->SetPref('bob', 'kmz', false);
        $this->assertSame(1, (int)$this->db->query("SELECT COUNT(*) FROM notification_prefs WHERE username = 'bob'")->fetchColumn());
        $this->assertFalse($this->n->Prefs('bob')['kmz']['on']);
    }

    public function testAnUnknownTypeIsAProgrammingError(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->n->Notify('nonsense', 'x');
    }

    public function testLinksMustBeSiteOrWebAddresses(): void
    {
        $this->assertSame('/wifidb/cp/', Notifier::CleanLink('/wifidb/cp/'));
        $this->assertSame('https://wifidb.example/x?a=1&b=2', Notifier::CleanLink('https://wifidb.example/x?a=1&b=2'));
        foreach (['javascript:alert(1)', '//evil.example/', 'data:text/html,x', 'https://a b', "https://x.example/\"onmouseover=", '', 'ftp://x'] as $bad) {
            $this->assertSame('', Notifier::CleanLink($bad), $bad);
        }
        $this->assertSame('', Notifier::CleanLink('/' . str_repeat('a', 600)));
    }

    public function testInboxIsNewestFirstAndPaged(): void
    {
        foreach (['one', 'two', 'three'] as $i => $title) {
            $this->now += 60;
            $this->n->Notify('import_finished', $title, '', '', ['alice']);
        }
        $this->assertSame(['three', 'two', 'one'], array_column($this->n->Inbox('alice'), 'title'));
        $this->assertSame(['two'], array_column($this->n->Inbox('alice', 1, 1), 'title'));
        $this->assertSame(3, $this->n->Total('alice'));
    }

    public function testReadingAndDeletingOnlyWorkOnYourOwn(): void
    {
        $this->n->Notify('import_finished', 'Mine', '', '', ['alice']);
        $this->n->Notify('import_finished', 'Theirs', '', '', ['bob']);
        $mine = $this->n->Inbox('alice')[0]['id'];
        $theirs = $this->n->Inbox('bob')[0]['id'];

        $this->assertFalse($this->n->MarkRead('alice', $theirs));
        $this->assertFalse($this->n->Delete('alice', $theirs));
        $this->assertSame(1, $this->n->UnreadCount('bob'));

        $this->assertTrue($this->n->MarkRead('alice', $mine));
        $this->assertFalse($this->n->MarkRead('alice', $mine), 'already read');
        $this->assertSame(0, $this->n->UnreadCount('alice'));
        $this->assertTrue($this->n->Delete('alice', $mine));
        $this->assertSame(0, $this->n->Total('alice'));
    }

    public function testMarkAllRead(): void
    {
        $this->n->Notify('import_started', 'a', '', '', ['alice']);
        $this->n->Notify('import_finished', 'b', '', '', ['alice']);
        $this->assertSame(2, $this->n->MarkAllRead('alice'));
        $this->assertSame(0, $this->n->UnreadCount('alice'));
    }

    public function testPruneKeepsRecentAndUnreadOnes(): void
    {
        $this->n->Notify('import_finished', 'old read', '', '', ['alice']);
        $this->n->Notify('import_finished', 'old unread', '', '', ['alice']);
        $this->n->MarkRead('alice', $this->n->Inbox('alice')[1]['id']);
        $this->now += 100 * 86400;
        $this->n->Notify('import_finished', 'new', '', '', ['alice']);
        $this->assertSame(1, $this->n->Prune(90), 'the read one, past 90 days');
        $this->assertSame(['new', 'old unread'], array_column($this->n->Inbox('alice'), 'title'));
        $this->now += 100 * 86400;
        $this->assertSame(1, $this->n->Prune(90), 'unread ones too after twice that');
    }

    public function testTitlesAreCutToTheColumnWidth(): void
    {
        $this->n->Notify('import_finished', str_repeat('é', 400), '', '', ['alice']);
        $this->assertSame(255, mb_strlen($this->n->Inbox('alice')[0]['title']));
    }
}
