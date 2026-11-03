<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// The scheduling page's admin actions for turning a job off and on, and their confirmation, in both themes.
final class ScheduleAdminRenderTest extends TestCase
{
    private static string $compileDir;

    public static function setUpBeforeClass(): void
    {
        self::$compileDir = sys_get_temp_dir() . '/wifidb-sched-' . getmypid();
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
        $smarty->assign('wifidb_theme', $theme);
        return $smarty;
    }

    private function schedulePage(string $theme, bool $adminMode): string
    {
        $row = ['color' => 'lightgreen', 'nodename' => 'wifidb-a', 'interval' => '10', 'status' => 'Waiting', 'schedpid' => '',
            'nextrun_local' => '2026-11-03 02:33:03 AM'];
        $smarty = $this->smarty($theme);
        $smarty->assign('wifidb_admin_mode', $adminMode);
        // enabled as the databases hand it back: a string
        $smarty->assign('wifidb_schedules', [
            ['id' => 9, 'daemon' => 'Import', 'enabled' => '1'] + $row,
            ['id' => 14, 'daemon' => 'Export', 'enabled' => '0', 'color' => 'red'] + $row,
        ]);
        $smarty->assign('wifidb_daemons', []);
        return $smarty->fetch('scheduling_schedule.tpl');
    }

    #[DataProvider('themes')]
    public function testInAdminModeEachJobCanBeTurnedOffOrOn(string $theme): void
    {
        $html = $this->schedulePage($theme, true);
        $this->assertStringContainsString('opt/admin_action.php?action=disable_schedule&amp;schedule_id=9&amp;', $html);
        $this->assertStringContainsString('opt/admin_action.php?action=enable_schedule&amp;schedule_id=14&amp;', $html);
        $this->assertStringNotContainsString('action=enable_schedule&amp;schedule_id=9&amp;', $html);
        $this->assertStringNotContainsString('action=disable_schedule&amp;schedule_id=14&amp;', $html);
    }

    #[DataProvider('themes')]
    public function testADisabledJobSaysSoToEveryone(string $theme): void
    {
        $html = $this->schedulePage($theme, false);
        $this->assertStringContainsString('Waiting (disabled)', $html, 'a red row that only says "Waiting" looks like a failure');
        $this->assertSame(1, substr_count($html, '(disabled)'));
        $this->assertStringNotContainsString('_schedule&amp;', $html, 'no admin links outside admin mode');
    }

    #[DataProvider('themes')]
    public function testTheConfirmationPostsAndEscapesTheNode(string $theme): void
    {
        $evil = '<script>alert(1)</script>';
        foreach (['disable_schedule' => 'Confirm Disable', 'enable_schedule' => 'Confirm Enable'] as $action => $button) {
            $smarty = $this->smarty($theme);
            $smarty->assign('action', $action);
            $smarty->assign('schedule_id', 14);
            $smarty->assign('schedule_info', ['id' => 14, 'nodename' => $evil, 'daemon' => 'Export', 'enabled' => $action === 'enable_schedule' ? '0' : '1',
                'status' => 'Waiting', 'interval' => '1440']);
            $smarty->assign('return_url', 'https://wifidb.example.com/opt/scheduling.php?func=schedule');
            $html = $smarty->fetch('admin_action_confirm.tpl');

            $this->assertStringContainsString('<form method="post" action="https://wifidb.example.com/opt/admin_action.php?action=' . $action
                . '&amp;schedule_id=14&amp;confirm=yes&amp;return=https%3A%2F%2Fwifidb.example.com%2Fopt%2Fscheduling.php%3Ffunc%3Dschedule"', $html);
            $this->assertStringContainsString($button, $html);
            $this->assertStringNotContainsString('<a href="https://wifidb.example.com/opt/admin_action.php?action=' . $action, $html,
                'a link would confirm it with a GET, which another site can make an admin send');
            $this->assertStringNotContainsString($evil, $html);
            $this->assertStringContainsString('&lt;script&gt;', $html);
        }
    }
}
