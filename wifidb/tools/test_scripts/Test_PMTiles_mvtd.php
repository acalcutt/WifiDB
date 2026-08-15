<?php
/*
Test_PMTiles_mvtd.php — checks mvtd's archive output path
Copyright (C) 2026 Andrew Calcutt

Runs standalone — no database:
  php Test_PMTiles_mvtd.php [output_dir]

Two things that would otherwise only be discovered on a full bucket run:

  1. The key lists moved out of mvtd's encoders and into mvt_bucket_fields().
     Key ORDER fixes the tag indices inside every encoded tile, so a reordering
     here silently rewrites every tile in every bucket.  The literals below are
     the lists as they stood before the move; they must still match exactly.

  2. mvtd now hands tiles to PMTilesWriter in Hilbert order rather than writing
     files in x-then-y order.  A wrong order does not fail on write — a reader
     binary-searches the directory and simply cannot find tiles that are there.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

require dirname(__FILE__) . '/../../wifidb/lib/mvt.inc.php';
require dirname(__FILE__) . '/../../wifidb/lib/pmtiles.inc.php';

$out_dir = $argv[1] ?? (sys_get_temp_dir() . '/pmtiles_test');
if (!is_dir($out_dir) && !mkdir($out_dir, 0775, true)) {
    fwrite(STDERR, "Cannot create {$out_dir}\n");
    exit(1);
}

$passed = 0;
$failed = 0;

function check(string $what, bool $ok, string $detail = ''): void {
    global $passed, $failed;
    if ($ok) { $passed++; echo "  ok   {$what}\n"; }
    else     { $failed++; echo "  FAIL {$what}" . ($detail !== '' ? " — {$detail}" : '') . "\n"; }
}

function section(string $name): void {
    echo "\n{$name}\n" . str_repeat('─', strlen($name)) . "\n";
}

// ── Field lists are unchanged ─────────────────────────────────────────────────

section('The schema matches the key lists it replaced');

// Verbatim from mvtd.php and mltd.php before mvt_bucket_fields() existed.
$before = [
    'monthly' => ['sectype', 'chan', 'radio', 'mac', 'user',
                  'ssid', 'auth', 'encry', 'nt', 'btx', 'otx',
                  'fa', 'la', 'points', 'high_gps_sig', 'high_gps_rssi',
                  'lat', 'lon', 'alt', 'manuf', 'id_str'],
    'heatmap' => ['sectype', 'age_days'],
    'cell_monthly' => ['mac', 'ssid', 'authmode', 'chan', 'type',
                       'fa', 'la', 'points', 'rssi', 'user', 'id_str'],
    'cell_heatmap' => ['mac', 'ssid', 'authmode', 'chan', 'type',
                       'fa', 'la', 'points', 'rssi', 'user', 'id_str', 'age_days'],
];

foreach ($before as $bucket => $keys) {
    $now = array_keys(mvt_bucket_fields($bucket));
    check(
        "{$bucket}: same fields in the same order",
        $now === $keys,
        'got ' . implode(',', array_diff($now, $keys)) . ' / lost ' . implode(',', array_diff($keys, $now))
    );
}

check('every age bucket shares the AP schema', (function () {
    $reference = mvt_bucket_fields('monthly');
    foreach (['0to1year', '1to2year', '2to3year', '3to5year', '5to10year', '10yrplus', 'daily'] as $bucket) {
        if (mvt_bucket_fields($bucket) !== $reference) return false;
    }
    return true;
})());

check(
    'every declared type is one TileJSON accepts',
    (function () {
        foreach (['monthly', 'heatmap', 'cell_monthly', 'cell_heatmap'] as $bucket) {
            foreach (mvt_bucket_fields($bucket) as $type) {
                if (!in_array($type, ['Number', 'String', 'Boolean'], true)) return false;
            }
        }
        return true;
    })()
);

// ── The ordering mvtd now applies ─────────────────────────────────────────────

section('Hilbert ordering inside a zoom');

// mvtd bins into $tile_map[$tx][$ty], so without the sort tiles arrive in
// x-then-y order.  This is the assertion that the sort is actually needed:
// if these two agreed, the sort would be dead code and nobody would notice it
// being dropped.
$z = 6;
$natural = [];
for ($tx = 0; $tx < 8; $tx++) {
    for ($ty = 0; $ty < 8; $ty++) {
        $natural[] = pmtiles_zxy_to_tileid($z, $tx, $ty);
    }
}
$sorted = $natural;
sort($sorted);
check('x-then-y order is not tile id order', $natural !== $sorted);

// ── An archive built the way mvtd builds one ──────────────────────────────────

section('An archive written through mvtd\'s loop shape');

$bucket  = 'monthly';
$min_zoom = 1;
$max_zoom = 8;
$path    = $out_dir . '/mvtd_shape.pmtiles';

$writer = new PMTilesWriter($path, [
    'tile_type'        => PMTILES_TYPE_MVT,
    'tile_compression' => PMTILES_COMPRESSION_GZIP,
    'min_zoom'         => $min_zoom,
    'max_zoom'         => $max_zoom,
    'bounds'           => [-180.0, -85.0, 180.0, 85.0],
    'dedupe'           => false,
]);

$expected = [];
$added    = 0;
for ($z = $min_zoom; $z <= $max_zoom; $z++) {
    // Bin the way mvtd does — a sparse scatter, keyed [tx][ty].
    $tile_map = [];
    $side     = 1 << $z;
    for ($i = 0; $i < 40; $i++) {
        $tx = ($i * 2654435761) % $side;
        $ty = ($i * 40503 + $z * 7) % $side;
        $tile_map[$tx][$ty][] = $i;
    }

    // The block mvtd now runs before writing.
    $order = [];
    foreach ($tile_map as $tx => $y_map) {
        foreach ($y_map as $ty => $idxs) {
            $order[] = [pmtiles_zxy_to_tileid($z, $tx, $ty), $tx, $ty];
        }
    }
    usort($order, fn($a, $b) => $a[0] <=> $b[0]);

    foreach ($order as [$id, $tx, $ty]) {
        $payload = gzencode("mvt {$bucket} {$z}/{$tx}/{$ty}", 6);
        $writer->add($z, $tx, $ty, $payload);
        $expected["{$z}/{$tx}/{$ty}"] = md5($payload);
        $added++;
    }
}

$writer->finalize([
    'name'          => $bucket,
    'format'        => 'pbf',
    'type'          => 'overlay',
    'vector_layers' => [[
        'id'      => $bucket,
        'minzoom' => $min_zoom,
        'maxzoom' => $max_zoom,
        'fields'  => mvt_bucket_fields($bucket),
    ]],
]);

check('the archive was written', file_exists($path));

$reader = new PMTilesReader($path);
$header = $reader->header();
check('every tile is addressed', $header['addressed_tiles_count'] === $added, "{$added} added");
check('zoom range matches the configured range', [$header['min_zoom'], $header['max_zoom']] === [$min_zoom, $max_zoom]);

$missing = [];
foreach ($expected as $key => $hash) {
    [$z, $x, $y] = array_map('intval', explode('/', $key));
    $tile = $reader->tile($z, $x, $y);
    if ($tile === null || md5($tile) !== $hash) $missing[] = $key;
}
check(
    'every tile is findable and intact',
    count($missing) === 0,
    count($missing) . ' wrong, e.g. ' . implode(', ', array_slice($missing, 0, 3))
);

$meta = $reader->metadata();
check('the layer is named after the bucket', ($meta['vector_layers'][0]['id'] ?? null) === $bucket);
check(
    'the declared fields are the ones the encoder would use',
    ($meta['vector_layers'][0]['fields'] ?? null) === mvt_bucket_fields($bucket)
);

// ── Skipping the sort breaks it, quietly ──────────────────────────────────────

section('Skipping the sort is caught, not tolerated');

check('unsorted tiles are refused', (function () use ($out_dir) {
    $writer = new PMTilesWriter($out_dir . '/unsorted.pmtiles');
    $z = 6;
    try {
        // x-then-y, which is what $tile_map iterates in naturally.
        for ($tx = 0; $tx < 4; $tx++) {
            for ($ty = 0; $ty < 4; $ty++) {
                $writer->add($z, $tx, $ty, "t{$tx}-{$ty}");
            }
        }
        return false;
    } catch (PMTilesException $e) {
        return strpos($e->getMessage(), 'ascending tile id order') !== false;
    }
})());

// ── The split between flat and archived buckets ───────────────────────────────

section('mvtd and mvt.php agree on which buckets are archives');

// Every bucket produces an archive by default, including the fast-churning
// ones: an archive is a complete artefact the endpoint can answer from without
// the database, and whether it is *seeded* is a separate decision made by the
// swarm's filename globs.
check('every bucket is archived by default', (function () {
    foreach (['daily', 'weekly', 'cell_daily', 'cell_weekly', 'monthly', 'heatmap'] as $bucket) {
        if (mvt_bucket_output($bucket) !== 'pmtiles') return false;
    }
    return true;
})());

check('a flat tier can still be configured', (function () {
    $node = new stdClass();
    $node->tile_flat_buckets = 'daily, cell_daily';
    return mvt_bucket_output('daily', $node) === 'dir'
        && mvt_bucket_output('cell_daily', $node) === 'dir'
        && mvt_bucket_output('weekly', $node) === 'pmtiles';
})());

check('monthly and wider become archives', (function () {
    foreach (['monthly', '0to1year', '1to2year', '2to3year', '3to5year',
              '5to10year', '10yrplus', 'heatmap',
              'cell_monthly', 'cell_0to1year', 'cell_10yrplus', 'cell_heatmap'] as $bucket) {
        if (mvt_bucket_output($bucket) !== 'pmtiles') return false;
    }
    return true;
})());

check('the split matches api/mvt.php\'s query limit', (function () {
    // The buckets left flat are exactly those whose date window is a week or
    // less -- the ones whose per-tile result set stays under $query_limit.
    // Anything wider truncates, which is why it must be pre-generated whole.
    foreach (['daily', 'weekly', 'cell_daily', 'cell_weekly'] as $bucket) {
        $base = strpos($bucket, 'cell_') === 0 ? substr($bucket, 5) : $bucket;
        [$start, $end] = bucket_date_window($base);
        if ($start === null) return false;                       // unbounded
        if (strtotime($start) < strtotime('-8 days')) return false;
    }
    return true;
})());

check('directories fall back to the install path', (function () {
    $fake = new stdClass();
    $fake->PATH = '/srv/wifidb/';
    $dirs = mvt_tile_dirs($fake);
    return $dirs['tiles'] === '/srv/wifidb/out/tiles'
        && $dirs['archives'] === '/srv/wifidb/out/pmtiles';
})());

check('configured directories win', (function () {
    $fake = new stdClass();
    $fake->PATH             = '/srv/wifidb/';
    $fake->tile_output_dir  = '/data/wifidb-tiles/';
    $fake->tile_archive_dir = '/data/wifidb-archives';
    $dirs = mvt_tile_dirs($fake);
    return $dirs['tiles'] === '/data/wifidb-tiles'
        && $dirs['archives'] === '/data/wifidb-archives';
})());

// ── The index cache api/mvt.php keeps in APCu ─────────────────────────────────

section('A cached index serves the same tiles');

$fresh  = new PMTilesReader($path);
$index  = $fresh->index();

// APCu stores a serialised copy, so anything that does not survive that would
// work in testing and fail under php-fpm.
$revived = unserialize(serialize($index));
check('the index survives serialisation', $revived == $index);

$cached = new PMTilesReader($path, $revived);
$differs = [];
foreach (array_slice(array_keys($expected), 0, 60) as $key) {
    [$z, $x, $y] = array_map('intval', explode('/', $key));
    if ($cached->tile($z, $x, $y) !== $fresh->tile($z, $x, $y)) $differs[] = $key;
}
check(
    'a reader built from a cached index agrees with a fresh one',
    count($differs) === 0,
    implode(', ', array_slice($differs, 0, 3))
);
check('the cached reader reports the same header', $cached->header() === $fresh->header());

// ── The ETag api/mvt.php derives from the archive ─────────────────────────────

section('The ETag fingerprint is content-derived');

/** The expression api/mvt.php uses; kept here so a change to it is caught. */
function archive_etag(array $header, string $bucket, int $z, int $x, int $y): string {
    return sprintf(
        '"%s-%x-%x-%d-%d-%d"',
        $bucket, $header['tile_data_bytes'], $header['addressed_tiles_count'], $z, $x, $y
    );
}

