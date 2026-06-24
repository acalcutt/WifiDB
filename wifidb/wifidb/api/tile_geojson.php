<?php
error_reporting(1);
@ini_set('display_errors', 0);
/*
tile_geojson.php — Per-tile GeoJSON API
Copyright (C) 2024 Andrew Calcutt

Returns a GeoJSON FeatureCollection of APs whose best-GPS point falls inside a
single Web-Mercator tile (z/x/y), filtered by age bucket.  Designed for use as
a MapLibre GeoJSON source with a tile-URL template so the client only fetches
data for visible tiles rather than loading the entire dataset.

Usage:
  /api/tile_geojson.php?z={z}&x={x}&y={y}&bucket={bucket}

Parameters:
  z       (int, required)   Zoom level (0–20)
  x       (int, required)   Tile column
  y       (int, required)   Tile row
  bucket  (string, required) Age bucket — one of:
            daily      last 36 hours
            weekly     last 7 days  (excluding daily)
            monthly    last 1 month (excluding weekly)
            0to1year   1 month – 1 year
            1to2year   1 year  – 2 years
            2to3year   2 years – 3 years
            legacy     older than 3 years

Response:
  application/json — GeoJSON FeatureCollection, CORS-enabled.
  HTTP 400 on invalid parameters.
  HTTP 204 (empty) when the tile contains no features (saves bandwidth).

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');

// ── CORS ─────────────────────────────────────────────────────────────────────
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

// ── Input validation ─────────────────────────────────────────────────────────
$z = filter_input(INPUT_GET, 'z', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0, 'max_range' => 20]]);
$x = filter_input(INPUT_GET, 'x', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$y = filter_input(INPUT_GET, 'y', FILTER_VALIDATE_INT, ['options' => ['min_range' => 0]]);
$bucket = preg_replace('/[^a-z0-9]/', '', strtolower((string)@$_REQUEST['bucket']));

$valid_buckets = ['daily', 'weekly', 'monthly', '0to1year', '1to2year', '2to3year', 'legacy'];

if ($z === false || $z === null || $x === false || $x === null || $y === false || $y === null) {
    http_response_code(400);
    header('Content-type: application/json');
    echo json_encode(['error' => 'Missing or invalid z, x, y parameters (integers required)']);
    exit;
}

if (!in_array($bucket, $valid_buckets, true)) {
    http_response_code(400);
    header('Content-type: application/json');
    echo json_encode(['error' => 'Invalid bucket. Must be one of: ' . implode(', ', $valid_buckets)]);
    exit;
}

// ── Tile → lat/lng bounding box ───────────────────────────────────────────────
// Standard Web-Mercator (EPSG:3857) tile bounds.
function tile_to_lon(int $x, int $z): float {
    return $x / pow(2, $z) * 360.0 - 180.0;
}
function tile_to_lat(int $y, int $z): float {
    $n = M_PI - 2.0 * M_PI * $y / pow(2, $z);
    return rad2deg(atan(sinh($n)));
}

$lon_min = tile_to_lon($x,     $z);
$lon_max = tile_to_lon($x + 1, $z);
$lat_max = tile_to_lat($y,     $z);
$lat_min = tile_to_lat($y + 1, $z);

// ── Decimal degrees → NMEA Degrees-Minutes (DDMM.MMMM) ───────────────────────
// Lat/Lon in wifi_gps are stored in DM format (as written by GPS receivers),
// e.g. 42°13.71' N is stored as 4213.7100.  BETWEEN must use DM bounds.
function dd2dm(float $dd): float {
    $sign    = ($dd < 0) ? -1 : 1;
    $abs     = abs($dd);
    $degrees = (int)$abs;
    $minutes = ($abs - $degrees) * 60.0;
    return $sign * ($degrees * 100 + $minutes);
}

$lat_min_dm = dd2dm($lat_min);
$lat_max_dm = dd2dm($lat_max);
$lon_min_dm = dd2dm($lon_min);
$lon_max_dm = dd2dm($lon_max);

// ── Date range for the requested bucket ──────────────────────────────────────
$now = new DateTime('now', new DateTimeZone('UTC'));
$now_str = $now->format('Y-m-d H:i:s');

// Each bucket is expressed as a [start, end) window using DateInterval offsets.
// null means "no bound on that side" (open-ended).
$date_filters = [
    'daily'    => ['start_interval' => 'PT36H',  'end_interval' => null],
    'weekly'   => ['start_interval' => 'P7D',    'end_interval' => 'PT36H'],
    'monthly'  => ['start_interval' => 'P1M',    'end_interval' => 'P7D'],
    '0to1year' => ['start_interval' => 'P1Y',    'end_interval' => 'P1M'],
    '1to2year' => ['start_interval' => 'P2Y',    'end_interval' => 'P1Y'],
    '2to3year' => ['start_interval' => 'P3Y',    'end_interval' => 'P2Y'],
    'legacy'   => ['start_interval' => null,     'end_interval' => 'P3Y'],
];

$filter = $date_filters[$bucket];

$start_date = null;
$end_date   = null;

if ($filter['start_interval'] !== null) {
    $d = clone $now;
    $d->sub(new DateInterval($filter['start_interval']));
    $start_date = $d->format('Y-m-d H:i:s');
}
if ($filter['end_interval'] !== null) {
    $d = clone $now;
    $d->sub(new DateInterval($filter['end_interval']));
    $end_date = $d->format('Y-m-d H:i:s');
}

// ── Debug mode ────────────────────────────────────────────────────────────────
if ((int)@$_REQUEST['debug'] === 1) {
    header('Content-type: application/json');
    echo json_encode([
        'tile'       => ['z' => $z, 'x' => $x, 'y' => $y],
        'bbox_dd'    => ['lat_min' => $lat_min, 'lat_max' => $lat_max,
                         'lon_min' => $lon_min, 'lon_max' => $lon_max],
        'bbox_dm'    => ['lat_min' => $lat_min_dm, 'lat_max' => $lat_max_dm,
                         'lon_min' => $lon_min_dm, 'lon_max' => $lon_max_dm],
        'bucket'     => $bucket,
        'start_date' => $start_date,
        'end_date'   => $end_date,
        'db_service' => $dbcore->sql->service,
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    exit;
}

// ── Query via shared export function ─────────────────────────────────────────
$result = $dbcore->export->BboxDateArray(
    $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
    $start_date, $end_date,
    null, 5000
);

if (empty($result['data'])) {
    header('Content-type: application/json');
    echo '{"type":"FeatureCollection","features":[]}';
    exit;
}

// ── Build GeoJSON ─────────────────────────────────────────────────────────────
$features = [];
foreach ($result['data'] as $ap_info) {
    $features[] = $dbcore->createGeoJSON->CreateApFeature($ap_info);
}

header('Content-type: application/json');
echo $dbcore->createGeoJSON->createGeoJSONstructure(implode(',', $features));
