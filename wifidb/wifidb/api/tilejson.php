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

The `tiles` array points directly to the pre-generated static tile files under
out/tiles/ (MVT/PBF) or out/tiles-mlt/ (MLT).  Apache serves them with the
correct Content-Type/Content-Encoding headers via the .htaccess in each directory.
The mvtd.php / mltd.php daemons generate tiles z1–z19 for all buckets;
no PHP tile-generation script is invoked per tile request.

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

// ── Format selection ─────────────────────────────────────────────────────────
// ?format=mlt   → MapLibre Tile (MLT) descriptor, served by mlt.php
// ?format=mvt   → Mapbox Vector Tile (PBF) descriptor (default), served by mvt.php
//
// The TileJSON "format" field is a MapLibre extension to TileJSON 3.0.0.
// MapLibre GL JS ≥ 5.12 and MapLibre Native (Android ≥ 12.1.0 / iOS ≥ 6.2.0)
// read this field to select the correct tile decoder.
$format = preg_replace('/[^a-z]/', '', strtolower((string)@$_REQUEST['format']));
if ($format !== 'mlt') $format = 'mvt';   // default

// ── Tile URL ─────────────────────────────────────────────────────────────────
// Point directly to the pre-generated static tile files.  Apache serves them
// with the correct Content-Type/Content-Encoding via out/tiles/.htaccess and
// out/tiles-mlt/.htaccess.  No PHP tile-generation script is involved.
if ($format === 'mlt') {
    $tiles_url = $base_url . '/out/tiles-mlt/' . $bucket . '/{z}/{x}/{y}.mlt';
} else {
    $tiles_url = $base_url . '/out/tiles/' . $bucket . '/{z}/{x}/{y}.pbf';
}
$tile_maxzoom = 19;

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
                'sectype'       => 'Number',  // 0=unknown, 1=open, 2=WEP, 3=secure
                'chan'           => 'Number',  // WiFi channel
                'points'        => 'Number',  // total observation points
                'high_gps_sig'  => 'Number',  // best GPS signal strength
                'high_gps_rssi' => 'Number',  // best GPS RSSI
                'radio'         => 'String',  // radio type (e.g. "802.11n", "802.11ax")
                'mac'           => 'String',  // BSSID
                'user'          => 'String',  // submitting username
                'ssid'          => 'String',  // network name
                'auth'          => 'String',  // auth method (e.g. "WPA2-Personal")
                'encry'         => 'String',  // encryption (e.g. "CCMP", "TKIP")
                'nt'            => 'String',  // network type (e.g. "Infrastructure")
                'btx'           => 'String',  // basic transmit rates
                'otx'           => 'String',  // optional transmit rates
                'fa'            => 'String',  // first seen datetime
                'la'            => 'String',  // last seen datetime
                'lat'           => 'String',  // latitude (decimal degrees string)
                'lon'           => 'String',  // longitude (decimal degrees string)
                'alt'           => 'String',  // altitude (metres string)
                'manuf'         => 'String',  // MAC manufacturer name
                'id_str'        => 'String',  // AP database ID (string form)
            ],
        ],
    ],
];

// Add format field for MLT tiles (MapLibre extension to TileJSON 3.0.0).
if ($format === 'mlt') {
    $tilejson['format'] = 'mlt';
}

echo json_encode($tilejson, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
