<?php
/*
mlt.inc.php — Pure PHP MapLibre Tile (MLT) Encoder
Copyright (C) 2024 Andrew Calcutt

Encodes WifiDB access-point features into the MapLibre Tile (MLT) format as
specified at https://maplibre.org/maplibre-tile-spec/specification/.

The encoder targets the stable subset of MLT v1 that covers all-Point geometry
with flat (non-nested) scalar and string properties — exactly what WifiDB needs.
Supported encodings: VARINT, DELTA-VARINT, RLE-VARINT, DELTA-RLE-VARINT for
integers; plain (length+data) or dictionary (length+offset+data) for strings;
COMPONENTWISE_DELTA-VARINT for the vertex buffer.  No FastPFOR, no FSST.

Reference implementation used to derive the binary layout:
  https://github.com/maplibre/maplibre-tile-spec/tree/main/java

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

// ── Physical stream type ordinals (high nibble of stream type byte) ──────────
define('MLT_PST_PRESENT', 0);
define('MLT_PST_DATA',    1);
define('MLT_PST_OFFSET',  2);
define('MLT_PST_LENGTH',  3);

// ── Dictionary/logical type ordinals (low nibble of stream type byte) ────────
// Used with DATA streams:
define('MLT_DICT_NONE',   0);
define('MLT_DICT_SINGLE', 1);
define('MLT_DICT_SHARED', 2);
define('MLT_DICT_VERTEX', 3);
define('MLT_DICT_MORTON', 4);
define('MLT_DICT_FSST',   5);

// Used with LENGTH streams:
define('MLT_LEN_VAR_BINARY',  0);   // plain string lengths
define('MLT_LEN_GEOMETRIES',  1);
define('MLT_LEN_PARTS',       2);
define('MLT_LEN_RINGS',       3);
define('MLT_LEN_TRIANGLES',   4);
define('MLT_LEN_SYMBOL',      5);
define('MLT_LEN_DICTIONARY',  6);   // dict-encoded string entry lengths

// Used with OFFSET streams:
define('MLT_OFF_VERTEX', 0);
define('MLT_OFF_INDEX',  1);
define('MLT_OFF_STRING', 2);   // string dictionary indices

// ── Logical level technique ordinals (bits 7-5 / 4-2 of encoding byte) ──────
define('MLT_LOG_NONE',               0);
define('MLT_LOG_DELTA',              1);
define('MLT_LOG_COMPONENTWISE_DELTA',2);
define('MLT_LOG_RLE',                3);
define('MLT_LOG_MORTON',             4);

// ── Physical level technique ordinals (bits 1-0 of encoding byte) ────────────
// Must match C++ PhysicalLevelTechnique enum: NONE=0, FAST_PFOR=1, VARINT=2, ALP=3
define('MLT_PHYS_NONE',    0);   // raw bytes — no compression
define('MLT_PHYS_FASTPFOR',1);   // FastPFOR (not used by this encoder; maplibre-native builds with MLT_WITH_FASTPFOR=OFF)
define('MLT_PHYS_VARINT',  2);   // VarInt (protobuf-compatible)

// ── Column type codes (from MltTypeMap.Tag0x01) ───────────────────────────────
// IDs (logical type = ID, stored as u32 or u64)
define('MLT_COL_ID_U32',      0);
define('MLT_COL_ID_U32_NULL', 1);
define('MLT_COL_ID_U64',      2);
define('MLT_COL_ID_U64_NULL', 3);
// Geometry
define('MLT_COL_GEOMETRY',    4);
// Scalar types — even = not nullable, odd = nullable
define('MLT_COL_BOOLEAN',    10);   define('MLT_COL_BOOLEAN_NULL',  11);
define('MLT_COL_INT8',       12);   define('MLT_COL_INT8_NULL',     13);
define('MLT_COL_UINT8',      14);   define('MLT_COL_UINT8_NULL',    15);
define('MLT_COL_INT32',      16);   define('MLT_COL_INT32_NULL',    17);
define('MLT_COL_UINT32',     18);   define('MLT_COL_UINT32_NULL',   19);
define('MLT_COL_INT64',      20);   define('MLT_COL_INT64_NULL',    21);
define('MLT_COL_UINT64',     22);   define('MLT_COL_UINT64_NULL',   23);
define('MLT_COL_FLOAT',      24);   define('MLT_COL_FLOAT_NULL',    25);
define('MLT_COL_DOUBLE',     26);   define('MLT_COL_DOUBLE_NULL',   27);
define('MLT_COL_STRING',     28);   define('MLT_COL_STRING_NULL',   29);
// Struct (complex, with children)
define('MLT_COL_STRUCT',     30);

define('MLT_EXTENT', 4096);

// ── Varint encoding ────────────────────────────────────────────────────────────
// Protobuf-compatible unsigned varint (7 bits per byte, MSB continuation flag).
function mlt_varint(int $n): string {
    // Treat $n as unsigned (values from mlt_zigzag or unsigned DB IDs
    // are always non-negative; this function only handles non-negative inputs).
    $out = '';
    do {
        $b = $n & 0x7F;
        $n >>= 7;
        if ($n !== 0) $b |= 0x80;
        $out .= chr($b);
    } while ($n !== 0);
    return $out;
}

// ZigZag-encode a signed integer to an unsigned integer for compact varint storage.
function mlt_zigzag(int $n): int {
    return ($n << 1) ^ ($n >> 31);
}

// Encode an array of integers as consecutive varints.
// $signed=true applies ZigZag first (for signed integers and delta values).
function mlt_encode_varints(array $values, bool $signed): string {
    $out = '';
    foreach ($values as $v) {
        $v = (int)$v;
        if ($signed) $v = mlt_zigzag($v);
        $out .= mlt_varint($v);
    }
    return $out;
}

// ── ORC-style Boolean RLE ──────────────────────────────────────────────────────
// Encodes a boolean array into ORC Byte-RLE on bit-packed bytes.
// Bits are packed MSB-first: value[0] → bit 7 of byte 0.
// Returns raw bytes (not prefixed with length).
function mlt_boolean_rle(array $bools): string {
    $n = count($bools);
    if ($n === 0) return '';

    // 1. Pack booleans into bytes (8 per byte, MSB first).
    $bytes = [];
    $byte = 0; $bit = 7;
    foreach ($bools as $v) {
        if ($v) $byte |= (1 << $bit);
        if (--$bit < 0) { $bytes[] = $byte; $byte = 0; $bit = 7; }
    }
    if ($bit < 7) $bytes[] = $byte; // flush partial byte

    // 2. Apply ORC Byte-level RLE.
    return _mlt_byte_rle($bytes);
}

// ORC Byte-RLE: length >= 3 identical bytes → (count−3), value.
//               literals (< 3 or varying) → -(count), byte, byte, ...
function _mlt_byte_rle(array $bytes): string {
    $n = count($bytes);
    if ($n === 0) return '';
    $out = '';
    $i = 0;
    while ($i < $n) {
        // Count identical run.
        $run = 1;
        while ($run < 130 && ($i + $run) < $n && $bytes[$i + $run] === $bytes[$i]) {
            $run++;
        }
        if ($run >= 3) {
            $out .= chr($run - 3) . chr($bytes[$i]);
            $i += $run;
            continue;
        }
        // Collect literals up to 128 (stopping early if a 3+ run begins).
        $lit = 1;
        while ($lit < 128 && ($i + $lit) < $n) {
            // Peek ahead: if next 3 bytes are identical, stop before them.
            if (($i + $lit + 2) < $n
                && $bytes[$i + $lit] === $bytes[$i + $lit + 1]
                && $bytes[$i + $lit] === $bytes[$i + $lit + 2]) {
                break;
            }
            $lit++;
        }
        $out .= chr((256 - $lit) & 0xFF); // store -lit as unsigned byte
        for ($j = 0; $j < $lit; $j++) {
            $out .= chr($bytes[$i + $j]);
        }
        $i += $lit;
    }
    return $out;
}

// ── Integer auto-encoding ──────────────────────────────────────────────────────
// Selects the smallest representation among PLAIN, DELTA, RLE, DELTA-RLE.
// Returns ['log1' => int, 'log2' => int, 'data' => string,
//          'numValues' => int, 'numRuns' => int|null, 'numRleValues' => int|null]
//
// numValues: count of varint-encoded integers in data (= N for non-RLE,
//            = len(runs) + len(unique_values) for RLE/DELTA-RLE).
// numRuns/numRleValues: extra fields for RleEncodedStreamMetadata (null if not RLE).
//
// $signed: whether the source values are a signed type (INT_32 etc.).
//   - PLAIN  → zigzag iff $signed
//   - DELTA  → delta values always zigzag (they are signed regardless of source)
//   - RLE    → values zigzag iff $signed
//   - DRLE   → delta values always zigzag
function mlt_encode_int_auto(array $values, bool $signed): array {
    $n = count($values);
    if ($n === 0) {
        return ['log1' => MLT_LOG_NONE, 'log2' => MLT_LOG_NONE,
                'data' => '', 'numValues' => 0, 'numRuns' => null, 'numRleValues' => null];
    }

    // Precompute deltas.
    $deltas = [];
    $prev = 0;
    foreach ($values as $v) {
        $deltas[] = (int)$v - $prev;
        $prev = (int)$v;
    }

    // ── PLAIN ──
    $plain_data = mlt_encode_varints($values, $signed);

    // ── DELTA ──
    $delta_data = mlt_encode_varints($deltas, true); // deltas always zigzag

    // ── Count runs for RLE and DELTA-RLE ──
    $rle_runs = []; $rle_vals = [];
    $i = 0;
    while ($i < $n) {
        $run = 1;
        while ($i + $run < $n && $values[$i + $run] === $values[$i]) $run++;
        $rle_runs[] = $run; $rle_vals[] = (int)$values[$i];
        $i += $run;
    }
    $num_rle_runs = count($rle_runs);
    $is_const = ($num_rle_runs === 1);

    $drle_runs = []; $drle_vals = [];
    $i = 0;
    while ($i < $n) {
        $run = 1;
        while ($i + $run < $n && $deltas[$i + $run] === $deltas[$i]) $run++;
        $drle_runs[] = $run; $drle_vals[] = $deltas[$i];
        $i += $run;
    }
    $num_drle_runs = count($drle_runs);

    // ── RLE (if avg run >= 2 or const stream) ──
    $rle_data = null; $rle_phys_num = 0;
    if ($n / $num_rle_runs >= 2 || $is_const) {
        $rle_phys_num = $num_rle_runs + count($rle_vals);
        if ($signed) {
            $encoded_vals = array_map('mlt_zigzag', $rle_vals);
            $rle_concat = array_merge($rle_runs, $encoded_vals);
        } else {
            $rle_concat = array_merge($rle_runs, $rle_vals);
        }
        $rle_data = mlt_encode_varints($rle_concat, false);
    }

    // ── DELTA-RLE (if avg delta run >= 2) ──
    $drle_data = null; $drle_phys_num = 0;
    if ($n / $num_drle_runs >= 2) {
        $drle_phys_num = $num_drle_runs + count($drle_vals);
        $encoded_drle_vals = array_map('mlt_zigzag', $drle_vals);
        $drle_concat = array_merge($drle_runs, $encoded_drle_vals);
        $drle_data = mlt_encode_varints($drle_concat, false);
    }

    // ── Pick smallest ──
    $sizes = [
        'plain' => strlen($plain_data),
        'delta' => strlen($delta_data),
        'rle'   => ($rle_data !== null)  ? strlen($rle_data)  : PHP_INT_MAX,
        'drle'  => ($drle_data !== null) ? strlen($drle_data) : PHP_INT_MAX,
    ];

    // Const streams always use RLE (matches Java: isConstStream forces RLE).
    $best = $is_const ? 'rle' : array_search(min($sizes), $sizes);

    switch ($best) {
        case 'delta':
            return ['log1' => MLT_LOG_DELTA, 'log2' => MLT_LOG_NONE,
                    'data' => $delta_data, 'numValues' => $n,
                    'numRuns' => null, 'numRleValues' => null];
        case 'rle':
            return ['log1' => MLT_LOG_RLE, 'log2' => MLT_LOG_NONE,
                    'data' => $rle_data, 'numValues' => $rle_phys_num,
                    'numRuns' => $num_rle_runs, 'numRleValues' => $n];
        case 'drle':
            return ['log1' => MLT_LOG_DELTA, 'log2' => MLT_LOG_RLE,
                    'data' => $drle_data, 'numValues' => $drle_phys_num,
                    'numRuns' => $num_drle_runs, 'numRleValues' => $n];
        default: // plain
            return ['log1' => MLT_LOG_NONE, 'log2' => MLT_LOG_NONE,
                    'data' => $plain_data, 'numValues' => $n,
                    'numRuns' => null, 'numRleValues' => null];
    }
}

// ── Stream metadata serializer ─────────────────────────────────────────────────
// Produces the binary StreamMetadata (or RleEncodedStreamMetadata) header.
//
// Stream type byte: (physicalStreamType << 4) | logicalType
// Encoding byte:    (logicalTech1 << 5) | (logicalTech2 << 2) | physicalTech
// Followed by: varint(numValues), varint(byteLength)
// If RLE (numRuns !== null): varint(numRuns), varint(numRleValues) appended.
function mlt_stream_meta(
    int $pst,       // PhysicalStreamType ordinal
    int $log_type,  // logical type ordinal (DictionaryType, LengthType, or OffsetType)
    int $log1,      // LogicalLevelTechnique1
    int $log2,      // LogicalLevelTechnique2
    int $phys,      // PhysicalLevelTechnique
    int $num_vals,  // numValues (post-encoding count)
    int $byte_len,  // byteLength (byte count of the data)
    ?int $num_runs = null,        // RleEncodedStreamMetadata.runs
    ?int $num_rle_values = null   // RleEncodedStreamMetadata.numRleValues (original N)
): string {
    $type_byte     = chr(($pst << 4) | $log_type);
    $encoding_byte = chr(($log1 << 5) | ($log2 << 2) | $phys);
    $meta = $type_byte . $encoding_byte . mlt_varint($num_vals) . mlt_varint($byte_len);
    if ($num_runs !== null) {
        $meta .= mlt_varint($num_runs) . mlt_varint($num_rle_values ?? 0);
    }
    return $meta;
}

// ── Integer stream builder ─────────────────────────────────────────────────────
// Wraps mlt_encode_int_auto with stream metadata, producing a complete stream.
// $pst: PhysicalStreamType ordinal.   $log_type: logical type ordinal.
function mlt_int_stream(array $values, bool $signed, int $pst, int $log_type): string {
    $enc = mlt_encode_int_auto($values, $signed);
    $is_rle = ($enc['log1'] === MLT_LOG_RLE || $enc['log2'] === MLT_LOG_RLE);
    $meta = mlt_stream_meta(
        $pst, $log_type,
        $enc['log1'], $enc['log2'], MLT_PHYS_VARINT,
        $enc['numValues'], strlen($enc['data']),
        $is_rle ? $enc['numRuns']      : null,
        $is_rle ? $enc['numRleValues'] : null
    );
    return $meta . $enc['data'];
}

// ── String column encoder ──────────────────────────────────────────────────────
// Selects between plain (length+data) and dictionary (length+offset+data)
// encoding, whichever produces smaller output.
//
// Returns the full column bytes including the leading stream-count varint and
// (if $nullable) the present stream.
// Null and empty-string values are treated as absent (null) for nullable columns.
function mlt_encode_string_column(array $values, bool $nullable): string {
    $n = count($values);

    // Separate present bitmap from non-null strings.
    $present = [];
    $non_null = [];
    foreach ($values as $v) {
        $is_present = ($v !== null && $v !== '');
        $present[] = $is_present;
        if ($is_present) $non_null[] = (string)$v;
    }

    if (empty($non_null)) {
        // All absent — signal zero streams (column present but empty in this tile).
        return mlt_varint(0);
    }

    // ── Build plain encoding ──
    $plain_len_data = '';
    $plain_raw_data = '';
    foreach ($non_null as $s) {
        $plain_len_data .= (int)strlen($s);   // collect as array below
        $plain_raw_data .= $s;
    }
    // Redo as array for mlt_int_stream
    $len_arr = array_map('strlen', $non_null);
    $plain_len_stream = mlt_int_stream($len_arr, false, MLT_PST_LENGTH, MLT_LEN_VAR_BINARY);
    $plain_data_meta  = mlt_stream_meta(
        MLT_PST_DATA, MLT_DICT_NONE,
        MLT_LOG_NONE, MLT_LOG_NONE, MLT_PHYS_NONE,
        count($non_null), strlen($plain_raw_data)
    );
    $plain_streams = $plain_len_stream . $plain_data_meta . $plain_raw_data;

    // ── Build dictionary encoding ──
    $dict = []; $dict_map = [];
    $offsets = []; $dict_lengths = []; $dict_bytes = '';
    foreach ($non_null as $s) {
        if (!isset($dict_map[$s])) {
            $dict_map[$s] = count($dict);
            $dict[] = $s;
            $dict_lengths[] = strlen($s);
            $dict_bytes .= $s;
        }
        $offsets[] = $dict_map[$s];
    }
    $dict_len_stream  = mlt_int_stream($dict_lengths, false, MLT_PST_LENGTH, MLT_LEN_DICTIONARY);
    $dict_off_stream  = mlt_int_stream($offsets, false, MLT_PST_OFFSET, MLT_OFF_STRING);
    $dict_data_meta   = mlt_stream_meta(
        MLT_PST_DATA, MLT_DICT_SINGLE,
        MLT_LOG_NONE, MLT_LOG_NONE, MLT_PHYS_NONE,
        count($dict), strlen($dict_bytes)
    );
    $dict_streams = $dict_len_stream . $dict_off_stream . $dict_data_meta . $dict_bytes;

    // ── Pick encoding ──
    $use_dict = (strlen($dict_streams) < strlen($plain_streams));
    $data_streams = $use_dict ? $dict_streams : $plain_streams;
    $data_stream_count = $use_dict ? 3 : 2;

    // ── Present stream (if nullable) ──
    $present_bytes = '';
    if ($nullable) {
        $raw = mlt_boolean_rle($present);
        $present_meta = mlt_stream_meta(
            MLT_PST_PRESENT, 0,
            MLT_LOG_RLE, MLT_LOG_NONE, MLT_PHYS_NONE,
            $n, strlen($raw)
        );
        $present_bytes = $present_meta . $raw;
        $data_stream_count++;
    }

    return mlt_varint($data_stream_count) . $present_bytes . $data_streams;
}

// ── Nullable integer column encoder ─────────────────────────────────────────
// Encodes an array of nullable integer values (null = absent).
// Returns: [varint numStreams] + present_stream + data_stream.
// Used for columns declared as INT32_NULL (type code 17) or similar.
function mlt_encode_nullable_int_column(array $values, bool $signed): string {
    $n = count($values);
    $present  = [];
    $non_null = [];
    foreach ($values as $v) {
        $is_present = ($v !== null);
        $present[]  = $is_present;
        if ($is_present) $non_null[] = (int)$v;
    }

    if (empty($non_null)) {
        return mlt_varint(0);  // all absent
    }

    $present_raw  = mlt_boolean_rle($present);
    $present_meta = mlt_stream_meta(
        MLT_PST_PRESENT, 0,
        MLT_LOG_RLE, MLT_LOG_NONE, MLT_PHYS_NONE,
        $n, strlen($present_raw)
    );
    $present_stream = $present_meta . $present_raw;
    $data_stream    = mlt_int_stream($non_null, $signed, MLT_PST_DATA, MLT_DICT_NONE);

    return mlt_varint(2) . $present_stream . $data_stream;
}

// ── Geometry column encoder ────────────────────────────────────────────────────
// Encodes all-Point geometry (the only type used in WifiDB).
// $points: array of ['x' => int, 'y' => int] in tile extent coordinates.
// Returns the geometry column body including the leading numStreams varint.
function mlt_encode_geometry_column(array $points): string {
    $n = count($points);

    // ── Stream 1: GeometryType (LENGTH stream, no logical type) ──────────────
    // All values are 0 (GeometryType.POINT.ordinal() = 0).
    // isConstStream → Java forces RLE regardless of other options.
    $geom_type_stream = mlt_int_stream(
        array_fill(0, $n, 0),
        false,                // isSigned = false (unsigned geometry type codes)
        MLT_PST_LENGTH, 0    // LENGTH stream, null logical type → ordinal 0
    );

    // ── Stream 2: VertexBuffer (DATA+VERTEX, COMPONENTWISE_DELTA+NONE+VARINT) ─
    // Interleave delta-zigzag-encoded x,y pairs:  dx0,dy0, dx1,dy1, ...
    $delta_vals = [];
    $prev_x = 0; $prev_y = 0;
    foreach ($points as $pt) {
        $dx = (int)$pt['x'] - $prev_x;
        $dy = (int)$pt['y'] - $prev_y;
        $delta_vals[] = mlt_zigzag($dx);
        $delta_vals[] = mlt_zigzag($dy);
        $prev_x = (int)$pt['x'];
        $prev_y = (int)$pt['y'];
    }
    $vertex_data  = mlt_encode_varints($delta_vals, false); // already zigzag-encoded
    $vertex_meta  = mlt_stream_meta(
        MLT_PST_DATA, MLT_DICT_VERTEX,
        MLT_LOG_COMPONENTWISE_DELTA, MLT_LOG_NONE, MLT_PHYS_VARINT,
        count($delta_vals), strlen($vertex_data) // numValues = 2*N
    );
    $vertex_stream = $vertex_meta . $vertex_data;

    return mlt_varint(2) . $geom_type_stream . $vertex_stream;
}

// ── Tile metadata serializer ───────────────────────────────────────────────────
// Serializes a column type code plus optional name and children.
function mlt_column_type_bytes(int $type_code, string $name = '', array $children = []): string {
    $out = mlt_varint($type_code);
    // Names are present for type codes >= 10 (scalar/string types, STRUCT).
    // IDs (0-3) and GEOMETRY (4) have no name field.
    if ($type_code >= 10) {
        $out .= mlt_varint(strlen($name)) . $name;
    }
    // STRUCT (30) has a child count and child type entries.
    if ($type_code === MLT_COL_STRUCT && !empty($children)) {
        $out .= mlt_varint(count($children));
        foreach ($children as $child) {
            $out .= mlt_column_type_bytes($child['type'], $child['name'] ?? '', $child['children'] ?? []);
        }
    }
    return $out;
}

// ── Main tile encoder ──────────────────────────────────────────────────────────
/**
 * Encode one WifiDB feature table (one age bucket) as an MLT tile.
 *
 * $layer_name: the bucket name used as the MLT layer/FeatureTable name. Buckets
 *   prefixed 'cell_' use the cell-tower schema below; all others use the AP schema.
 * $features:   array of rows. AP rows (non-'cell_' buckets), each with:
 *   'id'            => int      Feature ID (UINT_32)
 *   'x'             => int      Tile-coordinate x (0..extent-1)
 *   'y'             => int      Tile-coordinate y (0..extent-1)
 *   'sectype'       => int      Encryption type  (INT_32)
 *   'chan'           => int      Channel number   (INT_32)
 *   'points'        => int      Point count      (INT_32)
 *   'high_gps_sig'  => int      Best GPS signal  (INT_32)
 *   'high_gps_rssi' => int      Best GPS RSSI    (INT_32)
 *   'radio'         => string   Radio band string (nullable)
 *   'mac'           => string   MAC/BSSID         (nullable)
 *   'user'          => string   Contributor username (nullable)
 *   'ssid'          => string   Network name      (nullable)
 *   'auth'          => string   Auth type         (nullable)
 *   'encry'         => string   Encryption string (nullable)
 *   'nt'            => string   Network type      (nullable)
 *   'btx'           => string   Basic tx rates    (nullable)
 *   'otx'           => string   Optional tx rates (nullable)
 *   'fa'            => string   First seen date   (nullable)
 *   'la'            => string   Last seen date    (nullable)
 *   'lat'           => string   Latitude string   (nullable)
 *   'lon'           => string   Longitude string  (nullable)
 *   'alt'           => string   Altitude string   (nullable)
 *   'manuf'         => string   Manufacturer      (nullable)
 *   'age_days'      => int      Days since last active (optional, defaults to
 *                                0; only populated by the combined 'heatmap'
 *                                bucket, for heatmap-weight on the client).
 *
 * Cell rows ('cell_*' buckets), each with:
 *   'id'       => int      Feature ID (UINT_32)
 *   'x'        => int      Tile-coordinate x (0..extent-1)
 *   'y'        => int      Tile-coordinate y (0..extent-1)
 *   'points'   => int      Point count      (INT_32)
 *   'rssi'     => int      Best GPS RSSI    (INT_32)
 *   'age_days' => int      Days since last active (optional, defaults to 0)
 *   'mac'      => string   MCCMNC_LAC_CELLID (nullable)
 *   'ssid'     => string   Network/operator name (nullable)
 *   'authmode' => string   Authentication mode (nullable)
 *   'chan'     => string   Channel / frequency band (nullable)
 *   'type'     => string   Cell type, e.g. LTE/GSM/CDMA (nullable)
 *   'fa'       => string   First seen date   (nullable)
 *   'la'       => string   Last seen date    (nullable)
 *   'user'     => string   Contributor username (nullable)
 *
 * $extent:    Tile coordinate extent (default 4096, must match projection).
 *
 * Returns raw MLT bytes for one FeatureTable block (NOT gzip-compressed).
 * Returns an empty string if $features is empty.
 */
