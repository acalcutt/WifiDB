<?php
/*
pmtiles.inc.php — PMTiles v3 archive reader and writer
Copyright (C) 2026 Andrew Calcutt

Shared by:
  tools/daemon/mvtd.php   — writes one archive per pre-generated bucket
  wifidb/api/mvt.php      — serves tiles out of an archive instead of a tile tree

Contains:
  • Hilbert tile-id mapping (zxy <-> tile_id)
  • Varint and directory serialisation
  • PMTilesWriter — streaming, single-pass archive writer
  • PMTilesReader — header/directory parser and single-tile reader

Why an archive rather than a tile tree:
  A bucket at high zoom approaches one tile per AP per zoom, and a 300-byte
  .pbf still occupies a full filesystem block plus an inode plus a directory
  entry.  Packed end to end in an archive a 300-byte tile costs 300 bytes.
  The saving is block overhead, not PMTiles' content deduplication — WifiDB
  tiles are almost never byte-identical to each other, so dedup is off by
  default (see PMTilesWriter's $options['dedupe']).

Derived from the PMTiles reference implementation:
  https://github.com/protomaps/PMTiles — cpp/pmtiles.hpp
  Copyright (c) 2021 Protomaps LLC, licensed BSD-3-Clause.
The Hilbert curve mapping, directory serialisation and root/leaf sizing
follow that implementation directly.  The PMTiles specification itself is
public domain (CC0 where applicable).

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

// ── Constants ─────────────────────────────────────────────────────────────────

const PMTILES_HEADER_BYTES = 127;

// The root directory must fit in the first 16 KiB of the archive, so that a
// client can read the header and the whole root in one HTTP range request.
// The header eats the first 127 bytes of that budget.
const PMTILES_MAX_ROOT_BYTES = 16384 - PMTILES_HEADER_BYTES;

const PMTILES_COMPRESSION_UNKNOWN = 0;
const PMTILES_COMPRESSION_NONE    = 1;
const PMTILES_COMPRESSION_GZIP    = 2;
const PMTILES_COMPRESSION_BROTLI  = 3;
const PMTILES_COMPRESSION_ZSTD    = 4;

const PMTILES_TYPE_UNKNOWN = 0;
const PMTILES_TYPE_MVT     = 1;
const PMTILES_TYPE_PNG     = 2;
const PMTILES_TYPE_JPEG    = 3;
const PMTILES_TYPE_WEBP    = 4;
const PMTILES_TYPE_AVIF    = 5;
// MapLibre Vector Tile — spec v3.5.  What mltd's archives declare, so a
// reader knows the payloads are .mlt rather than guessing from the metadata.
const PMTILES_TYPE_MLT     = 6;

class PMTilesException extends RuntimeException {}

// Tile ids reach 2^62 at the deepest zoom the format allows, and offsets are
// 64-bit throughout.  On a 32-bit build the shifts below wrap silently and
// produce an archive whose directory points at the wrong bytes, so refuse
// rather than write one.  The same overflow was fixed in the reference C++
// implementation in March 2026 (1L -> 1LL in tileid_to_zxy).
if (PHP_INT_SIZE < 8) {
    throw new RuntimeException('pmtiles.inc.php requires a 64-bit PHP build');
}

// ── Varints ───────────────────────────────────────────────────────────────────

/**
 * Encodes an unsigned integer as a base-128 varint, low group first.
 */
function pmtiles_write_varint(int $value): string {
    if ($value < 0) {
        throw new PMTilesException("varint cannot encode a negative value ({$value})");
    }
    $out = '';
    while ($value >= 0x80) {
        $out .= chr(($value & 0x7f) | 0x80);
        $value >>= 7;
    }
    return $out . chr($value);
}

/**
 * Decodes one varint, advancing $pos past it.
 */
function pmtiles_read_varint(string $buf, int &$pos): int {
    $result = 0;
    $shift  = 0;
    $len    = strlen($buf);
    while (true) {
        if ($pos >= $len) {
            throw new PMTilesException('varint runs past the end of the buffer');
        }
        $byte = ord($buf[$pos]);
        $pos++;
        $result |= ($byte & 0x7f) << $shift;
        if (($byte & 0x80) === 0) {
            return $result;
        }
        $shift += 7;
        // A tile id, offset or length is at most 64 bits.  Without this a
        // corrupt directory reads forever instead of failing.
        if ($shift > 63) {
            throw new PMTilesException('varint is longer than 64 bits');
        }
    }
}

