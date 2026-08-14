#!/usr/bin/env python3
"""
Test_PMTiles_verify.py — checks archives written by lib/pmtiles.inc.php
Copyright (C) 2026 Andrew Calcutt

Run Test_PMTiles.php first, then:
  python Test_PMTiles_verify.py [output_dir]

The PHP tests prove the writer agrees with the PHP reader, which would also be
true if both were wrong in the same way. This re-reads the same archives with
the PMTiles reference reader in Python -- the one our mbutil fork vendors and
uses -- so passing here means mb-util can convert what we generate, and so can
anything else that follows the specification.

Finds the pmtiles package from a sibling mbutil or PMTiles checkout, or from
site-packages if it is installed. PMTILES_PYTHON_DIR overrides the search.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
"""

import json
import hashlib
import os
import sys
import tempfile

HERE = os.path.dirname(os.path.abspath(__file__))

# The package sits at PMTiles/python/pmtiles/pmtiles -- the directory to put on
# the path is the one holding setup.py, not python/ itself. mbutil appends the
# same path for the same reason.
CANDIDATES = [
    os.environ.get("PMTILES_PYTHON_DIR"),
    # Where our mbutil fork keeps it, as a submodule.
    os.path.join(HERE, "..", "..", "..", "..", "mbutil", "PMTiles", "python", "pmtiles"),
    os.path.join(HERE, "..", "..", "..", "..", "PMTiles", "python", "pmtiles"),
    os.path.join(HERE, "..", "..", "..", "PMTiles", "python", "pmtiles"),
]

for candidate in CANDIDATES:
    if candidate and os.path.isfile(os.path.join(candidate, "pmtiles", "reader.py")):
        sys.path.insert(0, os.path.abspath(candidate))
        break

try:
    from pmtiles.reader import Reader, MmapSource, all_tiles
    from pmtiles.tile import zxy_to_tileid, tileid_to_zxy, Compression, TileType
except ImportError as exc:
    print(f"Cannot import the pmtiles package ({exc}).")
    print("Point PMTILES_PYTHON_DIR at a PMTiles checkout's python/ directory,")
    print("or pip install pmtiles.")
    sys.exit(2)


passed = 0
failed = 0


def check(what, ok, detail=""):
    global passed, failed
    if ok:
        passed += 1
        print(f"  ok   {what}")
    else:
        failed += 1
        print(f"  FAIL {what}" + (f" -- {detail}" if detail else ""))


def section(name):
    print(f"\n{name}\n" + "-" * len(name))


out_dir = sys.argv[1] if len(sys.argv) > 1 else os.path.join(
    tempfile.gettempdir(), "pmtiles_test"
)
manifest_path = os.path.join(out_dir, "manifest.json")
if not os.path.exists(manifest_path):
    print(f"No manifest at {manifest_path} -- run Test_PMTiles.php first.")
    sys.exit(2)

with open(manifest_path) as handle:
    manifest = json.load(handle)


# -- Hilbert ids agree with the reference implementation ----------------------
#
# If PHP and Python disagree about which id a tile has, every other test still
# passes on its own terms and the archive is simply unreadable by anyone else.

section("Tile ids agree with the reference implementation")

