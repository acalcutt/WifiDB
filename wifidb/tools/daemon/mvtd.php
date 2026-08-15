<?php
/*
mvtd.php — Mapbox Vector Tile Pre-generation Daemon
Copyright (C) 2024 Andrew Calcutt

Pre-generates gzip-compressed PBF tiles for every age-bucket across a
configurable range of zoom levels.  tilejson.php then returns these as plain
static HTTP URLs so Apache serves tile files directly — completely bypassing
PHP processing per tile and eliminating the per-request database query that
made dynamic generation too slow for dense areas (e.g. MA/AZ with 800k APs).

Architecture — single-scan, index-binned:
  For each bucket the daemon makes ONE ordered scan of the bucket's APs using
  keyset pagination (WHERE AP_ID > last_id ORDER BY AP_ID LIMIT page_size).
  Keyset pagination keeps every page O(page_size) regardless of scan depth —
  unlike OFFSET/FETCH, which on a 9 M-row scan grew to 1300 s per 50 k page.

  Rows are stored ONCE in a flat $aps array.  For each zoom level the daemon
  builds a $tile_map of integer INDICES into $aps (not full row copies); the
  tile encoder reads $aps[$idx] when assembling each tile.  This avoids the
  7 GB+ memory blow-up that the original per-zoom row-copy design caused for
  the legacy bucket, while keeping the DB cost at exactly one scan per bucket.

Run via cron — suggested schedule:
  # Regenerate all tiles nightly (off-peak)
  0 2 * * * /usr/bin/php /path/to/tools/daemon/mvtd.php >> /var/log/mvtd.log 2>&1
  # Run multiple buckets in parallel by launching separate --bucket instances:
  # php mvtd.php --bucket daily &
  # php mvtd.php --bucket weekly &
  # (see tools/cron/update_mvt)

Output directory:
  {$output_dir}/{bucket}/{z}/{x}/{y}.pbf   (content is gzip-compressed PBF)

Tuning:
  $min_zoom  — lowest zoom level to generate
  $max_zoom  — highest zoom level to generate (12=standard, 14=slow/large)
  $page_size — APs fetched per DB page (50000 is a safe default)
  $force_regen — bypass TTL and regenerate all tiles (--force flag)

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
require $daemon_config['wifidb_install'].'/lib/mvt.inc.php';
require $daemon_config['wifidb_install'].'/lib/spatial.inc.php';  // morton_encode, assign_feature_minzoom
require $daemon_config['wifidb_install'].'/lib/pmtiles.inc.php';  // PMTilesWriter

$dbcore->daemon_name    = 'MVT Tile Generator';
$dbcore->lastedit       = '2024-06-23';
$dbcore->daemon_version = '1.0';

// ── PID file ───────────────────────────────────────────────────────────────
if (true) {
    if (!file_exists($dbcore->pid_file_loc)) {
        mkdir($dbcore->pid_file_loc);
    }
    $pid_filename   = 'mvtd_' . $dbcore->This_is_me . '_' . date('YmdHis') . '.pid';
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
// Adjust to match your data coverage and server capacity.

$min_zoom = $dbcore->tile_min_zoom;   // configured in config.inc.php 'tile_min_zoom'
$max_zoom = $dbcore->tile_max_zoom;   // configured in config.inc.php 'tile_max_zoom'

// Bounding box for the AP fetch query.
// APs outside this box are excluded from tile generation.
// The query-first approach makes this cheap: it's just a WHERE clause on the
// single bulk fetch, not a per-tile iteration multiplier.
//   World:     [-85.0, 85.0, -180.0, 180.0]
//   US+Canada: [20.0, 55.0, -130.0, -60.0]
$data_bbox = [
    'lat_min' => -85.0,
    'lat_max' =>  85.0,
    'lon_min' => -180.0,
    'lon_max' =>  180.0,
];

// Rows fetched per DB round-trip during the bulk AP fetch.
// 50000 is a safe default; increase if your server has fast network to the DB.
$page_size = 50000;



// Output directories, from mvt.inc.php so api/mvt.php reads from exactly where
// this writes.  They default to the install directory, which is right when the
// daemon runs on the web server: the .htaccess under out/tiles sets the
// Content-Type and Content-Encoding headers .pbf files need.  Override them in
// config.inc.php when generation runs elsewhere — a generator on a separate
// host writes millions of files, and they do not belong in the checkout.
$tile_dirs   = mvt_tile_dirs($dbcore);
$output_dir  = $tile_dirs['tiles'];
$archive_dir = $tile_dirs['archives'];

// Which buckets become archives is decided by mvt_bucket_output(); see there
// for why the line falls between weekly and monthly.
//
// Archives cost the TTL skip below: a .pmtiles file is written once, whole, so
// there is no per-tile mtime to compare and every run regenerates the bucket.
// At a 30-day TTL that is what happens anyway; at daily's one hour it would
// mean rebuilding sixteen times a day, which is the other half of the reason
// daily and weekly stay flat.

// Maximum features per tile for reference; actual per-tile dropping is done
// by the encoder's Morton-gap sort + 750 KB gzip retry loop (tippecanoe-style).
define('MAX_FEATURES_PER_TILE', 50000);

// ── CLI flags ─────────────────────────────────────────────────────────────────
// --force          Ignore TTL; regenerate all tiles.
// --bucket NAME    Process only the named bucket.  Run multiple instances in
//                  parallel (one per bucket) from an external wrapper script
//                  to maximise throughput; each holds its own DB connection.
$argv_safe   = $argv ?? [];
$force_regen = in_array('--force', $argv_safe, true);

$single_bucket = null;
for ($_i = 1, $_nc = count($argv_safe); $_i < $_nc; $_i++) {
    if ($argv_safe[$_i] === '--bucket') {
        $single_bucket = $argv_safe[++$_i] ?? null;
    }
}

// Both destinations, because which one a bucket uses is decided per bucket
// rather than on the command line. Naming only the flat tree made a run look
// misconfigured whenever it wrote an archive instead.
echo "Flat tiles : {$output_dir}  ("
    . (is_dir($output_dir) ? 'exists' : 'will be created')
    . ", " . (is_writable(dirname($output_dir)) ? 'writable' : 'NOT WRITABLE') . ")
";
echo "Archives   : {$archive_dir}  ("
    . (is_dir($archive_dir) ? 'exists' : 'will be created')
    . ", " . (is_writable(dirname($archive_dir)) ? 'writable' : 'NOT WRITABLE') . ")

";

function ts(): string { return date('[Y-m-d H:i:s] '); }

// ── Encode a tile from pre-fetched AP rows ────────────────────────────────────
// $idxs is the list of indices into the shared $all_aps array that the caller
// has determined belong to this tile.  No DB query is made here.  Storing only
// indices in the per-zoom tile_map (instead of full row copies) cuts memory
// usage roughly in half for large buckets.
// Uses tippecanoe-style drop-densest-as-needed thinning.
function encode_tile_from_points(
    int    $z, int $x, int $y,
    string $bucket,
    array  $idxs,
    array  $all_aps,
    int    $max_gz_bytes = 750000
): ?string {

    // ── Step 1: project to tile pixels + compute per-tile Morton index ────────
    // Morton index is computed fresh per-tile (not from the global sort) so that
    // gap calculations reflect intra-tile spatial density, not global density.
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

    // ── Step 2: sort by Morton index, then compute gap to predecessor ─────────
    // Gap = distance to nearest spatial neighbour in Morton order.
    // Large gap → isolated AP (sparse); small gap → dense cluster.
    usort($pts, fn($a, $b) => $a['morton'] <=> $b['morton']);
    $prev_m = PHP_INT_MIN;
    foreach ($pts as &$pt) {
        $pt['gap'] = ($prev_m === PHP_INT_MIN) ? PHP_INT_MAX : ($pt['morton'] - $prev_m);
        $prev_m    = $pt['morton'];
    }
    unset($pt);

    // ── Step 3: re-sort by gap descending — sparsest (largest gap) first ──────
    // This matches tippecanoe's "keep the sparsest X%" drop strategy: when we
    // slice to the first $keep elements we keep the most isolated APs and drop
    // the densest cluster members.
    usort($pts, fn($a, $b) => $b['gap'] <=> $a['gap']);

    // ── Step 4: deduplicate same-pixel + same-sectype ─────────────────────────
    // Because pts is sorted sparsest-first, the first occurrence of any
    // pixel+sectype is always the sparsest representative — correct behaviour.
    $seen    = [];
    $deduped = [];
    foreach ($pts as $pt) {
        $ap  = $all_aps[$pt['idx']];
        $k   = $pt['px'] . ':' . $pt['py'] . ':' . (int)$ap['sectype'];
        if (!isset($seen[$k])) {
            $seen[$k]  = true;
            $deduped[] = $pt;
        }
    }
    unset($seen, $pts);

    if (empty($deduped)) return null;

    // ── Step 5: encode → gzip, retry loop (tippecanoe --drop-densest-as-needed)
    // Target: compressed tile ≤ $max_gz_bytes (from config.inc.php 'tile_max_gz_bytes').
    // On each retry we keep only floor(keep × max_gz_bytes/actual_size) features,
    // always taking from the front of $deduped (= sparsest first).
    $max_gz_bytes = 750000;
    $keep         = count($deduped);
    $gz_bytes     = null;

    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);

        // ── Build MVT layer for this subset ───────────────────────────────────
        // The combined 'heatmap' bucket additionally carries 'age_days' so the
        // client can drive heatmap-weight by recency from a single source.
        // From mvt.inc.php, so the tile's tags and the archive's declared
        // vector_layers cannot describe different things.
        $is_heatmap = ($bucket === 'heatmap');
        $keys         = array_keys(mvt_bucket_fields($bucket));
        $keys_idx     = array_flip($keys);
        $values_bytes = [];
        $values_idx   = [];

        $add_value = function(string $type, $raw) use (&$values_bytes, &$values_idx): int {
            $key = $type . ':' . $raw;
            if (!isset($values_idx[$key])) {
                $values_idx[$key] = count($values_bytes);
                $values_bytes[]   = ($type === 'int')
                    ? pb_field_varint(4, (int)$raw)
                    : pb_field_string(1, (string)$raw);
            }
            return $values_idx[$key];
        };

        $features = [];
        foreach ($subset as $pt) {
            $ap   = $all_aps[$pt['idx']];
            $tags = [
                $keys_idx['sectype'],       $add_value('int', (int)$ap['sectype']),
                $keys_idx['chan'],           $add_value('int', (int)$ap['chan']),
                $keys_idx['radio'],         $add_value('str', (string)$ap['radio']),
                $keys_idx['mac'],           $add_value('str', (string)$ap['mac']),
                $keys_idx['user'],          $add_value('str', (string)$ap['user']),
                $keys_idx['ssid'],          $add_value('str', (string)$ap['ssid']),
                $keys_idx['auth'],          $add_value('str', (string)$ap['auth']),
                $keys_idx['encry'],         $add_value('str', (string)$ap['encry']),
                $keys_idx['nt'],            $add_value('str', (string)$ap['nt']),
                $keys_idx['btx'],           $add_value('str', (string)$ap['btx']),
                $keys_idx['otx'],           $add_value('str', (string)$ap['otx']),
                $keys_idx['fa'],            $add_value('str', (string)$ap['fa']),
                $keys_idx['la'],            $add_value('str', (string)$ap['la']),
                $keys_idx['points'],        $add_value('int', (int)$ap['points']),
                $keys_idx['high_gps_sig'],  $add_value('int', (int)$ap['high_gps_sig']),
                $keys_idx['high_gps_rssi'], $add_value('int', (int)$ap['high_gps_rssi']),
                $keys_idx['lat'],           $add_value('str', (string)$ap['lat']),
                $keys_idx['lon'],           $add_value('str', (string)$ap['lon']),
                $keys_idx['alt'],           $add_value('str', (string)$ap['alt']),
                $keys_idx['manuf'],         $add_value('str', (string)$ap['manuf']),
                $keys_idx['id_str'],        $add_value('str', (string)$ap['id']),
            ];
            if ($is_heatmap) {
                $tags[] = $keys_idx['age_days'];
                $tags[] = $add_value('int', (int)$ap['age_days']);
            }
            $features[] = mvt_encode_point_feature((int)$ap['id'], $pt['px'], $pt['py'], $tags);
        }

        if (empty($features)) return null;

        $layer_bytes = mvt_encode_layer($bucket, $features, $keys, $values_bytes);
        $tile_bytes  = mvt_encode_tile($layer_bytes);
        $gz_bytes    = gzencode($tile_bytes, 6);

        $sz = strlen($gz_bytes);
        if ($sz <= $max_gz_bytes) break;

        // Tile too large — log and scale down proportionally, same as tippecanoe.
        $new_keep = max(1, (int)floor($keep * $max_gz_bytes / $sz));
        $pct      = round(100.0 * $new_keep / count($deduped), 2);
        echo "  tile {$z}/{$x}/{$y} size is {$sz} >{$max_gz_bytes}; keeping sparsest {$pct}% ({$new_keep}/{$keep})\n";
        if ($new_keep >= $keep) break; // no progress; accept oversized tile
        $keep = $new_keep;
    }

    return $gz_bytes;
}

// ── Heatmap-only WiFi tile encoder ───────────────────────────────────────────
// Used exclusively for the 'heatmap' bucket (all ages combined, ~9 M APs).
// Encodes only sectype + age_days per feature (2 fields vs 22 in the regular
// encoder).  Combined with cap_feature_minzoom=7 and the minimal $aps field
// set, this keeps both RAM and per-tile CPU manageable for large datasets.
// Algorithm identical to encode_tile_from_points(): Morton-gap sort → sparsest-
// first dedup → gzip retry loop.
function encode_heatmap_tile_from_points(
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
        if (!isset($seen[$k])) {
            $seen[$k]  = true;
            $deduped[] = $pt;
        }
    }
    unset($seen, $pts);

    if (empty($deduped)) return null;

    $keys     = array_keys(mvt_bucket_fields($bucket));
    $keys_idx = array_flip($keys);
    $keep     = count($deduped);
    $gz_bytes = null;

    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);

        $values_bytes = [];
        $values_idx   = [];
        $add_value = function(string $type, $raw) use (&$values_bytes, &$values_idx): int {
            $key = $type . ':' . $raw;
            if (!isset($values_idx[$key])) {
                $values_idx[$key] = count($values_bytes);
                $values_bytes[]   = ($type === 'int')
                    ? pb_field_varint(4, (int)$raw)
                    : pb_field_string(1, (string)$raw);
            }
            return $values_idx[$key];
        };

        $features = [];
        foreach ($subset as $pt) {
            $ap   = $all_aps[$pt['idx']];
            $tags = [
                $keys_idx['sectype'],  $add_value('int', (int)$ap['sectype']),
                $keys_idx['age_days'], $add_value('int', (int)$ap['age_days']),
            ];
            $features[] = mvt_encode_point_feature((int)$ap['id'], $pt['px'], $pt['py'], $tags);
        }

        if (empty($features)) return null;

        $layer_bytes = mvt_encode_layer($bucket, $features, $keys, $values_bytes);
        $tile_bytes  = mvt_encode_tile($layer_bytes);
        $gz_bytes    = gzencode($tile_bytes, 6);

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

// ── Cell tile encoder ────────────────────────────────────────────────────────
// Mirrors encode_tile_from_points() — same Morton-gap sort + gzip retry loop.
// Layer name = $bucket (e.g. 'cell_daily'); pixel dedup uses px:py (no sectype).
function encode_cell_tile_from_mvt(
    int    $z, int $x, int $y,
    string $bucket,
    array  $idxs,
    array  $all_cells,
    int    $max_gz_bytes = 750000
): ?string {

    // ── Step 1: project + per-tile Morton index ───────────────────────────────
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

    // ── Step 2: Morton sort + gap ─────────────────────────────────────────────
    usort($pts, fn($a, $b) => $a['morton'] <=> $b['morton']);
    $prev_m = PHP_INT_MIN;
    foreach ($pts as &$pt) {
        $pt['gap'] = ($prev_m === PHP_INT_MIN) ? PHP_INT_MAX : ($pt['morton'] - $prev_m);
        $prev_m    = $pt['morton'];
    }
    unset($pt);

    // ── Step 3: sort by gap descending (sparsest first) ───────────────────────
    usort($pts, fn($a, $b) => $b['gap'] <=> $a['gap']);

    // ── Step 4: deduplicate same pixel (no sectype for cell data) ─────────────
    $seen    = [];
    $deduped = [];
    foreach ($pts as $pt) {
        $k = $pt['px'] . ':' . $pt['py'];
        if (!isset($seen[$k])) {
            $seen[$k]  = true;
            $deduped[] = $pt;
        }
    }
    unset($seen, $pts);

    if (empty($deduped)) return null;

    // ── Step 5: encode → gzip retry loop ─────────────────────────────────────
    $max_gz_bytes = 750000;
    $keep         = count($deduped);
    $gz_bytes     = null;

    for ($attempt = 0; $attempt < 5 && $keep >= 1; $attempt++) {
        $subset = ($keep === count($deduped)) ? $deduped : array_slice($deduped, 0, $keep);

        $is_heatmap = ($bucket === 'cell_heatmap');
        $keys         = array_keys(mvt_bucket_fields($bucket));
        $keys_idx     = array_flip($keys);
        $values_bytes = [];
        $values_idx   = [];

        $add_value = function(string $type, $raw) use (&$values_bytes, &$values_idx): int {
            $key = $type . ':' . $raw;
            if (!isset($values_idx[$key])) {
                $values_idx[$key] = count($values_bytes);
                $values_bytes[]   = ($type === 'int')
                    ? pb_field_varint(4, (int)$raw)
                    : pb_field_string(1, (string)$raw);
            }
            return $values_idx[$key];
        };

        $features = [];
        foreach ($subset as $pt) {
            $cell = $all_cells[$pt['idx']];
            $tags = [
                $keys_idx['mac'],      $add_value('str', (string)$cell['mac']),
                $keys_idx['ssid'],     $add_value('str', (string)$cell['ssid']),
                $keys_idx['authmode'], $add_value('str', (string)$cell['authmode']),
                $keys_idx['chan'],     $add_value('str', (string)$cell['chan']),
                $keys_idx['type'],     $add_value('str', (string)$cell['type']),
                $keys_idx['fa'],       $add_value('str', (string)$cell['fa']),
                $keys_idx['la'],       $add_value('str', (string)$cell['la']),
                $keys_idx['points'],   $add_value('int', (int)$cell['points']),
                $keys_idx['rssi'],     $add_value('int', (int)$cell['rssi']),
                $keys_idx['user'],     $add_value('str', (string)$cell['user']),
                $keys_idx['id_str'],   $add_value('str', (string)$cell['id']),
            ];
            if ($is_heatmap) {
                $tags[] = $keys_idx['age_days'];
                $tags[] = $add_value('int', (int)$cell['age_days']);
            }
            $features[] = mvt_encode_point_feature((int)$cell['id'], $pt['px'], $pt['py'], $tags);
        }

        if (empty($features)) return null;

        $layer_bytes = mvt_encode_layer($bucket, $features, $keys, $values_bytes);
        $tile_bytes  = mvt_encode_tile($layer_bytes);
        $gz_bytes    = gzencode($tile_bytes, 6);

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
$buckets = mvt_buckets();

if ($single_bucket !== null) {
    if (!in_array($single_bucket, $buckets)) {
        die("[mvtd] Unknown --bucket value: {$single_bucket}\n");
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
    $ttl          = mvt_bucket_ttl($bucket);
    $bucket_start = microtime(true);

    // ── Single-scan architecture (keyset-paginated) ──────────────────────────
    $is_cell = (strpos($bucket, 'cell_') === 0);
    $base_bucket = $is_cell ? substr($bucket, 5) : $bucket;  // 'cell_daily' → 'daily'
    [$start_date, $end_date] = bucket_date_window($base_bucket);
    $cap_feature_minzoom = mvt_bucket_cap_fmz($bucket);
    $mode = mvt_bucket_output($bucket, $dbcore);

    // ── Not this node's job ─────────────────────────────────────────────
    // Archives are generated on one node and mirrored to the rest; see
    // mvt_generates_archives() for why two generators cannot share a swarm.
    // Checked here, before the scan, so a subscriber's cron entry costs a
    // process start rather than a pass over nine million rows.
    if ($mode === 'pmtiles' && !mvt_generates_archives($dbcore, 'mvt')) {
        echo ts() . "[{$bucket}] Skipping — this node receives the MVT archive rather than building it.

";
        continue;
    }

    $lat_min_dm = dd2dm($data_bbox['lat_min']);
    $lat_max_dm = dd2dm($data_bbox['lat_max']);
    $lon_min_dm = dd2dm($data_bbox['lon_min']);
    $lon_max_dm = dd2dm($data_bbox['lon_max']);

    $label = $is_cell ? 'cells' : 'APs';
    echo ts() . "[{$bucket}] Fetching {$label} (keyset pagination)...\n";

    $aps       = [];
    $last_id   = 0;
    $newest_la = '';
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
            // Newest 'last active' in the bucket, for the change check.
            if (isset($row['la']) && (string)$row['la'] > $newest_la) {
                $newest_la = (string)$row['la'];
            }

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
        if (count($rows) < $page_size) break;  // last page
    }

    $ap_count = count($aps);
    if ($ap_count === 0 && $mode === 'dir') {
        echo ts() . "[{$bucket}] Skipping - no {$label} in bucket.\n\n";
        continue;
    }

    // Has anything changed since the last build?
    //
    // The archive records what it was made from, so it is its own state file:
    // nothing beside it to fall out of step, and the answer travels with the
    // archive when it is mirrored. Checked after the scan, because that is when
    // the count is known, and before tiling, which is the expensive half.
    //
    // A count alone cannot see one AP added and another removed in the same
    // window, so the newest last-active timestamp is compared with it. Both
    // come out of the scan already done.
    $fingerprint = $ap_count . ':' . $newest_la;
    if ($mode === 'pmtiles' && !$force_regen) {
        $existing = mvt_archive_file($archive_dir, $bucket);
        if (is_file($existing)) {
            try {
                $was = (new PMTilesReader($existing))->metadata()['wifidb'] ?? null;
                if (is_array($was) && ($was['fingerprint'] ?? null) === $fingerprint) {
                    echo ts() . "[{$bucket}] Unchanged since the last build "
                        . "({$ap_count} {$label}) - keeping the current archive.\n\n";
                    unset($aps);
                    continue;
                }
            } catch (PMTilesException $e) {
                // An unreadable archive is a reason to rebuild, not to stop.
                echo ts() . "[{$bucket}] Could not read the current archive: {$e->getMessage()}\n";
            }
        }
    }

    if ($ap_count === 0) {
        echo ts() . "[{$bucket}] No {$label} - writing an empty archive, so the "
            . "bucket stops showing the previous build.\n";
    }

    echo ts() . "[{$bucket}] {$ap_count} {$label} total. Generating tiles z{$min_zoom}–z{$max_zoom}...\n";

    // ── Z-order spatial sort + feature_minzoom assignment ───────────────────
    // assign_feature_minzoom() (lib/spatial.inc.php) encodes each AP as a
    // 56-bit Morton index, sorts the array once, then assigns feature_minzoom
    // from the gap to each AP's Morton-order predecessor.  See spatial.inc.php
    // for the full algorithm description and tippecanoe attribution.
    if ($ap_count > 0) {
        $sort_s  = microtime(true);
        $fmz_cum = assign_feature_minzoom($aps, $min_zoom, $max_zoom, MVT_DROP_SCALE_PIXELS, $cap_feature_minzoom);
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

    // ── Open the archive, when this bucket is written as one ────────────────
    // Built beside its destination and renamed into place at the end, so a run
    // that dies part-way leaves the previous archive serving rather than a
    // truncated file that reads as an empty bucket.
    $writer      = null;
    $archive_tmp = null;
    if ($mode === 'pmtiles') {
        if (!is_dir($archive_dir) && !mkdir($archive_dir, 0775, true)) {
            echo ts() . "[{$bucket}] Cannot create {$archive_dir} — skipping.\n\n";
            unset($aps);
            continue;
        }
        $archive_tmp  = "{$archive_dir}/{$bucket}.pmtiles.building";
        $writer = new PMTilesWriter($archive_tmp, [
            'tile_type' => PMTILES_TYPE_MVT,
            // Tiles are gzipped by the encoders above and stored exactly as
            // they come out, so this declares what is already there rather
            // than asking for a second round of compression.
            'tile_compression' => PMTILES_COMPRESSION_GZIP,
            'min_zoom' => $min_zoom,
            'max_zoom' => $max_zoom,
            'bounds'   => [
                $data_bbox['lon_min'], $data_bbox['lat_min'],
                $data_bbox['lon_max'], $data_bbox['lat_max'],
            ],
            // WifiDB tiles are essentially never byte-identical to one
            // another, so a hash of every distinct tile would cost gigabytes
            // to catch nothing.  Runs of identical tiles still collapse.
            'dedupe' => false,
        ]);
    }

    for ($z = $min_zoom; $z <= $max_zoom; $z++) {
        $z_start  = microtime(true);
        $tile_map = [];  // [tx][ty] => [idx, ...]

        // ── Bin AP indices into tiles for this zoom level ─────────────────────
        // Skip APs whose feature_minzoom exceeds the current zoom: they are in
        // dense clusters that should only appear at closer zooms.  This is the
        // O(N×Z) → O(N) reduction; at z=1–7 only a tiny fraction of APs pass.
        foreach ($aps as $idx => $ap) {
            if ($ap['feature_minzoom'] > $z) continue;
            $tx = lon_to_tile_x($ap['lon'], $z);
            $ty = lat_to_tile_y($ap['lat'], $z);
            $tile_map[$tx][$ty][] = $idx;
        }

        $z_written = $z_skipped = $z_empty = 0;

        // ── Decide the order tiles are written in ─────────────────────────────
        // A flat tree does not care, but a PMTiles archive must be written in
        // ascending tile id order — the reader binary-searches the directory,
        // so an unordered archive does not fail, it silently cannot find tiles
        // that are present.  Tile ids are zoom-major, so this zoom loop is
        // already correct at the top level; within a zoom, Hilbert order is
        // not x-then-y, hence the sort.  It sorts coordinates, not tiles, so
        // it costs nothing next to encoding.
        $order = [];
        foreach ($tile_map as $tx => $y_map) {
            foreach ($y_map as $ty => $tile_idxs) {
                $order[] = ($mode === 'pmtiles')
                    ? [pmtiles_zxy_to_tileid($z, $tx, $ty), $tx, $ty]
                    : [0, $tx, $ty];
            }
        }
        if ($mode === 'pmtiles') {
            usort($order, fn($a, $b) => $a[0] <=> $b[0]);
        }

        // ── Write tiles for this zoom ─────────────────────────────────────────
        foreach ($order as [$tile_id, $tx, $ty]) {
            $tile_idxs = $tile_map[$tx][$ty];
            $bucket_total++;
            $grand_total++;

            $tile_dir  = "{$output_dir}/{$bucket}/{$z}/{$tx}";
            $tile_file = "{$tile_dir}/{$ty}.pbf";

            // The TTL skip needs a per-tile mtime, which only a flat tree has.
            // An archive is written whole or not at all, so every tile in it
            // is encoded on every run.
            if ($mode === 'dir' && !$force_regen && file_exists($tile_file)
                && filesize($tile_file) >= 20          // 20 B = minimum valid gzip stream; smaller = truncated write
                && (time() - filemtime($tile_file)) < $ttl) {
                $z_skipped++;
                $grand_skipped++;
                continue;
            }

            if ($is_cell) {
                $gz_bytes = encode_cell_tile_from_mvt($z, $tx, $ty, $bucket, $tile_idxs, $aps, $dbcore->tile_max_gz_bytes);
            } elseif ($bucket === 'heatmap') {
                $gz_bytes = encode_heatmap_tile_from_points($z, $tx, $ty, $bucket, $tile_idxs, $aps, $dbcore->tile_max_gz_bytes);
            } else {
                $gz_bytes = encode_tile_from_points($z, $tx, $ty, $bucket, $tile_idxs, $aps, $dbcore->tile_max_gz_bytes);
            }

            if ($gz_bytes === null) {
                if ($mode === 'dir' && file_exists($tile_file)) unlink($tile_file);
                $z_empty++;
                $grand_empty++;
                continue;
            }

            if ($mode === 'pmtiles') {
                $writer->add($z, $tx, $ty, $gz_bytes);
                $z_written++;
                $bucket_written++;
                $grand_written++;
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

        // ── Delete tiles that existed before but have no APs now ──────────────
        // Only meaningful for a flat tree.  An archive is replaced whole, so a
        // tile that no longer has APs is gone by virtue of not being written.
        $z_dir = "{$output_dir}/{$bucket}/{$z}";
        if ($mode === 'dir' && is_dir($z_dir)) {
            foreach (glob("{$z_dir}/*", GLOB_ONLYDIR) as $x_dir) {
                $tx = (int)basename($x_dir);
                foreach (glob("{$x_dir}/*.pbf") as $pbf) {
                    $ty = (int)basename($pbf, '.pbf');
                    if (!isset($tile_map[$tx][$ty])) {
                        unlink($pbf);
                        $grand_deleted++;
                    }
                }
                if (!glob("{$x_dir}/*.pbf")) @rmdir($x_dir);
            }
        }

        $z_elapsed = round(microtime(true) - $z_start, 1);
        echo ts() . "[{$bucket}] z={$z}: {$z_written} written, {$z_skipped} skipped, {$z_empty} empty — {$z_elapsed}s\n";
        unset($tile_map);
    }

    unset($aps);

    // ── Close the archive and swap it into place ────────────────────────────
    if ($mode === 'pmtiles') {
        try {
            $writer->finalize([
                'name'        => $bucket,
                'format'      => 'pbf',
                'type'        => 'overlay',
                'description' => "WifiDB {$bucket}",
                'attribution' => '<a href="https://wifidb.net/" target="_blank">&copy; WifiDB</a> '
                    . date('Y-m-d'),
                'generated'   => gmdate('c'),
                // What this build was made from, read back by the next run
                // to decide whether rebuilding would change anything.
                'wifidb'      => [
                    'ap_count'    => $ap_count,
                    'newest_la'   => $newest_la,
                    'fingerprint' => $fingerprint,
                ],
                // The layer name is the bucket, matching what mvt_encode_layer()
                // wrote into every tile, and the fields come from the same call
                // the encoders used for their tag keys.
                'vector_layers' => [[
                    'id'          => $bucket,
                    'description' => "WifiDB {$bucket}",
                    'minzoom'     => $min_zoom,
                    'maxzoom'     => $max_zoom,
                    'fields'      => mvt_bucket_fields($bucket),
                ]],
            ]);

            // rename() is atomic within a filesystem, so a reader either has
            // the whole previous archive or the whole new one, never a mixture.
            // A dated name plus a stable hard link — see mvt_publish_archive()
            // for why a stable filename on its own is imported once by
            // pmtiles-swarm's watcher and never again.
            $keep      = isset($dbcore->tile_archive_keep) ? (int)$dbcore->tile_archive_keep : 2;
            $published = mvt_publish_archive($archive_tmp, $archive_dir, $bucket, $keep);
            if ($published === null) {
                echo "  [ERROR] Could not publish {$archive_tmp} into {$archive_dir}\n";
            } else {
                $mb = round(filesize($published) / 1048576, 1);
                echo ts() . "[{$bucket}] Archive written: {$published} ({$mb} MB)\n";
            }
        } catch (PMTilesException $e) {
            // An empty bucket throws rather than leaving a valid-looking
            // archive with nothing in it; either way the previous one stays.
            echo ts() . "[{$bucket}] Archive not written: {$e->getMessage()}\n";
            @unlink($archive_tmp);
        }
        $writer = null;
    }

    $bucket_elapsed = round(microtime(true) - $bucket_start, 1);
    echo ts() . "[{$bucket}] Done — {$bucket_written}/{$bucket_total} tiles written in {$bucket_elapsed}s\n\n";
}

$grand_elapsed = round(microtime(true) - $run_start, 1);

// ── Stale tile age cleanup ────────────────────────────────────────────────────
// Sweep all zoom levels (z1–z19) and delete any .pbf tile whose mtime exceeds
// the bucket's max age.  This is separate from the data-driven deletion in the
// main loop (Step 3): that removes tiles with no APs in the current run;
// this removes tiles that are simply too old relative to the bucket window,
// e.g. after the daemon was down for a while, or tiles written by mvt.php
// on-demand before the daemon covered those zoom levels.
echo ts() . "--- Stale tile cleanup (max-age sweep, all z) ---\n";
$cleanup_deleted = 0;
$now = time();
foreach ($buckets as $bucket) {
        $max_age    = mvt_bucket_max_age($bucket);
        $bucket_dir = "{$output_dir}/{$bucket}";
        if (!is_dir($bucket_dir)) continue;

        for ($z = $min_zoom; $z <= $max_zoom + 2; $z++) {
            $z_dir = "{$bucket_dir}/{$z}";
            if (!is_dir($z_dir)) continue;

            foreach (glob("{$z_dir}/*", GLOB_ONLYDIR) as $x_dir) {
                foreach (glob("{$x_dir}/*.pbf") as $f) {
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
