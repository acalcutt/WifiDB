# pmtiles-swarm on every node

The map's tile archives (`out/pmtiles/<bucket>.pmtiles`, one per bucket) can be shared between the nodes, and with
visitors' browsers, over BitTorrent by [pmtiles-swarm](https://github.com/TechIdiots-LLC/pmtiles-swarm). The Proxmox
template includes it (the npm release pinned in `proxmox/install-wifidb.sh`), off until `firstboot.env` turns it on.
With it on, every node is a swarm node and serves the map's tiles from its own copy of the archives on port 8090.

- **The node that builds the archives** (`tile_archive_generate`) watches its archive folder and publishes each new
  build under the category `wifidb-<bucket>` (`tile_swarm_category_prefix` and the bucket, `_` made `-`), and the MLT
  archives in `out/pmtiles-mlt` under `wifidb-mlt-<bucket>` when the site builds them (`tile_mlt_archive_generate`).
  1 MiB pieces, since most archives are small; it keeps 4 builds of the hourly buckets, 3 of the weekly ones and
  `tile_archive_keep` (at least) of the rest. It also signs each category's current build into the DHT (BEP 46) with a
  key of its own.
- **Every other node follows it:** one feed per category, in mirror mode, downloading each build into its own archive
  folder under the name the building node gave it, then pointing `<bucket>.pmtiles` at it (a hard link). So its site
  (`api/mvt.php`) and its swarm serve the same bytes, and a web seed request for a build works on whichever node the
  proxy sends it to. The builds a follower keeps are `tile_archive_keep`.

Only the building node records each build for the map page (`tools/cron/update_swarm_index`, every 15 minutes, asking
its own swarm on `127.0.0.1:8090`); a follower's run says so and leaves the record alone.

## Turning it on

In `/etc/wifidb/firstboot.env`, before the first start, or later followed by `/usr/local/sbin/wifidb-firstboot` (the
first boot again; `systemctl restart wifidb-firstboot` only runs it until it has once succeeded):

```bash
WIFIDB_SWARM=1
# on every node but the one that builds the archives: that node's address, port 8090
WIFIDB_SWARM_FOLLOW=http://<building node>:8090
# the BitTorrent port (TCP and UDP) and WebTorrent's, the next one up; default 6881. Give each node behind the same
# public address its own.
WIFIDB_SWARM_PORT=6881
```

And in `/srv/wifidb-data/config/web.overrides.php`, on every node:

```php
  'tile_archive_generate' => '<building node>',   // its hostname: the others mustn't build archives of their own
  'tile_archive_url' => 'https://wifidb.example.com/out/pmtiles',
  'tile_swarm_url' => 'https://data.wifidb.example.com',
  'tile_swarm_browser' => 'auto',                 // visitors' browsers share tiles too (off, manual, auto or on)
```

