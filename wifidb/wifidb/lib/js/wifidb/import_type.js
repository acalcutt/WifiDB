/*
 * import_type.js: on the upload page, picks the Import Type the chosen files look like, from their first bytes and
 * then their names (the same rules as lib/import_type.inc.php), and says what it picked. The choice stays the
 * person's: it is only a default, and picking another type afterwards wins.
 *
 * The form needs <input type="file" data-import-type-for="NAME"> (NAME is the radio group, "type") and an element
 * with id="import-type-note" for the message.
 */
(function (root) {
    'use strict';

    var HEAD_BYTES = 4096;
    var LABELS = {
        vistumbler: 'Vistumbler VS1/VSZ/CSV/MDB', ns1: 'NetStumbler NS1', wardrive: 'Wardrive DB/DB3',
        wiglewificsv: 'WigleWifi CSV', swardriving: 'SWardriving CSV', kismet: 'Kismet netxml'
    };
    var EXTENSIONS = {
        vs1: 'vistumbler', vsz: 'vistumbler', vscz: 'vistumbler', txt: 'vistumbler', csv: 'vistumbler', mdb: 'vistumbler',
        ns1: 'ns1', db: 'wardrive', db3: 'wardrive', gz: 'wiglewificsv', wiglecsv: 'wiglewificsv',
        wiglewificsv: 'wiglewificsv', netxml: 'kismet'
    };

    function startsWith(bytes, text, at) {
        at = at || 0;
        if (bytes.length < at + text.length) { return false; }
        for (var i = 0; i < text.length; i++) { if (bytes[at + i] !== text.charCodeAt(i)) { return false; } }
        return true;
    }

    /** the type a file is for (a key of LABELS), from its name and first bytes (a Uint8Array); null when neither says */
    function guess(name, bytes) {
        bytes = bytes || new Uint8Array(0);
        if (startsWith(bytes, 'SQLite format 3\u0000')) { return 'wardrive'; }
        if (startsWith(bytes, 'NetS')) { return 'ns1'; }
        if (bytes[0] === 0x1f && bytes[1] === 0x8b) { return 'wiglewificsv'; }   // only WiGLE's exports come gzipped
        if (startsWith(bytes, 'PK\u0003\u0004')) { return 'vistumbler'; }          // a VSZ
        if (startsWith(bytes, 'Standard Jet DB', 4) || startsWith(bytes, 'Standard ACE DB', 4)) { return 'vistumbler'; }
        var text = '';
        for (var i = 0; i < bytes.length; i++) { text += String.fromCharCode(bytes[i]); }
        text = text.replace(/^\xEF\xBB\xBF/, '').replace(/^\s+/, '');
        var lower = text.toLowerCase();
        if (lower.indexOf('wiglewifi') === 0) { return 'wiglewificsv'; }
        if (lower.indexOf('bssid,ssid,authmode,channel,frequency') === 0) { return 'swardriving'; }
        if (lower.indexOf('ssid,bssid,manufacturer') === 0) { return 'vistumbler'; }   // Vistumbler's detailed CSV
        if ((text.indexOf('<?xml') === 0 || text.indexOf('<detection-run') === 0)
            && (lower.indexOf('detection-run') !== -1 || lower.indexOf('kismet') !== -1)) { return 'kismet'; }
        if (lower.indexOf('# vistumbler') === 0) { return 'vistumbler'; }
        var dot = String(name).lastIndexOf('.');
        var ext = dot === -1 ? '' : String(name).slice(dot + 1).toLowerCase();
        return Object.prototype.hasOwnProperty.call(EXTENSIONS, ext) ? EXTENSIONS[ext] : null;
    }

    /** what to tell the person about [{name, type}]: {type to select or null, message} */
    function summary(found) {
        var types = [];
        found.forEach(function (f) { if (f.type && types.indexOf(f.type) === -1) { types.push(f.type); } });
        var unknown = found.filter(function (f) { return !f.type; }).map(function (f) { return f.name; });
        if (!types.length) {
            return { type: null, message: found.length ? 'Could not tell what type ' + (found.length === 1 ? 'this file is' : 'these files are') + ': pick one below.' : '' };
        }
        if (types.length > 1) {
            return { type: null, message: 'These files look like different types (' + types.map(function (t) { return LABELS[t]; }).join(', ')
                + '). One upload imports them all as one type: upload each type separately, or pick one below.' };
        }
        var message = (found.length === 1 ? 'This looks like ' : 'These look like ') + LABELS[types[0]] + ', so that is selected. Pick another below if it is not.';
        if (unknown.length) { message += ' (Could not tell for ' + unknown.join(', ') + '.)'; }
        return { type: types[0], message: message };
    }

    function readHead(file) {
        var blob = file.slice(0, HEAD_BYTES);
        if (blob.arrayBuffer) { return blob.arrayBuffer().then(function (b) { return new Uint8Array(b); }); }
        return new Promise(function (resolve) {
            var reader = new FileReader();
            reader.onload = function () { resolve(new Uint8Array(reader.result)); };
            reader.onerror = function () { resolve(new Uint8Array(0)); };
            reader.readAsArrayBuffer(blob);
        });
    }

    function init(input) {
        var group = input.getAttribute('data-import-type-for');
        var note = document.getElementById('import-type-note');
        input.addEventListener('change', function () {
            var files = Array.prototype.slice.call(input.files || []);
            Promise.all(files.map(function (f) {
                return readHead(f).then(function (bytes) { return { name: f.name, type: guess(f.name, bytes) }; },
                    function () { return { name: f.name, type: guess(f.name, null) }; });
            })).then(function (found) {
                var result = summary(found);
                if (result.type) {
                    var radio = input.form && input.form.querySelector('input[name="' + group + '"][value="' + result.type + '"]');
                    if (radio) { radio.checked = true; }
                }
                if (note) { note.textContent = result.message; }
            });
        });
    }

    if (typeof document !== 'undefined') {
        var start = function () {
            var inputs = document.querySelectorAll('input[type="file"][data-import-type-for]');
            for (var i = 0; i < inputs.length; i++) { init(inputs[i]); }
        };
        if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', start); } else { start(); }
    }

    if (typeof module !== 'undefined' && module.exports) {
        module.exports = { guess: guess, summary: summary };
    }
})(typeof window !== 'undefined' ? window : globalThis);
