<?php
/*
fix_dates.php: repairs access points' first and last active dates (wifi_ap.fa, la). MariaDB, SQL Server and PostgreSQL.

The old version rewrote dates stored as text in the wrong order (MM-DD-YYYY); the columns are real dates now, so that
can't happen any more. What can: a date left empty, and on MariaDB the zero date 0000-00-00. So this
  1. turns zero dates into empty ones (MariaDB only: the others can't hold them),
  2. fills an empty first or last active date from the access point's own readings (the earliest and latest),
  3. and where there are no readings, copies one into the other, as the old version did.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$q = function ($name) use ($dbcore) { return $dbcore->sql->ident($name); };   # mixed-case names, quoted on PostgreSQL
$run = function ($label, $sql) use ($dbcore) {
	$n = $dbcore->sql->conn->exec($sql);
	$dbcore->verbosed($label.": ".(int)$n);
};

if($dbcore->sql->service == "mysql")
{
	$run("wifi_ap first active zero dates emptied", "UPDATE wifi_ap SET fa = NULL WHERE fa = '0000-00-00 00:00:00'");
	$run("wifi_ap last active zero dates emptied", "UPDATE wifi_ap SET la = NULL WHERE la = '0000-00-00 00:00:00'");
	$run("wifi_gps zero dates emptied", "UPDATE wifi_gps SET GPS_Date = NULL WHERE GPS_Date = '0000-00-00 00:00:00'");
}

$ap = $q('AP_ID');
$date = $q('Hist_Date');
$run("empty first active dates filled from the readings",
	"UPDATE wifi_ap SET fa = (SELECT MIN(h.$date) FROM wifi_hist h WHERE h.$ap = wifi_ap.$ap) WHERE fa IS NULL");
$run("empty last active dates filled from the readings",
	"UPDATE wifi_ap SET la = (SELECT MAX(h.$date) FROM wifi_hist h WHERE h.$ap = wifi_ap.$ap) WHERE la IS NULL");
$run("still-empty first active dates set to the last active", "UPDATE wifi_ap SET fa = la WHERE fa IS NULL AND la IS NOT NULL");
$run("still-empty last active dates set to the first active", "UPDATE wifi_ap SET la = fa WHERE la IS NULL AND fa IS NOT NULL");
