<?php
/*
mvt.inc.php — Mapbox Vector Tile encoding library
Copyright (C) 2024 Andrew Calcutt

Shared by:
  tools/daemon/mvtd.php   — pre-generation daemon (requires via $daemon_config['wifidb_install'])
  wifidb/api/mvt.php      — on-demand tile endpoint  (includes via '../lib/mvt.inc.php')

Contains:
  • Raw Protobuf encoder (wire-type helpers, packed varints)
  • MVT layer/feature/tile message encoders
  • Web-Mercator tile coordinate helpers
  • NMEA Degrees-Minutes converter
  • Bucket date-window calculator

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

// ── Protobuf encoder ──────────────────────────────────────────────────────────
// Wire types: 0=varint, 2=length-delimited

function pb_varint(int $n): string {
    $buf = '';
    do {
        $byte = $n & 0x7F;
        $n    = ($n >> 7) & 0x1FFFFFFFFFFFFFF;  // unsigned right-shift (keep positive)
        if ($n > 0) $byte |= 0x80;
        $buf .= chr($byte);
    } while ($n > 0);
    return $buf;
}

/** Zigzag-encode a signed integer to unsigned for sint32 fields. */
function pb_zigzag(int $n): int {
    return ($n >= 0) ? ($n << 1) : (((-$n - 1) << 1) | 1);
}

/** Field tag + wire type 0 (varint). */
function pb_field_varint(int $field, int $val): string {
    return pb_varint(($field << 3) | 0) . pb_varint($val);
}

/** Field tag + wire type 2 (length-delimited bytes). */
function pb_field_bytes(int $field, string $data): string {
    return pb_varint(($field << 3) | 2) . pb_varint(strlen($data)) . $data;
}

/** Convenience: encode a length-delimited string field. */
function pb_field_string(int $field, string $s): string {
    return pb_field_bytes($field, $s);
}

/** Encode a repeated packed varint field (wire type 2, concatenated varints). */
function pb_packed_varints(int $field, array $ints): string {
    $data = '';
    foreach ($ints as $n) $data .= pb_varint($n);
    return pb_field_bytes($field, $data);
}

// ── MVT helpers ───────────────────────────────────────────────────────────────

/** Tile pixel extent — 4096×4096 coordinate space per tile. */
define('MVT_EXTENT', 4096);

/**
 * Project a lat/lon to integer pixel coordinates within a tile.
 * Uses Web-Mercator (EPSG:3857).  Returns [x, y] clamped to [0, MVT_EXTENT].
 */
function project_to_tile(float $lat, float $lon, int $z, int $tx, int $ty): array {
    $n  = pow(2.0, $z);
    $px = ($lon + 180.0) / 360.0 * $n;
    $py = (1.0 - log(tan(deg2rad($lat)) + 1.0 / cos(deg2rad($lat))) / M_PI) / 2.0 * $n;
    $lx = (int)(($px - $tx) * MVT_EXTENT);
    $ly = (int)(($py - $ty) * MVT_EXTENT);
    return [max(0, min(MVT_EXTENT, $lx)), max(0, min(MVT_EXTENT, $ly))];
}

/**
 * Encode a single POINT Feature message.
 * $tags = flat [key_idx, val_idx, key_idx, val_idx, ...] array.
 */
function mvt_encode_point_feature(int $id, int $px, int $py, array $tags): string {
    $data  = pb_field_varint(1, $id);
    $data .= pb_packed_varints(2, $tags);
    $data .= pb_field_varint(3, 1);  // type = POINT
    // Geometry: MoveTo cmd (1, count=1 → 9), then zigzag dx/dy from tile origin.
    $data .= pb_packed_varints(4, [9, pb_zigzag($px), pb_zigzag($py)]);
    return $data;
}

/**
 * Encode a complete MVT Layer message.
 * $features     = array of raw Feature message byte strings
 * $keys         = ordered property name strings
 * $values_bytes = ordered raw Value message byte strings
 */
function mvt_encode_layer(string $name, array $features, array $keys, array $values_bytes): string {
    $data  = pb_field_varint(15, 2);               // version = 2
    $data .= pb_field_string(1, $name);
    foreach ($features     as $f) $data .= pb_field_bytes(2, $f);
    foreach ($keys         as $k) $data .= pb_field_string(3, $k);
    foreach ($values_bytes as $v) $data .= pb_field_bytes(4, $v);
    $data .= pb_field_varint(5, MVT_EXTENT);
    return $data;
}

/** Encode a complete MVT Tile message containing one layer. */
function mvt_encode_tile(string $layer_bytes): string {
    return pb_field_bytes(3, $layer_bytes);
}

// ── Tile coordinate helpers ───────────────────────────────────────────────────

/** Tile x index for a given longitude and zoom. */
function lon_to_tile_x(float $lon_dd, int $z): int {
    return (int)floor(($lon_dd + 180.0) / 360.0 * pow(2, $z));
}

/** Tile y index for a given latitude and zoom (y increases southward). */
function lat_to_tile_y(float $lat_dd, int $z): int {
    $lat_rad = deg2rad($lat_dd);
    return (int)floor((1.0 - log(tan($lat_rad) + 1.0 / cos($lat_rad)) / M_PI) / 2.0 * pow(2, $z));
}

/**
 * Decimal-degree bounding box of a tile.
 * Returns [lat_min, lat_max, lon_min, lon_max].
 */
