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
 */
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
