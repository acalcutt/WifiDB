#!/usr/bin/php
<?php
/*
statsd.php, WiFiDB Statistics Cache Daemon
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$lastedit			=	"2025-12-16";
$dbcore->daemon_name	=	"Stats";

$arguments = $dbcore->parseArgs($argv);

if(@$arguments['h'])
{
	echo "Usage: statsd.php [args...]
  -f		(null)			Force daemon to run without being scheduled.
  -o		(null)			Run once and exit.
  -d		(null)			Run the Stats script as a Daemon.
  -v		(null)			Run Verbosely (SHOW EVERYTHING!)
  -l		(null)			Show License Information.
  -h		(null)			Show this screen.
  --version	(null)			Version Info.
  --logfile=filename.log	Specify the log file name so it can be written to the schedule db

";
	exit(-1);
}

if(@$arguments['version'])
{
	echo "WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals\n";
	exit(-2);
}

if(@$arguments['l'])
{
	echo "WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
{$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2 Random Intervals
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
Copyright (C) 2025 Andrew Calcutt

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation; Version 2 of the License.
This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
You should have received a copy of the GNU General Public License along with this program; If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
";
	exit(-3);
}

if(@$arguments['v']){$dbcore->verbose = 1;}else{$dbcore->verbose = 0;}
if(@$arguments['f']){$dbcore->ForceDaemonRun = 1;}else{$dbcore->ForceDaemonRun = 0;}
if(@$arguments['d']){$dbcore->daemonize = 1;}else{$dbcore->daemonize = 0;}
if(@$arguments['o']){$dbcore->RunOnceThrough = 1;}else{$dbcore->RunOnceThrough = 0;}
if(@$arguments['logfile']){$dbcore->LogFile = $arguments['logfile'];}else{$dbcore->LogFile = "";}

//Now we need to write the PID file so that the init.d file can control it.
if(!file_exists($dbcore->pid_file_loc))
{
	mkdir($dbcore->pid_file_loc);
}
$pid_filename = 'statsd_'.$dbcore->This_is_me.'_'.date("YmdHis").'.pid';
$dbcore->pid_file = $dbcore->pid_file_loc.$pid_filename;

if(!file_exists($dbcore->pid_file_loc))
{
	if(!mkdir($dbcore->pid_file_loc))
	{
		echo "Could not create PID Folder at path: $dbcore->pid_file_loc \n";
		exit(-4);
	}
}
if(file_put_contents($dbcore->pid_file, $dbcore->This_is_me) === FALSE)
{
	echo "Could not write pid file ($dbcore->pid_file), that's not good... >:[\n";
	exit(-5);
}

$dbcore->verbosed("Have written the PID file at ".$dbcore->pid_file." (".$dbcore->This_is_me.")");

echo "
WiFiDB".$dbcore->ver_array['wifidb']."
Codename: ".$dbcore->ver_array['codename']."
 - {$dbcore->daemon_name} Daemon {$dbcore->daemon_version}, {$lastedit}, GPLv2
Daemon Class Last Edit: {$dbcore->ver_array['Daemon']["last_edit"]}
PID File: [ $dbcore->pid_file ]
PID: [ $dbcore->This_is_me ]
 Log Level is: ".$dbcore->log_level."\n";

$job_id = 0;
$dbcore->job_interval = 300; // 5 minutes default
if(!$dbcore->ForceDaemonRun)
{
	$dbcore->verbosed("Running $dbcore->daemon_name jobs for $dbcore->node_name");

	#Claim a schedule ID
	if($dbcore->sql->service == "mysql")
		{$sql = "UPDATE `schedule` SET `pid` = ?, `pidfile` = ?, `logfile` = ?, `status` = ? WHERE `nodename` = ? And `daemon` = ? And `status` != ? And `enabled` = 1 LIMIT 1";}
	else if($dbcore->sql->service == "sqlsrv")
		{$sql = "UPDATE TOP (1) [schedule] SET [pid] = ?, [pidfile] = ?, [logfile] = ?, [status] = ? WHERE [nodename] = ? And [daemon] = ? And [status] != ? And [enabled] = 1";}
	$prepus = $dbcore->sql->conn->prepare($sql);
	$prepus->bindParam(1, $dbcore->This_is_me, PDO::PARAM_INT);
	$prepus->bindParam(2, $pid_filename, PDO::PARAM_STR);
	$prepus->bindParam(3, $logfile, PDO::PARAM_STR);
	$prepus->bindParam(4, $dbcore->StatusRunning, PDO::PARAM_STR);
	$prepus->bindParam(5, $dbcore->node_name, PDO::PARAM_STR);
	$prepus->bindParam(6, $dbcore->daemon_name, PDO::PARAM_STR);
	$prepus->bindParam(7, $dbcore->StatusRunning, PDO::PARAM_STR);
	$prepus->execute();

	#Start claimed schedule ID, if one exists
	$sql = "SELECT id, interval FROM schedule WHERE pid = ? And pidfile = ?";
	$prepgj = $dbcore->sql->conn->prepare($sql);
	$prepgj->bindParam(1, $dbcore->This_is_me, PDO::PARAM_INT);
	$prepgj->bindParam(2, $pid_filename, PDO::PARAM_STR);
	$prepgj->execute();

	if($prepgj->rowCount() === 0)
	{
		$dbcore->verbosed("There are no jobs that need to be run... Exiting...");
		unlink($dbcore->pid_file);
		exit(-6);
	}

	$job = $prepgj->fetch(2);
	$job_id = $job['id'];
	$dbcore->job_interval = $job['interval'];
	$dbcore->verbosed("Job ID: $job_id , Interval:".$dbcore->job_interval);
}

/**
 * Update or insert a cache entry
 */
