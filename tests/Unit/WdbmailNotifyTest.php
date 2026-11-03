<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;

// wdbmail::notify() / alert() / mail_users() on a throwaway SQLite database, with a mailer that only records.
// Each test runs in its own process because the real class needs stand-ins for two database classes.
final class WdbmailNotifyTest extends TestCase
{
    private PDO $db;

    private function mailer(): wdbmail
    {
        if (!class_exists('security', false)) { eval('class security { function __construct($a = null, $b = null) {} }'); }
        if (!class_exists('SQL', false)) { eval('class SQL { function __construct($a = null) {} }'); }
        require_once WIFIDB_WEB . '/../tools/daemon/lib/wdbmail.inc.php';
        eval('class RecordingMailer extends PHPMailer\PHPMailer\PHPMailer {
            public array $sent = [];
            public function send() {
                $to = $this->getToAddresses()[0][0];
                if (strpos($to, "bad") !== false) { $this->ErrorInfo = "refused"; return false; }
                $this->sent[] = $to;
                return true;
            }
        }');

        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        $this->db->exec("CREATE TABLE user_info (username TEXT, email TEXT, admin TEXT DEFAULT '0', disabled TEXT DEFAULT '0', validated TEXT DEFAULT '0',
            schedule TEXT DEFAULT '0', imports TEXT DEFAULT '0', kmz TEXT DEFAULT '0', new_users TEXT DEFAULT '0')");
        $this->db->exec("CREATE TABLE notifications (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, type TEXT, title TEXT, body TEXT, link TEXT, created TEXT, read_at TEXT)");
        $this->db->exec("CREATE TABLE notification_prefs (username TEXT, type TEXT, in_app INTEGER, PRIMARY KEY (username, type))");
        $this->db->exec("INSERT INTO user_info (username, email, admin, imports) VALUES
            ('first', 'bad@example.test', '1', '1'), ('second', 'second@example.test', '1', '1'), ('third', 'third@example.test', '0', '1'), ('quiet', 'quiet@example.test', '0', '0')");

        $sql = new stdClass();
        $sql->conn = $this->db;
        $m = new wdbmail([
            'hosturl' => 'https://wifidb.example', 'root' => '', 'wifidb_email_updates' => 1, 'email_validation' => 0,
            'smtp_debug' => 0, 'smtp_host' => 'smtp.example.test', 'smtp_port' => 587, 'smtp_user' => 'u', 'smtp_pass' => 'x',
            'smtp_from' => 'wifidb@example.test', 'smtp_replyto' => '', 'smtp_secure' => 'tls', 'smtp_auth' => true,
            'smtp_authtype' => 'LOGIN', 'smtp_options' => [], 'DKIM_domain' => '', 'DKIM_private' => '', 'DKIM_selector' => '',
            'DKIM_passphrase' => '', 'DKIM_identity' => '', 'DKIM_copyHeaderFields' => true,
            'ListUnsubscribe' => '<postmaster@example.test>', 'XMailer' => 'WifiDB',
        ], $sql);
        $m->mail = new RecordingMailer();
        return $m;
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testOneRefusedAddressDoesNotStopTheRest(): void
    {
        $m = $this->mailer();
        $this->assertSame(0, $m->mail_users('body', 'subject', 'import'), 'reports that something failed');
        $this->assertSame(['second@example.test', 'third@example.test'], $m->mail->sent, 'but the others still got it');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAnEmptyTypeSendsNothing(): void
    {
        $m = $this->mailer();
        ob_start();
        $this->assertSame(0, $m->mail_users('body', 'subject', ''));
        ob_end_clean();
        $this->assertSame([], $m->mail->sent);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNotifyStoresInAppAndEmailsOnlyThoseWhoAskedFor(): void
    {
        $m = $this->mailer();
        $this->assertSame(1, $m->notify('import_finished', 'Imported', 'Your file is in', '/wifidb/opt/userstats.php', 'import', ['third']));
        $this->assertSame(['Imported'], array_column($m->notifier()->Inbox('third'), 'title'));
        $this->assertSame(0, $m->notifier()->UnreadCount('quiet'), 'in-app is for the member it is about');
        $this->assertNotContains('quiet@example.test', $m->mail->sent);
        $this->assertContains('third@example.test', $m->mail->sent);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testNotifyWithNoEmailTypeSendsNoMail(): void
    {
        $m = $this->mailer();
        $m->notify('import_started', 'Started', '', '', '', ['third']);
        $this->assertSame([], $m->mail->sent);
        $this->assertSame(1, $m->notifier()->UnreadCount('third'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAlertReachesAdminsInAppAndByMail(): void
    {
        $m = $this->mailer();
        ob_start();
        $m->alert('Import failed', 'details here', '/wifidb/opt/scheduling.php');
        ob_end_clean();
        $this->assertSame(1, $m->notifier()->UnreadCount('first'));
        $this->assertSame(1, $m->notifier()->UnreadCount('second'));
        $this->assertSame(0, $m->notifier()->UnreadCount('third'), 'not an admin');
        $this->assertSame(['second@example.test'], $m->mail->sent, 'admins who asked for import mail (the first one refuses)');
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testAMissingNotificationsTableDoesNotBreakTheCaller(): void
    {
        $m = $this->mailer();
        $this->db->exec("DROP TABLE notifications");
        ob_start();
        $this->assertSame(0, $m->notify('import_finished', 'Imported', '', '', '', ['third']));
        $out = ob_get_clean();
        $this->assertStringContainsString('Notification not stored', $out);
    }
}
