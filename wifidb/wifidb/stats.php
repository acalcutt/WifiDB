<?php
/*
stats.php, WiFiDB Statistics Page
Copyright (C) 2011 Phil Ferland, 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms
of the GNU General Public License as published by the Free Software Foundation; either
version 2 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY;
without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program;
if not, write to the

   Free Software Foundation, Inc.,
   59 Temple Place, Suite 330,
   Boston, MA 02111-1307 USA
*/
define("SWITCH_SCREEN", "HTML");
define("SWITCH_EXTRAS", "");

include('lib/init.inc.php');

$usersa =  array();

# Get top N parameter (default 10)
$top_n = filter_input(INPUT_GET, 'top', FILTER_SANITIZE_NUMBER_INT);
if(!is_numeric($top_n) || $top_n < 1 || $top_n > 100){$top_n = 10;}

# Get date range filter parameters
$date_range = filter_input(INPUT_GET, 'range', FILTER_SANITIZE_STRING);
$start_date = filter_input(INPUT_GET, 'start_date', FILTER_SANITIZE_STRING);
$end_date = filter_input(INPUT_GET, 'end_date', FILTER_SANITIZE_STRING);

# Handle preset date ranges
$today = date('Y-m-d');
$date_filter_sql = "";
$date_filter_label = "All Time";
$use_cache = true; // Use cache for "All Time" view

switch($date_range) {
	case "30days":
		$start_date = date('Y-m-d', strtotime('-30 days'));
		$end_date = $today;
		$date_filter_label = "Last 30 Days";
		$use_cache = false;
		break;
	case "90days":
		$start_date = date('Y-m-d', strtotime('-90 days'));
		$end_date = $today;
		$date_filter_label = "Last 90 Days";
		$use_cache = false;
		break;
	case "6months":
		$start_date = date('Y-m-d', strtotime('-6 months'));
		$end_date = $today;
		$date_filter_label = "Last 6 Months";
		$use_cache = false;
		break;
	case "1year":
		$start_date = date('Y-m-d', strtotime('-1 year'));
		$end_date = $today;
		$date_filter_label = "Last Year";
		$use_cache = false;
		break;
	case "2years":
		$start_date = date('Y-m-d', strtotime('-2 years'));
		$end_date = $today;
		$date_filter_label = "Last 2 Years";
		$use_cache = false;
		break;
	case "custom":
		# Validate custom dates
		if(!empty($start_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date)) { $start_date = ""; }
		if(!empty($end_date) && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) { $end_date = ""; }
		if(!empty($start_date) || !empty($end_date)) {
			$date_filter_label = "Custom Range";
			$use_cache = false;
		}
		break;
	default:
		$date_range = "all";
		$start_date = "";
		$end_date = "";
		break;
}

# Build date filter SQL clause
if(!empty($start_date) && !empty($end_date)) {
	$date_filter_sql = " AND fa >= '".$start_date." 00:00:00' AND fa <= '".$end_date." 23:59:59'";
} elseif(!empty($start_date)) {
	$date_filter_sql = " AND fa >= '".$start_date." 00:00:00'";
} elseif(!empty($end_date)) {
	$date_filter_sql = " AND fa <= '".$end_date." 23:59:59'";
}

/**
 * Helper function to get cached data
 */
function getCache($dbcore, $cache_key) {
	try {
		$sql = "SELECT cache_data, updated_at FROM stats_cache WHERE cache_key = ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $cache_key, PDO::PARAM_STR);
		$prep->execute();
		$row = $prep->fetch(2);
		if($row) {
			return json_decode($row['cache_data'], true);
		}
	} catch(Exception $e) {
		// Cache table might not exist yet, fall back to live queries
	}
	return null;
}

# Initialize variables
$count = 0;
$open = 0;
$wep = 0;
$sec = 0;
$cell_count = 0;
$cell_types = array();
$bt_count = 0;
$bt_types = array();
$wifi_graph_data = array();
$cell_graph_data = array();
$bt_graph_data = array();
$top_wifi = array();
$top_cell = array();
$top_bt = array();
$cache_age = "";

