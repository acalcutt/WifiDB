<?php
/*
geojsond.php, WiFiDB GeoJson Daemon
Copyright (C) 2019 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->daemon_name	=	"GeoJson";
$dbcore->lastedit		=	"2019-03-16";
$dbcore->daemon_version =	"1.0";

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$pid_filename = 'geojsond_'.$dbcore->This_is_me.'_'.date("YmdHis").'.pid';
$dbcore->pid_file = $dbcore->pid_file_loc.$pid_filename;

if(!file_exists($dbcore->pid_file_loc))
{
	if(!mkdir($dbcore->pid_file_loc))
	{
		#throw new ErrorException("Could not make WiFiDB PID folder. ($dbcore->pid_file_loc)");
		echo "Could not create PID Folder at path: $dbcore->pid_file_loc \n";
		exit(-4);
	}
}
if(file_put_contents($dbcore->pid_file, $dbcore->This_is_me) === FALSE)
{
	echo "Could not write pid file ($dbcore->pid_file), that's not good... >:[\n";
	exit(-5);
}
echo "
WiFiDB ".$dbcore->ver_array['wifidb']." - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$dbcore->lastedit}, GPLv2
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level."\n";

$currentrun = date("Y-m-d G:i:s");

if($dbcore->sql->service == "mysql")
	{
		$exports = [
			["WifiDB_Legacy.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la < DATE_SUB('$currentrun',INTERVAL 3 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_2to3year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 3 YEAR) AND wap.la < DATE_SUB('$currentrun',INTERVAL 2 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_1to2year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 2 YEAR) AND wap.la < DATE_SUB('$currentrun',INTERVAL 1 YEAR) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_0to1year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 1 YEAR) AND wap.la < DATE_SUB('$currentrun',INTERVAL 1 MONTH) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_monthly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 1 MONTH) AND wap.la < DATE_SUB('$currentrun',INTERVAL 1 WEEK) ORDER BY wap.AP_ID LIMIT ?,?"],
			["WifiDB_weekly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM `wifi_ap` AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= DATE_SUB('$currentrun',INTERVAL 1 WEEK) ORDER BY wap.AP_ID LIMIT ?,?"]
		];
	}
else if($dbcore->sql->service == "sqlsrv")
	{
		$exports = [
			["WifiDB_Legacy.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la < dateadd(year, -3, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_2to3year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(year, -3, '$currentrun') AND wap.la < dateadd(year, -2, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_1to2year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(year, -2, '$currentrun') AND wap.la < dateadd(year, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_0to1year.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(year, -1, '$currentrun') AND wap.la < dateadd(month, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_monthly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(month, -1, '$currentrun') AND wap.la < dateadd(week, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"],
			["WifiDB_weekly.json", "SELECT wap.AP_ID, wap.BSSID, wap.SSID, wap.CHAN, wap.AUTH, wap.ENCR, wap.SECTYPE, wap.RADTYPE, wap.NETTYPE, wap.BTX, wap.OTX, wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS.Lat As Lat, wGPS.Lon As Lon, wGPS.Alt As Alt, wf.file_user FROM wifi_ap AS wap LEFT JOIN wifi_gps AS wGPS ON wGPS.GPS_ID = wap.HighGps_ID LEFT JOIN files AS wf ON wf.id = wap.File_ID WHERE wap.HighGps_ID IS NOT NULL AND wap.points IS NOT NULL AND wap.la >= dateadd(week, -1, '$currentrun') ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY"]
		];
	}

else if($dbcore->sql->service == "pgsql")
	{
		// Same six age buckets as the branches above. They differ only in the
		// wap.la window, so they are built from one template here rather than
		// repeating a 700-character query six times. DATE_SUB / dateadd become
		// "timestamp - interval", and paging is LIMIT/OFFSET.
		$pg_select = 'SELECT wap."AP_ID", wap."BSSID", wap."SSID", wap."CHAN", wap."AUTH", wap."ENCR", wap."SECTYPE", wap."RADTYPE", wap."NETTYPE", wap."BTX", wap."OTX", wap.fa, wap.la, wap.points, wap.high_gps_sig, wap.high_gps_rssi, wGPS."Lat" As "Lat", wGPS."Lon" As "Lon", wGPS."Alt" As "Alt", wf.file_user'
			. ' FROM wifi_ap AS wap'
			. ' LEFT JOIN wifi_gps AS wGPS ON wGPS."GPS_ID" = wap."HighGps_ID"'
			. ' LEFT JOIN files AS wf ON wf.id = wap."File_ID"'
			. ' WHERE wap."HighGps_ID" IS NOT NULL AND wap.points IS NOT NULL';
		$pg_now = "TIMESTAMP '$currentrun'";
		$pg_windows = [
			["WifiDB_Legacy.json",   " AND wap.la < ".$pg_now." - INTERVAL '3 years'"],
			["WifiDB_2to3year.json", " AND wap.la >= ".$pg_now." - INTERVAL '3 years' AND wap.la < ".$pg_now." - INTERVAL '2 years'"],
			["WifiDB_1to2year.json", " AND wap.la >= ".$pg_now." - INTERVAL '2 years' AND wap.la < ".$pg_now." - INTERVAL '1 year'"],
			["WifiDB_0to1year.json", " AND wap.la >= ".$pg_now." - INTERVAL '1 year' AND wap.la < ".$pg_now." - INTERVAL '1 month'"],
			["WifiDB_monthly.json",  " AND wap.la >= ".$pg_now." - INTERVAL '1 month' AND wap.la < ".$pg_now." - INTERVAL '1 week'"],
			["WifiDB_weekly.json",   " AND wap.la >= ".$pg_now." - INTERVAL '1 week'"],
		];
		$exports = [];
		foreach($pg_windows as list($pg_name, $pg_where))
		{
			$exports[] = [$pg_name, $pg_select.$pg_where.' ORDER BY wap."AP_ID" LIMIT ? OFFSET ?'];
		}
	}

// ── CLI flags ─────────────────────────────────────────────────────────────────
// --file FILENAME   Process only the named output file (e.g. WifiDB_weekly.json).
//                   Run multiple instances in parallel from an external wrapper
//                   to maximise throughput; each holds its own DB connection.
$argv_safe   = $argv ?? [];
$single_file = null;
for ($_i = 1, $_nc = count($argv_safe); $_i < $_nc; $_i++) {
    if ($argv_safe[$_i] === '--file') {
        $single_file = $argv_safe[++$_i] ?? null;
    }
}

$page_size = 100000;
$out_dir   = rtrim($daemon_config['wifidb_install'], '/') . '/out/geojson/';

foreach ($exports as list($filename, $sql)) {
    if ($single_file !== null && $filename !== $single_file) continue;

    // Convert to keyset pagination: insert AND AP_ID > ? before ORDER BY,
    // drop the OFFSET ? / LIMIT ?, ? placeholder so only 2 params remain.
    if ($dbcore->sql->service === 'mysql') {
        $ksql = str_replace(
            'ORDER BY wap.AP_ID LIMIT ?,?',
            'AND wap.AP_ID > ? ORDER BY wap.AP_ID LIMIT ?',
            $sql
        );
    } else if ($dbcore->sql->service === 'pgsql') {
        $ksql = str_replace(
            'ORDER BY wap."AP_ID" LIMIT ? OFFSET ?',
            'AND wap."AP_ID" > ? ORDER BY wap."AP_ID" LIMIT ?',
            $sql
        );
    } else {
        $ksql = str_replace(
            'ORDER BY [wap].[AP_ID] OFFSET ? ROWS FETCH NEXT ? ROWS ONLY',
            'AND wap.AP_ID > ? ORDER BY [wap].[AP_ID] OFFSET 0 ROWS FETCH NEXT ? ROWS ONLY',
            $sql
        );
    }

    $out_file = $out_dir . $filename;
    $tmp_file = $out_file . '.tmp';

    echo "\nProcessing: {$filename}\n";

    $fp = fopen($tmp_file, 'w');
    if ($fp === false) { echo "Cannot open tmp file: {$tmp_file} — skipping.\n"; continue; }

    fwrite($fp, '{"type":"FeatureCollection","features":[');
    $first   = true;
    $last_id = 0;
    $total   = 0;

    while (true) {
        $prep = $dbcore->sql->conn->prepare($ksql);
        $prep->bindParam(1, $last_id,   PDO::PARAM_INT);
        $prep->bindParam(2, $page_size, PDO::PARAM_INT);
        $prep->execute();
        $appointer = $prep->fetchAll(PDO::FETCH_ASSOC);
        $count     = count($appointer);

        foreach ($appointer as $ap) {
            $ap_info = [
                'id'            => $ap['AP_ID'],
                'new_ap'        => 1,
                'named'         => 0,
                'mac'           => $ap['BSSID'],
                'ssid'          => $ap['SSID'],
                'chan'          => $ap['CHAN'],
                'radio'         => $ap['RADTYPE'],
                'nt'            => $ap['NETTYPE'],
                'sectype'       => $ap['SECTYPE'],
                'auth'          => $ap['AUTH'],
                'encry'         => $ap['ENCR'],
                'btx'           => $ap['BTX'],
                'otx'           => $ap['OTX'],
                'fa'            => $ap['fa'],
                'la'            => $ap['la'],
                'points'        => $ap['points'],
                'high_gps_sig'  => $ap['high_gps_sig'],
                'high_gps_rssi' => $ap['high_gps_rssi'],
                'lat'           => $dbcore->convert->dm2dd($ap['Lat']),
                'lon'           => $dbcore->convert->dm2dd($ap['Lon']),
                'alt'           => $ap['Alt'],
                'manuf'         => $dbcore->findManuf($ap['BSSID']),
                'user'          => $ap['file_user'],
            ];
            $feature = $dbcore->createGeoJSON->CreateApFeature($ap_info, 1);
            if (!$first) fwrite($fp, ',');
            fwrite($fp, $feature);
            $first   = false;
            $last_id = (int)$ap['AP_ID'];
        }

        $total += $count;
        echo "  last_id={$last_id}  rows={$count}  total={$total}\n";
        if ($count < $page_size) break;
    }

    fwrite($fp, "\n]}");
    fclose($fp);
    rename($tmp_file, $out_file);
    echo "  Done: {$out_file}\n";
}

unlink($dbcore->pid_file);