# Changelog

## master
### ✨ Features and improvements
- _...Add new stuff here..._
- **PHP 8.2 to 8.5.** WiFiDB now runs on current PHP. PHP 7.x and 8.0/8.1 are out of security support, and the
  October 2026 PHP flaws (CVE-2026-109618 and CVE-2026-109619, remote code execution) are fixed only in 8.2.35, 8.3.36,
  8.4.27, 8.5.12 and later. The README lists the requirements and the upgrade steps.
- **A Proxmox LXC template** (Ubuntu 26.04): Apache, PHP-FPM with a separate pool for the heavy exports and tile
  endpoints, the monitor and websocket daemons as systemd services, and the cron jobs on wifidb.net's schedule. It uses
  your MySQL/MariaDB or SQL Server and keeps uploads, generated files, logs and settings on a data disk, so an upgrade is
  a new container on the same disk. See docs/proxmox.md.
- **MySQL/MariaDB in the cron jobs.** `import_files`, `export_kml` and `admind` asked SQL Server for the schedule with
  `sqlcmd` and a password edited into each script; they now ask through the daemons' own connection
  (`tools/daemon/schedulecheck.php`), so they work with either database and hold no credentials.
- **Tests and CI.** 172 PHPUnit tests (helpers, password and key generation, coordinate and signal conversions, and a
  compile of every Smarty template in both themes) and a lint of every PHP file, on PHP 8.2, 8.3, 8.4 and 8.5, for
  GitLab and GitHub.
- **Smarty 4.5.8** (was 3.1.39).

### 🐞 Bug fixes
- _...Add new stuff here..._
- **Security: a crafted theme cookie could point the template and compile folders anywhere.** The theme name is now
  accepted only if it is a folder in `themes/`.
- **Security: passwords are stored with bcrypt at cost 12** (was cost 7), checked in constant time. Existing passwords
  keep working and are upgraded the next time their user logs in. Back up `user_info` before upgrading.
- **Security: the login and theme cookies are `HttpOnly` and `SameSite=Lax`.**
- **Security: SSIDs, usernames, titles, notes, labels, MACs and values taken from the address are escaped in both
  themes' templates**, without double-escaping what PHP already escaped.
- **Security: session IDs, API keys and salts come from `random_int()`** instead of `mt_rand()`.
- **Security: removed `tar_file`,** which built a shell command from its argument and was never called.
- **`blank_db.sql` could not be imported into an empty database:** `cell_id` had its primary key defined twice.
- **PHP 8 fatals and deprecations:** string offsets with `{}`, the removed `__autoload()`, `implode()` argument order,
  optional parameters before required ones, the backtick operator, `case x;`, and the old cast names.
- **`FILTER_SANITIZE_STRING`** (deprecated in PHP 8.1) is replaced by `wifidb_input_string()`, with the same result.
- **Removed what could not run:** 26 utilities (dropped tables, deleted libraries, stubs), the pthreads prototype
  (pthreads does not exist in PHP 8), dead `mysql_*` code, and two templates that did not compile. Four utilities
  that used the dropped `wifi_signals` table now use `wifi_hist`.