// ── Hilbert curve tile ids ────────────────────────────────────────────────────
//
// PMTiles orders tiles along a Hilbert curve rather than by x then y, so that
// tiles near each other on the map are near each other in the file.  Tile ids
// are zoom-major: every id at zoom z is lower than every id at zoom z+1, which
// is what lets mvtd's existing zoom loop emit tiles in ascending id order with
// only a per-zoom sort added.

/**
 * One step of the Hilbert curve's quadrant rotation.
 */
function pmtiles_rotate(int $n, int &$x, int &$y, int $rx, int $ry): void {
    if ($ry === 0) {
        if ($rx !== 0) {
            $x = $n - 1 - $x;
            $y = $n - 1 - $y;
        }
        $swap = $x;
        $x    = $y;
        $y    = $swap;
    }
}

/**
 * Maps a tile coordinate to its PMTiles tile id.
 */
function pmtiles_zxy_to_tileid(int $z, int $x, int $y): int {
    if ($z < 0 || $z > 31) {
        throw new PMTilesException("zoom {$z} is outside the 64-bit tile id range");
    }
    $side = 1 << $z;
    if ($x < 0 || $y < 0 || $x >= $side || $y >= $side) {
        throw new PMTilesException("tile {$z}/{$x}/{$y} is outside the bounds of its zoom");
    }

    // Tiles below this zoom, i.e. (4^z - 1) / 3.  Always an exact division.
    $acc = intdiv((1 << ($z * 2)) - 1, 3);

    $tx = $x;
    $ty = $y;
    for ($a = $z - 1; $a >= 0; $a--) {
        $s  = 1 << $a;
        $rx = $s & $tx;
        $ry = $s & $ty;
        pmtiles_rotate($s, $tx, $ty, $rx, $ry);
        $acc += ((3 * $rx) ^ $ry) << $a;
    }
    return $acc;
}

/**
 * Number of bits needed to represent $n.
 */
function pmtiles_bit_width(int $n): int {
    $count = 0;
    while ($n > 0) {
        $count++;
        $n >>= 1;
    }
    return $count;
}

/**
 * Maps a PMTiles tile id back to [z, x, y].
 */
function pmtiles_tileid_to_zxy(int $tileid): array {
    if ($tileid < 0) {
        throw new PMTilesException('negative tile id');
    }
    $z   = intdiv(pmtiles_bit_width(3 * $tileid + 1) - 1, 2);
    $acc = intdiv((1 << ($z * 2)) - 1, 3);
    $pos = $tileid - $acc;

    $x = 0;
    $y = 0;
    for ($a = 0; $a < $z; $a++) {
        $s  = 1 << $a;
        $rx = $s & intdiv($pos, 2);
        $ry = $s & ($pos ^ $rx);
        pmtiles_rotate($s, $x, $y, $rx, $ry);
        $pos >>= 1;
        $x += $rx;
        $y += $ry;
    }
    return [$z, $x, $y];
}

// ── Directories ───────────────────────────────────────────────────────────────
//
// A directory is a list of entries, each [tile_id, offset, length, run_length],
// serialised as four separate varint runs rather than four fields per entry.
// Grouping like with like is what makes the directory compress: tile ids become
// small deltas, run lengths are nearly all 1, and offsets are nearly all 0
// because consecutive entries are contiguous in the data section.
//
// run_length 0 is not a tile: it means the entry points at a leaf directory.

/**
 * Serialises a list of entries.  Uncompressed — the caller applies the
 * archive's internal compression.
 *
 * @param array $entries List of [tile_id, offset, length, run_length], sorted
 *                       ascending by tile_id and indexed from zero.
 */
function pmtiles_serialize_directory(array $entries): string {
    $count = count($entries);
    $out   = pmtiles_write_varint($count);

    $last = 0;
    foreach ($entries as $entry) {
        $out .= pmtiles_write_varint($entry[0] - $last);
        $last = $entry[0];
    }
    foreach ($entries as $entry) {
        $out .= pmtiles_write_varint($entry[3]);
    }
    foreach ($entries as $entry) {
        $out .= pmtiles_write_varint($entry[2]);
    }
    for ($i = 0; $i < $count; $i++) {
        // 0 is a sentinel for "immediately after the previous entry", which is
        // the common case in a clustered archive.  Anything else is stored as
        // offset + 1 so that 0 stays free to mean that.
        if ($i > 0 && $entries[$i][1] === $entries[$i - 1][1] + $entries[$i - 1][2]) {
            $out .= pmtiles_write_varint(0);
        } else {
            $out .= pmtiles_write_varint($entries[$i][1] + 1);
        }
    }
    return $out;
}

