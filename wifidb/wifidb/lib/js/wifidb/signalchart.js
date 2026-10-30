/*
 * signalchart.js: the interactive signal history chart on an access point's page (Chart.js, as on the stats page).
 *
 * A chart is a <div class="sigchart"> with
 *   data-points   JSON rows [date, signal, rssi] (date "YYYY-MM-DD HH:MM:SS", UTC; rssi may be null), oldest first
 *   data-ranges   "1" to offer a time range (all, last day, week, month, year); counted back from the newest reading
 *   data-png, data-svg   addresses of the same graph as a picture, for saving or sharing (optional)
 * wifidbInitSignalCharts(container) draws every chart in container that has not been drawn; the page calls it when a
 * file's readings are opened, so a page with many files only draws the ones that are looked at.
 */
(function (root) {
    'use strict';

    /** milliseconds for "YYYY-MM-DD HH:MM:SS" (UTC), or NaN */
    function parseTime(text) {
        return Date.parse(String(text).replace(' ', 'T').slice(0, 19) + 'Z');
    }

    /** the readings in the last `days` days, counted back from the newest reading (all of them when days is 0) */
    function filterByDays(points, days) {
        days = Number(days) || 0;
        if (!days || !points.length) { return points; }
        var newest = NaN;
        for (var i = points.length - 1; i >= 0 && isNaN(newest); i--) { newest = parseTime(points[i][0]); }
        if (isNaN(newest)) { return points; }
        var cutoff = newest - days * 86400000;
        return points.filter(function (p) { var t = parseTime(p[0]); return isNaN(t) || t >= cutoff; });
    }

    function safeHref(url) {
        return typeof url === 'string' && url !== '' && !/^\s*(javascript|data|vbscript):/i.test(url) ? url : null;
    }

    function element(tag, text, attrs) {
        var e = document.createElement(tag);
        if (text) { e.textContent = text; }
        if (attrs) { Object.keys(attrs).forEach(function (k) { e.setAttribute(k, attrs[k]); }); }
        return e;
    }

    function init(box) {
        if (box.getAttribute('data-ready')) { return; }
        box.setAttribute('data-ready', '1');
        var points;
        try { points = JSON.parse(box.getAttribute('data-points') || '[]'); } catch (e) { points = []; }
        if (!Array.isArray(points) || !points.length) {
            box.appendChild(element('p', 'There are no readings to chart.'));
            return;
        }
        if (!root.Chart) {
            box.appendChild(element('p', 'The chart library did not load.'));
            return;
        }

        var bar = element('div', '', { 'class': 'sigchart-bar', style: 'margin: 4px 0; font-size: 12px;' });
        var rangeSelect = null;
        if (box.getAttribute('data-ranges') === '1') {
            bar.appendChild(document.createTextNode('Show: '));
            rangeSelect = element('select');
            [['0', 'all readings'], ['1', 'last 24 hours'], ['7', 'last 7 days'], ['30', 'last 30 days'], ['365', 'last year']].forEach(function (o) {
                rangeSelect.appendChild(element('option', o[1], { value: o[0] }));
            });
            bar.appendChild(rangeSelect);
            bar.appendChild(document.createTextNode(' '));
        }
        var rssiLabel = element('label');
        var rssiBox = element('input', '', { type: 'checkbox' });
        rssiBox.checked = true;
        rssiLabel.appendChild(rssiBox);
        rssiLabel.appendChild(document.createTextNode(' RSSI (dBm)'));
        bar.appendChild(rssiLabel);
        [['data-png', 'PNG'], ['data-svg', 'SVG']].forEach(function (l) {
            var href = safeHref(box.getAttribute(l[0]));
            if (href) {
                bar.appendChild(document.createTextNode(' · '));
                bar.appendChild(element('a', l[1], { href: href, title: 'This graph as a ' + l[1] + ' picture, to save or share' }));
            }
        });
        var count = element('span', '', { style: 'margin-left: 8px; color: #666;' });
        bar.appendChild(count);
        box.appendChild(bar);

        var holder = element('div', '', { style: 'position: relative; width: 100%; height: ' + (box.getAttribute('data-height') || '260') + 'px;' });
        var canvas = element('canvas');
        holder.appendChild(canvas);
        box.appendChild(holder);

        var chart = new root.Chart(canvas.getContext('2d'), {
            type: 'line',
            data: {
                labels: [],
                datasets: [
                    { label: 'Signal %', data: [], yAxisID: 'y', borderColor: 'rgba(0,153,102,1)', backgroundColor: 'rgba(0,153,102,0.15)', fill: true, tension: 0.1, borderWidth: 1.5 },
                    { label: 'RSSI dBm', data: [], yAxisID: 'y1', borderColor: 'rgba(204,102,0,1)', backgroundColor: 'rgba(204,102,0,0)', fill: false, tension: 0.1, borderWidth: 1.5, spanGaps: true }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                animation: false,
                interaction: { mode: 'index', intersect: false },
                plugins: { legend: { display: true } },
                scales: {
                    x: { ticks: { maxTicksLimit: 8, autoSkip: true, maxRotation: 0 } },
                    y: { min: 0, max: 100, position: 'left', title: { display: true, text: 'Signal %' } },
                    y1: { position: 'right', grid: { drawOnChartArea: false }, title: { display: true, text: 'RSSI dBm' } }
                }
            }
        });

        function draw() {
            var shown = filterByDays(points, rangeSelect ? rangeSelect.value : 0);
            var dots = shown.length <= 150 ? 2 : 0;
            chart.data.labels = shown.map(function (p) { return p[0]; });
            chart.data.datasets[0].data = shown.map(function (p) { return p[1]; });
            chart.data.datasets[1].data = shown.map(function (p) { return p[2]; });
            chart.data.datasets[0].pointRadius = dots;
            chart.data.datasets[1].pointRadius = dots;
            chart.data.datasets[1].hidden = !rssiBox.checked;
            chart.options.scales.y1.display = rssiBox.checked;
            chart.update();
            count.textContent = shown.length + ' of ' + points.length + ' readings';
        }
        if (rangeSelect) { rangeSelect.addEventListener('change', draw); }
        rssiBox.addEventListener('change', draw);
        draw();
    }

    root.wifidbInitSignalCharts = function (container) {
        var boxes = (container || document).querySelectorAll('.sigchart:not([data-ready])');
        for (var i = 0; i < boxes.length; i++) { init(boxes[i]); }
    };

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { parseTime: parseTime, filterByDays: filterByDays, safeHref: safeHref };
    }
})(typeof window !== 'undefined' ? window : globalThis);