# Try to use cache for "All Time" view
if($use_cache) {
	$summary = getCache($dbcore, 'summary_counts');
	if($summary !== null) {
		$count = $summary['total'];
		$open = $summary['open'];
		$wep = $summary['wep'];
		$sec = $summary['secure'];
		$cell_count = $summary['cell_total'];
		$cell_types = isset($summary['cell_types']) ? $summary['cell_types'] : array();
		$bt_count = $summary['bt_total'];
		$bt_types = isset($summary['bt_types']) ? $summary['bt_types'] : array();

		// Get cached time-series data
		$wifi_graph_data = getCache($dbcore, 'wifi_timeseries');
		if($wifi_graph_data === null) $wifi_graph_data = array();

		$cell_graph_data = getCache($dbcore, 'cell_timeseries');
		if($cell_graph_data === null) $cell_graph_data = array();

		$bt_graph_data = getCache($dbcore, 'bt_timeseries');
		if($bt_graph_data === null) $bt_graph_data = array();

		// Get cached top lists
		$cached_top_wifi = getCache($dbcore, 'top_wifi');
		if($cached_top_wifi !== null) {
			// Slice to requested top_n and apply HTML escaping
			$cached_top_wifi = array_slice($cached_top_wifi, 0, $top_n);
			foreach($cached_top_wifi as $idx => $wifi) {
				$top_wifi[$idx]['id'] = $wifi['id'];
				$top_wifi[$idx]['ssid'] = htmlspecialchars($dbcore->formatSSID($wifi['ssid']), ENT_QUOTES, 'UTF-8');
				$top_wifi[$idx]['bssid'] = htmlspecialchars($wifi['bssid'], ENT_QUOTES, 'UTF-8');
				$top_wifi[$idx]['auth'] = htmlspecialchars($wifi['auth'], ENT_QUOTES, 'UTF-8');
				$top_wifi[$idx]['encr'] = htmlspecialchars($wifi['encr'], ENT_QUOTES, 'UTF-8');
				$top_wifi[$idx]['points'] = $wifi['points'];
				$top_wifi[$idx]['validgps'] = $wifi['validgps'];
			}
		}

		$cached_top_cell = getCache($dbcore, 'top_cell');
		if($cached_top_cell !== null) {
			$cached_top_cell = array_slice($cached_top_cell, 0, $top_n);
			foreach($cached_top_cell as $idx => $cell) {
				$top_cell[$idx]['id'] = $cell['id'];
				$top_cell[$idx]['ssid'] = htmlspecialchars($dbcore->formatSSID($cell['ssid']), ENT_QUOTES, 'UTF-8');
				$top_cell[$idx]['mac'] = htmlspecialchars($cell['mac'], ENT_QUOTES, 'UTF-8');
				$top_cell[$idx]['type'] = htmlspecialchars($cell['type'], ENT_QUOTES, 'UTF-8');
				$top_cell[$idx]['points'] = $cell['points'];
				$top_cell[$idx]['validgps'] = $cell['validgps'];
			}
		}

		$cached_top_bt = getCache($dbcore, 'top_bt');
		if($cached_top_bt !== null) {
			$cached_top_bt = array_slice($cached_top_bt, 0, $top_n);
			foreach($cached_top_bt as $idx => $bt) {
				$top_bt[$idx]['id'] = $bt['id'];
				$top_bt[$idx]['ssid'] = htmlspecialchars($dbcore->formatSSID($bt['ssid']), ENT_QUOTES, 'UTF-8');
				$top_bt[$idx]['mac'] = htmlspecialchars($bt['mac'], ENT_QUOTES, 'UTF-8');
				$top_bt[$idx]['type'] = htmlspecialchars($bt['type'], ENT_QUOTES, 'UTF-8');
				$top_bt[$idx]['points'] = $bt['points'];
				$top_bt[$idx]['validgps'] = $bt['validgps'];
			}
		}

		$cache_age = "(cached)";
	} else {
		// Cache miss, fall back to live queries
		$use_cache = false;
	}
}

