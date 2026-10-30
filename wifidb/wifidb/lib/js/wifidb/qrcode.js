/* makeQRCode(element, text, {width, height}) draws a QR code as an inline SVG, using the vendored qrcode-generator
 * (lib/js/qrcode-generator/qrcode.js, which must be loaded first). Nothing is loaded from another site, and the text
 * only goes into the code's modules, never into the page's HTML.
 */
(function (root) {
	'use strict';

	var QUIET = 4;   // modules of empty border the QR standard asks for

	/** One path covering every dark module: "M x y h1 v1 h-1 z" for each. */
	function modulePath(qr) {
		var count = qr.getModuleCount();
		var path = '';
		for (var row = 0; row < count; row++) {
			for (var col = 0; col < count; col++) {
				if (qr.isDark(row, col)) { path += 'M' + (col + QUIET) + ' ' + (row + QUIET) + 'h1v1h-1z'; }
			}
		}
		return { path: path, size: count + 2 * QUIET };
	}

	function failure(container, doc) {
		container.textContent = '';
		var err = doc.createElement('div');
		err.className = 'wifidb-qr-error';
		err.textContent = 'QR generation unavailable';
		container.appendChild(err);
	}

	function makeQRCode(el, text, opts) {
		var doc = root.document;
		var container = (typeof el === 'string') ? doc.getElementById(el) : el;
		if (!container) { return; }
		try {
			var qr = root.qrcode(0, 'M');   // type 0: the smallest that fits
			qr.addData(String(text));
			qr.make();
			var m = modulePath(qr);
			var width = (opts && (opts.width || opts.height)) || 128;
			var height = (opts && opts.height) || width;
			var ns = 'http://www.w3.org/2000/svg';
			var svg = doc.createElementNS(ns, 'svg');
			svg.setAttribute('xmlns', ns);
			svg.setAttribute('viewBox', '0 0 ' + m.size + ' ' + m.size);
			svg.setAttribute('width', width);
			svg.setAttribute('height', height);
			svg.setAttribute('role', 'img');
			svg.setAttribute('aria-label', 'QR code');
			svg.setAttribute('shape-rendering', 'crispEdges');
			var background = doc.createElementNS(ns, 'rect');
			background.setAttribute('width', m.size);
			background.setAttribute('height', m.size);
			background.setAttribute('fill', '#fff');
			var modules = doc.createElementNS(ns, 'path');
			modules.setAttribute('d', m.path);
			modules.setAttribute('fill', '#000');
			svg.appendChild(background);
			svg.appendChild(modules);
			container.textContent = '';
			container.appendChild(svg);
		} catch (e) {
			failure(container, doc);
		}
	}

	if (typeof module !== 'undefined' && module.exports) { module.exports = { modulePath: modulePath, QUIET: QUIET }; }
	if (root && root.document) { root.makeQRCode = makeQRCode; }
})(typeof window !== 'undefined' ? window : this);
