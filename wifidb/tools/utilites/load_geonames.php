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
people). Each line updates the row with its key (a place's geonameid, a region's code, a country's ISO code) or adds
it, so loading again brings the tables up to date while the site goes on using them; once the whole file is in, the
rows it doesn't have (what GeoNames has since removed) are taken out, by the time each row was last loaded (loaded_at).
So a table ends up as the file has it: a --set of towns leaves only those towns. --keep leaves those rows instead
(adding to what's there); --replace empties each table first. A zip is read without unpacking it, the file a line at a
time (allCountries.txt is over a gigabyte), and a value longer than its column is cut to fit.
*/
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "cli");

const GEONAMES_URL = 'https://download.geonames.org/export/dump/';
const GEONAMES_SETS = array('allCountries', 'cities500', 'cities1000', 'cities5000', 'cities15000');

# table => the table's columns, in the order the file's tab-separated fields come (the first is each row's key), and the
# file --download fetches
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

$loads = array(); $file = ''; $replace = false; $keep = false; $download = false; $dir = ''; $set = 'allCountries';
foreach(array_slice($argv, 1) as $arg)
{
	if(isset($layouts[ltrim($arg, '-')])) { $loads[] = ltrim($arg, '-'); }
	elseif($arg === '--all') { $loads = array_keys($layouts); }
	elseif(strpos($arg, '--file=') === 0) { $file = substr($arg, 7); }
	elseif(strpos($arg, '--dir=') === 0) { $dir = rtrim(substr($arg, 6), '/'); }
	elseif(strpos($arg, '--set=') === 0) { $set = substr($arg, 6); }
	elseif($arg === '--download') { $download = true; }
	elseif($arg === '--replace') { $replace = true; }
	elseif($arg === '--keep') { $keep = true; }
}
$loads = array_values(array_unique($loads));
if(!$loads || ($download === ($file !== '')) || ($file !== '' && count($loads) > 1) || !in_array($set, GEONAMES_SETS, true))
{
	fwrite(STDERR, "Usage: php load_geonames.php --all --download [--replace|--keep] [--set=SET] [--dir=DIR]\n"
		."       php load_geonames.php --geonames|--admin1|--admin2|--countrynames (--download | --file=FILE) [--replace|--keep] [--set=SET]\n"
		."--download fetches the files from ".GEONAMES_URL." into --dir; --file loads one you have (a .txt, or a .zip with it inside).\n"
		."Each table ends up as the file has it; --keep leaves the rows the file doesn't have, --replace empties the table first.\n"
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

	# each line updates the row with its key (the first column), or adds it (the keys: sql/migrations/*/0011_geonames_keys.sql),
	# and stamps it with when this load started, so what the file no longer has is what's left with an older stamp
	$key = $columns[0];
	$stamp = gmdate('Y-m-d H:i:s');
	# a place's position also as numbers, for finding the nearest (lib/geonames.inc.php, migration 0013_geonames_coords)
	$coords = ($load === 'geonames') ? array(array_search('latitude', $columns), array_search('longitude', $columns)) : null;
	$written = array_merge($columns, $coords ? array('lat', 'lon') : array(), array('loaded_at'));
	# several rows to a statement, which is what makes a load of millions take minutes rather than hours: an upsert on
	# MariaDB and PostgreSQL, a MERGE on SQL Server (which takes at most 2,100 parameters to a statement)
	$cols_sql = implode(", ", array_map($quote, $written));
	$row_sql = "(".implode(", ", array_fill(0, count($written), "?")).")";
	$others = array_slice($written, 1);
	$statement = function ($n) use ($service, $quote, $table, $key, $written, $others, $cols_sql, $row_sql) {
		$rows = implode(", ", array_fill(0, $n, $row_sql));
		if($service == "mysql")
		{
			return "INSERT INTO ".$quote($table)." ($cols_sql) VALUES $rows ON DUPLICATE KEY UPDATE "
				.implode(", ", array_map(function ($c) use ($quote) { return $quote($c)." = VALUES(".$quote($c).")"; }, $others));
		}
		if($service == "pgsql")
		{
			return "INSERT INTO ".$quote($table)." ($cols_sql) VALUES $rows ON CONFLICT (".$quote($key).") DO UPDATE SET "
				.implode(", ", array_map(function ($c) use ($quote) { return $quote($c)." = EXCLUDED.".$quote($c); }, $others));
		}
		return "MERGE INTO ".$quote($table)." AS t USING (VALUES $rows) AS s ($cols_sql) ON t.".$quote($key)." = s.".$quote($key)
			." WHEN MATCHED THEN UPDATE SET ".implode(", ", array_map(function ($c) use ($quote) { return $quote($c)." = s.".$quote($c); }, $others))
			." WHEN NOT MATCHED THEN INSERT ($cols_sql) VALUES (".implode(", ", array_map(function ($c) use ($quote) { return "s.".$quote($c); }, $written))."));";
	};
	$per = ($service == "sqlsrv") ? max(1, intdiv(2000, count($written))) : 500;
	$prepared = array();
	# saves rows, inside a savepoint on PostgreSQL, which otherwise gives up on the whole transaction after one failure
	$save = function (array $rows) use ($conn, $service, $statement, &$prepared) {
		$n = count($rows);
		if(!isset($prepared[$n])) { $prepared[$n] = $conn->prepare($statement($n)); }
		if($service == "pgsql") { $conn->exec("SAVEPOINT geobatch"); }
		try
		{
			$prepared[$n]->execute(array_merge(...array_values($rows)));
			if($service == "pgsql") { $conn->exec("RELEASE SAVEPOINT geobatch"); }
		}
		catch(PDOException $e)
		{
			if($service == "pgsql") { $conn->exec("ROLLBACK TO SAVEPOINT geobatch"); }
			throw $e;
		}
	};
	$loaded = 0; $skipped = 0; $failed = 0;
	# a batch that can't be saved is tried a row at a time, so one bad line is all that's left out
	$flush = function (array &$batch) use ($conn, $save, &$loaded, &$failed) {
		if(!$batch) { return; }
		try { $save($batch); $loaded += count($batch); }
		catch(PDOException $e)
		{
			foreach($batch as $row)
			{
				try { $save(array($row)); $loaded++; }
				catch(PDOException $e) { $failed++; fwrite(STDERR, "Skipped a line: ".$e->getMessage()."\n"); }
			}
		}
		$batch = array();
		$conn->commit(); $conn->beginTransaction();
		echo "\r$loaded";
	};
	$batch = array();
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
		if($values[0] === null || $values[0] === '') { $skipped++; continue; }   # no key: nothing to find it by again
		if($coords)
		{
			foreach($coords as $i) { $values[] = is_numeric($fields[$i] ?? '') ? $fields[$i] : null; }
		}
		$values[] = $stamp;
		# a key twice in a batch would be the same row twice in one statement, which PostgreSQL and SQL Server refuse:
		# the later line wins, as it would a line at a time
		$batch["k".$values[0]] = $values;
		if(count($batch) >= $per) { $flush($batch); }
	}
	$flush($batch);
	$conn->commit();
	fclose($in);
	echo "\rLoaded $loaded rows into $table".($skipped + $failed ? ", skipped ".($skipped + $failed) : "").".\n";

	# what this file no longer has: the rows this load didn't stamp. Only after the whole file went in, since a line that
	# failed would take its row with it.
	if($replace || $keep) { continue; }
	if($failed)
	{
		echo "Not removing what $table had and this file doesn't: $failed lines couldn't be saved. Load it again once they can.\n";
		continue;
	}
	$removed = $conn->prepare("DELETE FROM ".$quote($table)." WHERE ".$quote('loaded_at')." < ? OR ".$quote('loaded_at')." IS NULL");
	$removed->execute(array($stamp));
	if($removed->rowCount() > 0) { echo "Removed ".$removed->rowCount()." rows from $table that this file no longer has.\n"; }
}
