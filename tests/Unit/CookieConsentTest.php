<?php
use PHPUnit\Framework\TestCase;

require_once WIFIDB_LIB . '/cookie_consent.inc.php';
require_once WIFIDB_LIB . '/visitor_country.inc.php';

// Who is asked before analytics and ad cookies, and the country that decides it.
final class CookieConsentTest extends TestCase
{
    public function testWhereItsRequiredAsksTheEeaTheUkAndSwitzerland(): void
    {
        foreach (['DE', 'FR', 'IE', 'NO', 'IS', 'LI', 'GB', 'CH', 'cy'] as $country) {
            $this->assertTrue(CookieConsent::Ask('region', $country), "$country is asked");
        }
        foreach (['US', 'CA', 'JP', 'BR', 'AU'] as $country) {
            $this->assertFalse(CookieConsent::Ask('region', $country), "$country isn't");
        }
        $this->assertTrue(CookieConsent::Ask('region', null), 'a country that can\'t be told is asked, to be safe');
    }

    public function testEveryoneOrNobody(): void
    {
        $this->assertTrue(CookieConsent::Ask('ask', 'US'));
        $this->assertFalse(CookieConsent::Ask('off', 'DE'));
        $this->assertTrue(CookieConsent::Ask('nonsense', 'US'), 'an unknown setting asks everyone');
        $this->assertSame('ask', CookieConsent::Mode(''));
    }

    public function testACdnsCountryIsTaken(): void
    {
        $this->assertSame('DE', VisitorCountry::Lookup(['HTTP_CF_IPCOUNTRY' => 'de', 'REMOTE_ADDR' => '203.0.113.9'], ''));
        $this->assertNull(VisitorCountry::Lookup(['HTTP_CF_IPCOUNTRY' => 'XX', 'REMOTE_ADDR' => '203.0.113.9'], ''), 'XX is Cloudflare not knowing');
        $this->assertNull(VisitorCountry::Lookup(['HTTP_CF_IPCOUNTRY' => 'T1', 'REMOTE_ADDR' => '203.0.113.9'], ''), 'T1 is Tor');
        $this->assertNull(VisitorCountry::Lookup(['REMOTE_ADDR' => '203.0.113.9'], '/no/such/file.mmdb'), 'no database: not known');
    }

    public function testTheCountryInEachDatabasesAnswer(): void
    {
        $this->assertSame('SE', VisitorCountry::CodeIn(['country' => ['iso_code' => 'SE', 'names' => ['en' => 'Sweden']]]), "DB-IP's and MaxMind's");
        $this->assertSame('SE', VisitorCountry::CodeIn(['country_code' => 'se', 'country' => 'Sweden']), "IPinfo's");
        $this->assertNull(VisitorCountry::CodeIn(null));
        $this->assertNull(VisitorCountry::CodeIn(['country' => ['iso_code' => 'Sweden']]));
    }

    public function testAFetchThatIsntADatabaseLeavesTheOneThereAlone(): void
    {
        $dir = sys_get_temp_dir() . '/wifidb-geoip-' . getmypid();
        @mkdir($dir);
        $db = "$dir/country.mmdb";
        file_put_contents($db, 'the copy that works');
        touch($db, time() - 40 * 86400);
        $urls = [];
        $fetch = function ($url, $file) use (&$urls) {
            $urls[] = $url;
            file_put_contents($file, str_repeat('<html>not a database</html>', 50000));
            return true;
        };
        try {
            VisitorCountry::Update($db, 'https://example.com/country-%s.mmdb', gmmktime(0, 0, 0, 1, 2, 2027), $fetch);
            $this->fail('a fetch that is not a database must not be put in place');
        } catch (RuntimeException $e) {
            $this->assertStringContainsString('No country database could be fetched', $e->getMessage());
        }
        $this->assertSame('the copy that works', file_get_contents($db));
        $this->assertSame(['https://example.com/country-2027-01.mmdb', 'https://example.com/country-2026-12.mmdb'], $urls,
            "this month's, then last month's (early in a month, before the new one is out)");
        $this->assertSame([basename($db)], array_values(array_diff(scandir($dir), ['.', '..'])), 'nothing left behind');

        touch($db);
        $this->assertSame('up to date', VisitorCountry::Update($db, '', null, function () { throw new LogicException('not fetched'); }));
        unlink($db);
        rmdir($dir);
    }
}
