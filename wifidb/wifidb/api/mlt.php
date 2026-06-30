<?php
error_reporting(1);
@ini_set('display_errors', 0);
/*
mlt.php — MapLibre Tile (MLT) endpoint
Copyright (C) 2024 Andrew Calcutt

Serves a single Web-Mercator tile as a MapLibre Tile (MLT) for use with
MapLibre's type:"vector" source when the TileJSON includes "format":"mlt".

URL:  /api/mlt.php?z={z}&x={x}&y={y}&bucket={bucket}

  bucket — one of: daily, weekly, monthly, 0to1year, 1to2year, 2to3year, 3to5year, 5to10year, 10yrplus

The MLT tile contains a single feature table whose name matches the bucket
parameter.  Fields: sectype (int), chan (int), radio (string), mac (string),
user (string).

Tiles are stored gzip-compressed in out/tiles-mlt/{bucket}/{z}/{x}/{y}.mlt,
the same directory mltd.php writes pre-generated tiles to.  On a cache miss
the tile is generated on-demand and written for future requests.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');
include('../lib/mvt.inc.php');    // tile_bounds_dd, dd2dm, bucket_date_window, project_to_tile
include('../lib/mlt.inc.php');    // mlt_encode_tile, MLT_EXTENT
include('../lib/spatial.inc.php');  // assign_feature_minzoom

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Input validation ─────────────────────────────────────────────────────────
$z = filter_input(INPUT_GET, 'z', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 20]]);
$x = filter_input(INPUT_GET, 'x', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$y = filter_input(INPUT_GET, 'y', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$bucket = preg_replace('/[^a-z0-9_]/', '', strtolower((string)@$_REQUEST['bucket']));

$valid_buckets = ['daily', 'weekly', 'monthly', '0to1year', '1to2year', '2to3year', '3to5year', '5to10year', '10yrplus',
                  'cell_daily', 'cell_weekly', 'cell_monthly', 'cell_0to1year', 'cell_1to2year', 'cell_2to3year', 'cell_3to5year', 'cell_5to10year', 'cell_10yrplus',
                  'heatmap', 'cell_heatmap'];

if ($z === false || $z === null || $x === false || $x === null || $y === false || $y === null) {
    http_response_code(400); header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or invalid z, x, y (integers required)']); exit;
}
if (!in_array($bucket, $valid_buckets, true)) {
    http_response_code(400); header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid bucket. Must be one of: ' . implode(', ', $valid_buckets)]); exit;
}

// ── Unified tile store ────────────────────────────────────────────────────────
// Tiles are stored in out/tiles-mlt/{bucket}/{z}/{x}/{y}.mlt (gzip-compressed MLT).
// This is the same directory mltd.php writes pre-generated tiles to.
define('TILE_DISK_CACHE', true);
$bucket_ttl = [
    'daily'     =>     3600,  //  1 hour
    'weekly'    =>    86400,  //  1 day
    'monthly'   =>   604800,  //  1 week
    '0to1year'  =>  2592000,  //  30 days
    '1to2year'  =>  2592000,
    '2to3year'  =>  2592000,
    '3to5year'  =>  2592000,
    '5to10year' =>  2592000,
    '10yrplus'  =>  2592000,
    'cell_daily'     =>     3600,
    'cell_weekly'    =>    86400,
    'cell_monthly'   =>   604800,
    'cell_0to1year'  =>  2592000,
    'cell_1to2year'  =>  2592000,
    'cell_2to3year'  =>  2592000,
    'cell_3to5year'  =>  2592000,
    'cell_5to10year' =>  2592000,
    'cell_10yrplus'  =>  2592000,
    'heatmap'        =>   604800,  //  1 week — see mvtd.php for rationale
    'cell_heatmap'   =>   604800,
];
$cache_ttl  = $bucket_ttl[$bucket] ?? 86400;
$tile_dir   = rtrim($dbcore->PATH, '/') . '/out/tiles-mlt/' . $bucket . '/' . $z . '/' . $x;
$tile_file  = $tile_dir . '/' . $y . '.mlt';

if (TILE_DISK_CACHE && file_exists($tile_file) && (time() - filemtime($tile_file)) < $cache_ttl) {
    header('Content-Type: application/vnd.mapbox-vector-tile');
    header('Content-Encoding: gzip');
    header('Vary: Accept-Encoding');
    header('Cache-Control: public, max-age=' . $cache_ttl);
    header('X-Tile-Cache: HIT');
    readfile($tile_file);
    exit;
}

// ── Tile bounding box + date window ──────────────────────────────────────────
[$lat_min, $lat_max, $lon_min, $lon_max] = tile_bounds_dd($z, $x, $y);

$lat_min_dm = dd2dm($lat_min);
$lat_max_dm = dd2dm($lat_max);
$lon_min_dm = dd2dm($lon_min);
$lon_max_dm = dd2dm($lon_max);

$is_cell     = (strpos($bucket, 'cell_') === 0);
$base_bucket = $is_cell ? substr($bucket, 5) : $bucket;
[$start_date, $end_date] = bucket_date_window($base_bucket);

// ── Query via shared export function ─────────────────────────────────────────
$query_limit = ($z >= 12) ? 5000 : min(50000, 5000 << max(0, 12 - $z));

if ($is_cell) {
    $result = $dbcore->export->BboxCellArray(
        $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
        $query_limit, null, $start_date, $end_date
    );
} else {
    $result = $dbcore->export->BboxDateArray(
        $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
        $start_date, $end_date,
        null, $query_limit
    );
}
$rows = $result['data'];

// ── Per-tile spatial thinning + gzip retry loop ───────────────────────────────
// Mirrors encode_mlt_tile_from_points() / encode_cell_mlt_tile_from_points()
// in mltd.php exactly — same algorithm as mvt.php:
//   1. Project rows to tile pixels + per-tile Morton index.
//   2. Sort by Morton index, compute gap to predecessor.
//   3. Re-sort by gap descending (sparsest first).
//   4. Deduplicate same-pixel [+ same-sectype for WiFi].
//   5. Encode → gzip; if > tile_max_gz_bytes, keep floor(keep × max/size), retry ≤5×.

// Step 1: project + per-tile Morton index.
$pts = [];
foreach ($rows as $row) {
    [$px, $py] = project_to_tile((float)$row['lat'], (float)$row['lon'], $z, $x, $y);
    $pts[] = ['row' => $row, 'px' => $px, 'py' => $py,
              'morton' => morton_encode((float)$row['lat'], (float)$row['lon'])];
}

// Step 2: Morton sort + gap to predecessor.
usort($pts, fn($a, $b) => $a['morton'] <=> $b['morton']);
$prev_m = PHP_INT_MIN;
foreach ($pts as &$pt) {
    $pt['gap'] = ($prev_m === PHP_INT_MIN) ? PHP_INT_MAX : ($pt['morton'] - $prev_m);
    $prev_m    = $pt['morton'];
}
unset($pt);

// Step 3: sort by gap descending (sparsest first).
usort($pts, fn($a, $b) => $b['gap'] <=> $a['gap']);

// Step 4: dedup same-pixel [+ same-sectype for WiFi].
$seen    = [];
$deduped = [];
foreach ($pts as $pt) {
    $k = $is_cell
        ? ($pt['px'] . ':' . $pt['py'])
        : ($pt['px'] . ':' . $pt['py'] . ':' . (int)$pt['row']['sectype']);
    if (!isset($seen[$k])) { $seen[$k] = true; $deduped[] = $pt; }
}
unset($seen, $pts);

// Step 5: encode → gzip retry loop (target ≤ tile_max_gz_bytes from config).
$max_gz_bytes = $dbcore->tile_max_gz_bytes;
$keep         = count($deduped);
$mlt_bytes_gz = null;
$has_features = false;

if ($keep > 0) {
    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset   = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);
        $features = [];
        foreach ($subset as $pt) {
            $row = $pt['row'];
            if ($is_cell) {
                $features[] = [
                    'id'       => (int)$row['id'],
                    'x'        => $pt['px'],
                    'y'        => $pt['py'],
                    'mac'      => (string)$row['mac'],
                    'ssid'     => (string)$row['ssid'],
                    'authmode' => (string)$row['authmode'],
                    'chan'     => (string)$row['chan'],
                    'type'     => (string)$row['type'],
                    'fa'       => (string)$row['fa'],
                    'la'       => (string)$row['la'],
                    'age_days' => mvt_age_days((string)$row['la']),
                    'points'   => (int)$row['points'],
                    'rssi'     => (int)$row['rssi'],
                    'user'     => (string)$row['user'],
                ];
            } else {
                $features[] = [
                    'id'            => (int)$row['id'],
                    'x'             => $pt['px'],
                    'y'             => $pt['py'],
                    'sectype'       => (int)$row['sectype'],
                    'chan'           => (int)$row['chan'],
                    'radio'         => (string)$row['radio'],
                    'mac'           => (string)$row['mac'],
                    'user'          => (string)$row['user'],
                    'ssid'          => (string)$row['ssid'],
                    'auth'          => (string)$row['auth'],
                    'encry'         => (string)$row['encry'],
                    'nt'            => (string)$row['nt'],
                    'btx'           => (string)$row['btx'],
                    'otx'           => (string)$row['otx'],
                    'fa'            => (string)$row['fa'],
                    'la'            => (string)$row['la'],
                    'age_days'      => mvt_age_days((string)$row['la']),
                    'points'        => (int)$row['points'],
                    'high_gps_sig'  => (int)$row['high_gps_sig'],
                    'high_gps_rssi' => (int)$row['high_gps_rssi'],
                    'lat'           => (string)$row['lat'],
                    'lon'           => (string)$row['lon'],
                    'alt'           => (string)$row['alt'],
                    'manuf'         => (string)$row['manuf'],
                ];
            }
        }

        if (empty($features)) break;
        $mlt_bytes    = mlt_encode_tile($bucket, $features);
        $mlt_bytes_gz = gzencode($mlt_bytes, 6);
        $has_features = true;

        $sz = strlen($mlt_bytes_gz);
        if ($sz <= $max_gz_bytes) break;
        $new_keep = max(1, (int)floor($keep * $max_gz_bytes / $sz));
        if ($new_keep >= $keep) break;
        $keep = $new_keep;
    }
}

// ── Response ──────────────────────────────────────────────────────────────────
if (!$has_features) {
    // Return an empty-tile response; do not cache so the next request retries.
    header('Content-Type: application/vnd.mapbox-vector-tile');
    header('Cache-Control: public, max-age=60');
    header('Content-Length: 0');
    http_response_code(204);
    exit;
}

if (TILE_DISK_CACHE) {
    if (!is_dir($tile_dir)) { @mkdir($tile_dir, 0775, true); }
    @file_put_contents($tile_file, $mlt_bytes_gz);
}

header('Content-Type: application/vnd.mapbox-vector-tile');
header('Content-Encoding: gzip');
header('Vary: Accept-Encoding');
header('Cache-Control: public, max-age=' . $cache_ttl);
header('Content-Length: ' . strlen($mlt_bytes_gz));
header('X-Tile-Cache: MISS');

echo $mlt_bytes_gz;
