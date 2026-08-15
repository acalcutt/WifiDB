<?php
/*
swarm_index.php — keep a local record of which archive build is current
Copyright (C) 2026 Andrew Calcutt

The map page puts a magnet in the fragment of every archived bucket's source
URL, so that one URL is self-sufficient: an ordinary client fetches the
TileJSON over HTTP and ignores the fragment, while a swarm-aware one has
something to join before it makes any call at all — and can therefore still
start when the swarm's HTTP endpoint cannot answer.

That only works if the fragment names something joinable.  The BEP 46 public
key alone is not: resolving it needs a DHT, and a browser has none, so a page
given only the key would have to fetch the very document the fragment is
attached to before it could join anything.  The magnet therefore carries the
current infohash as well, and this is what finds out what that is.

Deliberately not done during a page render.  The infohash is a fallback for a
client that cannot reach the TileJSON, and buying it with a request to the
swarm on every render would be paying for the failure case in the success case
— and would hand the swarm the ability to slow down or break the map by being
slow or broken itself.  So it is cached, and this fills the cache.

A failure never clears anything.  The last known build stays in the table and
keeps being published, which is the entire point: when the swarm's API is not
there, the answer it last gave is still the best one available.

Two ways in, and they complement each other:

  --record --bucket <name> --infohash <hex>
      Record one build directly, with no HTTP request.  Meant for
      pmtiles-swarm's onComplete hook, which already runs on this host and
      already knows both values, so this costs nothing and has no latency.
      See tools/cron/link_archive, which calls it.

      Note onComplete does not mean "a download finished": it fires for any
      archive the node holds whole and has not yet stamped, so a build imported
      from a watched folder — seeded at 100% from the moment it exists — fires
      it too.  This path therefore covers locally generated archives as well as
      mirrored ones.

  --check
      Print what this process resolved and where from, and exit. The first
      thing to run when a setting in config.inc.php appears to be ignored.

  --refresh [--url http://127.0.0.1:8090]
      Read the swarm's /api/categories and record every bucket it knows.
      Run from cron — see tools/cron/update_swarm_index.

      Give --url the node's own address. tile_swarm_url is where a browser
      should go, which in a pair behind a load balancer resolves to either
      node — so refreshing from it can record the neighbour's build as this
      node's. It also makes a local cron depend on public DNS and TLS to
      reach a service on its own loopback.

      The reconciliation, not the primary path.  It fills the table on a node
      that has never run the hook, repairs anything the hook could not write
      because the database was down at that moment, and notices a build that
      was retired or replaced by something other than a completion.  It also
      fills in the magnet and style URL, which the hook is never told.

The table is `swarm_archives`, in blank_db.sql and blank_db.sqlsrv.  An existing
install needs it adding by hand — there is no migration runner here — but not
urgently: every read is wrapped, so until the table exists the magnet simply
falls back to the public key alone, which is what it carried before any of this.

  MySQL:
    CREATE TABLE `swarm_archives` (
      `bucket` varchar(64) NOT NULL,
      `category` varchar(128) NOT NULL,
      `infohash` char(40) NOT NULL,
      `magnet` text DEFAULT NULL,
      `style_url` varchar(1024) DEFAULT NULL,
      `archive_name` varchar(255) DEFAULT NULL,
      `archive_size` bigint(20) UNSIGNED DEFAULT NULL,
      `built_at` timestamp NULL DEFAULT NULL,
      `public_key` char(64) DEFAULT NULL,
      `salt` varchar(128) DEFAULT NULL,
      `mutable_magnet` text DEFAULT NULL,
      `updated_at` timestamp NOT NULL DEFAULT current_timestamp(),
      `checked_at` timestamp NOT NULL DEFAULT current_timestamp(),
      PRIMARY KEY (`bucket`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

  SQL Server: see the [dbo].[swarm_archives] block in blank_db.sqlsrv.

  Adding the three key columns to a table created before them — note the two
  engines spell this differently, and SQL Server takes one ADD with a list:

    MySQL:
      ALTER TABLE swarm_archives
        ADD public_key char(64) DEFAULT NULL,
        ADD salt varchar(128) DEFAULT NULL,
        ADD mutable_magnet text DEFAULT NULL;

    SQL Server:
      ALTER TABLE swarm_archives
        ADD public_key char(64) NULL,
            salt nvarchar(128) NULL,
            mutable_magnet nvarchar(max) NULL;

Nothing here uses ON DUPLICATE KEY UPDATE or MERGE, because this branch runs on
both engines and they spell an upsert differently; mvt_swarm_record_archive()
reads and then updates or inserts, which either one takes.

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

if (php_sapi_name() !== 'cli') {
    http_response_code(404);
    exit(1);
}

// init.inc.php branches on these and refuses to start without them — it is how
// it decides between a web request and a command line, and which config to
// read. Both must be defined before it is required, as in mvtd.php.
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if (!(require(dirname(__FILE__).'/../daemon.config.inc.php'))) {
    fwrite(STDERR, "cannot read daemon.config.inc.php\n");
    exit(1);
}
if ($daemon_config['wifidb_install'] === '') {
    fwrite(STDERR, "daemon.config.inc.php has no wifidb_install path\n");
    exit(1);
}

require $daemon_config['wifidb_install'].'/lib/init.inc.php';
require $daemon_config['wifidb_install'].'/lib/mvt.inc.php';

/**
 * Writes a line to stderr, so stdout stays clean for anything parsing it.
 */
