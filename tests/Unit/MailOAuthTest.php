<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/mail_oauth.inc.php';

// Signing in to Gmail with a Google account: connecting it (MailOAuth::Start and Finish) and the access token PHPMailer
// gets when it signs in, with Google stood in for by a function.
final class MailOAuthTest extends TestCase
{
    private array $calls = [];
    private int $now = 1_800_000_000;

    /** Google: answers by address, and keeps what was asked */
    private function google(array $answers): callable
    {
        return function ($method, $url, $headers, $body) use ($answers) {
            $this->calls[] = ['method' => $method, 'url' => $url, 'headers' => $headers, 'form' => $body === null ? [] : $this->form($body)];
            $answer = $answers[$url];
            return is_callable($answer) ? $answer(end($this->calls)) : $answer;
        };
    }

    private function form(string $body): array { parse_str($body, $form); return $form; }

    private function provider(array $answers, string $refresh = 'refresh-1'): MailOAuth
    {
        return new MailOAuth('wifidb@example.test', 'client-1', 'secret-1', $refresh, [], $this->google($answers), fn () => $this->now);
    }

    public function testStartAsksForMailOfflineWithPkceAndKeepsTheVerifierInTheCookie(): void
    {
        [$url, $cookie] = MailOAuth::Start('client-1', 'https://wifidb.example/admin/mail.php?oauth=google', 'signup', 'me@example.test', [], $this->now);
        $this->assertStringStartsWith('https://accounts.google.com/o/oauth2/v2/auth?', $url);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->assertSame('https://mail.google.com/ openid email', $q['scope']);
        $this->assertSame(['offline', 'consent', 'code', 'S256'], [$q['access_type'], $q['prompt'], $q['response_type'], $q['code_challenge_method']]);
        $this->assertSame('https://wifidb.example/admin/mail.php?oauth=google', $q['redirect_uri']);
        $this->assertSame('me@example.test', $q['login_hint']);
        $kept = json_decode(base64_decode($cookie), true);
        $this->assertSame($q['state'], $kept['state']);
        $this->assertSame('signup', $kept['account']);
        $this->assertSame($this->now + MailOAuth::TTL, $kept['expires']);
        $this->assertSame(rtrim(strtr(base64_encode(hash('sha256', $kept['verifier'], true)), '+/', '-_'), '='), $q['code_challenge']);
        $this->assertStringNotContainsString($kept['verifier'], $url, 'the verifier stays here');
    }

    public function testFinishTradesTheCodeAndSaysWhichAccount(): void
    {
        [$url, $cookie] = MailOAuth::Start('client-1', 'https://wifidb.example/cb', 'main', '', [], $this->now);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $google = $this->google([
            MailOAuth::ENDPOINTS['token'] => [200, json_encode(['access_token' => 'at-1', 'refresh_token' => 'rt-1', 'expires_in' => 3599])],
            MailOAuth::ENDPOINTS['userinfo'] => fn ($call) => in_array('Authorization: Bearer at-1', $call['headers'], true)
                ? [200, json_encode(['email' => 'wifidb@example.test'])] : [401, '{}'],
        ]);
        $this->assertSame(['main', 'rt-1', 'wifidb@example.test'],
            MailOAuth::Finish('client-1', 'secret-1', 'https://wifidb.example/cb', 'code-1', $q['state'], $cookie, '', [], $google, $this->now + 30));
        $kept = json_decode(base64_decode($cookie), true);
        $this->assertSame(['grant_type' => 'authorization_code', 'code' => 'code-1', 'client_id' => 'client-1', 'client_secret' => 'secret-1',
            'redirect_uri' => 'https://wifidb.example/cb', 'code_verifier' => $kept['verifier']], $this->calls[0]['form']);
    }

