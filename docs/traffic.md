# Traffic

**Traffic** in the menu (`admin/traffic.php`, needs admin mode) shows the site's own counts, as tippr's traffic pages do,
in place of AWStats:

- page views and unique visitors by hour (48 hours), day (60 days) and month (24 months), as charts and tables;
- each day of the week's average over the last 60 days;
- this month's countries (by the visitor's address, `lib/visitor_country.inc.php`) and pages;
- what people did over the last day, week and month: sign-ups, sign-ins and failed sign-ins, uploads from the site and
  from the API (Vistumbler), and imports finished and set aside.

Times are UTC.

## How it's counted

- A **page view** is a GET of one of the site's pages, from a browser: not the API, map tiles, downloads or exports, and
  not a bot (by its User-Agent) or a prefetch.
- Each node writes its page views and events to a file of its own (`/srv/wifidb-data/log/traffic/pending.log` on the
  Proxmox template), which costs a page an append and no database work. `tools/cron/traffic`, each minute on every node,
  adds that file to the counts in the database (`traffic_counts`), so with several nodes on one database the page shows
  the whole site. If the database can't be reached, the file waits for the next run.
- A **unique visitor** is counted the first time they're seen in an hour, day or month, on whichever node. They're
  known by a salted hash of their username, or, signed out, of their address and browser (`traffic_visitors`), so no
  address is kept, and the hashes are deleted once their period is over.
- Hourly counts are kept for 35 days; daily and monthly ones for good. The counts go into backups; the visitor hashes
  don't.

Run `tools/utilites/migrate.php` after updating to create the tables (`0010_traffic`). On a server that isn't the
Proxmox template, add the cron job:

```
* * * * * www-data /opt/wifidb/tools/cron/traffic
```