// Run live queries if not using cache or cache miss
if(!$use_cache) {
	$top_wifi_list = array();
	$top_cell_list = array();
	$top_bt_list = array();
	#Get SECTYPE and AP Counts
	$sql = "SELECT SECTYPE, count(AP_ID) AS ap_count FROM wifi_ap WHERE BSSID <> '00:00:00:00:00:00' AND fa IS NOT NULL AND fa NOT LIKE '1970-01-01%'".$date_filter_sql." GROUP BY SECTYPE";
	$result = $dbcore->sql->conn->query($sql);
	$result->execute();
	$seclist = $result->fetchAll();
	foreach($seclist as $secval)
	{
		$count += $secval["ap_count"];
		if($secval["SECTYPE"] == 1)
			{$open = $secval["ap_count"];}
		elseif($secval["SECTYPE"] == 2)
			{$wep = $secval["ap_count"];}
		elseif($secval["SECTYPE"] == 3)
			{$sec = $secval["ap_count"];}
	}

	#Get Cell Tower counts by type
	$sql = "SELECT type, count(cell_id) AS cell_count FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE')".$date_filter_sql." GROUP BY type";
	$result = $dbcore->sql->conn->query($sql);
	$result->execute();
	$celllist = $result->fetchAll();
	foreach($celllist as $cellval)
	{
		$cell_count += $cellval["cell_count"];
		$cell_types[$cellval["type"]] = $cellval["cell_count"];
	}

	#Get Bluetooth counts by type
	$sql = "SELECT type, count(cell_id) AS bt_count FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE')".$date_filter_sql." GROUP BY type";
	$result = $dbcore->sql->conn->query($sql);
	$result->execute();
	$btlist = $result->fetchAll();
	foreach($btlist as $btval)
	{
		$bt_count += $btval["bt_count"];
		$bt_types[$btval["type"]] = $btval["bt_count"];
	}

	# Get Top N WiFi APs by points (most observations)
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT AP_ID, SSID, BSSID, AUTH, ENCR, points, HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%'".$date_filter_sql." ORDER BY points DESC LIMIT ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $top_n, PDO::PARAM_INT);
		$prep->execute();
		$top_wifi_list = $prep->fetchAll(2);
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT TOP ".$top_n." AP_ID, SSID, BSSID, AUTH, ENCR, points, HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%'".$date_filter_sql." ORDER BY points DESC";
		$result = $dbcore->sql->conn->query($sql);
		$top_wifi_list = $result->fetchAll(2);
	}
	foreach($top_wifi_list as $idx => $wifi)
	{
		$top_wifi[$idx]['id'] = $wifi['AP_ID'];
		$top_wifi[$idx]['ssid'] = htmlspecialchars($dbcore->formatSSID($wifi['SSID']), ENT_QUOTES, 'UTF-8');
		$top_wifi[$idx]['bssid'] = htmlspecialchars($wifi['BSSID'], ENT_QUOTES, 'UTF-8');
		$top_wifi[$idx]['auth'] = htmlspecialchars($wifi['AUTH'], ENT_QUOTES, 'UTF-8');
		$top_wifi[$idx]['encr'] = htmlspecialchars($wifi['ENCR'], ENT_QUOTES, 'UTF-8');
		$top_wifi[$idx]['points'] = $wifi['points'];
		$top_wifi[$idx]['validgps'] = ($wifi['HighGps_ID'] != "") ? 1 : 0;
	}

	# Get Top N Cell Towers by points (most observations)
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE')".$date_filter_sql." ORDER BY points DESC LIMIT ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $top_n, PDO::PARAM_INT);
		$prep->execute();
		$top_cell_list = $prep->fetchAll(2);
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT TOP ".$top_n." cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE')".$date_filter_sql." ORDER BY points DESC";
		$result = $dbcore->sql->conn->query($sql);
		$top_cell_list = $result->fetchAll(2);
	}
	foreach($top_cell_list as $idx => $cell)
	{
		$top_cell[$idx]['id'] = $cell['cell_id'];
		$top_cell[$idx]['ssid'] = htmlspecialchars($dbcore->formatSSID($cell['ssid']), ENT_QUOTES, 'UTF-8');
		$top_cell[$idx]['mac'] = htmlspecialchars($cell['mac'], ENT_QUOTES, 'UTF-8');
		$top_cell[$idx]['type'] = htmlspecialchars($cell['type'], ENT_QUOTES, 'UTF-8');
		$top_cell[$idx]['points'] = $cell['points'];
		$top_cell[$idx]['validgps'] = ($cell['highgps_id'] != "") ? 1 : 0;
	}

	# Get Top N Bluetooth Devices by points (most observations)
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE')".$date_filter_sql." ORDER BY points DESC LIMIT ?";
		$prep = $dbcore->sql->conn->prepare($sql);
		$prep->bindParam(1, $top_n, PDO::PARAM_INT);
		$prep->execute();
		$top_bt_list = $prep->fetchAll(2);
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT TOP ".$top_n." cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE')".$date_filter_sql." ORDER BY points DESC";
		$result = $dbcore->sql->conn->query($sql);
		$top_bt_list = $result->fetchAll(2);
	}
	foreach($top_bt_list as $idx => $bt)
	{
		$top_bt[$idx]['id'] = $bt['cell_id'];
		$top_bt[$idx]['ssid'] = htmlspecialchars($dbcore->formatSSID($bt['ssid']), ENT_QUOTES, 'UTF-8');
		$top_bt[$idx]['mac'] = htmlspecialchars($bt['mac'], ENT_QUOTES, 'UTF-8');
		$top_bt[$idx]['type'] = htmlspecialchars($bt['type'], ENT_QUOTES, 'UTF-8');
		$top_bt[$idx]['points'] = $bt['points'];
		$top_bt[$idx]['validgps'] = ($bt['highgps_id'] != "") ? 1 : 0;
	}

	# Time-series queries - only run for filtered views, skip for "All Time" if cache exists
	# For date-filtered views, we still need to compute these (but they'll be faster with date range)
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT DATE_FORMAT(fa, '%Y-%m') as month,
				COUNT(*) as new_count,
				SUM(CASE WHEN SECTYPE = 1 THEN 1 ELSE 0 END) as open_count,
				SUM(CASE WHEN SECTYPE = 2 THEN 1 ELSE 0 END) as wep_count,
				SUM(CASE WHEN SECTYPE = 3 THEN 1 ELSE 0 END) as secure_count
				FROM wifi_ap
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND BSSID <> '00:00:00:00:00:00'".$date_filter_sql."
				GROUP BY DATE_FORMAT(fa, '%Y-%m')
				ORDER BY month ASC";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT FORMAT(fa, 'yyyy-MM') as month,
				COUNT(*) as new_count,
				SUM(CASE WHEN SECTYPE = 1 THEN 1 ELSE 0 END) as open_count,
				SUM(CASE WHEN SECTYPE = 2 THEN 1 ELSE 0 END) as wep_count,
				SUM(CASE WHEN SECTYPE = 3 THEN 1 ELSE 0 END) as secure_count
				FROM wifi_ap
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND BSSID <> '00:00:00:00:00:00'".$date_filter_sql."
				GROUP BY FORMAT(fa, 'yyyy-MM')
				ORDER BY month ASC";
	}
	$result = $dbcore->sql->conn->query($sql);
	$timeseries_wifi = $result->fetchAll(2);

	$cumulative_total = 0;
	$cumulative_open = 0;
	$cumulative_wep = 0;
	$cumulative_secure = 0;

	foreach($timeseries_wifi as $row) {
		$cumulative_total += $row['new_count'];
		$cumulative_open += $row['open_count'];
		$cumulative_wep += $row['wep_count'];
		$cumulative_secure += $row['secure_count'];

		   $open_pct = ($cumulative_total > 0) ? round(($cumulative_open / $cumulative_total) * 100, 2) : 0;
		   $wep_pct = ($cumulative_total > 0) ? round(($cumulative_wep / $cumulative_total) * 100, 2) : 0;
		   // Secure is the remainder to ensure sum is 100
		   $secure_pct = ($cumulative_total > 0) ? round(100 - $open_pct - $wep_pct, 2) : 0;

		   $wifi_graph_data[] = array(
			   'month' => $row['month'],
			   'new_count' => (int)$row['new_count'],
			   'cumulative' => $cumulative_total,
			   'open_pct' => $open_pct,
			   'wep_pct' => $wep_pct,
			   'secure_pct' => $secure_pct
		   );
	}

	#Get time-series data for Cell towers per month
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT DATE_FORMAT(fa, '%Y-%m') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE')".$date_filter_sql."
				GROUP BY DATE_FORMAT(fa, '%Y-%m')
				ORDER BY month ASC";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT FORMAT(fa, 'yyyy-MM') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE')".$date_filter_sql."
				GROUP BY FORMAT(fa, 'yyyy-MM')
				ORDER BY month ASC";
	}
	$result = $dbcore->sql->conn->query($sql);
	$timeseries_cell = $result->fetchAll(2);

	$cumulative_cell = 0;
	foreach($timeseries_cell as $row) {
		$cumulative_cell += $row['new_count'];
		$cell_graph_data[] = array(
			'month' => $row['month'],
			'new_count' => (int)$row['new_count'],
			'cumulative' => $cumulative_cell
		);
	}

	#Get time-series data for Bluetooth per month
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT DATE_FORMAT(fa, '%Y-%m') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE')".$date_filter_sql."
				GROUP BY DATE_FORMAT(fa, '%Y-%m')
				ORDER BY month ASC";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT FORMAT(fa, 'yyyy-MM') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE')".$date_filter_sql."
				GROUP BY FORMAT(fa, 'yyyy-MM')
				ORDER BY month ASC";
	}
	$result = $dbcore->sql->conn->query($sql);
	$timeseries_bt = $result->fetchAll(2);

	$cumulative_bt = 0;
	foreach($timeseries_bt as $row) {
		$cumulative_bt += $row['new_count'];
		$bt_graph_data[] = array(
			'month' => $row['month'],
			'new_count' => (int)$row['new_count'],
			'cumulative' => $cumulative_bt
		);
	}
}

