<?php
error_reporting(1);
@ini_set('display_errors', 0);
/*
mvt.php — Mapbox Vector Tile (MVT / PBF) endpoint
Copyright (C) 2024 Andrew Calcutt

Serves a single Web-Mercator tile as a Mapbox Vector Tile (application/x-protobuf)
for use with MapLibre's type:"vector" source.  Intended to be called via the
TileJSON descriptor returned by tilejson.php.

URL:  /api/mvt.php?z={z}&x={x}&y={y}&bucket={bucket}

  bucket — one of: daily, weekly, monthly, 0to1year, 1to2year, 2to3year, legacy

The MVT contains a single layer whose name matches the bucket parameter.
Layer fields: sectype (int), chan (int), radio (string), mac (string), user (string)

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');
include('../lib/mvt.inc.php');
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
                  'cell_daily', 'cell_weekly', 'cell_monthly', 'cell_0to1year', 'cell_1to2year', 'cell_2to3year', 'cell_3to5year', 'cell_5to10year', 'cell_10yrplus'];

if ($z === false || $z === null || $x === false || $x === null || $y === false || $y === null) {
    http_response_code(400); header('Content-Type: application/json');
    echo json_encode(['error' => 'Missing or invalid z, x, y (integers required)']); exit;
}
if (!in_array($bucket, $valid_buckets, true)) {
    http_response_code(400); header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid bucket. Must be one of: ' . implode(', ', $valid_buckets)]); exit;
}

// ── Unified tile store ────────────────────────────────────────────────────────
// Tiles are stored in out/tiles/{bucket}/{z}/{x}/{y}.pbf (gzip-compressed PBF).
// This is the same directory the mvtd.php daemon writes pre-generated tiles to,
// so daemon-generated and dynamically-generated tiles share one cache.
// TTL values match mvtd.php $bucket_ttl — keeping them in sync means the daemon
// and mvt.php agree on when a tile is considered stale.
//
// Set TILE_DISK_CACHE to false to serve all tiles dynamically without reading
// or writing the on-disk cache.  Daemon-pre-generated tiles (z≤12) will still
// be served from disk by Apache before this script is even invoked, so this
// flag mainly controls whether z13+ on-demand tiles are cached on disk.
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
];
$cache_ttl  = $bucket_ttl[$bucket] ?? 86400;
$tile_dir   = rtrim($dbcore->PATH, '/') . '/out/tiles/' . $bucket . '/' . $z . '/' . $x;
$tile_file  = $tile_dir . '/' . $y . '.pbf';

if (TILE_DISK_CACHE && file_exists($tile_file) && (time() - filemtime($tile_file)) < $cache_ttl) {
    header('Content-Type: application/x-protobuf');
    header('Content-Encoding: gzip');
    header('Vary: Accept-Encoding');
    header('Cache-Control: public, max-age=' . $cache_ttl);
    header('X-Tile-Cache: HIT');
    readfile($tile_file);
    exit;
}

// ── Tile bounding box + date window (via mvt.inc.php) ────────────────────────
[$lat_min, $lat_max, $lon_min, $lon_max] = tile_bounds_dd($z, $x, $y);

$lat_min_dm = dd2dm($lat_min);
$lat_max_dm = dd2dm($lat_max);
$lon_min_dm = dd2dm($lon_min);
$lon_max_dm = dd2dm($lon_max);

$is_cell     = (strpos($bucket, 'cell_') === 0);
$base_bucket = $is_cell ? substr($bucket, 5) : $bucket;
[$start_date, $end_date] = bucket_date_window($base_bucket);

// ── Query via shared export function ─────────────────────────────────────────
// Scale the fetch limit with zoom level — the bbox is larger at low zoom so
// more rows may fall within it.  The Lat/Lon index on wifi_gps makes the bbox
// filter a fast index seek regardless of zoom, so larger limits are safe.
//   z>=12: 5 000 rows  (small bbox, many tiles cover a city)
//   z=10:  20 000 rows
//   z=8:   50 000 rows
//   z<=7:  50 000 rows (capped)
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
// Mirrors encode_tile_from_points() / encode_cell_tile_from_mvt() in mvtd.php:
//   1. Project rows to tile pixels + compute per-tile Morton index.
//   2. Sort by Morton index, compute gap to predecessor.
//   3. Re-sort by gap descending (sparsest APs first).
//   4. Deduplicate same-pixel [+ same-sectype for WiFi].
//   5. Encode → gzip; if >750 KB, keep floor(keep × 750000/size), retry ≤5×.
$add_value_fn = function(string $type, $raw, array &$vbytes, array &$vidx): int {
    $key = $type . ':' . $raw;
    if (!isset($vidx[$key])) {
        $vidx[$key] = count($vbytes);
        $vbytes[]   = ($type === 'int') ? pb_field_varint(4, (int)$raw) : pb_field_string(1, (string)$raw);
    }
    return $vidx[$key];
};

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

// Step 5: encode → gzip retry loop (target ≤750 KB, matching tippecanoe -M 750000).
$max_gz_bytes = 750000;
$keep         = count($deduped);
$gz_bytes     = null;
$has_features = false;

if ($keep > 0) {
    if ($is_cell) {
        $enc_keys = ['mac', 'ssid', 'authmode', 'chan', 'type',
                     'fa', 'la', 'points', 'rssi', 'user', 'id_str'];
    } else {
        $enc_keys = ['sectype', 'chan', 'radio', 'mac', 'user',
                     'ssid', 'auth', 'encry', 'nt', 'btx', 'otx',
                     'fa', 'la', 'points', 'high_gps_sig', 'high_gps_rssi',
                     'lat', 'lon', 'alt', 'manuf', 'id_str'];
    }
    $enc_keys_idx = array_flip($enc_keys);

    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);
        $vbytes = []; $vidx = [];
        $av = function(string $t, $v) use (&$vbytes, &$vidx, $add_value_fn): int {
            return $add_value_fn($t, $v, $vbytes, $vidx);
        };

        $features = [];
        foreach ($subset as $pt) {
            $row = $pt['row'];
            if ($is_cell) {
                $tags = [
                    $enc_keys_idx['mac'],      $av('str', (string)$row['mac']),
                    $enc_keys_idx['ssid'],     $av('str', (string)$row['ssid']),
                    $enc_keys_idx['authmode'], $av('str', (string)$row['authmode']),
                    $enc_keys_idx['chan'],      $av('str', (string)$row['chan']),
                    $enc_keys_idx['type'],     $av('str', (string)$row['type']),
                    $enc_keys_idx['fa'],       $av('str', (string)$row['fa']),
                    $enc_keys_idx['la'],       $av('str', (string)$row['la']),
                    $enc_keys_idx['points'],   $av('int', (int)$row['points']),
                    $enc_keys_idx['rssi'],     $av('int', (int)$row['rssi']),
                    $enc_keys_idx['user'],     $av('str', (string)$row['user']),
                    $enc_keys_idx['id_str'],   $av('str', (string)$row['id']),
                ];
            } else {
                $tags = [
                    $enc_keys_idx['sectype'],       $av('int', (int)$row['sectype']),
                    $enc_keys_idx['chan'],           $av('int', (int)$row['chan']),
                    $enc_keys_idx['radio'],         $av('str', (string)$row['radio']),
                    $enc_keys_idx['mac'],           $av('str', (string)$row['mac']),
                    $enc_keys_idx['user'],          $av('str', (string)$row['user']),
                    $enc_keys_idx['ssid'],          $av('str', (string)$row['ssid']),
                    $enc_keys_idx['auth'],          $av('str', (string)$row['auth']),
                    $enc_keys_idx['encry'],         $av('str', (string)$row['encry']),
                    $enc_keys_idx['nt'],            $av('str', (string)$row['nt']),
                    $enc_keys_idx['btx'],           $av('str', (string)$row['btx']),
                    $enc_keys_idx['otx'],           $av('str', (string)$row['otx']),
                    $enc_keys_idx['fa'],            $av('str', (string)$row['fa']),
                    $enc_keys_idx['la'],            $av('str', (string)$row['la']),
                    $enc_keys_idx['points'],        $av('int', (int)$row['points']),
                    $enc_keys_idx['high_gps_sig'],  $av('int', (int)$row['high_gps_sig']),
                    $enc_keys_idx['high_gps_rssi'], $av('int', (int)$row['high_gps_rssi']),
                    $enc_keys_idx['lat'],           $av('str', (string)$row['lat']),
                    $enc_keys_idx['lon'],           $av('str', (string)$row['lon']),
                    $enc_keys_idx['alt'],           $av('str', (string)$row['alt']),
                    $enc_keys_idx['manuf'],         $av('str', (string)$row['manuf']),
                    $enc_keys_idx['id_str'],        $av('str', (string)$row['id']),
                ];
            }
            $features[] = mvt_encode_point_feature((int)$row['id'], $pt['px'], $pt['py'], $tags);
        }

        if (empty($features)) break;

        $layer_bytes  = mvt_encode_layer($bucket, $features, $enc_keys, $vbytes);
        $tile_bytes   = mvt_encode_tile($layer_bytes);
        $gz_bytes     = gzencode($tile_bytes, 6);
        $has_features = true;

        $sz = strlen($gz_bytes);
        if ($sz <= $max_gz_bytes) break;
        $new_keep = max(1, (int)floor($keep * $max_gz_bytes / $sz));
        if ($new_keep >= $keep) break;
        $keep = $new_keep;
    }
}

if ($gz_bytes === null) {
    // No features — return an empty but valid gzip tile; don't cache.
    $gz_bytes = gzencode(mvt_encode_tile(mvt_encode_layer($bucket, [], [], [])), 6);
}

// ── Response ──────────────────────────────────────────────────────────────────
// Only persist to disk when caching is enabled and there is actual data.
// Empty tiles are never cached so the daemon's cleanup pass doesn't need to
// remove on-demand empties.  Set TILE_DISK_CACHE=false to disable entirely.
if (TILE_DISK_CACHE && $has_features) {
    if (!is_dir($tile_dir)) { @mkdir($tile_dir, 0775, true); }
    @file_put_contents($tile_file, $gz_bytes);
}

header('Content-Type: application/x-protobuf');
header('Content-Encoding: gzip');
header('Vary: Accept-Encoding');
header('Cache-Control: public, max-age=' . ($has_features ? $cache_ttl : 60));
header('Content-Length: ' . strlen($gz_bytes));
header('X-Tile-Cache: MISS');

echo $gz_bytes;
