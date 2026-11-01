<?php
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

// Renders the sign-in second step and the Security pages in both themes, with hostile text where a member's own text goes.
final class TwoFactorRenderTest extends TestCase
{
    private static string $compileDir;

    public static function setUpBeforeClass(): void
    {
        self::$compileDir = sys_get_temp_dir() . '/wifidb-2fa-' . getmypid();
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

    #[DataProvider('themes')]
    public function testTheSecondStepEscapesTheNameAndOffersWhatTheMemberHas(string $theme): void
    {
        $evil = '<script>alert(1)</script>';
        $smarty = $this->smarty($theme);
        $smarty->assign('mfa_user', $evil);
        $smarty->assign('mfa_return', '/wifidb/?a=1&b=2');
        $smarty->assign('mfa_error', 'That code did not work.');
        $smarty->assign('mfa_has_passkey', true);
        $smarty->assign('mfa_can_remember', true);
        $html = $smarty->fetch('login_mfa.tpl');

        $this->assertStringNotContainsString($evil, $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('That code did not work.', $html);
        $this->assertStringContainsString('autocomplete="one-time-code"', $html);
        $this->assertStringContainsString('Remember this computer for 30 days', $html);
        $this->assertStringContainsString('Use a passkey', $html);
        $this->assertStringContainsString('func=mfa_passkey_begin&return=%2Fwifidb%2F%3Fa%3D1%26b%3D2', $html, 'the return address is URL-encoded');
        $this->assertStringContainsString('lib/js/wifidb/passkeys.js', $html);
    }

    #[DataProvider('themes')]
    public function testTheSecondStepWithoutPasskeysOrDeviceKeyIsJustTheCodeBox(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('mfa_user', 'alice');
        $smarty->assign('mfa_return', '/');
        $smarty->assign('mfa_has_passkey', false);
        $smarty->assign('mfa_can_remember', false);
        $html = $smarty->fetch('login_mfa.tpl');
        $this->assertStringNotContainsString('Use a passkey', $html);
        $this->assertStringNotContainsString('passkeys.js', $html);
        $this->assertStringNotContainsString('Remember this computer', $html);
        $this->assertStringContainsString('name="code"', $html);
    }

    #[DataProvider('themes')]
    public function testTheSecurityPageListsMethodsAndEscapesTheirNames(string $theme): void
    {
        $evil = '<img src=x onerror=alert(1)>';
        $smarty = $this->smarty($theme);
        $smarty->assign('security_methods', [
            ['id' => 3, 'type' => 'totp', 'name' => $evil, 'created' => '2026-10-29 10:00:00', 'last_used' => null],
            ['id' => 4, 'type' => 'passkey', 'name' => 'Laptop', 'created' => '2026-10-29 11:00:00', 'last_used' => '2026-10-30 08:00:00'],
        ]);
        $smarty->assign('security_has', true);
        $smarty->assign('security_asks_login', true);
        $smarty->assign('security_asks_prefs', false);
        $smarty->assign('security_codes_left', 1);
        $html = $smarty->fetch('user_cp_security.tpl');

        $this->assertStringNotContainsString($evil, $html);
        $this->assertStringContainsString('&lt;img src=x onerror=alert(1)&gt;', $html);
        $this->assertSame(2, substr_count($html, 'value="Remove"'));
        $this->assertSame(1, substr_count($html, 'name="id" value="3"'));
        $this->assertStringContainsString('Authenticator app', $html);
        $this->assertStringContainsString('Not used yet', $html);
        $this->assertStringContainsString('You have 1 unused recovery code.', $html);
        $this->assertMatchesRegularExpression('/id="mfa_login" name="mfa_login" type="checkbox" checked>/', $html);
        $this->assertMatchesRegularExpression('/id="mfa_prefs" name="mfa_prefs" type="checkbox">/', $html);
        $this->assertStringNotContainsString('method="get"', $html);
    }

    #[DataProvider('themes')]
    public function testTheSecurityPageForAMemberWithNothingOffersToAddOnly(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('security_methods', []);
        $smarty->assign('security_has', false);
        $html = $smarty->fetch('user_cp_security.tpl');
        $this->assertStringContainsString('Add an authenticator app', $html);
        $this->assertStringNotContainsString('When to ask', $html);
        $this->assertStringNotContainsString('Make new codes', $html);
    }

    #[DataProvider('themes')]
    public function testTheAuthenticatorPageKeepsTheUriInsideItsScriptString(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('totp_secret', 'ABCD EFGH');
        $smarty->assign('totp_uri', "otpauth://totp/WifiDB:o'neil?secret=ABCDEFGH&issuer=</script><b>");
        $smarty->assign('totp_error', true);
        $html = $smarty->fetch('user_cp_security_totp.tpl');
        $this->assertStringNotContainsString('</script><b>', $html);
        $this->assertStringContainsString('That code did not match.', $html);
        $this->assertStringContainsString('<code>ABCD EFGH</code>', $html);
        $this->assertStringContainsString('makeQRCode(', $html);
    }

    #[DataProvider('themes')]
    public function testRecoveryCodesAreListedOnePerLine(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('recovery_headline', 'New recovery codes.');
        $smarty->assign('recovery_codes', ['aaaa-bbbb', 'cccc-dddd']);
        $html = $smarty->fetch('user_cp_security_codes.tpl');
        $this->assertStringContainsString("aaaa-bbbb\ncccc-dddd\n</pre>", $html);
    }
    #[DataProvider('themes')]
    public function testTheConfirmPageCarriesTheReturnAddressSafely(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('confirm_return', "/cp/index.php?func=pref&x='</script>");
        $smarty->assign('confirm_error', 'That code did not work.');
        $smarty->assign('confirm_has_passkey', true);
        $html = $smarty->fetch('user_cp_confirm.tpl');
        $this->assertStringNotContainsString("'</script>", $html);
        $this->assertStringContainsString('func=proc&amp;return=%2Fcp%2Findex.php%3Ffunc%3Dpref', $html);
        $this->assertStringContainsString('That code did not work.', $html);
        $this->assertStringContainsString('Use a passkey', $html);
    }

    #[DataProvider('themes')]
    public function testTheAdminModePageAsksForACodeAndCarriesTheReturnAddressSafely(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('admin_return', "/opt/scheduling.php?func=bad&x='</script>");
        $smarty->assign('admin_error', 'That code did not work.');
        $smarty->assign('admin_has_two_factor', true);
        $smarty->assign('admin_has_passkey', true);
        $smarty->assign('admin_minutes', 30);
        $html = $smarty->fetch('admin_mode.tpl');
        $this->assertStringNotContainsString("'</script>", $html);
        $this->assertStringContainsString('func=proc&amp;return=%2Fopt%2Fscheduling.php%3Ffunc%3Dbad', $html);
        $this->assertStringContainsString('name="code"', $html);
        $this->assertStringContainsString('That code did not work.', $html);
        $this->assertStringContainsString('Use a passkey', $html);
        $this->assertStringContainsString('30 minutes', $html);
    }

    #[DataProvider('themes')]
    public function testTheAdminModePageWithoutTwoFactorSendsTheAdminToSetItUp(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('admin_return', '/');
        $smarty->assign('admin_has_two_factor', false);
        $smarty->assign('admin_has_passkey', false);
        $smarty->assign('admin_minutes', 30);
        $html = $smarty->fetch('admin_mode.tpl');
        $this->assertStringNotContainsString('name="code"', $html);
        $this->assertStringContainsString('cp/security.php', $html);
    }

    #[DataProvider('themes')]
    public function testTheSiteSettingsPageNeverShowsASecretAndSaysWhereEachValueComesFrom(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('site_settings', [
            ['name' => 'signups_open', 'type' => 'bool', 'label' => 'Sign-ups are open', 'help' => 'Anyone <b>can</b> join', 'value' => true, 'is_set' => null, 'source' => 'saved'],
            ['name' => 'email_validation', 'type' => 'bool', 'label' => 'Confirm e-mail', 'help' => '', 'value' => false, 'is_set' => null, 'source' => 'config'],
            ['name' => 'cleantalk_key', 'type' => 'secret', 'label' => 'CleanTalk access key', 'help' => '', 'value' => null, 'is_set' => true, 'source' => 'saved'],
        ]);
        $smarty->assign('settings_message', 'CleanTalk did not accept that key. Nothing was saved.');
        $smarty->assign('settings_problem', true);
        $smarty->assign('settings_unencrypted', true);
        $html = $smarty->fetch('admin_settings.tpl');
        $this->assertStringContainsString('action="https://wifidb.example.com/admin/settings.php"', $html);
        $this->assertStringContainsString('method="post"', $html);
        $this->assertMatchesRegularExpression('/name="signups_open" type="checkbox" checked>/', $html);
        $this->assertMatchesRegularExpression('/name="email_validation" type="checkbox">/', $html);
        $this->assertStringContainsString('name="cleantalk_key" type="password" value=""', $html);
        $this->assertStringContainsString('name="clear_cleantalk_key"', $html);
        $this->assertStringContainsString('Set. Leave blank to keep it', $html);
        $this->assertStringContainsString('From config.inc.php', $html);
        $this->assertStringContainsString('Anyone &lt;b&gt;can&lt;/b&gt; join', $html);
        $this->assertStringContainsString('<font color="Red">CleanTalk did not accept that key', $html);
        $this->assertStringContainsString('stored unencrypted', $html);
    }

    #[DataProvider('themes')]
    public function testTheSignUpAndMessageFormsCarryTheSpamCheckHints(string $theme): void
    {
        foreach (['create_user.tpl', 'user_cp_sendmsg.tpl'] as $template) {
            $smarty = $this->smarty($theme);
            $smarty->assign('ct_form_time', 1_800_000_000);
            $smarty->assign('fromusers', []);
            $html = $smarty->fetch($template);
            $form = substr($html, strpos($html, '<form'), strpos($html, '</form>') - strpos($html, '<form'));
            $this->assertStringContainsString('name="ct_t" value="1800000000"', $form, $template);
            $this->assertStringContainsString('name="ct_js" value="0"', $form, $template);
            $this->assertStringContainsString("e.value = '1'", $html, $template);
        }
    }

    public function testTheLoginPageHidesTheSignUpLinkWhenSignUpsAreClosed(): void
    {
        foreach (['vistumbler_classic', 'vistumbler_mobile'] as $theme) {
            $smarty = $this->smarty($theme);
            $this->assertStringContainsString('func=create_user_form', $smarty->fetch('login_index.tpl'), "$theme, open by default");
            $smarty->assign('wifidb_signups_open', false);
            $this->assertStringNotContainsString('func=create_user_form', $smarty->fetch('login_index.tpl'), $theme);
        }
    }

    public function testTheMobileMenuOffersAdminModeOnlyToAdministratorsAndShowsTheWayOut(): void
    {
        $member = $this->smarty('vistumbler_mobile')->fetch('navigation.tpl');
        $this->assertStringNotContainsString('admin_mode.php', $member);
        $this->assertStringNotContainsString('func=bad', $member);

        $smarty = $this->smarty('vistumbler_mobile');
        $smarty->assign('wifidb_is_admin', true);
        $smarty->assign('wifidb_admin_mode', false);
        $off = $smarty->fetch('navigation.tpl');
        $this->assertStringContainsString('cp/admin_mode.php">Admin mode<', $off);
        $this->assertStringNotContainsString('Leave admin mode', $off);

        $smarty->assign('wifidb_admin_mode', true);
        $on = $smarty->fetch('navigation.tpl');
        $this->assertStringContainsString('admin_mode.php?func=off">Leave admin mode<', $on);
    }
}
