# WiFiDB on Proxmox

The Proxmox template is an Ubuntu LXC container with the whole site installed: Apache and PHP-FPM (with a separate
pool for the heavy endpoints), the monitor and websocket daemons as systemd services, and the cron jobs. It has no
database of its own: it uses your MariaDB, SQL Server or PostgreSQL. It serves plain HTTP on port 80, for HAProxy (or any
proxy) in front to do TLS.

`<version>` below is the build, `210` is the container ID, and the addresses are examples.

## What lives where

| Where | What | On an upgrade |
|---|---|---|
| the container's disk | code (`/var/www/wifidb`, `/opt/wifidb/tools`), Apache, PHP, services, the default cron times | replaced by the new template |
| `/srv/wifidb-data` (a second disk) | uploads (`import/`), generated files (`out/`: KML, CSV, tiles, PMTiles), logs, and your settings (`config/`) | kept: attach it to the new container |
| your database server | everything else | untouched |

`/srv/wifidb-data` works as a plain folder on the container's disk too, but then an upgrade means copying it. Give it
its own disk.

## What you need

- A database that exists, with a user that can create tables (the first boot loads the schema into an empty
  database), reachable from the container.
  - **MariaDB 10.6 or later:** utf8mb4. Create the database and user, e.g.
    `CREATE DATABASE wifi CHARACTER SET utf8mb4; CREATE USER 'wifidb'@'%' IDENTIFIED BY '…'; GRANT ALL ON wifi.* TO 'wifidb'@'%';`
  - **SQL Server:** SQL Server 2019 or later. Only if the template has the SQL Server driver (see below).
  - **PostgreSQL 11 or later:** a database the user owns (it doesn't need to be a superuser), e.g.
    `CREATE ROLE wifidb LOGIN PASSWORD '…'; CREATE DATABASE wifi OWNER wifidb ENCODING 'UTF8';`
- A name for this node. The database has a row per node (`settings.node_name`, `schedule.nodename`); the daemons and
  cron jobs only work on their own node's rows. Several nodes can share one database: see
  [Several nodes on one database](#several-nodes-on-one-database).

## Create the container

Put the template in the node's template storage (*local → CT Templates → Upload*, or `wget` it into
`/var/lib/vz/template/cache`), then:

```bash
pct create 210 local:vztmpl/WifiDB-<version>-proxmox-amd64.tar.zst \
    --hostname wifidb --ostype ubuntu --unprivileged 1 --features nesting=1 \
    --cores 4 --memory 4096 --swap 512 \
    --rootfs local-lvm:16 \
    --mp0 local-lvm:200,mp=/srv/wifidb-data \
    --net0 name=eth0,bridge=vmbr0,ip=192.168.1.50/24,gw=192.168.1.1 \
    --onboot 1
```

`--mp0` is the data disk: size it for the uploads and the generated tiles. (To use a folder on the node instead,
`--mp0 /tank/wifidb,mp=/srv/wifidb-data`; an unprivileged container's `www-data` is user 100033 on the node, so
`chown -R 100033:100033 /tank/wifidb` first.)

Before starting it, fill in the settings:

```bash
pct start 210   # the first boot stops and waits for the settings: that's expected
pct exec 210 -- nano /etc/wifidb/firstboot.env
pct exec 210 -- systemctl restart wifidb-firstboot
pct exec 210 -- journalctl -u wifidb-firstboot -f
```

The settings (each is described in the file):

```bash
WIFIDB_DB_TYPE=mariadb            # or sqlsrv, or pgsql
WIFIDB_DB_HOST=db.example.lan
WIFIDB_DB_NAME=wifi
WIFIDB_DB_USER=wifidb
WIFIDB_DB_PASS=…
WIFIDB_URL=https://wifidb.example.com
WIFIDB_NODE=wifidb                # this node's name in the database; default: the hostname
WIFIDB_EXTERNAL_PROXY=192.168.1.1 # the proxy's address(es), for real visitor IPs
#WIFIDB_SQLSRV_TRUST_CERT=0       # SQL Server: check its certificate (default 1: accept a self-signed one)
#WIFIDB_TWO_FACTOR_KEY=…          # another node on the same database: the first node's two_factor_key
```

The first boot writes the settings that differ from the defaults to `/srv/wifidb-data/config/*.overrides.php`, loads
the schema if the database is empty, names the node, then starts Apache, PHP-FPM, cron and the two daemons. The
config files the code reads are rebuilt from the release's defaults each time a container is made, with a line at the
end that loads your overrides, so a new release's new settings arrive by themselves and yours stay.

An existing database (say, the one your current server uses) is left as it is. If it has no row for this node, the
first boot adds the node: a `settings` row and `schedule` rows copied from the first node's, with imports and admin
jobs on and the rest (KML export, stats) off, since one node does those. To take over an existing node's rows
instead, set `WIFIDB_NODE` to its name.

## Behind HAProxy on OPNsense

With `WIFIDB_EXTERNAL_PROXY` set, the container trusts `X-Forwarded-For` from those addresses only, so the logs and the
app see the visitor's address. For a CARP pair, list both firewalls and the shared address, separated by spaces
(HAProxy connects from the active node's own address).

In HAProxy:

- the frontend has the certificate and sends `X-Forwarded-Proto: https`;
- the backend is the container's address, port 80, with *forwardfor* on;
- health check: `GET /health` (answers `ok`);
- the live pages use websockets at `/ws/…`: give the backend a long tunnel timeout (an hour or so).

The tile folders send their own headers (CORS, gzip, `Accept-Ranges` for PMTiles); let HAProxy pass them through
unchanged and don't compress `.pbf`, `.mlt` or `.pmtiles` responses there.

## What runs

- **Services** (systemd): `wifidb-monitord` (watches the other daemons) and `wifidb-websocketd` (live pages). Both
  restart themselves.
- **Cron** (`/etc/cron.d/wifidb`): the imports, exports, statistics, admin jobs, the manufacturer and swarm updates,
  and the MVT and MLT tile builds, at the times wifidb.net uses. To keep different times across upgrades, copy the
  file to `/srv/wifidb-data/cron.d/wifidb` and edit that; the first boot installs it over the shipped one.
- **PHP-FPM pools:** `www` for the pages (30 second limit) and `wdb-slow` for exports, geojson/gpx/kml, tiles, the map
  and user pages and search results (5 minutes). Which URLs go where is in `/etc/apache2/wifidb-slow-pool.conf`.
- **Logs:** `/srv/wifidb-data/log/<job>/` for the jobs, `journalctl -u wifidb-monitord` for the services, and
  `/var/log/apache2/wifidb-*.log`.

The import cron wrappers ask the database whether a run is due through `tools/daemon/schedulecheck.php`, so they work the
same on MySQL and SQL Server and hold no credentials.

## Several nodes on one database

Two or more containers can share one database behind HAProxy, each with its own data disk and its own `WIFIDB_NODE`.
Give every node after the first the first node's `two_factor_key` (from its `/srv/wifidb-data/config/web.overrides.php`)
as `WIFIDB_TWO_FACTOR_KEY`: it encrypts the members' authenticator secrets and the secrets in Site Settings, which
every node reads. The first boot adds a new node's `settings` and `schedule` rows.

- **Imports:** the queue is in the database, and each node's importer takes the next file in one step, so every upload
  is imported once. Set up **Shared files in S3** in Site Settings (`files_s3_*`; a bucket of its own, not the
  backups'). The bucket is then where uploads are kept: each one is put there (`PREFIX/uploads/`) before it's queued,
  and the node it came in on lets its own copy go; whichever node imports it fetches it and removes that working copy
  afterwards; deleting an import moves it to `PREFIX/deleted/` (or `user_deleted/`) in the bucket, with the same note
  beside it as `import/up/deleted/` has. Without it, an upload can only be imported by the node it came in on.
- **Admin jobs** (delete or reset an import, and so on) are taken by one node each.
- **Work one node does for all** (the tile and GeoJSON builds, the manufacturer list, the nightly backup) runs under a
  lock held in the database (`tools/daemon/lock_run.php`): whichever node starts it first does it, and the others skip
  that run. If that node stops, its lock goes with its database connection.
- **Scheduled daemons** have rows per node in `schedule`: the KML export and stats daemons belong on one node only
  (a node the first boot adds has them off).
- The generated files (tiles, GeoJSON, exports) are still on the disk of the node that built them.

## SQL Server

SQL Server needs Microsoft's ODBC driver 18 and the `pdo_sqlsrv` PHP extension. The build installs them when it can
and says in its log if it couldn't (`WARNING: the SQL Server driver could not be installed`); a template built that
way supports MariaDB only, and the first boot stops with a message if you ask it for `sqlsrv`. Check with
`pct exec 210 -- php -m | grep sqlsrv`.

## Upgrading

1. Download the new template and create a new container from it, with the same `--mp0` data disk (stop the old
   container first: a volume can't be attached to two).
2. Put the same `firstboot.env` in (or leave it: the settings are already on the data disk, and the first boot keeps
   them).
3. Start it. The first boot finds the settings and the database, and skips the schema load.
4. Point HAProxy's backend at the new container. Keep the old one until you're sure; the data disk is the only thing
   it shares, so start with a backup of the database.

## Two-factor sign-in

First boot makes `two_factor_key` (in `web.overrides.php` on the data disk) and keeps it; do not delete it, or saved
authenticators stop working. It also applies the database migrations on every boot. See `two-factor.md`.

## Notes

- **Time zone:** UTC, like the rest of WiFiDB.
- **Mail:** none is installed. The accounts' e-mail needs the SMTP settings in `web.overrides.php` (see
  `config.inc.php.example` for the `smtp_*` names).
- **Several web servers:** this template keeps uploads and generated files on a local disk, so two containers would not
  see each other's. Sharing them (a shared filesystem, or object storage for the generated files) is a larger change;
  see the project notes.
