# Changelog

## master
### ✨ Features and improvements
- _...Add new stuff here..._
- **QR codes (device linking) are drawn by a maintained library that ships with WifiDB** (qrcode-generator 2.0.4, as an inline image). The old code tried an unmaintained copy and then fell back to a public CDN; nothing is loaded from another site now, and the script no longer loads on every page.
- **Message editor updated** (SCEditor 2.1.3 → 3.2.3), and the style sheet for the classic theme's message editor now loads from the right place.
- **Usernames are matched exactly.** Message, statistics, export, API, sign-up and password-reset lookups compared usernames with `LIKE`, so a name containing `_` or `%` also matched other members: it could show someone else's mail count or statistics, wrongly refuse a sign-up, and a password reset could change the password of every member whose name fit the pattern. They now use `=`, which is still case-insensitive. Sign-up validation codes are also bound as parameters now.
- **In-app notifications.** A new Notifications page in the control panel (and an "Alerts" count in the header) tells members when their import starts, finishes or fails (with the reason), and tells administrators about new members and errors, so the site no longer depends on e-mail for this. Each kind can be switched off or on under Notifications → Settings; administrator error alerts are always shown. E-mail still goes to whoever chose it in their e-mail preferences, and sign-up validation and password reset stay e-mail. Run `tools/utilites/migrate.php` to create the two new tables (the header count stays at 0 until you do). A refused address in the e-mail list no longer stops everyone after it from getting the message.
- **Map libraries refreshed:** MapLibre GL JS 6.3.0 → 6.16.0 and maplibre-gl-inspect 1.8.2 → 1.9.0, maplibre-contour 0.1.0 → 0.1.1 and Chart.js 4.5.1 → 4.6.1, copied unchanged from npm. The separate `maplibre-gl-shared.mjs` file no longer exists in MapLibre and was removed.
- **PHPMailer 7.1.1**, installed with Composer and pinned to that exact version (was a 2018 copy, 6.0.6, kept in the repository; later releases fixed several security problems). Nothing changes in how mail is configured. A test builds the real mailer and checks, with an independent DKIM verifier, that sign-up and notification mail are both signed correctly.
- **Mail settings check and DKIM help.** `tools/utilites/check_mail.php` checks the mail settings and says in plain words what is wrong: settings still at their example values, a DKIM key file that is missing or unreadable (PHPMailer then sends the mail unsigned without any error), a DKIM record missing from DNS or for a different key, addresses outside the signing domain, missing SPF or DMARC; `--send` sends a signed test message. In the Proxmox template `wifidb-dkim generate` makes the key (kept on the data disk) and prints the DNS record to publish. `docs/email.md` documents every mail setting.
- **Interactive signal charts on an access point's page.** Each file's readings open with a Chart.js line chart (signal % and RSSI dBm, hover for values, a time range, and PNG/SVG links to save or share the same graph), drawn when that file is opened; one more chart at the top covers all the files listed. Both themes. The page already had the readings, so nothing extra is loaded.
- **Signal graphs as images, with no temporary files.** `api/wifi/signal.png` and `signal.svg` draw an access point's signal history straight from an address, so they work in an `<img>` tag, a forum post or a link. They take the same parameters Vistumbler's own links carry (SSID, Mac, Chn, Sig and the rest), plus `type=bar|line` and `text`, `bg` and `line` colors. The old form page now shows the picture through that address instead of saving a PNG in `tmp/` (a folder that had to be writable and was never cleaned up), so `tmp/` is no longer needed. Without PHP GD, the PNG address answers with the SVG.
- **PHP 8.2 to 8.5.** WiFiDB now runs on current PHP. PHP 7.x and 8.0/8.1 are out of security support, and the
  October 2026 PHP flaws (CVE-2026-109618 and CVE-2026-109619, remote code execution) are fixed only in 8.2.35, 8.3.36,
  8.4.27, 8.5.12 and later. The README lists the requirements and the upgrade steps.
- **A Proxmox LXC template** (Ubuntu 26.04): Apache, PHP-FPM with a separate pool for the heavy exports and tile
  endpoints, the monitor and websocket daemons as systemd services, and the cron jobs on wifidb.net's schedule. It uses
  your MariaDB or SQL Server and keeps uploads, generated files, logs and settings on a data disk, so an upgrade is
  a new container on the same disk. See docs/proxmox.md.
- **MariaDB in the cron jobs.** `import_files`, `export_kml` and `admind` asked SQL Server for the schedule with
  `sqlcmd` and a password edited into each script; they now ask through the daemons' own connection
  (`tools/daemon/schedulecheck.php`), so they work with either database and hold no credentials.
- **Tests and CI.** 172 PHPUnit tests (helpers, password and key generation, coordinate and signal conversions, and a
  compile of every Smarty template in both themes) and a lint of every PHP file, on PHP 8.2, 8.3, 8.4 and 8.5, for
  GitLab and GitHub.
- **Smarty 4.5.8** (was 3.1.39), installed with Composer and pinned to that exact version in composer.json instead of being copied into the repository. Upgrading it is a one-line change plus a test run (the tests compile every template). The Proxmox template runs `composer install --no-dev` when it is built.

### 🐞 Bug fixes
- _...Add new stuff here..._
- **Sign-up and password-reset mail was never DKIM-signed.** Only the main mailer had the DKIM settings; the sign-up mailer now has them too.
- **Sign-up and password-reset mail failed on a config built from the example file.** The mailer reads separate `signup_smtp_*` settings, but `config.inc.php.example` did not have them. They are now optional and fall back to the main `smtp_*` account, with a documented commented-out block in the example for using a separate account.
- **`rund.php ver` and `rund.php help` printed blanks and warnings.** They read version numbers from a variable that was never in scope; they use the core's version now.
- **Security: the signal graph page (api/wifi, opened from Vistumbler) wrote the access point's name and other values into the page unescaped,** so a crafted link ran script in the visitor's browser. Everything is escaped now, the colors and the signal list are validated before they reach GD (a bad color is an error on PHP 8), and the text and number of points are limited. Vistumbler's links work unchanged.
- **Security: a crafted login link could run script in the visitor's browser and send them to another site.** The `return` address on the login page went into an inline script unfiltered. Return addresses are now accepted only if they are paths on this site (or this site's own address), on the login page and on the confirm pages for deleting imports and the admin actions, and the page writes the address into its script as a safely encoded string.
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
