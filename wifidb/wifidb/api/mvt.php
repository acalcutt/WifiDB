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

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Input validation ─────────────────────────────────────────────────────────
$z = filter_input(INPUT_GET, 'z', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 20]]);
$x = filter_input(INPUT_GET, 'x', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$y = filter_input(INPUT_GET, 'y', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$bucket = preg_replace('/[^a-z0-9]/', '', strtolower((string)@$_REQUEST['bucket']));

$valid_buckets = ['daily', 'weekly', 'monthly', '0to1year', '1to2year', '2to3year', 'legacy'];

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
    'daily'    =>     3600,  //  1 hour
    'weekly'   =>    86400,  //  1 day
    'monthly'  =>   604800,  //  1 week
    '0to1year' =>  2592000,  //  30 days
    '1to2year' =>  2592000,
    '2to3year' =>  2592000,
    'legacy'   =>  2592000,
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

[$start_date, $end_date] = bucket_date_window($bucket);

// ── Query via shared export function ─────────────────────────────────────────
// Scale the fetch limit with zoom level — the bbox is larger at low zoom so
// more rows may fall within it.  The Lat/Lon index on wifi_gps makes the bbox
// filter a fast index seek regardless of zoom, so larger limits are safe.
//   z>=12: 5 000 rows  (small bbox, many tiles cover a city)
//   z=10:  20 000 rows
//   z=8:   50 000 rows
//   z<=7:  50 000 rows (capped)
$query_limit = ($z >= 12) ? 5000 : min(50000, 5000 << max(0, 12 - $z));

$result = $dbcore->export->BboxDateArray(
    $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
    $start_date, $end_date,
    null, $query_limit
);
$rows = $result['data'];

// ── Build MVT ─────────────────────────────────────────────────────────────────
// Fixed property key order — must match the indices used in tags arrays below.
$keys     = ['sectype', 'chan', 'radio', 'mac', 'user',
             'ssid', 'auth', 'encry', 'nt', 'btx', 'otx',
             'fa', 'la', 'points', 'high_gps_sig', 'high_gps_rssi',
             'lat', 'lon', 'alt', 'manuf', 'id_str'];
$keys_idx = array_flip($keys);   // name → index

// Value deduplication: store encoded Value message content, keyed by "type:raw".
$values_bytes = [];  // ordered Value message byte strings
$values_idx   = [];  // "type:raw" => index

/**
 * Return the index of a value in the values table, adding it if new.
 * @param string $type  'int' or 'str'
 * @param mixed  $raw   The raw PHP value
 */
$add_value = function(string $type, $raw) use (&$values_bytes, &$values_idx): int {
    $key = $type . ':' . $raw;
    if (!isset($values_idx[$key])) {
        $values_idx[$key] = count($values_bytes);
        // Encode as a Value proto message (just the inner fields — the Layer will frame it).
        if ($type === 'int') {
            $values_bytes[] = pb_field_varint(4, (int)$raw);   // int_value = field 4
        } else {
            $values_bytes[] = pb_field_string(1, (string)$raw); // string_value = field 1
        }
    }
    return $values_idx[$key];
};

// ── Zoom-based point thinning ─────────────────────────────────────────────────
// Assigns each point to a grid cell and keeps one point per (cell, sectype).
// Thinning kicks in below z=12 (two zoom levels later than before), and the
// cell key includes the security type so open/WEP/secure APs at the same
// location each get their own slot — matching tippecanoe's per-type behaviour.
//   z=12+: cell_size=1  (no thinning)
//   z=11:  cell_size=2  (2×2 px cells, 1 per sectype)
//   z=9:   cell_size=8
//   z=7:   cell_size=32 (128 cells/axis → ≤49 152 pts/tile across 3 sectypes)
//   z=4:   cell_size=256 (16 cells/axis → ≤768 pts/tile across 3 sectypes)
$cell_size  = max(1, 1 << max(0, 12 - $z));
$seen_cells = [];

$features = [];

// BboxDateArray returns ap_info arrays with 'lat'/'lon' already in decimal degrees.
foreach ($rows as $row) {
    [$px, $py] = project_to_tile((float)$row['lat'], (float)$row['lon'], $z, $x, $y);

    // Skip if another point already occupies this grid cell.
    if ($cell_size > 1) {
        $cell_key = (int)($px / $cell_size) . ':' . (int)($py / $cell_size) . ':' . (int)$row['sectype'];
        if (isset($seen_cells[$cell_key])) continue;
        $seen_cells[$cell_key] = true;
    }

    // Build flat [key_idx, val_idx, ...] tags array.
    $tags = [
        $keys_idx['sectype'],      $add_value('int', (int)$row['sectype']),
        $keys_idx['chan'],          $add_value('int', (int)$row['chan']),
        $keys_idx['radio'],        $add_value('str', (string)$row['radio']),
        $keys_idx['mac'],          $add_value('str', (string)$row['mac']),
        $keys_idx['user'],         $add_value('str', (string)$row['user']),
        $keys_idx['ssid'],         $add_value('str', (string)$row['ssid']),
        $keys_idx['auth'],         $add_value('str', (string)$row['auth']),
        $keys_idx['encry'],        $add_value('str', (string)$row['encry']),
        $keys_idx['nt'],           $add_value('str', (string)$row['nt']),
        $keys_idx['btx'],          $add_value('str', (string)$row['btx']),
        $keys_idx['otx'],          $add_value('str', (string)$row['otx']),
        $keys_idx['fa'],           $add_value('str', (string)$row['fa']),
        $keys_idx['la'],           $add_value('str', (string)$row['la']),
        $keys_idx['points'],       $add_value('int', (int)$row['points']),
        $keys_idx['high_gps_sig'], $add_value('int', (int)$row['high_gps_sig']),
        $keys_idx['high_gps_rssi'],$add_value('int', (int)$row['high_gps_rssi']),
        $keys_idx['lat'],          $add_value('str', (string)$row['lat']),
        $keys_idx['lon'],          $add_value('str', (string)$row['lon']),
        $keys_idx['alt'],          $add_value('str', (string)$row['alt']),
        $keys_idx['manuf'],        $add_value('str', (string)$row['manuf']),
        $keys_idx['id_str'],       $add_value('str', (string)$row['id']),
    ];

    $features[] = mvt_encode_point_feature((int)$row['id'], $px, $py, $tags);
}

$layer_bytes = mvt_encode_layer($bucket, $features, $keys, $values_bytes);
$tile_bytes  = mvt_encode_tile($layer_bytes);

// ── Response ──────────────────────────────────────────────────────────────────
// Gzip-compress before caching and sending.
$tile_bytes_gz = gzencode($tile_bytes, 6);

// Only persist to disk when caching is enabled and there is actual data.
// Empty tiles are never cached so the daemon's cleanup pass doesn't need to
// remove on-demand empties.  Set TILE_DISK_CACHE=false to disable entirely.
if (TILE_DISK_CACHE && !empty($features)) {
    if (!is_dir($tile_dir)) { @mkdir($tile_dir, 0775, true); }
    @file_put_contents($tile_file, $tile_bytes_gz);
}

header('Content-Type: application/x-protobuf');
header('Content-Encoding: gzip');
header('Vary: Accept-Encoding');
header('Cache-Control: public, max-age=' . (empty($features) ? 60 : $cache_ttl));
header('Content-Length: ' . strlen($tile_bytes_gz));
header('X-Tile-Cache: MISS');

echo $tile_bytes_gz;