/**
 * Parses a decompressed directory back into entries.
 */
function pmtiles_deserialize_directory(string $buf): array {
    $pos   = 0;
    $count = pmtiles_read_varint($buf, $pos);

    $entries = [];
    $last    = 0;
    for ($i = 0; $i < $count; $i++) {
        $last     += pmtiles_read_varint($buf, $pos);
        $entries[] = [$last, 0, 0, 0];
    }
    for ($i = 0; $i < $count; $i++) {
        $entries[$i][3] = pmtiles_read_varint($buf, $pos);
    }
    for ($i = 0; $i < $count; $i++) {
        $entries[$i][2] = pmtiles_read_varint($buf, $pos);
    }
    for ($i = 0; $i < $count; $i++) {
        $value = pmtiles_read_varint($buf, $pos);
        if ($value === 0 && $i > 0) {
            $entries[$i][1] = $entries[$i - 1][1] + $entries[$i - 1][2];
        } else {
            $entries[$i][1] = $value - 1;
        }
    }
    return $entries;
}

/**
 * Binary-searches a directory for the entry covering $tileid.
 *
 * Returns null when the tile is absent.  An entry whose run_length is 0 is a
 * leaf pointer, and is returned as-is for the caller to follow.
 */
function pmtiles_find_entry(array $entries, int $tileid): ?array {
    $lo = 0;
    $hi = count($entries) - 1;
    while ($lo <= $hi) {
        $mid = ($lo + $hi) >> 1;
        if ($tileid > $entries[$mid][0]) {
            $lo = $mid + 1;
        } elseif ($tileid < $entries[$mid][0]) {
            $hi = $mid - 1;
        } else {
            return $entries[$mid];
        }
    }

    // No exact hit.  $hi now points at the last entry below $tileid, which
    // still answers the query if it is a leaf pointer, or if $tileid falls
    // inside its run of repeated tiles.
    if ($hi >= 0) {
        if ($entries[$hi][3] === 0) {
            return $entries[$hi];
        }
        if ($tileid - $entries[$hi][0] < $entries[$hi][3]) {
            return $entries[$hi];
        }
    }
    return null;
}

// ── Header ────────────────────────────────────────────────────────────────────

/**
 * Packs a signed 32-bit value little-endian, independent of host byte order.
 */
function pmtiles_pack_i32(int $value): string {
    return pack('V', $value & 0xFFFFFFFF);
}

/**
 * Reads a little-endian signed 32-bit value.
 */
function pmtiles_unpack_i32(string $buf, int $offset): int {
    $value = unpack('V', substr($buf, $offset, 4))[1];
    return $value >= 0x80000000 ? $value - 0x100000000 : $value;
}

/**
 * Serialises the fixed 127-byte header.
 *
 * @param array $h Keys matching the field names in the v3 specification.
 */
function pmtiles_serialize_header(array $h): string {
    $out = 'PMTiles' . chr(3);

    foreach ([
        'root_dir_offset', 'root_dir_bytes',
        'json_metadata_offset', 'json_metadata_bytes',
        'leaf_dirs_offset', 'leaf_dirs_bytes',
        'tile_data_offset', 'tile_data_bytes',
        'addressed_tiles_count', 'tile_entries_count', 'tile_contents_count',
    ] as $field) {
        $out .= pack('P', $h[$field] ?? 0);
    }

    $out .= chr($h['clustered'] ?? 1);
    $out .= chr($h['internal_compression'] ?? PMTILES_COMPRESSION_GZIP);
    $out .= chr($h['tile_compression'] ?? PMTILES_COMPRESSION_GZIP);
    $out .= chr($h['tile_type'] ?? PMTILES_TYPE_MVT);
    $out .= chr($h['min_zoom'] ?? 0);
    $out .= chr($h['max_zoom'] ?? 0);

    $out .= pmtiles_pack_i32($h['min_lon_e7'] ?? 0);
    $out .= pmtiles_pack_i32($h['min_lat_e7'] ?? 0);
    $out .= pmtiles_pack_i32($h['max_lon_e7'] ?? 0);
    $out .= pmtiles_pack_i32($h['max_lat_e7'] ?? 0);

    $out .= chr($h['center_zoom'] ?? 0);
    $out .= pmtiles_pack_i32($h['center_lon_e7'] ?? 0);
    $out .= pmtiles_pack_i32($h['center_lat_e7'] ?? 0);

    if (strlen($out) !== PMTILES_HEADER_BYTES) {
        throw new PMTilesException('serialised header is ' . strlen($out) . ' bytes, expected ' . PMTILES_HEADER_BYTES);
    }
    return $out;
}

