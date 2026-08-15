/*
swarm.js, read bucket archives out of the pmtiles-swarm BitTorrent swarm
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

import { PMTiles } from 'pmtiles';

// Imported inside enableSwarm rather than here. A static import is evaluated
// as part of the map's own module graph, so anything WebTorrent objects to on
// load -- a platform API it expects and does not find, a browser that refuses
// it -- would take down the whole map script rather than just this feature.
// Loaded through a function, the same failure is caught and the map carries on
// over HTTP.

/**
 * Registers torrent-backed sources on a PMTiles protocol, so the map reads its
 * bucket archives from the swarm instead of from wifidb.net.
 *
 * ── Why this cannot use the magnet already in the style ──────────────────────
 *
 * Every archived bucket's source URL already carries a magnet in its fragment
 * (see mvt_archive_pmtiles_url in lib/mvt.inc.php). That one is a BEP 46
 * *mutable* magnet: it names a public key and a salt, and resolves through the
 * DHT to whichever build is current. Exactly the right handle to hold across
 * rebuilds -- and useless here, because WebTorrent implements neither BEP 46
 * nor a DHT at all. A browser has no UDP, so there is nothing for it to ask.
 *
 * What a browser can join is a swarm named by infohash, over WebRTC, through a
 * websocket tracker. pmtiles-swarm publishes exactly that in the `torrent`
 * block of the TileJSON it serves per category, so this fetches that document
 * and uses the plain `xt=urn:btih:` magnet out of it. The mutable magnet in the
 * fragment stays where it is: it costs nothing, it is what a client with a real
 * DHT would use, and it is the fallback if this endpoint ever disappears.
 *
 * ── Why nothing is registered until metadata has arrived ─────────────────────
 *
 * Protocol.add() is not a hint, it is a commitment: once a source is registered
 * under a URL, every read of that archive goes through it and there is no path
 * back to HTTP. A torrent whose metadata never arrives -- no seed online, no
 * tracker reachable, wss:// blocked -- would therefore take its bucket's tiles
 * down rather than merely fail to accelerate them.
 *
 * So each archive is joined first and registered only once its metadata is in
 * hand, and the whole batch runs against a deadline. Whatever is ready by then
 * is served from the swarm; everything else is left unregistered and loads over
 * HTTP exactly as it did before, which is also what happens when this module is
 * never loaded at all. Stragglers are destroyed rather than left running, since
 * an archive that missed the deadline can no longer be registered and would be
 * pulling pieces nobody will read.
 */

/** How long to wait for a category's TileJSON before giving up on it. */
const TILEJSON_TIMEOUT_MS = 5000;
/**
 * How long to wait for a torrent's metainfo before giving up on it.
 *
 * Generous, because a browser has the slowest possible route to it: connect a
 * WebSocket to a tracker, announce, wait for a peer list, complete a WebRTC
 * offer/answer with a seeder, and only then receive the metainfo over BEP 9.
 * Eight seconds looked reasonable and was not -- every archive timed out on a
 * working swarm, which reads as "the swarm is broken" rather than "the swarm
 * was not finished".
 *
 * Costs nothing when it is wrong: the map has already drawn by then, and an
 * archive still arriving is simply one that has not started serving yet.
 */
const METADATA_TIMEOUT_MS = 30000;
/**
 * How long the map waits before drawing, whatever the swarm is still doing.
 *
 * Shorter than the metadata timeout on purpose, and no longer a deadline for
 * the archives themselves: one that arrives after this is registered anyway
 * and starts serving from that point, because transformRequest is consulted
 * on every tile request rather than once when a source is resolved. So this
 * bounds only how long a person stares at an empty map.
 */
const BATCH_TIMEOUT_MS = 8000;
/**
 * Pieces held in memory per archive. PMTiles clusters tiles in Hilbert order,
 * so a piece fetched for one tile usually holds its neighbours -- which makes
 * this a prefetch budget as much as a cache.
 */
const CACHE_PIECES = 24;
/**
 * Connections per web seed. The swarm's magnets carry a BEP 19 url-list
 * pointing at the generating node, which is faster and far more available than
 * a handful of browsers; WebTorrent's default of 4 under-uses it.
 */
const MAX_WEB_CONNS = 8;

/**
 * Fetches a category's TileJSON and returns its `torrent` block.
 *
 * A miss here is unremarkable -- the swarm may be down, the category may not
 * have been published yet, CORS may not be configured -- and costs nothing but
 * the archive it describes, so it resolves null rather than throwing.
 *
 * @param {string} url TileJSON URL for one pmtiles-swarm category.
 * @returns {Promise<object|null>} The torrent block, or null.
 */
async function fetchTorrentBlock(url) {
  try {
    const response = await fetch(url, {
      signal: AbortSignal.timeout(TILEJSON_TIMEOUT_MS),
      // The document names the current build, so a cached copy can point at an
      // archive that has already been retired from the swarm.
      cache: 'no-cache',
    });
    if (!response.ok) {
      return null;
    }
    const doc = await response.json();
    return doc && typeof doc.torrent === 'object' ? doc.torrent : null;
  } catch {
    return null;
  }
}