#Count the number of users who have imported files
$sql = "SELECT count(distinct file_user) AS user_count FROM files WHERE completed = 1";
$result = $dbcore->sql->conn->query($sql);
$usercount = $result->fetch(2);

#Get the latest import list
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT id, file_user, title, file_date, ValidGPS FROM files WHERE completed = 1 ORDER BY id DESC LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 id, file_user, title, file_date, ValidGPS FROM files WHERE completed = 1 ORDER BY id DESC";}
$result = $dbcore->sql->conn->query($sql);
$lastuser = $result->fetch(2);
$lastid = $lastuser['id'];
$lastusername =  htmlspecialchars($lastuser['file_user'], ENT_QUOTES, 'UTF-8');
$lasttitle = htmlspecialchars($lastuser['title'], ENT_QUOTES, 'UTF-8');
$lastdate = htmlspecialchars($lastuser['date'], ENT_QUOTES, 'UTF-8');
$list_validgps = $lastuser['ValidGPS'];
if($lastdate == ""){$lastdate = date("Y-m-d H:i:s");}

#Find if last user has valid GPS
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT ValidGPS FROM files WHERE file_user LIKE ? And ValidGPS = 1 LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 ValidGPS FROM files WHERE file_user LIKE ? And ValidGPS = 1";}
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $lastusername, PDO::PARAM_STR);
$prep->execute();
$appointer = $prep->fetch(2);
$user_validgps = $appointer['ValidGPS'];

