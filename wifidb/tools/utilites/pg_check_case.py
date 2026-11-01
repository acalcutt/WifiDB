#!/usr/bin/env python3
"""Report identifiers inside pgsql branches whose casing does not match the schema.

Read-only. Handles both PHP quoting styles: in a double-quoted PHP string a SQL
double quote appears as \\", in a single-quoted one it appears as ".
"""
import re
import sys

SCHEMA = 'wifidb/blank_db.sqlsrv'
src = open(SCHEMA, encoding='utf-8-sig', errors='replace').read()

TABLE_COLS = {}
for m in re.finditer(r'CREATE TABLE \[dbo\]\.\[([^\]]+)\]\((.*?)\n\)\s*ON \[PRIMARY\]', src, re.S):
    TABLE_COLS[m.group(1).lower()] = {c.group(1).lower(): c.group(1)
                                      for c in re.finditer(r'^\t\[([^\]]+)\]', m.group(2), re.M)}

SQLKW = set("""select from where and or not in is null as on left right inner outer join group by
order asc desc limit offset insert into values update set delete distinct count sum avg min max
case when then else end like between having union all exists cast text numeric integer bigint
smallint timestamp double precision varchar conflict do nothing returning current_timestamp
xmax to_char coalesce replace substring upper lower md5 now interval true false top rows fetch
next only output merge matched using target source excluded default""".split())


def collect_pg_lines(path):
    """yield (lineno, line) for lines that belong to a pgsql branch."""
    lines = open(path, encoding='utf-8', errors='replace').read().replace('\r\n', '\n').split('\n')
    out = []
    i = 0
    while i < len(lines):
        if re.search(r'==\s*"pgsql"', lines[i]):
            j = i + 1
            depth = 0
            started = False
            while j < len(lines) and j < i + 45:
                l = lines[j]
                out.append((j + 1, l))
                depth += l.count('{') - l.count('}')
                if '{' in l:
                    started = True
                if started and depth <= 0:
                    break
                if not started and l.rstrip().endswith(';'):
                    break
                j += 1
            i = j
        i += 1
    return out


def check(path):
    problems = []
    for ln, line in collect_pg_lines(path):
        if 'SELECT' not in line.upper() and 'FROM' not in line.upper() \
           and 'WHERE' not in line.upper() and 'SET ' not in line.upper() \
           and 'JOIN' not in line.upper() and 'ORDER' not in line.upper() \
           and 'INTO' not in line.upper() and 'GROUP' not in line.upper():
            continue
        # normalise both PHP quoting styles to a plain " for analysis
        probe = line.replace('\\"', '"')
        # every double-quoted identifier
        for name in re.findall(r'"([A-Za-z_]\w*)"', probe):
            low = name.lower()
            canon = {c[low] for c in TABLE_COLS.values() if low in c}
            if canon and name not in canon:
                problems.append((ln, 'quoted', name, sorted(canon)))
        # bare words that are mixed-case columns somewhere (should be quoted)
        for name in re.findall(r'(?<![\w."])([A-Za-z_]\w*)(?![\w."])', probe):
            low = name.lower()
            if low in SQLKW:
                continue
            canon = {c[low] for c in TABLE_COLS.values() if low in c}
            mixed = {c for c in canon if c != c.lower()}
            if mixed and name == name.lower() and name not in canon:
                problems.append((ln, 'bare', name, sorted(canon)))
    return problems


if __name__ == '__main__':
    total = 0
    for p in sys.argv[1:]:
        try:
            pr = check(p)
        except Exception as e:
            print('ERR', p, e)
            continue
        if pr:
            print('=== %s' % p.replace('\\', '/'))
            for ln, kind, name, canon in pr:
                print('  %5d %-6s %-18s schema has %s' % (ln, kind, name, canon))
            total += len(pr)
    print('\nTOTAL casing problems inside pgsql arms: %d' % total)
