/*
swarm-control.js, deciding whether to read from the swarm and letting a visitor say otherwise
Copyright (C) 2026 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/

/**
 * The swarm's on/off decision, its toggle, and its readout.
 *
 * Separate from swarm.js because the two answer different questions and have
 * very different costs. swarm.js is the transport and pulls in WebTorrent --
 * 220 KB that must not be fetched by a page that is never going to join
 * anything. This file is a few hundred bytes of policy, is always loaded when
 * the feature is configured, and reaches for swarm.js through a dynamic import
 * only once something has actually decided to turn the swarm on. A visitor who
 * leaves it off therefore pays for this file and nothing else.
 *
 * ── The default rule ─────────────────────────────────────────────────────────
 *
 * The obvious rule is "on when the visitor is on wifi", and the browser will
 * not answer that question. `navigator.connection.type` is the only API that
 * names the medium, it is Chromium-only, it is on its way out, and Firefox and
 * Safari have never had it -- so a rule written around it decides nothing at
 * all for a large share of visitors.
 *
 * What is asked instead is the inverse: is there a reason NOT to. In order:
 *
 *   1. `saveData` -- someone has switched Data Saver on. That is a deliberate
 *      statement about this device and it outranks anything inferred.
 *   2. `type === 'cellular'` -- believed where it exists, which is Chromium.
 *   3. `effectiveType` of `2g`/`slow-2g` -- no medium, but a link this slow is
 *      not one to open a dozen torrent swarms over whatever is carrying it.
 *      Present in Chromium alongside the above, and the useful half of the API.
 *   4. `type` of `wifi`/`ethernet` -- a positive signal rather than an absence,
 *      recorded for the readout so the toggle can say why it turned itself on.
 *
 * Everything left over is `unknown`, and unknown enables the swarm. That is the
 * load-bearing choice in this file and it deserves saying plainly: every
 * Firefox and Safari visitor lands here, so treating unknown as metered would
 * mean auto mode never once turns on outside Chromium, which is indistinguish-
 * able from the feature not existing. The cost of being wrong is a mobile
 * Firefox visitor pulling tiles over cellular until they press the button --
 * bounded, visible in the readout, and remembered once they do.
 *
 * ── Precedence ───────────────────────────────────────────────────────────────
 *
 *   URL  ?swarm=1 / ?swarm=0   → stored choice → auto-detect → server default
 *
 * The URL is first because comparing `&swarm=1` against `&swarm=0` on the same
 * page is how every claim about this feature gets checked; a stored choice from
 * a previous visit silently winning that comparison would make the check lie.
 * A URL override deliberately does NOT persist, for the same reason.
 *
 * ── Detection is not a one-time decision ─────────────────────────────────────
 *
 * `navigator.connection` fires `change` when the link does, so a visitor who
 * walks into wifi mid-session gets the swarm switched on then rather than on
 * their next page load. That only applies while the decision is still auto's to
 * make: an explicit choice, from the URL or from the button, ends it.
 */

/**
 * Where an explicit choice is remembered. Deliberately not a cookie: it is a
 * client-side display preference, it never needs to reach the server, and the
 * server has its own default in `tile_swarm_browser` already.
 */
const STORAGE_KEY = 'wifidb.swarm.enabled';

/** How often the readout is refreshed while the swarm is on. */
const POLL_MS = 2000;

/**
 * Reads `?swarm=` out of a query string.
 *
 * Matches map.php: anything other than `0` is on, an empty value is not a
 * choice at all. Kept identical because both sides read the same parameter and
 * a page that turned the swarm on server-side while this turned it off would be
 * an unusually confusing thing to debug.
 *
 * @param {string} search A `location.search`-style string.
 * @returns {boolean|null} The forced state, or null if not specified.
 */
export function urlChoice(search) {
  let value;
  try {
    value = new URLSearchParams(search || '').get('swarm');
  } catch {
    return null;
  }
  if (value === null) return null;
  const trimmed = value.trim();
  if (trimmed === '') return null;
  return trimmed !== '0';
}

/**
 * The remembered choice, or null if there is none.
 *
 * Every access is wrapped: localStorage throws rather than returning null when
 * a browser has storage switched off for the site, and a map that fails to load
 * because someone blocked cookies would be a poor trade for a toggle.
 *
 * @returns {boolean|null} What was chosen last, or null.
 */
export function storedChoice() {
  try {
    const value = window.localStorage.getItem(STORAGE_KEY);
    if (value === '1') return true;
    if (value === '0') return false;
  } catch {
    // Storage unavailable. No stored choice, which is a true statement.
  }
  return null;
}

/**
 * Remembers a choice, or forgets it when passed null.
 * @param {boolean|null} value What to remember.
 */
export function rememberChoice(value) {
  try {
    if (value === null) window.localStorage.removeItem(STORAGE_KEY);
    else window.localStorage.setItem(STORAGE_KEY, value ? '1' : '0');
  } catch {
    // Storage unavailable. The choice still applies to this page view; it just
    // does not survive it.
  }
}

