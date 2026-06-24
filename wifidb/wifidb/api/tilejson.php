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

The `tiles` array in the response points to mvt.php which serves
Mapbox Vector Tiles (PBF) per z/x/y tile.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
header('Content-Type: application/json');
header('Cache-Control: public, max-age=300'); // 5-minute cache on the metadata

// ── Input validation ─────────────────────────────────────────────────────────
$bucket = preg_replace('/[^a-z0-9]/', '', strtolower((string)@$_REQUEST['bucket']));

$bucket_meta = [
    'daily'    => ['name' => 'WifiDB Daily',    'desc' => 'WiFi APs active in the last 36 hours'],
    'weekly'   => ['name' => 'WifiDB Weekly',   'desc' => 'WiFi APs active in the last 7 days'],
    'monthly'  => ['name' => 'WifiDB Monthly',  'desc' => 'WiFi APs active 1 week – 1 month ago'],
    '0to1year' => ['name' => 'WifiDB 0-1 Year', 'desc' => 'WiFi APs active 1 month – 1 year ago'],
    '1to2year' => ['name' => 'WifiDB 1-2 Year', 'desc' => 'WiFi APs active 1 – 2 years ago'],
    '2to3year' => ['name' => 'WifiDB 2-3 Year', 'desc' => 'WiFi APs active 2 – 3 years ago'],
    'legacy'   => ['name' => 'WifiDB Legacy',   'desc' => 'WiFi APs last active more than 3 years ago'],
];

if (!array_key_exists($bucket, $bucket_meta)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid bucket. Must be one of: ' . implode(', ', array_keys($bucket_meta))]);
    exit;
}

$base_url = rtrim($dbcore->URL_PATH, '/');

// ── Tile URL ─────────────────────────────────────────────────────────────────
// mvt.php serves all tiles.  It checks out/tiles/{bucket}/{z}/{x}/{y}.pbf first
// (the unified store written by both mvtd.php and itself); on a cache miss it
// queries the DB, generates the tile, writes it to out/tiles/, and returns it.
// This means pre-generated daemon tiles are served from the same code path as
// on-demand tiles — no separate static/dynamic mode needed.
$tiles_url    = $base_url . '/api/mvt.php?bucket=' . $bucket . '&z={z}&x={x}&y={y}';
$tile_maxzoom = 14;

$tilejson = [
    'tilejson'      => '3.0.0',
    'name'          => $bucket_meta[$bucket]['name'],
    'description'   => $bucket_meta[$bucket]['desc'],
    'version'       => '1.0.0',
    'attribution'   => '<a href="https://wifidb.net">© WifiDB contributors</a>',
    'scheme'        => 'xyz',
    'tiles'         => [$tiles_url],
    'minzoom'       => 1,
    'maxzoom'       => $tile_maxzoom,
    'bounds'        => [-180.0, -85.051129, 180.0, 85.051129],
    'vector_layers' => [
        [
            'id'          => $bucket,
            'description' => $bucket_meta[$bucket]['desc'],
            'minzoom'     => 0,
            'maxzoom'     => $tile_maxzoom,
            'fields'      => [
                'sectype' => 'Number',  // 1=open, 2=WEP, 3=secure
                'chan'     => 'Number',  // WiFi channel
                'radio'   => 'String',  // radio type (e.g. "802.11n")
                'mac'     => 'String',  // BSSID
                'user'    => 'String',  // submitting username
            ],
        ],
    ],
];

echo json_encode($tilejson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