/**
 * Parses the fixed 127-byte header.
 */
function pmtiles_deserialize_header(string $buf): array {
    if (strlen($buf) < PMTILES_HEADER_BYTES) {
        throw new PMTilesException('header is shorter than ' . PMTILES_HEADER_BYTES . ' bytes');
    }
    if (substr($buf, 0, 7) !== 'PMTiles') {
        throw new PMTilesException('not a PMTiles archive (bad magic number)');
    }
    $version = ord($buf[7]);
    if ($version !== 3) {
        throw new PMTilesException("PMTiles version {$version} is not supported (only 3)");
    }

    $counts = unpack('P11', substr($buf, 8, 88));
    $header = [
        'version'               => 3,
        'root_dir_offset'       => $counts[1],
        'root_dir_bytes'        => $counts[2],
        'json_metadata_offset'  => $counts[3],
        'json_metadata_bytes'   => $counts[4],
        'leaf_dirs_offset'      => $counts[5],
        'leaf_dirs_bytes'       => $counts[6],
        'tile_data_offset'      => $counts[7],
        'tile_data_bytes'       => $counts[8],
        'addressed_tiles_count' => $counts[9],
        'tile_entries_count'    => $counts[10],
        'tile_contents_count'   => $counts[11],
        'clustered'             => ord($buf[96]) === 1,
        'internal_compression'  => ord($buf[97]),
        'tile_compression'      => ord($buf[98]),
        'tile_type'             => ord($buf[99]),
        'min_zoom'              => ord($buf[100]),
        'max_zoom'              => ord($buf[101]),
        'min_lon_e7'            => pmtiles_unpack_i32($buf, 102),
        'min_lat_e7'            => pmtiles_unpack_i32($buf, 106),
        'max_lon_e7'            => pmtiles_unpack_i32($buf, 110),
        'max_lat_e7'            => pmtiles_unpack_i32($buf, 114),
        'center_zoom'           => ord($buf[118]),
        'center_lon_e7'         => pmtiles_unpack_i32($buf, 119),
        'center_lat_e7'         => pmtiles_unpack_i32($buf, 123),
    ];
    return $header;
}

// ── Internal compression ──────────────────────────────────────────────────────

/**
 * Applies the archive's internal compression to a directory or the metadata.
 */
function pmtiles_compress(string $data, int $compression): string {
    switch ($compression) {
        case PMTILES_COMPRESSION_NONE:
            return $data;
        case PMTILES_COMPRESSION_GZIP:
            return gzencode($data, 6);
        default:
            throw new PMTilesException("internal compression {$compression} is not supported");
    }
}

/**
 * Reverses pmtiles_compress().
 */
function pmtiles_decompress(string $data, int $compression): string {
    switch ($compression) {
        case PMTILES_COMPRESSION_NONE:
            return $data;
        case PMTILES_COMPRESSION_GZIP:
            $out = @gzdecode($data);
            if ($out === false) {
                throw new PMTilesException('gzip section could not be decompressed');
            }
            return $out;
        default:
            throw new PMTilesException("internal compression {$compression} is not supported");
    }
}

// ── Writer ────────────────────────────────────────────────────────────────────

/**
 * Streaming PMTiles v3 writer.
 *
 * Tiles must be added in ascending tile id order, which for a zoom-major
 * generator means: iterate zooms ascending, and within each zoom sort the
 * tile list by pmtiles_zxy_to_tileid() before encoding.  Requiring order from
 * the caller is what keeps this single-pass — there is no point at which the
 * whole tile index has to be resident.
 *
 * Nothing is held in memory but the entry currently being extended: tile
 * payloads stream to one temporary file and directory entries to another, and
 * both are concatenated into the archive by finalize().
 *
 * Usage:
 *   $w = new PMTilesWriter('/tmp/monthly.pmtiles', [
 *       'tile_type'        => PMTILES_TYPE_MVT,
 *       'tile_compression' => PMTILES_COMPRESSION_GZIP,
 *       'bounds'           => [-180.0, -85.0, 180.0, 85.0],
 *       'center'           => [0.0, 0.0, 3],
 *   ]);
 *   $w->add($z, $x, $y, $gz_bytes);   // ascending tile id
 *   $w->finalize(['name' => 'monthly', 'vector_layers' => [...]]);
 */
