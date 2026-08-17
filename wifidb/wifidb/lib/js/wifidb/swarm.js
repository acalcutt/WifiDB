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
 * So this fetches the category's TileJSON, whose `torrent` block pmtiles-swarm
 * publishes with everything a browser can actually act on. The mutable magnet
 * in the fragment stays where it is: it costs nothing, it is what a client with
 * a real DHT would use, and it is the fallback if this endpoint disappears.
 *
 * ── What a browser is handed, and why the magnet is not enough ───────────────
 *
 * The obvious handle from that block is the plain `xt=urn:btih:` magnet, joined
 * over WebRTC through a websocket tracker. That works, and it is not sufficient:
 * a magnet names a swarm and carries no piece hashes, and the metainfo those
 * live in can only come from a **peer**, over BEP 9. A web seed serves file
 * payload and never metainfo.
 *
 * A browser that cannot reach a peer therefore cannot use the web seed either,
 * however reachable that web seed is -- it holds bytes it has no way to verify.
 * On a carrier network that blocks the websocket trackers, an archive with a
 * perfectly healthy HTTPS web seed reports nought peers, nought web seeds, and
 * serves nothing at all.
 *
 * The `torrent` block also names the .torrent itself, a few hundred bytes over
 * the same HTTPS the TileJSON arrived on, and that carries the piece hashes,
 * the trackers and the web seed together. Fetching it takes the peer off the
 * critical path: the engine is ready the moment it is built, and the web seed
 * alone can serve the archive. That is the preferred route, with the magnet
 * kept for when it is unavailable -- see chooseTorrentId.
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
 * hand. Anything that never gets there is left unregistered and loads over HTTP
 * exactly as it did before, which is also what happens when this module is
 * never loaded at all.
 *
 * The batch deadline bounds only how long the caller waits before drawing. An
 * archive arriving after it is still registered and still starts serving, so
 * nothing is thrown away for being late -- see the note in the handoff about
 * the version that did throw them away and reported a working swarm as broken.
 */

/** How long to wait for a category's TileJSON before giving up on it. */
const TILEJSON_TIMEOUT_MS = 5000;
/**
 * How long to wait for an archive's .torrent, and for the web seed check that
 * decides whether the metainfo is worth having.
 *
 * Both are ordinary HTTPS requests against the same hosts the map already
 * talks to, so a budget in seconds is generous. Neither is worth waiting on
 * longer than the TileJSON that named them.
 */
const METAINFO_TIMEOUT_MS = 5000;
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
 * Fetches an archive's .torrent, so this page holds the metainfo outright.
 *
 * This is the difference between working on a restricted network and not
 * working at all, and the reason is worth stating plainly. A magnet names a
 * swarm by infohash and nothing else; the piece hashes live in the metainfo,
 * which a BitTorrent client can only obtain from a **peer**, over BEP 9. A web
 * seed serves file payload and never metainfo, so it cannot bootstrap one.
 *
 * So a browser that cannot reach a peer -- a carrier network blocking the
 * websocket trackers, a NAT no WebRTC candidate survives -- cannot use the web
 * seed either, however reachable that web seed is. It has bytes it is not
 * allowed to trust. Tested on a phone: nought peers, nought web seeds, both
 * HTTPS endpoints answering perfectly the whole time.
 *
 * The .torrent removes the peer from the critical path entirely. It is a few
 * hundred bytes over the same HTTPS the TileJSON came from, and it carries the
 * piece hashes, the trackers and the web seed together.
 *
 * @param {string} url URL of the archive's .torrent.
 * @returns {Promise<Uint8Array|null>} The metainfo, or null.
 */
async function fetchMetainfo(url) {
  try {
    const response = await fetch(url, {
      signal: AbortSignal.timeout(METAINFO_TIMEOUT_MS),
      // Names one build, like the document that pointed here.
      cache: 'no-cache',
    });
    if (!response.ok) {
      return null;
    }
    return new Uint8Array(await response.arrayBuffer());
  } catch {
    return null;
  }
}

/**
 * Whether a web seed will actually answer this page.
 *
 * Holding the metainfo means the engine is ready the moment it is constructed,
 * with no peer involved -- which removes the very thing that used to prove an
 * archive was worth registering. Waiting for metadata was never only a wait: it
 * was evidence that somebody out there could serve this archive, and an archive
 * that failed to produce it stayed on HTTP. Registering on metainfo alone would
 * throw that evidence away and bind a bucket to a source that may have nothing
 * behind it, which does not fall back -- it stalls, and the tiles never draw.
 *
 * One request for one byte restores the evidence: a 206 proves the host is
 * reachable, serves ranges, and permits this origin, which is everything the
 * engine needs from it. Cheap enough to do per archive, and the alternative --
 * fetching a whole piece to be sure -- would cost megabytes on exactly the
 * connections this is meant to help.
 *
 * @param {string} url The web seed to try.
 * @returns {Promise<boolean>} Whether it answered.
 */
