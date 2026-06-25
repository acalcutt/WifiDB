<?php
/*
mvtd.php — Mapbox Vector Tile Pre-generation Daemon
Copyright (C) 2024 Andrew Calcutt

Pre-generates gzip-compressed PBF tiles for every age-bucket across a
configurable range of zoom levels.  tilejson.php then returns these as plain
static HTTP URLs so Apache serves tile files directly — completely bypassing
PHP processing per tile and eliminating the per-request database query that
made dynamic generation too slow for dense areas (e.g. MA/AZ with 800k APs).

Architecture — query-first approach:
  For each bucket the daemon makes ONE paginated DB query (50k rows/page) to
  fetch all APs in the bucket's date window.  The results are binned into a
  per-tile map entirely in PHP, then each non-empty tile is encoded and written.
  This replaces the old per-tile query loop that issued millions of DB queries
  for world-coverage runs.  DB round-trips drop from O(tiles) to O(AP_count/50k).

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

$min_zoom = 1;
$max_zoom = 19;   // z1-z19 matches the tippecanoe PMTiles export.
                  // z13-z19 tiles are small-bbox and fast to generate per-tile.
                  // On-demand fallback (mvt.php) still handles any cache misses.

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

// Output directory — must be web-accessible.  The .htaccess in this directory
// sets the correct Content-Type/Content-Encoding headers for .pbf files.
$output_dir = rtrim($dbcore->PATH, '/') . '/out/tiles';

// Per-bucket regeneration TTL in seconds.
// Tiles whose file mtime is newer than this are skipped on incremental runs.
// Use --force on the command line to bypass and regenerate everything.
$bucket_ttl = [
    'daily'    =>     3600,  //  1 hour
    'weekly'   =>    86400,  //  1 day
    'monthly'  =>   604800,  //  1 week
    '0to1year' =>  2592000,  //  30 days
    '1to2year' =>  2592000,
    '2to3year' =>  2592000,
    'legacy'   =>  2592000,
];

// Maximum features per tile before the density thinning budget kicks in.
// The tile encoder's 1.5 MB uncompressed layer budget handles this automatically;
// this constant is kept for reference but is no longer a per-zoom DB limit.
// The bulk fetch retrieves all APs once; thinning happens in PHP per tile.
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

echo "Output dir : {$output_dir}\n";
echo "Dir exists : " . (is_dir($output_dir) ? 'YES' : 'NO — will be created on first non-empty tile') . "\n";
echo "Writable   : " . (is_writable(dirname($output_dir)) ? 'YES' : 'NO — check permissions!') . "\n\n";

function ts(): string { return date('[Y-m-d H:i:s] '); }

// ── Encode a tile from pre-fetched AP rows ────────────────────────────────────
// $aps is an array of rows already in decimal-degree lat/lon (as returned by
// BboxDateArray after dm2dd conversion).  No DB query is made here.
// Uses tippecanoe-style drop-densest-as-needed thinning.
function encode_tile_from_points(
    int    $z, int $x, int $y,
    string $bucket,
    array  $aps
): ?string {

    // Project all APs to pixel coordinates within this tile.
    $points = [];
    foreach ($aps as $ap) {
        [$px, $py] = project_to_tile((float)$ap['lat'], (float)$ap['lon'], $z, $x, $y);
        $points[] = ['ap' => $ap, 'px' => $px, 'py' => $py];
    }

    // ── Density grid (32×32 cells ≈ 128 px/cell in 4096-extent tile) ─────────
    $density_res = 32;
    $cell_px     = (float)MVT_EXTENT / $density_res;
    $cell_count  = [];
    foreach ($points as &$pt) {
        $cx       = min($density_res - 1, (int)($pt['px'] / $cell_px));
        $cy       = min($density_res - 1, (int)($pt['py'] / $cell_px));
        $ck       = $cx * $density_res + $cy;
        $pt['ck'] = $ck;
        $cell_count[$ck] = ($cell_count[$ck] ?? 0) + 1;
    }
    unset($pt);

    // Sort: sparsest cells first; densest dropped when byte budget is full.
    usort($points, function($a, $b) use ($cell_count) {
        return $cell_count[$a['ck']] - $cell_count[$b['ck']];
    });

    // ── Build MVT layer within the 1.5 MB uncompressed budget ─────────────────
    $keys     = ['sectype', 'chan', 'radio', 'mac', 'user',
                  'ssid', 'auth', 'encry', 'nt', 'btx', 'otx',
                  'fa', 'la', 'points', 'high_gps_sig', 'high_gps_rssi',
                  'lat', 'lon', 'alt', 'manuf', 'id_str'];
    $keys_idx = array_flip($keys);
    $values_bytes = [];
    $values_idx   = [];

    $add_value = function(string $type, $raw) use (&$values_bytes, &$values_idx): int {
        $key = $type . ':' . $raw;
        if (!isset($values_idx[$key])) {
            $values_idx[$key] = count($values_bytes);
            $values_bytes[] = ($type === 'int')
                ? pb_field_varint(4, (int)$raw)
                : pb_field_string(1, (string)$raw);
        }
        return $values_idx[$key];
    };

    $max_layer_bytes = 1500000;
    $est_size        = 20;
    $features        = [];
    $seen_pixel      = [];

    foreach ($points as $pt) {
        $ap = $pt['ap'];
        $px = $pt['px'];
        $py = $pt['py'];

        $pixel_key = $px . ':' . $py . ':' . (int)$ap['sectype'];
        if (isset($seen_pixel[$pixel_key])) continue;
        $seen_pixel[$pixel_key] = true;

        $tags = [
            $keys_idx['sectype'],      $add_value('int', (int)$ap['sectype']),
            $keys_idx['chan'],          $add_value('int', (int)$ap['chan']),
            $keys_idx['radio'],        $add_value('str', (string)$ap['radio']),
            $keys_idx['mac'],          $add_value('str', (string)$ap['mac']),
            $keys_idx['user'],         $add_value('str', (string)$ap['user']),
            $keys_idx['ssid'],         $add_value('str', (string)$ap['ssid']),
            $keys_idx['auth'],         $add_value('str', (string)$ap['auth']),
            $keys_idx['encry'],        $add_value('str', (string)$ap['encry']),
            $keys_idx['nt'],           $add_value('str', (string)$ap['nt']),
            $keys_idx['btx'],          $add_value('str', (string)$ap['btx']),
            $keys_idx['otx'],          $add_value('str', (string)$ap['otx']),
            $keys_idx['fa'],           $add_value('str', (string)$ap['fa']),
            $keys_idx['la'],           $add_value('str', (string)$ap['la']),
            $keys_idx['points'],       $add_value('int', (int)$ap['points']),
            $keys_idx['high_gps_sig'], $add_value('int', (int)$ap['high_gps_sig']),
            $keys_idx['high_gps_rssi'],$add_value('int', (int)$ap['high_gps_rssi']),
            $keys_idx['lat'],          $add_value('str', (string)$ap['lat']),
            $keys_idx['lon'],          $add_value('str', (string)$ap['lon']),
            $keys_idx['alt'],          $add_value('str', (string)$ap['alt']),
            $keys_idx['manuf'],        $add_value('str', (string)$ap['manuf']),
            $keys_idx['id_str'],       $add_value('str', (string)$ap['id']),
        ];
        $feat      = mvt_encode_point_feature((int)$ap['id'], $px, $py, $tags);
        $feat_cost = strlen($feat) + 3;

        if ($est_size + $feat_cost > $max_layer_bytes) break;

        $features[] = $feat;
        $est_size  += $feat_cost;
    }

    if (empty($features)) return null;

    $layer_bytes = mvt_encode_layer($bucket, $features, $keys, $values_bytes);
    $tile_bytes  = mvt_encode_tile($layer_bytes);
    return gzencode($tile_bytes, 6);
}

// ── Main generation loop ──────────────────────────────────────────────────────
$buckets = ['daily', 'weekly', 'monthly', '0to1year', '1to2year', '2to3year', 'legacy'];

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
    $ttl          = $bucket_ttl[$bucket];
    $bucket_start = microtime(true);

    // ── Step 1: Fetch all APs for this bucket in paginated passes ─────────────
    // One query per $page_size rows instead of one query per tile.
    // For large historical buckets (legacy, 2to3year, etc.) this may take
    // several pages; for daily/weekly it is usually a single page.
    [$start_date, $end_date] = bucket_date_window($bucket);

    $lat_min_dm = dd2dm($data_bbox['lat_min']);
    $lat_max_dm = dd2dm($data_bbox['lat_max']);
    $lon_min_dm = dd2dm($data_bbox['lon_min']);
    $lon_max_dm = dd2dm($data_bbox['lon_max']);

    echo ts() . "[{$bucket}] Fetching APs...\n";
    $aps    = [];
    $offset = 0;
    while (true) {
        $result = $dbcore->export->BboxDateArray(
            $lat_min_dm, $lat_max_dm, $lon_min_dm, $lon_max_dm,
            $start_date, $end_date,
            $offset, $page_size
        );
        $rows = $result['data'] ?? [];
        if (empty($rows)) break;

        foreach ($rows as $row) {
            $aps[] = [
                'id'            => (int)$row['id'],
                'lat'           => (float)$row['lat'],
                'lon'           => (float)$row['lon'],
                'alt'           => (string)$row['alt'],
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
                'points'        => (int)$row['points'],
                'high_gps_sig'  => (int)$row['high_gps_sig'],
                'high_gps_rssi' => (int)$row['high_gps_rssi'],
                'manuf'         => (string)$row['manuf'],
            ];
        }
        $offset += count($rows);
        echo ts() . "[{$bucket}]   ... {$offset} APs fetched\n";
        if (count($rows) < $page_size) break;  // last page
    }

    $ap_count = count($aps);
    if ($ap_count === 0) {
        echo ts() . "[{$bucket}] Skipping — no APs in bucket.\n\n";
        continue;
    }
    echo ts() . "[{$bucket}] {$ap_count} APs total. Generating tiles z{$min_zoom}–z{$max_zoom}...\n";

    $bucket_written = 0;
    $bucket_total   = 0;

    // ── Step 2: Per zoom level — bin APs into tiles and write ─────────────────
    // Each AP is assigned to exactly one tile per zoom level based on its
    // lat/lon.  Only tiles that received at least one AP are written; tiles
    // that previously existed but now have no APs are deleted.
    for ($z = $min_zoom; $z <= $max_zoom; $z++) {
        $z_start  = microtime(true);
        $tile_map = [];  // [x][y] => [ap, ...]

        foreach ($aps as $ap) {
            $tx = lon_to_tile_x($ap['lon'], $z);
            $ty = lat_to_tile_y($ap['lat'], $z);
            $tile_map[$tx][$ty][] = $ap;
        }

        $z_written = $z_skipped = $z_empty = 0;

        foreach ($tile_map as $tx => $y_map) {
            foreach ($y_map as $ty => $tile_aps) {
                $bucket_total++;
                $grand_total++;

                $tile_dir  = "{$output_dir}/{$bucket}/{$z}/{$tx}";
                $tile_file = "{$tile_dir}/{$ty}.pbf";

                if (!$force_regen && file_exists($tile_file)
                    && (time() - filemtime($tile_file)) < $ttl) {
                    $z_skipped++;
                    $grand_skipped++;
                    continue;
                }

                $gz_bytes = encode_tile_from_points($z, $tx, $ty, $bucket, $tile_aps);

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

        // ── Step 3: Delete tiles that existed before but have no APs now ──────
        // Walk the on-disk z directory and remove any .pbf not in $tile_map.
        // This keeps the tile store clean when data ages out of a bucket.
        $z_dir = "{$output_dir}/{$bucket}/{$z}";
        if (is_dir($z_dir)) {
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
    $bucket_elapsed = round(microtime(true) - $bucket_start, 1);
    echo ts() . "[{$bucket}] Done — {$bucket_written}/{$bucket_total} tiles written in {$bucket_elapsed}s\n\n";
}

$grand_elapsed = round(microtime(true) - $run_start, 1);

// ── On-demand tile cleanup ───────────────────────────────────────────────────
// mvt.php writes on-demand tiles for z > $max_zoom (e.g. z13–14) into the same
// out/tiles/ store, but never deletes them.  The daemon owns all cleanup:
// sweep every bucket for tiles at z > $max_zoom that are older than their TTL
// and delete them so disk space doesn't accumulate indefinitely.
if ($single_bucket === null) {
    echo ts() . "--- On-demand tile cleanup (z>" . $max_zoom . ") ---\n";
    $cleanup_deleted = 0;
    foreach ($buckets as $bucket) {
        $ttl = $bucket_ttl[$bucket];
        $bucket_dir = "{$output_dir}/{$bucket}";
        if (!is_dir($bucket_dir)) continue;

        // Scan only zoom levels above what the daemon generates.
        for ($z = $max_zoom + 1; $z <= 20; $z++) {
            $z_dir = "{$bucket_dir}/{$z}";
            if (!is_dir($z_dir)) continue;

            $x_dirs = glob("{$z_dir}/*", GLOB_ONLYDIR);
            if (!$x_dirs) continue;
            foreach ($x_dirs as $x_dir) {
                $files = glob("{$x_dir}/*.pbf");
                if (!$files) continue;
                foreach ($files as $f) {
                    if ((time() - filemtime($f)) >= $ttl) {
                        unlink($f);
                        $cleanup_deleted++;
                    }
                }
                // Remove empty x directory.
                @rmdir($x_dir);
            }
            // Remove empty z directory.
            @rmdir($z_dir);
        }
    }
    echo "  Deleted {$cleanup_deleted} stale on-demand tiles.\n";
    echo "--- End cleanup ---\n\n";
}

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "Total tiles considered : {$grand_total}\n";
echo "Written (non-empty)    : {$grand_written}\n";
echo "Skipped (fresh cache)  : {$grand_skipped}\n";
echo "Empty (no data)        : {$grand_empty}\n";
echo "Deleted (no data now)  : {$grand_deleted}\n";
echo "Elapsed                : {$grand_elapsed}s\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";

unlink($dbcore->pid_file);
