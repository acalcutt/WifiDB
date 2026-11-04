<?php
/*
load_geonames.php: loads a GeoNames dump file (https://download.geonames.org/export/dump/) into its table, for the
"places nearby" on the access point page. MariaDB, SQL Server and PostgreSQL.

  php load_geonames.php --all --download [--replace] [--set=cities500] [--dir=DIR]   (everything, fetched for you)
  php load_geonames.php --geonames     --download [--replace] [--set=cities500]       (one table, fetched for you)
  php load_geonames.php --geonames     --file=allCountries.zip        [--replace]   (or the .txt inside it)
  php load_geonames.php --admin1       --file=admin1CodesASCII.txt    [--replace]
  php load_geonames.php --admin2       --file=admin2Codes.txt         [--replace]
  php load_geonames.php --countrynames --file=countryInfo.txt         [--replace]

--download fetches each file from download.geonames.org into --dir (default: /srv/wifidb-data/geonames on a Proxmox
container, else the temporary folder), and uses one already there instead of fetching it again (delete it for a new
one). --set picks the places for --geonames: allCountries (the default: every place, about 12 million, a 400 MB download)
or one of GeoNames' smaller lists, cities500, cities1000, cities5000 or cities15000 (the towns with at least that many
people). --replace empties each table first. A zip is read without unpacking it, the file a line at a time
(allCountries.txt is over a gigabyte), and a value longer than its column is cut to fit.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

const GEONAMES_URL = 'https://download.geonames.org/export/dump/';
const GEONAMES_SETS = array('allCountries', 'cities500', 'cities1000', 'cities5000', 'cities15000');

# table => the table's columns, in the order the file's tab-separated fields come, and the file --download fetches
$layouts = array(
	"countrynames" => array("geonames_country_names", array("ISO", "ISO3", "ISO-Numeric", "fips", "Country", "Capital", "Area", "Population",
		"Continent", "tld", "CurrencyCode", "CurrencyName", "Phone", "Postal Code Format", "Postal Code Regex", "Languages", "geonamesid",
		"neighbors", "EquivalentFipsCode"), "countryInfo.txt"),
	"admin1" => array("geonames_admin1", array("admin1", "name", "asciiname", "geonameid"), "admin1CodesASCII.txt"),
	"admin2" => array("geonames_admin2", array("admin2", "name", "asciiname", "geonameid"), "admin2Codes.txt"),
	"geonames" => array("geonames", array("geonameid", "name", "asciiname", "alternatenames", "latitude", "longitude", "feature_class",
		"feature_code", "country_code", "cc2", "admin1_code", "admin2_code", "admin3_code", "admin4_code", "population", "elevation",
		"gtopo30", "timezone", "mod_date"), null),   # the set's zip
);

$loads = array(); $file = ''; $replace = false; $download = false; $dir = ''; $set = 'allCountries';
foreach(array_slice($argv, 1) as $arg)
{
	if(isset($layouts[ltrim($arg, '-')])) { $loads[] = ltrim($arg, '-'); }
	elseif($arg === '--all') { $loads = array_keys($layouts); }
	elseif(strpos($arg, '--file=') === 0) { $file = substr($arg, 7); }
	elseif(strpos($arg, '--dir=') === 0) { $dir = rtrim(substr($arg, 6), '/'); }
	elseif(strpos($arg, '--set=') === 0) { $set = substr($arg, 6); }
	elseif($arg === '--download') { $download = true; }
	elseif($arg === '--replace') { $replace = true; }
}
$loads = array_values(array_unique($loads));
if(!$loads || ($download === ($file !== '')) || ($file !== '' && count($loads) > 1) || !in_array($set, GEONAMES_SETS, true))
{
	fwrite(STDERR, "Usage: php load_geonames.php --all --download [--replace] [--set=SET] [--dir=DIR]\n"
		."       php load_geonames.php --geonames|--admin1|--admin2|--countrynames (--download | --file=FILE) [--replace] [--set=SET]\n"
		."--download fetches the files from ".GEONAMES_URL." into --dir; --file loads one you have (a .txt, or a .zip with it inside).\n"
		."SET is what --geonames loads: ".implode(', ', GEONAMES_SETS)." (allCountries, every place, is the default).\n");
	exit(2);
}

/** where --download keeps the files: --dir, else the data disk of a Proxmox container, else the temporary folder */
function geonames_dir($dir)
{
	if($dir === '') { $dir = is_dir('/srv/wifidb-data') && is_writable('/srv/wifidb-data') ? '/srv/wifidb-data/geonames' : sys_get_temp_dir().'/wifidb-geonames'; }
	if(!is_dir($dir) && !@mkdir($dir, 0755, true)) { fwrite(STDERR, "Can't make $dir\n"); exit(1); }
	return $dir;
}

