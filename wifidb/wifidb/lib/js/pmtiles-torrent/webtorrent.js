import {
  __name
} from "./chunk-7QVYU63E.js";

// src/engines/webtorrent.ts
var PRIORITY_VALUES = {
  critical: 10,
  high: 5,
  normal: 0
};
function abortError() {
  const error = new Error("The operation was aborted.");
  error.name = "AbortError";
  return error;
}
__name(abortError, "abortError");
function raceAbort(promise, signal) {
  if (!signal) return promise;
  if (signal.aborted) return Promise.reject(abortError());
  return new Promise((resolve, reject) => {
    const onAbort = /* @__PURE__ */ __name(() => reject(abortError()), "onAbort");
    signal.addEventListener("abort", onAbort, { once: true });
    promise.then(
      (value) => {
        signal.removeEventListener("abort", onAbort);
        resolve(value);
      },
      (error) => {
        signal.removeEventListener("abort", onAbort);
        reject(error);
      }
    );
  });
}
__name(raceAbort, "raceAbort");
function deriveKey(torrentId) {
  if (typeof torrentId !== "string") return "torrent:unknown";
  const magnet = /xt=urn:bt[im]h:([a-z0-9]+)/i.exec(torrentId);
  if (magnet) return `torrent:${magnet[1].toLowerCase()}`;
  if (/^[a-f0-9]{40}$/i.test(torrentId)) {
    return `torrent:${torrentId.toLowerCase()}`;
  }
  return `torrent:${torrentId}`;
}
__name(deriveKey, "deriveKey");
function pickFile(torrent, filePath) {
  if (torrent.files.length === 0) throw new Error("torrent contains no files");
  if (filePath) {
    const match = torrent.files.find(
      (file) => file.path === filePath || file.name === filePath
    );
    if (!match) {
      throw new Error(
        `no file "${filePath}" in torrent (has: ${torrent.files.map((f) => f.path).join(", ")})`
      );
    }
    return match;
  }
  const byLength = [...torrent.files].sort((a, b) => b.length - a.length);
  return byLength.find((file) => file.name.endsWith(".pmtiles")) ?? byLength[0];
}
__name(pickFile, "pickFile");
var RESUME_VERSION = 1;
async function resumeFilePath(resumePath, key) {
  const path = await import("path");
  const safe = key.replace(/[^a-z0-9]+/gi, "_").slice(0, 120);
  return path.join(resumePath, `${safe}.resume.json`);
}
__name(resumeFilePath, "resumeFilePath");
async function loadResume(resumePath, key, dataPath) {
  try {
    const [fs, path] = await Promise.all([
      import("fs/promises"),
      import("path")
    ]);
    const file = await resumeFilePath(resumePath, key);
    const saved = JSON.parse(await fs.readFile(file, "utf8"));
    if (saved.version !== RESUME_VERSION) return null;
    if (!saved.dataFile || !saved.bitfield) return null;
    const stat = await fs.stat(path.join(dataPath, saved.dataFile));
    if (stat.size !== saved.dataSize) return null;
    if (Math.floor(stat.mtimeMs) !== Math.floor(saved.dataMtimeMs)) return null;
    return {
      ...saved,
      bitfield: new Uint8Array(Buffer.from(saved.bitfield, "base64"))
    };
  } catch {
    return null;
  }
}
__name(loadResume, "loadResume");
async function saveResume(resumePath, key, torrent, file) {
  try {
    const [fs, path] = await Promise.all([
      import("fs/promises"),
      import("path")
    ]);
    const bitfield = torrent.bitfield?.buffer;
    if (!bitfield) return;
    const dataFile = file.path;
    const stat = await fs.stat(path.join(torrent.path, dataFile));
    await fs.mkdir(resumePath, { recursive: true });
    const target = await resumeFilePath(resumePath, key);
    const body = JSON.stringify({
      version: RESUME_VERSION,
      infoHash: torrent.infoHash,
      numPieces: torrent.pieces.length,
      dataFile,
      dataSize: stat.size,
      dataMtimeMs: Math.floor(stat.mtimeMs),
      bitfield: Buffer.from(bitfield).toString("base64")
    });
    await fs.writeFile(`${target}.tmp`, body);
    await fs.rename(`${target}.tmp`, target);
  } catch {
  }
}
__name(saveResume, "saveResume");
async function loadWebTorrent() {
  try {
    const specifier = "webtorrent";
    const mod = await import(specifier);
    const ctor = mod.default ?? mod;
    if (typeof ctor !== "function") {
      throw new Error("webtorrent module did not export a constructor");
    }
    return ctor;
  } catch (error) {
    throw new Error(
      `WebTorrentEngine requires the optional peer dependency 'webtorrent'. Install it, or pass an existing client via options.client. (${error.message})`,
      { cause: error }
    );
  }
}
__name(loadWebTorrent, "loadWebTorrent");
var WebTorrentEngine = class {
  static {
    __name(this, "WebTorrentEngine");
  }
  key;
  #torrentId;
  #options;
  #client;
  #torrent;
  #file;
  #ownsClient = false;
  #ownsTorrent = true;
  #readyPromise;
  #destroyed = false;
  #resumeTimer;
  constructor(torrentId, options = {}) {
    this.#torrentId = torrentId;
    this.#options = options;
    this.key = deriveKey(torrentId);
  }
  /** The underlying torrent, once metadata has arrived. For swarm stats. */
  get torrent() {
    return this.#torrent;
  }
  ready() {
    if (!this.#readyPromise) this.#readyPromise = this.#start();
    return this.#readyPromise;
  }
  async readRange(offset, length, options = {}) {
    await this.ready();
    const file = this.#file;
    const { signal } = options;
    if (signal?.aborted) throw abortError();
    const end = offset + length - 1;
    const iterator = file[Symbol.asyncIterator]({ start: offset, end });
    const out = new Uint8Array(length);
    let written = 0;
    try {
      while (written < length) {
        const result = await raceAbort(
          Promise.resolve(iterator.next()),
          signal
        );
        if (result.done) break;
        const chunk = result.value;
        const take = Math.min(chunk.byteLength, length - written);
        out.set(chunk.subarray(0, take), written);
        written += take;
      }
    } finally {
      try {
        await iterator.return?.();
      } catch {
      }
    }
    if (written < length) {
      throw new Error(
        `short read from torrent: got ${written} of ${length} bytes at offset ${offset}`
      );
    }
    return out;
  }
  hint(offset, length, priority) {
    const torrent = this.#torrent;
    const file = this.#file;
    if (!torrent || !file || torrent.destroyed || length <= 0) return;
    const first = Math.floor((file.offset + offset) / torrent.pieceLength);
    const last = Math.floor(
      (file.offset + offset + length - 1) / torrent.pieceLength
    );
    torrent.select(first, last, PRIORITY_VALUES[priority]);
    if (priority === "critical") torrent.critical(first, last);
  }
  /**
   * Withdraws a previous hint, so the range stops competing for bandwidth.
   *
   * This only clears non-streaming selections, which is what `hint()` creates —
   * the selections an in-flight read makes for itself are untouched.
   */
  unhint(offset, length) {
    const torrent = this.#torrent;
    const file = this.#file;
    if (!torrent || !file || torrent.destroyed || length <= 0) return;
    const first = Math.floor((file.offset + offset) / torrent.pieceLength);
    const last = Math.floor(
      (file.offset + offset + length - 1) / torrent.pieceLength
    );
    torrent.deselect(first, last);
  }
  async destroy() {
    if (this.#destroyed) return;
    this.#destroyed = true;
    if (this.#resumeTimer !== void 0) {
      clearInterval(this.#resumeTimer);
      this.#resumeTimer = void 0;
    }
    if (this.#options.resumePath && this.#options.path && this.#torrent) {
      await saveResume(
        this.#options.resumePath,
        this.key,
        this.#torrent,
        this.#file
      );
    }
    const torrent = this.#torrent;
    const client = this.#client;
    this.#torrent = void 0;
    this.#file = void 0;
    this.#client = void 0;
    this.#readyPromise = void 0;
    if (this.#ownsClient && client) {
      await new Promise((resolve) => client.destroy(() => resolve()));
      return;
    }
    if (this.#ownsTorrent && torrent && !torrent.destroyed) {
      torrent.destroy({ destroyStore: false });
    }
  }
  /**
   * Periodically persists resume data, so a crash costs at most one interval
   * of re-hashing rather than the whole store.
   */
  #startResumeTimer() {
    if (!this.#options.resumePath || !this.#options.path) return;
    const interval = this.#options.resumeIntervalMs ?? 6e4;
    this.#resumeTimer = setInterval(() => {
      if (this.#torrent && this.#file) {
        void saveResume(
          this.#options.resumePath,
          this.key,
          this.#torrent,
          this.#file
        );
      }
    }, interval);
    this.#resumeTimer.unref?.();
  }
  /** Removes resume data that turned out not to describe this torrent. */
  async #discardResume() {
    try {
      const fs = await import("fs/promises");
      const target = await resumeFilePath(
        this.#options.resumePath,
        this.key
      );
      await fs.rm(target, { force: true });
    } catch {
    }
  }
  async #start() {
    if (this.#destroyed) throw new Error("engine is destroyed");
    const provided = this.#options.client;
    let client;
    if (provided) {
      client = typeof provided === "function" ? await provided() : provided;
    } else {
      const WebTorrent = await loadWebTorrent();
      client = new WebTorrent(this.#options.clientOptions ?? {});
      this.#ownsClient = true;
    }
    this.#client = client;
    const addOptions = {
      // Without this WebTorrent selects every piece and starts downloading the
      // whole archive. We want on-demand ranges only — the point of the
      // exercise is serving a 700 GiB map without holding 700 GiB.
      deselect: true
    };
    if (this.#options.path) addOptions.path = this.#options.path;
    if (this.#options.announce) addOptions.announce = this.#options.announce;
    if (this.#options.maxWebConns) {
      addOptions.maxWebConns = this.#options.maxWebConns;
    }
    let resume = null;
    if (this.#options.resumePath && this.#options.path) {
      resume = await loadResume(
        this.#options.resumePath,
        this.key,
        this.#options.path
      );
      if (resume) addOptions.bitfield = resume.bitfield;
    }
    const timeoutMs = this.#options.readyTimeoutMs ?? 6e4;
    const torrent = await new Promise((resolve, reject) => {
      let settled = false;
      const finish = /* @__PURE__ */ __name((fn) => {
        if (settled) return;
        settled = true;
        clearTimeout(timer);
        fn();
      }, "finish");
      const timer = setTimeout(() => {
        finish(
          () => reject(
            new Error(
              `timed out after ${timeoutMs}ms waiting for torrent metadata (${this.key})`
            )
          )
        );
      }, timeoutMs);
      let added;
      try {
        added = client.add(
          this.#torrentId,
          addOptions,
          (t) => finish(() => resolve(t))
        );
      } catch (error) {
        finish(() => reject(error));
        return;
      }
      added.once("error", (error) => {
        if (/duplicate torrent/i.test(error?.message ?? "")) {
          this.#ownsTorrent = false;
          return;
        }
        finish(() => reject(error));
      });
    });
    this.#torrent = torrent;
    const file = pickFile(torrent, this.#options.filePath);
    this.#file = file;
    if (resume && resume.infoHash !== torrent.infoHash) {
      await this.#discardResume();
      this.#torrent = void 0;
      this.#file = void 0;
      torrent.destroy({ destroyStore: false });
      throw new Error(
        `resume data for ${this.key} describes torrent ${resume.infoHash}, not ${torrent.infoHash}; discarded, retry to verify from disk`
      );
    }
    this.#startResumeTimer();
    return {
      infoHash: torrent.infoHash,
      pieceLength: torrent.pieceLength,
      numPieces: torrent.pieces.length,
      fileLength: file.length,
      fileOffset: file.offset,
      name: file.name
    };
  }
};
export {
  WebTorrentEngine
};
//# sourceMappingURL=webtorrent.js.map