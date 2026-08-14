<?php
error_reporting(1);
@ini_set('display_errors', 0);
/*
tilejson.php — TileJSON 3.0.0 metadata endpoint
Copyright (C) 2024 Andrew Calcutt

Returns a TileJSON 3.0.0 descriptor for one age-bucket of the WifiDB history.
MapLibre can use this directly as a type:"vector" source URL:

  controller.AddSourceJson("wifidb_weekly", json_encode([
      "type" => "vector",
      "url"  => "https://wifidb.net/api/tilejson.php?bucket=weekly"
  ]));

Parameters:
  bucket   — required. One of: daily, weekly, monthly, 0to1year, 1to2year,
             2to3year, 3to5year, 5to10year, 10yrplus.
  format   — optional. mvt (default) or mlt.
  source   — optional. daemon (default) or api.
             daemon: tile URLs point to pre-generated static files under
                     out/tiles/ (MVT) or out/tiles-mlt/ (MLT). Apache serves
                     them directly; no PHP is invoked per tile request.
             api:    tile URLs point to mvt.php / mlt.php which generate tiles
                     on-demand with Morton-curve spatial thinning and write
                     results to the same disk cache. Use this when the daemon
                     has not yet generated tiles for a zoom range, or when you
                     need guaranteed freshness.
  minzoom  — optional integer [0–19]. Default 1. Overrides the minzoom field
             in the returned TileJSON (does not affect what the daemon has
             pre-generated; use in combination with source=api if needed).
  maxzoom  — optional integer [0–19]. Default 19. Must be ≥ minzoom.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');
// Functions, not a class, so the autoloader in init.inc.php does not reach
// it. Without this every request fatals and returns an HTML error page,
// which a client reports as 'Unexpected token <' rather than as a 500.
include_once('../lib/mvt.inc.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // 5-minute cache on the metadata

// ── Input validation ─────────────────────────────────────────────────────────
$bucket = preg_replace('/[^a-z0-9_]/', '', strtolower((string)@$_REQUEST['bucket']));

$bucket_meta = [
    'daily'     => ['name' => 'WifiDB Daily',      'desc' => 'WiFi APs active in the last 36 hours'],
    'weekly'    => ['name' => 'WifiDB Weekly',     'desc' => 'WiFi APs active in the last 7 days'],
    'monthly'   => ['name' => 'WifiDB Monthly',    'desc' => 'WiFi APs active 1 week – 1 month ago'],
    '0to1year'  => ['name' => 'WifiDB 0-1 Year',   'desc' => 'WiFi APs active 1 month – 1 year ago'],
    '1to2year'  => ['name' => 'WifiDB 1-2 Year',   'desc' => 'WiFi APs active 1 – 2 years ago'],
    '2to3year'  => ['name' => 'WifiDB 2-3 Year',   'desc' => 'WiFi APs active 2 – 3 years ago'],
    '3to5year'  => ['name' => 'WifiDB 3-5 Year',   'desc' => 'WiFi APs active 3 – 5 years ago'],
    '5to10year' => ['name' => 'WifiDB 5-10 Year',  'desc' => 'WiFi APs active 5 – 10 years ago'],
    '10yrplus'      => ['name' => 'WifiDB 10+ Year',       'desc' => 'WiFi APs last active more than 10 years ago'],
    'cell_daily'     => ['name' => 'WifiDB Cell Daily',     'desc' => 'Cell towers active in the last 36 hours'],
    'cell_weekly'    => ['name' => 'WifiDB Cell Weekly',    'desc' => 'Cell towers active in the last 7 days'],
    'cell_monthly'   => ['name' => 'WifiDB Cell Monthly',   'desc' => 'Cell towers active 1 week – 1 month ago'],
    'cell_0to1year'  => ['name' => 'WifiDB Cell 0-1 Year',  'desc' => 'Cell towers active 1 month – 1 year ago'],
    'cell_1to2year'  => ['name' => 'WifiDB Cell 1-2 Year',  'desc' => 'Cell towers active 1–2 years ago'],
    'cell_2to3year'  => ['name' => 'WifiDB Cell 2-3 Year',  'desc' => 'Cell towers active 2–3 years ago'],
    'cell_3to5year'  => ['name' => 'WifiDB Cell 3-5 Year',  'desc' => 'Cell towers active 3–5 years ago'],
    'cell_5to10year' => ['name' => 'WifiDB Cell 5-10 Year', 'desc' => 'Cell towers active 5–10 years ago'],
    'cell_10yrplus'  => ['name' => 'WifiDB Cell 10+ Year',  'desc' => 'Cell towers last active more than 10 years ago'],
    'heatmap'        => ['name' => 'WifiDB Heatmap (All Ages)',      'desc' => 'All WiFi APs, all ages combined, recency-weighted via age_days'],
    'cell_heatmap'   => ['name' => 'WifiDB Cell Heatmap (All Ages)', 'desc' => 'All cell towers, all ages combined, recency-weighted via age_days'],
];

if (!array_key_exists($bucket, $bucket_meta)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid bucket. Must be one of: ' . implode(', ', array_keys($bucket_meta))]);
    exit;
}

$base_url = rtrim($dbcore->URL_PATH, '/');

// ── Format selection ─────────────────────────────────────────────────────────
// ?format=mlt   → MapLibre Tile (MLT) descriptor
// ?format=mvt   → Mapbox Vector Tile (PBF) descriptor (default)
//
// The TileJSON "format" field is a MapLibre extension to TileJSON 3.0.0.
// MapLibre GL JS ≥ 5.12 and MapLibre Native (Android ≥ 12.1.0 / iOS ≥ 6.2.0)
// read this field to select the correct tile decoder.
$format = preg_replace('/[^a-z]/', '', strtolower((string)@$_REQUEST['format']));
if ($format !== 'mlt') $format = 'mvt';   // default

// ── Source selection ─────────────────────────────────────────────────────────
// ?source=daemon  → tile URLs point to pre-generated static files (default)
//                   Apache serves them directly; no PHP per tile request.
// ?source=api     → tile URLs point to mvt.php / mlt.php for on-demand
//                   generation with Morton spatial thinning + disk cache.
$source = preg_replace('/[^a-z]/', '', strtolower((string)@$_REQUEST['source']));
if ($source !== 'api') $source = 'daemon';   // default

// ── Zoom range ───────────────────────────────────────────────────────────────
// ?minzoom=N  (integer 0–19, defaults to config 'tile_min_zoom')
// ?maxzoom=N  (integer 0–19, defaults to config 'tile_max_zoom', must be ≥ minzoom)
$minzoom_param = filter_input(INPUT_GET, 'minzoom', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 19]]);
$maxzoom_param = filter_input(INPUT_GET, 'maxzoom', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 19]]);
$tile_minzoom  = ($minzoom_param !== false && $minzoom_param !== null) ? (int)$minzoom_param : $dbcore->tile_min_zoom;
$tile_maxzoom  = ($maxzoom_param !== false && $maxzoom_param !== null) ? (int)$maxzoom_param : $dbcore->tile_max_zoom;
if ($tile_minzoom > $tile_maxzoom) {
    http_response_code(400);
    echo json_encode(['error' => 'minzoom must be less than or equal to maxzoom']);
    exit;
}

// ── Tile URL ─────────────────────────────────────────────────────────────────
// cell_* buckets use the same URL patterns as AP buckets.
$is_cell = (strpos($bucket, 'cell_') === 0);

$is_archive  = (mvt_bucket_output($bucket, $dbcore) === 'pmtiles');
$swarm_url   = mvt_swarm_tilejson_url($dbcore, $bucket);
$api_base    = $base_url . '/api';
$api_tiles   = ($format === 'mlt')
    ? $api_base . '/mlt.php?z={z}&x={x}&y={y}&bucket=' . $bucket
    : $api_base . '/mvt.php?z={z}&x={x}&y={y}&bucket=' . $bucket;

// ── source=daemon for an archived bucket ─────────────────────────────────────
// There is no {z}/{x}/{y} file to link to inside a .pmtiles archive, so the
// static URL pattern below has no equivalent.  Hand the client to the swarm
// instead: /latest/{category}/tiles.json resolves to the newest build and
// points at immutable, content-addressed tile URLs, which is a better answer
// than the static path ever was — those could not be cached for long, because
// the bytes behind them changed on every regeneration.
//
// Redirecting rather than copying the swarm's answer keeps one description of
// a build in existence.  It also delivers the torrent block in the swarm's
// response body, so a torrent-aware client gets the magnet without WifiDB
// having to learn the infohash of a build it did not publish.
if ($source === 'daemon' && $is_archive && $swarm_url !== null) {
    header('Location: ' . $swarm_url, true, 302);
    // Matches the max-age the swarm sets on the document being redirected to;
    // no point holding the pointer longer than the thing it points at.
    header('Cache-Control: public, max-age=300');
    header('Content-Type: application/json');
    // A 302 body is not usually read, but this one is worth filling in: the
    // magnet is what a client needs precisely when following the redirect
    // fails, and putting it here means it is on the last response we control.
    $body = [
        'tilejson_url' => $swarm_url,
        'note'         => 'Archived buckets are served by pmtiles-swarm; follow tilejson_url.',
    ];
    $magnet = mvt_swarm_magnet($dbcore, $bucket);
    if ($magnet !== null) {
        $body['magnet'] = $magnet;
    }
    echo json_encode($body, JSON_UNESCAPED_SLASHES);
    exit;
}

if ($source === 'api' || $is_archive) {
    // Archived buckets with no swarm configured land here too: api/mvt.php and
    // api/mlt.php read the archive directly, so this works on a standalone
    // install with nothing else running.
    $tiles_url = $api_tiles;
} else {
    // Pre-generated static files served directly by Apache.
    if ($format === 'mlt') {
        $tiles_url = $base_url . '/out/tiles-mlt/' . $bucket . '/{z}/{x}/{y}.mlt';
    } else {
        $tiles_url = $base_url . '/out/tiles/' . $bucket . '/{z}/{x}/{y}.pbf';
    }
}

$tilejson = [
    'tilejson'      => '3.0.0',
    'name'          => $bucket_meta[$bucket]['name'],
    'description'   => $bucket_meta[$bucket]['desc'],
    'version'       => '1.0.0',
    'attribution'   => '<a href="https://wifidb.net">© WifiDB contributors</a>',
    'scheme'        => 'xyz',
    'tiles'         => [$tiles_url],
    'minzoom'       => $tile_minzoom,
    'maxzoom'       => $tile_maxzoom,
    'bounds'        => [-180.0, -85.051129, 180.0, 85.051129],
    // From mvt.inc.php — the same call the tile encoders build their tag keys
    // from, and the same one written into each archive's own metadata.  This
    // used to be three hand-maintained lists here (AP, cell, and the heatmap
    // addition), which is three chances to describe a field the tiles do not
    // carry, or to omit one they do.  Bucket shape is decided there.
    'vector_layers' => [
        [
            'id'          => $bucket,
            'description' => $bucket_meta[$bucket]['desc'],
            'minzoom'     => $tile_minzoom,
            'maxzoom'     => $tile_maxzoom,
            'fields'      => mvt_bucket_fields($bucket),
        ],
    ],
];

// Where the archive for this bucket can be had, when a swarm is configured.
// Present on every response for an archived bucket, including source=api, so
// it is discoverable without having to follow a redirect to find it.
if ($is_archive && $swarm_url !== null) {
    $tilejson['swarm'] = [
        'category' => mvt_swarm_category($dbcore, $bucket),
        'tilejson' => $swarm_url,
    ];

    // The per-category BEP 46 magnet, which is the fallback that survives this
    // endpoint.  Everything else here is an HTTP URL and stops working when the
    // server behind it does; a mutable magnet resolves through the DHT, names
    // the category rather than a build, and therefore stays correct across
    // every regeneration.  A client that stored it once can still find the
    // current archive with nothing of ours reachable.
    $magnet = mvt_swarm_magnet($dbcore, $bucket);
    if ($magnet !== null) {
        $tilejson['swarm']['magnet'] = $magnet;

        // Ready to paste into a style's source. The fragment is never sent in
        // an HTTP request, so a client that knows nothing about torrents
        // fetches the TileJSON and ignores it, while a torrent-aware one has
        // the magnet before the first request — and still has it if that
        // request fails.
        $tilejson['swarm']['source_url'] = $swarm_url . '#' . $magnet;
    }
}

// The archive addressed directly, for a client that would rather range-read it
// than ask an endpoint for tiles.  MapLibre GL JS understands pmtiles:// with
// no help from us, so this removes both WifiDB and the swarm from the request
// path entirely — and carries the same magnet in its fragment, so a
// torrent-aware client can take the swarm route from the same one URL.
$pmtiles_url = mvt_archive_pmtiles_url($dbcore, $bucket);
if ($pmtiles_url !== null) {
    $tilejson['pmtiles'] = ['url' => $pmtiles_url];
}

// Add format field for MLT tiles (MapLibre extension to TileJSON 3.0.0).
if ($format === 'mlt') {
    $tilejson['format'] = 'mlt';
}

echo json_encode($tilejson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