#Get the latest AP
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT AP_ID,SSID,HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL ORDER BY AP_ID DESC LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 AP_ID,SSID,HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL ORDER BY AP_ID DESC";}
$result = $dbcore->sql->conn->query($sql);
$lastap_array = $result->fetch(2);

$lastap_id = $lastap_array['AP_ID'];
$lastap_ssid = htmlspecialchars($dbcore->formatSSID($lastap_array['SSID']), ENT_QUOTES, 'UTF-8');
if($lastap_array['HighGps_ID'] == ""){$ap_validgps = 0;}else{$ap_validgps = 1;}

# Get the latest user to import (with GPS)
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT file_user FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY id DESC LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 file_user FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY id DESC";}
$result = $dbcore->sql->conn->query($sql);
$lastuser_withgps = $result->fetch(2);
$lastusername_withgps = $lastuser_withgps && isset($lastuser_withgps['file_user']) ? htmlspecialchars($lastuser_withgps['file_user'], ENT_QUOTES, 'UTF-8') : '';

# Get the latest AP added (with GPS)
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT AP_ID,SSID FROM wifi_ap WHERE HighGps_ID IS NOT NULL ORDER BY AP_ID DESC LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 AP_ID,SSID FROM wifi_ap WHERE HighGps_ID IS NOT NULL ORDER BY AP_ID DESC";}
$result = $dbcore->sql->conn->query($sql);
$lastap_withgps = $result->fetch(2);
$lastap_id_withgps = $lastap_withgps && isset($lastap_withgps['AP_ID']) ? $lastap_withgps['AP_ID'] : '';
$lastap_ssid_withgps = $lastap_withgps && isset($lastap_withgps['SSID']) ? htmlspecialchars($dbcore->formatSSID($lastap_withgps['SSID']), ENT_QUOTES, 'UTF-8') : '';