function swarm_log(string $line): void {
    fwrite(STDERR, '[swarm_index] '.$line."\n");
}

/**
 * Reads a --name value from the argument list.
 */
function swarm_arg(array $argv, string $name): ?string {
    $flag = '--'.$name;
    foreach ($argv as $i => $value) {
        if ($value === $flag) {
            return $argv[$i + 1] ?? null;
        }
        if (strpos($value, $flag.'=') === 0) {
            return substr($value, strlen($flag) + 1);
        }
    }
    return null;
}

/**
 * The bucket a swarm category belongs to, or null when it is not ours.
 *
 * The inverse of mvt_swarm_category(): the prefix keeps our categories from
 * colliding with other tilesets on a shared node, and underscores became
 * hyphens on the way out.  A node may well carry openmaptiles or protomaps
 * builds that have no bucket at all, and those are not ours to record.
 */
function swarm_bucket_for(string $category, $dbcore): ?string {
    $prefix = isset($dbcore->tile_swarm_category_prefix) && $dbcore->tile_swarm_category_prefix !== ''
        ? $dbcore->tile_swarm_category_prefix
        : 'wifidb-';

    if (strpos($category, $prefix) !== 0) {
        return null;
    }
    $bucket = str_replace('-', '_', substr($category, strlen($prefix)));

    // Bucket names are the keys of a table and end up in paths elsewhere, so
    // anything that is not one is refused rather than sanitised.
    if (!preg_match('/^[a-z0-9_]+$/', $bucket)) {
        return null;
    }
    return in_array($bucket, mvt_buckets(), true) ? $bucket : null;
}

/**
 * Why a category did not map to a bucket, for the log.
 *
 * Only ever called on the failing path, so it can afford to be wordy: the
 * useful version of this message names the prefix that was expected, since a
 * mismatch there is the likeliest cause and the hardest to see.
 */
function swarm_why_not(string $category, $dbcore): string {
    $prefix = isset($dbcore->tile_swarm_category_prefix) && $dbcore->tile_swarm_category_prefix !== ''
        ? $dbcore->tile_swarm_category_prefix
        : 'wifidb-';

    if (strpos($category, $prefix) !== 0) {
        return "not one of ours (expected the prefix '$prefix')";
    }

    $bucket = str_replace('-', '_', substr($category, strlen($prefix)));
    if (!preg_match('/^[a-z0-9_]+$/', $bucket)) {
        return "'$bucket' is not a plain bucket name";
    }
    return "'$bucket' is not one of this install's buckets";
}

/**
 * Fetches JSON from the swarm, or null.
 *
 * Short timeouts on purpose: this runs on a schedule, so being slow is worse
 * than being late — a refresher blocked for two minutes on an unreachable host
 * is a refresher that overlaps its own next run.
 */
function swarm_fetch(string $url): ?array {
    $context = stream_context_create([
        'http' => [
            'timeout' => 15,
            'ignore_errors' => true,
            'header' => "Accept: application/json\r\n",
        ],
    ]);

    $body = @file_get_contents($url, false, $context);
    if ($body === false) {
        swarm_log("could not reach $url");
        return null;
    }

    $decoded = json_decode($body, true);
    if (!is_array($decoded)) {
        swarm_log("$url did not answer with JSON");
        return null;
    }
    return $decoded;
}