function tile_bounds_dd(int $z, int $x, int $y): array {
    $n       = pow(2.0, $z);
    $lon_min = $x       / $n * 360.0 - 180.0;
    $lon_max = ($x + 1) / $n * 360.0 - 180.0;
    $lat_max = rad2deg(atan(sinh(M_PI * (1.0 - 2.0 * $y       / $n))));
    $lat_min = rad2deg(atan(sinh(M_PI * (1.0 - 2.0 * ($y + 1) / $n))));
    return [$lat_min, $lat_max, $lon_min, $lon_max];
}

// ── NMEA coordinate converter ─────────────────────────────────────────────────

/**
 * Convert decimal degrees to NMEA Degrees-Minutes (DDMM.MMMM storage format).
 * wifi_gps stores lat/lon in DM format (e.g. 42°13.71' → 4213.7100).
 */
function dd2dm(float $dd): float {
    $sign    = ($dd < 0) ? -1 : 1;
    $abs     = abs($dd);
    $degrees = (int)$abs;
    $minutes = ($abs - $degrees) * 60.0;
    return $sign * ($degrees * 100 + $minutes);
}

// ── Bucket date windows ───────────────────────────────────────────────────────

/**
 * Return [start_date, end_date] strings (Y-m-d H:i:s, UTC) for a bucket.
 * Either may be null for open-ended ranges (daily has no end, 10yrplus has no start).
 * Mirrors the intervals used across mvtd.php, mvt.php, and geojsond.php.
 *
 * Buckets (newest → oldest):
 *   daily      last 36 hours
 *   weekly     36 h – 7 days
 *   monthly    7 days – 1 month
 *   0to1year   1 month – 1 year
 *   1to2year   1 – 2 years
 *   2to3year   2 – 3 years
 *   3to5year   3 – 5 years   (post-spike slump, ~2021–2023)
 *   5to10year  5 – 10 years  (growth ramp + 2021 spike, ~2016–2021)
 *   10yrplus   10+ years     (sparse pre-2016 data)
 *   legacy     alias → same as 3to5year + 5to10year + 10yrplus combined (3yr+),
 *              kept for backward-compatibility with opt/map.php and other callers.
 *   heatmap    unbounded — every age combined into one source, for the
 *              all-ages heatmap layer (per-feature recency is carried via
 *              the 'age_days' tag instead of a bucket date window).
 */
/**
 * Every bucket the daemons generate, AP buckets then their cell counterparts
 * then the two combined heatmaps.
 *
 * The daemons iterate this, and opt/map.php walks it to work out which archives
 * a browser could join.  One list, because a bucket present in one place and
 * absent from another is not a visible error: it is an archive that quietly
 * never generates, or a source the page never offers.
 *
 * 'legacy' is deliberately absent.  It is an alias kept for callers that still
 * ask for it by name, not a bucket anything generates.
 */
function mvt_buckets(): array {
    return [
        'daily', 'weekly', 'monthly', '0to1year', '1to2year', '2to3year',
        '3to5year', '5to10year', '10yrplus',
        'cell_daily', 'cell_weekly', 'cell_monthly', 'cell_0to1year',
        'cell_1to2year', 'cell_2to3year', 'cell_3to5year', 'cell_5to10year',
        'cell_10yrplus',
        'heatmap', 'cell_heatmap',
    ];
}

/**
 * How a bucket is stored: 'dir' for a flat .pbf tree, 'pmtiles' for a single
 * archive.
 *
 * Read by mvtd.php when writing and by api/mvt.php when serving, so that one
 * answer governs both.  Were they to disagree, the daemon would write an
 * archive the endpoint never looks in, and the endpoint would fall through to
 * a live query — which is the case this split exists to prevent.
 *
 * The line falls where api/mvt.php stops being able to answer correctly.  Its
 * per-tile query is capped by $query_limit; for a window of a day or a week
 * the result set stays under the cap, so a missing tile can be filled live and
 * cached.  Past a week the cap starts truncating, and the tile comes back
 * promptly and quietly wrong — worse than not coming back at all.  Those
 * buckets have to be complete before anything asks, and a bucket that is
 * always generated whole gains nothing from being millions of separate files.
 */
function mvt_bucket_output($bucket, $dbcore = null): string {
    // Which buckets stay a flat .pbf tree. Empty by default: every bucket
    // produces an archive, so every bucket has one complete, self-describing
    // artefact that api/mvt.php can answer from without touching the database.
    //
    // Generating an archive is not the same as seeding one. pmtiles-swarm
    // selects what it publishes by filename glob, so a bucket rebuilt hourly
    // can have an archive on disk and no torrent at all -- which is what keeps
    // daily out of the swarm without keeping it out of the archive tier.
    $flat = ['daily' => false, 'weekly' => false, 'cell_daily' => false, 'cell_weekly' => false];
    if ($dbcore !== null && isset($dbcore->tile_flat_buckets) && $dbcore->tile_flat_buckets !== '') {
        $flat = [];
        foreach (explode(',', $dbcore->tile_flat_buckets) as $name) {
            $flat[trim($name)] = true;
        }
    }
    return !empty($flat[$bucket]) ? 'dir' : 'pmtiles';
}

/**
 * Where generated tiles live, keyed 'tiles', 'archives', 'tiles_mlt' and
 * 'archives_mlt'.
 *
 * Defaults to the install directory, which is right when generation runs on
 * the web server.  The tile_*_dir settings in config.inc.php move them when it
 * does not.  MVT and MLT archives are kept apart rather than distinguished by
 * filename, so that one format's build can never half-overwrite the other's.
 */
