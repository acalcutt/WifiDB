<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Renders the notification pages in both themes with hostile text in every field a notification carries.
final class NotificationRenderTest extends TestCase
{
    private static string $compileDir;

    public static function setUpBeforeClass(): void
    {
        self::$compileDir = sys_get_temp_dir() . '/wifidb-notif-' . getmypid();
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

    private function smarty(string $theme): Smarty
    {
        $smarty = new Smarty();
        $smarty->setTemplateDir(WIFIDB_WEB . "/themes/$theme/templates");
        $smarty->setCompileDir(self::$compileDir);
        $smarty->setCaching(Smarty::CACHING_OFF);
        $smarty->muteUndefinedOrNullWarnings();
        $smarty->assign('wifidb_host_url', 'https://wifidb.example.com/');
        $smarty->assign('themeurl', 'https://wifidb.example.com/themes/' . $theme . '/');
        return $smarty;
    }

    #[DataProvider('themes')]
    public function testTheListEscapesWhatANotificationCarries(string $theme): void
    {
        $evil = '<script>alert(1)</script>';
        $smarty = $this->smarty($theme);
        $smarty->assign('notifications', [
            ['id' => 7, 'type' => 'import_finished', 'title' => $evil, 'body' => "line one\n$evil", 'link' => '/x?a=1&b="2"', 'created' => '2026-10-29 12:00:00', 'read_at' => null, 'unread' => true],
            ['id' => 8, 'type' => 'admin_alert', 'title' => 'Plain', 'body' => '', 'link' => '', 'created' => '2026-10-29 11:00:00', 'read_at' => '2026-10-29 11:30:00', 'unread' => false],
        ]);
        $smarty->assign('notification_total', 2);
        $smarty->assign('notification_unread', 1);
        $smarty->assign('notification_page', 1);
        $smarty->assign('notification_pages', 1);
        $html = $smarty->fetch('user_cp_notifications.tpl');

        $this->assertStringNotContainsString($evil, $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('href="/x?a=1&amp;b=&quot;2&quot;"', $html, 'the link is escaped inside its attribute');
        $this->assertStringContainsString('Notifications (1 unread)', $html);
        $this->assertSame(1, substr_count($html, 'value="Mark read"'), 'only an unread one can be marked read');
        $this->assertSame(2, substr_count($html, 'value="Delete"'));
        $this->assertStringNotContainsString('method="get"', $html, 'changes are POSTs');
    }

    #[DataProvider('themes')]
    public function testAnEmptyListSaysSo(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('notifications', []);
        $smarty->assign('notification_unread', 0);
        $smarty->assign('notification_page', 1);
        $smarty->assign('notification_pages', 1);
        $html = $smarty->fetch('user_cp_notifications.tpl');
        $this->assertStringContainsString('You have no notifications.', $html);
        $this->assertStringContainsString('value="Mark all read" disabled', $html);
    }

    #[DataProvider('themes')]
    public function testTheSettingsPageListsEachChoiceWithItsState(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('notification_prefs', [
            'import_finished' => ['label' => 'Your import finished', 'on' => true],
            'export' => ['label' => 'New map data exports', 'on' => false],
        ]);
        $smarty->assign('notification_saved', true);
        $html = $smarty->fetch('user_cp_notification_settings.tpl');
        $this->assertMatchesRegularExpression('/name="type\[import_finished\]" type="checkbox" checked>/', $html);
        $this->assertMatchesRegularExpression('/name="type\[export\]" type="checkbox">/', $html);
        $this->assertStringContainsString('Saved.', $html);
    }
}