/**
 * Records every bucket the swarm knows about.
 */
function swarm_refresh($dbcore, ?string $override = null): int {
    // Prefer an explicitly given URL over the configured one, and it is worth
    // using: tile_swarm_url is the address a *browser* should go to, which in
    // a pair behind a load balancer is a name that resolves to either node.
    // A node refreshing its own cache from that name may be answered by its
    // neighbour, and would then record the wrong build for itself. It also
    // makes a local cron depend on external DNS and TLS to reach a service
    // listening on its own loopback.
    //
    //   --url http://127.0.0.1:8090
    $base = $override !== null && $override !== ''
        ? $override
        : (isset($dbcore->tile_swarm_url) ? (string)$dbcore->tile_swarm_url : '');

    if ($base === '') {
        swarm_log('no swarm URL: set tile_swarm_url, or pass --url');
        return 0;
    }

    $base = rtrim($base, '/');

    // Every bucket this install knows about, asked for by name.
    //
    // Not /api/categories, which lists what exists but needs a credential:
    // everything under /api/ does, except login and session. We do not need it
    // anyway -- mvt_swarm_category() already gives the name each bucket is
    // published under, and /latest/<category>/tiles.json is public. So this
    // asks directly: nothing to authenticate, nothing to enumerate, and one
    // request per bucket instead of one plus one per bucket.
    //
    // The RSS feed is public too and carries the infohash and magnet, but not
    // the BEP 46 public key, which only the TileJSON publishes. Reading one
    // document rather than two is the reason this uses tiles.json.
    $recorded = 0;
    $missing  = 0;
    $failed   = 0;

    foreach (mvt_buckets() as $bucket) {
        $category = mvt_swarm_category($dbcore, $bucket);
        if ($category === null) {
            continue;
        }

        $tilejson = $base.'/latest/'.rawurlencode($category).'/tiles.json';
        $document = swarm_fetch($tilejson, true);
        if ($document === null) {
            // Nothing published for this category yet, or the swarm did not
            // answer. Either way the cached row stays exactly as it is.
            $missing++;
            continue;
        }

        $torrent = isset($document['torrent']) && is_array($document['torrent'])
            ? $document['torrent']
            : array();
        $infohash = isset($torrent['infohash'])
            ? strtolower(trim((string)$torrent['infohash']))
            : '';
        if (!preg_match('/^[0-9a-f]{40}$/', $infohash)) {
            swarm_log("$bucket: $category published no usable infohash");
            $missing++;
            continue;
        }

        // The public key comes from here rather than from configuration
        // because it is the swarm's to know: an operator copying 64 hex
        // characters into a config file is a step that can be skipped,
        // mistyped or left behind when the key is rotated, and every one of
        // those produces a magnet that looks valid and resolves to nothing.
        // Nothing secret is read -- the public half is meant to be given out,
        // and is already in every TileJSON this node serves.
        $magnet         = isset($torrent['magnet']) ? (string)$torrent['magnet'] : null;
        $mutable_magnet = null;
        $public_key     = null;
        $salt           = null;

        if (isset($torrent['mutable']) && is_array($torrent['mutable'])) {
            $mutable = $torrent['mutable'];
            // Stored as published rather than reassembled: the format is the
            // swarm's to define and has already changed once.
            if (isset($mutable['magnet'])) {
                $mutable_magnet = (string)$mutable['magnet'];
            }
            if (isset($mutable['publicKey'])) {
                $public_key = strtolower(trim((string)$mutable['publicKey']));
                if (!preg_match('/^[0-9a-f]{64}$/', $public_key)) {
                    swarm_log("$bucket: ignoring a public key that is not 64 hex characters");
                    $public_key = null;
                    $mutable_magnet = null;
                }
            }
            if (isset($mutable['salt'])) {
                $salt = (string)$mutable['salt'];
            }
        }

        $ok = mvt_swarm_record_archive($dbcore, $bucket, array(
            'category'       => $category,
            'infohash'       => $infohash,
            'magnet'         => $magnet,
            'mutable_magnet' => $mutable_magnet,
            'public_key'     => $public_key,
            'salt'           => $salt,
            'style_url'      => $tilejson,
            'name'           => isset($torrent['name']) ? (string)$torrent['name'] : null,
            'size'           => isset($torrent['size']) ? (int)$torrent['size'] : null,
        ));

        if ($ok) {
            $recorded++;
            swarm_log("$bucket -> ".substr($infohash, 0, 12));
        } else {
            $failed++;
            swarm_log("$bucket: could not be recorded");
        }
    }

    swarm_log("recorded $recorded, $missing not published yet, $failed failed");
    return 0;
}