// Two nodes hold byte-identical copies of a build but receive them at
// different times. pmtiles.js throws EtagMismatch when a validator changes
// between range requests, so an mtime-derived ETag would break direct reads
// as soon as HAProxy sent the next request to the other node.
$copy = $out_dir . '/mvtd_shape_copy.pmtiles';
copy($path, $copy);
touch($copy, time() + 7200);

$here  = (new PMTilesReader($path))->header();
$there = (new PMTilesReader($copy))->header();

check(
    'the same build fingerprints the same on both nodes',
    archive_etag($here, 'monthly', 8, 3, 4) === archive_etag($there, 'monthly', 8, 3, 4)
);
check(
    'mtime differs between those copies, so it would not have',
    filemtime($path) !== filemtime($copy)
);

check('different tiles fingerprint differently', (function () use ($here) {
    return archive_etag($here, 'monthly', 8, 3, 4) !== archive_etag($here, 'monthly', 8, 3, 5);
})());

check('a different bucket fingerprints differently', (function () use ($here) {
    return archive_etag($here, 'monthly', 8, 3, 4) !== archive_etag($here, 'weekly', 8, 3, 4);
})());

// A rebuild with even one AP more or less moves both counters.
$rebuilt = ['tile_data_bytes' => $here['tile_data_bytes'] + 19,
            'addressed_tiles_count' => $here['addressed_tiles_count'] + 1];
