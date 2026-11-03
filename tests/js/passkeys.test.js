// Run with: node --test tests/js/passkeys.test.js
const test = require('node:test');
const assert = require('node:assert');
const { toBuffer, toText } = require('../../wifidb/wifidb/lib/js/wifidb/passkeys.js');

test('base64url text and bytes convert both ways, without padding or + and /', () => {
    const bytes = Uint8Array.from([0, 250, 251, 252, 253, 254, 255, 62, 63]);
    const text = toText(bytes.buffer);
    assert.match(text, /^[A-Za-z0-9_-]+$/);
    assert.deepStrictEqual(Array.from(new Uint8Array(toBuffer(text))), Array.from(bytes));
});

test('text the server sends (no padding, any length) is read', () => {
    for (const length of [1, 2, 3, 4, 31, 32, 33]) {
        const bytes = Uint8Array.from({ length }, (_, i) => (i * 37 + 11) % 256);
        assert.deepStrictEqual(Array.from(new Uint8Array(toBuffer(toText(bytes.buffer)))), Array.from(bytes), `length ${length}`);
    }
});

// signIn() in a stand-in browser: returns what navigator.credentials.get was asked for
async function signInAsks(begin, userVerification) {
    const path = require.resolve('../../wifidb/wifidb/lib/js/wifidb/passkeys.js');
    const asked = [];
    const bytes = new Uint8Array([1, 2, 3]).buffer;
    global.window = {
        PublicKeyCredential: function () {},
        navigator: { credentials: { get: async (options) => {
            asked.push(options.publicKey);
            return { id: 'cred', response: { clientDataJSON: bytes, authenticatorData: bytes, signature: bytes } };
        } } },
    };
    const realFetch = global.fetch;
    global.fetch = async (url) => ({ json: async () => (url === 'begin' ? begin : { ok: true }) });
    try {
        delete require.cache[path];
        const answer = await require(path).signIn('begin', 'finish', {}, userVerification);
        assert.deepStrictEqual(answer, { ok: true });
    } finally {
        global.fetch = realFetch;
        delete global.window;
        delete require.cache[path];
    }
    return asked[0];
}

test('signIn asks for the PIN or fingerprint when the server says so (admin mode), and not otherwise', async () => {
    const begin = { ok: true, challenge: 'AQID', rp_id: 'wifidb.example.com', allow: ['AQID'] };
    assert.strictEqual((await signInAsks({ ...begin, user_verification: 'required' })).userVerification, 'required');
    assert.strictEqual((await signInAsks(begin)).userVerification, 'discouraged');
    assert.strictEqual((await signInAsks(begin, 'required')).userVerification, 'required', 'the page can still ask for it');
});
