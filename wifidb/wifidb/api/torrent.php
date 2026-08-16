<?php
error_reporting(1);
@ini_set('display_errors', 0);
/*
torrent.php — this site's copy of a bucket archive's .torrent
Copyright (C) 2026 Andrew Calcutt

Serves the metainfo for one age-bucket's PMTiles archive, as cached in
swarm_archives by tools/cron/update_swarm_index.

  https://wifidb.net/api/torrent.php?bucket=monthly

Why this exists rather than a link to the swarm node that published it:

  The metainfo is the one part of an archive a browser cannot obtain any
  other way.  A magnet names a swarm and carries no piece hashes; those live
  in the metainfo, which a BitTorrent client can only get from a peer, over
  BEP 9.  A web seed serves file payload and never metainfo.  So a browser
  that cannot reach a peer — a carrier network blocking the websocket
  trackers, a NAT no WebRTC candidate survives — cannot use the web seed
  either, however reachable that web seed is: it holds bytes it has no way
  to verify.

  Serving the metainfo from here removes the peer from the critical path.
  It also removes the swarm node: the web seed for these archives is already
  on this host, so with this endpoint the entire read is same-origin and
  depends on nothing but this server.

  Any ordinary torrent client can use it too — it is a .torrent like any
  other, carrying the trackers and the web seed the swarm published with it.

Parameters:
  bucket   — required.  One of the buckets in mvt_buckets().

This program is free software; you can redistribute it and/or modify it under
the terms of the GNU General Public License as published by the Free Software
Foundation; Version 2 of the License.
*/

define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "api");

include('../lib/init.inc.php');
// Functions, not a class, so the autoloader in init.inc.php does not reach it.
include_once('../lib/mvt.inc.php');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, HEAD, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

/**
 * Answers with an error, in the shape the rest of api/ uses.
 * @param int $code HTTP status.
 * @param string $message What went wrong.
 * @return void
 */
function torrent_fail(int $code, string $message): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode(array('error' => $message));
    exit;
}

$bucket = preg_replace('/[^a-z0-9_]/', '', strtolower((string)@$_REQUEST['bucket']));
if ($bucket === '' || !in_array($bucket, mvt_buckets(), true)) {
    torrent_fail(400, 'bucket must be one of: ' . implode(', ', mvt_buckets()));
}

$cached = mvt_swarm_cached_archive($dbcore, $bucket);
$raw    = mvt_swarm_torrent_file($dbcore, $bucket);
if ($raw === null) {
    // Distinguished from a bad bucket, because the two want different actions:
    // one is a caller error, the other means update_swarm_index has not run
    // since this bucket was built, or the swarm published no .torrent for it.
    torrent_fail(404, 'no metainfo cached for this bucket yet');
}

$infohash = isset($cached['infohash']) ? strtolower(trim((string)$cached['infohash'])) : '';
$name     = isset($cached['archive_name']) && $cached['archive_name'] !== ''
    ? preg_replace('/[^A-Za-z0-9._-]/', '_', (string)$cached['archive_name'])
    : $bucket . '.pmtiles';

// The infohash is exactly what this document's identity is, so it makes a
// perfect validator: the bytes cannot change without it changing.  The URL is
// per-bucket and therefore moves with each build, which is why the max-age is
// short even though the body it currently names is immutable.
if ($infohash !== '') {
    header('ETag: "' . $infohash . '"');
    $seen = isset($_SERVER['HTTP_IF_NONE_MATCH']) ? trim($_SERVER['HTTP_IF_NONE_MATCH']) : '';
    if ($seen !== '' && trim($seen, '"') === $infohash) {
        http_response_code(304);
        exit;
    }
}

header('Content-Type: application/x-bittorrent');
header('Content-Length: ' . strlen($raw));
header('Cache-Control: public, max-age=300');
// inline rather than attachment: a browser fetching this with fetch() does not
// care, and a person opening it in a torrent client is better served by their
// client's own handling than by a forced download.
header('Content-Disposition: inline; filename="' . $name . '.torrent"');

if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
    echo $raw;
}