function updateCache($dbcore, $cache_key, $cache_data) {
	$json_data = json_encode($cache_data);
	$now = date('Y-m-d H:i:s.v');

	// Try to update first
	if($dbcore->sql->service == "mysql") {
		$sql = "INSERT INTO stats_cache (cache_key, cache_data, updated_at) VALUES (?, ?, ?)
				ON DUPLICATE KEY UPDATE cache_data = VALUES(cache_data), updated_at = VALUES(updated_at)";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "MERGE INTO stats_cache AS target
				USING (SELECT ? AS cache_key, ? AS cache_data, ? AS updated_at) AS source
				ON target.cache_key = source.cache_key
				WHEN MATCHED THEN UPDATE SET cache_data = source.cache_data, updated_at = source.updated_at
				WHEN NOT MATCHED THEN INSERT (cache_key, cache_data, updated_at) VALUES (source.cache_key, source.cache_data, source.updated_at);";
	}
	$prep = $dbcore->sql->conn->prepare($sql);
	if($dbcore->sql->service == "mysql") {
		$prep->bindParam(1, $cache_key, PDO::PARAM_STR);
		$prep->bindParam(2, $json_data, PDO::PARAM_STR);
		$prep->bindParam(3, $now, PDO::PARAM_STR);
	} else {
		$prep->bindParam(1, $cache_key, PDO::PARAM_STR);
		$prep->bindParam(2, $json_data, PDO::PARAM_STR);
		$prep->bindParam(3, $now, PDO::PARAM_STR);
	}
	$prep->execute();
}

/**
 * Generate and cache all statistics
 */