check(
    'a rebuild changes the fingerprint',
    archive_etag($here, 'monthly', 8, 3, 4) !== archive_etag($rebuilt, 'monthly', 8, 3, 4)
);

check(
    'the ETag is strong, since pmtiles.js discards weak ones',
    strpos(archive_etag($here, 'monthly', 8, 3, 4), 'W/') !== 0
);

// ── MVT and MLT stay in step ──────────────────────────────────────────────────

section('The MVT and MLT paths differ only by format');

$root = dirname(__FILE__) . '/../..';
$src  = [
    'mvtd' => @file_get_contents("{$root}/tools/daemon/mvtd.php"),
    'mltd' => @file_get_contents("{$root}/tools/daemon/mltd.php"),
    'mvt'  => @file_get_contents("{$root}/wifidb/api/mvt.php"),
    'mlt'  => @file_get_contents("{$root}/wifidb/api/mlt.php"),
];
$have_src = !in_array(false, array_map(fn($s) => $s !== false, $src), true);

if (!$have_src) {
    echo "  skip  source files not readable from here\n";
} else {
    // The four tables that used to be copied into each file. A copy reappearing
    // is how these drifted apart before, and it drifts silently: the daemon
    // regenerates tiles the endpoint still considers fresh.
    foreach (['bucket_ttl', 'bucket_max_age', 'bucket_cap_fmz', 'drop_scale_pixels'] as $name) {
        $copies = [];
        foreach ($src as $file => $code) {
            if (preg_match('/\$' . preg_quote($name, '/') . '\s*=\s*[\[0-9]/', $code)) {
                $copies[] = $file;
            }
        }
        check("no local copy of \${$name}", count($copies) === 0, 'in ' . implode(', ', $copies));
    }

    // Both daemons must ask the same questions of the same helpers.
    foreach ([
        'mvt_bucket_output'  => 'which buckets are archives',
        'mvt_tile_dirs'      => 'where output goes',
        'mvt_bucket_ttl'     => 'when a tile is stale',
        'mvt_bucket_cap_fmz' => 'the feature_minzoom cap',
        'mvt_bucket_fields'  => 'the layer schema',
    ] as $fn => $what) {
        $missing = [];
        foreach (['mvtd', 'mltd'] as $file) {
            if (strpos($src[$file], $fn) === false) $missing[] = $file;
        }
        check("both daemons share {$what}", count($missing) === 0, 'missing from ' . implode(', ', $missing));
    }

    foreach (['mvt_bucket_output', 'mvt_tile_dirs', 'mvt_bucket_ttl'] as $fn) {
        $missing = [];
        foreach (['mvt', 'mlt'] as $file) {
            if (strpos($src[$file], $fn) === false) $missing[] = $file;
        }
        check("both endpoints call {$fn}()", count($missing) === 0, 'missing from ' . implode(', ', $missing));
    }

    // Both daemons must order tiles before handing them to the writer.
    foreach (['mvtd', 'mltd'] as $file) {
        check(
            "{$file} sorts by tile id before writing",
            strpos($src[$file], 'pmtiles_zxy_to_tileid') !== false
                && strpos($src[$file], 'usort($order') !== false
        );
        // Publishing goes through the shared helper rather than each daemon
        // renaming for itself, so the dated-name-plus-stable-link convention
        // the swarm watcher depends on cannot hold in one and not the other.
        check(
            "{$file} publishes through mvt_publish_archive()",
            strpos($src[$file], '.building') !== false
                && strpos($src[$file], 'mvt_publish_archive($archive_tmp') !== false
        );
        check(
            "{$file} does not rename the archive itself",
            strpos($src[$file], 'rename($archive_tmp') === false
        );
    }

    // Both daemons rebuild only when the bucket changed, and both publish an
    // empty archive when it empties. A daemon missing either would either burn
    // hours re-encoding identical data, or freeze a bucket showing contents it
    // no longer has -- neither of which is visible in the output.
    foreach (['mvtd', 'mltd'] as $file) {
        check("{$file} skips a rebuild when nothing changed",
            strpos($src[$file], '$fingerprint') !== false
                && strpos($src[$file], "keeping the current archive") !== false);
        check("{$file} records the fingerprint in the archive",
            strpos($src[$file], "'fingerprint' => \$fingerprint") !== false);
        check("{$file} writes an empty archive rather than skipping",
            strpos($src[$file], "\$ap_count === 0 && \$mode === 'dir'") !== false);
        check("{$file} honours --force over the change check",
            strpos($src[$file], '!$force_regen') !== false);
    }

    // Every endpoint must include the library whose functions it calls.
    // init.inc.php's autoloader maps class names to lib/{class}.inc.php, so a
    // file of plain functions is never loaded by it -- and the failure is a
    // fatal error returning WifiDB's HTML error page, which a map client
    // reports as "Unexpected token '<'" rather than as a 500.
    foreach (['mvt', 'mlt', 'tilejson'] as $endpoint) {
        $code = @file_get_contents("{$root}/wifidb/api/{$endpoint}.php");
        $uses = $code !== false && preg_match('/mvt_[a-z_]+\(/', $code);
        check(
            "{$endpoint}.php includes mvt.inc.php for the functions it calls",
            !$uses || strpos($code, "include('../lib/mvt.inc.php')") !== false
                   || strpos($code, "include_once('../lib/mvt.inc.php')") !== false
        );
    }

    // A module has its own scope, so anything an inline on* attribute names
    // has to be published to window deliberately. Matched case-insensitively:
    // these templates use onClick, and a case-sensitive search for onclick is
    // what let this ship -- the buttons simply stopped working.
    foreach (['vistumbler_mobile', 'vistumbler_classic'] as $theme) {
        $tpl = @file_get_contents("{$root}/wifidb/themes/{$theme}/templates/map.tpl");
        if ($tpl === false) continue;
        preg_match_all('/on(?:click|change|input|submit)="([a-zA-Z_][a-zA-Z0-9_]*)/i', $tpl, $m);
        $missing = [];
        foreach (array_unique($m[1]) as $fn) {
            // The published block first, then the name inside it. Split in
            // two because the block spans lines and holds its own brackets.
            $block = '';
            if (preg_match('/Object\.assign\(window,\s*\{(.*?)\}\s*\)/s', $tpl, $b)) {
                $block = $b[1];
            }
            if (strpos($block, $fn) === false) {
                $missing[] = $fn;
            }
        }
        check("{$theme}: every inline handler is reachable from global scope",
            count($missing) === 0, implode(', ', $missing));
    }

    // Each archive must declare its own format.
    check('mvtd declares MVT', strpos($src['mvtd'], 'PMTILES_TYPE_MVT') !== false);
    check('mltd declares MLT', strpos($src['mltd'], 'PMTILES_TYPE_MLT') !== false);
    check('the daemons do not declare each other\'s type',
        strpos($src['mvtd'], 'PMTILES_TYPE_MLT') === false
        && strpos($src['mltd'], 'PMTILES_TYPE_MVT') === false);

    // Both endpoints must fingerprint from archive contents, never mtime.
    foreach (['mvt', 'mlt'] as $file) {
        check("{$file}.php sends an ETag", strpos($src[$file], "header('ETag: ") !== false);
        check("{$file}.php honours If-None-Match", strpos($src[$file], 'HTTP_IF_NONE_MATCH') !== false);
        check(
            "{$file}.php's ETag is not mtime-derived",
            !preg_match('/\$etag\s*=\s*sprintf\([^;]*filemtime/s', $src[$file])
        );
    }
}

