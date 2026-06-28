<?php
/*
spatial.inc.php — Spatial index helpers for tile generation
Copyright (C) 2024 Andrew Calcutt

Shared by:
  tools/daemon/mvtd.php   — MVT pre-generation daemon
  tools/daemon/mltd.php   — MLT pre-generation daemon

Contains:
  • Z-order (Morton) curve encoding
  • Feature minzoom assignment (tippecanoe-style drop-densest thinning)

Z-order / Morton algorithm derived from tippecanoe by Mapbox / felt.
Copyright (c) 2014-2023, Mapbox.  BSD 2-Clause License.
https://github.com/felt/tippecanoe

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

// ── Z-order (Morton) curve encoding ──────────────────────────────────────────
//
// Each AP's (lat, lon) is encoded as a 56-bit Z-order (Morton) curve index
// (28 bits per axis, interleaved: longitude in even bit positions, latitude in
// odd bit positions).  After sorting all APs by this index, spatially proximate
// APs are contiguous in the array.  The Morton gap between consecutive APs in
// sorted order reflects local spatial density: a large gap means the AP is
// spatially isolated (sparse area); a small gap means it is in a dense cluster.
//
// Assigning each AP a feature_minzoom from this gap mirrors tippecanoe's
// --drop-densest-as-needed logic and reduces the O(N × Z) per-zoom binning
// loop in the tile daemons to near-O(N), cutting low-zoom generation for the
// 9 M-row legacy bucket from hours to seconds.
//
// Spread function: interleave bits of a 28-bit integer into every other
// position of a 56-bit integer.  Input bit k → output bit 2k.
// All mask constants are < 2^63, so they are positive PHP 64-bit integers.

/**
 * Spread the low 28 bits of $v into every other bit position of a 56-bit int.
 * Used internally by morton_encode().
 */
function morton_spread(int $v): int {
    $v &= 0x0FFFFFFF;                              // clamp to 28 bits
    $v = ($v | ($v << 16)) & 0x0000FFFF0000FFFF;  // spread 16-bit groups
    $v = ($v | ($v <<  8)) & 0x00FF00FF00FF00FF;  // spread  8-bit groups
    $v = ($v | ($v <<  4)) & 0x0F0F0F0F0F0F0F0F;  // spread  4-bit groups
    $v = ($v | ($v <<  2)) & 0x3333333333333333;  // spread  2-bit groups
    $v = ($v | ($v <<  1)) & 0x5555555555555555;  // spread  1-bit groups
    return $v;
}

/**
 * Return the 56-bit Z-order (Morton) curve index for a (lat, lon) point.
 * Latitude [-90, 90] and longitude [-180, 180] are each mapped to [0, 2^28)
 * and interleaved: longitude occupies even bit positions, latitude odd.
 */
function morton_encode(float $lat, float $lon): int {
    $scale = (1 << 28) - 1;  // 268435455 — max 28-bit value
    $x = max(0, min($scale, (int)(($lon + 180.0) / 360.0 * $scale)));
    $y = max(0, min($scale, (int)(($lat +  90.0) / 180.0 * $scale)));
    return morton_spread($x) | (morton_spread($y) << 1);
}

// ── Feature minzoom assignment ────────────────────────────────────────────────