async function probeWebSeed(url) {
  try {
    const response = await fetch(url, {
      headers: { Range: 'bytes=0-0' },
      signal: AbortSignal.timeout(METAINFO_TIMEOUT_MS),
    });
    return response.ok;
  } catch {
    // Unreachable, no CORS, or ranges refused. Indistinguishable from here and
    // the same answer either way: not something to bind a bucket to.
    return false;
  }
}

/**
 * Decides what to hand the engine, and says which way it went.
 *
 * Two routes, in order of how little they ask of the network:
 *
 *   metainfo  the .torrent over HTTPS, with a web seed confirmed to answer.
 *             Needs no peer, no tracker and no WebRTC, so it survives a
 *             network that blocks all three.
 *   magnet    the infohash, joined over a websocket tracker. Needs a peer, and
 *             the wait for metadata is what proves one exists.
 *
 * The magnet is not a lesser handle in general -- for a client with a DHT it is
 * the better one, since it needs no host at all. It is lesser *here*, because a
 * browser has no DHT and no UDP, and the one thing a magnet cannot supply is
 * the one thing a browser cannot get anywhere else.
 *
 * @param {object} block The `torrent` block from the category's TileJSON.
 * @param {string} bucket Named in the log line.
 * @param {Function} log Where to report what happened.
 * @returns {Promise<Uint8Array|string|null>} What to add, or null.
 */
