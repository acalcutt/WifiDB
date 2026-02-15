#!/usr/bin/php
<?php
/*
 update_cell_carriers.php
 Reads a Wigle mccMnc JSON file and updates/inserts rows into cell_carriers.
 Usage: php update_cell_carriers.php /path/to/mccMnc.json
 */

define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your [tools]/daemon.config.inc.php\n");}
if($daemon_config['wifidb_install'] === ""){die("You need to edit your daemon config file first in: [tools dir]/daemon.config.inc.php\n");}

require $daemon_config['wifidb_install'].'/lib/init.inc.php';

function get_val($rec, $keys, $default = null)
{
    foreach($keys as $k) {
        if(isset($rec[$k]) && $rec[$k] !== '') return $rec[$k];
    }
    return $default;
}

$argv0 = isset($argv[0]) ? $argv[0] : 'update_cell_carriers.php';

// CLI usage:
// php update_cell_carriers.php [local.json | https://api.wigle.net/api/v2/cell/mccMnc] [--user USER --pass PASS]

$input = isset($argv[1]) ? $argv[1] : '';
$cli_user = null; $cli_pass = null;
for($i=2;$i<count($argv);$i++){
    if($argv[$i] === '--user' && isset($argv[$i+1])){ $cli_user = $argv[$i+1]; $i++; }
    if($argv[$i] === '--pass' && isset($argv[$i+1])){ $cli_pass = $argv[$i+1]; $i++; }
}

// credentials: CLI overrides env
$wigle_user = $cli_user ? $cli_user : (getenv('WIGLE_USER') ? getenv('WIGLE_USER') : null);
$wigle_pass = $cli_pass ? $cli_pass : (getenv('WIGLE_PASS') ? getenv('WIGLE_PASS') : null);

if($input === '' || preg_match('#^https?://#i',$input)) {
    $url = ($input === '' ? 'https://api.wigle.net/api/v2/cell/mccMnc' : $input);
    echo "Downloading JSON from: $url\n";
    // try curl first
    if(function_exists('curl_version')){
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        if($wigle_user !== null && $wigle_pass !== null){
            curl_setopt($ch, CURLOPT_USERPWD, $wigle_user.":".$wigle_pass);
        }
        $txt = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err = curl_error($ch);
        curl_close($ch);
        if($txt === false || $httpcode >= 400){ die("Failed to download JSON: HTTP $httpcode $err\n"); }
    } else {
        // fallback to file_get_contents with stream context
        $opts = array('http' => array('method' => 'GET','timeout'=>60,'header'=>"Accept: application/json\r\n"));
        if($wigle_user !== null && $wigle_pass !== null){
            $auth = base64_encode($wigle_user.":".$wigle_pass);
            $opts['http']['header'] .= "Authorization: Basic $auth\r\n";
        }
        $ctx = stream_context_create($opts);
        $txt = @file_get_contents($url, false, $ctx);
        if($txt === false) { die("Failed to download JSON via file_get_contents.\n"); }
    }
} else {
    $json_file = $input;
    echo "Reading JSON from local file: $json_file\n";
    if(!file_exists($json_file)) { die("JSON file not found: $json_file\n"); }
    $txt = file_get_contents($json_file);
}
$data = json_decode($txt, true);
if($data === null) { die("Could not parse JSON (json_last_error: ".json_last_error().")\n"); }

// Find array of records robustly
function find_records_array($d)
{
    if(isset($d['data']) && is_array($d['data'])) return $d['data'];
    if(isset($d['mccMnc']) && is_array($d['mccMnc'])) return $d['mccMnc'];
    if(array_values($d) === $d) return $d; // top-level numeric array

    $out = array();

    // Collect records from any nested arrays at first level (merge groups)
    foreach($d as $k => $v){
        if(!is_array($v) || empty($v)) continue;

        // If $v is a numeric array of records
        if(array_values($v) === $v){
            foreach($v as $rec){ if(is_array($rec)) $out[] = $rec; }
            continue;
        }

        // If $v is an associative map (e.g., mnc => record), gather values that are arrays
        $values = array_values($v);
        $all_values_are_arrays = true;
        foreach($values as $val){ if(!is_array($val)){ $all_values_are_arrays = false; break; } }
        if($all_values_are_arrays){
            foreach($v as $sub){ $out[] = $sub; }
            continue;
        }

        // Deeper nested: scan each child and merge any numeric-array children
        foreach($v as $sub){
            if(is_array($sub)){
                if(array_values($sub) === $sub){
                    foreach($sub as $rec){ if(is_array($rec)) $out[] = $rec; }
                } elseif(isset($sub['mcc']) && isset($sub['mnc'])){
                    $out[] = $sub;
                }
            }
        }
    }

    if(!empty($out)) return $out;
    return array();
}

