# Serving map archives over BitTorrent — where this stands

Written 2026-08-14, at the end of the session that got the browser side working.
Updated the same day with the toggle and the auto-detect default. Read this
before touching the swarm code; most of it is things that are not visible from
the code itself.

## What it does

WifiDB generates PMTiles archives per bucket. pmtiles-swarm distributes them
between nodes over BitTorrent and serves tiles from them. As of today a
**browser** also reads them out of the swarm over WebRTC, instead of fetching
every tile from the server.

Two nodes: **http-01** (generates) and **http-02** (mirrors). `data.wifidb.net`
is the swarm's public face. `swarm.wifidb.net` is a *different* node serving the
basemap — several errors on the map page come from there and are unrelated to
anything here.

## Working, verified

- Settings reach `$dbcore`, the `swarm_archives` cache holds all 20 buckets,
  and `swarm_index.php --refresh` records them.
- The map's sources are `https://data.wifidb.net/latest/<category>/tiles.json`
  with the magnet in the fragment. They follow the newest build rather than
  naming a file.
- `swarm.js` joins archives by infohash and MapLibre's `transformRequest`
  rewrites tile URLs to `pmtiles://<infohash>/z/x/y` for archives that joined,
  leaving everything else alone.
- **14 of 20 archives join and serve from pieces**, the largest at 1281 pieces.
  The rest fall back to HTTP, which is the designed behaviour.

## Written but not yet seen on the live box

The toggle and the auto-detect default, below. Tested offline and nothing more
than that: the preference rules, the toggle's lifecycle, and both templates
rendering and producing valid JS in every mode. No part of it has faced a real
swarm, a real browser, or the live site. Treat "works" here as "the logic does
what it says", not as a deployment report.

### The toggle

A `Swarm: On`/`Swarm: Off` button at the end of the map's control bar, after
`Show Heatmap`, with its readout on the line below —
`13/20 archives · 11 seeds · 3 peers · 14 web · 6.1 MB in`, refreshed every two
seconds. Both sit inside the `#all_controls` span, which is the whole of why
they collapse with the other buttons: `control_toggle()` puts `display: none` on
that span and everything under it goes. Anything added here belongs inside it
for the same reason.

The toggle takes effect on the next tile request rather than on a reload, so pan
or zoom to see it; the tiles already drawn stay as they were fetched.

**Every connection is counted in exactly one of three buckets, and that is the
point of the readout.** WebTorrent's `numPeers` is just `wires.length`, and a
web seed holds a wire like anything else — so a page pulling every byte over
HTTP from the generating node reports the same healthy count as one genuinely
trading pieces with browsers. `wire.type === 'webSeed'` separates those, and
`wire.isSeeder` splits the rest into **seeds** (hold the whole archive) and
**peers** (hold part). Seeds and peers are the two figures that mean the swarm
is doing what it exists to do; `web` alone means it is not.

All three are shown even at zero, and so are the bytes. That is deliberate: a
figure that vanishes when it is zero looks exactly like a readout that has
stopped refreshing.

### The default rule

`tile_swarm_browser` is now four states rather than a flag, because "is the
feature offered" and "is it on" stopped being one question once there was a
button: `off` (0), `manual`, `auto`, `on` (1). `off` is still the hard off
switch — no sources rendered, nothing imported, the map exactly as it was.

Detection lives in `lib/js/wifidb/swarm-control.js` and asks the inverse of the
obvious question. Not "are they on wifi" — nothing reliably answers that —
but "is there a reason not to": `saveData`, then `type === 'cellular'`, then
`effectiveType` of `2g`/`slow-2g`. Anything left is **unknown, and unknown
enables the swarm**. That is the load-bearing choice: every Firefox and Safari
visitor lands in unknown, so treating it as metered would mean `auto` never
fires outside Chromium at all.

Precedence is **URL → stored choice → auto-detect → config**, and a URL override
is deliberately not remembered, so `&swarm=1` against `&swarm=0` still compares
what it claims to.

`auto` also listens for `connection`'s `change` event, so walking into wifi
turns the swarm on mid-session and walking out turns it off. Pressing the button
ends that: an explicit choice is remembered in `localStorage` and auto stops
second-guessing it.

## Open, roughly in order of value

1. **None of the above has run on the live site.** First thing to do.
   `?swarm=1` and `?swarm=0` still force the matter, so the toggle can be
   compared against both.
2. **Six buckets do not join** — `daily`, `weekly`, `0to1year`, `cell_daily`,
   `cell_weekly`, `cell_monthly`. All small and frequently rebuilt. Unknown
   whether they are slow to find a peer or have none. `?swarmwait=300` was
   added to answer exactly this and had not been confirmed working at the time
   of writing. The readout now makes this cheaper to watch: `n/20 archives`
   climbs as they land, so a bucket that is merely slow looks different from one
   that never arrives.
3. **Anonymous visitors get no swarm.** A logged-out request renders no sources.
   Never established whether that is intended.
4. **`FileETag MTime Size`** — the original thread. Blocked all along because
   `tile_archive_generate` never reached `$dbcore`, so http-02 never stopped
   generating its own archives. Now genuinely unblocked: let a build cycle
   through, confirm mtimes agree across both nodes, then change the vhost.