// ── Swarm URLs ────────────────────────────────────────────────────────────────

section('Swarm categories and URLs');

$off = new stdClass();
$off->PATH = '/srv/wifidb/';
check('no swarm configured means no URL', mvt_swarm_tilejson_url($off, 'monthly') === null);
check('and no category', mvt_swarm_category($off, 'monthly') === null);

$on = new stdClass();
$on->PATH           = '/srv/wifidb/';
$on->tile_swarm_url = 'https://swarm.wifidb.net/';

check(
    'underscores become hyphens and the prefix is applied',
    mvt_swarm_category($on, 'cell_monthly') === 'wifidb-cell-monthly'
);
check(
    'the URL is the swarm\'s stable per-category document',
    mvt_swarm_tilejson_url($on, 'heatmap') === 'https://swarm.wifidb.net/latest/wifidb-heatmap/tiles.json',
    mvt_swarm_tilejson_url($on, 'heatmap')
);
check('a trailing slash on the base does not double up',
    strpos(mvt_swarm_tilejson_url($on, 'monthly'), '//latest') === false);

$custom = clone $on;
$custom->tile_swarm_category_prefix = 'wdb-';
check('the prefix is configurable',
    mvt_swarm_category($custom, 'monthly') === 'wdb-monthly');

// A prefix is what keeps 'monthly' from colliding with any other tileset on a
// node that also carries openmaptiles and protomaps builds.
check('every bucket maps to a distinct category', (function () use ($on) {
    $seen = [];
    foreach (['daily', 'monthly', 'heatmap', 'cell_daily', 'cell_monthly', 'cell_heatmap',
              '0to1year', '1to2year', '2to3year', '3to5year', '5to10year', '10yrplus'] as $bucket) {
        $category = mvt_swarm_category($on, $bucket);
        if (isset($seen[$category])) return false;
        $seen[$category] = true;
    }
    return count($seen) === 12;
})());