/**
 * Morton-sort $aps and assign a feature_minzoom to every entry.
 *
 * This is the main entry point for tile daemons.  It performs three steps:
 *   1. Encodes each AP's (lat, lon) as a 56-bit Morton index stored in
 *      $ap['_morton'] using morton_encode().
 *   2. Sorts $aps in-place ascending by _morton (C-level array_multisort,
 *      ~O(N log N)).  The array is re-indexed 0, 1, 2, …
 *   3. Walks the sorted array and assigns $ap['feature_minzoom'] from the
 *      Morton gap to the preceding entry.  Large gap (spatially isolated AP)
 *      → low minzoom (visible at all zoom levels).  Small gap (dense cluster)
 *      → high minzoom (only visible when zoomed in close).
 *
 * Formula (28-bit axes, tile extent = 4096 = 2^12):
 *   1 pixel² at zoom z ≈ 2^(32 − 2z) Morton units.
 *   feature_minzoom = ceil( (32 − log2(gap) + 2·log2(drop_scale_pixels)) / 2 )
 *   clamped to [$min_zoom, $max_zoom].  gap = 0 (exact duplicate) → $max_zoom.
 *
 * @param  array  &$aps               AP rows array — modified in-place.
 *                                    Each entry gains '_morton' and
 *                                    'feature_minzoom' keys.  The array is
 *                                    re-ordered (Morton sort).
 * @param  int    $min_zoom           Lowest zoom level to generate.
 * @param  int    $max_zoom           Highest zoom level to generate.
 * @param  float  $drop_scale_pixels  Minimum pixel gap before an AP is shown
 *                                    (1.5 matches tippecanoe default behaviour).
 * @param  int    $cap_feature_minzoom  Hard cap on feature_minzoom (default 1 = $min_zoom).
 *                                    Set to $min_zoom for exact tippecanoe
 *                                    --drop-densest-as-needed equivalence: every
 *                                    AP is a candidate at every zoom; the tile
 *                                    encoder's per-tile density sort + 1.5 MB
 *                                    budget decides what is actually included.
 *                                    Raise (e.g. to 13) only if RAM is tight:
 *                                    low-zoom tiles for 1.5 M-AP buckets can
 *                                    require 300-500 MB peak per tile.
 * @return array  [z => cumulative_ap_count] keyed by zoom level, for logging.
 */
function assign_feature_minzoom(
    array &$aps,
    int    $min_zoom,
    int    $max_zoom,
    float  $drop_scale_pixels,
    int    $cap_feature_minzoom = 1
): array {
    // Fast path: when the cap ≤ min_zoom every AP is visible at every zoom level
    // (equivalent to tippecanoe --drop-densest-as-needed with no pre-filtering).
    // Skip the O(N log N) Morton sort entirely and assign feature_minzoom = min_zoom
    // to all APs.  The per-tile encoder in encode_tile_from_points() performs the
    // actual density-based dropping on a per-tile basis, exactly as tippecanoe does.
    if ($cap_feature_minzoom <= $min_zoom) {
        foreach ($aps as &$ap) {
            $ap['feature_minzoom'] = $min_zoom;
        }
        unset($ap);
        $fmz_cum = [];
        $total   = count($aps);
        for ($z = $min_zoom; $z <= $max_zoom; $z++) {
            $fmz_cum[$z] = $total;
        }
        return $fmz_cum;
    }

    // Step 1: encode each AP as a Morton index.
    foreach ($aps as &$ap) {
        $ap['_morton'] = morton_encode((float)$ap['lat'], (float)$ap['lon']);
    }
    unset($ap);

    // Step 2: sort in-place by Morton index (C quicksort, re-indexes to 0,1,2,…).
    array_multisort(array_column($aps, '_morton'), SORT_NUMERIC, SORT_ASC, $aps);

    // Step 3: assign feature_minzoom from gap to predecessor in sorted order.
    $prev_m   = -1;
    $log2_sc2 = 2.0 * log($drop_scale_pixels, 2);
    $fmz_raw  = [];

    foreach ($aps as &$ap) {
        $gap = ($prev_m >= 0) ? ($ap['_morton'] - $prev_m) : PHP_INT_MAX;
        if ($gap <= 0) {
            $fmz = $max_zoom;
        } else {
            $fmz = (int)ceil((32.0 - log($gap, 2) + $log2_sc2) / 2.0);
            $fmz = max($min_zoom, min($max_zoom, $fmz));
        }
        $ap['feature_minzoom'] = $fmz;
        if ($fmz > $cap_feature_minzoom) {
            $ap['feature_minzoom'] = $cap_feature_minzoom;
            $fmz = $cap_feature_minzoom;
        }
        $fmz_raw[$fmz]         = ($fmz_raw[$fmz] ?? 0) + 1;
        $prev_m                = $ap['_morton'];
    }
    unset($ap);

    // Build cumulative visible-AP count per zoom level for logging.
    $cum     = 0;
    $fmz_cum = [];
    for ($z = $min_zoom; $z <= $max_zoom; $z++) {
        $cum        += ($fmz_raw[$z] ?? 0);
        $fmz_cum[$z] = $cum;
    }
    return $fmz_cum;
}