class PMTilesWriter {

    // One packed entry: tile_id, offset (both 64-bit), length, run_length.
    private const ENTRY_FORMAT = 'PPVV';
    private const ENTRY_BYTES  = 24;

    private string $path;
    private string $dataPath;
    private string $entryPath;

    /** @var resource */
    private $dataFh;
    /** @var resource */
    private $entryFh;

    private array $options;

    private int $offset         = 0;   // next free byte in the tile data section
    private int $lastTileId     = -1;
    private int $entryCount     = 0;
    private int $addressedCount = 0;
    private int $contentCount   = 0;

    /** The entry still open for run-length extension, or null. */
    private ?array $pending = null;

    /** Content hash of the most recent distinct tile, for adjacent dedup. */
    private ?string $lastHash   = null;
    private int     $lastOffset = 0;
    private int     $lastLength = 0;

    /** hash => [offset, length], only populated when full dedup is on. */
    private array $seen = [];

    private int $minZoomSeen = 255;
    private int $maxZoomSeen = 0;

    private bool $closed = false;

    /**
     * @param string $path    Where the finished archive is written.
     * @param array  $options tile_type, tile_compression, internal_compression,
     *                        bounds [w,s,e,n], center [lon,lat,z], min_zoom,
     *                        max_zoom, dedupe, tmp_dir.
     */
    public function __construct(string $path, array $options = []) {
        $this->path    = $path;
        $this->options = $options + [
            'tile_type'            => PMTILES_TYPE_MVT,
            'tile_compression'     => PMTILES_COMPRESSION_GZIP,
            'internal_compression' => PMTILES_COMPRESSION_GZIP,
            'bounds'               => [-180.0, -85.0, 180.0, 85.0],
            'center'               => null,
            'min_zoom'             => null,
            'max_zoom'             => null,
            // Full dedup keeps a hash of every distinct tile in memory, which
            // for a bucket of ten million tiles is gigabytes.  WifiDB tiles
            // are almost never byte-identical, so it would buy nothing; runs
            // of identical adjacent tiles are still collapsed either way, at
            // no memory cost.
            'dedupe'               => false,
            'tmp_dir'              => null,
        ];

        $tmpBase = $this->options['tmp_dir'] !== null
            ? rtrim($this->options['tmp_dir'], '/') . '/' . basename($path)
            : $path;

        $this->dataPath  = $tmpBase . '.data.tmp';
        $this->entryPath = $tmpBase . '.entries.tmp';

        $dataFh = fopen($this->dataPath, 'wb');
        if ($dataFh === false) {
            throw new PMTilesException("cannot open {$this->dataPath} for writing");
        }
        $entryFh = fopen($this->entryPath, 'wb');
        if ($entryFh === false) {
            fclose($dataFh);
            throw new PMTilesException("cannot open {$this->entryPath} for writing");
        }
        $this->dataFh  = $dataFh;
        $this->entryFh = $entryFh;
    }

    /**
     * Adds one tile.  $data is stored verbatim, so it must already carry the
     * compression declared as 'tile_compression' — mvtd's tiles arrive gzipped
     * and are not recompressed here.
     *
     * @param string $data Tile payload; empty payloads are ignored.
     */
    public function add(int $z, int $x, int $y, string $data): void {
        if ($this->closed) {
            throw new PMTilesException('cannot add tiles after finalize()');
        }
        if ($data === '') {
            return;
        }

        $tileId = pmtiles_zxy_to_tileid($z, $x, $y);
        if ($tileId <= $this->lastTileId) {
            // Worth failing loudly: a reader binary-searches the directory, so
            // an out-of-order archive does not error, it silently fails to
            // find tiles that are present.
            throw new PMTilesException(
                "tile {$z}/{$x}/{$y} (id {$tileId}) is not after the previous id {$this->lastTileId};"
                . ' tiles must be added in ascending tile id order'
            );
        }
        $this->lastTileId = $tileId;

        if ($z < $this->minZoomSeen) $this->minZoomSeen = $z;
        if ($z > $this->maxZoomSeen) $this->maxZoomSeen = $z;
        $this->addressedCount++;

        [$offset, $length] = $this->place($data);

        // Extend the open entry when this tile is the next id along and points
        // at the same bytes; otherwise start a new one.
        if ($this->pending !== null
            && $tileId === $this->pending[0] + $this->pending[3]
            && $offset === $this->pending[1]) {
            $this->pending[3]++;
            return;
        }

        $this->flushPending();
        $this->pending = [$tileId, $offset, $length, 1];
    }