// ── Who generates and who receives ────────────────────────────────────────────

section('Only one node generates archives');

$node = new stdClass();
$node->PATH = '/srv/wifidb/';
check('MVT archives are generated by default', mvt_generates_archives($node, 'mvt') === true);
check('MLT archives are not', mvt_generates_archives($node, 'mlt') === false);

$subscriber = clone $node;
$subscriber->tile_archive_generate = 0;
check('a subscriber does not generate MVT archives',
    mvt_generates_archives($subscriber, 'mvt') === false);

$mltnode = clone $node;
$mltnode->tile_mlt_archive_generate = 1;
check('MLT archives can be turned on', mvt_generates_archives($mltnode, 'mlt') === true);

// Serving is a separate question from generating: a subscriber must still read
// the archives it receives, or turning generation off would take its endpoints
// down with it.
check('serving is decided separately from generating',
    mvt_bucket_output('monthly') === 'pmtiles' && mvt_bucket_output('daily') === 'pmtiles');

if ($have_src) {
    foreach (['mvtd', 'mltd'] as $file) {
        $code = @file_get_contents("{$root}/tools/daemon/{$file}.php");
        check("{$file} checks before scanning, not after", (function () use ($code) {
            if ($code === false) return false;
            $guard = strpos($code, 'mvt_generates_archives($dbcore');
            $fetch = strpos($code, 'Fetching {$label} (keyset pagination)');
            return $guard !== false && $fetch !== false && $guard < $fetch;
        })());
    }
}

// ── Publishing an archive ─────────────────────────────────────────────────────

section('Publishing a build');

$pubdir = $out_dir . '/publish';
if (!is_dir($pubdir)) mkdir($pubdir, 0775, true);
array_map('unlink', glob("{$pubdir}/*") ?: []);

file_put_contents("{$pubdir}/monthly.pmtiles.building", 'build A');
$dated = mvt_publish_archive("{$pubdir}/monthly.pmtiles.building", $pubdir, 'monthly', 2);

