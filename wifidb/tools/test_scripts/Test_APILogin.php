<?php
define("SWITCH_SCREEN", "cli");
define("SWITCH_EXTRAS", "cli");
error_reporting("E_ALL");

require('../config.inc.php');
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );


$dbcore->verbose = 1;
$dbcore->named = 1;
$username = "pferland";

echo "-----
    get data from user_login_hashes\r\n";
$sql = "SELECT `apikey` FROM `wifi`.`user_info` WHERE `username` = ?";
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $dbcore->sec->username, PDO::PARAM_STR);
$prep->execute();
$array = $prep->fetch(2);
var_dump($array);

$_REQUEST['apikey'] = $array['apikey'];

echo "-----
    Test API Login Check with UserName and API Key\r\n";

var_dump($dbcore->sec->ValidateAPIKey());
var_dump($dbcore->sec->login_val);