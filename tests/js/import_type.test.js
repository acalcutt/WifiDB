// Run with: node --test tests/js/import_type.test.js
const test = require('node:test');
const assert = require('node:assert');
const { guess, summary } = require('../../wifidb/wifidb/lib/js/wifidb/import_type.js');
const { cases } = require('../fixtures/import_types.json');

// the same cases as tests/Unit/ImportTypeTest.php, so the page and the server agree
for (const c of cases) {
    test(`the upload page takes ${c.why} for what it is`, () => {
        assert.strictEqual(guess(c.name, new Uint8Array(Buffer.from(c.head, 'base64'))), c.type);
    });
}

test('what the page says, and selects', () => {
    assert.deepStrictEqual(summary([]), { type: null, message: '' });
    const one = summary([{ name: 'a.vs1', type: 'vistumbler' }, { name: 'b.vsz', type: 'vistumbler' }]);
    assert.strictEqual(one.type, 'vistumbler');
    assert.match(one.message, /These look like Vistumbler/);
    const mixed = summary([{ name: 'a.vs1', type: 'vistumbler' }, { name: 'w.csv', type: 'wiglewificsv' }]);
    assert.strictEqual(mixed.type, null, 'files of different types leave the choice alone');
    assert.match(mixed.message, /different types \(Vistumbler VS1\/VSZ\/CSV\/MDB, WigleWifi CSV\)/);
    const partly = summary([{ name: 'a.vs1', type: 'vistumbler' }, { name: 'x.jpg', type: null }]);
    assert.strictEqual(partly.type, 'vistumbler');
    assert.match(partly.message, /Could not tell for x\.jpg/);
    assert.match(summary([{ name: 'x.jpg', type: null }]).message, /Could not tell what type this file is/);
});