async function chooseTorrentId(block, bucket, log, localTorrent) {
  const webSeed = Array.isArray(block.webseeds) ? block.webseeds[0] : null;
  // This site's own copy first, where it has one. Same origin as the page and
  // as the web seed, so the whole read depends on one host rather than two and
  // touches no cross-origin request at all -- which also means it survives the
  // swarm node being unreachable, and there is no CORS left to misconfigure.
  const metainfoUrl =
    localTorrent ||
    (typeof block.torrent === 'string' && block.torrent !== ''
      ? block.torrent
      : null);
  if (metainfoUrl && webSeed) {
    // Both at once: they are independent, and one round trip is enough of a
    // delay to add to a map that has not drawn yet.
    const [metainfo, answered] = await Promise.all([
      fetchMetainfo(metainfoUrl),
      probeWebSeed(webSeed),
    ]);
    if (metainfo && answered) {
      log(`${bucket}: metainfo over HTTPS, web seed answering; no peer needed`);
      return metainfo;
    }
    if (metainfo && !answered) {
      // The metainfo is good and there is nothing proven to serve it. Falling
      // through to the magnet rather than registering, because the magnet path
      // still has to find a peer and so still proves somebody can serve this.
      log(`${bucket}: web seed did not answer; falling back to the swarm`);
    }
  }

  if (isJoinable(block.magnet)) {
    return block.magnet;
  }
  log(`${bucket}: magnet is not joinable from a browser, staying on HTTP`);
  return null;
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
 * Finds the torrent-capable sources in a style, by the convention in the
 * fragment.
 *
 * A source URL of the form
 *
 *   https://host/latest/<category>/tiles.json#torrent=<url>&magnet=<magnet>
 *
 * says "there is a swarm behind this". The fragment is never sent in a request,
 * so the same string is an ordinary TileJSON URL to MapLibre and to every other
 * consumer of the style; only something that goes looking for it -- this --
 * sees anything else. That is what makes it safe to put in a style file served
 * to everybody.
 *
 * Discovery rather than a server-rendered list, because the styles are not
 * ours. A basemap comes from a tileserver-gl instance and is edited there, and
 * the alternative was a copy of its source list kept here and kept in step by
 * hand. Anything that carries the handle is offered; anything that does not is
 * left alone.
 *
 * `torrent=` is required and `magnet=` is not: a magnet on its own cannot get
 * piece hashes to a browser, which is the whole reason the convention names the
 * metainfo first. A source with only a magnet is therefore not a candidate.
 *
 * @param {object} style A MapLibre style object, from map.getStyle().
 * @param {object} [options] Options.
 * @param {Array<object>} [options.exclude] Archives already being handled,
 *   matched on their TileJSON URL, so a bucket the server already listed is not
 *   joined twice under two names.
 * @returns {Array<object>} `{bucket, tilejson, torrent, magnet}` per source.
 */
export function archivesFromStyle(style, { exclude = [] } = {}) {
  const seen = new Set(
    (exclude ?? [])
      .map((archive) => String(archive?.tilejson ?? '').split('#')[0])
      .filter(Boolean),
  );

  const found = [];
  for (const [id, source] of Object.entries(style?.sources ?? {})) {
    const url = typeof source?.url === 'string' ? source.url : '';
    const hash = url.indexOf('#');
    if (hash < 0) continue;

    const handles = new URLSearchParams(url.slice(hash + 1));
    const torrent = handles.get('torrent');
    if (!torrent) continue;

    const tilejson = url.slice(0, hash);
    // The same archive can appear under two source names in one style, and can
    // also be one the server already listed. Either way it is one swarm and one
    // engine; joining it twice would mean two clients fighting over one cache.
    if (seen.has(tilejson)) continue;
    seen.add(tilejson);

    found.push({
      bucket: id,
      tilejson,
      torrent,
      magnet: handles.get('magnet') ?? undefined,
    });
  }
  return found;
}

/**
 * Joins one archive's swarm and builds a source for it./**
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
  const block =
    (await fetchTorrentBlock(archive.tilejson)) ??
    // No document, or one without a torrent block. A source discovered from a
    // style still has the handles from its fragment, and that is exactly the
    // case the fragment exists for -- it is consulted when the document in
    // front of it cannot be. Without them there is nothing left to try.
    (archive.torrent || archive.magnet
      ? { torrent: archive.torrent, magnet: archive.magnet, webseeds: [] }
      : null);
  if (!block) {
    log(`${archive.bucket}: no TileJSON from the swarm, staying on HTTP`);
    return null;
  }
  const torrentId = await chooseTorrentId(
    block,
    archive.bucket,
    log,
    // Supplied by PHP from swarm_archives, where a copy has been cached.
    archive.torrent,
  );
  if (!torrentId) {
    return null;
  }

  // Either a magnet string or the metainfo itself. WebTorrent's add() takes
  // both, and the engine passes whatever it is given straight through, so this
  // needs nothing from pmtiles-torrent that it did not already do.
  const engine = new lib.WebTorrentEngine(torrentId, {
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
 * @returns {Promise<object|null>} A handle carrying `rewrite`, `stats` and
 *   `destroy`, or null when there was nothing to join and no client to join it
 *   with. A handle whose archives all failed is still a handle: `rewrite`
 *   passes every URL through, which is the same thing null means, and one still
 *   arriving can register against it.
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

  // Set once the caller has been released, so a late join can say so. Not a
  // cutoff: registering stays useful afterwards, see below.
  let deadlinePassed = false;
  let destroyed = false;
  const registered = [];
  // What has been put to the swarm, joined or not, by bucket. The ratio in the
  // readout is "of the archives we tried, how many are serving", so this has to
  // grow when join() adds more -- it used to be the initial array's length,
  // which read as 21/20 the moment a source discovered from the style joined.
  //
  // A set rather than a counter because a later discovery pass can offer an
  // archive that was offered before and failed. Retrying it is right; counting
  // it twice is not.
  const offeredBuckets = new Set(
    archives.map((archive) => archive.bucket).filter(Boolean),
  );
  // Which archives are behind the protocol, by infohash. Populated as they
  // arrive rather than built at the deadline, because arrivals after it count.
  const joined = new Map();

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
        if (destroyed) {
          // Switched off while this was still joining. Nothing will read it.
          release(result.engine);
          return;
        }
        protocol.add(new PMTiles(result.source));
        registered.push(result);
        joined.set(result.infoHash, result);
        if (deadlinePassed) {
          report(`${result.bucket}: joined late, serving from here on`);
        }
      }),
  );

  // Nothing joined and nothing still trying. Only then is the client certain
  // to be useless -- checking this at the deadline instead would destroy it
  // out from under archives that were still 20 seconds from arriving, which is
  // the same mistake the batch deadline used to make with the ones that had
  // already landed.
  const settled = Promise.all(pending).then(() => {
    if (registered.length === 0 && !destroyed) {
      report('nothing joinable, staying on HTTP');
      destroyed = true;
      client.destroy();
    }
  });

  await Promise.race([
    settled,
    // A deadline on the batch rather than a timeout per archive: what is being
    // bounded is how long the map waits, and one slow archive must not be able
    // to hold up either the others or the layers.
    new Promise((resolve) => setTimeout(resolve, batchTimeoutMs)),
  ]);
  deadlinePassed = true;

  return {
    client,
    get archives() {
      return registered.map((entry) => entry.bucket);
    },
    /** How many of the archives offered are being served from the swarm. */
    get joinedCount() {
      return registered.length;
    },
    /** How many were offered, joined or not. */
    get offeredCount() {
      return offeredBuckets.size;
    },

    /**
     * Stops reading from the swarm and drops every peer connection.
     *
     * Clearing `joined` is what actually takes effect: rewrite() consults it
     * per request, so the next tile goes back over HTTP whether or not anything
     * still holds this handle. The sources stay registered on the protocol --
     * pmtiles offers no way to withdraw one -- but with nothing rewritten to
     * `pmtiles://`, nothing ever asks them for a tile.
     */
    destroy() {
      if (destroyed) return;
      destroyed = true;
      joined.clear();
      registered.length = 0;
      try {
        client.destroy();
      } catch {
        // Already gone. Nothing to release and nothing useful to say about it.
      }
    },

    /**
     * Joins more archives against the client this handle already owns.
     *
     * Separate from the initial batch because the two are discovered at
     * different times and must not wait on each other. The batch comes from the
     * server and is ready before the map is built; the style's own sources
     * cannot be read until MapLibre has loaded the style, which is after. Making
     * the batch wait for that would delay every layer on the page for the sake
     * of an archive that can perfectly well arrive late.
     *
     * Arriving late costs nothing: `rewrite` is consulted on every tile request,
     * so an archive registered at twenty seconds simply starts serving from the
     * next one. Tiles fetched before then went over HTTP, which is correct.
     *
     * Anything already joined is skipped rather than joined twice -- one swarm
     * wants one engine, and two would fight over one cache.
     *
     * @param {Array<object>} more `{bucket, tilejson, torrent, magnet}` each.
     * @returns {Promise<number>} How many of them are now serving.
     */
    async join(more) {
      if (destroyed || !Array.isArray(more) || more.length === 0) return 0;

      const wanted = more.filter(
        (archive) =>
          !registered.some((held) => held.bucket === archive.bucket),
      );
      for (const archive of wanted) offeredBuckets.add(archive.bucket);
      const results = await Promise.all(
        wanted.map((archive) =>
          joinArchive(archive, client, lib, report, metadataTimeoutMs).catch(
            (error) => {
              report(`${archive.bucket}: ${error.message}, staying on HTTP`);
              return null;
            },
          ),
        ),
      );

      let added = 0;
      for (const result of results) {
        if (!result) continue;
        // Switched off while this was joining. Nothing will ever read it.
        if (destroyed) {
          release(result.engine);
          continue;
        }
        protocol.add(new PMTiles(result.source));
        registered.push(result);
        joined.set(result.infoHash, result);
        added += 1;
      }
      return added;
    },

    /**
     * Rewrites a tile URL to read through the swarm, or leaves it alone.    /**
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
     *
     * Every connection is counted in exactly one of three buckets, and the
     * split is the whole point of this function:
     *
     *   webSeeds  `wire.type === 'webSeed'` -- an HTTP range reader against the
     *             generating node, dressed as a wire. WebTorrent's `numPeers`
     *             is just `wires.length`, so a page pulling every byte from
     *             wifidb.net over HTTP reports the same healthy peer count as
     *             one genuinely trading pieces with browsers. This is the
     *             number that makes those two distinguishable.
     *   seeds     a real peer holding the whole archive. WebTorrent sets
     *             `isSeeder` from `have-all` or a full bitfield.
     *   peers     a real peer holding part of it.
     *
     * Bytes are summed off the torrents rather than read off the client,
     * because the client has no such property: WebTorrent puts `downloaded`,
     * `uploaded` and `received` on Torrent and gives the client only
     * `progress`, `ratio` and the two speeds. Reading `client.downloaded` --
     * which this did -- yields undefined, silently, and a readout that can
     * never show a byte no matter how many arrive.
     *
     * `downloaded` is what the bitfield says is verified and held, so it is
     * archive bytes; `received` is everything off the wire including overhead
     * and duplicates. Both are kept because a large gap between them is worth
     * seeing.
     *
     * @returns {object} Totals plus a per-archive breakdown.
     */
    stats() {
      let peers = 0;
      let seeds = 0;
      let webSeeds = 0;
      let downloaded = 0;
      let uploaded = 0;
      let received = 0;
      for (const torrent of client.torrents) {
        for (const wire of torrent.wires || []) {
          if (wire.type === 'webSeed') webSeeds++;
          else if (wire.isSeeder) seeds++;
          else peers++;
        }
        downloaded += torrent.downloaded || 0;
        uploaded += torrent.uploaded || 0;
        received += torrent.received || 0;
      }
      return {
        peers,
        seeds,
        webSeeds,
        downloaded,
        uploaded,
        received,
        // The one number that cannot sit still while anything is happening,
        // which is what makes a live readout legible as live.
        downloadSpeed: client.downloadSpeed || 0,
        joined: registered.length,
        offered: offeredBuckets.size,
        archives: registered.map((entry) => ({
          bucket: entry.bucket,
          infoHash: entry.infoHash,
          ...entry.source.stats,
        })),
      };
    },
  };
}