function mvt_tile_dirs($dbcore): array {
    $install = rtrim($dbcore->PATH, '/');
    $pick = function (string $setting, string $fallback) use ($dbcore, $install): string {
        return (isset($dbcore->$setting) && $dbcore->$setting !== '')
            ? rtrim($dbcore->$setting, '/')
            : $install . $fallback;
    };
    return [
        'tiles'        => $pick('tile_output_dir',      '/out/tiles'),
        'archives'     => $pick('tile_archive_dir',     '/out/pmtiles'),
        'tiles_mlt'    => $pick('tile_mlt_output_dir',  '/out/tiles-mlt'),
        'archives_mlt' => $pick('tile_mlt_archive_dir', '/out/pmtiles-mlt'),
    ];
}

/**
 * The pmtiles-swarm category a bucket's archive is published under, or null
 * when no swarm is configured.
 *
 * Bucket names use underscores and swarm categories are a flat namespace
 * shared with everything else that node carries, so 'cell_monthly' becomes
 * 'wifidb-cell-monthly': prefixed so it cannot collide with an unrelated
 * 'monthly', and hyphenated to read as a category rather than a variable.
 */
function mvt_swarm_category($dbcore, string $bucket): ?string {
    if (!isset($dbcore->tile_swarm_url) || $dbcore->tile_swarm_url === '') {
        return null;
    }
    $prefix = isset($dbcore->tile_swarm_category_prefix) && $dbcore->tile_swarm_category_prefix !== ''
        ? $dbcore->tile_swarm_category_prefix
        : 'wifidb-';
    return $prefix . str_replace('_', '-', $bucket);
}

/**
 * The stable URL for a bucket's current archive, or null when no swarm is
 * configured.
 *
 * This is the one document in the chain that moves.  It resolves to the newest
 * archive in the category and points at immutable, content-addressed tile URLs
 * underneath, so a style can hold this URL across rebuilds and the tiles it
 * hands out stay cacheable for a year.  Its body also carries the archive's
 * torrent block — infohash and magnet — which is how a torrent-aware client
 * joins the swarm without WifiDB ever having to know the infohash.
 */
function mvt_swarm_tilejson_url($dbcore, string $bucket, bool $with_magnet = true): ?string {
    $category = mvt_swarm_category($dbcore, $bucket);
    if ($category === null) {
        return null;
    }

    $url = rtrim($dbcore->tile_swarm_url, '/')
        . '/latest/' . rawurlencode($category) . '/tiles.json';

    if (!$with_magnet) {
        return $url;
    }

    // The magnet in the fragment, which is what makes one URL enough.  A
    // fragment is never sent in a request, so an ordinary client fetches this
    // document over HTTP and never sees it, while a swarm-aware one has
    // something to join before it makes any call at all -- and can therefore
    // still start when this server cannot answer.
    $magnet = mvt_swarm_magnet($dbcore, $bucket);
    return $magnet === null ? $url : $url . '#' . $magnet;
}

/**
 * A pmtiles:// URL for a bucket's archive, with the mutable magnet in the
 * fragment, or null when no public archive URL is configured.
 *
 * MapLibre GL JS reads pmtiles:// natively through the PMTiles protocol: it
 * range-reads the archive and needs no tile endpoint at all.  The fragment is
 * inert to it — verified against the protocol's own URL handling, which
 * recovers the archive URL unchanged and never sends a fragment in a request —
 * so a plain client works today with stock pmtiles.js, while a torrent-aware
 * client can read the magnet and pull from the swarm instead.  That is the same
 * degradation as the tiles.json form, one layer lower down.
 *
 * Points at the stable filename rather than the dated one, because a style
 * holds this URL across rebuilds.  The cost is that the bytes behind it change:
 * pmtiles.js compares ETags between range requests and retries once when they
 * differ, which covers a build landing mid-read.  It does NOT cover two nodes
 * serving the same archive under different ETags — see the .htaccess in the
 * archive directory, and pin readers to one node for this path.
 */
function mvt_archive_pmtiles_url($dbcore, string $bucket): ?string {
    if (!isset($dbcore->tile_archive_url) || $dbcore->tile_archive_url === '') {
        return null;
    }
    if (mvt_bucket_output($bucket, $dbcore) !== 'pmtiles') {
        return null;
    }

    $url = 'pmtiles://' . rtrim($dbcore->tile_archive_url, '/') . '/' . $bucket . '.pmtiles';

    $magnet = mvt_swarm_magnet($dbcore, $bucket);
    return $magnet === null ? $url : $url . '#' . $magnet;
}

/**
 * What a browser needs in order to read the archives out of the swarm instead
 * of over HTTP: one entry per bucket that has both an archive URL and a swarm
 * category, empty when either is unconfigured.
 *
 * `key` is the style's source URL with `pmtiles://` removed, because that is
 * the exact string the PMTiles protocol keys its source registry on — derived
 * here from the same function that builds the URL, so the two cannot drift
 * apart into the silent HTTP fallback a mismatched key produces.
 *
 * `tilejson` is the swarm's per-category document rather than anything WifiDB
 * serves, and it stays the client's source of truth even though the fragment
 * on `key` now carries a joinable infohash too.  The document says which build
 * is current; the fragment says which one was current when the page was
 * rendered, which is a fallback for a client that cannot fetch the document
 * rather than a substitute for doing so.  Preferring the fragment would mean
 * serving tiles from a superseded build for as long as anybody still seeds it.
 * See lib/js/wifidb/swarm.js.
 */