// A stable filename alone is imported once by pmtiles-swarm's watcher and
// never again: replacing a watched file fires chokidar's 'change', and
// WatchManager only listens for 'add'. Verified against chokidar directly.
check('the build lands under a dated name', $dated !== null && preg_match('#/monthly-\d{8}\.pmtiles$#', $dated) === 1, (string)$dated);
check('the temporary is gone', !file_exists("{$pubdir}/monthly.pmtiles.building"));
check('the stable name exists', is_file("{$pubdir}/monthly.pmtiles"));
check('and holds the same bytes', file_get_contents("{$pubdir}/monthly.pmtiles") === 'build A');

// Simulate a later build. Same day, so the dated name collides -- which is the
// common case for a bucket rebuilt more than once in a day, and must overwrite
// rather than fail.
file_put_contents("{$pubdir}/monthly.pmtiles.building", 'build B');
$dated2 = mvt_publish_archive("{$pubdir}/monthly.pmtiles.building", $pubdir, 'monthly', 2);
check('a same-day rebuild replaces the dated build', $dated2 === $dated);
check('the stable name follows the new build',
    file_get_contents("{$pubdir}/monthly.pmtiles") === 'build B');

// Retention, using names from earlier days.
foreach (['20260101', '20260202', '20260303'] as $day) {
    file_put_contents("{$pubdir}/monthly-{$day}.pmtiles", "old {$day}");
}
file_put_contents("{$pubdir}/monthly.pmtiles.building", 'build C');
mvt_publish_archive("{$pubdir}/monthly.pmtiles.building", $pubdir, 'monthly', 2);
$remaining = glob("{$pubdir}/monthly-[0-9]*.pmtiles") ?: [];
check('older builds are retired', count($remaining) === 2, count($remaining) . ' left');
check('the newest is kept', in_array($dated, $remaining, true));

// Another bucket's builds must not be swept up by the glob.
file_put_contents("{$pubdir}/weekly-20260101.pmtiles", 'other bucket');
file_put_contents("{$pubdir}/monthly.pmtiles.building", 'build D');
mvt_publish_archive("{$pubdir}/monthly.pmtiles.building", $pubdir, 'monthly', 1);
check('another bucket is untouched', is_file("{$pubdir}/weekly-20260101.pmtiles"));

check('keep is never allowed to reach zero', (function () use ($pubdir) {
    file_put_contents("{$pubdir}/heatmap.pmtiles.building", 'only build');
    $path = mvt_publish_archive("{$pubdir}/heatmap.pmtiles.building", $pubdir, 'heatmap', 0);
    return $path !== null && is_file($path);
})());

// ── The mutable magnet ────────────────────────────────────────────────────────

section('The per-category mutable magnet');

check('no key configured means no magnet', mvt_swarm_magnet($on, 'monthly') === null);

$keyed = clone $on;
$keyed->tile_swarm_public_key = str_pad('3b6a2f', 64, '0');

// Generated by pmtiles-swarm's own mutableMagnet() in src/mutable.js. If these
// diverge, WifiDB hands out a magnet that resolves to nothing while looking
// entirely well-formed.
$reference = [
    'monthly'      => 'magnet:?xs=urn:btpk:3b6a2f0000000000000000000000000000000000000000000000000000000000&dn=wifidb-monthly&s=wifidb-monthly',
    'cell_heatmap' => 'magnet:?xs=urn:btpk:3b6a2f0000000000000000000000000000000000000000000000000000000000&dn=wifidb-cell-heatmap&s=wifidb-cell-heatmap',
    '10yrplus'     => 'magnet:?xs=urn:btpk:3b6a2f0000000000000000000000000000000000000000000000000000000000&dn=wifidb-10yrplus&s=wifidb-10yrplus',
];
foreach ($reference as $bucket_name => $expect) {
    check(
        "{$bucket_name}: byte-identical to pmtiles-swarm's mutableMagnet()",
        mvt_swarm_magnet($keyed, $bucket_name) === $expect,
        mvt_swarm_magnet($keyed, $bucket_name) ?? 'null'
    );
}

check('the salt is the category, so one key addresses every bucket', (function () use ($keyed) {
    $salts = [];
    foreach (['monthly', 'heatmap', 'cell_monthly'] as $bucket_name) {
        preg_match('/&s=([^&]+)$/', mvt_swarm_magnet($keyed, $bucket_name), $m);
        $salts[] = $m[1];
    }
    return $salts === ['wifidb-monthly', 'wifidb-heatmap', 'wifidb-cell-monthly'];
})());

// A malformed key would produce a magnet that looks right and finds nothing.
foreach (['too-short' => 'abc123',
          'not-hex'   => str_pad('zz', 64, 'z'),
          'with-0x'   => '0x' . str_pad('3b', 62, '0')] as $why => $bad) {
    $broken = clone $on;
    $broken->tile_swarm_public_key = $bad;
    check("a {$why} key yields no magnet", mvt_swarm_magnet($broken, 'monthly') === null);
}

check('an uppercase key is normalised, not rejected', (function () use ($on) {
    $upper = clone $on;
    $upper->tile_swarm_public_key = strtoupper(str_pad('3b6a2f', 64, '0'));
    return mvt_swarm_magnet($upper, 'monthly')
        === 'magnet:?xs=urn:btpk:3b6a2f0000000000000000000000000000000000000000000000000000000000&dn=wifidb-monthly&s=wifidb-monthly';
})());

