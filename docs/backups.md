# Backups, moving servers and restoring

WifiDB backs up its whole database to one file, copies it to S3 or to another server, and restores it. A backup made on
**SQL Server restores on MariaDB, and the other way round**, so the same tools move a site to a new server or to the
other kind of database.

Everything is on **Backups** (`admin/backups.php`, admin mode on) or with `tools/utilites/backup.php` on the server.

## What a backup holds

A backup is one file, `wifidb-backup-YYYYMMDD-HHMMSS.tar`, in `backup_dir` (the Proxmox template:
`/srv/wifidb-data/backups`; otherwise `{wifidb_tools}/backups`). It's a plain tar:

| File | Contents |
|---|---|
| `tables/<table>.<part>.jsonl.gz` | every row of every table, one JSON array per line, 500,000 rows per part |
| `secrets.json` | `two_factor_key`, which unlocks the saved authenticator apps and the keys on Site Settings |
| `manifest.json` (last) | where it came from (site, database, WifiDB version, migrations), every table's columns and row counts, and each file's SHA-256 |

Values are written so neither database's own types leak into the file: dates as `Y-m-d H:i:s`, binary columns as
base64, and the odd text that isn't valid UTF-8 as `{"x": base64}`.

Not included, because they belong to the server rather than the site: sessions (`user_login_hashes`), this database's
migration list, the node's daemon switch and schedule (`settings`, `schedule`), running daemons (`daemon_pid_stats`) and
queued admin jobs. Uploaded files, exports and tiles are files, not database rows: copy those with rsync.

Backups hold e-mail addresses, password hashes and the key. The folder is `www-data`'s, mode 0700.

## Making one

- **Backups → Back up now**, or `php backup.php export`. The site stays up; changes made while it runs may be only
  partly in it.
- **Every night** at about 03:30 (`tools/cron/backups daily`), unless Site Settings turns it off. The newest
  `backup_keep` (7) of these are kept; backups made by hand are never removed.

The page queues requests; `tools/cron/backups`, each minute, runs them one at a time and the page shows the log. A
failed request leaves the administrators an alert.

## Downloading one

Each finished backup has a **Download** link. It answers byte ranges, so a big download that was cut off can carry on
(`curl -C -`, or a browser's resume). The template serves the backups page from its own PHP pool with no time limit
(`wdb-backup`), since the other pools stop a request after 30 seconds or 5 minutes. On another web server, give
`admin/backups.php` and `admin/backup_transfer.php` a PHP setup without `max_execution_time` or
`request_terminate_timeout` limits.

A downloaded `.tar` copied into another server's backups folder shows up on its Backups page and restores the same way.

## Copies in S3 (Garage, MinIO, AWS)

On **Site Settings**, give an S3 address (such as `https://s3.example.com`, without the bucket), region (Garage: the
`s3_region` in `garage.toml`, usually `garage`), bucket, folder (default: the site's domain), key ID and secret. Saving
tries the bucket first. Each nightly backup is then copied there too, and the newest `backup_s3_keep` (30) are kept.
Other backups can be copied with **Copy to S3**; the Backups page lists the bucket and **fetches** a backup from it,
checked against the SHA-256 stored with it.

```bash
php backup.php list --remote
php backup.php upload wifidb-backup-YYYYMMDD-HHMMSS
php backup.php fetch wifidb-backup-YYYYMMDD-HHMMSS
```

Give the backups a key of their own that can reach only that bucket. With Garage:

```bash
garage bucket create wifidb-backups
garage key create wifidb-backups
garage bucket allow --read --write wifidb-backups --key wifidb-backups
```

Big files go up in 64 MB parts and come down in pieces, carrying on after an interruption.

## Moving to a new server

1. **Set up the new server** (the Proxmox template, say) with its own empty database: MariaDB or SQL Server, either one.
   First boot creates the tables and applies the migrations.
2. **Get a backup there**, any of these ways:
   - **Copy it with a transfer key.** On the old server's Backups page, **make a key** (shown once, good for 7 days).
     On the new server's Backups page, under *Copy a backup from another server*, give the old server's address and the
     key. Tick *have it make a new backup first* for a fresh one, and *restore it here* to restore as soon as it's
     copied. Or: `php backup.php import https://old.example.com --key wdbk_... --fresh --restore --yes`
   - **Fetch it from S3**, if the old server copies its backups to a bucket.
   - **Download** it from the old server and copy it into the new server's backups folder.
3. **Restore** it from the Backups page (type the site's domain to confirm), or
   `php backup.php restore wifidb-backup-YYYYMMDD-HHMMSS --yes`.
4. Check the site, then point DNS (or the proxy) at the new server, and revoke the transfer key on the old one.

### What restore does

1. Checks the whole backup against its SHA-256s, and that this database has every migration the backup's had (a
   backup from a newer WifiDB needs this one upgraded first). Nothing changes until both pass.
2. Pauses the scheduled imports and exports (`schedulecheck.php` reports nothing due while `backups/restoring` exists).
3. Empties the site's tables and loads the backup's rows, matching columns by name and keeping ids, then moves each id
   counter past the highest id.
4. Empties the sessions, so everyone signs in again, with an account from the backup.
5. Installs the backup's `two_factor_key` in the template's settings file, so authenticator apps keep working.
   Elsewhere the log says to set it in `config.inc.php`.

A row the target refuses (too long for its column, a duplicate under the other database's collation) doesn't stop the
restore: it's written to `NAME.rejects.jsonl` in the backups folder and counted in the log.

Between the two databases, a few columns aren't quite alike: some are `varchar` (not Unicode) on SQL Server where
MariaDB holds any character, and MariaDB's `tinyint(1)` is a `bit` (0 or 1) on SQL Server. Real WifiDB data fits both;
`proxmox/test-backup-crossdb.sh` fills every column of every table and checks a round trip.

Passkeys are tied to the site's address: they keep working only if the new server has the same one.

## Transfer keys

A transfer key lets another WifiDB server list this server's backups, copy them, and ask for a new one. That is all it
can do.

- Made on the Backups page (or `php backup.php key`), shown once, good for 7 days. A new key replaces the old one;
  **revoke** removes it.
- Only its hash is kept, in the backups folder, which backups never include.
- The other server sends it as `X-WifiDB-Transfer-Key` to `admin/backup_transfer.php`. Files move 16 MB at a time, and
  the whole file is checked against its SHA-256. An interrupted copy carries on where it stopped.
- For a test server with a self-signed certificate, tick *don't check its TLS certificate* (`--insecure`).

## From the command line

```bash
php backup.php export
php backup.php list [--remote]
php backup.php verify NAME
php backup.php restore NAME --yes
php backup.php delete NAME
php backup.php import URL --key KEY [--name NAME] [--fresh] [--restore --yes] [--insecure]
php backup.php upload NAME | fetch NAME
php backup.php key [--revoke]
```

On the template, run it as `www-data`: `runuser -u www-data -- php /opt/wifidb/tools/utilites/backup.php ...`.

## Tests

- `proxmox/test-backup-crossdb.sh TEMPLATE.tar.zst`: MariaDB to SQL Server and back, every column of every table.
- `proxmox/test-s3.sh`: the S3 client and the backup copies against a one-node Garage.
- The template boot test makes a backup from the page, downloads it (whole and by range), copies it with a transfer
  key, and restores it.
