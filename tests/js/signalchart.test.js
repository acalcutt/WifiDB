// Run with: node --test tests/js/signalchart.test.js
const test = require('node:test');
const assert = require('node:assert');
const { parseTime, filterByDays, safeHref } = require('../../wifidb/wifidb/lib/js/wifidb/signalchart.js');

const points = [
    ['2024-01-01 10:00:00', 10, -80],
    ['2024-01-02 10:00:00', 20, -70],
    ['2024-01-09 10:00:00', 30, -60],
    ['2024-02-20 10:00:00', 40, null],
];

test('dates are read as UTC', () => {
    assert.strictEqual(parseTime('2024-01-01 10:00:00'), Date.UTC(2024, 0, 1, 10));
    assert.ok(isNaN(parseTime('junk')));
});

test('a range counts back from the newest reading, not from today', () => {
    assert.strictEqual(filterByDays(points, 0).length, 4);
    assert.strictEqual(filterByDays(points, 1).length, 1);
    assert.strictEqual(filterByDays(points, 30).length, 1, 'the newest is Feb 20, so 30 days back is Jan 21');
    assert.strictEqual(filterByDays(points, 60).length, 4);
    assert.strictEqual(filterByDays(points, 365).length, 4);
});

test('odd input is handled', () => {
    assert.strictEqual(filterByDays([], 7).length, 0);
    assert.strictEqual(filterByDays(points, 'x').length, 4, 'junk means all');
    assert.strictEqual(filterByDays([['junk', 1, 1], ['2024-01-01 00:00:00', 2, 2]], 1).length, 2, 'an unreadable date is kept');
});

test('links to scripts are refused', () => {
    assert.strictEqual(safeHref('javascript:alert(1)'), null);
    assert.strictEqual(safeHref(' JavaScript:alert(1)'), null);
    assert.strictEqual(safeHref('data:text/html,x'), null);
    assert.strictEqual(safeHref(''), null);
    assert.strictEqual(safeHref(null), null);
    assert.strictEqual(safeHref('../api/wifi/signal.png?ap=1'), '../api/wifi/signal.png?ap=1');
    assert.strictEqual(safeHref('https://wifidb.example.com/api/wifi/signal.svg?ap=1'), 'https://wifidb.example.com/api/wifi/signal.svg?ap=1');
});