    /**
     * Finds or writes the bytes for a tile, returning [offset, length].
     */
    private function place(string $data): array {
        $length = strlen($data);
        $hash   = md5($data, true);

        // Adjacent match: free, and the only kind that helps a tileset whose
        // tiles are all different except for runs of identical empties.
        if ($hash === $this->lastHash && $length === $this->lastLength) {
            return [$this->lastOffset, $this->lastLength];
        }

        if ($this->options['dedupe'] && isset($this->seen[$hash])) {
            return $this->seen[$hash];
        }

        $offset = $this->offset;
        if (fwrite($this->dataFh, $data) !== $length) {
            throw new PMTilesException("short write to {$this->dataPath}");
        }
        $this->offset += $length;
        $this->contentCount++;

        $this->lastHash   = $hash;
        $this->lastOffset = $offset;
        $this->lastLength = $length;

        if ($this->options['dedupe']) {
            $this->seen[$hash] = [$offset, $length];
        }
        return [$offset, $length];
    }

    /**
     * Writes the open entry to the entry file.
     */
    private function flushPending(): void {
        if ($this->pending === null) {
            return;
        }
        $packed = pack(
            self::ENTRY_FORMAT,
            $this->pending[0], $this->pending[1], $this->pending[2], $this->pending[3]
        );
        if (fwrite($this->entryFh, $packed) !== self::ENTRY_BYTES) {
            throw new PMTilesException("short write to {$this->entryPath}");
        }
        $this->entryCount++;
        $this->pending = null;
    }