// The fragment is what makes this survive the endpoint: never sent in a
// request, so a plain client ignores it and a torrent-aware one keeps it.
check('the style URL carries the magnet in the fragment', (function () use ($keyed) {
    $url = mvt_swarm_tilejson_url($keyed, 'monthly') . '#' . mvt_swarm_magnet($keyed, 'monthly');
    return strpos($url, '/latest/wifidb-monthly/tiles.json#magnet:?xs=urn:btpk:') !== false
        && substr_count($url, '#') === 1;
})());

// ── The pmtiles:// URL ────────────────────────────────────────────────────────

section('Addressing the archive directly');

check('no archive URL configured means none is offered',
    mvt_archive_pmtiles_url($keyed, 'monthly') === null);

$direct = clone $keyed;
$direct->tile_archive_url = 'https://wifidb.net/out/pmtiles/';

$expect_direct = 'pmtiles://https://wifidb.net/out/pmtiles/monthly.pmtiles'
    . '#magnet:?xs=urn:btpk:3b6a2f0000000000000000000000000000000000000000000000000000000000'
    . '&dn=wifidb-monthly&s=wifidb-monthly';
check('the URL carries the magnet in its fragment',
    mvt_archive_pmtiles_url($direct, 'monthly') === $expect_direct,
    (string)mvt_archive_pmtiles_url($direct, 'monthly'));

check('a configured flat bucket is not offered one', (function () use ($direct) {
    $flat = clone $direct;
    $flat->tile_flat_buckets = 'daily';
    return mvt_archive_pmtiles_url($flat, 'daily') === null;
})());

check('it points at the stable name, which a style can hold across rebuilds',
    strpos(mvt_archive_pmtiles_url($direct, 'monthly'), '/monthly.pmtiles#') !== false);

$nokey = clone $direct;
unset($nokey->tile_swarm_public_key);
check('without a key it is still a valid pmtiles:// URL, just without a magnet',
    mvt_archive_pmtiles_url($nokey, 'monthly') === 'pmtiles://https://wifidb.net/out/pmtiles/monthly.pmtiles');

// pmtiles.js appends /{z}/{x}/{y} to this URL and re-parses it with
// /pmtiles:\/\/(.+)\/(\d+)\/(\d+)\/(\d+)/. The greedy group must still recover
// the archive URL with a fragment in the middle -- verified against the real
// implementation, asserted here so a change to the magnet's shape is caught.
check('a tile URL built from it still resolves back to the archive', (function () use ($direct) {
    $url  = mvt_archive_pmtiles_url($direct, 'monthly');
    $tile = $url . '/10/301/384';
    if (!preg_match('#pmtiles://(.+)/(\d+)/(\d+)/(\d+)#', $tile, $m)) return false;
    return $m[1] === substr($url, 10) && [$m[2], $m[3], $m[4]] === ['10', '301', '384'];
})());

check('the magnet contains no unescaped slash to confuse that regex',
    strpos(substr(mvt_swarm_magnet($keyed, 'monthly'), 8), '/') === false);

// ── What the browser is handed to join the swarm with ────────────────────────

section('Reading the archives from a browser');

check('nothing is offered without an archive URL',
    mvt_swarm_browser_sources($keyed) === []);

$browser = mvt_swarm_browser_sources($direct);

check('every generated archive is offered', count($browser) === count(mvt_buckets()),
    count($browser) . ' of ' . count(mvt_buckets()));

// The whole registration turns on this string. The protocol looks up whatever
// follows pmtiles:// and silently constructs an HTTP source when nothing
// matches, so a key that is merely close produces a map that works, shows no
// error, and never touches the swarm.
check('the key is the style URL with the scheme removed', (function () use ($browser, $direct) {
    foreach ($browser as $entry) {
        if ('pmtiles://' . $entry['key'] !== mvt_archive_pmtiles_url($direct, $entry['bucket'])) {
            return false;
        }
    }
    return $browser !== [];
})());

check('the key keeps the fragment, which is what the protocol keys on', (function () use ($browser) {
    return strpos($browser[0]['key'], '#magnet:?xs=urn:btpk:') !== false;
})());

// A browser has no DHT, so the mutable magnet in the fragment resolves to
// nothing there. The swarm's own TileJSON is where the joinable infohash
// magnet comes from, which is why each entry carries that URL as well.
check('each entry names the swarm category document', (function () use ($browser, $direct) {
    foreach ($browser as $entry) {
        if ($entry['tilejson'] !== mvt_swarm_tilejson_url($direct, $entry['bucket'])) {
            return false;
        }
    }
    return true;
})());

check('a flat bucket is left out, having no archive to join', (function () use ($direct) {
    $flat = clone $direct;
    $flat->tile_flat_buckets = 'daily,weekly';
    $offered = array_column(mvt_swarm_browser_sources($flat), 'bucket');
    return !in_array('daily', $offered, true)
        && !in_array('weekly', $offered, true)
        && in_array('monthly', $offered, true);
})());