    public function testFinishRefusesWhatDidNotStartHereOrTookTooLongOrWasRefused(): void
    {
        [$url, $cookie] = MailOAuth::Start('client-1', 'https://wifidb.example/cb', 'main', '', [], $this->now);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $google = $this->google([]);
        foreach ([
            'another browser'  => ['code-1', 'someone-elses-state', $cookie, '', $this->now, "didn't start in this browser"],
            'no cookie'        => ['code-1', $q['state'], '', '', $this->now, "didn't start in this browser"],
            'too long'         => ['code-1', $q['state'], $cookie, '', $this->now + MailOAuth::TTL + 1, 'took too long'],
            'said no'          => ['', $q['state'], $cookie, 'access_denied', $this->now, 'Google said no (access_denied)'],
        ] as $case => [$code, $state, $c, $error, $when, $says]) {
            try {
                MailOAuth::Finish('client-1', 'secret-1', 'https://wifidb.example/cb', $code, $state, $c, $error, [], $google, $when);
                $this->fail("$case was accepted");
            } catch (RuntimeException $e) {
                $this->assertStringContainsString($says, $e->getMessage(), $case);
            }
        }
        $this->assertSame([], $this->calls, 'none of them asked Google anything');
    }

    public function testNoRefreshTokenIsAFailureNotASilentlyBrokenSetup(): void
    {
        [$url, $cookie] = MailOAuth::Start('client-1', 'https://wifidb.example/cb', 'main', '', [], $this->now);
        parse_str(parse_url($url, PHP_URL_QUERY), $q);
        $this->expectExceptionMessage('no refresh token');
        MailOAuth::Finish('client-1', 'secret-1', 'https://wifidb.example/cb', 'code-1', $q['state'], $cookie, '', [],
            $this->google([MailOAuth::ENDPOINTS['token'] => [200, json_encode(['access_token' => 'at-1'])]]), $this->now);
    }

    public function testPHPMailerGetsTheAccessTokenInXoauth2FormKeptUntilItRunsOut(): void
    {
        $issued = 0;
        $oauth = $this->provider([MailOAuth::ENDPOINTS['token'] => function ($call) use (&$issued) {
            return [200, json_encode(['access_token' => 'at-' . (++$issued), 'expires_in' => 3600])];
        }]);
        $this->assertInstanceOf(PHPMailer\PHPMailer\OAuthTokenProvider::class, $oauth);
        $this->assertSame("user=wifidb@example.test\001auth=Bearer at-1\001\001", base64_decode($oauth->getOauth64()));
        $this->assertSame(['grant_type' => 'refresh_token', 'refresh_token' => 'refresh-1', 'client_id' => 'client-1', 'client_secret' => 'secret-1'],
            $this->calls[0]['form']);
        $oauth->getOauth64();
        $this->assertCount(1, $this->calls, 'the same token for the next message');
        $this->now += 3600 - 59;
        $this->assertSame("user=wifidb@example.test\001auth=Bearer at-2\001\001", base64_decode($oauth->getOauth64()), 'a new one when it is about to run out');
        $this->assertSame('', $oauth->Error());
    }

    public function testATokenGoogleTookBackSaysToConnectAgain(): void
    {
        $oauth = $this->provider([MailOAuth::ENDPOINTS['token'] => [400, json_encode(['error' => 'invalid_grant', 'error_description' => 'Token has been expired or revoked.'])]]);
        try {
            $oauth->getOauth64();
            $this->fail('a token was made up');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('invalid_grant (Token has been expired or revoked.)', $e->getMessage());
            $this->assertStringContainsString('connect the account again on the Mail page', $e->getMessage());
            $this->assertSame($e->getMessage(), $oauth->Error(), 'kept, for the log and the Mail page');
        }
    }

    public function testNothingConnectedAsksNobody(): void
    {
        $oauth = $this->provider([], '');
        $this->expectExceptionMessage('No Google account is connected');
        try { $oauth->AccessToken(); } finally { $this->assertSame([], $this->calls); }
    }

    public function testRevokingIsBestEffort(): void
    {
        MailOAuth::Revoke('rt-1', [], $this->google([MailOAuth::ENDPOINTS['revoke'] => [200, '{}']]));
        $this->assertSame(['token' => 'rt-1'], $this->calls[0]['form']);
        MailOAuth::Revoke('rt-1', [], function () { throw new RuntimeException('unreachable'); });
        $this->addToAssertionCount(1);   # and Google being out of reach isn't an error
    }
}