# Get the latest import list (with GPS)
if($dbcore->sql->service == "mysql")
	{$sql = "SELECT id, title FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY id DESC LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
	{$sql = "SELECT TOP 1 id, title FROM files WHERE completed = 1 AND ValidGPS = 1 ORDER BY id DESC";}
$result = $dbcore->sql->conn->query($sql);
$lastimport_withgps = $result->fetch(2);
$lastimport_id_withgps = $lastimport_withgps && isset($lastimport_withgps['id']) ? $lastimport_withgps['id'] : '';
$lastimport_title_withgps = $lastimport_withgps && isset($lastimport_withgps['title']) ? htmlspecialchars($lastimport_withgps['title'], ENT_QUOTES, 'UTF-8') : '';

if ($usercount == NULL)
{
    $lastusername = "No users in Database.";
    $lasttitle = "No imports have finished yet.";
    $lastdate = date("Y-m-d H:i:s");
    $usercount = 0;
    $lastid = 0;
}

$dbcore->smarty->assign('wifidb_page_label', 'Index Page');
$dbcore->smarty->assign('total_aps', $count);
$dbcore->smarty->assign('open_aps', $open);
$dbcore->smarty->assign('wep_aps', $wep);
$dbcore->smarty->assign('sec_aps', $sec);
$dbcore->smarty->assign('total_users', $usercount['user_count']);
$dbcore->smarty->assign('new_ap_id', $lastap_id);
$dbcore->smarty->assign('ap_validgps', $ap_validgps);
$dbcore->smarty->assign('list_validgps', $list_validgps);
$dbcore->smarty->assign('user_validgps', $user_validgps);
$dbcore->smarty->assign('new_import_user', $lastusername);
$dbcore->smarty->assign('new_ap_ssid', $lastap_ssid);
$dbcore->smarty->assign('new_import_date', $lastdate);
$dbcore->smarty->assign('new_import_title', $lasttitle);
$dbcore->smarty->assign('new_import_id', $lastid);

$dbcore->smarty->assign('new_import_user_withgps', $lastusername_withgps);
$dbcore->smarty->assign('new_ap_id_withgps', $lastap_id_withgps);
$dbcore->smarty->assign('new_ap_ssid_withgps', $lastap_ssid_withgps);
$dbcore->smarty->assign('new_import_id_withgps', $lastimport_id_withgps);
$dbcore->smarty->assign('new_import_title_withgps', $lastimport_title_withgps);

$dbcore->smarty->assign('cell_count', $cell_count);
$dbcore->smarty->assign('cell_types', $cell_types);
$dbcore->smarty->assign('bt_count', $bt_count);
$dbcore->smarty->assign('bt_types', $bt_types);
$dbcore->smarty->assign('top_n', $top_n);
$dbcore->smarty->assign('top_wifi', $top_wifi);
$dbcore->smarty->assign('top_cell', $top_cell);
$dbcore->smarty->assign('top_bt', $top_bt);

$dbcore->smarty->assign('date_range', $date_range);
$dbcore->smarty->assign('start_date', $start_date);
$dbcore->smarty->assign('end_date', $end_date);
$dbcore->smarty->assign('date_filter_label', $date_filter_label.$cache_age);

$dbcore->smarty->assign('wifi_graph_data', json_encode($wifi_graph_data));
$dbcore->smarty->assign('wifi_graph_data_raw', $wifi_graph_data);
$dbcore->smarty->assign('cell_graph_data', json_encode($cell_graph_data));
$dbcore->smarty->assign('bt_graph_data', json_encode($bt_graph_data));

$dbcore->smarty->display('index.tpl');

?>
