import {
  __name
} from "./chunk-7QVYU63E.js";

// src/cache.ts
var PieceCache = class {
  static {
    __name(this, "PieceCache");
  }
  #maxBytes;
  #bytes = 0;
  // Map preserves insertion order, so the first key is always the LRU entry.
  #entries = /* @__PURE__ */ new Map();
  /**
   * @param maxBytes Byte budget. Zero disables caching entirely.
   */
  constructor(maxBytes) {
    this.#maxBytes = Math.max(0, maxBytes);
  }
  /** Total bytes currently held. */
  get byteLength() {
    return this.#bytes;
  }
  /** Current byte budget. */
  get maxBytes() {
    return this.#maxBytes;
  }
  /** Number of pieces currently held. */
  get size() {
    return this.#entries.size;
  }
  /**
   * Changes the budget, evicting as needed. Used once torrent metadata arrives
   * and the real piece length is known.
   */
  resize(maxBytes) {
    this.#maxBytes = Math.max(0, maxBytes);
    this.#evict();
  }
  /** Looks up a piece, marking it most recently used. */
  get(index) {
    const hit = this.#entries.get(index);
    if (hit === void 0) return void 0;
    this.#entries.delete(index);
    this.#entries.set(index, hit);
    return hit;
  }
  /** Stores a piece, evicting the least recently used entries if over budget. */
  set(index, piece) {
    if (this.#maxBytes === 0) return;
    if (piece.byteLength > this.#maxBytes) return;
    const existing = this.#entries.get(index);
    if (existing !== void 0) {
      this.#entries.delete(index);
      this.#bytes -= existing.byteLength;
    }
    this.#entries.set(index, piece);
    this.#bytes += piece.byteLength;
    this.#evict();
  }
  /** Drops every cached piece. */
  clear() {
    this.#entries.clear();
    this.#bytes = 0;
  }
  /** Evicts least recently used entries until within budget. */
  #evict() {
    while (this.#bytes > this.#maxBytes) {
      const oldest = this.#entries.keys().next();
      if (oldest.done) break;
      const evicted = this.#entries.get(oldest.value);
      this.#entries.delete(oldest.value);
      if (evicted !== void 0) this.#bytes -= evicted.byteLength;
    }
  }
};

// src/id.ts
var MAGNET = /^magnet:\?.*xt=urn:bt[im]h:[a-z0-9]+/i;
var INFOHASH = /^(?:[a-f0-9]{40}|[a-z2-7]{32})$/i;
function isTorrentId(value) {
  if (typeof value !== "string") return false;
  const trimmed = value.trim();
  return MAGNET.test(trimmed) || INFOHASH.test(trimmed) || isTorrentFile(trimmed);
}
__name(isTorrentId, "isTorrentId");
function isTorrentFile(value) {
  if (typeof value !== "string") return false;
  return value.trim().split("?")[0].toLowerCase().endsWith(".torrent");
}
__name(isTorrentFile, "isTorrentFile");
function torrentDisplayName(value) {
  if (typeof value !== "string") return null;
  const trimmed = value.trim();
  if (isTorrentFile(trimmed)) {
    const base = trimmed.split("?")[0].split(/[\\/]/).pop() ?? "";
    return base.slice(0, -".torrent".length) || null;
  }
  const dn = /[?&]dn=([^&]*)/.exec(trimmed);
  if (!dn) return null;
  try {
    return decodeURIComponent(dn[1]) || null;
  } catch {
    return dn[1] || null;
  }
}
__name(torrentDisplayName, "torrentDisplayName");

// src/layout.ts
var HEADER_SIZE = 127;
var MAGIC = "PMTiles";
function getUint64(view, offset) {
  const low = view.getUint32(offset, true);
  const high = view.getUint32(offset + 4, true);
  return high * 2 ** 32 + low;
}
__name(getUint64, "getUint64");
function readLayout(bytes) {
  if (bytes.byteLength < HEADER_SIZE) return null;
  for (let i = 0; i < MAGIC.length; i++) {
    if (bytes[i] !== MAGIC.charCodeAt(i)) return null;
  }
  const specVersion = bytes[7];
  if (specVersion !== 3) return null;
  const view = new DataView(bytes.buffer, bytes.byteOffset, bytes.byteLength);
  return {
    specVersion,
    rootDirectoryOffset: getUint64(view, 8),
    rootDirectoryLength: getUint64(view, 16),
    jsonMetadataOffset: getUint64(view, 24),
    jsonMetadataLength: getUint64(view, 32),
    leafDirectoryOffset: getUint64(view, 40),
    leafDirectoryLength: getUint64(view, 48),
    tileDataOffset: getUint64(view, 56),
    tileDataLength: getUint64(view, 64)
  };
}
__name(readLayout, "readLayout");

// src/source.ts
var IMMUTABLE = "public, max-age=31536000, immutable";
var MIN_CACHE_BYTES = 64 * 1024 * 1024;
var DEFAULT_CACHE_PIECES = 8;
var DEFAULT_MAX_LEAF_PREFETCH_BYTES = 256 * 1024 * 1024;
var DEFAULT_HYDRATE_IDLE_MS = 2e3;
function abortError() {
  const error = new Error("The operation was aborted.");
  error.name = "AbortError";
  return error;
}
__name(abortError, "abortError");
var TorrentSource = class {
  static {
    __name(this, "TorrentSource");
  }
  #engine;
  #options;
  #cache;
  #pending = /* @__PURE__ */ new Map();
  #initPromise;
  #info;
  #layoutRead = false;
  /** Regions to fetch in the background while nothing is being requested. */
  #hydrationRegions = [];
  #hydrationActive = false;
  #idleTimer;
  #destroyed = false;
  #stats = {
    cacheHits: 0,
    cacheMisses: 0,
    bytesFetched: 0,
    bytesServed: 0,
    cancelled: 0
  };
  constructor(engine, options = {}) {
    this.#engine = engine;
    this.#options = {
      cacheBytes: options.cacheBytes,
      cachePieces: options.cachePieces ?? DEFAULT_CACHE_PIECES,
      prefetchDirectories: options.prefetchDirectories ?? true,
      maxLeafPrefetchBytes: options.maxLeafPrefetchBytes ?? DEFAULT_MAX_LEAF_PREFETCH_BYTES,
      hydrateIdleMs: options.hydrateIdleMs ?? DEFAULT_HYDRATE_IDLE_MS,
      key: options.key
    };
    this.#cache = new PieceCache(this.#options.cacheBytes ?? MIN_CACHE_BYTES);
  }
  /** The underlying engine, for seeding stats or swarm introspection. */
  get engine() {
    return this.#engine;
  }
  /** Counters describing cache and fetch behaviour. */
  get stats() {
    return {
      ...this.#stats,
      cachedPieces: this.#cache.size,
      cachedBytes: this.#cache.byteLength,
      cacheBudget: this.#cache.maxBytes,
      hydrating: this.#hydrationActive
    };
  }
  /** A unique key for this archive, available before metadata arrives. */
  getKey() {
    return this.#options.key ?? this.#engine.key;
  }
  /**
   * Resolves torrent metadata without reading any archive bytes. Useful if you
   * want to fail fast at startup rather than on the first tile request.
   */
  async ready() {
    return this.#init();
  }
  /**
   * Reads a byte range out of the archive.
   *
   * The `etag` argument is accepted for interface compatibility and ignored: an
   * infohash *is* a content hash, so the bytes behind a given key can never
   * change. PMTiles' ETag-mismatch retry path is structurally unreachable here.
   */
  async getBytes(offset, length, signal, _etag) {
    if (signal?.aborted) throw abortError();
    const info = await this.#init();
    if (offset < 0 || length < 0) {
      throw new RangeError(`invalid range: offset ${offset}, length ${length}`);
    }
    if (offset >= info.fileLength) {
      throw new RangeError(
        `offset ${offset} is past the end of the archive (${info.fileLength} bytes)`
      );
    }
    const wanted = Math.min(length, info.fileLength - offset);
    if (wanted === 0) {
      return {
        data: new ArrayBuffer(0),
        etag: info.infoHash,
        cacheControl: IMMUTABLE
      };
    }
    const firstPiece = this.#pieceIndexOf(offset);
    const lastPiece = this.#pieceIndexOf(offset + wanted - 1);
    const indices = [];
    for (let i = firstPiece; i <= lastPiece; i++) indices.push(i);
    const pieces = await Promise.all(
      indices.map((index) => this.#getPiece(index, signal))
    );
    const out = new Uint8Array(wanted);
    let written = 0;
    for (let n = 0; n < indices.length; n++) {
      const piece = pieces[n];
      const pieceStart = this.#pieceFileRange(indices[n]).start;
      const from = Math.max(0, offset - pieceStart);
      const to = Math.min(piece.byteLength, offset + wanted - pieceStart);
      out.set(piece.subarray(from, to), written);
      written += to - from;
    }
    if (written !== wanted) {
      throw new Error(
        `short read: assembled ${written} of ${wanted} bytes at offset ${offset}`
      );
    }
    this.#stats.bytesServed += written;
    if (!this.#layoutRead && offset === 0 && written >= HEADER_SIZE) {
      this.#layoutRead = true;
      this.#prefetchDirectories(out);
    }
    return {
      data: out.buffer,
      etag: info.infoHash,
      cacheControl: IMMUTABLE
    };
  }
  /** Releases the cache, cancels in-flight reads and destroys the engine. */
  async destroy() {
    this.#destroyed = true;
    this.#suspendHydration();
    this.#hydrationRegions = [];
    this.#cache.clear();
    for (const pending of this.#pending.values()) pending.controller.abort();
    this.#pending.clear();
    this.#initPromise = void 0;
    this.#info = void 0;
    this.#layoutRead = false;
    await this.#engine.destroy();
  }
  /** Resolves and validates torrent metadata, once. */
  #init() {
    if (!this.#initPromise) {
      this.#initPromise = this.#engine.ready().then((info) => {
        if (!(info.pieceLength > 0)) {
          throw new Error(
            `engine reported invalid piece length ${info.pieceLength}`
          );
        }
        if (!(info.fileLength > 0)) {
          throw new Error(
            `engine reported empty archive (${info.fileLength} bytes)`
          );
        }
        if (this.#options.cacheBytes === void 0) {
          this.#cache.resize(
            Math.max(
              MIN_CACHE_BYTES,
              this.#options.cachePieces * info.pieceLength
            )
          );
        }
        this.#info = info;
        return info;
      });
    }
    return this.#initPromise;
  }
  /** Maps a file-relative offset to the torrent piece containing it. */
  #pieceIndexOf(fileOffset) {
    const info = this.#info;
    return Math.floor((info.fileOffset + fileOffset) / info.pieceLength);
  }
  /**
   * The portion of a piece that lies inside the archive file, as inclusive
   * file-relative bounds. Pieces at either end of the file may be clipped when
   * the torrent holds more than one file.
   */
  #pieceFileRange(index) {
    const info = this.#info;
    const globalStart = index * info.pieceLength;
    const globalEnd = globalStart + info.pieceLength - 1;
    return {
      start: Math.max(0, globalStart - info.fileOffset),
      end: Math.min(info.fileLength - 1, globalEnd - info.fileOffset)
    };
  }
  /**
   * Fetches one piece, sharing in-flight work between concurrent callers.
   *
   * Cancellation is reference counted: an aborted request stops waiting
   * immediately, but the underlying fetch is only cancelled once *every* waiter
   * has gone. Forwarding a caller's signal straight through would let one
   * abandoned tile request kill a piece another request is still waiting on.
   */
  #getPiece(index, signal) {
    const cached = this.#cache.get(index);
    if (cached !== void 0) {
      this.#stats.cacheHits++;
      return Promise.resolve(cached);
    }
    this.#stats.cacheMisses++;
    let entry = this.#pending.get(index);
    if (entry === void 0) {
      const created = {
        controller: new AbortController(),
        waiters: 0,
        settled: false,
        promise: void 0
      };
      created.promise = this.#fetchPiece(index, created.controller.signal);
      created.promise.then(
        () => {
          created.settled = true;
        },
        () => {
          created.settled = true;
        }
      );
      created.promise.catch(() => {
      });
      created.promise.finally(() => {
        if (this.#pending.get(index) === created) this.#pending.delete(index);
        this.#scheduleHydration();
      }).catch(() => {
      });
      this.#pending.set(index, created);
      entry = created;
      this.#suspendHydration();
    }
    const pending = entry;
    pending.waiters++;
    return new Promise((resolve, reject) => {
      let detached = false;
      const detach = /* @__PURE__ */ __name(() => {
        if (detached) return true;
        detached = true;
        pending.waiters--;
        if (pending.waiters === 0 && !pending.settled && !pending.controller.signal.aborted) {
          this.#stats.cancelled++;
          pending.controller.abort();
        }
        signal?.removeEventListener("abort", onAbort);
        return false;
      }, "detach");
      const onAbort = /* @__PURE__ */ __name(() => {
        if (!detach()) reject(abortError());
      }, "onAbort");
      if (signal?.aborted) {
        detach();
        reject(abortError());
        return;
      }
      signal?.addEventListener("abort", onAbort, { once: true });
      pending.promise.then(
        (value) => {
          detach();
          resolve(value);
        },
        (error) => {
          detach();
          reject(error);
        }
      );
    });
  }
  /** Reads one whole piece from the engine and caches it. */
  async #fetchPiece(index, signal) {
    const { start, end } = this.#pieceFileRange(index);
    const length = end - start + 1;
    const bytes = await this.#engine.readRange(start, length, {
      signal,
      priority: "critical"
    });
    if (bytes.byteLength !== length) {
      throw new Error(
        `engine returned ${bytes.byteLength} bytes for piece ${index}, expected ${length}`
      );
    }
    this.#stats.bytesFetched += bytes.byteLength;
    this.#cache.set(index, bytes);
    return bytes;
  }
  /**
   * Tells the engine which regions matter before anything asks for them.
   *
   * Every tile lookup is gated on a directory read, so the root directory is
   * worth treating as critical even though nothing is blocked on it yet.
   */
  #prefetchDirectories(header) {
    if (!this.#options.prefetchDirectories) return;
    const hint = this.#engine.hint?.bind(this.#engine);
    if (!hint) return;
    const layout = readLayout(header);
    if (!layout) return;
    const info = this.#info;
    const inBounds = /* @__PURE__ */ __name((offset, length) => length > 0 && offset >= 0 && offset + length <= info.fileLength, "inBounds");
    if (inBounds(layout.rootDirectoryOffset, layout.rootDirectoryLength)) {
      hint(layout.rootDirectoryOffset, layout.rootDirectoryLength, "critical");
    }
    if (inBounds(layout.jsonMetadataOffset, layout.jsonMetadataLength)) {
      hint(layout.jsonMetadataOffset, layout.jsonMetadataLength, "high");
    }
    if (inBounds(layout.leafDirectoryOffset, layout.leafDirectoryLength) && layout.leafDirectoryLength <= this.#options.maxLeafPrefetchBytes) {
      this.#hydrationRegions.push({
        offset: layout.leafDirectoryOffset,
        length: layout.leafDirectoryLength
      });
      this.#scheduleHydration();
    }
  }
  /**
   * Stops background hydration because a request needs the bandwidth. Called
   * whenever a piece fetch starts.
   */
  #suspendHydration() {
    if (this.#idleTimer !== void 0) {
      clearTimeout(this.#idleTimer);
      this.#idleTimer = void 0;
    }
    if (!this.#hydrationActive) return;
    this.#hydrationActive = false;
    const unhint = this.#engine.unhint?.bind(this.#engine);
    if (!unhint) return;
    for (const region of this.#hydrationRegions) {
      unhint(region.offset, region.length);
    }
  }
  /**
   * Arms background hydration to resume once the source has been idle for
   * `hydrateIdleMs`. A request arriving in the meantime disarms it again.
   */
  #scheduleHydration() {
    if (this.#destroyed) return;
    if (this.#hydrationActive || this.#hydrationRegions.length === 0) return;
    if (!this.#engine.hint || !this.#engine.unhint) return;
    if (this.#pending.size > 0) return;
    if (this.#idleTimer !== void 0) return;
    this.#idleTimer = setTimeout(() => {
      this.#idleTimer = void 0;
      if (this.#destroyed || this.#pending.size > 0) return;
      this.#hydrationActive = true;
      const hint = this.#engine.hint.bind(this.#engine);
      for (const region of this.#hydrationRegions) {
        hint(region.offset, region.length, "normal");
      }
    }, this.#options.hydrateIdleMs);
    this.#idleTimer.unref?.();
  }
};
export {
  HEADER_SIZE,
  PieceCache,
  TorrentSource,
  isTorrentFile,
  isTorrentId,
  readLayout,
  torrentDisplayName
};
//# sourceMappingURL=index.js.map