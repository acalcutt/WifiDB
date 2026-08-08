#!/usr/bin/env python3
"""Flag SQL in the WifiDB PHP tree that Postgres would reject or silently mis-key.

Two failure classes are reported:
  CASE  - a mixed-case schema identifier or alias appearing unquoted, which
          Postgres folds to lower case (breaking the column lookup, or the
          $row['Key'] lookup when it is a SELECT alias).
  DIAL  - dialect syntax with no Postgres equivalent (TOP n, LIMIT a, b,
          OFFSET .. FETCH, MERGE, backticks, ...).

Lines already inside an explicit pgsql branch are skipped.
"""
import os
import re
import sys

ROOT = sys.argv[1] if len(sys.argv) > 1 else 'wifidb'

# Mixed-case identifiers that exist in the schema (from blank_db.sqlsrv), plus
# the SELECT aliases the PHP layer reads back by exact name.
SCHEMA_MIXED = """AccuracyMeters Alt AP_ID Area AUTH BSSID BTx BTX Capital CHAN Continent
Country CurrencyCode CurrencyName DB_stats ENCR EquivalentFipsCode FA File_GPS_ID File_ID
FLAGS Geo GPS_Date GPS_ID HighGps_ID Hist_Date Hist_ID HorDilPitch ISO ISO3 KPH LA Label
Languages Lat Lon Manufacturer ModDate MPH NETTYPE New NewAPPercent NT NumOfSats OTx OTX
Phone Population RADTYPE RSSI SECTYPE Sig SSID TrackAngle ValidGPS Vis_ver""".split()

SQL_HINT = re.compile(
    r'\b(SELECT|INSERT\s+INTO|UPDATE|DELETE\s+FROM|MERGE\s+INTO|REPLACE\s+INTO|'
    r'FROM|WHERE|ORDER\s+BY|GROUP\s+BY|INNER\s+JOIN|LEFT\s+JOIN|SET\s)\b', re.I)

DIALECT = [
    (re.compile(r'\bSELECT\s+TOP\s+\d', re.I), 'SELECT TOP n'),
    (re.compile(r'\bLIMIT\s+[^\s;"\']+\s*,'), 'LIMIT a, b'),
    (re.compile(r'OFFSET\s+\S+\s+ROWS', re.I), 'OFFSET..FETCH'),
    (re.compile(r'\bMERGE\s+INTO\b', re.I), 'MERGE'),
    (re.compile(r'\bREPLACE\s+INTO\b', re.I), 'REPLACE INTO'),
    (re.compile(r'ON\s+DUPLICATE\s+KEY', re.I), 'ON DUPLICATE KEY'),
    (re.compile(r'`'), 'backtick quoting'),
    (re.compile(r'\[[A-Za-z_]\w*\]'), 'bracket quoting'),
    (re.compile(r'\bDATE_FORMAT\s*\(', re.I), 'DATE_FORMAT'),
    (re.compile(r'\bFORMAT\s*\(\s*\w+\s*,', re.I), 'T-SQL FORMAT'),
    (re.compile(r'\bGETDATE\s*\(', re.I), 'GETDATE'),
    (re.compile(r'\bGETUTCDATE\s*\(', re.I), 'GETUTCDATE'),
    (re.compile(r'\bISNULL\s*\(', re.I), 'ISNULL'),
    (re.compile(r'\bSUBSTRING_INDEX\s*\(', re.I), 'SUBSTRING_INDEX'),
    (re.compile(r'\bDATEADD\s*\(|\bDATEDIFF\s*\(', re.I), 'DATEADD/DATEDIFF'),
    (re.compile(r'WITH\s*\(\s*(HOLDLOCK|NOLOCK)', re.I), 'table hint'),
    (re.compile(r'\bROW_NUMBER\s*\(\s*\)\s*OVER', re.I), 'ROW_NUMBER paging'),
    (re.compile(r'\bIDENTITY\b|\bSCOPE_IDENTITY\b', re.I), 'IDENTITY'),
]

mixed_re = re.compile(r'(?<![\w."`\[])(' + '|'.join(sorted(SCHEMA_MIXED, key=len, reverse=True)) +
                      r')(?![\w."`\]])')

rows = []
for dirpath, dirnames, filenames in os.walk(ROOT):
    dirnames[:] = [d for d in dirnames if d not in ('smarty', 'themes', 'out', '.git')]
    for fn in filenames:
        if not fn.endswith('.php'):
            continue
        path = os.path.join(dirpath, fn)
        text = open(path, encoding='utf-8', errors='replace').read()
        lines = text.split('\n')
        # Track which lines sit inside an "else if (... == \"pgsql\")" block by
        # brace depth; good enough for this codebase's one-liner branch style.
        pg_until = -1
        for i, line in enumerate(lines):
            if 'pgsql' in line:
                pg_until = i + (1 if line.rstrip().endswith(')') else 0)
                continue
            if i <= pg_until:
                continue
            if not SQL_HINT.search(line):
                continue
            hits = []
            for rx, label in DIALECT:
                if rx.search(line):
                    hits.append(label)
            # Ignore already-quoted "Mixed" identifiers.
            stripped = re.sub(r'"[A-Za-z_]\w*"', '', line)
            cases = sorted(set(mixed_re.findall(stripped)))
            if cases:
                hits.append('CASE:' + ','.join(cases))
            if hits:
                rows.append((path.replace('\\', '/'), i + 1, '; '.join(hits), line.strip()[:110]))

by_file = {}
for p, ln, why, src in rows:
    by_file.setdefault(p, []).append((ln, why, src))

for p in sorted(by_file):
    print('\n=== %s  (%d)' % (p, len(by_file[p])))
    for ln, why, src in by_file[p]:
        print('  %5d  %-38s %s' % (ln, why[:38], src))
print('\nTOTAL lines flagged: %d across %d files' % (len(rows), len(by_file)))
