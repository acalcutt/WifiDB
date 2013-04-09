<?php
global $switches;
$switches = array('extras'=>'cli','screen'=>"CLI");

require( '../daemon/config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->verbose = 1;
$dbcore->named = 1;
$username = "pferland2";
$password = "wires";
echo "Test login:\r\n";
var_dump($dbcore->sec->Login($username, $password));
var_dump($dbcore->sec->username);
var_dump($dbcore->sec->pass_hash);

echo "-----
    get data from user_login_hashes\r\n";
$sql = "SELECT * FROM `wifi`.`user_login_hashes` WHERE `username` = ?";
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $dbcore->sec->username, PDO::PARAM_STR);
$prep->execute();
$array = $prep->fetch(2);
var_dump($array);

$hashed_pass = $array['hash'];

echo "-----
    Test login check with logged in data\r\n";
var_dump(crypt($dbcore->sec->pass_hash, $hashed_pass));

var_dump($dbcore->sec->LoginCheck($dbcore->sec->username, $hashed_pass, 0));
var_dump($dbcore->sec->login_val);


?>
