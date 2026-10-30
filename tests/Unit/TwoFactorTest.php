<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/totp.inc.php';
require_once WIFIDB_LIB . '/webauthn.inc.php';
require_once WIFIDB_LIB . '/two_factor.inc.php';
require_once __DIR__ . '/../Support/SoftwareAuthenticator.php';

final class TwoFactorTest extends TestCase
{
    private PDO $db;
    private TwoFactor $tf;
    private int $now;
    private string $key;
    private const USER = 'alice';
    private const HASH = 'session-hash-1';

    protected function setUp(): void
    {
        $this->now = 1_800_000_000;
        $this->key = Totp::GenerateKey();
        $this->db = new PDO('sqlite::memory:', null, null, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        foreach ([
            "CREATE TABLE user_info (username TEXT, password TEXT, mfa_login INTEGER NOT NULL DEFAULT 1, mfa_prefs INTEGER NOT NULL DEFAULT 0)",
            "CREATE TABLE user_login_hashes (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, hash TEXT, utime INTEGER,
                mfa_pending INTEGER NOT NULL DEFAULT 0, mfa_at TEXT, admin_until TEXT, prefs_until TEXT, challenge TEXT, challenge_expires TEXT)",
            "CREATE TABLE user_two_factor (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, type TEXT, name TEXT, secret TEXT,
                public_key TEXT, alg INTEGER, sign_count INTEGER NOT NULL DEFAULT 0, last_step INTEGER NOT NULL DEFAULT 0, created TEXT, last_used TEXT)",
            "CREATE TABLE user_recovery_codes (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, code_hash TEXT, created TEXT, used TEXT)",
            "CREATE TABLE two_factor_attempts (id INTEGER PRIMARY KEY AUTOINCREMENT, username TEXT, attempted TEXT)",
        ] as $ddl) { $this->db->exec($ddl); }
        $this->db->exec("INSERT INTO user_info (username, password) VALUES ('alice', 'hash-of-password')");
        $this->db->exec("INSERT INTO user_info (username, password) VALUES ('bob', 'bobs-hash')");
        $this->session(self::USER, self::HASH);
        $this->tf = new TwoFactor($this->db, ['two_factor_key' => $this->key, 'hosturl' => 'https://wifidb.example.com'], fn () => $this->now);
    }

    private function session(string $user, string $hash): void
    {
        $this->db->prepare("INSERT INTO user_login_hashes (username, hash, utime) VALUES (?, ?, ?)")->execute([$user, $hash, $this->now]);
    }

    /** Sets up an authenticator app for the member and returns its secret. */
    private function enrollTotp(string $user = self::USER, string $hash = self::HASH): array
    {
        $start = $this->tf->StartTotp($user, $hash);
        $result = $this->tf->FinishTotp($user, $hash, 'Phone', Totp::Code($start['secret'], Totp::Step($this->now)));
        $this->assertTrue($result['ok']);
        return [$start['secret'], $result];
    }

    private function code(string $secret, int $stepsAhead = 0): string
    {
        return Totp::Code($secret, Totp::Step($this->now) + $stepsAhead);
    }

    // ── adding an authenticator app ──

    public function testFirstAuthenticatorTurnsOnTwoFactorAndGivesRecoveryCodes(): void
    {
        $this->assertFalse($this->tf->HasTwoFactor(self::USER));
        [, $result] = $this->enrollTotp();
        $this->assertTrue($this->tf->HasTwoFactor(self::USER));
        $this->assertCount(10, $result['recovery_codes']);
        $this->assertSame(10, $this->tf->RecoveryCodesLeft(self::USER));
        $this->assertTrue($this->tf->AsksAtLogin(self::USER));
        $this->assertFalse($this->tf->AsksOnPreferences(self::USER));
    }

    public function testASecondMethodDoesNotReplaceTheRecoveryCodes(): void
    {
        $this->enrollTotp();
        $start = $this->tf->StartTotp(self::USER, self::HASH);
        // the next step's code, because the first authenticator's code for this step is spent
        $result = $this->tf->FinishTotp(self::USER, self::HASH, 'Tablet', Totp::Code($start['secret'], Totp::Step($this->now)));
        $this->assertTrue($result['ok']);
        $this->assertArrayNotHasKey('recovery_codes', $result);
        $this->assertCount(2, $this->tf->Methods(self::USER));
    }

    public function testAWrongCodeDoesNotAddTheAuthenticator(): void
    {
        $this->tf->StartTotp(self::USER, self::HASH);
        $result = $this->tf->FinishTotp(self::USER, self::HASH, 'Phone', '000000');
        $this->assertSame('wrong_code', $result['error']);
        $this->assertFalse($this->tf->HasTwoFactor(self::USER));
    }

    public function testSetupExpiresAndCannotBeReused(): void
    {
        $start = $this->tf->StartTotp(self::USER, self::HASH);
        $this->now += TwoFactor::CHALLENGE_TTL + 1;
        $this->assertSame('expired', $this->tf->FinishTotp(self::USER, self::HASH, 'Phone', $this->code($start['secret']))['error']);

        $start = $this->tf->StartTotp(self::USER, self::HASH);
        $this->tf->FinishTotp(self::USER, self::HASH, 'Phone', '000000');   // the wrong code uses up the setup
        $this->assertSame('expired', $this->tf->FinishTotp(self::USER, self::HASH, 'Phone', $this->code($start['secret']))['error']);
    }

    public function testSecretsAreEncryptedInTheDatabase(): void
    {
        [$secret] = $this->enrollTotp();
        $stored = $this->db->query("SELECT secret FROM user_two_factor")->fetchColumn();
        $this->assertStringStartsWith('enc:', $stored);
        $this->assertStringNotContainsString($secret, $stored);
        $this->now += 30;
        $this->assertTrue($this->tf->VerifyCode(self::USER, $this->code($secret))['ok']);
    }

    // ── checking codes ──

    public function testACodeWorksOnceAndNotAgain(): void
    {
        [$secret] = $this->enrollTotp();
        $this->now += 60;
        $code = $this->code($secret);
        $this->assertSame('totp', $this->tf->VerifyCode(self::USER, $code)['via']);
        $this->assertSame('wrong_code', $this->tf->VerifyCode(self::USER, $code)['error']);
    }

    public function testRecoveryCodesWorkOnce(): void
    {
        [, $enrolled] = $this->enrollTotp();
        $recovery = $enrolled['recovery_codes'][3];
        $this->assertSame('recovery', $this->tf->VerifyCode(self::USER, strtoupper($recovery))['via']);
        $this->assertSame(9, $this->tf->RecoveryCodesLeft(self::USER));
        $this->assertFalse($this->tf->VerifyCode(self::USER, $recovery)['ok']);
    }

    public function testTooManyWrongCodesLockCheckingForAWhile(): void
    {
        [$secret] = $this->enrollTotp();
        $this->now += 60;
        for ($i = 0; $i < TwoFactor::MAX_TRIES; $i++) {
            $this->assertSame('wrong_code', $this->tf->VerifyCode(self::USER, '000000')['error']);
        }
        $this->assertSame('throttled', $this->tf->VerifyCode(self::USER, $this->code($secret))['error'], 'even the right code');
        $this->now += TwoFactor::TRY_WINDOW + 1;
        $this->assertTrue($this->tf->VerifyCode(self::USER, $this->code($secret))['ok']);
    }

    public function testOneMembersMistakesDoNotLockAnother(): void
    {
        $this->session('bob', 'bob-hash');
        [$secretB] = $this->enrollTotp('bob', 'bob-hash');
        $this->enrollTotp();
        $this->now += 60;
        for ($i = 0; $i < TwoFactor::MAX_TRIES; $i++) { $this->tf->VerifyCode(self::USER, '000000'); }
        $this->assertTrue($this->tf->VerifyCode('bob', $this->code($secretB))['ok']);
    }

    // ── sessions ──

    public function testAPendingSessionGrantsNothingUntilTheSecondFactorIsDone(): void
    {
        $this->enrollTotp();
        $this->tf->MarkPending(self::USER, self::HASH);
        $this->assertTrue($this->tf->IsPending(self::USER, self::HASH));
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));
        $this->tf->MfaDone(self::USER, self::HASH);
        $this->assertFalse($this->tf->IsPending(self::USER, self::HASH));
        $this->assertFalse($this->tf->MfaStale(self::USER, self::HASH));
    }

    public function testALongLivedSessionAsksAgainAfterThirtyDays(): void
    {
        $this->enrollTotp();
        $this->tf->MfaDone(self::USER, self::HASH);
        $this->now += 29 * 86400;
        $this->assertFalse($this->tf->MfaStale(self::USER, self::HASH));
        $this->now += 2 * 86400;
        $this->assertTrue($this->tf->MfaStale(self::USER, self::HASH));
    }

    public function testNothingIsStaleWhenTheMemberTurnedLoginCodesOff(): void
    {
        $this->enrollTotp();
        $this->tf->SetAsks(self::USER, false, false);
        $this->now += 90 * 86400;
        $this->assertFalse($this->tf->MfaStale(self::USER, self::HASH));
        $this->assertFalse($this->tf->AsksAtLogin(self::USER));
    }

    // ── remember this computer ──

    public function testADeviceIsTrustedForThirtyDays(): void
    {
        $this->enrollTotp();
        $token = $this->tf->DeviceToken(self::USER);
        $this->assertNotSame('', $token);
        $this->assertTrue($this->tf->DeviceTrusted(self::USER, $token));
        $this->now += 29 * 86400;
        $this->assertTrue($this->tf->DeviceTrusted(self::USER, $token));
        $this->now += 2 * 86400;
        $this->assertFalse($this->tf->DeviceTrusted(self::USER, $token));
    }

    public function testADeviceTokenStopsWorkingWhenSomethingChanges(): void
    {
        $this->enrollTotp();
        $token = $this->tf->DeviceToken(self::USER);
        $this->assertFalse($this->tf->DeviceTrusted('bob', $token), "someone else's");
        $this->assertFalse($this->tf->DeviceTrusted(self::USER, $token . 'x'), 'tampered with');
        $this->assertFalse($this->tf->DeviceTrusted(self::USER, 'garbage'));

        $this->db->exec("UPDATE user_info SET password = 'a-new-hash' WHERE username = 'alice'");
        $this->assertFalse($this->tf->DeviceTrusted(self::USER, $token), 'password changed');

        $this->db->exec("UPDATE user_info SET password = 'hash-of-password' WHERE username = 'alice'");
        $this->assertTrue($this->tf->DeviceTrusted(self::USER, $token));
        $this->now += 30;
        $start = $this->tf->StartTotp(self::USER, self::HASH);
        $this->tf->FinishTotp(self::USER, self::HASH, 'Tablet', $this->code($start['secret']));
        $this->assertFalse($this->tf->DeviceTrusted(self::USER, $token), 'a method was added');
    }

    public function testNoDeviceTrustWithoutASiteKey(): void
    {
        $tf = new TwoFactor($this->db, ['hosturl' => 'https://wifidb.example.com'], fn () => $this->now);
        $this->assertSame('', $tf->DeviceToken(self::USER));
        $this->assertFalse($tf->DeviceTrusted(self::USER, 'a.1.b'));
    }

    // ── admin mode ──

    public function testAdminModeNeedsTwoFactorSetUp(): void
    {
        $result = $this->tf->StepUpWithCode(self::USER, self::HASH, '123456');
        $this->assertSame('two_factor_required', $result['error']);
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));
    }

    public function testAdminModeNeedsItsOwnFreshCode(): void
    {
        [$secret] = $this->enrollTotp();
        $this->now += 60;
        $loginCode = $this->code($secret);
        $this->assertTrue($this->tf->VerifyCode(self::USER, $loginCode)['ok']);   // what signed the member in
        $this->tf->MfaDone(self::USER, self::HASH);

        // the same code cannot also turn admin mode on, and neither can being signed in
        $this->assertSame('wrong_code', $this->tf->StepUpWithCode(self::USER, self::HASH, $loginCode)['error']);
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));

        $this->now += 30;
        $result = $this->tf->StepUpWithCode(self::USER, self::HASH, $this->code($secret));
        $this->assertTrue($result['ok']);
        $this->assertTrue($this->tf->AdminModeActive(self::USER, self::HASH));
    }

    public function testAWrongCodeDoesNotTurnAdminModeOn(): void
    {
        $this->enrollTotp();
        $this->assertSame('wrong_code', $this->tf->StepUpWithCode(self::USER, self::HASH, '000000')['error']);
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));
    }

    public function testAdminModeIsPerSession(): void
    {
        [$secret] = $this->enrollTotp();
        $this->session(self::USER, 'another-session');
        $this->now += 60;
        $this->assertTrue($this->tf->StepUpWithCode(self::USER, self::HASH, $this->code($secret))['ok']);
        $this->assertTrue($this->tf->AdminModeActive(self::USER, self::HASH));
        $this->assertFalse($this->tf->AdminModeActive(self::USER, 'another-session'));
    }

    public function testAdminModeEndsWhenIdleAndStaysWhileUsed(): void
    {
        [$secret] = $this->enrollTotp();
        $this->now += 60;
        $this->tf->StepUpWithCode(self::USER, self::HASH, $this->code($secret));
        $this->now += 20 * 60;
        $this->assertTrue($this->tf->AdminModeActive(self::USER, self::HASH), 'used again after 20 minutes');
        $this->now += 20 * 60;
        $this->assertTrue($this->tf->AdminModeActive(self::USER, self::HASH), 'that use pushed the end out');
        $this->now += 31 * 60;
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH), 'idle for more than 30 minutes');
    }

    public function testAdminModeCanBeTurnedOff(): void
    {
        [$secret] = $this->enrollTotp();
        $this->now += 60;
        $this->tf->StepUpWithCode(self::USER, self::HASH, $this->code($secret));
        $this->tf->DisableAdminMode(self::USER, self::HASH);
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));
    }

    public function testAPendingSessionCannotTurnAdminModeOn(): void
    {
        [$secret] = $this->enrollTotp();
        $this->tf->MarkPending(self::USER, self::HASH);
        $this->now += 60;
        $this->assertSame('no_session', $this->tf->StepUpWithCode(self::USER, self::HASH, $this->code($secret))['error']);
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));
    }

    // ── removing and resetting ──

    public function testRemovingTheLastMethodTurnsTwoFactorOffEntirely(): void
    {
        [$secret] = $this->enrollTotp();
        $this->now += 60;
        $this->tf->StepUpWithCode(self::USER, self::HASH, $this->code($secret));
        $id = $this->tf->Methods(self::USER)[0]['id'];
        $this->assertFalse($this->tf->RemoveMethod('bob', $id), "not bob's to remove");
        $this->assertTrue($this->tf->RemoveMethod(self::USER, $id));
        $this->assertFalse($this->tf->HasTwoFactor(self::USER));
        $this->assertSame(0, $this->tf->RecoveryCodesLeft(self::USER));
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));
    }

    public function testResetClearsEverythingForTheLockedOutMember(): void
    {
        [, $enrolled] = $this->enrollTotp();
        $this->tf->ResetTwoFactor(self::USER);
        $this->assertSame([], $this->tf->Methods(self::USER));
        $this->assertFalse($this->tf->VerifyCode(self::USER, $enrolled['recovery_codes'][0])['ok']);
    }

    // ── passkeys ──

    private function addPasskey(SoftwareAuthenticator $passkey, string $name = 'Laptop'): array
    {
        $start = $this->tf->StartPasskeyRegistration(self::USER, self::HASH);
        [$cd, $att] = $passkey->register($start['challenge'], $this->tf->origin, $this->tf->rpId);
        return $this->tf->FinishPasskeyRegistration(self::USER, self::HASH, $name, $cd, $att);
    }

    public function testAddingAPasskeyAndSigningInWithIt(): void
    {
        $passkey = new SoftwareAuthenticator();
        $added = $this->addPasskey($passkey);
        $this->assertTrue($added['ok']);
        $this->assertCount(10, $added['recovery_codes'], 'a passkey as the first method gets recovery codes too');

        $start = $this->tf->StartPasskeyAssertion(self::USER, self::HASH);
        $this->assertSame([Webauthn::Base64UrlEncode($passkey->credentialId)], $start['allow']);
        [$cd, $ad, $sig] = $passkey->assert($start['challenge'], $this->tf->origin, $this->tf->rpId, 1);
        $result = $this->tf->VerifyPasskey(self::USER, self::HASH, Webauthn::Base64UrlEncode($passkey->credentialId), $cd, $ad, $sig);
        $this->assertSame('passkey', $result['via']);
    }

    public function testAPasskeySignInCannotBeReplayed(): void
    {
        $passkey = new SoftwareAuthenticator();
        $this->addPasskey($passkey);
        $id = Webauthn::Base64UrlEncode($passkey->credentialId);
        $start = $this->tf->StartPasskeyAssertion(self::USER, self::HASH);
        [$cd, $ad, $sig] = $passkey->assert($start['challenge'], $this->tf->origin, $this->tf->rpId, 1);
        $this->assertTrue($this->tf->VerifyPasskey(self::USER, self::HASH, $id, $cd, $ad, $sig)['ok']);
        $this->assertSame('expired', $this->tf->VerifyPasskey(self::USER, self::HASH, $id, $cd, $ad, $sig)['error']);
    }

    public function testThePasskeyOfAnotherMemberIsNotAccepted(): void
    {
        $alices = new SoftwareAuthenticator();
        $this->addPasskey($alices);
        $this->session('bob', 'bob-hash');
        $start = $this->tf->StartPasskeyRegistration('bob', 'bob-hash');
        $bobs = new SoftwareAuthenticator();
        [$cd, $att] = $bobs->register($start['challenge'], $this->tf->origin, $this->tf->rpId);
        $this->assertTrue($this->tf->FinishPasskeyRegistration('bob', 'bob-hash', 'Key', $cd, $att)['ok']);

        $start = $this->tf->StartPasskeyAssertion('bob', 'bob-hash');
        [$cd, $ad, $sig] = $alices->assert($start['challenge'], $this->tf->origin, $this->tf->rpId, 1);
        $result = $this->tf->VerifyPasskey('bob', 'bob-hash', Webauthn::Base64UrlEncode($alices->credentialId), $cd, $ad, $sig);
        $this->assertSame('unknown_passkey', $result['error']);
    }

    public function testThePasskeyIsRefusedFromAnotherSite(): void
    {
        $passkey = new SoftwareAuthenticator();
        $start = $this->tf->StartPasskeyRegistration(self::USER, self::HASH);
        [$cd, $att] = $passkey->register($start['challenge'], 'https://evil.example.net', $this->tf->rpId);
        $result = $this->tf->FinishPasskeyRegistration(self::USER, self::HASH, 'Laptop', $cd, $att);
        $this->assertSame('passkey_rejected', $result['error']);
        $this->assertFalse($this->tf->HasTwoFactor(self::USER));
    }

    public function testTheSamePasskeyCannotBeAddedTwice(): void
    {
        $passkey = new SoftwareAuthenticator();
        $this->assertTrue($this->addPasskey($passkey)['ok']);
        $this->assertSame('already_added', $this->addPasskey($passkey)['error']);
    }

    public function testAdminModeWithAPasskeyNeedsThePersonVerified(): void
    {
        $passkey = new SoftwareAuthenticator();
        $this->addPasskey($passkey);
        $id = Webauthn::Base64UrlEncode($passkey->credentialId);

        // a touch only (user present, not verified): refused
        $start = $this->tf->StartPasskeyAssertion(self::USER, self::HASH);
        [$cd, $ad, $sig] = $passkey->assert($start['challenge'], $this->tf->origin, $this->tf->rpId, 1, 0x01);
        $this->assertFalse($this->tf->StepUpWithPasskey(self::USER, self::HASH, $id, $cd, $ad, $sig)['ok']);
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH));

        // a PIN or fingerprint (user present and verified): admin mode turns on
        $start = $this->tf->StartPasskeyAssertion(self::USER, self::HASH);
        [$cd, $ad, $sig] = $passkey->assert($start['challenge'], $this->tf->origin, $this->tf->rpId, 2, 0x05);
        $this->assertTrue($this->tf->StepUpWithPasskey(self::USER, self::HASH, $id, $cd, $ad, $sig)['ok']);
        $this->assertTrue($this->tf->AdminModeActive(self::USER, self::HASH));
    }
    // ── Confirming before preferences are saved ──

    public function testPreferencesOnlyAskWhenTheMemberChoseThat(): void
    {
        [$secret] = $this->enrollTotp();
        $this->assertFalse($this->tf->NeedsPreferencesCheck(self::USER, self::HASH), 'off until chosen');
        $this->tf->SetAsks(self::USER, true, true);
        $this->assertTrue($this->tf->NeedsPreferencesCheck(self::USER, self::HASH));
        $this->assertFalse($this->tf->NeedsPreferencesCheck('bob', 'x'), 'a member with no method is never asked');
    }

    public function testAGoodCodeOpensPreferencesForTenMinutesOnly(): void
    {
        [$secret] = $this->enrollTotp();
        $this->tf->SetAsks(self::USER, true, true);
        $this->now += 60;
        $this->assertTrue($this->tf->ConfirmPreferencesWithCode(self::USER, self::HASH, $this->code($secret))['ok']);
        $this->assertFalse($this->tf->NeedsPreferencesCheck(self::USER, self::HASH));
        $this->now += 9 * 60;
        $this->assertFalse($this->tf->NeedsPreferencesCheck(self::USER, self::HASH), 'still open');
        $this->now += 2 * 60;
        $this->assertTrue($this->tf->NeedsPreferencesCheck(self::USER, self::HASH), 'closed again');
    }

    public function testAWrongCodeLeavesPreferencesClosed(): void
    {
        $this->enrollTotp();
        $this->tf->SetAsks(self::USER, true, true);
        $this->assertSame('wrong_code', $this->tf->ConfirmPreferencesWithCode(self::USER, self::HASH, '000000')['error']);
        $this->assertTrue($this->tf->NeedsPreferencesCheck(self::USER, self::HASH));
    }

    public function testTheCodeThatSignedYouInCannotAlsoOpenPreferences(): void
    {
        [$secret] = $this->enrollTotp();
        $this->tf->SetAsks(self::USER, true, true);
        $this->now += 60;
        $code = $this->code($secret);
        $this->assertTrue($this->tf->VerifyCode(self::USER, $code)['ok']);
        $this->assertSame('wrong_code', $this->tf->ConfirmPreferencesWithCode(self::USER, self::HASH, $code)['error']);
    }

    public function testConfirmingPreferencesIsNotAdminModeAndTheOtherWayRound(): void
    {
        [$secret] = $this->enrollTotp();
        $this->tf->SetAsks(self::USER, true, true);
        $this->now += 60;
        $this->tf->ConfirmPreferencesWithCode(self::USER, self::HASH, $this->code($secret));
        $this->assertFalse($this->tf->AdminModeActive(self::USER, self::HASH), 'confirming preferences does not turn on admin mode');
        $this->now += 30;
        $this->tf->SetAsks(self::USER, true, true);
        $this->db->exec("UPDATE user_login_hashes SET prefs_until = NULL");
        $this->assertTrue($this->tf->StepUpWithCode(self::USER, self::HASH, $this->code($secret))['ok']);
        $this->assertTrue($this->tf->NeedsPreferencesCheck(self::USER, self::HASH), 'and admin mode does not confirm preferences');
    }

    public function testConfirmingIsPerSessionAndNotForAPendingOne(): void
    {
        [$secret] = $this->enrollTotp();
        $this->tf->SetAsks(self::USER, true, true);
        $this->session(self::USER, 'other');
        $this->tf->MarkPending(self::USER, 'other');
        $this->now += 60;
        $this->assertSame('no_session', $this->tf->ConfirmPreferencesWithCode(self::USER, 'other', $this->code($secret))['error']);
        $this->assertTrue($this->tf->NeedsPreferencesCheck(self::USER, 'other'));
    }
}