if ($have_src) {
    // The daemons and the map must agree on what exists. A bucket in one list
    // and not the other is an archive that never generates, or a source the
    // page never offers -- neither of which shows up as an error.
    foreach (['mvtd', 'mltd'] as $file) {
        check("{$file} takes its bucket list from mvt.inc.php",
            strpos($src[$file], '$buckets = mvt_buckets()') !== false);
    }

    $mp = @file_get_contents("{$root}/wifidb/opt/map.php");
    check('map.php offers the browser sources to the template',
        $mp !== false && strpos($mp, 'mvt_swarm_browser_sources') !== false);
    check('and leaves the swarm off unless it is configured on',
        $mp !== false && strpos($mp, 'tile_swarm_browser') !== false);
    check('the import map can resolve what the swarm module imports', (function () use ($mp) {
        if ($mp === false) return false;
        foreach (['webtorrent', 'pmtiles-torrent', 'pmtiles-torrent/webtorrent',
                  'wifidb-swarm'] as $specifier) {
            if (strpos($mp, "'{$specifier}'") === false) return false;
        }
        return true;
    })());

    foreach (['vistumbler_mobile', 'vistumbler_classic'] as $theme) {
        $tpl = @file_get_contents("{$root}/wifidb/themes/{$theme}/templates/map.tpl");
        if ($tpl === false) continue;
        // Registering after the layers are added does nothing at all: the
        // protocol has already bound an HTTP source to the archive's URL and
        // caches it for the life of the page.
        check("{$theme}: the layers wait for the swarm to register",
            strpos($tpl, 'swarmReady.then(init)') !== false
                && strpos($tpl, "\t\t\t\t\tinit();") === false);
        // Both branches must define it, or a map with no swarm configured
        // throws ReferenceError before it draws anything.
        check("{$theme}: swarmReady exists whether or not a swarm is configured",
            strpos($tpl, 'const swarmReady = Promise.resolve(null)') !== false
                && strpos($tpl, 'const swarmReady = enableSwarm(') !== false);
        // 220 KB of WebTorrent on every map view, for a feature that is off.
        check("{$theme}: the swarm module is imported only when it is used",
            preg_match('/\{if \$wifidb_swarm_sources ne \'\[\]\'\}\s*(?:\/\/[^\n]*\n\s*)*import \{ enableSwarm \}/', $tpl) === 1);
    }

    $swarm_js = @file_get_contents("{$root}/wifidb/lib/js/wifidb/swarm.js");
    check('the swarm module exists', $swarm_js !== false);
    if ($swarm_js !== false) {
        // Protocol.add() is a commitment, not a hint: once a source is bound
        // there is no path back to HTTP, so an archive whose metadata never
        // arrives would take its bucket's tiles down rather than just fail to
        // accelerate them.
        check('it waits for metadata before registering anything',
            strpos($swarm_js, 'engine.ready()') !== false
                && strpos($swarm_js, 'protocol.add') > strpos($swarm_js, 'deadlinePassed'));
        check('it releases archives that arrive after the deadline',
            strpos($swarm_js, 'ready too late') !== false);
        // A static import is evaluated with the map's own module graph, so a
        // WebTorrent that throws on load would take the map down with it
        // rather than merely failing to accelerate anything.
        check('it loads WebTorrent lazily, so a bad load cannot break the map',
            strpos($swarm_js, "import('webtorrent')") !== false
                && preg_match("/^import .*'webtorrent'/m", $swarm_js) === 0);
        // Both halves matter: an infohash to join, and a websocket tracker to
        // find peers through. Announced only to udp:// trackers, a perfectly
        // healthy swarm has no peer a browser can reach.
        check('it rejects a magnet a browser cannot act on',
            strpos($swarm_js, "startsWith('urn:btih:')") !== false
                && strpos($swarm_js, "startsWith('wss://')") !== false);
    }

    $tj = @file_get_contents("{$root}/wifidb/api/tilejson.php");
    check('tilejson.php offers the pmtiles:// URL',
        $tj !== false && strpos($tj, 'mvt_archive_pmtiles_url') !== false);
    check('tilejson.php publishes the magnet', $tj !== false && strpos($tj, 'mvt_swarm_magnet') !== false);
    check('and offers a paste-ready source URL',
        $tj !== false && strpos($tj, "'source_url'") !== false);
    check('tilejson.php builds fields from the shared schema',
        $tj !== false && strpos($tj, 'mvt_bucket_fields($bucket)') !== false);
    check('tilejson.php no longer carries its own field lists',
        $tj !== false && strpos($tj, "'high_gps_rssi' => 'Number'") === false
                      && strpos($tj, "'authmode' => 'String'") === false);
    check('tilejson.php redirects archived buckets to the swarm',
        $tj !== false && strpos($tj, 'mvt_swarm_tilejson_url') !== false
                      && strpos($tj, "header('Location: ' . \$swarm_url, true, 302)") !== false);
}

echo "\n" . str_repeat('━', 60) . "\n";
echo "passed: {$passed}   failed: {$failed}\n";
echo str_repeat('━', 60) . "\n";

exit($failed === 0 ? 0 : 1);
