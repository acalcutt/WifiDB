<?php
global $switches;
$switches = array('extras'=>'cli','screen'=>"CLI");

require( '../daemon/config.inc.php' );
require( $daemon_config['wifidb_install']."/lib/init.inc.php" );

$dbcore->verbose = 1;
$dbcore->named = 1;
$username = "pferland2";
echo "Test login:\r\n";
var_dump($dbcore->sec->Login($username, "wires"));

$sql = "SELECT * FROM `wifi`.`user_login_hashes` WHERE `username` = ?";
$prep = $dbcore->sql->conn->prepare($sql);
$prep->bindParam(1, $username, PDO::PARAM_STR);
$prep->execute();
$array = $prep->fetch(2);

$hashed_pass = $array['hash'];
$salt = $array['salt'];
echo "Test login check with logged in data";
var_dump($dbcore->sec->APILoginCheck($username, $hashed_pass, $salt, 0));

?>