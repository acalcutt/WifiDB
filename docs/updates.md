# Servers and updates

**Admin > Servers** (`admin/servers.php`, needs admin mode) lists every server of the site and updates them to a new
release, one at a time. It's built for the Proxmox containers ([proxmox.md](proxmox.md)); a copy of the site installed
another way shows up in the list but doesn't update itself.

## What the page shows

Each server reports every minute from cron (`tools/cron/server_status`): its WifiDB version, load, memory, the disks
WifiDB uses (`/` and `/srv/wifidb-data`) and its services (Apache, PHP-FPM, cron, the two daemons, the updater). The
page calls out a server that hasn't reported for five minutes, one running an older version than the newest release
(or the newest another server runs), a disk over 90% full, memory almost gone, a load over twice its CPUs, a service
that isn't running (one switched off for that server is fine), and an update that failed. A server gone for a week
drops off; **Forget** removes one now (it comes back if it reports again).

On a server running [pmtiles-swarm](pmtiles-swarm.md), the Services column shows it too: whether it answers on port 8090,
its release, whether it publishes the archives or mirrors them, and how many categories it has. The page calls out a swarm that's
stopped or not answering, and a mirroring server that still hasn't got the publishing server's newest build of a
category half an hour after it was made.

## Updating

Pick a release and **Update every server**. The page only writes the request (`server_update_requests`); on each
server, `wifidb-update` (root's, run each minute by `wifidb-update.timer`) does it:

1. It takes the update lock in the database, so the servers update **one at a time**; the others show "waiting its
   turn".
2. It downloads the release's **update package** from the release source set for that server (below) and checks it
   against the release's **SHA256SUMS**. A package that doesn't match is refused and nothing changes.
3. If you ticked **back up the site first**, the first server to update makes a backup (`tools/utilites/backup.php
   export`, as on the Backups page). On a big database that takes a long time and a lot of disk: leave it off if the
   database is backed up another way.
4. It stops cron and the two daemons and waits for running jobs (an import, a tile build) for up to 30 minutes, then
   stops what's left (a stopped job's schedule row is taken back on its next run). It saves the system files it's
   about to replace, moves the code aside (`/var/www/wifidb.old`, `/opt/wifidb/tools.old`, `/opt/wifidb/sql.old`,
   `/opt/wifidb/swarm.old`), installs the package (`install-files.sh`, the same script the template build uses) and
   runs `wifidb-firstboot` again, which keeps the settings on the data disk, applies the new database migrations,
   installs the release's pmtiles-swarm if the server has another ([pmtiles-swarm.md](pmtiles-swarm.md)) and starts
   everything.
5. It checks the site answers on the new version (`/health/ready`, and the home page without a PHP error). If it
   doesn't, or anything in step 4 failed, it puts the old code and system files back and runs the first boot again:
   the page says "failed, old version back" and why.

The data disk (`/srv/wifidb-data`: uploads, generated files, logs, settings) isn't touched. The first server to
update applies the release's migrations; the servers still on the older version keep working meanwhile, since a
migration only adds. The `.old` folders stay until the next update, for putting a version back by hand.

**Call it off** stops servers that haven't started on the request; one already installing finishes.

On a server, `wifidb-update --status` shows the request and that server's state, `wifidb-update --releases` reads
the release list now (the page's list is refreshed hourly), and `/var/log/wifidb-update.log` has the whole story.

## Where releases come from

Each server's updater reads only the release source in its own `/etc/wifidb/updates.conf` (root's, mode 600, written
by the first boot from `firstboot.env`), so the page can ask for a version but not point a server anywhere else:

```bash
WIFIDB_UPDATES=1                                     # 0: no updates from the page (the timer is off)
WIFIDB_UPDATE_SOURCE=gitlab                          # or github
WIFIDB_UPDATE_GITLAB_URL=https://gitlab.techidiots.net
WIFIDB_UPDATE_GITLAB_PROJECT=techidiots-llc/WifiDB   # path or numeric ID
WIFIDB_UPDATE_GITHUB_REPO=owner/name                 # for SOURCE=github
WIFIDB_UPDATE_TOKEN=…                                # a private project: GitLab read_api, GitHub contents read
```

GitLab is the default. The token is sent only to that host: a download handed off to another host (GitLab's object
storage, GitHub's) goes without it. After changing these, run `/usr/local/sbin/wifidb-firstboot` (or let the next
update's first boot pick them up). `systemctl restart wifidb-firstboot` does nothing once a first boot has finished.

## The release files

Both pipelines (GitLab's `.gitlab-ci.yml`, GitHub's `release.yml`) publish, for each release:

| File | What |
|---|---|
| `WifiDB-v<version>-proxmox-amd64.tar.zst` | the Proxmox LXC template, for a new container |
| `WifiDB-v<version>-update.tar.gz` | the update package, cut from that template (`proxmox/make-update-package.sh`) |
| `SHA256SUMS` | every file's SHA-256 |

The update package holds `wifidb-update-<version>/` with `VERSION`, `web/` (the site, with Composer's `vendor/`),
`tools/`, `sql/` (`blank_db.*` and `migrations/`), `swarm/` (the pmtiles-swarm release it goes with: npm's
`package.json` and `package-lock.json`, not the package itself, which the first boot fetches from npm) and `proxmox/` (`install-files.sh` and the system files it installs:
Apache and PHP-FPM settings, the cron file, the services, the first boot and the updater). Releases from before
update packages don't show in the list: those servers are updated by a new container from the template, as before.

Tests: `proxmox/test-update.sh TEMPLATE` serves packages made from a template from a stand-in for GitLab's API and
checks the refusal, the rollback and an update with a backup.
