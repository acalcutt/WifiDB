<?php
/*
update_positions.php: works out where each access point (and, with --cells, each cell tower and Bluetooth device) most
likely is, from all its readings (lib/position.inc.php), and stores it in est_lat, est_lon, est_alt, est_accuracy and
est_points: what the maps, tiles and exports show. Imports keep them up to date; this is for what was already there
(migration 0016_positions filled them from the strongest reading) and for after the calculation changes.

  php update_positions.php [--cells] [--all] [--from ID] [--to ID]

  --cells     cell towers and Bluetooth devices (cell_id) instead of access points
  --all       every one, again; without it, only those not worked out yet (est_points empty)
  --from/--to an id range, to split a large database between runs

It goes through the table in batches of ids and can be stopped and run again: without --all it picks up where it left
off. Run it with nothing else importing, or let the imports run: each row is worked out from its readings at the time.
MariaDB, SQL Server and PostgreSQL.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";
require_once $daemon_config['wifidb_install']."/lib/position.inc.php";

$opt = function ($flag) use ($argv) { $i = array_search($flag, $argv, true); return ($i !== false && isset($argv[$i + 1])) ? (int)$argv[$i + 1] : null; };
$cells = in_array('--cells', $argv, true);
$all = in_array('--all', $argv, true);
$from = $opt('--from');
$to = $opt('--to');

$position = new ApPosition($dbcore->sql->conn, $dbcore->sql->service);
$table = $cells ? 'cell_id' : 'wifi_ap';
$key = $cells ? 'cell_id' : $dbcore->sql->ident('AP_ID');

# ids in batches, each after the last of the one before (SQL Server can refuse a result left open while others run)
$batch = 1000;
$list = $dbcore->sql->conn->prepare("SELECT $key AS id FROM $table WHERE $key > ?".($to !== null ? " AND $key <= ?" : "")
	.($all ? "" : " AND est_points IS NULL")." ORDER BY $key ASC ".$dbcore->sql->limit(0, $batch));
$last = ($from !== null) ? $from - 1 : 0;
$checked = 0; $placed = 0; $none = 0;
$started = time();
while(true)
{
	$list->execute($to !== null ? array($last, $to) : array($last));
	$ids = $list->fetchAll(PDO::FETCH_COLUMN);
	if(!$ids) { break; }
	foreach($ids as $id)
	{
		$last = (int)$id;
		$checked++;
		if($position->Update((int)$id, $cells) === null) { $none++; } else { $placed++; }
	}
	echo ($cells ? "Cell" : "AP")." ".$last.": ".$checked." checked, ".$placed." placed, ".$none." with no GPS (".(time() - $started)."s)\r\n";
}
echo "Done: ".$checked." ".($cells ? "cell towers and Bluetooth devices" : "access points").", ".$placed." placed, ".$none." with no reading that has a GPS fix.\r\n";
