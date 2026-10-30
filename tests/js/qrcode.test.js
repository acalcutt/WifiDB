// Run with: node --test tests/js/qrcode.test.js
const test = require('node:test');
const assert = require('node:assert');
const qrcode = require('../../wifidb/wifidb/lib/js/qrcode-generator/qrcode.js');
const { modulePath, QUIET } = require('../../wifidb/wifidb/lib/js/wifidb/qrcode.js');

function make(text) {
    const qr = qrcode(0, 'M');
    qr.addData(text);
    qr.make();
    return qr;
}

test('the smallest QR code is 21 modules, with the three finder squares in place', () => {
    const qr = make('hi');
    assert.strictEqual(qr.getModuleCount(), 21);
    for (const [r, c] of [[0, 0], [0, 20], [20, 0]]) { assert.ok(qr.isDark(r, c), `corner ${r},${c}`); }
    assert.ok(!qr.isDark(1, 1), 'inside the finder ring');
    assert.ok(qr.isDark(3, 3), 'the finder centre');
});

test('the path draws exactly the dark modules, inside a quiet border', () => {
    const qr = make('hi');
    const { path, size } = modulePath(qr);
    assert.strictEqual(size, 21 + 2 * QUIET);
    let dark = 0;
    for (let r = 0; r < 21; r++) { for (let c = 0; c < 21; c++) { if (qr.isDark(r, c)) { dark++; } } }
    assert.strictEqual((path.match(/M/g) || []).length, dark);
    assert.ok(path.startsWith(`M${QUIET} ${QUIET}h1v1h-1z`), 'the top-left module is first');
    assert.ok(/^(M\d+ \d+h1v1h-1z)+$/.test(path), 'only numbers and the fixed square, nothing from the text');
});

test('a link-sized text fits and grows the code', () => {
    const url = 'https://wifidb.example.com/cp/redeem_link.php?token=' + 'a'.repeat(64);
    const qr = make(url);
    assert.ok(qr.getModuleCount() > 21);
    assert.strictEqual((qr.getModuleCount() - 17) % 4, 0, 'sizes are 17 + 4n');
});