/**
 * What can be told about this connection, and why.
 *
 * @returns {{verdict: string, reason: string}} `verdict` is one of
 *   `unsupported`, `metered`, `capable`, `unknown`; `reason` is the phrase the
 *   readout shows.
 */
export function detectConnection() {
  if (typeof window.RTCPeerConnection !== 'function') {
    // No WebRTC, so there is no transport to a browser peer at all. Not a
    // preference -- there is nothing here to turn on.
    return { verdict: 'unsupported', reason: 'this browser has no WebRTC' };
  }

  const conn =
    navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  if (!conn) {
    return { verdict: 'unknown', reason: 'connection type not reported' };
  }

  if (conn.saveData === true) {
    return { verdict: 'metered', reason: 'Data Saver is on' };
  }
  if (conn.type === 'cellular') {
    return { verdict: 'metered', reason: 'on a cellular connection' };
  }
  if (conn.effectiveType === '2g' || conn.effectiveType === 'slow-2g') {
    return { verdict: 'metered', reason: `slow connection (${conn.effectiveType})` };
  }
  if (conn.type === 'wifi' || conn.type === 'ethernet') {
    return { verdict: 'capable', reason: `on ${conn.type}` };
  }
  return { verdict: 'unknown', reason: 'connection type not reported' };
}

/**
 * Resolves the initial state, applying the precedence described at the top.
 *
 * @param {string} mode The server's `tile_swarm_browser` mode: `on`, `auto` or
 *   `manual`.
 * @param {string} search A `location.search`-style string.
 * @returns {{enabled: boolean, decidedBy: string, reason: string}} `decidedBy`
 *   is `url`, `stored`, `auto` or `config`, and is what says whether the
 *   decision is still auto's to revisit on a network change.
 */
export function resolvePreference(mode, search) {
  const forced = urlChoice(search);
  if (forced !== null) {
    return {
      enabled: forced,
      decidedBy: 'url',
      reason: `?swarm=${forced ? '1' : '0'}`,
    };
  }

  const stored = storedChoice();
  if (stored !== null) {
    return { enabled: stored, decidedBy: 'stored', reason: 'your saved choice' };
  }

  // A browser that cannot do WebRTC is off whatever the mode says, since `on`
  // is a statement about this site's policy and not about the browser.
  const detected = detectConnection();
  if (detected.verdict === 'unsupported') {
    return { enabled: false, decidedBy: 'auto', reason: detected.reason };
  }

  if (mode === 'auto') {
    return {
      enabled: detected.verdict !== 'metered',
      decidedBy: 'auto',
      reason: detected.reason,
    };
  }
  return {
    enabled: mode === 'on',
    decidedBy: 'config',
    reason: mode === 'on' ? 'on by default' : 'off unless you turn it on',
  };
}

/**
 * Bytes as something short enough to sit in a control bar.
 * @param {number} bytes Byte count.
 * @returns {string} e.g. `4.2 MB`.
 */
function formatBytes(bytes) {
  if (!bytes) return '0 B';
  const units = ['B', 'KB', 'MB', 'GB'];
  let value = bytes;
  let unit = 0;
  while (value >= 1024 && unit < units.length - 1) {
    value /= 1024;
    unit++;
  }
  return `${value < 10 && unit > 0 ? value.toFixed(1) : Math.round(value)} ${units[unit]}`;
}

/**
 * Wires the toggle up to the swarm and keeps the readout current.
 *
 * Returns a promise the caller can gate its layers on, which resolves as soon
 * as the initial batch has had its chance -- immediately when the swarm starts
 * off. Turning it on later never blocks anything: by then the layers are in,
 * and an archive registers against a protocol the map is already reading
 * through, so it starts serving on the next tile request.
 *
 * @param {object} options Options.
 * @param {object} options.protocol The pmtiles Protocol the style reads through.
 * @param {Array<object>} options.archives `{bucket, infohash, tilejson}` per archive.
 * @param {string} options.mode `on`, `auto` or `manual`, from tile_swarm_browser.
 * @param {HTMLElement} [options.button] The toggle. Optional: the policy still
 *   applies without one, there is just no way to override it from the page.
 * @param {HTMLElement} [options.status] Where the readout goes.
 * @param {Function} [options.discover] Called each time the swarm is switched
 *   on, and awaited, to find archives the server did not list -- the style's
 *   own torrent-capable sources, which cannot be read until MapLibre has loaded
 *   the style. Returns `{bucket, tilejson, torrent, magnet}` per source. It runs
 *   only while the swarm is on, which is what puts these sources under the same
 *   switch as everything else: turning it off destroys the handle and takes
 *   them with it, and turning it back on finds them again.
 * @param {number} [options.metadataTimeoutMs] Passed through to enableSwarm.
 * @param {number} [options.batchTimeoutMs] Passed through to enableSwarm.
 * @returns {Promise<object|null>} The swarm handle, or null if it started off.
 */