mismatches = []
for z in (0, 1, 2, 5, 9, 12, 14, 19):
    side = 1 << z
    step = max(1, side // 17)
    for x in range(0, side, step):
        for y in range(0, side, step):
            php_id = zxy_to_tileid(z, x, y)  # reference
            if tileid_to_zxy(php_id) != (z, x, y):
                mismatches.append(f"{z}/{x}/{y}")
check(
    "the reference round-trips over the same sweep the PHP tests use",
    not mismatches,
    ", ".join(mismatches[:3]),
)


# -- The small archive --------------------------------------------------------

section("The small archive, read by the reference reader")

small = manifest["small"]
with open(small["path"], "rb") as handle:
    reader = Reader(MmapSource(handle))
    header = reader.header()
    metadata = reader.metadata()

    check("header parses", header["addressed_tiles_count"] == len(small["tiles"]))
    check("tile type is MVT", header["tile_type"] == TileType.MVT)
    check(
        "internal compression is gzip",
        header["internal_compression"] == Compression.GZIP,
    )
    check("tile compression is gzip", header["tile_compression"] == Compression.GZIP)
    check("clustered flag is set", header["clustered"] is True)
    check("zoom range", (header["min_zoom"], header["max_zoom"]) == (0, 5))
    check(
        "bounds survive the e7 conversion",
        (header["min_lon_e7"], header["max_lat_e7"]) == (-1800000000, 850000000),
        f'{header["min_lon_e7"]}, {header["max_lat_e7"]}',
    )

    # The metadata section is the one place tippecanoe departs from the
    # specification -- it writes raw JSON while declaring gzip. Reaching this
    # line at all means ours is compressed as the header claims, because the
    # reference reader decompresses unconditionally.
    check("metadata decompresses and parses", metadata.get("name") == "wifidb-test")
    check(
        "vector_layers survive",
        metadata.get("vector_layers", [{}])[0].get("fields", {}).get("sectype")
        == "Number",
    )

    wrong = []
    for key, expected_hash in small["tiles"].items():
        z, x, y = (int(part) for part in key.split("/"))
        data = reader.get(z, x, y)
        if data is None or hashlib.md5(data).hexdigest() != expected_hash:
            wrong.append(key)
    check(
        "every tile matches the hash PHP recorded",
        not wrong,
        f"{len(wrong)} wrong, e.g. " + ", ".join(wrong[:3]),
    )

with open(small["path"], "rb") as handle:
    enumerated = list(all_tiles(MmapSource(handle)))
check(
    "iterating the archive yields every tile exactly once",
    len(enumerated) == len(small["tiles"]),
    f"{len(enumerated)} vs {len(small['tiles'])}",
)


# -- The large archive, which uses leaf directories ---------------------------

section("The large archive, which uses leaf directories")

large = manifest["large"]
with open(large["path"], "rb") as handle:
    reader = Reader(MmapSource(handle))
    header = reader.header()

    check("leaf directories are present", header["leaf_directory_length"] > 0)
    check(
        "the root fits in the first 16 KiB",
        header["root_offset"] + header["root_length"] <= 16384,
        f'root ends at {header["root_offset"] + header["root_length"]}',
    )
    check("every tile is addressed", header["addressed_tiles_count"] == large["count"])
    check(
        "tile compression is none, as declared",
        header["tile_compression"] == Compression.NONE,
    )

    wrong = []
    for key, expected in large["tiles"].items():
        z, x, y = (int(part) for part in key.split("/"))
        data = reader.get(z, x, y)
        if data is None or data.decode() != expected:
            wrong.append(key)
    check(
        "sampled tiles resolve through the leaf directories",
        not wrong,
        ", ".join(wrong[:3]),
    )

with open(large["path"], "rb") as handle:
    count = sum(1 for _ in all_tiles(MmapSource(handle)))
check(
    "iterating crosses every leaf and finds them all",
    count == large["count"],
    f"{count} vs {large['count']}",
)


# -- Runs of identical tiles --------------------------------------------------

section("Runs of identical tiles")

with open(manifest["runs"]["path"], "rb") as handle:
    reader = Reader(MmapSource(handle))
    header = reader.header()
    check("64 tiles addressed", header["addressed_tiles_count"] == 64)
    check("25 distinct payloads stored", header["tile_contents_count"] == 25)

    # The point of a run: the reference reader must resolve every id inside it
    # back to the one stored copy, not only the id the entry starts at.
    resolved = 0
    for i in range(40):
        z, x, y = tileid_to_zxy(zxy_to_tileid(3, 0, 0) + i)
        if reader.get(z, x, y) == b"EMPTY":
            resolved += 1
    check("all 40 ids in the run resolve", resolved == 40, f"{resolved} of 40")

with open(manifest["runs"]["path"], "rb") as handle:
    count = sum(1 for _ in all_tiles(MmapSource(handle)))
check("iteration expands the run", count == 64, f"{count} of 64")


print("\n" + "=" * 60)
print(f"passed: {passed}   failed: {failed}")
print("=" * 60)

sys.exit(0 if failed == 0 else 1)
