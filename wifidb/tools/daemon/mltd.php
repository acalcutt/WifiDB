<?php
/*
mltd.php — MapLibre Tile (MLT) Pre-generation Daemon
Copyright (C) 2024 Andrew Calcutt

Pre-generates gzip-compressed MLT tiles for every age-bucket across a
configurable range of zoom levels.  tilejson.php?format=mlt then returns these
as static HTTP URLs (served via mlt.php which checks the on-disk cache first).

Architecture mirrors mvtd.php exactly: query-first, one paginated DB fetch per
bucket, binned into a per-tile map in PHP, then encoded and written.

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
$min_zoom = 1;
$max_zoom = 19;   // z1-z19 matches the tippecanoe PMTiles export.

$data_bbox = [
    'lat_min' => -85.0,
    'lat_max' =>  85.0,
    'lon_min' => -180.0,
    'lon_max' =>  180.0,
];

$page_size = 50000;

// Output directory — parallel to out/tiles/ but for .mlt files.
$output_dir = rtrim($dbcore->PATH, '/') . '/out/tiles-mlt';

$bucket_ttl = [
    'daily'    =>     3600,  //  1 hour
    'weekly'   =>    86400,  //  1 day
    'monthly'  =>   604800,  //  1 week
    '0to1year' =>  2592000,  //  30 days
    '1to2year' =>  2592000,
    '2to3year' =>  2592000,
    'legacy'   =>  2592000,
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
// Mirrors encode_tile_from_points() in mvtd.php but writes MLT instead of MVT.
// Applies the same 32×32 density-grid thinning and 1.5 MB byte-budget cap.
// Returns gzip-compressed MLT bytes, or null if the tile has no usable points.
function encode_mlt_tile_from_points(
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
    $cell_px     = (float)MLT_EXTENT / $density_res;
    $cell_count  = [];
    foreach ($points as &$pt) {
        $cx       = min($density_res - 1, (int)($pt['px'] / $cell_px));
        $cy       = min($density_res - 1, (int)($pt['py'] / $cell_px));
        $ck       = $cx * $density_res + $cy;
        $pt['ck'] = $ck;
        $cell_count[$ck] = ($cell_count[$ck] ?? 0) + 1;
    }
    unset($pt);

    // Sort: sparsest cells first; densest dropped when budget is exhausted.
    usort($points, function($a, $b) use ($cell_count) {
        return $cell_count[$a['ck']] - $cell_count[$b['ck']];
    });

    // ── Collect features within the 1.5 MB uncompressed byte budget ──────────
    // MLT encodes all features column-by-column after collection, so we use a
    // rough per-feature estimate (id=4, x/y=4, props≈30 → ~38 bytes) to cap
    // total feature count before the final encode step.
    $max_tile_bytes = 1500000;
    $est_size       = 0;
    $features       = [];
    $seen_pixel     = [];

    foreach ($points as $pt) {
        $ap = $pt['ap'];
        $px = $pt['px'];
        $py = $pt['py'];

        // Deduplicate: same pixel + sectype → skip.
        $pixel_key = $px . ':' . $py . ':' . (int)$ap['sectype'];
        if (isset($seen_pixel[$pixel_key])) continue;
        $seen_pixel[$pixel_key] = true;

        if ($est_size + 38 > $max_tile_bytes) break;
        $est_size += 38;

        $features[] = [
            'id'            => (int)$ap['id'],
            'x'             => $px,
            'y'             => $py,
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

    return gzencode($mlt_bytes, 6);
}

// ── Main generation loop ──────────────────────────────────────────────────────
$buckets = ['daily', 'weekly', 'monthly', '0to1year', '1to2year', '2to3year', 'legacy'];

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
        if (count($rows) < $page_size) break;
    }

    $ap_count = count($aps);
    if ($ap_count === 0) {
        echo ts() . "[{$bucket}] Skipping — no APs in bucket.\n\n";
        continue;
    }
    echo ts() . "[{$bucket}] {$ap_count} APs total. Generating tiles z{$min_zoom}–z{$max_zoom}...\n";

    $bucket_written = 0;
    $bucket_total   = 0;

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
                $tile_file = "{$tile_dir}/{$ty}.mlt";

                if (!$force_regen && file_exists($tile_file)
                    && (time() - filemtime($tile_file)) < $ttl) {
                    $z_skipped++;
                    $grand_skipped++;
                    continue;
                }

                $gz_bytes = encode_mlt_tile_from_points($z, $tx, $ty, $bucket, $tile_aps);

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

// ── On-demand tile cleanup ────────────────────────────────────────────────────
// mlt.php writes on-demand tiles for z > $max_zoom into out/tiles-mlt/ but
// never deletes them.  The daemon owns cleanup for the whole store.
if ($single_bucket === null) {
    echo ts() . "--- On-demand tile cleanup (z>" . $max_zoom . ") ---\n";
    $cleanup_deleted = 0;
    foreach ($buckets as $bucket) {
        $ttl        = $bucket_ttl[$bucket];
        $bucket_dir = "{$output_dir}/{$bucket}";
        if (!is_dir($bucket_dir)) continue;

        for ($z = $max_zoom + 1; $z <= 20; $z++) {
            $z_dir = "{$bucket_dir}/{$z}";
            if (!is_dir($z_dir)) continue;

            $x_dirs = glob("{$z_dir}/*", GLOB_ONLYDIR);
            if (!$x_dirs) continue;
            foreach ($x_dirs as $x_dir) {
                $files = glob("{$x_dir}/*.mlt");
                if (!$files) continue;
                foreach ($files as $f) {
                    if ((time() - filemtime($f)) >= $ttl) {
                        unlink($f);
                        $cleanup_deleted++;
                    }
                }
                @rmdir($x_dir);
            }
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
