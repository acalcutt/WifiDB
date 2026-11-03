<?php
/*
update_high_gps.php: works out each access point's summary columns again from its readings, the way the importer
keeps them (import.inc.php, UpdateHighPoints):
  fa, la             the first and last reading
  high_rssi, high_sig, points
  HighGps_ID, high_gps_rssi, high_gps_sig   the best reading with a GPS fix: strongest RSSI, then newest, then the
                                            most satellites, then the most accurate
MariaDB, SQL Server and PostgreSQL. An older version kept pointers to readings (FirstHist_ID, HighRSSI_ID, ...), which
wifi_ap no longer has.

  php update_high_gps.php [--from AP_ID] [--to AP_ID]
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$opt = function ($flag) use ($argv) { $i = array_search($flag, $argv, true); return ($i !== false && isset($argv[$i + 1])) ? (int)$argv[$i + 1] : null; };
$from = $opt('--from');
$to = $opt('--to');
$q = function ($name) use ($dbcore) { return $dbcore->sql->ident($name); };   # mixed-case names, quoted on PostgreSQL
$ap = $q('AP_ID');

# read in batches, each after the last id of the one before: a result left open while the others run is something SQL
# Server can refuse, and production has millions of access points
$batch = 5000;
$list = $dbcore->sql->conn->prepare("SELECT $ap, fa, la, high_rssi, high_sig, points, ".$q('HighGps_ID').", high_gps_rssi, high_gps_sig FROM wifi_ap"
	." WHERE $ap > ?".($to !== null ? " AND $ap <= ?" : "")." ORDER BY $ap ASC ".$dbcore->sql->limit(0, $batch));
$last = ($from !== null) ? $from - 1 : 0;
$rows = array();
$next = function () use ($list, &$last, &$rows, $to) {
	if(!$rows)
	{
		$list->execute($to !== null ? array($last, $to) : array($last));
		$rows = $list->fetchAll(PDO::FETCH_ASSOC);
		if(!$rows) { return false; }
	}
	$row = array_shift($rows);
	$last = (int)$row['AP_ID'];
	return $row;
};

$totals = $dbcore->sql->conn->prepare("SELECT MIN(".$q('Hist_Date').") AS fa, MAX(".$q('Hist_Date').") AS la, MAX(".$q('RSSI').") AS high_rssi,"
	." MAX(".$q('Sig').") AS high_sig, COUNT(*) AS points FROM wifi_hist WHERE $ap = ? AND ".$q('Hist_Date')." IS NOT NULL");
$best = $dbcore->sql->conn->prepare("SELECT h.".$q('GPS_ID')." AS gps_id, h.".$q('RSSI')." AS rssi, h.".$q('Sig')." AS sig"
	." FROM wifi_hist h INNER JOIN wifi_gps g ON h.".$q('GPS_ID')." = g.".$q('GPS_ID')
	." WHERE h.$ap = ? AND h.".$q('Hist_Date')." IS NOT NULL AND g.".$q('Lat')." != '0.0000'"
	." ORDER BY h.".$q('RSSI')." DESC, h.".$q('Hist_Date')." DESC, g.".$q('NumOfSats')." DESC, g.".$q('AccuracyMeters')." ASC, h.".$q('Hist_ID')." ASC "
	.$dbcore->sql->limit(0, 1));
$update = $dbcore->sql->conn->prepare("UPDATE wifi_ap SET fa = ?, la = ?, high_rssi = ?, high_sig = ?, points = ?, ".$q('HighGps_ID')." = ?,"
	." high_gps_rssi = ?, high_gps_sig = ? WHERE $ap = ?");

$checked = 0; $changed = 0;
while($row = $next())
{
	$id = $row['AP_ID'];
	$checked++;
	$totals->execute(array($id));
	$t = $totals->fetch(PDO::FETCH_ASSOC);
	if(!$t || !(int)$t['points']) { continue; }   # no readings: nothing to work it out from
	$best->execute(array($id));
	$g = $best->fetch(PDO::FETCH_ASSOC) ?: array('gps_id' => null, 'rssi' => null, 'sig' => null);
	$best->closeCursor();
	$new = array($t['fa'], $t['la'], $t['high_rssi'], $t['high_sig'], $t['points'], $g['gps_id'], $g['rssi'], $g['sig']);
	$old = array($row['fa'], $row['la'], $row['high_rssi'], $row['high_sig'], $row['points'], $row['HighGps_ID'], $row['high_gps_rssi'], $row['high_gps_sig']);
	# dates come back with or without fractions depending on the database: compare the seconds only
	$same = function ($a, $b) { return substr((string)$a, 0, 19) === substr((string)$b, 0, 19); };
	if(count(array_filter(array_map($same, $new, $old))) === count($new)) { continue; }
	$update->execute(array_merge($new, array($id)));
	$changed++;
	echo "AP $id: updated (best GPS point ".($g['gps_id'] === null ? 'none' : $g['gps_id']).")\r\n";
}
echo "Checked $checked access points, updated $changed.\r\n";
