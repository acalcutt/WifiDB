// Passkeys end to end in a real browser (Chromium with a virtual authenticator): add one on the Security page, sign in
// with it, and use it for the check before preferences are saved. Run by test-passkey.inc.sh, inside the Playwright image.
// Reads USER and PASS from the environment; the site is https://wifidb.test (a TLS proxy with a throwaway certificate).
const { chromium } = require('playwright');

const base = 'https://wifidb.test';
let failed = 0;
function check(name, ok) { console.log((ok ? '  ok   ' : '  FAIL ') + name); if (!ok) { failed = 1; } }

(async () => {
    const browser = await chromium.launch();
    const context = await browser.newContext({ ignoreHTTPSErrors: true });
    const page = await context.newPage();
    page.setDefaultTimeout(20000);
    const cdp = await context.newCDPSession(page);
    await cdp.send('WebAuthn.enable');
    const { authenticatorId } = await cdp.send('WebAuthn.addVirtualAuthenticator', { options: {
        protocol: 'ctap2', transport: 'internal', hasResidentKey: true, hasUserVerification: true, isUserVerified: true, automaticPresenceSimulation: true,
    } });

    async function login() {
        await page.goto(base + '/login.php');
        await page.fill('input[name=time_user]', process.env.USER_NAME);
        await page.fill('input[name=time_pass]', process.env.PASS);
        await Promise.all([page.waitForLoadState('load'), page.click('input[type=submit][value=Login]')]);
    }
    const text = async () => (await page.content());

    try {
        await login();
        await page.goto(base + '/cp/security.php');
        check('the Security page opens for a member with no second factor', (await text()).includes('Two-factor sign-in'));
        check('the passkey button shows (the browser supports WebAuthn)', await page.isVisible('#wifidb_passkey_add'));

        await page.fill('#wifidb_passkey_name', 'Virtual key');
        await page.click('#wifidb_passkey_add');
        await page.waitForSelector('#wifidb_passkey_codes', { state: 'visible' });
        const codes = (await page.textContent('#wifidb_passkey_codes_list')).trim().split('\n').filter(Boolean);
        check('adding a passkey shows ten recovery codes', codes.length === 10);
        const { credentials } = await cdp.send('WebAuthn.getCredentials', { authenticatorId });
        check('the browser really holds the new credential', credentials.length === 1);

        await page.goto(base + '/cp/security.php');
        const html = await text();
        check('the page lists it by name', html.includes('Virtual key') && html.includes('Passkey'));

        // sign in again from scratch: the second step must offer the passkey and accept it
        await context.clearCookies();
        await login();
        await page.goto(base + '/cp/security.php');
        check('a right password alone grants nothing now (the second step is pending)', (await text()).includes('must be logged in'));
        await page.goto(base + '/login.php?func=mfa');
        check('the second step offers the passkey', await page.isVisible('#wifidb_mfa_passkey'));
        await page.click('#wifidb_mfa_passkey');
        await page.waitForURL((url) => !url.toString().includes('func=mfa'), { timeout: 20000 });
        await page.goto(base + '/cp/security.php');
        check('...and the passkey signs in', (await text()).includes('Two-factor sign-in') && !(await text()).includes('must be logged in'));
        const cookies = await context.cookies();
        check('no device cookie was set without asking for one', !cookies.some((c) => c.name === 'WiFiDB_device'));

        // ask before preferences are saved
        await page.check('#mfa_prefs');
        await page.click('form[action*="func=settings"] input[type=submit]');
        await page.goto(base + '/cp/index.php?func=profile');
        check('preferences now send the member to the check', page.url().includes('/cp/confirm.php'));
        check('the check offers the passkey', await page.isVisible('#wifidb_confirm_passkey'));
        await page.click('#wifidb_confirm_passkey');
        await page.waitForURL((url) => url.toString().includes('func=profile'), { timeout: 20000 });
        check('the passkey opens the preferences', !page.url().includes('confirm.php') && page.url().includes('func=profile'));
        await page.goto(base + '/cp/index.php?func=pref');
        check('...and they stay open for the next page', !page.url().includes('confirm.php'));

        const after = await cdp.send('WebAuthn.getCredentials', { authenticatorId });
        check('the credential counter moved on (a copy would show up)', after.credentials[0].signCount > 0);
    } catch (error) {
        console.log('  FAIL the browser test stopped: ' + error.message.split('\n')[0]);
        console.log('       at ' + page.url());
        failed = 1;
    }
    await browser.close();
    process.exit(failed);
})();