    /**
     * Builds the directories, writes the archive and removes the temporaries.
     *
     * @param array $metadata Decoded JSON metadata — name, vector_layers and
     *                        anything else the tileset wants to carry.
     */
    public function finalize(array $metadata = []): void {
        if ($this->closed) {
            throw new PMTilesException('finalize() called twice');
        }
        $this->flushPending();
        $this->closed = true;

        fclose($this->dataFh);
        fclose($this->entryFh);

        // An archive with no tiles is legal, and writing one is the point: a
        // bucket whose data has gone empty must publish that emptiness, or the
        // previous build stays the newest and shows stale contents forever.
        // The directory is a serialised empty list, and a reader finds nothing
        // in it, which is the correct answer.

        $internal = $this->options['internal_compression'];
        [$rootBytes, $leavesPath, $leavesLength] = $this->buildDirectories($internal);

        $json = pmtiles_compress(
            json_encode($metadata, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            $internal
        );

        $bounds = $this->options['bounds'];
        $center = $this->options['center'] ?? [
            ($bounds[0] + $bounds[2]) / 2,
            ($bounds[1] + $bounds[3]) / 2,
            $this->options['min_zoom'] ?? $this->minZoomSeen,
        ];

        $rootOffset = PMTILES_HEADER_BYTES;
        $jsonOffset = $rootOffset + strlen($rootBytes);
        $leafOffset = $jsonOffset + strlen($json);
        $dataOffset = $leafOffset + $leavesLength;

        $header = pmtiles_serialize_header([
            'root_dir_offset'       => $rootOffset,
            'root_dir_bytes'        => strlen($rootBytes),
            'json_metadata_offset'  => $jsonOffset,
            'json_metadata_bytes'   => strlen($json),
            'leaf_dirs_offset'      => $leafOffset,
            'leaf_dirs_bytes'       => $leavesLength,
            'tile_data_offset'      => $dataOffset,
            'tile_data_bytes'       => $this->offset,
            'addressed_tiles_count' => $this->addressedCount,
            'tile_entries_count'    => $this->entryCount,
            'tile_contents_count'   => $this->contentCount,
            'clustered'             => 1,
            'internal_compression'  => $internal,
            'tile_compression'      => $this->options['tile_compression'],
            'tile_type'             => $this->options['tile_type'],
            'min_zoom'              => $this->options['min_zoom'] ?? $this->minZoomSeen,
            'max_zoom'              => $this->options['max_zoom'] ?? $this->maxZoomSeen,
            'min_lon_e7'            => (int)round($bounds[0] * 1e7),
            'min_lat_e7'            => (int)round($bounds[1] * 1e7),
            'max_lon_e7'            => (int)round($bounds[2] * 1e7),
            'max_lat_e7'            => (int)round($bounds[3] * 1e7),
            'center_zoom'           => (int)$center[2],
            'center_lon_e7'         => (int)round($center[0] * 1e7),
            'center_lat_e7'         => (int)round($center[1] * 1e7),
        ]);

        $out = fopen($this->path, 'wb');
        if ($out === false) {
            throw new PMTilesException("cannot open {$this->path} for writing");
        }
        fwrite($out, $header);
        fwrite($out, $rootBytes);
        fwrite($out, $json);
        if ($leavesPath !== null) {
            $this->appendFile($out, $leavesPath);
        }
        $this->appendFile($out, $this->dataPath);
        fclose($out);

        @unlink($this->dataPath);
        @unlink($this->entryPath);
        if ($leavesPath !== null) {
            @unlink($leavesPath);
        }
    }

    /**
     * Chooses a directory layout and builds it.
     *
     * Returns [root bytes, leaf file path or null, leaf section length].
     */
    private function buildDirectories(int $internal): array {
        // Try a single root directory first.  Only worth attempting when the
        // entry count could conceivably fit — every entry costs at least four
        // bytes before compression, so beyond a few tens of thousands the
        // answer is always no and building the string is wasted work.
        if ($this->entryCount === 0) {
            return [pmtiles_compress(pmtiles_serialize_directory([]), $internal), null, 0];
        }

        if ($this->entryCount <= 65536) {
            $flat = pmtiles_compress(
                pmtiles_serialize_directory($this->readEntries(0, $this->entryCount)),
                $internal
            );
            if (strlen($flat) <= PMTILES_MAX_ROOT_BYTES) {
                return [$flat, null, 0];
            }
        }

        // Otherwise split into leaves, doubling the leaf size until the root
        // that indexes them fits the 16 KiB budget.
        $leafSize = 4096;
        while (true) {
            [$root, $path, $length] = $this->buildLeaves($leafSize, $internal);
            if (strlen($root) <= PMTILES_MAX_ROOT_BYTES) {
                return [$root, $path, $length];
            }
            @unlink($path);
            $leafSize *= 2;
        }
    }

    /**
     * Writes every leaf directory to a temporary file and returns the root
     * that indexes them.
     */
    private function buildLeaves(int $leafSize, int $internal): array {
        // Beside the other temporaries rather than beside the finished
        // archive, so that a caller who moved them out of a web-served
        // directory moved all of them.
        $leafPath = $this->dataPath . '.leaves.tmp';
        $leafFh   = fopen($leafPath, 'wb');
        if ($leafFh === false) {
            throw new PMTilesException("cannot open {$leafPath} for writing");
        }

        $rootEntries = [];
        $leafOffset  = 0;
        for ($start = 0; $start < $this->entryCount; $start += $leafSize) {
            $slice = $this->readEntries($start, min($leafSize, $this->entryCount - $start));
            $leaf  = pmtiles_compress(pmtiles_serialize_directory($slice), $internal);

            // run_length 0 marks this as a pointer to a leaf rather than a tile.
            $rootEntries[] = [$slice[0][0], $leafOffset, strlen($leaf), 0];

            fwrite($leafFh, $leaf);
            $leafOffset += strlen($leaf);
        }
        fclose($leafFh);

        $root = pmtiles_compress(pmtiles_serialize_directory($rootEntries), $internal);
        return [$root, $leafPath, $leafOffset];
    }

    /**
     * Reads $count entries starting at index $start from the entry file.
     */
    private function readEntries(int $start, int $count): array {
        $fh = fopen($this->entryPath, 'rb');
        if ($fh === false) {
            throw new PMTilesException("cannot reopen {$this->entryPath}");
        }
        fseek($fh, $start * self::ENTRY_BYTES);
        $raw = fread($fh, $count * self::ENTRY_BYTES);
        fclose($fh);

        $entries = [];
        for ($i = 0; $i < $count; $i++) {
            $fields = unpack(
                'Ptile_id/Poffset/Vlength/Vrun',
                substr($raw, $i * self::ENTRY_BYTES, self::ENTRY_BYTES)
            );
            $entries[] = [$fields['tile_id'], $fields['offset'], $fields['length'], $fields['run']];
        }
        return $entries;
    }

    /**
     * Copies a temporary file onto the end of the archive.
     */
    private function appendFile($out, string $path): void {
        $in = fopen($path, 'rb');
        if ($in === false) {
            throw new PMTilesException("cannot reopen {$path}");
        }
        stream_copy_to_stream($in, $out);
        fclose($in);
    }
}

// ── Reader ────────────────────────────────────────────────────────────────────

/**
 * Reads tiles out of a PMTiles v3 archive on local disk.
 *
 * The header and root directory are parsed once and can be handed out with
 * index() and passed back to the constructor, so a request-per-tile caller
 * such as api/mvt.php can keep them in APCu and reduce each tile to one seek
 * and one read.  Key that cache on the archive's mtime: a new build swaps the
 * file underneath and every offset in the old index becomes wrong.
 */
class PMTilesReader {