function generateStatsCache($dbcore) {
	$dbcore->verbosed("Generating statistics cache...", 1);
	$start_time = microtime(true);

	// 1. Summary counts
	$dbcore->verbosed("  - Generating summary counts...", 1);
	$sql = "SELECT SECTYPE, count(AP_ID) AS ap_count FROM wifi_ap WHERE BSSID <> '00:00:00:00:00:00' AND fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' GROUP BY SECTYPE";
	$result = $dbcore->sql->conn->query($sql);
	$result->execute();
	$seclist = $result->fetchAll(2);

	$summary = array('total' => 0, 'open' => 0, 'wep' => 0, 'secure' => 0);
	foreach($seclist as $secval) {
		$summary['total'] += $secval["ap_count"];
		if($secval["SECTYPE"] == 1) { $summary['open'] = (int)$secval["ap_count"]; }
		elseif($secval["SECTYPE"] == 2) { $summary['wep'] = (int)$secval["ap_count"]; }
		elseif($secval["SECTYPE"] == 3) { $summary['secure'] = (int)$secval["ap_count"]; }
	}

	// Cell tower counts
	$sql = "SELECT type, count(cell_id) AS cell_count FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE') GROUP BY type";
	$result = $dbcore->sql->conn->query($sql);
	$result->execute();
	$celllist = $result->fetchAll(2);
	$summary['cell_total'] = 0;
	$summary['cell_types'] = array();
	foreach($celllist as $cellval) {
		$summary['cell_total'] += $cellval["cell_count"];
		$summary['cell_types'][$cellval["type"]] = (int)$cellval["cell_count"];
	}

	// Bluetooth counts
	$sql = "SELECT type, count(cell_id) AS bt_count FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE') GROUP BY type";
	$result = $dbcore->sql->conn->query($sql);
	$result->execute();
	$btlist = $result->fetchAll(2);
	$summary['bt_total'] = 0;
	$summary['bt_types'] = array();
	foreach($btlist as $btval) {
		$summary['bt_total'] += $btval["bt_count"];
		$summary['bt_types'][$btval["type"]] = (int)$btval["bt_count"];
	}

	// User count
	$sql = "SELECT count(distinct file_user) AS user_count FROM files WHERE completed = 1";
	$result = $dbcore->sql->conn->query($sql);
	$usercount = $result->fetch(2);
	$summary['users'] = (int)$usercount['user_count'];

	updateCache($dbcore, 'summary_counts', $summary);
	$dbcore->verbosed("    Summary counts cached.", 1);

	// 2. Time-series data for WiFi (this is the expensive query)
	$dbcore->verbosed("  - Generating WiFi time-series data...", 1);
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT DATE_FORMAT(fa, '%Y-%m') as month,
				COUNT(*) as new_count,
				SUM(CASE WHEN SECTYPE = 1 THEN 1 ELSE 0 END) as open_count,
				SUM(CASE WHEN SECTYPE = 2 THEN 1 ELSE 0 END) as wep_count,
				SUM(CASE WHEN SECTYPE = 3 THEN 1 ELSE 0 END) as secure_count,
				/* compute auth_open_count from AUTH (include WEP as Open for auth chart) */
				SUM(CASE WHEN SECTYPE = 1  OR SECTYPE = 2 THEN 1 ELSE 0 END) as auth_open_count,
				SUM(CASE WHEN AUTH LIKE '%OWE%' THEN 1 ELSE 0 END) as auth_owe_count,
				SUM(CASE WHEN AUTH LIKE '%WPA3%' THEN 1 ELSE 0 END) as auth_wpa3_count,
				SUM(CASE WHEN AUTH LIKE '%WPA2%' OR AUTH LIKE '%WPA2-%' THEN 1 ELSE 0 END) as auth_wpa2_count,
				SUM(CASE WHEN AUTH LIKE '%WPA%' AND AUTH NOT LIKE '%WPA2%' AND AUTH NOT LIKE '%WPA3%' THEN 1 ELSE 0 END) as auth_wpa_count
				FROM wifi_ap
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND BSSID <> '00:00:00:00:00:00'
				GROUP BY DATE_FORMAT(fa, '%Y-%m')
				ORDER BY month ASC";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT FORMAT(fa, 'yyyy-MM') as month,
			COUNT(*) as new_count,
			SUM(CASE WHEN SECTYPE = 1 THEN 1 ELSE 0 END) as open_count,
			SUM(CASE WHEN SECTYPE = 2 THEN 1 ELSE 0 END) as wep_count,
			SUM(CASE WHEN SECTYPE = 3 THEN 1 ELSE 0 END) as secure_count,
			/* compute auth_open_count from AUTH (include WEP as Open for auth chart) */
			SUM(CASE WHEN SECTYPE = 1  OR SECTYPE = 2 THEN 1 ELSE 0 END) as auth_open_count,
			SUM(CASE WHEN AUTH LIKE '%WPA3%' THEN 1 ELSE 0 END) as auth_wpa3_count,
			SUM(CASE WHEN AUTH LIKE '%WPA2%' THEN 1 ELSE 0 END) as auth_wpa2_count,
			SUM(CASE WHEN AUTH LIKE '%WPA%' AND AUTH NOT LIKE '%WPA2%' AND AUTH NOT LIKE '%WPA3%' THEN 1 ELSE 0 END) as auth_wpa_count,
			SUM(CASE WHEN AUTH LIKE '%OWE%' THEN 1 ELSE 0 END) as auth_owe_count
		FROM wifi_ap
		WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND BSSID <> '00:00:00:00:00:00'
		GROUP BY FORMAT(fa, 'yyyy-MM')
		ORDER BY month ASC";
	}
	$result = $dbcore->sql->conn->query($sql);
	$timeseries_wifi = $result->fetchAll(2);

	$wifi_graph_data = array();
	$cumulative_total = 0;
	$cumulative_open = 0;
	$cumulative_wep = 0;
	$cumulative_secure = 0;
	$cumulative_auth_open = 0;
	$cumulative_auth_owe = 0;
	$cumulative_auth_wpa = 0;
	$cumulative_auth_wpa2 = 0;
	$cumulative_auth_wpa3 = 0;

		foreach($timeseries_wifi as $row) {
		$cumulative_total += $row['new_count'];
		$cumulative_open += $row['open_count'];
		$cumulative_wep += $row['wep_count'];
		$cumulative_secure += $row['secure_count'];

			// accumulate auth counts (prefer AUTH-derived auth_open_count which includes WEP)
			$cumulative_auth_open += isset($row['auth_open_count']) ? $row['auth_open_count'] : ((isset($row['open_count']) ? $row['open_count'] : 0) + (isset($row['wep_count']) ? $row['wep_count'] : 0));
			$cumulative_auth_owe += isset($row['auth_owe_count']) ? $row['auth_owe_count'] : 0;
			$cumulative_auth_wpa3 += isset($row['auth_wpa3_count']) ? $row['auth_wpa3_count'] : 0;
			$cumulative_auth_wpa2 += isset($row['auth_wpa2_count']) ? $row['auth_wpa2_count'] : 0;
			$cumulative_auth_wpa += isset($row['auth_wpa_count']) ? $row['auth_wpa_count'] : 0;

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
			   'secure_pct' => $secure_pct,
				'auth_open_pct' => ($cumulative_total > 0) ? round(($cumulative_auth_open / $cumulative_total) * 100, 2) : 0,
				   'auth_owe_pct' => ($cumulative_total > 0) ? round(($cumulative_auth_owe / $cumulative_total) * 100, 2) : 0,
				   'auth_wpa3_pct' => ($cumulative_total > 0) ? round(($cumulative_auth_wpa3 / $cumulative_total) * 100, 2) : 0,
				   'auth_wpa2_pct' => ($cumulative_total > 0) ? round(($cumulative_auth_wpa2 / $cumulative_total) * 100, 2) : 0,
				   'auth_wpa_pct' => ($cumulative_total > 0) ? round(($cumulative_auth_wpa / $cumulative_total) * 100, 2) : 0
		   );
	}
	updateCache($dbcore, 'wifi_timeseries', $wifi_graph_data);
	$dbcore->verbosed("    WiFi time-series cached (".count($wifi_graph_data)." months).", 1);

	// 3. Time-series data for Cell towers
	$dbcore->verbosed("  - Generating Cell tower time-series data...", 1);
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT DATE_FORMAT(fa, '%Y-%m') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE')
				GROUP BY DATE_FORMAT(fa, '%Y-%m')
				ORDER BY month ASC";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT FORMAT(fa, 'yyyy-MM') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE')
				GROUP BY FORMAT(fa, 'yyyy-MM')
				ORDER BY month ASC";
	}
	$result = $dbcore->sql->conn->query($sql);
	$timeseries_cell = $result->fetchAll(2);

	$cell_graph_data = array();
	$cumulative_cell = 0;
	foreach($timeseries_cell as $row) {
		$cumulative_cell += $row['new_count'];
		$cell_graph_data[] = array(
			'month' => $row['month'],
			'new_count' => (int)$row['new_count'],
			'cumulative' => $cumulative_cell
		);
	}
	updateCache($dbcore, 'cell_timeseries', $cell_graph_data);
	$dbcore->verbosed("    Cell time-series cached (".count($cell_graph_data)." months).", 1);

	// 4. Time-series data for Bluetooth
	$dbcore->verbosed("  - Generating Bluetooth time-series data...", 1);
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT DATE_FORMAT(fa, '%Y-%m') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE')
				GROUP BY DATE_FORMAT(fa, '%Y-%m')
				ORDER BY month ASC";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT FORMAT(fa, 'yyyy-MM') as month, COUNT(*) as new_count
				FROM cell_id
				WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE')
				GROUP BY FORMAT(fa, 'yyyy-MM')
				ORDER BY month ASC";
	}
	$result = $dbcore->sql->conn->query($sql);
	$timeseries_bt = $result->fetchAll(2);

	$bt_graph_data = array();
	$cumulative_bt = 0;
	foreach($timeseries_bt as $row) {
		$cumulative_bt += $row['new_count'];
		$bt_graph_data[] = array(
			'month' => $row['month'],
			'new_count' => (int)$row['new_count'],
			'cumulative' => $cumulative_bt
		);
	}
	updateCache($dbcore, 'bt_timeseries', $bt_graph_data);
	$dbcore->verbosed("    Bluetooth time-series cached (".count($bt_graph_data)." months).", 1);

	// 5. Top WiFi APs (by points)
	$dbcore->verbosed("  - Generating top WiFi APs...", 1);
	$top_n = 100; // Cache top 100, page can limit display
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT AP_ID, SSID, BSSID, AUTH, ENCR, points, HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' ORDER BY points DESC LIMIT ?";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT TOP (?) AP_ID, SSID, BSSID, AUTH, ENCR, points, HighGps_ID FROM wifi_ap WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' ORDER BY points DESC";
	}
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->bindParam(1, $top_n, PDO::PARAM_INT);
	$prep->execute();
	$top_wifi_list = $prep->fetchAll(2);

	$top_wifi = array();
	foreach($top_wifi_list as $idx => $wifi) {
		$top_wifi[$idx] = array(
			'id' => $wifi['AP_ID'],
			'ssid' => $wifi['SSID'],
			'bssid' => $wifi['BSSID'],
			'auth' => $wifi['AUTH'],
			'encr' => $wifi['ENCR'],
			'points' => (int)$wifi['points'],
			'validgps' => ($wifi['HighGps_ID'] != "") ? 1 : 0
		);
	}
	updateCache($dbcore, 'top_wifi', $top_wifi);
	$dbcore->verbosed("    Top WiFi cached (".count($top_wifi)." APs).", 1);

	// 6. Top Cell towers
	$dbcore->verbosed("  - Generating top Cell towers...", 1);
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE') ORDER BY points DESC LIMIT ?";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT TOP (?) cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type NOT IN ('BT','BLE') ORDER BY points DESC";
	}
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->bindParam(1, $top_n, PDO::PARAM_INT);
	$prep->execute();
	$top_cell_list = $prep->fetchAll(2);

	$top_cell = array();
	foreach($top_cell_list as $idx => $cell) {
		$top_cell[$idx] = array(
			'id' => $cell['cell_id'],
			'ssid' => $cell['ssid'],
			'mac' => $cell['mac'],
			'type' => $cell['type'],
			'points' => (int)$cell['points'],
			'validgps' => ($cell['highgps_id'] != "") ? 1 : 0
		);
	}
	updateCache($dbcore, 'top_cell', $top_cell);
	$dbcore->verbosed("    Top Cell cached (".count($top_cell)." towers).", 1);

	// 7. Top Bluetooth devices
	$dbcore->verbosed("  - Generating top Bluetooth devices...", 1);
	if($dbcore->sql->service == "mysql") {
		$sql = "SELECT cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE') ORDER BY points DESC LIMIT ?";
	} else if($dbcore->sql->service == "sqlsrv") {
		$sql = "SELECT TOP (?) cell_id, ssid, mac, type, points, highgps_id FROM cell_id WHERE fa IS NOT NULL AND fa NOT LIKE '1970-01-01%' AND type IN ('BT','BLE') ORDER BY points DESC";
	}
	$prep = $dbcore->sql->conn->prepare($sql);
	$prep->bindParam(1, $top_n, PDO::PARAM_INT);
	$prep->execute();
	$top_bt_list = $prep->fetchAll(2);

	$top_bt = array();
	foreach($top_bt_list as $idx => $bt) {
		$top_bt[$idx] = array(
			'id' => $bt['cell_id'],
			'ssid' => $bt['ssid'],
			'mac' => $bt['mac'],
			'type' => $bt['type'],
			'points' => (int)$bt['points'],
			'validgps' => ($bt['highgps_id'] != "") ? 1 : 0
		);
	}
	updateCache($dbcore, 'top_bt', $top_bt);
	$dbcore->verbosed("    Top Bluetooth cached (".count($top_bt)." devices).", 1);

	$elapsed = round(microtime(true) - $start_time, 2);
	$dbcore->verbosed("Statistics cache generation complete in {$elapsed} seconds.", 1);

	return true;
}