export function attachSwarmControl({
  protocol,
  archives,
  discover,
  mode,
  button,
  status,
  metadataTimeoutMs,
  batchTimeoutMs,
}) {
  const decision = resolvePreference(mode, window.location.search);
  const supported = detectConnection().verdict !== 'unsupported';

  let handle = null;
  let busy = false;
  let poll = null;
  // Cleared the moment anything explicit happens, which is what stops a network
  // change from overriding a choice somebody made on purpose.
  let autoManaged = decision.decidedBy === 'auto';
  let note = decision.reason;

  function render() {
    if (button) {
      button.textContent = handle ? 'Swarm: On' : 'Swarm: Off';
      button.disabled = busy || !supported;
      button.title = supported
        ? 'Read map archives from other browsers over BitTorrent instead of ' +
          'fetching every tile from wifidb.net. Applies to tiles loaded from ' +
          'here on, so pan or zoom to see the effect.'
        : 'Unavailable: this browser has no WebRTC.';
    }
    if (!status) return;

    if (busy) {
      status.textContent = 'joining…';
      return;
    }
    if (!handle) {
      status.textContent = note ? `off — ${note}` : 'off';
      return;
    }

    let s;
    try {
      s = handle.stats();
    } catch {
      // The client went away underneath us. Says so rather than throwing once
      // every POLL_MS from a timer nobody is watching.
      status.textContent = 'on — no readout';
      return;
    }

    const parts = [`${s.joined}/${s.offered} archives`];
    // Seeds, peers and web seeds apart, because they answer different
    // questions. A web seed is the generating node over HTTP wearing a wire, so
    // it says nothing about the swarm; a seed or a peer is another browser
    // that answered. All three are shown even at zero -- "0 peers" is the
    // finding, and hiding it leaves the line looking like it was never asked.
    parts.push(`${s.seeds} seed${s.seeds === 1 ? '' : 's'}`);
    parts.push(`${s.peers} peer${s.peers === 1 ? '' : 's'}`);
    if (s.webSeeds) parts.push(`${s.webSeeds} web`);
    // Always rendered, for the same reason, and because a figure that is
    // structurally absent is indistinguishable from a readout that has stopped
    // refreshing -- which is exactly how the missing client.downloaded read.
    parts.push(
      s.downloadSpeed
        ? `${formatBytes(s.downloaded)} in (${formatBytes(s.downloadSpeed)}/s)`
        : `${formatBytes(s.downloaded)} in`,
    );
    if (s.uploaded) parts.push(`${formatBytes(s.uploaded)} out`);
    status.textContent = parts.join(' · ');
  }

  function startPolling() {
    if (poll !== null) return;
    poll = window.setInterval(render, POLL_MS);
  }

  function stopPolling() {
    if (poll === null) return;
    window.clearInterval(poll);
    poll = null;
  }

  async function enable() {
    if (handle || busy || !supported) return handle;
    busy = true;
    render();
    try {
      // Here and nowhere else is WebTorrent pulled in.
      const { enableSwarm } = await import('wifidb-swarm');
      handle = await enableSwarm({
        protocol,
        archives,
        metadataTimeoutMs,
        batchTimeoutMs,
      });
      if (!handle) {
        // Nothing to join, or no WebTorrent client to join it with. Distinct
        // from every archive failing, which does return a handle -- and the
        // readout has to say something other than the connection reason it was
        // showing before, which would now read as if it had never been asked.
        note = 'nothing joinable';
      }
    } catch (error) {
      note = `could not start (${error.message})`;
      handle = null;
    }
    window.wifidbSwarm = handle;
    busy = false;
    if (handle) startPolling();
    render();

    // After the handle is published and the caller released, never before.
    // Whatever this finds has to wait for the map's style, and the initial
    // batch must not: it is what the page gates its layers on. So these join
    // in the background and start serving from the next tile request.
    if (handle && typeof discover === 'function') {
      Promise.resolve()
        .then(() => discover())
        .then((found) => (found?.length ? handle.join(found) : 0))
        .then((added) => {
          if (added > 0) render();
        })
        .catch(() => {
          // A style that never loaded, or one with nothing in it. The swarm is
          // already running on the server's own list and this was only ever an
          // addition to it.
        });
    }
    return handle;
  }

  function disable() {
    stopPolling();
    if (handle) {
      handle.destroy();
      handle = null;
    }
    window.wifidbSwarm = null;
    render();
  }

  if (button) {
    button.addEventListener('click', () => {
      if (busy || !supported) return;
      const wanted = !handle;
      // An explicit choice, so it is remembered and auto stops second-guessing
      // it for the rest of the session.
      autoManaged = false;
      rememberChoice(wanted);
      note = 'your saved choice';
      if (wanted) enable();
      else disable();
    });
  }

  // A visitor who walks into wifi should get the swarm without reloading, and
  // one who walks out of it should stop paying for it. Only while nobody has
  // said otherwise.
  const conn =
    navigator.connection || navigator.mozConnection || navigator.webkitConnection;
  if (conn && typeof conn.addEventListener === 'function' && mode === 'auto') {
    conn.addEventListener('change', () => {
      if (!autoManaged) return;
      const now = detectConnection();
      note = now.reason;
      const wanted = now.verdict !== 'metered' && now.verdict !== 'unsupported';
      if (wanted && !handle) enable();
      else if (!wanted && handle) disable();
      else render();
    });
  }

  render();
  return decision.enabled ? enable() : Promise.resolve(null);
}
