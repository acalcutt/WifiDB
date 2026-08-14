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
function mvt_swarm_tilejson_url($dbcore, string $bucket): ?string {
    $category = mvt_swarm_category($dbcore, $bucket);
    if ($category === null) {
        return null;
    }
    return rtrim($dbcore->tile_swarm_url, '/') . '/latest/' . rawurlencode($category) . '/tiles.json';
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
    if ($which === 'mlt') {
        // Off by default: nothing reads MLT out of a PMTiles archive yet, and
        // building one is a full extra pass over the bucket.
        return isset($dbcore->tile_mlt_archive_generate)
            && (bool)$dbcore->tile_mlt_archive_generate;
    }
    return !isset($dbcore->tile_archive_generate) || (bool)$dbcore->tile_archive_generate;
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
 * The BEP 46 mutable magnet for a bucket's archive, or null when no swarm
 * public key is configured.
 *
 * This is the one handle that outlives everything else.  An infohash names a
 * specific build and changes on every regeneration; a mutable magnet names the
 * category and resolves, through the DHT, to whichever archive is current.  It
 * is built from the public key and the salt alone, and pmtiles-swarm salts each
 * record with the category name, so this is computable here with no request to
 * the swarm — which is the point.  A client holding this magnet can find the
 * current archive when tilejson.php, wifidb.net and the swarm's HTTP endpoint
 * are all unreachable, because the DHT is none of them.
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
    if (!isset($dbcore->tile_swarm_public_key) || $dbcore->tile_swarm_public_key === '') {
        return null;
    }

    // An ed25519 public key is 32 bytes, so 64 hex characters. Anything else
    // would produce a magnet that looks valid and resolves to nothing.
    $key = strtolower(trim($dbcore->tile_swarm_public_key));
    if (!preg_match('/^[0-9a-f]{64}$/', $key)) {
        return null;
    }

    return 'magnet:?xs=urn:btpk:' . $key
        . '&dn=' . rawurlencode($category)
        . '&s=' . rawurlencode($category);
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