/**
 * Whether a magnet is one a browser can actually act on.
 *
 * Two conditions, and both are load-bearing. `xt=urn:btih:` names a swarm by
 * infohash; `xs=urn:btpk:` names a mutable record instead, which needs a DHT
 * to resolve and so is inert here -- see the note at the top. And at least one
 * `wss://` tracker, because WebRTC is the only transport a browser has: an
 * archive announced only to `udp://` trackers has a perfectly healthy swarm
 * that this page cannot see a single peer of.
 *
 * Parsed rather than pattern-matched, because the tracker URLs arrive
 * percent-encoded and a substring test for `wss` passes on things that are not
 * trackers at all.
 *
 * @param {unknown} magnet Candidate magnet URI.
 * @returns {boolean} Whether it is usable here.
 */
function isJoinable(magnet) {
  if (typeof magnet !== 'string' || !magnet.startsWith('magnet:?')) {
    return false;
  }
  const params = new URLSearchParams(magnet.slice('magnet:?'.length));
  return (
    params.getAll('xt').some((value) => value.startsWith('urn:btih:')) &&
    params.getAll('tr').some((value) => value.startsWith('wss://'))
  );
}

/**
 * Rejects once the given number of milliseconds has passed.
 * @param {number} ms Delay.
 * @param {string} what Named in the rejection, for the log line.
 * @returns {Promise<never>} A promise that only ever rejects.
 */
function expire(ms, what) {
  return new Promise((_resolve, reject) => {
    setTimeout(() => reject(new Error(`${what} timed out after ${ms}ms`)), ms);
  });
}

/**
 * Destroys an engine without letting the failure become the caller's problem.
 * @param {object} engine The engine to release.
 * @returns {Promise<void>} Always resolves.
 */
async function release(engine) {
  try {
    await engine.destroy();
  } catch {
    // Already gone, or the client went first. Either way there is nothing left
    // to release and nothing useful to say about it.
  }
}

/**
 * Joins one archive's swarm and builds a source for it.
 *
 * Deliberately stops short of registering: whether the result is still wanted
 * depends on the batch deadline, which is not this function's business.
 *
 * @param {object} archive The `{bucket, key, tilejson}` triple from PHP.
 * @param {object} client Shared WebTorrent client.
 * @param {object} lib The lazily-imported `{TorrentSource, WebTorrentEngine}`.
 * @param {Function} log Where to report what happened.
 * @returns {Promise<object|null>} An unregistered source, or null.
 */
async function joinArchive(archive, client, lib, log, metadataTimeoutMs) {
  const block = await fetchTorrentBlock(archive.tilejson);
  if (!block) {
    log(`${archive.bucket}: no TileJSON from the swarm, staying on HTTP`);
    return null;
  }
  if (!isJoinable(block.magnet)) {
    log(`${archive.bucket}: magnet is not joinable from a browser, staying on HTTP`);
    return null;
  }

  const engine = new lib.WebTorrentEngine(block.magnet, {
    client,
    // Deliberately no `resumePath`: it is the only part of this engine that
    // touches node:fs, and leaving it unset is what lets the package run
    // unmodified in a browser.
    maxWebConns: MAX_WEB_CONNS,
    readyTimeoutMs: METADATA_TIMEOUT_MS,
  });

  let info;
  try {
    info = await Promise.race([
      engine.ready(),
      expire(metadataTimeoutMs, `${archive.bucket} metadata`),
    ]);
  } catch (error) {
    log(`${archive.bucket}: ${error.message}, staying on HTTP`);
    await release(engine);
    return null;
  }

  const source = new lib.TorrentSource(engine, {
    // The infohash, which is what the archive is: no longer a copy of the
    // style's source URL that had to match it character for character. That
    // string was the fragile part -- a fragment out of step, or an escape
    // spelled differently, and the protocol quietly built an HTTP source
    // instead, which reads correctly and looks exactly like success.
    key: info.infoHash,
    cachePieces: CACHE_PIECES,
  });

  log(`${archive.bucket}: joined ${info.infoHash.slice(0, 12)} (${info.numPieces} pieces)`);
  return { bucket: archive.bucket, engine, source, infoHash: info.infoHash };
}

/**
 * Puts every archive that the swarm can serve behind the PMTiles protocol.
 *
 * Resolves when the batch is done or the deadline passes, whichever comes
 * first, and never rejects: the caller waits on it before adding its layers,
 * and a swarm problem must not be able to stop a map from loading.
 *
 * @param {object} options Options.
 * @param {object} options.protocol The pmtiles Protocol the style reads through.
 * @param {Array<object>} options.archives `{bucket, key, tilejson}` per archive.
 * @param {Function} [options.log] Receives one line per archive.
 * @returns {Promise<object|null>} A handle for inspection, or null if the
 *   swarm was unusable.
 */