function mvt_swarm_browser_sources($dbcore): array {
    $sources = [];
    foreach (mvt_buckets() as $bucket) {
        $tilejson = mvt_swarm_tilejson_url($dbcore, $bucket);
        if ($tilejson === null) {
            continue;
        }

        $cached = mvt_swarm_cached_archive($dbcore, $bucket);
        $infohash = $cached === null
            ? null
            : strtolower(trim((string)$cached['infohash']));
        if ($infohash === null || !preg_match('/^[0-9a-f]{40}$/', $infohash)) {
            // Nothing cached for this bucket yet.  The source is still worth
            // offering -- it reads over HTTP like any other -- but there is
            // nothing for the browser to join, so it is not listed here.
            continue;
        }

        $sources[] = [
            'bucket'   => $bucket,
            'infohash' => $infohash,
            'tilejson' => $tilejson,
        ];
    }
    return $sources;
}

/**
 * The stable path to a bucket's current archive — the hard link every build
 * repoints, so callers never have to list the directory or know the date.
 */
function mvt_archive_file(string $root, string $bucket): string {
    return rtrim($root, '/') . '/' . $bucket . '.pmtiles';
}

/**
 * Whether this node generates archives, as opposed to receiving them.
 *
 * Two nodes running the same daemons against the same database do not produce
 * the same archive: the attribution and 'generated' fields carry the run's
 * date, and age_days is measured from the moment of the scan.  Different bytes
 * mean a different infohash, so each node would seed its own swarm for the same
 * bucket, share nothing, publish competing BEP 46 records under the same salt,
 * and serve ETags that disagree — which breaks a range-reading client the
 * moment the load balancer moves it between them.
 *
 * So exactly one node in a pair generates archives and the rest subscribe to
 * it.  Set tile_archive_generate to false on the others; their daemons then
 * skip archived buckets before the database scan rather than after it, and the
 * endpoints go on serving whatever the mirror has delivered.  Flat buckets are
 * unaffected and keep generating everywhere, because per-tile files are never
 * compared between nodes.
 *
 * @param string $which 'mvt' or 'mlt'.
 */
function mvt_generates_archives($dbcore, string $which = 'mvt'): bool {
    $setting = $which === 'mlt'
        ? ($dbcore->tile_mlt_archive_generate ?? 0)
        : ($dbcore->tile_archive_generate ?? 1);

    // A node name rather than a flag, which is what makes this usable when the
    // configuration is synced between nodes and therefore cannot say different
    // things on each.
    //
    //   'tile_archive_generate' => 'http-01'
    //   'tile_archive_generate' => 'http-01, http-03'
    //
    // The alternative in a synced setup is to leave the flag on everywhere and
    // rely on the second node simply not having a cron entry.  That works, and
    // it is invisible: nothing on that node says it must not generate, so the
    // day somebody adds the entry for an unrelated reason, two nodes start
    // building the same buckets independently -- different bytes, different
    // infohashes, competing BEP 46 records under one salt, and ETags that
    // disagree.  Naming the node puts the intent where it can be read.
    if (is_string($setting) || is_array($setting)) {
        $allowed = is_array($setting)
            ? $setting
            : preg_split('/[\s,]+/', trim($setting), -1, PREG_SPLIT_NO_EMPTY);

        // The hostname first, because it is the only identity here that cannot
        // be synced away.  wifidb_nodename lives in daemon.config.inc.php,
        // which sits under the tree rsync copies between nodes -- so on a
        // synced pair both machines report the same one, and comparing against
        // it would answer the same on each, which is the entire thing this
        // form exists to avoid.  It is still accepted, for installs where it
        // is genuinely distinct.
        $names = array_filter([
            gethostname() ?: null,
            isset($dbcore->node_name) ? (string)$dbcore->node_name : null,
        ]);

        foreach ($names as $name) {
            if (in_array($name, $allowed, true)) {
                return true;
            }
        }

        // Nothing matched, including the case where this node has no identity
        // to offer at all.  No rather than yes: generating where it was not
        // asked for is the failure that is expensive to undo, and the daemon
        // says plainly which it decided.
        return false;
    }

    return (bool)$setting;
}

/**
 * Moves a finished archive into place under a dated name, points the bucket's
 * stable name at it, and retires older builds.  Returns the dated path, or
 * null on failure.
 *
 * Why dated rather than replacing one file: pmtiles-swarm's watcher acts on
 * chokidar's `add` event only.  Renaming over an existing watched file fires
 * `change`, not `add`, so a stable filename is imported once and every later
 * build is silently ignored — the archive on disk moves on, the endpoint
 * serves the new data, and the swarm goes on seeding the first build with
 * nothing to indicate it.  A new name each build is what makes the import fire.
 *
 * Why a stable name as well: api/mvt.php and api/mlt.php need a path they can
 * form without listing the directory, and a hard link costs nothing and points
 * at the same bytes.  Set the watch folder's `latestLink` to this name so the
 * watcher skips it — otherwise it is imported as a second archive of the same
 * bytes, under a name that changes every build.
 *
 * @param string $tmp    The .building file to publish.
 * @param string $dir    Archive directory.
 * @param string $bucket Bucket name, used for both filenames.
 * @param int    $keep   How many dated builds to retain, newest first.
 */