`tile_archive_url` is the web seed every torrent carries, which is what makes a new build usable before anyone else has
a copy (without it: the site's address and `/out/pmtiles`). `tile_swarm_url` is where browsers get the archives'
TileJSON and tiles. The first boot warns when a following node would build archives too, or when the building node
doesn't build any.

## The proxy (HAProxy on OPNsense)

pmtiles-swarm's own [haproxy.md](https://github.com/TechIdiots-LLC/pmtiles-swarm/blob/main/docs/haproxy.md) has the
details. In short:

- **A backend for the swarm's name** (`data.wifidb.example.com`, with its certificate): every node's address, port
  **8090**, round robin, health check `GET /health` (it answers 503 when the engine is down, so don't check `/`). The
  node trusts `X-Forwarded-Proto` and `Host` from the addresses in `WIFIDB_EXTERNAL_PROXY`, so the URLs it hands out are
  the public ones: send `X-Forwarded-Proto: https`.
- **Timeouts of an hour** (`timeout client 1h`, `timeout server 1h`): a web seed is a long range request.
- **No compression** of the swarm's responses: a compressed response loses the ETag a reader needs.
- **Port forwards** from the internet straight to each node, not through HAProxy: 6881 and 6882 (or the node's
  `WIFIDB_SWARM_PORT` and the next one), TCP **and** UDP. A node without them still seeds to peers it connects to,
  but nobody can connect to it.
- **Never port 8091**, the swarm's console, which only listens inside the container anyway.

## Where things are

| | |
| --- | --- |
| `/srv/wifidb-data/swarm/swarm.config.json` | its settings (below) |
| `/srv/wifidb-data/swarm/data/` | its catalog, resume data and DHT state |
| `/srv/wifidb-data/swarm/publisher.pem` | the building node's BEP 46 signing key |
| `/srv/wifidb-data/out/pmtiles/` | the archives, shared with the site through the `pmtiles-swarm` group |
| `/var/lib/pmtiles-swarm/` | the package, in its own account's home |

It runs as its own account, `pmtiles-swarm`, which can't read the site's settings (or its database password).
`journalctl -u pmtiles-swarm` has its log. Its console is on port 8091 inside the container: `ssh -L
8091:127.0.0.1:8091 root@<container>`, then `http://127.0.0.1:8091/`, user `admin`, with the password in
`/root/pmtiles-swarm-first-login.txt` (written at the first boot that turned it on: keep it somewhere safer, then
delete the file). The API key is `auth.apiKey` in the settings.

With the shared files bucket (Site Settings), `tools/cron/out_sync` leaves `out/pmtiles` and `out/pmtiles-mlt` to the
swarm on these nodes (`out_sync_skip`), and goes on syncing the GeoJSON and KML exports.

**Its settings.** Every first boot writes the parts that make it this site's node: the watch entries for the archive
folders (or the subscriptions into them), the ports, `trustProxy` (loopback and `WIFIDB_EXTERNAL_PROXY`),
`publishingUrl` (`tile_swarm_url`), the BEP 46 signing, and the console on 127.0.0.1. The first one also starts it as
wifidb.net's runs: no UPnP or NAT-PMP (forward the ports by hand), DHT and local peer discovery on, an MD5 of each
archive, the feed titled "WifiDB tile archives", and a follower checking the feeds every 2 minutes. Everything else,
and anything changed on the console, is kept.

## Moving from a swarm that already runs

The categories, piece size and kept builds are the ones wifidb.net's swarm uses, so magnets and TileJSON URLs stay
the same. Its own watch entries and `link_archive` hook aren't needed in the container: a follower's subscriptions
point `<bucket>.pmtiles` at each download themselves, and the building node records each build every 15 minutes.

- **The ports:** to keep the forwards you have, set `WIFIDB_SWARM_PORT` to the old `libtorrent.listen` port
  (36881 on wifidb.net) on the node that takes them over.
- **The signing key:** to keep the BEP 46 key the site has already handed out (`tile_swarm_public_key`, or the one in
  the swarm's TileJSON), copy the old publisher's `keyPath` file (`/etc/pmtiles-swarm/publisher.pem`) to
  `/srv/wifidb-data/swarm/publisher.pem` on the building node before its first boot with the swarm on. Otherwise it
  makes a new one, and `tools/cron/update_swarm_index` picks the new public key up by itself. **Stop the old
  publisher first:** two nodes publishing under one key fight over every record.

## Upgrading pmtiles-swarm

A new template brings the release it was built with (`WIFIDB_SWARM_PACKAGE` in `proxmox/build-template.sh` builds one
with another). In place, as root in the container:

```bash
env npm_config_cache=/tmp/npm-cache npm install --prefix /var/lib/pmtiles-swarm pmtiles-swarm@<version>
chown -R pmtiles-swarm:pmtiles-swarm /var/lib/pmtiles-swarm
rm -rf /tmp/npm-cache
/usr/local/sbin/wifidb-firstboot       # writes its unit from the new release and restarts it
```

Read that release's changelog first, and keep every node on the same release.