5. **The basemap node** — `409 not probed` on `/latest/openmaptiles/tiles.json`
   and `400`s on its tile endpoint. Every page load. Not ours.

## Traps, all of which cost hours today

**Silent fallbacks are the house style, and they hide misconfiguration.**
Every reader is `isset($dbcore->x) ? … : default` and every cache read is
wrapped in a `catch` returning null. So a setting that never arrives, a table
that cannot be read, and a table that is empty are *indistinguishable at the
point of use*. Four separate dead ends today were this. Reach for the
instrumentation first:

- `swarm_index.php --check` — prints what the CLI resolved and from where
- `?swarmdebug=1` — the same for a web request, to the browser console
- `mvt_swarm_last_error()` — why the last cache read gave up

`?swarmdebug=1` prints `swarmSetting` and `swarmMode` side by side for the same
reason. An unrecognised `tile_swarm_browser` resolves to `off`, which is the
safe answer and a silent one: a typo there is indistinguishable from the feature
being switched off on purpose, and those two lines are what tells them apart.

**`dbcore.inc.php` maps config keys one at a time.** A setting not in that list
does not exist as far as the code is concerned, and nothing warns. Adding a
`tile_*` setting means adding it there too. This is why the four swarm modes
are values of the existing `tile_swarm_browser` rather than a second key beside
it — the same reason is worth applying to the next setting that is tempted.

**Everything under `/srv/www` is synced between nodes**, including
`daemon.config.inc.php`. So `wifidb_nodename` reads the same on both, and
nothing in a config file can distinguish them. `tile_archive_generate` therefore
takes a node name compared against the **hostname**, which rsync cannot copy.

**Smarty compiles templates and caches them.** An upload that preserves
timestamps leaves the compiled copy looking newer, and the change does not take.
`rm -rf smarty/templates_c/*` when a `.tpl` edit appears to do nothing.

**Smarty's `if` grammar is narrower than it looks.** `{if ($x|default:0) > 0}`
does not parse — a modifier inside parentheses compared to a literal — and fails
by silently rendering nothing.

**`curl` does not see what the browser sees.** No session, and the default theme
is `vistumbler_mobile` while a logged-in browser gets `vistumbler_classic`.
Several hours were spent on a deployment problem that did not exist because of
this.

**WebTorrent's client has no `downloaded` or `uploaded`.** They are on
*Torrent*, along with `received`; the client gets only `progress`, `ratio`,
`downloadSpeed` and `uploadSpeed`. Reading `client.downloaded` returns
`undefined` rather than throwing, so the first readout shipped with a byte
counter that could never display a byte — and since it was hidden when falsy,
it looked like a swarm moving no data rather than a property that does not
exist. Sum the torrents. This was caught from a screenshot showing peers but no
bytes, which is worth remembering as the shape of the tell.

**The database is SQL Server.** MySQL DDL will not run. `ON DUPLICATE KEY
UPDATE`, `IF()`, and multiple `ADD` clauses in one `ALTER` are all MySQL-only.
`mvt_swarm_record_archive()` reads-then-updates-or-inserts for this reason.

## Timing, and the bug worth remembering

`swarm.js` had a batch deadline that **released archives that joined after it**.
Archives were taking 10–30s to join; the deadline was 8s. So the joins were
succeeding all along and being discarded a second before they landed, and it
read as "nothing joinable". It is now correct to keep them, because
`transformRequest` is consulted on every tile request — an archive joining at
twenty seconds simply starts serving from then on. That was not true when the
binding lived in the style's source URL, which is resolved once.

The lesson generalises: a timeout that discards successful work reports it as
failure, and looks identical to the work never happening.

**The same mistake was still there in a second place** and has since been
fixed. When nothing had joined *by the deadline*, `enableSwarm` destroyed the
client outright — so on a page where every archive was slow rather than absent,
the eight-second deadline tore down the client twenty seconds before the joins
would have landed, and the late-join path that was written to keep them could
never run. The check now waits for every join to actually settle before
concluding there is nothing to join. Worth knowing if the six buckets in the
open list turn out to have been arriving all along.

## Deploying

There is no build step. Files are copied to the server and picked up. The ones
that matter here:

    lib/dbcore.inc.php              lib/mvt.inc.php
    lib/createGeoJSON.inc.php       lib/js/wifidb/swarm.js
    opt/map.php                     lib/js/wifidb/swarm-control.js
    tools/daemon/swarm_index.php    tools/cron/link_archive
    tools/cron/update_swarm_index

    themes/vistumbler_classic/templates/map.tpl
    themes/vistumbler_classic/styles.css
    themes/vistumbler_mobile/templates/map.tpl
    themes/vistumbler_mobile/html5style.css

Both themes, every time — and note the stylesheets are not the same filename in
the two of them. Clear `templates_c` after a `.tpl` change.

`swarm-control.js` is new and needs its import-map entry, which is in
`opt/map.php`; a bare specifier with nothing mapped to it fails at module load
and takes the whole map script with it, so deploy the two together.
