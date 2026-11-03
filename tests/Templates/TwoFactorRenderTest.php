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
    public function testTheMailPageNeverShowsThePasswordOrKeyAndEscapesWhatTheServerSaid(string $theme): void
    {
        $evil = '<script>alert(1)</script>';
        $smarty = $this->smarty($theme);
        $smarty->assign('mail_main', [
            ['name' => 'smtp_host', 'type' => 'text', 'label' => 'Mail server', 'help' => '', 'value' => 'smtp.example.test', 'is_set' => null, 'source' => 'config', 'options' => null],
            ['name' => 'smtp_secure', 'type' => 'choice', 'label' => 'Encryption', 'help' => '', 'value' => '', 'is_set' => null, 'source' => 'saved',
                'options' => ['tls' => 'STARTTLS', 'ssl' => 'SSL/TLS', '' => 'None']],
            ['name' => 'smtp_pass', 'type' => 'secret', 'label' => 'Password', 'help' => '', 'value' => null, 'is_set' => true, 'source' => 'saved', 'options' => null],
        ]);
        $smarty->assign('mail_signup', [
            ['name' => 'signup_smtp_host', 'type' => 'text', 'label' => "Sign-up account's mail server", 'help' => '', 'value' => '', 'is_set' => null, 'source' => 'default', 'options' => null],
        ]);
        $smarty->assign('mail_signing', [
            ['name' => 'DKIM_private_string', 'type' => 'secret', 'label' => 'Signing key', 'help' => '', 'value' => null, 'is_set' => true, 'source' => 'saved', 'options' => null],
        ]);
        $smarty->assign('mail_oauth_settings', [
            ['name' => 'smtp_oauth_secret', 'type' => 'secret', 'label' => 'Google OAuth client secret', 'help' => '', 'value' => null, 'is_set' => true, 'source' => 'saved', 'options' => null],
        ]);
        $smarty->assign('mail_oauth', ['redirect' => 'https://wifidb.example.com/admin/mail.php?oauth=google', 'ready' => false, 'accounts' => [
            ['id' => 'main', 'name' => 'The mail account', 'connected' => "me$evil@gmail.test", 'set' => true],
            ['id' => 'signup', 'name' => 'The sign-up account', 'connected' => '', 'set' => false],
        ]]);
        $smarty->assign('mail_dkim', ['from' => 'saved', 'bits' => 2048, 'name' => 'wifidb._domainkey.example.test', 'record' => 'v=DKIM1; k=rsa; p=MIIB', 'new_selector' => 'wifidb202611']);
        $smarty->assign('mail_results', [['ok', 'The DKIM record matches the key.'], ['fail', "No DKIM record. Publish this:\n    v=DKIM1"]]);
        $smarty->assign('mail_test', ['to' => 'me@example.test', 'signup' => true, 'sent' => false, 'error' => $evil, 'lines' => "SERVER -> CLIENT: 535 $evil"]);
        $smarty->assign('mail_to', 'me@example.test');
        $smarty->assign('mail_validation', true);
        $html = $smarty->fetch('admin_mail.tpl');
        $this->assertStringNotContainsString($evil, $html);
        $this->assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
        $this->assertStringContainsString('action="https://wifidb.example.com/admin/mail.php"', $html);
        $this->assertStringContainsString('name="smtp_pass" type="password" value=""', $html);
        $this->assertMatchesRegularExpression('/<textarea id="DKIM_private_string" name="DKIM_private_string"[^>]*placeholder="Set. Leave blank to keep it"><\/textarea>/', $html, 'the key is never shown');
        $this->assertStringContainsString('name="clear_DKIM_private_string"', $html);
        $this->assertMatchesRegularExpression('/name="smtp_secure" type="radio" value="" checked> None/', $html);
        $this->assertStringContainsString('v=DKIM1; k=rsa; p=MIIB', $html, 'the record to publish');
        $this->assertStringContainsString('named wifidb._domainkey.example.test', $html);
        $this->assertStringContainsString('name="replace"', $html, 'replacing the key in use takes a tick');
        $this->assertStringContainsString("No DKIM record. Publish this:<br />\n    v=DKIM1", $html);
        $this->assertMatchesRegularExpression('/name="account" type="radio" value="signup" checked>/', $html);
        $this->assertStringContainsString('nobody can finish signing up', $html);
        $this->assertStringContainsString('<code>https://wifidb.example.com/admin/mail.php?oauth=google</code>', $html, 'the redirect URI for Google\'s console');
        $this->assertStringContainsString('name="smtp_oauth_secret" type="password" value=""', $html);
        $this->assertMatchesRegularExpression('/Connected as me&lt;script&gt;.*<input type="hidden" name="func" value="oauth_disconnect">/s', $html);
        $this->assertMatchesRegularExpression('/value="oauth_connect">\s*<input type="submit" value="Connect with Google" disabled/', $html, 'not before the client is saved');
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

    #[DataProvider('themes')]
    public function testTheBackupsPageListsBackupsWithDownloadRestoreAndDelete(string $theme): void
    {
        $smarty = $this->smarty($theme);
        $smarty->assign('backups_list', [
            ['name' => 'wifidb-backup-20270101-033000', 'state' => 'done', 'kind' => 'daily', 'created' => '2027-01-01 03:30:00', 'size_text' => '1.2 GB', 'rows' => 1234567,
             'sha256' => str_repeat('ab', 32), 'source' => ['site' => 'https://wifidb.example.com/<b>', 'service' => 'sqlsrv']],
            ['name' => 'wifidb-backup-20270102-033000', 'state' => 'running', 'step' => 'exporting wifi_hist', 'created' => '2027-01-02 03:30:00', 'size_text' => '0 B'],
        ]);
        $smarty->assign('backups_job', ['action' => 'export', 'state' => 'running', 'started' => '2027-01-02 03:30:00', 'log_text' => "[03:30:01] exporting <wifi_hist>"]);
        $smarty->assign('backups_pending', [['action' => 'restore', 'name' => 'wifidb-backup-20270101-033000', 'requested_by' => 'root']]);
        $smarty->assign('backups_busy', true);
        $smarty->assign('backups_ready', true);
        $smarty->assign('backups_dir', '/srv/wifidb-data/backups');
        $smarty->assign('backups_domain', 'wifidb.example.com');
        $html = $smarty->fetch('admin_backups.tpl');
        $this->assertSame(1, substr_count($html, 'func=download&amp;name=wifidb-backup-20270101-033000'), 'only the finished one downloads');
        $this->assertSame(1, substr_count($html, 'value="Restore"'));
        $this->assertSame(1, substr_count($html, 'value="Delete"'), "not the one that's running");
        $this->assertStringContainsString('placeholder="wifidb.example.com"', $html);
        $this->assertStringContainsString('1,234,567 rows', $html);
        $this->assertStringContainsString('SQL Server', $html);
        $this->assertStringContainsString('exporting wifi_hist', $html);
        $this->assertStringContainsString('&lt;wifi_hist&gt;', $html);
        $this->assertStringNotContainsString('<b>', substr($html, strpos($html, 'Backups on this server')));
        $this->assertStringContainsString('Waiting to start: restore', $html);
        $this->assertStringContainsString('location.reload()', $html);
    }

    #[DataProvider('themes')]
    public function testTheControlPanelHasAnAdminSectionForAdministratorsOnly(string $theme): void
    {
        $member = $this->smarty($theme)->fetch('user_cp_header.tpl');
        $this->assertStringContainsString('cp/security.php', $member);
        $this->assertStringNotContainsString('admin_mode.php', $member);

        $smarty = $this->smarty($theme);
        $smarty->assign('wifidb_is_admin', true);
        $smarty->assign('wifidb_admin_mode', false);
        $off = $smarty->fetch('user_cp_header.tpl');
        $this->assertStringContainsString('<td class="subheading">Admin</td>', $off);
        $this->assertStringContainsString('cp/admin_mode.php?return=https%3A%2F%2Fwifidb.example.com%2Fcp%2F">Turn on admin mode<', $off, 'and back to the control panel');
        $this->assertStringNotContainsString('Leave admin mode', $off);

        $smarty->assign('wifidb_admin_mode', true);
        $on = $smarty->fetch('user_cp_header.tpl');
        $this->assertStringContainsString('Admin mode is on.', $on);
        $this->assertStringContainsString('admin_mode.php?func=off">Leave admin mode<', $on);
        $this->assertStringNotContainsString('Turn on admin mode', $on);
    }

    public static function menus(): array
    {
        return ['classic' => ['vistumbler_classic', 'header.tpl'], 'mobile' => ['vistumbler_mobile', 'navigation.tpl']];
    }

    #[DataProvider('menus')]
    public function testTheMenuHasAnAdminSectionWhoseLinksComeWithAdminMode(string $theme, string $template): void
    {
        $member = $this->smarty($theme)->fetch($template);
        foreach (['>Admin<', 'admin_mode.php', 'func=bad', 'admin/settings.php'] as $text) {
            $this->assertStringNotContainsString($text, $member);
        }

        $smarty = $this->smarty($theme);
        $smarty->assign('wifidb_is_admin', true);
        $smarty->assign('wifidb_admin_mode', false);
        $off = $smarty->fetch($template);
        $this->assertMatchesRegularExpression('#>Admin</(div|li)>\s*<(div|li)[^>]*><a href="https://wifidb\.example\.com/cp/admin_mode\.php">Turn on admin mode</a>#', $off,
            'the heading, with the one link under it');
        foreach (['admin/settings.php', 'func=bad', 'Leave admin mode'] as $text) {
            $this->assertStringNotContainsString($text, $off, 'the admin pages wait for admin mode');
        }

        $smarty->assign('wifidb_admin_mode', true);
        $on = $smarty->fetch($template);
        foreach (['opt/scheduling.php?func=bad', 'admin/settings.php', 'admin/logins.php', 'admin/mail.php', 'admin/backups.php', 'admin/servers.php',
            'admin/traffic.php', 'cp/admin_mode.php?func=off">Leave admin mode<'] as $link) {
            $this->assertStringContainsString($link, $on);
        }
        $this->assertStringNotContainsString('Turn on admin mode', $on);
        $heading = strpos($on, '>Admin<');
        $this->assertGreaterThan(strpos($on, 'Support Forum'), $heading, "after the site's own links");
        $this->assertLessThan(strpos($on, 'admin/settings.php'), $heading, 'the admin pages are under it');
        $this->assertLessThan(strpos($on, 'Other Projects'), strpos($on, 'Leave admin mode'), 'and before the other projects');
    }
}
