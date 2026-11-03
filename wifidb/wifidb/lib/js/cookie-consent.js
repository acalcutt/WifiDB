/*
cookie-consent.js, the cookie banner. header.tpl loads it only when Site Settings has Google Analytics or AdSense, after
setting Google's consent mode to "denied" for everything, and says in window.WIFIDB_CONSENT.ask whether this visitor is
asked: Site Settings' "Visitors' consent", worked out on the server (lib/cookie_consent.inc.php, by the visitor's country
when it's "where it's required"). A visitor who isn't asked gets analytics and ads as usual; one who is sees the banner,
and nothing uses cookies for analytics or personalized ads until they allow it. A choice is kept in this browser for a
year (vi_cookie_consent), and "Cookie choices" in the footer opens the banner again to change it.
*/
(function () {
    'use strict';
    var COOKIE_NAME = 'vi_cookie_consent';
    var KINDS = ['ad_storage', 'analytics_storage', 'ad_user_data', 'ad_personalization'];
    var settings = window.WIFIDB_CONSENT || { ask: true };

    function every(value) {
        var cons = {};
        KINDS.forEach(function (kind) { cons[kind] = value; });
        return cons;
    }

    function readConsent() {
        var m = document.cookie.match(new RegExp('(?:^|; )' + COOKIE_NAME + '=([^;]*)'));
        if (!m) { return null; }
        try { return JSON.parse(decodeURIComponent(m[1])); } catch (e) { return null; }
    }

    function writeConsent(cons) {
        var expires = new Date();
        expires.setFullYear(expires.getFullYear() + 1);
        document.cookie = COOKIE_NAME + '=' + encodeURIComponent(JSON.stringify(cons)) + '; path=/; expires=' + expires.toUTCString() +
            '; SameSite=Lax' + (location.protocol === 'https:' ? '; Secure' : '');
    }

    function applyConsentState(cons) {
        if (!window.gtag) { return; }
        var update = {};
        KINDS.forEach(function (kind) { if (kind in cons) { update[kind] = cons[kind]; } });
        try {
            window.gtag('consent', 'update', update);
            if (cons.ad_storage === 'denied') { window.gtag('set', 'ads_data_redaction', true); }
        } catch (e) {}
    }

    function setConsentAndClose(cons) {
        writeConsent(cons);
        applyConsentState(cons);
        var el = document.getElementById('vi-cookie-banner');
        if (el) { el.remove(); }
    }

    function showBanner(current) {
        if (document.getElementById('vi-cookie-banner')) { return; }
        var div = document.createElement('div');
        div.id = 'vi-cookie-banner';
        div.style.cssText = 'position:fixed; left:0; right:0; bottom:0; background:#fff; color:#000; border-top:1px solid #ccc; padding:14px; z-index:9999; font-family:Arial,Helvetica,sans-serif;';
        div.innerHTML = '\
            <div style="max-width:1000px;margin:0 auto;display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap;">\
                <div style="flex:1;min-width:260px">\
                    <strong>Cookie choices</strong> — We use cookies for essential site functions, analytics, and advertising. You can accept all, reject non-essential cookies, or choose specific settings.\
                </div>\
                <div style="flex:0 0 auto;">\
                    <button id="vi-consent-accept" style="margin-right:8px;padding:8px 12px;cursor:pointer;">Accept all</button>\
                    <button id="vi-consent-reject" style="margin-right:8px;padding:8px 12px;cursor:pointer;">Reject all</button>\
                    <button id="vi-consent-settings" style="padding:8px 12px;cursor:pointer;">Settings</button>\
                </div>\
            </div>\
            <div id="vi-consent-modal" style="display:none;padding-top:12px;">\
                <label style="display:block;margin-bottom:6px"><input type=checkbox id=vi-toggle-analytics> Allow analytics cookies</label>\
                <label style="display:block;margin-bottom:6px"><input type=checkbox id=vi-toggle-personal> Allow ad personalization</label>\
                <div style="margin-top:8px">\
                    <button id="vi-consent-save" style="margin-right:8px;padding:8px 12px;cursor:pointer;">Save</button>\
                    <button id="vi-consent-cancel" style="padding:8px 12px;cursor:pointer;">Cancel</button>\
                </div>\
            </div>';
        document.body.appendChild(div);

        document.getElementById('vi-consent-accept').addEventListener('click', function () { setConsentAndClose(every('granted')); });
        document.getElementById('vi-consent-reject').addEventListener('click', function () { setConsentAndClose(every('denied')); });
        document.getElementById('vi-consent-settings').addEventListener('click', function () {
            document.getElementById('vi-consent-modal').style.display = 'block';
            document.getElementById('vi-toggle-analytics').checked = (current.analytics_storage === 'granted');
            document.getElementById('vi-toggle-personal').checked = (current.ad_personalization === 'granted');
        });
        document.getElementById('vi-consent-save').addEventListener('click', function () {
            var analytics = document.getElementById('vi-toggle-analytics').checked ? 'granted' : 'denied';
            var ads = document.getElementById('vi-toggle-personal').checked ? 'granted' : 'denied';
            setConsentAndClose({ ad_storage: ads, analytics_storage: analytics, ad_user_data: ads, ad_personalization: ads });
        });
        document.getElementById('vi-consent-cancel').addEventListener('click', function () {
            var el = document.getElementById('vi-cookie-banner');
            if (el) { el.remove(); }
        });
    }

    // A choice made before, or for a visitor who isn't asked everything allowed, applies now, before Google's tags run.
    var chosen = readConsent();
    if (chosen) { applyConsentState(chosen); }
    else if (!settings.ask) { applyConsentState(every('granted')); }

    // "Cookie choices" in the footer
    window.WifiDBCookieChoices = function () {
        showBanner(readConsent() || every(settings.ask ? 'denied' : 'granted'));
    };

    function maybeShow() { if (!chosen && settings.ask) { showBanner(every('denied')); } }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', maybeShow); } else { maybeShow(); }
})();
