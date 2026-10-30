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
