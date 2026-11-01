<?php
/*
load_geonames.php: loads a GeoNames dump file (https://download.geonames.org/export/dump/) into its table, for the
"places nearby" on the access point page. MariaDB, SQL Server and PostgreSQL.

  php load_geonames.php --geonames     --file=allCountries.txt        [--replace]   (unzip allCountries.zip first)
  php load_geonames.php --admin1       --file=admin1CodesASCII.txt    [--replace]
  php load_geonames.php --admin2       --file=admin2Codes.txt         [--replace]
  php load_geonames.php --countrynames --file=countryInfo.txt         [--replace]

--replace empties the table first. The file is read a line at a time (allCountries.txt is over a gigabyte), and a value
longer than its column is cut to fit.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

# table => the table's columns, in the order the file's tab-separated fields come
$layouts = array(
	"geonames" => array("geonames", array("geonameid", "name", "asciiname", "alternatenames", "latitude", "longitude", "feature_class",
		"feature_code", "country_code", "cc2", "admin1_code", "admin2_code", "admin3_code", "admin4_code", "population", "elevation",
		"gtopo30", "timezone", "mod_date")),
	"admin1" => array("geonames_admin1", array("admin1", "name", "asciiname", "geonameid")),
	"admin2" => array("geonames_admin2", array("admin2", "name", "asciiname", "geonameid")),
	"countrynames" => array("geonames_country_names", array("ISO", "ISO3", "ISO-Numeric", "fips", "Country", "Capital", "Area", "Population",
		"Continent", "tld", "CurrencyCode", "CurrencyName", "Phone", "Postal Code Format", "Postal Code Regex", "Languages", "geonamesid",
		"neighbors", "EquivalentFipsCode")),
);

$load = ''; $file = ''; $replace = false;
foreach(array_slice($argv, 1) as $arg)
{
	if(isset($layouts[ltrim($arg, '-')])) { $load = ltrim($arg, '-'); }
	elseif(strpos($arg, '--file=') === 0) { $file = substr($arg, 7); }
	elseif($arg === '--replace') { $replace = true; }
}
if($load === '' || $file === '')
{
	fwrite(STDERR, "Usage: php load_geonames.php --geonames|--admin1|--admin2|--countrynames --file=FILE [--replace]\n"
		."Get the files from https://download.geonames.org/export/dump/\n");
	exit(2);
}
$in = @fopen($file, 'r');
if(!$in) { fwrite(STDERR, "Can't read $file\n"); exit(1); }

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

list($table, $columns) = $layouts[$load];
$conn = $dbcore->sql->conn;
$service = $dbcore->sql->service;
# some of these names have spaces and hyphens in them, so they're quoted on every database
$quote = function ($name) use ($service) {
	if($service == "mysql") { return '`'.$name.'`'; }
	if($service == "sqlsrv") { return '['.$name.']'; }
	return '"'.$name.'"';
};

# how long each column may be, from the database itself
$schema = $service == "mysql" ? "DATABASE()" : ($service == "pgsql" ? "current_schema()" : "'dbo'");
$prep = $conn->prepare("SELECT COLUMN_NAME, CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_NAME = ? AND TABLE_SCHEMA = $schema");
$prep->execute(array($table));
$lengths = array();
foreach($prep->fetchAll(PDO::FETCH_NUM) as $c) { if($c[1] !== null && (int)$c[1] > 0) { $lengths[$c[0]] = (int)$c[1]; } }

if($replace)
{
	$conn->exec("DELETE FROM ".$quote($table));
	echo "Emptied $table.\n";
}

$insert = $conn->prepare("INSERT INTO ".$quote($table)." (".implode(", ", array_map($quote, $columns)).") VALUES (".implode(", ", array_fill(0, count($columns), "?")).")");
$loaded = 0; $skipped = 0;
$conn->beginTransaction();
while(($line = fgets($in)) !== false)
{
	$line = rtrim($line, "\r\n");
	if($line === '' || $line[0] === '#') { continue; }   # countryInfo.txt starts with comment lines
	$fields = explode("\t", $line);
	if(count($fields) < 2) { $skipped++; continue; }
	$values = array();
	foreach($columns as $i => $column)
	{
		$v = isset($fields[$i]) ? $fields[$i] : '';
		if(isset($lengths[$column]) && mb_strlen($v, 'UTF-8') > $lengths[$column]) { $v = mb_substr($v, 0, $lengths[$column], 'UTF-8'); }
		$values[] = ($v === '' && in_array($column, array('geonameid', 'geonamesid'), true)) ? null : $v;
	}
	# PostgreSQL gives up on the whole transaction after one failed statement; a savepoint keeps it to the one line
	if($service == "pgsql") { $conn->exec("SAVEPOINT geoline"); }
	try
	{
		$insert->execute($values);
		$loaded++;
		if($service == "pgsql") { $conn->exec("RELEASE SAVEPOINT geoline"); }
	}
	catch(PDOException $e)
	{
		if($service == "pgsql") { $conn->exec("ROLLBACK TO SAVEPOINT geoline"); }
		$skipped++;
		fwrite(STDERR, "Skipped a line: ".$e->getMessage()."\n");
	}
	if($loaded % 1000 === 0) { $conn->commit(); $conn->beginTransaction(); echo "\r$loaded"; }
}
$conn->commit();
fclose($in);
echo "\rLoaded $loaded rows into $table".($skipped ? ", skipped $skipped" : "").".\n";