While(1)
{
	# Safely kill script if Daemon kill flag has been set
	if($dbcore->checkDaemonKill($job_id))
	{
		$dbcore->verbosed("The flag to kill the daemon is set. unset it to run this daemon.");
		if(!$dbcore->ForceDaemonRun){$dbcore->SetNextJob($job_id);}
		unlink($dbcore->pid_file);
		echo "Daemon was told to kill itself\n";
		exit(-7);
	}

	# Generate and cache statistics
	generateStatsCache($dbcore);

	if($dbcore->daemonize)
	{
		$sleep_time = $dbcore->job_interval > 0 ? $dbcore->job_interval : 300;
		$dbcore->verbosed("Sleeping for $sleep_time seconds.", 1);
		sleep($sleep_time);
	}
	else
	{
		$dbcore->verbosed("Not set to run as a daemon, exiting.");
		$dbcore->return_message = 0;
		break;
	}
}
if(!$dbcore->ForceDaemonRun)
{
	#Finished Job
	$dbcore->verbosed("Finished - Id:".$job_id, 1);
	#Set Next Run Job to Waiting
	$dbcore->SetNextJob($job_id);
	$dbcore->verbosed("Next Job Schedule Set for: ".$dbcore->daemon_name , 1);
}
unlink($dbcore->pid_file);
exit($dbcore->return_message);