export async function enableSwarm({
  protocol,
  archives,
  log,
  // Overridable so a long wait can be tried without editing this file: the
  // question "does a peer ever answer" needs a different budget from the one a
  // map should wait on, and guessing at it from timeouts alone is slow.
  metadataTimeoutMs = METADATA_TIMEOUT_MS,
  batchTimeoutMs = BATCH_TIMEOUT_MS,
}) {
  const report = log ?? ((message) => console.info(`[swarm] ${message}`));
  if (!Array.isArray(archives) || archives.length === 0) {
    return null;
  }

  let client;
  let lib;
  try {
    const [webtorrent, source, engine] = await Promise.all([
      import('webtorrent'),
      import('pmtiles-torrent'),
      import('pmtiles-torrent/webtorrent'),
    ]);
    lib = {
      TorrentSource: source.TorrentSource,
      WebTorrentEngine: engine.WebTorrentEngine,
    };
    // One client for the page: one peer pool, one set of tracker connections,
    // one WebRTC stack, however many archives end up behind it.
    client = new webtorrent.default();
  } catch (error) {
    report(`no WebTorrent client (${error.message}), staying on HTTP`);
    return null;
  }
  // Peer-level failures are routine and already handled inside the client;
  // without a listener they would surface as unhandled errors.
  client.on('error', (error) => report(`client: ${error.message}`));

  // Set once the caller has been released, which is the moment registering
  // stops being useful: the layers go in immediately afterwards, and the
  // protocol binds an HTTP source to any URL it does not already know.
  let deadlinePassed = false;
  const registered = [];

  const pending = archives.map((archive) =>
    joinArchive(archive, client, lib, report, metadataTimeoutMs)
      .catch((error) => {
        report(`${archive.bucket}: ${error.message}, staying on HTTP`);
        return null;
      })
      .then((result) => {
        if (!result) return;
        // Registered even after the deadline. The deadline bounds how long the
        // map waits before drawing, not how long an archive is useful for:
        // transformRequest is consulted on every tile request, so one that
        // joins at twenty seconds simply starts serving from then on. This
        // used to release them, which was right when the binding lived in the
        // style's source URL and was resolved once -- it is not any more.
        protocol.add(new PMTiles(result.source));
        registered.push(result);
        if (deadlinePassed) {
          report(`${result.bucket}: joined late, serving from here on`);
        }
      }),
  );

  await Promise.race([
    Promise.all(pending),
    // A deadline on the batch rather than a timeout per archive: what is being
    // bounded is how long the map waits, and one slow archive must not be able
    // to hold up either the others or the layers.
    new Promise((resolve) => setTimeout(resolve, batchTimeoutMs)),
  ]);
  deadlinePassed = true;

  if (registered.length === 0) {
    report('nothing joinable, staying on HTTP');
    client.destroy();
    return null;
  }

  // Which archives ended up behind the protocol, by infohash.
  const joined = new Map(registered.map((entry) => [entry.infoHash, entry]));

  return {
    client,
    get archives() {
      return registered.map((entry) => entry.bucket);
    },

    /**
     * Rewrites a tile URL to read through the swarm, or leaves it alone.
     *
     * The map's sources are ordinary https TileJSON URLs, so MapLibre fetches
     * and parses them itself -- which is what keeps every other consumer of
     * that style working, maplibre-gl-inspect included. The tile URLs the
     * document hands back name the archive by infohash, and that is the hook:
     * an archive this joined gets its tiles served out of pieces, and one it
     * did not is returned untouched for MapLibre to fetch over HTTP.
     *
     * Intended for MapLibre's `transformRequest`. Registering a protocol for
     * `https` would work too and would be a mistake -- the lookup is by scheme,
     * so it would capture styles, glyphs, sprites and every other request, and
     * the pass-through case would mean reimplementing the loader.
     *
     * @param {string} url The URL MapLibre is about to request.
     * @returns {string} The same URL, or a pmtiles:// one that reads locally.
     */
    rewrite(url) {
      // .../archives/<40 hex>/<z>/<x>/<y>.<ext>
      const match = /\/archives\/([0-9a-f]{40})\/(\d+)\/(\d+)\/(\d+)\./i.exec(
        url,
      );
      if (!match) return url;

      const [, infoHash, z, x, y] = match;
      if (!joined.has(infoHash.toLowerCase())) return url;

      // Matches the tile pattern the PMTiles protocol looks for, keyed by the
      // same infohash the source was registered under.
      return `pmtiles://${infoHash.toLowerCase()}/${z}/${x}/${y}`;
    },
    /**
     * What the swarm has actually done, for telling a real swarm read apart
     * from a web-seed read that merely went through this code.
     * @returns {object} Totals plus a per-archive breakdown.
     */
    stats() {
      return {
        peers: client.torrents.reduce((sum, t) => sum + t.numPeers, 0),
        downloaded: client.downloaded,
        uploaded: client.uploaded,
        archives: registered.map((entry) => ({
          bucket: entry.bucket,
          infoHash: entry.infoHash,
          ...entry.source.stats,
        })),
      };
    },
  };
}