$records = find_records_array($data);
if(empty($records)){
    echo "No records found in API response. Top-level keys:\n";
    if(is_array($data)){
        foreach(array_keys($data) as $k) echo " - $k\n";
    } else {
        echo " (non-array response)\n";
    }
    echo "Response preview (first 2048 bytes):\n".substr($txt,0,2048)."\n";
    die("Aborting: no records to process.\n");
}

// Diagnostic: show how many records we detected and a sample
$record_count = count($records);
echo "Detected records: $record_count\n";
if($record_count > 0){
    $keys = array_keys($records);
    $preview_keys = array_slice($keys, 0, 20);
    echo "Record keys (first 20): ".implode(", ", $preview_keys)."\n";
    $first = reset($records);
    $first_key = key($records);
    $sample = json_encode($first, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
    if($sample === false || $sample === null) { $sample = 'null'; }
    echo "First record key: $first_key\n";
    echo "First record preview:\n".substr($sample,0,2048)."\n";
}

$count = 0; $inserted = 0; $updated = 0; $skipped = 0;

foreach($records as $rec) {
    $mcc = get_val($rec, array('mcc','mccInt','mcc_int'));
    $mnc = get_val($rec, array('mnc','mncInt','mnc_int'));
    $mcc_int = get_val($rec, array('mccInt','mcc_int'));
    $mnc_int = get_val($rec, array('mncInt','mnc_int'));
    $iso = get_val($rec, array('iso','countryCode','country_code'));
    $country = get_val($rec, array('country','countryName'));
    $country_code = get_val($rec, array('countryCode','country_code'));
    $network = get_val($rec, array('network','brand','operator','name'));

    if($mcc === null || $mnc === null) { $skipped++; continue; }

    // normalize
    $mcc = (int)$mcc;
    $mnc_str = (string)$mnc;
    $mnc_trim = ltrim($mnc_str, "0"); if($mnc_trim === '') $mnc_trim = '0';
    $mcc_int = $mcc_int !== null ? (int)$mcc_int : $mcc;
    $mnc_int = $mnc_int !== null ? (int)$mnc_int : (int)$mnc_str;
    // Truncate fields to match DB column sizes to avoid SQL Server truncation errors
    // Allow storing slightly longer MNC strings (up to 8 chars) in DB
    $mnc_str = mb_substr($mnc_str, 0, 8);
    $network = $network !== null ? mb_substr($network,0,72) : null;
    $country = $country !== null ? mb_substr($country,0,33) : null;
    $iso = $iso !== null ? mb_substr($iso,0,3) : null;
    $country_code = $country_code !== null ? mb_substr($country_code,0,4) : null;

    // attempt to find existing row by mcc and mnc (try exact and padded)
    $found = false;
    $sql = "SELECT carrier_id FROM cell_carriers WHERE mcc = :mcc AND mnc = :mnc";
    $prep = $dbcore->sql->conn->prepare($sql);
    $prep->bindParam(':mcc', $mcc, PDO::PARAM_INT);
    $prep->bindParam(':mnc', $mnc_str, PDO::PARAM_STR);
    $prep->execute();
    $row = $prep->fetch(2);
    if($row && isset($row['carrier_id'])) { $carrier_id = $row['carrier_id']; $found = true; }
    else {
        // try padded mnc to 3 digits
        $mnc_padded = str_pad($mnc_trim, 3, '0', STR_PAD_LEFT);
        if($mnc_padded !== $mnc_str) {
            $prep2 = $dbcore->sql->conn->prepare($sql);
            $prep2->bindParam(':mcc', $mcc, PDO::PARAM_INT);
            $prep2->bindParam(':mnc', $mnc_padded, PDO::PARAM_STR);
            $prep2->execute();
            $row2 = $prep2->fetch(2);
            if($row2 && isset($row2['carrier_id'])) { $carrier_id = $row2['carrier_id']; $found = true; }
        }
    }

    // Prefer DB-specific atomic upserts when available
    try {
        if($dbcore->sql->service == 'mysql') {
            // Note: requires a UNIQUE index on (mcc,mnc) to prevent duplicates
            $sql_upsert = "INSERT INTO cell_carriers (mcc, mcc_int, mnc, mnc_int, iso, country, country_code, network) VALUES (:mcc, :mcc_int, :mnc, :mnc_int, :iso, :country, :country_code, :network)"
                . " ON DUPLICATE KEY UPDATE mcc_int=VALUES(mcc_int), mnc_int=VALUES(mnc_int), iso=VALUES(iso), country=VALUES(country), country_code=VALUES(country_code), network=VALUES(network)";
            $stmt = $dbcore->sql->conn->prepare($sql_upsert);
            $stmt->bindParam(':mcc', $mcc, PDO::PARAM_INT);
            $stmt->bindParam(':mcc_int', $mcc_int, PDO::PARAM_INT);
            $stmt->bindParam(':mnc', $mnc_str, PDO::PARAM_STR);
            $stmt->bindParam(':mnc_int', $mnc_int, PDO::PARAM_INT);
            $stmt->bindParam(':iso', $iso);
            $stmt->bindParam(':country', $country);
            $stmt->bindParam(':country_code', $country_code);
            $stmt->bindParam(':network', $network);
            $ok = $stmt->execute();
            if($ok) {
                // We can't easily tell insert vs update here portably; assume success
                $inserted++;
            }
        } elseif($dbcore->sql->service == 'sqlsrv') {
            // Use MERGE for atomic upsert on SQL Server
            $merge = "MERGE INTO cell_carriers WITH (HOLDLOCK) AS target"
                . " USING (SELECT :mcc AS mcc, :mnc AS mnc) AS source (mcc, mnc)"
                . " ON target.mcc = source.mcc AND target.mnc = source.mnc"
                . " WHEN MATCHED THEN UPDATE SET mcc_int = :mcc_int_u, mnc_int = :mnc_int_u, iso = :iso_u, country = :country_u, country_code = :country_code_u, network = :network_u"
                . " WHEN NOT MATCHED THEN INSERT (mcc, mcc_int, mnc, mnc_int, iso, country, country_code, network) VALUES (:mcc_i, :mcc_int_i, :mnc_i, :mnc_int_i, :iso_i, :country_i, :country_code_i, :network_i);";

            $m = $dbcore->sql->conn->prepare($merge);
            // match params
            $m->bindParam(':mcc', $mcc, PDO::PARAM_INT);
            $m->bindParam(':mnc', $mnc_str, PDO::PARAM_STR);
            // update params
            $m->bindParam(':mcc_int_u', $mcc_int, PDO::PARAM_INT);
            $m->bindParam(':mnc_int_u', $mnc_int, PDO::PARAM_INT);
            $m->bindParam(':iso_u', $iso);
            $m->bindParam(':country_u', $country);
            $m->bindParam(':country_code_u', $country_code);
            $m->bindParam(':network_u', $network);
            // insert params
            $m->bindParam(':mcc_i', $mcc, PDO::PARAM_INT);
            $m->bindParam(':mcc_int_i', $mcc_int, PDO::PARAM_INT);
            $m->bindParam(':mnc_i', $mnc_str, PDO::PARAM_STR);
            $m->bindParam(':mnc_int_i', $mnc_int, PDO::PARAM_INT);
            $m->bindParam(':iso_i', $iso);
            $m->bindParam(':country_i', $country);
            $m->bindParam(':country_code_i', $country_code);
            $m->bindParam(':network_i', $network);
            $m->execute();
            // SQL Server MERGE doesn't give simple inserted/updated counts here; assume success
            $inserted++;
        } else {
            // fallback: existing select then update/insert logic
            if($found) {
                $usql = "UPDATE cell_carriers SET mcc_int = :mcc_int, mnc_int = :mnc_int, iso = :iso, country = :country, country_code = :country_code, network = :network WHERE carrier_id = :cid";
                $up = $dbcore->sql->conn->prepare($usql);
                $up->bindParam(':mcc_int', $mcc_int);
                $up->bindParam(':mnc_int', $mnc_int);
                $up->bindParam(':iso', $iso);
                $up->bindParam(':country', $country);
                $up->bindParam(':country_code', $country_code);
                $up->bindParam(':network', $network);
                $up->bindParam(':cid', $carrier_id, PDO::PARAM_INT);
                $up->execute();
                $updated++;
            } else {
                $isql = "INSERT INTO cell_carriers (mcc, mcc_int, mnc, mnc_int, iso, country, country_code, network) VALUES (:mcc, :mcc_int, :mnc, :mnc_int, :iso, :country, :country_code, :network)";
                $ins = $dbcore->sql->conn->prepare($isql);
                $ins->bindParam(':mcc', $mcc, PDO::PARAM_INT);
                $ins->bindParam(':mcc_int', $mcc_int, PDO::PARAM_INT);
                $ins->bindParam(':mnc', $mnc_str, PDO::PARAM_STR);
                $ins->bindParam(':mnc_int', $mnc_int, PDO::PARAM_INT);
                $ins->bindParam(':iso', $iso);
                $ins->bindParam(':country', $country);
                $ins->bindParam(':country_code', $country_code);
                $ins->bindParam(':network', $network);
                $ins->execute();
                $inserted++;
            }
        }
    } catch(Exception $e) {
        echo "DB error: ".$e->getMessage()."\n";
    }
    $count++;
}

echo "Processed: $count, Inserted: $inserted, Updated: $updated, Skipped: $skipped\n";

?>
