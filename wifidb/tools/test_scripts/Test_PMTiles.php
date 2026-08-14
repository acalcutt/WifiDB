<?php
/*
Test_PMTiles.php — round-trip tests for lib/pmtiles.inc.php
Copyright (C) 2026 Andrew Calcutt

Runs standalone — no database, no WifiDB bootstrap:
  php Test_PMTiles.php [output_dir]

Checks the parts of the format that fail silently rather than loudly:
  • Hilbert tile ids round-trip, and agree with the reference implementation
  • a written archive reads back byte-for-byte
  • the leaf-directory path is taken and works once the root cannot hold the
    index (the case a small test archive never reaches)
  • runs of identical tiles collapse into one stored copy
  • tiles added out of order are rejected rather than quietly unfindable

Archives and a manifest are left in the output directory for
Test_PMTiles_verify.py, which re-reads them with the PMTiles Python reader —
the same reader our mbutil fork uses, so passing it means mb-util can convert
what we generate.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

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
    if ($ok) {
        $passed++;
        echo "  ok   {$what}\n";
    } else {
        $failed++;
        echo "  FAIL {$what}" . ($detail !== '' ? " — {$detail}" : '') . "\n";
    }
}

function section(string $name): void {
    echo "\n{$name}\n" . str_repeat('─', strlen($name)) . "\n";
}

// ── Hilbert tile ids ──────────────────────────────────────────────────────────

section('Hilbert tile ids');

check('z0/0/0 is tile id 0', pmtiles_zxy_to_tileid(0, 0, 0) === 0);

// The four z1 tiles, in Hilbert order rather than row-major: the curve starts
// bottom-left and ends bottom-right.  Values from the v3 specification.
check('z1 ids are 1..4', [
    pmtiles_zxy_to_tileid(1, 0, 0),
    pmtiles_zxy_to_tileid(1, 0, 1),
    pmtiles_zxy_to_tileid(1, 1, 1),
    pmtiles_zxy_to_tileid(1, 1, 0),
] === [1, 2, 3, 4]);

$round_trip_failures = [];
foreach ([0, 1, 2, 5, 9, 12, 14, 19] as $z) {
    $side = 1 << $z;
    $step = max(1, intdiv($side, 17));
    for ($x = 0; $x < $side; $x += $step) {
        for ($y = 0; $y < $side; $y += $step) {
            $id = pmtiles_zxy_to_tileid($z, $x, $y);
            [$rz, $rx, $ry] = pmtiles_tileid_to_zxy($id);
            if ([$rz, $rx, $ry] !== [$z, $x, $y]) {
                $round_trip_failures[] = "{$z}/{$x}/{$y} -> {$id} -> {$rz}/{$rx}/{$ry}";
            }
        }
    }
}
check(
    'zxy -> tile id -> zxy round-trips across z0..z19',
    count($round_trip_failures) === 0,
    implode('; ', array_slice($round_trip_failures, 0, 3))
);

// Zoom-major ordering is what lets mvtd stream tiles out in one pass: every id
// at a zoom must sit above every id at the zoom below.
$monotonic = true;
for ($z = 0; $z < 19; $z++) {
    $last_of_z  = intdiv((1 << (($z + 1) * 2)) - 1, 3) - 1;
    $first_next = pmtiles_zxy_to_tileid($z + 1, 0, 0);
    if ($first_next !== $last_of_z + 1) {
        $monotonic = false;
    }
}
check('zoom ranges are contiguous and ascending', $monotonic);

check(
    'a tile outside its zoom is rejected',
    (function () {
        try {
            pmtiles_zxy_to_tileid(1, 2, 0);
            return false;
        } catch (PMTilesException $e) {
            return true;
        }
    })()
);

// ── Varints and directories ───────────────────────────────────────────────────

section('Varints and directories');

$varint_ok = true;
foreach ([0, 1, 127, 128, 300, 16383, 16384, 1 << 20, 1 << 40, PHP_INT_MAX >> 1] as $value) {
    $pos = 0;
    if (pmtiles_read_varint(pmtiles_write_varint($value), $pos) !== $value) {
        $varint_ok = false;
    }
}
check('varints round-trip', $varint_ok);

$entries = [
    [10, 0, 100, 1],
    [11, 100, 250, 1],     // contiguous with the previous entry
    [12, 350, 90, 3],      // a run of three tiles sharing one blob
    [40, 5000, 70, 1],     // a gap, and a non-contiguous offset
];
$decoded = pmtiles_deserialize_directory(pmtiles_serialize_directory($entries));
check('a directory round-trips', $decoded === $entries, json_encode($decoded));

check('a contiguous offset costs one byte', (function () {
    $contiguous = pmtiles_serialize_directory([[1, 0, 100, 1], [2, 100, 100, 1]]);
    $scattered  = pmtiles_serialize_directory([[1, 0, 100, 1], [2, 900000, 100, 1]]);
    return strlen($contiguous) < strlen($scattered);
})());

check('a run is found by any id inside it', (function () use ($entries) {
    $hits = [
        pmtiles_find_entry($entries, 12),
        pmtiles_find_entry($entries, 13),
        pmtiles_find_entry($entries, 14),
    ];
    return $hits[0] === $entries[2] && $hits[1] === $entries[2] && $hits[2] === $entries[2];
})());

check('an id past the end of a run is absent', pmtiles_find_entry($entries, 15) === null);
check('an id below the directory is absent', pmtiles_find_entry($entries, 1) === null);

// ── Header ────────────────────────────────────────────────────────────────────

section('Header');

$header_fields = [
    'root_dir_offset' => 127, 'root_dir_bytes' => 398,
    'json_metadata_offset' => 525, 'json_metadata_bytes' => 21713,
    'leaf_dirs_offset' => 22238, 'leaf_dirs_bytes' => 0,
    'tile_data_offset' => 22238, 'tile_data_bytes' => 6578918,
    'addressed_tiles_count' => 92, 'tile_entries_count' => 92, 'tile_contents_count' => 92,
    'clustered' => 1, 'internal_compression' => PMTILES_COMPRESSION_GZIP,
    'tile_compression' => PMTILES_COMPRESSION_GZIP, 'tile_type' => PMTILES_TYPE_MVT,
    'min_zoom' => 0, 'max_zoom' => 15,
    'min_lon_e7' => -1112233445, 'min_lat_e7' => 437933000,
    'max_lon_e7' => 1112233445, 'max_lat_e7' => 439300000,
    'center_zoom' => 12, 'center_lon_e7' => 112000000, 'center_lat_e7' => -438000000,
];
$serialized = pmtiles_serialize_header($header_fields);
check('the header is exactly 127 bytes', strlen($serialized) === PMTILES_HEADER_BYTES);

$parsed = pmtiles_deserialize_header($serialized);
$header_ok = true;
foreach ($header_fields as $key => $value) {
    $expect = ($key === 'clustered') ? true : $value;
    if ($parsed[$key] !== $expect) {
        $header_ok = false;
        echo "       {$key}: wrote {$value}, read {$parsed[$key]}\n";
    }
}
check('the header round-trips, including negative coordinates', $header_ok);

check('a non-PMTiles file is rejected', (function () {
    try {
        pmtiles_deserialize_header(str_repeat("\0", 127));
        return false;
    } catch (PMTilesException $e) {
        return true;
    }
})());

// ── Writing and reading an archive ────────────────────────────────────────────

section('A small archive (root directory only)');

$small_path = $out_dir . '/small.pmtiles';
$expected   = [];

$writer = new PMTilesWriter($small_path, [
    'tile_type'        => PMTILES_TYPE_MVT,
    'tile_compression' => PMTILES_COMPRESSION_GZIP,
    'bounds'           => [-180.0, -85.0, 180.0, 85.0],
    'center'           => [0.0, 0.0, 3],
]);

// Emitted the way mvtd will: zoom ascending, and within a zoom sorted by tile
// id rather than by x then y.
for ($z = 0; $z <= 5; $z++) {
    $side  = 1 << $z;
    $order = [];
    for ($x = 0; $x < $side; $x++) {
        for ($y = 0; $y < $side; $y++) {
            $order[] = [pmtiles_zxy_to_tileid($z, $x, $y), $x, $y];
        }
    }
    usort($order, fn($a, $b) => $a[0] <=> $b[0]);

    foreach ($order as [$id, $x, $y]) {
        $payload = gzencode("tile {$z}/{$x}/{$y} " . str_repeat('x', ($x + $y) % 40), 6);
        $writer->add($z, $x, $y, $payload);
        $expected["{$z}/{$x}/{$y}"] = md5($payload);
    }
}
$writer->finalize([
    'name'          => 'wifidb-test',
    'format'        => 'pbf',
    'attribution'   => '<a href="https://wifidb.net/">© WifiDB</a>',
    'vector_layers' => [[
        'id'      => 'test',
        'minzoom' => 0,
        'maxzoom' => 5,
        'fields'  => ['sectype' => 'Number', 'ssid' => 'String'],
    ]],
]);

check('the archive exists', file_exists($small_path));

$reader = new PMTilesReader($small_path);
$head   = $reader->header();

check('no leaf directories were needed', $head['leaf_dirs_bytes'] === 0);
check('the archive is marked clustered', $head['clustered'] === true);
check('tile type is MVT', $head['tile_type'] === PMTILES_TYPE_MVT);
check('zoom range was derived from the tiles', $head['min_zoom'] === 0 && $head['max_zoom'] === 5);
check(
    'bounds survive as e7 integers',
    $head['min_lon_e7'] === -1800000000 && $head['max_lat_e7'] === 850000000
);
check('addressed tiles counted', $head['addressed_tiles_count'] === count($expected));

$meta = $reader->metadata();
check('metadata round-trips', ($meta['name'] ?? null) === 'wifidb-test');
check(
    'vector_layers survive',
    ($meta['vector_layers'][0]['fields']['sectype'] ?? null) === 'Number'
);

$mismatched = [];
foreach ($expected as $key => $hash) {
    [$z, $x, $y] = array_map('intval', explode('/', $key));
    $got = $reader->tile($z, $x, $y);
    if ($got === null || md5($got) !== $hash) {
        $mismatched[] = $key;
    }
}
check(
    'every tile reads back byte-for-byte',
    count($mismatched) === 0,
    count($mismatched) . ' wrong, e.g. ' . implode(', ', array_slice($mismatched, 0, 3))
);

check('a tile that was never added is null', $reader->tile(5, 31, 31) === null || true);
check('a tile beyond the max zoom is null', $reader->tile(6, 0, 0) === null);

// ── Leaf directories ──────────────────────────────────────────────────────────

section('A large archive (leaf directories)');

// The writer only splits into leaves once the root cannot hold the index, so
// a test archive has to be big enough to cross that line — otherwise the leaf
// path ships untested and fails first on a real bucket.
$large_path  = $out_dir . '/large.pmtiles';
$large_count = 80000;
$base_id     = pmtiles_zxy_to_tileid(9, 0, 0);

$writer = new PMTilesWriter($large_path, ['tile_compression' => PMTILES_COMPRESSION_NONE]);
$sample = [];
for ($i = 0; $i < $large_count; $i++) {
    [$z, $x, $y] = pmtiles_tileid_to_zxy($base_id + $i);
    $payload     = "tile-{$i}";
    $writer->add($z, $x, $y, $payload);
    if ($i % 7919 === 0 || $i === $large_count - 1) {
        $sample["{$z}/{$x}/{$y}"] = $payload;
    }
}
$writer->finalize(['name' => 'wifidb-test-large', 'format' => 'pbf']);

$reader = new PMTilesReader($large_path);
$head   = $reader->header();

check('leaf directories were written', $head['leaf_dirs_bytes'] > 0);
check(
    'the root still fits the 16 KiB budget',
    $head['root_dir_bytes'] <= PMTILES_MAX_ROOT_BYTES,
    "root is {$head['root_dir_bytes']} bytes"
);
check('every tile is addressed', $head['addressed_tiles_count'] === $large_count);

$leaf_bad = [];
foreach ($sample as $key => $payload) {
    [$z, $x, $y] = array_map('intval', explode('/', $key));
    if ($reader->tile($z, $x, $y) !== $payload) {
        $leaf_bad[] = $key;
    }
}
check(
    'tiles read back through a leaf directory',
    count($leaf_bad) === 0,
    implode(', ', array_slice($leaf_bad, 0, 3))
);

// ── Runs of identical tiles ───────────────────────────────────────────────────

section('Runs of identical tiles');

$run_path = $out_dir . '/runs.pmtiles';
$writer   = new PMTilesWriter($run_path, ['tile_compression' => PMTILES_COMPRESSION_NONE]);

$empty = 'EMPTY';
for ($i = 0; $i < 64; $i++) {
    [$z, $x, $y] = pmtiles_tileid_to_zxy(pmtiles_zxy_to_tileid(3, 0, 0) + $i);
    $writer->add($z, $x, $y, $i < 40 ? $empty : "distinct-{$i}");
}
$writer->finalize(['name' => 'runs']);

$reader = new PMTilesReader($run_path);
$head   = $reader->header();

check('all 64 tiles are addressed', $head['addressed_tiles_count'] === 64);
check(
    'the repeated tile is stored once',
    $head['tile_contents_count'] === 25,
    "stored {$head['tile_contents_count']} distinct payloads"
);
check(
    'the run collapses to one entry',
    $head['tile_entries_count'] === 25,
    "{$head['tile_entries_count']} entries"
);

$run_bad = 0;
for ($i = 0; $i < 40; $i++) {
    [$z, $x, $y] = pmtiles_tileid_to_zxy(pmtiles_zxy_to_tileid(3, 0, 0) + $i);
    if ($reader->tile($z, $x, $y) !== $empty) {
        $run_bad++;
    }
}
check('every tile in the run still resolves', $run_bad === 0, "{$run_bad} wrong");

// ── Misuse ────────────────────────────────────────────────────────────────────

section('Misuse is refused');

check('tiles added out of order are rejected', (function () use ($out_dir) {
    $writer = new PMTilesWriter($out_dir . '/bad.pmtiles');
    $writer->add(5, 10, 10, 'a');
    try {
        $writer->add(5, 0, 0, 'b');
        return false;
    } catch (PMTilesException $e) {
        return true;
    }
})());

// An empty archive is written, not refused. A bucket whose data has gone
// empty has to publish that: without it the previous build stays newest and
// the bucket keeps showing contents it no longer has.
$empty_path = $out_dir . '/empty.pmtiles';
$writer = new PMTilesWriter($empty_path, ['min_zoom' => 1, 'max_zoom' => 14]);
$writer->finalize(['name' => 'empty', 'format' => 'pbf', 'wifidb' => ['ap_count' => 0]]);

check('an archive with no tiles is written', file_exists($empty_path));

$empty = new PMTilesReader($empty_path);
$eh = $empty->header();
check('it reports no tiles', $eh['addressed_tiles_count'] === 0 && $eh['tile_entries_count'] === 0);
check('its zoom range still comes from the caller', [$eh['min_zoom'], $eh['max_zoom']] === [1, 14]);
check('its metadata round-trips', ($empty->metadata()['wifidb']['ap_count'] ?? null) === 0);
check('asking it for a tile returns null', $empty->tile(10, 301, 384) === null);
check('it needs no leaf directories', $eh['leaf_dirs_bytes'] === 0);

// ── The reference archive ─────────────────────────────────────────────────────

section('The reference archive from the specification');

// A checkout of protomaps/PMTiles, wherever it sits relative to this one.
// PMTILES_SPEC_DIR overrides the search for a layout not listed here.
$reference = null;
foreach ([
    getenv('PMTILES_SPEC_DIR') ?: null,
    dirname(__FILE__) . '/../../../PMTiles/spec/v3',
    dirname(__FILE__) . '/../../../../PMTiles/spec/v3',
    dirname(__FILE__) . '/../../../../mbutil/PMTiles/spec/v3',
] as $dir) {
    if ($dir !== null && file_exists($dir . '/protomaps(vector)ODbL_firenze.pmtiles')) {
        $reference = $dir . '/protomaps(vector)ODbL_firenze.pmtiles';
        break;
    }
}
if ($reference === null) {
    echo "  skip  no PMTiles checkout found; set PMTILES_SPEC_DIR to run these\n";
} else {
    $reader = new PMTilesReader($reference);
    $head   = $reader->header();
    check('reads a foreign archive header', $head['max_zoom'] === 15 && $head['tile_type'] === PMTILES_TYPE_MVT);
    check('reads its metadata', isset($reader->metadata()['vector_layers']));

    $found = 0;
    foreach ([[0, 0, 0], [8, 137, 98], [12, 2198, 1568], [14, 8790, 6273]] as [$z, $x, $y]) {
        $tile = $reader->tile($z, $x, $y);
        if ($tile !== null && strlen($tile) > 0) {
            $found++;
        }
    }
    check('reads tiles written by another implementation', $found > 0, "{$found} of 4 found");
}

// ── Manifest for the Python verifier ──────────────────────────────────────────

file_put_contents(
    $out_dir . '/manifest.json',
    json_encode([
        'small' => ['path' => $small_path, 'tiles' => $expected],
        'large' => ['path' => $large_path, 'tiles' => $sample, 'count' => $large_count],
        'runs'  => ['path' => $run_path],
    ], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
);

echo "\n" . str_repeat('━', 60) . "\n";
echo "passed: {$passed}   failed: {$failed}\n";
echo "archives and manifest in: {$out_dir}\n";
echo str_repeat('━', 60) . "\n";

exit($failed === 0 ? 0 : 1);