    private string $path;
    /** @var resource|null */
    private $fh = null;
    private array $header;
    private array $root;

    /**
     * @param string     $path  Archive on local disk.
     * @param array|null $index Previously returned by index(), to skip parsing.
     */
    public function __construct(string $path, ?array $index = null) {
        $this->path = $path;
        if ($index !== null) {
            $this->header = $index['header'];
            $this->root   = $index['root'];
            return;
        }

        $head = $this->read(0, PMTILES_HEADER_BYTES);
        $this->header = pmtiles_deserialize_header($head);
        $this->root   = pmtiles_deserialize_directory(pmtiles_decompress(
            $this->read($this->header['root_dir_offset'], $this->header['root_dir_bytes']),
            $this->header['internal_compression']
        ));
    }

    public function __destruct() {
        if ($this->fh !== null) {
            fclose($this->fh);
        }
    }

    /**
     * The parsed header.
     */
    public function header(): array {
        return $this->header;
    }

    /**
     * The header and root directory, for caching between requests.
     */
    public function index(): array {
        return ['header' => $this->header, 'root' => $this->root];
    }

    /**
     * The decoded JSON metadata.
     */
    public function metadata(): array {
        if ($this->header['json_metadata_bytes'] === 0) {
            return [];
        }
        $json = pmtiles_decompress(
            $this->read($this->header['json_metadata_offset'], $this->header['json_metadata_bytes']),
            $this->header['internal_compression']
        );
        return json_decode($json, true) ?? [];
    }

    /**
     * Returns one tile's stored bytes, or null when the archive does not hold
     * it.  The bytes carry whatever compression the header declares — for an
     * mvtd archive that is gzip, which is also what an HTTP client wants, so
     * the common path decompresses nothing.
     */
    public function tile(int $z, int $x, int $y): ?string {
        $tileId = pmtiles_zxy_to_tileid($z, $x, $y);

        $entries   = $this->root;
        $dirOffset = $this->header['leaf_dirs_offset'];

        // Two levels is what any writer produces; the extra iterations cost
        // nothing and stop a malformed archive from looping forever.
        for ($depth = 0; $depth < 4; $depth++) {
            $entry = pmtiles_find_entry($entries, $tileId);
            if ($entry === null) {
                return null;
            }
            if ($entry[3] !== 0) {
                return $this->read($this->header['tile_data_offset'] + $entry[1], $entry[2]);
            }
            if ($entry[2] === 0) {
                return null;
            }
            $entries = pmtiles_deserialize_directory(pmtiles_decompress(
                $this->read($dirOffset + $entry[1], $entry[2]),
                $this->header['internal_compression']
            ));
        }
        throw new PMTilesException('leaf directories are nested more than four deep');
    }

    /**
     * Reads a byte range from the archive.
     */
    private function read(int $offset, int $length): string {
        if ($length === 0) {
            return '';
        }
        if ($this->fh === null) {
            $fh = fopen($this->path, 'rb');
            if ($fh === false) {
                throw new PMTilesException("cannot open {$this->path} for reading");
            }
            $this->fh = $fh;
        }
        if (fseek($this->fh, $offset) !== 0) {
            throw new PMTilesException("cannot seek to {$offset} in {$this->path}");
        }
        $data = fread($this->fh, $length);
        if ($data === false || strlen($data) !== $length) {
            throw new PMTilesException("short read of {$length} bytes at {$offset} in {$this->path}");
        }
        return $data;
    }
}
