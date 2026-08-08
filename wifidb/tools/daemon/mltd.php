<?php
/*
mltd.php — MapLibre Tile (MLT) Pre-generation Daemon
Copyright (C) 2024 Andrew Calcutt

Pre-generates gzip-compressed MLT tiles for every age-bucket across a
configurable range of zoom levels.  tilejson.php?format=mlt then returns these
as static HTTP URLs (served via mlt.php which checks the on-disk cache first).

Architecture mirrors mvtd.php exactly: ONE keyset-paginated DB scan per
bucket (WHERE AP_ID > last_id ORDER BY AP_ID LIMIT n — flat per-page cost),
rows stored once in $aps, per-zoom $tile_map holds integer indices into $aps.

Output directory:
  {$output_dir}/{bucket}/{z}/{x}/{y}.mlt   (content is gzip-compressed MLT)

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if (!(require(dirname(__FILE__).'/../daemon.config.inc.php'))) {
    die("You need to create and configure your [tools]/daemon.config.inc.php");
}
if ($daemon_config['wifidb_install'] === '') {
    die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");
}
require $daemon_config['wifidb_install'].'/lib/init.inc.php';
require $daemon_config['wifidb_install'].'/lib/mvt.inc.php';   // project_to_tile, lon_to_tile_x, lat_to_tile_y, dd2dm, bucket_date_window
require $daemon_config['wifidb_install'].'/lib/mlt.inc.php';   // mlt_encode_tile
require $daemon_config['wifidb_install'].'/lib/spatial.inc.php';  // morton_encode, assign_feature_minzoom

$dbcore->daemon_name    = 'MLT Tile Generator';
$dbcore->lastedit       = '2024-06-24';
$dbcore->daemon_version = '1.0';

// ── PID file ───────────────────────────────────────────────────────────────
if (true) {
    if (!file_exists($dbcore->pid_file_loc)) {
        mkdir($dbcore->pid_file_loc);
    }
    $pid_filename   = 'mltd_' . $dbcore->This_is_me . '_' . date('YmdHis') . '.pid';
    $dbcore->pid_file = $dbcore->pid_file_loc . $pid_filename;
    if (!file_exists($dbcore->pid_file_loc)) {
        if (!mkdir($dbcore->pid_file_loc)) {
            echo "Could not create PID folder at: $dbcore->pid_file_loc\n";
            exit(-4);
        }
    }
    if (file_put_contents($dbcore->pid_file, $dbcore->This_is_me) === false) {
        echo "Could not write PID file ($dbcore->pid_file)\n";
        exit(-5);
    }

    echo "\nWiFiDB " . $dbcore->ver_array['wifidb']
        . " - {$dbcore->daemon_name} {$dbcore->daemon_version}, {$dbcore->lastedit}, GPLv2"
        . "\nPID File: [ $dbcore->pid_file ]"
        . "\nPID:      [ $dbcore->This_is_me ]"
        . "\nLog Level: " . $dbcore->log_level . "\n\n";
}

// ── Configuration ─────────────────────────────────────────────────────────────
$min_zoom = $dbcore->tile_min_zoom;   // configured in config.inc.php 'tile_min_zoom'
$max_zoom = $dbcore->tile_max_zoom;   // configured in config.inc.php 'tile_max_zoom'

$data_bbox = [
    'lat_min' => -85.0,
    'lat_max' =>  85.0,
    'lon_min' => -180.0,
    'lon_max' =>  180.0,
];

$page_size = 50000;

// ── Z-order thinning scale ────────────────────────────────────────────────────
// Controls how aggressively the Morton-curve spatial sort thins features at low
// zoom levels.  See mvtd.php for the detailed description.  Must be kept in
// sync with $drop_scale_pixels in mvtd.php so MVT and MLT tiles contain the
// same feature sets.
$drop_scale_pixels = 1.5;

// Must match $bucket_cap_fmz in mvtd.php.  See mvtd.php for full description.
// heatmap/cell_heatmap use cap=7 to prevent swap-thrash on 9 M+ AP datasets.
$bucket_cap_fmz = [
    'heatmap'      => 7,
    'cell_heatmap' => 7,
];

// Output directory — parallel to out/tiles/ but for .mlt files.
$output_dir = rtrim($dbcore->PATH, '/') . '/out/tiles-mlt';

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
    // Combined all-ages heatmap-only buckets — see mvtd.php for rationale.
    'heatmap'        =>   604800,  //  1 week
    'cell_heatmap'   =>   604800,
];

// Per-bucket maximum tile age in seconds.
// Any tile file older than this is deleted during the cleanup sweep, regardless
// of whether the daemon would regenerate it.  Set to roughly 2× the bucket's
// own time window so stale tiles are purged once the data has fully rolled out
// of the window.
$bucket_max_age = [
    'daily'     =>    172800,  //  2 days   (bucket window: 1 day)
    'weekly'    =>   1209600,  //  14 days  (bucket window: 7 days)
    'monthly'   =>   5184000,  //  60 days  (bucket window: ~30 days)
    '0to1year'  =>  31536000,  //  1 year
    '1to2year'  =>  31536000,  //  1 year
    '2to3year'  =>  31536000,  //  1 year
    '3to5year'  =>  63072000,  //  2 years
    '5to10year' =>  63072000,  //  2 years
    '10yrplus'  =>  63072000,  //  2 years
    'cell_daily'     =>    172800,
    'cell_weekly'    =>   1209600,
    'cell_monthly'   =>   5184000,
    'cell_0to1year'  =>  31536000,
    'cell_1to2year'  =>  31536000,
    'cell_2to3year'  =>  31536000,
    'cell_3to5year'  =>  63072000,
    'cell_5to10year' =>  63072000,
    'cell_10yrplus'  =>  63072000,
    'heatmap'        =>   5184000,  //  60 days
    'cell_heatmap'   =>   5184000,
];

// ── CLI flags ─────────────────────────────────────────────────────────────────
$argv_safe   = $argv ?? [];
$force_regen = in_array('--force', $argv_safe, true);

$single_bucket = null;
for ($_i = 1, $_nc = count($argv_safe); $_i < $_nc; $_i++) {
    if ($argv_safe[$_i] === '--bucket') {
        $single_bucket = $argv_safe[++$_i] ?? null;
    }
}

echo "Output dir : {$output_dir}\n";
echo "Dir exists : " . (is_dir($output_dir) ? 'YES' : 'NO — will be created on first non-empty tile') . "\n";
echo "Writable   : " . (is_writable(dirname($output_dir)) ? 'YES' : 'NO — check permissions!') . "\n\n";

function ts(): string { return date('[Y-m-d H:i:s] '); }

// ── Encode a tile from pre-fetched AP rows (MLT version) ─────────────────────
// Mirrors encode_tile_from_points() in mvtd.php exactly, but writes MLT instead
// of MVT.  Same algorithm: per-tile Morton-gap sort, pixel+sectype dedup, then
// gzip retry loop keeping sparsest features until tile ≤ $max_gz_bytes.
function encode_mlt_tile_from_points(
    int    $z, int $x, int $y,
    string $bucket,
    array  $idxs,
    array  $all_aps,
    int    $max_gz_bytes = 750000
): ?string {

    // Step 1: project to tile pixels + per-tile Morton index.
    $pts = [];
    foreach ($idxs as $idx) {
        $ap = $all_aps[$idx];
        [$px, $py] = project_to_tile((float)$ap['lat'], (float)$ap['lon'], $z, $x, $y);
        $pts[] = [
            'idx'    => $idx,
            'px'     => $px,
            'py'     => $py,
            'morton' => morton_encode((float)$ap['lat'], (float)$ap['lon']),
        ];
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

    // Step 4: deduplicate same-pixel + same-sectype.
    $seen    = [];
    $deduped = [];
    foreach ($pts as $pt) {
        $ap = $all_aps[$pt['idx']];
        $k  = $pt['px'] . ':' . $pt['py'] . ':' . (int)$ap['sectype'];
        if (!isset($seen[$k])) { $seen[$k] = true; $deduped[] = $pt; }
    }
    unset($seen, $pts);

    if (empty($deduped)) return null;

    // Step 5: encode → gzip retry loop (target ≤ $max_gz_bytes).
    $keep     = count($deduped);
    $gz_bytes = null;

    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset   = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);
        $features = [];
        foreach ($subset as $pt) {
            $ap = $all_aps[$pt['idx']];
            $features[] = [
                'id'            => (int)$ap['id'],
                'x'             => $pt['px'],
                'y'             => $pt['py'],
                'sectype'       => (int)$ap['sectype'],
                'chan'           => (int)$ap['chan'],
                'radio'         => $ap['radio'],
                'mac'           => $ap['mac'],
                'user'          => $ap['user'],
                'ssid'          => $ap['ssid'],
                'auth'          => $ap['auth'],
                'encry'         => $ap['encry'],
                'nt'            => $ap['nt'],
                'btx'           => $ap['btx'],
                'otx'           => $ap['otx'],
                'fa'            => $ap['fa'],
                'la'            => $ap['la'],
                'age_days'      => (int)$ap['age_days'],
                'points'        => (int)$ap['points'],
                'high_gps_sig'  => (int)$ap['high_gps_sig'],
                'high_gps_rssi' => (int)$ap['high_gps_rssi'],
                'lat'           => (string)$ap['lat'],
                'lon'           => (string)$ap['lon'],
                'alt'           => $ap['alt'],
                'manuf'         => $ap['manuf'],
            ];
        }

        if (empty($features)) return null;
        $mlt_bytes = mlt_encode_tile($bucket, $features);
        if ($mlt_bytes === '') return null;
        $gz_bytes  = gzencode($mlt_bytes, 6);

        $sz = strlen($gz_bytes);
        if ($sz <= $max_gz_bytes) break;
        $new_keep = max(1, (int)floor($keep * $max_gz_bytes / $sz));
        $pct      = round(100.0 * $new_keep / count($deduped), 2);
        echo "  tile {$z}/{$x}/{$y} size is {$sz} >{$max_gz_bytes}; keeping sparsest {$pct}% ({$new_keep}/{$keep})\n";
        if ($new_keep >= $keep) break;
        $keep = $new_keep;
    }

    return $gz_bytes;
}

// ── Heatmap-only WiFi tile encoder (MLT version) ─────────────────────────────
// MLT mirror of encode_heatmap_tile_from_points() in mvtd.php.
// Encodes only sectype + age_days per feature.  See mvtd.php for rationale.
function encode_heatmap_mlt_tile_from_points(
    int    $z, int $x, int $y,
    string $bucket,
    array  $idxs,
    array  $all_aps,
    int    $max_gz_bytes = 750000
): ?string {

    $pts = [];
    foreach ($idxs as $idx) {
        $ap = $all_aps[$idx];
        [$px, $py] = project_to_tile((float)$ap['lat'], (float)$ap['lon'], $z, $x, $y);
        $pts[] = [
            'idx'    => $idx,
            'px'     => $px,
            'py'     => $py,
            'morton' => morton_encode((float)$ap['lat'], (float)$ap['lon']),
        ];
    }

    usort($pts, fn($a, $b) => $a['morton'] <=> $b['morton']);
    $prev_m = PHP_INT_MIN;
    foreach ($pts as &$pt) {
        $pt['gap'] = ($prev_m === PHP_INT_MIN) ? PHP_INT_MAX : ($pt['morton'] - $prev_m);
        $prev_m    = $pt['morton'];
    }
    unset($pt);

    usort($pts, fn($a, $b) => $b['gap'] <=> $a['gap']);

    $seen    = [];
    $deduped = [];
    foreach ($pts as $pt) {
        $ap = $all_aps[$pt['idx']];
        $k  = $pt['px'] . ':' . $pt['py'] . ':' . (int)$ap['sectype'];
        if (!isset($seen[$k])) { $seen[$k] = true; $deduped[] = $pt; }
    }
    unset($seen, $pts);

    if (empty($deduped)) return null;

    $keep     = count($deduped);
    $gz_bytes = null;

    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset   = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);
        $features = [];
        foreach ($subset as $pt) {
            $ap = $all_aps[$pt['idx']];
            $features[] = [
                'id'       => (int)$ap['id'],
                'x'        => $pt['px'],
                'y'        => $pt['py'],
                'sectype'  => (int)$ap['sectype'],
                'age_days' => (int)$ap['age_days'],
            ];
        }

        if (empty($features)) return null;
        $mlt_bytes = mlt_encode_tile($bucket, $features);
        if ($mlt_bytes === '') return null;
        $gz_bytes  = gzencode($mlt_bytes, 6);

        $sz = strlen($gz_bytes);
        if ($sz <= $max_gz_bytes) break;
        $new_keep = max(1, (int)floor($keep * $max_gz_bytes / $sz));
        $pct      = round(100.0 * $new_keep / count($deduped), 2);
        echo "  tile {$z}/{$x}/{$y} size is {$sz} >{$max_gz_bytes}; keeping sparsest {$pct}% ({$new_keep}/{$keep})\n";
        if ($new_keep >= $keep) break;
        $keep = $new_keep;
    }

    return $gz_bytes;
}

// ── Cell tile encoder (MLT version) ──────────────────────────────────────────
// Mirrors encode_cell_tile_from_mvt() in mvtd.php — same Morton-gap sort +
// gzip retry loop, no sectype in pixel dedup (cell data has none).
function encode_cell_mlt_tile_from_points(
    int    $z, int $x, int $y,
    string $bucket,
    array  $idxs,
    array  $all_cells,
    int    $max_gz_bytes = 750000
): ?string {

    // Step 1: project to tile pixels + per-tile Morton index.
    $pts = [];
    foreach ($idxs as $idx) {
        $cell = $all_cells[$idx];
        [$px, $py] = project_to_tile((float)$cell['lat'], (float)$cell['lon'], $z, $x, $y);
        $pts[] = [
            'idx'    => $idx,
            'px'     => $px,
            'py'     => $py,
            'morton' => morton_encode((float)$cell['lat'], (float)$cell['lon']),
        ];
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

    // Step 4: deduplicate same pixel (no sectype for cell data).
    $seen    = [];
    $deduped = [];
    foreach ($pts as $pt) {
        $k = $pt['px'] . ':' . $pt['py'];
        if (!isset($seen[$k])) { $seen[$k] = true; $deduped[] = $pt; }
    }
    unset($seen, $pts);

    if (empty($deduped)) return null;

    // Step 5: encode → gzip retry loop.
    $keep     = count($deduped);
    $gz_bytes = null;

    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset   = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);
        $features = [];
        foreach ($subset as $pt) {
            $cell = $all_cells[$pt['idx']];
            $features[] = [
                'id'       => (int)$cell['id'],
                'x'        => $pt['px'],
                'y'        => $pt['py'],
                'mac'      => (string)$cell['mac'],
                'ssid'     => (string)$cell['ssid'],
                'authmode' => (string)$cell['authmode'],
                'chan'      => (string)$cell['chan'],
                'type'     => (string)$cell['type'],
                'fa'       => (string)$cell['fa'],
                'la'       => (string)$cell['la'],
                'age_days' => (int)$cell['age_days'],
                'points'   => (int)$cell['points'],
                'rssi'     => (int)$cell['rssi'],
                'user'     => (string)$cell['user'],
            ];
        }

        if (empty($features)) return null;
        $mlt_bytes = mlt_encode_tile($bucket, $features);
        if ($mlt_bytes === '') return null;
        $gz_bytes  = gzencode($mlt_bytes, 6);

        $sz = strlen($gz_bytes);
        if ($sz <= $max_gz_bytes) break;
        $new_keep = max(1, (int)floor($keep * $max_gz_bytes / $sz));
        $pct      = round(100.0 * $new_keep / count($deduped), 2);
        echo "  tile {$z}/{$x}/{$y} size is {$sz} >{$max_gz_bytes}; keeping sparsest {$pct}% ({$new_keep}/{$keep})\n";
        if ($new_keep >= $keep) break;
        $keep = $new_keep;
    }

    return $gz_bytes;
}

// ── Main generation loop ──────────────────────────────────────────────────────
$buckets = ['daily', 'weekly', 'monthly', '0to1year', '1to2year', '2to3year', '3to5year', '5to10year', '10yrplus',
            'cell_daily', 'cell_weekly', 'cell_monthly', 'cell_0to1year', 'cell_1to2year', 'cell_2to3year', 'cell_3to5year', 'cell_5to10year', 'cell_10yrplus',
            'heatmap', 'cell_heatmap'];

if ($single_bucket !== null) {
    if (!in_array($single_bucket, $buckets)) {
        die("[mltd] Unknown --bucket value: {$single_bucket}\n");
    }
    $buckets = [$single_bucket];
}

$grand_total   = 0;
$grand_written = 0;
$grand_skipped = 0;
$grand_empty   = 0;
$grand_deleted = 0;

$run_start = microtime(true);

foreach ($buckets as $bucket) {
    $ttl          = $bucket_ttl[$bucket];
    $bucket_start = microtime(true);

    // ── Single-scan architecture (keyset-paginated) ──────────────────────────
    // One ordered scan of the bucket's APs (BboxDateArray with $last_id keyset
    // pagination — flat O(page_size) per page regardless of depth).  Rows are
    // stored once in $aps; per-zoom $tile_map holds INTEGER INDICES into $aps,
    // not row copies, so memory grows ~linearly with row count.  See mvtd.php
    // for the design rationale (same architecture).

    $is_cell = (strpos($bucket, 'cell_') === 0);
    $base_bucket = $is_cell ? substr($bucket, 5) : $bucket;
    [$start_date, $end_date] = bucket_date_window($base_bucket);
    $cap_feature_minzoom = $bucket_cap_fmz[$bucket] ?? 1;

    $lat_min_dm = dd2dm($data_bbox['lat_min']);
    $lat_max_dm = dd2dm($data_bbox['lat_max']);
    $lon_min_dm = dd2dm($data_bbox['lon_min']);
    $lon_max_dm = dd2dm($data_bbox['lon_max']);

    $label = $is_cell ? 'cells' : 'APs';
    echo ts() . "[{$bucket}] Fetching {$label} (keyset pagination)...\n";

    $aps       = [];
    $last_id   = 0;
    while (true) {
        if ($is_cell) {
            $result = $dbcore->export->BboxCellArray(
                $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
                $page_size, $last_id, $start_date, $end_date
            );
        } else {
            $result = $dbcore->export->BboxDateArray(
                $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
                $start_date, $end_date,
                null, $page_size, $last_id
            );
        }
        $rows = $result['data'] ?? [];
        if (empty($rows)) break;

        foreach ($rows as $row) {
            $lat = (float)$row['lat'];
            $lon = (float)$row['lon'];
            $rid = (int)$row['id'];
            if ($rid > $last_id) $last_id = $rid;
            if ($lat == 0.0 && $lon == 0.0) continue;

            if ($is_cell) {
                $aps[] = [
                    'id'       => $rid,
                    'lat'      => $lat,
                    'lon'      => $lon,
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
            } elseif ($bucket === 'heatmap') {
                // Heatmap tiles only need position + recency + security type.
                // Storing 5 fields instead of 22 for ~9 M APs cuts peak RAM
                // from ~12 GB to ~2 GB, preventing swap-thrash on 15 GB servers.
                $aps[] = [
                    'id'       => $rid,
                    'lat'      => $lat,
                    'lon'      => $lon,
                    'age_days' => mvt_age_days((string)$row['la']),
                    'sectype'  => (int)$row['sectype'],
                ];
            } else {
                $aps[] = [
                    'id'            => $rid,
                    'lat'           => $lat,
                    'lon'           => $lon,
                    'alt'           => (string)$row['alt'],
                    'sectype'       => (int)$row['sectype'],
                    'chan'          => (int)$row['chan'],
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
                    'manuf'         => (string)$row['manuf'],
                ];
            }
        }
        echo ts() . "[{$bucket}]   ... " . count($aps) . " {$label} fetched\n";
        if (count($rows) < $page_size) break;
    }

    $ap_count = count($aps);
    if ($ap_count === 0) {
        echo ts() . "[{$bucket}] Skipping — no {$label} in bucket.\n\n";
        continue;
    }

    echo ts() . "[{$bucket}] {$ap_count} {$label} total. Generating tiles z{$min_zoom}–z{$max_zoom}...\n";

    // ── Z-order spatial sort + feature_minzoom assignment ───────────────────
    // assign_feature_minzoom() (lib/spatial.inc.php) encodes each AP as a
    // 56-bit Morton index, sorts the array once, then assigns feature_minzoom
    // from the gap to each AP's Morton-order predecessor.  See spatial.inc.php
    // for the full algorithm description and tippecanoe attribution.
    {
        $sort_s  = microtime(true);
        $fmz_cum = assign_feature_minzoom($aps, $min_zoom, $max_zoom, $drop_scale_pixels, $cap_feature_minzoom);
        $snaps   = [];
        foreach ([1, 5, 7, 10, 13, 14] as $zs) {
            if ($zs >= $min_zoom && $zs <= $max_zoom) {
                $snaps[] = "z≤{$zs}:" . number_format($fmz_cum[$zs]);
            }
        }
        $sort_e = round(microtime(true) - $sort_s, 1);
        echo ts() . "[{$bucket}] Morton sort + feature_minzoom done ({$sort_e}s). "
            . "APs visible by zoom: " . implode(', ', $snaps) . "\n";
    }

    $bucket_written = 0;
    $bucket_total   = 0;

    for ($z = $min_zoom; $z <= $max_zoom; $z++) {
        $z_start  = microtime(true);
        $tile_map = [];  // [tx][ty] => [idx, ...]

        // Skip APs whose feature_minzoom exceeds the current zoom (same logic
        // as mvtd.php — see morton_encode comment for algorithm credit).
        foreach ($aps as $idx => $ap) {
            if ($ap['feature_minzoom'] > $z) continue;
            $tx = lon_to_tile_x($ap['lon'], $z);
            $ty = lat_to_tile_y($ap['lat'], $z);
            $tile_map[$tx][$ty][] = $idx;
        }

        $z_written = $z_skipped = $z_empty = 0;

        // ── Write tiles for this zoom ──────────────────────────────────────────
        foreach ($tile_map as $tx => $y_map) {
            foreach ($y_map as $ty => $tile_idxs) {
                $bucket_total++;
                $grand_total++;

                $tile_dir  = "{$output_dir}/{$bucket}/{$z}/{$tx}";
                $tile_file = "{$tile_dir}/{$ty}.mlt";

                if (!$force_regen && file_exists($tile_file)
                    && filesize($tile_file) >= 20          // 20 B = minimum valid gzip stream; smaller = truncated write
                    && (time() - filemtime($tile_file)) < $ttl) {
                    $z_skipped++;
                    $grand_skipped++;
                    continue;
                }

                if ($is_cell) {
                    $gz_bytes = encode_cell_mlt_tile_from_points($z, $tx, $ty, $bucket, $tile_idxs, $aps, $dbcore->tile_max_gz_bytes);
                } elseif ($bucket === 'heatmap') {
                    $gz_bytes = encode_heatmap_mlt_tile_from_points($z, $tx, $ty, $bucket, $tile_idxs, $aps, $dbcore->tile_max_gz_bytes);
                } else {
                    $gz_bytes = encode_mlt_tile_from_points($z, $tx, $ty, $bucket, $tile_idxs, $aps, $dbcore->tile_max_gz_bytes);
                }

                if ($gz_bytes === null) {
                    if (file_exists($tile_file)) unlink($tile_file);
                    $z_empty++;
                    $grand_empty++;
                    continue;
                }

                if (!is_dir($tile_dir)) mkdir($tile_dir, 0775, true);
                if (file_put_contents($tile_file, $gz_bytes) !== false) {
                    $z_written++;
                    $bucket_written++;
                    $grand_written++;
                } else {
                    echo "  [ERROR] Failed to write {$tile_file}\n";
                }
            }
        }

        // ── Delete tiles that existed before but have no APs now ──────────────
        $z_dir = "{$output_dir}/{$bucket}/{$z}";
        if (is_dir($z_dir)) {
            foreach (glob("{$z_dir}/*", GLOB_ONLYDIR) as $x_dir) {
                $tx = (int)basename($x_dir);
                foreach (glob("{$x_dir}/*.mlt") as $mlt_f) {
                    $ty = (int)basename($mlt_f, '.mlt');
                    if (!isset($tile_map[$tx][$ty])) {
                        unlink($mlt_f);
                        $grand_deleted++;
                    }
                }
                if (!glob("{$x_dir}/*.mlt")) @rmdir($x_dir);
            }
        }

        $z_elapsed = round(microtime(true) - $z_start, 1);
        echo ts() . "[{$bucket}] z={$z}: {$z_written} written, {$z_skipped} skipped, {$z_empty} empty — {$z_elapsed}s\n";
        unset($tile_map);
    }

    unset($aps);

    $bucket_elapsed = round(microtime(true) - $bucket_start, 1);
    echo ts() . "[{$bucket}] Done — {$bucket_written}/{$bucket_total} tiles written in {$bucket_elapsed}s\n\n";
}

$grand_elapsed = round(microtime(true) - $run_start, 1);

// ── Stale tile age cleanup ────────────────────────────────────────────────────
// Sweep all zoom levels (z1–z19) and delete any .mlt tile whose mtime exceeds
// the bucket's max age.  This is separate from the data-driven deletion in the
// main loop: that removes tiles with no APs in the current run; this removes
// tiles that are simply too old relative to the bucket window.
echo ts() . "--- Stale tile cleanup (max-age sweep, all z) ---\n";
$cleanup_deleted = 0;
$now = time();
foreach ($buckets as $bucket) {
        $max_age    = $bucket_max_age[$bucket];
        $bucket_dir = "{$output_dir}/{$bucket}";
        if (!is_dir($bucket_dir)) continue;

        for ($z = $min_zoom; $z <= $max_zoom + 2; $z++) {
            $z_dir = "{$bucket_dir}/{$z}";
            if (!is_dir($z_dir)) continue;

            foreach (glob("{$z_dir}/*", GLOB_ONLYDIR) as $x_dir) {
                foreach (glob("{$x_dir}/*.mlt") as $f) {
                    if (($now - filemtime($f)) > $max_age) {
                        unlink($f);
                        $cleanup_deleted++;
                    }
                }
                @rmdir($x_dir);
            }
            @rmdir($z_dir);
        }
    }
    echo "  Deleted {$cleanup_deleted} tiles exceeding bucket max-age.\n";
echo "--- End cleanup ---\n\n";

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total tiles considered : {$grand_total}\n";
echo "Written (non-empty)    : {$grand_written}\n";
echo "Skipped (fresh cache)  : {$grand_skipped}\n";
echo "Empty (no data)        : {$grand_empty}\n";
echo "Deleted (no data now)  : {$grand_deleted}\n";
echo "Elapsed                : {$grand_elapsed}s\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

unlink($dbcore->pid_file);