function mvt_publish_archive(string $tmp, string $dir, string $bucket, int $keep = 2): ?string {
    $dated  = $dir . '/' . $bucket . '-' . date('Ymd') . '.pmtiles';
    $stable = $dir . '/' . $bucket . '.pmtiles';

    // Same directory, so this is atomic: a reader holds either the whole
    // previous build or the whole new one.
    if (!rename($tmp, $dated)) {
        return null;
    }

    // Relink the stable name. Unlink first because link() will not overwrite,
    // which leaves a brief window where the stable name is absent — hence the
    // endpoints answering 503 rather than falling through to a live query.
    if (file_exists($stable) || is_link($stable)) {
        @unlink($stable);
    }
    if (!@link($dated, $stable)) {
        // Hard links fail across filesystems and on some network shares.
        // A copy costs the space but keeps the stable name working.
        @copy($dated, $stable);

        // copy() does not carry the timestamp over, and a hard link does — so
        // without this the stable name's mtime depends on which of the two
        // paths was taken. Apache derives its ETag partly from mtime, and
        // pmtiles-swarm goes to some trouble to make a mirror's copy of an
        // archive carry the origin's, precisely so two nodes serving the same
        // bytes serve the same validator. A fresh timestamp here throws that
        // away for the one name clients actually read.
        $built = @filemtime($dated);
        if ($built !== false) {
            @touch($stable, $built);
        }
    }

    // Retire older builds, newest kept. Sorting by name works because the
    // suffix is YYYYMMDD, which is why it is not a locale-formatted date.
    $builds = glob($dir . '/' . $bucket . '-[0-9]*.pmtiles') ?: [];
    rsort($builds);
    foreach (array_slice($builds, max(1, $keep)) as $old) {
        @unlink($old);
    }

    return $dated;
}

/**
 * Records what the swarm currently holds for a bucket.
 *
 * Upserts, and only moves `updated_at` when the infohash actually changed —
 * which is what makes "this build is three weeks old" distinguishable from
 * "we last managed to ask three weeks ago".  `checked_at` always moves, so a
 * silent refresher and a broken one look different.
 *
 * Returns true when something was written, false when it could not be.  A
 * failure is never fatal: the caller's job is to keep the cache warm, and a
 * cache that cannot be written is a cache that keeps its last value, which is
 * the behaviour that matters.
 */
