# Vendored browser libraries

Copied verbatim from npm — do not edit. Each keeps its own LICENSE alongside it.

`wifidb/` is the exception: WifiDB's own modules, under this repository's
licence, and the only directory here that may be edited.

| directory | package | version |
|---|---|---|
| `maplibre-gl-inspect/` | `@maplibre/maplibre-gl-inspect` | 1.8.2 |
| `fflate/` | `fflate` | 0.8.3 |
| `maplibre-contour/` | `maplibre-contour` | 0.1.0 |
| `maplibre-gl/` | `maplibre-gl` | 6.3.0 |
| `pmtiles/` | `pmtiles` | 4.5.0 |
| `pmtiles-torrent/` | `pmtiles-torrent` | 0.4.4 |
| `webtorrent/` | `webtorrent` | 3.0.21 |

## The swarm libraries

`pmtiles-torrent/` and `webtorrent/` are loaded only when `tile_swarm_browser`
is on and there are archives to join — the import in `map.tpl` is inside a
Smarty conditional for that reason. WebTorrent alone is 220 KB, which is not
worth spending on every map view for a feature that is off.

`pmtiles-torrent` ships `index.js`, `webtorrent.js` and the `chunk-*.js` that
both import by relative path. All three have to stay in this directory, and the
chunk's name changes with its contents, so copy the set rather than individual
files. These came from the sibling `pmtiles-torrent` repository — `npm run
build` there, then copy `dist/esm/`.

## Why ESM, and why an import map

maplibre-gl 6 dropped the UMD build: `dist/` contains only `.mjs`, so there is
no `maplibregl` global and anything using it must be a module. `pmtiles`
imports `fflate` by bare specifier, which a browser cannot resolve on its own —
hence the import map emitted by `opt/map.php`, which maps each bare name to the
file here. Mapping rather than rewriting keeps these copies byte-identical to
what npm published, so refreshing one is a copy rather than a merge.

`maplibre-gl.mjs` loads `maplibre-gl-shared.mjs` and `maplibre-gl-worker.mjs`
by relative path at runtime. All three have to stay in the same directory.

## Refreshing

    npm install maplibre-gl@6 pmtiles@4 fflate maplibre-contour \
        @maplibre/maplibre-gl-inspect

then copy the files named in the table above out of `node_modules`. Check that
the import map in `opt/map.php` still names the right entry file for each.