function mlt_encode_tile(string $layer_name, array $features, int $extent = MLT_EXTENT): string {
    $n = count($features);
    if ($n === 0) return '';

    $is_cell = (strpos($layer_name, 'cell_') === 0);

    // ── Tile metadata (embedded FeatureTableMetadata) ─────────────────────────
    if ($is_cell) {
        $columns = [
            ['type' => MLT_COL_ID_U32],
            ['type' => MLT_COL_GEOMETRY],
            // Non-nullable integer scalars
            ['type' => MLT_COL_INT32,       'name' => 'points'],
            ['type' => MLT_COL_INT32,       'name' => 'rssi'],
            ['type' => MLT_COL_INT32,       'name' => 'age_days'],
            // Nullable string properties
            ['type' => MLT_COL_STRING_NULL, 'name' => 'mac'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'ssid'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'authmode'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'chan'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'type'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'fa'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'la'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'user'],
        ];
    } else {
        // Column schema matches the tippecanoe PMTiles export: 21 property fields
        // plus the implicit ID and geometry columns.
        $columns = [
            // ID: UINT_32, not nullable (typeCode = 0, no name)
            ['type' => MLT_COL_ID_U32],
            // Geometry: not nullable (typeCode = 4, no name)
            ['type' => MLT_COL_GEOMETRY],
            // Non-nullable integer scalars
            ['type' => MLT_COL_INT32,       'name' => 'sectype'],
            ['type' => MLT_COL_INT32,       'name' => 'chan'],
            ['type' => MLT_COL_INT32,       'name' => 'points'],
            ['type' => MLT_COL_INT32,       'name' => 'high_gps_sig'],
            ['type' => MLT_COL_INT32,       'name' => 'high_gps_rssi'],
            ['type' => MLT_COL_INT32,       'name' => 'age_days'],
            // Nullable string properties
            ['type' => MLT_COL_STRING_NULL, 'name' => 'radio'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'mac'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'user'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'ssid'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'auth'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'encry'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'nt'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'btx'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'otx'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'fa'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'la'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'lat'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'lon'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'alt'],
            ['type' => MLT_COL_STRING_NULL, 'name' => 'manuf'],
        ];
    }

    $meta = mlt_varint(strlen($layer_name)) . $layer_name
          . mlt_varint($extent)
          . mlt_varint(count($columns));
    foreach ($columns as $col) {
        $meta .= mlt_column_type_bytes(
            $col['type'],
            isset($col['name']) ? $col['name'] : '',
            isset($col['children']) ? $col['children'] : []
        );
    }

    // ── Feature table body ────────────────────────────────────────────────────
    // Column order must match metadata order above.

    if ($is_cell) {
        $ids       = []; $points_xy = [];
        $pointcnts = []; $rssis     = []; $age_days = [];
        $macs      = []; $ssids     = []; $authmodes = [];
        $chans     = []; $types     = []; $fas = []; $las = []; $users = [];

        foreach ($features as $f) {
            $ids[]       = (int)$f['id'];
            $points_xy[] = ['x' => (int)$f['x'], 'y' => (int)$f['y']];
            $pointcnts[] = (int)$f['points'];
            $rssis[]     = (int)$f['rssi'];
            $age_days[]  = isset($f['age_days']) ? (int)$f['age_days'] : 0;
            $macs[]      = isset($f['mac'])      ? $f['mac']      : null;
            $ssids[]     = isset($f['ssid'])     ? $f['ssid']     : null;
            $authmodes[] = isset($f['authmode']) ? $f['authmode'] : null;
            $chans[]     = isset($f['chan'])     ? $f['chan']     : null;
            $types[]     = isset($f['type'])     ? $f['type']     : null;
            $fas[]       = isset($f['fa'])       ? $f['fa']       : null;
            $las[]       = isset($f['la'])       ? $f['la']       : null;
            $users[]     = isset($f['user'])     ? $f['user']     : null;
        }

        $id_col     = mlt_int_stream($ids, false, MLT_PST_DATA, MLT_DICT_NONE);
        $geom_col   = mlt_encode_geometry_column($points_xy);
        $points_col = mlt_int_stream($pointcnts, true, MLT_PST_DATA, MLT_DICT_NONE);
        $rssi_col   = mlt_int_stream($rssis,     true, MLT_PST_DATA, MLT_DICT_NONE);
        $age_col    = mlt_int_stream($age_days,  true, MLT_PST_DATA, MLT_DICT_NONE);
        $mac_col      = mlt_encode_string_column($macs,      true);
        $ssid_col     = mlt_encode_string_column($ssids,     true);
        $authmode_col = mlt_encode_string_column($authmodes, true);
        $chan_col     = mlt_encode_string_column($chans,     true);
        $type_col     = mlt_encode_string_column($types,     true);
        $fa_col       = mlt_encode_string_column($fas,       true);
        $la_col       = mlt_encode_string_column($las,       true);
        $user_col     = mlt_encode_string_column($users,     true);

        $body = $id_col . $geom_col
              . $points_col . $rssi_col . $age_col
              . $mac_col . $ssid_col . $authmode_col . $chan_col . $type_col
              . $fa_col . $la_col . $user_col;
    } else {
        $ids       = []; $points_xy = [];
        $sectypes  = []; $chans     = []; $pointcnts = []; $sig = []; $rssi = []; $age_days = [];
        $radios    = []; $macs      = []; $users  = [];
        $ssids     = []; $auths     = []; $encrys = []; $nts = [];
        $btxs      = []; $otxs      = [];
        $fas       = []; $las       = [];
        $lats      = []; $lons      = []; $alts   = []; $manufs = [];

        foreach ($features as $f) {
            $ids[]       = (int)$f['id'];
            $points_xy[] = ['x' => (int)$f['x'], 'y' => (int)$f['y']];
            $sectypes[]  = (int)$f['sectype'];
            $chans[]     = (int)$f['chan'];
            $pointcnts[] = (int)$f['points'];
            $sig[]       = (int)$f['high_gps_sig'];
            $rssi[]      = (int)$f['high_gps_rssi'];
            $age_days[]  = isset($f['age_days']) ? (int)$f['age_days'] : 0;
            $radios[]    = isset($f['radio'])  ? $f['radio']  : null;
            $macs[]      = isset($f['mac'])    ? $f['mac']    : null;
            $users[]     = isset($f['user'])   ? $f['user']   : null;
            $ssids[]     = isset($f['ssid'])   ? $f['ssid']   : null;
            $auths[]     = isset($f['auth'])   ? $f['auth']   : null;
            $encrys[]    = isset($f['encry'])  ? $f['encry']  : null;
            $nts[]       = isset($f['nt'])     ? $f['nt']     : null;
            $btxs[]      = isset($f['btx'])    ? $f['btx']    : null;
            $otxs[]      = isset($f['otx'])    ? $f['otx']    : null;
            $fas[]       = isset($f['fa'])     ? $f['fa']     : null;
            $las[]       = isset($f['la'])     ? $f['la']     : null;
            $lats[]      = isset($f['lat'])    ? $f['lat']    : null;
            $lons[]      = isset($f['lon'])    ? $f['lon']    : null;
            $alts[]      = isset($f['alt'])    ? $f['alt']    : null;
            $manufs[]    = isset($f['manuf'])  ? $f['manuf']  : null;
        }

        // ID column — UINT_32, no stream count prefix.
        $id_col      = mlt_int_stream($ids, false, MLT_PST_DATA, MLT_DICT_NONE);
        // Geometry column — [varint numStreams] + streams.
        $geom_col    = mlt_encode_geometry_column($points_xy);
        // Non-nullable integer columns — INT_32 signed, no stream count prefix.
        $sectype_col = mlt_int_stream($sectypes,  true, MLT_PST_DATA, MLT_DICT_NONE);
        $chan_col     = mlt_int_stream($chans,     true, MLT_PST_DATA, MLT_DICT_NONE);
        $points_col  = mlt_int_stream($pointcnts, true, MLT_PST_DATA, MLT_DICT_NONE);
        $sig_col     = mlt_int_stream($sig,       true, MLT_PST_DATA, MLT_DICT_NONE);
        $rssi_col    = mlt_int_stream($rssi,      true, MLT_PST_DATA, MLT_DICT_NONE);
        $age_col     = mlt_int_stream($age_days,  true, MLT_PST_DATA, MLT_DICT_NONE);
        // Nullable string columns — [varint numStreams] + present + data streams.
        $radio_col   = mlt_encode_string_column($radios,  true);
        $mac_col     = mlt_encode_string_column($macs,    true);
        $user_col    = mlt_encode_string_column($users,   true);
        $ssid_col    = mlt_encode_string_column($ssids,   true);
        $auth_col    = mlt_encode_string_column($auths,   true);
        $encry_col   = mlt_encode_string_column($encrys,  true);
        $nt_col      = mlt_encode_string_column($nts,     true);
        $btx_col     = mlt_encode_string_column($btxs,    true);
        $otx_col     = mlt_encode_string_column($otxs,    true);
        $fa_col      = mlt_encode_string_column($fas,     true);
        $la_col      = mlt_encode_string_column($las,     true);
        $lat_col     = mlt_encode_string_column($lats,    true);
        $lon_col     = mlt_encode_string_column($lons,    true);
        $alt_col     = mlt_encode_string_column($alts,    true);
        $manuf_col   = mlt_encode_string_column($manufs,  true);

        $body = $id_col . $geom_col
              . $sectype_col . $chan_col . $points_col . $sig_col . $rssi_col . $age_col
              . $radio_col . $mac_col . $user_col
              . $ssid_col . $auth_col . $encry_col . $nt_col
              . $btx_col . $otx_col
              . $fa_col . $la_col
              . $lat_col . $lon_col . $alt_col . $manuf_col;
    }

    // ── Wrap in FeatureTable envelope ─────────────────────────────────────────
    // Layout: [varint tagLength][varint tag=1][metadata][body]
    // tagLength = sizeof(tag) + sizeof(metadata) + sizeof(body)
    $tag        = mlt_varint(1);
    $tag_length = strlen($tag) + strlen($meta) + strlen($body);

    return mlt_varint($tag_length) . $tag . $meta . $body;
}
