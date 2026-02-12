/* Lightweight polling client for scheduling pages
   Usage: include a small inline script that sets `WIFIDB_BASE_URL` then call
   `schedulingLiveInit({func:'waiting', interval:15000})` on DOMContentLoaded.
*/
(function(){
    if(window.schedulingLiveInit) return; // already loaded

    function safeHtmlReplace(html){
        try{
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            // Prefer the `div.center` inside `.main` (page content). Do NOT match other elements like <p class="center"> (top-menu).
            var newMain = doc.querySelector('.main');
            var newCenter = null;
            if (newMain) {
                newCenter = newMain.querySelector('div.center');
            }
            if (!newCenter) {
                newCenter = doc.querySelector('div.center');
            }
            if(!newCenter) return false;

            // Remove any Schedule Display Settings tables from the incoming fragment
            // so we don't duplicate the controls that were intentionally moved outside
            try {
                var tables = newCenter.querySelectorAll('table.content_table');
                for (var ti = 0; ti < tables.length; ti++) {
                    var t = tables[ti];
                    if ((t.textContent || '').indexOf('Schedule Display Settings') !== -1) {
                        t.parentNode.removeChild(t);
                    }
                }
            } catch (e) {}

            var oldMain = document.querySelector('.main');
            var oldCenter = null;
            if (oldMain) {
                oldCenter = oldMain.querySelector('div.center');
            }
            if (oldCenter) {
                oldCenter.parentNode.replaceChild(newCenter, oldCenter);
            } else {
                // If no existing div.center found in .main, try replacing any top-level div.center
                var fallback = document.querySelector('div.center');
                if (fallback) {
                    fallback.parentNode.replaceChild(newCenter, fallback);
                } else {
                    // As last resort append to body
                    document.body.appendChild(newCenter);
                }
            }
            return true;
        }catch(e){
            return false;
        }
    }

    function fetchData(base, func){
        var url = (base || '') + 'opt/scheduling_api.php?func=' + encodeURIComponent(func);
        return fetch(url, {credentials:'same-origin'}).then(function(r){ return r.json(); });
    }

    window.schedulingLiveInit = function(opts){
        opts = opts || {};
        var func = opts.func || 'waiting';
        // opts.interval is expected in milliseconds when provided by templates.
        var interval = (typeof opts.interval === 'number') ? opts.interval : 15000;
        var base = (typeof WIFIDB_BASE_URL !== 'undefined') ? WIFIDB_BASE_URL : '';
        var stopped = false;

        function readCookie(name) {
            var dc = document.cookie;
            var prefix = name + "=";
            var begin = dc.indexOf("; " + prefix);
            if (begin == -1) {
                begin = dc.indexOf(prefix);
                if (begin != 0) return null;
            } else {
                begin += 2;
            }
            var end = document.cookie.indexOf(";", begin);
            if (end == -1) {
                end = dc.length;
            }
            return decodeURIComponent(dc.substring(begin + prefix.length, end));
        }

        function tick(){
            if(stopped) return;
            fetchData(base, func).then(function(data){
                if(data && data.ok){
                    if(data.center_html){
                        safeHtmlReplace(data.center_html);
                    }
                    if(data.counts){
                        try{
                            var c = data.counts || {};
                            var baseHref = base || '';
                            function setCountFor(hrefSuffix, count){
                                try{
                                    var full = baseHref + hrefSuffix;
                                    var el = document.querySelector('a[href="'+full+'"]');
                                    if(!el) {
                                        // try contains match (older templates may have different formatting)
                                        var els = document.querySelectorAll('a[href*="opt/scheduling.php"]');
                                        for(var i=0;i<els.length;i++){
                                            var text = els[i].textContent || els[i].innerText || '';
                                            if(text.indexOf(hrefSuffix.split('?')[1] || hrefSuffix) !== -1 || text.indexOf('Files') !== -1) {
                                                el = els[i]; break;
                                            }
                                        }
                                    }
                                    if(el){
                                        // replace the last parentheses number
                                        el.textContent = el.textContent.replace(/\(\s*\d+\s*\)\s*$/,'('+ (typeof count === 'number' ? count : '') +')');
                                    }
                                }catch(e){}
                            }

                            // Files Importing -> base + 'opt/scheduling.php'
                            setCountFor('opt/scheduling.php', c.importing_count || 0);
                            // Files Waiting -> ?func=waiting
                            setCountFor('opt/scheduling.php?func=waiting', c.waiting_count || 0);
                            // Files Completed -> ?func=done
                            setCountFor('opt/scheduling.php?func=done', c.complete_count || 0);
                            // Files Bad -> ?func=bad
                            setCountFor('opt/scheduling.php?func=bad', c.bad_count || 0);
                        }catch(e){}
                    }
                }
            }).catch(function(){}).finally(function(){
                if(!stopped) {
                    // Recalculate interval each tick from cookie so UI changes take effect immediately.
                    try {
                        var cookieVal = readCookie('wifidb_refresh');
                        var intervalMs = interval;
                        if (cookieVal !== null) {
                            var parsed = parseInt(cookieVal, 10);
                            if (!isNaN(parsed) && parsed > 0) {
                                intervalMs = parsed * 1000; // cookie stores seconds
                            }
                        }
                        window.setTimeout(tick, intervalMs);
                    } catch (e) {
                        window.setTimeout(tick, interval);
                    }
                }
            });
        }

        tick();

        return {
            stop: function(){ stopped = true; }
        };
    };
})();
