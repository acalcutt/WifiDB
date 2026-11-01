<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/cleantalk.inc.php';

final class CleanTalkTest extends TestCase
{
    private array $sent = [];

    /** A CleanTalk whose server answers $answer (a string, or false for no answer), remembering what it was sent. */
    private function talk($answer, string $key = 'k3y'): CleanTalk
    {
        return new CleanTalk($key, function ($url, $body, $timeout) use ($answer) {
            $this->sent[] = ['url' => $url, 'body' => $body === null ? null : json_decode($body, true), 'timeout' => $timeout];
            return $answer;
        });
    }

    public function testASignUpIsSentWithWhatCleanTalkNeeds(): void
    {
        $result = $this->talk('{"allow":1,"comment":"*** Allowed ***","id":"x"}')->CheckNewUser('a@example.test', 'alice', '203.0.113.9', true, 14, 'Firefox');
        $this->assertSame(['allow' => true, 'checked' => true, 'reason' => 'Allowed'], $result);
        $sent = $this->sent[0];
        $this->assertSame(CleanTalk::SERVER, $sent['url']);
        $this->assertSame(CleanTalk::TIMEOUT, $sent['timeout']);
        $this->assertSame('check_newuser', $sent['body']['method_name']);
        $this->assertSame('k3y', $sent['body']['auth_key']);
        $this->assertSame('a@example.test', $sent['body']['sender_email']);
        $this->assertSame('alice', $sent['body']['sender_nickname']);
        $this->assertSame('203.0.113.9', $sent['body']['sender_ip']);
        $this->assertSame(1, $sent['body']['js_on']);
        $this->assertSame(14, $sent['body']['submit_time']);
        $this->assertArrayNotHasKey('message', $sent['body']);
    }

    public function testSpamIsRefusedWithCleanTalksReason(): void
    {
        $result = $this->talk('{"allow":0,"comment":"*** Forbidden. Sender blacklisted. ***"}')->CheckNewUser('s@example.test', 'spam', '198.51.100.1', false);
        $this->assertFalse($result['allow']);
        $this->assertTrue($result['checked']);
        $this->assertSame('Forbidden. Sender blacklisted.', $result['reason']);
        $this->assertSame(0, $this->sent[0]['body']['js_on']);
        $this->assertArrayNotHasKey('submit_time', $this->sent[0]['body'], 'unknown, so not sent');
    }

    public function testAMessageCarriesItsText(): void
    {
        $this->talk('{"allow":1}')->CheckMessage('hello there', 'a@example.test', 'alice', '203.0.113.9', true, 30);
        $this->assertSame('check_message', $this->sent[0]['body']['method_name']);
        $this->assertSame('hello there', $this->sent[0]['body']['message']);
    }

    public function testNoAnswerLetsItThrough(): void
    {
        $this->assertSame(['allow' => true, 'checked' => false, 'reason' => 'CleanTalk did not answer'], $this->talk(false)->CheckNewUser('a@x.test', 'a', '1.2.3.4', true));
        $this->assertTrue($this->talk('<html>502</html>')->CheckNewUser('a@x.test', 'a', '1.2.3.4', true)['allow']);
    }

    public function testAnErrorOrAnInactiveKeyLetsItThrough(): void
    {
        $error = $this->talk('{"allow":0,"errno":2,"errstr":"Access key unset."}')->CheckNewUser('a@x.test', 'a', '1.2.3.4', true);
        $this->assertSame(['allow' => true, 'checked' => false, 'reason' => 'CleanTalk: Access key unset.'], $error);
        $inactive = $this->talk('{"allow":0,"inactive":1,"comment":"*** Key is not active ***"}')->CheckMessage('m', 'a@x.test', 'a', '1.2.3.4', true);
        $this->assertTrue($inactive['allow']);
        $this->assertFalse($inactive['checked']);
    }

    public function testCheckingTheKey(): void
    {
        $this->assertSame('ok', $this->talk('{"data":{"valid":1,"moderate":1}}', 'a b')->CheckKey());
        $this->assertSame(CleanTalk::API . '?method_name=notice_paid_till&auth_key=a%20b', $this->sent[0]['url']);
        $this->assertNull($this->sent[0]['body'], 'a GET');
        $this->assertSame('invalid', $this->talk('{"data":{"valid":0}}')->CheckKey());
        $this->assertSame('invalid', $this->talk('{"error_message":"Access key unset.","error_no":1}')->CheckKey());
        $this->assertSame('unreachable', $this->talk(false)->CheckKey());
        $this->assertSame('invalid', $this->talk('{"data":{"valid":1}}', '')->CheckKey(), 'no key, nothing to ask');
    }
}