function mvt_swarm_record_archive($dbcore, string $bucket, array $archive): bool {
    $infohash = strtolower(trim((string)($archive['infohash'] ?? '')));
    if (!preg_match('/^[0-9a-f]{40}$/', $infohash)) {
        return false;
    }
    if (!preg_match('/^[a-z0-9_]+$/', $bucket)) {
        return false;
    }

    $category     = (string)($archive['category'] ?? '');
    $magnet       = isset($archive['magnet']) ? (string)$archive['magnet'] : null;
    $style_url    = isset($archive['style_url']) ? (string)$archive['style_url'] : null;
    $name         = isset($archive['name']) ? (string)$archive['name'] : null;
    $size         = isset($archive['size']) ? (int)$archive['size'] : null;
    $built_at     = isset($archive['built_at']) ? (string)$archive['built_at'] : null;

    // Read, then update or insert, rather than ON DUPLICATE KEY UPDATE or
    // MERGE.  This branch runs on both MySQL and SQL Server and those spell an
    // upsert differently — as they do IF() and COALESCE-over-VALUES(), both of
    // which this needed.  Deciding in PHP keeps one statement pair that either
    // engine will take, and makes the two rules below readable rather than
    // buried in dialect.
    $existing = mvt_swarm_cached_archive($dbcore, $bucket);

    // Only the fields this caller actually knows.  One that has the infohash
    // and nothing else — the onComplete hook, handed a path and a category and
    // never a magnet — must not blank what the poller filled in.  Recording
    // what you know should never erase what somebody else knew.
    $known = array(
        'category'       => $category !== '' ? $category : null,
        'magnet'         => $magnet,
        'style_url'      => $style_url,
        'archive_name'   => $name,
        'archive_size'   => $size,
        'built_at'       => $built_at,
        'public_key'     => isset($archive['public_key']) ? (string)$archive['public_key'] : null,
        'salt'           => isset($archive['salt']) ? (string)$archive['salt'] : null,
        'mutable_magnet' => isset($archive['mutable_magnet']) ? (string)$archive['mutable_magnet'] : null,
    );
    $known = array_filter($known, static function ($value) {
        return $value !== null && $value !== '';
    });

    try {
        if ($existing === null) {
            $columns = array_merge(array('bucket', 'infohash'), array_keys($known));
            $values  = array_merge(array($bucket, $infohash), array_values($known));
            $sql = 'INSERT INTO swarm_archives (' . implode(', ', $columns)
                 . ', updated_at, checked_at) VALUES ('
                 . implode(', ', array_fill(0, count($columns), '?'))
                 . ', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)';
            $stmt = $dbcore->sql->conn->prepare($sql);
            $stmt->execute($values);
            return true;
        }

        $sets   = array('infohash = ?', 'checked_at = CURRENT_TIMESTAMP');
        $values = array($infohash);
        foreach ($known as $column => $value) {
            $sets[]   = $column . ' = ?';
            $values[] = $value;
        }

        // Only when the build actually moved.  Rewriting this on every poll
        // would make every row look freshly built and quietly defeat the age
        // check that drops a retired infohash.
        if (strtolower((string)$existing['infohash']) !== $infohash) {
            $sets[] = 'updated_at = CURRENT_TIMESTAMP';
        }

        $values[] = $bucket;
        $stmt = $dbcore->sql->conn->prepare(
            'UPDATE swarm_archives SET ' . implode(', ', $sets) . ' WHERE bucket = ?'
        );
        $stmt->execute($values);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Notes that a bucket was checked and found unchanged.
 *
 * Separate from recording a build, because "the swarm answered and nothing has
 * changed" and "the swarm did not answer" are different states and only one of
 * them should move `checked_at`.
 */
function mvt_swarm_touch_archive($dbcore, string $bucket): bool {
    try {
        $stmt = $dbcore->sql->conn->prepare(
            'UPDATE swarm_archives SET checked_at = CURRENT_TIMESTAMP WHERE bucket = ?'
        );
        $stmt->bindParam(1, $bucket, PDO::PARAM_STR);
        $stmt->execute();
        return true;
    } catch (Exception $e) {
        return false;
    }
}

/**
 * How old a cached infohash may be before it is left out of the magnet, in
 * days.  Zero or less means never drop it.
 */
function mvt_swarm_infohash_max_age($dbcore): int {
    if (!isset($dbcore->tile_swarm_infohash_max_days)) {
        return 30;
    }
    return (int)$dbcore->tile_swarm_infohash_max_days;
}

/**
 * The last thing the swarm told us about a bucket's archive, or null.
 *
 * A cache, not a source of truth: the swarm decides which build is current,
 * and this is the most recent answer it gave.  Reading it costs one indexed
 * lookup and never touches the network, which is the whole point — a page
 * render must not wait on the swarm, and must not break when it is down.
 *
 * See tools/daemon/swarm_index.php for what fills this in.
 */
function mvt_swarm_cached_archive($dbcore, string $bucket): ?array {
    try {
        $stmt = $dbcore->sql->conn->prepare(
            'SELECT bucket, category, infohash, magnet, style_url, archive_name,
                    archive_size, built_at, public_key, salt, mutable_magnet,
                    updated_at, checked_at
               FROM swarm_archives WHERE bucket = ?'
        );
        $stmt->bindParam(1, $bucket, PDO::PARAM_STR);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        // A missing table is the state every install is in until the schema is
        // updated, and it must degrade to "no cached build" rather than taking
        // the map page down with it.
        return null;
    }

    return $row === false ? null : $row;
}

/**
 * The magnet for a bucket's archive, or null when no swarm public key is
 * configured.
 *
 * Carries two identifiers, and they do different jobs:
 *
 *   xs=urn:btpk:  the public key.  The one handle that outlives everything
 *                 else — an infohash names a specific build and changes on
 *                 every regeneration, while this names the category and
 *                 resolves, through the DHT, to whichever archive is current.
 *                 Computed from the key and the salt alone, so it is available
 *                 with no request to the swarm and stays valid when
 *                 tilejson.php, wifidb.net and the swarm's HTTP endpoint are
 *                 all unreachable, because the DHT is none of them.
 *
 *   xt=urn:btih:  the build that was current when the cache was last filled.
 *                 A browser has no DHT and cannot resolve the key, so without
 *                 this it would have to fetch the very document this magnet is
 *                 attached to before it could join anything — which is exactly
 *                 what putting a magnet in the fragment exists to avoid.
 *
 * The infohash is deliberately the cached one rather than a fresh lookup.  It
 * is a fallback for a client that cannot reach the TileJSON, so buying it with
 * a request to the swarm on every page render would be paying for the failure
 * case in the success case.
 *
 * It is also dropped once it is older than tile_swarm_infohash_max_days: past
 * some point a build has been retired, and pointing a client at a swarm that
 * no longer exists is worse than sending it to the key alone.  Note this is
 * measured from when the cache last *changed*, not when it was last checked —
 * a swarm that has been unreachable for a week has not made the build it last
 * reported any older.
 *
 * Format matches mutableMagnet() in pmtiles-swarm's src/mutable.js.  Web seeds
 * are deliberately omitted: those belong to a particular build, and this URI
 * is meant to stay valid across all of them.
 */
function mvt_swarm_magnet($dbcore, string $bucket): ?string {
    $category = mvt_swarm_category($dbcore, $bucket);
    if ($category === null) {
        return null;
    }

    $cached = mvt_swarm_cached_archive($dbcore, $bucket);

    // Is the cached build recent enough to be worth pointing anyone at? Past
    // some age it has been retired, and a magnet naming a swarm that no longer
    // exists is worse than one naming only the key. Measured from when the
    // build last CHANGED, not when the swarm was last reachable — an outage
    // does not make a build older.
    $fresh = false;
    if ($cached !== null) {
        $max_days = mvt_swarm_infohash_max_age($dbcore);
        $age_days = null;
        if (!empty($cached['updated_at'])) {
            $changed = strtotime((string)$cached['updated_at']);
            if ($changed !== false) {
                $age_days = (time() - $changed) / 86400;
            }
        }
        $fresh = $max_days <= 0 || $age_days === null || $age_days <= $max_days;
    }

    // What the swarm itself published, verbatim. Preferred over rebuilding the
    // string here because the format is the swarm's to define — it carries the
    // current infohash, the key, the salt, the display name and any web seeds,
    // and it has already changed shape once. Reconstructing it means keeping
    // two implementations in step across two repositories, and the one here
    // would fail quietly by producing a magnet that merely looks right.
    if ($fresh && !empty($cached['mutable_magnet'])) {
        return (string)$cached['mutable_magnet'];
    }

    // Nothing published to copy, so assemble one. Either the swarm was asked
    // and had no mutable record, or — much more usually — the onComplete hook
    // recorded this build a moment ago and knows only its infohash, the
    // refresher not having run since. Dropping the infohash here would throw
    // away exactly what the hook exists to deliver early.
    $key = '';
    if (isset($dbcore->tile_swarm_public_key) && $dbcore->tile_swarm_public_key !== '') {
        $key = strtolower(trim((string)$dbcore->tile_swarm_public_key));
    } elseif ($cached !== null && !empty($cached['public_key'])) {
        // Discovered rather than configured: the public half is in every
        // TileJSON the swarm serves, so the refresher reads it from there
        // instead of asking an operator to copy 64 hex characters correctly.
        $key = strtolower(trim((string)$cached['public_key']));
    }

    // An ed25519 public key is 32 bytes, so 64 hex characters. Anything else
    // would produce a magnet that looks valid and resolves to nothing.
    if (!preg_match('/^[0-9a-f]{64}$/', $key)) {
        return null;
    }

    $salt = $cached !== null && !empty($cached['salt'])
        ? (string)$cached['salt']
        : $category;

    $magnet = 'magnet:?';
    $infohash = $cached === null
        ? null
        : strtolower(trim((string)$cached['infohash']));
    if ($fresh && $infohash !== null && preg_match('/^[0-9a-f]{40}$/', $infohash)) {
        // First, because that is where a client looks for something to join
        // and a good many stop reading once they have found it.
        $magnet .= 'xt=urn:btih:' . $infohash . '&';
    }

    return $magnet . 'xs=urn:btpk:' . $key
        . '&dn=' . rawurlencode($salt)
        . '&s=' . rawurlencode($salt);
}

/**
 * How long a bucket's tiles stay fresh, in seconds.
 *
 * Read by mvtd and mltd to decide whether to regenerate a flat tile, and by
 * api/mvt.php and api/mlt.php to set max-age and to decide whether a cached
 * tile can be served.  One table, because a daemon that thought a tile was
 * stale while the endpoint thought it fresh would rewrite tiles nobody reads.
 */
function mvt_bucket_ttl(string $bucket): int {
    static $ttl = [
        'daily'     =>     3600,  //  1 hour
        'weekly'    =>    86400,  //  1 day
        'monthly'   =>   604800,  //  1 week
        '0to1year'  =>  2592000,  //  30 days
        '1to2year'  =>  2592000,
        '2to3year'  =>  2592000,
        '3to5year'  =>  2592000,
        '5to10year' =>  2592000,
        '10yrplus'  =>  2592000,
        // The combined all-ages buckets cost a full unbounded scan, which is
        // far more expensive than any single age bucket, so they regenerate on
        // a monthly-equivalent cadence rather than a daily one.
        'heatmap'   =>   604800,  //  1 week
    ];
    $base = (strpos($bucket, 'cell_') === 0) ? substr($bucket, 5) : $bucket;
    return $ttl[$base] ?? 86400;
}

/**
 * How old a flat tile may get before the cleanup sweep deletes it, regardless
 * of whether the daemon would regenerate it.
 *
 * Roughly twice the bucket's own window, so stale tiles are purged once the
 * data has rolled out of it — after a daemon outage, say, or when a tile falls
 * permanently below the AP threshold.  Only meaningful for flat output; an
 * archive is replaced whole.
 */
function mvt_bucket_max_age(string $bucket): int {
    static $max_age = [
        'daily'     =>    172800,  //  2 days   (window: 1 day)
        'weekly'    =>   1209600,  //  14 days  (window: 7 days)
        'monthly'   =>   5184000,  //  60 days  (window: ~30 days)
        '0to1year'  =>  31536000,  //  1 year
        '1to2year'  =>  31536000,
        '2to3year'  =>  31536000,
        '3to5year'  =>  63072000,  //  2 years
        '5to10year' =>  63072000,
        '10yrplus'  =>  63072000,
        'heatmap'   =>   5184000,  //  60 days
    ];
    $base = (strpos($bucket, 'cell_') === 0) ? substr($bucket, 5) : $bucket;
    return $max_age[$base] ?? 2592000;
}

/**
 * How aggressively the Morton-curve sort thins features at low zoom.
 *
 * An AP appears at zoom z only once its Morton gap to its nearest spatial
 * neighbour exceeds (this many tile pixels)² in Morton space.  1.0 shows an AP
 * as soon as APs stop overlapping; 2.0 demands two pixels of separation, which
 * halves the feature count per zoom step and matches tippecanoe's default
 * --drop-densest-as-needed behaviour at gamma=1 with droprate≈2.
 *
 * MVT and MLT tiles must contain the same features, so both daemons read this
 * rather than each carrying a copy.
 */
const MVT_DROP_SCALE_PIXELS = 1.5;

/**
 * Hard cap on feature_minzoom for a bucket.
 *
 * For most buckets a cap of 1 takes the fast path in assign_feature_minzoom():
 * no Morton sort, every AP gets feature_minzoom=1, and all density thinning
 * happens per tile.  At =< 1.5 M APs each z=1 candidate list is around 400 K,
 * which is one to two seconds per tile.
 *
 * The heatmap buckets combine every age window, around 9 M APs.  With a cap of
 * 1 all of them become candidates in each of the four z=1 tiles, and the
 * per-tile usort plus the $aps array together exhaust a 15 GB server, turning
 * z=1 alone into a twenty-hour job.  A cap of 7 makes the Morton sort run:
 * globally sparse APs keep feature_minzoom 1-6 and stay in low-zoom tiles,
 * while the dense clusters get 7 and appear only at z>=7.  Low-zoom candidate
 * sets fall to 50 K-200 K globally and per-tile sorts stay sub-second.
 */
function mvt_bucket_cap_fmz(string $bucket): int {
    static $caps = ['heatmap' => 7, 'cell_heatmap' => 7];
    return $caps[$bucket] ?? 1;
}

/**
 * The attributes a bucket's features carry, as field name => TileJSON type.
 *
 * The single source of truth for a bucket's schema.  The tile encoders build
 * their key list from array_keys() of this, and a PMTiles archive's
 * vector_layers metadata is built from the same call, so a field cannot end up
 * described in one place and encoded in another.  Key ORDER is significant:
 * it fixes the tag indices inside every encoded tile, so appending is safe and
 * reordering rewrites every tile in the bucket.
 *
 * Three shapes:
 *   heatmap       position, recency and security type only — see mvtd.php for
 *                 why the combined all-ages bucket carries almost nothing
 *   cell_*        cell tower fields; cell_heatmap adds age_days
 *   everything    the full AP record
 */
function mvt_bucket_fields(string $bucket): array {
    if ($bucket === 'heatmap') {
        return [
            'sectype'  => 'Number',
            'age_days' => 'Number',
        ];
    }

    if (strpos($bucket, 'cell_') === 0) {
        $fields = [
            'mac'      => 'String',
            'ssid'     => 'String',
            'authmode' => 'String',
            'chan'     => 'String',
            'type'     => 'String',
            'fa'       => 'String',
            'la'       => 'String',
            'points'   => 'Number',
            'rssi'     => 'Number',
            'user'     => 'String',
            'id_str'   => 'String',
        ];
        if ($bucket === 'cell_heatmap') {
            $fields['age_days'] = 'Number';
        }
        return $fields;
    }

    // lat/lon/alt are encoded as strings rather than numbers: they come out of
    // the database as fixed-precision decimals and round-tripping them through
    // a float would change the coordinate a client reads back.
    return [
        'sectype'       => 'Number',
        'chan'          => 'Number',
        'radio'         => 'String',
        'mac'           => 'String',
        'user'          => 'String',
        'ssid'          => 'String',
        'auth'          => 'String',
        'encry'         => 'String',
        'nt'            => 'String',
        'btx'           => 'String',
        'otx'           => 'String',
        'fa'            => 'String',
        'la'            => 'String',
        'points'        => 'Number',
        'high_gps_sig'  => 'Number',
        'high_gps_rssi' => 'Number',
        'lat'           => 'String',
        'lon'           => 'String',
        'alt'           => 'String',
        'manuf'         => 'String',
        'id_str'        => 'String',
    ];
}

function bucket_date_window(string $bucket): array {
    $now     = new DateTime('now', new DateTimeZone('UTC'));
    $windows = [
        'daily'     => ['PT36H', null   ],
        'weekly'    => ['P7D',   'PT36H'],
        'monthly'   => ['P1M',   'P7D'  ],
        '0to1year'  => ['P1Y',   'P1M'  ],
        '1to2year'  => ['P2Y',   'P1Y'  ],
        '2to3year'  => ['P3Y',   'P2Y'  ],
        '3to5year'  => ['P5Y',   'P3Y'  ],  // post-spike slump (~2021–2023)
        '5to10year' => ['P10Y',  'P5Y'  ],  // growth ramp + 2021 spike (~2016–2021)
        '10yrplus'  => [null,    'P10Y' ],  // sparse pre-2016 data
        'legacy'    => [null,    'P3Y'  ],  // backward-compat alias (covers 3yr+)
        'heatmap'   => [null,    null   ],  // all ages, unbounded
    ];
    [$start_ivl, $end_ivl] = $windows[$bucket];
    $start_date = $end_date = null;
    if ($start_ivl !== null) {
        $d = clone $now; $d->sub(new DateInterval($start_ivl));
        $start_date = $d->format('Y-m-d H:i:s');
    }
    if ($end_ivl !== null) {
        $d = clone $now; $d->sub(new DateInterval($end_ivl));
        $end_date = $d->format('Y-m-d H:i:s');
    }
    return [$start_date, $end_date];
}

/**
 * Days between a row's last-active timestamp and now — used as the
 * 'age_days' feature tag on the combined 'heatmap'/'cell_heatmap' buckets so
 * the client can weight heatmap-density by recency (heatmap-weight) without
 * needing per-age-bucket layers. Returns 0 for unparseable/empty input.
 */
function mvt_age_days(string $la): int {
    if ($la === '') return 0;
    $ts = strtotime($la);
    if ($ts === false) return 0;
    $days = (int)floor((time() - $ts) / 86400);
    return max(0, $days);
}
