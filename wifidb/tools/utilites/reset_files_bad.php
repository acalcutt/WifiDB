<?php
define("SWITCH_SCREEN", "CLI");
define("SWITCH_EXTRAS", "daemon");

if(!(require(dirname(__FILE__).'/../daemon.config.inc.php'))){die("You need to create and configure your config.inc.php file in the [tools dir]/daemon/config.inc.php");}
if($daemon_config['wifidb_install'] == ""){die("You need to edit your daemon config file first in: [tools dir]/daemon/config.inc.php");}
require $daemon_config['wifidb_install']."/lib/init.inc.php";

$dbcore->verbosed("Resetting files_bad entries to files_tmp");

echo "Enter the files_bad ID to reset: ";
$handle = fopen ("php://stdin","r");
$bad_id = trim(fgets($handle));
if($bad_id == ''){
    echo "No id entered, exiting!\n";
    exit;
}

// Get hash for the bad file
if($dbcore->sql->service == "mysql")
    {$sql = "SELECT id, file_name, file_orig, file_user, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type, error_msg FROM files_bad WHERE id = ? LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
    {$sql = "SELECT TOP 1 [id], [file_name], [file_orig], [file_user], [otherusers], [notes], [title], [size], [file_date], [hash], [converted], [prev_ext], [type], [error_msg] FROM [files_bad] WHERE [id] = ?";}
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $bad_id, PDO::PARAM_INT);
$prep->execute();
$file_bad = $prep->fetch(PDO::FETCH_ASSOC);
if(!$file_bad){
    echo "files_bad id $bad_id not found\n";
    exit;
}
$hash = $file_bad['hash'];

// Check if file already exists in files
if($dbcore->sql->service == "mysql")
    {$sql = "SELECT id FROM files WHERE hash = ? LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
    {$sql = "SELECT TOP 1 [id] FROM [files] WHERE [hash] = ?";}
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $hash, PDO::PARAM_STR);
$prep->execute();
$exists = $prep->fetch(PDO::FETCH_ASSOC);
if($exists && $exists['id']){
    echo "File with same hash already exists in 'files' (id={$exists['id']}). Not re-adding to import queue.\n";
    echo "If you want to force re-import, remove the existing files row first.\n";
    exit;
}

// Check files_tmp to avoid duplicate tmp entries
if($dbcore->sql->service == "mysql")
    {$sql = "SELECT id FROM files_tmp WHERE hash = ? LIMIT 1";}
else if($dbcore->sql->service == "sqlsrv")
    {$sql = "SELECT TOP 1 [id] FROM [files_tmp] WHERE [hash] = ?";}
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $hash, PDO::PARAM_STR);
$prep->execute();
$tmp_exists = $prep->fetch(PDO::FETCH_ASSOC);
if($tmp_exists && $tmp_exists['id']){
    echo "A files_tmp entry with same hash already exists (id={$tmp_exists['id']}). Not re-adding.\n";
    exit;
}

// Insert into files_tmp
$retry = true;
while($retry){
    try{
        $sql = "INSERT INTO files_tmp (file_user, file_name, file_orig, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type) SELECT file_user, file_name, file_orig, otherusers, notes, title, size, file_date, hash, converted, prev_ext, type FROM files_bad WHERE id = ?";
        $res = $dbcore->sql->conn->prepare($sql);
        $res->bindParam(1, $bad_id, PDO::PARAM_INT);
        $res->execute();
        $retry = false;
    } catch (Exception $e){
        $retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
    }
}

// Delete files_bad row
$retry = true;
while($retry){
    try{
        if($dbcore->sql->service == "mysql")
            {$sql = "DELETE FROM files_bad WHERE id = ?";}
        else if($dbcore->sql->service == "sqlsrv")
            {$sql = "DELETE FROM [files_bad] WHERE [id] = ?";}
        $res = $dbcore->sql->conn->prepare($sql);
        $res->bindParam(1, $bad_id, PDO::PARAM_INT);
        $res->execute();
        $retry = false;
    } catch (Exception $e){
        $retry = $dbcore->sql->isPDOException($dbcore->sql->conn, $e);
    }
}

echo "Moved files_bad id $bad_id to files_tmp and removed from files_bad. Daemon will pick it up for import.\n";

?>