<?php
/*
cell_geojsond.php — WiFiDB Cell Network GeoJSON Daemon
Copyright (C) 2024 Andrew Calcutt

Pre-generates cell_networks.json for the map.  Improvements over the
original version:
  - Keyset pagination (WHERE cell_id > last_id) instead of OFFSET/LIMIT,
    keeping every page O(page_size) regardless of table size.
  - LEFT JOIN cell_carriers in SQL (MySQL path) eliminates the per-row
    lookup that caused an N+1 query on large tables.
  - Streams features directly to a .tmp file; atomically renames to the
    live file on completion so readers never see a partial result.

This program is free software; you can redistribute it and/or modify it
under the terms of the GNU General Public License as published by the Free
Software Foundation; Version 2 of the License.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if (!(require(dirname(__FILE__).'/../daemon.config.inc.php'))) {
    die("You need to create and configure your [tools]/daemon.config.inc.php");
}
if ($daemon_config['wifidb_install'] === '') {
    die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");
}
require $daemon_config['wifidb_install'].'/lib/init.inc.php';

$page_size = 100000;
$out_file  = rtrim($daemon_config['wifidb_install'], '/') . '/out/geojson/cell_networks.json';
$tmp_file  = $out_file . '.tmp';

// ── SQL — keyset pagination: AND cell_id.cell_id > ? ... LIMIT/FETCH ? ───────
// MySQL: LEFT JOIN cell_carriers eliminates the per-row lookup.
// MCC = first 3 chars of the part before the first '_' in mac.
// MNC = remainder of that part after the first 3 chars.
if ($dbcore->sql->service === 'mysql') {
    $sql = "SELECT cell_id.cell_id, cell_id.mac, cell_id.ssid, cell_id.authmode, cell_id.chan, cell_id.type,
                   cell_id.fa, cell_id.la, cell_id.points, cell_id.high_gps_rssi AS rssi,
                   c_gps.lat AS lat, c_gps.lon AS lon,
                   c_file.file_user AS file_user,
                   cc.network, cc.country
            FROM cell_id
            INNER JOIN wifi_gps      AS c_gps ON c_gps.GPS_ID = cell_id.highgps_id
            INNER JOIN files         AS c_file ON c_file.id   = cell_id.file_id
            LEFT  JOIN cell_carriers AS cc
                   ON  cc.mcc = CAST(LEFT(SUBSTRING_INDEX(cell_id.mac, '_', 1), 3) AS UNSIGNED)
                   AND cc.mnc = CAST(SUBSTRING(SUBSTRING_INDEX(cell_id.mac, '_', 1), 4) AS UNSIGNED)
            WHERE cell_id.type != 'BT' AND cell_id.type != 'BLE' AND cell_id.highgps_id IS NOT NULL
              AND cell_id.cell_id > ?
            ORDER BY cell_id.cell_id ASC
            LIMIT ?";
} else if ($dbcore->sql->service === 'sqlsrv') {
    $sql = "SELECT cell_id.cell_id, cell_id.mac, cell_id.ssid, cell_id.authmode, cell_id.chan, cell_id.type,
                   cell_id.fa, cell_id.la, cell_id.points, cell_id.high_gps_rssi AS rssi,
                   c_gps.lat AS lat, c_gps.lon AS lon,
                   c_file.[file_user] AS [file_user],
                   cell_carriers.network, cell_carriers.country
            FROM cell_id
            INNER JOIN wifi_gps AS c_gps ON c_gps.GPS_ID = cell_id.highgps_id
            INNER JOIN files    AS c_file ON c_file.id   = cell_id.file_id
            LEFT  OUTER JOIN cell_carriers
                   ON CAST(mcc AS varchar) = SUBSTRING(cell_id.mac, 1, 3)
                  AND CAST(mnc AS varchar) = SUBSTRING(cell_id.mac, 4, 3)
            WHERE cell_id.type != 'BT' AND cell_id.type != 'BLE' AND cell_id.highgps_id IS NOT NULL
              AND cell_id.cell_id > ?
            ORDER BY cell_id.cell_id
            OFFSET 0 ROWS FETCH NEXT ? ROWS ONLY";
}

echo "Writing cell GeoJSON to: {$out_file}\n";

$fp = fopen($tmp_file, 'w');
if ($fp === false) { die("Cannot open tmp file: {$tmp_file}\n"); }

fwrite($fp, '{"type":"FeatureCollection","features":[');
$first   = true;
$last_id = 0;
$total   = 0;

while (true) {
    $prep = $dbcore->sql->conn->prepare($sql);
    $prep->bindParam(1, $last_id,   PDO::PARAM_INT);
    $prep->bindParam(2, $page_size, PDO::PARAM_INT);
    $prep->execute();
    $rows  = $prep->fetchAll(PDO::FETCH_ASSOC);
    $count = count($rows);

    foreach ($rows as $ap) {
        $name    = $ap['network'] ?: $ap['ssid'];
        $ap_info = [
            'id'       => $ap['cell_id'],
            'mac'      => $ap['mac'],
            'mapname'  => $dbcore->formatSSID($name),
            'network'  => $ap['network'],
            'ssid'     => $dbcore->formatSSID($ap['ssid']),
            'authmode' => $ap['authmode'],
            'chan'     => $ap['chan'],
            'type'     => $ap['type'],
            'lat'      => $dbcore->convert->dm2dd($ap['lat']),
            'lon'      => $dbcore->convert->dm2dd($ap['lon']),
            'rssi'     => $ap['rssi'],
            'fa'       => $ap['fa'],
            'la'       => $ap['la'],
            'user'     => $ap['file_user'],
            'points'   => $ap['points'],
        ];
        $feature = $dbcore->createGeoJSON->CreateApFeature($ap_info, 1);
        if (!$first) fwrite($fp, ',');
        fwrite($fp, $feature);
        $first   = false;
        $last_id = (int)$ap['cell_id'];
    }

    $total += $count;
    echo "  last_id={$last_id}  rows_this_page={$count}  total={$total}\n";
    if ($count < $page_size) break;
}

fwrite($fp, "\n]}");
fclose($fp);
rename($tmp_file, $out_file);
echo "Done. Wrote {$total} features to {$out_file}\n";