$argv = $_SERVER['argv'] ?? [];

if (in_array('--check', $argv, true)) {
    // What this process actually resolved, and where from. Every reader of
    // these is written `isset($dbcore->x) ? ... : default`, so an unmapped or
    // unedited setting is indistinguishable from a deliberate one at the point
    // it is used -- which is exactly the confusion this prints its way out of.
    $config_file = $daemon_config['wifidb_install'] . '/lib/config.inc.php';

    echo "loaded from
";
    echo "  wifidb_install : {$daemon_config['wifidb_install']}
";
    echo "  config.inc.php : $config_file"
        . (is_readable($config_file) ? '' : '   [NOT READABLE]') . "
";
    echo '  dbcore.inc.php : '
        . $daemon_config['wifidb_install'] . "/lib/dbcore.inc.php
";
    echo '  mvt.inc.php    : '
        . $daemon_config['wifidb_install'] . "/lib/mvt.inc.php
";

    // Straight out of the file, so a value present here but absent below means
    // the setting is simply not being carried onto $dbcore.
    $raw = [];
    if (is_readable($config_file)) {
        $text = file_get_contents($config_file);
        foreach (['tile_swarm_url', 'tile_archive_url', 'tile_archive_generate'] as $key) {
            $pattern = '/\'' . preg_quote($key, '/') . '\'\s*=>\s*([^,\r\n]+)/';
            if (preg_match($pattern, $text, $m)) {
                $raw[$key] = trim($m[1]);
            }
        }
    }

    echo "
setting                        in the file        on \$dbcore
";
    foreach ([
        'tile_swarm_url',
        'tile_swarm_category_prefix',
        'tile_swarm_public_key',
        'tile_swarm_infohash_max_days',
        'tile_archive_url',
        'tile_archive_dir',
        'tile_archive_generate',
        'tile_archive_keep',
    ] as $key) {
        $onCore = isset($dbcore->$key)
            ? (is_scalar($dbcore->$key) ? (string)$dbcore->$key : gettype($dbcore->$key))
            : '(unset)';
        $inFile = $raw[$key] ?? '';
        printf("  %-28s %-18s %s
", $key, $inFile, $onCore);
    }

    echo "
hostname         : " . (gethostname() ?: '(none)') . "
";
    echo 'wifidb_nodename  : ' . ($dbcore->node_name ?? '(none)') . "
";
    echo 'generates mvt    : '
        . (mvt_generates_archives($dbcore, 'mvt') ? 'yes' : 'no') . "
";
    echo 'buckets          : ' . count(mvt_buckets()) . "
";
    echo 'first category   : '
        . (mvt_swarm_category($dbcore, mvt_buckets()[0]) ?? '(null -- tile_swarm_url is not set)')
        . "
";
    exit(0);
}

if (in_array('--record', $argv, true)) {
    $bucket   = swarm_arg($argv, 'bucket');
    $infohash = swarm_arg($argv, 'infohash');
    $category = swarm_arg($argv, 'category');

    // Given a category instead of a bucket, work the bucket out — which is
    // what the onComplete hook has, since that is what the swarm passes.
    if ($bucket === null && $category !== null) {
        $bucket = swarm_bucket_for($category, $dbcore);
        if ($bucket === null) {
            swarm_log("not one of ours, ignoring: $category");
            exit(0);
        }
    }

    if ($bucket === null || $infohash === null) {
        fwrite(STDERR, "usage: swarm_index.php --record --bucket <name>|--category <name> --infohash <hex>\n");
        exit(2);
    }

    $ok = mvt_swarm_record_archive($dbcore, $bucket, [
        'category' => $category ?? mvt_swarm_category($dbcore, $bucket) ?? '',
        'infohash' => $infohash,
        // Left alone: the hook knows the build but not the magnet the swarm
        // publishes for it, and overwriting a good value with null would make
        // the fragment worse than it was.
    ]);

    if (!$ok) {
        swarm_log("could not record $bucket");
        exit(1);
    }
    swarm_log("$bucket -> ".substr($infohash, 0, 12));
    exit(0);
}

exit(swarm_refresh($dbcore, swarm_arg($argv, 'url')));