/** fetches GEONAMES_URL.$name into $dir, unless it's there already. @return string the file */
function geonames_download($name, $dir)
{
	$path = $dir.'/'.$name;
	if(is_file($path) && filesize($path) > 0) { echo "Using $path, downloaded before (delete it for a new one).\n"; return $path; }
	echo "Downloading ".GEONAMES_URL."$name to $path\n";
	$part = $path.'.part';
	$out = @fopen($part, 'wb');
	if(!$out) { fwrite(STDERR, "Can't write $part\n"); exit(1); }
	$ch = curl_init(GEONAMES_URL.$name);
	curl_setopt_array($ch, array(CURLOPT_FILE => $out, CURLOPT_FOLLOWLOCATION => true, CURLOPT_FAILONERROR => true,
		CURLOPT_CONNECTTIMEOUT => 30, CURLOPT_LOW_SPEED_LIMIT => 1024, CURLOPT_LOW_SPEED_TIME => 120,
		CURLOPT_USERAGENT => 'WiFiDB load_geonames.php', CURLOPT_NOPROGRESS => false,
		CURLOPT_XFERINFOFUNCTION => function ($ch, $total, $done) {
			static $last = 0;
			if($total > 0 && time() !== $last) { $last = time(); echo "\r".round($done / 1048576)." of ".round($total / 1048576)." MB"; }
			return 0;
		}));
	$ok = curl_exec($ch);
	$error = curl_error($ch);
	fclose($out);
	echo "\r";
	if(!$ok) { @unlink($part); fwrite(STDERR, "Couldn't download $name: $error\n"); exit(1); }
	rename($part, $path);
	echo "Downloaded $name (".round(filesize($path) / 1048576, 1)." MB).\n";
	return $path;
}

/** a file to read lines from: a .txt as it is, a zip's .txt (the one named for the zip) without unpacking it */
function geonames_open($file)
{
	if(strtolower(pathinfo($file, PATHINFO_EXTENSION)) === 'zip')
	{
		$zip = new ZipArchive();
		if($zip->open($file) !== true) { fwrite(STDERR, "Can't open $file as a zip\n"); exit(1); }
		$inner = pathinfo($file, PATHINFO_FILENAME).'.txt';
		if($zip->locateName($inner) === false)
		{
			for($i = 0, $inner = null; $i < $zip->numFiles && $inner === null; $i++)
			{
				$n = $zip->getNameIndex($i);
				if(substr($n, -4) === '.txt' && stripos($n, 'readme') === false) { $inner = $n; }
			}
		}
		$zip->close();
		$in = $inner === null ? false : @fopen('zip://'.realpath($file).'#'.$inner, 'r');
		if(!$in) { fwrite(STDERR, "Can't find the data in $file\n"); exit(1); }
		return $in;
	}
	$in = @fopen($file, 'r');
	if(!$in) { fwrite(STDERR, "Can't read $file\n"); exit(1); }
	return $in;
}

# the files first, so a download that fails leaves the tables as they were
$files = array();
if($download)
{
	$dir = geonames_dir($dir);
	foreach($loads as $load) { $files[$load] = geonames_download($layouts[$load][2] ?? $set.'.zip', $dir); }
}
else
{
	$files[$loads[0]] = $file;
}
$inputs = array();
foreach($files as $load => $path) { $inputs[$load] = geonames_open($path); }

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$conn = $dbcore->sql->conn;
$service = $dbcore->sql->service;
# some of these names have spaces and hyphens in them, so they're quoted on every database
$quote = function ($name) use ($service) {
	if($service == "mysql") { return '`'.$name.'`'; }
	if($service == "sqlsrv") { return '['.$name.']'; }
	return '"'.$name.'"';
};
$schema = $service == "mysql" ? "DATABASE()" : ($service == "pgsql" ? "current_schema()" : "'dbo'");

foreach($inputs as $load => $in)
{
	list($table, $columns) = $layouts[$load];
	# how long each column may be, from the database itself
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
}
